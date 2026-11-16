<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.products.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'featured' => $this->boolean('featured'),
            'is_active' => $this->boolean('is_active'),
            'track_inventory' => $this->has('track_inventory') ? $this->boolean('track_inventory') : true,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')->ignore($product?->id),
                Rule::unique('products', 'barcode2')->ignore($product?->id),
            ],
            'barcode2' => [
                'nullable',
                'string',
                'max:255',
                'different:barcode',
                Rule::unique('products', 'barcode2')->ignore($product?->id),
                Rule::unique('products', 'barcode')->ignore($product?->id),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'between:0,100'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'track_inventory' => ['boolean'],
            'featured' => ['boolean'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del producto es obligatorio.',
            'brand.required' => 'La marca es obligatoria.',
            'description.required' => 'La descripción es obligatoria.',
            'barcode.unique' => 'Ya existe un producto con ese código de barras.',
            'barcode2.unique' => 'Ese código de barras ya está registrado en otro producto.',
            'barcode2.different' => 'El segundo código debe ser diferente del primero.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio no puede ser negativo.',
            'cost.min' => 'El costo no puede ser negativo.',
            'wholesale_price.min' => 'El precio de mayoreo no puede ser negativo.',
            'min_stock.min' => 'El mínimo de existencia no puede ser negativo.',
            'min_stock.integer' => 'Las existencias mínimas deben ser un número entero.',
            'tax_rate.required' => 'La tasa de IVA es obligatoria.',
            'tax_rate.between' => 'La tasa de IVA debe estar entre 0 y 100.',
            'stock.required' => 'La existencia es obligatoria.',
            'stock.min' => 'La existencia no puede ser negativa.',
            'stock.integer' => 'Las existencias deben ser un número entero.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.max' => 'La imagen no debe superar los 2 MB.',
        ];
    }
}
