<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomeCardRequest;
use App\Models\HomeCard;
use App\Models\HomePage;
use App\Models\Product;
use App\Services\GalleryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class HomeCardController extends Controller
{
    public function store(HomeCardRequest $request, GalleryService $gallery): RedirectResponse
    {
        $section = $request->validated('section');
        $newPath = null;
        $newGallery = [];

        try {
            $newPath = $this->storeMainImage($request);
            $newGallery = $gallery->store($request->file('gallery', []), 'home/cards');
            if ($request->filled('gallery_url')) {
                $newGallery[] = $request->validated('gallery_url');
            }

            $createdCard = DB::transaction(function () use ($request, $section, $newPath, $newGallery, $gallery): HomeCard {
                HomePage::lockForEditing();
                $position = min(4294967295, (int) HomeCard::query()->where('section', $section)->max('sort_order') + 1);
                $data = $this->payloadWithRemoved($request, $newPath, $newGallery, [], $gallery, $section)[0];

                if ($section === 'categories') {
                    $data['slug'] = $this->categorySlug($data['title']);
                    if ($data['is_active']) {
                        $this->prependCategory();
                        $position = 0;
                    }
                }

                return HomeCard::query()->create([
                    ...$data,
                    'section' => $section,
                    'sort_order' => $position,
                ]);
            }, 3);

            $this->copyImageToProduct($createdCard->product_id, $newPath);
        } catch (Throwable $exception) {
            $this->cleanup($newPath, $newGallery, $gallery);

            throw $exception;
        }

        return $this->back($section, 'Card agregada.', $createdCard->id);
    }

    public function update(HomeCardRequest $request, HomeCard $card, GalleryService $gallery): RedirectResponse
    {
        $newPath = null;
        $newGallery = [];
        $removed = [];

        try {
            $newPath = $this->storeMainImage($request);
            $newGallery = $gallery->store($request->file('gallery', []), 'home/cards');
            if ($request->filled('gallery_url')) {
                $newGallery[] = $request->validated('gallery_url');
            }

            $removed = DB::transaction(function () use ($request, $card, $newPath, $newGallery, $gallery): array {
                HomePage::lockForEditing();
                $current = HomeCard::query()->lockForUpdate()->findOrFail($card->id);
                $oldImage = $current->image_path;
                $galleryPaths = $current->gallery ?? [];
                [$data, $removedGallery] = $this->payloadWithRemoved($request, $newPath, $newGallery, $galleryPaths, $gallery, $current->section);
                $data['section'] = $current->section;
                if ($current->section === 'categories' && ! $current->is_active && $data['is_active']) {
                    $this->prependCategory($current->id);
                    $data['sort_order'] = 0;
                }
                $current->update($data);

                if (($newPath || $request->filled('image_url') || $request->boolean('remove_image')) && $oldImage) {
                    $removedGallery[] = $oldImage;
                }

                return $removedGallery;
            }, 3);

            $gallery->deleteAfterCommit($removed);
            $this->copyImageToProduct($request->validated('product_id'), $newPath);
        } catch (Throwable $exception) {
            $this->cleanup($newPath, $newGallery, $gallery);

            throw $exception;
        }

        return $this->back($card->section, 'Card actualizada.', $card->id);
    }

    public function destroy(HomeCard $card, GalleryService $gallery): RedirectResponse
    {
        $section = $card->section;

        $removed = DB::transaction(function () use ($card): array {
            HomePage::lockForEditing();
            $current = HomeCard::query()->lockForUpdate()->findOrFail($card->id);
            $files = array_values(array_filter([$current->image_path, ...($current->gallery ?? [])]));
            $current->delete();

            return $files;
        }, 3);

        $gallery->deleteAfterCommit($removed);

        return $this->back($section, 'Card eliminada.');
    }

    public function move(Request $request, HomeCard $card): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];
        $section = $card->section;

        DB::transaction(function () use ($card, $direction): void {
            HomePage::lockForEditing();
            $cards = HomeCard::query()->where('section', $card->section)
                ->lockForUpdate()->orderBy('sort_order')->orderBy('id')->get();

            foreach ($cards as $position => $item) {
                if ($item->sort_order !== $position) {
                    HomeCard::query()->whereKey($item->id)->update(['sort_order' => $position]);
                    $item->sort_order = $position;
                }
            }

            $index = $cards->search(fn (HomeCard $item): bool => $item->id === $card->id);
            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || $target < 0 || $target >= $cards->count()) {
                return;
            }

            $other = $cards[$target];
            HomeCard::query()->whereKey($card->id)->update(['sort_order' => $other->sort_order]);
            HomeCard::query()->whereKey($other->id)->update(['sort_order' => $cards[$index]->sort_order]);
        }, 3);

        return $this->back($section, 'Orden actualizado.', $card->id);
    }

    private function categorySlug(string $title): string
    {
        $base = rtrim(substr(Str::slug($title), 0, 160), '-') ?: 'categoria';
        $slug = $base;
        $suffix = 2;

        while (HomeCard::query()->where('slug', $slug)->exists()) {
            $ending = '-'.$suffix++;
            $slug = rtrim(substr($base, 0, 160 - strlen($ending)), '-').$ending;
        }

        return $slug;
    }

    private function prependCategory(?int $exceptId = null): void
    {
        $query = HomeCard::query()->section('categories');
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $categories = $query->ordered()->lockForUpdate()->get(['id', 'sort_order']);
        foreach ($categories as $index => $category) {
            $position = $index + 1;
            if ($category->sort_order !== $position) {
                HomeCard::query()->whereKey($category->id)->update(['sort_order' => $position]);
            }
        }
    }

    private function storeMainImage(HomeCardRequest $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('home/cards', 'public');

        if ($path === false) {
            throw new RuntimeException('No se pudo guardar la foto principal.');
        }

        return $path;
    }

    /**
     * Copia la foto de la card al producto vinculado, solo si el producto aún no tiene foto propia.
     */
    private function copyImageToProduct(int|string|null $productId, ?string $imagePath): void
    {
        if (! $productId || ! $imagePath) {
            return;
        }

        rescue(function () use ($productId, $imagePath): void {
            $product = Product::query()->find($productId);

            if (! $product || $product->image_path) {
                return;
            }

            $target = 'products/'.basename($imagePath);

            if (Storage::disk('public')->copy($imagePath, $target)) {
                $product->update(['image_path' => $target]);
            }
        });
    }

    /**
     * @param  array<int, string>  $currentGallery
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function payloadWithRemoved(HomeCardRequest $request, ?string $newPath, array $newGallery, array $currentGallery, GalleryService $gallery, string $section): array
    {
        $data = $request->safe()->only([
            'title', 'subtitle', 'description', 'price', 'sale_price', 'badge',
            'link_url', 'link_label', 'product_id', 'is_active',
        ]);

        if ($section === 'categories') {
            $data = [...$data, ...$request->safe()->only(['icon'])];
        }

        $removed = array_values(array_intersect($currentGallery, $request->input('remove_gallery', [])));
        $data['gallery'] = array_merge($gallery->keep($currentGallery, $removed), $newGallery);

        if ($newPath) {
            $data['image_path'] = $newPath;
            $data['image_url'] = null;
        } elseif ($request->filled('image_url')) {
            $data['image_path'] = null;
            $data['image_url'] = $request->validated('image_url');
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
            $data['image_url'] = null;
        }

        return [$data, $removed];
    }

    /** @param array<int, string> $newGallery */
    private function cleanup(?string $newPath, array $newGallery, GalleryService $gallery): void
    {
        if ($newPath) {
            rescue(fn () => Storage::disk('public')->delete($newPath));
        }

        $gallery->delete($newGallery);
    }

    private function back(string $section, string $message, ?int $cardId = null): RedirectResponse
    {
        $parameters = $section === 'categories' && $cardId !== null ? ['family' => $cardId] : [];

        return redirect()->to(route('admin.home.edit', $parameters).'#'.(HomeCard::ANCHORS[$section] ?? 'presentacion'))
            ->with('status', $message);
    }
}
