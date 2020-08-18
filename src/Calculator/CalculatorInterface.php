<?php

namespace Ayeo\Price\Calculator;

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
    public function multiply(Price $left, float $times): Price;
    public function divide(Price $left, float $times): Price;
    public function hasSameCurrencies(Price $left, Price $right): bool;
}
