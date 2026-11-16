<form id="form-{{ $block }}" action="{{ route('admin.home.update') }}" method="POST" class="space-y-5" data-editor-form="{{ $label }}: textos y visibilidad">
    @csrf
    @method('PUT')
    <input type="hidden" name="block" value="{{ $block }}">
    <div class="cms-panel">@include('admin.home.partials.visibility', ['block' => $block, 'label' => $label])</div>
    <div class="cms-panel space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="cms-label">Título de la sección</h2>
            <button type="button" class="inline-flex min-h-11 items-center gap-1 text-xs font-semibold text-orange-300" data-description-trigger="{{ $block }}_description" hidden><x-icon name="pencil" class="size-4" /> Editar descripción</button>
        </div>
        @include('admin.home.partials.text-field', ['key' => $block.'_title', 'label' => 'Título del bloque'])
        @include('admin.home.partials.text-field', ['key' => $block.'_description', 'label' => 'Descripción'])
        <x-button type="submit" variant="secondary" size="sm">Guardar textos y visibilidad</x-button>
    </div>
</form>
