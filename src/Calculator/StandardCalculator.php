<?php

namespace Ayeo\Price\Calculator;

use Ayeo\Price\Currency;
use Ayeo\Price\Decorator\Money\DecoratorInterface;
use Ayeo\Price\Money;
use Ayeo\Price\Price;
use LogicException;

class StandardCalculator implements CalculatorInterface
{
    /** @var DecoratorInterface[] */
    private array $decorators;

    public function __construct(DecoratorInterface ...$decorators)
    {
        $this->decorators = $decorators;
    }

    public function add(Price $left, Price $right): Price
    {
        if ($left->isEmpty()) {
            return clone $right;
        }
        if ($right->isEmpty()) {
            return clone $left;
        }
        $this->compareCurrencySymbols($left->getCurrency(), $right->getCurrency());
        $currency = $this->buildCurrency($left, $right);
        $newGross = $left->getGross() + $right->getGross();
        $newNett = $left->getNett() + $right->getNett();
        $taxRate = $this->getTaxForPrices($left, $right);

        return new Price($newNett, $newGross, $currency, $taxRate);
    }

    public function subtract(Price $left, Price $right): Price
    {
        if ($left->isEmpty()) {
            return clone $left;
        }
        if ($right->isEmpty()) {
            return clone $left;
        }

        $this->compareCurrencySymbols($left->getCurrency(), $right->getCurrency());
        $currency = $this->buildCurrency($left, $right);
        if ($left->isGreaterThan($right)) {
            $newGross = $left->getGross() - $right->getGross();
            $newNett = $left->getNett() - $right->getNett();
            if ($newNett <= 0) { // in some situations with mixed taxes
                return Price::buildEmpty((string)$currency, $this->getTaxForPrices($left, $right) !== null);

            }

            return new Price($newNett, $newGross, $currency, $this->getTaxForPrices($left, $right));
        }

        return Price::buildEmpty((string)$currency, $this->getTaxForPrices($left, $right) !== null);
    }

    public function multiply(Price $left, float $times): Price
    {
        if ($times < 0) {
            throw new LogicException('Multiply param must greater than 0');
        }

        $nett = $left->getNett() * $times;
        $gross = $left->getGross() * $times;

        return new Price($nett, $gross, $left->getCurrencySymbol(), $left->getTaxRate());
    }

    public function divide(Price $left, float $times): Price
    {
        if ($times <= 0) {
            throw new LogicException('Divide factor must be positive and greater than zero');
        }

        $nett = $left->getNett() / $times;

        return Price::buildByNett($nett, $left->getTaxRate(), $left->getCurrencySymbol());
    }

    private function buildCurrency(Price $left, Price $right): ?Currency
    {
        if ($left->isEmpty() === false && $right->isEmpty() === false) {
            $this->compareCurrencySymbols($left->getCurrency(), $right->getCurrency());
        }

        if (!$left->isEmpty()) {
            return $left->getCurrency();
        }

        if (!$right->isEmpty()) {
            return $right->getCurrency();
        }

        return null;
    }

    /**
     * @throws LogicException
     */
    private function compareCurrencySymbols(Currency $left, Currency $right): void
    {
        if ($left->isEqual($right) === false) {
            $message = sprintf(
                'Can not operate on different currencies ("%s" and "%s")',
                (string)$left,
                (string)$right
            );

            throw new LogicException($message);
        }
    }

    private function getTaxForPrices(Price $left, Price $right): ?int
    {
        if ($left->isEmpty()) {
            return $right->getTaxRate();
        }

        if ($right->isEmpty()) {
            return $left->getTaxRate();
        }

        if ($this->areTaxesIdentical($left, $right)) {
            return $left->getTaxRate();
        }

        return null;
    }

    private function areTaxesIdentical(Price $left, Price $right): bool
    {
        $bothHasTaxSet = $left->hasTaxRate() && $right->hasTaxRate();
        if ($bothHasTaxSet === false) {
            return false;
        }

        return $left->getTaxRate() === $right->getTaxRate();
    }

    public function decorateMoney(Money $money): Money
    {
        foreach($this->decorators as $decorator) {
            $money = $decorator->decorateMoney($money);
        }

        return $money;
    }
}
