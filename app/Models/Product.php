<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    public const LOW_STOCK_THRESHOLD = 5;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'image_path',
        'brand',
        'description',
        'price',
        'cost',
        'wholesale_price',
        'tax_rate',
        'stock',
        'min_stock',
        'track_inventory',
        'featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'track_inventory' => 'boolean',
            'featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): void
    {
        $query->where('featured', true);
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('barcode', 'like', "%{$term}%")
                ->orWhere('brand', 'like', "%{$term}%");
        });
    }

    public function isInStock(): bool
    {
        return ! $this->track_inventory || $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        if (! $this->track_inventory) {
            return false;
        }

        return $this->stock > 0 && $this->stock <= $this->lowStockThreshold();
    }

    public function lowStockThreshold(): int
    {
        return $this->min_stock > 0 ? $this->min_stock : self::LOW_STOCK_THRESHOLD;
    }

    public function marginPercentage(): ?float
    {
        $cost = (float) $this->cost;

        if ($cost <= 0) {
            return null;
        }

        return round((((float) $this->price - $cost) / $cost) * 100, 2);
    }

    public function hasCost(): bool
    {
        return (float) $this->cost > 0;
    }

    public function wholesalePrice(): ?float
    {
        return $this->wholesale_price === null ? null : (float) $this->wholesale_price;
    }

    public function stockLabel(): string
    {
        if (! $this->track_inventory) {
            return 'Sin control de inventario';
        }

        if (! $this->isInStock()) {
            return 'Agotado';
        }

        if ($this->isLowStock()) {
            return "Últimas {$this->stock} piezas";
        }

        return 'Disponible';
    }
}
