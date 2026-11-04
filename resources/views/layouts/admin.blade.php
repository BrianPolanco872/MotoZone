@php
    $user = auth()->user();
    $isAdmin = $user?->hasRole('admin') ?? false;
    $initials = $user
        ? strtoupper(str($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''))
        : 'MZ';
    $roleLabel = $isAdmin ? 'Administrador' : ($user?->hasRole('empleado') ? 'Empleado' : 'Sin rol');
    $panelHome = $isAdmin
        ? route('admin.dashboard')
        : ($user?->can('pos.access') ? route('pos.index') : route('home'));

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
    } elseif ($user?->can('pos.sales.day.own')) {
        $adminNavigation[] = ['label' => 'Mi corte', 'icon' => 'chart', 'route' => 'pos.cuts.mine'];
    }

    if ($user?->can('admin.users.manage')) {
        $adminNavigation[] = ['label' => 'Empleados', 'icon' => 'users', 'route' => 'admin.users.index'];
    }

    $adminNavigation[] = ['label' => 'Mi perfil', 'icon' => 'lock', 'route' => 'admin.profile.edit'];

    if ($isAdmin) {
        $adminNavigation[] = ['label' => 'Taller', 'icon' => 'wrench'];
        $adminNavigation[] = ['label' => 'Clientes', 'icon' => 'users'];
        $adminNavigation[] = ['label' => 'Reportes', 'icon' => 'chart'];
        $adminNavigation[] = ['label' => 'Configuración', 'icon' => 'cog'];
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Panel') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-900 antialiased">
    <div class="min-h-full">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-ink-950 lg:flex">
            <a href="{{ $panelHome }}" class="flex h-16 items-center gap-3 px-6">
                <span class="flex size-9 items-center justify-center rounded-lg bg-brand-500 text-white">
                    <x-icon name="moto" class="size-5" />
                </span>
                <div>
                    <p class="font-display text-xl font-bold uppercase leading-none tracking-tight text-white">Moto<span class="text-brand-500">Zone</span></p>
                    <p class="mt-0.5 text-[11px] text-slate-400">Panel administrativo</p>
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
                               'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                               'bg-white/10 text-white' => $isActive,
                               'text-slate-400 hover:bg-white/5 hover:text-white' => ! $isActive,
                           ])
                           @if ($isActive) aria-current="page" @endif>
                            <x-icon :name="$item['icon']" class="size-5 {{ $isActive ? 'text-brand-400' : '' }}" />
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-400">
                            <x-icon :name="$item['icon']" class="size-5" />
                            {{ $item['label'] }}
                            <span class="ml-auto rounded bg-white/5 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-slate-400">Pronto</span>
                        </span>
                    @endif
                @endforeach

                <div class="my-3 border-t border-white/10" aria-hidden="true"></div>

                <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-white/5 hover:text-white">
                    <x-icon name="store" class="size-5" />
                    Ver tienda
                    <x-icon name="arrow-right" class="ml-auto size-4 text-slate-500" />
                </a>
            </nav>

            <div class="m-3 rounded-xl bg-white/5 p-3">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-semibold text-white">{{ $initials }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-white">{{ $user?->name ?? 'Invitado' }}</p>
                        <p class="truncate text-xs text-slate-400">{{ $user?->email }}</p>
                        <p class="truncate text-[11px] text-brand-300">{{ $roleLabel }}</p>
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg border border-white/10 px-3 py-2 text-xs font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
                        <x-icon name="logout" class="size-4" />
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
                <div class="flex h-16 items-center gap-4 px-4 sm:px-6 lg:px-8">
                    <a href="{{ route('home') }}" class="flex size-9 items-center justify-center rounded-lg bg-brand-500 text-white lg:hidden" aria-label="Ir a la tienda">
                        <x-icon name="moto" class="size-5" />
                    </a>

                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-semibold tracking-tight text-slate-900">@yield('title', 'Panel')</h1>
                        <p class="hidden text-xs text-slate-500 sm:block">@yield('subtitle', 'Administración de ' . config('app.name'))</p>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <a href="{{ route('home') }}" class="hidden h-9 items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 sm:inline-flex">
                            <x-icon name="store" class="size-4" />
                            Ver tienda
                        </a>

                        <span class="flex size-9 items-center justify-center rounded-full bg-ink-900 text-sm font-semibold text-white" title="{{ $user?->name }}">{{ $initials }}</span>

                        <form action="{{ route('logout') }}" method="POST" class="hidden sm:block">
                            @csrf
                            <button type="submit" class="flex h-9 items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                <x-icon name="logout" class="size-4" />
                                Salir
                            </button>
                        </form>

                        <details class="relative lg:hidden" data-dismissable>
                            <summary class="flex size-9 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 [&::-webkit-details-marker]:hidden" aria-label="Abrir menú del panel">
                                <x-icon name="menu" class="size-5" />
                            </summary>
                            <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10">
                                @foreach ($adminNavigation as $item)
                                    @if (isset($item['route']))
                                        <a href="{{ route($item['route']) }}" @class([
                                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                            'bg-slate-100 text-ink-900' => request()->routeIs($item['route']),
                                            'text-slate-600 hover:bg-slate-50' => ! request()->routeIs($item['route']),
                                        ])>
                                            <x-icon :name="$item['icon']" class="size-4 text-slate-500" />
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                @endforeach
                                <a href="{{ route('home') }}" class="mt-2 flex items-center gap-3 border-t border-slate-100 px-3 pt-3 pb-1 text-sm font-medium text-slate-600 hover:text-brand-700">
                                    <x-icon name="store" class="size-4 text-slate-500" />
                                    Ver tienda
                                </a>
                                <form action="{{ route('logout') }}" method="POST" class="border-t border-slate-100 pt-1">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-brand-700">
                                        <x-icon name="logout" class="size-4 text-slate-500" />
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
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                        <x-icon name="check" class="mt-0.5 size-4 shrink-0" />
                        <p>{{ session('status') }}</p>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
