@php($isHomeStorefront = trim($__env->yieldContent('home-style')) === 'storefront')
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
        <header class="home-header fixed inset-x-0 top-0 z-40 border-b border-[#e6ded1] bg-[#fbf9f5]/95 shadow-[0_2px_18px_rgba(40,30,20,0.09)] backdrop-blur-xl">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="home-logo flex min-w-0 shrink-0 items-center gap-2.5" aria-label="{{ config('app.name') }} · Inicio">
                    <span class="home-logo-mark flex size-10 shrink-0 items-center justify-center rounded text-xl font-bold italic text-white shadow-sm">M</span>
                    <span class="flex min-w-0 flex-col">
                        <span class="font-display text-xl font-bold uppercase leading-none tracking-tight text-[#0f172a] sm:text-2xl">Moto<span class="home-accent">Zone</span></span>
                        <span class="mt-1 hidden truncate text-[9px] font-bold uppercase tracking-[0.16em] text-slate-500 sm:block">Refacciones &amp; Accesorios de Moto</span>
                    </span>
                </a>
                <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
                    @if ($showHomeLocation)
                        <a href="#ubicacion" class="home-button home-button-light hidden sm:inline-flex"><x-icon name="map-pin" class="size-4 text-[#ea580c]" /> <span>Ver mostrador central</span></a>
                    @endif
                    @if ($homeWhatsappUrl)
                        <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="home-button home-button-whatsapp" aria-label="Cotizar por WhatsApp"><x-icon name="chat" class="size-4" /> <span class="hidden sm:inline md:hidden">WhatsApp</span><span class="hidden md:inline">Cotizar por WhatsApp</span></a>
                    @elseif ($homeEmailUrl)
                        <a href="{{ $homeEmailUrl }}" class="home-button home-button-primary" aria-label="Contacto por correo"><x-icon name="mail" class="size-4" /> <span class="hidden sm:inline md:hidden">Contacto</span><span class="hidden md:inline">Contacto por correo</span></a>
                    @elseif ($homePhoneUrl)
                        <a href="{{ $homePhoneUrl }}" class="home-button home-button-primary" aria-label="Llamar al mostrador"><x-icon name="phone" class="size-4" /> <span class="hidden sm:inline md:hidden">Llamar</span><span class="hidden md:inline">Llamar al mostrador</span></a>
                    @endif
                    @auth
                        @if (auth()->user()->hasRole('admin'))
                            <a href="{{ route('admin.dashboard') }}" class="home-panel-button flex size-10 items-center justify-center rounded-lg border border-[#e2d9cc] bg-white text-slate-700 shadow-sm" aria-label="Panel de administración" title="Panel de administración"><x-icon name="cog" class="size-5" /></a>
                        @elseif (auth()->user()->can('pos.access'))
                            <a href="{{ route('pos.index') }}" class="home-panel-button flex size-10 items-center justify-center rounded-lg border border-[#e2d9cc] bg-white text-slate-700 shadow-sm" aria-label="Punto de venta" title="Punto de venta"><x-icon name="cart" class="size-5" /></a>
                        @elseif (auth()->user()->can('admin.products.manage'))
                            <a href="{{ route('admin.products.index') }}" class="home-panel-button flex size-10 items-center justify-center rounded-lg border border-[#e2d9cc] bg-white text-slate-700 shadow-sm" aria-label="Productos" title="Productos"><x-icon name="inventory" class="size-5" /></a>
                        @else
                            <a href="{{ route('admin.profile.edit') }}" class="home-panel-button flex size-10 items-center justify-center rounded-lg border border-[#e2d9cc] bg-white text-slate-700 shadow-sm" aria-label="Mi perfil" title="Mi perfil"><x-icon name="lock" class="size-5" /></a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="home-panel-button inline-flex h-10 items-center gap-1.5 rounded-lg border border-[#e2d9cc] bg-white px-2.5 text-slate-700 shadow-sm sm:px-3" aria-label="Acceso Personal" title="Acceso Personal"><x-icon name="lock" class="size-4 text-[#ea580c]" /><span class="hidden text-[10px] font-bold uppercase tracking-wide lg:inline">Acceso Personal</span><span class="sr-only">Iniciar sesión</span></a>
                    @endauth
                </div>
            </div>
        </header>
    @else
    <div class="bg-slate-950 text-white">
        <div class="mx-auto flex h-9 max-w-7xl items-center justify-between gap-4 px-4 text-[11px] sm:px-6 lg:px-8">
            <p class="flex items-center gap-2">
                <x-icon name="truck" class="size-4 text-mz-accent-primary" />
                <span>Envíos a todo México en 24–48 h</span>
            </p>
            <div class="hidden items-center gap-5 sm:flex">
                <a href="tel:+525512345678" class="flex items-center gap-1.5 transition hover:text-mz-accent-primary/80">
                    <x-icon name="phone" class="size-3.5" />
                    55 1234 5678
                </a>
                <span class="flex items-center gap-1.5 text-white/60">
                    <x-icon name="clock" class="size-3.5" />
                    Lun–Sáb · 9:00 a 19:00
                </span>
            </div>
        </div>
    </div>

    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="{{ config('app.name') }} · Inicio">
                <span class="flex size-10 items-center justify-center rounded-md bg-slate-900 text-mz-accent-primary">
                    <x-icon name="moto" class="size-6" />
                </span>
                <span class="font-display text-2xl font-bold uppercase leading-none tracking-tight text-slate-900">Moto<span class="text-mz-accent-primary">Zone</span></span>
            </a>

            <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 lg:flex" aria-label="Navegación principal">
                <a href="{{ route('home') }}" @class(['transition hover:text-mz-accent-primary', 'text-slate-900' => request()->routeIs('home')]) @if (request()->routeIs('home')) aria-current="page" @endif>Inicio</a>

                <a href="tel:+525512345678" class="transition hover:text-mz-accent-primary">Contacto</a>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                @auth
                    @if (auth()->user()->hasRole('admin'))
                        <a href="{{ route('admin.dashboard') }}" class="flex size-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900" aria-label="Panel de administración" title="Panel de administración">
                            <x-icon name="cog" class="size-5" />
                        </a>
                    @elseif (auth()->user()->can('pos.access'))
                        <a href="{{ route('pos.index') }}" class="flex size-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900" aria-label="Punto de venta" title="Punto de venta">
                            <x-icon name="cart" class="size-5" />
                        </a>
                    @elseif (auth()->user()->can('admin.products.manage'))
                        <a href="{{ route('admin.products.index') }}" class="flex size-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900" aria-label="Productos" title="Productos">
                            <x-icon name="inventory" class="size-5" />
                        </a>
                    @else
                        <a href="{{ route('admin.profile.edit') }}" class="flex size-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900" aria-label="Mi perfil" title="Mi perfil">
                            <x-icon name="lock" class="size-5" />
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-slate-900 px-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-mz-accent-primary focus-visible:ring-offset-2" aria-label="Iniciar sesión" title="Iniciar sesión">
                        <x-icon name="login" class="size-4" />
                        <span class="hidden sm:inline">Iniciar sesión</span>
                    </a>
                @endauth

                <details class="relative lg:hidden" data-dismissable>
                    <summary class="flex size-10 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 [&::-webkit-details-marker]:hidden" aria-label="Abrir menú">
                        <x-icon name="menu" class="size-5" />
                    </summary>
                    <div class="absolute right-0 mt-2 w-72 rounded-lg border border-slate-200 bg-white p-3 shadow-xl shadow-slate-900/10">
                        <a href="{{ route('home') }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @if (request()->routeIs('home')) aria-current="page" @endif>Inicio <x-icon name="arrow-right" class="size-4 text-slate-500" /></a>
                        <a href="tel:+525512345678" class="mt-2 flex items-center gap-2 border-t border-slate-100 px-3 pt-3 text-sm text-slate-600">
                            <x-icon name="phone" class="size-4 text-slate-500" />
                            Contacto · 55 1234 5678
                        </a>
                    </div>
                </details>
            </div>
        </div>
    </header>
    @endif

    <main id="contenido" @class(['flex-1', 'pt-20' => $isHomeStorefront])>
        @yield('content')
    </main>

    @if ($isHomeStorefront)
        <footer class="home-footer mt-auto border-t border-[#e6ded1] text-stone-600">
            @if (($showHomeLocation && $homePage->text('address') !== '') || $homePage->text('hours') !== '' || $homeWhatsappUrl || $homePhoneUrl || $homeEmailUrl)
                <div class="home-footer-contact-band border-b border-[#e6ded1] px-4 py-8 sm:px-6 lg:px-8">
                    <div class="mx-auto grid max-w-7xl gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @if ($showHomeLocation && $homePage->text('address') !== '')
                            <article class="home-footer-contact-card"><x-icon name="map-pin" class="size-8 shrink-0 text-[#ea580c]" /><div><h2>Dirección del mostrador</h2><p>{{ $homePage->text('address') }}</p></div></article>
                        @endif
                        @if ($homePage->text('hours') !== '')
                            <article class="home-footer-contact-card"><x-icon name="clock" class="size-8 shrink-0 text-[#15803d]" /><div><h2>Horarios</h2><p>{{ $homePage->text('hours') }}</p></div></article>
                        @endif
                        @if ($homeWhatsappUrl)
                            <article class="home-footer-contact-card"><x-icon name="chat" class="size-8 shrink-0 text-[#15803d]" /><div><h2>WhatsApp</h2><a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer">Escríbenos al mostrador</a></div></article>
                        @endif
                        @if ($homePhoneUrl || $homeEmailUrl)
                            <article class="home-footer-contact-card"><x-icon name="phone" class="size-8 shrink-0 text-[#0284c7]" /><div><h2>Contacto</h2>@if ($homePhoneUrl)<a href="{{ $homePhoneUrl }}">{{ $homePage->text('phone') }}</a>@endif @if ($homeEmailUrl)<a href="{{ $homeEmailUrl }}">{{ $homePage->text('email') }}</a>@endif</div></article>
                        @endif
                    </div>
                </div>
            @endif

            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-5 lg:px-8 lg:py-14">
                <div class="min-w-0 sm:col-span-2">
                    <a href="{{ route('home') }}" class="home-logo inline-flex items-center gap-2.5" aria-label="{{ config('app.name') }} · Inicio">
                        <span class="home-logo-mark flex size-10 items-center justify-center rounded text-xl font-bold italic text-white">M</span>
                        <span class="font-display text-2xl font-bold uppercase tracking-tight text-stone-900">Moto<span class="home-accent">Zone</span></span>
                    </a>
                    @if ($homePage->text('store_description') !== '')
                        <p class="mt-4 max-w-md whitespace-pre-line text-sm leading-relaxed">{{ $homePage->text('store_description') }}</p>
                    @endif
                    @if ($homePage->text('store_name') !== '')
                        <p class="mt-3 text-sm font-semibold text-stone-900">{{ $homePage->text('store_name') }}</p>
                    @endif
                </div>

                @if ($categoryCards->isNotEmpty())
                    <div class="min-w-0">
                        <h2 class="font-display text-sm font-bold uppercase tracking-wide text-stone-900">Categorías principales</h2>
                        <ul class="mt-4 grid gap-2.5 text-sm">
                            @foreach ($categoryCards->take(5) as $card)
                                <li><a href="{{ $card->linkedProducts->isNotEmpty() ? '#'.$card->familyAnchor() : '#categorias' }}" class="home-nav-link">{{ $card->title }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="min-w-0">
                    <h2 class="font-display text-sm font-bold uppercase tracking-wide text-stone-900">Atención al cliente</h2>
                    <ul class="mt-4 grid gap-2.5 text-sm">
                        @if ($homeWhatsappUrl)
                            <li><a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="home-nav-link">Cotizaciones por WhatsApp</a></li>
                        @endif
                        @if ($homeEmailUrl)
                            <li><a href="{{ $homeEmailUrl }}" class="home-nav-link">Contacto por correo</a></li>
                        @endif
                        @if ($showHomeLocation)
                            <li><a href="#ubicacion" class="home-nav-link">Cómo llegar al mostrador</a></li>
                        @endif
                        @if ($homePhoneUrl)
                            <li><a href="{{ $homePhoneUrl }}" class="home-nav-link">Teléfono del mostrador</a></li>
                        @endif
                        @auth
                            @if (auth()->user()->hasRole('admin'))
                                <li><a href="{{ route('admin.dashboard') }}" class="home-nav-link">Panel de administración</a></li>
                            @elseif (auth()->user()->can('pos.access'))
                                <li><a href="{{ route('pos.index') }}" class="home-nav-link">Punto de venta</a></li>
                            @endif
                        @else
                            <li><a href="{{ route('login') }}" class="home-nav-link">Acceso al panel</a></li>
                        @endauth
                    </ul>
                </div>

                @if ($showHomeLocation || $homePhoneUrl || $homeWhatsappUrl || $homeEmailUrl || $homePage->text('hours') !== '')
                    <div class="min-w-0">
                        <h2 class="font-display text-sm font-bold uppercase tracking-wide text-stone-900">Mostrador central</h2>
                        <ul class="mt-4 grid gap-3 text-sm">
                            @if ($showHomeLocation && $homePage->text('address') !== '')
                                <li class="flex items-start gap-2"><x-icon name="map-pin" class="mt-0.5 size-4 shrink-0 home-accent" /><span class="whitespace-pre-line">{{ $homePage->text('address') }}</span></li>
                            @endif
                            @if ($homePage->text('hours') !== '')
                                <li class="flex items-start gap-2"><x-icon name="clock" class="mt-0.5 size-4 shrink-0 home-accent" /><span class="whitespace-pre-line">{{ $homePage->text('hours') }}</span></li>
                            @endif
                            @if ($homePhoneUrl)
                                <li><a href="{{ $homePhoneUrl }}" class="home-nav-link inline-flex items-center gap-2"><x-icon name="phone" class="size-4 shrink-0 home-accent" />{{ $homePage->text('phone') }}</a></li>
                            @endif
                            @if ($homeEmailUrl)
                                <li><a href="{{ $homeEmailUrl }}" class="home-nav-link inline-flex min-w-0 items-start gap-2 break-words"><x-icon name="mail" class="mt-0.5 size-4 shrink-0 home-accent" />{{ $homePage->text('email') }}</a></li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>

            <div class="border-t border-[#e2d9cc] bg-[#f0ebe1]">
                <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs sm:flex-row sm:px-6 lg:px-8">
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
                    <p>Hecho para quienes ruedan.</p>
                </div>
            </div>
        </footer>
        @if (($homeWhatsappUrl && $homePage->enabled('whatsapp_bubble')) || ($homeEmailUrl && $homePage->enabled('email_bubble')))
            <div class="home-contact-bubbles fixed z-40 flex flex-col gap-3">
                @if ($homeWhatsappUrl && $homePage->enabled('whatsapp_bubble'))
                    <a href="{{ $homeWhatsappUrl }}" target="_blank" rel="noopener" class="home-whatsapp-bubble flex size-12 items-center justify-center rounded-full text-white shadow-lg" aria-label="Chatear en WhatsApp" title="Chatear en WhatsApp"><x-icon name="chat" class="size-6" /></a>
                @endif
                @if ($homeEmailUrl && $homePage->enabled('email_bubble'))
                    <a href="{{ $homeEmailUrl }}" class="home-email-bubble flex size-12 items-center justify-center rounded-full border border-orange-200 bg-white shadow-lg" aria-label="Escríbenos por correo" title="Escríbenos por correo"><x-icon name="mail" class="size-6" /></a>
                @endif
            </div>
        @endif
    @else
    <footer class="mt-16 bg-slate-950 text-slate-400">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="{{ config('app.name') }} · Inicio">
                        <span class="flex size-10 items-center justify-center rounded-lg bg-white/5 text-mz-accent-primary">
                            <x-icon name="moto" class="size-6" />
                        </span>
                        <span class="font-display text-2xl font-bold uppercase leading-none tracking-tight text-white">Moto<span class="text-mz-accent-primary">Zone</span></span>
                    </a>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed">Refacciones y accesorios para quienes viven sobre dos ruedas. Piezas originales y genéricas con respaldo de taller.</p>
                </div>

                <div>
                    <h2 class="font-display text-sm font-semibold uppercase tracking-[0.16em] text-white">Ayuda</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="tel:+525512345678" class="transition hover:text-mz-accent-primary/80">Envíos y entregas</a></li>
                        <li><a href="tel:+525512345678" class="transition hover:text-mz-accent-primary/80">Garantía de piezas</a></li>
                        <li><a href="tel:+525512345678" class="transition hover:text-mz-accent-primary/80">Devoluciones</a></li>
                        @auth
                            @if (auth()->user()->hasRole('admin'))
                                <li><a href="{{ route('admin.dashboard') }}" class="transition hover:text-mz-accent-primary/80">Panel de administración</a></li>
                            @elseif (auth()->user()->can('pos.access'))
                                <li><a href="{{ route('pos.index') }}" class="transition hover:text-mz-accent-primary/80">Punto de venta</a></li>
                            @endif
                        @else
                            <li><a href="{{ route('login') }}" class="transition hover:text-mz-accent-primary/80">Iniciar sesión</a></li>
                        @endauth
                    </ul>
                </div>

                <div>
                    <h2 class="font-display text-sm font-semibold uppercase tracking-[0.16em] text-white">Contacto</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li class="flex items-start gap-2">
                            <x-icon name="map-pin" class="mt-0.5 size-4 shrink-0 text-mz-accent-primary" />
                            Av. de los Talleres 245, Col. Industrial
                        </li>
                        <li class="flex items-center gap-2">
                            <x-icon name="phone" class="size-4 shrink-0 text-mz-accent-primary" />
                            <a href="tel:+525512345678" class="transition hover:text-mz-accent-primary/80">55 1234 5678</a>
                        </li>
                        <li class="flex items-center gap-2">
                            <x-icon name="clock" class="size-4 shrink-0 text-mz-accent-primary" />
                            Lun–Sáb · 9:00 a 19:00
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
                <p>Hecho para quienes ruedan.</p>
            </div>
        </div>
    </footer>

    <a href="https://wa.me/525512345678?text=Hola%2C%20busco%20una%20refacci%C3%B3n%20para%20mi%20moto" target="_blank" rel="noopener" class="fixed bottom-5 right-5 z-40 flex size-12 items-center justify-center rounded-full bg-mz-accent-primary text-white shadow-lg shadow-mz-accent-primary/25 transition hover:bg-mz-accent-hover focus:outline-none focus:ring-2 focus:ring-mz-accent-primary focus:ring-offset-2" aria-label="Escríbenos por WhatsApp">
        <x-icon name="chat" class="size-6" />
    </a>
    @endif
</body>
</html>
