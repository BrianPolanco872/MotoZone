@php($photos = $gallery ?? [])
<fieldset class="space-y-3 rounded-md border border-mz-border p-4">
    <legend class="px-1 text-sm font-medium">{{ $label }}</legend>
    <p class="text-sm text-mz-text-muted">{{ $help ?? 'Sube hasta 6 fotos desde tu dispositivo. La primera se usa como portada.' }} JPEG, PNG o WebP, hasta 20 MB cada una.</p>
    @if ($photos !== [])
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($photos as $path)
                <li class="space-y-1">
                    <img src="{{ Storage::disk('public')->url($path) }}" alt="Foto {{ $loop->iteration }}" loading="lazy" class="h-24 w-full rounded-md object-cover">
                    <label class="flex items-center gap-2 text-xs text-mz-text-secondary">
                        <input type="checkbox" name="remove_gallery[]" value="{{ $path }}"> Quitar
                    </label>
                </li>
            @endforeach
        </ul>
    @endif
    <div>
        <label for="{{ $name ?? 'gallery' }}-files" class="mb-1 block text-sm">Agregar fotos</label>
        <input id="{{ $name ?? 'gallery' }}-files" type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple class="mz-input w-full">
    </div>
    @error('gallery')<p class="text-sm text-mz-error">{{ $message }}</p>@enderror
    @error('gallery.*')<p class="text-sm text-mz-error">{{ $message }}</p>@enderror
</fieldset>
