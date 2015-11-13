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
	 */
	public function __construct($symbol)
	{
		$this->symbol =  $this->validate($symbol);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->symbol;
	}

	/**
	 * @return int
	 */
	public function getPrecision()
	{
		//todo: add precision map
		return Currency::PRECISION;
	}

	/**
	 * @param Currency $currency
	 * @return bool
	 */
	public function isEqual(Currency $currency)
	{
		return (string) $this === (string) $currency;
	}

	/**
	 * @param string $symbol
	 * @return string
	 * @throws \LogicException
	 */
	private function validate($symbol)
	{
		if (preg_match('#^[A-Z]{3}$#', $symbol)) {
			return strtoupper($symbol);
		} else {
			$message = sprintf('Invalid currency symbol: "%s"', $symbol);
			throw new \LogicException($message);
		}
	}
}