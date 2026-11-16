@props(['product', 'class' => '', 'iconClass' => 'size-16 sm:size-20'])

<div {{ $attributes->merge(['class' => 'relative flex items-center justify-center overflow-hidden rounded-lg bg-ink-900 blueprint-grid ' . $class]) }}>
    <span class="absolute left-3 top-3 font-display text-[11px] uppercase tracking-[0.18em] text-white/60">{{ $product->sku }}</span>
    <x-icon name="moto" class="{{ $iconClass }} text-mz-accent-primary/80 transition duration-300 group-hover:scale-105 group-hover:text-mz-accent-primary" />
    <span class="absolute -bottom-8 -right-8 size-24 rounded-full border border-white/10"></span>
    <span class="absolute bottom-3 right-3 font-display text-[11px] uppercase tracking-[0.18em] text-white/50">{{ $product->brand }}</span>
    @if ($product->image_path)
        <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="absolute inset-0 size-full bg-white object-contain p-4" width="640" height="480" loading="lazy" decoding="async" onerror="this.hidden = true">
    @endif
</div>
