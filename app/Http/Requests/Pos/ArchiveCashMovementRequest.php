<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class ArchiveCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pos.cash.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'before_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'before_date.required' => 'Selecciona la fecha límite para archivar.',
            'before_date.date' => 'La fecha no es válida.',
        ];
    }
}
