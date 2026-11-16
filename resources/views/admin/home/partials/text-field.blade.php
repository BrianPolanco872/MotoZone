<div>
    <label for="{{ $key }}" class="mb-1 block text-sm font-medium">{{ $label }}</label>
    @if (str_ends_with($key, '_description'))
        <textarea id="{{ $key }}" name="content[{{ $key }}]" rows="3" maxlength="1000" class="mz-input w-full" data-preview-input="{{ $key }}">{{ old('content.'.$key, $page->text($key)) }}</textarea>
    @else
        <input id="{{ $key }}" name="content[{{ $key }}]" value="{{ old('content.'.$key, $page->text($key)) }}" maxlength="{{ $key === 'hero_button_url' ? 2048 : 160 }}" class="mz-input h-11 w-full" data-preview-input="{{ $key }}" @required(str_ends_with($key, '_title') || $key === 'hero_button')>
    @endif
    @error('content.'.$key)<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
</div>
