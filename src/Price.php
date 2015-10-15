<?php
namespace Ayeo\Price;

/**
 * Price model
 */
class Price
{
    /**
     * todo: Add map symbol => precision
     * There exists currencies with different precision
     * but those are extremely uncommon
     *
     * Full list:
     * https://pl.wikipedia.org/wiki/Jen
     * https://pl.wikipedia.org/wiki/Funt_cypryjski
     * https://pl.wikipedia.org/wiki/Dinar_iracki
     * https://pl.wikipedia.org/wiki/Dinar_jordański
     * https://pl.wikipedia.org/wiki/Dinar_kuwejcki
     * https://pl.wikipedia.org/wiki/Dinar_Bahrajnu
     */
    const PRECISION = 2;

    /**
     * @var float
     */
	private $nett;

    /**
     * @var float
     */
	private $gross;

    /**
     * @var null|string ISO 4217 (3 uppercase chars)
     */
	private $currencySymbol;

    /**
     * @param float $nett
     * @param float $gross
     * @param null $currencySymbol
     */
	public function __construct($nett = 0.00, $gross = 0.00, $currencySymbol = null)
	{
        $this->validateValues($nett, $gross);
        $this->currencySymbol = $this->processCurrencySymbol($currencySymbol);

        $this->nett = $nett;
        $this->gross = $gross;
	}

    /**
     * @param float $nett
     * @param integer $tax
     * @return Price
     */
    static public function buildByNett($nett, $tax)
    {
        $tax = Price::processTax($tax);
        $gross = $nett * (100 + $tax) / 100;

        return new Price($nett, $gross);
    }

    /**
     * @param float $gross
     * @param integer $tax
     * @return Price
     */
    static public function buildByGross($gross, $tax)
    {
        $tax = Price::processTax($tax);
        $nett = $gross / (100 + $tax) * 100;

        return new Price($nett, $gross);
    }

    /**
     * @param float $nett
     * @param float $gross
     */
    private function validateValues($nett, $gross)
    {
        if (is_numeric($nett) === false) {
            throw new \LogicException('Nett must be numeric');
        }

        if (is_numeric($gross) === false) {
            throw new \LogicException('Gross must be numeric');
        }

        if ($nett < 0) {
            throw new \LogicException('Nett must be positive');
        }

        if ($gross < 0) {
            throw new \LogicException('Gross must be positive');
        }

        if ($nett > $gross) {
            throw new \LogicException('Nett must not be greater than gross');
        }
    }

    /**
     * @param null|string $currencySymbol ISO 4217 (3 uppercase chars)
     * @return string
     */
    private function processCurrencySymbol($currencySymbol = null)
    {
        if (is_null($currencySymbol) === false) {
            if (preg_match('#^[A-Z]{3}$#', $currencySymbol)) {
                return strtoupper($currencySymbol);
            } else {
                $message = sprintf('Invalid currency symbol: "%s"', $currencySymbol);
                throw new \LogicException($message);
            }
        }
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
     * @return float
     */
	public function getGross()
	{
		return round($this->gross, Price::PRECISION);
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
     * @return float
     */
	public function getNett()
	{
		return round($this->nett, Price::PRECISION);
	}

    /**
     * @param Price $priceToAdd
     * @return Price
     */
	public function add(Price $priceToAdd)
	{
		$this->checkCurrencies($this, $priceToAdd);

		$newGross = $this->getGross() + $priceToAdd->getGross();
		$newNett = $this->getNett() + $priceToAdd->getNett();

		return new Price($newNett, $newGross, $this->currencySymbol);
	}

    /**
     * @param Price $A
     * @param Price $B
     */
    private function checkCurrencies(Price $A, Price $B)
    {
        if ($A->hasCurrency() === false && $B->hasCurrency() === false) {
            return;
        }

        //fixme: one of currencies may still not been set here
        //we get exception anyway, should we translate the exception here?

        if ($A->getCurrencySymbol() !== $B->getCurrencySymbol()) {
            $message = sprintf(
                'Can not do operate on different currencies ("%s" and "%s")',
                $A->getCurrencySymbol(),
                $B->getCurrencySymbol()
            );
            throw new \LogicException($message);
        }
    }

    /**
     * @return bool
     */
    public function hasCurrency()
    {
        return isset($this->currencySymbol);
    }

    /**
     * Returns 3 chars iso 4217 symbol
     * @return string
     */
    public function getCurrencySymbol()
    {
        if (is_null($this->currencySymbol)) {
            throw new \RuntimeException('Currency symbol is not set');
        }

        return $this->currencySymbol;
    }

    /**
     * @param Price $priceToSubtract
     * @return Price
     */
    public function subtract(Price $priceToSubtract)
    {
        $this->checkCurrencies($this, $priceToSubtract);

        if ($this->isGreaterThan($priceToSubtract)) {
            $newGross = $this->getGross() - $priceToSubtract->getGross();
            $newNett = $this->getNett() - $priceToSubtract->getNett();

            return new Price($newNett, $newGross, $this->currencySymbol);
        }

        return new Price(); //zero
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
     * @param integer $times
     * @return Price
     */
    public function multiply($times)
    {
        if (is_integer($times) === false) {
            throw new \LogicException('Multiply param must be integer');
        }

        if ($times <= 0) {
            throw new \LogicException('Multiply param must greater than 0');
        }

        $nett = $this->getNett() * $times;
        $gross = $this->getGross() * $times;

        return new Price($nett, $gross, $this->currencySymbol);
    }

    /**
     * @param float $gross
     * @return Price
     */
    public function subtractGross($gross)
    {
        $this->validateValue($gross);

        if ($gross > $this->getGross())
        {
            return new Price();
        }

        $newGross = $this->getGross() - (float) $gross;
        $newNett = $this->calculateNett($newGross);

        return new Price($newNett, $newGross, $this->currencySymbol);
    }

    /**
     * @param $gross
     */
    private function validateValue($gross)
    {
        if (is_numeric($gross) === false) {
            throw new \LogicException('Value must be numeric');
        }

        if ($gross <= 0) {
            throw new \LogicException('Value must be greater than zero');
        }
    }

    /**
     * @param float $gross
     * @return float
     */
    private function calculateNett($gross)
    {
        return $gross / (1 + $this->getTax() / 100);
    }

    /**
     * @return int
     */
	public function getTax()
	{
        if ($this->nett > 0) {
            return round($this->gross / $this->nett * 100 - 100, 0);
        }

        return 0;
	}

    /**
     * @param float $gross
     * @return Price
     */
    public function addGross($gross)
    {
        $this->validateValue($gross);

        $newGross = $this->getGross() + (float) $gross;
        $newNett = $newNett = $this->calculateNett($newGross);

        return new Price($newNett, $newGross, $this->currencySymbol);
    }

    /**
     * @param $tax
     * @return int
     */
    static private function processTax($tax)
    {
        if (is_numeric($tax) === false) {
            throw new \LogicException('Tax percent must be integer');
        }

        if ((float) $tax != round($tax, 0)) {
            throw new \LogicException('Tax percent must be integer');
        }

        if ($tax < 0) {
            throw new \LogicException('Tax percent must positive');
        }

        return (int) $tax;
    }
}