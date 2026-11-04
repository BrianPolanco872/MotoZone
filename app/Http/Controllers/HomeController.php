<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::query()
            ->withCount(['products' => fn (Builder $query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        $featuredProducts = Product::query()
            ->active()
            ->featured()
            ->with('category')
            ->latest()
            ->take(8)
            ->get();

        $stats = [
            'products' => Product::query()->active()->count(),
            'categories' => $categories->count(),
            'brands' => Product::query()->active()->distinct()->count('brand'),
        ];

        return view('home', [
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'stats' => $stats,
        ]);
    }
}
