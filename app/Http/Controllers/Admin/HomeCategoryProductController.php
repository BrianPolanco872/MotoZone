<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeCard;
use App\Models\HomePage;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HomeCategoryProductController extends Controller
{
    public function store(Request $request, HomeCard $card): RedirectResponse
    {
        abort_unless($card->section === 'categories', 404);
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
        ]);

        DB::transaction(function () use ($card, $data): void {
            $current = $this->lockCategory($card);
            $product = Product::query()->active()->lockForUpdate()->findOrFail($data['product_id']);
            if (! $current->linkedProducts()->whereKey($product->id)->exists()) {
                $next = (int) $current->linkedProducts()->max('home_card_product.sort_order') + 1;
                $current->linkedProducts()->attach($product->id, ['sort_order' => min(4294967295, $next)]);
            }
        }, 3);

        return $this->back($card);
    }

    public function destroy(Request $request, HomeCard $card, Product $product): RedirectResponse
    {
        abort_unless($card->section === 'categories', 404);
        DB::transaction(function () use ($card, $product): void {
            $current = $this->lockCategory($card);
            abort_unless($current->linkedProducts()->whereKey($product->id)->exists(), 404);
            $current->linkedProducts()->detach($product->id);
        }, 3);

        return $this->back($card);
    }

    public function move(Request $request, HomeCard $card, Product $product): RedirectResponse
    {
        abort_unless($card->section === 'categories', 404);
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($card, $product, $direction): void {
            $current = $this->lockCategory($card);
            $products = $current->linkedProducts()->get();
            $index = $products->search(fn (Product $item): bool => $item->id === $product->id);
            abort_if($index === false, 404);
            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($target >= 0 && $target < $products->count()) {
                $other = $products[$target];
                $products[$target] = $products[$index];
                $products[$index] = $other;
            }

            foreach ($products as $position => $item) {
                $current->linkedProducts()->updateExistingPivot($item->id, ['sort_order' => $position]);
            }
        }, 3);

        return $this->back($card);
    }

    private function lockCategory(HomeCard $card): HomeCard
    {
        HomePage::lockForEditing();
        $current = HomeCard::query()->lockForUpdate()->findOrFail($card->id);
        abort_unless($current->section === 'categories', 404);

        return $current;
    }

    private function back(HomeCard $card): RedirectResponse
    {
        return redirect()->to(route('admin.home.edit', ['family' => $card->id]).'#categorias')
            ->with('status', 'Productos de la familia actualizados.');
    }
}
