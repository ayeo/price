<?php

namespace Ayeo\Price\Test;

use Ayeo\Price\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function dataProvider(): array
    {
        return [
            [1, 1, false],
            [2, 1, true],
            [2., 1., true],
            [1, 2, false],
            [1., 2., false],
            [1.999999, 2.0, false],
            [2., 2.000001, false],
            [2.00001, 2.000001, true],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testIsGreaterThan(float $value, float $compareWith, bool $isGreater): void
    {
        $money = new Money($value);
        $moneyCompareWith = new Money($compareWith);
        $result = $money->isGreaterThan($moneyCompareWith);
        $this->assertEquals($isGreater, $result);
    }

    public function testCanNotCreateNegativeMoney(): void
    {
        $this->expectException(\LogicException::class);
        new Money(-1);
    }
}
