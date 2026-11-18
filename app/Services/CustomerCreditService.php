<?php

namespace App\Services;

use App\Models\CustomerCredit;
use App\Models\CustomerPayment;
use App\Models\DailyCut;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CustomerCreditService
{
    public function __construct(
        private readonly DailyCutService $dailyCuts,
        private readonly CashMovementService $cashMovements,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Convierte un importe decimal validado a centavos enteros.
     */
    public function toCents(string|int|float $amount): int
    {
        if (is_int($amount)) {
            return $amount * 100;
        }

        $normalized = trim((string) $amount);

        if (! preg_match('/^\d{1,7}(\.\d{1,2})?$/', $normalized)) {
            throw ValidationException::withMessages(['amount' => 'El monto debe tener como máximo dos decimales.']);
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    /**
     * Representación decimal canónica que espera el registro monetario existente.
     */
    public static function decimal(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    /**
     * @param  array{customer_name: string, customer_phone?: ?string, description: string, total_amount: string, initial_payment?: ?string, method?: ?string}  $data
     */
    public function create(User $user, array $data, string $idempotencyKey): CustomerCredit
    {
        $totalCents = $this->toCents($data['total_amount']);
        $initialAmount = $data['initial_payment'] ?? null;
        $initialCents = ($initialAmount === null || $initialAmount === '') ? 0 : $this->toCents($initialAmount);
        $method = $data['method'] ?? null;

        if ($totalCents < 1) {
            throw ValidationException::withMessages(['total_amount' => 'La deuda debe ser mayor a cero.']);
        }

        if ($initialCents > $totalCents) {
            throw ValidationException::withMessages(['initial_payment' => 'El abono inicial no puede superar el total de la deuda.']);
        }

        if ($initialCents > 0 && ! in_array($method, CustomerCredit::METHODS, true)) {
            throw ValidationException::withMessages(['method' => 'Selecciona un método de pago válido.']);
        }

        $date = today()->toDateString();

        return $this->dailyCuts->withDayLock($date, function () use ($user, $data, $idempotencyKey, $totalCents, $initialCents, $method, $date): CustomerCredit {
            $existing = CustomerCredit::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                return $existing;
            }

            $this->assertDayOpen($user, $date);

            $credit = CustomerCredit::create([
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'description' => $data['description'],
                'total_cents' => $totalCents,
                'balance_cents' => $totalCents,
                'status' => CustomerCredit::STATUS_PENDING,
                'idempotency_key' => $idempotencyKey,
                'created_by' => $user->id,
            ]);

            if ($initialCents > 0) {
                $this->recordPayment($credit, $user, $initialCents, $method, $idempotencyKey.':initial');
            }

            $this->audit->log([
                'user' => $user,
                'action' => 'crear_credito',
                'entity_type' => 'customer_credit',
                'entity_id' => $credit->id,
                'before' => null,
                'after' => [
                    'customer_name' => $credit->customer_name,
                    'total_cents' => $credit->total_cents,
                    'initial_cents' => $initialCents,
                ],
            ]);

            return $credit->fresh();
        });
    }

    public function pay(CustomerCredit $credit, User $user, string $amount, string $method, string $idempotencyKey): CustomerPayment
    {
        $amountCents = $this->toCents($amount);

        if ($amountCents < 1) {
            throw ValidationException::withMessages(['amount' => 'El abono debe ser mayor a cero.']);
        }

        if (! in_array($method, CustomerCredit::METHODS, true)) {
            throw ValidationException::withMessages(['method' => 'Selecciona un método de pago válido.']);
        }

        $date = today()->toDateString();

        return $this->dailyCuts->withDayLock($date, function () use ($credit, $user, $amountCents, $method, $idempotencyKey, $date): CustomerPayment {
            $existing = CustomerPayment::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                if ((int) $existing->customer_credit_id !== (int) $credit->id) {
                    throw ValidationException::withMessages(['idempotency_key' => 'La operación ya fue registrada en otra cuenta.']);
                }

                return $existing;
            }

            $this->assertDayOpen($user, $date);

            return $this->recordPayment($credit, $user, $amountCents, $method, $idempotencyKey);
        });
    }

    private function recordPayment(CustomerCredit $credit, User $user, int $amountCents, string $method, string $idempotencyKey): CustomerPayment
    {
        $locked = CustomerCredit::query()->whereKey($credit->id)->lockForUpdate()->firstOrFail();

        if ($locked->isSettled() || $locked->balance_cents <= 0) {
            throw ValidationException::withMessages(['amount' => 'Esta cuenta ya está liquidada.']);
        }

        if ($amountCents > $locked->balance_cents) {
            throw ValidationException::withMessages(['amount' => 'El abono no puede superar el saldo pendiente.']);
        }

        $previousBalance = $locked->balance_cents;

        $payment = $this->cashMovements->create($user, [
            'category' => 'customer_payment',
            'amount' => self::decimal($amountCents),
            'method' => $method,
            'reason' => 'Abono a cartera manual',
            'customer_name' => $locked->customer_name,
            'customer_credit_id' => $locked->id,
            'idempotency_key' => $idempotencyKey,
            'happened_at' => Carbon::now(),
        ]);

        $balance = $locked->balance_cents - $amountCents;

        $locked->update([
            'balance_cents' => $balance,
            'status' => $balance === 0 ? CustomerCredit::STATUS_SETTLED : CustomerCredit::STATUS_PENDING,
        ]);

        $this->audit->log([
            'user' => $user,
            'action' => 'abonar_credito',
            'entity_type' => 'customer_credit',
            'entity_id' => $locked->id,
            'before' => ['balance_cents' => $previousBalance],
            'after' => [
                'payment_id' => $payment->id,
                'amount_cents' => $amountCents,
                'method' => $method,
                'balance_cents' => $balance,
            ],
        ]);

        return $payment;
    }

    /**
     * Las altas y abonos comparten el mismo bloqueo de día que los cierres.
     */
    private function assertDayOpen(User $user, string $date): void
    {
        if (DailyCut::isDayClosed($date)) {
            throw ValidationException::withMessages(['day' => 'El día ya está cerrado. No se pueden registrar movimientos de cartera.']);
        }

        $latest = DailyCut::latestVersion($date, $user);

        if ($latest?->isClosed()) {
            throw ValidationException::withMessages(['day' => 'Tu corte del día está cerrado. Reábrelo antes de registrar movimientos de cartera.']);
        }
    }
}
