@php
    $money = fn ($value) => '$'.number_format((float) $value, 2);
    $rows = collect($breakdown['employees'] ?? []);
    $onlineUsers = collect($onlineUsers ?? []);
    $onlineMap = $onlineUsers->keyBy('id');
    $canSeeAny = $canSeeAny ?? false;
    $canViewCut = auth()->user()?->can('pos.cut.manage') || auth()->user()?->can('pos.cut.own');

    $sumTickets = (int) $rows->sum('tickets');
    $sumSales = (float) $rows->sum('sales');
    $sumCash = (float) $rows->sum('cash');
    $sumOtherPayments = (float) $rows->sum('other_payments');
    $sumRefunds = (float) $rows->sum('refunds');
    $sumCashIn = (float) $rows->sum('cash_in');
    $sumCashOut = (float) $rows->sum('cash_out');
    $sumShouldHave = (float) $rows->sum('should_have');

    $onlineCount = $rows->filter(fn ($r) => $onlineMap->has($r['id']))->count();
    $closedCount = $rows->filter(fn ($r) => ! empty($r['is_day_closed']))->count();
    $openCount = $rows->count() - $closedCount;
@endphp

@if ($rows->isEmpty())
    <div class="px-5 py-16 text-center">
        <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-mz-bg-surface-2 text-mz-text-muted">
            <x-icon name="clock" class="size-7" />
        </div>
        <p class="mt-4 text-base font-bold text-mz-text-primary">
            {{ $canSeeAny ? 'No hay actividad de cajeros registrada hoy' : 'Aún no tienes actividad registrada hoy' }}
        </p>
        <p class="mt-1 text-xs text-mz-text-muted max-w-md mx-auto">
            {{ $canSeeAny
                ? 'En cuanto algún empleado inicie turno con efectivo inicial o cobre su primer ticket, aparecerá en este monitor en tiempo real.'
                : 'Registra el efectivo inicial de tu caja para comenzar a operar el punto de venta.' }}
        </p>
    </div>
@else
    @if ($rows->contains(fn ($row) => ! empty($row['has_unallocated_mixed'])))
        <p role="alert" class="p-4 text-sm text-mz-warning">Hay pagos mixtos sin desglose de efectivo. El efectivo de esos pagos no está determinado.</p>
    @endif

    {{-- DESGLOSE DE TODOS SUMADO: Tarjetas de métricas consolidadas para el Admin --}}
    @if ($canSeeAny)
        <div class="p-4 sm:p-5 border-b border-mz-border bg-mz-bg-surface-2/20">
            <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Total Ventas --}}
                <div class="rounded-xl border border-mz-border-light/60 bg-mz-bg-surface p-4 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-mz-text-muted">Ventas Consolidadas</p>
                        <p class="mt-1 font-display text-2xl font-black tabular-nums text-mz-text-primary">{{ $money($sumSales) }}</p>
                        <p class="text-[11px] text-mz-text-muted mt-0.5">{{ $sumTickets }} ticket{{ $sumTickets === 1 ? '' : 's' }} cobrado{{ $sumTickets === 1 ? '' : 's' }}</p>
                    </div>
                    <div class="flex size-10 items-center justify-center rounded-xl bg-mz-accent-primary/10 text-mz-accent-primary border border-mz-accent-primary/20">
                        <x-icon name="chart" class="size-5" />
                    </div>
                </div>

                {{-- Efectivo en Cajas --}}
                <div class="rounded-xl border border-mz-border-light/60 bg-mz-bg-surface p-4 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-mz-text-muted">Efectivo Físico en Gavetas</p>
                        <p class="mt-1 font-display text-2xl font-black tabular-nums text-mz-success">{{ $money($sumShouldHave) }}</p>
                        <p class="text-[11px] text-mz-text-muted mt-0.5">{{ $money($sumCash) }} por ventas en efectivo</p>
                    </div>
                    <div class="flex size-10 items-center justify-center rounded-xl bg-mz-success/10 text-mz-success border border-mz-success/20">
                        <x-icon name="banknotes" class="size-5" />
                    </div>
                </div>

                {{-- Tarjetas y Transferencias --}}
                <div class="rounded-xl border border-mz-border-light/60 bg-mz-bg-surface p-4 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-mz-text-muted">Ventas Electrónicas</p>
                        <p class="mt-1 font-display text-2xl font-black tabular-nums text-mz-accent-primary">{{ $money($sumOtherPayments) }}</p>
                        <p class="text-[11px] text-mz-text-muted mt-0.5">Tarjeta y transferencias directas</p>
                    </div>
                    <div class="flex size-10 items-center justify-center rounded-xl bg-mz-bg-surface-2 text-mz-text-secondary border border-mz-border-light">
                        <x-icon name="receipt" class="size-5" />
                    </div>
                </div>

                {{-- Estado del Personal --}}
                <div class="rounded-xl border border-mz-border-light/60 bg-mz-bg-surface p-4 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-mz-text-muted">Monitoreo de Turnos</p>
                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-mz-success/15 border border-mz-success/30 px-2.5 py-0.5 text-xs font-bold text-mz-success">
                                <span class="relative flex size-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-mz-success opacity-75"></span>
                                    <span class="relative inline-flex rounded-full size-1.5 bg-mz-success"></span>
                                </span>
                                {{ $onlineCount }} en línea
                            </span>
                            @if ($closedCount > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-purple-500/15 border border-purple-500/30 px-2 py-0.5 text-xs font-bold text-purple-800 pos-night:text-purple-300">
                                    <x-icon name="check" class="size-3" />
                                    {{ $closedCount }} cerró día
                                </span>
                            @endif
                        </div>
                        <p class="text-[11px] text-mz-text-muted mt-1">{{ $openCount }} turno{{ $openCount === 1 ? '' : 's' }} activo{{ $openCount === 1 ? '' : 's' }} de {{ $rows->count() }} personal</p>
                    </div>
                    <div class="flex size-10 items-center justify-center rounded-xl bg-mz-bg-surface-2 text-mz-text-muted border border-mz-border-light">
                        <x-icon name="users" class="size-5" />
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- TABLA DETALLADA EN TIEMPO REAL: Cada empleado con su estado y ventas --}}
    <div class="overflow-x-auto pos-scrollbar">
        <table class="min-w-full divide-y divide-mz-border text-sm">
            <thead class="bg-mz-bg-surface-2/40">
                <tr class="text-left text-xs uppercase tracking-wider text-mz-text-muted">
                    <th scope="col" class="px-5 py-3.5 font-semibold">Cajero / Empleado</th>
                    <th scope="col" class="px-4 py-3.5 font-semibold">Conexión en vivo</th>
                    <th scope="col" class="px-4 py-3.5 font-semibold">Estado del Turno</th>
                    <th scope="col" class="px-4 py-3.5 text-center font-semibold">Tickets</th>
                    <th scope="col" class="px-4 py-3.5 text-right font-semibold">Ventas Totales</th>
                    <th scope="col" class="px-4 py-3.5 text-right font-semibold">Efectivo en Caja</th>
                    <th scope="col" class="px-4 py-3.5 text-right font-semibold">Tarjeta / Transf.</th>
                    <th scope="col" class="px-4 py-3.5 text-right font-semibold">Diferencia</th>
                    @if ($canViewCut)
                        <th scope="col" class="px-4 py-3.5 text-right font-semibold"><span class="sr-only">Acciones</span></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-mz-border-light/40">
                @foreach ($rows as $row)
                    @php
                        $session = $onlineMap->get($row['id']);
                        $isOnline = $session !== null;
                        $latestCut = $row['latest_cut'] ?? null;
                        $isClosed = ! empty($row['is_day_closed']) || ($latestCut?->isClosed() ?? false);
                        $difference = $latestCut?->cash_counted !== null ? (float) $latestCut->difference : null;
                        $isSelf = $row['id'] === auth()->id();
                        $cashInDrawer = (float) ($row['should_have'] ?? 0);
                    @endphp
                    <tr class="group transition-colors duration-150 hover:bg-mz-bg-surface-2/40">
                        {{-- Empleado --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-mz-bg-surface-2 text-xs font-black text-mz-accent-primary border border-mz-border-light shadow-inner">
                                    {{ strtoupper(mb_substr($row['name'] ?? '?', 0, 2)) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-mz-text-primary group-hover:text-mz-accent-primary transition-colors">
                                        {{ $row['name'] }}
                                        @if ($isSelf)
                                            <span class="ml-1 rounded bg-mz-accent-primary/15 px-1.5 py-0.2 text-[10px] font-semibold text-mz-accent-primary border border-mz-accent-primary/20">Tú</span>
                                        @endif
                                    </p>
                                    @if ((float) ($row['refunds'] ?? 0) > 0)
                                        <p class="text-[11px] text-mz-error font-medium">Devoluciones: -{{ $money($row['refunds']) }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Conexión en vivo --}}
                        <td class="whitespace-nowrap px-4 py-3.5">
                            @if ($isOnline)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-mz-success/15 border border-mz-success/30 px-3 py-1 text-xs font-bold text-mz-success">
                                    <span class="relative flex size-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-mz-success opacity-75"></span>
                                        <span class="relative inline-flex rounded-full size-2 bg-mz-success"></span>
                                    </span>
                                    En línea
                                </span>
                                <span class="mt-1 block text-[10px] text-mz-text-muted font-mono">{{ $session['last_activity']->diffForHumans() }}</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-mz-bg-surface-2 border border-mz-border-light px-2.5 py-1 text-xs font-semibold text-mz-text-muted">
                                    <span class="size-1.5 rounded-full bg-mz-text-muted/50"></span>
                                    Desconectado
                                </span>
                            @endif
                        </td>

                        {{-- Estado del Turno / Día --}}
                        <td class="whitespace-nowrap px-4 py-3.5">
                            @if ($isClosed)
                                <div>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-500/15 border border-purple-500/30 px-3 py-1 text-xs font-extrabold text-purple-800 pos-night:text-purple-300">
                                        <x-icon name="lock" class="size-3.5" />
                                        Cerró su día
                                    </span>
                                    @if (! empty($row['closed_at']) || $latestCut?->closed_at)
                                        <span class="mt-1 block text-[11px] text-mz-text-muted font-mono">
                                            A las {{ \Carbon\Carbon::parse($row['closed_at'] ?? $latestCut->closed_at)->format('H:i') }}
                                        </span>
                                    @endif
                                </div>
                            @elseif ($latestCut)
                                <div>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-500/15 border border-blue-500/30 px-3 py-1 text-xs font-bold text-blue-800 pos-night:text-blue-300">
                                        <x-icon name="clock" class="size-3.5" />
                                        Corte v{{ $latestCut->version }} (Abierto)
                                    </span>
                                    <span class="mt-1 block text-[10px] text-mz-text-muted">Turno sigue activo</span>
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-mz-warning/15 border border-mz-warning/30 px-3 py-1 text-xs font-bold text-mz-warning">
                                    <span class="size-2 rounded-full bg-mz-warning animate-pulse"></span>
                                    Turno activo · Cobrando
                                </span>
                            @endif
                        </td>

                        {{-- Tickets --}}
                        <td class="whitespace-nowrap px-4 py-3.5 text-center font-display text-base font-bold tabular-nums text-mz-text-primary">
                            {{ (int) $row['tickets'] }}
                        </td>

                        {{-- Ventas Totales --}}
                        <td class="whitespace-nowrap px-4 py-3.5 text-right font-display text-base font-extrabold tabular-nums text-mz-text-primary">
                            {{ $money($row['sales']) }}
                        </td>

                        {{-- Efectivo en Caja: ventas en efectivo + ingresos de efectivo − salidas de efectivo --}}
                        <td class="whitespace-nowrap px-4 py-3.5 text-right font-display text-sm font-bold tabular-nums text-mz-success">
                            {{ $money($cashInDrawer) }}
                        </td>

                        {{-- Tarjeta / Transferencias --}}
                        <td class="whitespace-nowrap px-4 py-3.5 text-right font-display text-sm font-semibold tabular-nums text-mz-text-muted">
                            {{ $money($row['other_payments'] ?? max(0, (float)$row['sales'] - (float)$row['cash'])) }}
                        </td>

                        {{-- Diferencia de Arqueo --}}
                        <td class="whitespace-nowrap px-4 py-3.5 text-right tabular-nums">
                            @if ($difference === null)
                                <span class="text-xs text-mz-text-muted font-medium">Sin corte</span>
                            @else
                                <span @class([
                                    'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold tabular-nums',
                                    'bg-mz-success/15 text-mz-success border border-mz-success/30' => abs($difference) < 0.005 || $difference > 0,
                                    'bg-mz-error/15 text-mz-error border border-mz-error/30' => $difference < -0.005,
                                ])>
                                    {{ $difference > 0 ? '+' : '' }}{{ $money($difference) }}
                                </span>
                                <span class="mt-0.5 block text-[10px] text-mz-text-muted font-semibold">
                                    {{ abs($difference) < 0.005 ? 'Cuadra exacto' : ($difference > 0 ? 'Sobrante' : 'Faltante') }}
                                </span>
                            @endif
                        </td>

                        {{-- Acciones --}}
                        @if ($canViewCut)
                            <td class="whitespace-nowrap px-4 py-3.5 text-right">
                                <a href="{{ $canSeeAny
                                        ? route('pos.cuts.index', ['date' => $date, 'employee' => $row['id']])
                                        : route('pos.cuts.mine') }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface px-3 py-1.5 text-xs font-bold text-mz-text-secondary hover:text-mz-text-primary transition-all shadow-xs active:scale-95">
                                    <x-icon name="report" class="size-3.5 text-mz-accent-primary" />
                                    Ver corte
                                </a>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>

            {{-- FILA DE TOTALES CONSOLIDADOS (Todos sumados) --}}
            @if ($canSeeAny)
                <tfoot class="border-t-2 border-mz-accent-primary/40 bg-gradient-to-r from-mz-bg-surface via-mz-bg-surface-2/60 to-mz-bg-surface">
                    <tr class="text-xs font-bold text-mz-text-primary">
                        <td class="px-5 py-4" colspan="3">
                            <div class="flex items-center gap-2">
                                <span class="flex size-7 items-center justify-center rounded-lg bg-mz-accent-primary/20 text-mz-accent-primary">
                                    <x-icon name="chart" class="size-4" />
                                </span>
                                <div>
                                    <span class="text-sm font-black uppercase tracking-wider">TOTAL CONSOLIDADO DEL NEGOCIO</span>
                                    <span class="block text-[11px] text-mz-text-muted font-normal">Suma de todos los cajeros activos y cerrados de hoy</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center font-display text-base font-black tabular-nums">{{ $sumTickets }}</td>
                        <td class="px-4 py-4 text-right font-display text-lg font-black tabular-nums">{{ $money($sumSales) }}</td>
                        <td class="px-4 py-4 text-right font-display text-base font-bold tabular-nums text-mz-success">{{ $money($sumShouldHave) }}</td>
                        <td class="px-4 py-4 text-right font-display text-base font-semibold tabular-nums text-mz-text-muted">{{ $money($sumOtherPayments) }}</td>
                        <td class="px-4 py-4 text-right text-[11px] text-mz-text-muted font-medium">Arqueo global</td>
                        @if ($canViewCut)
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('pos.cuts.index', ['date' => $date]) }}" class="x-button x-button-primary rounded-lg px-3 py-1.5 text-xs font-bold shadow-md shadow-mz-accent-primary/20 active:scale-95">
                                    Corte global
                                </a>
                            </td>
                        @endif
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
@endif
