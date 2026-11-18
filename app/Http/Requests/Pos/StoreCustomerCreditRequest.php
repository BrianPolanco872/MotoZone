<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'empleado']) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'description' => ['required', 'string', 'max:2000'],
            'total_amount' => ['required', 'string', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'initial_payment' => ['nullable', 'string', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'method' => [
                Rule::requiredIf(fn (): bool => (float) $this->input('initial_payment', 0) > 0),
                'nullable',
                Rule::in(['cash', 'card', 'transfer']),
            ],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'total_amount.regex' => 'El total debe ser un número positivo con máximo dos decimales.',
            'initial_payment.regex' => 'El abono inicial debe tener máximo dos decimales.',
            'initial_payment.lte' => 'El abono inicial no puede superar el total de la deuda.',
            'method.required' => 'Selecciona el método de pago del abono inicial.',
            'method.in' => 'El método de pago no es válido.',
            'idempotency_key.required' => 'Recarga la página e inténtalo de nuevo.',
        ];
    }
}
