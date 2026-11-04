<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use Carbon\CarbonInterface;
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
        $customerPayments = $this->sumMovement($cash, 'in', 'customer_payment');
        $otherIn = $this->sumMovement($cash, 'in', 'other');
        $supplierPayments = $this->sumMovement($cash, 'out', 'supplier_payment');
        $expenses = $this->sumMovement($cash, 'out', 'expense');
        $withdrawals = $this->sumMovement($cash, 'out', 'withdrawal');
        $otherOut = $this->sumMovement($cash, 'out', 'other');

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
            'customer_payments' => $customerPayments,
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
        $computerSubtotal = (float) ($cut->totals['computer_panel']['subtotal'] ?? 0);
        $difference = round($amount - $computerSubtotal, 2);

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
     * Generate and persist the global cut plus one cut per employee with activity.
     * Creates a NEW version each time instead of overwriting.
     *
     * @return Collection<int, DailyCut>
     */
    public function generate(CarbonInterface|string $date, User $generatedBy): Collection
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = $start->copy()->endOfDay();
        $cutDate = $start->toDateString();

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
        $cutKey = DailyCut::keyFor($cutDate, $scope, $userId);
        $version = DailyCut::nextVersionFor($cutDate, $scope, $userId);

        // Find previous latest version to link
        $previous = DailyCut::query()
            ->where('cut_key', $cutKey)
            ->orderByDesc('version')
            ->first();

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
}
