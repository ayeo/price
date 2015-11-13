<?php
namespace Ayeo\Price;

class Money
{
	/**
	 * @var float
	 */
	private $value;

	/**
	 * @param float $value
	 */
	public function __construct($value)
	{
		if (is_numeric($value) === false) {
			throw new \LogicException('Money value must be numeric');
		}

		if ($value < 0) {
			throw new \LogicException('Money value must be positive');
		}

		$this->value = (float) $value;
	}

	/**
	 * @return float
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param Money $money
	 * @return bool
	 */
	public function isGreaterThan(Money $money)
	{
		//floating point calculations precision problem here
		return round($this->getValue(), 6) > round($money->getValue(), 6);
	}
}
