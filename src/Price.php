<?php

namespace Ayeo\Price;

class Price
{
	private Money $nett;
	private Money $gross;
	private ?Currency $currency = null;
	private Tax $tax;
	private bool $mixedTax = false;

    public function __construct(float $nett = 0.00, float $gross = 0.00, string $currencySymbol = null, int $taxRate = null)
    {
    	if (!($nett == 0 && $gross == 0 && is_null($currencySymbol)))
    	{
            //allow no currency for empty price
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
    public static function buildEmpty(string $currency = null): Price
    {
        static $emptyPrice = [];

        $currencySymbol = (string)$currency;
        if (array_key_exists($currencySymbol, $emptyPrice)) {
            return $emptyPrice[$currencySymbol];
        }

        return $emptyPrice[$currencySymbol] = new Price(0, 0, $currency, 0);
    }

    public static function buildByNett(float $nett, int $taxValue, string $currencySymbol = null): Price
    {
        $tax = new Tax($taxValue);

        return new Price($nett, $tax->calculateGross($nett), $currencySymbol, $taxValue);
    }

    public static function buildByGross(float $gross, int $taxValue, string $currencySymbol): Price
    {
        $tax = new Tax($taxValue);

        return new Price($tax->calculateNett($gross), $gross, $currencySymbol, $taxValue);
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

    private function getTax(): Tax
    {
        return $this->tax;
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

        return $this->getTax()->getValue();
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
	    $currency = $this->buildCurrency($this, $priceToAdd);
        $newGross = $this->getGross() + $priceToAdd->getGross();
        $newNett = $this->getNett() + $priceToAdd->getNett();
	    $taxRate = $this->getTaxForPrices($this, $priceToAdd);

	    return new Price($newNett, $newGross, $currency, $taxRate);
    }

    public function subtract(Price $priceToSubtract): Price
    {
	    $currency = $this->buildCurrency($this, $priceToSubtract);
        if ($this->isGreaterThan($priceToSubtract)) {
            $newGross = $this->getGross() - $priceToSubtract->getGross();
            $newNett = $this->getNett() - $priceToSubtract->getNett();

            return new Price($newNett, $newGross, $currency, $this->getTaxForPrices($this, $priceToSubtract));
        }

        return Price::buildEmpty();
    }

    private function buildCurrency(Price $A, Price $B): ?Currency
    {
        if ($A->isEmpty() === false && $B->isEmpty() === false) {
            $this->checkCurrencies($A->getCurrency(), $B->getCurrency());
        }

    	if ($A->hasCurrency()) {
    		return $A->getCurrency();
	    }

	    if ($B->hasCurrency()) {
    		return $B->getCurrency();
	    }

	    return null;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
    	if ($this->gross->getValue() == 0 && $this->nett->getValue() == 0)
	    {
	    	return true;
	    }

	    return false;
    }

    private function getTaxForPrices(Price $A, Price $B)
    {
    	if ($A->isEmpty()) {
	    	return $B->getTaxRate();
	    }

	    if ($B->isEmpty()) {
		    return $A->getTaxRate();
	    }

        if ($this->areTaxesIdentical($A, $B)) {
            return $A->getTaxRate();
        }

        return null;
    }

    private function areTaxesIdentical(Price $A, Price $B)
    {
        $bothHasTaxSet = $A->hasTaxRate() && $B->hasTaxRate();

        if ($bothHasTaxSet === false) {
            return false;
        }

        return $A->getTaxRate() === $B->getTaxRate();
    }

    public function multiply(float $times): Price
    {
        if ($times < 0) {
            throw new \LogicException('Multiply param must greater than 0');
        }

        $nett = $this->getNett() * $times;
        $gross = $this->getGross() * $times;

        return new Price($nett, $gross, $this->getCurrencySymbol(), $this->getTaxRate());
    }

    public function divide(float $times): Price
    {
        if ($times <= 0) {
            throw new \LogicException('Divide factor must be positive and greater than zero');
        }

        $nett = $this->getNett() / $times;
        $gross = $this->getGross() / $times;

        return new Price($nett, $gross, $this->getCurrencySymbol(), $this->getTaxRate());
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
    public function subtractGross(float $grossValue, string $currencySymbol)
    {
        $gross = new Money($grossValue);
        $this->checkCurrencies($this->getCurrency(), new Currency($currencySymbol));

	    if ($grossValue == 0)
	    {
		    return clone($this);
	    }

        if ($gross->getValue() > $this->getGross()) {
            return new Price(0, 0, $this->getCurrencySymbol());
        }

        $newGross = $this->getGross() - $gross->getValue();
        return new Price($this->getTax()->calculateNett($newGross), $newGross, $this->getCurrencySymbol(), $this->tax->getValue());
    }

    /**
     * Allow to subtract from nett value without knowing price tax rate
     *
     * @param float $nettValue
     */
    public function subtractNett($nettValue, string $currencySymbol): Price
    {
        $nett = new Money($nettValue);
        $this->checkCurrencies($this->getCurrency(), new Currency($currencySymbol));

        if ($nettValue == 0)
        {
            return clone($this);
        }

        if ($nett->getValue() > $this->getNett())
        {
            return new Price(0, 0, $this->getCurrencySymbol());
        }

        $newNett = $this->getNett() - $nett->getValue();
        return new Price($newNett, $this->getTax()->calculateGross($newNett), $this->getCurrencySymbol(), $this->tax->getValue());
    }

    /**
     * @param float $grossValue
     * @return Price
     */
    public function addGross(float $grossValue, string $currencySymbol = null): Price
    {
        if ($currencySymbol !== null) {
            $this->checkCurrencies($this->getCurrency(), new Currency($currencySymbol));
        }
        $gross = new Money($grossValue);

        $newGross = $this->getGross() + $gross->getValue();
        return new Price($this->getTax()->calculateNett($newGross), $newGross, $this->getCurrencySymbol(), $this->tax->getValue());
    }

    private function checkCurrencies(Currency $currencyA, Currency $currencyB): void
    {
        if ($currencyA->isEqual($currencyB) === false) {
            $message = sprintf(
                'Can not operate on different currencies ("%s" and "%s")',
                (string) $currencyA,
                (string) $currencyB
            );

            throw new \LogicException($message);
        }
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

    public function getIsCurrencyMergable(string $symbol): bool
    {
    	if (isset($this->currency) === false) {
    		return true;
	    }

	    return ((string) $this->currency) == $symbol;
    }
}
