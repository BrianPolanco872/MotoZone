<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\DailyCut;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BitacoraController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $cuts = $this->cutsQuery($filters)->paginate(8)->withQueryString();
        $employees = User::withTrashed()
            ->where(function (Builder $query): void {
                $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->whereIn('name', ['admin', 'empleado']))
                    ->orWhereIn('id', DailyCut::query()
                        ->select('user_id')
                        ->where('scope', DailyCut::SCOPE_EMPLOYEE)
                        ->whereNotNull('user_id'));
            })
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name']);

        return view('pos.bitacora.index', [
            'cuts' => $cuts,
            'employees' => $employees,
            'filters' => $filters,
            'selectedEmployee' => $employees->firstWhere('id', $filters['employee']),
            'pdfUrl' => route('pos.bitacora.pdf', array_filter($filters, fn ($value): bool => $value !== null)),
        ]);
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->validatedFilters($request);
        $cuts = $this->cutsQuery($filters)->get();
        $selectedEmployee = $filters['employee'] === null
            ? null
            : User::withTrashed()->find($filters['employee']);

        $filenameDate = $filters['date'] ?? now()->format('Ymd');

        return Pdf::loadView('pos.bitacora.pdf', [
            'cuts' => $cuts,
            'filters' => $filters,
            'selectedEmployee' => $selectedEmployee,
            'generatedAt' => now(),
        ])
            ->setPaper('letter', 'landscape')
            ->download("bitacora-cortes-{$filenameDate}.pdf");
    }

    /**
     * @return array{date: ?string, employee: ?int}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'employee' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        return [
            'date' => $validated['date'] ?? null,
            'employee' => isset($validated['employee']) ? (int) $validated['employee'] : null,
        ];
    }

    /**
     * @param  array{date: ?string, employee: ?int}  $filters
     * @return Builder<DailyCut>
     */
    private function cutsQuery(array $filters): Builder
    {
        $scope = $filters['employee'] === null ? DailyCut::SCOPE_GLOBAL : DailyCut::SCOPE_EMPLOYEE;

        return DailyCut::query()
            ->with([
                'user' => fn (Builder $query) => $query->withTrashed()->select(['id', 'name']),
                'generatedBy' => fn (Builder $query) => $query->withTrashed()->select(['id', 'name']),
            ])
            ->where('scope', $scope)
            ->when($filters['date'] !== null, fn (Builder $query) => $query->where('cut_date', $filters['date']))
            ->when($filters['employee'] !== null, fn (Builder $query) => $query->where('user_id', $filters['employee']))
            ->orderByDesc('cut_date')
            ->orderByDesc('version')
            ->orderByDesc('id');
    }
}
