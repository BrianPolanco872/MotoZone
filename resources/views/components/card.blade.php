@props([
    'padded' => true,
    'header' => null,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => $padded ? 'mz-card mz-card-padded' : 'mz-card p-4 sm:p-6']) }}>
    @if ($header)
        <div class="mb-4">
            {{ $header }}
        </div>
    @endif

    {{ $slot }}

    @if ($footer)
        <div class="mt-4 flex flex-col-reverse sm:flex-row sm:justify-end sm:gap-3">
            {{ $footer }}
        </div>
    @endif
</div>
