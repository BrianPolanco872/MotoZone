@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false,
    'loading' => false,
])

<?php
$variantClasses = match ($variant) {
    'primary' => 'mz-button-primary',
    'secondary' => 'mz-button-secondary',
    'ghost' => 'mz-button-ghost',
    'danger' => 'mz-button-danger',
    'success' => 'mz-button-success',
    default => 'mz-button-primary',
};

$sizeClasses = match ($size) {
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
    default => 'px-4 py-2 text-sm',
};

$icon = $attributes->slot ?? null;
?>

<button
    {{ $attributes->merge(['type' => $type, 'disabled' => $disabled || $loading])->class([
        'mz-button',
        $variantClasses,
        $sizeClasses,
    ]) }}
>
    @if ($loading)
        <svg class="size-4 shrink-0 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif
    {{ $slot }}
</button>
