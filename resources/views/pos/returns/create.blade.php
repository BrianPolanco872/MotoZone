@extends('layouts.admin')

@section('title', 'Nueva devolución')
@section('subtitle', 'Venta ' . $sale->folio)

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">
                Venta del <span class="font-medium text-slate-700">{{ $sale->paid_at?->format('d/m/Y H:i') }}</span>
                · Atendió {{ $sale->user?->name }}
                · Total ${{ number_format((float) $sale->total, 2) }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pos.returns.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                <x-icon name="arrow-uturn-left" class="size-4" />
                Devoluciones
            </a>
            <a href="{{ route('pos.sales.ticket', $sale) }}" target="_blank" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                <x-icon name="printer" class="size-4" />
                Ticket
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
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

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Productos a devolver</h2>
                <p class="text-xs text-slate-500">Indica cuántas piezas regresan. Solo puedes devolver lo que sigue sin devolución.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3 font-medium">Producto</th>
                            <th class="px-5 py-3 text-right font-medium">Precio</th>
                            <th class="px-5 py-3 text-center font-medium">Vendidas</th>
                            <th class="px-5 py-3 text-center font-medium">Devueltas</th>
                            <th class="px-5 py-3 text-center font-medium">Disponibles</th>
                            <th class="px-5 py-3 text-center font-medium">Devolver</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($sale->items as $index => $item)
                            @php($available = $item->availableReturnQuantity())
                            <tr @class(['transition hover:bg-slate-50', 'opacity-60' => $available === 0])>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-900">{{ $item->product_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $item->sku ?: $item->barcode }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-slate-700">${{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-center tabular-nums text-slate-700">{{ $item->quantity }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-center tabular-nums text-slate-700">{{ $item->returned_quantity }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-center">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        'bg-emerald-50 text-emerald-700' => $available > 0,
                                        'bg-slate-100 text-slate-500' => $available === 0,
                                    ])>{{ $available }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-center">
                                    <input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}">
                                    <input
                                        type="number"
                                        name="items[{{ $index }}][quantity]"
                                        min="0"
                                        max="{{ $available }}"
                                        value="{{ old("items.{$index}.quantity", 0) }}"
                                        @disabled($available === 0)
                                        class="h-9 w-20 rounded-md border border-slate-200 bg-white px-2 text-center text-sm font-medium tabular-nums text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:bg-slate-100"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <label for="return-reason" class="block text-sm font-medium text-slate-700">Motivo de la devolución</label>
            <textarea id="return-reason" name="reason" rows="3" required maxlength="500" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="Ej. producto defectuoso, talla incorrecta, cliente cambió de opinión...">{{ old('reason') }}</textarea>
            @error('reason') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror

            <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a href="{{ route('pos.returns.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancelar</a>
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 text-sm font-semibold text-white transition hover:bg-brand-800">
                    <x-icon name="arrow-uturn-left" class="size-4" />
                    Enviar solicitud
                </button>
            </div>
        </section>
    </form>
@endsection
