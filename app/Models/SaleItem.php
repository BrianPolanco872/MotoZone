<?php

namespace App\Models;

use App\Casts\QuantityCast;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleItem extends Model
{
    use HasFactory;

    protected $attributes = [
        'sale_mode' => Product::MODE_UNIT,
    ];

    protected $fillable = [
        'sale_id',
        'product_id',
        'category_id',
        'is_custom',
        'custom_name',
        'custom_price',
        'product_name',
        'sku',
        'barcode',
        'category_name',
        'quantity',
        'sale_mode',
        'inventory_snapshot',
        'unit_price',
        'cost_price',
        'tax_rate',
        'line_subtotal',
        'tax_amount',
        'line_total',
        'line_cost',
        'returned_quantity',
        'price_overridden',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => QuantityCast::class,
            'inventory_snapshot' => 'array',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'line_subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'line_cost' => 'decimal:2',
            'returned_quantity' => QuantityCast::class,
            'price_overridden' => 'boolean',
            'is_custom' => 'boolean',
            'custom_price' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function saleMode(): string
    {
        return $this->sale_mode ?? Product::MODE_UNIT;
    }

    public function isBulk(): bool
    {
        return $this->saleMode() === Product::MODE_BULK;
    }

    public function availableReturnQuantity(): int|float
    {
        return Quantity::number(max(0, Quantity::milli($this->quantity) - Quantity::milli($this->returned_quantity ?? 0)));
    }

    public function profit(): float
    {
        return round((float) $this->line_total - (float) $this->tax_amount - (float) $this->line_cost, 2);
    }
}
