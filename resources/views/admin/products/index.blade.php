@extends('layouts.admin')

@section('title', 'Productos')
@section('subtitle', 'Inventario y precios del catálogo')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-mz-text-secondary">
                <span class="font-semibold text-mz-text-primary">{{ $products->total() }}</span>
                {{ $products->total() === 1 ? 'producto registrado' : 'productos registrados' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('catalog.index') }}" class="x-button x-button-secondary size-sm">
                <x-icon name="store" class="size-4" />
                Ver tienda
            </a>
            <a href="{{ route('admin.products.create') }}" class="x-button x-button-primary size-sm">
                <x-icon name="plus" class="size-4" />
                Nuevo producto
            </a>
        </div>
    </div>

    {{-- Panel de herramientas avanzadas --}}
    <section class="mt-4 mz-card mz-card-padded overflow-hidden">
        <div class="border-b border-mz-border-light px-4 py-3">
            <h2 class="text-sm font-semibold text-mz-text-primary">Herramientas de inventario y precios</h2>
            <p class="text-xs text-mz-text-muted">Actualización masiva, importación de datos y gestión global de stock.</p>
        </div>

        <div class="p-4 sm:p-5 space-y-4">
            {{-- Formulario de Precios Masivos --}}
            <form action="{{ route('admin.products.massUpdate') }}" method="POST" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto_auto] lg:items-end">
                @csrf
                <div>
                    <label for="mass-tax-rate" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">IVA global (%)</label>
                    <input id="mass-tax-rate" name="tax_rate" type="number" step="0.01" min="0" max="100" value="16" class="mz-input mt-1.5 h-10 w-full">
                </div>
                <div>
                    <label for="mass-margin" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Ganancia global (%)</label>
                    <input id="mass-margin" name="margin_percentage" type="number" step="0.01" min="0" value="30" class="mz-input mt-1.5 h-10 w-full">
                </div>
                <div>
                    <button type="submit" class="x-button x-button-primary size-sm w-full">
                        <x-icon name="refresh-cw" class="size-4" />
                        Aplicar a todos
                    </button>
                </div>
                <div>
                    <button type="button" onclick="if(confirm('¿Estás seguro de limpiar TODO el inventario? Todas las existencias pasarán a cero.')) { document.getElementById('clear-inventory-form').submit(); }" class="x-button x-button-danger size-sm w-full">
                        <x-icon name="trash" class="size-4" />
                        Limpiar inventario
                    </button>
                </div>
            </form>

            <form id="clear-inventory-form" action="{{ route('admin.products.clearInventory') }}" method="POST" class="hidden">
                @csrf
            </form>

            <hr class="border-mz-border-light">

            {{-- Importación CSV --}}
            <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 lg:flex-row lg:items-end">
                @csrf
                <div class="flex-1">
                    <label for="import_file" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Importar catálogo CSV</label>
                    <div class="mt-1.5 flex items-center gap-2">
                        <input id="import_file" type="file" name="import_file" accept=".csv,.txt,.xlsx,.xls" class="block w-full text-sm text-mz-text-secondary file:mr-3 file:h-10 file:rounded-md file:border-0 file:bg-mz-bg-surface-2 file:px-3 file:text-sm file:font-medium file:text-mz-text-secondary hover:file:bg-mz-bg-surface-2">
                    </div>
                    <p class="mt-1 text-xs text-mz-text-muted">Columnas: sku, nombre, marca, categoria, costo, precio_venta, precio_mayoreo, stock, min_stock, iva, codigo_barras, descripcion</p>
                </div>
                <div>
                    <button type="submit" class="x-button x-button-primary size-sm">
                        <x-icon name="upload" class="size-4" />
                        Importar archivo
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- Filtros --}}
    <form action="{{ route('admin.products.index') }}" method="GET" class="mt-4 mz-card mz-card-padded overflow-hidden">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_14rem_12rem_auto] lg:items-end">
            <div>
                <label for="admin-search" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Buscar</label>
                <div class="relative mt-1.5">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-mz-text-muted" />
                    <input id="admin-search" type="search" name="search" value="{{ $search }}" placeholder="Nombre, marca o SKU" class="mz-input pl-9 mt-1.5 h-10 w-full">
                </div>
            </div>

            <div>
                <label for="admin-category" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Categoría</label>
                <select id="admin-category" name="categoria" class="mz-select mt-1.5 h-10 w-full">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected(request('categoria') === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="admin-stock" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Existencia</label>
                <select id="admin-stock" name="stock" class="mz-select mt-1.5 h-10 w-full">
                    <option value="">Todas</option>
                    <option value="bajo" @selected($stockFilter === 'bajo')>Stock bajo</option>
                    <option value="agotado" @selected($stockFilter === 'agotado')>Agotados</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="x-button x-button-primary size-sm">
                    Filtrar
                </button>
                <a href="{{ route('admin.products.index') }}" class="x-button x-button-secondary size-sm">
                    Limpiar
                </a>
            </div>
        </div>
    </form>

    {{-- Tabla de productos --}}
    <section class="mt-4 mz-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-mz-border-light text-sm">
                <thead class="bg-mz-bg-surface">
                    <tr class="text-left text-xs uppercase tracking-wide text-mz-text-muted">
                        <th scope="col" class="px-4 py-3 font-medium">Producto</th>
                        <th scope="col" class="px-4 py-3 font-medium">SKU / Código</th>
                        <th scope="col" class="px-4 py-3 font-medium">Categoría</th>
                        <th scope="col" class="px-4 py-3 font-medium">Existencia</th>
                        <th scope="col" class="px-4 py-3 font-medium">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Precio</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light">
                    @forelse ($products as $product)
                        <tr class="transition hover:bg-mz-bg-surface-2">
                            <td class="px-4 py-3">
                                <p class="font-medium text-mz-text-primary">{{ $product->name }}</p>
                                <p class="text-xs text-mz-text-secondary">{{ $product->brand }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-mz-text-secondary">
                                <p>{{ $product->sku }}</p>
                                @if ($product->barcode)
                                    <p class="mt-0.5 text-[11px] text-mz-text-muted">{{ $product->barcode }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-mz-text-secondary">{{ $product->category->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <x-stock-badge :product="$product" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span @class([
                                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                    'bg-mz-success/10 text-mz-success ring-mz-success/20' => $product->is_active,
                                    'bg-mz-text-muted/10 text-mz-text-muted ring-mz-text-muted/20' => ! $product->is_active,
                                ])>
                                    <x-icon name="{{ $product->is_active ? 'check' : 'clock' }}" class="size-3" />
                                    {{ $product->is_active ? 'Publicado' : 'Borrador' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-mz-text-primary">
                                <p>${{ number_format((float) $product->price, 2) }}</p>
                                @if ($product->hasCost())
                                    <p class="mt-0.5 text-[11px] font-normal text-mz-text-muted">Costo ${{ number_format((float) $product->cost, 2) }} · {{ number_format((float) $product->marginPercentage(), 1) }}%</p>
                                @else
                                    <p class="mt-0.5 text-[11px] font-normal text-mz-warning">Sin costo capturado</p>
                                @endif
                                <p class="mt-0.5 text-[11px] font-normal text-mz-text-muted">IVA {{ number_format((float) $product->tax_rate * 100, 2) }}%</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('catalog.show', $product) }}" class="inline-flex size-8 items-center justify-center rounded-md border border-mz-border-light text-mz-text-muted transition hover:border-mz-accent-primary hover:text-mz-accent-primary" aria-label="Ver {{ $product->name }} en la tienda">
                                        <x-icon name="arrow-right" class="size-4" />
                                    </a>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="x-button x-button-ghost size-xs text-mz-text-secondary">
                                        <x-icon name="pencil" class="size-3.5" />
                                        Editar
                                    </a>
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar el producto \"{{ $product->name }}\"? Esta acción no se puede deshacer.')" class="ml-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="x-button x-button-ghost size-xs text-mz-error" aria-label="Eliminar {{ $product->name }}">
                                            <x-icon name="trash" class="size-3.5" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-14 text-center">
                                <p class="text-sm font-medium text-mz-text-secondary">Sin productos con esos filtros</p>
                                <a href="{{ route('admin.products.index') }}" class="mt-2 inline-block text-xs font-medium text-mz-accent-primary hover:text-mz-accent-hover">Limpiar filtros</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
@endsection
