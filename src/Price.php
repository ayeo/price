<?php
namespace Ayeo\Price;

/**
 * Price model
 */
class Price
{
    /**
     * @var Money
     */
	private $nett;

    /**
     * @var Money
     */
	private $gross;

    /**
     * @var Currency
     */
	private $currency;

    /**
     * @var Tax
     */
	private $tax;

    /**
     * @var bool
     */
	private $mixedTax = false;

    /**
     * @param float $nett
     * @param float $gross
     * @param string $currencySymbol
     */
    public function __construct($nett = 0.00, $gross = 0.00, $currencySymbol = null, $taxRate = null)
    {
    	if ($nett == 0 && $gross == 0 && is_null($currencySymbol)) {

	    }
	    else
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

    /**
     * Builds price using same value for gross and nett
     * That means 0% tax
     *
     * @param float $value
     * @param $currencySymbol
     * @return Price
     */
    public static function build($value, $currencySymbol)
    {
        return Price::buildByNett($value, 0, $currencySymbol);
    }

    /**
     * fixme: does zero price needs currency symbol?
     * supporting the issue is overkill (no explicit advantages)
     *
     * Builds zero price
     *
     * @param $currencySymbol
     * @return Price
     */
    public static function buildEmpty($currency = null)
    {
        return new Price(0, 0, $currency, 0);
    }

    /**
     * @param float $nett
     * @param integer $taxValue
     * @param string $currencySymbol
     * @return Price
     */
    public static function buildByNett($nett, $taxValue, $currencySymbol = null)
    {
        $tax = new Tax($taxValue);

        return new Price($nett, $tax->calculateGross($nett), $currencySymbol, $taxValue);
    }

    /**
     * @param float $gross
     * @param integer $taxValue
     * @param string $currencySymbol
     * @return Price
     */
    public static function buildByGross($gross, $taxValue, $currencySymbol)
    {
        $tax = new Tax($taxValue);

        return new Price($tax->calculateNett($gross), $gross, $currencySymbol, $taxValue);
    }

    /**
     * @return float
     */
    public function getNett()
    {
	    if (is_null($this->currency)){
		    return $this->nett->getValue();
	    }

	    return round($this->nett->getValue(), $this->currency->getPrecision());

    }

    /**
     * @return float
     */
    public function getGross()
    {
    	if (is_null($this->currency)) {
		    return $this->gross->getValue(); //should be always 0
	    }

	    return round($this->gross->getValue(), $this->currency->getPrecision());
    }

    /**
     * @return Tax;
     */
    private function getTax()
    {
        return $this->tax;
    }

    /**
     * @return int
     */
    public function getTaxRate()
    {
        return $this->getTaxValue();
    }

    /**
     * @deprecated Use getTaxRate()
     * Returns tax rate not value!!
     * @return int
     */
    public function getTaxValue()
    {
        if ($this->hasTaxRate() == false) {
		//can not throw exception couse dp catch it and not working
            //throw new \LogicException("Tax rate is mixed");
        }

        return $this->getTax()->getValue();
    }

    /**
     * @return float
     */
    public function getTaxDiff()
    {
        return $this->getGross() - $this->getNett();
    }

    /**
     * @deprecated: use getTaxDiff() instead
     * Returns tax value!
     * @return float
     */
    public function getTaxPrice()
    {
        return $this->getGross() - $this->getNett();
    }

    /**
     * @param Price $price
     * @return bool
     */
    public function isLowerThan(Price $price)
    {
        return $this->getGross() < $price->getGross();
    }

    /**
     * @param Price $price
     * @return bool
     */
    public function isGreaterThan(Price $price)
    {
        return $this->getGross() > $price->getGross();
    }

    /**
     * @param Price $price
     * @return bool
     */
    public function isEqual(Price $price)
    {
    	try {
		    $isCurrencyEqual = $this->getCurrency()->isEqual($price->getCurrency());
	    } catch (\LogicException $e) {
    		return false;
	    }

        $isGrossEqual = $this->getGross() === $price->getGross();
        $isNettEqual = $this->getNett() === $price->getNett();

        return ($isGrossEqual  && $isNettEqual && $isCurrencyEqual);
    }

    /**
     * @param Price $priceToAdd
     * @return Price
     */
    public function add(Price $priceToAdd)
    {
	    if ($this->isEmpty() || $priceToAdd->isEmpty()) {

	    } else {
		    $this->checkCurrencies($this->getCurrency(), $priceToAdd->getCurrency());
	    }

	    $currency = $this->buildCurrency($this, $priceToAdd);

        $newGross = $this->getGross() + $priceToAdd->getGross();
        $newNett = $this->getNett() + $priceToAdd->getNett();

	    $taxRate = $this->getTaxForPrices($this, $priceToAdd);
	    return new Price($newNett, $newGross, $currency, $taxRate);
    }

    /**
     * @param Price $priceToSubtract
     * @return Price
     */
    public function subtract(Price $priceToSubtract)
    {
    	if ($this->isEmpty() || $priceToSubtract->isEmpty()) {

	    } else {
		    $this->checkCurrencies($this->getCurrency(), $priceToSubtract->getCurrency());
	    }

	    $currency = $this->buildCurrency($this, $priceToSubtract);

        if ($this->isGreaterThan($priceToSubtract)) {
            $newGross = $this->getGross() - $priceToSubtract->getGross();
            $newNett = $this->getNett() - $priceToSubtract->getNett();

            return new Price($newNett, $newGross, $currency, $this->getTaxForPrices($this, $priceToSubtract));
        }

        return Price::buildEmpty();
    }

    private function buildCurrency(Price $A, Price $B)
    {
    	if ($A->isEmpty() === false) {
    		return $A->getCurrency();
	    }

	    if ($B->isEmpty() === false) {
    		return $B->getCurrency();
	    }

	    return null;
    }
    public function isEmpty()
    {
    	if ($this->gross->getValue() == 0 && $this->nett->getValue() == 0 && is_null($this->currency))
	    {
	    	return true;
	    }

	    return false;
    }

    private function getTaxForPrices(Price $A, Price $B)
    {
    	if ($A->isEmpty()) {
	    	return $B->getTaxValue();
	    }

	    if ($B->isEmpty()) {
		    return $A->getTaxValue();
	    }

        if ($this->areTaxesIdentical($A, $B)) {
            return $A->getTaxValue();
        }

        return null;
    }

    private function areTaxesIdentical(Price $A, Price $B)
    {
        $bothHasTaxSet = $A->hasTaxRate() && $B->hasTaxRate();

        if ($bothHasTaxSet === false) {
            return false;
        }

        return $A->getTaxValue() === $B->getTaxValue();
    }

    /**
     * @param float $times
     * @return Price
     */
    public function multiply($times)
    {
        if ($times <= 0) {
            throw new \LogicException('Multiply param must greater than 0');
        }

        $nett = $this->getNett() * $times;
        $gross = $this->getGross() * $times;

        return new Price($nett, $gross, $this->getCurrencySymbol(), $this->getTaxRate());
    }

    /**
     * @param float $times
     * @return Price
     */
    public function divide($times)
    {
        if ($times <= 0) {
            throw new \LogicException('Divide factor must be positive and greater than zero');
        }

        $nett = $this->getNett() / $times;
        $gross = $this->getGross() / $times;

        return new Price($nett, $gross, $this->getCurrencySymbol(), $this->getTaxRate());
    }

    /**
     * Returns 3 chars iso 4217 symbol
     *
     * @return string
     */
    public function getCurrencySymbol()
    {
    	if (is_null($this->currency)) {
    		return null;
	    }

        return (string) $this->currency;
    }

    /**
     * @return Currency
     */
    public function getCurrency()
    {
    	if (is_null($this->currency)) {
    		throw new \LogicException('Currency is unknown');
	    }
        return $this->currency;
    }

    /**
     * Allow to subtract from gross value without knowing price tax rate
     *
     * @param $grossValue
     * @param $currencySymbol
     * @return Price
     */
    public function subtractGross($grossValue, $currencySymbol)
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
        return new Price($this->getTax()->calculateNett($newGross), $newGross, $this->getCurrencySymbol());
    }

    /**
     * @param float $grossValue
     * @return Price
     */
    public function addGross($grossValue) //todo:add currency
    {
        $gross = new Money($grossValue);

        $newGross = $this->getGross() + $gross->getValue();
        return new Price($this->getTax()->calculateNett($newGross), $newGross, $this->getCurrencySymbol(), $this->tax->getValue());
    }

    /**
     * @param Currency $currencyA
     * @param Currency $currencyB
     */
    private function checkCurrencies(Currency $currencyA, Currency $currencyB)
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
     *
     * @return string
     */
    public function __toString()
    {
        return number_format($this->getGross(), 2, '.', ' ')." ".$this->getCurrencySymbol();
    }

    /**
     * @return bool
     */
    public function hasTaxRate()
    {
        return $this->mixedTax == false;
    }

    public function getIsCurrencyMergable($symbol)
    {
    	if (isset($this->currency) === false) {
    		return true;
	    }

	    return ((string) $this->currency) == $symbol;
    }
}
