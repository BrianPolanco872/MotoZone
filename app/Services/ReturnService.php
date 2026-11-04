<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    /**
     * @param  array{sale_id: int, reason: string, items: array<int, array{sale_item_id: int, quantity: int}>}  $data
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

                if ($item['quantity'] > $saleItem->availableReturnQuantity()) {
                    throw ValidationException::withMessages([
                        'items' => "No puedes devolver más de {$saleItem->availableReturnQuantity()} de «{$saleItem->product_name}».",
                    ]);
                }

                $lineTotal = round((float) $saleItem->unit_price * $item['quantity'], 2);
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
        return DB::transaction(function () use ($return, $user): SaleReturn {
            $return = SaleReturn::query()
                ->whereKey($return->getKey())
                ->lockForUpdate()
                ->with('items.saleItem.product', 'sale')
                ->firstOrFail();

            if (! $return->isPending()) {
                throw ValidationException::withMessages(['return' => 'Esta devolución ya fue procesada.']);
            }

            $refundTotal = 0.0;

            foreach ($return->items as $returnItem) {
                $saleItem = SaleItem::query()->whereKey($returnItem->sale_item_id)->lockForUpdate()->firstOrFail();

                if ($returnItem->quantity > $saleItem->availableReturnQuantity()) {
                    throw ValidationException::withMessages([
                        'items' => "La devolución de «{$saleItem->product_name}» excede lo vendido.",
                    ]);
                }

                $saleItem->increment('returned_quantity', $returnItem->quantity);
                $refundTotal += (float) $returnItem->line_total;

                $product = $saleItem->product;

                if ($returnItem->restock && $product && $product->track_inventory) {
                    $stockBefore = $product->stock;
                    $stockAfter = $stockBefore + $returnItem->quantity;

                    $product->update(['stock' => $stockAfter]);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'sale_id' => $return->sale_id,
                        'type' => StockMovement::TYPE_RETURN,
                        'quantity' => $returnItem->quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'note' => "Devolución venta {$return->sale->folio}",
                    ]);
                }
            }

            CashMovement::create([
                'user_id' => $user->id,
                'sale_id' => $return->sale_id,
                'type' => CashMovement::TYPE_OUT,
                'category' => 'refund',
                'amount' => round($refundTotal, 2),
                'concept' => "Devolución venta {$return->sale->folio}",
                'happened_at' => now(),
            ]);

            $return->update([
                'status' => SaleReturn::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'total' => round($refundTotal, 2),
            ]);

            return $return->fresh(['items.saleItem']);
        });
    }

    public function reject(SaleReturn $return, User $user): SaleReturn
    {
        if (! $return->isPending()) {
            throw ValidationException::withMessages(['return' => 'Esta devolución ya fue procesada.']);
        }

        $return->update([
            'status' => SaleReturn::STATUS_REJECTED,
            'approved_by' => $user->id,
            'rejected_at' => now(),
        ]);

        return $return;
    }

    /**
     * @param  array<int, array{sale_item_id: int, quantity: int}>  $items
     * @return array<int, array{sale_item_id: int, quantity: int}>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $saleItemId = (int) ($item['sale_item_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($saleItemId <= 0 || $quantity <= 0) {
                continue;
            }

            $normalized[] = [
                'sale_item_id' => $saleItemId,
                'quantity' => $quantity,
            ];
        }

        return $normalized;
    }
}
