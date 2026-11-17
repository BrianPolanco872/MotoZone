<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\TicketSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index(TicketSettings $tickets): View
    {
        $user = auth()->user();

        $hasInitialCash = CashMovement::query()
            ->where('user_id', $user?->id)
            ->where('category', 'initial')
            ->where('type', CashMovement::TYPE_IN)
            ->whereBetween('happened_at', [today()->startOfDay(), today()->endOfDay()])
            ->exists();

        return view('pos.index', [
            'settings' => $tickets->current(),
            'permissions' => [
                'sell' => $user?->can('pos.sell') ?? false,
                'price_edit' => $user?->can('pos.price.edit') ?? false,
                'discount' => $user?->can('pos.discount') ?? false,
                'cancel_sale' => $user?->can('pos.cancel.sale') ?? false,
                'wholesale' => $user?->can('pos.wholesale') ?? false,
                'cash' => $user?->can('pos.cash.manage') ?? false,
                'cash_view' => ($user?->can('pos.caja.view') || $user?->can('pos.cash.manage')) ?? false,
                'returns_request' => $user?->can('pos.returns.request') ?? false,
                'return_do' => $user?->can('pos.return.do') ?? false,
                'sales_day_any' => $user?->can('pos.sales.day.any') ?? false,
                'cut' => $user?->can('pos.cut.manage') ?? false,
                'cut_own' => $user?->can('pos.cut.own') ?? false,
                'tickets_any' => $user?->can('pos.tickets.print.any') ?? false,
                'products_view' => $user?->can('pos.products.view') ?? false,
                'products_edit' => $user?->can('pos.products.edit') ?? false,
                'day_closed' => DailyCut::isDayClosed(today()->toDateString()),
            ],
            'initial_cash_registered' => $hasInitialCash,
            'frequentProducts' => $this->frequentProducts(),
            'today' => [
                'sales' => Sale::query()
                    ->paid()
                    ->where('user_id', $user?->id)
                    ->whereBetween('paid_at', [today()->startOfDay(), today()->endOfDay()])
                    ->sum('total'),
                'tickets' => Sale::query()
                    ->paid()
                    ->where('user_id', $user?->id)
                    ->whereBetween('paid_at', [today()->startOfDay(), today()->endOfDay()])
                    ->count(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json(['products' => []]);
        }

        $products = Product::query()
            ->active()
            ->where(function ($query) use ($term): void {
                $query->where('barcode', $term)
                    ->orWhere('barcode2', $term)
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('brand', 'like', "%{$term}%");
            })
            ->orderByRaw('CASE WHEN barcode = ? OR barcode2 = ? THEN 0 ELSE 1 END', [$term, $term])
            ->orderBy('name')
            ->limit(12)
            ->get();

        return response()->json([
            'products' => $products->map(fn (Product $product): array => $this->productPayload($product))->all(),
        ]);
    }

    public function frequent(): JsonResponse
    {
        return response()->json(['products' => $this->frequentProducts()]);
    }

    public function show(Request $request, Sale $sale): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 403);

        $canSeeAny = $user->can('pos.sales.day.any');
        abort_unless($sale->user_id === $user->id || $canSeeAny, 403);
        abort_unless($sale->isPending(), 422, 'La venta ya no está pendiente.');

        $sale->load('items');

        return response()->json([
            'sale' => [
                'id' => $sale->id,
                'total' => (float) $sale->total,
                'payment_method' => $sale->payment_method,
                'items' => $sale->items->map(fn (SaleItem $item): array => [
                    'sale_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'is_custom' => $item->is_custom,
                    'custom_name' => $item->custom_name,
                    'custom_price' => $item->custom_price === null ? null : (float) $item->custom_price,
                    'name' => $item->product_name,
                    'sku' => $item->sku,
                    'barcode' => $item->barcode,
                    'unit_price' => (float) $item->unit_price,
                    'quantity' => $item->quantity,
                    'tax_rate' => (float) $item->tax_rate,
                    'stock' => null,
                    'track_inventory' => false,
                    'wholesale_price' => null,
                ])->all(),
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function frequentProducts(): array
    {
        $ids = SaleItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as sold'))
            ->whereNotNull('product_id')
            ->whereHas('sale', fn ($query) => $query->paid()->where('paid_at', '>=', now()->subDays(30)))
            ->groupBy('product_id')
            ->orderByDesc('sold')
            ->limit(8)
            ->pluck('product_id');

        $products = Product::query()
            ->active()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $ordered = $ids->map(fn ($id) => $products->get($id))->filter()->values();

        if ($ordered->isEmpty()) {
            $ordered = Product::query()->active()->featured()->orderBy('name')->limit(8)->get();
        }

        return $ordered->map(fn (Product $product): array => $this->productPayload($product))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'barcode2' => $product->barcode2,
            'price' => (float) $product->price,
            'wholesale_price' => $product->wholesalePrice(),
            'tax_rate' => (float) $product->tax_rate,
            'stock' => $product->stock,
            'min_stock' => $product->min_stock,
            'track_inventory' => $product->track_inventory,
            'brand' => $product->brand,
            'image_path' => $product->image_path,
            'in_stock' => $product->isInStock(),
            'low_stock' => $product->isLowStock(),
            'stock_label' => $product->stockLabel(),
        ];
    }
}
