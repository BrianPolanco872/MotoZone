@extends('layouts.admin')

@section('title')
Caja
@endsection

@section('subtitle')
Entradas y salidas de efectivo del día
@endsection

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <!-- Encabezado compacto -->
    <div class="mb-6">
        <h1 class="text-lg font-bold text-mz-text-primary">Caja</h1>
        <p class="text-sm text-mz-text-muted">Entradas y salidas de efectivo del día</p>
    </div>

    <!-- Acciones principales -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('home') }}" class="x-button x-button-secondary size-sm">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Ver tienda
            </a>
            <a href="{{ route('admin.caja.corte') }}" class="x-button x-button-primary size-sm">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Corte del día
            </a>
            {{-- Botón Ver archivados --}}
            <a href="{{ request()->query('show_archived') ? route('admin.caja.index') : route('admin.caja.index') . '?show_archived=1' }}" class="x-button x-button-secondary size-sm">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                @if(request('show_archived')) Ver todos @else Ver archivados
                @endif
            </a>
        </div>
    </div>

    <!-- Filtros y Resumen -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Filtros -->
        <div class="lg:col-span-2 mz-card mz-card-padded">
            <h2 class="text-xs font-bold text-mz-text-muted uppercase tracking-widest mb-4">Filtrar Movimientos</h2>
            <form method="GET" action="{{ route('admin.caja.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                <div class="space-y-1">
                    <label class="text-[11px] font-medium text-mz-text-secondary">Desde</label>
                    <input type="date" name="from" class="mz-input h-10" value="{{ old('from', $filters['from']) }}" />
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-medium text-mz-text-secondary">Hasta</label>
                    <input type="date" name="to" class="mz-input h-10" value="{{ old('to', $filters['to']) }}" />
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-medium text-mz-text-secondary">Empleado</label>
                    <select name="employee" class="mz-select h-10">
                        <option value="">Todos</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee', $filters['employee']) == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-medium text-mz-text-secondary">Tipo</label>
                    <select name="type" class="mz-select h-10">
                        <option value="">Todos</option>
                        <option value="in" {{ old('type', $filters['type']) == 'in' ? 'selected' : '' }}>Entradas</option>
                        <option value="out" {{ old('type', $filters['type']) == 'out' ? 'selected' : '' }}>Salidas</option>
                    </select>
                </div>
                <div class="space-y-1 md:col-span-2">
                    <label class="text-[11px] font-medium text-mz-text-secondary">Búsqueda</label>
                    <div class="flex gap-2">
                        <input type="text" name="search" placeholder="Concepto..." class="mz-input flex-1 h-10" value="{{ old('search', $filters['search']) }}" />
                        <button type="submit" class="x-button x-button-primary size-sm h-10" title="Aplicar filtros">
                                <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v16c0 1-.9 1.9-2 2h2c0 0 1.1-.5 2-2V6l-2-2zm11.32-1.854a5.986 5.986 0 01-.708-.168m.758-.033A3.003 3.003 0 1112.25 20.99l-1.008-.218a3.006 3.006 0 00-1.886-1.338zM9.878 9.86a2.987 2.987 0 01.65-.013M14.122 9.86a2.986 2.986 0 01-.65.013m0 0V5.38a2.988 2.988 0 00-1.99-2.016m.838 17.016A5.987 5.987 0 0115.75 12a5.987 5.987 0 01-1.028-2.013m1.785-.013A2.986 2.986 0 0118.122 9.86m0 0V5.38a2.988 2.988 0 01-1.99-2.016m.838 17.016A5.987 5.987 0 0115.75 12a5.987 5.987 0 01-1.028-2.013m1.785-.013A2.986 2.986 0 0118.122 9.86m0 0V5.38a2.988 2.988 0 01-1.99-2.016m.838 17.016A5.987 5.987 0 0115.75 12a5.987 5.987 0 01-1.028-2.013m1.785-.013A2.986 2.986 0 0118.122 9.86m0 0V5.38a2.988 2.988 0 01-1.99-2.016m.838 17.016A5.987 5.987 0 0115.75 12a5.987 5.987 0 01-1.028-2.013"></path></svg>
                                Filtrar
                            </button>
                            <a href="{{ route('admin.caja.index') }}" class="x-button x-button-secondary size-sm h-10" title="Limpiar filtros">
                                <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Limpiar
                            </a>
                        </div>
                </div>
            </form>
        </div>

        <!-- Resumen por empleado -->
        <div class="mz-card mz-card-padded">
            <h2 class="text-xs font-bold text-mz-text-muted uppercase tracking-widest mb-4">Por Empleado</h2>
            <div class="space-y-3">
                @foreach($employeeBreakdown as $emp)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-mz-bg-surface-2 flex items-center justify-center text-[9px] font-bold text-mz-text-secondary">
                                {{ strtoupper(mb_substr($emp['name'], 0, 1)) }}
                            </div>
                            <span class="text-xs text-mz-text-secondary truncate max-w-[100px]">{{ $emp['name'] }}</span>
                        </div>
                        <span class="text-xs font-bold {{ $emp['total'] >= 0 ? 'text-mz-success' : 'text-mz-error' }}">
                            {{ $emp['total'] >= 0 ? '+' : '' }}${{ number_format((float)$emp['total'], 2) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Tarjetas de resumen -->
    <section class="mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="mz-card p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-bold text-mz-success uppercase tracking-wider">Entradas del período</p>
                </div>
                <p class="text-2xl font-bold text-mz-text-primary">+${{ number_format($inTotal, 2) }}</p>
                <p class="text-[10px] text-mz-text-muted mt-2">Ventas y fondos iniciales</p>
            </div>

            <div class="mz-card p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-bold text-mz-warning uppercase tracking-wider">Salidas del período</p>
                </div>
                <p class="text-2xl font-bold text-mz-text-primary">-${{ number_format($outTotal, 2) }}</p>
                <p class="text-[10px] text-mz-text-muted mt-2">Gastos, pagos y retiros</p>
            </div>

            <div class="mz-card p-5 relative">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-bold text-mz-text-muted uppercase tracking-wider">Neto registrado</p>
                    <button class="text-mz-text-muted hover:text-mz-text-secondary transition" data-tooltip="Excluye movimientos archivados (si está activado)">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </button>
                </div>
                <p class="text-2xl font-bold text-mz-text-primary">${{ number_format($netTotal, 2) }}</p>
                <p class="text-[10px] text-mz-text-muted mt-2">Entradas − salidas</p>
            </div>

            <div class="mz-card p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-bold text-mz-accent-primary uppercase tracking-wider">Efectivo esperado en caja</p>
                </div>
                <p class="text-2xl font-bold text-mz-text-primary">${{ number_format($expectedCash, 2) }}</p>
                <p class="text-[10px] text-mz-text-muted mt-2">Fondo inicial + neto del día</p>
            </div>
        </div>
    </section>

    <!-- Tabla Original Restaurada -->
    <div class="mz-card overflow-hidden">
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
                            <td>
                                {{ \Carbon\Carbon::parse($m->happened_at)->setTimezone('America/Mexico_City')->format('d/m/Y') }}
                                <br><span class="text-[10px] text-mz-text-muted">{{ \Carbon\Carbon::parse($m->happened_at)->setTimezone('America/Mexico_City')->format('H:i') }}</span>
                            </td>
                            <td>
                                <span class="mz-badge {{ $m->isIn() ? 'mz-badge-approved' : 'mz-badge-rejected' }}">
                                    {{ $m->isIn() ? 'Entrada' : 'Salida' }}
                                </span>
                            </td>
                            <td>
                                <p class="text-sm font-semibold text-mz-text-primary">{{ $m->concept }}</p>
                                <p class="text-xs text-mz-text-muted">{{ $m->categoryLabel() }}</p>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-mz-bg-surface-2 flex items-center justify-center text-[10px] font-bold text-mz-text-secondary">
                                        {{ strtoupper(mb_substr($m->user?->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <span class="text-mz-text-secondary">{{ $m->user?->name ?? 'Sistema' }}</span>
                                </div>
                            </td>
                            <td class="text-sm font-bold text-right tabular-nums {{ $m->isIn() ? 'text-mz-success' : 'text-mz-error' }}">
                                {{ $m->isIn() ? '+' : '-' }}${{ number_format((float) $m->amount, 2) }}
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    {{-- Botón Archivar --}}
                                    <form method="POST" action="{{ route('admin.caja.archive', $m->id) }}" class="inline">
                                        @csrf
                                        @method('POST')
                                        <button class="inline-flex items-center gap-2 rounded-md bg-mz-success/10 px-3 py-2 text-xs font-semibold text-mz-success transition hover:bg-mz-success/20" title="Archivar movimiento">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 15l2.286-2.286a1 1 0 011.414 0l1.614 1.613a1 1 0 010 1.414l-2.286 2.286a9 9 0 01-1.414 1.414L12 19.723l-1.786-1.786a1 1 0 01-1.414-1.414l2.286-2.286a9 9 0 011.414-1.414l1.614-1.613a1 1 0 010 1.414L9.343 15 7.726 9.343a9 9 0 011.414-1.414l1.614 1.613a1 1 0 011.414 0l1.613-1.614a9 9 0 01-.047-1.416zM19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            Archivar
                                        </button>
                                    </form>
                                    {{-- Botón Eliminar --}}
                                    <form method="POST" action="{{ route('admin.caja.destroy', $m->id) }}" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este movimiento?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex items-center gap-2 rounded-md bg-mz-error/10 px-3 py-2 text-xs font-semibold text-mz-error transition hover:bg-mz-error/20" title="Eliminar movimiento">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-mz-text-muted">No hay movimientos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                // Reserva para futuros modales
            }
        });
    </script>
@endsection
