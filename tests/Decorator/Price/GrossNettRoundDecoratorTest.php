<?php

namespace Ayeo\Price\Test\Decorator\Price;

use Ayeo\Price\Decorator\Price\GrossNettRoundDecorator;
use Ayeo\Price\Money;
use Ayeo\Price\PriceValue;
use Ayeo\Price\Tax;
use PHPUnit\Framework\TestCase;

class GrossNettRoundDecoratorTest extends TestCase
{
    public function dataProvider(): array
    {
        return [
            [0, 1., 1.00],
            [0, 1.1, 1.00],
            [0, 1.8, 2.00],
            [1, 1., 1.00],
            [1, 1.1, 1.10],
            [1, 1.11, 1.10],
            [1, 1.15, 1.20],
            [8, 1.1111111111, 1.11111111],
            [5, 1.1111111111, 1.11111],
            [7, 1.1191111111, 1.1191111],
            [2, 1.1191111111, 1.12],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDecorate(int $precision, float $value, float $expectedValue): void
    {
        $price = new PriceValue(new Money($value), new Money($value), Tax::build($value, $value), true, null);
        $decorator = new GrossNettRoundDecorator($precision);
        $decoratedPrice = $decorator->decoratePrice($price);
        $this->assertEquals($expectedValue, $decoratedPrice->getNett()->getValue());
    }
}
