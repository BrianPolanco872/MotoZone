@extends('layouts.admin')

@section('title', 'Historial de cortes')
@section('subtitle', 'Historial de cortes globales del negocio por día')

@section('content')
    {{-- Barra superior --}}
    <div class="rounded-2xl border border-mz-border bg-gradient-to-r from-mz-bg-surface via-mz-bg-surface/95 to-mz-bg-surface p-4 sm:p-5 shadow-xl shadow-black/25">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-mz-accent-primary/15 text-mz-accent-primary border border-mz-accent-primary/25">
                    <x-icon name="report" class="size-5" />
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white">Historial de cortes globales</h2>
                    <p class="text-xs text-mz-text-muted mt-0.5">Cada corte global guarda el total de todos los empleados de ese día para auditoría y arqueo de caja.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('pos.cuts.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl bg-mz-accent-primary hover:bg-mz-accent-hover text-white px-4 text-xs font-bold transition-all shadow-md shadow-mz-accent-primary/20 active:scale-95">
                    <x-icon name="report" class="size-4" />
                    Corte actual del día
                </a>
                <button type="button" class="inline-flex h-11 items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary hover:text-white transition-all shadow-xs active:scale-95" onclick="history.back()">
                    <x-icon name="arrow-uturn-left" class="size-4" />
                    Regresar atrás
                </button>
                <button type="button" id="btn-delete-selected" class="inline-flex h-11 items-center gap-2 rounded-xl border border-red-500 bg-red-500/10 px-4 text-xs font-bold text-red-400 hover:bg-red-500 hover:text-white transition-all shadow-xs active:scale-95 hidden" onclick="deleteSelectedCuts()">
                    <x-icon name="trash" class="size-4" />
                    Eliminar seleccionados
                </button>
            </div>
        </div>

        {{-- Filtros de historial --}}
        <form method="GET" action="{{ route('pos.cuts.history') }}" class="mt-4 pt-4 border-t border-mz-border-light/60 flex flex-wrap items-end gap-3">
            <div>
                <label for="hist-date" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Fecha</label>
                <input id="hist-date" type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="mz-input mt-1.5 h-11 w-44 rounded-xl text-sm font-semibold">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-mz-accent-primary hover:bg-mz-accent-hover text-white px-5 text-xs font-bold transition-all shadow-md shadow-mz-accent-primary/20 active:scale-95">
                    <x-icon name="search" class="size-4" />
                    Buscar
                </button>
                <a href="{{ route('pos.cuts.history') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-mz-border-light bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary hover:text-white transition-all active:scale-95">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Tabla de cortes guardados --}}
    <section class="mt-5 rounded-2xl border border-mz-border bg-mz-bg-surface shadow-xl shadow-black/20 overflow-hidden">
        <div class="overflow-x-auto pos-scrollbar">
            <table class="min-w-full divide-y divide-mz-border text-sm">
                <thead class="bg-mz-bg-surface-2/40">
                    <tr class="text-left text-xs uppercase tracking-wider text-mz-text-muted">
                        <th class="px-5 py-3.5 font-semibold text-center">
                            <input type="checkbox" id="select-all-cuts" class="w-4 h-4 text-mz-accent-primary rounded border-mz-accent-primary focus:ring-mz-accent-primary">
                        </th>
                        <th class="px-5 py-3.5 font-semibold">Fecha</th>
                        <th class="px-5 py-3.5 font-semibold">Versión</th>
                        <th class="px-5 py-3.5 font-semibold">Generado el</th>
                        <th class="px-5 py-3.5 font-semibold">Por</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Ventas</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Caja</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Diferencia</th>
                        <th class="px-5 py-3.5 font-semibold">Estado</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light/40">
                    @forelse ($cuts as $cut)
                        @php
                            $totals = $cut->totals ?? [];
                            $computer = (float) ($totals['computer_panel']['subtotal'] ?? 0);
                            $cashBox = (float) ($totals['cash_box_panel']['subtotal'] ?? 0);
                            $diff = round($cashBox - $computer, 2);
                            $isRec = abs($diff) < 0.005;
                        @endphp
                        <tr class="group transition-colors duration-150 hover:bg-mz-bg-surface-2/40">
                            <td class="px-5 py-3.5 text-center">
                                <input type="checkbox" class="cut-checkbox w-4 h-4 text-mz-accent-primary rounded border-mz-accent-primary focus:ring-mz-accent-primary" value="{{ $cut->id }}">
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-bold text-white text-xs font-mono">{{ $cut->cut_date->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-center font-mono text-xs font-bold text-mz-accent-primary">v{{ $cut->version }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs text-mz-text-muted font-mono">{{ $cut->generated_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3.5 text-xs text-mz-text-secondary">{{ $cut->generatedBy?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right font-display text-sm font-bold tabular-nums text-white">${{ number_format((float) ($totals['totals']['sales'] ?? 0), 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right font-display text-sm font-bold tabular-nums text-white">${{ number_format($cashBox, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right font-display text-sm font-bold tabular-nums {{ $isRec ? 'text-mz-success' : 'text-mz-error' }}">
                                ${{ number_format(abs($diff), 2) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider {{ $isRec ? 'bg-mz-success/15 text-mz-success border border-mz-success/30' : 'bg-mz-error/15 text-mz-error border border-mz-error/30' }}">
                                    {{ $isRec ? 'Cuadra' : 'Diferencia' }}
                                </span>
                                @if ($cut->isClosed())
                                    <span class="mt-0.5 block text-[10px] text-mz-text-muted font-semibold">Día cerrado</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center gap-1.5">
                                    <button 
                                        type="button" 
                                        onclick="downloadPdf('{{ route('pos.cuts.pdf', ['id' => $cut->id]) }}')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-zinc-200 bg-zinc-800 border border-zinc-700 hover:bg-zinc-700 hover:text-white transition-all duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Descargar PDF
                                    </button>
                                    <button 
                                        type="button" 
                                        onclick="deleteCut('{{ route('pos.cuts.destroy', $cut->id) }}')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-200 bg-red-900/20 border border-red-900/30 hover:bg-red-900/30 hover:text-red-400 transition-all duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Borrar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-16 text-center">
                                <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-mz-bg-surface-2 text-mz-text-muted">
                                    <x-icon name="report" class="size-7" />
                                </div>
                                <p class="mt-3 text-sm font-bold text-mz-text-primary">Todavía no hay versiones de corte guardadas</p>
                                <a href="{{ route('pos.cuts.index') }}" class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-mz-accent-primary hover:text-mz-accent-hover">
                                    Generar el corte de hoy &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $cuts->links() }}</div>

    <script>
        async function downloadPdf(urlDescarga) {
            try {
                const response = await fetch(urlDescarga, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/pdf',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    // Si el servidor responde con error, obtener texto sin romper con json()
                    const errorText = await response.text();
                    console.error("Error del servidor:", errorText);
                    alert(`Error al generar el PDF (${response.status})`);
                    return;
                }

                // Procesar correctamente como archivo binario (Blob)
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'corte_global.pdf';
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error("Error de red:", error);
                alert("Error de red al descargar el PDF. Inténtelo de nuevo.");
            }
        }

        async function deleteCut(urlEliminar) {
            if (!confirm('¿Está seguro de que desea eliminar este corte del historial? Esta acción no se puede deshacer.')) {
                return;
            }

            try {
                const response = await fetch(urlEliminar, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error("Error del servidor:", errorText);
                    alert('Error al eliminar el corte.');
                    return;
                }

                // Recargar la página para reflejar los cambios
                window.location.reload();
            } catch (error) {
                console.error("Error de red:", error);
                alert("Error de red al eliminar el corte. Inténtelo de nuevo.");
            }
        }

        // --- Selección múltiple de cortes ---
        const selectAllCheckbox = document.getElementById('select-all-cuts');
        const cutCheckboxes = document.querySelectorAll('.cut-checkbox');
        const btnDeleteSelected = document.getElementById('btn-delete-selected');

        function updateDeleteButtonVisibility() {
            const checked = document.querySelectorAll('.cut-checkbox:checked');
            if (checked.length > 0) {
                btnDeleteSelected.classList.remove('hidden');
            } else {
                btnDeleteSelected.classList.add('hidden');
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                cutCheckboxes.forEach(cb => cb.checked = this.checked);
                updateDeleteButtonVisibility();
            });
        }

        cutCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateDeleteButtonVisibility);
        });

        async function deleteSelectedCuts() {
            const checked = document.querySelectorAll('.cut-checkbox:checked');
            const ids = Array.from(checked).map(cb => cb.value);

            if (ids.length === 0) {
                alert('Seleccione al menos un corte.');
                return;
            }

            if (!confirm(`¿Está seguro de que desea eliminar ${ids.length} corte(s) seleccionado(s)? Esta acción no se puede deshacer.`)) {
                return;
            }

            try {
                const response = await fetch('{{ route('pos.cuts.destroy-selected') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ ids: ids }),
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error("Error del servidor:", errorText);
                    alert('Error al eliminar los cortes seleccionados.');
                    return;
                }

                const result = await response.json();
                alert(result.message || 'Cortes eliminados correctamente.');
                window.location.reload();
            } catch (error) {
                console.error("Error de red:", error);
                alert("Error de red al eliminar los cortes. Inténtelo de nuevo.");
            }
        }
    </script>

@endsection
