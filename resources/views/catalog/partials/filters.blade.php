@php
    $hasFilters = $search !== '' || $activeCategory !== null || $sort !== 'relevancia';
@endphp

<div class="space-y-6">
    <form action="{{ route('catalog.index') }}" method="GET" role="search" class="rounded-xl border border-slate-200 bg-white p-4">
        @if ($activeCategory)
            <input type="hidden" name="categoria" value="{{ $activeCategory->slug }}">
        @endif
        @if ($sort !== 'relevancia')
            <input type="hidden" name="orden" value="{{ $sort }}">
        @endif

        <label class="block">
            <span class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Buscar</span>
            <span class="relative mt-2 block">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Nombre, marca o SKU"
                    class="h-10 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-mz-accent-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/20"
                >
            </span>
        </label>

        <button type="submit" class="mt-3 inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-mz-accent-primary px-4 text-sm font-semibold text-white transition hover:bg-mz-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-mz-accent-primary focus-visible:ring-offset-2">
            <x-icon name="search" class="size-4" />
            Aplicar búsqueda
        </button>
    </form>

    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Categorías</p>
        <ul class="mt-3 space-y-1">
            <li>
                <a href="{{ route('catalog.index', array_filter(['search' => $search, 'orden' => $sort !== 'relevancia' ? $sort : null])) }}" @class([
                    'flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm transition',
                    'bg-mz-accent-primary font-semibold text-white' => $activeCategory === null,
                    'text-slate-600 hover:bg-slate-100 hover:text-ink-900' => $activeCategory !== null,
                ])>
                    <span class="flex items-center gap-2">
                        <x-icon name="inventory" class="size-4 {{ $activeCategory === null ? 'text-white' : 'text-slate-400' }}" />
                        Todas las piezas
                    </span>
                    <span @class(['text-xs tabular-nums', 'text-white/80' => $activeCategory === null, 'text-slate-500' => $activeCategory !== null])>{{ $categories->sum('products_count') }}</span>
                </a>
            </li>
            @foreach ($categories as $category)
                <li>
                    <a href="{{ route('catalog.index', array_filter(['search' => $search, 'orden' => $sort !== 'relevancia' ? $sort : null, 'categoria' => $category->slug])) }}" @class([
                        'flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm transition',
                        'bg-mz-accent-primary font-semibold text-white' => $activeCategory?->is($category),
                        'text-slate-600 hover:bg-slate-100 hover:text-ink-900' => ! $activeCategory?->is($category),
                    ])>
                        <span class="flex items-center gap-2">
                            <x-icon :name="$category->icon()" class="size-4 {{ $activeCategory?->is($category) ? 'text-white' : 'text-slate-400' }}" />
                            {{ $category->name }}
                        </span>
                        <span @class(['text-xs tabular-nums', 'text-white/80' => $activeCategory?->is($category), 'text-slate-500' => ! $activeCategory?->is($category)])>{{ $category->products_count }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    @if ($hasFilters)
        <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-mz-accent-primary transition hover:text-mz-accent-hover">
            <x-icon name="x" class="size-3.5" />
            Limpiar filtros
        </a>
    @endif
</div>
