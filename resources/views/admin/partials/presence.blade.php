@php
    $onlineCount = $onlineUsers->count();
    $authId = auth()->id();
@endphp

@if (auth()->user()?->hasRole('admin'))
    <div @class([
        'flex h-9 items-center gap-2 rounded-lg border border-mz-success/30 bg-mz-success/10 px-2.5' => $usesPosTheme,
        'contents' => ! $usesPosTheme,
    ])>
        <div @class(['flex items-center gap-2', 'ml-2' => ! $usesPosTheme])>
            <span class="flex h-2.5 w-2.5 rounded-full bg-mz-success @if($onlineCount === 0) opacity-50 @endif" title="Usuarios en línea"></span>
            <span @class([
                'text-xs',
                'font-bold text-mz-success' => $usesPosTheme,
                'font-medium text-mz-text-secondary' => ! $usesPosTheme,
            ])>{{ $onlineCount }} en línea</span>
        </div>

        @if ($onlineCount > 0)
            <details @class(['group', 'xs:relative' => $usesPosTheme, 'relative' => ! $usesPosTheme]) data-dismissable>
                <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-[10px] text-mz-text-muted transition hover:text-mz-accent-primary [&::-webkit-details-marker]:hidden" aria-label="Ver quiénes están en línea">
                    <span @class(['hidden sm:inline' => $usesPosTheme])>Ver quiénes están en línea</span>
                    <x-icon name="chevron-down" class="size-3.5 transition group-open:rotate-180" />
                </summary>
                <div @class([
                    'absolute z-20 mt-1 w-48 rounded-lg border border-mz-border-light bg-mz-bg-surface p-2 shadow-xl',
                    'right-4 top-full xs:right-0 xs:top-auto pos-night:shadow-black/40' => $usesPosTheme,
                    'right-0 shadow-black/40' => ! $usesPosTheme,
                ])>
                    @foreach ($onlineUsers as $u)
                        <div class="flex items-center gap-2 rounded-md px-2 py-1 text-xs @if($u['id'] === $authId) bg-mz-bg-surface-2 @endif">
                            <span @class([
                                'flex size-6 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold text-white',
                                'bg-orange-700' => $usesPosTheme,
                                'bg-mz-accent-primary' => ! $usesPosTheme,
                            ])>
                                @php
                                    $initials = strtoupper(str($u['name'])->explode(' ')->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
                                @endphp
                                {{ $initials }}
                            </span>
                            <div class="min-w-0">
                                <p class="leading-none text-mz-text-primary">{{ $u['name'] }} @if($u['id'] === $authId) <span class="ml-1 text-[10px] uppercase text-mz-text-muted">(tú)</span> @endif</p>
                                <p class="text-[10px] leading-none text-mz-text-muted">{{ $u['last_activity']->diffForHumans() }} atrás</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    </div>
@endif
