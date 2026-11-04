<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'category_id',
        'product_name',
        'sku',
        'barcode',
        'category_name',
        'quantity',
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
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'line_subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'line_cost' => 'decimal:2',
            'returned_quantity' => 'integer',
            'price_overridden' => 'boolean',
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

    public function availableReturnQuantity(): int
    {
        return max(0, $this->quantity - $this->returned_quantity);
    }

    public function profit(): float
    {
        return round((float) $this->line_total - (float) $this->tax_amount - (float) $this->line_cost, 2);
    }
}
