<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeProducts = Product::query()->active();
        $totalProducts = Product::query()->count();

        $categories = Category::query()
            ->withCount(['products' => fn (Builder $query) => $query->where('is_active', true)])
            ->orderByDesc('products_count')
            ->get();

        $metrics = [
            [
                'label' => 'Productos activos',
                'value' => $activeProducts->count(),
                'note' => "{$totalProducts} registrados en total",
                'icon' => 'inventory',
            ],
            [
                'label' => 'Valor del inventario',
                'value' => '$'.number_format((float) Product::query()->active()->selectRaw('COALESCE(SUM(price * stock), 0) as total')->value('total'), 2),
                'note' => 'Precio de lista por existencia',
                'icon' => 'chart',
            ],
            [
                'label' => 'Stock bajo',
                'value' => Product::query()->active()->whereBetween('stock', [1, Product::LOW_STOCK_THRESHOLD])->count(),
                'note' => 'Con 5 piezas o menos',
                'icon' => 'alert',
            ],
            [
                'label' => 'Agotados',
                'value' => Product::query()->active()->where('stock', 0)->count(),
                'note' => 'Requieren reposición',
                'icon' => 'x',
            ],
        ];

        $lowStockProducts = Product::query()
            ->active()
            ->where('stock', '<=', Product::LOW_STOCK_THRESHOLD)
            ->with('category')
            ->orderBy('stock')
            ->take(5)
            ->get();

        $recentProducts = Product::query()
            ->with('category')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'categories' => $categories,
            'lowStockProducts' => $lowStockProducts,
            'recentProducts' => $recentProducts,
        ]);
    }
}
