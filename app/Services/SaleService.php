<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly FolioGenerator $folios,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Create a paid or pending sale from the POS cart.
     *
     * @param  array{items: array<int, array{product_id: int, quantity: int, unit_price?: float|null, wholesale?: bool}>, status?: string, amount_paid?: float|null, idempotency_key: string, notes?: string|null}  $data
     */
    public function create(User $user, array $data): Sale
    {
        $status = $data['status'] ?? Sale::STATUS_PAID;
        $items = $this->normalizeItems($data['items'] ?? []);

        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Agrega al menos un producto a la venta.']);
        }

        if (! in_array($status, [Sale::STATUS_PAID, Sale::STATUS_PENDING], true)) {
            throw ValidationException::withMessages(['status' => 'El estado de la venta no es válido.']);
        }

        $existing = Sale::query()->where('idempotency_key', $data['idempotency_key'])->first();

        if ($existing) {
            return $existing->load('items');
        }

        return DB::transaction(function () use ($user, $data, $items, $status): Sale {
            $existing = Sale::query()->where('idempotency_key', $data['idempotency_key'])->first();

            if ($existing) {
                return $existing->load('items');
            }

            $products = $this->lockProducts($items);
            $preparedItems = $this->prepareItems($items, $products, $user, $status);

            $totals = $this->totals($preparedItems);
            $amountPaid = $status === Sale::STATUS_PAID
                ? round((float) ($data['amount_paid'] ?? $totals['total']), 2)
                : 0.0;

            if ($status === Sale::STATUS_PAID && $amountPaid < $totals['total']) {
                throw ValidationException::withMessages(['amount_paid' => 'El pago recibido es menor al total de la venta.']);
            }

            $sale = Sale::create([
                'user_id' => $user->id,
                'status' => $status,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['tax'],
                'total' => $totals['total'],
                'amount_paid' => $amountPaid,
                'change_amount' => round(max(0, $amountPaid - $totals['total']), 2),
                'idempotency_key' => $data['idempotency_key'],
                'paid_at' => $status === Sale::STATUS_PAID ? now() : null,
                'notes' => $data['notes'] ?? null,
            ]);

            $sale->update(['folio' => $this->folios->forId($sale->id)]);

            $this->logPriceChanges($sale, $user, $preparedItems);

            foreach ($preparedItems as $item) {
                SaleItem::create([...$item, 'sale_id' => $sale->id]);
            }

            if ($status === Sale::STATUS_PAID) {
                $this->applyStockAndCash($sale, $preparedItems, $products, $user);
            }

            return $sale->load('items');
        });
    }

    /**
     * Charge a sale that was left pending.
     */
    public function chargePending(Sale $sale, User $user, ?float $amountPaid = null, ?string $paymentMethod = null): Sale
    {
        return DB::transaction(function () use ($sale, $user, $amountPaid, $paymentMethod): Sale {
            $sale = Sale::query()->whereKey($sale->getKey())->lockForUpdate()->firstOrFail();

            if (! $sale->isPending()) {
                throw ValidationException::withMessages(['sale' => 'Esta venta ya no está pendiente.']);
            }

            if (! $sale->items()->exists()) {
                throw ValidationException::withMessages(['sale' => 'La venta pendiente no tiene productos.']);
            }

            $items = $sale->items->map(fn (SaleItem $item): array => [
                'product_id' => (int) $item->product_id,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'wholesale' => false,
            ])->all();

            $products = $this->lockProducts($items);
            $this->assertStock($sale->items, $products);

            $total = (float) $sale->total;
            $paid = round($amountPaid ?? $total, 2);

            if ($paid < $total) {
                throw ValidationException::withMessages(['amount_paid' => 'El pago recibido es menor al total de la venta.']);
            }

            $sale->update([
                'status' => Sale::STATUS_PAID,
                'amount_paid' => $paid,
                'change_amount' => round($paid - $total, 2),
                'paid_at' => now(),
                'payment_method' => $paymentMethod ?? $sale->payment_method,
            ]);

            $preparedItems = $sale->items->map(fn (SaleItem $item): array => [
                'product_id' => $item->product_id,
                'category_id' => $item->category_id,
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'category_name' => $item->category_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'cost_price' => (float) $item->cost_price,
                'tax_rate' => (float) $item->tax_rate,
                'line_subtotal' => (float) $item->line_subtotal,
                'tax_amount' => (float) $item->tax_amount,
                'line_total' => (float) $item->line_total,
                'line_cost' => (float) $item->line_cost,
                'price_overridden' => $item->price_overridden,
            ])->all();

            $this->applyStockAndCash($sale, $preparedItems, $products, $user);

            return $sale->fresh(['items']);
        });
    }

    public function cancelPending(Sale $sale, User $user): Sale
    {
        return DB::transaction(function () use ($sale, $user): Sale {
            $sale = Sale::query()->whereKey($sale->getKey())->lockForUpdate()->firstOrFail();

            if (! $sale->isPending()) {
                throw ValidationException::withMessages(['sale' => 'Solo se pueden cancelar ventas pendientes.']);
            }

            $sale->update([
                'status' => Sale::STATUS_CANCELLED,
                'notes' => trim(($sale->notes ? $sale->notes.' | ' : '')."Cancelada por {$user->name}"),
            ]);

            return $sale;
        });
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_price?: float|null, wholesale?: bool}>  $items
     * @return array<int, array{product_id: int, quantity: int, unit_price?: float|null, wholesale?: bool}>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $normalized[] = [
                'product_id' => $productId,
                'quantity' => min($quantity, 9999),
                'unit_price' => isset($item['unit_price']) && is_numeric($item['unit_price'])
                    ? (float) $item['unit_price']
                    : null,
                'wholesale' => (bool) ($item['wholesale'] ?? false),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @return Collection<int, Product>
     */
    private function lockProducts(array $items)
    {
        $ids = array_values(array_unique(array_map(fn (array $item): int => $item['product_id'], $items)));

        return Product::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_price?: float|null, wholesale?: bool}>  $items
     * @param  Collection<int, Product>  $products
     * @return array<int, array<string, mixed>>
     */
    private function prepareItems(array $items, $products, User $user, string $status): array
    {
        $prepared = [];

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                throw ValidationException::withMessages(['items' => 'Uno de los productos ya no existe.']);
            }

            if (! $product->is_active) {
                throw ValidationException::withMessages(['items' => "El producto «{$product->name}» no está disponible."]);
            }

            if ($status === Sale::STATUS_PAID && $product->track_inventory && $product->stock < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => "No hay existencia suficiente de «{$product->name}». Disponible: {$product->stock}.",
                ]);
            }

            $pricing = $this->pricing->resolveUnitPrice(
                $product,
                $user,
                (bool) ($item['wholesale'] ?? false),
                $item['unit_price'] ?? null,
            );

            $amounts = $this->pricing->lineAmounts(
                $pricing['unit_price'],
                $item['quantity'],
                (float) $product->tax_rate,
            );

            $prepared[] = [
                'product_id' => $product->id,
                'category_id' => $product->category_id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'category_name' => $product->category?->name,
                'quantity' => $item['quantity'],
                'unit_price' => $pricing['unit_price'],
                'cost_price' => (float) $product->cost,
                'tax_rate' => (float) $product->tax_rate,
                'line_subtotal' => $amounts['line_subtotal'],
                'tax_amount' => $amounts['tax_amount'],
                'line_total' => $amounts['line_total'],
                'line_cost' => round((float) $product->cost * $item['quantity'], 2),
                'price_overridden' => $pricing['overridden'],
            ];
        }

        return $prepared;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, tax: float, total: float}
     */
    private function totals(array $items): array
    {
        return [
            'subtotal' => round(array_sum(array_column($items, 'line_subtotal')), 2),
            'tax' => round(array_sum(array_column($items, 'tax_amount')), 2),
            'total' => round(array_sum(array_column($items, 'line_total')), 2),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $preparedItems
     * @param  Collection<int, Product>  $products
     */
    private function applyStockAndCash(Sale $sale, array $preparedItems, $products, User $user): void
    {
        foreach ($preparedItems as $item) {
            $product = $products->get($item['product_id']);

            if (! $product || ! $product->track_inventory) {
                continue;
            }

            $stockBefore = $product->stock;
            $stockAfter = $stockBefore - (int) $item['quantity'];

            $product->update(['stock' => $stockAfter]);

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'sale_id' => $sale->id,
                'type' => StockMovement::TYPE_SALE,
                'quantity' => -1 * (int) $item['quantity'],
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'note' => "Venta {$sale->folio}",
            ]);
        }

        CashMovement::create([
            'user_id' => $user->id,
            'sale_id' => $sale->id,
            'type' => CashMovement::TYPE_IN,
            'category' => 'sale',
            'amount' => (float) $sale->total,
            'concept' => "Venta {$sale->folio}",
            'happened_at' => $sale->paid_at ?? now(),
        ]);
    }

    /**
     * @param  Collection<int, SaleItem>  $items
     * @param  Collection<int, Product>  $products
     */
    private function assertStock($items, $products): void
    {
        foreach ($items as $item) {
            $product = $products->get($item->product_id);

            if (! $product) {
                throw ValidationException::withMessages(['items' => "El producto «{$item->product_name}» ya no existe."]);
            }

            if ($product->track_inventory && $product->stock < $item->quantity) {
                throw ValidationException::withMessages([
                    'items' => "No hay existencia suficiente de «{$product->name}». Disponible: {$product->stock}.",
                ]);
            }
        }
    }

    /**
     * Registra auditoría cuando un producto se cobra con un precio modificado
     * respecto al catálogo (permiso pos.price.edit / cambiar precio).
     *
     * @param  array<int, array<string, mixed>>  $preparedItems
     */
    private function logPriceChanges(Sale $sale, User $user, array $preparedItems): void
    {
        $overridden = array_filter($preparedItems, fn (array $item): bool => ! empty($item['price_overridden']));

        if ($overridden === []) {
            return;
        }

        $this->audit->log([
            'user' => $user,
            'action' => AuditLog::ACTION_CHANGE_PRICE,
            'entity_type' => 'sale',
            'entity_id' => $sale->id,
            'before' => null,
            'after' => [
                'sale_id' => $sale->id,
                'folio' => $sale->folio,
                'overridden_items' => array_values(array_map(fn (array $item): array => [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'unit_price' => (float) $item['unit_price'],
                    'line_total' => (float) $item['line_total'],
                ], $overridden)),
            ],
        ]);
    }
}
