@extends('layouts.admin')

@section('title', 'Productos')
@section('subtitle', 'Inventario y precios de productos')

@section('content')
    @if (session('error'))
        <x-alert variant="error">{{ session('error') }}</x-alert>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-mz-text-secondary">
                <span class="font-semibold text-mz-text-primary">{{ $products->total() }}</span>
                {{ $products->total() === 1 ? 'producto registrado' : 'productos registrados' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.products.export') }}" class="x-button x-button-secondary size-sm">
                <x-icon name="inventory" class="size-4" />
                Exportar inventario
            </a>
            <a href="{{ route('admin.products.create') }}" class="x-button x-button-primary size-sm">
                <x-icon name="plus" class="size-4" />
                Nuevo producto
            </a>
        </div>
    </div>

    <details class="mt-4 mz-card px-4 py-3" @if ($errors->has('import_file')) open @endif>
        <summary class="cursor-pointer text-sm font-semibold text-mz-text-primary">Importar productos CSV</summary>

        <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-end">
            @csrf
            <div class="flex-1">
                <label for="import_file" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Archivo CSV o TXT</label>
                <input id="import_file" type="file" name="import_file" accept=".csv,.txt" required aria-describedby="import-help{{ $errors->has('import_file') ? ' import-errors' : '' }}" aria-invalid="{{ $errors->has('import_file') ? 'true' : 'false' }}" class="mt-1.5 block w-full text-sm text-mz-text-secondary file:mr-3 file:h-10 file:rounded-md file:border-0 file:bg-mz-bg-surface-2 file:px-3 file:text-sm file:font-medium file:text-mz-text-secondary hover:file:bg-mz-bg-surface-2">
                <p id="import-help" class="mt-1 text-xs text-mz-text-muted">CSV UTF-8 o TXT separado por comas o punto y coma. Para productos nuevos: codigo (o sku), nombre y precio_venta. Opcionales: marca, descripcion, stock (existencias), min_stock (mínimo de alerta), codigo_barras, codigo_barras_2, costo, precio_mayoreo e iva. El código original se conserva como SKU y se guarda como código de barras principal si no indicas codigo_barras. El segundo código solo se guarda si tú lo proporcionas. Al reimportar, solo se completan códigos principales vacíos: no se modifican precios, existencias, segundo código ni otros datos. Si ya hay código principal, el producto se omite. Haz un respaldo antes de reparar mediante reimportación. Puedes completar marca y descripción después. Usa punto decimal, sin símbolos de moneda.</p>
                @if ($errors->has('import_file'))
                    <x-alert variant="error" id="import-errors" class="mt-2">
                        <p>No se importó ningún producto. Corrige el archivo y vuelve a seleccionarlo.</p>
                        <ul>
                            @foreach ($errors->get('import_file') as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                @endif
            </div>
            <div>
                <button type="submit" class="x-button x-button-primary size-sm">
                    <x-icon name="upload" class="size-4" />
                    Importar archivo
                </button>
            </div>
        </form>
    </details>

    {{-- Filtros --}}
    <form action="{{ route('admin.products.index') }}" method="GET" class="mt-4 mz-card mz-card-padded overflow-hidden">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(12rem,1fr)_10rem_11rem_auto] xl:items-end">
            <div>
                <label for="admin-search" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Buscar</label>
                <div class="relative mt-1.5">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-mz-text-muted" />
                    <input id="admin-search" type="search" name="search" value="{{ $search }}" placeholder="Nombre, marca, SKU o código de barras" class="mz-input pl-9 mt-1.5 h-10 w-full">
                </div>
            </div>

            <div>
                <label for="admin-stock" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Existencia</label>
                <select id="admin-stock" name="stock" class="mz-select mt-1.5 h-10 w-full">
                    <option value="">Todas</option>
                    <option value="bajo" @selected($stockFilter === 'bajo')>Existencias bajas · activos</option>
                    <option value="agotado" @selected($stockFilter === 'agotado')>Agotados · activos</option>
                </select>
            </div>

            <div>
                <label for="admin-review" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Revisión de productos</label>
                <select id="admin-review" name="revision" class="mz-select mt-1.5 h-10 w-full">
                    <option value="">Todos</option>
                    <option value="sin-foto" @selected($reviewFilter === 'sin-foto')>Sin foto capturada</option>
                    <option value="sin-precio" @selected($reviewFilter === 'sin-precio')>Sin precio válido</option>
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
                        <th scope="col" class="px-4 py-3 font-medium">Códigos de barras</th>
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
                                <p class="mt-1 text-xs text-mz-text-muted">{{ match ($product->saleMode()) { \App\Models\Product::MODE_BULK => 'A granel', \App\Models\Product::MODE_KIT => 'Paquete (kit)', default => 'Por unidad/pieza' } }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-mz-text-secondary">
                                @if ($product->barcode)
                                    <p><span class="text-mz-text-muted">1:</span> {{ $product->barcode }}</p>
                                @endif
                                @if ($product->barcode2)
                                    <p class="mt-0.5 text-[11px] text-mz-text-muted"><span>2:</span> {{ $product->barcode2 }}</p>
                                @endif
                            </td>
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
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" data-confirm="¿Estás seguro de eliminar el producto &quot;{{ $product->name }}&quot;? Esta acción no se puede deshacer." onsubmit="return confirm(this.dataset.confirm)" class="ml-1">
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
                            <td colspan="6" class="px-4 py-14 text-center">
                                <p class="text-sm font-medium text-mz-text-secondary">Sin productos con esos filtros</p>
                                <a href="{{ route('admin.products.index') }}" class="mt-2 inline-block text-xs font-medium text-mz-accent-primary hover:underline">Limpiar filtros</a>
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
