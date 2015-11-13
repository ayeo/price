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
     * @var string ISO 4217 (3 uppercase chars)
     */
	private $currencySymbol;

    /**
     * @param float $nett
     * @param float $gross
     * @param string $currencySymbol
     */
    public function __construct($nett = 0.00, $gross = 0.00, $currencySymbol)
    {
        $this->validateValues($nett, $gross);

        $this->currencySymbol = $this->processCurrencySymbol($currencySymbol);
        $this->nett = $nett;
        $this->gross = $gross;
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
        return new Price($value, $value, $currencySymbol);
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
        return new Price(0, 0, $currencySymbol);
    }

    /**
     * @param float $nett
     * @param integer $tax
     * @param null|string $currencySymbol
     * @return Price
     */
    public static function buildByNett($nett, $tax, $currencySymbol = null)
    {
        return new Price($nett, $nett * (100 + Price::processTax($tax)) / 100, $currencySymbol);
    }

    /**
     * @param float $gross
     * @param integer $tax
     * @param null|string $currencySymbol
     * @return Price
     */
    public static function buildByGross($gross, $tax, $currencySymbol)
    {
        return new Price(Price::calculateNett($gross, Price::processTax($tax)), $gross, $currencySymbol);
    }

    /**
     * @return float
     */
    public function getNett()
    {
        return round($this->nett, Price::PRECISION);
    }

    /**
     * @return float
     */
    public function getGross()
    {
        return round($this->gross, Price::PRECISION);
    }

    /**
     * @return int
     */
    public function getTax()
    {
        if ($this->nett > 0) {
            return (int) round($this->gross / $this->nett * 100 - 100, 0);
        }

        return 0;
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
        $this->checkCurrencies($this->getCurrencySymbol(), $priceToAdd->getCurrencySymbol());

        $newGross = $this->getGross() + $priceToAdd->getGross();
        $newNett = $this->getNett() + $priceToAdd->getNett();

        return new Price($newNett, $newGross, $this->currencySymbol);
    }

    /**
     * @param Price $priceToSubtract
     * @return Price
     */
    public function subtract(Price $priceToSubtract)
    {
        $this->checkCurrencies($this->getCurrencySymbol(), $priceToSubtract->getCurrencySymbol());

        if ($this->isGreaterThan($priceToSubtract)) {
            $newGross = $this->getGross() - $priceToSubtract->getGross();
            $newNett = $this->getNett() - $priceToSubtract->getNett();

            return new Price($newNett, $newGross, $this->currencySymbol);
        }

        return Price::buildEmpty($this->getCurrencySymbol());
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
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->currencySymbol;
    }

    /**
     *  //fixme: what about currency validation
     * @param float $gross
     * @return Price
     */
    public function subtractGross($gross, $currencySymbol)
    {
        //todo: validate currency symbol
        $this->checkCurrencies($this->getCurrencySymbol(), $currencySymbol);
        $this->validateValue($gross);

        if ($gross > $this->getGross()) {
            return new Price(0, 0, $this->getCurrencySymbol());
        }

        $newGross = $this->getGross() - (float) $gross;
        $newNett = $this->calculateNett($newGross, $this->getTax());

        return new Price($newNett, $newGross, $this->currencySymbol);
    }

    /**
     * @param float $gross
     * @return Price
     */
    public function addGross($gross)
    {
        $this->validateValue($gross);

        $newGross = $this->getGross() + (float) $gross;
        $newNett = $this->calculateNett($newGross, $this->getTax());

        return new Price($newNett, $newGross, $this->currencySymbol);
    }

    /**
     * @param $tax
     * @return int
     */
    private static function processTax($tax)
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

    /**
     * @param float $gross
     * @param int $tax
     * @return float
     */
    private static function calculateNett($gross, $tax)
    {
        return $gross / (100 + $tax) * 100;
    }

    /**
     * @param double $gross
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
     * @param string $currencyA
     * @param string $currencyB
     */
    private function checkCurrencies($currencyA, $currencyB)
    {
        if ($currencyA !== $currencyB) {
            $message = sprintf(
                'Can not operate on different currencies ("%s" and "%s")',
                $currencyA,
                $currencyB
            );

            throw new \LogicException($message);
        }
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

        //floating point calculations precision problem here
        if (round($nett, 6) > round($gross, 6)) {
            throw new \LogicException('Nett must not be greater than gross');
        }
    }

    /**
     * @param string $currencySymbol ISO 4217 (3 uppercase chars)
     * @return string
     */
    private function processCurrencySymbol($currencySymbol)
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
     * Default format. Use own formatting for more custom purposes
     *
     * @return string
     */
    public function __toString()
    {
        return number_format($this->getGross(), 2, '.', ' ')." ".$this->getCurrencySymbol();
    }
}