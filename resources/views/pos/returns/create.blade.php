@extends('layouts.admin')

@section('title', 'Nueva devolución')
@section('subtitle', 'Venta ' . $sale->folio)

@section('content')
    {{-- Barra superior informativa --}}
    <div class="rounded-2xl border border-mz-border bg-gradient-to-r from-mz-bg-surface via-mz-bg-surface/95 to-mz-bg-surface p-4 sm:p-5 shadow-xl shadow-black/25">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="rounded-md bg-mz-accent-primary/10 border border-mz-accent-primary/25 px-2.5 py-0.5 text-xs font-mono font-bold text-mz-accent-primary">Folio {{ $sale->folio }}</span>
                    <span class="text-xs text-mz-text-muted">· {{ $sale->paid_at?->format('d/m/Y H:i') }}</span>
                </div>
                <p class="text-xs text-mz-text-muted mt-1.5">
                    Atendido por: <strong class="text-white">{{ $sale->user?->name }}</strong>
                    · Total de la venta: <strong class="text-mz-accent-primary">${{ number_format((float) $sale->total, 2) }}</strong>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('pos.returns.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-3.5 text-xs font-bold text-mz-text-secondary hover:text-white transition-all shadow-xs active:scale-95">
                    <x-icon name="arrow-uturn-left" class="size-4" />
                    Devoluciones
                </a>
                <a href="{{ route('pos.sales.ticket', $sale) }}" target="_blank" class="inline-flex h-10 items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-3.5 text-xs font-bold text-mz-text-secondary hover:text-white transition-all shadow-xs active:scale-95">
                    <x-icon name="printer" class="size-4 text-mz-accent-primary" />
                    Ver ticket original
                </a>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-mz-error/30 bg-mz-error/10 p-4 text-sm text-mz-error shadow-md" role="alert">
            <p class="font-bold flex items-center gap-2">
                <x-icon name="alert" class="size-4" />
                Por favor revisa la información de la solicitud:
            </p>
            <ul class="mt-1.5 list-disc space-y-0.5 pl-6 text-xs text-mz-error/90">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('pos.returns.store') }}" method="POST" class="mt-5 space-y-5">
        @csrf
        <input type="hidden" name="sale_id" value="{{ $sale->id }}">

        {{-- Tabla de productos a devolver --}}
        <section class="rounded-2xl border border-mz-border bg-mz-bg-surface shadow-xl shadow-black/20 overflow-hidden">
            <div class="border-b border-mz-border px-5 py-4 bg-mz-bg-surface-2/30">
                <h2 class="text-sm font-bold text-white">Productos de la venta a devolver</h2>
                <p class="text-xs text-mz-text-muted mt-0.5">Ingresa la cantidad que el cliente regresa. Granel permite hasta 3 decimales; piezas y paquetes, solo enteros. No se puede superar la cantidad disponible para devolución.</p>
            </div>

            <div class="overflow-x-auto pos-scrollbar">
                <table class="min-w-full divide-y divide-mz-border text-sm">
                    <thead class="bg-mz-bg-surface-2/40">
                        <tr class="text-left text-xs uppercase tracking-wider text-mz-text-muted">
                            <th class="px-5 py-3.5 font-semibold">Producto</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Precio unitario</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Vendidas</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Ya devueltas</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Disponibles</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Cantidad a devolver</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mz-border-light/40">
                        @foreach ($sale->items as $index => $item)
                            @php($available = $item->availableReturnQuantity())
                            <tr @class(['group transition-colors duration-150 hover:bg-mz-bg-surface-2/40', 'opacity-50 bg-mz-bg-primary/20' => ! ($available > 0)])>
                                <td class="px-5 py-3.5">
                                    <p class="font-bold text-sm text-mz-text-primary group-hover:text-mz-accent-primary transition-colors">{{ $item->product_name }}</p>
                                    <p class="text-xs text-mz-text-muted font-mono mt-0.5">{{ $item->sku ?: ($item->barcode ?: 'S/C') }}</p>
                                    <p class="text-xs text-mz-text-muted">{{ match ($item->saleMode()) { \App\Models\Product::MODE_BULK => 'A granel', \App\Models\Product::MODE_KIT => 'Paquete (kit)', default => 'Por unidad/pieza' } }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right font-display text-base font-bold tabular-nums text-white">${{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-center font-medium text-mz-text-secondary tabular-nums">{{ \App\Support\Quantity::format($item->quantity) }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-center font-medium text-mz-text-muted tabular-nums">{{ \App\Support\Quantity::format($item->returned_quantity) }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-center">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums',
                                        'bg-mz-success/15 text-mz-success border border-mz-success/30' => $available > 0,
                                        'bg-mz-bg-surface-2 text-mz-text-muted border border-mz-border-light' => ! ($available > 0),
                                    ])>{{ \App\Support\Quantity::format($available) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-center">
                                    <input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}" @disabled(! ($available > 0))>
                                    <input
                                        type="number"
                                        name="items[{{ $index }}][quantity]"
                                        min="0"
                                        step="{{ $item->isBulk() ? '0.001' : '1' }}"
                                        max="{{ \App\Support\Quantity::format($available) }}"
                                        aria-label="Cantidad a devolver de {{ $item->product_name }}"
                                        value="{{ old("items.{$index}.quantity", 0) }}"
                                        @disabled(! ($available > 0))
                                        class="h-10 w-24 rounded-xl border border-mz-border-light bg-mz-bg-primary/90 px-2 text-center text-base font-bold tabular-nums text-white focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/20 disabled:opacity-40 transition-all shadow-inner"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Motivo y Envío --}}
        <section class="rounded-2xl border border-mz-border bg-mz-bg-surface p-5 sm:p-6 shadow-xl shadow-black/20">
            <label for="return-reason" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Motivo o justificación de la devolución</label>
            <textarea id="return-reason" name="reason" rows="3" required maxlength="500"
                      class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 p-3.5 text-sm text-white placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all mt-2 shadow-inner"
                      placeholder="Describe la causa (ej. producto con defecto de fábrica, talla incorrecta, cliente solicitó garantía)...">{{ old('reason') }}</textarea>
            @error('reason') <p class="mt-1.5 text-xs font-semibold text-mz-error">{{ $message }}</p> @enderror

            <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('pos.returns.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-mz-border-light px-5 text-xs font-bold text-mz-text-secondary hover:bg-mz-bg-surface-2 hover:text-white transition-all active:scale-95">
                    Cancelar
                </a>
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-mz-accent-primary hover:bg-mz-accent-hover text-white px-6 text-xs font-bold shadow-lg shadow-mz-accent-primary/25 transition-all active:scale-95">
                    <x-icon name="arrow-uturn-left" class="size-4" />
                    Enviar solicitud de devolución
                </button>
            </div>
        </section>
    </form>
@endsection
