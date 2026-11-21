<?php

namespace App\Models;

use App\Casts\QuantityCast;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    public const LOW_STOCK_THRESHOLD = 5;

    public const MODE_UNIT = 'unit';

    public const MODE_BULK = 'bulk';

    public const MODE_KIT = 'kit';

    protected $attributes = [
        'sale_mode' => self::MODE_UNIT,
        'kit_deduct_components' => false,
    ];

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'barcode2',
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
        'sale_mode',
        'kit_deduct_components',
        'featured',
        'is_active',
        'home_visible',
        'home_position',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'stock' => QuantityCast::class,
            'min_stock' => QuantityCast::class,
            'track_inventory' => 'boolean',
            'kit_deduct_components' => 'boolean',
            'featured' => 'boolean',
            'is_active' => 'boolean',
            'home_visible' => 'boolean',
            'home_position' => 'integer',
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

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function kitItems(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_kit_items', 'kit_product_id', 'component_product_id')
            ->withPivot('quantity');
    }

    public function usedInKits(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_kit_items', 'component_product_id', 'kit_product_id')
            ->withPivot('quantity');
    }

    public function saleMode(): string
    {
        return $this->sale_mode ?? self::MODE_UNIT;
    }

    public function isBulk(): bool
    {
        return $this->saleMode() === self::MODE_BULK;
    }

    public function isKit(): bool
    {
        return $this->saleMode() === self::MODE_KIT;
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): void
    {
        $query->where('featured', true);
    }

    public function scopeLowStock(Builder $query): void
    {
        $query->where('sale_mode', '!=', self::MODE_KIT)
            ->where('track_inventory', true)
            ->where('stock', '>', 0)
            ->whereRaw('stock <= CASE WHEN min_stock > 0 THEN min_stock ELSE ? END', [self::LOW_STOCK_THRESHOLD]);
    }

    public function scopeOutOfStock(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->where(function (Builder $query): void {
                $query->where('sale_mode', '!=', self::MODE_KIT)
                    ->where('track_inventory', true)->where('stock', '<=', 0);
            })->orWhere(function (Builder $query): void {
                $query->where('sale_mode', self::MODE_KIT)
                    ->where(function (Builder $query): void {
                        $query->doesntHave('kitItems')
                            ->orWhereHas('kitItems', function (Builder $components): void {
                                $componentTable = $components->getModel()->getTable();

                                $components->where("{$componentTable}.is_active", false)
                                    ->orWhere("{$componentTable}.sale_mode", self::MODE_KIT)
                                    ->orWhere('product_kit_items.quantity', '<=', 0)
                                    ->orWhere('product_kit_items.quantity', '>', Quantity::MAX)
                                    ->orWhere(function (Builder $components) use ($componentTable): void {
                                        $components->where("{$componentTable}.sale_mode", '!=', self::MODE_BULK)
                                            ->whereRaw('product_kit_items.quantity <> ROUND(product_kit_items.quantity, 0)');
                                    })
                                    ->orWhere(function (Builder $components) use ($componentTable): void {
                                        $components->where('products.kit_deduct_components', true)
                                            ->where("{$componentTable}.track_inventory", true)
                                            ->whereColumn("{$componentTable}.stock", '<', 'product_kit_items.quantity');
                                    });
                            });
                    });
            });
        });
    }

    public function scopeMissingPhoto(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('image_path')->orWhereRaw("TRIM(image_path) = ''"));
    }

    public function scopeMissingPrice(Builder $query): void
    {
        $query->where('price', '<=', 0);
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
                ->orWhere('barcode2', 'like', "%{$term}%")
                ->orWhere('brand', 'like', "%{$term}%");
        });
    }

    public function availableStock(): int|float|null
    {
        if (! $this->isKit()) {
            return $this->track_inventory ? $this->stock : null;
        }

        $this->loadMissing('kitItems');

        if ($this->kitItems->isEmpty()) {
            return 0;
        }

        $available = null;

        foreach ($this->kitItems as $component) {
            $quantity = $component->pivot->quantity;

            if (! $component->is_active || $component->isKit()
                || ! Quantity::valid($quantity, $component->isBulk()) || Quantity::milli($quantity) === 0) {
                return 0;
            }

            if ($this->kit_deduct_components && $component->track_inventory) {
                $stock = max(0, Quantity::milli($component->stock));
                $kits = intdiv($stock, Quantity::milli($quantity));
                $available = $available === null ? $kits : min($available, $kits);
            }
        }

        return $available;
    }

    public function isInStock(): bool
    {
        $available = $this->availableStock();

        return $available === null || $available > 0;
    }

    public function isLowStock(): bool
    {
        if ($this->isKit() || ! $this->track_inventory) {
            return false;
        }

        return $this->stock > 0 && $this->stock <= $this->lowStockThreshold();
    }

    public function lowStockThreshold(): int|float
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
        if ($this->isKit()) {
            return $this->isInStock() ? 'Disponible' : 'Agotado';
        }

        if (! $this->track_inventory) {
            return 'Sin control de inventario';
        }

        if (! $this->isInStock()) {
            return 'Agotado';
        }

        if ($this->isLowStock()) {
            return $this->isBulk()
                ? 'Existencia baja: '.Quantity::format($this->stock)
                : 'Últimas '.Quantity::format($this->stock).' piezas';
        }

        return 'Disponible';
    }
}
