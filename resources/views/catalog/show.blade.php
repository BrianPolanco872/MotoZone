@extends('layouts.site')

@section('title', $product->name . ' · ' . $product->brand)
@section('description', $product->description)

@section('content')
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <nav class="flex flex-wrap items-center gap-2 text-xs text-slate-500" aria-label="Ruta de navegación">
                <a href="{{ route('home') }}" class="transition hover:text-brand-700">Inicio</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('catalog.index') }}" class="transition hover:text-brand-700">Catálogo</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('catalog.index', ['categoria' => $product->category->slug]) }}" class="transition hover:text-brand-700">{{ $product->category->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-700">{{ $product->name }}</span>
            </nav>
        </div>
    </div>

    <article class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-14">
            <div class="group">
                <x-product-visual :product="$product" class="aspect-square rounded-2xl" icon-class="size-28 sm:size-36" />
            </div>

            <div>
                <a href="{{ route('catalog.index', ['categoria' => $product->category->slug]) }}" class="inline-flex items-center gap-1.5 font-display text-xs font-semibold uppercase tracking-[0.2em] text-brand-700 transition hover:text-brand-800">
                    <x-icon :name="$product->category->icon()" class="size-4" />
                    {{ $product->category->name }}
                </a>

                <h1 class="mt-3 font-display text-4xl font-bold uppercase leading-[0.95] tracking-tight text-ink-900 sm:text-5xl">{{ $product->name }}</h1>

                <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                    <span class="font-display text-sm uppercase tracking-[0.16em] text-slate-500">{{ $product->brand }}</span>
                    <span class="h-4 w-px bg-slate-200" aria-hidden="true"></span>
                    <span class="text-slate-500">SKU {{ $product->sku }}</span>
                    <x-stock-badge :product="$product" />
                </div>

                <div class="mt-6 flex flex-wrap items-end gap-3 border-y border-slate-200 py-5">
                    <p class="font-display text-5xl font-semibold leading-none text-ink-900">${{ number_format((float) $product->price, 2) }}</p>
                    <p class="pb-1 text-xs text-slate-500">MXN · IVA incluido</p>
                </div>

                <p class="mt-5 text-sm leading-relaxed text-slate-600">{{ $product->description }}</p>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    @if ($product->isInStock())
                        <a href="https://wa.me/525512345678?text={{ urlencode("Hola, me interesa {$product->name} ({$product->sku})") }}" target="_blank" rel="noopener" class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-lg bg-brand-700 px-6 text-sm font-semibold text-white transition hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                            <x-icon name="chat" class="size-5" />
                            Consultar disponibilidad
                        </a>
                    @else
                        <span class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-lg bg-slate-200 px-6 text-sm font-semibold text-slate-500">
                            <x-icon name="alert" class="size-5" />
                            Sin existencias
                        </span>
                    @endif
                    <a href="{{ route('catalog.index', ['categoria' => $product->category->slug]) }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-lg border border-slate-300 px-6 text-sm font-semibold text-ink-900 transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">
                        Seguir comprando
                    </a>
                </div>

                @unless ($product->isInStock())
                    <p class="mt-3 text-xs text-slate-500">Déjanos tus datos por WhatsApp y te avisamos cuando llegue el nuevo embarque.</p>
                @endunless

                <dl class="mt-8 grid grid-cols-2 gap-x-6 gap-y-4 rounded-xl border border-slate-200 bg-white p-5 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-[0.12em] text-slate-500">Número de parte</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $product->sku }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.12em] text-slate-500">Marca</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $product->brand }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.12em] text-slate-500">Categoría</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $product->category->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.12em] text-slate-500">Existencias</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $product->stock }} {{ $product->stock === 1 ? 'pieza' : 'piezas' }}</dd>
                    </div>
                </dl>

                <ul class="mt-6 space-y-2 text-xs text-slate-500">
                    <li class="flex items-center gap-2">
                        <x-icon name="truck" class="size-4 text-brand-700" />
                        Envío asegurado en 24–48 h a todo México.
                    </li>
                    <li class="flex items-center gap-2">
                        <x-icon name="shield" class="size-4 text-brand-700" />
                        Garantía de 30 días por defecto de fabricación.
                    </li>
                    <li class="flex items-center gap-2">
                        <x-icon name="wrench" class="size-4 text-brand-700" />
                        Si dudas de compatibilidad, verificamos tu modelo antes de enviar.
                    </li>
                </ul>
            </div>
        </div>
    </article>

    @if ($relatedProducts->isNotEmpty())
        <section class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="font-display text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">También te puede servir</p>
                        <h2 class="mt-2 font-display text-3xl font-bold uppercase tracking-tight text-ink-900 sm:text-4xl">Más de {{ $product->category->name }}</h2>
                    </div>
                    <a href="{{ route('catalog.index', ['categoria' => $product->category->slug]) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-900 transition hover:text-brand-700">
                        Ver categoría
                        <x-icon name="arrow-right" class="size-4" />
                    </a>
                </div>

                <div class="mt-7 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($relatedProducts as $related)
                        <x-product-card :product="$related" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
