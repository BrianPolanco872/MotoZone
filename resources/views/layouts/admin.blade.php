@php
    $user = auth()->user();
    $isAdmin = $user?->hasRole('admin') ?? false;
    $initials = $user
        ? strtoupper(str($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''))
        : 'MZ';
    $roleLabel = $isAdmin ? 'Administrador' : ($user?->hasRole('empleado') ? 'Empleado' : 'Sin rol');
    $panelHome = $isAdmin
        ? route('admin.dashboard')
        : ($user?->can('admin.products.manage') ? route('admin.products.index') : ($user?->can('pos.access') ? route('pos.index') : route('home')));

    $adminNavigation = [];

    if ($isAdmin) {
        $adminNavigation[] = ['label' => 'Panel', 'icon' => 'home', 'route' => 'admin.dashboard'];
    }

    if ($user?->can('admin.products.manage')) {
        $adminNavigation[] = ['label' => 'Productos', 'icon' => 'inventory', 'route' => 'admin.products.index'];
    }

    if ($user?->can('pos.access')) {
        $adminNavigation[] = ['label' => 'Punto de venta', 'icon' => 'cart', 'route' => 'pos.index'];
    }

    if ($user?->can('pos.cash.manage')) {
        $adminNavigation[] = ['label' => 'Caja', 'icon' => 'banknotes', 'route' => 'pos.cash.index'];
    }

    if ($user?->can('pos.cut.manage')) {
        $adminNavigation[] = ['label' => 'Corte del día', 'icon' => 'report', 'route' => 'pos.cuts.index'];
    } elseif ($user?->can('pos.cut.own')) {
        $adminNavigation[] = ['label' => 'Mi corte', 'icon' => 'chart', 'route' => 'pos.cuts.mine'];
    }

    if ($user?->can('admin.users.manage')) {
        $adminNavigation[] = ['label' => 'Empleados', 'icon' => 'users', 'route' => 'admin.users.index'];
    }

    $adminNavigation[] = ['label' => 'Mi perfil', 'icon' => 'lock', 'route' => 'admin.profile.edit'];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-mz-bg-primary">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Panel') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-mz-text-primary antialiased">
    <div class="min-h-full">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-mz-bg-surface lg:flex">
            <a href="{{ $panelHome }}" class="flex h-16 items-center gap-3 px-6">
                <span class="flex size-9 items-center justify-center rounded-md bg-mz-accent-primary text-white">
                    <x-icon name="moto" class="size-5" />
                </span>
                <div>
                    <p class="font-display text-xl font-bold uppercase leading-none tracking-tight text-mz-text-primary">Moto<span class="text-mz-accent-primary">Zone</span></p>
                    <p class="mt-0.5 text-[11px] text-mz-text-muted">Panel administrativo</p>
                </div>
            </a>

            <nav class="mt-4 flex-1 space-y-0.5 overflow-y-auto px-3" aria-label="Navegación del panel">
                @foreach ($adminNavigation as $item)
                    @php
                        $isActive = isset($item['route']) && request()->routeIs($item['route']);
                    @endphp
                    @if (isset($item['route']))
                        <a href="{{ route($item['route']) }}"
                           @class([
                               'group flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition-colors',
                               'bg-white/10 text-mz-text-primary' => $isActive,
                               'text-mz-text-muted hover:bg-white/5 hover:text-mz-text-primary' => ! $isActive,
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

                <div class="my-3 border-t border-white/10" aria-hidden="true"></div>

                <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-mz-text-muted transition-colors hover:bg-white/5 hover:text-mz-text-primary">
                    <x-icon name="store" class="size-5" />
                    Ver tienda
                    <x-icon name="arrow-right" class="ml-auto size-4 text-mz-text-muted" />
                </a>
            </nav>

            <div class="m-3 rounded-lg bg-white/5 p-3">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-mz-accent-primary text-sm font-semibold text-white">{{ $initials }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-mz-text-primary">{{ $user?->name ?? 'Invitado' }}</p>
                        <p class="truncate text-xs text-mz-text-muted">{{ $user?->email }}</p>
                        <p class="truncate text-[11px] text-mz-accent-primary/70">{{ $roleLabel }}</p>
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

        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 border-b border-mz-border-light bg-mz-bg-surface/90 backdrop-blur">
                <div class="flex h-16 items-center gap-4 px-4 sm:px-6 lg:px-8">
                    <a href="{{ route('home') }}" class="flex size-9 items-center justify-center rounded-md bg-mz-accent-primary text-white lg:hidden" aria-label="Ir a la tienda">
                        <x-icon name="moto" class="size-5" />
                    </a>

                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-semibold tracking-tight text-mz-text-primary">@yield('title', 'Panel')</h1>
                        <p class="hidden text-xs text-mz-text-muted sm:block">@yield('subtitle', 'Administración de ' . config('app.name'))</p>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <a href="{{ route('home') }}" class="hidden h-9 items-center gap-2 rounded-md border border-mz-border-light px-3 text-sm font-medium text-mz-text-secondary transition-colors hover:bg-mz-bg-surface-2 sm:inline-flex">
                            <x-icon name="store" class="size-4" />
                            Ver tienda
                        </a>

                        @include('admin.partials.presence')

                        <span class="flex size-9 items-center justify-center rounded-full bg-mz-bg-surface-2 text-sm font-semibold text-mz-text-primary" title="{{ $user?->name }}">{{ $initials }}</span>

                        <form action="{{ route('logout') }}" method="POST" class="hidden sm:block">
                            @csrf
                            <button type="submit" class="x-button x-button-ghost h-9 text-sm">
                                <x-icon name="logout" class="size-4" />
                                Salir
                            </button>
                        </form>

                        <details class="relative lg:hidden" data-dismissable>
                            <summary class="flex size-9 cursor-pointer list-none items-center justify-center rounded-md border border-mz-border-light text-mz-text-secondary transition-colors hover:bg-mz-bg-surface-2 [&::-webkit-details-marker]:hidden" aria-label="Abrir menú del panel">
                                <x-icon name="menu" class="size-5" />
                            </summary>
                            <div class="absolute right-0 mt-2 w-56 rounded-lg border border-mz-border-light bg-mz-bg-surface p-2 shadow-xl shadow-black/40">
                                @foreach ($adminNavigation as $item)
                                    @if (isset($item['route']))
                                        <a href="{{ route($item['route']) }}" @class([
                                            'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                            'bg-mz-bg-surface-2 text-mz-text-primary' => request()->routeIs($item['route']),
                                            'text-mz-text-muted hover:bg-mz-bg-surface-2' => ! request()->routeIs($item['route']),
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

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <x-alert variant="success">
                        {{ session('status') }}
                    </x-alert>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
