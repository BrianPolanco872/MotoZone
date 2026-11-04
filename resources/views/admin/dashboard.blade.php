@extends('layouts.admin')

@section('title', 'Panel')
@section('subtitle', 'Resumen del catálogo y el inventario')

@section('content')
    @php
        $maxCategoryCount = max($categories->max('products_count'), 1);
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">Estado actual del catálogo de refacciones.</p>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.products.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                <x-icon name="inventory" class="size-4" />
                Inventario
            </a>
            <a href="{{ route('catalog.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-medium text-white transition hover:bg-brand-800">
                <x-icon name="store" class="size-4" />
                Ver tienda
            </a>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($metrics as $metric)
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                    <span @class([
                        'flex size-9 shrink-0 items-center justify-center rounded-lg',
                        'bg-amber-50 text-amber-600' => $metric['label'] === 'Stock bajo',
                        'bg-rose-50 text-rose-600' => $metric['label'] === 'Agotados',
                        'bg-brand-50 text-brand-700' => ! in_array($metric['label'], ['Stock bajo', 'Agotados'], true),
                    ])>
                        <x-icon :name="$metric['icon']" class="size-5" />
                    </span>
                </div>
                <p class="mt-3 font-display text-3xl font-semibold tracking-tight text-slate-900">{{ $metric['value'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $metric['note'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Inventario por categoría</h2>
                    <p class="text-xs text-slate-500">Productos activos en cada categoría</p>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $categories->sum('products_count') }} piezas</span>
            </div>

            <ul class="mt-5 space-y-4">
                @foreach ($categories as $category)
                    <li>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex min-w-0 items-center gap-2 text-slate-700">
                                <x-icon :name="$category->icon()" class="size-4 shrink-0 text-slate-500" />
                                <span class="truncate">{{ $category->name }}</span>
                            </span>
                            <span class="shrink-0 font-medium tabular-nums text-slate-500">{{ $category->products_count }}</span>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-brand-500" style="width: {{ max(3, (int) round($category->products_count / $maxCategoryCount * 100)) }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Reponer inventario</h2>
                    <p class="text-xs text-slate-500">Piezas con 5 existencias o menos</p>
                </div>
                <span class="inline-flex size-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <x-icon name="alert" class="size-4" />
                </span>
            </div>

            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($lowStockProducts as $product)
                    <li class="py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-800">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">{{ $product->sku }} · {{ $product->category->name }}</p>
                            </div>
                            <span @class([
                                'shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold',
                                'bg-rose-50 text-rose-700' => ! $product->isInStock(),
                                'bg-amber-50 text-amber-700' => $product->isInStock(),
                            ])>{{ $product->stock }}</span>
                        </div>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $product->isInStock() ? 'bg-amber-500' : 'bg-rose-500' }}" style="width: {{ min(100, (int) round($product->stock / 5 * 100)) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">Todo el inventario está por encima del mínimo.</li>
                @endforelse
            </ul>

            <a href="{{ route('admin.products.index', ['stock' => 'bajo']) }}" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-700 transition hover:text-brand-800">
                Ver stock bajo
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Productos recientes</h2>
                <p class="text-xs text-slate-500">Últimas piezas agregadas al catálogo</p>
            </div>
            <a href="{{ route('admin.products.index') }}" class="text-xs font-medium text-brand-700 transition hover:text-brand-800">Ver todos</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-5 py-3 font-medium">SKU</th>
                        <th scope="col" class="px-5 py-3 font-medium">Producto</th>
                        <th scope="col" class="px-5 py-3 font-medium">Categoría</th>
                        <th scope="col" class="px-5 py-3 font-medium">Existencia</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium">Precio</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentProducts as $product)
                        <tr class="transition hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 font-mono text-xs text-slate-500">{{ $product->sku }}</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('catalog.show', $product) }}" class="font-medium text-slate-900 transition hover:text-brand-700">{{ $product->name }}</a>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $product->category->name }}</td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <x-stock-badge :product="$product" />
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-medium tabular-nums text-slate-900">${{ number_format((float) $product->price, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Aún no hay productos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
