@props([
    'open' => false,
    'title' => null,
    'closeLabel' => 'Cerrar',
])

<div {{ $attributes->merge(['class' => 'fixed inset-0 z-50 ' . ($open ? '' : 'hidden')]) }} aria-hidden="{{ $open ? 'false' : 'true' }}">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" aria-hidden="true"></div>

        <div class="relative w-full max-w-lg overflow-hidden">
            <div class="mz-modal">
                @if ($title)
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-mz-text-primary">{{ $title }}</h2>
                        <button type="button" class="rounded-md p-1 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-colors" aria-label="{{ $closeLabel }}">
                            <x-icon name="x" class="size-5" />
                        </button>
                    </div>
                @endif

                <div class="mb-6">
                    {{ $slot }}
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:gap-3">
                    {{ $attributes->slot ?? '' }}
                </div>
            </div>
        </div>
    </div>
</div>
