<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Editar inicio · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="home-cms min-h-dvh" data-home-editor>
    <a href="#editor-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50">Saltar al editor</a>
    <header class="cms-header sticky top-0 z-30 backdrop-blur-xl">
        <div class="mx-auto flex min-h-16 max-w-5xl items-center justify-between gap-2 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="cms-icon-button" aria-label="Volver al panel"><x-icon name="arrow-uturn-left" /></a>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-[10px] font-bold tracking-wider uppercase">
                        <span class="text-mz-text-muted">MotoZone CMS</span>
                        <span class="cms-badge text-mz-success"><span class="size-1.5 rounded-full bg-current"></span> Sesión activa</span>
                    </div>
                    <h1 class="cms-heading truncate text-base">Editar Inicio</h1>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('admin.home.preview') }}" target="_blank" rel="noopener" class="cms-secondary inline-flex min-h-11 items-center gap-2 rounded-lg px-3 text-xs font-semibold">
                    <x-icon name="store" class="size-5" /> <span class="hidden xs:inline">Vista previa</span><span class="sr-only">, abre otra pestaña con los cambios guardados</span>
                </a>
                <a href="{{ route('admin.profile.edit') }}" class="flex size-9 items-center justify-center rounded-full bg-mz-accent-primary text-sm font-bold text-white" aria-label="Perfil de {{ auth()->user()->name }}">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</a>
            </div>
        </div>
    </header>

    <main id="editor-content" class="mx-auto max-w-5xl space-y-6 px-4 pt-5 pb-40 sm:px-6" tabindex="-1">
        @yield('content')
    </main>

    <footer class="cms-footer fixed inset-x-0 bottom-0 z-30 backdrop-blur-xl" data-editor-toolbar hidden>
        <div class="mx-auto max-w-5xl px-4 py-3 sm:px-6">
            <p class="mb-2 truncate text-xs text-mz-text-muted" role="status" aria-live="polite" data-editor-status>Selecciona un formulario para guardar.</p>
            <div class="flex gap-3">
                <button type="button" class="cms-secondary inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-lg font-semibold" data-editor-discard><x-icon name="x" class="size-4 text-mz-error" /> Descartar</button>
                <button type="button" class="mz-button mz-button-primary min-h-11 flex-[1.4]" data-editor-save><x-icon name="check" class="size-4" /> Guardar cambios</button>
            </div>
            <p class="mt-2 text-center text-[11px] text-mz-text-muted">Cada formulario se publica por separado. La vista previa muestra lo guardado.</p>
        </div>
    </footer>
</body>
</html>
