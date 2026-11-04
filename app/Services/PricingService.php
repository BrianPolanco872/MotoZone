<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;

class PricingService
{
    /**
     * Resolve the unit price the server will charge for a product.
     *
     * @return array{unit_price: float, overridden: bool, wholesale: bool}
     */
    public function resolveUnitPrice(Product $product, User $user, bool $wholesale = false, ?float $requestedPrice = null): array
    {
        $catalogPrice = (float) $product->price;
        $wholesalePrice = $product->wholesalePrice();

        $useWholesale = $wholesale && $wholesalePrice !== null && $user->can('pos.wholesale');
        $defaultPrice = $useWholesale ? $wholesalePrice : $catalogPrice;

        if ($requestedPrice !== null && $user->can('pos.price.edit')) {
            $requestedPrice = round($requestedPrice, 2);

            if ($requestedPrice >= 0 && abs($requestedPrice - $defaultPrice) > 0.0001) {
                return [
                    'unit_price' => $requestedPrice,
                    'overridden' => true,
                    'wholesale' => $useWholesale,
                ];
            }
        }

        return [
            'unit_price' => round($defaultPrice, 2),
            'overridden' => false,
            'wholesale' => $useWholesale,
        ];
    }

    /**
     * Split a tax-inclusive line total into subtotal and tax.
     *
     * @return array{line_total: float, line_subtotal: float, tax_amount: float}
     */
    public function lineAmounts(float $unitPrice, int $quantity, float $taxRate): array
    {
        $lineTotal = round($unitPrice * $quantity, 2);
        $divisor = 1 + max(0, $taxRate);
        $lineSubtotal = round($lineTotal / $divisor, 2);

        return [
            'line_total' => $lineTotal,
            'line_subtotal' => $lineSubtotal,
            'tax_amount' => round($lineTotal - $lineSubtotal, 2),
        ];
    }
}
