<?php

namespace Tests\Unit\Crm;

use App\Support\Crm\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{string, int}>
     */
    public static function inputs(): array
    {
        return [
            'vírgula decimal' => ['399,90', 39990],
            'milhar e vírgula' => ['1.234,56', 123456],
            'com símbolo' => ['R$ 129,90', 12990],
            'ponto decimal' => ['1234.5', 123450],
            'milhar com ponto' => ['1.500', 150000],
            'inteiro' => ['900', 90000],
        ];
    }

    #[DataProvider('inputs')]
    public function test_it_converts_reais_to_cents(string $input, int $expected): void
    {
        $this->assertSame($expected, Money::toCents($input));
    }

    public function test_it_rejects_garbage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::toCents('abc');
    }

    public function test_it_formats_cents(): void
    {
        $this->assertSame('R$ 3.999,00', Money::format(399900));
        $this->assertSame('−R$ 10,50', Money::format(-1050));
        $this->assertSame('—', Money::format(null));
        $this->assertSame('R$ 12,3 mil', Money::short(1234567));
    }
}
