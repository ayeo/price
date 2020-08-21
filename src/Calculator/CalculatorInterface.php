<?php

namespace Ayeo\Price\Calculator;

use Ayeo\Price\Money;
use Ayeo\Price\Price;
use LogicException;

interface CalculatorInterface
{
    /**
     * @throws LogicException
     */
    public function add(Price $left, Price $right): Price;
    /**
     * @throws LogicException
     */
    public function subtract(Price $left, Price $right): Price;
    /**
     * @throws LogicException
     */
    public function multiply(Price $left, float $times): Price;
    /**
     * @throws LogicException
     */
    public function divide(Price $left, float $times): Price;
    public function decorateMoney(Money $money): Money;
}
