@props([
    'striped' => false,
    'hover' => true,
])

<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <table class="mz-table">
        <thead>
            {{ $attributes->slot ?? '' }}
        </thead>
    </table>
</div>
