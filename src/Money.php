<?php

namespace Ayeo\Price;

class Money
{
	private float $value;

	public function __construct(float $value)
	{
		if ($value < 0.) {
			throw new \LogicException('Money value must be positive');
		}

		$this->value = $value;
	}

	public function getValue(): float
	{
		return $this->value;
	}

	public function isGreaterThan(Money $money): bool
	{
		//floating point calculations precision problem here
		return round($this->getValue(), 6) > round($money->getValue(), 6);
	}
}
