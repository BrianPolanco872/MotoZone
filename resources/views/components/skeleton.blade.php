@props([
    'width' => 'full',
    'height' => '16',
    'radius' => 'md',
])

<div {{ $attributes->merge(['class' => 'mz-skeleton w-' . $width . ' h-' . $height . ' rounded-' . $radius]) }}></div>
