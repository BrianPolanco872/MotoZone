<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CustomerPayment;
use App\Models\SupplierPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CashMovementService
{
    /**
     * Create a manual cash movement, or a customer/supplier payment when the
     * category corresponds to one of those dedicated tables.
     *
     * @param  array{type?: string, category: string, amount: float|string, concept?: string, method?: string, customer_name?: string, customer_id?: int, supplier_name?: string, supplier_id?: int, reason?: string}  $data
     * @return CashMovement|CustomerPayment|SupplierPayment
     */
    public function create(User $user, array $data)
    {
        $category = (string) ($data['category'] ?? '');
        $definition = CashMovement::CATEGORIES[$category] ?? null;

        if (! $definition || ! $definition['manual']) {
            throw ValidationException::withMessages(['category' => 'Selecciona un tipo de movimiento válido.']);
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'El monto debe ser mayor a cero.']);
        }

        // --- Abono de cliente → tabla dedicada (no crea CashMovement) ---
        if ($category === 'customer_payment') {
            $payment = CustomerPayment::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'method' => $data['method'] ?? CashMovement::TYPE_IN,
                'reason' => $data['reason'] ?? null,
                'customer_name' => $data['customer_name'] ?? '',
                'customer_id' => $data['customer_id'] ?? null,
                'happened_at' => $data['happened_at'] ?? now(),
            ]);

            $this->audit('customer_payment.create', $user, $payment, null, $payment->getAttributes());

            return $payment;
        }

        // --- Pago a proveedor → tabla dedicada (no crea CashMovement) ---
        if ($category === 'supplier_payment') {
            $payment = SupplierPayment::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'method' => $data['method'] ?? CashMovement::TYPE_OUT,
                'reason' => $data['reason'] ?? '',
                'supplier_name' => $data['supplier_name'] ?? '',
                'supplier_id' => $data['supplier_id'] ?? null,
                'happened_at' => $data['happened_at'] ?? now(),
            ]);

            $this->audit('supplier_payment.create', $user, $payment, null, $payment->getAttributes());

            return $payment;
        }

        // --- Movimiento de caja tradicional ---
        $type = $definition['type'];

        if ($category === 'other' && in_array($data['type'] ?? null, [CashMovement::TYPE_IN, CashMovement::TYPE_OUT], true)) {
            $type = $data['type'];
        }

        $concept = trim((string) ($data['concept'] ?? ''));

        $movement = CashMovement::create([
            'user_id' => $user->id,
            'type' => $type,
            'category' => $category,
            'amount' => $amount,
            'concept' => $concept,
            'happened_at' => $data['happened_at'] ?? now(),
        ]);

        $this->audit('cash_movement.create', $user, $movement, null, $movement->getAttributes());

        return $movement;
    }

    /**
     * Archive a cash movement (soft). Does not affect historical cuts.
     */
    public function archive(CashMovement $movement, User $user): CashMovement
    {
        if ($movement->isArchived()) {
            throw ValidationException::withMessages(['movement' => 'Este movimiento ya está archivado.']);
        }

        $movement->update([
            'archivado_at' => now(),
            'archivado_por' => $user->id,
        ]);

        $this->audit('cash_movement.archive', $user, $movement, null, [
            'archivado_at' => $movement->archivado_at,
            'archivado_por' => $movement->archivado_por,
        ]);

        return $movement;
    }

    /**
     * Archive all cash movements before a given date (soft).
     *
     * @return int Number of archived movements.
     */
    public function archiveBefore(string $date, User $user): int
    {
        $cutoff = Carbon::parse($date)->startOfDay();

        $movements = CashMovement::query()
            ->whereNull('archivado_at')
            ->whereNull('deleted_at')
            ->where('happened_at', '<', $cutoff)
            ->get();

        foreach ($movements as $movement) {
            $movement->update([
                'archivado_at' => now(),
                'archivado_por' => $user->id,
            ]);

            $this->audit('cash_movement.archive_mass', $user, $movement, null, [
                'archivado_at' => $movement->archivado_at,
                'archivado_por' => $movement->archivado_por,
            ]);
        }

        return $movements->count();
    }

    /**
     * Restore an archived cash movement.
     */
    public function restore(CashMovement $movement): CashMovement
    {
        if (! $movement->isArchived()) {
            throw ValidationException::withMessages(['movement' => 'Este movimiento no está archivado.']);
        }

        $movement->update([
            'archivado_at' => null,
            'archivado_por' => null,
        ]);

        $this->audit('cash_movement.restore', auth()->user() ?? $movement->user, $movement, null, [
            'archivado_at' => null,
            'archivado_por' => null,
        ]);

        return $movement;
    }

    /**
     * Soft-delete a cash movement with a mandatory reason.
     * Cannot delete if the movement belongs to a closed cut.
     *
     * @param  string  $reason  Mandatory reason for deletion.
     */
    public function delete(CashMovement $movement, User $user, string $reason): CashMovement
    {
        if ($movement->isDeleted()) {
            throw ValidationException::withMessages(['movement' => 'Este movimiento ya fue eliminado.']);
        }

        if ($movement->isArchived()) {
            throw ValidationException::withMessages(['movement' => 'Un movimiento archivado no se puede eliminar. Réstauralo primero.']);
        }

        if ($movement->belongsToClosedCut()) {
            throw ValidationException::withMessages([
                'movement' => 'No se puede eliminar este movimiento porque pertenece a un corte cerrado. Solo se puede archivar.',
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'El motivo de eliminación es obligatorio.']);
        }

        $previous = $movement->getAttributes();

        // Usar query builder directo para evitar problemas con el flag exists
        // del modelo resuelto por route model binding.
        CashMovement::where('id', $movement->id)->update([
            'deleted_at' => now(),
            'deleted_by' => $user->id,
            'deletion_reason' => trim($reason),
        ]);

        // Refrescar atributos en memoria sin aplicar el scope de SoftDeletes
        // (fresh() aplicaría WHERE deleted_at IS NULL y no encontraría el registro).
        $movement->setRawAttributes(array_merge($previous, [
            'deleted_at' => now(),
            'deleted_by' => $user->id,
            'deletion_reason' => trim($reason),
            'updated_at' => now(),
        ]), true);

        $this->audit('cash_movement.delete', $user, $movement, $previous, [
            'deleted_at' => $movement->deleted_at,
            'deleted_by' => $movement->deleted_by,
            'deletion_reason' => $movement->deletion_reason,
        ]);

        return $movement;
    }

    /**
     * Audit log for sensitive cash movement actions.
     */
    private function audit(string $action, User $user, CashMovement $movement, ?array $before, array $after): void
    {
        Log::channel('stack')->info('cash_movement.audit', [
            'action' => $action,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'movement_id' => $movement->id,
            'movement_category' => $movement->category,
            'movement_amount' => $movement->amount,
            'before' => $before,
            'after' => $after,
            'ip' => request()->ip(),
            'created_at' => now()->toISOString(),
        ]);
    }
}
// Fase 2 - aplicado y verificado
