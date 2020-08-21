<?php

namespace Ayeo\Price\Decorator\Money;

use Ayeo\Price\Money;

class RoundDecorator implements DecoratorInterface
{
    private int $precision;

    public function __construct(int $precision)
    {
        $this->precision = $precision;
    }

    public function decorateMoney(Money $money): Money
    {
        return new Money(round($money->getValue(), $this->precision));
    }
}
