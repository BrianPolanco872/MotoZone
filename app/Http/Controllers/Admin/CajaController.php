<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreCashMovementRequest;
use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Services\CashMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CajaController extends Controller
{
    /**
     * La caja se administra en una sola pantalla (/pos/caja). Se redirige por compatibilidad.
     */
    public function index(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('pos.cash.manage'), 403);

        return redirect()->route('pos.cash.index', $request->query());
    }

    public function store(StoreCashMovementRequest $request, CashMovementService $service): RedirectResponse
    {
        abort_unless(request()->user()->can('pos.cash.manage'), 403);

        if (DailyCut::isDayClosed(today('America/Mexico_City')->toDateString())) {
            throw ValidationException::withMessages([
                'cash_movement' => 'El día ya está cerrado. No se pueden registrar movimientos de caja.',
            ]);
        }

        $validated = $request->validated();
        $validated['happened_at'] = $validated['happened_at'] ?? now('America/Mexico_City');

        $movement = $service->create($request->user(), $validated);

        $label = $movement instanceof CashMovement
            ? $movement->categoryLabel()
            : ucfirst(str_replace('_', ' ', $movement->getMorphClass() ?? $movement->getTable()));

        return back()->with('status', 'Movimiento de '.$label.' registrado correctamente.');
    }

    public function destroy(Request $request, CashMovement $cashMovement): RedirectResponse
    {
        abort_unless(request()->user()->can('pos.cash.manage'), 403);

        $validated = $request->validate([
            'deletion_reason' => ['required', 'string', 'max:500'],
        ]);

        if (! $cashMovement->canBeDeleted()) {
            return back()->withErrors(['cash_movement' => 'Este movimiento no se puede eliminar porque pertenece a un corte cerrado.']);
        }

        $cashMovement->deleted_by = $request->user()->id;
        $cashMovement->deletion_reason = $validated['deletion_reason'];
        $cashMovement->delete();

        return back()->with('status', 'Movimiento eliminado correctamente.');
    }

    public function archive(Request $request, CashMovement $cashMovement): RedirectResponse
    {
        abort_unless(request()->user()->can('pos.cash.manage'), 403);

        if (! $cashMovement->canBeDeleted()) {
            return back()->withErrors(['cash_movement' => 'Este movimiento no se puede archivar porque pertenece a un corte cerrado.']);
        }

        $cashMovement->archivado_por = $request->user()->id;
        $cashMovement->archivado_at = now('America/Mexico_City');
        $cashMovement->save();

        return back()->with('status', 'Movimiento archivado correctamente.');
    }

    /**
     * El corte se consulta en /pos/corte. Se redirige por compatibilidad.
     */
    public function corte(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('pos.cash.manage'), 403);

        return $request->user()->can('pos.cut.manage')
            ? redirect()->route('pos.cuts.index', $request->query())
            : redirect()->route('pos.cash.index');
    }

    public function export(Request $request)
    {
        abort_unless(request()->user()->can('pos.cash.manage'), 403);

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

        $filename = 'caja_movimientos_'.now('America/Mexico_City')->format('Ymd_His').'.csv';

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
