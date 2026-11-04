<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-ink-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-ink-950 px-4 py-10 font-sans text-slate-900 antialiased">
    <div class="w-full max-w-md">
        <div class="flex flex-col items-center text-center">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="{{ config('app.name') }} · Inicio">
                <span class="flex size-11 items-center justify-center rounded-lg bg-brand-500 text-white">
                    <x-icon name="moto" class="size-6" />
                </span>
                <span class="font-display text-3xl font-bold uppercase leading-none tracking-tight text-white">Moto<span class="text-brand-500">Zone</span></span>
            </a>
            <h1 class="mt-6 font-display text-2xl font-semibold uppercase tracking-tight text-white">Acceso al panel</h1>
            <p class="mt-2 text-sm text-slate-400">Inicia sesión para administrar el catálogo o abrir el punto de venta.</p>
        </div>

        <div class="mt-8 rounded-2xl border border-white/10 bg-white p-6 shadow-2xl shadow-black/30 sm:p-8">
            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                    <ul class="list-disc space-y-1 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.store') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Nombre completo</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="Ana Ramírez">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="tu@motozone.mx">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Contraseña</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="••••••••">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Mantener mi sesión iniciada
                </label>

                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    Entrar
                    <x-icon name="arrow-right" class="size-4" />
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-slate-500">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 font-medium text-slate-300 transition hover:text-white">
                <x-icon name="arrow-right" class="size-4 rotate-180" />
                Volver a la tienda
            </a>
        </p>
    </div>
</body>
</html>
