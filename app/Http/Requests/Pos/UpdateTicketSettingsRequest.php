<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'business' => ['required', 'array:name,address,phone,tax_id'],
            'business.name' => ['required', 'string', 'max:100'],
            'business.address' => ['nullable', 'string', 'max:255'],
            'business.phone' => ['nullable', 'string', 'max:50'],
            'business.tax_id' => ['nullable', Rule::requiredIf($this->boolean('ticket.show_tax_id')), 'string', 'max:20'],
            'ticket' => ['required', 'array:width_mm,footer,policy,show_logo,show_tax_id,show_sku,show_cashier,show_tax,show_qr'],
            'ticket.width_mm' => ['required', 'integer', 'in:58,80'],
            'ticket.footer' => ['nullable', 'string', 'max:255'],
            'ticket.policy' => ['nullable', 'string', 'max:1000'],
            'ticket.show_logo' => ['required', 'boolean'],
            'ticket.show_tax_id' => ['required', 'boolean'],
            'ticket.show_sku' => ['required', 'boolean'],
            'ticket.show_cashier' => ['required', 'boolean'],
            'ticket.show_tax' => ['required', 'boolean'],
            'ticket.show_qr' => ['required', 'boolean'],
            'qr_image' => ['nullable', 'image', 'mimes:png', 'max:1024', 'dimensions:max_width=1200,max_height=1200'],
        ];
    }
}
