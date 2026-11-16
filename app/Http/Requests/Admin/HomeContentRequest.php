<?php

namespace App\Http\Requests\Admin;

use App\Models\HomePage;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class HomeContentRequest extends FormRequest
{
    public const BLOCK_FIELDS = [
        'hero' => ['hero_eyebrow', 'hero_title', 'hero_description', 'hero_button', 'hero_button_url', 'hero_image_alt', 'show_hero'],
        'categories' => ['categories_title', 'categories_description', 'show_categories'],
        'products' => ['products_title', 'products_description', 'show_products'],
        'new' => ['new_title', 'new_description', 'show_new'],
        'decoration' => ['auto_decoration'],
        'contact' => ['store_name', 'store_description', 'phone', 'whatsapp', 'email', 'hours', 'whatsapp_message', 'show_whatsapp_bubble', 'show_email_bubble'],
        'location' => ['address', 'location_reference', 'maps_url', 'map_mode', 'map_embed_url', 'latitude', 'longitude', 'show_location'],
        'information' => ['store_notice', 'advice_title', 'advice_description', 'benefit_1_title', 'benefit_1_description', 'benefit_2_title', 'benefit_2_description', 'benefit_3_title', 'benefit_3_description', 'benefit_4_title', 'benefit_4_description', 'metric_1_value', 'metric_1_label', 'metric_2_value', 'metric_2_label', 'metric_3_value', 'metric_3_label', 'show_notice', 'show_benefits', 'show_metrics', 'show_advice', 'footer_description', 'address', 'maps_url', 'hours', 'whatsapp', 'phone', 'email', 'facebook_url', 'instagram_url', 'copyright_text'],
    ];

    public const BLOCK_ANCHORS = ['hero' => 'presentacion', 'categories' => 'categorias', 'products' => 'destacados', 'new' => 'nuevos', 'decoration' => 'decoracion', 'contact' => 'contacto', 'location' => 'ubicacion', 'information' => 'informacion'];

    protected function getRedirectUrl(): string
    {
        return route('admin.home.edit').'#'.self::BLOCK_ANCHORS[$this->block()];
    }

    public function block(): string
    {
        $block = $this->input('block');

        return is_string($block) && isset(self::BLOCK_FIELDS[$block]) ? $block : 'hero';
    }

    public function authorize(): bool
    {
        return ($this->user()?->is_active && $this->user()?->hasRole('admin')) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $fields = $this->has('block')
            ? self::BLOCK_FIELDS[$this->block()]
            : array_merge(...array_values(self::BLOCK_FIELDS));
        $hero = ! $this->has('block') || $this->block() === 'hero';
        $rules = [
            'block' => ['sometimes', 'string', 'in:'.implode(',', array_keys(self::BLOCK_FIELDS))],
            'content' => ['sometimes', 'array:'.implode(',', $fields)],
            'hero_image' => [$hero ? 'nullable' : 'prohibited', 'bail', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:max_width=6000,max_height=6000'],
            'hero_image_url' => [$hero ? 'sometimes' : 'prohibited', 'nullable', 'string', 'max:2048', new HttpsImageUrl],
            'remove_hero_image' => [$hero ? 'sometimes' : 'prohibited', 'boolean'],
            'gallery' => [$hero ? 'sometimes' : 'prohibited', 'array', 'max:6'],
            'gallery.*' => [$hero ? 'sometimes' : 'prohibited', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:max_width=6000,max_height=6000'],
            'gallery_url' => [$hero ? 'sometimes' : 'prohibited', 'nullable', 'string', 'max:2048', new HttpsImageUrl],
            'remove_gallery' => [$hero ? 'sometimes' : 'prohibited', 'array'],
            'remove_gallery.*' => ['string', 'max:2048'],
        ];

        foreach ([...HomePage::TEXT_FIELDS, ...HomePage::CONTACT_FIELDS, ...HomePage::LOCATION_FIELDS, ...HomePage::INFORMATION_FIELDS, ...HomePage::FOOTER_FIELDS] as $key => $label) {
            if (in_array($key, $fields, true)) {
                $required = array_key_exists($key, HomePage::TEXT_FIELDS) && (str_ends_with($key, '_title') || $key === 'hero_button');
                $max = match ($key) {
                    'map_embed_url' => 8192,
                    'hero_button_url', 'maps_url', 'facebook_url', 'instagram_url' => 2048,
                    'phone', 'whatsapp' => 32,
                    'email' => 254,
                    'address', 'location_reference', 'store_notice', 'copyright_text' => 500,
                    'hours', 'whatsapp_message', 'footer_description', 'store_description' => 1000,
                    default => str_ends_with($key, '_description') ? 1000 : 160,
                };
                $rules['content.'.$key] = ['bail', 'sometimes', $required ? 'required' : 'nullable', 'string', 'max:'.$max];
            }
        }
        if (in_array('hero_button_url', $fields, true)) {
            $rules['content.hero_button_url'][] = 'regex:/^(\/[^\s]*|https:\/\/[^\s]+)$/';
        }

        foreach (['show_hero', 'show_categories', 'show_products', 'show_new', 'auto_decoration', 'show_location', 'show_notice', 'show_benefits', 'show_metrics', 'show_advice', 'show_whatsapp_bubble', 'show_email_bubble'] as $key) {
            if (in_array($key, $fields, true)) {
                $rules['content.'.$key] = ['sometimes', 'required', 'boolean'];
            }
        }

        if (in_array('email', $fields, true)) {
            $rules['content.email'][] = 'email:filter';
            foreach (['phone', 'whatsapp'] as $key) {
                $rules['content.'.$key][] = function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value !== null && trim((string) $value) !== '' && HomePage::normalizePhoneNumber($value) === null) {
                        $fail('Escribe de 7 a 15 dígitos, con código de país para WhatsApp; puedes usar +, espacios, guiones o paréntesis.');
                    }
                };
            }
        }

        if (in_array('map_mode', $fields, true)) {
            $rules['content.map_mode'][] = 'in:google,pin';
            $rules['content.latitude'] = ['bail', 'sometimes', 'nullable', 'numeric', 'between:-90,90'];
            $rules['content.longitude'] = ['bail', 'sometimes', 'nullable', 'numeric', 'between:-180,180'];
            foreach (['maps_url', 'map_embed_url'] as $key) {
                $rules['content.'.$key][] = function (string $attribute, mixed $value, Closure $fail) use ($key): void {
                    if (! HomePage::isGoogleMapsUrl($value, $key === 'map_embed_url')) {
                        $fail($key === 'map_embed_url'
                            ? 'Pega solo la URL HTTPS de Google Maps > Compartir > Insertar mapa (src), no el HTML ni un enlace corto.'
                            : 'Escribe un enlace HTTPS de Google Maps para llegar al mostrador.');
                    }
                };
            }
        } elseif (in_array('maps_url', $fields, true)) {
            $rules['content.maps_url'][] = function (string $attribute, mixed $value, Closure $fail): void {
                if ($value !== null && trim((string) $value) !== '' && ! HomePage::isGoogleMapsUrl($value)) {
                    $fail('Escribe un enlace HTTPS de Google Maps para llegar al mostrador.');
                }
            };
        }

        return $rules;
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ($this->has('block') && $this->block() !== 'location')) {
                return;
            }

            $input = $this->input('content', []);
            if (array_intersect(array_keys($input), self::BLOCK_FIELDS['location']) === []) {
                return;
            }

            $content = array_replace(HomePage::current()->content ?? [], $input);
            $latitude = $content['latitude'] ?? null;
            $longitude = $content['longitude'] ?? null;
            $hasLatitude = $latitude !== null && $latitude !== '';
            $hasLongitude = $longitude !== null && $longitude !== '';

            if (($content['map_mode'] ?? '') === 'pin' || $hasLatitude || $hasLongitude) {
                if (! $hasLatitude) {
                    $validator->errors()->add('content.latitude', 'Indica la latitud y la longitud juntas para colocar el pin.');
                }
                if (! $hasLongitude) {
                    $validator->errors()->add('content.longitude', 'Indica la latitud y la longitud juntas para colocar el pin.');
                }
            }

            if (($content['map_mode'] ?? '') === 'google' && empty($content['map_embed_url'])) {
                $validator->errors()->add('content.map_embed_url', 'Indica la URL de inserción de Google Maps para mostrar este mapa.');
            }
        }, function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ($this->has('block') && $this->block() !== 'hero')) {
                return;
            }

            $page = HomePage::current();
            $kept = array_diff($page->gallery ?? [], $this->input('remove_gallery', []));
            if (count($kept) + count($this->file('gallery', [])) + (int) $this->filled('gallery_url') > 6) {
                $validator->errors()->add('gallery', 'La presentación admite hasta 6 fotos.');
            }

            if ($this->boolean('remove_hero_image') && ($this->hasFile('hero_image') || $this->filled('hero_image_url'))
                || $this->hasFile('hero_image') && $this->filled('hero_image_url')) {
                $validator->errors()->add('hero_image_url', 'Elige subir un archivo, pegar un enlace o quitar la foto; no combines opciones.');
            }

            $hasImage = $this->hasFile('hero_image')
                || $this->filled('hero_image_url')
                || $this->filled('gallery_url')
                || $kept !== []
                || ($page->heroImageUrl() && ! $this->boolean('remove_hero_image'))
                || ($page->hero_image_path && ! $this->boolean('remove_hero_image'));
            if ($hasImage && trim((string) $this->input('content.hero_image_alt', $page->text('hero_image_alt'))) === '') {
                $validator->errors()->add('content.hero_image_alt', 'Describe la imagen principal para quienes no pueden verla.');
            }
        }];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attributes = ['hero_image' => 'imagen principal', 'hero_image_url' => 'enlace de la imagen principal', 'gallery_url' => 'enlace de galería', 'content' => 'contenido'];
        foreach ([...HomePage::TEXT_FIELDS, ...HomePage::CONTACT_FIELDS, ...HomePage::LOCATION_FIELDS, ...HomePage::INFORMATION_FIELDS, ...HomePage::FOOTER_FIELDS] as $key => $label) {
            $attributes['content.'.$key] = mb_strtolower($label);
        }

        return $attributes;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'max.string' => 'El campo :attribute no debe superar :max caracteres.',
            'boolean' => 'La visibilidad debe ser sí o no.',
            'content.array' => 'El contenido contiene campos no permitidos.',
            'content.email.email' => 'Escribe un correo de contacto válido.',
            'content.map_mode.in' => 'Selecciona Google Maps o pin por coordenadas.',
            'content.latitude.numeric' => 'La latitud debe ser un número.',
            'content.longitude.numeric' => 'La longitud debe ser un número.',
            'content.latitude.between' => 'La latitud debe estar entre -90 y 90.',
            'content.longitude.between' => 'La longitud debe estar entre -180 y 180.',
            'content.hero_button_url.regex' => 'Escribe una ruta interna (/#destacados) o un enlace HTTPS.',
            'hero_image.image' => 'El archivo debe ser una imagen.',
            'hero_image.mimes' => 'Usa una imagen JPEG, PNG o WebP.',
            'hero_image.max' => 'La imagen no debe superar los 20 MB.',
            'hero_image.dimensions' => 'La imagen no debe superar 6000 × 6000 píxeles.',
            'hero_image_url.max' => 'El enlace de la imagen no debe superar 2048 caracteres.',
            'gallery_url.max' => 'El enlace de galería no debe superar 2048 caracteres.',
            'gallery.array' => 'Envía las fotos como una lista.',
            'gallery.max' => 'La presentación admite hasta 6 fotos.',
            'gallery.*.image' => 'Cada archivo debe ser una imagen.',
            'gallery.*.mimes' => 'Usa imágenes JPEG, PNG o WebP.',
            'gallery.*.max' => 'Cada imagen no debe superar los 20 MB.',
            'gallery.*.dimensions' => 'Cada imagen no debe superar 6000 × 6000 píxeles.',
        ];
    }
}
