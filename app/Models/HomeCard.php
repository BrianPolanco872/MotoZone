<?php

namespace App\Models;

use Database\Factories\HomeCardFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class HomeCard extends Model
{
    /** @use HasFactory<HomeCardFactory> */
    use HasFactory;

    public const SECTIONS = ['categories', 'products', 'new'];

    public const ANCHORS = ['categories' => 'categorias', 'products' => 'destacados', 'new' => 'nuevos'];

    public const CATEGORY_ICONS = [
        'engine' => 'Motor', 'brake' => 'Frenos', 'wrench' => 'Herramienta',
        'bolt' => 'Eléctrico', 'helmet' => 'Casco', 'moto' => 'Moto',
        'chain' => 'Cadena', 'suspension' => 'Suspensión', 'tag' => 'Etiqueta',
    ];

    public const CATEGORY_PRESETS = [
        ['title' => 'Motor y Transmisión', 'icon' => 'engine'],
        ['title' => 'Frenos y Suspensión', 'icon' => 'brake'],
        ['title' => 'Filtros y Aceites', 'icon' => 'wrench'],
        ['title' => 'Eléctrico y Baterías', 'icon' => 'bolt'],
        ['title' => 'Accesorios y Cascos', 'icon' => 'helmet'],
    ];

    protected $fillable = [
        'section', 'sort_order', 'is_active', 'title', 'subtitle', 'description',
        'image_path', 'image_url', 'gallery', 'price', 'sale_price', 'badge',
        'link_url', 'link_label', 'product_id', 'slug', 'icon',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'gallery' => 'array',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function linkedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('sort_order')
            ->orderByPivot('sort_order')->orderBy('products.id');
    }

    public function familyAnchor(): string
    {
        return 'familia-'.$this->id.($this->slug ? '-'.$this->slug : '');
    }

    public function scopeSection(Builder $query, string $section): void
    {
        $query->where('section', $section);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_url ?: ($this->image_path ? Storage::disk('public')->url($this->image_path) : null);
    }

    /** @return list<string> */
    public function galleryUrls(): array
    {
        return array_values(array_filter(array_merge(
            [$this->imageUrl()],
            array_map(fn (string $path): string => str_starts_with(strtolower($path), 'https://') ? $path : Storage::disk('public')->url($path), $this->gallery ?? []),
        )));
    }

    public function coverUrl(): ?string
    {
        return $this->galleryUrls()[0] ?? null;
    }

    public function hasDiscount(): bool
    {
        return $this->price !== null && $this->sale_price !== null && (float) $this->sale_price < (float) $this->price;
    }

    public function destination(): ?string
    {
        if ($this->section === 'categories' && $this->relationLoaded('linkedProducts')
            && $this->linkedProducts->contains(fn (Product $product): bool => $product->is_active)) {
            return route('home').'#'.$this->familyAnchor();
        }

        if ($this->product) {
            return route('catalog.show', $this->product);
        }

        return rtrim((string) parse_url($this->link_url ?? '', PHP_URL_PATH), '/') !== '/catalogo'
            ? ($this->link_url ?: null)
            : null;
    }
}
