@php
    $exists = $card->exists;
    $isProductLike = $section !== 'categories';
    $usesThousands = $section === 'products';
    $action = $exists ? route('admin.home.cards.update', $card) : route('admin.home.cards.store');
    $useOldInput = old('section') === $section && (string) old('card_id', '') === (string) ($card->id ?? '');
    $formValues = $card->only(['title', 'subtitle', 'description', 'price', 'sale_price', 'badge', 'link_url', 'link_label', 'product_id', 'is_active', 'slug', 'icon', 'sort_order', 'image_url']);
    if ($useOldInput) {
        $formValues = array_replace($formValues, old());
    }
    if ($usesThousands) {
        foreach (['price', 'sale_price'] as $field) {
            $value = $formValues[$field] ?? null;
            if (is_numeric($value)) {
                $formValues[$field] = number_format((float) $value, 2, '.', ',');
            }
        }
    }
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-4" data-card-form data-editor-form="{{ $exists ? 'Editar: '.$card->title : 'Nueva card' }}" @if ($useOldInput && old('title') !== null) data-editor-recovered @endif>
    @csrf
    @if ($exists) @method('PUT') @endif
    <input type="hidden" name="section" value="{{ $section }}">
    @if ($exists) <input type="hidden" name="card_id" value="{{ $card->id }}"> @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="{{ $section }}-{{ $card->id ?? 'new' }}-title" class="mb-1 block text-sm font-medium">{{ $isProductLike ? 'Título' : 'Categoría asignada (nombre a mostrar)' }}</label>
            <input id="{{ $section }}-{{ $card->id ?? 'new' }}-title" name="title" value="{{ $formValues['title'] ?? '' }}" required maxlength="160" class="mz-input h-11 w-full">
            @error('title')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>
    </div>

    @unless ($isProductLike)
        <div>
            <label for="categories-{{ $card->id ?? 'new' }}-icon" class="mb-1 block text-sm font-medium">Icono de la familia</label>
            <select id="categories-{{ $card->id ?? 'new' }}-icon" name="icon" class="mz-select h-11 w-full">
                @foreach (\App\Models\HomeCard::CATEGORY_ICONS as $icon => $iconLabel)
                    <option value="{{ $icon }}" @selected(($formValues['icon'] ?? 'tag') === $icon)>{{ $iconLabel }}</option>
                @endforeach
            </select>
            @error('icon')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>
    @endunless

    @if ($section !== 'products')
        <div>
            <label for="{{ $section }}-{{ $card->id ?? 'new' }}-description" class="mb-1 block text-sm font-medium">Descripción corta</label>
            <textarea id="{{ $section }}-{{ $card->id ?? 'new' }}-description" name="description" rows="2" maxlength="1000" class="mz-input w-full">{{ $formValues['description'] ?? '' }}</textarea>
            @error('description')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>
    @endif

    @if ($isProductLike)
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $section }}-{{ $card->id ?? 'new' }}-price" class="mb-1 block text-sm font-medium">Precio regular</label>
                <input id="{{ $section }}-{{ $card->id ?? 'new' }}-price" name="price" type="{{ $usesThousands ? 'text' : 'number' }}" @if ($usesThousands) inputmode="decimal" aria-describedby="{{ $section }}-{{ $card->id ?? 'new' }}-price-help" @else step="0.01" min="0" max="99999999" @endif value="{{ $formValues['price'] ?? '' }}" class="mz-input h-11 w-full">
                @error('price')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $section }}-{{ $card->id ?? 'new' }}-sale-price" class="mb-1 block text-sm font-medium">Precio de oferta <span class="font-normal text-mz-text-muted">(opcional)</span></label>
                <input id="{{ $section }}-{{ $card->id ?? 'new' }}-sale-price" name="sale_price" type="{{ $usesThousands ? 'text' : 'number' }}" @if ($usesThousands) inputmode="decimal" aria-describedby="{{ $section }}-{{ $card->id ?? 'new' }}-price-help" @else step="0.01" min="0" max="99999999" @endif value="{{ $formValues['sale_price'] ?? '' }}" class="mz-input h-11 w-full">
                @error('sale_price')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
        </div>

        @if ($usesThousands)
            <p id="{{ $section }}-{{ $card->id ?? 'new' }}-price-help" class="text-xs text-mz-text-muted">Usa coma para miles y punto para decimales: 1,299.50. También puedes escribir 1299.50. Máximo: 99,999,999.00.</p>
        @endif

    @endif

    @if ($isProductLike)
        <div class="space-y-2 rounded-lg border border-mz-border bg-mz-bg-surface-2/40 p-3" data-product-picker data-search-url="{{ route('admin.home.products.search') }}">
            <p class="text-sm font-medium">Vincular a un producto <span class="font-normal text-mz-text-muted">(opcional)</span></p>
            <p class="text-xs text-mz-text-muted">Solo se usa para el botón «Ver más». Al elegirlo se copian el título y el precio regular; la oferta se escribe en esta card.</p>
            <input type="hidden" name="product_id" value="{{ $formValues['product_id'] ?? '' }}" data-product-id>
            <div class="flex flex-wrap gap-2">
                <input type="search" class="mz-input h-11 min-w-0 flex-1" placeholder="Busca por nombre, SKU o código de barras" data-product-search aria-label="Buscar producto para la card">
                <x-button type="button" variant="secondary" data-product-clear>Quitar</x-button>
            </div>
            <ul class="divide-y divide-mz-border" data-product-results></ul>
            <p class="text-sm" data-product-chosen>
                @if (! empty($formValues['product_id']) && (string) $formValues['product_id'] !== (string) $card->product_id)
                    Producto seleccionado #{{ $formValues['product_id'] }}. Puedes buscarlo de nuevo para comprobar el vínculo.
                @elseif (! empty($formValues['product_id']) && $card->product)
                    <span class="font-medium">{{ $card->product->name }}</span> · {{ $card->product->sku }}
                @else
                    Sin producto vinculado.
                @endif
            </p>
            <p role="status" aria-live="polite" class="text-xs text-mz-text-muted" data-product-status></p>
            @error('product_id')<p class="text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>
    @endif

    @include('admin.partials.media-upload', [
        'mediaId' => $section.'-'.($card->id ?? 'new'),
        'label' => 'Fotos de la card',
        'mainName' => 'image',
        'mainUrl' => $card->imageUrl(),
        'mainSourceUrl' => $card->image_url ?? '',
        'mainUrlValue' => $formValues['image_url'] ?? '',
        'galleryUrlValue' => $useOldInput ? old('gallery_url', '') : '',
        'gallery' => $card->gallery ?? [],
        'removeMain' => 'remove_image',
        'removeGallery' => 'remove_gallery',
        'help' => 'La foto principal es la portada y las del carrusel se suman. Solo la foto principal subida desde un archivo puede copiarse a un producto vinculado; los enlaces se usan únicamente en la home.',
    ])

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="space-y-1">
            <input type="hidden" name="is_active" value="0">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" role="switch" name="is_active" value="1" @checked($formValues['is_active'] ?? true)>
                Card activa
            </label>
            @error('is_active')<p class="text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-2">
            <x-button type="button" variant="secondary" data-editor-cancel>Cancelar</x-button>
            <x-button type="submit">{{ $isProductLike ? ($exists ? 'Guardar card' : 'Añadir al bloque') : ($exists ? 'Guardar categoría' : 'Guardar y activar categoría') }}</x-button>
        </div>
    </div>
</form>
