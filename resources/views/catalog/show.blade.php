@extends('layouts.site')

@section('title', $product->name . ($product->brand ? ' · ' . $product->brand : ''))
@section('description', $product->description ?: $product->name)

@php
    $homePage = \App\Models\HomePage::current();
    $rawWaPhone = $homePage->text('whatsapp') ?: $homePage->text('phone');
    $normalizedWaPhone = \App\Models\HomePage::normalizePhoneNumber($rawWaPhone) ?? '525512345678';
    $cleanWaPhone = ltrim($normalizedWaPhone, '+');

    $waMessage = "Hola, me interesa consultar el producto: {$product->name}" . ($product->sku ? " (SKU: {$product->sku})" : "");
    $whatsappUrl = "https://wa.me/{$cleanWaPhone}?text=" . rawurlencode($waMessage);

    $storeEmail = trim($homePage->text('email')) ?: 'contacto@motozone.com';
    $emailSubject = "Consulta: {$product->name}" . ($product->sku ? " ({$product->sku})" : "");
    $emailBody = "Hola,\n\nMe gustaría consultar la disponibilidad y detalles de la siguiente refacción:\n\n- Producto: {$product->name}\n"
        . ($product->sku ? "- SKU / No. de parte: {$product->sku}\n" : "")
        . ($product->brand ? "- Marca: {$product->brand}\n" : "")
        . "- Precio: $" . number_format((float) $product->price, 2) . "\n\nSaludos.";
    $mailUrl = "mailto:{$storeEmail}?subject=" . rawurlencode($emailSubject) . "&body=" . rawurlencode($emailBody);

    $specs = array_filter([
        'Número de parte / SKU' => $product->sku,
        'Marca' => $product->brand,
        'Categoría' => $product->category?->name,
        'Código de barras' => $product->barcode,
        'Código alterno' => $product->barcode2,
    ], fn ($val) => ! is_null($val) && trim((string) $val) !== '');
@endphp

@section('content')
    <div class="border-b border-slate-200/80 bg-slate-50/70">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <nav class="flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500" aria-label="Ruta de navegación">
                <a href="{{ route('home') }}" class="transition hover:text-orange-600">Inicio</a>
                <span aria-hidden="true" class="text-slate-300">/</span>
                @if ($product->category)
                    <span class="text-slate-600">{{ $product->category->name }}</span>
                    <span aria-hidden="true" class="text-slate-300">/</span>
                @endif
                <span class="truncate font-semibold text-slate-900" aria-current="page">{{ $product->name }}</span>
            </nav>
        </div>
    </div>

    <article class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-12 items-start">
            {{-- Columna izquierda: Imagen limpia del producto --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-6 lg:p-8">
                <x-product-visual :product="$product" class="aspect-square w-full rounded-xl" icon-class="size-28 sm:size-36" />
            </div>

            {{-- Columna derecha: Información esencial y contacto directo --}}
            <div>
                {{-- Encabezado: Marca, SKU y Badge de disponibilidad limpia --}}
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5 text-xs">
                        @if (filled($product->brand))
                            <span class="font-display font-bold uppercase tracking-[0.16em] text-orange-600">{{ $product->brand }}</span>
                            @if (filled($product->sku))
                                <span class="h-3.5 w-px bg-slate-200" aria-hidden="true"></span>
                            @endif
                        @endif
                        @if (filled($product->sku))
                            <span class="font-mono text-slate-500">SKU {{ $product->sku }}</span>
                        @endif
                    </div>

                    @if ($product->isInStock())
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                            <span class="size-2 rounded-full bg-emerald-500"></span>
                            Disponible
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-inset ring-slate-400/20">
                            <span class="size-2 rounded-full bg-slate-400"></span>
                            Agotado / No disponible
                        </span>
                    @endif
                </div>

                {{-- Nombre del producto --}}
                <h1 class="mt-3 font-display text-3xl font-extrabold uppercase leading-tight tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                    {{ $product->name }}
                </h1>

                {{-- Precio limpio (sin leyendas de relleno) --}}
                <div class="mt-5 border-y border-slate-200/80 py-4">
                    <p class="font-display text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">
                        ${{ number_format((float) $product->price, 2) }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">{{ match ($product->saleMode()) { \App\Models\Product::MODE_BULK => 'A granel · precio por unidad de medida', \App\Models\Product::MODE_KIT => 'Precio por paquete (kit)', default => 'Precio por unidad/pieza' } }}</p>
                </div>

                {{-- Descripción si existe --}}
                @if (filled($product->description))
                    <div class="mt-5 text-sm leading-relaxed text-slate-600">
                        <p>{{ $product->description }}</p>
                    </div>
                @endif

                {{-- Botones de contacto directo (WhatsApp y Correo) --}}
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-12 flex-1 items-center justify-center gap-2.5 rounded-xl bg-[#25D366] px-6 text-sm font-bold text-white shadow-md shadow-[#25D366]/20 transition hover:bg-[#20bd5a] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#25D366] focus-visible:ring-offset-2">
                        <svg class="size-5 shrink-0 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                        </svg>
                        <span>Consultar por WhatsApp</span>
                    </a>

                    <a href="{{ $mailUrl }}" class="inline-flex h-12 flex-1 items-center justify-center gap-2.5 rounded-xl border border-slate-300 bg-white px-6 text-sm font-bold text-slate-800 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2">
                        <x-icon name="mail" class="size-5 shrink-0 text-slate-600" />
                        <span>Consultar por Correo</span>
                    </a>
                </div>

                {{-- Ficha técnica condicional: solo campos con valor registrado --}}
                @if (count($specs) > 0)
                    <div class="mt-8">
                        <h2 class="font-display text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Ficha técnica</h2>
                        <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 rounded-2xl border border-slate-200/80 bg-slate-50 p-4 text-sm">
                            @foreach ($specs as $label => $value)
                                <div class="rounded-xl border border-slate-200/70 bg-white p-3 shadow-xs">
                                    <dt class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $label }}</dt>
                                    <dd class="mt-1 font-mono text-sm font-semibold text-slate-900 break-all">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>
        </div>
    </article>

    @if ($relatedProducts->isNotEmpty())
        <section class="border-t border-slate-200/80 bg-slate-50">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="font-display text-xs font-semibold uppercase tracking-[0.24em] text-orange-600">También te puede servir</p>
                        <h2 class="mt-2 font-display text-3xl font-extrabold uppercase tracking-tight text-slate-900 sm:text-4xl">Otras piezas</h2>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($relatedProducts as $related)
                        <x-product-card :product="$related" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
