<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\ArchiveCashMovementRequest;
use App\Http\Requests\Pos\StoreCashMovementRequest;
use App\Models\AuditLog;
use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\CashMovementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CashMovementController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'employee' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['nullable', 'in:in,out'],
            'search' => ['nullable', 'string', 'max:255'],
            'show_archived' => ['nullable', 'boolean'],
        ]);

        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : null;
        $employee = $request->filled('employee') ? (int) $request->query('employee') : null;
        $type = $request->query('type');
        $search = trim((string) $request->query('search', ''));
        $showArchived = $request->boolean('show_archived', false);

        $query = CashMovement::query()
            ->with('user', 'archivedBy', 'deletedBy')
            ->when(! $showArchived, fn ($q) => $q->whereNull('archivado_at'))
            ->when($from, fn ($q) => $q->where('happened_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('happened_at', '<=', $to))
            ->when($employee !== null, fn ($q) => $q->where('user_id', $employee))
            ->when($type !== null, fn ($q) => $q->where('type', $type))
            ->when($search !== '', fn ($q) => $q->where('concept', 'like', "%{$search}%"))
            ->when(! $canSeeAny, fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('happened_at');

        $movements = $query->paginate(20)->withQueryString();

        // Totals for visible (non-archived) movements matching filters
        $totalsQuery = CashMovement::query()
            ->when(! $showArchived, fn ($q) => $q->whereNull('archivado_at'))
            ->when($from, fn ($q) => $q->where('happened_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('happened_at', '<=', $to))
            ->when($employee !== null, fn ($q) => $q->where('user_id', $employee))
            ->when($type !== null, fn ($q) => $q->where('type', $type))
            ->when($search !== '', fn ($q) => $q->where('concept', 'like', "%{$search}%"))
            ->when(! $canSeeAny, fn ($q) => $q->where('user_id', $user->id));

        $inTotal = round((float) $totalsQuery->where('type', CashMovement::TYPE_IN)->sum('amount'), 2);
        $outTotal = round((float) $totalsQuery->where('type', CashMovement::TYPE_OUT)->sum('amount'), 2);

        $categories = CashMovement::manualCategories();

        $employees = collect();
        if ($canSeeAny) {
            $employees = User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'empleado']))
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('pos.cash.index', [
            'movements' => $movements,
            'canSeeAny' => $canSeeAny,
            'employees' => $employees,
            'categories' => $categories,
            'inCategories' => array_filter($categories, fn ($c) => $c['type'] === CashMovement::TYPE_IN),
            'outCategories' => array_filter($categories, fn ($c) => $c['type'] === CashMovement::TYPE_OUT),
            'inTotal' => $inTotal,
            'outTotal' => $outTotal,
            'netTotal' => round($inTotal - $outTotal, 2),
            'filters' => [
                'from' => $from?->toDateString() ?? '',
                'to' => $to?->toDateString() ?? '',
                'employee' => $employee,
                'type' => $type,
                'search' => $search,
                'show_archived' => $showArchived,
            ],
        ]);
    }

    public function store(StoreCashMovementRequest $request, CashMovementService $service): RedirectResponse
    {
        if (DailyCut::isDayClosed(today()->toDateString())) {
            throw ValidationException::withMessages(['cash_movement' => 'Este día ya está cerrado. No se pueden registrar movimientos de caja.']);
        }

        $movement = $service->create($request->user(), $request->validated());

        return redirect()
            ->route('pos.cash.index')
            ->with('status', 'Movimiento de '.mb_strtolower($movement->categoryLabel()).' registrado por $'.number_format((float) $movement->amount, 2).'.');
    }

    public function archive(Request $request, CashMovement $cashMovement, CashMovementService $service): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'boolean'],
        ]);

        abort_unless($request->user()->can('pos.cash.manage'), 403);

        $before = $cashMovement->only(['archivado_at']);
        $service->archive($cashMovement, $request->user());
        $after = $cashMovement->fresh()->only(['archivado_at']);

        $this->audit->log([
            'action' => AuditLog::ACTION_ARCHIVE_CASH_MOVEMENT,
            'entity_type' => 'cash_movement',
            'entity_id' => $cashMovement->id,
            'before' => $before,
            'after' => $after,
        ]);

        return back()->with('status', 'Movimiento archivado. Ya no aparece en la vista principal pero sigue contando en cortes históricos.');
    }

    public function archiveMass(ArchiveCashMovementRequest $request, CashMovementService $service): RedirectResponse
    {
        abort_unless($request->user()->can('pos.cash.manage'), 403);

        $count = $service->archiveBefore($request->validated('before_date'), $request->user());

        return back()->with('status', "{$count} movimiento(s) anterior(es) al ".$request->validated('before_date').' archivado(s).');
    }

    public function restore(Request $request, CashMovement $cashMovement, CashMovementService $service): RedirectResponse
    {
        abort_unless($request->user()->can('pos.cash.manage'), 403);

        $service->restore($cashMovement);

        return back()->with('status', 'Movimiento restaurado de la vista archivada.');
    }

    public function destroy(Request $request, CashMovement $cashMovement, CashMovementService $service): RedirectResponse
    {
        abort_unless($request->user()->can('pos.cash.manage'), 403);

        $validated = $request->validate([
            'deletion_reason' => ['required', 'string', 'max:500'],
        ]);

        $before = $cashMovement->only(['deleted_at', 'deletion_reason']);
        try {
            $service->delete($cashMovement, $request->user(), $validated['deletion_reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
        $after = $cashMovement->fresh()->only(['deleted_at', 'deletion_reason']);

        $this->audit->log([
            'action' => AuditLog::ACTION_DELETE_CASH_MOVEMENT,
            'entity_type' => 'cash_movement',
            'entity_id' => $cashMovement->id,
            'before' => $before,
            'after' => $after,
        ]);

        return back()->with('status', 'Movimiento eliminado (soft).');
    }

    public function export(Request $request, CashMovementService $service)
    {
        abort_unless($request->user()->can('pos.cash.manage'), 403);

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'employee' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['nullable', 'in:in,out'],
            'search' => ['nullable', 'string', 'max:255'],
            'show_archived' => ['nullable', 'boolean'],
        ]);

        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : null;
        $employee = $request->filled('employee') ? (int) $request->query('employee') : null;
        $type = $request->query('type');
        $search = trim((string) $request->query('search', ''));
        $showArchived = $request->boolean('show_archived', false);

        $movements = CashMovement::query()
            ->with('user')
            ->when(! $showArchived, fn ($q) => $q->whereNull('archivado_at'))
            ->when($from, fn ($q) => $q->where('happened_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('happened_at', '<=', $to))
            ->when($employee !== null, fn ($q) => $q->where('user_id', $employee))
            ->when($type !== null, fn ($q) => $q->where('type', $type))
            ->when($search !== '', fn ($q) => $q->where('concept', 'like', "%{$search}%"))
            ->when(! $request->user()->can('pos.sales.day.any'), fn ($q) => $q->where('user_id', $request->user()->id))
            ->orderByDesc('happened_at')
            ->get();

        $headers = [
            'Fecha/Hora',
            'Tipo',
            'Categoría',
            'Concepto',
            'Monto',
            'Empleado',
            'Archivado',
        ];

        $rows = [];
        foreach ($movements as $m) {
            $rows[] = [
                $m->happened_at?->format('d/m/Y H:i') ?? '',
                $m->isIn() ? 'Entrada' : 'Salida',
                $m->categoryLabel(),
                $m->concept,
                number_format((float) $m->amount, 2),
                $m->user?->name ?? '',
                $m->isArchived() ? 'Sí' : 'No',
            ];
        }

        $filename = 'caja_movimientos_'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
