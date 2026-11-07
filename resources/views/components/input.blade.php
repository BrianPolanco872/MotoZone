@props([
    'type' => 'text',
    'id' => null,
    'name' => null,
    'value' => old($name ?? null),
    'label' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'helper' => null,
])

<label for="{{ $id }}" class="block text-sm font-medium text-mz-text-secondary mb-1">{{ $label }}</label>

<input
    type="{{ $type }}"
    id="{{ $id }}"
    name="{{ $name }}"
    value="{{ $value }}"
    placeholder="{{ $placeholder }}"
    {{ $required ? 'required' : '' }}
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge(['class' => 'mz-input' . ($error ? ' border-mz-error focus:ring-mz-error' : '')]) }} />
@php
    // Fallback for non-Livewire attribute merging
    $inputClass = 'mz-input';
    if ($error) {
        $inputClass .= ' border-mz-error focus:ring-mz-error';
    }
@endphp

@if ($error)
    <p class="mt-1 text-xs font-medium text-mz-error">{{ $error }}</p>
@endif

@if ($helper)
    <p class="mt-1 text-xs text-mz-text-muted">{{ $helper }}</p>
@endif
