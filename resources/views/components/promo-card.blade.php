@props(['card'])

@php
    $destination = $card->destination();
    $isFeaturedCard = $card->section === 'products';
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-orange-500/50 hover:shadow-xl focus-within:-translate-y-1 focus-within:border-orange-500/50 focus-within:shadow-xl">
    @if ($card->galleryUrls() !== [])
        <div class="relative aspect-[4/3] w-full overflow-hidden border-b border-slate-100 bg-white p-4">
            <x-carousel :images="$card->galleryUrls()" :alt="$card->title" class="size-full" image-class="size-full object-contain transition-transform duration-300 group-hover:scale-105" data-carousel-auto="{{ $isFeaturedCard ? 4000 : 0 }}" data-carousel-effect="depth" />
        </div>
    @else
        <div class="flex aspect-[4/3] w-full items-center justify-center border-b border-slate-100 bg-slate-50 p-4 text-orange-600">
            <x-icon name="tag" class="size-10 transition-transform duration-300 group-hover:scale-110" />
        </div>
    @endif

    <div class="flex flex-1 flex-col p-5">
        @if (! $card->exists)
            <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-orange-700">Ejemplo</p>
        @endif

        <h3 class="font-display text-base font-bold uppercase leading-snug tracking-tight text-slate-900 transition-colors group-hover:text-orange-600">{{ $card->title }}</h3>

        @if (! $isFeaturedCard && $card->description)
            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $card->description }}</p>
        @endif

        <div class="mt-auto pt-4">
            @if ($card->price !== null)
                <div class="mb-3 flex flex-wrap items-baseline gap-2">
                    <p class="font-display text-2xl font-extrabold text-slate-900">${{ number_format((float) ($card->sale_price ?? $card->price), 2) }}</p>
                    @if ($card->hasDiscount())
                        <p class="text-sm font-semibold text-slate-400 line-through">${{ number_format((float) $card->price, 2) }}</p>
                        <span class="rounded-full border border-orange-200 bg-orange-50 px-2 py-0.5 text-[11px] font-bold text-orange-700">Oferta</span>
                    @endif
                </div>
            @endif

            @if ($destination)
                <a href="{{ $destination }}" class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-xl bg-orange-600 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-orange-600/20 transition-all duration-200 hover:-translate-y-0.5 hover:bg-orange-500 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">
                    Ver más
                    <x-icon name="arrow-right" class="size-4" />
                </a>
            @endif
        </div>
    </div>
</article>
