<?php
namespace Ayeo\Price;

class Tax
{
	/**
	 * @var int
	 */
	private $value;

	public function __construct($tax)
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

		$this->value = $tax;
	}

	/**
	 * @param float $nett
	 * @param float $gross
	 * @return Tax
	 */
	static public function build($nett, $gross)
	{
		//todo validate?
		if ($nett > 0) {
			$taxValue =  (int) round($gross / $nett * 100 - 100, 0);
		} else {
			$taxValue = 0;
		}

		return new Tax($taxValue);
	}

	/**
	 * @return int
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Calculate gross value based on given nett
	 *
	 * @param float $nett
	 * @return float
	 */
	public function calculateGross($nett)
	{
		return $nett * ($this->getValue() + 100) / 100;
	}

	/**
	 * Calculate nett value based on given gross
	 *
	 * @param float $gross
	 * @return float
	 */
	public function calculateNett($gross)
	{
		return $gross * 100 / ($this->getValue() + 100);
	}

	public function validate($nett, $gross)
    {
        if (round($gross, 2) !== round($nett * (1 + $this->getValue()/ 100), 2)) {
            throw new \LogicException("Invalid tax rate");
        }
    }
}
