<div class="ticket ticket-sheet" style="--ticket-width: {{ $settings['ticket']['width_mm'] }}mm">
    <div class="center">
        @if ($settings['ticket']['show_logo'])
            <div style="display:inline-block;background:#000;color:#fff;padding:8px;font:bold 24px sans-serif;border-radius:4px">MZ</div>
        @endif
        <p class="business">{{ $settings['business']['name'] }}</p>
        @if (! empty($settings['business']['address']))
            <p class="muted">{{ $settings['business']['address'] }}</p>
        @endif
        @if (! empty($settings['business']['phone']))
            <p class="muted">Tel. {{ $settings['business']['phone'] }}</p>
        @endif
        @if ($settings['ticket']['show_tax_id'] && ! empty($settings['business']['tax_id']))
            <p class="muted">RFC {{ $settings['business']['tax_id'] }}</p>
        @endif
    </div>
    <hr class="divider">
    <div class="center">
        <p class="folio">{{ $sale->folio }}</p>
        <p class="muted">{{ $sale->paid_at?->format('d/m/Y H:i') ?? $sale->created_at->format('d/m/Y H:i') }}</p>
        @if ($settings['ticket']['show_cashier'])
            <p class="muted">Atendió: {{ $sale->user?->name }}</p>
        @endif
    </div>
    <hr class="divider">
    <table>
        <thead><tr><th>Producto</th><th class="num">Cant.</th><th class="num">Importe</th></tr></thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>
                        <span class="item-name">{{ $item->product_name }}</span>
                        <span class="muted" style="display:block">
                            @if ($settings['ticket']['show_sku'])
                                {{ $item->sku ?: $item->barcode }} ·
                            @endif
                            ${{ number_format((float) $item->unit_price, 2) }} {{ $item->saleMode() === \App\Models\Product::MODE_KIT ? 'por paquete' : 'por unidad' }}
                            @if ($item->isBulk()) · A granel @endif
                        </span>
                    </td>
                    <td class="num">{{ \App\Support\Quantity::format($item->quantity) }}</td>
                    <td class="num">${{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
                @if ($item->returned_quantity > 0)
                    <tr><td colspan="3" class="muted">Cantidad devuelta{{ $item->isBulk() ? ' a granel' : ($item->saleMode() === \App\Models\Product::MODE_KIT ? ' en paquetes' : ' en piezas') }}: {{ \App\Support\Quantity::format($item->returned_quantity) }}</td></tr>
                @endif
            @endforeach
        </tbody>
    </table>
    <hr class="divider">
    <table class="totals">
        <tr><td class="label">Subtotal</td><td class="num">${{ number_format((float) $sale->subtotal, 2) }}</td></tr>
        @if ($settings['ticket']['show_tax'])
            <tr><td class="label">IVA incluido</td><td class="num">${{ number_format((float) $sale->tax_total, 2) }}</td></tr>
        @endif
        <tr class="total-row"><td>Total</td><td class="num">${{ number_format((float) $sale->total, 2) }}</td></tr>
        <tr><td class="label">Pago con</td><td class="num">${{ number_format((float) $sale->amount_paid, 2) }}</td></tr>
        <tr><td class="label">Cambio</td><td class="num">${{ number_format((float) $sale->change_amount, 2) }}</td></tr>
    </table>
    <hr class="divider">
    <div class="center">
        <p class="muted">Método de pago: {{ config('pos.payment_methods.'.($sale->payment_method ?? 'cash').'.label', ucfirst($sale->payment_method ?? 'efectivo')) }}</p>
        <p class="footer" style="white-space:pre-line">{{ $settings['ticket']['footer'] }}</p>
        @if (filled($settings['ticket']['policy']))
            <p class="muted" style="white-space:pre-line">{{ $settings['ticket']['policy'] }}</p>
        @endif
        @if ($settings['ticket']['show_qr'] && $settings['ticket']['qr_image_url'])
            <img src="{{ $settings['ticket']['qr_image_url'] }}" alt="Código QR de facturación o soporte" style="display:block;width:24mm;height:24mm;object-fit:contain;margin:12px auto">
            <p class="muted">Facturación / Soporte</p>
        @endif
    </div>
</div>
