<?php

namespace Tests\Unit;

use App\Support\DiscountCalculator;
use PHPUnit\Framework\TestCase;

class DiscountCalculatorTest extends TestCase
{
    public function test_calculates_discount_from_sale_and_purchase_price(): void
    {
        $this->assertSame(33.33, DiscountCalculator::calculate(1320.0, 880.0));
        $this->assertSame(50.0, DiscountCalculator::calculate(200.0, 100.0));
    }

    public function test_returns_null_when_not_computable(): void
    {
        $this->assertNull(DiscountCalculator::calculate(1000.0, null));
        $this->assertNull(DiscountCalculator::calculate(0.0, 10.0));
    }

    public function test_negative_discount_when_purchase_price_is_higher(): void
    {
        $this->assertSame(-25.0, DiscountCalculator::calculate(100.0, 125.0));
    }
}
