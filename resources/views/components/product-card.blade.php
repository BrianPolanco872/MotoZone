@props(['product'])

<article class="group relative flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-xl hover:shadow-slate-900/5">
    <a href="{{ route('catalog.show', $product) }}" class="block p-2" aria-label="Ver {{ $product->name }}">
        <x-product-visual :product="$product" class="aspect-[4/3]" />
    </a>

    <div class="flex flex-1 flex-col px-4 pb-4 pt-2">
        <div class="flex items-center justify-between gap-2">
            <span class="font-display text-xs uppercase tracking-[0.16em] text-slate-500">{{ $product->brand }}</span>
            <x-stock-badge :product="$product" />
        </div>

        <h3 class="mt-2 text-sm font-medium leading-snug text-slate-900">
            <a href="{{ route('catalog.show', $product) }}" class="transition hover:text-brand-700">{{ $product->name }}</a>
        </h3>

        <div class="mt-auto flex items-end justify-between pt-4">
            <p class="font-display text-2xl font-semibold leading-none text-slate-900">${{ number_format((float) $product->price, 2) }}</p>
            <a href="{{ route('catalog.show', $product) }}" class="flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition group-hover:border-brand-600 group-hover:bg-brand-800 group-hover:text-white" aria-label="Consultar {{ $product->name }}">
                <x-icon name="arrow-right" class="size-4" />
            </a>
        </div>
    </div>
</article>
