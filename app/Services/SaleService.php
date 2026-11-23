<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\Quantity;
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
     * @param  array{items: array<int, array{product_id?: int, quantity: int|float|string, unit_price?: float|null, wholesale?: bool, is_custom?: bool, custom_name?: string, custom_price?: float}>, status?: string, amount_paid?: float|null, idempotency_key: string, notes?: string|null, payment_method?: string}  $data
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
            $preparedItems = $this->prepareItems($items, $products, $user);

            if ($status === Sale::STATUS_PAID) {
                $preparedItems = $this->prepareStock($preparedItems, $products);
            }

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
        }, 3);
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
                'product_id' => $item->product_id,
                'sale_item_id' => $item->id,
                'is_custom' => $item->is_custom,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'sale_mode' => $item->sale_mode,
                'inventory_snapshot' => $item->inventory_snapshot,
            ])->all();

            $products = $this->lockProducts($items);
            $items = $this->prepareStock($items, $products);

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

            foreach ($items as $item) {
                $sale->items->firstWhere('id', $item['sale_item_id'])->update([
                    'inventory_snapshot' => $item['inventory_snapshot'],
                ]);
            }

            $this->applyStockAndCash($sale, $items, $products, $user);

            return $sale->fresh(['items']);
        }, 3);
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
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $index => $item) {
            if (! is_array($item) || ! Quantity::valid($item['quantity'] ?? null)
                || Quantity::milli($item['quantity']) < 1 || Quantity::milli($item['quantity']) > 9999000) {
                throw ValidationException::withMessages(["items.{$index}.quantity" => 'La cantidad debe ser positiva, no superar 9999 y tener como máximo tres decimales.']);
            }

            $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $quantity = Quantity::number(Quantity::milli($item['quantity']));
            $isCustom = (bool) ($item['is_custom'] ?? false);
            if ($isCustom) {
                $productId = null;
            }

            if ($isCustom && ! Quantity::valid($quantity, false)) {
                throw ValidationException::withMessages(["items.{$index}.quantity" => 'Los artículos libres se venden en cantidades enteras.']);
            }

            // Los artículos libres no requieren product_id; los del catálogo sí.
            if (! $isCustom && (! $productId || is_bool($item['product_id'] ?? null))) {
                throw ValidationException::withMessages(["items.{$index}.product_id" => 'Selecciona un producto válido.']);
            }

            $normalized[] = [
                'product_id' => $productId,
                'is_custom' => $isCustom,
                'custom_name' => $isCustom ? trim((string) ($item['custom_name'] ?? '')) : null,
                'custom_price' => $isCustom ? (float) ($item['custom_price'] ?? 0) : null,
                'quantity' => $quantity,
                'unit_price' => isset($item['unit_price']) && is_numeric($item['unit_price'])
                    ? (float) $item['unit_price']
                    : null,
                'wholesale' => (bool) ($item['wholesale'] ?? false),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<int, Product>
     */
    private function lockProducts(array $items): Collection
    {
        $ids = array_values(array_unique(array_filter(
            array_column($items, 'product_id'),
            fn (?int $id): bool => $id !== null && $id > 0,
        )));

        if ($ids === []) {
            return collect();
        }

        $componentIds = [];
        foreach ($items as $item) {
            foreach ($item['inventory_snapshot']['components'] ?? [] as $component) {
                $componentIds[] = $component['product_id'];
            }
        }

        $liveIds = array_column(array_filter($items, fn (array $item): bool => ! isset($item['inventory_snapshot'])), 'product_id');
        $componentIds = array_merge($componentIds, DB::table('product_kit_items')
            ->whereIn('kit_product_id', $liveIds)->pluck('component_product_id')->all());

        return Product::query()
            ->whereIn('id', array_unique([...$ids, ...$componentIds]))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  Collection<int, Product>  $products
     * @return array<int, array<string, mixed>>
     */
    private function prepareItems(array $items, Collection $products, User $user): array
    {
        $prepared = [];
        $recipes = DB::table('product_kit_items')
            ->whereIn('kit_product_id', array_column($items, 'product_id'))
            ->orderBy('kit_product_id')->orderBy('component_product_id')
            ->lockForUpdate()->get()->groupBy('kit_product_id');

        foreach ($items as $index => $item) {
            if (! empty($item['is_custom'])) {
                $unitPrice = round((float) ($item['custom_price'] ?? $item['unit_price'] ?? 0), 2);
                $amounts = $this->pricing->lineAmounts($unitPrice, $item['quantity'], 0.0);

                $prepared[] = [
                    'product_id' => null,
                    'category_id' => null,
                    'is_custom' => true,
                    'sale_mode' => Product::MODE_UNIT,
                    'inventory_snapshot' => null,
                    'custom_name' => $item['custom_name'],
                    'custom_price' => $unitPrice,
                    'product_name' => $item['custom_name'],
                    'sku' => null,
                    'barcode' => null,
                    'category_name' => null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'cost_price' => 0.0,
                    'tax_rate' => 0.0,
                    'line_subtotal' => $amounts['line_subtotal'],
                    'tax_amount' => $amounts['tax_amount'],
                    'line_total' => $amounts['line_total'],
                    'line_cost' => 0.0,
                    'price_overridden' => false,
                ];

                continue;
            }

            $product = $products->get($item['product_id']);

            if (! $product) {
                throw ValidationException::withMessages(['items' => 'Uno de los productos ya no existe.']);
            }

            if (! $product->is_active) {
                throw ValidationException::withMessages(['items' => "El producto «{$product->name}» no está disponible."]);
            }

            if (! Quantity::valid($item['quantity'], $product->isBulk())) {
                throw ValidationException::withMessages(["items.{$index}.quantity" => 'Este producto se vende en cantidades enteras.']);
            }

            $components = [];
            if ($product->isKit()) {
                foreach ($recipes->get($product->id, collect()) as $recipe) {
                    $component = $products->get($recipe->component_product_id);
                    if (! $component || ! $component->is_active || $component->isKit()
                        || $component->id === $product->id
                        || ! Quantity::valid($recipe->quantity, $component->isBulk())
                        || Quantity::milli($recipe->quantity) <= 0) {
                        throw ValidationException::withMessages(['items' => "Revisa el contenido de «{$product->name}»; un componente cambió o no está disponible. Vuelve a intentar la venta."]);
                    }
                    $components[] = [
                        'product_id' => $component->id,
                        'product_name' => $component->name,
                        'quantity' => Quantity::number(Quantity::milli($recipe->quantity)),
                        'sale_mode' => $component->saleMode(),
                    ];
                }
                if ($components === []) {
                    throw ValidationException::withMessages(['items' => "El paquete «{$product->name}» no tiene componentes."]);
                }
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
                'sale_mode' => $product->saleMode(),
                'inventory_snapshot' => [
                    'version' => 1,
                    'mode' => $product->saleMode(),
                    'kit_deduct_components' => $product->isKit() && $product->kit_deduct_components,
                    'components' => $components,
                    'effects' => null,
                ],
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
    private function applyStockAndCash(Sale $sale, array $preparedItems, Collection $products, User $user): void
    {
        foreach ($preparedItems as $item) {
            foreach ($item['inventory_snapshot']['effects'] ?? [] as $effect) {
                $product = $products->get($effect['product_id']);
                $stockBefore = $product->stock;
                $stockAfter = Quantity::number(Quantity::milli($stockBefore) - Quantity::milli($effect['quantity']));

                $product->update(['stock' => $stockAfter]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'sale_id' => $sale->id,
                    'type' => StockMovement::TYPE_SALE,
                    'quantity' => Quantity::number(-Quantity::milli($effect['quantity'])),
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'note' => "Venta {$sale->folio}",
                ]);
            }
        }

        // Navidad se maneja aparte: su importe nunca entra como efectivo de caja.
        $navidadAmount = round(array_sum(array_map(static function (array $item): float {
            return mb_strtolower(trim((string) ($item['category_name'] ?? ''))) === 'navidad'
                ? (float) $item['line_total']
                : 0.0;
        }, $preparedItems)), 2);
        $cashAmount = round((float) $sale->total - $navidadAmount, 2);

        if ($cashAmount > 0) {
            CashMovement::create([
                'user_id' => $user->id,
                'sale_id' => $sale->id,
                'type' => CashMovement::TYPE_IN,
                'category' => 'sale',
                'amount' => $cashAmount,
                'concept' => "Venta {$sale->folio}",
                'happened_at' => $sale->paid_at ?? now(),
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  Collection<int, Product>  $products
     * @return array<int, array<string, mixed>>
     */
    private function prepareStock(array $items, Collection $products): array
    {
        $quantities = [];

        foreach ($items as &$item) {
            if (! empty($item['is_custom'])) {
                continue;
            }

            $product = $products->get($item['product_id']);

            if (! $product) {
                throw ValidationException::withMessages(['items' => "El producto «{$item['product_name']}» ya no existe."]);
            }

            if (! $product->is_active) {
                throw ValidationException::withMessages(['items' => "El producto «{$item['product_name']}» no está disponible."]);
            }

            $snapshot = $item['inventory_snapshot'] ?? [
                'version' => 1, 'mode' => Product::MODE_UNIT,
                'kit_deduct_components' => false, 'components' => [], 'effects' => null,
            ];
            if ($product->saleMode() !== $snapshot['mode'] || ! Quantity::valid($item['quantity'], $snapshot['mode'] === Product::MODE_BULK)) {
                throw ValidationException::withMessages(['items' => "La modalidad de «{$product->name}» cambió. Revisa la venta pendiente."]);
            }

            $consumptions = [];
            if ($snapshot['mode'] === Product::MODE_KIT) {
                foreach ($snapshot['components'] as $component) {
                    $componentProduct = $products->get($component['product_id']);
                    if (! $componentProduct || ! $componentProduct->is_active || $componentProduct->isKit()
                        || $componentProduct->saleMode() !== $component['sale_mode']) {
                        throw ValidationException::withMessages(['items' => "Un componente de «{$product->name}» ya no existe o cambió de modalidad."]);
                    }
                    if ($snapshot['kit_deduct_components'] && $componentProduct->track_inventory) {
                        $consumptions[] = [
                            'product_id' => $componentProduct->id,
                            'product_name' => $component['product_name'],
                            'milli' => Quantity::milli($component['quantity']) * intdiv(Quantity::milli($item['quantity']), 1000),
                        ];
                    }
                }
            } elseif ($product->track_inventory) {
                $consumptions[] = ['product_id' => $product->id, 'product_name' => $item['product_name'], 'milli' => Quantity::milli($item['quantity'])];
            }

            $snapshot['effects'] = [];
            foreach ($consumptions as $consumption) {
                $target = $products->get($consumption['product_id']);
                $quantities[$target->id] = ($quantities[$target->id] ?? 0) + $consumption['milli'];
                if (Quantity::milli($target->stock) < $quantities[$target->id]) {
                    throw ValidationException::withMessages([
                        'items' => "No hay existencia suficiente de «{$target->name}». Disponible: {$target->stock}.",
                    ]);
                }
                $snapshot['effects'][] = [
                    'product_id' => $target->id,
                    'product_name' => $consumption['product_name'],
                    'quantity' => Quantity::number($consumption['milli']),
                ];
            }
            $item['inventory_snapshot'] = $snapshot;
        }

        unset($item);

        return $items;
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
