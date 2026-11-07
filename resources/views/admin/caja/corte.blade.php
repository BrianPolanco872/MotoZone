@extends('layouts.admin')

@section('title')
Corte del día
@endsection

@section('subtitle')
Resumen de caja por fecha
@endsection

@section('content')
    @php
        $user = auth()->user();
        $initials = strtoupper(str($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    @endphp

    <!-- Selector de fecha y acciones -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-lg font-semibold text-mz-text-primary">Corte del día</h2>
            <p class="text-sm text-mz-text-muted">{{ \Carbon\Carbon::parse($date)->locale('es')->translatedFormat('l d \d\e F Y') }}</p>
        </div>

        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('admin.caja.corte') }}" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date }}" class="mz-input h-10 w-auto">
                <button type="submit" class="x-button x-button-secondary size-sm h-10">
                    Ver
                </button>
            </form>

            <a href="{{ route('admin.caja.index') }}" class="x-button x-button-secondary size-sm h-10">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver a caja
            </a>
        </div>
    </div>

    <!-- Tarjetas del corte -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="mz-card p-5">
            <p class="text-[10px] font-bold text-mz-success uppercase tracking-wider mb-1">Entradas</p>
            <p class="text-2xl font-bold text-mz-text-primary">+${{ number_format($inTotal, 2) }}</p>
        </div>
        <div class="mz-card p-5">
            <p class="text-[10px] font-bold text-mz-warning uppercase tracking-wider mb-1">Salidas</p>
            <p class="text-2xl font-bold text-mz-text-primary">-${{ number_format($outTotal, 2) }}</p>
        </div>
        <div class="mz-card p-5">
            <p class="text-[10px] font-bold text-mz-text-muted uppercase tracking-wider mb-1">Neto del día</p>
            <p class="text-2xl font-bold text-mz-text-primary">${{ number_format($netTotal, 2) }}</p>
        </div>
    </div>

    <!-- Estado del corte -->
    @if ($dailyCut)
        <div class="mz-alert mz-alert-warning mb-6 flex items-center gap-3">
            <svg class="size-5 text-mz-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <p class="text-sm font-bold text-mz-warning">Corte ya cerrado</p>
                <p class="text-xs text-mz-text-secondary">Este día ya tiene un corte generado. Los movimientos ya no se pueden editar.</p>
            </div>
        </div>
    @endif

    <!-- Desglose por empleado -->
    <div class="mz-card mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-mz-border">
            <h3 class="text-sm font-bold text-mz-text-primary">Desglose por empleado</h3>
        </div>
        <div class="p-0">
            <table class="mz-table">
                <thead>
                    <tr>
                        <th class="font-bold">Empleado</th>
                        <th class="text-center font-bold">Movimientos</th>
                        <th class="text-right font-bold">Total neto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byEmployee as $emp)
                        <tr>
                            <td class="font-medium text-mz-text-primary">{{ $emp['name'] }}</td>
                            <td class="text-center">{{ $emp['count'] }}</td>
                            <td class="font-bold text-right tabular-nums {{ $emp['total'] >= 0 ? 'text-mz-success' : 'text-mz-warning' }}">
                                {{ $emp['total'] >= 0 ? '+' : '' }}${{
                                    number_format((float) $emp['total'], 2)
                                }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-sm text-mz-text-muted">Sin movimientos registrados por empleado en esta fecha.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabla de movimientos -->
    <div class="mz-card overflow-hidden">
        <div class="border-b border-mz-border px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-mz-text-primary">Movimientos de la fecha</h3>
                <p class="text-[10px] text-mz-text-muted">{{ $movements->count() }} registro(s)</p>
            </div>
            <a href="{{ route('admin.caja.export', array_merge(request()->query(), ['date' => $date])) }}" class="x-button x-button-secondary size-sm">
                <svg class="size-4 text-mz-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Exportar CSV
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="mz-table">
                <thead>
                    <tr>
                        <th class="font-bold">Fecha / Hora</th>
                        <th class="font-bold">Tipo</th>
                        <th class="font-bold">Concepto</th>
                        <th class="font-bold">Empleado</th>
                        <th class="text-right font-bold">Monto</th>
                        <th class="text-right font-bold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $m)
                        <tr class="group">
                            <td class="whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($m->happened_at)->setTimezone('America/Mexico_City')->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                <span class="mz-badge {{ $m->isIn() ? 'mz-badge-approved' : 'mz-badge-low' }}">
                                    {{ $m->isIn() ? 'Entrada' : 'Salida' }}
                                </span>
                            </td>
                            <td class="text-mz-text-primary">
                                {{ $m->concept }}
                                <br><span class="text-[10px] text-mz-text-muted block">{{ $m->categoryLabel() }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-5 h-5 rounded-full bg-mz-bg-surface-2 flex items-center justify-center text-[10px] font-bold text-mz-text-secondary">
                                        @php echo strtoupper(mb_substr($m->user?->name ?? '—', 0, 1)); @endphp
                                    </div>
                                    <span class="text-mz-text-secondary font-medium">{{ $m->user?->name ?? 'Sistema' }}</span>
                                </div>
                            </td>
                            <td class="font-bold text-right tabular-nums {{ $m->isIn() ? 'text-mz-success' : 'text-mz-warning' }}">
                                {{ $m->isIn() ? '+' : '-' }}${{
                                    number_format((float) $m->amount, 2)
                                }}
                            </td>
                            <td class="text-right">
                                <button class="text-mz-text-muted hover:text-mz-accent-primary transition" title="Editar">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-mz-text-muted">No hay movimientos registrados en esta fecha.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
