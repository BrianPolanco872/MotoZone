@extends('layouts.home-editor')

@section('content')
    @php
        $sections = [
            ['key' => 'categories', 'anchor' => 'categorias', 'label' => 'Categorías', 'heading' => 'Categorías asignadas', 'help' => 'Cards promocionales independientes; no asignan categorías a productos.'],
            ['key' => 'products', 'anchor' => 'destacados', 'label' => 'Destacados', 'heading' => 'Cards destacadas', 'help' => 'Productos, recomendaciones y ofertas con precio propio.'],
            ['key' => 'new', 'anchor' => 'nuevos', 'label' => 'Recién llegados', 'heading' => 'Cards publicadas', 'help' => 'Novedades, temporada y lanzamientos para tus clientes.'],
        ];
    @endphp

    <section class="cms-panel cms-secondary flex items-center justify-between gap-3" aria-label="Resumen del inicio">
        <div class="flex min-w-0 items-center gap-3">
            <span class="cms-icon-tile"><x-icon name="store" class="size-6 text-mz-accent-primary" /></span>
            <div class="min-w-0">
                <p class="text-[10px] font-semibold tracking-wider text-mz-text-secondary uppercase">Página de Inicio</p>
                <h2 class="cms-heading truncate text-base">{{ collect(['hero', 'categories', 'products', 'new', 'location', 'benefits', 'metrics', 'advice', 'notice'])->filter(fn ($block) => $page->enabled($block))->count() }} secciones visibles</h2>
            </div>
        </div>
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="cms-badge shrink-0 text-mz-success"><x-icon name="arrow-right" class="size-3.5" /> Ver tienda<span class="sr-only">, abre otra pestaña</span></a>
    </section>

    @include('admin.home.partials.navigation')

    <section id="presentacion" class="cms-block space-y-5" data-editor-panel aria-labelledby="tab-presentacion">
        <form id="form-hero" action="{{ route('admin.home.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5" data-editor-form="Presentación">
            @csrf
            @method('PUT')
            <input type="hidden" name="block" value="hero">
            <div class="cms-panel">@include('admin.home.partials.visibility', ['block' => 'hero', 'label' => 'Banner principal de bienvenida en la web'])</div>
            <div class="cms-panel space-y-5">
                <div class="flex items-center justify-between gap-2"><h2 class="cms-label">Contenido del hero banner</h2><span class="cms-badge">{{ $page->enabled('hero') ? 'Visible' : 'Oculto' }}</span></div>
                <div data-hero-preview class="cms-hero-preview relative isolate flex min-h-[230px] items-center overflow-hidden rounded-xl bg-gradient-to-br from-orange-50 to-sky-100 sm:min-h-[280px]">
                    @if ($page->galleryUrls() !== [])
                        <div class="cms-hero-preview-background absolute inset-0" aria-hidden="true">
                            <x-carousel :images="$page->galleryUrls()" :alt="$page->text('hero_image_alt')" class="h-full w-full" image-class="h-full w-full object-cover" data-carousel-auto="5500" />
                        </div>
                    @else
                        <div class="absolute inset-0 flex items-center justify-end gap-3 pr-8 text-orange-900/30" aria-hidden="true"><x-icon name="moto" class="size-24" /><span class="text-xs font-semibold uppercase tracking-widest">Tu foto de fondo</span></div>
                    @endif
                    <div class="cms-hero-preview-overlay absolute inset-0" aria-hidden="true"></div>
                    <div class="relative z-10 max-w-lg px-5 py-7 sm:px-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-700" data-preview-text="hero_eyebrow">{{ $page->text('hero_eyebrow') }}</p>
                        <h3 class="mt-2 text-2xl font-bold leading-tight text-slate-900 sm:text-3xl" data-preview-text="hero_title">{{ $page->text('hero_title') }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-700" data-preview-text="hero_description">{{ $page->text('hero_description') }}</p>
                    </div>
                </div>
                @include('admin.partials.media-upload', [
                    'mediaId' => 'hero', 'label' => 'Imagen de fondo y galería', 'mainName' => 'hero_image',
                    'mainUrl' => $page->heroImageUrl(), 'gallery' => $page->gallery ?? [],
                    'mainUrlName' => 'hero_image_url', 'mainUrlError' => 'hero_image_url',
                    'mainSourceUrl' => $page->content['hero_image_url'] ?? '',
                    'mainUrlValue' => old('hero_image_url', $page->content['hero_image_url'] ?? ''),
                    'galleryUrlValue' => old('block') === 'hero' ? old('gallery_url', '') : '',
                    'removeMain' => 'remove_hero_image', 'removeGallery' => 'remove_gallery',
                    'help' => 'La foto principal aparece como fondo del banner; las fotos adicionales forman su carrusel.',
                ])
                @foreach (['hero_eyebrow' => 'Texto breve superior', 'hero_title' => 'Título H1 principal', 'hero_description' => 'Subtítulo o descripción breve'] as $key => $label)
                    @include('admin.home.partials.text-field', ['key' => $key, 'label' => $label])
                @endforeach
                <div>
                    <label for="hero_image_alt" class="mb-1 block text-xs font-medium">Descripción accesible de la imagen</label>
                    <input id="hero_image_alt" name="content[hero_image_alt]" value="{{ old('content.hero_image_alt', $page->text('hero_image_alt')) }}" maxlength="160" class="mz-input h-11 w-full">
                    @error('content.hero_image_alt')<p class="mt-1 text-sm text-mz-error">{{ $message }}</p>@enderror
                </div>
                <x-button type="submit">Guardar presentación</x-button>
            </div>
        </form>
    </section>

    @foreach ($sections as $section)
        @php
            $sectionCards = $cards[$section['key']] ?? collect();
            $newCard = new \App\Models\HomeCard(['section' => $section['key']]);
        @endphp
        <section id="{{ $section['anchor'] }}" class="cms-block space-y-5" data-editor-panel aria-labelledby="tab-{{ $section['anchor'] }}">
            @if ($section['key'] === 'categories')
                @include('admin.home.partials.categories', ['categoryCards' => $sectionCards])
            @else
            @include('admin.home.partials.block-settings', ['block' => $section['key'], 'label' => $section['label']])

            @if ($section['key'] === 'new')
                <form id="decoracion" action="{{ route('admin.home.update') }}" method="POST" class="cms-panel flex flex-wrap items-center justify-between gap-3" data-editor-form="Decoración automática">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="block" value="decoration">
                    <div>
                        <input type="hidden" name="content[auto_decoration]" value="0">
                        <label class="flex items-center gap-3 text-sm font-semibold"><input type="checkbox" role="switch" name="content[auto_decoration]" value="1" @checked(old('block') === 'decoration' ? old('content.auto_decoration') : ($page->content['auto_decoration'] ?? true))> Decoración automática</label>
                        <p class="mt-2 text-xs text-mz-text-muted">Detalles de temporada, sin cambiar tus textos ni crear promociones.</p>
                    </div>
                    <x-button type="submit" variant="secondary" size="sm">Guardar apariencia</x-button>
                </form>
            @endif

            <details class="cms-panel p-0" data-editor-add @if (old('section') === $section['key'] && ! old('card_id')) open @endif>
                <summary class="cms-secondary flex min-h-20 cursor-pointer items-center justify-between gap-3 rounded-xl p-4">
                    <span class="flex items-center gap-3"><span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-mz-accent-primary text-white"><x-icon name="plus" /></span><span><span class="cms-heading block text-base">{{ $section['key'] === 'categories' ? 'Asignar nueva categoría' : 'Agregar nueva card' }}</span><span class="mt-1 block text-xs text-mz-text-secondary">{{ $section['key'] === 'categories' ? 'Define el nombre, destino y foto de la card' : 'Configura producto, precio y fotos' }}</span></span></span>
                    <x-icon name="chevron-down" class="size-5 shrink-0" />
                </summary>
                <div class="p-4">@include('admin.home.partials.card-form', ['card' => $newCard, 'section' => $section['key']])</div>
            </details>

            <div class="flex items-center justify-between gap-2 px-1">
                <h2 class="cms-heading text-base">{{ $section['heading'] }} <span class="cms-badge ml-1 text-mz-success">{{ $sectionCards->where('is_active', true)->count() }} activas</span></h2>
                <span class="text-xs text-sky-300">Ordenar con ↑ ↓</span>
            </div>
            <p class="px-1 text-xs text-mz-text-muted">{{ $section['help'] }}</p>
            <div class="space-y-3">
                @forelse ($sectionCards as $card)
                    @php($isFirst = $loop->first)
                    @php($isLast = $loop->last)
                    <article class="cms-panel p-3" data-editor-card>
                        <div class="flex items-center gap-3">
                            <div class="relative size-16 shrink-0 overflow-hidden rounded-lg bg-mz-bg-primary">
                                @if ($card->coverUrl())<img src="{{ $card->coverUrl() }}" alt="" loading="lazy" referrerpolicy="no-referrer" class="size-full object-cover">@else<span class="flex size-full items-center justify-center text-mz-text-muted"><x-icon name="tag" /></span>@endif
                                <span class="absolute right-0 bottom-0 rounded-tl bg-mz-accent-primary px-1.5 text-[10px] font-bold text-white">#{{ $loop->iteration }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="cms-heading truncate text-sm">{{ $card->title }}</h3>
                                @if ($card->product)<p class="mt-1 truncate text-xs text-mz-text-muted">SKU: {{ $card->product->sku }}</p>@endif
                                @if ($card->destination())<p class="mt-1 truncate text-xs text-mz-text-muted">Destino: {{ $card->destination() }}</p>@endif
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    @if ($card->price !== null)<span class="text-sm font-semibold text-orange-300">${{ number_format((float) ($card->hasDiscount() ? $card->sale_price : $card->price), 2) }} MXN</span>@endif
                                    @if ($card->hasDiscount())<span class="text-xs text-mz-text-muted line-through">${{ number_format((float) $card->price, 2) }}</span>@endif
                                    @unless ($card->is_active)<span class="cms-badge text-mz-text-muted">Inactiva</span>@endunless
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-col gap-1 sm:flex-row">
                                <button type="button" class="cms-icon-button cms-secondary" data-editor-edit aria-label="Editar {{ $card->title }}"><x-icon name="pencil" class="size-4" /></button>
                                <form action="{{ route('admin.home.cards.destroy', $card) }}" method="POST" data-home-confirm="¿Eliminar la card «{{ $card->title }}»? También se borrarán sus fotos.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cms-icon-button cms-secondary text-mz-error" aria-label="Eliminar {{ $card->title }}" disabled><x-icon name="trash" class="size-4" /></button>
                                </form>
                            </div>
                        </div>
                        <div class="mt-3 flex gap-2 border-t border-mz-border pt-2">
                            @foreach (['up' => 'Subir', 'down' => 'Bajar'] as $direction => $label)
                                <form action="{{ route('admin.home.cards.move', $card) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="direction" value="{{ $direction }}">
                                    <x-button type="submit" variant="secondary" size="sm" :disabled="($direction === 'up' && $isFirst) || ($direction === 'down' && $isLast)" aria-label="{{ $label }} {{ $card->title }}"><x-icon name="{{ $direction === 'up' ? 'arrow-up' : 'arrow-down' }}" class="size-3.5" /> {{ $label }}</x-button>
                                </form>
                            @endforeach
                        </div>
                        <details class="mt-3" data-editor-card-details @if (old('card_id') == $card->id) open @endif>
                            <summary class="cursor-pointer text-xs font-semibold text-orange-300">Editar card</summary>
                            <div class="mt-4">@include('admin.home.partials.card-form', ['card' => $card, 'section' => $section['key']])</div>
                        </details>
                    </article>
                @empty
                    <p class="cms-panel border border-dashed border-mz-border py-8 text-center text-sm text-mz-text-muted">Todavía no hay cards en este bloque. Agrega la primera.</p>
                @endforelse
            </div>
            @endif
        </section>
    @endforeach

    <section id="contacto" class="cms-block space-y-5" data-editor-panel aria-labelledby="tab-contacto">
        @include('admin.home.partials.contact')
    </section>

    <section id="ubicacion" class="cms-block space-y-5" data-editor-panel aria-labelledby="tab-ubicacion">
        @include('admin.home.partials.location')
    </section>

    <section id="informacion" class="cms-block space-y-5" data-editor-panel aria-labelledby="tab-informacion">
        @include('admin.home.partials.information')
    </section>

    <aside class="cms-panel flex items-start gap-3 bg-mz-bg-primary">
        <x-icon name="bolt" class="mt-0.5 size-5 shrink-0 text-orange-300" />
        <div><h2 class="text-xs font-semibold">Consejo de conversión MotoZone</h2><p class="mt-1 text-xs leading-relaxed text-mz-text-secondary">Mantén claros los bloques, usa fotos propias y revisa la vista previa después de guardar.</p></div>
    </aside>

    <details class="cms-panel">
        <summary class="cursor-pointer text-sm font-medium">Restablecer textos y apariencia</summary>
        <p class="mt-3 text-xs text-mz-text-secondary">Vuelve a los textos y la apariencia iniciales. Conserva las fotos, las cards, el contacto y la ubicación.</p>
        <form action="{{ route('admin.home.reset') }}" method="POST" class="mt-3" data-home-confirm="¿Restablecer los textos y la apariencia? Se conservarán las fotos y las cards.">
            @csrf
            <input type="hidden" name="confirm" value="1">
            <x-button type="submit" variant="secondary" disabled>Restablecer textos y apariencia</x-button>
        </form>
    </details>

    <dialog class="cms-description-dialog w-full max-w-lg rounded-t-2xl border-0 p-5 text-mz-text-primary sm:rounded-2xl" data-editor-description aria-labelledby="description-heading">
        <div class="mb-4 flex items-center justify-between gap-3"><h2 id="description-heading" class="cms-heading">Editar texto de cabecera</h2><button type="button" class="cms-icon-button" data-description-close aria-label="Cerrar descripción"><x-icon name="x" /></button></div>
        <label for="description-draft" class="mb-2 block text-xs">Descripción corta secundaria</label>
        <textarea id="description-draft" rows="4" maxlength="1000" class="mz-input w-full" data-description-draft></textarea>
        <button type="button" class="mz-button mz-button-primary mt-4 min-h-11 w-full" data-description-apply>Aplicar descripción</button>
        <p class="mt-2 text-xs text-mz-text-muted">Después guarda el bloque para publicarla.</p>
    </dialog>
    <noscript><p role="status" class="cms-panel text-sm text-mz-warning">Activa JavaScript para usar pestañas, vista previa de fotos y confirmaciones. Los formularios siguen disponibles sin JavaScript.</p></noscript>
@endsection
