<?php

namespace Tests\Unit;

use App\Services\EvaluationDraftCalculator;
use App\Support\Decimal;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EvaluationDraftCalculatorTest extends TestCase
{
    #[DataProvider('roundingVectors')]
    public function test_half_up_rounding_is_decimal_and_deterministic(string $input, string $expected): void
    {
        $this->assertSame($expected, Decimal::roundHalfUp($input, 2));
    }

    public static function roundingVectors(): array
    {
        return [
            ['12.3449', '12.34'],
            ['12.3450', '12.35'],
            ['99.9950', '100.00'],
        ];
    }

    public function test_score_validation_rejects_non_plain_or_off_step_values(): void
    {
        $calculator = new EvaluationDraftCalculator;
        foreach (['5e0', 'NaN', 'INF', '-0.5', '10.5', '0.1', '0.50000'] as $hostile) {
            try {
                $calculator->normalizeScore($hostile);
                $this->fail("The score {$hostile} should be rejected.");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame('0.0000', $calculator->normalizeScore('0'));
        $this->assertSame('10.0000', $calculator->normalizeScore('10.0000'));
        $this->assertSame('1.2500', $calculator->component('0.5000', '25.0000'));
    }
}
