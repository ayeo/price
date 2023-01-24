<?php

namespace Ayeo\Price;

/**
 * Currency model
 */
class Currency
{
	/**
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
	 * @var string
	 */
	private $symbol;

	/**
	 * @var string ISO 4217 (3 chars)
     * @throws \LogicException on invalid symbol
	 */
	public function __construct(string $symbol)
	{
		$this->symbol =  $this->validate($symbol);
	}

	public function __toString(): string
	{
		return $this->symbol;
	}

	public function getPrecision(): int
	{
		//todo: add precision map
		return Currency::PRECISION;
	}

	public function isEqual(Currency $currency): bool
	{
		return (string) $this === (string) $currency;
	}

	private function validate(string $symbol): string
	{
	    // we should check symbol is ISO 4217 valid
		if (preg_match('#^[A-Z]{3}$#', $symbol)) {
			return strtoupper($symbol);
		} else {
			$message = sprintf('Invalid currency symbol: "%s"', $symbol);
			throw new \LogicException($message);
		}
	}
}
