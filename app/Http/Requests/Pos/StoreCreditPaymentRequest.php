<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCreditPaymentRequest extends FormRequest
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
            'amount' => ['required', 'string', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'method' => ['required', Rule::in(['cash', 'card', 'transfer'])],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.regex' => 'El abono debe ser un número con máximo dos decimales.',
            'method.required' => 'Selecciona el método de pago.',
            'method.in' => 'El método de pago no es válido.',
            'idempotency_key.required' => 'Recarga la página e inténtalo de nuevo.',
        ];
    }
}
