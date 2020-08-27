<?php

namespace Ayeo\Price;

use Ayeo\Price\Calculator\CalculatorInterface;
use Ayeo\Price\Calculator\CalculatorRegistry;

class Price
{
	private Money $nett;
	private Money $gross;
	private ?Currency $currency = null;
	private Tax $tax;
	private bool $mixedTax = false;

    public function __construct(float $nett = 0.00, float $gross = 0.00, string $currencySymbol = null, int $taxRate = null)
    {
        if ($nett == 0 && $gross == 0 && is_null($currencySymbol))
        {
            //allow no currency for empty price
        }
        elseif($currencySymbol !== null)
        {
            $this->currency = new Currency($currencySymbol);
        }


        $this->nett = new Money($nett);
        $this->gross = new Money($gross);

        if ($this->nett->isGreaterThan($this->gross))
        {
            throw new \LogicException('Nett must not be greater than gross');
        }

        if (is_null($taxRate)) {
            $this->tax = Tax::build($nett, $gross);
            $this->mixedTax = true;
        } else {
            $this->tax = new Tax($taxRate);
            $this->tax->validate($nett, $gross);
        }
    }

    public static function build(float $value, ?string $currencySymbol): Price
    {
        return Price::buildByNett($value, 0, $currencySymbol);
    }

    /**
     * fixme: does zero price needs currency symbol?
     * supporting the issue is overkill (no explicit advantages)
     */
    public static function buildEmpty(string $currency = null, bool $withTax = true): Price
    {
        return new Price(0, 0, $currency, $withTax ? 0 : null);
    }

    public static function buildByNett(float $nett, int $taxValue, string $currencySymbol = null): Price
    {
        $tax = new Tax($taxValue);

        return self::getCalculator($currencySymbol ? new Currency($currencySymbol) : null)
            ->decoratePrice(new Price($nett, $tax->calculateGross($nett), $currencySymbol, $taxValue));
    }

    public static function buildByGross(float $gross, int $taxValue, ?string $currencySymbol): Price
    {
        $tax = new Tax($taxValue);

        return self::getCalculator($currencySymbol ? new Currency($currencySymbol) : null)
            ->decoratePrice(new Price($tax->calculateNett($gross), $gross, $currencySymbol, $taxValue));
    }

    private static function getCalculator(?Currency $currency): CalculatorInterface
    {
        return CalculatorRegistry::getInstance()->getCalculator($currency);
    }

    public function getNett(): float
    {
	    if (is_null($this->currency)){
		    return $this->nett->getValue();
	    }

	    return round($this->nett->getValue(), $this->currency->getPrecision());

    }

    public function getGross(): float
    {
    	if (is_null($this->currency)) {
		    return $this->gross->getValue(); //should be always 0
	    }

	    return round($this->gross->getValue(), $this->currency->getPrecision());
    }

    public function getTaxRate(): int
    {
        return $this->getTaxValue();
    }

    /**
     * @deprecated Use getTaxRate()
     * Returns tax rate not value!!
     * @return int
     */
    private function getTaxValue()
    {
        if ($this->hasTaxRate() == false) {
		//can not throw exception couse dp catch it and not working
            //throw new \LogicException("Tax rate is mixed");
        }

        return $this->tax->getValue();
    }

    public function getTaxDiff(): float
    {
        return $this->getGross() - $this->getNett();
    }

    public function isLowerThan(Price $price): bool
    {
        return $this->getGross() < $price->getGross();
    }

    public function isGreaterThan(Price $price): bool
    {
        return $this->getGross() > $price->getGross();
    }

    private function hasCurrency(): bool
    {
    	return is_null($this->currency) === false;
    }

    private function compareCurrencies(Price $A, Price $B): bool
    {
    	if ($A->hasCurrency() === false && $B->hasCurrency() === false)  {
	    	return true;
	    }

	    try {
		    return $A->getCurrency()->isEqual($B->getCurrency());
	    } catch (\LogicException $e) {
		    return false;
	    }
    }

    public function isEqual(Price $price): bool
    {
    	if ($this->compareCurrencies($this, $price) === false) {
    		return false;
	    }

        $isGrossEqual = $this->getGross() === $price->getGross();
        $isNettEqual = $this->getNett() === $price->getNett();

        return ($isGrossEqual  && $isNettEqual);
    }

    public function add(Price $priceToAdd): Price
    {
        if ($this->isEmpty()) {
            return $this->getCalculator($priceToAdd->getCurrency())->add($this, $priceToAdd);
        }
        return $this->getCalculator($this->getCurrency())->add($this, $priceToAdd);
    }

    public function subtract(Price $priceToSubtract): Price
    {
        return $this->getCalculator($this->getCurrency())->subtract($this, $priceToSubtract);
    }

    public function isEmpty(): bool
    {
    	if ($this->gross->getValue() == 0 && $this->nett->getValue() == 0)
	    {
	    	return true;
	    }

	    return false;
    }

    public function multiply(float $times): Price
    {
        return $this->getCalculator($this->getCurrency())->multiply($this, $times);
    }

    public function divide(float $times): Price
    {
        return $this->getCalculator($this->getCurrency())->divide($this, $times);
    }

    public function getCurrencySymbol(): ?string
    {
    	if (is_null($this->currency)) {
    		return null;
	    }

        return (string) $this->currency;
    }

    public function getCurrency(): Currency
    {
    	if (is_null($this->currency)) {
    		throw new \LogicException('Currency is unknown');
	    }
        return $this->currency;
    }

    /**
     * Allow to subtract from gross value without knowing price tax rate
     */
    public function subtractGross(float $grossValue, string $currencySymbol): Price
    {
        if (null === $currencySymbol) {
            $currencySymbol = $this->getCurrencySymbol();
        }

        return $this->getCalculator($this->getCurrency())
            ->subtract($this, new Price($grossValue, $grossValue, $currencySymbol, null));
    }

    /**
     * Allow to subtract from nett value without knowing price tax rate
     */
    public function subtractNett(float $nettValue, string $currencySymbol): Price
    {
        if (null === $currencySymbol) {
            $currencySymbol = $this->getCurrencySymbol();
        }

        return $this->getCalculator($this->getCurrency())
            ->subtract($this, Price::buildByNett($nettValue, $this->getTaxRate(), $currencySymbol));
    }

    /**
     * @param float $grossValue
     * @return Price
     */
    public function addGross(float $grossValue, string $currencySymbol = null): Price
    {
        if (null === $currencySymbol) {
            $currencySymbol = $this->getCurrencySymbol();
        }

        return $this->getCalculator($this->getCurrency())
            ->add($this, Price::buildByGross($grossValue, $this->getTaxRate(), $currencySymbol));
    }

    /**
     * Default format. Use own formatting for more custom purposes
     */
    public function __toString(): string
    {
        return number_format($this->getGross(), 2, '.', ' ')." ".$this->getCurrencySymbol();
    }

    public function hasTaxRate(): bool
    {
        return $this->mixedTax == false;
    }
}
