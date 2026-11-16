@php
    $money = fn ($value) => '$'.number_format((float) $value, 2);
    $dateStr = $date?->toDateString() ?? today()->toDateString();
    $breakdown = $breakdown ?? null;
    $total = $breakdown['total'] ?? [];
    $employees = $breakdown['employees'] ?? collect();
    $onlineUsers = $onlineUsers ?? collect();
    $onlineIds = $onlineUsers instanceof \Illuminate\Support\Collection ? $onlineUsers->pluck('id')->all() : [];
@endphp

@if ($breakdown && $employees->isNotEmpty())
    <section class="mt-5 mz-card overflow-hidden border border-mz-border-light shadow-xl">
        <div class="border-b border-mz-border-light bg-gradient-to-r from-mz-bg-surface to-mz-bg-surface-2 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="flex size-2 rounded-full bg-mz-accent-primary animate-pulse"></span>
                    <h2 class="text-base font-bold text-mz-text-primary">Desglose de Ventas por Empleado — {{ $date->format('d/m/Y') }}</h2>
                </div>
                <p class="text-xs text-mz-text-muted mt-0.5">Monitoreo consolidado de cajeros, presencia en tiempo real y cuadre de caja.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold bg-mz-bg-surface-2 text-mz-text-secondary border border-mz-border-light">
                    <span class="size-2 rounded-full bg-mz-accent-primary"></span>
                    {{ $employees->count() }} empleado(s) registrados
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-mz-border-light text-sm">
                <thead class="bg-mz-bg-surface-2/90 backdrop-blur-sm">
                    <tr class="text-left text-[11px] uppercase tracking-wider font-semibold text-mz-text-muted">
                        <th scope="col" class="px-4 py-3">Empleado</th>
                        <th scope="col" class="px-4 py-3 text-center">Presencia</th>
                        <th scope="col" class="px-4 py-3 text-center">Estado del día</th>
                        <th scope="col" class="px-4 py-3 text-right">Tickets</th>
                        <th scope="col" class="px-4 py-3 text-right font-bold text-mz-text-primary">Ventas Totales</th>
                        <th scope="col" class="px-4 py-3 text-right text-emerald-400">Efectivo</th>
                        <th scope="col" class="px-4 py-3 text-right text-cyan-400">Electrónico</th>
                        <th scope="col" class="px-4 py-3 text-right text-rose-400">Devoluciones</th>
                        <th scope="col" class="px-4 py-3 text-right text-amber-400">Salidas</th>
                        <th scope="col" class="px-4 py-3 text-right font-bold text-mz-accent-primary">En Gaveta</th>
                        <th scope="col" class="px-4 py-3 text-right">Corte</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light/60 bg-mz-bg-surface">
                    @foreach ($employees as $row)
                        @php
                            $isOnline = in_array($row['id'], $onlineIds, true);
                            $isClosed = ! empty($row['is_day_closed']);
                            $isPending = ($row['status'] ?? 'pending') === 'pending';
                        @endphp
                        <tr class="align-middle transition-colors hover:bg-mz-bg-surface-2/80 @if($isClosed) opacity-85 @endif">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-mz-bg-surface-2 border border-mz-border-light text-xs font-bold text-mz-accent-primary uppercase">
                                        {{ substr($row['name'], 0, 2) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('pos.cuts.index', ['date' => $dateStr, 'employee' => $row['id']]) }}" class="text-sm font-semibold text-mz-text-primary hover:text-mz-accent-primary hover:underline transition">
                                            {{ $row['name'] }}
                                        </a>
                                        <p class="text-[11px] text-mz-text-muted">{{ $row['email'] ?? 'Cajero' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($isOnline)
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="size-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                        En línea
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-medium bg-mz-bg-surface-2 text-mz-text-muted border border-mz-border-light">
                                        <span class="size-1.5 rounded-full bg-mz-text-muted"></span>
                                        Desconectado
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($isClosed)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-zinc-800 text-zinc-300 border border-zinc-700">
                                        <x-icon name="lock" class="size-3" />
                                        Cerró su día
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-mz-accent-primary/10 text-mz-accent-primary border border-mz-accent-primary/20">
                                        <span class="size-1.5 rounded-full bg-mz-accent-primary"></span>
                                        Turno activo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right font-medium text-mz-text-secondary tabular-nums">
                                {{ $row['tickets'] }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-mz-text-primary tabular-nums">
                                {{ $money($row['sales']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold text-emerald-400 tabular-nums">
                                {{ $money($row['cash']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold text-cyan-400 tabular-nums">
                                {{ $money($row['other_payments'] ?? 0) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-medium text-rose-400 tabular-nums">
                                @if((float)$row['refunds'] > 0)
                                    −{{ $money($row['refunds']) }}
                                @else
                                    $0.00
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right font-medium text-amber-400 tabular-nums">
                                @if((float)$row['cash_out'] > 0)
                                    −{{ $money($row['cash_out']) }}
                                @else
                                    $0.00
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-mz-accent-primary tabular-nums">
                                {{ $money($row['should_have']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                @if ($isPending)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-mz-warning/10 text-mz-warning border border-mz-warning/20">
                                        Pendiente
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        v{{ $row['latest_cut']->version }} · {{ $row['latest_cut']->generated_at?->format('H:i') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-mz-border bg-gradient-to-r from-mz-bg-surface-2 to-mz-bg-surface font-bold text-sm">
                        <td colspan="3" class="px-4 py-4 text-mz-text-primary uppercase tracking-wide text-xs">
                            Total General Consolidado
                        </td>
                        <td class="px-4 py-4 text-right tabular-nums text-mz-text-primary">
                            {{ $total['tickets'] ?? 0 }}
                        </td>
                        <td class="px-4 py-4 text-right tabular-nums text-base font-extrabold text-mz-text-primary">
                            {{ $money($total['sales'] ?? 0) }}
                        </td>
                        <td class="px-4 py-4 text-right tabular-nums font-bold text-emerald-400">
                            {{ $money($total['cash'] ?? 0) }}
                        </td>
                        <td class="px-4 py-4 text-right tabular-nums font-bold text-cyan-400">
                            {{ $money($total['other_payments'] ?? 0) }}
                        </td>
                        <td class="px-4 py-4 text-right tabular-nums font-semibold text-rose-400">
                            @if((float)($total['refunds'] ?? 0) > 0)
                                −{{ $money($total['refunds']) }}
                            @else
                                $0.00
                            @endif
                        </td>
                        <td class="px-4 py-4 text-right tabular-nums font-semibold text-amber-400">
                            @if((float)($total['cash_out'] ?? 0) > 0)
                                −{{ $money($total['cash_out']) }}
                            @else
                                $0.00
                            @endif
                        </td>
                        <td class="px-4 py-4 text-right tabular-nums text-base font-extrabold text-mz-accent-primary">
                            {{ $money($total['should_have'] ?? 0) }}
                        </td>
                        <td class="px-4 py-4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
@endif
