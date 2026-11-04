@extends('layouts.admin')

@section('title', 'Productos')
@section('subtitle', 'Inventario y precios del catálogo')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">
            <span class="font-semibold text-slate-900">{{ $products->total() }}</span>
            {{ $products->total() === 1 ? 'producto registrado' : 'productos registrados' }}
        </p>
        <div class="flex items-center gap-2">
            <a href="{{ route('catalog.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                <x-icon name="store" class="size-4" />
                Ver tienda
            </a>
            <a href="{{ route('admin.products.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-medium text-white transition hover:bg-brand-800">
                <x-icon name="plus" class="size-4" />
                Nuevo producto
            </a>
        </div>
    </div>

    <!-- Panel de herramientas avanzadas -->
    <div class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3.5">
            <h2 class="text-sm font-semibold text-slate-900">Herramientas de inventario y precios</h2>
            <p class="text-xs text-slate-500">Actualización masiva, importación de datos y gestión global de stock.</p>
        </div>

        <div class="p-5 space-y-5">
            <!-- Formulario de Precios Masivos -->
            <form action="{{ route('admin.products.massUpdate') }}" method="POST" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto_auto] lg:items-end">
                @csrf
                <div>
                    <label for="mass-tax-rate" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">IVA global (%)</label>
                    <input id="mass-tax-rate" name="tax_rate" type="number" step="0.01" min="0" max="100" value="16" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                </div>
                <div>
                    <label for="mass-margin" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Ganancia global (%)</label>
                    <input id="mass-margin" name="margin_percentage" type="number" step="0.01" min="0" value="30" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                </div>
                <div>
                    <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-ink-900 px-4 text-sm font-medium text-white transition hover:bg-ink-800">
                        <x-icon name="refresh-cw" class="size-4" />
                        Aplicar a todos
                    </button>
                </div>
                <div>
                    <button type="button" onclick="if(confirm('¿Estás seguro de limpiar TODO el inventario? Todas las existencias pasarán a cero.')) { document.getElementById('clear-inventory-form').submit(); }" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-rose-200 bg-white px-4 text-sm font-medium text-rose-600 transition hover:bg-rose-50">
                        <x-icon name="trash" class="size-4" />
                        Limpiar inventario
                    </button>
                </div>
            </form>

            <form id="clear-inventory-form" action="{{ route('admin.products.clearInventory') }}" method="POST" class="hidden">
                @csrf
            </form>

            <hr class="border-slate-100">

            <!-- Importación CSV -->
            <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 lg:flex-row lg:items-end">
                @csrf
                <div class="flex-1">
                    <label for="import_file" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Importar catálogo CSV</label>
                    <div class="mt-1.5 flex items-center gap-2">
                        <input id="import_file" type="file" name="import_file" accept=".csv,.txt,.xlsx,.xls" class="block w-full text-sm text-slate-500 file:mr-3 file:h-10 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Columnas: sku, nombre, marca, categoria, costo, precio_venta, precio_mayoreo, stock, min_stock, iva, codigo_barras, descripcion</p>
                </div>
                <div>
                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 text-sm font-semibold text-white transition hover:bg-brand-800">
                        <x-icon name="upload" class="size-4" />
                        Importar archivo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <form action="{{ route('admin.products.index') }}" method="GET" class="mt-5 rounded-xl border border-slate-200 bg-white p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_14rem_12rem_auto] lg:items-end">
            <div>
                <label for="admin-search" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Buscar</label>
                <div class="relative mt-1.5">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-500" />
                    <input id="admin-search" type="search" name="search" value="{{ $search }}" placeholder="Nombre, marca o SKU" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-500 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                </div>
            </div>

            <div>
                <label for="admin-category" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Categoría</label>
                <select id="admin-category" name="categoria" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-3 pr-8 text-sm text-slate-700 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected(request('categoria') === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="admin-stock" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Existencia</label>
                <select id="admin-stock" name="stock" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-3 pr-8 text-sm text-slate-700 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                    <option value="">Todas</option>
                    <option value="bajo" @selected($stockFilter === 'bajo')>Stock bajo</option>
                    <option value="agotado" @selected($stockFilter === 'agotado')>Agotados</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="h-10 rounded-lg bg-ink-900 px-4 text-sm font-medium text-white transition hover:bg-ink-800">Filtrar</button>
                <a href="{{ route('admin.products.index') }}" class="h-10 rounded-lg border border-slate-200 px-4 text-sm font-medium leading-10 text-slate-600 transition hover:bg-slate-50">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-5 py-3 font-medium">Producto</th>
                        <th scope="col" class="px-5 py-3 font-medium">SKU / Código</th>
                        <th scope="col" class="px-5 py-3 font-medium">Categoría</th>
                        <th scope="col" class="px-5 py-3 font-medium">Existencia</th>
                        <th scope="col" class="px-5 py-3 font-medium">Estado</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium">Precio</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Ver</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">{{ $product->brand }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 font-mono text-xs text-slate-500">
                                <p>{{ $product->sku }}</p>
                                @if ($product->barcode)
                                    <p class="mt-0.5 text-[11px] text-slate-400">{{ $product->barcode }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $product->category->name }}</td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <x-stock-badge :product="$product" />
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                    'bg-emerald-50 text-emerald-700 ring-emerald-600/20' => $product->is_active,
                                    'bg-slate-100 text-slate-600 ring-slate-500/20' => ! $product->is_active,
                                ])>{{ $product->is_active ? 'Publicado' : 'Borrador' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-medium tabular-nums text-slate-900">
                                <p>${{ number_format((float) $product->price, 2) }}</p>
                                @if ($product->hasCost())
                                    <p class="mt-0.5 text-[11px] font-normal text-slate-400">Costo ${{ number_format((float) $product->cost, 2) }} · {{ number_format((float) $product->marginPercentage(), 1) }}%</p>
                                @else
                                    <p class="mt-0.5 text-[11px] font-normal text-amber-600">Sin costo capturado</p>
                                @endif
                                <p class="mt-0.5 text-[11px] font-normal text-slate-400">IVA {{ number_format((float) $product->tax_rate * 100, 2) }}%</p>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('catalog.show', $product) }}" class="inline-flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-brand-500 hover:text-brand-700" aria-label="Ver {{ $product->name }} en la tienda">
                                        <x-icon name="arrow-right" class="size-4" />
                                    </a>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-slate-600 transition hover:border-brand-500 hover:text-brand-700">
                                        <x-icon name="pencil" class="size-3.5" />
                                        Editar
                                    </a>
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar el producto "{{ $product->name }}"? Esta acción no se puede deshacer.')" class="ml-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-600 transition hover:bg-red-50 hover:text-white" aria-label="Eliminar {{ $product->name }}">
                                            <x-icon name="trash" class="size-3.5" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-slate-700">Sin productos con esos filtros</p>
                                <a href="{{ route('admin.products.index') }}" class="mt-2 inline-block text-xs font-medium text-brand-700 hover:text-brand-800">Limpiar filtros</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $products->links() }}
    </div>
@endsection
