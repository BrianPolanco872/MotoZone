@php
    $user = auth()->user();
    $isPos = request()->routeIs('pos.index');
    $usesPosTheme = $isPos || request()->routeIs(
        'pos.cuts.index', 'pos.cuts.mine', 'pos.cuts.history',
        'pos.cash.index', 'pos.abonos.index', 'pos.tickets.edit', 'pos.bitacora.index',
        'admin.users.index', 'admin.users.create', 'admin.users.edit',
        'admin.profile.edit',
        'admin.products.index', 'admin.products.create', 'admin.products.edit',
    );
    $isAdmin = $user?->hasRole('admin') ?? false;
    $initials = $user
        ? strtoupper(str($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''))
        : 'MZ';
    $roleLabel = $isAdmin ? 'Administrador' : ($user?->hasRole('empleado') ? 'Empleado' : 'Sin rol');
    $panelHome = $isAdmin
        ? route('admin.dashboard')
        : ($user?->can('admin.products.manage') ? route('admin.products.index') : ($user?->can('pos.access') ? route('pos.index') : route('home')));

    $adminNavigation = [];

    if ($user?->can('admin.products.manage')) {
        $adminNavigation[] = ['label' => 'Productos', 'icon' => 'inventory', 'route' => 'admin.products.index', 'active' => 'admin.products.*'];
    }

    if ($isAdmin) {
        $adminNavigation[] = ['label' => 'Editar inicio', 'icon' => 'home', 'route' => 'admin.home.edit', 'active' => 'admin.home.*'];
        $adminNavigation[] = ['label' => 'Operación', 'section' => true];
    }

    if ($user?->can('pos.access')) {
        $adminNavigation[] = ['label' => 'Punto de venta', 'icon' => 'cart', 'route' => 'pos.index'];
    }

    if ($user?->can('pos.cut.manage')) {
        $adminNavigation[] = ['label' => 'Corte del día', 'icon' => 'report', 'route' => 'pos.cuts.index', 'active' => 'pos.cuts.*'];
    } elseif ($user?->can('pos.cut.own')) {
        $adminNavigation[] = ['label' => 'Mi corte', 'icon' => 'chart', 'route' => 'pos.cuts.mine'];
    }

    if ($user?->can('pos.caja.view') || $user?->can('pos.cash.manage')) {
        $adminNavigation[] = ['label' => 'Caja', 'icon' => 'banknotes', 'route' => 'pos.cash.index'];
    }

    if ($user?->hasRole(['admin', 'empleado'])) {
        $adminNavigation[] = ['label' => 'Abonos / Créditos', 'icon' => 'banknotes', 'route' => 'pos.abonos.index'];
    }

    if ($user?->can('admin.users.manage')) {
        $adminNavigation[] = ['label' => 'Empleados', 'icon' => 'users', 'route' => 'admin.users.index', 'active' => 'admin.users.*'];
    }

    if ($isAdmin && $user?->can('pos.access')) {
        $adminNavigation[] = ['label' => 'Tickets', 'icon' => 'report', 'route' => 'pos.tickets.edit'];
    }

    if ($user?->can('pos.cut.manage') && $user?->can('pos.access')) {
        $adminNavigation[] = ['label' => 'Bitácora', 'icon' => 'report', 'route' => 'pos.bitacora.index', 'active' => 'pos.bitacora.*'];
    }

    $adminNavigation[] = ['label' => 'Mi perfil', 'icon' => 'lock', 'route' => 'admin.profile.edit'];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-mz-bg-primary" @if ($usesPosTheme) data-pos-theme="light" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Panel') · {{ config('app.name') }}</title>
    @if ($usesPosTheme)
        <script id="pos-theme-script">
            (() => {
                const root = document.documentElement;
                const storageKey = 'motozone.pos.theme';

                try {
                    if (sessionStorage.getItem(storageKey) === 'dark') {
                        root.dataset.posTheme = 'dark';
                    }
                } catch {
                    // El selector sigue disponible si el navegador bloquea el almacenamiento.
                }

                document.addEventListener('DOMContentLoaded', () => {
                    const toggle = document.getElementById('pos-theme-toggle');
                    const updateToggle = () => toggle.setAttribute('aria-pressed', String(root.dataset.posTheme === 'dark'));

                    updateToggle();
                    toggle.addEventListener('click', () => {
                        root.dataset.posTheme = root.dataset.posTheme === 'dark' ? 'light' : 'dark';
                        updateToggle();

                        try {
                            sessionStorage.setItem(storageKey, root.dataset.posTheme);
                        } catch {
                            // El cambio se conserva mientras la página siga abierta.
                        }
                    });
                });
            })();
        </script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-mz-text-primary antialiased">
    <div class="min-h-full">
        @if (! $isPos)
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-mz-bg-surface lg:flex pos-day:border-r pos-day:border-mz-border">
            <a href="{{ $panelHome }}" class="flex h-16 items-center gap-3 px-6">
                <span class="flex size-9 items-center justify-center rounded-md bg-mz-accent-primary text-white pos-night:bg-orange-700">
                    <x-icon name="moto" class="size-5" />
                </span>
                <div>
                    <p class="font-display text-xl font-bold uppercase leading-none tracking-tight text-mz-text-primary">Moto<span class="text-mz-accent-primary">Zone</span></p>
                    <p class="mt-0.5 text-[11px] text-mz-text-muted">Panel administrativo</p>
                </div>
            </a>

            <nav class="mt-4 flex-1 space-y-0.5 overflow-y-auto px-3" aria-label="Navegación del panel">
                @foreach ($adminNavigation as $item)
                    @if (isset($item['section']))
                        <p class="px-3 pb-2 pt-5 text-[10px] font-semibold uppercase tracking-widest text-mz-text-muted">{{ $item['label'] }}</p>
                        @continue
                    @endif
                    @php
                        $isActive = isset($item['route']) && request()->routeIs($item['active'] ?? $item['route']);
                    @endphp
                    @if (isset($item['route']))
                        <a href="{{ route($item['route']) }}"
                           @class([
                               'group flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition-colors',
                               'bg-white/10 text-mz-text-primary pos-day:bg-mz-bg-surface-2' => $isActive,
                               'text-mz-text-muted hover:bg-white/5 hover:text-mz-text-primary pos-day:hover:bg-mz-bg-surface-2' => ! $isActive,
                           ])
                           @if ($isActive) aria-current="page" @endif>
                            <x-icon :name="$item['icon']" class="size-5 {{ $isActive ? 'text-mz-accent-primary' : '' }}" />
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-mz-text-muted">
                            <x-icon :name="$item['icon']" class="size-5" />
                            {{ $item['label'] }}
                            <span class="ml-auto rounded bg-white/5 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-mz-text-muted">Pronto</span>
                        </span>
                    @endif
                @endforeach

                <div class="my-3 border-t border-white/10 pos-day:border-mz-border" aria-hidden="true"></div>

                <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-mz-text-muted transition-colors hover:bg-white/5 hover:text-mz-text-primary pos-day:hover:bg-mz-bg-surface-2">
                    <x-icon name="store" class="size-5" />
                    Ver tienda
                    <x-icon name="arrow-right" class="ml-auto size-4 text-mz-text-muted" />
                </a>
            </nav>

            <div class="m-3 rounded-lg bg-white/5 p-3 pos-day:bg-mz-bg-surface-2">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-mz-accent-primary text-sm font-semibold text-white pos-night:bg-orange-700">{{ $initials }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-mz-text-primary">{{ $user?->name ?? 'Invitado' }}</p>
                        <p class="truncate text-xs text-mz-text-muted">{{ $user?->email }}</p>
                        <p class="truncate text-[11px] text-mz-accent-primary/70 pos-day:text-mz-accent-primary">{{ $roleLabel }}</p>
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="x-button x-button-secondary w-full text-xs">
                        <x-icon name="logout" class="size-4" />
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>
        @endif

        <div @class(['lg:pl-64' => ! $isPos, 'flex min-h-dvh flex-col' => $isPos])>
            <header @class([
                'top-0 z-40 border-b',
                'relative border-mz-border bg-mz-bg-surface lg:sticky' => $usesPosTheme,
                'sticky border-mz-border-light bg-mz-bg-surface/90 backdrop-blur' => ! $usesPosTheme,
            ])>
                <div @class([
                    'flex items-center px-4 sm:px-6',
                    'min-h-14 flex-wrap justify-between gap-x-4 gap-y-2 py-2.5' => $usesPosTheme,
                    'h-16 gap-4' => ! $usesPosTheme,
                    'lg:px-8' => ! $isPos,
                ])>
                    <div class="flex min-w-0 items-center gap-3">
                        @if ($isPos)
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-orange-500/10 text-mz-accent-primary">
                                <x-icon name="store" class="size-5" />
                            </span>
                        @else
                            <a href="{{ route('home') }}" class="flex size-9 shrink-0 items-center justify-center rounded-md bg-mz-accent-primary text-white lg:hidden pos-night:bg-orange-700" aria-label="Ir a la tienda">
                                <x-icon name="moto" class="size-5" />
                            </a>
                        @endif
                        <div class="min-w-0">
                            <h1 @class([
                                'truncate tracking-tight text-mz-text-primary',
                                'text-base font-bold leading-tight' => $isPos,
                                'text-lg font-semibold' => ! $isPos,
                            ])>@yield('title', 'Panel')</h1>
                            <p class="hidden text-xs text-mz-text-muted sm:block">@yield('subtitle', 'Administración de ' . config('app.name'))</p>
                        </div>
                    </div>

                    <div @class(['flex items-center gap-2', 'flex-wrap' => $usesPosTheme, 'ml-auto' => ! $usesPosTheme])>
                        <a href="{{ route('home') }}" @class([
                            'h-9 items-center gap-2 border border-mz-border-light px-3 text-mz-text-secondary transition-colors hover:bg-mz-bg-surface-2 sm:inline-flex',
                            'inline-flex rounded-lg text-xs font-semibold' => $isPos,
                            'hidden rounded-md text-sm font-medium' => ! $isPos,
                        ]) @if ($isPos) aria-label="Ver tienda" @endif>
                            <x-icon name="store" class="size-4" />
                            <span @class(['hidden sm:inline' => $isPos])>Ver tienda</span>
                        </a>

                        @include('admin.partials.presence')

                        @if ($usesPosTheme)
                            <button id="pos-theme-toggle" type="button" aria-label="Modo noche" aria-pressed="false"
                                    class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-3 text-xs font-bold text-amber-950 transition-colors hover:bg-amber-100 pos-night:border-mz-border-light pos-night:bg-mz-bg-surface-2 pos-night:text-white pos-night:hover:bg-mz-border-light">
                                <span class="inline-flex items-center gap-1.5 pos-night:hidden" aria-hidden="true">
                                    <x-icon name="sun" class="size-4 text-amber-700" /> Modo Día
                                </span>
                                <span class="hidden items-center gap-1.5 pos-night:inline-flex" aria-hidden="true">
                                    <x-icon name="moon" class="size-4 text-amber-300" /> Modo Noche
                                </span>
                            </button>
                        @endif

                        <span @class([
                            'flex shrink-0 items-center justify-center rounded-full font-semibold',
                            'size-7 bg-slate-900 text-xs text-white ring-1 ring-mz-border-light pos-night:bg-slate-700' => $isPos,
                            'size-9 bg-mz-bg-surface-2 text-sm text-mz-text-primary' => ! $isPos,
                        ]) title="{{ $user?->name }}">{{ $initials }}</span>

                        <form action="{{ route('logout') }}" method="POST" class="hidden sm:block">
                            @csrf
                            <button type="submit" @class([
                                'x-button x-button-ghost h-9 text-sm' => ! $isPos,
                                'inline-flex h-9 items-center gap-1.5 rounded-lg border border-mz-border-light px-3 text-xs font-semibold text-mz-text-secondary transition-colors hover:bg-mz-error/10 hover:text-mz-error' => $isPos,
                            ])>
                                <x-icon name="logout" class="size-4" />
                                Salir
                            </button>
                        </form>

                        <details @class(['xs:relative' => $usesPosTheme, 'relative' => ! $usesPosTheme, 'lg:hidden' => ! $isPos]) data-dismissable>
                            <summary @class([
                                'flex h-9 cursor-pointer list-none items-center justify-center gap-2 border border-mz-border-light shadow-xs transition-colors [&::-webkit-details-marker]:hidden',
                                'w-9 rounded-md bg-mz-bg-surface-2 text-mz-text-secondary hover:border-mz-accent-primary/50 hover:text-mz-accent-primary' => ! $isPos,
                                'rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white hover:bg-slate-800 pos-night:bg-slate-800 pos-night:hover:bg-slate-700' => $isPos,
                            ]) aria-label="Abrir menú del panel">
                                <x-icon name="menu" class="size-5" />
                                @if ($isPos)
                                    <span class="hidden xs:inline">Módulos</span>
                                @endif
                            </summary>
                            <div @class([
                                'absolute top-full z-50 mt-2 max-h-[calc(100dvh-6rem)] w-64 overflow-y-auto rounded-lg border border-mz-border-light bg-mz-bg-surface p-2 shadow-xl',
                                'right-4 xs:right-0 pos-night:shadow-black/60' => $usesPosTheme,
                                'right-0 shadow-black/60' => ! $usesPosTheme,
                            ])>
                                @foreach ($adminNavigation as $item)
                                    @if (isset($item['section']))
                                        <p class="px-3 pb-1 pt-3 text-[10px] font-semibold uppercase tracking-widest text-mz-text-muted">{{ $item['label'] }}</p>
                                        @continue
                                    @endif
                                    @if (isset($item['route']))
                                        <a href="{{ route($item['route']) }}" @class([
                                            'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                            'bg-mz-bg-surface-2 text-mz-text-primary' => request()->routeIs($item['active'] ?? $item['route']),
                                            'text-mz-text-muted hover:bg-mz-bg-surface-2' => ! request()->routeIs($item['active'] ?? $item['route']),
                                        ])>
                                            <x-icon :name="$item['icon']" class="size-4 text-mz-text-muted" />
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                @endforeach
                                <a href="{{ route('home') }}" class="mt-2 flex items-center gap-3 border-t border-mz-border-light px-3 pt-3 pb-1 text-sm font-medium text-mz-text-secondary hover:text-mz-accent-primary">
                                    <x-icon name="store" class="size-4 text-mz-text-muted" />
                                    Ver tienda
                                </a>
                                <form action="{{ route('logout') }}" method="POST" class="border-t border-mz-border-light pt-1">
                                    @csrf
                                    <button type="submit" class="x-button x-button-ghost w-full text-sm">
                                        <x-icon name="logout" class="size-4" />
                                        Cerrar sesión
                                    </button>
                                </form>
                            </div>
                        </details>
                    </div>
                </div>
            </header>

            <main @class([
                'px-4 py-6 sm:px-6 lg:px-8' => ! $isPos,
                'mx-auto flex w-full max-w-[1920px] flex-1 flex-col px-4 py-4 sm:px-6' => $isPos,
            ])>
                @if (session('status'))
                    <x-alert variant="success">
                        {{ session('status') }}
                    </x-alert>
                @endif

                @yield('content')
            </main>

            @if ($isPos)
                @yield('pos-checkout')
            @endif
        </div>
    </div>
</body>
</html>
