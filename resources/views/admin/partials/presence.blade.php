@php
    $onlineCount = $onlineUsers->count();
    $authId = auth()->id();
@endphp

@if (auth()->user()?->hasRole('admin'))
    <div class="ml-2 flex items-center gap-2">
        <span class="flex h-2.5 w-2.5 rounded-full bg-mz-success @if($onlineCount === 0) opacity-50 @endif" title="Usuarios en línea"></span>
        <span class="text-xs font-medium text-mz-text-secondary">{{ $onlineCount }} en línea</span>
    </div>

    @if ($onlineCount > 0)
        <details class="group relative" data-dismissable>
            <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-[10px] text-mz-text-muted transition hover:text-mz-accent-primary [&::-webkit-details-marker]:hidden" aria-label="Ver quiénes están en línea">
                Ver quiénes están en línea
                <x-icon name="chevron-down" class="size-3.5 transition group-open:rotate-180" />
            </summary>
            <div class="absolute right-0 z-20 mt-1 w-48 rounded-lg border border-mz-border-light bg-mz-bg-surface p-2 shadow-xl shadow-black/40">
                @foreach ($onlineUsers as $u)
                    <div class="flex items-center gap-2 rounded-md px-2 py-1 text-xs @if($u['id'] === $authId) bg-mz-bg-surface-2 @endif">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-mz-accent-primary text-[10px] font-semibold text-white">
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
@endif
