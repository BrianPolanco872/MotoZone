<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\GenerateDailyCutRequest;
use App\Models\AuditLog;
use App\Models\CustomerPayment;
use App\Models\DailyCut;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\DailyCutService;
use App\Services\PresenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DailyCutController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly PresenceService $presence,
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
                // Seguridad: un empleado no puede consultar cortes de otro,
                // ignoramos cualquier employee_id enviado y usamos el de la sesión.
                $employee = $user;
            } else {
                $employee = User::withTrashed()->findOrFail($request->integer('employee'));
            }
        } elseif (! $canSeeAny) {
            // Empleado sin permiso de ver todos: solo su propio corte
            $employee = $user;
        }

        $totals = $service->build($date, $employee);
        $snapshot = $service->findLatestCut($date->toDateString(), $employee);
        $versions = $snapshot ? $service->findAllVersions($date->toDateString(), $employee) : collect();

        $viewData = [
            'totals' => $totals,
            'date' => $date,
            'employee' => $employee,
            'snapshot' => $snapshot,
            'versions' => $versions,
            'employees' => $this->employees($canSeeAny),
            'readOnly' => false,
            'canSeeAny' => $canSeeAny,
        ];

        // En el corte global, el admin ve el desglose por empleado y la sumatoria final.
        if (! $employee) {
            $viewData['breakdown'] = $service->employeeBreakdown($date->toDateString());
        }

        return view('pos.cuts.index', $viewData);
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
        $canManageCuts = $user->can('pos.cut.manage');

        $date = Carbon::parse($request->validated('date') ?? today()->toDateString());

        // Un empleado con permiso de corte propio solo genera SU corte,
        // nunca el global ni el de un compañero.
        $employee = null;
        if ($request->filled('employee')) {
            if (! $canSeeAny) {
                abort(403, 'No puedes hacer el corte de otro empleado.');
            }
            $employee = User::withTrashed()->findOrFail($request->integer('employee'));
        } elseif (! $canManageCuts) {
            $employee = $user;
        }

        $cuts = $service->generate($date, $user, $employee);

        // Cerrar el día solo corresponde al corte global del admin/encargado.
        // Un empleado que genera su propio corte no cierra el día ni bloquea a los demás.
        if (! $employee && $canManageCuts) {
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
                'cuts_generated' => $cuts->count(),
            ],
        ]);

        if ($request->filled('notes') && $employee) {
            $cutKey = DailyCut::keyFor($date->toDateString(), DailyCut::SCOPE_EMPLOYEE, $employee->id);
            DailyCut::query()
                ->where('cut_key', $cutKey)
                ->orderByDesc('version')
                ->first()?->update(['notes' => $request->validated('notes')]);
        }

        $label = $employee ? "de {$employee->name}" : 'global';

        return redirect()
            ->route($employee ? 'pos.cuts.mine' : 'pos.cuts.index', [
                'date' => $date->toDateString(),
                'employee' => $employee?->id,
            ])
            ->with('status', "Corte {$label} del {$date->format('d/m/Y')} generado correctamente (nueva versión).");
    }

    /**
     * Cierra el día (corte global final). Bloqueado si hay otros cajeros con
     * sesión activa o empleados con actividad sin corte, salvo confirmación forzada.
     */
    public function closeDay(Request $request, DailyCutService $service): RedirectResponse
    {
        $user = $request->user();
        $date = Carbon::parse($request->query('date', today()->toDateString()))->toDateString();
        $force = $request->boolean('force', false);

        // ¿Hay otros usuarios con sesión activa (últimos 5 minutos)?
        $online = $this->presence->onlineUsers();
        $othersOnline = $online->filter(fn ($u) => $u->id !== $user->id);

        if ($othersOnline->isNotEmpty() && ! $force) {
            $nombres = $othersOnline->pluck('name')->implode(', ');

            return back()
                ->withErrors([
                    'close_day' => "No se puede cerrar el día: hay cajeros en línea ({$nombres}). Pídele que cierren su corte o usa \"Forzar cierre\".",
                ])
                ->withInput();
        }

        // ¿Todos los empleados con actividad tienen su corte del día?
        $breakdown = $service->employeeBreakdown($date);
        $pending = $breakdown->filter(fn ($row) => $row['status'] !== 'done');

        if ($pending->isNotEmpty() && ! $force) {
            $nombres = $pending->pluck('name')->implode(', ');

            return back()
                ->withErrors([
                    'close_day' => "Faltan cortes del día de: {$nombres}. Pídelos que cierren su corte o usa \"Forzar cierre\".",
                ])
                ->withInput();
        }

        $cutKey = DailyCut::keyFor($date, DailyCut::SCOPE_GLOBAL);
        $latest = DailyCut::query()
            ->where('cut_key', $cutKey)
            ->orderByDesc('version')
            ->first();

        if (! $latest) {
            return back()->withErrors(['close_day' => 'No hay corte global generado para hoy.']);
        }

        $latest->update(['closed_at' => now()]);

        $this->audit->log([
            'action' => 'cerrar_dia',
            'entity_type' => 'daily_cut',
            'entity_id' => $latest->id,
            'before' => null,
            'after' => [
                'date' => $date,
                'scope' => DailyCut::SCOPE_GLOBAL,
                'force' => $force,
            ],
        ]);

        return redirect()
            ->route('pos.cuts.index', ['date' => $date])
            ->with('status', "Día {$date} cerrado correctamente.");
    }

    public function recordCashCounted(Request $request, DailyCut $dailyCut, DailyCutService $service): RedirectResponse
    {
        // Acepta montos escritos con separador de miles (ej. "14,325") o coma decimal.
        $cashCounted = $request->input('cash_counted');

        if (is_string($cashCounted)) {
            $request->merge(['cash_counted' => $this->normalizeAmount($cashCounted)]);
        }

        $validated = $request->validate([
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
            (float) $validated['cash_counted'],
            $validated['reason'] ?? ''
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
                // Seguridad: un empleado no puede imprimir cortes de otro,
                // ignoramos cualquier employee_id enviado y usamos el de la sesión.
                $employee = $user;
            } else {
                $employee = User::withTrashed()->findOrFail($request->integer('employee'));
            }
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

        $viewData = [
            'totals' => $snapshot?->totals ?? $service->build($date, $employee),
            'date' => $date,
            'employee' => $employee,
            'cut' => $snapshot,
            'settings' => config('pos'),
        ];

        if (! $employee) {
            $viewData['breakdown'] = $service->employeeBreakdown($date->toDateString());
        }

        return view('pos.cuts.print', $viewData);
    }

    public function history(Request $request): View
    {
        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'employee' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : null;

        $employeeId = null;
        if ($request->filled('employee')) {
            if (! $canSeeAny) {
                abort(403, 'No puedes filtrar el historial por otro empleado.');
            }
            $employeeId = (int) $request->query('employee');
        } elseif (! $canSeeAny) {
            $employeeId = $user->id;
        }

        $cuts = DailyCut::query()
            ->with('generatedBy', 'user')
            ->when($from, fn ($q) => $q->where('cut_date', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->where('cut_date', '<=', $to->toDateString()))
            ->when($employeeId !== null, fn ($q) => $q->where('user_id', $employeeId))
            ->orderByDesc('cut_date')
            ->orderByDesc('version')
            ->paginate(20)
            ->withQueryString();

        $employees = collect();
        if ($canSeeAny) {
            $employees = User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'empleado']))
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('pos.cuts.history', [
            'cuts' => $cuts,
            'employees' => $employees,
            'selectedEmployee' => $employeeId,
            'filters' => [
                'from' => $from?->toDateString() ?? '',
                'to' => $to?->toDateString() ?? '',
                'employee' => $employeeId,
            ],
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

    /**
     * Cierra el turno/día del empleado autenticado.
     * Guarda un corte inmutable, estampa daily_cut_id en los abonos y pagos
     * del turno, y evita doble cierre.
     */
    public function closeMyDay(Request $request, DailyCutService $service): RedirectResponse
    {
        $user = $request->user();
        $date = Carbon::parse($request->input('date', today('America/Mexico_City')->toDateString()))->toDateString();

        // Evitar doble cierre: ¿ya existe un corte cerrado para este empleado hoy?
        $latest = DailyCut::latestVersion($date, $user);

        if ($latest && $latest->isClosed()) {
            return back()->withErrors(['close_my_day' => 'Ya cerraste el día para esta fecha. No se permite doble cierre.']);
        }

        // Si no existe versión previa, generamos una nueva. Si existe una abierta, la cerramos.
        if (! $latest) {
            $cuts = $service->generate($date, $user, $user);
            $latest = $cuts->first();
        }

        $latest->update(['closed_at' => now()]);

        // Estampar daily_cut_id en abonos y pagos del turno que aún no tengan corte asignado.
        $start = Carbon::parse($date)->startOfDay();
        $end = $start->copy()->endOfDay();

        CustomerPayment::query()
            ->whereNull('daily_cut_id')
            ->where('user_id', $user->id)
            ->whereBetween('happened_at', [$start, $end])
            ->update(['daily_cut_id' => $latest->id]);

        SupplierPayment::query()
            ->whereNull('daily_cut_id')
            ->where('user_id', $user->id)
            ->whereBetween('happened_at', [$start, $end])
            ->update(['daily_cut_id' => $latest->id]);

        $this->audit->log([
            'action' => 'cerrar_mi_dia',
            'entity_type' => 'daily_cut',
            'entity_id' => $latest->id,
            'before' => null,
            'after' => [
                'date' => $date,
                'scope' => DailyCut::SCOPE_EMPLOYEE,
                'employee_id' => $user->id,
                'cut_id' => $latest->id,
            ],
        ]);

        return redirect()
            ->route('pos.cuts.mine', ['date' => $date])
            ->with('status', "Día {$date} cerrado correctamente. Tus contadores se reiniciaron para el próximo turno.");
    }

    /**
     * Reabre el corte de empleado del día para el usuario autenticado,
     * permitiéndole seguir vendiendo en el mismo turno.
     */
    public function reopenMyDay(Request $request): RedirectResponse
    {
        $user = $request->user();
        $date = today()->toDateString();

        $cut = DailyCut::query()
            ->where('scope', DailyCut::SCOPE_EMPLOYEE)
            ->where('user_id', $user->id)
            ->whereDate('cut_date', $date)
            ->orderByDesc('version')
            ->first();

        if (! $cut) {
            return back()->withErrors(['reopen_my_day' => 'No tienes un corte de hoy para reabrir.']);
        }

        if (! $cut->isClosed()) {
            return back()->withErrors(['reopen_my_day' => 'Tu corte de hoy ya está abierto.']);
        }

        $cut->update(['closed_at' => null]);

        $this->audit->log([
            'action' => 'reabrir_mi_dia',
            'entity_type' => 'daily_cut',
            'entity_id' => $cut->id,
            'before' => null,
            'after' => [
                'date' => $date,
                'scope' => DailyCut::SCOPE_EMPLOYEE,
                'employee_id' => $user->id,
                'cut_id' => $cut->id,
            ],
        ]);

        return back()
            ->with('status', 'Tu corte del día se ha reabierto. Ya puedes registrar ventas nuevamente.');
    }

    /**
     * Detalle de abonos de clientes para el modal (solo lectura).
     */
    public function customerPaymentsDetail(Request $request, DailyCutService $service)
    {
        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');

        $request->validate([
            'date' => ['required', 'date'],
            'employee' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $date = Carbon::parse($request->query('date'))->toDateString();
        $employee = null;

        if ($request->filled('employee')) {
            if (! $canSeeAny) {
                abort(403, 'No puedes ver los abonos de otro empleado.');
            }
            $employee = User::withTrashed()->findOrFail($request->integer('employee'));
        } elseif (! $canSeeAny) {
            $employee = $user;
        }

        $payments = $service->customerPaymentsForCut($date, $employee);

        return response()->json([
            'date' => $date,
            'employee' => $employee ? ['id' => $employee->id, 'name' => $employee->name] : null,
            'total_cash' => round((float) $payments->where('method', 'cash')->sum('amount'), 2),
            'total_non_cash' => round((float) $payments->where('method', '!=', 'cash')->sum('amount'), 2),
            'items' => $payments->map(function ($p) {
                return [
                    'fecha' => $p->happened_at?->format('d/m/Y'),
                    'hora' => $p->happened_at?->format('H:i'),
                    'cliente' => $p->customer_name,
                    'monto' => (float) $p->amount,
                    'metodo' => $p->method,
                    'motivo' => $p->reason,
                    'empleado' => $p->user?->name ?? '—',
                ];
            }),
        ]);
    }

    /**
     * Detalle de pagos a proveedores para el modal (solo lectura).
     */
    public function supplierPaymentsDetail(Request $request, DailyCutService $service)
    {
        $user = $request->user();
        $canSeeAny = $user->can('pos.sales.day.any');

        $request->validate([
            'date' => ['required', 'date'],
            'employee' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $date = Carbon::parse($request->query('date'))->toDateString();
        $employee = null;

        if ($request->filled('employee')) {
            if (! $canSeeAny) {
                abort(403, 'No puedes ver los pagos de otro empleado.');
            }
            $employee = User::withTrashed()->findOrFail($request->integer('employee'));
        } elseif (! $canSeeAny) {
            $employee = $user;
        }

        $payments = $service->supplierPaymentsForCut($date, $employee);

        return response()->json([
            'date' => $date,
            'employee' => $employee ? ['id' => $employee->id, 'name' => $employee->name] : null,
            'total' => round((float) $payments->sum('amount'), 2),
            'items' => $payments->map(function ($p) {
                return [
                    'fecha' => $p->happened_at?->format('d/m/Y'),
                    'hora' => $p->happened_at?->format('H:i'),
                    'proveedor' => $p->supplier_name,
                    'monto' => (float) $p->amount,
                    'metodo' => $p->method,
                    'motivo' => $p->reason,
                    'empleado' => $p->user?->name ?? '—',
                ];
            }),
        ]);
    }

    /**
     * Convierte montos capturados como texto a formato numérico simple.
     *
     * Acepta separadores de miles ("14,325" o "14,325.50") y coma decimal
     * ("14325,50"). Devuelve el valor listo para la regla `numeric`.
     */
    private function normalizeAmount(string $value): string
    {
        $value = trim(preg_replace('/[^\d.,]/', '', $value) ?? '');

        if ($value === '') {
            return '';
        }

        $lastDot = strrpos($value, '.');
        $lastComma = strrpos($value, ',');

        if ($lastDot !== false && $lastComma !== false) {
            $decimal = max($lastDot, $lastComma);
            $integer = preg_replace('/[.,]/', '', substr($value, 0, $decimal)) ?? '';
            $fraction = preg_replace('/[.,]/', '', substr($value, $decimal + 1)) ?? '';

            return $integer.'.'.$fraction;
        }

        if ($lastComma !== false) {
            $decimals = strlen($value) - $lastComma - 1;

            return $decimals <= 2
                ? str_replace(',', '.', $value)
                : str_replace(',', '', $value);
        }

        return $value;
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
