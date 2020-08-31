<?php

namespace Ayeo\Price\Test\Decorator\Price;

use Ayeo\Price\Decorator\Price\GrossNettRoundDecorator;
use Ayeo\Price\Money;
use Ayeo\Price\Price;
use PHPUnit\Framework\TestCase;

class GrossNettRoundDecoratorTest extends TestCase
{
    public function dataProvider(): array
    {
        return [
            [0, 1., 1.00, 'PLN'],
            [0, 1.1, 1.00, 'PLN'],
            [0, 1.8, 2.00, 'PLN'],
            [1, 1., 1.00, 'PLN'],
            [1, 1.1, 1.10, 'PLN'],
            [1, 1.11, 1.10, 'PLN'],
            [1, 1.15, 1.20, 'PLN'],
            [9, 1.1111111111, 1.11, 'PLN'],
            [9, 1.1191111111, 1.12, 'PLN'],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function test1(int $precision, float $value, float $expectedValue): void
    {
        $price = Price::buildByNett($value, 0, 'PLN');
        $decorator = new GrossNettRoundDecorator($precision);
        $decoratedPrice = $decorator->decoratePrice($price);
        $this->assertEquals($expectedValue, $decoratedPrice->getNett());
    }
}
