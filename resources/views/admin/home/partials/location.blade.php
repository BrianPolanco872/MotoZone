@php($useOldLocation = old('block') === 'location')

<form id="form-location" action="{{ route('admin.home.update') }}" method="POST" class="space-y-5" data-editor-form="Ubicación" @if ($useOldLocation) data-editor-recovered @endif>
    @csrf
    @method('PUT')
    <input type="hidden" name="block" value="location">

    <div class="cms-panel">@include('admin.home.partials.visibility', ['block' => 'location', 'label' => 'Dirección y mapa de la tienda'])</div>

    <div class="cms-panel space-y-4">
        <h2 class="cms-label">Dirección y cómo llegar</h2>
        <p class="text-xs text-mz-text-secondary">Campos opcionales. Usa únicamente la ubicación real de tu tienda.</p>
        @foreach (['address', 'location_reference'] as $key)
            <div>
                <label for="{{ $key }}" class="mb-1 block text-sm font-medium">{{ \App\Models\HomePage::LOCATION_FIELDS[$key] }}</label>
                <textarea id="{{ $key }}" name="content[{{ $key }}]" rows="3" maxlength="500" class="mz-input w-full">{{ $useOldLocation ? old('content.'.$key) : $page->text($key) }}</textarea>
                @error('content.'.$key)<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
        <div>
            <label for="maps_url" class="mb-1 block text-sm font-medium">{{ \App\Models\HomePage::LOCATION_FIELDS['maps_url'] }}</label>
            <input id="maps_url" type="url" name="content[maps_url]" value="{{ $useOldLocation ? old('content.maps_url') : $page->text('maps_url') }}" maxlength="2048" class="mz-input h-11 w-full" aria-describedby="maps-url-help">
            <p id="maps-url-help" class="mt-1 text-xs text-mz-text-muted">Enlace HTTPS de Google Maps para abrir cómo llegar. Para mostrar el mapa dentro de la página, pega el enlace corto en el campo del mapa de abajo.</p>
            @error('content.maps_url')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="cms-panel space-y-4">
        <h2 class="cms-label">Mapa</h2>
        <div>
            <label for="map_mode" class="mb-1 block text-sm font-medium">{{ \App\Models\HomePage::LOCATION_FIELDS['map_mode'] }}</label>
            <select id="map_mode" name="content[map_mode]" class="mz-select h-11 w-full" aria-describedby="map-mode-help">
                @foreach (['' => 'Sin mapa', 'google' => 'Google Maps (enlace corto o insertado)', 'pin' => 'Pin por coordenadas (OpenStreetMap)'] as $value => $label)
                    <option value="{{ $value }}" @selected(($useOldLocation ? old('content.map_mode') : $page->text('map_mode')) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p id="map-mode-help" class="mt-1 text-xs text-mz-text-muted">Selecciona Google y pega su enlace corto o URL de inserción. El modo pin requiere ambas coordenadas; no hay marcador arrastrable ni búsqueda automática de dirección.</p>
            @error('content.map_mode')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="map_embed_url" class="mb-1 block text-sm font-medium">Enlace corto o URL de inserción de Google Maps</label>
            <input id="map_embed_url" type="url" name="content[map_embed_url]" value="{{ $useOldLocation ? old('content.map_embed_url') : $page->text('map_embed_url') }}" maxlength="8192" class="mz-input h-11 w-full" aria-describedby="map-embed-help">
            <p id="map-embed-help" class="mt-1 text-xs leading-relaxed text-mz-text-muted">Acepta https://maps.app.goo.gl/… y lo convierte al guardar. También puedes pegar la URL src de Compartir &gt; Insertar mapa (https://www.google.com/maps/embed?pb=…). No pegues el HTML iframe. Si no se puede resolver el enlace, se conserva la ubicación anterior.</p>
            @error('content.map_embed_url')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach (['latitude' => 90, 'longitude' => 180] as $key => $limit)
                <div>
                    <label for="{{ $key }}" class="mb-1 block text-sm font-medium">{{ \App\Models\HomePage::LOCATION_FIELDS[$key] }}</label>
                    <input id="{{ $key }}" type="number" name="content[{{ $key }}]" value="{{ $useOldLocation ? old('content.'.$key) : $page->text($key) }}" min="{{ -$limit }}" max="{{ $limit }}" step="any" class="mz-input h-11 w-full" aria-describedby="coordinates-help">
                    @error('content.'.$key)<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
        <p id="coordinates-help" class="text-xs text-mz-text-muted">Introduce ambas coordenadas con punto decimal: latitud entre −90 y 90; longitud entre −180 y 180. Cero es válido en ambas.</p>
        <x-button type="submit">Guardar ubicación</x-button>
    </div>
</form>
