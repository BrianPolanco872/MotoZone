@props(['images' => [], 'alt' => '', 'imageClass' => 'size-full object-cover'])

@php($photos = array_values(array_filter($images ?? [])))

@if ($photos !== [])
    <div {{ $attributes->merge(['class' => 'relative overflow-hidden']) }} data-carousel>
        <div class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth" data-carousel-track>
            @foreach ($photos as $index => $src)
                <img src="{{ $src }}" alt="{{ $index === 0 ? $alt : $alt.' (foto '.($index + 1).')' }}" class="w-full shrink-0 snap-center {{ $imageClass }}" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.hidden = true">
            @endforeach
        </div>
        @if (count($photos) > 1)
            <button type="button" data-carousel-prev aria-label="Foto anterior" class="absolute left-2 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/60 text-lg text-white hover:bg-black/80">‹</button>
            <button type="button" data-carousel-next aria-label="Foto siguiente" class="absolute right-2 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/60 text-lg text-white hover:bg-black/80">›</button>
            <p class="absolute bottom-2 right-2 rounded bg-black/60 px-2 py-0.5 text-xs text-white">{{ count($photos) }} fotos</p>
        @endif
    </div>
@endif
