@php
    $money = fn ($value) => '$'.number_format((float) $value, 2);
    $dateStr = $date?->toDateString() ?? today()->toDateString();
    $breakdown = $breakdown ?? null;
    $total = $breakdown['total'] ?? [];
    $employees = $breakdown['employees'] ?? collect();
@endphp

@if ($breakdown && $employees->isNotEmpty())
    <section class="mt-5 mz-card mz-card-padded overflow-hidden">
        <div class="border-b border-mz-border-light px-4 py-3">
            <h2 class="text-base font-semibold text-mz-text-primary">Por empleado — {{ $date->format('d/m/Y') }}</h2>
            <p class="text-xs text-mz-text-muted">Solo se muestran los empleados con actividad o corte generado este día.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-mz-border-light text-sm">
                <thead class="bg-mz-bg-surface">
                    <tr class="text-left text-xs uppercase tracking-wide text-mz-text-muted">
                        <th scope="col" class="px-4 py-3 font-medium">Empleado</th>
                        <th scope="col" class="px-4 py-3 font-medium text-right">Tickets</th>
                        <th scope="col" class="px-4 py-3 font-medium text-right">Ventas</th>
                        <th scope="col" class="px-4 py-3 font-medium text-right">Efectivo</th>
                        <th scope="col" class="px-4 py-3 font-medium text-right">Devoluciones</th>
                        <th scope="col" class="px-4 py-3 font-medium text-right">Salidas</th>
                        <th scope="col" class="px-4 py-3 font-medium text-right">Debe haber</th>
                        <th scope="col" class="px-4 py-3 font-medium text-right">Corte</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light">
                    @foreach ($employees as $row)
                        @php
                            $isPending = $row['status'] === 'pending';
                            $badge = $isPending
                                ? '<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-mz-warning/10 text-mz-warning">Pendiente</span>'
                                : ('<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-mz-success/10 text-mz-success">Hecho v'.$row['latest_cut']->version.' · '.$row['latest_cut']->generated_at?->format('H:i').'</span>');
                        @endphp
                        <tr class="align-top transition hover:bg-mz-bg-surface-2 @if($isPending) bg-mz-warning/5 @endif">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('pos.cuts.index', ['date' => $dateStr, 'employee' => $row['id']]) }}" class="text-mz-text-secondary hover:text-mz-accent-primary hover:underline text-sm font-medium">{{ $row['name'] }}</a>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">{{ $row['tickets'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-mz-text-secondary">{{ $money($row['sales']) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-mz-success">{{ $money($row['cash']) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-mz-error">−{{ $money($row['refunds']) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-mz-warning">−{{ $money($row['cash_out']) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums font-medium text-mz-text-primary">{{ $money($row['should_have']) }}</td>
                            <td class="px-4 py-3 text-right">{!! $badge !!}</td>
                        </tr>
                    @endforeach

                    <tr class="border-t border-mz-border bg-mz-bg-surface-2 font-bold">
                        <td class="px-4 py-3 text-mz-text-primary">Total del día</td>
                        <td class="px-4 py-3 text-right text-mz-text-primary">{{ $total['tickets'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-mz-text-primary">{{ $money($total['sales'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-mz-success">{{ $money($total['cash'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-mz-error">−{{ $money($total['refunds'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-mz-warning">−{{ $money(0) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-mz-text-primary">{{ $money($total['sales'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endif
