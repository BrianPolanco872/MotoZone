<?php

namespace App\Models;

use Database\Factories\HomePageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HomePage extends Model
{
    /** @use HasFactory<HomePageFactory> */
    use HasFactory;

    public const TEXT_FIELDS = [
        'hero_eyebrow' => 'Presentación breve',
        'hero_title' => 'Título principal',
        'hero_description' => 'Descripción principal',
        'hero_button' => 'Texto del botón',
        'hero_button_url' => 'Enlace del botón',
        'hero_image_alt' => 'Descripción de la imagen principal',
        'categories_title' => 'Título de categorías',
        'categories_description' => 'Descripción de categorías',
        'products_title' => 'Título de productos',
        'products_description' => 'Descripción de productos',
        'new_title' => 'Título de recién llegados',
        'new_description' => 'Descripción de recién llegados',
    ];

    public const DEFAULT_CONTENT = [
        'hero_eyebrow' => 'MotoZone',
        'hero_title' => 'Refacciones y accesorios para tu moto',
        'hero_description' => 'Descubre las piezas y accesorios de MotoZone.',
        'hero_button' => 'Ver novedades',
        'hero_button_url' => '/#nuevos',
        'hero_image_alt' => '',
        'categories_title' => 'Explora por categoría',
        'categories_description' => 'Descubre nuestras selecciones de portada.',
        'products_title' => 'Productos de portada',
        'products_description' => 'Una selección de piezas y accesorios de MotoZone.',
        'new_title' => 'Recién llegados',
        'new_description' => 'Lo más reciente en piezas y accesorios.',
        'footer_description' => 'Refacciones y accesorios para tu moto. Calidad y servicio garantizado.',
        'copyright_text' => '© 2026 MotoZone. Todos los derechos reservados.',
        'facebook_url' => '',
        'instagram_url' => '',
        'show_hero' => true,
        'show_categories' => true,
        'show_products' => true,
        'show_new' => true,
        'auto_decoration' => true,
    ];

    public const CONTACT_FIELDS = [
        'store_name' => 'Nombre del mostrador',
        'store_description' => 'Descripción del mostrador',
        'phone' => 'Teléfono',
        'whatsapp' => 'WhatsApp con código de país',
        'email' => 'Correo de contacto',
        'hours' => 'Horarios de atención',
        'whatsapp_message' => 'Mensaje inicial de WhatsApp',
    ];

    public const LOCATION_FIELDS = [
        'address' => 'Dirección',
        'location_reference' => 'Referencias para llegar',
        'maps_url' => 'Enlace para llegar en Google Maps',
        'map_mode' => 'Tipo de mapa',
        'map_embed_url' => 'URL de inserción de Google Maps',
        'latitude' => 'Latitud',
        'longitude' => 'Longitud',
    ];

    public const INFORMATION_FIELDS = [
        'store_notice' => 'Aviso del mostrador',
        'advice_title' => 'Título de asesoría',
        'advice_description' => 'Descripción de asesoría',
        'benefit_1_title' => 'Beneficio 1: título',
        'benefit_1_description' => 'Beneficio 1: descripción',
        'benefit_2_title' => 'Beneficio 2: título',
        'benefit_2_description' => 'Beneficio 2: descripción',
        'benefit_3_title' => 'Beneficio 3: título',
        'benefit_3_description' => 'Beneficio 3: descripción',
        'benefit_4_title' => 'Beneficio 4: título',
        'benefit_4_description' => 'Beneficio 4: descripción',
        'metric_1_value' => 'Indicador 1: valor',
        'metric_1_label' => 'Indicador 1: etiqueta',
        'metric_2_value' => 'Indicador 2: valor',
        'metric_2_label' => 'Indicador 2: etiqueta',
        'metric_3_value' => 'Indicador 3: valor',
        'metric_3_label' => 'Indicador 3: etiqueta',
    ];

    public const FOOTER_FIELDS = [
        'footer_description' => 'Descripción corta del negocio',
        'facebook_url' => 'Enlace de Facebook',
        'instagram_url' => 'Enlace de Instagram',
        'copyright_text' => 'Texto de Copyright',
    ];

    protected $fillable = ['content', 'hero_image_path', 'gallery'];

    protected function casts(): array
    {
        return ['content' => 'array', 'gallery' => 'array'];
    }

    public static function current(): self
    {
        return self::query()->find(1) ?? new self(['content' => []]);
    }

    /** Must be called inside the transaction that performs the editorial write. */
    public static function lockForEditing(): self
    {
        if (! self::query()->whereKey(1)->exists()) {
            DB::table('home_pages')->insertOrIgnore([
                'id' => 1,
                'content' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return self::query()->lockForUpdate()->findOrFail(1);
    }

    public function text(string $key): string
    {
        return (string) ($this->content[$key] ?? self::DEFAULT_CONTENT[$key] ?? '');
    }

    public function enabled(string $section): bool
    {
        return (bool) ($this->content['show_'.$section] ?? self::DEFAULT_CONTENT['show_'.$section] ?? false);
    }

    public function heroButtonUrl(): ?string
    {
        $url = $this->text('hero_button_url');

        return $url !== '' && rtrim((string) parse_url($url, PHP_URL_PATH), '/') !== '/catalogo' ? $url : null;
    }

    public function heroImageUrl(): ?string
    {
        return ($this->content['hero_image_url'] ?? '') ?: ($this->hero_image_path ? Storage::disk('public')->url($this->hero_image_path) : null);
    }

    public static function normalizePhoneNumber(string $number): ?string
    {
        $number = preg_replace('/[\s().-]/u', '', trim($number));

        return $number !== null && preg_match('/^\+?[1-9][0-9]{6,14}$/D', $number) === 1 ? $number : null;
    }

    public function phoneUrl(): ?string
    {
        $number = self::normalizePhoneNumber($this->text('phone'));

        return $number !== null ? 'tel:'.$number : null;
    }

    public function whatsappUrl(?string $message = null): ?string
    {
        $number = self::normalizePhoneNumber($this->text('whatsapp')) ?: self::normalizePhoneNumber($this->text('phone'));
        if ($number === null) {
            return null;
        }

        $message ??= $this->text('whatsapp_message');

        return 'https://wa.me/'.ltrim($number, '+').($message !== '' ? '?text='.rawurlencode($message) : '');
    }

    public function footerDescription(): string
    {
        $desc = trim($this->text('footer_description'));
        if ($desc !== '') {
            return $desc;
        }

        $storeDesc = trim($this->text('store_description'));
        if ($storeDesc !== '') {
            return $storeDesc;
        }

        return 'Refacciones y accesorios para quienes viven sobre dos ruedas. Piezas originales y genéricas con respaldo de taller.';
    }

    public function copyrightText(): string
    {
        $text = trim($this->text('copyright_text'));
        if ($text === '') {
            $text = '© '.date('Y').' '.config('app.name', 'MotoZone').'. Todos los derechos reservados.';
        }

        return str_replace('{year}', date('Y'), $text);
    }

    public function facebookUrl(): ?string
    {
        $url = trim($this->text('facebook_url'));
        if ($url === '') {
            return null;
        }

        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
            ? $url
            : 'https://'.ltrim($url, '/');
    }

    public function instagramUrl(): ?string
    {
        $url = trim($this->text('instagram_url'));
        if ($url === '') {
            return null;
        }

        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
            ? $url
            : 'https://'.ltrim($url, '/');
    }

    public function emailUrl(): ?string
    {
        $email = trim($this->text('email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return 'mailto:'.str_replace('%40', '@', rawurlencode($email)).'?subject='.rawurlencode('Cotización de refacciones MotoZone');
    }

    public function gmailComposeUrl(): ?string
    {
        $email = trim($this->text('email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return 'https://mail.google.com/mail/?view=cm&fs=1&to='.rawurlencode($email)
            .'&su='.rawurlencode('Cotización de refacciones MotoZone');
    }

    public static function isGoogleMapsUrl(string $url, bool $embed = false): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return false;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '/';
        $googleHosts = ['google.com', 'www.google.com', 'maps.google.com', 'google.com.mx', 'www.google.com.mx', 'maps.google.com.mx'];

        if ($embed) {
            parse_str($parts['query'] ?? '', $query);

            return in_array($host, $googleHosts, true)
                && rtrim($path, '/') === '/maps/embed'
                && is_string($query['pb'] ?? null) && $query['pb'] !== '';
        }

        return ($host === 'maps.app.goo.gl' && trim($path, '/') !== '')
            || ($host === 'goo.gl' && str_starts_with($path, '/maps/'))
            || (in_array($host, $googleHosts, true)
                && ($path === '/maps' || str_starts_with($path, '/maps/')
                    || (str_starts_with($host, 'maps.') && $path === '/' && ($parts['query'] ?? '') !== '')));
    }

    public function mapEmbedUrl(): ?string
    {
        if ($this->text('map_mode') === 'google') {
            $url = $this->text('map_embed_url');

            return self::isGoogleMapsUrl($url, true) ? $url : null;
        }

        $coordinates = $this->pinCoordinates();
        if ($coordinates === null) {
            return null;
        }

        [$latitude, $longitude] = $coordinates;

        return 'https://www.openstreetmap.org/export/embed.html?'.http_build_query([
            'bbox' => implode(',', [max(-180, $longitude - 0.01), max(-90, $latitude - 0.006), min(180, $longitude + 0.01), min(90, $latitude + 0.006)]),
            'layer' => 'mapnik',
            'marker' => $latitude.','.$longitude,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function directionsUrl(): ?string
    {
        $url = $this->text('maps_url');
        if (self::isGoogleMapsUrl($url)) {
            return $url;
        }

        $coordinates = $this->pinCoordinates();

        return $coordinates !== null
            ? 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode(implode(',', $coordinates))
            : null;
    }

    /** @return array{float, float}|null */
    private function pinCoordinates(): ?array
    {
        $latitude = $this->content['latitude'] ?? null;
        $longitude = $this->content['longitude'] ?? null;
        if ($this->text('map_mode') !== 'pin' || ! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        return $latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180
            ? [$latitude, $longitude] : null;
    }

    /** @return list<string> */
    public function galleryUrls(): array
    {
        return array_values(array_filter(array_merge(
            [$this->heroImageUrl()],
            array_map(fn (string $path): string => str_starts_with(strtolower($path), 'https://') ? $path : Storage::disk('public')->url($path), $this->gallery ?? []),
        )));
    }

    public function decorationMonth(): ?int
    {
        return ($this->content['auto_decoration'] ?? true) ? now(config('app.timezone'))->month : null;
    }
}
