<?php

namespace Ayeo\Price\Calculator;

use Ayeo\Price\Currency;
use Ayeo\Price\Decorator\Price\GrossNettRoundDecorator;

class CalculatorRegistry
{
    private static ?CalculatorRegistry $instance = null;
    /** @var CalculatorInterface[] */
    private array $calculators = [];
    private CalculatorInterface $defaultCalculator;

    private function __construct(CalculatorInterface $calculator)
    {
        $this->defaultCalculator = $calculator;
    }

    public static function getInstance(): CalculatorRegistry
    {
        if (null === self::$instance) {
            self::$instance = new CalculatorRegistry(new StandardCalculator(new GrossNettRoundDecorator(2)));
        }

        return self::$instance;
    }

    public function setCalculator(Currency $currency, StandardCalculator $configuration): void
    {
        // what if there is already defined configuration for given currency? For now, overwrite.
        $this->calculators[(string)$currency] = $configuration;
    }

    public function getCalculator(?Currency $currency): CalculatorInterface
    {
        if ($currency === null || !$this->has($currency)) {
            return $this->defaultCalculator;
        }

        return $this->calculators[(string)$currency];
    }

    private function has(Currency $currency): bool
    {
        return array_key_exists((string)$currency, $this->calculators);
    }
}
