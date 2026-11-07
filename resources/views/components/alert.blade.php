@props([
    'variant' => 'info',
    'dismissible' => false,
])

<?php
$variantClasses = match ($variant) {
    'success' => 'mz-alert-success',
    'error' => 'mz-alert-error',
    'warning' => 'mz-alert-warning',
    'info' => 'mz-alert-info',
    default => 'mz-alert-info',
};

$icons = [
    'success' => 'check',
    'error' => 'alert',
    'warning' => 'alert',
    'info' => 'info',
];
?>

<div {{ $attributes->merge(['class' => 'mz-alert ' . $variantClasses]) }} role="alert">
    <div class="flex items-start gap-3">
        <x-icon name="{{ $icons[$variant] ?? 'info' }}" class="mt-0.5 size-4 shrink-0" />
        <div>
            {{ $slot }}
        </div>
    </div>

    @if ($dismissible)
        <button type="button" class="ml-auto -mr-1 -mt-1 rounded-md p-1.5 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-colors" aria-label="Cerrar alerta">
            <x-icon name="x" class="size-4" />
        </button>
    @endif
</div>
