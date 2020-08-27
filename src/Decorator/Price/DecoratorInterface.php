<?php

namespace Ayeo\Price\Decorator\Price;

use Ayeo\Price\Price;

interface DecoratorInterface
{
    public function decoratePrice(Price $price): Price;
}
