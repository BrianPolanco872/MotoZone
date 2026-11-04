<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\ChargePendingSaleRequest;
use App\Http\Requests\Pos\StoreSaleRequest;
use App\Models\AuditLog;
use App\Models\DailyCut;
use App\Models\Sale;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function store(StoreSaleRequest $request, SaleService $service): JsonResponse|RedirectResponse
    {
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

        $before = $sale->only(['status', 'notes']);
        $service->cancelPending($sale, $request->user());
        $after = $sale->fresh()->only(['status', 'notes']);

        $this->audit->log([
            'action' => AuditLog::ACTION_CANCEL_SALE,
            'entity_type' => 'sale',
            'entity_id' => $sale->id,
            'before' => $before,
            'after' => $after,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'redirect' => route('pos.sales.pending')]);
        }

        return redirect()->route('pos.sales.pending')->with('status', 'Venta pendiente cancelada.');
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

    public function day(Request $request): View
    {
        $request->validate(['date' => ['nullable', 'date']]);

        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');
        $date = Carbon::parse($request->query('date', today()->toDateString()));

        $query = Sale::query()
            ->paid()
            ->whereBetween('paid_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->when(! $canSeeAny, fn ($query) => $query->where('user_id', $user->id))
            ->when($request->filled('employee'), fn ($query, $employee) => $query->where('user_id', (int) $employee));

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

    public function ticket(Request $request, Sale $sale): View
    {
        $this->authorizeTicket($request->user(), $sale);

        $sale->load('items', 'user');

        return view('pos.tickets.ticket', [
            'sale' => $sale,
            'settings' => config('pos'),
        ]);
    }

    public function ticketData(Request $request, Sale $sale): JsonResponse
    {
        $this->authorizeTicket($request->user(), $sale);

        $sale->load('items', 'user');

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
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                    'returned_quantity' => (int) $item->returned_quantity,
                ])->all(),
            ],
            'settings' => config('pos'),
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
