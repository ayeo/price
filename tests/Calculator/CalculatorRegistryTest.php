<?php

namespace Ayeo\Price\Test\Calculator;

namespace Ayeo\Price\Test\Calculator;

use Ayeo\Price\Calculator\CalculatorRegistry;
use Ayeo\Price\Calculator\StandardCalculator;
use Ayeo\Price\Currency;
use PHPUnit\Framework\TestCase;

class CalculatorRegistryTest extends TestCase
{
    public function testCanSetAndGet(): void
    {
        $registry = CalculatorRegistry::getInstance();
        $registry->setCalculator($currencyUSD = new Currency('USD'), $calculator = new StandardCalculator());
        $registry->setCalculator($currencyEUR = new Currency('EUR'), new StandardCalculator());
        $this->assertSame($calculator, $registry->getCalculator($currencyUSD));
        $this->assertNotSame($calculator, $registry->getCalculator($currencyEUR));
    }
}
