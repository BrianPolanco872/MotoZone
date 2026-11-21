<?php

namespace App\Support;

use InvalidArgumentException;

class Quantity
{
    public const MAX = 4294967295;

    public static function valid(mixed $value, bool $fractional = true): bool
    {
        try {
            $milli = self::milli($value);
        } catch (InvalidArgumentException) {
            return false;
        }

        return $milli >= 0 && ($fractional || $milli % 1000 === 0);
    }

    public static function milli(mixed $value): int
    {
        if ((! is_int($value) && ! is_float($value) && ! is_string($value))
            || (is_float($value) && ! is_finite($value))) {
            throw new InvalidArgumentException('La cantidad debe ser decimal con hasta tres decimales.');
        }

        $decimal = is_float($value) ? json_encode($value, JSON_PRESERVE_ZERO_FRACTION) : (string) $value;

        if (! preg_match('/\A(-?)([0-9]+)(?:\.([0-9]{1,3}))?\z/', $decimal, $parts)) {
            throw new InvalidArgumentException('La cantidad debe ser decimal con hasta tres decimales.');
        }

        $whole = ltrim($parts[2], '0');
        $fraction = (int) str_pad($parts[3] ?? '', 3, '0');
        $maximum = (string) self::MAX;

        if (strlen($whole) > strlen($maximum)
            || (strlen($whole) === strlen($maximum) && strcmp($whole, $maximum) > 0)
            || ($whole === $maximum && $fraction > 0)) {
            throw new InvalidArgumentException('La cantidad excede el rango de inventario.');
        }

        $milli = (int) $whole * 1000 + $fraction;

        return $parts[1] === '-' ? -$milli : $milli;
    }

    public static function number(int $milli): int|float
    {
        return $milli % 1000 === 0 ? intdiv($milli, 1000) : $milli / 1000;
    }

    public static function format(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        $milli = self::milli(is_float($value) ? round($value, 3) : $value);
        $absolute = abs($milli);
        $fraction = rtrim(str_pad((string) ($absolute % 1000), 3, '0', STR_PAD_LEFT), '0');

        return ($milli < 0 ? '-' : '').intdiv($absolute, 1000).($fraction === '' ? '' : '.'.$fraction);
    }
}
