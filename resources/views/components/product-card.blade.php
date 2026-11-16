@props(['product'])

<article class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-orange-500/50 hover:shadow-xl">
    <a href="{{ route('catalog.show', $product) }}" class="block overflow-hidden border-b border-slate-100 bg-white p-3" aria-label="Ver {{ $product->name }}">
        <x-product-visual :product="$product" class="aspect-[4/3] w-full" />
    </a>

    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center justify-between gap-2">
            <span class="font-display text-xs font-bold uppercase tracking-[0.16em] text-slate-500">{{ $product->brand }}</span>
            <x-stock-badge :product="$product" />
        </div>

        <h3 class="mt-2 text-sm font-semibold leading-snug text-slate-900 transition-colors group-hover:text-orange-600">
            <a href="{{ route('catalog.show', $product) }}">{{ $product->name }}</a>
        </h3>

        <div class="mt-auto flex items-end justify-between gap-3 pt-4">
            <div>
                <p class="font-display text-2xl font-extrabold leading-none text-slate-900">${{ number_format((float) $product->price, 2) }}</p>
                <p class="mt-1 font-mono text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $product->sku }}</p>
            </div>
            <a href="{{ route('catalog.show', $product) }}" class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 shadow-sm transition group-hover:border-orange-500 group-hover:bg-orange-600 group-hover:text-white" aria-label="Consultar {{ $product->name }}">
                <x-icon name="arrow-right" class="size-4" />
            </a>
        </div>
    </div>
</article>
