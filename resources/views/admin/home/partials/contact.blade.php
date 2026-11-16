@php($useOldContact = old('block') === 'contact')

<form id="form-contact" action="{{ route('admin.home.update') }}" method="POST" class="space-y-5" data-editor-form="Contacto" @if ($useOldContact) data-editor-recovered @endif>
    @csrf
    @method('PUT')
    <input type="hidden" name="block" value="contact">

    <div class="cms-panel space-y-4">
        <h2 class="cms-label">Contacto y mostrador</h2>
        <p class="text-xs text-mz-text-secondary">Datos públicos de la tienda. Todos los campos son opcionales; no cambian usuarios, correo del sistema ni tickets.</p>
        @foreach (['store_name' => ['text', 160], 'store_description' => ['textarea', 1000], 'phone' => ['tel', 32], 'whatsapp' => ['tel', 32], 'email' => ['email', 254], 'hours' => ['textarea', 1000], 'whatsapp_message' => ['textarea', 1000]] as $key => [$type, $maxLength])
            <div>
                <label for="{{ $key }}" class="mb-1 block text-sm font-medium">{{ \App\Models\HomePage::CONTACT_FIELDS[$key] }}</label>
                @if ($type === 'textarea')
                    <textarea id="{{ $key }}" name="content[{{ $key }}]" rows="3" maxlength="{{ $maxLength }}" class="mz-input w-full" @if ($key === 'hours') aria-describedby="hours-help" @endif>{{ $useOldContact ? old('content.'.$key) : $page->text($key) }}</textarea>
                @else
                    <input id="{{ $key }}" type="{{ $type }}" name="content[{{ $key }}]" value="{{ $useOldContact ? old('content.'.$key) : $page->text($key) }}" maxlength="{{ $maxLength }}" class="mz-input h-11 w-full" @if (in_array($key, ['whatsapp', 'email'], true)) aria-describedby="{{ $key }}-help" @endif>
                @endif
                @if ($key === 'whatsapp')
                    <p id="whatsapp-help" class="mt-1 text-xs text-mz-text-muted">Incluye el código de país en el número de WhatsApp.</p>
                @elseif ($key === 'email')
                    <p id="email-help" class="mt-1 text-xs text-mz-text-muted">El enlace mailto abre el cliente de correo preferido del visitante, no necesariamente Gmail.</p>
                @elseif ($key === 'hours')
                    <p id="hours-help" class="mt-1 text-xs text-mz-text-muted">Los horarios son texto informativo; no calculan automáticamente si la tienda está abierta.</p>
                @endif
                @error('content.'.$key)<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>

    <div class="cms-panel space-y-4">
        <h2 class="cms-label">Burbujas de contacto</h2>
        <p class="text-xs text-mz-text-secondary">Solo aparecen si están activadas y tienen su dato de contacto configurado.</p>
        @foreach (['whatsapp_bubble' => 'Mostrar burbuja de WhatsApp', 'email_bubble' => 'Mostrar burbuja de correo'] as $section => $label)
            <div>
                <input type="hidden" name="content[show_{{ $section }}]" value="0">
                <label class="flex cursor-pointer items-center justify-between gap-4">
                    <span class="text-sm font-semibold">{{ $label }}</span>
                    <input type="checkbox" role="switch" class="cms-switch shrink-0" name="content[show_{{ $section }}]" value="1" @checked($useOldContact ? old('content.show_'.$section) : $page->enabled($section))>
                </label>
                @error('content.show_'.$section)<p class="mt-2 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
        <x-button type="submit">Guardar contacto</x-button>
    </div>
</form>
