@php
    $mediaId = $mediaId ?? 'media-'.uniqid();
    $galleryPaths = array_values($gallery ?? []);
    $mainUrl = $mainUrl ?? null;
    $mainSourceUrl = $mainSourceUrl ?? '';
    $mainUrlName = $mainUrlName ?? 'image_url';
    $mainUrlError = $mainUrlError ?? $mainUrlName;
    $mainUrlValue = $mainUrlValue ?? '';
    $galleryUrlValue = $galleryUrlValue ?? '';
    $removeMain = $removeMain ?? 'remove_image';
    $removeGallery = $removeGallery ?? 'remove_gallery';
@endphp

<fieldset class="space-y-3 rounded-xl border border-mz-border bg-mz-bg-surface-2/30 p-4" data-media>
    <legend class="px-1 text-sm font-medium text-mz-text-primary">{{ $label }}</legend>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <p class="text-sm font-medium">Foto principal</p>
            <label for="{{ $mediaId }}-main" class="mt-2 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-mz-border-light bg-mz-bg-surface px-4 py-6 text-center transition hover:border-mz-accent-primary hover:bg-mz-bg-surface-2">
                <span class="flex size-10 items-center justify-center rounded-full bg-mz-accent-primary/15 text-mz-accent-primary">
                    <x-icon name="plus" class="size-5" />
                </span>
                <span class="text-sm font-medium">Subir imagen</span>
                <span class="text-xs text-mz-text-muted">JPEG, PNG o WebP · máx. 20 MB</span>
                <input id="{{ $mediaId }}-main" type="file" name="{{ $mainName }}" accept="image/jpeg,image/png,image/webp" class="sr-only" data-media-main>
            </label>

            <label for="{{ $mediaId }}-main-url" class="mt-3 block text-xs font-medium">O pega el enlace directo de la imagen (HTTPS)</label>
            <input id="{{ $mediaId }}-main-url" type="url" name="{{ $mainUrlName }}" value="{{ $mainUrlValue }}" maxlength="2048" placeholder="https://…" class="mz-input mt-1 h-11 w-full" data-media-main-url data-media-original-url="{{ $mainSourceUrl }}">
            <p class="mt-1 text-xs text-mz-text-muted">De Google Imágenes, copia la dirección de la imagen, no la página de resultados. El sitio externo puede registrar la visita.</p>
            <p class="text-xs text-mz-text-muted" role="status" aria-live="polite" data-media-main-url-status></p>
            @error($mainUrlError)<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror

            <div class="mt-3 overflow-hidden rounded-lg border border-mz-border {{ $mainUrl ? '' : 'hidden' }}" data-media-main-wrap>
                <img @if ($mainUrl) src="{{ $mainUrl }}" @endif alt="Vista previa de la foto principal" class="h-40 w-full object-cover" referrerpolicy="no-referrer" data-media-main-preview>
            </div>

            @if ($mainUrl)
                <label class="mt-2 flex items-center gap-2 text-xs text-mz-text-secondary">
                    <input type="checkbox" name="{{ $removeMain }}" value="1" class="rounded border-mz-border-light bg-mz-bg-surface-2 text-mz-accent-primary focus:ring-mz-accent-primary" data-media-main-remove>
                    Quitar foto actual
                </label>
            @endif

            @error($mainName)<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <p class="text-sm font-medium">Carrusel de fotos</p>
            <p class="text-xs text-mz-text-muted">Hasta 6 fotos en total. La primera se usa como portada. Pulsa × y guarda para quitar una foto; Descartar restaura el borrador.</p>

            <div class="mt-2 grid grid-cols-3 gap-2">
                @foreach ($galleryPaths as $path)
                    <label class="group relative block cursor-pointer overflow-hidden rounded-lg border border-mz-border bg-mz-bg-surface" data-media-gallery-item>
                        <img src="{{ str_starts_with(strtolower($path), 'https://') ? $path : Storage::disk('public')->url($path) }}" alt="Foto {{ $loop->iteration }}" class="h-20 w-full object-cover" loading="lazy" referrerpolicy="no-referrer">
                        <input type="checkbox" name="{{ $removeGallery }}[]" value="{{ $path }}" class="peer sr-only" aria-label="Quitar foto {{ $loop->iteration }} del carrusel" data-media-gallery-remove>
                        <span class="absolute right-1 top-1 flex size-6 items-center justify-center rounded-full bg-black/70 text-xs font-bold text-white transition peer-checked:bg-mz-error" aria-hidden="true">×</span>
                    </label>
                @endforeach
            </div>

            <div class="mt-2 grid grid-cols-3 gap-2" data-media-new-gallery></div>

            <label for="{{ $mediaId }}-gallery" class="mt-2 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-mz-border-light px-3 py-2 text-xs font-medium text-mz-text-secondary transition hover:border-mz-accent-primary hover:text-mz-accent-primary">
                <x-icon name="plus" class="size-4" /> Agregar fotos
                <input id="{{ $mediaId }}-gallery" type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" data-media-gallery>
            </label>

            <label for="{{ $mediaId }}-gallery-url" class="mt-3 block text-xs font-medium">O pega un enlace directo de cualquier imagen de Google Imágenes (uno por guardado)</label>
            <input id="{{ $mediaId }}-gallery-url" type="url" name="gallery_url" value="{{ $galleryUrlValue }}" maxlength="2048" placeholder="https://…" class="mz-input mt-1 h-11 w-full" data-media-gallery-url>
            <p class="mt-1 text-xs text-mz-text-muted">Copia la dirección de la imagen, no la página de resultados.</p>
            <div class="mt-2 hidden overflow-hidden rounded-lg border border-mz-border" data-media-gallery-url-preview hidden>
                <img alt="Vista previa del enlace para la galería" class="h-20 w-full object-cover" referrerpolicy="no-referrer" data-media-gallery-url-image>
            </div>
            <p class="text-xs text-mz-text-muted" role="status" aria-live="polite" data-media-gallery-url-status></p>
            @error('gallery_url')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror

            @error('gallery')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
            @error('gallery.*')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>
    </div>

    @if (! empty($help))
        <p class="text-xs text-mz-text-muted">{{ $help }}</p>
    @endif
</fieldset>
