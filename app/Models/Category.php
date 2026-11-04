<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function icon(): string
    {
        return match ($this->slug) {
            'motor' => 'engine',
            'frenos' => 'brake',
            'llantas' => 'tire',
            'transmision' => 'chain',
            'electrico' => 'bolt',
            'suspension' => 'suspension',
            'accesorios' => 'helmet',
            default => 'wrench',
        };
    }
}
