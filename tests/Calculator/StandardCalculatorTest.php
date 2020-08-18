<?php

namespace Ayeo\Price\Test\Calculator;

use Ayeo\Price\Calculator\StandardCalculator;
use Ayeo\Price\Price;
use PHPUnit\Framework\TestCase;

class StandardCalculatorTest extends TestCase
{
    public function subtractDataProvider(): array
    {
        return [
            [0, 'PLN', 0, 'PLN', 0],
            [0, 'PLN', 1, 'PLN', 0],
            [1, 'PLN', 0, 'PLN', 1],
            [1, 'PLN', 1, 'PLN', 0],
            [2, 'PLN', 1, 'PLN', 1],
            [1, 'PLN', 2, 'PLN', 0],
        ];
    }

    /**
     * @dataProvider subtractDataProvider
     */
    public function testSubtract(float $lValue, string $lCurrency, float $rValue, string $rCurrency, float $eResult)
    {
        $calculator = new StandardCalculator();
        $result = $calculator->subtract(
            Price::buildByNett($lValue, 0, $lCurrency),
            Price::buildByNett($rValue, 0, $rCurrency)
        );
        $this->assertEquals($eResult, $result->getNett());
    }

    public function addDataProvider(): array
    {
        return [
            [0, 'PLN', 0, 'PLN', 0],
            [0, 'PLN', 1, 'PLN', 1],
            [1, 'PLN', 0, 'PLN', 1],
            [1, 'PLN', 1, 'PLN', 2],
        ];
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAdd(float $lValue, string $lCurrency, float $rValue, string $rCurrency, float $eResult)
    {
        $calculator = new StandardCalculator();
        $result = $calculator->add(
            Price::buildByNett($lValue, 0, $lCurrency),
            Price::buildByNett($rValue, 0, $rCurrency)
        );
        $this->assertEquals($eResult, $result->getNett());
    }

    public function multiplyDataProvider(): array
    {
        return [
            [0, 'PLN', 0, 0],
            [0, 'PLN', 1, 0],
            [1, 'PLN', 0, 0],
            [1, 'PLN', 1, 1],
            [1, 'PLN', 2, 2],
            [100, 'PLN', 0.01, 1],
        ];
    }

    /**
     * @dataProvider multiplyDataProvider
     */
    public function testMultiply(float $lValue, string $lCurrency, float $times, float $eResult)
    {
        $calculator = new StandardCalculator();
        $result = $calculator->multiply(Price::buildByNett($lValue, 0, $lCurrency),$times);
        $this->assertEquals($eResult, $result->getNett());
    }

    public function divideDataProvider(): array
    {
        return [
            [0, 'PLN', 1, 0],
            [0.01, 'PLN', 0.01, 1],
            [1, 'PLN', 1, 1],
            [1, 'PLN', 2, 0.5],
            [100, 'PLN', 100, 1],
            [100, 'PLN', 0.01, 10000],
        ];
    }

    /**
     * @dataProvider divideDataProvider
     */
    public function testDivide(float $lValue, string $lCurrency, float $times, float $eResult)
    {
        $calculator = new StandardCalculator();
        $result = $calculator->divide(Price::buildByNett($lValue, 0, $lCurrency),$times);
        $this->assertEquals($eResult, $result->getNett());
    }
}
