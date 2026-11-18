<?php

namespace App\Http\Requests\Admin;

use App\Models\HomeCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class HomeCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->is_active && $this->user()?->hasRole('admin')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);

        if (($this->route('card')?->section ?? $this->input('section')) !== 'products') {
            return;
        }

        foreach (['price', 'sale_price'] as $field) {
            $value = $this->input($field);

            if (is_string($value) && preg_match('/\A[0-9]{1,3}(?:,[0-9]{3})+(?:\.[0-9]{1,2})?\z/', $value) === 1) {
                $this->merge([$field => str_replace(',', '', $value)]);
            }
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $card = $this->route('card');
        $isCategory = ($card?->section ?? $this->input('section')) === 'categories';

        return [
            'slug' => ['exclude'],
            'icon' => [Rule::excludeIf(! $isCategory), 'sometimes', 'nullable', 'string', Rule::in(array_keys(HomeCard::CATEGORY_ICONS))],
            'sort_order' => ['exclude'],
            'section' => ['required', Rule::in(HomeCard::SECTIONS)],
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => [Rule::excludeIf($isCategory), 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'bail', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:max_width=6000,max_height=6000'],
            'image_url' => ['sometimes', 'nullable', 'string', 'max:2048', new HttpsImageUrl],
            'remove_image' => ['sometimes', 'boolean'],
            'gallery' => ['sometimes', 'array', 'max:6'],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:max_width=6000,max_height=6000'],
            'gallery_url' => ['sometimes', 'nullable', 'string', 'max:2048', new HttpsImageUrl],
            'remove_gallery' => ['sometimes', 'array'],
            'remove_gallery.*' => ['string', 'max:2048'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:99999999', Rule::when($this->filled('price'), 'lte:price')],
            'badge' => [Rule::excludeIf($isCategory), 'nullable', 'string', 'max:60'],
            'link_url' => [Rule::excludeIf($isCategory), 'nullable', 'string', 'max:2048', 'regex:/^(\/[^\s]*|https:\/\/[^\s]+)$/'],
            'link_label' => [Rule::excludeIf($isCategory), 'nullable', 'string', 'max:80'],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $current = $this->route('card')?->gallery ?? [];
            $kept = array_diff($current, $this->input('remove_gallery', []));
            if (count($kept) + count($this->file('gallery', [])) + (int) $this->filled('gallery_url') > 6) {
                $validator->errors()->add('gallery', 'Cada card admite hasta 6 fotos.');
            }

            if ($this->boolean('remove_image') && ($this->hasFile('image') || $this->filled('image_url'))
                || $this->hasFile('image') && $this->filled('image_url')) {
                $validator->errors()->add('image_url', 'Elige subir un archivo, pegar un enlace o quitar la foto; no combines opciones.');
            }
        }];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'title' => 'título', 'subtitle' => 'subtítulo', 'description' => 'descripción',
            'image' => 'foto principal', 'image_url' => 'enlace de la foto principal', 'gallery' => 'fotos', 'gallery_url' => 'enlace de galería', 'price' => 'precio',
            'sale_price' => 'precio de oferta', 'badge' => 'etiqueta', 'link_url' => 'enlace',
            'link_label' => 'texto del enlace', 'product_id' => 'producto', 'section' => 'sección',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'max.string' => 'El campo :attribute no debe superar :max caracteres.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'min' => 'El campo :attribute no puede ser negativo.',
            'sale_price.lte' => 'El precio de oferta no puede ser mayor que el precio regular.',
            'image.image' => 'La foto principal debe ser una imagen.',
            'image.mimes' => 'Usa una imagen JPEG, PNG o WebP.',
            'image.max' => 'La foto principal no debe superar los 20 MB.',
            'image.dimensions' => 'La foto principal no debe superar 6000 × 6000 píxeles.',
            'image_url.max' => 'El enlace de la foto no debe superar 2048 caracteres.',
            'gallery_url.max' => 'El enlace de galería no debe superar 2048 caracteres.',
            'gallery.array' => 'Envía las fotos como una lista.',
            'gallery.max' => 'Cada card admite hasta 6 fotos.',
            'gallery.*.image' => 'Cada archivo debe ser una imagen.',
            'gallery.*.mimes' => 'Usa imágenes JPEG, PNG o WebP.',
            'gallery.*.max' => 'Cada imagen no debe superar los 20 MB.',
            'gallery.*.dimensions' => 'Cada imagen no debe superar 6000 × 6000 píxeles.',
            'link_url.regex' => 'Escribe una ruta interna (/#destacados) o un enlace HTTPS.',
            'product_id.exists' => 'Elige un producto activo del inventario.',
        ];
    }
}
