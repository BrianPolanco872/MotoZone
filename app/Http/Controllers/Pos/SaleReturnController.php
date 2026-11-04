<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreSaleReturnRequest;
use App\Models\AuditLog;
use App\Models\DailyCut;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\AuditLogService;
use App\Services\ReturnService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SaleReturnController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('search', ''));

        $returns = SaleReturn::query()
            ->when(! $canSeeAny, fn ($query) => $query->where('requested_by', $user->id))
            ->when(in_array($status, [SaleReturn::STATUS_PENDING, SaleReturn::STATUS_APPROVED, SaleReturn::STATUS_REJECTED], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->whereHas('sale', fn ($saleQuery) => $saleQuery->where('folio', 'like', "%{$search}%")))
            ->with('sale', 'requester', 'approver', 'items.saleItem')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('pos.returns.index', [
            'returns' => $returns,
            'status' => $status,
            'search' => $search,
            'canSeeAny' => $canSeeAny,
            'canApprove' => $user->can('pos.returns.approve'),
        ]);
    }

    public function create(Request $request, Sale $sale): View
    {
        $user = $request->user();
        abort_unless($sale->user_id === $user->id || $user->can('pos.sales.day.any'), 403);
        abort_unless($sale->isPaid(), 404);

        $sale->load('items');

        return view('pos.returns.create', [
            'sale' => $sale,
        ]);
    }

    public function store(StoreSaleReturnRequest $request, ReturnService $service): RedirectResponse
    {
        if (DailyCut::isDayClosed(today()->toDateString())) {
            throw ValidationException::withMessages(['sale_return' => 'Este día ya está cerrado. No se pueden registrar devoluciones.']);
        }

        $return = $service->request($request->user(), $request->validated());

        $this->audit->log([
            'action' => AuditLog::ACTION_RETURN_SALE,
            'entity_type' => 'sale_return',
            'entity_id' => $return->id,
            'before' => null,
            'after' => [
                'sale_id' => $return->sale_id,
                'status' => $return->status,
                'total' => (float) $return->total,
            ],
        ]);

        return redirect()
            ->route('pos.returns.index')
            ->with('status', "Solicitud de devolución de la venta {$return->sale->folio} registrada. Queda pendiente de autorización.");
    }

    public function approve(Request $request, SaleReturn $saleReturn, ReturnService $service): RedirectResponse
    {
        abort_unless($request->user()->can('pos.returns.approve'), 403);

        $service->approve($saleReturn, $request->user());

        return redirect()
            ->route('pos.returns.index')
            ->with('status', 'Devolución autorizada. El stock y el efectivo quedaron actualizados.');
    }

    public function reject(Request $request, SaleReturn $saleReturn, ReturnService $service): RedirectResponse
    {
        abort_unless($request->user()->can('pos.returns.approve'), 403);

        $service->reject($saleReturn, $request->user());

        return redirect()
            ->route('pos.returns.index')
            ->with('status', 'Solicitud de devolución rechazada.');
    }
}
