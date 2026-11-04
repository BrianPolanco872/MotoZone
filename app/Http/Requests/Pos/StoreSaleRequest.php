<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pos.sell') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(fn (array $item): array => [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_price' => isset($item['unit_price']) && is_numeric($item['unit_price']) ? (float) $item['unit_price'] : null,
                'wholesale' => filter_var($item['wholesale'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])
            ->all();

        $this->merge([
            'items' => $items,
            'status' => $this->input('status', 'paid'),
            'amount_paid' => $this->filled('amount_paid') ? (float) $this->input('amount_paid') : null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.wholesale' => ['boolean'],
            'status' => ['required', Rule::in(['paid', 'pending'])],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:cash,card,transfer,mixed'],
            'idempotency_key' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Agrega al menos un producto a la venta.',
            'items.min' => 'Agrega al menos un producto a la venta.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a cero.',
            'amount_paid.min' => 'El pago no puede ser negativo.',
            'idempotency_key.required' => 'No se pudo identificar la venta. Recarga la página e inténtalo de nuevo.',
        ];
    }
}
