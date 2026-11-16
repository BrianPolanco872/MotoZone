<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'home_visible',
        'home_position',
        'gallery',
    ];

    protected function casts(): array
    {
        return [
            'home_visible' => 'boolean',
            'home_position' => 'integer',
            'gallery' => 'array',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return list<string> */
    public function galleryUrls(): array
    {
        return array_map(fn (string $path): string => Storage::disk('public')->url($path), $this->gallery ?? []);
    }

    public function coverUrl(): ?string
    {
        return $this->galleryUrls()[0] ?? null;
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
