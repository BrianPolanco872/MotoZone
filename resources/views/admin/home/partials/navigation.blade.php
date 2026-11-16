@php
    $initialBlock = old('section', old('block'));
    $initialAnchor = ['hero' => 'presentacion', 'categories' => 'categorias', 'products' => 'destacados', 'new' => 'nuevos', 'decoration' => 'nuevos', 'contact' => 'contacto', 'location' => 'ubicacion', 'information' => 'informacion'][$initialBlock ?? ''] ?? '';
@endphp
<nav aria-label="Secciones del inicio" class="space-y-3" data-editor-navigation data-initial-panel="{{ $initialAnchor }}">
    <div class="flex flex-wrap items-center justify-between gap-1 px-1">
        <span class="cms-label">Secciones de la tienda</span>
        <span class="text-[10px] text-mz-text-muted">Toca para cambiar de bloque</span>
    </div>
    <div class="cms-tabs -mx-4 flex gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
        @foreach ([
            'presentacion' => ['label' => 'Presentación', 'icon' => 'inventory', 'key' => 'hero'],
            'categorias' => ['label' => 'Categorías', 'icon' => 'tag', 'key' => 'categories'],
            'destacados' => ['label' => 'Destacados', 'icon' => 'bolt', 'key' => 'products'],
            'nuevos' => ['label' => 'Recién llegados', 'icon' => 'plus', 'key' => 'new'],
            'contacto' => ['label' => 'Contacto', 'icon' => 'phone', 'key' => 'contact'],
            'ubicacion' => ['label' => 'Ubicación', 'icon' => 'map-pin', 'key' => 'location'],
            'informacion' => ['label' => 'Información', 'icon' => 'report', 'key' => 'information'],
        ] as $anchor => $tab)
            <a id="tab-{{ $anchor }}" href="#{{ $anchor }}" class="cms-tab inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full px-4 text-xs font-semibold" data-editor-tab="{{ $anchor }}">
                <x-icon name="{{ $tab['icon'] }}" class="size-4" /> {{ $tab['label'] }}
                @if ($tab['key'] === 'hero')
                    <span class="size-2 rounded-full {{ $page->enabled('hero') ? 'bg-mz-success' : 'bg-mz-text-muted' }}" aria-label="{{ $page->enabled('hero') ? 'Visible' : 'Oculto' }}"></span>
                @elseif (in_array($tab['key'], ['categories', 'products', 'new'], true))
                    <span class="cms-tab-count rounded-full px-1.5 py-0.5 text-[10px]">{{ ($cards[$tab['key']] ?? collect())->count() }}</span>
                @endif
            </a>
        @endforeach
    </div>
</nav>
@if ($errors->any())
    <div role="alert" class="cms-panel border border-mz-error text-sm text-mz-error">
        <p class="font-semibold">No se guardaron los cambios. Revisa lo siguiente:</p>
        <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
@if (session('status'))<p role="status" class="cms-panel text-sm text-mz-success">{{ session('status') }}</p>@endif
