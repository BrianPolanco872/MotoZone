@include('admin.home.partials.block-settings', ['block' => 'categories', 'label' => 'Grid visual de familias en la home'])

<section class="cms-panel space-y-4" aria-labelledby="family-selector-heading">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 id="family-selector-heading" class="cms-label">Categoría activa a gestionar</h2>
            <p class="mt-1 text-xs text-mz-text-muted">Selecciona para vincular productos o editar sus detalles.</p>
        </div>
        <a href="#new-category-form" class="mz-button mz-button-primary min-h-11 text-xs" data-family-create><x-icon name="plus" class="size-4" /> Crear categoría</a>
    </div>

    @if ($categoryCards->isNotEmpty())
        <form action="{{ route('admin.home.edit') }}#categorias" method="GET">
            <label for="family-selector" class="sr-only">Familia de la home a gestionar</label>
            <select id="family-selector" name="family" class="mz-select min-h-12 w-full" data-auto-submit>
                @foreach ($categoryCards as $category)
                    <option value="{{ $category->id }}" @selected($selectedCategory?->id === $category->id)>{{ $category->title }} · {{ $category->linkedProducts->count() }} productos{{ $category->is_active ? '' : ' · Oculta' }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="mz-button mz-button-secondary mt-2">Seleccionar</button></noscript>
        </form>
        <div class="grid grid-cols-1 gap-2 xs:grid-cols-2 lg:grid-cols-3">
            @foreach ($categoryCards as $category)
                <a href="{{ route('admin.home.edit', ['family' => $category->id]) }}#categorias" @class(['flex min-w-0 items-center gap-3 rounded-xl border p-3 transition hover:border-mz-accent-primary', 'border-mz-accent-primary bg-mz-bg-surface-2' => $selectedCategory?->id === $category->id, 'border-transparent bg-mz-bg-primary' => $selectedCategory?->id !== $category->id]) @if ($selectedCategory?->id === $category->id) aria-current="true" @endif>
                    @if ($category->coverUrl())
                        <img src="{{ $category->coverUrl() }}" alt="" class="size-10 shrink-0 rounded-lg object-cover" loading="lazy">
                    @else
                        <span class="cms-icon-tile text-orange-300"><x-icon :name="$category->icon ?: 'tag'" /></span>
                    @endif
                    <span class="min-w-0"><span class="block truncate text-xs font-semibold">{{ $category->title }}</span><span class="mt-1 block text-[11px] text-mz-text-muted">{{ $category->linkedProducts->count() }} productos · {{ $category->is_active ? 'Visible' : 'Oculta' }}</span></span>
                </a>
            @endforeach
        </div>
    @else
        <p class="rounded-xl bg-mz-bg-primary p-4 text-sm text-mz-text-secondary">Todavía no hay categorías de portada. Crea la primera con una de las familias sugeridas.</p>
    @endif
    <p class="text-xs text-mz-text-muted">{{ $categoryCards->where('is_active', true)->count() }} categorías activas. Estas agrupaciones solo pertenecen a la home; no clasifican el inventario.</p>
</section>

<details id="new-category-form" class="cms-panel p-0" data-editor-add @if (old('section') === 'categories' && ! old('card_id')) open @endif>
    <summary class="cms-secondary flex min-h-20 cursor-pointer items-center justify-between gap-3 rounded-xl p-4">
        <span class="flex items-center gap-3"><span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-mz-accent-primary text-white"><x-icon name="plus" /></span><span><span class="cms-heading block text-base">Formulario: Nueva categoría</span><span class="mt-1 block text-xs text-mz-text-secondary">Define nombre, descripción e icono o foto.</span></span></span>
        <x-icon name="chevron-down" class="size-5 shrink-0" />
    </summary>
    <div class="space-y-4 p-4">
        <div>
            <p class="mb-2 text-xs font-semibold">Familias sugeridas</p>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Models\HomeCard::CATEGORY_PRESETS as $preset)
                    <button type="button" class="cms-secondary inline-flex min-h-11 items-center gap-2 rounded-lg px-3 text-xs" data-family-preset data-title="{{ $preset['title'] }}" data-icon="{{ $preset['icon'] }}"><x-icon name="{{ $preset['icon'] }}" class="size-4 text-orange-300" /> {{ $preset['title'] }}</button>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-mz-text-muted">Solo rellenan el formulario; tú decides qué guardar.</p>
        </div>
        @include('admin.home.partials.card-form', ['card' => $newCard, 'section' => 'categories'])
    </div>
</details>

@if ($selectedCategory)
    <section class="cms-panel space-y-4" data-editor-card aria-labelledby="selected-family-heading">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <span class="cms-icon-tile text-orange-300"><x-icon :name="$selectedCategory->icon ?: 'tag'" /></span>
                <div class="min-w-0"><h2 id="selected-family-heading" class="cms-heading truncate">{{ $selectedCategory->title }}</h2><p class="mt-1 text-xs text-mz-text-muted">/#{{ $selectedCategory->familyAnchor() }} · {{ $selectedCategory->is_active ? 'Visible en home' : 'Categoría oculta' }}</p></div>
            </div>
            <span class="cms-badge text-mz-success">{{ $selectedCategory->linkedProducts->count() }} productos asignados</span>
        </div>
        <details data-editor-card-details @if (old('section') === 'categories' && old('card_id') == $selectedCategory->id && ! old('product_id')) open @endif>
            <summary class="cursor-pointer py-2 text-sm font-semibold text-orange-300">Editar detalles, icono y fotos</summary>
            <div class="mt-4">@include('admin.home.partials.card-form', ['card' => $selectedCategory, 'section' => 'categories'])</div>
        </details>
        <div class="flex flex-wrap gap-2 border-t border-mz-border pt-3">
            @foreach (['up' => 'Subir categoría', 'down' => 'Bajar categoría'] as $direction => $label)
                <form action="{{ route('admin.home.cards.move', $selectedCategory) }}" method="POST">
                    @csrf
                    <input type="hidden" name="direction" value="{{ $direction }}">
                    <x-button type="submit" variant="secondary" size="sm" :disabled="($direction === 'up' && $categoryCards->first()->id === $selectedCategory->id) || ($direction === 'down' && $categoryCards->last()->id === $selectedCategory->id)"><x-icon name="{{ $direction === 'up' ? 'arrow-up' : 'arrow-down' }}" class="size-4" /> {{ $label }}</x-button>
                </form>
            @endforeach
            <form action="{{ route('admin.home.cards.destroy', $selectedCategory) }}" method="POST" data-home-confirm="¿Eliminar esta categoría de la home y sus fotos? Los productos del inventario no se borran.">
                @csrf
                @method('DELETE')
                <button type="submit" class="mz-button mz-button-danger text-xs" disabled><x-icon name="trash" class="size-4" /> Eliminar categoría</button>
            </form>
        </div>
    </section>

    <section class="cms-panel space-y-4" aria-labelledby="link-family-heading">
        <div class="flex items-center gap-3"><span class="cms-icon-tile text-orange-300"><x-icon name="chain" /></span><div><h2 id="link-family-heading" class="cms-heading text-base">Cargar productos a esta categoría</h2><p class="mt-1 text-xs text-mz-text-muted">Vinculando a: {{ $selectedCategory->title }}</p></div></div>
        <form action="{{ route('admin.home.categories.products.store', $selectedCategory) }}" method="POST" class="space-y-3" data-editor-form="Vincular producto a {{ $selectedCategory->title }}" @if (old('section') === 'categories' && old('card_id') == $selectedCategory->id && old('product_id') !== null) data-editor-recovered @endif>
            @csrf
            <input type="hidden" name="section" value="categories">
            <input type="hidden" name="card_id" value="{{ $selectedCategory->id }}">
            <div data-product-picker data-search-url="{{ route('admin.home.products.search') }}" data-picker-instruction="seleccionado. Pulsa Vincular para añadirlo a esta familia.">
                <label for="family-product-search" class="mb-2 block text-xs font-medium">Buscar por nombre, SKU o código de barras</label>
                <input type="hidden" name="product_id" value="{{ old('section') === 'categories' && old('card_id') == $selectedCategory->id ? old('product_id') : '' }}" data-product-id>
                <div class="flex gap-2">
                    <input id="family-product-search" type="search" class="mz-input h-11 min-w-0 flex-1" placeholder="Ej. Pistón, BAL-001 o escanea el código de barras" data-product-search>
                    <x-button type="button" variant="secondary" data-product-clear>Quitar</x-button>
                </div>
                <ul class="mt-2 divide-y divide-mz-border" data-product-results></ul>
                <p class="mt-2 text-sm" data-product-chosen>
                    @if (old('section') === 'categories' && old('card_id') == $selectedCategory->id && old('product_id'))
                        Producto seleccionado #{{ old('product_id') }}. Puedes buscarlo de nuevo para comprobar el vínculo.
                    @else
                        Busca y selecciona un producto del inventario.
                    @endif
                </p>
                <p class="mt-1 text-xs text-mz-text-muted" role="status" aria-live="polite" data-product-status></p>
            </div>
            @error('product_id')<p class="text-sm text-mz-error">{{ $message }}</p>@enderror
            <p class="text-xs text-mz-text-muted">Solo añade una referencia en esta familia. No modifica categorías, precios ni existencias del producto.</p>
            <x-button type="submit"><x-icon name="plus" class="size-4" /> Vincular</x-button>
        </form>
    </section>

    <section class="space-y-3" aria-labelledby="family-products-heading">
        <div class="flex flex-wrap items-center justify-between gap-2 px-1"><h2 id="family-products-heading" class="cms-heading text-base">Productos en «{{ $selectedCategory->title }}»</h2><span class="cms-badge text-mz-success">{{ $selectedCategory->linkedProducts->count() }} asignados</span></div>
        <p class="px-1 text-xs text-mz-text-muted">Usa las flechas para ordenar. Los productos inactivos no se muestran al público.</p>
        @forelse ($selectedCategory->linkedProducts as $product)
            <article class="cms-panel p-3">
                <div class="flex items-center gap-3">
                    <div class="relative size-16 shrink-0 overflow-hidden rounded-lg bg-mz-bg-primary">
                        @if ($product->image_path)<img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="" class="size-full object-contain" loading="lazy">@else<span class="flex size-full items-center justify-center text-orange-300"><x-icon name="moto" /></span>@endif
                        <span class="absolute right-0 bottom-0 rounded-tl bg-mz-accent-primary px-1.5 text-[10px] text-white">#{{ $loop->iteration }}</span>
                    </div>
                    <div class="min-w-0 flex-1"><h3 class="cms-heading truncate text-sm">{{ $product->name }}</h3><p class="mt-1 truncate text-xs text-mz-text-muted">SKU: {{ $product->sku }} · {{ $product->stock }} en stock</p><p class="mt-1 text-sm font-semibold text-orange-300">${{ number_format((float) $product->price, 2) }} MXN</p>@unless ($product->is_active)<span class="cms-badge text-mz-warning">Inactivo</span>@endunless</div>
                    <form action="{{ route('admin.home.categories.products.destroy', [$selectedCategory, $product]) }}" method="POST" data-home-confirm="¿Desvincular este producto de la familia? No se borra del inventario.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cms-icon-button cms-secondary text-mz-error" aria-label="Desvincular {{ $product->name }}" disabled><x-icon name="x" class="size-4" /></button>
                    </form>
                </div>
                <div class="mt-3 flex gap-2 border-t border-mz-border pt-2">
                    @foreach (['up' => 'Subir', 'down' => 'Bajar'] as $direction => $label)
                        <form action="{{ route('admin.home.categories.products.move', [$selectedCategory, $product]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="direction" value="{{ $direction }}">
                            <x-button type="submit" variant="secondary" size="sm" :disabled="($direction === 'up' && $loop->parent->first) || ($direction === 'down' && $loop->parent->last)" aria-label="{{ $label }} {{ $product->name }}"><x-icon name="{{ $direction === 'up' ? 'arrow-up' : 'arrow-down' }}" class="size-3.5" /> {{ $label }}</x-button>
                        </form>
                    @endforeach
                </div>
            </article>
        @empty
            <p class="cms-panel border border-dashed border-mz-border py-8 text-center text-sm text-mz-text-muted">Esta familia todavía no tiene productos. Busca y vincula el primero.</p>
        @endforelse
    </section>
@endif
