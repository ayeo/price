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
            [0, 'PLN', 0, 0, 'PLN', 0, 0, 0, true],
            [0, 'PLN', 10, 0, 'PLN', 10, 0, 0, true],
            [0, 'PLN', 0, 0, 'PLN', 10, 0, 0, true],
            [0, 'PLN', 0, 1, 'PLN', 0, 0, 0, true],
            [1, 'PLN', 0, 0, 'PLN', 0, 1, 1, true],
            [1, 'PLN', 10, 1, 'PLN', 10, 0, 0, true],
            [10, 'PLN', 10, 10, 'PLN', 0, 0, 0, false],
            [2, 'PLN', 0, 1, 'PLN', 0, 1, 1, true],
            [100, 'PLN', 10, 10, 'PLN', 0, 90, 100, false],
            [100, 'PLN', 5, 10, 'PLN', 50, 90, 90, false],
            [1, 'PLN', 0, 2, 'PLN', 0, 0, 0, true],
        ];
    }

    /**
     * @dataProvider subtractDataProvider
     */
    public function testSubtract(
        float $lValue,
        string $lCurrency,
        int $lTax,
        float $rValue,
        string $rCurrency,
        int $rTax,
        float $eResultNett,
        float $eResultGross,
        bool $hasTaxRate
    ): void {
        $calculator = new StandardCalculator();
        $result = $calculator->subtract(
            Price::buildByNett($lValue, $lTax, $lCurrency),
            Price::buildByNett($rValue, $rTax, $rCurrency)
        );
        $this->assertEquals($eResultNett, $result->getNett());
        $this->assertEquals($eResultGross, $result->getGross());
        $this->assertEquals($hasTaxRate, $result->hasTaxRate());
    }

    public function addDataProvider(): array
    {
        return [
            [0, 'PLN', 0, 0, 'PLN', 0, 0, 0, true],
            [0, 'PLN', 0, 1, 'PLN', 0, 1, 1, true],
            [1, 'PLN', 0, 0, 'PLN', 0, 1, 1, true],
            [1, 'PLN', 0, 1, 'PLN', 0, 2, 2, true],
            [10, 'PLN', 10, 10, 'PLN', 0, 20, 21, false],
        ];
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAdd(
        float $lValue,
        string $lCurrency,
        int $lTax,
        float $rValue,
        string $rCurrency,
        int $rTax,
        float $eResultNett,
        float $eResultGross,
        bool $hasTaxRate
    ): void {
        $calculator = new StandardCalculator();
        $result = $calculator->add(
            Price::buildByNett($lValue, $lTax, $lCurrency),
            Price::buildByNett($rValue, $rTax, $rCurrency)
        );
        $this->assertEquals($eResultNett, $result->getNett());
        $this->assertEquals($eResultGross, $result->getGross());
        $this->assertEquals($hasTaxRate, $result->hasTaxRate());
    }

    public function multiplyDataProvider(): array
    {
        return [
            [0, 'PLN', 0, 0, 0, 0],
            [0, 'PLN', 0, 1, 0, 0],
            [1, 'PLN', 0, 0, 0, 0],
            [1, 'PLN', 0, 1, 1, 1],
            [1, 'PLN', 0, 2, 2, 2],
            [100, 'PLN', 0, 0.01, 1, 1],
            [0, 'PLN', 10, 0, 0, 0],
            [0, 'PLN', 10, 1, 0, 0],
            [1, 'PLN', 10, 0, 0, 0],
            [10, 'PLN', 10, 1, 10, 11],
            [10, 'PLN', 10, 2, 20, 22],
            [100, 'PLN', 10, 0.01, 1, 1.1],
        ];
    }

    /**
     * @dataProvider multiplyDataProvider
     */
    public function testMultiply(
        float $value,
        string $currency,
        int $tax,
        float $times,
        float $resultN,
        float $resultG
    ): void {
        $calculator = new StandardCalculator();
        $result = $calculator->multiply(Price::buildByNett($value, $tax, $currency), $times);
        $this->assertEquals($resultN, $result->getNett());
        $this->assertEquals($resultG, $result->getGross());
        $this->assertTrue($result->hasTaxRate());
    }

    public function divideDataProvider(): array
    {
        return [
            [0, 0, 'PLN', 0, 1, 0, 0],
            [0, 0, 'PLN', 10, 1, 0, 0],
            [0.01, 0.01, 'PLN', 0, 0.01, 1, 1],
            [0.1, 0.11, 'PLN', 10, 0.01, 10, 11],
            [1, 1, 'PLN', 0, 1, 1, 1],
            [1, 1.1, 'PLN', 10, 1, 1, 1.1],
            [1, 1, 'PLN', 0, 2, 0.5, 0.5],
            [1, 1.1, 'PLN', 10, 2, 0.5, 0.55],
            [100, 100, 'PLN', 0, 100, 1, 1],
            [100, 110, 'PLN', 10, 100, 1, 1.1],
            [100, 100, 'PLN', 0, 0.01, 10000, 10000],
            [100, 110, 'PLN', 10, 0.01, 10000, 11000],
            [9.7560975609756, 12, 'PLN', 23, 0.8, 12.2, 15],
        ];
    }

    /**
     * @dataProvider divideDataProvider
     */
    public function testDivide(
        float $nett,
        float $gross,
        string $currency,
        int $tax,
        float $times,
        float $resultN,
        float $resultG
    ): void {
        $calculator = new StandardCalculator();
        $result = $calculator->divide(new Price($nett, $gross, $currency, $tax), $times);
        $this->assertEquals($resultN, $result->getNett());
        $this->assertEquals($resultG, $result->getGross());
        $this->assertTrue($result->hasTaxRate());
    }

    public function testDivideNegative(): void
    {
        $this->expectException(\LogicException::class);
        $calculator = new StandardCalculator();
        $calculator->divide(new Price(1, 1, 'USD'), -1);
    }

    public function testAddEmpty(): void
    {
        $calculator = new StandardCalculator();
        $left = new Price(1, 1, 'USD');
        $right = Price::buildEmpty();

        $sum = $calculator->add($left, $right);
        $this->assertEquals(1, $sum->getNett());
        $sum = $calculator->add($right, $left);
        $this->assertEquals(1, $sum->getNett());
        $sum = $calculator->add($right, $right);
        $this->assertEquals(0, $sum->getNett());
    }
}
