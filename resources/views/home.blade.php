@extends('layouts.site')

@section('title', 'Refacciones y accesorios para tu moto')
@section('description', $homePage->text('hero_description'))
@section('home-style', 'storefront')

@php
    $homeWhatsappUrl = $homePage->whatsappUrl();
    $homePhoneUrl = $homePage->phoneUrl();
    $homeEmailUrl = $homePage->emailUrl();
    $homeGmailUrl = $homePage->gmailComposeUrl();
    $homeMapEmbedUrl = $homePage->mapEmbedUrl();
    $homeDirectionsUrl = $homePage->directionsUrl();
    $homeGalleryUrls = $homePage->galleryUrls();
    $hasHomeHeroVisual = $homeGalleryUrls !== [] || ($isEditorPreview ?? false);
    $escapedHomeHeroTitle = e($homePage->text('hero_title'));
    $homeHeroTitle = preg_replace_callback('/accesorios/iu', fn ($match) => '<span class="home-hero-accent">'.$match[0].'</span>', $escapedHomeHeroTitle) ?? $escapedHomeHeroTitle;
    $showHomeLocation = $homePage->enabled('location') && (
        $homePage->text('address') !== '' || $homePage->text('location_reference') !== '' || $homeMapEmbedUrl || $homeDirectionsUrl
    );
    $hasHomeLocationDetails = $homePage->text('address') !== '' || $homePage->text('location_reference') !== ''
        || $homePage->text('hours') !== '' || $homePhoneUrl || $homeEmailUrl;
    $homeBenefits = $homePage->enabled('benefits')
        ? collect(range(1, 4))->filter(fn ($position) => $homePage->text('benefit_'.$position.'_title') !== '' || $homePage->text('benefit_'.$position.'_description') !== '')
        : collect();
    $homeMetrics = $homePage->enabled('metrics')
        ? collect(range(1, 3))->filter(fn ($position) => $homePage->text('metric_'.$position.'_value') !== '' && $homePage->text('metric_'.$position.'_label') !== '')
        : collect();
    $benefitIcons = ['store', 'check', 'shield', 'chat'];
    $categorySpans = [5, 3, 4, 4, 8];
    $metricAccents = ['#ea580c', '#15803d', '#0284c7'];
    $homeActiveCategories = $categoryCards->where('is_active', true)->count();
@endphp

@section('content')
    @if ($isEditorPreview ?? false)
        <aside class="border-b border-orange-200 bg-orange-50 px-4 py-4 text-sm text-slate-800" aria-label="Vista de ejemplo">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3">
                <p><strong>Vista de ejemplo.</strong> Los cuatro bloques se muestran para revisar la distribución. Las cards marcadas como ejemplo no están publicadas. Nada se guarda ni se publica aquí.</p>
                <a href="{{ route('admin.home.edit') }}" class="shrink-0 font-semibold text-orange-800 underline">Volver a Editar inicio</a>
            </div>
        </aside>
    @endif

    @if ($showHomeLocation)
        <aside class="home-status-bar" aria-label="Información del mostrador">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-5 gap-y-2 px-4 py-2.5 sm:px-6 lg:px-8">
                <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="home-status-label"><x-icon name="store" class="size-4" /> {{ $homePage->text('store_name') ?: 'Mostrador central' }}</span>
                    @if ($homePage->text('address') !== '')
                        <span class="text-stone-600">{{ $homePage->text('address') }}</span>
                    @endif
                    @if ($homePage->text('hours') !== '')
                        <span class="hidden text-stone-600 md:inline">{{ $homePage->text('hours') }}</span>
                    @endif
                </div>
                <a href="#ubicacion" class="home-status-link">Ver mapa y rutas <x-icon name="arrow-right" class="size-3.5" /></a>
            </div>
        </aside>
    @endif

    <div class="home-season" @if ($homePage->decorationMonth()) data-home-month="{{ $homePage->decorationMonth() }}" @endif>
    <div class="home-season-detail" aria-hidden="true"></div>

    @if ($homePage->enabled('hero'))
        <section id="heroBannerSection" class="home-hero relative isolate flex min-h-[550px] items-center overflow-hidden" data-home-hero>
            @if ($homeGalleryUrls !== [])
                <div class="home-hero-parallax" data-home-parallax aria-hidden="true">
                    <x-carousel :images="$homeGalleryUrls" :alt="$homePage->text('hero_image_alt')" class="home-hero-background" image-class="home-hero-background-image" data-carousel-auto="5500" />
                </div>
            @endif
            <div class="home-hero-overlay" aria-hidden="true"></div>
            <div class="home-hero-glow home-hero-glow-one" aria-hidden="true"></div>
            <div class="home-hero-glow home-hero-glow-two" aria-hidden="true"></div>

            <div @class(['relative z-10 mx-auto w-full max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20', 'lg:grid lg:grid-cols-2' => $hasHomeHeroVisual])>
                <div class="max-w-3xl">
                    @if ($homePage->text('hero_eyebrow') !== '')
                        <p class="home-hero-eyebrow inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/90 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-[#c2410c] shadow-lg shadow-orange-950/10 backdrop-blur">
                            <x-icon name="moto" class="size-4" /> <span>{{ $homePage->text('hero_eyebrow') }}</span>
                        </p>
                    @endif
                    <h1 class="home-hero-title mt-5 whitespace-pre-line font-display text-4xl font-bold uppercase leading-[1.02] tracking-tight text-[#0f172a] sm:text-5xl lg:text-6xl">{!! $homeHeroTitle !!}</h1>
                    @if (($isEditorPreview ?? false) && $homePage->text('hero_description') !== '')
                        <p class="home-hero-description mt-6 max-w-2xl rounded-xl border border-white/80 bg-white/80 p-4 text-base leading-relaxed text-slate-700 shadow-lg shadow-orange-950/10 backdrop-blur-sm sm:p-5">{{ $homePage->text('hero_description') }}</p>
                    @endif
                    <div class="mt-7 flex flex-wrap items-center gap-3">
                        @if ($homeWhatsappUrl)
                            <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="home-button home-button-whatsapp"><x-icon name="chat" class="size-5" /> Cotizar por WhatsApp</a>
                        @endif
                        @if ($homeGmailUrl)
                            <a href="{{ $homeGmailUrl }}" target="_blank" rel="noopener noreferrer" class="home-button home-button-light" aria-label="Enviar por Gmail"><x-icon name="mail" class="size-5" /> Gmail</a>
                        @endif
                        @if ($homePhoneUrl && ! $homeWhatsappUrl && ! $homeGmailUrl)
                            <a href="{{ $homePhoneUrl }}" class="home-button home-button-primary"><x-icon name="phone" class="size-5" /> Llamar al mostrador</a>
                        @endif
                        @if ($showHomeLocation)
                            <a href="#ubicacion" class="home-button home-button-light"><x-icon name="map-pin" class="size-5 text-[#ea580c]" /> Ver mostrador central</a>
                        @endif
                    </div>
                    @if ($homeMetrics->isNotEmpty())
                        <dl class="home-metric-grid mt-8 grid grid-cols-3 gap-2 sm:gap-3">
                            @foreach ($homeMetrics as $position)
                                <div class="home-metric-card flex min-w-0 flex-col rounded-xl border border-white/80 bg-white/90 p-3 shadow-lg shadow-orange-950/10 backdrop-blur sm:p-4">
                                    <dd class="font-display text-lg font-bold leading-tight sm:text-2xl" style="color: {{ $metricAccents[$loop->index % count($metricAccents)] }}">{{ $homePage->text('metric_'.$position.'_value') }}</dd>
                                    <dt class="mt-1 text-[9px] font-bold uppercase leading-tight tracking-wide text-slate-600 sm:text-[11px]">{{ $homePage->text('metric_'.$position.'_label') }}</dt>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </div>
                @if (($isEditorPreview ?? false) && $homeGalleryUrls === [])
                    <div class="home-hero-preview-visual hidden lg:flex"><x-icon name="moto" class="size-36" /><span>Agrega fotos del mostrador desde Editar inicio</span></div>
                @endif
            </div>
        </section>
    @else
        <h1 class="sr-only">Refacciones y accesorios para tu moto</h1>
    @endif

    @if ($homePage->enabled('notice') && $homePage->text('store_notice') !== '')
        <aside class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8" aria-label="Información del mostrador">
            <p class="home-notice flex items-start gap-3 rounded-2xl border border-[#e2d9cc] bg-white px-5 py-4 text-sm leading-relaxed text-stone-700 shadow-sm"><x-icon name="store" class="mt-0.5 size-5 shrink-0 text-[#ea580c]" /><span class="whitespace-pre-line">{{ $homePage->text('store_notice') }}</span></p>
        </aside>
    @endif

    @if ($homeBenefits->isNotEmpty())
        <section class="home-benefits-section border-y border-[#e6ded1]" aria-label="Información de la tienda">
            <div class="mx-auto grid max-w-7xl gap-4 px-4 py-8 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ($homeBenefits as $position)
                    <article class="home-benefit-card flex items-start gap-4 p-5">
                        <span class="home-benefit-icon flex size-12 shrink-0 items-center justify-center rounded-xl" data-home-benefit="{{ $position }}"><x-icon :name="$benefitIcons[$position - 1] ?? 'check'" class="size-6" /></span>
                        <div class="min-w-0">
                            @if ($homePage->text('benefit_'.$position.'_title') !== '')
                                <h2 class="font-display text-base font-bold leading-tight text-stone-900">{{ $homePage->text('benefit_'.$position.'_title') }}</h2>
                            @endif
                            @if ($homePage->text('benefit_'.$position.'_description') !== '')
                                <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-stone-600">{{ $homePage->text('benefit_'.$position.'_description') }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if ($categoryCards->isNotEmpty())
        <section id="categorias" class="home-categories-section scroll-mt-24 px-4 py-12 sm:px-6 lg:px-8 lg:py-20" aria-labelledby="home-categories-title">
            <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="home-section-kicker font-display flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.16em]"><span class="size-2.5 rounded-full bg-[#ea580c]"></span> Catálogo oficial</p>
                    <h2 id="home-categories-title" class="mt-2 font-display text-3xl font-bold uppercase tracking-tight text-stone-900 sm:text-4xl">{{ $homePage->text('categories_title') }}</h2>
                    @if ($homePage->text('categories_description') !== '')
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-stone-600">{{ $homePage->text('categories_description') }}</p>
                    @endif
                </div>
                <span class="home-category-count self-start rounded-xl border border-[#e2d9cc] bg-white px-4 py-2 text-xs font-semibold text-stone-700 shadow-sm sm:self-auto">{{ $homeActiveCategories }} {{ $homeActiveCategories === 1 ? 'categoría activa' : 'categorías activas' }}</span>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-stone-500">Filtrar vista:</span>
                @foreach ($categoryCards as $card)
                    <button type="button" class="home-category-pill rounded-full border border-[#e2d9cc] bg-white px-3.5 py-2 text-xs font-semibold text-stone-800 shadow-sm transition hover:border-[#ea580c]/50 hover:bg-[#fff3e5]" data-home-category-pill="{{ $loop->index }}" aria-pressed="false">{{ $card->title }}</button>
                @endforeach
                <button type="button" class="home-category-reset hidden items-center gap-1 rounded-lg border border-[#ea580c]/40 bg-white px-3 py-2 text-xs font-bold text-[#c2410c] shadow-sm hover:bg-[#fff3e5]" data-home-category-reset><x-icon name="x" class="size-3.5" /> Mostrar todas</button>
            </div>

            <div class="home-card-grid mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-12">
                @foreach ($categoryCards as $card)
                    @php($destination = $card->destination())
                    @php($span = $categorySpans[$loop->index % count($categorySpans)])
                    <article data-home-category-card="{{ $loop->index }}" data-home-category-layout="{{ $span }}" @class([
                        'home-category-card group flex flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm',
                        'lg:col-span-5' => $span === 5,
                        'lg:col-span-3' => $span === 3,
                        'lg:col-span-4' => $span === 4,
                        'lg:col-span-8 lg:flex-row lg:items-stretch' => $span === 8,
                    ])>
                        <div class="home-category-card-content flex min-w-0 flex-1 flex-col p-5">
                            <div class="flex items-center justify-between gap-3">
                                <span class="home-category-icon flex size-10 items-center justify-center rounded-lg bg-[#ea580c]/10 text-[#ea580c]"><x-icon :name="$card->icon ?: 'tag'" class="size-5" /></span>
                                @if ($card->linkedProducts->isNotEmpty())
                                    <span class="home-category-product-count rounded-full border border-[#e2d9cc] bg-[#fff8ed] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide">{{ $card->linkedProducts->count() }} productos</span>
                                @endif
                            </div>
                            @if (! $card->exists)
                                <p class="mt-3 text-[11px] font-semibold uppercase tracking-wide text-orange-800">Ejemplo</p>
                            @endif
                            <h3 class="font-display mt-4 text-lg font-bold uppercase tracking-tight text-stone-900 transition-colors group-hover:text-[#c2410c]">{{ $card->title }}</h3>
                            @if ($card->description)
                                <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ $card->description }}</p>
                            @endif
                            @if ($destination)
                                <a href="{{ $destination }}" class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-bold text-[#c2410c] transition-all hover:gap-2">{{ $card->link_label ?: 'Ver categoría' }} <x-icon name="arrow-right" class="size-4" /></a>
                            @endif
                        </div>
                        @if ($card->galleryUrls() !== [])
                            <x-carousel :images="$card->galleryUrls()" :alt="$card->title" class="home-category-photo aspect-[16/9] bg-[#fff3e5]" />
                        @else
                            <span class="home-category-art flex h-28 items-center justify-center bg-[#fff3e5] text-[#ea580c]"><x-icon :name="$card->icon ?: 'tag'" class="size-10" /></span>
                        @endif
                    </article>
                @endforeach
            </div>

            @foreach ($categoryCards as $card)
                @if ($card->linkedProducts->isNotEmpty())
                    <details id="{{ $card->familyAnchor() }}" class="home-family-list mt-6 scroll-mt-24 rounded-2xl border border-[#e2d9cc] bg-white p-4 shadow-sm sm:p-6" data-home-family>
                        <summary class="cursor-pointer font-display text-lg font-bold uppercase tracking-tight text-stone-900">{{ $card->title }} <span class="ml-2 text-sm font-normal normal-case tracking-normal text-stone-500">{{ $card->linkedProducts->count() }} productos</span></summary>
                        <div class="home-card-grid mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($card->linkedProducts as $familyProduct)
                                <x-product-card :product="$familyProduct" />
                            @endforeach
                        </div>
                    </details>
                @endif
            @endforeach
            </div>
        </section>
    @endif

    @if ($productCards->isNotEmpty())
        <section id="destacados" class="home-featured scroll-mt-24 border-y border-[#f3d2ad]" aria-labelledby="home-products-title">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0">
                    <p class="home-section-kicker font-display flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.16em]"><span class="size-2.5 rounded-full bg-[#ea580c]"></span> Nuestra selección</p>
                        <h2 id="home-products-title" class="mt-2 font-display text-3xl font-bold uppercase tracking-tight text-stone-900 sm:text-4xl">{{ $homePage->text('products_title') }}</h2>
                        @if ($homePage->text('products_description') !== '')
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-stone-600">{{ $homePage->text('products_description') }}</p>
                        @endif
                    </div>
                </div>
                <div class="home-card-grid mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($productCards as $card)
                        <x-promo-card :card="$card" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($newCards->isNotEmpty())
        <section id="nuevos" class="home-new-section scroll-mt-24 px-4 py-12 sm:px-6 lg:px-8 lg:py-20" aria-labelledby="home-new-title">
            <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="home-section-kicker font-display flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.16em]"><span class="size-2.5 rounded-full bg-[#ea580c]"></span> Novedades de mostrador</p>
                    <h2 id="home-new-title" class="mt-2 font-display text-3xl font-bold uppercase tracking-tight text-stone-900 sm:text-4xl">{{ $homePage->text('new_title') }}</h2>
                    @if ($homePage->text('new_description') !== '')
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-stone-600">{{ $homePage->text('new_description') }}</p>
                    @endif
                    @if ($isEditorPreview ?? false)
                        <p class="mt-2 text-xs font-medium text-orange-800">Cards de ejemplo para revisar la distribución; no se publican.</p>
                    @endif
                </div>
            </div>
            <div class="home-card-grid mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($newCards as $card)
                    <x-promo-card :card="$card" />
                @endforeach
            </div>
            </div>
        </section>
    @endif

    @if ($showHomeLocation)
        <section id="ubicacion" class="home-location-section scroll-mt-24 border-t border-[#f3d2ad]" aria-labelledby="home-location-title">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20">
                <div class="min-w-0">
                    <p class="home-section-kicker flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.16em]"><span class="size-2.5 rounded-full bg-[#ea580c]"></span> Encuéntranos</p>
                    <h2 id="home-location-title" class="mt-2 font-display text-3xl font-bold uppercase tracking-tight text-stone-900 sm:text-4xl">{{ $homePage->text('store_name') ?: 'Visita nuestro mostrador' }}</h2>
                    @if ($homePage->text('store_description') !== '')
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-stone-600">{{ $homePage->text('store_description') }}</p>
                    @endif
                </div>
                <div class="mt-8 grid gap-6 lg:grid-cols-12 lg:items-stretch">
                    <div class="home-location-info flex flex-col justify-between gap-6 rounded-2xl border bg-white p-6 lg:col-span-5">
                        @if ($hasHomeLocationDetails)
                            <dl class="grid gap-5 text-sm">
                                @if ($homePage->text('address') !== '')
                                    <div class="flex items-start gap-3">
                                        <x-icon name="map-pin" class="mt-0.5 size-5 shrink-0 text-[#ea580c]" />
                                        <div class="min-w-0"><dt class="font-semibold text-stone-900">Dirección</dt><dd class="mt-1 whitespace-pre-line leading-relaxed text-stone-600">{{ $homePage->text('address') }}</dd></div>
                                    </div>
                                @endif
                                @if ($homePage->text('location_reference') !== '')
                                    <div class="flex items-start gap-3">
                                        <x-icon name="arrow-right" class="mt-0.5 size-5 shrink-0 text-[#0284c7]" />
                                        <div class="min-w-0"><dt class="font-semibold text-stone-900">Referencia</dt><dd class="mt-1 whitespace-pre-line leading-relaxed text-stone-600">{{ $homePage->text('location_reference') }}</dd></div>
                                    </div>
                                @endif
                                @if ($homePage->text('hours') !== '')
                                    <div class="flex items-start gap-3">
                                        <x-icon name="clock" class="mt-0.5 size-5 shrink-0 text-[#15803d]" />
                                        <div class="min-w-0"><dt class="font-semibold text-stone-900">Horarios</dt><dd class="mt-1 whitespace-pre-line leading-relaxed text-stone-600">{{ $homePage->text('hours') }}</dd></div>
                                    </div>
                                @endif
                                @if ($homePhoneUrl)
                                    <div class="flex items-start gap-3">
                                        <x-icon name="phone" class="mt-0.5 size-5 shrink-0 text-[#0284c7]" />
                                        <div class="min-w-0"><dt class="font-semibold text-stone-900">Teléfono</dt><dd class="mt-1"><a href="{{ $homePhoneUrl }}" class="leading-relaxed text-stone-600 underline underline-offset-4">{{ $homePage->text('phone') }}</a></dd></div>
                                    </div>
                                @endif
                                @if ($homeEmailUrl)
                                    <div class="flex items-start gap-3">
                                        <x-icon name="mail" class="mt-0.5 size-5 shrink-0 text-[#ea580c]" />
                                        <div class="min-w-0"><dt class="font-semibold text-stone-900">Correo</dt><dd class="mt-1"><a href="{{ $homeEmailUrl }}" class="break-words leading-relaxed text-stone-600 underline underline-offset-4">{{ $homePage->text('email') }}</a></dd></div>
                                    </div>
                                @endif
                            </dl>
                        @endif
                        @if ($homeDirectionsUrl || $homeWhatsappUrl)
                            <div class="flex flex-col gap-3 sm:flex-row">
                                @if ($homeDirectionsUrl)
                                    <a href="{{ $homeDirectionsUrl }}" target="_blank" rel="noopener" class="home-button home-button-primary flex-1"><x-icon name="map-pin" class="size-5" /> Cómo llegar</a>
                                @endif
                                @if ($homeWhatsappUrl)
                                    <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="home-button home-button-whatsapp flex-1"><x-icon name="chat" class="size-5" /> WhatsApp</a>
                                @endif
                            </div>
                        @endif
                    </div>
                    @if ($homeMapEmbedUrl)
                        <div class="home-location-map-shell flex min-w-0 flex-col overflow-hidden rounded-2xl border bg-white lg:col-span-7">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#e6ded1] bg-[#f0ebe1] px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-stone-600">
                                <span class="flex items-center gap-2"><x-icon name="map-pin" class="size-4 text-[#ea580c]" /> Mapa del mostrador</span>
                                @if ($homeDirectionsUrl)
                                    <a href="{{ $homeDirectionsUrl }}" target="_blank" rel="noopener" class="text-[#0284c7] hover:underline">Abrir ruta</a>
                                @endif
                            </div>
                            <iframe src="{{ $homeMapEmbedUrl }}" title="Mapa de ubicación de {{ $homePage->text('store_name') ?: 'MotoZone' }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="home-location-map w-full flex-1 border-0" allowfullscreen></iframe>
                            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2 text-xs text-stone-600">
                                <a href="{{ $homeDirectionsUrl ?: $homeMapEmbedUrl }}" target="_blank" rel="noopener" class="underline underline-offset-2">Abrir mapa en otra pestaña</a>
                                @if ($homePage->text('map_mode') === 'pin')
                                    <span>&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener" class="underline underline-offset-2">Colaboradores de OpenStreetMap</a></span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if ($homePage->enabled('advice') && ($homePage->text('advice_title') !== '' || $homePage->text('advice_description') !== ''))
        <section class="home-advice-section px-4 py-12 sm:px-6 lg:px-8 lg:py-16" aria-label="Asesoría">
            <div class="home-advice mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-8 rounded-2xl border border-[#f3d2ad] p-6 shadow-xl shadow-orange-950/5 sm:p-10">
                <div class="min-w-0 max-w-2xl">
                    <span class="flex size-11 items-center justify-center rounded-xl bg-[#ea580c]/10 text-[#ea580c]"><x-icon name="wrench" class="size-6" /></span>
                    @if ($homePage->text('advice_title') !== '')
                        <h2 class="mt-4 font-display text-2xl font-bold uppercase tracking-tight text-stone-900 sm:text-3xl">{{ $homePage->text('advice_title') }}</h2>
                    @endif
                    @if ($homePage->text('advice_description') !== '')
                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-stone-600">{{ $homePage->text('advice_description') }}</p>
                    @endif
                </div>
                @if ($homeWhatsappUrl || $homeEmailUrl || $homePhoneUrl)
                    <div class="flex flex-wrap gap-3">
                        @if ($homeWhatsappUrl)
                            <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener" class="home-button home-button-primary"><x-icon name="chat" class="size-5" /> Escríbenos por WhatsApp</a>
                        @endif
                        @if ($homeEmailUrl)
                            <a href="{{ $homeEmailUrl }}" class="home-button home-button-light"><x-icon name="mail" class="size-5" /> Escríbenos por correo</a>
                        @endif
                        @if ($homePhoneUrl && ! $homeWhatsappUrl && ! $homeEmailUrl)
                            <a href="{{ $homePhoneUrl }}" class="home-button home-button-primary"><x-icon name="phone" class="size-5" /> Llámanos</a>
                        @endif
                    </div>
                @endif
            </div>
        </section>
    @endif

    </div>
@endsection
