<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Refacciones y accesorios para tu moto') · {{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Refacciones, accesorios y equipo para tu moto. Catálogo por categorías con stock actualizado y envíos a todo México.')">
    <meta property="og:title" content="@yield('title', 'Refacciones y accesorios para tu moto') · {{ config('app.name') }}">
    <meta property="og:description" content="@yield('description', 'Refacciones, accesorios y equipo para tu moto. Catálogo por categorías con stock actualizado.')">
    <meta property="og:type" content="website">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-slate-900 antialiased">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-slate-900 focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white">Saltar al contenido</a>

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

                <details class="group relative" data-dismissable>
                    <summary class="flex cursor-pointer list-none items-center gap-1 transition hover:text-mz-accent-primary [&::-webkit-details-marker]:hidden">
                        Categorías
                        <x-icon name="chevron-down" class="size-4 transition group-open:rotate-180" />
                    </summary>
                    <div class="absolute left-0 top-full mt-3 w-72 rounded-lg border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/5">
                        @foreach ($navCategories as $category)
                            <a href="{{ route('catalog.index', ['categoria' => $category->slug]) }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-mz-accent-primary">
                                <x-icon :name="$category->icon()" class="size-4 text-slate-500" />
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                </details>

                <a href="{{ route('catalog.index') }}" @class(['transition hover:text-mz-accent-primary', 'text-slate-900' => request()->routeIs('catalog.*')]) @if (request()->routeIs('catalog.*')) aria-current="page" @endif>Catálogo</a>
                <a href="tel:+525512345678" class="transition hover:text-mz-accent-primary">Contacto</a>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <form action="{{ route('catalog.index') }}" method="GET" role="search" class="hidden md:block">
                    <label class="relative block">
                        <span class="sr-only">Buscar refacciones por nombre, marca o SKU</span>
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-500" />
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Buscar pieza o SKU" class="h-10 w-52 rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-500 focus:border-mz-accent-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/20 xl:w-60">
                    </label>
                </form>

                <a href="{{ route('catalog.index') }}" class="hidden h-10 items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:inline-flex">
                    <x-icon name="cart" class="size-4" />
                    Cotizar
                </a>

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
                        <form action="{{ route('catalog.index') }}" method="GET" role="search" class="mb-2">
                            <label class="relative block">
                                <span class="sr-only">Buscar refacciones</span>
                                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-500" />
                                <input type="search" name="search" placeholder="Buscar pieza o SKU" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm focus:border-mz-accent-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/20">
                            </label>
                        </form>

                        <a href="{{ route('catalog.index') }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Catálogo completo <x-icon name="arrow-right" class="size-4 text-slate-500" /></a>
                        @foreach ($navCategories as $category)
                            <a href="{{ route('catalog.index', ['categoria' => $category->slug]) }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                                <x-icon :name="$category->icon()" class="size-4 text-slate-500" />
                                {{ $category->name }}
                            </a>
                        @endforeach
                        <a href="tel:+525512345678" class="mt-2 flex items-center gap-2 border-t border-slate-100 px-3 pt-3 text-sm text-slate-600">
                            <x-icon name="phone" class="size-4 text-slate-500" />
                            55 1234 5678
                        </a>
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main id="contenido" class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-16 bg-slate-950 text-slate-400">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
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
                    <h2 class="font-display text-sm font-semibold uppercase tracking-[0.16em] text-white">Catálogo</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($navCategories->take(5) as $category)
                            <li>
                                <a href="{{ route('catalog.index', ['categoria' => $category->slug]) }}" class="transition hover:text-mz-accent-primary/80">{{ $category->name }}</a>
                            </li>
                        @endforeach
                        <li>
                            <a href="{{ route('catalog.index') }}" class="transition hover:text-mz-accent-primary/80">Ver todo</a>
                        </li>
                    </ul>
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
</body>
</html>
