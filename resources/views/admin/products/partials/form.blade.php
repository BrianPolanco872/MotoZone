@php
    $isEdit = $product->exists;
    $labelClass = 'block text-sm font-medium text-mz-text-secondary';
    $errorClass = 'mt-1 text-xs font-medium text-mz-error';
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

<form id="product-form" data-product-form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            {{-- Información del producto --}}
            <x-card padded>
                <div>
                    <h2 class="text-base font-semibold text-mz-text-primary">Información del producto</h2>
                    <p class="text-xs text-mz-text-secondary">Datos que verá el cliente en el catálogo.</p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="{{ $labelClass }}">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" required class="mz-input mt-1.5 h-11 w-full" placeholder="Kit de arrastre 428H">
                        @error('name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="brand" class="{{ $labelClass }}">Marca</label>
                        <input id="brand" name="brand" type="text" value="{{ old('brand', $product->brand) }}" required class="mz-input mt-1.5 h-11 w-full" placeholder="DID">
                        @error('brand') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="category_id" class="{{ $labelClass }}">Categoría</label>
                        <select id="category_id" name="category_id" required class="mz-select mt-1.5 h-11 w-full">
                            <option value="">Selecciona una categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="description" class="{{ $labelClass }}">Descripción</label>
                        <textarea id="description" name="description" rows="4" required class="mz-input mt-1.5 w-full min-h-[100px]" placeholder="Describe la pieza, compatibilidad y materiales.">{{ old('description', $product->description) }}</textarea>
                        @error('description') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-card>

            {{-- Identificación --}}
            <x-card padded>
                <div>
                    <h2 class="text-base font-semibold text-mz-text-primary">Identificación</h2>
                    <p class="text-xs text-mz-text-secondary">El código de barras es el que se escanea en el punto de venta.</p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="barcode" class="{{ $labelClass }}">Código de barras</label>
                        <input id="barcode" name="barcode" type="text" value="{{ old('barcode', $product->barcode) }}" class="mz-input mt-1.5 h-11 w-full font-mono" placeholder="7501234567890">
                        @error('barcode') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="sku" class="{{ $labelClass }}">SKU</label>
                        <input id="sku" name="sku" type="text" value="{{ old('sku', $product->sku) }}" required class="mz-input mt-1.5 h-11 w-full font-mono" placeholder="KIT-428H">
                        @error('sku') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
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

                <div class="mt-4 space-y-4">
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
                            <input id="stock" name="stock" type="number" step="1" min="0" value="{{ old('stock', $product->stock ?? 0) }}" required class="mz-input mt-1.5 h-11 w-full" placeholder="0">
                            @error('stock') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="min_stock" class="{{ $labelClass }}">Mínimo para alerta</label>
                            <input id="min_stock" name="min_stock" type="number" step="1" min="0" value="{{ old('min_stock', $product->min_stock ?? 0) }}" class="mz-input mt-1.5 h-11 w-full" placeholder="0">
                            <p class="mt-1 text-xs text-mz-text-secondary">Se avisa cuando queden estas piezas o menos.</p>
                            @error('min_stock') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
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
                            <span class="font-medium">Producto destacado</span>
                            <span class="block text-xs text-mz-text-muted">Se muestra en la página de inicio.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 text-sm text-mz-text-secondary">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $isEdit ? $product->is_active : true)) class="mz-checkbox mt-0.5">
                        <span>
                            <span class="font-medium">Publicado en el catálogo</span>
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
