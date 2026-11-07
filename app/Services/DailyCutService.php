<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CustomerPayment;
use App\Models\DailyCut;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SupplierPayment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DailyCutService
{
    /**
     * Build the daily cut totals for the whole business or one employee.
     *
     * Calculates two independent panels:
     *  - Panel 1 "Dinero registrado en computadora": from sales (cash only) + manual cash movements.
     *  - Panel 2 "Caja": from cash_movements table (all in/out movements).
     *
     * @return array<string, mixed>
     */
    public function build(CarbonInterface|string $date, ?User $employee = null): array
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = $start->copy()->endOfDay();

        $scopeFilter = function ($query) use ($employee): void {
            if ($employee) {
                $query->where('user_id', $employee->id);
            }
        };

        // ---------- Ventas cobradas (solo efectivo cuenta para dinero en caja) ----------
        $sales = Sale::query()
            ->paid()
            ->whereBetween('paid_at', [$start, $end])
            ->when($employee, fn ($query) => $query->where('user_id', $employee->id))
            ->with('items')
            ->get();

        $cashSales = $sales->where('payment_method', 'cash');
        $nonCashSales = $sales->where('payment_method', '!=', 'cash');

        $salesTotalCash = round((float) $cashSales->sum(fn (Sale $sale): float => (float) $sale->total), 2);
        $salesTotalNonCash = round((float) $nonCashSales->sum(fn (Sale $sale): float => (float) $sale->total), 2);
        $salesTotalAll = round($salesTotalCash + $salesTotalNonCash, 2);

        $salesSubtotal = round((float) $sales->sum(fn (Sale $sale): float => (float) $sale->subtotal), 2);
        $salesTax = round((float) $sales->sum(fn (Sale $sale): float => (float) $sale->tax_total), 2);

        // ---------- Movimientos de caja ----------
        $cash = CashMovement::query()
            ->whereBetween('happened_at', [$start, $end])
            ->when($employee, fn ($query) => $query->where('user_id', $employee->id))
            ->get();

        $initial = $this->sumMovement($cash, 'in', 'initial');
        $change = $this->sumMovement($cash, 'in', 'change');
        $otherIn = $this->sumMovement($cash, 'in', 'other');
        $supplierPaymentsLegacy = $this->sumMovement($cash, 'out', 'supplier_payment');
        $expenses = $this->sumMovement($cash, 'out', 'expense');
        $withdrawals = $this->sumMovement($cash, 'out', 'withdrawal');
        $otherOut = $this->sumMovement($cash, 'out', 'other');

        // ---------- Abonos de clientes y pagos a proveedores (tablas dedicadas) ----------
        $customerPaymentsAll = CustomerPayment::query()
            ->whereBetween('happened_at', [$start, $end])
            ->when($employee, fn ($query) => $query->where('user_id', $employee->id))
            ->get();

        $supplierPaymentsAll = SupplierPayment::query()
            ->whereBetween('happened_at', [$start, $end])
            ->when($employee, fn ($query) => $query->where('user_id', $employee->id))
            ->get();

        // Solo los abonos en EFECTIVO suman al dinero esperado en caja.
        $customerPaymentsCash = round((float) $customerPaymentsAll->where('method', 'cash')->sum('amount'), 2);
        $customerPaymentsNonCash = round((float) $customerPaymentsAll->where('method', '!=', 'cash')->sum('amount'), 2);
        $customerPaymentsTotal = round($customerPaymentsCash + $customerPaymentsNonCash, 2);

        // Los pagos a proveedores son egreso sin importar el método.
        $supplierPaymentsTotal = round((float) $supplierPaymentsAll->sum('amount'), 2);

        // Mantener la variable legacy para no romper código existente que la use.
        $customerPayments = $customerPaymentsCash;
        $supplierPayments = $supplierPaymentsTotal;

        // ---------- Devoluciones aprobadas ----------
        $returns = SaleReturn::query()
            ->where('status', SaleReturn::STATUS_APPROVED)
            ->whereBetween('approved_at', [$start, $end])
            ->when($employee, fn ($query) => $query->whereHas('sale', fn ($saleQuery) => $saleQuery->where('user_id', $employee->id)))
            ->with('items.saleItem')
            ->get();

        $refunds = round((float) $returns->sum(fn (SaleReturn $return): float => (float) $return->total), 2);

        // ---------- Panel 1: Dinero registrado en computadora ----------
        // Ingresos: dinero inicial + cambio + otros ingresos + pagos de clientes
        $computerCashIn = round($initial + $change + $otherIn, 2);
        // Ventas en efectivo cobradas
        $computerCashSales = $salesTotalCash;
        // Pagos de clientes en efectivo (manual category customer_payment)
        $computerCustomerPayments = $customerPayments;
        // Egresos: devoluciones + salidas (proveedores, gastos, retiros, otros)
        $computerRefunds = $refunds;
        $computerCashOut = round($supplierPayments + $expenses + $withdrawals + $otherOut, 2);

        $computerSubtotal = round(
            $computerCashIn + $computerCashSales + $computerCustomerPayments - $computerRefunds - $computerCashOut,
            2
        );

        // ---------- Panel 2: Caja (desde tabla de movimientos de caja) ----------
        // Incluye la categoría automática 'sale' (ventas en efectivo) y 'refund' (devoluciones)
        $cashBoxIn = round($initial + $change + $otherIn, 2);
        // Ventas en efectivo registradas como movimiento de caja (categoría 'sale')
        $boxSalesMovements = $this->sumMovement($cash, 'in', 'sale');
        // Pagos de clientes
        $boxCustomerPayments = $customerPayments;
        // Egresos: devoluciones (categoría 'refund') + salidas manuales
        $boxRefundsMovements = $this->sumMovement($cash, 'out', 'refund');
        $boxCashOut = round($supplierPayments + $expenses + $withdrawals + $otherOut, 2);

        $cashBoxSubtotal = round(
            $cashBoxIn + $boxSalesMovements + $boxCustomerPayments - $boxRefundsMovements - $boxCashOut,
            2
        );

        // ---------- Ganancia y departamentos ----------
        $profit = 0.0;
        $departments = [];

        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $profit += $item->profit();

                $key = $item->category_name ?: 'Sin departamento';
                $departments[$key] ??= ['name' => $key, 'quantity' => 0, 'total' => 0.0, 'ticket_ids' => []];
                $departments[$key]['quantity'] += (int) $item->quantity;
                $departments[$key]['total'] += (float) $item->line_total;
                $departments[$key]['ticket_ids'][$sale->id] = true;
            }
        }

        $profit = round($profit, 2);

        $returnedProfit = 0.0;
        foreach ($returns as $return) {
            foreach ($return->items as $returnItem) {
                $saleItem = $returnItem->saleItem;

                if (! $saleItem || $saleItem->quantity <= 0) {
                    continue;
                }

                $returnedProfit += ($saleItem->profit() / $saleItem->quantity) * $returnItem->quantity;
            }
        }

        $returnedProfit = round($returnedProfit, 2);

        $netSales = round($salesTotalAll - $refunds, 2);
        $netProfit = round($profit - $returnedProfit, 2);

        $departmentRows = array_values(array_map(function (array $department): array {
            return [
                'name' => $department['name'],
                'quantity' => $department['quantity'],
                'total' => round($department['total'], 2),
                'tickets' => count($department['ticket_ids']),
            ];
        }, $departments));

        usort($departmentRows, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        // ---------- Conciliación ----------
        $difference = round($cashBoxSubtotal - $computerSubtotal, 2);
        $isReconciled = abs($difference) < 0.005;

        return [
            'date' => $start->toDateString(),
            'generated_at' => now()->toIso8601String(),
            'employee' => $employee ? ['id' => $employee->id, 'name' => $employee->name] : null,
            'sales' => [
                'count' => $sales->count(),
                'subtotal' => $salesSubtotal,
                'tax' => $salesTax,
                'total_cash' => $salesTotalCash,
                'total_non_cash' => $salesTotalNonCash,
                'total' => $salesTotalAll,
            ],
            'computer_panel' => [
                'initial' => $initial,
                'change' => $change,
                'other_in' => $otherIn,
                'cash_in_total' => $computerCashIn,
                'cash_sales' => $computerCashSales,
                'customer_payments' => $computerCustomerPayments,
                'refunds' => $computerRefunds,
                'cash_out' => $computerCashOut,
                'subtotal' => $computerSubtotal,
            ],
            'cash_box_panel' => [
                'initial' => $initial,
                'change' => $change,
                'other_in' => $otherIn,
                'cash_in_total' => $cashBoxIn,
                'sales_movements' => $boxSalesMovements,
                'customer_payments' => $boxCustomerPayments,
                'refunds' => $boxRefundsMovements,
                'cash_out' => $boxCashOut,
                'subtotal' => $cashBoxSubtotal,
            ],
            'customer_payments' => $customerPaymentsTotal,
            'customer_payments_cash' => $customerPaymentsCash,
            'customer_payments_non_cash' => $customerPaymentsNonCash,
            'supplier_payments' => $supplierPaymentsTotal,
            'reconciliation' => [
                'computer_subtotal' => $computerSubtotal,
                'cash_box_subtotal' => $cashBoxSubtotal,
                'difference' => $difference,
                'is_reconciled' => $isReconciled,
            ],
            'cash_out' => [
                'suppliers' => $supplierPayments,
                'expenses' => $expenses,
                'withdrawals' => $withdrawals,
                'other' => $otherOut,
                'total' => $boxCashOut,
            ],
            'totals' => [
                'sales' => $salesTotalAll,
                'refunds' => $refunds,
                'net_sales' => $netSales,
                'profit' => $netProfit,
                'tickets' => $sales->count(),
            ],
            'departments' => $departmentRows,
            'returns' => [
                'count' => $returns->count(),
                'total' => $refunds,
            ],
        ];
    }

    /**
     * Record the physical cash count for a cut and compute the over/short.
     */
    public function recordCashCounted(DailyCut $cut, float $amount, string $reason = ''): DailyCut
    {
        // El conteo físico se compara contra el total mostrado en "Dinero en caja"
        // (movimientos de caja), que es el mismo "TOTAL (debe haber)" del corte.
        $totals = $cut->totals ?? [];
        $expected = (float) ($totals['cash_box_panel']['subtotal'] ?? $totals['computer_panel']['subtotal'] ?? 0);
        $difference = round($amount - $expected, 2);

        $cut->update([
            'cash_counted' => $amount,
            'cash_counted_at' => now(),
            'difference' => $difference,
            'is_reconciled' => abs($difference) < 0.005,
            'reason' => trim($reason) ?: $cut->reason,
        ]);

        return $cut->fresh();
    }

    /**
     * Generate and persist cuts.
     *
     * When $onlyEmployee is given, only that employee's cut is generated
     * (corte propio). No global cut, no other employees, no day closure.
     *
     * @return Collection<int, DailyCut>
     */
    public function generate(CarbonInterface|string $date, User $generatedBy, ?User $onlyEmployee = null): Collection
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = $start->copy()->endOfDay();
        $cutDate = $start->toDateString();

        // Corte propio de un empleado: aislado, nunca global ni de otros.
        if ($onlyEmployee) {
            $cut = $this->storeVersion(
                $cutDate,
                DailyCut::SCOPE_EMPLOYEE,
                $onlyEmployee->id,
                $this->build($start, $onlyEmployee),
                $generatedBy,
                notes: "Corte de {$onlyEmployee->name} generado el {$start->format('d/m/Y')}"
            );

            return collect([$cut]);
        }

        // Global cut (new version)
        $this->storeVersion(
            $cutDate,
            DailyCut::SCOPE_GLOBAL,
            null,
            $this->build($start),
            $generatedBy,
            notes: 'Corte global generado el '.$start->format('d/m/Y')
        );

        // Employee cuts
        $employeeIds = Sale::query()
            ->paid()
            ->whereBetween('paid_at', [$start, $end])
            ->distinct()
            ->pluck('user_id')
            ->merge(
                CashMovement::query()
                    ->whereBetween('happened_at', [$start, $end])
                    ->distinct()
                    ->pluck('user_id')
            );

        $returnUserIds = SaleReturn::query()
            ->where('status', SaleReturn::STATUS_APPROVED)
            ->whereBetween('approved_at', [$start, $end])
            ->with('sale')
            ->get()
            ->pluck('sale.user_id');

        $employeeIds = $employeeIds->merge($returnUserIds)->filter()->unique()->values();

        foreach ($employeeIds as $employeeId) {
            $employee = User::withTrashed()->find($employeeId);

            if (! $employee) {
                continue;
            }

            $this->storeVersion(
                $cutDate,
                DailyCut::SCOPE_EMPLOYEE,
                $employee->id,
                $this->build($start, $employee),
                $generatedBy,
                notes: "Corte de {$employee->name} generado el {$start->format('d/m/Y')}"
            );
        }

        return DailyCut::query()
            ->whereDate('cut_date', $cutDate)
            ->orderBy('scope')
            ->orderBy('user_id')
            ->orderByDesc('version')
            ->get();
    }

    /**
     * Store a new version of a daily cut (never overwrites).
     */
    public function storeVersion(
        string $cutDate,
        string $scope,
        ?int $userId,
        array $totals,
        User $generatedBy,
        string $notes = ''
    ): DailyCut {
        $attempts = 0;
        $maxAttempts = 3;

        while (true) {
            $attempts++;

            $cutKey = DailyCut::keyFor($cutDate, $scope, $userId);
            $version = DailyCut::nextVersionFor($cutDate, $scope, $userId);

            // Find previous latest version to link
            $previous = DailyCut::query()
                ->where('cut_key', $cutKey)
                ->orderByDesc('version')
                ->first();

            try {
                return DailyCut::create([
                    'cut_key' => $cutKey,
                    'cut_date' => $cutDate,
                    'scope' => $scope,
                    'user_id' => $userId,
                    'version' => $version,
                    'totals' => $totals,
                    'generated_by' => $generatedBy->id,
                    'generated_at' => now(),
                    'reason' => '',
                    'cash_counted' => null,
                    'cash_counted_at' => null,
                    'difference' => null,
                    'is_reconciled' => false,
                    'previous_cut_id' => $previous?->id,
                    'notes' => $notes,
                ]);
            } catch (QueryException $e) {
                if ($attempts >= $maxAttempts || ! $this->isUniqueVersionConflict($e)) {
                    throw $e;
                }

                // Colisión de versión por corte concurrente: recalcular y reintentar.
                continue;
            }
        }
    }

    private function isUniqueVersionConflict(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'unique')
            && (str_contains($message, 'cut_key') || str_contains($message, 'Duplicate entry'));
    }

    /**
     * Find the latest version of a cut for a given date and employee.
     */
    public function findLatestCut(string $date, ?User $employee = null): ?DailyCut
    {
        return DailyCut::latestVersion($date, $employee);
    }

    /**
     * Find all versions of a cut for a given date and employee.
     */
    public function findAllVersions(string $date, ?User $employee = null): Collection
    {
        $cutDate = Carbon::parse($date)->toDateString();
        $scope = $employee ? DailyCut::SCOPE_EMPLOYEE : DailyCut::SCOPE_GLOBAL;
        $cutKey = DailyCut::keyFor($cutDate, $scope, $employee?->id);

        return DailyCut::query()
            ->where('cut_key', $cutKey)
            ->orderByDesc('version')
            ->with('generatedBy')
            ->get();
    }

    private function sumMovement(Collection $movements, string $type, string $category): float
    {
        return round((float) $movements
            ->where('type', $type)
            ->where('category', $category)
            ->sum(fn (CashMovement $movement): float => (float) $movement->amount), 2);
    }

    /**
     * Devuelve los abonos de clientes de un día/empleado, con el empleado que los registró.
     * Usado por el modal de detalle de "Pagos de Clientes".
     *
     * @return Collection<int, CustomerPayment>
     */
    public function customerPaymentsForCut(string $date, ?User $employee = null): Collection
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = $start->copy()->endOfDay();

        return CustomerPayment::query()
            ->with('user')
            ->whereBetween('happened_at', [$start, $end])
            ->when($employee, fn ($query) => $query->where('user_id', $employee->id))
            ->orderByDesc('happened_at')
            ->get();
    }

    /**
     * Devuelve los pagos a proveedores de un día/empleado, con el empleado que los registró.
     * Usado por el modal de detalle de "Pagos a Proveedores".
     *
     * @return Collection<int, SupplierPayment>
     */
    public function supplierPaymentsForCut(string $date, ?User $employee = null): Collection
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = $start->copy()->endOfDay();

        return SupplierPayment::query()
            ->with('user')
            ->whereBetween('happened_at', [$start, $end])
            ->when($employee, fn ($query) => $query->where('user_id', $employee->id))
            ->orderByDesc('happened_at')
            ->get();
    }

    /**
     * Desglose del corte por empleado para el día indicado.
     *
     * Solo incluye empleados que tuvieron actividad ese día (ventas, movimientos
     * de caja, devoluciones o un corte generado). Cada fila trae su estado de
     * corte (done/pending) y el total global viene aparte para la sumatoria.
     *
     * @return array{employees: Collection<int, array>, total: array<string, mixed>}
     */
    public function employeeBreakdown(string $date): array
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = $start->copy()->endOfDay();
        $cutDate = $start->toDateString();

        $employeeIds = Sale::query()
            ->paid()
            ->whereBetween('paid_at', [$start, $end])
            ->distinct()
            ->pluck('user_id')
            ->merge(
                CashMovement::query()
                    ->whereBetween('happened_at', [$start, $end])
                    ->distinct()
                    ->pluck('user_id')
            )
            ->merge(
                SaleReturn::query()
                    ->where('status', SaleReturn::STATUS_APPROVED)
                    ->whereBetween('approved_at', [$start, $end])
                    ->with('sale')
                    ->get()
                    ->pluck('sale.user_id')
            )
            ->merge(
                DailyCut::query()
                    ->whereDate('cut_date', $cutDate)
                    ->whereNotNull('user_id')
                    ->distinct()
                    ->pluck('user_id')
            )
            ->filter()
            ->unique()
            ->values();

        $employees = User::query()
            ->whereKey($employeeIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $rows = $employees->map(function (User $employee) use ($start, $cutDate): array {
            $totals = $this->build($start, $employee);
            $latest = $this->findLatestCut($cutDate, $employee);

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'status' => $latest ? 'done' : 'pending',
                'latest_cut' => $latest,
                'tickets' => (int) ($totals['sales']['count'] ?? 0),
                'sales' => (float) ($totals['sales']['total'] ?? 0),
                'cash' => (float) ($totals['sales']['total_cash'] ?? 0),
                'refunds' => (float) ($totals['returns']['total'] ?? 0),
                'cash_out' => (float) ($totals['cash_out']['total'] ?? 0),
                'should_have' => (float) ($totals['computer_panel']['subtotal'] ?? 0),
            ];
        });

        $global = $this->build($start);
        $globalTotals = $global['totals'] ?? [];

        return [
            'employees' => $rows,
            'total' => [
                'tickets' => (int) ($globalTotals['tickets'] ?? 0),
                'sales' => (float) ($globalTotals['sales'] ?? 0),
                'cash' => (float) ($globalTotals['sales'] ?? 0),
                'refunds' => (float) ($globalTotals['refunds'] ?? 0),
                'net_sales' => (float) ($globalTotals['net_sales'] ?? 0),
                'profit' => (float) ($globalTotals['profit'] ?? 0),
            ],
        ];
    }
}
