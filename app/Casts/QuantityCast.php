<?php

namespace App\Casts;

use App\Support\Quantity;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class QuantityCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): int|float|null
    {
        return $value === null ? null : Quantity::number(Quantity::milli($value));
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $milli = Quantity::milli($value);

        return sprintf('%s%d.%03d', $milli < 0 ? '-' : '', intdiv(abs($milli), 1000), abs($milli) % 1000);
    }
}
