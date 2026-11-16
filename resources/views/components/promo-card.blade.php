@props(['card'])

@php
    $destination = $card->destination();
    $isFeaturedCard = $card->section === 'products';
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:border-mz-accent-primary">
    @if ($card->galleryUrls() !== [])
        <x-carousel :images="$card->galleryUrls()" :alt="$card->title" class="aspect-[4/3] bg-slate-50" data-carousel-auto="{{ $isFeaturedCard ? 4000 : 0 }}" />
    @else
        <span class="flex aspect-[4/3] items-center justify-center bg-slate-50 text-mz-accent-primary">
            <x-icon name="tag" class="size-10" />
        </span>
    @endif

    <div class="flex flex-1 flex-col p-4">
        @if (! $card->exists)
            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-orange-800">Ejemplo</p>
        @endif

        <h3 class="text-sm font-semibold text-ink-900">{{ $card->title }}</h3>

        @if (! $isFeaturedCard && $card->description)
            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $card->description }}</p>
        @endif

        <div class="mt-auto pt-4">
            @if ($card->price !== null)
                <div class="flex flex-wrap items-baseline gap-2">
                    <p class="font-display text-xl font-semibold text-ink-900">${{ number_format((float) ($card->sale_price ?? $card->price), 2) }}</p>
                    @if ($card->hasDiscount())
                        <p class="text-sm text-slate-400 line-through">${{ number_format((float) $card->price, 2) }}</p>
                        <p class="rounded bg-mz-accent-primary/10 px-2 py-0.5 text-xs font-semibold text-orange-700">Oferta</p>
                    @endif
                </div>
            @endif

            @if ($destination)
                <a href="{{ $destination }}" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-mz-accent-primary hover:text-mz-accent-hover">
                    Ver más
                    <x-icon name="arrow-right" class="size-4" />
                </a>
            @endif
        </div>
    </div>
</article>
