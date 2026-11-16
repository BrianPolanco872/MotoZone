@php
    $isHomeStorefront = trim($__env->yieldContent('home-style')) === 'storefront';
    $homePage = $homePage ?? \App\Models\HomePage::current();
    $homeWhatsappUrl = $homeWhatsappUrl ?? $homePage->whatsappUrl();
    $homePhoneUrl = $homePhoneUrl ?? $homePage->phoneUrl();
    $homeEmailUrl = $homeEmailUrl ?? $homePage->emailUrl();
    $homeDirectionsUrl = $homeDirectionsUrl ?? $homePage->directionsUrl();
    $showHomeLocation = $showHomeLocation ?? ($homePage->enabled('location') && ($homePage->text('address') !== '' || $homePage->text('location_reference') !== '' || $homePage->mapEmbedUrl() || $homeDirectionsUrl));

    // Datos del Footer dinámico desde el bloque Información
    $footerDescription = $homePage->footerDescription();
    $footerAddress = trim($homePage->text('address'));
    $footerMapsUrl = trim($homePage->text('maps_url')) ?: $homeDirectionsUrl;
    $footerHours = trim($homePage->text('hours'));
    $footerPhone = trim($homePage->text('phone'));
    $footerEmail = trim($homePage->text('email'));
    $footerFacebook = $homePage->facebookUrl();
    $footerInstagram = $homePage->instagramUrl();
    $footerCopyright = $homePage->copyrightText();

    $footerCategories = isset($categoryCards) && $categoryCards->isNotEmpty()
        ? $categoryCards->take(5)
        : \App\Models\Category::query()->where('is_active', true)->whereHas('products', fn ($q) => $q->active())->orderBy('name')->take(5)->get();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Refacciones y accesorios para tu moto') · {{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Refacciones, accesorios y equipo para tu moto. Consulta las existencias disponibles y recibe asesoría para elegir tu pieza.')">
    <meta property="og:title" content="@yield('title', 'Refacciones y accesorios para tu moto') · {{ config('app.name') }}">
    <meta property="og:description" content="@yield('description', 'Refacciones, accesorios y equipo para tu moto. Consulta las existencias disponibles y recibe asesoría para elegir tu pieza.')">
    <meta property="og:type" content="website">
    @if ($isHomeStorefront)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class(['flex min-h-screen flex-col bg-white font-sans text-slate-900 antialiased', 'home-storefront' => $isHomeStorefront])>
    <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-slate-900 focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white">Saltar al contenido</a>

    @if ($isHomeStorefront)
        <header class="home-header fixed inset-x-0 top-0 z-40 border-b border-slate-200/80 bg-white/95 shadow-sm backdrop-blur-xl">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="home-logo flex min-w-0 shrink-0 items-center gap-2.5" aria-label="{{ config('app.name') }} · Inicio">
                    <span class="home-logo-mark flex size-10 shrink-0 items-center justify-center rounded-xl bg-orange-600 text-xl font-extrabold italic text-white shadow-md shadow-orange-600/30">M</span>
                    <span class="flex min-w-0 flex-col">
                        <span class="font-display text-xl font-extrabold uppercase leading-none tracking-tight text-slate-900 sm:text-2xl">Moto<span class="text-orange-600">Zone</span></span>
                        <span class="mt-1 hidden truncate text-[9px] font-bold uppercase tracking-[0.16em] text-slate-500 sm:block">Refacciones &amp; Accesorios de Moto</span>
                    </span>
                </a>
                <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
                    @if ($showHomeLocation)
                        <a href="#ubicacion" class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50 px-4 text-xs font-bold text-slate-700 shadow-sm transition hover:border-orange-500/50 hover:bg-orange-50 hover:text-orange-600 hidden sm:inline-flex"><x-icon name="map-pin" class="size-4 text-orange-600" /> <span>Ver mostrador</span></a>
                    @endif
                    @if ($homeWhatsappUrl)
                        <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-emerald-600 px-4 text-xs font-bold uppercase tracking-wide text-white shadow-md shadow-emerald-950/20 transition hover:bg-emerald-500" aria-label="Cotizar por WhatsApp"><x-icon name="chat" class="size-4" /> <span class="hidden sm:inline md:hidden">WhatsApp</span><span class="hidden md:inline">Cotizar por WhatsApp</span></a>
                    @elseif ($homeEmailUrl)
                        <a href="{{ $homeEmailUrl }}" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-orange-600 px-4 text-xs font-bold uppercase tracking-wide text-white shadow-md shadow-orange-950/20 transition hover:bg-orange-500" aria-label="Contacto por correo"><x-icon name="mail" class="size-4" /> <span class="hidden sm:inline md:hidden">Contacto</span><span class="hidden md:inline">Contacto por correo</span></a>
                    @elseif ($homePhoneUrl)
                        <a href="{{ $homePhoneUrl }}" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-orange-600 px-4 text-xs font-bold uppercase tracking-wide text-white shadow-md shadow-orange-950/20 transition hover:bg-orange-500" aria-label="Llamar al mostrador"><x-icon name="phone" class="size-4" /> <span class="hidden sm:inline md:hidden">Llamar</span><span class="hidden md:inline">Llamar al mostrador</span></a>
                    @endif
                    @auth
                        @if (auth()->user()->hasRole('admin'))
                            <a href="{{ route('admin.dashboard') }}" class="flex size-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-700 shadow-sm transition hover:border-orange-500 hover:text-orange-600" aria-label="Panel de administración" title="Panel de administración"><x-icon name="cog" class="size-5" /></a>
                        @elseif (auth()->user()->can('pos.access'))
                            <a href="{{ route('pos.index') }}" class="flex size-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-700 shadow-sm transition hover:border-orange-500 hover:text-orange-600" aria-label="Punto de venta" title="Punto de venta"><x-icon name="cart" class="size-5" /></a>
                        @elseif (auth()->user()->can('admin.products.manage'))
                            <a href="{{ route('admin.products.index') }}" class="flex size-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-700 shadow-sm transition hover:border-orange-500 hover:text-orange-600" aria-label="Productos" title="Productos"><x-icon name="inventory" class="size-5" /></a>
                        @else
                            <a href="{{ route('admin.profile.edit') }}" class="flex size-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-700 shadow-sm transition hover:border-orange-500 hover:text-orange-600" aria-label="Mi perfil" title="Mi perfil"><x-icon name="lock" class="size-5" /></a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-4 text-xs font-bold uppercase tracking-wide text-slate-700 shadow-sm transition hover:border-orange-500/50 hover:bg-orange-50 hover:text-orange-600" aria-label="Iniciar sesión" title="Iniciar sesión"><x-icon name="login" class="size-4" /> <span class="hidden sm:inline">Iniciar sesión</span></a>
                    @endauth
                </div>
            </div>
        </header>
    @else
        <div class="bg-slate-950 text-white">
            <div class="mx-auto flex h-9 max-w-7xl items-center justify-between gap-4 px-4 text-[11px] sm:px-6 lg:px-8">
                <p class="flex items-center gap-2 text-slate-300">
                    <x-icon name="truck" class="size-4 text-orange-500" />
                    <span>Refacciones y accesorios para tu moto</span>
                </p>
                <div class="hidden items-center gap-5 sm:flex">
                    @if ($footerPhone !== '')
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $footerPhone) }}" class="flex items-center gap-1.5 transition hover:text-orange-400">
                            <x-icon name="phone" class="size-3.5 text-orange-500" />
                            {{ $footerPhone }}
                        </a>
                    @endif
                    @if ($footerHours !== '')
                        <span class="flex items-center gap-1.5 text-slate-400">
                            <x-icon name="clock" class="size-3.5 text-orange-500" />
                            {{ $footerHours }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur-md">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="{{ config('app.name') }} · Inicio">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-orange-600 text-xl font-extrabold italic text-white shadow-md shadow-orange-600/30">M</span>
                    <span class="font-display text-2xl font-extrabold uppercase leading-none tracking-tight text-slate-900">Moto<span class="text-orange-600">Zone</span></span>
                </a>

                <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 lg:flex" aria-label="Navegación principal">
                    <a href="{{ route('home') }}" @class(['transition hover:text-orange-600', 'text-slate-900 font-semibold' => request()->routeIs('home')]) @if (request()->routeIs('home')) aria-current="page" @endif>Inicio</a>
                    @if ($homeWhatsappUrl)
                        <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="transition hover:text-orange-600">WhatsApp</a>
                    @elseif ($footerPhone !== '')
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $footerPhone) }}" class="transition hover:text-orange-600">Contacto</a>
                    @endif
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    @auth
                        @if (auth()->user()->hasRole('admin'))
                            <a href="{{ route('admin.dashboard') }}" class="flex size-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:border-orange-500 hover:text-orange-600" aria-label="Panel de administración" title="Panel de administración">
                                <x-icon name="cog" class="size-5" />
                            </a>
                        @elseif (auth()->user()->can('pos.access'))
                            <a href="{{ route('pos.index') }}" class="flex size-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:border-orange-500 hover:text-orange-600" aria-label="Punto de venta" title="Punto de venta">
                                <x-icon name="cart" class="size-5" />
                            </a>
                        @elseif (auth()->user()->can('admin.products.manage'))
                            <a href="{{ route('admin.products.index') }}" class="flex size-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:border-orange-500 hover:text-orange-600" aria-label="Productos" title="Productos">
                                <x-icon name="inventory" class="size-5" />
                            </a>
                        @else
                            <a href="{{ route('admin.profile.edit') }}" class="flex size-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:border-orange-500 hover:text-orange-600" aria-label="Mi perfil" title="Mi perfil">
                                <x-icon name="lock" class="size-5" />
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-3.5 text-xs font-bold uppercase tracking-wider text-white transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2" aria-label="Iniciar sesión" title="Iniciar sesión">
                            <x-icon name="login" class="size-4" />
                            <span class="hidden sm:inline">Iniciar sesión</span>
                        </a>
                    @endauth

                    <details class="relative lg:hidden" data-dismissable>
                        <summary class="flex size-10 cursor-pointer list-none items-center justify-center rounded-xl border border-slate-200 text-slate-700 transition hover:bg-slate-50" aria-label="Abrir menú">
                            <x-icon name="menu" class="size-5" />
                        </summary>
                        <div class="absolute right-0 mt-2 w-48 rounded-xl border border-slate-200 bg-white p-2 text-sm shadow-xl">
                            <a href="{{ route('home') }}" class="block rounded-lg px-3 py-2 text-slate-700 transition hover:bg-slate-100 hover:text-orange-600">Inicio</a>
                            @if ($homeWhatsappUrl)
                                <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="block rounded-lg px-3 py-2 text-slate-700 transition hover:bg-slate-100 hover:text-orange-600">WhatsApp</a>
                            @endif
                        </div>
                    </details>
                </div>
            </div>
        </header>
    @endif

    <main id="contenido" @class(['flex-1', 'pt-20' => $isHomeStorefront])>
        @yield('content')
    </main>

    {{-- Footer Principal Dinámico y Editable desde CMS "Información" --}}
    <footer class="mt-auto border-t border-slate-800 bg-slate-950 text-slate-400">
        {{-- Fila superior destacada para Storefront (si hay datos de ubicación, horarios o contacto) --}}
        @if ($isHomeStorefront && ($footerAddress !== '' || $footerHours !== '' || $homeWhatsappUrl || $footerPhone !== '' || $footerEmail !== ''))
            <div class="border-b border-slate-800/80 bg-slate-900/60 px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto grid max-w-7xl gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @if ($footerAddress !== '')
                        <article class="flex items-center gap-3.5 rounded-2xl border border-slate-800 bg-slate-900/90 p-4 text-slate-300 shadow-sm">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-orange-500/10 text-orange-500">
                                <x-icon name="map-pin" class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <h2 class="font-display text-xs font-bold uppercase tracking-wider text-white">Ubicación</h2>
                                <p class="mt-0.5 truncate text-xs text-slate-400" title="{{ $footerAddress }}">{{ $footerAddress }}</p>
                            </div>
                        </article>
                    @endif
                    @if ($footerHours !== '')
                        <article class="flex items-center gap-3.5 rounded-2xl border border-slate-800 bg-slate-900/90 p-4 text-slate-300 shadow-sm">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400">
                                <x-icon name="clock" class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <h2 class="font-display text-xs font-bold uppercase tracking-wider text-white">Horarios</h2>
                                <p class="mt-0.5 truncate text-xs text-slate-400" title="{{ $footerHours }}">{{ $footerHours }}</p>
                            </div>
                        </article>
                    @endif
                    @if ($homeWhatsappUrl)
                        <article class="flex items-center gap-3.5 rounded-2xl border border-slate-800 bg-slate-900/90 p-4 text-slate-300 shadow-sm">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#25D366]/15 text-[#25D366]">
                                <x-icon name="chat" class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <h2 class="font-display text-xs font-bold uppercase tracking-wider text-white">WhatsApp</h2>
                                <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="mt-0.5 block truncate text-xs text-[#25D366] transition hover:underline">Escríbenos al mostrador</a>
                            </div>
                        </article>
                    @endif
                    @if ($footerPhone !== '' || $footerEmail !== '')
                        <article class="flex items-center gap-3.5 rounded-2xl border border-slate-800 bg-slate-900/90 p-4 text-slate-300 shadow-sm">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-400">
                                <x-icon name="phone" class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <h2 class="font-display text-xs font-bold uppercase tracking-wider text-white">Contacto</h2>
                                @if ($footerPhone !== '')
                                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $footerPhone) }}" class="mt-0.5 block truncate text-xs text-sky-400 transition hover:underline">{{ $footerPhone }}</a>
                                @endif
                                @if ($footerEmail !== '')
                                    <a href="mailto:{{ $footerEmail }}" class="block truncate text-xs text-slate-400 transition hover:underline">{{ $footerEmail }}</a>
                                @endif
                            </div>
                        </article>
                    @endif
                </div>
            </div>
        @endif

        {{-- Columnas del Footer organizadas en 4 bloques --}}
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:gap-8 lg:px-8 lg:py-16">
            {{-- Columna 1: Marca, descripción corta y Redes Sociales --}}
            <div class="min-w-0">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5" aria-label="{{ config('app.name') }} · Inicio">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-orange-600 text-xl font-extrabold italic text-white shadow-md shadow-orange-600/30">M</span>
                    <span class="font-display text-2xl font-extrabold uppercase tracking-tight text-white">Moto<span class="text-orange-500">Zone</span></span>
                </a>

                @if ($footerDescription !== '')
                    <p class="mt-4 max-w-sm whitespace-pre-line text-sm leading-relaxed text-slate-400">
                        {{ $footerDescription }}
                    </p>
                @endif

                {{-- Redes Sociales --}}
                @if ($footerFacebook || $footerInstagram)
                    <div class="mt-5 flex items-center gap-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Síguenos:</span>
                        <div class="flex items-center gap-2">
                            @if ($footerFacebook)
                                <a href="{{ $footerFacebook }}" target="_blank" rel="noopener noreferrer" class="flex size-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900 text-slate-400 transition hover:border-orange-500 hover:bg-orange-500/10 hover:text-white" aria-label="Facebook de MotoZone" title="Facebook">
                                    <x-icon name="facebook" class="size-4" />
                                </a>
                            @endif
                            @if ($footerInstagram)
                                <a href="{{ $footerInstagram }}" target="_blank" rel="noopener noreferrer" class="flex size-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900 text-slate-400 transition hover:border-orange-500 hover:bg-orange-500/10 hover:text-white" aria-label="Instagram de MotoZone" title="Instagram">
                                    <x-icon name="instagram" class="size-4" />
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Columna 2: Categorías --}}
            <div class="min-w-0">
                <h2 class="font-display text-xs font-bold uppercase tracking-[0.16em] text-white">Categorías</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @forelse ($footerCategories as $cat)
                        @php
                            $catTitle = is_a($cat, \App\Models\Category::class) ? $cat->name : $cat->title;
                            $catUrl = is_a($cat, \App\Models\Category::class)
                                ? route('home') . '#categorias'
                                : ($cat->linkedProducts->isNotEmpty() ? '#' . $cat->familyAnchor() : '#categorias');
                        @endphp
                        <li>
                            <a href="{{ $catUrl }}" class="text-slate-400 transition hover:text-orange-400">
                                {{ $catTitle }}
                            </a>
                        </li>
                    @empty
                        <li><a href="{{ route('home') }}#categorias" class="text-slate-400 transition hover:text-orange-400">Explorar catálogo</a></li>
                    @endforelse
                </ul>
            </div>

            {{-- Columna 3: Ubicación y Horarios --}}
            <div class="min-w-0">
                <h2 class="font-display text-xs font-bold uppercase tracking-[0.16em] text-white">Ubicación y Horarios</h2>
                <ul class="mt-4 space-y-3.5 text-sm">
                    @if ($footerAddress !== '')
                        <li class="flex items-start gap-2.5 text-slate-400">
                            <x-icon name="map-pin" class="mt-0.5 size-4 shrink-0 text-orange-500" />
                            <span class="whitespace-pre-line leading-relaxed">{{ $footerAddress }}</span>
                        </li>
                    @endif

                    @if ($footerMapsUrl)
                        <li>
                            <a href="{{ $footerMapsUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-xs font-semibold text-orange-400 transition hover:text-orange-300 hover:underline">
                                <x-icon name="arrow-right" class="size-3.5" />
                                <span>Ver en Google Maps</span>
                            </a>
                        </li>
                    @endif

                    @if ($footerHours !== '')
                        <li class="flex items-start gap-2.5 text-slate-400">
                            <x-icon name="clock" class="mt-0.5 size-4 shrink-0 text-orange-500" />
                            <span class="whitespace-pre-line leading-relaxed">{{ $footerHours }}</span>
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Columna 4: Contacto directo & WhatsApp --}}
            <div class="min-w-0">
                <h2 class="font-display text-xs font-bold uppercase tracking-[0.16em] text-white">Contacto directo</h2>
                <div class="mt-4 space-y-3">
                    @if ($homeWhatsappUrl)
                        <div>
                            <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#25D366] px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-[#25D366]/20 transition hover:bg-[#20bd5a] focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2 focus:ring-offset-slate-950">
                                <svg class="size-4 shrink-0 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                </svg>
                                <span>Cotizar por WhatsApp</span>
                            </a>
                        </div>
                    @endif

                    @if ($footerPhone !== '')
                        <div>
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $footerPhone) }}" class="inline-flex items-center gap-2 text-sm text-slate-400 transition hover:text-orange-400">
                                <x-icon name="phone" class="size-4 text-orange-500" />
                                <span>{{ $footerPhone }}</span>
                            </a>
                        </div>
                    @endif

                    @if ($footerEmail !== '')
                        <div>
                            <a href="mailto:{{ $footerEmail }}" class="inline-flex min-w-0 items-center gap-2 break-all text-sm text-slate-400 transition hover:text-orange-400">
                                <x-icon name="mail" class="size-4 shrink-0 text-orange-500" />
                                <span>{{ $footerEmail }}</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Barra inferior de Copyright y Enlace discreto a Panel administrativo --}}
        <div class="border-t border-slate-900 bg-black">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-5 text-xs text-slate-500 sm:flex-row sm:px-6 lg:px-8">
                <p>{{ $footerCopyright }}</p>

                <div class="flex items-center gap-4 text-xs">
                    @auth
                        @if (auth()->user()->hasRole('admin'))
                            <a href="{{ route('admin.dashboard') }}" class="text-slate-400 transition hover:text-orange-400">Panel de administración</a>
                        @elseif (auth()->user()->can('pos.access'))
                            <a href="{{ route('pos.index') }}" class="text-slate-400 transition hover:text-orange-400">Punto de venta</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="text-slate-500 transition hover:text-slate-300">Acceso administrativo</a>
                    @endauth
                </div>
            </div>
        </div>
    </footer>

    {{-- Burbuja flotante de WhatsApp --}}
    @if ($homeWhatsappUrl)
        <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="fixed bottom-5 right-5 z-40 flex size-12 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-[#25D366]/30 transition hover:bg-[#20bd5a] focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2" aria-label="Escríbenos por WhatsApp">
            <x-icon name="chat" class="size-6" />
        </a>
    @endif
</body>
</html>
