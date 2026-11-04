<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\GenerateDailyCutRequest;
use App\Models\AuditLog;
use App\Models\DailyCut;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\DailyCutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DailyCutController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function index(Request $request, DailyCutService $service): View
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'employee' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');

        $date = Carbon::parse($request->query('date', today()->toDateString()));
        $employee = null;

        if ($request->filled('employee')) {
            if (! $canSeeAny) {
                abort(403, 'No puedes ver el corte de otro empleado.');
            }
            $employee = User::withTrashed()->findOrFail($request->integer('employee'));
        } elseif (! $canSeeAny) {
            // Empleado sin permiso de ver todos: solo su propio corte
            $employee = $user;
        }

        $totals = $service->build($date, $employee);
        $snapshot = $service->findLatestCut($date->toDateString(), $employee);
        $versions = $snapshot ? $service->findAllVersions($date->toDateString(), $employee) : collect();

        return view('pos.cuts.index', [
            'totals' => $totals,
            'date' => $date,
            'employee' => $employee,
            'snapshot' => $snapshot,
            'versions' => $versions,
            'employees' => $this->employees($canSeeAny),
            'readOnly' => false,
            'canSeeAny' => $canSeeAny,
        ]);
    }

    public function mine(Request $request, DailyCutService $service): View
    {
        $date = Carbon::parse($request->query('date', today()->toDateString()));
        $employee = $request->user();

        $totals = $service->build($date, $employee);
        $snapshot = $service->findLatestCut($date->toDateString(), $employee);
        $versions = $snapshot ? $service->findAllVersions($date->toDateString(), $employee) : collect();

        return view('pos.cuts.index', [
            'totals' => $totals,
            'date' => $date,
            'employee' => $employee,
            'snapshot' => $snapshot,
            'versions' => $versions,
            'employees' => collect(),
            'readOnly' => true,
            'canSeeAny' => false,
        ]);
    }

    public function store(GenerateDailyCutRequest $request, DailyCutService $service): RedirectResponse
    {
        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');

        $date = Carbon::parse($request->validated('date') ?? today()->toDateString());

        $employee = null;
        if ($request->filled('employee')) {
            if (! $canSeeAny) {
                abort(403, 'No puedes hacer el corte de otro empleado.');
            }
            $employee = User::withTrashed()->findOrFail($request->integer('employee'));
        } elseif (! $canSeeAny) {
            $employee = $user;
        }

        $service->generate($date, $user);

        // Cerrar el día para cortes globales (corte final del día).
        // Los días cerrados no aceptan más ventas, devoluciones ni movimientos de caja.
        if (! $employee) {
            $cutKey = DailyCut::keyFor($date->toDateString(), DailyCut::SCOPE_GLOBAL);
            DailyCut::query()
                ->where('cut_key', $cutKey)
                ->orderByDesc('version')
                ->first()?->update(['closed_at' => now()]);
        }

        $this->audit->log([
            'action' => AuditLog::ACTION_MAKE_CUT,
            'entity_type' => 'daily_cut',
            'entity_id' => null,
            'before' => null,
            'after' => [
                'date' => $date->toDateString(),
                'scope' => $employee ? DailyCut::SCOPE_EMPLOYEE : DailyCut::SCOPE_GLOBAL,
                'employee_id' => $employee?->id,
            ],
        ]);

        if ($request->filled('notes')) {
            $cutKey = DailyCut::keyFor($date->toDateString(), $employee ? DailyCut::SCOPE_EMPLOYEE : DailyCut::SCOPE_GLOBAL, $employee?->id);
            DailyCut::query()
                ->where('cut_key', $cutKey)
                ->orderByDesc('version')
                ->first()?->update(['notes' => $request->validated('notes')]);
        }

        $label = $employee ? "de {$employee->name}" : 'global';

        return redirect()
            ->route('pos.cuts.index', ['date' => $date->toDateString(), 'employee' => $employee?->id])
            ->with('status', "Corte {$label} del {$date->format('d/m/Y')} generado correctamente (nueva versión).");
    }

    public function recordCashCounted(Request $request, DailyCut $dailyCut, DailyCutService $service): RedirectResponse
    {
        $request->validate([
            'cash_counted' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Authorization: only the cut owner or admin can record cash count
        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');

        if ($dailyCut->scope === DailyCut::SCOPE_EMPLOYEE && $dailyCut->user_id !== $user->id && ! $canSeeAny) {
            abort(403, 'No puedes registrar el conteo de este corte.');
        }

        $before = $dailyCut->only(['cash_counted', 'cash_counted_at']);
        $service->recordCashCounted(
            $dailyCut,
            (float) $request->validated('cash_counted'),
            $request->validated('reason') ?? ''
        );
        $after = $dailyCut->fresh()->only(['cash_counted', 'cash_counted_at', 'difference']);

        $this->audit->log([
            'action' => AuditLog::ACTION_RECORD_CASH_COUNT,
            'entity_type' => 'daily_cut',
            'entity_id' => $dailyCut->id,
            'before' => $before,
            'after' => $after,
        ]);

        return back()->with('status', 'Efectivo contado registrado. Sobrante/faltante calculado.');
    }

    public function print(Request $request, DailyCutService $service): View
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'employee' => ['nullable', 'integer', 'exists:users,id'],
            'version' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');

        $date = Carbon::parse($request->query('date', today()->toDateString()));
        $employee = null;

        if ($request->filled('employee')) {
            if (! $canSeeAny) {
                abort(403, 'No puedes imprimir el corte de otro empleado.');
            }
            $employee = User::withTrashed()->findOrFail($request->integer('employee'));
        } elseif (! $canSeeAny) {
            $employee = $user;
        }

        $snapshot = $service->findLatestCut($date->toDateString(), $employee);

        // If a specific version is requested, load it
        if ($request->filled('version')) {
            $snapshot = DailyCut::query()
                ->where('cut_key', DailyCut::keyFor($date->toDateString(), $employee ? DailyCut::SCOPE_EMPLOYEE : DailyCut::SCOPE_GLOBAL, $employee?->id))
                ->where('version', $request->integer('version'))
                ->firstOrFail();
        }

        return view('pos.cuts.print', [
            'totals' => $snapshot?->totals ?? $service->build($date, $employee),
            'date' => $date,
            'employee' => $employee,
            'cut' => $snapshot,
            'settings' => config('pos'),
        ]);
    }

    public function history(): View
    {
        $cuts = DailyCut::query()
            ->with('generatedBy', 'user')
            ->orderByDesc('cut_date')
            ->orderByDesc('version')
            ->paginate(20);

        return view('pos.cuts.history', [
            'cuts' => $cuts,
        ]);
    }

    public function destroy(DailyCut $dailyCut): RedirectResponse
    {
        // Only allow deleting the latest version of a cut
        $latest = DailyCut::query()
            ->where('cut_key', $dailyCut->cut_key)
            ->orderByDesc('version')
            ->first();

        if ($latest?->id !== $dailyCut->id) {
            return back()->withErrors(['solo se puede eliminar la versión más reciente de un corte.']);
        }

        $dailyCut->delete();

        return redirect()
            ->route('pos.cuts.history')
            ->with('status', 'Corte del '.$dailyCut->cut_date->format('d/m/Y').' eliminado.');
    }

    public function destroyAll(): RedirectResponse
    {
        DailyCut::query()->where('scope', DailyCut::SCOPE_GLOBAL)->delete();

        return redirect()
            ->route('pos.cuts.history')
            ->with('status', 'Todos los cortes del historial han sido eliminados.');
    }

    private function employees(bool $canSeeAny): Collection
    {
        if (! $canSeeAny) {
            return collect();
        }

        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'empleado']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
