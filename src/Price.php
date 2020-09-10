<?php

namespace Ayeo\Price;

use Ayeo\Price\Calculator\CalculatorInterface;
use Ayeo\Price\Calculator\CalculatorRegistry;

class Price
{
    private PriceValue $priceValue;

    public function __construct(float $nett = 0.00, float $gross = 0.00, string $currencySymbol = null, int $taxRate = null)
    {
        $currency = null;
        if ($nett == 0 && $gross == 0 && is_null($currencySymbol))
        {
            //allow no currency for empty price
        }
        elseif($currencySymbol !== null)
        {
            $currency = new Currency($currencySymbol);
        }

        $nettMoney = new Money($nett);
        $grossMoney = new Money($gross);
        if ($nettMoney->isGreaterThan($grossMoney))
        {
            throw new \LogicException('Nett must not be greater than gross');
        }

        if (is_null($taxRate)) {
            $tax = Tax::build($nett, $gross);
            $mixedTax = true;
        } else {
            $tax = new Tax($taxRate);
            $tax->validate($nett, $gross);
            $mixedTax = false;
        }

        $this->priceValue = $this::getCalculator($currency)->decoratePrice(new PriceValue($nettMoney, $grossMoney, $tax, $mixedTax, $currency));
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

        return new Price($nett, $tax->calculateGross($nett), $currencySymbol, $taxValue);
    }

    public static function buildByGross(float $gross, int $taxValue, ?string $currencySymbol): Price
    {
        $tax = new Tax($taxValue);

        return new Price($tax->calculateNett($gross), $gross, $currencySymbol, $taxValue);
    }

    private static function getCalculator(?Currency $currency): CalculatorInterface
    {
        return CalculatorRegistry::getInstance()->getCalculator($currency);
    }

    public function getNett(): float
    {
	    if (!$this->priceValue->hasCurrency()){
		    return $this->priceValue->getNett()->getValue();
	    }

	    return round($this->priceValue->getNett()->getValue(), $this->priceValue->getCurrency()->getPrecision());

    }

    public function getGross(): float
    {
        if (!$this->priceValue->hasCurrency()){
		    return  $this->priceValue->getGross()->getValue(); //should be always 0
	    }

	    return round($this->priceValue->getGross()->getValue(), $this->priceValue->getCurrency()->getPrecision());
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

        return $this->priceValue->getTax()->getValue();
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
    	return $this->priceValue->hasCurrency();
    }

    private function compareCurrencies(Price $left, Price $right): bool
    {
        if ($left->hasCurrency() === false && $right->hasCurrency() === false) {
            return true;
        }

	    try {
		    return $left->getCurrency()->isEqual($right->getCurrency());
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
        return $this->getCalculator($this->priceValue->getCurrency())->add($this, $priceToAdd);
    }

    public function subtract(Price $priceToSubtract): Price
    {
        return $this->getCalculator($this->priceValue->getCurrency())->subtract($this, $priceToSubtract);
    }

    public function isEmpty(): bool
    {
    	if ($this->priceValue->getGross()->getValue() == 0 && $this->priceValue->getNett()->getValue() == 0)
	    {
	    	return true;
	    }

	    return false;
    }

    public function multiply(float $times): Price
    {
        return $this->getCalculator($this->priceValue->getCurrency())->multiply($this, $times);
    }

    public function divide(float $times): Price
    {
        return $this->getCalculator($this->priceValue->getCurrency())->divide($this, $times);
    }

    public function getCurrencySymbol(): ?string
    {
    	if (!$this->priceValue->hasCurrency()) {
    		return null;
	    }

        return (string) $this->priceValue->getCurrency();
    }

    public function getCurrency(): Currency
    {
    	if (!$this->priceValue->hasCurrency()) {
    		throw new \LogicException('Currency is unknown');
	    }
        return $this->priceValue->getCurrency();
    }

    /**
     * Allow to subtract from gross value without knowing price tax rate
     */
    public function subtractGross(float $grossValue, string $currencySymbol): Price
    {
        return $this->getCalculator($this->priceValue->getCurrency())
            ->subtract($this, new Price($this->priceValue->getTax()->calculateNett($grossValue), $grossValue, $currencySymbol, null));
    }

    /**
     * Allow to subtract from nett value without knowing price tax rate
     */
    public function subtractNett(float $nettValue, string $currencySymbol): Price
    {
        return $this->getCalculator($this->priceValue->getCurrency())
            ->subtract($this, new Price($nettValue, $this->priceValue->getTax()->calculateGross($nettValue), $currencySymbol, null));
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

        return $this->getCalculator($this->priceValue->getCurrency())
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
        return $this->priceValue->isMixedTax() == false;
    }

    public function getValue(): PriceValue
    {
        return $this->priceValue;
    }
}
