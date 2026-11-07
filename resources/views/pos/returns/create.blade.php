@extends('layouts.admin')

@section('title', 'Nueva devolución')
@section('subtitle', 'Venta ' . $sale->folio)

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-mz-text-muted">
                Venta del <span class="font-medium text-mz-text-secondary">{{ $sale->paid_at?->format('d/m/Y H:i') }}</span>
                · Atendió {{ $sale->user?->name }}
                · Total ${{ number_format((float) $sale->total, 2) }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pos.returns.index') }}" class="x-button x-button-secondary size-sm h-10">
                <x-icon name="arrow-uturn-left" class="size-4" />
                Devoluciones
            </a>
            <a href="{{ route('pos.sales.ticket', $sale) }}" target="_blank" class="x-button x-button-secondary size-sm h-10">
                <x-icon name="printer" class="size-4" />
                Ticket
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="mz-alert mz-alert-error mt-5" role="alert">
            <p class="font-semibold">Revisa la solicitud:</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('pos.returns.store') }}" method="POST" class="mt-5 space-y-5">
        @csrf
        <input type="hidden" name="sale_id" value="{{ $sale->id }}">

        <section class="mz-card overflow-hidden">
            <div class="border-b border-mz-border px-5 py-4">
                <h2 class="text-base font-semibold text-mz-text-primary">Productos a devolver</h2>
                <p class="text-xs text-mz-text-muted">Indica cuántas piezas regresan. Solo puedes devolver lo que sigue sin devolución.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="mz-table">
                    <thead>
                        <tr>
                            <th class="font-medium">Producto</th>
                            <th class="text-right font-medium">Precio</th>
                            <th class="text-center font-medium">Vendidas</th>
                            <th class="text-center font-medium">Devueltas</th>
                            <th class="text-center font-medium">Disponibles</th>
                            <th class="text-center font-medium">Devolver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $index => $item)
                            @php($available = $item->availableReturnQuantity())
                            <tr @class(['opacity-60' => $available === 0])>
                                <td>
                                    <p class="font-medium text-mz-text-primary">{{ $item->product_name }}</p>
                                    <p class="text-xs text-mz-text-muted">{{ $item->sku ?: $item->barcode }}</p>
                                </td>
                                <td class="whitespace-nowrap text-right tabular-nums">${{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="whitespace-nowrap text-center tabular-nums">{{ $item->quantity }}</td>
                                <td class="whitespace-nowrap text-center tabular-nums">{{ $item->returned_quantity }}</td>
                                <td class="whitespace-nowrap text-center">
                                    <span @class([
                                        'mz-badge',
                                        'mz-badge-available' => $available > 0,
                                        'mz-badge-out' => $available === 0,
                                    ])>{{ $available }}</span>
                                </td>
                                <td class="whitespace-nowrap text-center">
                                    <input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}">
                                    <input
                                        type="number"
                                        name="items[{{ $index }}][quantity]"
                                        min="0"
                                        max="{{ $available }}"
                                        value="{{ old("items.{$index}.quantity", 0) }}"
                                        @disabled($available === 0)
                                        class="h-9 w-20 rounded-md border border-mz-border-light bg-mz-bg-surface-2 px-2 text-center text-sm font-medium tabular-nums text-mz-text-primary focus:border-mz-accent-primary focus:outline-none focus:ring-1 focus:ring-mz-accent-primary disabled:opacity-50"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mz-card mz-card-padded">
            <label for="return-reason" class="block text-sm font-medium text-mz-text-secondary">Motivo de la devolución</label>
            <textarea id="return-reason" name="reason" rows="3" required maxlength="500" class="mz-input mt-1.5" placeholder="Ej. producto defectuoso, talla incorrecta, cliente cambió de opinión...">{{ old('reason') }}</textarea>
            @error('reason') <p class="mt-1 text-xs font-medium text-mz-error">{{ $message }}</p> @enderror

            <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a href="{{ route('pos.returns.index') }}" class="x-button x-button-secondary size-md h-11">Cancelar</a>
                <button type="submit" class="x-button x-button-primary size-md h-11 font-semibold">
                    <x-icon name="arrow-uturn-left" class="size-4" />
                    Enviar solicitud
                </button>
            </div>
        </section>
    </form>
@endsection
