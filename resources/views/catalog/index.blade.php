@extends('layouts.site')

@section('title', $activeCategory ? $activeCategory->name : 'Catálogo de refacciones')
@section('description', $activeCategory ? $activeCategory->description : 'Explora el catálogo completo de refacciones para motos: motor, frenos, llantas, transmisión, eléctrico, suspensión y accesorios.')

@section('content')
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <nav class="flex flex-wrap items-center gap-2 text-xs text-slate-500" aria-label="Ruta de navegación">
                <a href="{{ route('home') }}" class="transition hover:text-mz-accent-primary">Inicio</a>
                <span aria-hidden="true">/</span>
                @if ($activeCategory)
                    <a href="{{ route('catalog.index') }}" class="transition hover:text-mz-accent-primary">Catálogo</a>
                    <span aria-hidden="true">/</span>
                    <span class="text-slate-700">{{ $activeCategory->name }}</span>
                @else
                    <span class="text-slate-700">Catálogo</span>
                @endif
            </nav>

            <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="flex items-center gap-2 font-display text-xs font-semibold uppercase tracking-[0.22em] text-mz-accent-primary">
                        <span class="h-px w-6 bg-mz-accent-primary" aria-hidden="true"></span>
                        Refacciones para tu moto
                    </p>
                    <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-tight text-ink-900 sm:text-5xl">
                        {{ $activeCategory?->name ?? 'Todas las piezas' }}
                    </h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-600">
                        {{ $activeCategory?->description ?? 'Refacciones, consumibles y accesorios con existencias actualizadas para que elijas con confianza.' }}
                    </p>
                </div>
                <p class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-600">
                    <x-icon name="inventory" class="size-4 text-mz-accent-primary" />
                    <span class="font-semibold text-ink-900">{{ $products->total() }}</span>
                    {{ $products->total() === 1 ? 'pieza encontrada' : 'piezas encontradas' }}
                </p>
            </div>
        </div>
    </div>

    <div class="bg-slate-50">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <div class="lg:grid lg:grid-cols-[17rem_1fr] lg:gap-10">
                <aside class="hidden lg:block">
                    <div class="sticky top-24">
                        @include('catalog.partials.filters')
                    </div>
                </aside>

                <details class="group mb-5 rounded-xl border border-slate-200 bg-white lg:hidden">
                    <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 text-sm font-semibold text-ink-900 [&::-webkit-details-marker]:hidden">
                        <span class="flex items-center gap-2">
                            <x-icon name="menu" class="size-4 text-slate-500" />
                            Filtros y categorías
                        </span>
                        <x-icon name="chevron-down" class="size-4 text-slate-500 transition group-open:rotate-180" />
                    </summary>
                    <div class="border-t border-slate-200 p-4">
                        @include('catalog.partials.filters')
                    </div>
                </details>

                <div>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($search !== '')
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs text-slate-600">
                                    Búsqueda: <span class="font-medium text-ink-900">{{ $search }}</span>
                                </span>
                            @endif
                            @if ($activeCategory)
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs text-slate-600">
                                    Categoría: <span class="font-medium text-ink-900">{{ $activeCategory->name }}</span>
                                </span>
                            @endif
                        </div>

                        <form action="{{ route('catalog.index') }}" method="GET" class="flex items-center gap-2">
                            @if ($search !== '')
                                <input type="hidden" name="search" value="{{ $search }}">
                            @endif
                            @if ($activeCategory)
                                <input type="hidden" name="categoria" value="{{ $activeCategory->slug }}">
                            @endif
                            <label for="sort" class="text-xs text-slate-500">Ordenar por</label>
                            <select id="sort" name="orden" data-auto-submit class="h-9 rounded-lg border border-slate-200 bg-white pl-3 pr-8 text-sm text-slate-700 focus:border-mz-accent-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/20">
                                <option value="relevancia" @selected($sort === 'relevancia')>Relevancia</option>
                                <option value="precio-asc" @selected($sort === 'precio-asc')>Precio: menor a mayor</option>
                                <option value="precio-desc" @selected($sort === 'precio-desc')>Precio: mayor a menor</option>
                                <option value="nombre" @selected($sort === 'nombre')>Nombre A–Z</option>
                            </select>
                            <button type="submit" class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">Aplicar</button>
                        </form>
                    </div>

                    @if ($products->isEmpty())
                        <div class="mt-8 flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                            <span class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                <x-icon name="search" class="size-7" />
                            </span>
                            <h2 class="mt-4 font-display text-2xl font-semibold uppercase tracking-tight text-ink-900">Sin resultados</h2>
                            <p class="mt-2 max-w-sm text-sm text-slate-600">No encontramos piezas con esos filtros. Prueba con otra palabra, marca o número de parte.</p>
                            <a href="{{ route('catalog.index') }}" class="mt-5 inline-flex h-10 items-center gap-2 rounded-lg bg-mz-accent-primary px-4 text-sm font-semibold text-white transition hover:bg-mz-accent-hover">
                                Ver todo el catálogo
                                <x-icon name="arrow-right" class="size-4" />
                            </a>
                        </div>
                    @else
                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($products as $product)
                                <x-product-card :product="$product" />
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
