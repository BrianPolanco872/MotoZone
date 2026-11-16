@props(['images' => [], 'alt' => '', 'imageClass' => 'size-full object-cover'])

@php($photos = array_values(array_filter($images ?? [])))

@if ($photos !== [])
    <div {{ $attributes->merge(['class' => 'relative overflow-hidden']) }} data-carousel>
        <div class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" data-carousel-track>
            @foreach ($photos as $index => $src)
                <img src="{{ $src }}" alt="{{ $index === 0 ? $alt : $alt.' (foto '.($index + 1).')' }}" class="w-full shrink-0 snap-center {{ $imageClass }}" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.hidden = true">
            @endforeach
        </div>
        @if (count($photos) > 1)
            <button type="button" data-carousel-prev aria-label="Foto anterior" class="absolute left-3 top-1/2 z-20 flex size-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-slate-900/60 text-xl font-bold text-white shadow-lg backdrop-blur-md transition-all hover:scale-110 hover:bg-orange-600 focus:outline-none">‹</button>
            <button type="button" data-carousel-next aria-label="Foto siguiente" class="absolute right-3 top-1/2 z-20 flex size-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-slate-900/60 text-xl font-bold text-white shadow-lg backdrop-blur-md transition-all hover:scale-110 hover:bg-orange-600 focus:outline-none">›</button>
            <p class="absolute bottom-3 right-3 z-20 rounded-full border border-white/20 bg-slate-900/70 px-3 py-1 text-[11px] font-semibold text-white shadow-md backdrop-blur-md">{{ count($photos) }} fotos</p>
        @endif
    </div>
@endif
