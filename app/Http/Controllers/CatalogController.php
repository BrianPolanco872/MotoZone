<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class CatalogController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->loadMissing('kitItems');

        $relatedProducts = Product::query()
            ->with('kitItems')
            ->active()
            ->whereKeyNot($product->getKey())
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('catalog.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
