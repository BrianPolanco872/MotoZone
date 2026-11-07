@props([
    'variant' => 'secondary',
    'label' => null,
])

<?php
$variantClasses = match ($variant) {
    'available' => 'mz-badge-available',
    'low' => 'mz-badge-low',
    'out' => 'mz-badge-out',
    'pending' => 'mz-badge-pending',
    'approved' => 'mz-badge-approved',
    'rejected' => 'mz-badge-rejected',
    'archived' => 'mz-badge-archived',
    default => 'mz-badge-low',
};
?>

<span {{ $attributes->merge(['class' => 'mz-badge ' . $variantClasses]) }}>
    {{ $label ?? $slot }}
</span>
