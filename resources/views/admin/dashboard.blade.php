@extends('layouts.admin')

@section('title', 'Resumen del negocio')
@section('subtitle', 'Ventas de mostrador, inventario y portada pública')

@section('content')
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-mz-accent-primary">MotoZone · Control diario</p>
            <h2 class="mt-1 text-xl font-semibold tracking-tight text-mz-text-primary sm:text-2xl">Lo importante, en un solo lugar.</h2>
            <p class="mt-2 flex items-center gap-1.5 text-xs text-mz-text-secondary">
                <x-icon name="clock" class="size-3.5" />
                Consulta del <time datetime="{{ $generatedAt->toIso8601String() }}">{{ $generatedAt->format('d/m/Y · H:i') }}</time>
                · {{ config('app.timezone') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('home') }}" class="x-button x-button-secondary size-sm">
                <x-icon name="home" class="size-4" /> Ver home
            </a>
            <a href="{{ route('pos.index') }}" class="x-button x-button-primary size-sm">
                <x-icon name="cart" class="size-4" /> Abrir POS
            </a>
        </div>
    </div>

    @php
        $metrics = [
            ['label' => 'Vendido hoy', 'value' => '$'.number_format((float) $salesSummary['paid_total'], 2), 'note' => 'Ventas cobradas · todos los métodos', 'icon' => 'banknotes', 'url' => route('pos.sales.day', ['date' => $generatedAt->toDateString()]), 'tone' => 'neutral'],
            ['label' => 'Tickets cobrados', 'value' => number_format($salesSummary['tickets']), 'note' => 'Operaciones cobradas hoy', 'icon' => 'receipt', 'url' => route('pos.sales.day', ['date' => $generatedAt->toDateString()]), 'tone' => 'neutral'],
            ['label' => 'Productos agotados', 'value' => number_format($inventoryCounts['out_of_stock']), 'note' => 'Activos con inventario controlado', 'icon' => 'inventory', 'url' => route('admin.products.index', ['stock' => 'agotado']), 'tone' => $inventoryCounts['out_of_stock'] > 0 ? 'danger' : 'neutral'],
            ['label' => 'Productos con existencias bajas', 'value' => number_format($inventoryCounts['low_stock']), 'note' => 'Según el mínimo de cada producto', 'icon' => 'alert', 'url' => route('admin.products.index', ['stock' => 'bajo']), 'tone' => $inventoryCounts['low_stock'] > 0 ? 'warning' : 'neutral'],
        ];
    @endphp

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($metrics as $metric)
            <a href="{{ $metric['url'] }}" class="mz-card group p-5 transition-colors hover:border-mz-accent-primary focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-mz-accent-primary">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-mz-text-secondary">{{ $metric['label'] }}</p>
                    <span @class([
                        'flex size-9 shrink-0 items-center justify-center rounded-lg',
                        'bg-mz-bg-surface-2 text-mz-text-secondary' => $metric['tone'] === 'neutral',
                        'bg-mz-error/10 text-mz-error' => $metric['tone'] === 'danger',
                        'bg-mz-warning/10 text-mz-warning' => $metric['tone'] === 'warning',
                    ])>
                        <x-icon :name="$metric['icon']" class="size-4" />
                    </span>
                </div>
                <p class="mt-4 break-words font-display text-3xl font-semibold tracking-tight tabular-nums text-mz-text-primary">{{ $metric['value'] }}</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="text-xs text-mz-text-muted">{{ $metric['note'] }}</p>
                    <x-icon name="arrow-right" class="size-3.5 shrink-0 text-mz-text-muted group-hover:text-mz-accent-primary" />
                </div>
            </a>
        @endforeach
    </div>

    <div class="mt-3 flex flex-col gap-2 text-xs text-mz-text-secondary sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <p>El importe vendido no representa efectivo en caja ni utilidad.</p>
        <a href="{{ route('pos.returns.index', ['status' => 'approved']) }}" class="inline-flex items-center gap-1.5 font-medium hover:text-mz-accent-primary" aria-label="Consultar historial de devoluciones aprobadas">
            <x-icon name="arrow-uturn-left" class="size-3.5" />
            Devoluciones aprobadas hoy: ${{ number_format((float) $salesSummary['refunds_total'], 2) }} · {{ $salesSummary['refunds_count'] }} solicitudes
            <x-icon name="arrow-right" class="size-3.5" />
        </a>
        <p class="w-full text-mz-text-muted">Las devoluciones se muestran por separado; pueden corresponder a ventas de días anteriores.</p>
    </div>

    <div class="mt-6 grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        <div class="flex min-w-0 flex-col gap-6 xl:col-span-2">
            <section class="mz-card overflow-hidden" aria-labelledby="restock-title">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-mz-border-light p-5">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-widest text-mz-text-muted">Operación · Inventario</p>
                        <h2 id="restock-title" class="mt-1 text-base font-semibold text-mz-text-primary">Reposición prioritaria</h2>
                        <p class="mt-1 text-xs text-mz-text-secondary">Agotados primero. Hasta 6 refacciones que necesitan atención.</p>
                    </div>
                    <a href="{{ route('admin.products.index', ['stock' => 'bajo']) }}" class="x-button x-button-secondary size-xs">Ver existencias bajas <x-icon name="arrow-right" class="size-3.5" /></a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <caption class="sr-only">Productos activos con inventario controlado, agotados o en su mínimo de existencia.</caption>
                        <thead class="bg-mz-bg-surface-2 text-left text-xs text-mz-text-muted">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-medium">Refacción / SKU</th>
                                <th scope="col" class="px-3 py-3 text-right font-medium">Existencias</th>
                                <th scope="col" class="px-3 py-3 text-right font-medium">Mínimo</th>
                                <th scope="col" class="px-5 py-3 font-medium">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-mz-border-light">
                            @forelse ($lowStockProducts as $product)
                                <tr class="transition-colors hover:bg-mz-bg-surface-2">
                                    <td class="px-5 py-4">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-mz-text-primary hover:text-mz-accent-primary">{{ $product->name }}</a>
                                        <p class="mt-1 font-mono text-xs text-mz-text-muted">{{ $product->sku }}</p>
                                    </td>
                                    <td class="px-3 py-4 text-right font-semibold tabular-nums text-mz-text-primary">{{ $product->stock }}</td>
                                    <td class="px-3 py-4 text-right tabular-nums text-mz-text-secondary">{{ $product->lowStockThreshold() }}</td>
                                    <td class="whitespace-nowrap px-5 py-4"><x-stock-badge :product="$product" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center">
                                        <x-icon name="check" class="mx-auto size-6 text-mz-text-muted" />
                                        <p class="mt-3 text-sm font-medium text-mz-text-primary">Sin reposiciones pendientes</p>
                                        <p class="mt-1 text-xs text-mz-text-secondary">Ningún producto activo controlado está agotado o en su mínimo.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="mz-card overflow-hidden" aria-labelledby="bestsellers-title">
                <div class="border-b border-mz-border-light p-5">
                    <p class="text-xs font-medium uppercase tracking-widest text-mz-text-muted">Operación · Demanda</p>
                    <h2 id="bestsellers-title" class="mt-1 text-base font-semibold text-mz-text-primary">Más vendidos</h2>
                    <p class="mt-1 text-xs text-mz-text-secondary">Últimos 30 días · {{ $rankingStart->format('d/m') }} al {{ $generatedAt->format('d/m/Y') }}</p>
                    <p class="mt-1 text-xs text-mz-text-muted">Refacciones activas · unidades brutas cobradas, sin descontar devoluciones.</p>
                </div>
                <ol class="divide-y divide-mz-border-light">
                    @forelse ($topProducts as $product)
                        <li class="flex items-center gap-3 px-5 py-4">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-mz-bg-surface-2 text-xs font-semibold tabular-nums text-mz-text-muted">{{ $loop->iteration }}</span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.products.edit', $product) }}" class="block truncate text-sm font-medium text-mz-text-primary hover:text-mz-accent-primary">{{ $product->name }}</a>
                                <p class="mt-1 font-mono text-xs text-mz-text-muted">{{ $product->sku }}</p>
                                <div class="mt-1.5"><x-stock-badge :product="$product" /></div>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="font-display text-xl font-semibold tabular-nums text-mz-text-primary">{{ number_format((int) $product->units_sold) }}</p>
                                <p class="text-xs text-mz-text-muted">unidades</p>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-mz-text-secondary">Sin ventas de refacciones activas en este periodo.</li>
                    @endforelse
                </ol>
            </section>
        </div>

        <div class="flex min-w-0 flex-col gap-6">
            <section class="mz-card p-5" aria-labelledby="catalog-health-title">
                <p class="text-xs font-medium uppercase tracking-widest text-mz-text-muted">Público · Calidad</p>
                <h2 id="catalog-health-title" class="mt-1 text-base font-semibold text-mz-text-primary">Productos por completar</h2>
                <p class="mt-1 text-xs text-mz-text-secondary">{{ number_format($catalogCounts['active']) }} productos publicados. Revisa lo que consulta tu cliente.</p>
                <div class="mt-5 flex flex-col gap-3">
                    <a href="{{ route('admin.products.index', ['revision' => 'sin-foto']) }}" class="flex items-center gap-3 rounded-lg border border-mz-border-light p-3 transition-colors hover:border-mz-accent-primary">
                        <x-icon name="inventory" class="size-4 shrink-0 text-mz-text-muted" />
                        <span class="flex-1 text-sm text-mz-text-secondary">Sin foto capturada</span>
                        <span class="font-semibold tabular-nums text-mz-text-primary">{{ number_format($catalogCounts['missing_photo']) }}</span>
                        <x-icon name="arrow-right" class="size-3.5 text-mz-text-muted" />
                    </a>
                    <a href="{{ route('admin.products.index', ['revision' => 'sin-precio']) }}" class="flex items-center gap-3 rounded-lg border border-mz-border-light p-3 transition-colors hover:border-mz-accent-primary">
                        <x-icon name="tag" class="size-4 shrink-0 text-mz-text-muted" />
                        <span class="flex-1 text-sm text-mz-text-secondary">Sin precio válido</span>
                        <span class="font-semibold tabular-nums text-mz-text-primary">{{ number_format($catalogCounts['missing_price']) }}</span>
                        <x-icon name="arrow-right" class="size-3.5 text-mz-text-muted" />
                    </a>
                </div>
                <p class="mt-3 text-xs text-mz-text-muted">Una refacción puede tener ambos pendientes. Foto capturada no verifica que el archivo siga disponible.</p>
                @if ($catalogCounts['active'] === 0)
                    <p class="mt-4 text-sm text-mz-text-secondary">Aún no hay productos publicados.</p>
                @elseif ($catalogCounts['missing_photo'] === 0 && $catalogCounts['missing_price'] === 0)
                    <p class="mt-4 flex items-center gap-2 text-sm text-mz-text-secondary"><x-icon name="check" class="size-4" /> Sin pendientes de foto o precio.</p>
                @endif
            </section>

            <section class="mz-card overflow-hidden" aria-labelledby="home-featured-title">
                <div class="border-b border-mz-border-light p-5">
                    <p class="text-xs font-medium uppercase tracking-widest text-mz-text-muted">Público · Portada</p>
                    <h2 id="home-featured-title" class="mt-1 text-base font-semibold text-mz-text-primary">Destacados de la home</h2>
                    <p class="mt-1 text-xs text-mz-text-secondary">Las {{ $homeCards->count() }} cards activas de productos destacados.</p>
                </div>
                <ul class="divide-y divide-mz-border-light">
                    @forelse ($homeCards as $card)
                        <li class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-mz-text-primary">{{ $card->title }}</p>
                                    <p class="mt-1 text-xs text-mz-text-muted">
                                        @if ($card->hasDiscount())
                                            ${{ number_format((float) $card->sale_price, 2) }} · antes ${{ number_format((float) $card->price, 2) }}
                                        @elseif ($card->price !== null)
                                            ${{ number_format((float) $card->price, 2) }}
                                        @else
                                            Sin precio
                                        @endif
                                    </p>
                                </div>
                                @if ($card->badge)
                                    <span class="shrink-0 rounded-full bg-mz-accent-primary/15 px-2 py-0.5 text-xs text-mz-accent-primary">{{ $card->badge }}</span>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-mz-text-secondary">No hay cards de productos destacados en la home.</li>
                    @endforelse
                </ul>
                <div class="border-t border-mz-border-light p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <a href="{{ route('admin.home.edit') }}#destacados" class="text-xs font-medium text-mz-accent-primary hover:text-mz-accent-hover">Editar cards de portada</a>
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-xs font-medium text-mz-text-secondary hover:text-mz-accent-primary">Ver home <x-icon name="arrow-right" class="size-3.5" /></a>
                    </div>
                </div>
            </section>
        </div>
    </div>

    @php
        $dashboardOnlineUsers = collect($onlineUsers ?? []);
        $dashboardOnlineMap = $dashboardOnlineUsers->keyBy('id');
        $hasEmployeeActivity = static fn (array $employee): bool =>
            (int) ($employee['tickets'] ?? 0) > 0
            || (float) ($employee['sales'] ?? 0) > 0
            || (float) ($employee['cash_in'] ?? 0) > 0
            || (float) ($employee['cash_out'] ?? 0) > 0
            || (float) ($employee['refunds'] ?? 0) > 0;
        $teamRows = collect($employeeBreakdown['employees'] ?? [])
            ->filter(fn (array $employee): bool =>
                $dashboardOnlineMap->has($employee['id'])
                || $hasEmployeeActivity($employee)
                || ($employee['latest_cut'] ?? null) !== null
            )
            ->values();
        $onlineEmployeeCount = $teamRows->filter(fn (array $employee): bool => $dashboardOnlineMap->has($employee['id']))->count();
        $activeEmployeeCount = $teamRows->filter($hasEmployeeActivity)->count();
        $generatedCutCount = $teamRows->filter(fn (array $employee): bool => ($employee['latest_cut'] ?? null) !== null)->count();
    @endphp

    <section class="mt-6 mz-card overflow-hidden" aria-labelledby="team-cuts-title">
        <div class="border-b border-mz-border-light p-5">
            <p class="text-xs font-medium uppercase tracking-widest text-mz-text-muted">Equipo · Operación de hoy</p>
            <h2 id="team-cuts-title" class="mt-1 text-base font-semibold text-mz-text-primary">Sesiones y cortes por empleado</h2>
            <p class="mt-1 text-xs text-mz-text-secondary">“En línea” indica una sesión activa en los últimos cinco minutos; no representa asistencia física.</p>
        </div>

        <div class="grid grid-cols-1 gap-3 border-b border-mz-border-light p-4 sm:grid-cols-3 sm:p-5">
            <div class="rounded-lg border border-mz-border-light p-3">
                <p class="text-xs text-mz-text-secondary">Sesiones activas</p>
                <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-mz-text-primary">{{ number_format($onlineEmployeeCount) }}</p>
            </div>
            <div class="rounded-lg border border-mz-border-light p-3">
                <p class="text-xs text-mz-text-secondary">Personas con actividad hoy</p>
                <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-mz-text-primary">{{ number_format($activeEmployeeCount) }}</p>
            </div>
            <div class="rounded-lg border border-mz-border-light p-3">
                <p class="text-xs text-mz-text-secondary">Cortes generados hoy</p>
                <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-mz-text-primary">{{ number_format($generatedCutCount) }}</p>
            </div>
        </div>

        @if ($teamRows->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-mz-text-secondary">Sin sesiones recientes ni actividad registrada hoy.</p>
        @else
            <ul class="divide-y divide-mz-border-light">
                @foreach ($teamRows as $employee)
                    @php
                        $session = $dashboardOnlineMap->get($employee['id']);
                        $latestCut = $employee['latest_cut'];
                    @endphp
                    <li class="flex min-w-0 flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-mz-text-primary">{{ $employee['name'] }}</p>
                            <p class="mt-1 text-xs text-mz-text-secondary">
                                {{ number_format((int) $employee['tickets']) }} tickets · ${{ number_format((float) $employee['sales'], 2) }} en ventas
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            @if ($session)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-mz-success/10 px-2.5 py-1 font-medium text-mz-success">
                                    <span class="size-1.5 rounded-full bg-mz-success"></span>
                                    En línea · {{ $session['last_activity']->diffForHumans() }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-mz-bg-surface-2 px-2.5 py-1 font-medium text-mz-text-secondary">
                                    Sin sesión reciente
                                </span>
                            @endif

                            @if ($latestCut)
                                <span class="inline-flex items-center gap-1 rounded-full bg-mz-accent-primary/10 px-2.5 py-1 font-medium text-mz-accent-primary">
                                    {{ $employee['is_day_closed'] ? 'Corte cerrado' : 'Corte generado' }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-mz-bg-surface-2 px-2.5 py-1 font-medium text-mz-text-secondary">
                                    Sin corte registrado
                                </span>
                            @endif
                        </div>

                        <a href="{{ route('pos.cuts.index', ['date' => $generatedAt->toDateString(), 'employee' => $employee['id']]) }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-medium text-mz-accent-primary hover:text-mz-accent-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-mz-accent-primary" aria-label="Ver corte de {{ $employee['name'] }}">
                            Ver corte <x-icon name="arrow-right" class="size-3.5" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
