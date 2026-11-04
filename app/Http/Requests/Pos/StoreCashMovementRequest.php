<?php

namespace App\Http\Requests\Pos;

use App\Models\CashMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pos.cash.manage') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(array_keys(CashMovement::manualCategories()))],
            'type' => ['nullable', Rule::in([CashMovement::TYPE_IN, CashMovement::TYPE_OUT])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'concept' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Selecciona el tipo de movimiento.',
            'category.in' => 'El tipo de movimiento no es válido.',
            'amount.required' => 'Escribe el monto.',
            'amount.min' => 'El monto debe ser mayor a cero.',
            'concept.required' => 'Escribe el motivo del movimiento.',
        ];
    }
}
