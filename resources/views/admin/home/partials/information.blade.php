@php
    $useOldInformation = old('block') === 'information';
    $informationSections = [
        'notice' => ['label' => 'Aviso del mostrador', 'fields' => ['store_notice']],
        'benefits' => ['label' => 'Beneficios', 'fields' => ['benefit_1_title', 'benefit_1_description', 'benefit_2_title', 'benefit_2_description', 'benefit_3_title', 'benefit_3_description', 'benefit_4_title', 'benefit_4_description']],
        'metrics' => ['label' => 'Indicadores', 'fields' => ['metric_1_value', 'metric_1_label', 'metric_2_value', 'metric_2_label', 'metric_3_value', 'metric_3_label']],
        'advice' => ['label' => 'Asesoría', 'fields' => ['advice_title', 'advice_description']],
    ];
@endphp

<form id="form-information" action="{{ route('admin.home.update') }}" method="POST" class="space-y-5" data-editor-form="Información" @if ($useOldInformation) data-editor-recovered @endif>
    @csrf
    @method('PUT')
    <input type="hidden" name="block" value="information">

    <div class="cms-panel space-y-2">
        <h2 class="cms-label">Información de la tienda y Pie de página</h2>
        <p class="text-xs text-mz-text-secondary">Edita los datos que se mostrarán en el Footer y secciones informativas de la tienda pública. Los campos que dejes vacíos se ocultarán automáticamente.</p>
    </div>

    {{-- Sección de Footer y Datos del negocio --}}
    <fieldset class="cms-panel space-y-5">
        <legend class="cms-label flex items-center gap-2">
            <x-icon name="store" class="size-4 text-mz-accent-primary" />
            <span>Pie de página (Footer)</span>
        </legend>
        <p class="text-xs text-mz-text-secondary">Configuración directa del pie de página público (marca, ubicación, horarios, contacto y redes).</p>

        {{-- 1. Descripción corta del negocio --}}
        <div>
            <label for="footer_description" class="mb-1 block text-sm font-medium">Descripción corta del negocio</label>
            <textarea id="footer_description" name="content[footer_description]" rows="3" maxlength="1000" class="mz-input w-full" placeholder="Refacciones y accesorios para tu moto...">{{ $useOldInformation ? old('content.footer_description') : ($page->text('footer_description') ?: $page->text('store_description')) }}</textarea>
            <p class="mt-1 text-xs text-mz-text-muted">Aparece en el pie de página debajo del logo de MOTOZONE.</p>
            @error('content.footer_description')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>

        {{-- 2. Ubicación física y Enlace de Google Maps --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="footer_address" class="mb-1 block text-sm font-medium">Dirección / Ubicación física</label>
                <textarea id="footer_address" name="content[address]" rows="2" maxlength="500" class="mz-input w-full" placeholder="Av. de los Talleres 245, Col. Industrial...">{{ $useOldInformation ? old('content.address') : $page->text('address') }}</textarea>
                @error('content.address')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="footer_maps_url" class="mb-1 block text-sm font-medium">Enlace de Google Maps (opcional)</label>
                <input id="footer_maps_url" type="url" name="content[maps_url]" value="{{ $useOldInformation ? old('content.maps_url') : $page->text('maps_url') }}" maxlength="2048" class="mz-input h-11 w-full" placeholder="https://maps.google.com/...">
                <p class="mt-1 text-xs text-mz-text-muted">Enlace HTTPS para abrir cómo llegar al mostrador.</p>
                @error('content.maps_url')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- 3. Horarios de atención --}}
        <div>
            <label for="footer_hours" class="mb-1 block text-sm font-medium">Horarios de atención</label>
            <input id="footer_hours" type="text" name="content[hours]" value="{{ $useOldInformation ? old('content.hours') : $page->text('hours') }}" maxlength="160" class="mz-input h-11 w-full" placeholder="Lun - Sáb: 9:00 AM - 7:00 PM">
            @error('content.hours')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>

        {{-- 4 y 5. WhatsApp, Teléfono y Correo --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="footer_whatsapp" class="mb-1 block text-sm font-medium">Número de WhatsApp</label>
                <input id="footer_whatsapp" type="tel" name="content[whatsapp]" value="{{ $useOldInformation ? old('content.whatsapp') : $page->text('whatsapp') }}" maxlength="32" class="mz-input h-11 w-full" placeholder="+52 55 1234 5678">
                <p class="mt-1 text-xs text-mz-text-muted">Con código de país para enlace directo https://wa.me/...</p>
                @error('content.whatsapp')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="footer_phone" class="mb-1 block text-sm font-medium">Teléfono de contacto</label>
                <input id="footer_phone" type="tel" name="content[phone]" value="{{ $useOldInformation ? old('content.phone') : $page->text('phone') }}" maxlength="32" class="mz-input h-11 w-full" placeholder="55 1234 5678">
                @error('content.phone')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="footer_email" class="mb-1 block text-sm font-medium">Correo electrónico</label>
                <input id="footer_email" type="email" name="content[email]" value="{{ $useOldInformation ? old('content.email') : $page->text('email') }}" maxlength="254" class="mz-input h-11 w-full" placeholder="contacto@motozone.com">
                @error('content.email')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- 6. Redes sociales (Facebook e Instagram) --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="footer_facebook_url" class="mb-1 block text-sm font-medium">Facebook (opcional)</label>
                <input id="footer_facebook_url" type="text" name="content[facebook_url]" value="{{ $useOldInformation ? old('content.facebook_url') : $page->text('facebook_url') }}" maxlength="2048" class="mz-input h-11 w-full" placeholder="https://facebook.com/motozone">
                <p class="mt-1 text-xs text-mz-text-muted">Enlace o usuario de tu página de Facebook.</p>
                @error('content.facebook_url')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="footer_instagram_url" class="mb-1 block text-sm font-medium">Instagram (opcional)</label>
                <input id="footer_instagram_url" type="text" name="content[instagram_url]" value="{{ $useOldInformation ? old('content.instagram_url') : $page->text('instagram_url') }}" maxlength="2048" class="mz-input h-11 w-full" placeholder="https://instagram.com/motozone">
                <p class="mt-1 text-xs text-mz-text-muted">Enlace o usuario de tu perfil de Instagram.</p>
                @error('content.instagram_url')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- 7. Texto de Copyright --}}
        <div>
            <label for="footer_copyright_text" class="mb-1 block text-sm font-medium">Texto de Copyright</label>
            <input id="footer_copyright_text" type="text" name="content[copyright_text]" value="{{ $useOldInformation ? old('content.copyright_text') : $page->text('copyright_text') }}" maxlength="160" class="mz-input h-11 w-full" placeholder="© {{ date('Y') }} MotoZone. Todos los derechos reservados.">
            <p class="mt-1 text-xs text-mz-text-muted">Puedes usar {year} para que el año se actualice automáticamente.</p>
            @error('content.copyright_text')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>
    </fieldset>

    {{-- Secciones complementarias (Avisos, Beneficios, Indicadores, Asesoría) --}}
    @foreach ($informationSections as $section => $settings)
        <fieldset class="cms-panel space-y-4">
            <legend class="cms-label">{{ $settings['label'] }}</legend>
            <div>
                <input type="hidden" name="content[show_{{ $section }}]" value="0">
                <label class="flex cursor-pointer items-center justify-between gap-4">
                    <span class="text-sm font-semibold">Mostrar {{ mb_strtolower($settings['label']) }} en tienda</span>
                    <input type="checkbox" role="switch" class="cms-switch shrink-0" name="content[show_{{ $section }}]" value="1" @checked($useOldInformation ? old('content.show_'.$section) : $page->enabled($section))>
                </label>
                @error('content.show_'.$section)<p class="mt-2 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
            @foreach ($settings['fields'] as $key)
                <div>
                    <label for="{{ $key }}" class="mb-1 block text-sm font-medium">{{ \App\Models\HomePage::INFORMATION_FIELDS[$key] }}</label>
                    @if ($key === 'store_notice' || str_ends_with($key, '_description'))
                        <textarea id="{{ $key }}" name="content[{{ $key }}]" rows="3" maxlength="{{ $key === 'store_notice' ? 500 : 1000 }}" class="mz-input w-full">{{ $useOldInformation ? old('content.'.$key) : $page->text($key) }}</textarea>
                    @else
                        <input id="{{ $key }}" type="text" name="content[{{ $key }}]" value="{{ $useOldInformation ? old('content.'.$key) : $page->text($key) }}" maxlength="160" class="mz-input h-11 w-full">
                    @endif
                    @error('content.'.$key)<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </fieldset>
    @endforeach

    <div class="cms-panel"><x-button type="submit">Guardar información</x-button></div>
</form>
