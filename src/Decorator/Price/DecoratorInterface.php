<?php

namespace Ayeo\Price\Decorator\Price;

use Ayeo\Price\Price;
use Ayeo\Price\PriceValue;

interface DecoratorInterface
{
    public function decoratePrice(PriceValue $price): PriceValue;
}
