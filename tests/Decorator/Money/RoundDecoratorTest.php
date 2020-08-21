<?php

namespace Ayeo\Price\Test\Decorator\Money;

use Ayeo\Price\Decorator\Money\RoundDecorator;
use Ayeo\Price\Money;
use PHPUnit\Framework\TestCase;

class RoundDecoratorTest extends TestCase
{
    public function dataProvider(): array
    {
        return [
            [0, 1., 1.],
            [0, 1.1, 1.],
            [0, 1.8, 2.],
            [1, 1., 1.0],
            [1, 1.1, 1.1],
            [1, 1.11, 1.1],
            [1, 1.15, 1.2],
            [9, 1.1111111111, 1.111111111],
            [9, 1.1111111119, 1.111111112],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function test1(int $precision, float $value, float $expectedValue): void
    {
        $money = new Money($value);
        $decorator = new RoundDecorator($precision);
        $decoratedMoney = $decorator->decorateMoney($money);
        $this->assertEquals($expectedValue, $decoratedMoney->getValue());
    }
}
