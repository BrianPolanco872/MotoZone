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
    $homeHeroTitle = preg_replace_callback('/accesorios/iu', fn ($match) => '<span class="home-hero-accent text-orange-500">'.$match[0].'</span>', $escapedHomeHeroTitle) ?? $escapedHomeHeroTitle;
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
    $metricAccents = ['#ea580c', '#16a34a', '#0284c7'];
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
        <aside class="home-status-bar border-b border-slate-800 bg-slate-900 text-slate-300" aria-label="Información del mostrador">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-5 gap-y-2 px-4 py-2.5 sm:px-6 lg:px-8">
                <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-orange-400">
                        <x-icon name="store" class="size-4 text-orange-500" />
                        {{ $homePage->text('store_name') ?: 'Mostrador central' }}
                    </span>
                    @if ($homePage->text('address') !== '')
                        <span class="text-xs text-slate-400">{{ $homePage->text('address') }}</span>
                    @endif
                    @if ($homePage->text('hours') !== '')
                        <span class="hidden text-xs text-slate-400 md:inline">· {{ $homePage->text('hours') }}</span>
                    @endif
                </div>
                <a href="#ubicacion" class="inline-flex items-center gap-1 text-xs font-semibold text-orange-400 transition hover:text-orange-300">
                    Ver mapa y rutas <x-icon name="arrow-right" class="size-3.5" />
                </a>
            </div>
        </aside>
    @endif

    <div class="home-season" @if ($homePage->decorationMonth()) data-home-month="{{ $homePage->decorationMonth() }}" @endif>
    <div class="home-season-detail" aria-hidden="true"></div>

    {{-- HERO SECTION FULL-BLEED CON PARALLAX Y DEGRADADO OSCURO --}}
    @if ($homePage->enabled('hero'))
        <section id="heroBannerSection" class="home-hero relative isolate flex min-h-[75vh] w-full items-center overflow-hidden bg-slate-950" data-home-hero>
            {{-- Fondo Parallax Full-Bleed detrás del texto --}}
            <div class="home-hero-bg-container pointer-events-none absolute inset-0 z-0 h-full w-full overflow-hidden" data-home-parallax>
                @if ($homeGalleryUrls !== [])
                    <x-carousel :images="$homeGalleryUrls" :alt="$homePage->text('hero_image_alt')" class="home-hero-carousel size-full pointer-events-auto" image-class="home-hero-background-image size-full object-cover object-center" data-carousel-auto="5500" />
                @else
                    <div class="size-full bg-slate-950">
                        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-slate-950 to-black"></div>
                        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)] opacity-35"></div>
                    </div>
                @endif
            </div>

            {{-- Capa de degradado oscuro requerida (bg-gradient-to-r from-slate-950/90 via-slate-900/65 to-slate-900/20) --}}
            <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-r from-slate-950/90 via-slate-900/65 to-slate-900/20" aria-hidden="true"></div>
            {{-- Gradiente vertical de apoyo para suavizado de bordes --}}
            <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-t from-slate-950/85 via-transparent to-slate-950/50" aria-hidden="true"></div>

            {{-- Contenido del Hero (Texto blanco puro con acentos naranja) --}}
            <div class="relative z-10 mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <div class="max-w-2xl lg:max-w-3xl">
                    @if ($homePage->text('hero_eyebrow') !== '')
                        <p class="inline-flex items-center gap-2 rounded-full border border-orange-500/40 bg-slate-900/80 px-4 py-1.5 text-xs font-bold uppercase tracking-[0.16em] text-orange-400 shadow-xl shadow-black/40 backdrop-blur-md">
                            <x-icon name="moto" class="size-4 text-orange-500" />
                            <span>{{ $homePage->text('hero_eyebrow') }}</span>
                        </p>
                    @endif

                    <h1 class="home-hero-title mt-5 font-display text-4xl font-extrabold uppercase leading-[1.05] tracking-tight text-white drop-shadow-md sm:text-5xl lg:text-6xl">
                        {!! $homeHeroTitle !!}
                    </h1>

                    @if ($homePage->text('hero_description') !== '')
                        <p class="mt-5 max-w-2xl text-base leading-relaxed text-slate-200 drop-shadow sm:text-lg">
                            {{ $homePage->text('hero_description') }}
                        </p>
                    @endif

                    <div class="mt-8 flex flex-wrap items-center gap-3.5">
                        <a href="#categorias" class="inline-flex min-h-12 items-center justify-center gap-2.5 rounded-xl bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wider text-white shadow-lg shadow-orange-600/30 transition-all duration-200 hover:-translate-y-0.5 hover:bg-orange-500 hover:shadow-orange-600/40 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">
                            Explorar catálogo
                            <x-icon name="arrow-right" class="size-4" />
                        </a>

                        @if ($homeWhatsappUrl)
                            <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold uppercase tracking-wider text-white shadow-md shadow-emerald-950/20 transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-500">
                                <x-icon name="chat" class="size-5" /> Cotizar por WhatsApp
                            </a>
                        @endif

                        @if ($homeGmailUrl)
                            <a href="{{ $homeGmailUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-bold uppercase tracking-wider text-white backdrop-blur-md transition-all duration-200 hover:-translate-y-0.5 hover:bg-white/20" aria-label="Enviar por Gmail">
                                <x-icon name="mail" class="size-5" /> Gmail
                            </a>
                        @endif

                        @if ($homePhoneUrl && ! $homeWhatsappUrl && ! $homeGmailUrl)
                            <a href="{{ $homePhoneUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-bold uppercase tracking-wider text-white backdrop-blur-md transition-all duration-200 hover:-translate-y-0.5 hover:bg-white/20">
                                <x-icon name="phone" class="size-5 text-orange-400" /> Llamar al mostrador
                            </a>
                        @endif

                        @if ($showHomeLocation)
                            <a href="#ubicacion" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-bold uppercase tracking-wider text-white backdrop-blur-md transition-all duration-200 hover:-translate-y-0.5 hover:bg-white/20">
                                <x-icon name="map-pin" class="size-5 text-orange-400" /> Ver mostrador
                            </a>
                        @endif
                    </div>

                    @if ($homeMetrics->isNotEmpty())
                        <dl class="home-metric-grid mt-10 grid max-w-xl grid-cols-3 gap-3">
                            @foreach ($homeMetrics as $position)
                                <div class="flex min-w-0 flex-col rounded-xl border border-white/10 bg-slate-900/60 p-3.5 shadow-xl backdrop-blur-md sm:p-4">
                                    <dd class="font-display text-xl font-bold leading-tight text-white sm:text-2xl" style="color: {{ $metricAccents[$loop->index % count($metricAccents)] }}">
                                        {{ $homePage->text('metric_'.$position.'_value') }}
                                    </dd>
                                    <dt class="mt-1 text-[10px] font-bold uppercase leading-tight tracking-wider text-slate-300 sm:text-xs">
                                        {{ $homePage->text('metric_'.$position.'_label') }}
                                    </dt>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </div>
            </div>
        </section>
    @else
        <h1 class="sr-only">Refacciones y accesorios para tu moto</h1>
    @endif

    {{-- AVISO DEL MOSTRADOR --}}
    @if ($homePage->enabled('notice') && $homePage->text('store_notice') !== '')
        <aside class="bg-white px-4 pt-8 sm:px-6 lg:px-8" aria-label="Información del mostrador">
            <div class="mx-auto max-w-7xl">
                <p class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50 px-5 py-4 text-sm leading-relaxed text-slate-700 shadow-sm">
                    <x-icon name="store" class="mt-0.5 size-5 shrink-0 text-orange-600" />
                    <span class="whitespace-pre-line">{{ $homePage->text('store_notice') }}</span>
                </p>
            </div>
        </aside>
    @endif

    {{-- BENEFICIOS (Gris sutil slate-50) --}}
    @if ($homeBenefits->isNotEmpty())
        <section class="home-benefits-section border-y border-slate-200/80 bg-slate-50" aria-label="Información de la tienda">
            <div class="mx-auto grid max-w-7xl gap-4 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ($homeBenefits as $position)
                    <article class="flex items-start gap-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:shadow-md">
                        <span class="home-benefit-icon flex size-12 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-600" data-home-benefit="{{ $position }}">
                            <x-icon :name="$benefitIcons[$position - 1] ?? 'check'" class="size-6" />
                        </span>
                        <div class="min-w-0">
                            @if ($homePage->text('benefit_'.$position.'_title') !== '')
                                <h2 class="font-display text-base font-bold leading-tight text-slate-900">{{ $homePage->text('benefit_'.$position.'_title') }}</h2>
                            @endif
                            @if ($homePage->text('benefit_'.$position.'_description') !== '')
                                <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $homePage->text('benefit_'.$position.'_description') }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- CATEGORÍAS (Blanco limpio bg-white) --}}
    @if ($categoryCards->isNotEmpty())
        <section id="categorias" class="home-categories-section scroll-mt-24 bg-white px-4 py-12 sm:px-6 lg:px-8 lg:py-20" aria-labelledby="home-categories-title">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-display flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-orange-600">
                            <span class="size-2 rounded-full bg-orange-600"></span> Catálogo oficial
                        </p>
                        <h2 id="home-categories-title" class="mt-2 font-display text-3xl font-extrabold uppercase tracking-tight text-slate-900 sm:text-4xl">
                            {{ $homePage->text('categories_title') }}
                        </h2>
                        @if ($homePage->text('categories_description') !== '')
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600">{{ $homePage->text('categories_description') }}</p>
                        @endif
                    </div>
                    <span class="self-start rounded-xl border border-slate-200/80 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 shadow-sm sm:self-auto">
                        {{ $homeActiveCategories }} {{ $homeActiveCategories === 1 ? 'categoría activa' : 'categorías activas' }}
                    </span>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Filtrar vista:</span>
                    @foreach ($categoryCards as $card)
                        <button type="button" class="home-category-pill rounded-full border border-slate-200/80 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-orange-500/50 hover:bg-orange-50 hover:text-orange-600" data-home-category-pill="{{ $loop->index }}" aria-pressed="false">{{ $card->title }}</button>
                    @endforeach
                    <button type="button" class="home-category-reset hidden items-center gap-1 rounded-full border border-orange-300 bg-orange-50 px-3.5 py-2 text-xs font-bold text-orange-700 shadow-sm hover:bg-orange-100" data-home-category-reset><x-icon name="x" class="size-3.5" /> Mostrar todas</button>
                </div>

                <div class="home-card-grid mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-12">
                    @foreach ($categoryCards as $card)
                        @php($destination = $card->destination())
                        @php($span = $categorySpans[$loop->index % count($categorySpans)])
                        <article data-home-category-card="{{ $loop->index }}" data-home-category-layout="{{ $span }}" @class([
                            'home-category-card group flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-orange-500/50 hover:shadow-xl focus-within:-translate-y-1 focus-within:border-orange-500/50 focus-within:shadow-xl',
                            'lg:col-span-5' => $span === 5,
                            'lg:col-span-3' => $span === 3,
                            'lg:col-span-4' => $span === 4,
                            'lg:col-span-8 lg:flex-row lg:items-stretch' => $span === 8,
                        ])>
                            <div class="home-category-card-content flex min-w-0 flex-1 flex-col p-6">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="flex size-11 items-center justify-center rounded-xl bg-orange-100 text-orange-600 shadow-sm">
                                        <x-icon :name="$card->icon ?: 'tag'" class="size-5" />
                                    </span>
                                    @if ($card->linkedProducts->isNotEmpty())
                                        <span class="rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-700">
                                            {{ $card->linkedProducts->count() }} productos
                                        </span>
                                    @endif
                                </div>
                                @if (! $card->exists)
                                    <p class="mt-3 text-[11px] font-bold uppercase tracking-wide text-orange-700">Ejemplo</p>
                                @endif
                                <h3 class="font-display mt-4 text-xl font-bold uppercase tracking-tight text-slate-900 transition-colors group-hover:text-orange-600">{{ $card->title }}</h3>
                                @if ($card->description)
                                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $card->description }}</p>
                                @endif
                                @if ($destination)
                                    <a href="{{ $destination }}" class="mt-auto inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-xl bg-orange-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-orange-600/20 transition-all duration-200 hover:-translate-y-0.5 hover:gap-3 hover:bg-orange-500 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600">
                                        {{ $card->link_label ?: 'Ver categoría' }}
                                        <x-icon name="arrow-right" class="size-4" />
                                    </a>
                                @endif
                            </div>
                            @if ($card->galleryUrls() !== [])
                                <div @class([
                                    'relative aspect-[16/9] w-full overflow-hidden border-t border-slate-100 bg-white p-4',
                                    'lg:w-72 lg:border-l lg:border-t-0' => $span === 8,
                                ])>
                                    <x-carousel :images="$card->galleryUrls()" :alt="$card->title" class="size-full" image-class="size-full object-contain transition-transform duration-300 group-hover:scale-105" data-carousel-effect="depth" />
                                </div>
                            @else
                                <span @class([
                                    'flex h-32 w-full items-center justify-center border-t border-slate-100 bg-slate-50 p-4 text-orange-600',
                                    'lg:w-72 lg:border-l lg:border-t-0' => $span === 8,
                                ])>
                                    <x-icon :name="$card->icon ?: 'tag'" class="size-12 transition-transform duration-300 group-hover:scale-110" />
                                </span>
                            @endif
                        </article>
                    @endforeach
                </div>

                @foreach ($categoryCards as $card)
                    @if ($card->linkedProducts->isNotEmpty())
                        <details id="{{ $card->familyAnchor() }}" class="home-family-list mt-8 scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm" data-home-family>
                            <summary class="cursor-pointer font-display text-lg font-bold uppercase tracking-tight text-slate-900 hover:text-orange-600 transition-colors">
                                {{ $card->title }} <span class="ml-2 text-sm font-semibold normal-case tracking-normal text-slate-500">({{ $card->linkedProducts->count() }} productos disponibles)</span>
                            </summary>
                            <div class="home-card-grid mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
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

    {{-- PRODUCTOS DESTACADOS (Gris piedra sutil bg-slate-50) --}}
    @if ($productCards->isNotEmpty())
        <section id="destacados" class="home-featured scroll-mt-24 border-y border-slate-200/80 bg-slate-50 px-4 py-12 sm:px-6 lg:px-8 lg:py-20" aria-labelledby="home-products-title">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-display flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-orange-600">
                            <span class="size-2 rounded-full bg-orange-600"></span> Nuestra selección
                        </p>
                        <h2 id="home-products-title" class="mt-2 font-display text-3xl font-extrabold uppercase tracking-tight text-slate-900 sm:text-4xl">
                            {{ $homePage->text('products_title') }}
                        </h2>
                        @if ($homePage->text('products_description') !== '')
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600">{{ $homePage->text('products_description') }}</p>
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

    {{-- RECIÉN LLEGADOS / NOVEDADES (Blanco limpio bg-white) --}}
    @if ($newCards->isNotEmpty())
        <section id="nuevos" class="home-new-section scroll-mt-24 bg-white px-4 py-12 sm:px-6 lg:px-8 lg:py-20" aria-labelledby="home-new-title">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-display flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-orange-600">
                            <span class="size-2 rounded-full bg-orange-600"></span> Novedades de mostrador
                        </p>
                        <h2 id="home-new-title" class="mt-2 font-display text-3xl font-extrabold uppercase tracking-tight text-slate-900 sm:text-4xl">
                            {{ $homePage->text('new_title') }}
                        </h2>
                        @if ($homePage->text('new_description') !== '')
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600">{{ $homePage->text('new_description') }}</p>
                        @endif
                        @if ($isEditorPreview ?? false)
                            <p class="mt-2 text-xs font-semibold text-orange-800">Cards de ejemplo para revisar la distribución; no se publican.</p>
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

    {{-- UBICACIÓN Y MOSTRADOR (Gris piedra sutil bg-slate-50) --}}
    @if ($showHomeLocation)
        <section id="ubicacion" class="home-location-section scroll-mt-24 border-t border-slate-200/80 bg-slate-50 px-4 py-12 sm:px-6 lg:px-8 lg:py-20" aria-labelledby="home-location-title">
            <div class="mx-auto max-w-7xl">
                <div class="min-w-0">
                    <p class="font-display flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-orange-600">
                        <span class="size-2 rounded-full bg-orange-600"></span> Encuéntranos
                    </p>
                    <h2 id="home-location-title" class="mt-2 font-display text-3xl font-extrabold uppercase tracking-tight text-slate-900 sm:text-4xl">
                        {{ $homePage->text('store_name') ?: 'Visita nuestro mostrador' }}
                    </h2>
                    @if ($homePage->text('store_description') !== '')
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600">{{ $homePage->text('store_description') }}</p>
                    @endif
                </div>

                <div class="mt-8 grid gap-6 lg:grid-cols-12 lg:items-stretch">
                    <div class="flex flex-col justify-between gap-6 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm lg:col-span-5">
                        @if ($hasHomeLocationDetails)
                            <dl class="grid gap-5 text-sm">
                                @if ($homePage->text('address') !== '')
                                    <div class="flex items-start gap-3">
                                        <x-icon name="map-pin" class="mt-0.5 size-5 shrink-0 text-orange-600" />
                                        <div class="min-w-0">
                                            <dt class="font-bold text-slate-900">Dirección</dt>
                                            <dd class="mt-1 whitespace-pre-line leading-relaxed text-slate-600">{{ $homePage->text('address') }}</dd>
                                        </div>
                                    </div>
                                @endif
                                @if ($homePage->text('location_reference') !== '')
                                    <div class="flex items-start gap-3">
                                        <x-icon name="arrow-right" class="mt-0.5 size-5 shrink-0 text-sky-600" />
                                        <div class="min-w-0">
                                            <dt class="font-bold text-slate-900">Referencia</dt>
                                            <dd class="mt-1 whitespace-pre-line leading-relaxed text-slate-600">{{ $homePage->text('location_reference') }}</dd>
                                        </div>
                                    </div>
                                @endif
                                @if ($homePage->text('hours') !== '')
                                    <div class="flex items-start gap-3">
                                        <x-icon name="clock" class="mt-0.5 size-5 shrink-0 text-emerald-600" />
                                        <div class="min-w-0">
                                            <dt class="font-bold text-slate-900">Horarios</dt>
                                            <dd class="mt-1 whitespace-pre-line leading-relaxed text-slate-600">{{ $homePage->text('hours') }}</dd>
                                        </div>
                                    </div>
                                @endif
                                @if ($homePhoneUrl)
                                    <div class="flex items-start gap-3">
                                        <x-icon name="phone" class="mt-0.5 size-5 shrink-0 text-sky-600" />
                                        <div class="min-w-0">
                                            <dt class="font-bold text-slate-900">Teléfono</dt>
                                            <dd class="mt-1">
                                                <a href="{{ $homePhoneUrl }}" class="font-medium text-slate-700 underline underline-offset-4 hover:text-orange-600">{{ $homePage->text('phone') }}</a>
                                            </dd>
                                        </div>
                                    </div>
                                @endif
                                @if ($homeEmailUrl)
                                    <div class="flex items-start gap-3">
                                        <x-icon name="mail" class="mt-0.5 size-5 shrink-0 text-orange-600" />
                                        <div class="min-w-0">
                                            <dt class="font-bold text-slate-900">Correo</dt>
                                            <dd class="mt-1">
                                                <a href="{{ $homeEmailUrl }}" class="break-words font-medium text-slate-700 underline underline-offset-4 hover:text-orange-600">{{ $homePage->text('email') }}</a>
                                            </dd>
                                        </div>
                                    </div>
                                @endif
                            </dl>
                        @endif

                        @if ($homeDirectionsUrl || $homeWhatsappUrl)
                            <div class="flex flex-col gap-3 sm:flex-row">
                                @if ($homeDirectionsUrl)
                                    <a href="{{ $homeDirectionsUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-orange-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-orange-600/20 transition hover:bg-orange-500">
                                        <x-icon name="map-pin" class="size-4" /> Cómo llegar
                                    </a>
                                @endif
                                @if ($homeWhatsappUrl)
                                    <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-emerald-950/20 transition hover:bg-emerald-500">
                                        <x-icon name="chat" class="size-4" /> WhatsApp
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($homeMapEmbedUrl)
                        <div class="flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm lg:col-span-7">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-100 px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-700">
                                <span class="flex items-center gap-2">
                                    <x-icon name="map-pin" class="size-4 text-orange-600" /> Mapa del mostrador
                                </span>
                                @if ($homeDirectionsUrl)
                                    <a href="{{ $homeDirectionsUrl }}" target="_blank" rel="noopener" class="text-sky-600 hover:underline">Abrir ruta</a>
                                @endif
                            </div>
                            <iframe src="{{ $homeMapEmbedUrl }}" title="Mapa de ubicación de {{ $homePage->text('store_name') ?: 'MotoZone' }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="home-location-map w-full flex-1 border-0" allowfullscreen></iframe>
                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-4 py-2 text-xs text-slate-500">
                                <a href="{{ $homeDirectionsUrl ?: $homeMapEmbedUrl }}" target="_blank" rel="noopener" class="underline underline-offset-2 hover:text-slate-700">Abrir mapa en otra pestaña</a>
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

    {{-- ASESORÍA Y TALLER (Blanco limpio bg-white con banner carbón slate-900) --}}
    @if ($homePage->enabled('advice') && ($homePage->text('advice_title') !== '' || $homePage->text('advice_description') !== ''))
        <section class="bg-white px-4 py-12 sm:px-6 lg:px-8 lg:py-16" aria-label="Asesoría">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-8 rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-slate-950 p-8 shadow-2xl text-white sm:p-12">
                <div class="min-w-0 max-w-2xl">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-orange-600/20 text-orange-500 border border-orange-500/30">
                        <x-icon name="wrench" class="size-6" />
                    </span>
                    @if ($homePage->text('advice_title') !== '')
                        <h2 class="mt-4 font-display text-2xl font-extrabold uppercase tracking-tight text-white sm:text-3xl">
                            {{ $homePage->text('advice_title') }}
                        </h2>
                    @endif
                    @if ($homePage->text('advice_description') !== '')
                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-300 sm:text-base">
                            {{ $homePage->text('advice_description') }}
                        </p>
                    @endif
                </div>
                @if ($homeWhatsappUrl || $homeEmailUrl || $homePhoneUrl)
                    <div class="flex flex-wrap gap-3.5">
                        @if ($homeWhatsappUrl)
                            <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-lg shadow-emerald-950/40 transition hover:-translate-y-0.5 hover:bg-emerald-500">
                                <x-icon name="chat" class="size-5" /> Escríbenos por WhatsApp
                            </a>
                        @endif
                        @if ($homeEmailUrl)
                            <a href="{{ $homeEmailUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white/20">
                                <x-icon name="mail" class="size-5" /> Escríbenos por correo
                            </a>
                        @endif
                        @if ($homePhoneUrl && ! $homeWhatsappUrl && ! $homeEmailUrl)
                            <a href="{{ $homePhoneUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-orange-600 px-6 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-lg shadow-orange-600/30 transition hover:-translate-y-0.5 hover:bg-orange-500">
                                <x-icon name="phone" class="size-5" /> Llámanos
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </section>
    @endif

    </div>
@endsection
