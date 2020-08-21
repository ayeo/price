<?php

namespace Ayeo\Price\Decorator\Money;

use Ayeo\Price\Money;

interface DecoratorInterface
{
    public function decorateMoney(Money $money): Money;
}
