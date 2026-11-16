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
        <h2 class="cms-label">Información complementaria</h2>
        <p class="text-xs text-mz-text-secondary">Todos los campos son opcionales. Completa solo los beneficios e indicadores que puedas confirmar; no se calculan automáticamente. Las secciones sin contenido no se publican.</p>
    </div>

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
