<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\Quantity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function __construct(private readonly DailyCutService $cuts) {}

    /**
     * @param  array{sale_id: int, reason: string, items: array<int, array{sale_item_id: int, quantity: int|float|string}>}  $data
     */
    public function request(User $user, array $data): SaleReturn
    {
        $sale = Sale::query()->with('items')->findOrFail($data['sale_id']);

        if (! $sale->isPaid()) {
            throw ValidationException::withMessages(['sale_id' => 'Solo se pueden devolver ventas pagadas.']);
        }

        $items = $this->normalizeItems($data['items'] ?? []);

        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Selecciona al menos un producto a devolver.']);
        }

        return DB::transaction(function () use ($user, $sale, $data, $items): SaleReturn {
            $saleItems = $sale->items->keyBy('id');
            $total = 0.0;
            $prepared = [];

            foreach ($items as $item) {
                $saleItem = $saleItems->get($item['sale_item_id']);

                if (! $saleItem) {
                    throw ValidationException::withMessages(['items' => 'Uno de los productos no pertenece a esta venta.']);
                }

                $this->validateReturnQuantity($saleItem, $item['quantity']);

                if ($item['quantity'] > $saleItem->availableReturnQuantity()) {
                    throw ValidationException::withMessages([
                        'items' => "No puedes devolver más de {$saleItem->availableReturnQuantity()} de «{$saleItem->product_name}».",
                    ]);
                }

                $lineTotal = $this->refundAmount($saleItem, $item['quantity']);
                $total += $lineTotal;

                $prepared[] = [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => (float) $saleItem->unit_price,
                    'line_total' => $lineTotal,
                    'restock' => true,
                ];
            }

            $return = SaleReturn::create([
                'sale_id' => $sale->id,
                'requested_by' => $user->id,
                'status' => SaleReturn::STATUS_PENDING,
                'reason' => trim((string) $data['reason']),
                'total' => round($total, 2),
            ]);

            foreach ($prepared as $item) {
                $return->items()->create($item);
            }

            return $return->load('items.saleItem');
        });
    }

    public function approve(SaleReturn $return, User $user): SaleReturn
    {
        $approvedAt = now();
        $date = $approvedAt->toDateString();

        return $this->cuts->withDayLock($date, function () use ($return, $user, $date, $approvedAt): SaleReturn {
            if (DailyCut::isDayClosed($date)) {
                throw ValidationException::withMessages(['return' => 'Este día ya está cerrado. No se pueden aprobar devoluciones.']);
            }

            $return = SaleReturn::query()
                ->whereKey($return->getKey())
                ->lockForUpdate()
                ->with('items.saleItem.product', 'sale')
                ->firstOrFail();

            if (! $return->isPending()) {
                throw ValidationException::withMessages(['return' => 'Esta devolución ya fue procesada.']);
            }

            $refundTotal = 0.0;
            $saleItems = SaleItem::query()->whereIn('id', $return->items->pluck('sale_item_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $productIds = [];
            foreach ($saleItems as $saleItem) {
                if ($saleItem->inventory_snapshot === null) {
                    $productIds[] = $saleItem->product_id;
                } else {
                    foreach ($saleItem->inventory_snapshot['effects'] ?? [] as $effect) {
                        $productIds[] = $effect['product_id'];
                    }
                }
            }
            $products = Product::query()->whereIn('id', array_filter($productIds))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($return->items as $returnItem) {
                $saleItem = $saleItems->get($returnItem->sale_item_id);

                if (! $saleItem) {
                    throw ValidationException::withMessages(['items' => 'Uno de los renglones vendidos ya no existe.']);
                }

                $this->validateReturnQuantity($saleItem, $returnItem->quantity);

                if ($returnItem->quantity > $saleItem->availableReturnQuantity()) {
                    throw ValidationException::withMessages([
                        'items' => "La devolución de «{$saleItem->product_name}» excede lo vendido.",
                    ]);
                }

                $lineTotal = $this->refundAmount($saleItem, $returnItem->quantity);
                $returnItem->update(['line_total' => $lineTotal]);
                $saleItem->update(['returned_quantity' => Quantity::number(
                    Quantity::milli($saleItem->returned_quantity) + Quantity::milli($returnItem->quantity),
                )]);
                $refundTotal += (float) $returnItem->line_total;

                if ($returnItem->restock) {
                    $this->restoreStock($saleItem, $returnItem->quantity, $products, $return, $user);
                }
            }

            CashMovement::create([
                'user_id' => $user->id,
                'sale_id' => $return->sale_id,
                'type' => CashMovement::TYPE_OUT,
                'category' => 'refund',
                'amount' => round($refundTotal, 2),
                'concept' => "Devolución venta {$return->sale->folio}",
                'happened_at' => $approvedAt,
            ]);

            $return->update([
                'status' => SaleReturn::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => $approvedAt,
                'total' => round($refundTotal, 2),
            ]);

            return $return->fresh(['items.saleItem']);
        });
    }

    public function reject(SaleReturn $return, User $user): SaleReturn
    {
        return DB::transaction(function () use ($return, $user): SaleReturn {
            $return = SaleReturn::query()->whereKey($return->getKey())->lockForUpdate()->firstOrFail();

            if (! $return->isPending()) {
                throw ValidationException::withMessages(['return' => 'Esta devolución ya fue procesada.']);
            }

            $return->update([
                'status' => SaleReturn::STATUS_REJECTED,
                'approved_by' => $user->id,
                'rejected_at' => now(),
            ]);

            return $return;
        });
    }

    /**
     * Devuelve artículos de una venta pagada al instante, reutilizando el flujo
     * de solicitud + autorización para no duplicar stock, caja ni auditoría.
     *
     * @param  array<int, array{sale_item_id: int, quantity: int|float}>  $items
     */
    public function returnPaidItemsNow(User $user, Sale $sale, array $items, string $reason): SaleReturn
    {
        $return = $this->request($user, [
            'sale_id' => $sale->id,
            'reason' => $reason,
            'items' => $items,
        ]);

        return $this->approve($return, $user);
    }

    public function returnSingleItemNow(User $user, Sale $sale, SaleItem $saleItem, int|float $quantity): SaleReturn
    {
        return $this->returnPaidItemsNow($user, $sale, [
            ['sale_item_id' => $saleItem->id, 'quantity' => $quantity],
        ], 'Devolución de artículo desde el POS');
    }

    public function cancelPaidSaleNow(User $user, Sale $sale): SaleReturn
    {
        $items = $sale->items
            ->filter(fn (SaleItem $item): bool => $item->availableReturnQuantity() > 0)
            ->map(fn (SaleItem $item): array => [
                'sale_item_id' => $item->id,
                'quantity' => $item->availableReturnQuantity(),
            ])->all();

        if ($items === []) {
            throw ValidationException::withMessages(['sale' => 'Esta venta no tiene artículos por devolver.']);
        }

        $return = $this->returnPaidItemsNow($user, $sale, $items, 'Cancelación de venta desde el POS');

        $sale->update([
            'status' => Sale::STATUS_CANCELLED,
            'notes' => trim(($sale->notes ? $sale->notes.' | ' : '')."Cancelada por {$user->name}"),
        ]);

        return $return->fresh();
    }

    /**
     * @param  array<int, array{sale_item_id: int, quantity: int|float|string}>  $items
     * @return array<int, array{sale_item_id: int, quantity: int|float}>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];
        $seen = [];

        foreach ($items as $item) {
            $saleItemId = (int) ($item['sale_item_id'] ?? 0);
            if (! Quantity::valid($item['quantity'] ?? null) || Quantity::milli($item['quantity']) > 9999000) {
                throw ValidationException::withMessages(['items' => 'La cantidad a devolver no es válida o supera tres decimales.']);
            }
            $quantity = Quantity::number(Quantity::milli($item['quantity']));

            if ($saleItemId <= 0 || $quantity <= 0) {
                continue;
            }

            if (isset($seen[$saleItemId])) {
                throw ValidationException::withMessages(['items' => 'Selecciona cada renglón una sola vez.']);
            }
            $seen[$saleItemId] = true;

            $normalized[] = [
                'sale_item_id' => $saleItemId,
                'quantity' => $quantity,
            ];
        }

        return $normalized;
    }

    private function validateReturnQuantity(SaleItem $saleItem, int|float $quantity): void
    {
        if (! Quantity::valid($quantity, $saleItem->saleMode() === Product::MODE_BULK) || $quantity <= 0) {
            throw ValidationException::withMessages(['items' => 'Solo los productos a granel permiten devoluciones fraccionarias.']);
        }
    }

    private function refundAmount(SaleItem $saleItem, int|float $quantity): float
    {
        $lineCents = (int) round((float) $saleItem->line_total * 100);
        $previousMilli = Quantity::milli($saleItem->returned_quantity);
        $returnedMilli = $previousMilli + Quantity::milli($quantity);
        $soldMilli = Quantity::milli($saleItem->quantity);

        if ($saleItem->inventory_snapshot === null) {
            $refunded = min($lineCents, (int) round((float) $saleItem->unit_price * $saleItem->returned_quantity * 100));

            return min(max(0, $lineCents - $refunded), (int) round((float) $saleItem->unit_price * $quantity * 100)) / 100;
        }

        $refunded = (int) round($lineCents * ($previousMilli / $soldMilli), 0, PHP_ROUND_HALF_UP);
        $cumulativeCents = $returnedMilli === $soldMilli
            ? $lineCents
            : (int) round($lineCents * ($returnedMilli / $soldMilli), 0, PHP_ROUND_HALF_UP);

        return max(0, min($lineCents - $refunded, $cumulativeCents - $refunded)) / 100;
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function restoreStock(SaleItem $saleItem, int|float $quantity, Collection $products, SaleReturn $return, User $user): void
    {
        $snapshot = $saleItem->inventory_snapshot;
        $effects = $snapshot['effects'] ?? [];
        if ($snapshot === null && ! $saleItem->is_custom) {
            $product = $products->get($saleItem->product_id);
            if ($product && $product->track_inventory && ! $product->isKit()) {
                $effects = [['product_id' => $product->id, 'quantity' => $saleItem->quantity]];
            }
        }

        foreach ($effects as $effect) {
            $product = $products->get($effect['product_id']);
            if (! $product) {
                continue;
            }

            $restoreMilli = $saleItem->saleMode() === Product::MODE_KIT
                ? intdiv(Quantity::milli($effect['quantity']), intdiv(Quantity::milli($saleItem->quantity), 1000)) * intdiv(Quantity::milli($quantity), 1000)
                : Quantity::milli($quantity);
            $stockBefore = $product->stock;
            $stockAfterMilli = Quantity::milli($stockBefore) + $restoreMilli;
            if ($stockAfterMilli > Quantity::milli(Quantity::MAX)) {
                throw ValidationException::withMessages(['items' => "La devolución supera el límite de existencias de «{$product->name}»."]);
            }
            $stockAfter = Quantity::number($stockAfterMilli);
            $product->update(['stock' => $stockAfter]);

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'sale_id' => $return->sale_id,
                'type' => StockMovement::TYPE_RETURN,
                'quantity' => Quantity::number($restoreMilli),
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'note' => "Devolución venta {$return->sale->folio}",
            ]);
        }
    }
}
