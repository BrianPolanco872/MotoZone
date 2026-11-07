@php
    $money = fn ($value) => '$'.number_format((float) $value, 2);
    $rows = $breakdown['employees'] ?? collect();
    $onlineUsers = collect($onlineUsers ?? []);
    $onlineMap = $onlineUsers->keyBy('id');
    $canSeeAny = $canSeeAny ?? false;
    $canViewCut = auth()->user()?->can('pos.cut.manage') || auth()->user()?->can('pos.cut.own');

    $sumTickets = (int) $rows->sum('tickets');
    $sumSales = (float) $rows->sum('sales');
    $sumCash = (float) $rows->sum('cash');
    $sumShouldHave = (float) $rows->sum('should_have');
@endphp

@if ($rows->isEmpty())
    <div class="px-4 py-10 text-center">
        <div class="mx-auto flex size-10 items-center justify-center rounded-full bg-mz-bg-surface-2 text-mz-text-muted">
            <x-icon name="clock" class="size-5" />
        </div>
        <p class="mt-3 text-sm font-medium text-mz-text-secondary">
            {{ $canSeeAny ? 'Nadie ha registrado actividad hoy' : 'Aún no tienes actividad registrada hoy' }}
        </p>
        <p class="mt-1 text-xs text-mz-text-muted">
            {{ $canSeeAny
                ? 'En cuanto alguien registre dinero inicial, haga una venta o genere su corte, aparecerá aquí.'
                : 'Registra el dinero inicial de tu caja para comenzar.' }}
        </p>
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-mz-border-light text-sm">
            <thead class="bg-mz-bg-surface">
                <tr class="text-left text-[11px] uppercase tracking-wide text-mz-text-muted">
                    <th scope="col" class="px-4 py-3 font-medium">Empleado</th>
                    <th scope="col" class="px-4 py-3 font-medium">Sesión</th>
                    <th scope="col" class="px-4 py-3 font-medium">Corte</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">Tickets</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">Ventas</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">Efectivo</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">Saldo esperado</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">Diferencia</th>
                    @if ($canViewCut)
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-mz-border-light">
                @foreach ($rows as $row)
                    @php
                        $session = $onlineMap->get($row['id']);
                        $latestCut = $row['latest_cut'] ?? null;
                        $difference = $latestCut?->cash_counted !== null ? (float) $latestCut->difference : null;
                        $isSelf = $row['id'] === auth()->id();
                    @endphp
                    <tr class="transition hover:bg-mz-bg-surface-2">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-mz-bg-surface-2 text-[10px] font-bold text-mz-text-muted">
                                    {{ strtoupper(mb_substr($row['name'] ?? '?', 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-mz-text-secondary">
                                        {{ $row['name'] }}
                                        @if ($isSelf)
                                            <span class="text-[10px] font-normal text-mz-text-muted">· tú</span>
                                        @endif
                                    </p>
                                    @if ((float) $row['refunds'] > 0)
                                        <p class="text-[10px] text-mz-error">Devoluciones {{ $money($row['refunds']) }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if ($session)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-mz-success/10 px-2.5 py-0.5 text-[11px] font-semibold text-mz-success">
                                    <span class="relative flex size-1.5">
                                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-mz-success/50 opacity-75"></span>
                                        <span class="relative inline-flex size-1.5 rounded-full bg-mz-success"></span>
                                    </span>
                                    En línea
                                </span>
                                <span class="mt-0.5 block text-[10px] text-mz-text-muted">{{ $session['last_activity']->diffForHumans() }}</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-mz-bg-surface-2 px-2.5 py-0.5 text-[11px] font-semibold text-mz-text-muted">
                                    Sin sesión
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if ($latestCut)
                                <span class="inline-flex items-center gap-1 rounded-full bg-mz-success/10 px-2.5 py-0.5 text-[11px] font-semibold text-mz-success">
                                    <x-icon name="check" class="size-3" />
                                    Hecho v{{ $latestCut->version }} · {{ $latestCut->generated_at?->format('H:i') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-mz-warning/10 px-2.5 py-0.5 text-[11px] font-semibold text-mz-warning">
                                    <x-icon name="clock" class="size-3" />
                                    Pendiente
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-mz-text-secondary">{{ (int) $row['tickets'] }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-mz-text-secondary">{{ $money($row['sales']) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-mz-text-secondary">{{ $money($row['cash']) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-mz-text-primary">{{ $money($row['should_have']) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">
                            @if ($difference === null)
                                <span class="text-mz-text-muted">—</span>
                            @else
                                <span @class([
                                    'font-semibold',
                                    'text-mz-success' => abs($difference) < 0.005 || $difference > 0,
                                    'text-mz-error' => $difference < -0.005,
                                ])>
                                    {{ $difference > 0 ? '+' : '' }}{{ $money($difference) }}
                                </span>
                                <span class="mt-0.5 block text-[10px] text-mz-text-muted">
                                    {{ abs($difference) < 0.005 ? 'Cuadra' : ($difference > 0 ? 'Sobrante' : 'Faltante') }}
                                </span>
                            @endif
                        </td>
                        @if ($canViewCut)
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ $canSeeAny
                                        ? route('pos.cuts.index', ['date' => $date, 'employee' => $row['id']])
                                        : route('pos.cuts.mine') }}"
                                   class="x-button x-button-secondary size-xs">
                                    Ver corte
                                </a>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
            @if ($canSeeAny)
                <tfoot class="bg-mz-bg-surface-2/50">
                    <tr class="text-xs font-semibold text-mz-text-muted">
                        <td class="px-4 py-3" colspan="3">Total de empleados activos</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $sumTickets }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $money($sumSales) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $money($sumCash) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $money($sumShouldHave) }}</td>
                        <td class="px-4 py-3 text-right text-[10px] font-normal text-mz-text-muted">Suma de empleados</td>
                        @if ($canViewCut)
                            <td class="px-4 py-3"></td>
                        @endif
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
@endif
