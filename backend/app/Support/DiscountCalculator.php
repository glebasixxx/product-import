<?php

namespace App\Support;

final class DiscountCalculator
{
    public static function calculate(float $price, ?float $purchasePrice): ?float
    {
        if ($purchasePrice === null || $price <= 0.0) {
            return null;
        }

        return round(($price - $purchasePrice) / $price * 100, 2);
    }
}
