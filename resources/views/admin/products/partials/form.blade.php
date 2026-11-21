@php
    $isEdit = $product->exists;
    $labelClass = 'block text-sm font-medium text-mz-text-secondary';
    $errorClass = 'mt-1 text-xs font-medium text-mz-error';
    $saleMode = old('sale_mode', $product->saleMode());
    $isKit = $saleMode === \App\Models\Product::MODE_KIT;
    $stockStep = $saleMode === \App\Models\Product::MODE_BULK ? '0.001' : '1';
@endphp

@if ($errors->any())
    <x-alert variant="error">
        <p class="font-semibold">Revisa los siguientes campos:</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif

<form id="product-form" data-product-form data-components-config="{{ json_encode(['url' => route('admin.products.components'), 'exclude' => $product->id], JSON_THROW_ON_ERROR) }}" action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
        <input type="hidden" name="original_stock" value="{{ old('original_stock', $product->stock) }}">
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            {{-- Información del producto --}}
            <x-card padded>
                <div>
                    <h2 class="text-base font-semibold text-mz-text-primary">Información del producto</h2>
                    <p class="text-xs text-mz-text-secondary">Datos que verá el cliente en la ficha del producto.</p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="{{ $labelClass }}">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" required class="mz-input mt-1.5 h-11 w-full" placeholder="Kit de arrastre 428H">
                        @error('name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="brand" class="{{ $labelClass }}">Marca (opcional)</label>
                        <input id="brand" name="brand" type="text" value="{{ old('brand', $product->brand) }}" class="mz-input mt-1.5 h-11 w-full" placeholder="DID">
                        @error('brand') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="description" class="{{ $labelClass }}">Descripción (opcional)</label>
                        <textarea id="description" name="description" rows="4" class="mz-input mt-1.5 w-full min-h-[100px]" placeholder="Describe la pieza, compatibilidad y materiales.">{{ old('description', $product->description) }}</textarea>
                        @error('description') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-card>

            <x-card padded>
                <fieldset aria-describedby="sale-mode-help sale-mode-error">
                    <legend class="text-base font-semibold text-mz-text-primary">¿Cómo se vende este producto?</legend>
                    <p id="sale-mode-help" class="mt-1 text-xs text-mz-text-secondary">El precio y costo capturados corresponden al producto o paquete completo.</p>
                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:gap-5">
                        @foreach (['unit' => 'Por unidad/pieza', 'bulk' => 'A granel', 'kit' => 'Como paquete (kit)'] as $mode => $label)
                            <label for="sale_mode_{{ $mode }}" class="flex items-center gap-2 text-sm text-mz-text-secondary">
                                <input id="sale_mode_{{ $mode }}" type="radio" name="sale_mode" value="{{ $mode }}" @checked($saleMode === $mode) required class="mz-checkbox">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <p id="sale-mode-error" class="{{ $errorClass }}">{{ $errors->first('sale_mode') }}</p>
                </fieldset>

                <fieldset data-kit-fields @disabled(! $isKit) @if (! $isKit) hidden @endif class="mt-5 space-y-4" aria-describedby="kit-items-error">
                    <legend class="text-sm font-semibold text-mz-text-primary">Contenido del paquete</legend>
                    <div>
                        <label for="component-search" class="{{ $labelClass }}">Buscar por nombre o código de barras</label>
                        <input id="component-search" type="search" autocomplete="off" class="mz-input mt-1.5 h-11 w-full" aria-describedby="component-search-help component-status" aria-controls="component-results">
                        <p id="component-search-help" class="mt-1 text-xs text-mz-text-secondary">Agrega productos por pieza o a granel. No se permiten otros paquetes.</p>
                        <p id="component-status" data-component-status role="status" aria-live="polite" class="mt-1 text-xs text-mz-text-secondary"></p>
                        <ul id="component-results" data-component-results class="mt-2 space-y-2"></ul>
                    </div>

                    <div data-kit-rows class="space-y-3">
                        @foreach ($kitRows as $row)
                            <div data-kit-row data-index="{{ $row['index'] }}" class="rounded-md border border-mz-border p-3">
                                <p class="text-sm font-medium text-mz-text-primary">{{ $row['name'] }}</p>
                                <p class="text-xs text-mz-text-secondary">{{ $row['sale_mode'] === 'bulk' ? 'A granel' : 'Por unidad/pieza' }}{{ ! $row['track_inventory'] ? ' · Sin control de inventario' : '' }}</p>
                                <input data-component-id type="hidden" name="kit_items[{{ $row['index'] }}][product_id]" value="{{ $row['product_id'] }}">
                                @error('kit_items.'.$row['index'].'.product_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                <div class="mt-2 flex items-end gap-3">
                                    <div class="min-w-0 flex-1">
                                        <label for="kit_quantity_{{ $row['index'] }}" class="{{ $labelClass }}">Cantidad por paquete de {{ $row['name'] }}</label>
                                        <input id="kit_quantity_{{ $row['index'] }}" name="kit_items[{{ $row['index'] }}][quantity]" type="number" min="{{ $row['sale_mode'] === 'bulk' ? '0.001' : '1' }}" step="{{ $row['sale_mode'] === 'bulk' ? '0.001' : '1' }}" max="4294967295" value="{{ $row['quantity'] }}" required aria-invalid="{{ $errors->has('kit_items.'.$row['index'].'.quantity') ? 'true' : 'false' }}" aria-describedby="kit_quantity_error_{{ $row['index'] }}" class="mz-input mt-1.5 h-11 w-full">
                                    </div>
                                    <button type="button" data-remove-component class="x-button x-button-secondary size-sm" aria-label="Quitar {{ $row['name'] }} del paquete">Quitar</button>
                                </div>
                                <p id="kit_quantity_error_{{ $row['index'] }}" class="{{ $errorClass }}">{{ $errors->first('kit_items.'.$row['index'].'.quantity') }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p id="kit-items-error" class="{{ $errorClass }}">{{ $errors->first('kit_items') }}</p>
                    <input type="hidden" name="kit_deduct_components" value="0">
                    <label for="kit_deduct_components" class="flex items-start gap-3 text-sm text-mz-text-secondary">
                        <input id="kit_deduct_components" name="kit_deduct_components" type="checkbox" value="1" @checked(old('kit_deduct_components', $product->kit_deduct_components ?? false)) class="mz-checkbox mt-0.5">
                        <span>Descontar del inventario los productos del paquete al vender</span>
                    </label>
                    <p class="text-xs text-mz-text-secondary">El paquete no tiene existencia propia. Los componentes sin control de inventario no se descuentan.</p>
                    <noscript><p class="text-sm text-mz-text-secondary">Activa JavaScript para buscar, agregar o quitar componentes. No se puede guardar un paquete vacío.</p></noscript>
                </fieldset>
            </x-card>

            {{-- Identificación --}}
            <x-card padded>
                <div>
                    <h2 class="text-base font-semibold text-mz-text-primary">Identificación</h2>
                    <p class="text-xs text-mz-text-secondary">Los dos códigos pueden capturarse con el escáner del punto de venta.</p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="barcode" class="{{ $labelClass }}">Código de barras</label>
                        <input id="barcode" name="barcode" type="text" value="{{ old('barcode', $product->barcode) }}" class="mz-input mt-1.5 h-11 w-full font-mono" placeholder="7501234567890">
                        @error('barcode') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="barcode2" class="{{ $labelClass }}">Código de barras 2</label>
                        <input id="barcode2" name="barcode2" type="text" value="{{ old('barcode2', $product->barcode2) }}" class="mz-input mt-1.5 h-11 w-full font-mono" placeholder="00(ABC)+123">
                        @error('barcode2') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-card>

            {{-- Precios --}}
            <x-card padded>
                <div>
                    <h2 class="text-base font-semibold text-mz-text-primary">Precios</h2>
                    <p class="text-xs text-mz-text-secondary">El precio de venta es final e incluye IVA. La ganancia se calcula sobre el costo.</p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="cost" class="{{ $labelClass }}">Precio costo (MXN)</label>
                        <input id="cost" name="cost" type="number" step="0.01" min="0" value="{{ $product->cost }}" class="mz-input mt-1.5 h-11 w-full" placeholder="0.00">
                        @error('cost') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="margin" class="{{ $labelClass }}">Ganancia (%)</label>
                        <input id="margin" type="number" step="0.01" value="{{ old('margin', $product->marginPercentage() ?? 30) }}" class="mz-input mt-1.5 h-11 w-full" placeholder="30">
                        <p class="mt-1 text-xs text-mz-text-secondary">Al escribir el costo y la ganancia, el precio de venta se calcula solo.</p>
                    </div>

                    <div>
                        <label for="price" class="{{ $labelClass }}">Precio venta (MXN)</label>
                        <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $product->price) }}" required class="mz-input mt-1.5 h-11 w-full" placeholder="0.00">
                        @error('price') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="wholesale_price" class="{{ $labelClass }}">Precio mayoreo (MXN) <span class="font-normal text-mz-text-muted">(opcional)</span></label>
                        <input id="wholesale_price" name="wholesale_price" type="number" step="0.01" min="0" value="{{ old('wholesale_price', $product->wholesale_price) }}" class="mz-input mt-1.5 h-11 w-full" placeholder="0.00">
                        @error('wholesale_price') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tax_rate" class="{{ $labelClass }}">Tasa de IVA (%)</label>
                        <input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100" value="{{ old('tax_rate', $isEdit ? (float) $product->tax_rate * 100 : 16) }}" required class="mz-input mt-1.5 h-11 w-full" placeholder="16">
                        @error('tax_rate') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-card>

            {{-- Inventario --}}
            <x-card padded>
                <div>
                    <h2 class="text-base font-semibold text-mz-text-primary">Inventario</h2>
                    <p class="text-xs text-mz-text-secondary">Si el producto no usa inventario, se puede vender aunque la existencia sea cero.</p>
                </div>

                <fieldset data-product-inventory @disabled($isKit) @if ($isKit) hidden @endif class="mt-4 space-y-4">
                    <input type="hidden" name="track_inventory" value="0">
                    <label class="flex items-start gap-3 text-sm text-mz-text-secondary">
                        <input id="track_inventory" name="track_inventory" type="checkbox" value="1" @checked(old('track_inventory', $isEdit ? $product->track_inventory : true)) class="mz-checkbox mt-0.5">
                        <span>
                            <span class="font-medium">Este producto usa inventario</span>
                            <span class="block text-xs text-mz-text-muted">Al venderlo se descuenta la existencia y se registra el movimiento.</span>
                        </span>
                    </label>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="stock" class="{{ $labelClass }}">Cantidad actual</label>
                            <input id="stock" name="stock" type="number" step="{{ $stockStep }}" min="0" max="4294967295" value="{{ old('stock', $product->stock ?? 0) }}" required class="mz-input mt-1.5 h-11 w-full" placeholder="0">
                            @error('stock') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="min_stock" class="{{ $labelClass }}">Mínimo para alerta</label>
                            <input id="min_stock" name="min_stock" type="number" step="{{ $stockStep }}" min="0" max="4294967295" value="{{ old('min_stock', $product->min_stock ?? 0) }}" class="mz-input mt-1.5 h-11 w-full" placeholder="0">
                            <p class="mt-1 text-xs text-mz-text-secondary">Se avisa cuando quede esta cantidad o menos.</p>
                            @error('min_stock') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </fieldset>
            </x-card>
        </div>

        <div class="space-y-5">
            {{-- Imagen --}}
            <x-card padded>
                <div>
                    <h2 class="text-base font-semibold text-mz-text-primary">Imagen</h2>
                    <p class="text-xs text-mz-text-secondary">JPG, PNG o WebP. Máximo 2 MB.</p>
                </div>

                @if ($isEdit && $product->image_path)
                    <div class="mt-4">
                        <img src="{{ asset('storage/'.$product->image_path) }}" alt="Imagen actual de {{ $product->name }}" class="aspect-square w-full rounded-md border border-mz-border object-cover">
                    </div>
                @endif

                <div class="mt-4">
                    <label for="image" class="{{ $labelClass }}">{{ $isEdit ? 'Reemplazar imagen' : 'Subir imagen' }}</label>
                    <input id="image" name="image" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-mz-text-secondary file:mr-3 file:rounded-md file:border-0 file:bg-mz-bg-surface-2 file:px-3 file:py-2 file:text-sm file:font-medium file:text-mz-text-secondary hover:file:bg-mz-bg-surface-2">
                    @error('image') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>
            </x-card>

            {{-- Publicación --}}
            <x-card padded>
                <div>
                    <h2 class="text-base font-semibold text-mz-text-primary">Publicación</h2>
                </div>

                <div class="mt-4 space-y-3">
                    <label class="flex items-start gap-3 text-sm text-mz-text-secondary">
                        <input type="checkbox" name="featured" value="1" @checked(old('featured', $product->featured)) class="mz-checkbox mt-0.5">
                        <span>
                            <span class="font-medium">Destacado en POS (legado)</span>
                            <span class="block text-xs text-mz-text-muted">Conserva el destacado del punto de venta. No selecciona productos para la portada.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 text-sm text-mz-text-secondary">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $isEdit ? $product->is_active : true)) class="mz-checkbox mt-0.5">
                        <span>
                            <span class="font-medium">Ficha pública activa</span>
                            <span class="block text-xs text-mz-text-muted">Desmárcalo para ocultarlo sin eliminarlo.</span>
                        </span>
                    </label>
                </div>
            </x-card>
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.products.index') }}" class="x-button x-button-secondary size-sm">
            Cancelar
        </a>
        <button type="submit" class="x-button x-button-primary size-sm">
            <x-icon name="check" class="size-4" />
            {{ $submitLabel }}
        </button>
    </div>
</form>
