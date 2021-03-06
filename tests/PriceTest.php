<?php

namespace Ayeo\Price\Test;

use Ayeo\Price\Price;
use LogicException;
use PHPUnit\Framework\TestCase;

class PriceTest extends TestCase
{
    public function testBuildInvalidPrice()
    {
        $this->expectException(LogicException::class);
        new Price(120.00, 100.00, "PLN", 23);
    }

    public function testAddingPricesWithSameTax()
    {
        $A = Price::buildByNett(100.00, 20, "PLN");
        $B = Price::buildByNett(200.00, 20, "PLN");

        $result = $A->add($B);

        $this->assertEquals(300.00, $result->getNett());
        $this->assertEquals(360.00, $result->getGross());
        $this->assertEquals(20, $result->getTaxRate());
        //$this->assertEquals(true, $result->hasTaxRate());
    }

    public function testAddingPricesWithDifferentTax()
    {
        $A = Price::buildByNett(100.00, 20, "PLN");
        $B = Price::buildByNett(200.00, 10, "PLN");

        $result = $A->add($B);

        $this->assertEquals(300.00, $result->getNett());
        $this->assertEquals(120 + 220, $result->getGross());
        $this->assertEquals(false, $result->hasTaxRate());
    }

    public function testSimpleSubtractingPrices()
    {
        $A = Price::buildByNett(180.00, 23, "PLN");
        $B = Price::buildByNett(220.00, 23, "PLN");

        $result = $B->subtract($A);

        $this->assertEquals(40.00, $result->getNett());
        $this->assertEquals(40.00 * 1.23, $result->getGross());
        $this->assertEquals(true, $result->hasTaxRate());
    }

    public function testSubtractingPricesWithDifferentTax()
    {
        $A = Price::buildByNett(160.00, 20, "PLN");
        $B = Price::buildByNett(120.00, 10, "PLN");

        $result = $A->subtract($B);

        $this->assertEquals(40.00, $result->getNett());
        $this->assertEquals(60, $result->getGross());
        $this->assertEquals(false, $result->hasTaxRate());
    }

    /**
     * @dataProvider creatingDataProvider
     */
	public function testCreating($nett, $gross, $tax)
	{
		$price = new Price($nett, $gross, 'USD', $tax);
        $this->assertEquals($tax, $price->getTaxRate());
	}

    /**
     * @dataProvider creatingDataProvider
     */
    public function testBuildingGross($nett, $gross, $tax)
    {
        $price = Price::buildByGross($gross, $tax, 'USD');
        $this->assertEquals(round($nett, 2), $price->getNett());
    }

    /**
     * @dataProvider creatingDataProvider
     */
    public function testBuildingNett($nett, $gross, $tax)
    {
        $price = Price::buildByNett($nett, $tax, 'USD');
        $this->assertEquals(round($gross, 2), $price->getGross());
    }

    public function creatingDataProvider()
    {
        return [
            [100, 123, 23],
            [68.2927, 84.0000, 23],
            [31.7073, 39.0000, 23],

            [109.7561, 135.0000, 23],
            [109.7561, 135.0000, 23],
            [109.7561, 135.0001, 23],
            [109.7561, 135.0002, 23],
            [109.7561, 135.0003, 23],
            [109.7561, 135.0004, 23],
            [109.7561, 135.0005, 23],
            [109.7561, 135.0006, 23],
            [109.7561, 135.0007, 23],
            [109.7561, 135.0008, 23],
            [109.7561, 135.0009, 23],
            [109.7561, 135.0010, 23],
            [109.7561, 135.0020, 23],
            [109.7561, 135.0030, 23],
            [109.7561, 135.0040, 23],
            [110.16, 135.5000, 23],

            [0.81, 0.8748, 8],
        ];
    }

	/**
	 * @dataProvider addingDataProvider
	 */
	public function testAdding($grossA, $grossB, $expectedGross)
	{
        $tax = 23;
		$nettA = $grossA / (100 + $tax) * 100;
		$nettB = $grossB / (100 + $tax) * 100;

		$A = new Price($nettA, $grossA, 'USD', $tax);
		$B = new Price($nettB, $grossB, 'USD', $tax);

		$this->assertEquals($expectedGross, $A->add($B)->getGross());
		$this->assertEquals($expectedGross, $B->add($A)->getGross());

		$this->assertEquals($tax, $B->add($A)->getTaxRate());
		$this->assertEquals($tax, $A->add($B)->getTaxRate());

		$this->assertEqualsWithDelta($nettA, $A->getNett(), 0.01);
		$this->assertEqualsWithDelta($nettB, $B->getNett(), 0.01);
	}

	public function addingDataProvider()
	{
		return [
			[123.00,     246.00,    369.00],
			[ 32.21,      33.32,     65.53],
		];
	}

    public function testPricesAreImmutableWhileAdding()
    {
        $A = new Price(100, 120, 'PLN');
        $B = new Price(200, 300, 'PLN');
        $A->add($B);

        $this->assertEquals(100, $A->getNett());
        $this->assertEquals(120, $A->getGross());

        $this->assertEquals(200, $B->getNett());
        $this->assertEquals(300, $B->getGross());
    }

    public function testAddingSameCurrencies()
    {
        $A = new Price(100, 130, 'USD');
        $B = new Price(300, 330, 'USD');

        $C = $A->add($B);
        $this->assertEquals(400, $C->getNett());
        $this->assertEquals(460, $C->getGross());
        $this->assertEquals('USD', $C->getCurrencySymbol());
    }

    public function testAddingDifferentCurrencies()
    {
        $this->expectExceptionMessage('Can not operate on different currencies ("USD" and "GBP")');
        $A = new Price(100, 130, 'USD');
        $B = new Price(300, 330, 'GBP');
        $A->add($B);
    }

    public function testNettGreaterThanGross()
    {
        $this->expectExceptionMessage('Nett must not be greater than gross');
        new Price(100.00, 90.00, 'USD');
    }

    public function testNettSameAsGross()
    {
        $price = new Price(100.00, 100.00, 'USD', 0);
        $this->assertEquals(0, $price->getTaxRate());
    }

    public function testInvalidCurrencySymbol()
    {
        $this->expectExceptionMessage('Invalid currency symbol: "PLNG"');
        new Price(100, 200, 'PLNG');
    }

    public function testSubtractGrossBiggerThanPrice()
    {
        $price = new Price(100, 140, 'PLN', 40);
        $newPrice = $price->subtractGross(150.00, 'PLN');

        $this->assertEquals(0.00, $newPrice->getGross());
        $this->assertEquals(0.00, $newPrice->getNett());
        $this->assertFalse($newPrice->hasTaxRate());
    }

    public function testSubstractingGraterPrice()
    {
        $smaller = new Price(1.00, 1.10, 'EUR', 10);
        $bigger = new Price(2.00, 2.20, 'EUR', 10);

        $result = $smaller->subtract($bigger);
        $this->assertEquals(0.00, $result->getGross());
        $this->assertEquals(0.00, $result->getNett());
        $this->assertEquals(true, $result->hasTaxRate());

    }

    /**
     * @dataProvider isEqualDataProvider
     */
    public function testIsEqual(float $nA, float $gA, ?string $sA, float $nB, float $gB, ?string $sB, bool $expect): void
    {
        $A = new Price($nA, $gA, $sA);
        $B = new Price($nB, $gB, $sB);

        if ($expect)
        {
            $this->assertTrue($A->isEqual($B));
            $this->assertTrue($B->isEqual($A));
        }
        else
        {
            $this->assertFalse($A->isEqual($B));
            $this->assertFalse($B->isEqual($A));
        }
    }

    public function isEqualDataProvider()
    {
        return [
            [100.00, 123.00, 'USD', 100.00, 123.00, 'USD', true],
            [100.00, 123.00, 'USD', 100.01, 123.02, 'USD', false],
            [100.00, 123.00, 'USD', 100.0014, 123.0021, 'USD', true],
            [100.00, 123.00, 'USD', 100.00, 123.00, 'EUR', false],
            [100.00, 123.00, 'USD', 100.01, 123.02, 'EUR', false],
            [100.00, 123.00, 'USD', 100.0014, 123.0021, 'EUR', false],
            [100.00, 123.00, null, 100.0014, 123.0021, 'EUR', false],
            [100.00, 123.00, 'USD', 100.0014, 123.0021, null, false],
            [100.00, 123.00, null, 100.0014, 123.0021, null, true], // round prices so are same
        ];
    }

    public function testNegativeNett()
    {
        $this->expectExceptionMessage('Money value must be positive');
        new Price(-10.00, 20, 'USD');
    }

    public function testNegativeNettAndGross()
    {
        $this->expectExceptionMessage('Money value must be positive');
        new Price(10.00, -15.00, 'USD');
    }

    public function  testSubtractNett()
    {
        $price = new Price(10, 12, 'USD');
        $result = $price->subtractNett(5.00, 'USD');

        $this->assertEquals(5, $result->getNett());
        $this->assertEquals(6, $result->getGross());
        $this->assertFalse($result->hasTaxRate());
    }

    public function testSubtractNegativeNett()
    {
        $this->expectExceptionMessage('Money value must be positive');
        $price = new Price(13.34, 15.53, 'USD');
        $price->subtractNett(-10.00, 'USD');
    }

    /**
     * Allow to subtract 0
     */
    public function testSubtractZeroNett()
    {
        $price = new Price(13.34, 15.53, 'USD');
        $newPrice = $price->subtractNett(0.00, 'USD');
        $this->assertTrue($price->isEqual($newPrice));
        $this->assertFalse($price->hasTaxRate());
    }

    public function  testSubtractGross()
    {
        $price = new Price(13.34, 15.53, 'USD');
        $result = $price->subtractGross(10.00, 'USD');

        $this->assertEquals(5.53, $result->getGross());
    }

    public function testSubtractNegativeGross()
    {
        $this->expectExceptionMessage('Money value must be positive');
        $price = new Price(13.34, 15.53, 'USD');
        $price->subtractGross(-10.00, 'USD');
    }

    /**
     * Allow to subtract 0
     */
    public function testSubtractZeroGross()
    {
        $price = new Price(13.34, 15.53, 'USD');
        $newPrice = $price->subtractGross(0.00, 'USD');
        $this->assertTrue($price->isEqual($newPrice));
    }
    public function testAddGross()
    {
        $A = new Price(13.24, 20.99, 'USD');
        $result = $A->addGross(10.01);

        $this->assertEquals(31.00, $result->getGross());
        $this->assertEquals('USD', $result->getCurrencySymbol());
        $this->assertEquals(20.99, $A->getGross());
    }

    public function testNettValueAfterAddGross()
    {
        $A = new Price(100.00, 123.00, 'USD');
        $result = $A->addGross(123.00);

        $this->assertEquals(246.00, $result->getGross());
        $this->assertEquals(200.00, $result->getNett());
        $this->assertEquals(23, $result->getTaxRate());
        $this->assertEquals('USD', $result->getCurrencySymbol());

    }

    public function testBuildByNettUsingNegativeTax()
    {
        $this->expectExceptionMessage('Tax percent must positive');
        Price::buildByNett(100.00, -2);
    }

    public function testBuildByNettUsingIntegerTax()
    {
        $price = Price::buildByNett(100.00, 23, 'GBP');
        $this->assertEquals(123.00, $price->getGross());
    }

    public function testBuildByNettUsingIntegerLikeTax()
    {
        $price = Price::buildByNett(100.00, 23.00, 'PLN');
        $this->assertEquals(123.00, $price->getGross());
    }

    public function testBuildByNettUsingIntegerLikStringTax()
    {
        $price = Price::buildByNett(100.00, "23.00", 'EUR');
        $this->assertEquals(123.00, $price->getGross());
    }

    public function testBuildByGross()
    {
        $price = Price::buildByGross(123.00, 23, 'USD');
        $this->assertEquals(100.00, $price->getNett());
    }

    public function testMultiply()
    {
        $price = new Price(120.00, 150.00, 'PLN');
        $result = $price->multiply(5);

        $this->assertEquals(600.00, $result->getNett());
        $this->assertEquals(750.00, $result->getGross());
        $this->assertEquals('PLN', $result->getCurrencySymbol());
    }

    public function testMultiplyWithSpecificPrice()
    {
        $price = new Price(1.36, 1.67, 'PLN', 23);
        $result = $price->multiply(1.41);

        $result = clone $result;

        $this->assertEquals(1.92, $result->getNett());
        $this->assertEquals(2.35, $result->getGross());
        $this->assertEquals('PLN', $result->getCurrencySymbol());
        $this->assertEquals(23, $result->getTaxRate());
    }

    public function testDivide()
    {
        $price = Price::buildByGross(233.29, 23, 'PLN');
        $price = $price->divide(3);
        $this->assertEquals(63.22, $price->getNett());
        $this->assertEquals(77.76, $price->getGross());
        $this->assertEquals(23, $price->getTaxRate());
    }

    public function testDivideWithSpecificPrice()
    {
        $price = new Price(0.67, 0.82, 'PLN', 23);
        $price = $price->divide(1);

        $this->assertEquals(0.67, $price->getNett());
        $this->assertEquals(0.82, $price->getGross());
        $this->assertEquals(23, $price->getTaxRate());
    }

    public function testAddDifferentCurrencies()
    {
        $this->expectExceptionMessage('Can not operate on different currencies ("USD" and "GBP")');
        $usd = Price::buildByGross(100.00, 8, 'USD');
        $eur = Price::buildByGross(100.00, 8, 'GBP');
        $usd->add($eur);
    }

    // comment because Tax::validate() is commented
//    public function testInvalidTaxRate()
//    {
//        $this->setExpectedException("\\LogicException");
//        $price = new Price(100.00, 120.00, "USD", 10);
//    }

    public function testFluentInterface()
    {
    	$price = Price::buildByGross(100, 23, "PLN");
    	$newPrice = $price->add($price)->multiply(2)->divide(3);

    	$this->assertEqualsWithDelta((100+100)*2/3, $newPrice->getGross(),  0.01);
    }

    public function testCreateEmptyPriceWithNoCurrency()
    {
        $this->expectException(LogicException::class);
    	$price = Price::buildEmpty();
    	$price->getCurrency();
    }

    public function testAddEmptyToNonEmpty()
    {
    	$A = Price::buildEmpty();
    	$B = Price::buildByGross(10.50, 19, 'EUR');

    	$result1 = $A->add($B);
    	$result2 = $B->add($A);

    	$this->assertEquals($result1, $result2);
    	$this->assertEquals(19, $result1->getTaxRate());
	    $this->assertEquals('EUR', $result1->getCurrencySymbol());
	    $this->assertEquals(19, $result2->getTaxRate());
	    $this->assertEquals('EUR', $result2->getCurrencySymbol());
    }


    public function testAddingEmpties()
    {
        $list = [
            new Price(0, 0, 'PLN'),
            new Price(0, 0, 'PLN')
        ];

        $total = Price::buildEmpty();

        foreach ($list as $priceToAdd) {
            $total = $total->add($priceToAdd);
        }

        $this->assertEquals(0, $total->getGross());
        $this->assertEquals('PLN', $total->getCurrency());
    }

    public function testMultiplyByZero()
    {
        $price = Price::buildByNett(10, 23, 'PLN');

        $results = $price->multiply(0);

        $this->assertEquals(0, $results->getNett());
        $this->assertEquals(0, $results->getGross());
        $this->assertEquals(23, $results->getTaxRate());
    }

    public function testMultipleByNegativeValue()
    {
        $this->expectExceptionMessage('Multiply param must greater than 0');
        $price = Price::buildByNett(10, 23, 'PLN');
        $price->multiply(-5);
    }

    public function testToString(): void
    {
        $price = Price::buildByNett(10, 23, 'PLN');
        $this->assertEquals('12.30 PLN', (string)$price);
    }

    public function isLowerDataProvider(): array
    {
        return [
            [
                Price::buildByNett(10, 23, 'PLN'),
                Price::buildByGross(10, 23, 'PLN'),
                false,
            ],
            [
                Price::buildByGross(10, 23, 'PLN'),
                Price::buildByGross(10, 23, 'PLN'),
                true,
            ],
            [
                Price::buildByGross(10, 23, 'PLN'),
                Price::buildByNett(10, 23, 'PLN'),
                false,
            ],
        ];
    }

    /**
     * @dataProvider isLowerDataProvider
     */
    public function testIsLower(Price $left, Price $right, bool $isLower): void
    {
        $this->assertEquals($isLower, $left->isEqual($right));
    }
}
