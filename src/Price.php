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
     * @deprecated: Constructor will be private within few next releases
     * It is not possible to calculate correct tax rate for a given nett and gross
     * Dedicated class will be introduced to allow building price with these data but
     * acquiring tax value will be prohibited
     *
     * Building price is only possible using:
     * - buildByGross()
     * - buildByNett()
     * - buildEmpty()
     *
     * @param float $nett
     * @param float $gross
     * @param string $currencySymbol
     */
    public function __construct($nett = 0.00, $gross = 0.00, $currencySymbol, $tax = null)
    {
        //var_dump($tax);
        //todo: check tax
        $this->currency = new Currency($currencySymbol);
        $this->nett = new Money($nett);
        $this->gross = new Money($gross);

        if ($this->nett->isGreaterThan($this->gross))
        {
            throw new \LogicException('Nett must not be greater than gross');
        }

        if (is_null($tax)) {
            $this->tax = Tax::build($nett, $gross);
            $this->mixedTax = true;
        } else {
            $this->tax = new Tax($tax);
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
    public static function buildEmpty($currencySymbol)
    {
        return new Price(0, 0, $currencySymbol, 0);
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
        return round($this->nett->getValue(), $this->currency->getPrecision());
    }

    /**
     * @return float
     */
    public function getGross()
    {
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
     * Returns tax rate not value!!
     * @return int
     */
    public function getTaxValue()
    {
        if ($this->hasTaxRate() == false) {
            throw new \LogicException("Tax rate is mixed");
        }

        return $this->getTax()->getValue();
    }

    /**
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
        $isGrossEqual = $this->getGross() === $price->getGross();
        $isNettEqual = $this->getNett() === $price->getNett();

        return ($isGrossEqual  && $isNettEqual);
    }

    /**
     * @param Price $priceToAdd
     * @return Price
     */
    public function add(Price $priceToAdd)
    {
        $this->checkCurrencies($this->getCurrency(), $priceToAdd->getCurrency());

        $newGross = $this->getGross() + $priceToAdd->getGross();
        $newNett = $this->getNett() + $priceToAdd->getNett();

        return new Price($newNett, $newGross, $this->getCurrencySymbol(), $this->getTaxForPrices($this, $priceToAdd));
    }

    /**
     * @param Price $priceToSubtract
     * @return Price
     */
    public function subtract(Price $priceToSubtract)
    {
        $this->checkCurrencies($this->getCurrency(), $priceToSubtract->getCurrency());

        if ($this->isGreaterThan($priceToSubtract)) {
            $newGross = $this->getGross() - $priceToSubtract->getGross();
            $newNett = $this->getNett() - $priceToSubtract->getNett();

            return new Price($newNett, $newGross, $this->getCurrencySymbol(), $this->getTaxForPrices($this, $priceToSubtract));
        }

        return Price::buildEmpty($this->getCurrencySymbol());
    }

    private function getTaxForPrices(Price $A, Price $B)
    {
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

        return new Price($nett, $gross, $this->getCurrencySymbol());
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

        return new Price($nett, $gross, $this->getCurrencySymbol());
    }

    /**
     * Returns 3 chars iso 4217 symbol
     *
     * @return string
     */
    public function getCurrencySymbol()
    {
        return (string) $this->currency;
    }

    /**
     * @return Currency
     */
    public function getCurrency()
    {
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
}