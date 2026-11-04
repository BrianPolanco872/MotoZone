<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class ChargePendingSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pos.sell') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount_paid' => $this->filled('amount_paid') ? (float) $this->input('amount_paid') : null,
            'payment_method' => $this->input('payment_method', 'cash'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:cash,card,transfer,mixed'],
        ];
    }
}
