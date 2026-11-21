<?php

namespace App\Http\Requests\Pos;

use App\Support\Quantity;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pos.returns.request') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'sale_id' => ['required', 'integer', Rule::exists('sales', 'id')],
            'reason' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array'],
            'items.*.sale_item_id' => ['required', 'integer', 'distinct', Rule::exists('sale_items', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'min:0', 'max:9999', function (string $attribute, mixed $value, Closure $fail): void {
                if (! Quantity::valid($value)) {
                    $fail('La cantidad debe tener como máximo tres decimales.');
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sale_id.required' => 'Selecciona la venta a devolver.',
            'reason.required' => 'Escribe el motivo de la devolución.',
            'items.required' => 'Selecciona al menos un producto.',
            'items.min' => 'Selecciona al menos un producto.',
        ];
    }
}
