<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomeContentRequest;
use App\Models\HomeCard;
use App\Models\HomePage;
use App\Models\Product;
use App\Services\AuditLogService;
use App\Services\GalleryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class HomeContentController extends Controller
{
    public function edit(Request $request): View
    {
        $page = HomePage::current();
        $cards = HomeCard::query()->with(['product', 'linkedProducts'])->ordered()->get()->groupBy('section');
        $categories = $cards->get('categories', collect());
        $family = $request->old('section') === 'categories' && $request->old('card_id')
            ? $request->old('card_id') : $request->query('family');
        $selectedCategory = is_scalar($family)
            ? $categories->first(fn (HomeCard $card): bool => (string) $card->id === (string) $family)
            : null;
        $selectedCategory ??= $categories->first();

        return view('admin.home.edit', compact('page', 'cards', 'selectedCategory'));
    }

    public function update(HomeContentRequest $request, GalleryService $gallery): RedirectResponse
    {
        $hero = ! $request->has('block') || $request->block() === 'hero';
        $newPath = null;
        $newGallery = [];

        try {
            if ($hero && $request->hasFile('hero_image')) {
                $newPath = $request->file('hero_image')->store('home/hero', 'public');
                if ($newPath === false) {
                    throw new RuntimeException('No se pudo guardar la imagen principal.');
                }
            }

            if ($hero) {
                $newGallery = $gallery->store($request->file('gallery', []), 'home/hero');
                if ($request->filled('gallery_url')) {
                    $newGallery[] = $request->validated('gallery_url');
                }
            }

            $removedGallery = DB::transaction(function () use ($request, $hero, $newPath, $newGallery, $gallery): array {
                $page = HomePage::lockForEditing();
                $content = array_replace($page->content ?? [], $request->validated('content', []));
                $removed = [];

                if ($hero) {
                    if ($newPath) {
                        $page->hero_image_path = $newPath;
                        $content['hero_image_url'] = '';
                    } elseif ($request->filled('hero_image_url')) {
                        $page->hero_image_path = null;
                        $content['hero_image_url'] = $request->validated('hero_image_url');
                    } elseif ($request->boolean('remove_hero_image')) {
                        $page->hero_image_path = null;
                        $content['hero_image_url'] = '';
                    }

                    $current = $page->gallery ?? [];
                    $removed = array_values(array_intersect($current, $request->input('remove_gallery', [])));
                    $page->gallery = array_merge($gallery->keep($current, $removed), $newGallery);
                }

                $page->content = $content;
                $page->save();

                return $removed;
            }, 3);

            $gallery->deleteAfterCommit($removedGallery);
        } catch (Throwable $exception) {
            if ($newPath) {
                rescue(fn () => Storage::disk('public')->delete($newPath));
            }
            $gallery->delete($newGallery);

            throw $exception;
        }

        $anchor = $request->has('block') ? '#'.HomeContentRequest::BLOCK_ANCHORS[$request->block()] : '';

        return redirect()->to(route('admin.home.edit').$anchor)->with('status', 'Contenido de la home guardado.');
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $term = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $products = Product::query()->active()
            ->when($term !== '', fn (Builder $query) => $query->search($term))
            ->orderBy('name')->orderBy('id')->limit(10)->get();

        return response()->json(['products' => $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'stock' => $product->stock,
            'price' => $product->price,
            'image_url' => $product->image_path ? Storage::disk('public')->url($product->image_path) : null,
            'is_active' => $product->is_active,
        ])->all()]);
    }

    public function reset(Request $request, AuditLogService $auditLog): RedirectResponse
    {
        abort_unless($request->user()?->is_active && $request->user()?->hasRole('admin'), 403);
        $request->validate(['confirm' => ['required', 'accepted']]);

        DB::transaction(function () use ($auditLog): void {
            $page = HomePage::lockForEditing();
            $before = $page->only(['content', 'hero_image_path']);
            $defaults = HomePage::DEFAULT_CONTENT;
            unset($defaults['hero_image_alt']);
            $page->content = array_replace($page->content ?? [], $defaults);
            $page->save();
            $auditLog->log([
                'action' => 'restablecer_inicio',
                'entity_type' => HomePage::class,
                'entity_id' => $page->id,
                'before' => $before,
                'after' => $page->only(['content', 'hero_image_path']),
            ]);
        }, 3);

        return redirect()->route('admin.home.edit')->with('status', 'Valores iniciales restablecidos; fotos y cards conservadas.');
    }
}
