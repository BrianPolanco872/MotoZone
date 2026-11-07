@extends('layouts.admin')

@section('title', 'Panel')
@section('subtitle', 'Resumen del catálogo y el inventario')

@section('content')
    @php
        $maxCategoryCount = max($categories->max('products_count'), 1);
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-mz-text-secondary">Estado actual del catálogo de refacciones.</p>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.products.index') }}" class="x-button x-button-secondary size-sm">
                <x-icon name="inventory" class="size-4" />
                Inventario
            </a>
            <a href="{{ route('catalog.index') }}" class="x-button x-button-primary size-sm">
                <x-icon name="store" class="size-4" />
                Ver tienda
            </a>
        </div>
    </div>

    {{-- Tarjetas de resumen (KPIs) --}}
    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($metrics as $metric)
            <div class="mz-card p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium text-mz-text-secondary">{{ $metric['label'] }}</p>
                    <span @class([
                        'flex size-9 shrink-0 items-center justify-center rounded-md',
                        'bg-mz-warning/10 text-mz-warning' => $metric['label'] === 'Stock bajo',
                        'bg-mz-error/10 text-mz-error' => $metric['label'] === 'Agotados',
                        'bg-mz-accent-primary/10 text-mz-accent-primary' => ! in_array($metric['label'], ['Stock bajo', 'Agotados'], true),
                    ])>
                        <x-icon :name="$metric['icon']" class="size-5" />
                    </span>
                </div>
                <p class="mt-3 font-display text-3xl font-semibold tracking-tight text-mz-text-primary">{{ $metric['value'] }}</p>
                <p class="mt-1 text-xs text-mz-text-muted">{{ $metric['note'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Inventario por categoría y reponer inventario --}}
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="mz-card p-4 sm:p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-base font-semibold text-mz-text-primary">Inventario por categoría</h2>
                    <p class="text-xs text-mz-text-secondary">Productos activos en cada categoría</p>
                </div>
                <span class="rounded-md bg-mz-bg-surface-2 px-2.5 py-1 text-xs font-medium text-mz-text-secondary">{{ $categories->sum('products_count') }} piezas</span>
            </div>

            <ul class="space-y-4">
                @foreach ($categories as $category)
                    <li>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex min-w-0 items-center gap-2 text-mz-text-secondary">
                                <x-icon :name="$category->icon()" class="size-4 shrink-0 text-mz-text-muted" />
                                <span class="truncate">{{ $category->name }}</span>
                            </span>
                            <span class="shrink-0 font-medium tabular-nums text-mz-text-secondary">{{ $category->products_count }}</span>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-mz-bg-surface-2">
                            <div class="h-full rounded-full bg-mz-accent-primary" style="width: {{ max(3, (int) round($category->products_count / $maxCategoryCount * 100)) }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mz-card p-4 sm:p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-base font-semibold text-mz-text-primary">Reponer inventario</h2>
                    <p class="text-xs text-mz-text-secondary">Piezas con 5 existencias o menos</p>
                </div>
                <span class="inline-flex size-8 items-center justify-center rounded-md bg-mz-warning/10 text-mz-warning">
                    <x-icon name="alert" class="size-4" />
                </span>
            </div>

            <ul class="mt-4 divide-y divide-mz-border-light">
                @forelse ($lowStockProducts as $product)
                    <li class="py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-mz-text-primary">{{ $product->name }}</p>
                                <p class="text-xs text-mz-text-secondary">{{ $product->sku }} · {{ $product->category->name }}</p>
                            </div>
                            <span @class([
                                'shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold',
                                'bg-mz-error/10 text-mz-error' => ! $product->isInStock(),
                                'bg-mz-warning/10 text-mz-warning' => $product->isInStock(),
                            ])>{{ $product->stock }}</span>
                        </div>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-mz-bg-surface-2">
                            <div class="h-full rounded-full {{ $product->isInStock() ? 'bg-mz-warning' : 'bg-mz-error' }}" style="width: {{ min(100, (int) round($product->stock / 5 * 100)) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-mz-text-muted">Todo el inventario está por encima del mínimo.</li>
                @endforelse
            </ul>

            <a href="{{ route('admin.products.index', ['stock' => 'bajo']) }}" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-mz-accent-primary hover:text-mz-accent-hover">
                Ver stock bajo
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>

    {{-- Productos recientes --}}
    <div class="mt-5 mz-card overflow-hidden">
        <div class="border-b border-mz-border-light px-4 py-3">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-mz-text-primary">Productos recientes</h2>
                    <p class="text-xs text-mz-text-secondary">Últimas piezas agregadas al catálogo</p>
                </div>
                <a href="{{ route('admin.products.index') }}" class="text-xs font-medium text-mz-accent-primary hover:text-mz-accent-hover">Ver todos</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-mz-border-light text-sm">
                <thead class="bg-mz-bg-surface">
                    <tr class="text-left text-xs uppercase tracking-wide text-mz-text-muted">
                        <th class="px-4 py-3 font-medium">SKU</th>
                        <th class="px-4 py-3 font-medium">Producto</th>
                        <th class="px-4 py-3 font-medium">Categoría</th>
                        <th class="px-4 py-3 font-medium">Existencia</th>
                        <th class="px-4 py-3 text-right font-medium">Precio</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light">
                    @forelse ($recentProducts as $product)
                        <tr class="transition hover:bg-mz-bg-surface-2">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-mz-text-muted">{{ $product->sku }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('catalog.show', $product) }}" class="font-medium text-mz-text-secondary hover:text-mz-accent-primary">{{ $product->name }}</a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-mz-text-secondary">{{ $product->category->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <x-stock-badge :product="$product" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-mz-text-primary">${{ number_format((float) $product->price, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-mz-text-muted">Aún no hay productos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
