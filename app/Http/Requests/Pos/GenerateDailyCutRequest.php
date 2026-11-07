<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class GenerateDailyCutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['pos.cut.manage', 'pos.cut.own']) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
