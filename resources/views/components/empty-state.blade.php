@props([
    'icon' => 'search',
    'title' => 'No hay datos',
    'subtitle' => null,
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'mz-empty-state']) }}>
    <div class="mz-empty-state-icon">
        <x-icon name="{{ $icon }}" class="size-6" />
    </div>
    <h3 class="text-sm font-medium text-mz-text-secondary">{{ $title }}</h3>
    @if ($subtitle)
        <p class="mt-1 text-xs text-mz-text-muted">{{ $subtitle }}</p>
    @endif
    @if ($action)
        <div class="mt-4">{{ $action }}</div>
    @endif
</div>
