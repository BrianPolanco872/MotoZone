<?php

namespace Tests\Unit;

use App\Support\Quantity;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class QuantityTest extends TestCase
{
    #[TestWith([0])]
    #[TestWith([12])]
    #[TestWith([0.125])]
    #[TestWith(['2.750'])]
    #[TestWith(['0002.001'])]
    #[TestWith(['4294967295.000'])]
    public function test_accepts_nonnegative_quantities_with_up_to_three_decimals(mixed $value): void
    {
        $this->assertTrue(Quantity::valid($value));
    }

    #[TestWith([null])]
    #[TestWith([true])]
    #[TestWith([false])]
    #[TestWith([[]])]
    #[TestWith([''])]
    #[TestWith(['abc'])]
    #[TestWith(['1e3'])]
    #[TestWith([' 1'])]
    #[TestWith(['1 '])]
    #[TestWith(['1,5'])]
    #[TestWith(['1.0000'])]
    #[TestWith(['0.0001'])]
    #[TestWith([0.0001])]
    #[TestWith([0.30000000000000004])]
    #[TestWith([4294967295.00001])]
    #[TestWith(['-0.001'])]
    #[TestWith([-1])]
    #[TestWith(['4294967295.001'])]
    #[TestWith(['4294967296'])]
    #[TestWith(['999999999999999999999999999999'])]
    public function test_rejects_invalid_negative_or_out_of_range_quantities(mixed $value): void
    {
        $this->assertFalse(Quantity::valid($value));
    }

    public function test_rejects_nonfinite_floats(): void
    {
        $this->assertFalse(Quantity::valid(INF));
        $this->assertFalse(Quantity::valid(-INF));
        $this->assertFalse(Quantity::valid(NAN));
    }

    public function test_integral_mode_rejects_fractions_without_rejecting_decimal_zeros(): void
    {
        $this->assertTrue(Quantity::valid('2.000', false));
        $this->assertFalse(Quantity::valid('2.001', false));
    }

    #[TestWith(['2.750', 2750])]
    #[TestWith([0.001, 1])]
    #[TestWith(['-0.125', -125])]
    #[TestWith(['-4294967295', -4294967295000])]
    #[TestWith([4294967294.999, 4294967294999])]
    #[TestWith(['4294967295', 4294967295000])]
    public function test_converts_signed_quantities_to_exact_integer_milliunits(mixed $value, int $expected): void
    {
        $this->assertSame($expected, Quantity::milli($value));
    }

    #[TestWith(['0.0001'])]
    #[TestWith(['-4294967295.001'])]
    #[TestWith(['not a quantity'])]
    #[TestWith([null])]
    public function test_conversion_throws_instead_of_truncating_or_replacing_invalid_values(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        Quantity::milli($value);
    }

    #[TestWith([0, 0])]
    #[TestWith([2000, 2])]
    #[TestWith([-2000, -2])]
    #[TestWith([2750, 2.75])]
    #[TestWith([-125, -0.125])]
    public function test_numbers_preserve_legacy_integer_types_and_fractional_values(int $milli, int|float $expected): void
    {
        $this->assertSame($expected, Quantity::number($milli));
    }

    #[TestWith(['0.000', '0'])]
    #[TestWith(['12.000', '12'])]
    #[TestWith(['2.750', '2.75'])]
    #[TestWith(['0.001', '0.001'])]
    #[TestWith(['-0.120', '-0.12'])]
    #[TestWith(['4294967295.000', '4294967295'])]
    public function test_formats_quantities_without_unnecessary_zeros(mixed $value, string $expected): void
    {
        $this->assertSame($expected, Quantity::format($value));
    }

    public function test_integer_milliunit_arithmetic_does_not_accumulate_float_errors(): void
    {
        $remaining = Quantity::milli('2.750') - Quantity::milli('0.500');

        $this->assertSame(2.25, Quantity::number($remaining));
        $this->assertSame(300, Quantity::milli('0.100') + Quantity::milli('0.200'));
    }

    public function test_display_rounds_database_float_aggregate_artifacts_without_relaxing_input_validation(): void
    {
        $aggregate = 0.1 + 0.2;

        $this->assertSame('0.3', Quantity::format($aggregate));
        $this->assertFalse(Quantity::valid($aggregate));
    }

    public function test_display_accepts_derived_kit_counts_larger_than_physical_stock_range(): void
    {
        $this->assertSame('5000000000', Quantity::format(5000000000));
        $this->assertFalse(Quantity::valid(5000000000));
    }
}
