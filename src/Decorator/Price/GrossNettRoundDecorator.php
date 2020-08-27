<?php

namespace Ayeo\Price\Decorator\Price;

use Ayeo\Price\Price;

class GrossNettRoundDecorator implements DecoratorInterface
{
    private int $precision;

    public function __construct(int $precision)
    {
        $this->precision = $precision;
    }

    public function decoratePrice(Price $price): Price
    {
        return new Price(
            round($price->getNett(), $this->precision),
            round($price->getGross(), $this->precision),
            $price->getCurrencySymbol(),
            $price->hasTaxRate() ? $price->getTaxRate() : null
        );
    }
}
