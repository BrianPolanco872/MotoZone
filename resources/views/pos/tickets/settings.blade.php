@extends('layouts.admin')

@section('title', 'Configurar Ticket e Impresora')
@section('subtitle', 'Personaliza cada detalle de tus comprobantes de venta')

@section('content')
    @php
        $savedSettings = ['business' => $settings['business'], 'ticket' => $settings['ticket']];
        $paperWidth = (int) old('ticket.width_mm', $settings['ticket']['width_mm']) === 80 ? 80 : 58;
        $options = [
            'show_logo' => ['Isotipo MotoZone', 'Incluye el distintivo MZ en el encabezado.'],
            'show_tax_id' => ['RFC del negocio', 'Muestra la identificación fiscal registrada.'],
            'show_sku' => ['Código SKU', 'Identifica cada producto en el detalle.'],
            'show_cashier' => ['Nombre del cajero', 'Indica quién atendió la venta.'],
            'show_tax' => ['Desglose de IVA', 'Muestra el impuesto incluido en el total.'],
            'show_qr' => ['Código QR', 'Incluye tu imagen real de soporte o facturación.'],
        ];
    @endphp

    <style>
        @include('pos.tickets.receipt-styles')
        .ticket-settings-tab[aria-selected="true"] {
            color: var(--color-mz-accent-primary);
            border-color: currentColor;
            background: color-mix(in srgb, currentColor 8%, transparent);
        }
        [data-ticket-settings] [hidden] { display: none !important; }
        #ticket-print-root { display: none; }
        @media print {
            html[data-pos-theme], body.ticket-example-print { height: auto; margin: 0; padding: 0; background: white; color-scheme: light; }
            body.ticket-example-print > :not(#ticket-print-root) { display: none !important; }
            #ticket-print-root { display: block; }
        }
    </style>

    <div data-ticket-settings data-saved="{{ json_encode($savedSettings) }}" class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('pos.index') }}" class="mb-3 inline-flex items-center gap-2 text-xs text-mz-text-muted hover:text-mz-accent-primary"><x-icon name="arrow-uturn-left" class="size-4" /> Volver al punto de venta</a>
                <h2 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">Configurar Ticket <span class="text-mz-accent-primary">e Impresora</span></h2>
                <p class="mt-2 text-sm text-mz-text-muted">Tu negocio, en cada ticket.</p>
            </div>
            <span class="rounded-full border border-mz-border-light bg-mz-bg-surface px-3 py-1.5 text-xs text-mz-text-secondary">Punto de venta / Configuración</span>
        </div>

        <div class="flex gap-3 rounded-xl border border-mz-accent-primary/25 bg-mz-accent-primary/5 p-4">
            <x-icon name="printer" class="mt-0.5 size-5 shrink-0 text-mz-accent-primary" />
            <div class="text-sm"><p class="font-semibold">Impresión mediante navegador</p><p class="mt-1 text-mz-text-muted">Selecciona tu impresora instalada en el diálogo de impresión. Esta página no conecta por USB ni confirma la impresión física.</p></div>
        </div>

        @if ($errors->any())
            <div role="alert" class="mz-alert mz-alert-error">
                <p class="font-semibold">Revisa los siguientes campos:</p>
                <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form id="ticket-settings-form" action="{{ route('pos.tickets.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div role="tablist" aria-label="Configuración del ticket" class="grid grid-cols-3 border-b border-mz-border-light">
                @foreach (['connection' => 'Conexión', 'fields' => 'Campos', 'preview' => 'Vista previa'] as $key => $label)
                    <button type="button" id="ticket-tab-{{ $key }}" role="tab" aria-controls="ticket-panel-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}" data-ticket-tab="{{ $key }}" class="ticket-settings-tab border-b-2 border-transparent px-2 py-4 text-sm font-semibold text-mz-text-muted transition-colors hover:text-mz-text-primary">{{ $label }}</button>
                @endforeach
            </div>

            <div data-ticket-workspace class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                <section id="ticket-panel-connection" role="tabpanel" aria-labelledby="ticket-tab-connection" tabindex="0" data-ticket-panel="connection" class="space-y-5">
                    <div class="mz-card rounded-xl p-5 sm:p-6">
                        <div class="mb-6 flex items-center gap-3"><span class="flex size-11 items-center justify-center rounded-xl bg-mz-accent-primary/10 text-mz-accent-primary"><x-icon name="printer" /></span><div><h3 class="font-semibold">Preferencias de impresión</h3><p class="mt-1 text-xs text-mz-text-muted">Configura el papel de tu impresora térmica.</p></div></div>
                        <label for="ticket-width" class="mb-2 block text-sm font-medium">Ancho del papel</label>
                        <select id="ticket-width" name="ticket[width_mm]" class="mz-select">
                            @foreach ([80 => '80 mm · Estándar', 58 => '58 mm · Compacto'] as $width => $label)
                                <option value="{{ $width }}" @selected($paperWidth === $width)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs leading-relaxed text-mz-text-muted">Selecciona también este tamaño en el controlador de tu impresora. Imprime a escala 100 %, sin encabezados ni pies del navegador.</p>
                        <div class="mt-6 divide-y divide-mz-border border-t border-mz-border">
                            @foreach (['Corte automático del papel', 'Apertura del cajón de dinero'] as $label)
                                <label class="flex items-center justify-between gap-4 py-4 text-sm text-mz-text-muted"><span>{{ $label }}<span class="mt-1 block text-xs">No disponible desde esta página</span></span><input type="checkbox" disabled class="size-5 accent-orange-500" /></label>
                            @endforeach
                        </div>
                        <p class="rounded-lg bg-mz-bg-primary p-3 text-xs leading-relaxed text-mz-text-muted">Estas funciones dependen del controlador del sistema y del navegador. No se envían comandos ESC/POS directos.</p>
                    </div>
                    <div class="mz-card rounded-xl p-5"><h3 class="font-semibold">Haz una prueba antes de guardar</h3><p class="mt-2 text-sm text-mz-text-muted">Se imprimirá el borrador de la vista previa, sin registrar una venta ni guardar cambios.</p><x-button variant="secondary" data-ticket-print class="mt-4 w-full"><x-icon name="printer" class="size-4" /> Probar impresión</x-button></div>
                </section>

                <section id="ticket-panel-fields" role="tabpanel" aria-labelledby="ticket-tab-fields" tabindex="0" data-ticket-panel="fields" hidden class="space-y-5">
                    <div class="mz-card space-y-5 rounded-xl p-5 sm:p-6">
                        <h3 class="flex items-center gap-2 font-semibold"><x-icon name="store" class="size-5 text-mz-accent-primary" /> Datos del negocio</h3>
                        @foreach (['name' => 'Nombre del negocio', 'address' => 'Dirección', 'phone' => 'Teléfono', 'tax_id' => 'RFC'] as $key => $label)
                            <div><x-input :id="'ticket-business-'.$key" :name="'business['.$key.']'" :label="$label" :value="old('business.'.$key, $settings['business'][$key])" :error="$errors->first('business.'.$key)" :required="$key === 'name'" /></div>
                        @endforeach
                    </div>
                    <div class="mz-card rounded-xl p-5 sm:p-6">
                        <h3 class="mb-3 font-semibold">Contenido del ticket</h3>
                        <div class="divide-y divide-mz-border">
                            @foreach ($options as $key => [$label, $description])
                                <label for="ticket-{{ $key }}" class="flex cursor-pointer items-center justify-between gap-4 py-4"><span><span class="block text-sm font-medium">{{ $label }}</span><span class="mt-1 block text-xs text-mz-text-muted">{{ $description }}</span></span><input type="hidden" name="ticket[{{ $key }}]" value="0"><input id="ticket-{{ $key }}" type="checkbox" name="ticket[{{ $key }}]" value="1" @checked(old('ticket.'.$key, $settings['ticket'][$key])) class="size-5 shrink-0 accent-orange-500" /></label>
                            @endforeach
                        </div>
                        <div class="mt-4 rounded-lg border border-dashed border-mz-border-light p-4">
                            <label for="ticket-qr-image" class="block text-sm font-medium">Imagen del código QR</label>
                            <p id="ticket-qr-help" class="mt-2 text-xs leading-relaxed text-mz-text-muted">Carga un código QR real de soporte o facturación. PNG, máximo 1 MB. No generamos códigos ni comprobamos su destino.</p>
                            <input id="ticket-qr-image" type="file" name="qr_image" accept="image/png" aria-describedby="ticket-qr-help ticket-qr-status" class="mt-3 block w-full text-xs text-mz-text-secondary file:mr-3 file:rounded-md file:border-0 file:bg-mz-bg-surface-2 file:px-3 file:py-2 file:text-mz-text-primary" />
                            <p id="ticket-qr-status" role="status" class="mt-2 text-xs text-mz-text-muted">{{ $settings['ticket']['qr_image_url'] ? 'Hay una imagen guardada.' : 'No hay una imagen guardada.' }}</p>
                        </div>
                    </div>
                    <div class="mz-card space-y-5 rounded-xl p-5 sm:p-6">
                        @foreach (['footer' => 'Mensaje de despedida', 'policy' => 'Política de cambios y devoluciones'] as $key => $label)
                            <div><label for="ticket-{{ $key }}" class="mb-2 block text-sm font-medium">{{ $label }}</label><textarea id="ticket-{{ $key }}" name="ticket[{{ $key }}]" rows="3" class="mz-input">{{ old('ticket.'.$key, $settings['ticket'][$key]) }}</textarea></div>
                        @endforeach
                    </div>
                </section>

                <section id="ticket-panel-preview" role="tabpanel" aria-labelledby="ticket-tab-preview" tabindex="0" data-ticket-panel="preview" data-ticket-preview hidden class="rounded-xl border border-mz-border bg-mz-bg-surface p-4 sm:p-5">
                    <div class="mb-5 flex items-center justify-between gap-2"><h3 class="text-sm font-semibold">Vista previa</h3><span class="rounded bg-mz-accent-primary/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-mz-accent-primary">Borrador</span></div>
                    <article id="ticket-paper" aria-label="Ticket de ejemplo" class="ticket-sheet" style="--ticket-width: {{ $paperWidth }}mm">
                        <div class="center receipt-heading">
                            <div data-ticket-visible="show_logo" class="receipt-logo">MZ</div>
                            <p data-ticket-text="business.name" class="business"></p>
                            <p data-ticket-text="business.address" class="muted"></p>
                            <p class="muted">Tel. <span data-ticket-text="business.phone"></span></p>
                            <p class="muted" data-ticket-visible="show_tax_id">RFC <span data-ticket-text="business.tax_id"></span></p>
                        </div>
                        <hr class="divider">
                        <div class="center receipt-heading">
                            <p class="muted">VENTA DE EJEMPLO</p>
                            <p class="muted" data-ticket-visible="show_cashier">Atendió: Cajero de ejemplo</p>
                        </div>
                        <hr class="divider">
                        <table class="items">
                            <thead><tr><th scope="col" class="num quantity">Cant.</th><th scope="col" class="description">Producto</th><th scope="col" class="num">Importe</th></tr></thead>
                            <tbody>
                                <tr>
                                    <td class="num quantity">1</td>
                                    <td class="description">
                                        <span class="item-name">Aceite</span>
                                        <span data-ticket-visible="show_sku" class="muted item-sku">ACE-001</span>
                                        <span class="muted item-price"><span class="unit-amount">$148.00</span> por unidad</span>
                                    </td>
                                    <td class="num">$148.00</td>
                                </tr>
                            </tbody>
                        </table>
                        <hr class="divider">
                        <div class="receipt-summary">
                            <p>N.º de artículos: 1</p>
                            <table>
                                <tr data-ticket-visible="show_tax"><td class="label">IVA incluido</td><td class="num">$20.41</td></tr>
                                <tr class="total-row"><td>Total</td><td class="num">$148.00</td></tr>
                                <tr><td class="label">Pago con</td><td class="num">$500.00</td></tr>
                                <tr><td class="label">Cambio</td><td class="num">$352.00</td></tr>
                            </table>
                        </div>
                        <hr class="divider">
                        <div class="center receipt-end">
                            <p class="muted">Método de pago: Efectivo</p>
                            <p data-ticket-text="ticket.footer" class="receipt-footer"></p>
                            <p data-ticket-text="ticket.policy" class="muted receipt-footer"></p>
                            <div data-ticket-visible="show_qr" class="receipt-qr-block" hidden>
                                <img data-ticket-qr hidden alt="Código QR" class="receipt-qr">
                            </div>
                        </div>
                    </article>
                    <div class="mt-5 space-y-3"><p class="text-center text-xs text-mz-text-muted">Ejemplo · <span data-ticket-width-label>{{ $paperWidth }}</span> mm · No es un comprobante de venta</p><x-button variant="secondary" data-ticket-print class="w-full"><x-icon name="printer" class="size-4" /> Probar impresión</x-button><p class="text-center text-xs leading-relaxed text-mz-text-muted">Para descargar, elige «Guardar como PDF» en el diálogo de impresión.</p></div>
                </section>
            </div>

            <div class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center justify-between gap-3 border-t border-mz-border-light bg-mz-bg-surface/95 px-4 py-4 backdrop-blur sm:mx-0 sm:rounded-xl sm:border sm:px-5">
                <p class="hidden text-xs text-mz-text-muted sm:block">Los cambios se aplican al guardar.</p>
                <div class="ml-auto flex gap-3"><x-button type="reset" variant="secondary">Descartar</x-button><x-button type="submit"><x-icon name="check" class="size-4" /> Guardar cambios</x-button></div>
            </div>
        </form>
    </div>

    <script>
        window.addEventListener('beforeprint', () => {
            if (document.getElementById('ticket-print-root')) {
                return;
            }

            const printRoot = document.getElementById('ticket-paper').cloneNode(true);
            printRoot.id = 'ticket-print-root';
            document.body.append(printRoot);
            document.body.classList.add('ticket-example-print');
        });

        window.addEventListener('afterprint', () => {
            document.getElementById('ticket-print-root')?.remove();
            document.body.classList.remove('ticket-example-print');
        });
    </script>
@endsection
