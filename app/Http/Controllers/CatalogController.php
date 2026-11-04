<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('orden', 'relevancia');

        $categories = Category::query()
            ->withCount(['products' => fn (Builder $query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        $activeCategory = $categories->firstWhere('slug', $request->query('categoria'));

        $products = Product::query()
            ->active()
            ->with('category')
            ->search($search)
            ->when($activeCategory, fn (Builder $query) => $query->whereBelongsTo($activeCategory))
            ->when($sort === 'precio-asc', fn (Builder $query) => $query->orderBy('price'))
            ->when($sort === 'precio-desc', fn (Builder $query) => $query->orderByDesc('price'))
            ->when($sort === 'nombre', fn (Builder $query) => $query->orderBy('name'))
            ->when(! in_array($sort, ['precio-asc', 'precio-desc', 'nombre'], true), fn (Builder $query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        return view('catalog.index', [
            'products' => $products,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load('category');

        $relatedProducts = Product::query()
            ->active()
            ->whereBelongsTo($product->category)
            ->whereKeyNot($product->getKey())
            ->with('category')
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('catalog.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
