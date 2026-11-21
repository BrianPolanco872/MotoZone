@props(['product'])

@php
    $availableStock = $product->availableStock();
    $label = match (true) {
        ! $product->isKit() => $product->stockLabel(),
        $availableStock === null => 'Sin control de inventario',
        ! ($availableStock > 0) => 'Agotado',
        default => \App\Support\Quantity::format($availableStock).' paquete(s) disponibles',
    };
    $classes = match (true) {
        $availableStock !== null && ! ($availableStock > 0) => 'bg-slate-100 text-slate-500 ring-slate-400/20',
        $product->isLowStock() => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        default => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {$classes}"]) }}>
    <span class="size-1.5 rounded-full bg-current"></span>
    {{ $label }}
</span>
