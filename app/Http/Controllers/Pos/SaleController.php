<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\ChargePendingSaleRequest;
use App\Http\Requests\Pos\StoreSaleRequest;
use App\Models\AuditLog;
use App\Models\DailyCut;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ReturnService;
use App\Services\SaleService;
use App\Services\TicketSettings;
use App\Support\Quantity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly ReturnService $returns,
    ) {}

    public function store(StoreSaleRequest $request, SaleService $service): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        // Un empleado que cerró su propio corte no puede vender hasta reabrirlo.
        if (DailyCut::isDayClosedForUser(today()->toDateString(), $user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Este día ya está cerrado para ti. No se pueden registrar ventas.',
                    'day_closed_for_user' => true,
                    'reopen_url' => route('pos.cuts.reopen-my-day'),
                ], 422);
            }

            throw ValidationException::withMessages([
                'sale' => 'Este día ya está cerrado. No se pueden registrar ventas.',
            ]);
        }

        if (DailyCut::isDayClosed(today()->toDateString())) {
            throw ValidationException::withMessages(['sale' => 'Este día ya está cerrado. No se pueden registrar ventas.']);
        }

        $sale = $service->create($request->user(), $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'sale' => ['id' => $sale->id, 'folio' => $sale->folio, 'total' => (float) $sale->total],
            ]);
        }

        return redirect()
            ->route('pos.sales.ticket', $sale)
            ->with('status', "Venta {$sale->folio} registrada correctamente.");
    }

    public function charge(ChargePendingSaleRequest $request, Sale $sale, SaleService $service): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        // Un empleado que cerró su propio corte no puede cobrar hasta reabrirlo.
        if (DailyCut::isDayClosedForUser(today()->toDateString(), $user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Este día ya está cerrado para ti. No se pueden cobrar ventas.',
                    'day_closed_for_user' => true,
                    'reopen_url' => route('pos.cuts.reopen-my-day'),
                ], 422);
            }

            throw ValidationException::withMessages([
                'sale' => 'Este día ya está cerrado. No se pueden cobrar ventas.',
            ]);
        }

        if (DailyCut::isDayClosed(today()->toDateString())) {
            throw ValidationException::withMessages(['sale' => 'Este día ya está cerrado. No se pueden cobrar ventas.']);
        }

        $this->authorizeSaleAccess($request->user(), $sale);

        $sale = $service->chargePending($sale, $request->user(), $request->validated('amount_paid'), $request->validated('payment_method'));

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => route('pos.sales.ticket', $sale),
                'sale' => ['id' => $sale->id, 'folio' => $sale->folio, 'total' => (float) $sale->total],
            ]);
        }

        return redirect()->route('pos.sales.ticket', $sale);
    }

    public function cancel(Request $request, Sale $sale, SaleService $service): JsonResponse|RedirectResponse
    {
        $this->authorizeSaleAccess($request->user(), $sale);

        $wasPaid = $sale->isPaid();
        $before = $sale->only(['status', 'notes']);

        if ($wasPaid) {
            $this->returns->cancelPaidSaleNow($request->user(), $sale);
        } else {
            $service->cancelPending($sale, $request->user());
        }

        $after = $sale->fresh()->only(['status', 'notes']);

        $this->audit->log([
            'action' => AuditLog::ACTION_CANCEL_SALE,
            'entity_type' => 'sale',
            'entity_id' => $sale->id,
            'before' => $before,
            'after' => $after,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'redirect' => $wasPaid ? route('pos.sales.day') : route('pos.sales.pending')]);
        }

        return redirect()->route($wasPaid ? 'pos.sales.day' : 'pos.sales.pending')
            ->with('status', $wasPaid ? 'Venta cancelada y artículos devueltos al inventario.' : 'Venta pendiente cancelada.');
    }

    public function pending(Request $request): View
    {
        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');

        $sales = Sale::query()
            ->pending()
            ->when(! $canSeeAny, fn ($query) => $query->where('user_id', $user->id))
            ->with('items', 'user')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('pos.sales.pending', [
            'sales' => $sales,
            'canSeeAny' => $canSeeAny,
        ]);
    }

    public function pendingList(Request $request): JsonResponse
    {
        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');

        $sales = Sale::query()
            ->pending()
            ->when(! $canSeeAny, fn ($query) => $query->where('user_id', $user->id))
            ->with('items', 'user')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'sales' => $sales->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'folio' => $sale->folio,
                'created_at' => $sale->created_at?->format('d/m/Y H:i'),
                'total' => (float) $sale->total,
                'item_count' => $sale->items->count(),
                'cashier' => $canSeeAny ? $sale->user?->name : null,
            ]),
        ]);
    }

    public function dayList(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();
        $date = Carbon::parse($request->query('date', today()->toDateString()));
        $search = trim((string) $request->query('search', ''));

        $query = Sale::query()
            ->whereIn('status', [Sale::STATUS_PAID, Sale::STATUS_CANCELLED])
            ->whereBetween('paid_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->where('user_id', $user->id)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('folio', 'like', "%{$search}%")
                    ->orWhereHas('items', fn ($items) => $items
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%")
                        ->orWhere('custom_name', 'like', "%{$search}%"));
            }));

        // shortcut: 100 ventas por consulta; paginar si el modal crece.
        $sales = $query
            ->with('items', 'user')
            ->withSum(['returns as refunded_total' => fn ($query) => $query->where('status', SaleReturn::STATUS_APPROVED)], 'total')
            ->orderByDesc('paid_at')
            ->limit(100)
            ->get()
            ->map(fn (Sale $sale): array => [
                'id' => $sale->id,
                'code' => $sale->folio,
                'status' => $sale->status,
                'cancelled' => $sale->status === Sale::STATUS_CANCELLED,
                'time' => $sale->paid_at?->format('H:i'),
                'cashier' => $sale->user?->name,
                'client' => $sale->user?->name,
                'item_count' => $sale->items->count(),
                'total' => round((float) $sale->total - (float) ($sale->refunded_total ?? 0), 2),
                'amount_paid' => (float) $sale->amount_paid,
                'items' => $sale->items->map(fn (SaleItem $item): array => [
                    'id' => $item->id,
                    'sku' => $item->sku,
                    'description' => $item->product_name ?: $item->custom_name,
                    'quantity' => $item->quantity,
                    'returned_quantity' => $item->returned_quantity,
                    'available_quantity' => $item->availableReturnQuantity(),
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ])->all(),
            ]);

        return response()->json(['sales' => $sales]);
    }

    public function returnItem(Request $request, Sale $sale, SaleItem $saleItem): JsonResponse
    {
        $this->authorizeSaleAccess($request->user(), $sale);
        abort_unless($saleItem->sale_id === $sale->id, 404);
        abort_unless($sale->isPaid(), 422, 'La venta ya no está pagada.');

        $validated = $request->validate([
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ]);

        $quantity = isset($validated['quantity'])
            ? (float) $validated['quantity']
            : $saleItem->availableReturnQuantity();

        $return = $this->returns->returnSingleItemNow($request->user(), $sale, $saleItem, $quantity);

        $this->audit->log([
            'action' => AuditLog::ACTION_RETURN_SALE,
            'entity_type' => 'sale_return',
            'entity_id' => $return->id,
            'before' => null,
            'after' => [
                'sale_id' => $sale->id,
                'sale_item_id' => $saleItem->id,
                'quantity' => $quantity,
                'total' => (float) $return->total,
            ],
        ]);

        return response()->json([
            'ok' => true,
            'return' => ['id' => $return->id, 'total' => (float) $return->total],
            'item' => [
                'id' => $saleItem->id,
                'returned_quantity' => $saleItem->fresh()->returned_quantity,
            ],
        ]);
    }

    public function day(Request $request): View
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'employee' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $user = $request->user();

        // Solo el administrador puede consultar las ventas de todos los empleados.
        // Un cajero o empleado ve únicamente su propia tabla del día.
        $canSeeAny = $user->hasRole('admin');
        $date = Carbon::parse($request->query('date', today()->toDateString()));

        $query = Sale::query()
            ->paid()
            ->whereBetween('paid_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->when(! $canSeeAny, fn ($query) => $query->where('user_id', $user->id))
            ->when($canSeeAny && $request->filled('employee'), fn ($query) => $query->where('user_id', $request->integer('employee')));

        $summary = [
            'count' => (clone $query)->count(),
            'total' => round((float) (clone $query)->sum('total'), 2),
        ];

        $sales = $query
            ->with('items', 'user')
            ->orderByDesc('paid_at')
            ->paginate(15)
            ->withQueryString();

        return view('pos.sales.day', [
            'sales' => $sales,
            'summary' => $summary,
            'date' => $date,
            'canSeeAny' => $canSeeAny,
            'employees' => $canSeeAny ? $this->employees() : collect(),
            'canRequestReturns' => $user->can('pos.returns.request'),
        ]);
    }

    public function previous(Request $request): View
    {
        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');
        $search = trim((string) $request->query('search', ''));

        $query = Sale::query()
            ->paid()
            ->when(! $canSeeAny, fn ($query) => $query->where('user_id', $user->id))
            ->when($search !== '', fn ($query) => $query->where('folio', 'like', "%{$search}%"));

        $summary = [
            'count' => (clone $query)->count(),
            'total' => round((float) (clone $query)->sum('total'), 2),
        ];

        $sales = $query
            ->with('items', 'user')
            ->orderByDesc('paid_at')
            ->paginate(20)
            ->withQueryString();

        return view('pos.sales.previous', [
            'sales' => $sales,
            'summary' => $summary,
            'search' => $search,
            'canSeeAny' => $canSeeAny,
            'canRequestReturns' => $user->can('pos.returns.request'),
        ]);
    }

    public function ticket(Request $request, Sale $sale, TicketSettings $tickets): View|Response
    {
        $this->authorizeTicket($request->user(), $sale);

        $sale->load('items', 'user');
        $settings = $tickets->current();
        $embedded = $request->query('embed') === '1';
        $autoPrint = ! $embedded && $request->query('print') === '1';

        if ($settings['ticket']['print_mode'] === 'text' && ($embedded || $autoPrint)) {
            $date = $sale->paid_at ?? $sale->created_at;
            $method = $sale->payment_method ?? 'cash';

            $view = view('pos.tickets.text-print', [
                'embedded' => $embedded,
                'autoPrint' => $autoPrint,
                'receipt' => [
                    'business' => $settings['business'],
                    'ticket' => array_diff_key($settings['ticket'], array_flip(['show_logo', 'show_qr', 'qr_image_url'])),
                    'sale' => [
                        'folio' => $sale->folio,
                        'date' => $date->format('d/m/Y'),
                        'time' => $date->format('H:i'),
                        'cashier' => $sale->user?->name,
                        'payment_method' => config('pos.payment_methods.'.$method.'.label', ucfirst($method)),
                        'total' => $sale->total,
                        'tax_total' => $sale->tax_total,
                        'amount_paid' => $sale->amount_paid,
                        'change_amount' => $sale->change_amount,
                        'items' => $sale->items->map(fn (SaleItem $item): array => [
                            'name' => $item->product_name,
                            'quantity' => Quantity::format($item->quantity),
                            'unit_price' => $item->unit_price,
                            'line_total' => $item->line_total,
                            'sku' => $item->sku ?: $item->barcode,
                            'sale_mode' => $item->saleMode(),
                            'returned_quantity' => Quantity::format($item->returned_quantity),
                        ])->all(),
                    ],
                ],
            ]);
        } else {
            $view = view('pos.tickets.ticket', [
                'sale' => $sale,
                'settings' => $settings,
                'embedded' => $embedded,
                'autoPrint' => $autoPrint,
            ]);
        }

        return $embedded ? response($view)->header('Cache-Control', 'private, no-store') : $view;
    }

    public function ticketData(Request $request, Sale $sale, TicketSettings $tickets): JsonResponse
    {
        $this->authorizeTicket($request->user(), $sale);

        $sale->load('items', 'user');

        $settings = $tickets->current();

        return response()->json([
            'sale' => [
                'id' => $sale->id,
                'folio' => $sale->folio,
                'total' => (float) $sale->total,
                'subtotal' => (float) $sale->subtotal,
                'tax_total' => (float) $sale->tax_total,
                'amount_paid' => (float) $sale->amount_paid,
                'change_amount' => (float) $sale->change_amount,
                'paid_at' => $sale->paid_at?->format('d/m/Y H:i'),
                'user' => $sale->user?->name,
                'items' => $sale->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'barcode' => $item->barcode,
                    'category' => $item->category_name,
                    'quantity' => $item->quantity,
                    'sale_mode' => $item->saleMode(),
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                    'returned_quantity' => $item->returned_quantity,
                ])->all(),
            ],
            'settings' => $settings,
            'ticket_html' => view('pos.tickets.receipt', compact('sale', 'settings'))->render(),
        ]);
    }

    private function authorizeTicket(User $user, Sale $sale): void
    {
        if ($sale->user_id === $user->id) {
            abort_unless(
                $user->can('pos.tickets.print.own') || $user->can('pos.tickets.print.any'),
                403,
            );

            return;
        }

        abort_unless($user->can('pos.tickets.print.any'), 403);
    }

    private function authorizeSaleAccess(User $user, Sale $sale): void
    {
        if ($sale->user_id === $user->id) {
            abort_unless($user->can('pos.sell'), 403);

            return;
        }

        abort_unless($user->can('pos.sales.day.any') && $user->can('pos.sell'), 403);
    }

    private function employees()
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'empleado']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
