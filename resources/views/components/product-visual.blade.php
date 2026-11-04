@props(['product', 'class' => '', 'iconClass' => 'size-16 sm:size-20'])

<div {{ $attributes->merge(['class' => 'relative flex items-center justify-center overflow-hidden rounded-lg bg-ink-900 blueprint-grid ' . $class]) }}>
    <span class="absolute left-3 top-3 font-display text-[11px] uppercase tracking-[0.18em] text-white/60">{{ $product->sku }}</span>
    <x-icon :name="$product->category->icon()" class="{{ $iconClass }} text-brand-500/80 transition duration-300 group-hover:scale-105 group-hover:text-brand-400" />
    <span class="absolute -bottom-8 -right-8 size-24 rounded-full border border-white/10"></span>
    <span class="absolute bottom-3 right-3 font-display text-[11px] uppercase tracking-[0.18em] text-white/50">{{ $product->category->name }}</span>
</div>
