<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-ink-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · {{ config('app.name') }}</title>
    <script id="pos-theme-reset">
        try {
            sessionStorage.removeItem('motozone.pos.theme');
        } catch {
            // Si el almacenamiento no está disponible, el panel arranca en modo Día.
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-ink-950 px-4 py-12 font-sans antialiased">
    <div class="w-full max-w-2xl">
        <div class="flex flex-col items-center text-center">
            <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ config('app.name') }} · Inicio">
                <span class="flex size-14 items-center justify-center rounded-xl bg-brand-500 text-white">
                    <x-icon name="moto" class="size-8" />
                </span>
                <span class="font-display text-4xl font-bold uppercase leading-none tracking-tight text-white">Moto<span class="text-brand-500">Zone</span></span>
            </a>
            <h1 class="mt-8 font-display text-4xl font-semibold uppercase leading-none tracking-tight text-white">Acceso al panel</h1>
            <p class="mt-3 text-base text-slate-400">Inicia sesión para administrar los productos o abrir el punto de venta.</p>
        </div>

        <div class="mt-10 rounded-2xl border border-stone-300 bg-[#F7F3EB] p-10 sm:p-12 shadow-xl shadow-black/40">
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 text-base text-rose-800" role="alert">
                    <ul class="list-disc space-y-1 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.store') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label for="name" class="block text-base font-medium text-slate-800">Nombre completo</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" class="mt-2 h-12 w-full rounded-lg border border-stone-300 bg-white px-4 text-base text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 outline-none" placeholder="Ana Ramírez">
                </div>

                <div>
                    <label for="email" class="block text-base font-medium text-slate-800">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="mt-2 h-12 w-full rounded-lg border border-stone-300 bg-white px-4 text-base text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 outline-none" placeholder="tu@motozone.mx">
                </div>

                <div>
                    <label for="password" class="block text-base font-medium text-slate-800">Contraseña</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-2 h-12 w-full rounded-lg border border-stone-300 bg-white px-4 text-base text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 outline-none" placeholder="••••••••">
                </div>

                <label class="flex items-center gap-3 text-base text-slate-800 font-medium">
                    <input type="checkbox" name="remember" value="1" class="size-5 rounded border-stone-500 text-brand-600 focus:ring-brand-500 bg-white">
                    Mantener mi sesión iniciada
                </label>

                <button type="submit" class="inline-flex h-12 w-full items-center justify-center gap-3 rounded-lg bg-brand-700 px-6 text-base font-semibold text-white transition hover:bg-brand-800 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    Entrar
                    <x-icon name="arrow-right" class="size-5" />
                </button>
            </form>
        </div>

        <p class="mt-8 text-center text-base text-slate-400">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 font-medium text-slate-400 hover:text-brand-600 hover:bg-brand-50/50 px-4 py-2 rounded-lg border border-stone-300 transition-colors duration-200">
                <x-icon name="arrow-right" class="size-5 rotate-180" />
                Volver a la tienda
            </a>
        </p>
</body>
</html>
