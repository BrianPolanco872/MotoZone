<input type="hidden" name="content[show_{{ $block }}]" value="0">
<label class="flex cursor-pointer items-center justify-between gap-4">
    <span class="flex min-w-0 items-center gap-3">
        <span class="cms-icon-tile"><x-icon name="store" class="size-5 text-orange-300" /></span>
        <span><span class="block text-sm font-semibold">Mostrar bloque en tienda</span><span class="mt-1 block text-xs text-mz-text-secondary">{{ $label }}</span></span>
    </span>
    <input type="checkbox" role="switch" class="cms-switch shrink-0" name="content[show_{{ $block }}]" value="1" @checked(old('block') === $block ? old('content.show_'.$block) : $page->enabled($block))>
</label>
@error('content.show_'.$block)<p class="mt-2 text-sm text-mz-error">{{ $message }}</p>@enderror
