@php
    $isEdit = $product->exists;
    $inputClass = 'mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20';
    $labelClass = 'block text-sm font-medium text-slate-700';
    $errorClass = 'mt-1 text-xs font-medium text-rose-600';
@endphp

@if ($errors->any())
    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
        <p class="font-semibold">Revisa los siguientes campos:</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form id="product-form" data-product-form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Información del producto</h2>
                <p class="text-xs text-slate-500">Datos que verá el cliente en el catálogo.</p>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="{{ $labelClass }}">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" required class="{{ $inputClass }}" placeholder="Kit de arrastre 428H">
                        @error('name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="brand" class="{{ $labelClass }}">Marca</label>
                        <input id="brand" name="brand" type="text" value="{{ old('brand', $product->brand) }}" required class="{{ $inputClass }}" placeholder="DID">
                        @error('brand') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="category_id" class="{{ $labelClass }}">Categoría</label>
                        <select id="category_id" name="category_id" required class="{{ $inputClass }}">
                            <option value="">Selecciona una categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="description" class="{{ $labelClass }}">Descripción</label>
                        <textarea id="description" name="description" rows="4" required class="mt-1.5 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="Describe la pieza, compatibilidad y materiales.">{{ old('description', $product->description) }}</textarea>
                        @error('description') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Identificación</h2>
                <p class="text-xs text-slate-500">El código de barras es el que se escanea en el punto de venta.</p>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="barcode" class="{{ $labelClass }}">Código de barras</label>
                        <input id="barcode" name="barcode" type="text" value="{{ old('barcode', $product->barcode) }}" class="{{ $inputClass }} font-mono" placeholder="7501234567890">
                        @error('barcode') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="sku" class="{{ $labelClass }}">SKU</label>
                        <input id="sku" name="sku" type="text" value="{{ old('sku', $product->sku) }}" required class="{{ $inputClass }} font-mono" placeholder="KIT-428H">
                        @error('sku') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Precios</h2>
                <p class="text-xs text-slate-500">El precio de venta es final e incluye IVA. La ganancia se calcula sobre el costo.</p>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="cost" class="{{ $labelClass }}">Precio costo (MXN)</label>
                        <input id="cost" name="cost" type="number" step="0.01" min="0" value="{{ $product->cost }}" class="{{ $inputClass }}" placeholder="0.00">
                        @error('cost') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="margin" class="{{ $labelClass }}">Ganancia (%)</label>
                        <input id="margin" type="number" step="0.01" value="{{ old('margin', $product->marginPercentage() ?? 30) }}" class="{{ $inputClass }}" placeholder="30">
                        <p class="mt-1 text-xs text-slate-500">Al escribir el costo y la ganancia, el precio de venta se calcula solo.</p>
                    </div>

                    <div>
                        <label for="price" class="{{ $labelClass }}">Precio venta (MXN)</label>
                        <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $product->price) }}" required class="{{ $inputClass }}" placeholder="0.00">
                        @error('price') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="wholesale_price" class="{{ $labelClass }}">Precio mayoreo (MXN) <span class="font-normal text-slate-400">(opcional)</span></label>
                        <input id="wholesale_price" name="wholesale_price" type="number" step="0.01" min="0" value="{{ old('wholesale_price', $product->wholesale_price) }}" class="{{ $inputClass }}" placeholder="0.00">
                        @error('wholesale_price') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tax_rate" class="{{ $labelClass }}">Tasa de IVA (%)</label>
                        <input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100" value="{{ old('tax_rate', $isEdit ? (float) $product->tax_rate * 100 : 16) }}" required class="{{ $inputClass }}" placeholder="16">
                        @error('tax_rate') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Inventario</h2>
                <p class="text-xs text-slate-500">Si el producto no usa inventario, se puede vender aunque la existencia sea cero.</p>

                <div class="mt-5 space-y-4">
                    <input type="hidden" name="track_inventory" value="0">
                    <label class="flex items-start gap-3 text-sm text-slate-700">
                        <input id="track_inventory" name="track_inventory" type="checkbox" value="1" @checked(old('track_inventory', $isEdit ? $product->track_inventory : true)) class="mt-0.5 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="font-medium">Este producto usa inventario</span>
                            <span class="block text-xs text-slate-500">Al venderlo se descuenta la existencia y se registra el movimiento.</span>
                        </span>
                    </label>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="stock" class="{{ $labelClass }}">Cantidad actual</label>
                            <input id="stock" name="stock" type="number" step="1" min="0" value="{{ old('stock', $product->stock ?? 0) }}" required class="{{ $inputClass }}" placeholder="0">
                            @error('stock') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="min_stock" class="{{ $labelClass }}">Mínimo para alerta</label>
                            <input id="min_stock" name="min_stock" type="number" step="1" min="0" value="{{ old('min_stock', $product->min_stock ?? 0) }}" class="{{ $inputClass }}" placeholder="0">
                            <p class="mt-1 text-xs text-slate-500">Se avisa cuando queden estas piezas o menos.</p>
                            @error('min_stock') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Imagen</h2>
                <p class="text-xs text-slate-500">JPG, PNG o WebP. Máximo 2 MB.</p>

                @if ($isEdit && $product->image_path)
                    <img src="{{ asset('storage/'.$product->image_path) }}" alt="Imagen actual de {{ $product->name }}" class="mt-4 aspect-square w-full rounded-lg border border-slate-200 object-cover">
                @endif

                <div class="mt-4">
                    <label for="image" class="{{ $labelClass }}">{{ $isEdit ? 'Reemplazar imagen' : 'Subir imagen' }}</label>
                    <input id="image" name="image" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                    @error('image') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Publicación</h2>

                <div class="mt-4 space-y-3">
                    <label class="flex items-start gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="featured" value="1" @checked(old('featured', $product->featured)) class="mt-0.5 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="font-medium">Producto destacado</span>
                            <span class="block text-xs text-slate-500">Se muestra en la página de inicio.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $isEdit ? $product->is_active : true)) class="mt-0.5 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="font-medium">Publicado en el catálogo</span>
                            <span class="block text-xs text-slate-500">Desmárcalo para ocultarlo sin eliminarlo.</span>
                        </span>
                    </label>
                </div>
            </section>
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.products.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancelar</a>
        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 text-sm font-semibold text-white transition hover:bg-brand-800">
            <x-icon name="check" class="size-4" />
            {{ $submitLabel }}
        </button>
    </div>
</form>
