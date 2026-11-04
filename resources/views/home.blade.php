@extends('layouts.site')

@section('title', 'Refacciones y accesorios para tu moto')
@section('description', 'Catálogo de refacciones para motos por categoría: motor, frenos, llantas, transmisión, eléctrico, suspensión y accesorios. Stock actualizado y envíos a todo México.')

@section('content')
    <section class="relative overflow-hidden bg-ink-950 text-white">
        <div class="absolute inset-0 blueprint-grid opacity-50" aria-hidden="true"></div>
        <div class="absolute -left-24 top-10 size-72 rounded-full bg-brand-700/10 blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:gap-16 lg:px-8 lg:py-24">
            <div>
                <p class="flex items-center gap-3 font-display text-sm font-medium uppercase tracking-[0.24em] text-brand-400">
                    <span class="h-px w-8 bg-brand-500" aria-hidden="true"></span>
                    Refaccionaria para motos
                </p>

                <h1 class="mt-5 font-display text-6xl font-bold uppercase leading-[0.88] tracking-tight sm:text-7xl lg:text-8xl">
                    Tu moto<br>
                    no se<br>
                    <span class="text-brand-500">detiene.</span>
                </h1>

                <p class="mt-6 max-w-md text-sm leading-relaxed text-slate-300 sm:text-base">
                    Refacciones originales y genéricas, accesorios y equipo de taller. Encuentra tu pieza por categoría o número de parte y vuelve a rodar hoy.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ route('catalog.index') }}" class="inline-flex h-12 items-center gap-2 rounded-lg bg-brand-700 px-6 text-sm font-semibold text-white transition hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-ink-950">
                        Ver catálogo
                        <x-icon name="arrow-right" class="size-4" />
                    </a>
                    <a href="#categorias" class="inline-flex h-12 items-center gap-2 rounded-lg border border-white/15 px-6 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40">
                        Explorar categorías
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex h-12 items-center gap-2 rounded-lg border border-brand-500/40 bg-brand-500/10 px-6 text-sm font-semibold text-brand-100 transition hover:bg-brand-500/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                        <x-icon name="login" class="size-4" />
                        Acceso al panel
                    </a>
                </div>

                <dl class="mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-white/10 pt-6">
                    <div>
                        <dt class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Piezas</dt>
                        <dd class="mt-1 font-display text-3xl font-semibold">{{ $stats['products'] }}+</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Categorías</dt>
                        <dd class="mt-1 font-display text-3xl font-semibold">{{ $stats['categories'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Marcas</dt>
                        <dd class="mt-1 font-display text-3xl font-semibold">{{ $stats['brands'] }}</dd>
                    </div>
                </dl>
            </div>

            <div class="relative">
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 backdrop-blur sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-display text-xs uppercase tracking-[0.2em] text-slate-400">Buscador de piezas</p>
                        <span class="rounded bg-brand-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-brand-300">Stock real</span>
                    </div>

                    <form action="{{ route('catalog.index') }}" method="GET" class="mt-4 space-y-3">
                        <div>
                            <label for="hero-search" class="text-xs text-slate-400">Nombre, marca o número de parte</label>
                            <div class="relative mt-1.5">
                                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                <input id="hero-search" type="search" name="search" placeholder="Ej. KIT-428H" class="h-12 w-full rounded-lg border border-white/10 bg-ink-900 pl-10 pr-3 text-sm text-white placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            </div>
                        </div>
                        <button type="submit" class="flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-brand-700 text-sm font-semibold text-white transition hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                            Buscar pieza
                            <x-icon name="arrow-right" class="size-4" />
                        </button>
                    </form>

                    <div class="mt-5 grid grid-cols-2 gap-2 border-t border-white/10 pt-5">
                        @foreach ($categories->take(4) as $category)
                            <a href="{{ route('catalog.index', ['categoria' => $category->slug]) }}" class="flex items-center gap-2 rounded-lg border border-white/5 px-3 py-2 text-xs text-slate-300 transition hover:border-brand-500/40 hover:text-white">
                                <x-icon :name="$category->icon()" class="size-4 text-brand-400" />
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 py-8 sm:px-6 lg:grid-cols-4 lg:px-8">
            @foreach ([
                ['icon' => 'truck', 'title' => 'Envío 24–48 h', 'text' => 'A todo México'],
                ['icon' => 'shield', 'title' => 'Piezas verificadas', 'text' => 'Originales y genéricas'],
                ['icon' => 'wrench', 'title' => 'Asesoría de taller', 'text' => 'Te ayudamos a elegir'],
                ['icon' => 'check', 'title' => 'Garantía real', 'text' => '30 días por defecto'],
            ] as $benefit)
                <div class="flex items-start gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                        <x-icon :name="$benefit['icon']" class="size-5" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-ink-900">{{ $benefit['title'] }}</p>
                        <p class="text-xs text-slate-500">{{ $benefit['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section id="categorias" class="mx-auto max-w-7xl scroll-mt-24 px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="font-display text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">Catálogo</p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase tracking-tight text-ink-900 sm:text-5xl">Compra por categoría</h2>
            </div>
            <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-900 transition hover:text-brand-700">
                Ver catálogo completo
                <x-icon name="arrow-right" class="size-4" />
            </a>
        </div>

        <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            @foreach ($categories as $category)
                @if ($loop->first)
                    <a href="{{ route('catalog.index', ['categoria' => $category->slug]) }}" class="group relative col-span-2 flex flex-col justify-between overflow-hidden rounded-xl bg-ink-900 p-6 text-white transition hover:bg-ink-800">
                        <div class="absolute inset-0 blueprint-grid opacity-50" aria-hidden="true"></div>
                        <div class="relative flex items-start justify-between">
                            <x-icon :name="$category->icon()" class="size-10 text-brand-500" />
                            <span class="rounded-full bg-white/10 px-2.5 py-1 text-[11px] font-medium text-slate-300">{{ $category->products_count }} piezas</span>
                        </div>
                        <div class="relative mt-10">
                            <p class="font-display text-2xl font-semibold uppercase tracking-tight sm:text-3xl">{{ $category->name }}</p>
                            <p class="mt-1 max-w-xs text-xs text-slate-400">{{ $category->description }}</p>
                            <span class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-brand-400">
                                Explorar
                                <x-icon name="arrow-right" class="size-3.5 transition group-hover:translate-x-1" />
                            </span>
                        </div>
                    </a>
                @else
                    <a href="{{ route('catalog.index', ['categoria' => $category->slug]) }}" class="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-slate-900/5">
                        <x-icon :name="$category->icon()" class="size-7 text-slate-500 transition group-hover:text-brand-700" />
                        <div class="mt-6">
                            <p class="text-sm font-semibold text-ink-900">{{ $category->name }}</p>
                            <p class="text-xs text-slate-500">{{ $category->products_count }} piezas</p>
                        </div>
                    </a>
                @endif
            @endforeach
        </div>
    </section>

    <section class="border-y border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="font-display text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">Selección</p>
                    <h2 class="mt-2 font-display text-4xl font-bold uppercase tracking-tight text-ink-900 sm:text-5xl">Piezas destacadas</h2>
                </div>
                <a href="{{ route('catalog.index', ['orden' => 'precio-asc']) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-900 transition hover:text-brand-700">
                    Ver todas
                    <x-icon name="arrow-right" class="size-4" />
                </a>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featuredProducts as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="grid gap-10 rounded-2xl border border-slate-200 bg-white p-6 sm:p-10 lg:grid-cols-[0.9fr_1.1fr] lg:p-12">
            <div>
                <p class="font-display text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">Por qué MotoZone</p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase leading-[0.95] tracking-tight text-ink-900 sm:text-5xl">Piezas que aguantan el camino.</h2>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-slate-600">No vendemos cajas: conocemos cada modelo que atendemos. Si dudas de compatibilidad, escríbenos y lo verificamos contigo.</p>
                <a href="https://wa.me/525512345678" target="_blank" rel="noopener" class="mt-6 inline-flex h-11 items-center gap-2 rounded-lg bg-ink-900 px-5 text-sm font-semibold text-white transition hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    <x-icon name="chat" class="size-4" />
                    Consultar por WhatsApp
                </a>
            </div>

            <ol class="divide-y divide-slate-100">
                @foreach ([
                    ['title' => 'Stock visible y actualizado', 'text' => 'Cada ficha muestra existencias reales. Sin sorpresas al confirmar tu pedido.'],
                    ['title' => 'Compatibilidad verificada', 'text' => 'Indicamos marca, SKU y modelo para que la pieza quede a la primera.'],
                    ['title' => 'Envío asegurado', 'text' => 'Empacamos cada refacción y damos seguimiento hasta que llega a tus manos.'],
                ] as $index => $item)
                    <li class="flex gap-5 py-5 first:pt-0 last:pb-0">
                        <span class="font-display text-2xl font-semibold text-brand-700">0{{ $index + 1 }}</span>
                        <div>
                            <p class="text-sm font-semibold text-ink-900">{{ $item['title'] }}</p>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $item['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <p class="text-center font-display text-xs font-semibold uppercase tracking-[0.24em] text-slate-600">Trabajamos con</p>
        <div class="mt-6 grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-4 lg:grid-cols-8">
            @foreach (['Honda', 'Yamaha', 'Bajaj', 'Italika', 'Suzuki', 'KTM', 'DID', 'NGK'] as $brand)
                <p class="text-center font-display text-lg font-semibold uppercase tracking-wide text-slate-600">{{ $brand }}</p>
            @endforeach
        </div>
    </section>
@endsection
