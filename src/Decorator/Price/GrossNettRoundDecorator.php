<?php

namespace Ayeo\Price\Decorator\Price;

use Ayeo\Price\Money;
use Ayeo\Price\PriceValue;

class GrossNettRoundDecorator implements DecoratorInterface
{
    private int $precision;

    public function __construct(int $precision)
    {
        $this->precision = $precision;
    }

    public function decoratePrice(PriceValue $price): PriceValue
    {
        return new PriceValue(
            new Money(round($price->getNett()->getValue(), $this->precision)),
            new Money(round($price->getGross()->getValue(), $this->precision)),
            $price->getTax(),
            $price->isMixedTax(),
            $price->getCurrency()
        );
    }
}
