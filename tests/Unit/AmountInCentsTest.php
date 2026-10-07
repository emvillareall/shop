<?php

namespace Tests\Unit;

use App\Support\Money\AmountInCents;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AmountInCentsTest extends TestCase
{
    #[DataProvider('validAmounts')]
    public function test_amounts_are_parsed_without_float_arithmetic(string|int $input, int $expected): void
    {
        $this->assertSame($expected, AmountInCents::parse($input));
    }

    public static function validAmounts(): array
    {
        return [
            ['60', 6000],
            ['60.0', 6000],
            ['60.00', 6000],
            ['60,00', 6000],
            ['0.01', 1],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function test_invalid_amounts_are_rejected(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        AmountInCents::parse($input);
    }

    public static function invalidAmounts(): array
    {
        return [
            ['-1'],
            ['abc'],
            ['60.001'],
            ['1,234.56'],
        ];
    }

    public function test_seven_percent_of_sixty_dollars_is_four_twenty(): void
    {
        $this->assertSame(700, AmountInCents::parsePercentHundredths('7'));
        $this->assertSame(420, AmountInCents::percentage(6000, 700));
        $this->assertSame('64.20', AmountInCents::format(6420));
    }
}
