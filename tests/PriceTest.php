<?php
namespace Ayeo\Price\Test;

use Ayeo\Price\Price;
use LogicException;

class PriceTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider testCreatingDataProvider
     */
	public function testCreating($nett, $gross, $tax)
	{
		$price = new Price($nett, $gross, 'USD');
        $this->assertEquals($tax, $price->getTax());
	}

    public function testCreatingDataProvider()
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
            [109.7561, 135.0050, 23],
            [109.7561, 135.0060, 23],
            [109.7561, 135.0070, 23],
            [109.7561, 135.0100, 23],
            [109.7561, 135.5000, 23],
        ];
    }

	/**
	 * @dataProvider testAddingDataProvider
	 */
	public function testAdding($grossA, $grossB, $expectedGross)
	{
        $tax = 23;
		$nettA = $grossA / (100 + $tax) * 100;
		$nettB = $grossB / (100 + $tax) * 100;

		$A = new Price($nettA, $grossA, 'USD');
		$B = new Price($nettB, $grossB, 'USD');

		$this->assertEquals($expectedGross, $A->add($B)->getGross());
		$this->assertEquals($expectedGross, $B->add($A)->getGross());

		$this->assertEquals($tax, $B->add($A)->getTax());
		$this->assertEquals($tax, $A->add($B)->getTax());

		$this->assertEquals($nettA, $A->getNett(), '', 0.01);
		$this->assertEquals($nettB, $B->getNett(), '', 0.01);
	}

	public function testAddingDataProvider()
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

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Can not operate on different currencies ("USD" and "GBP")
     */
    public function testAddingDifferentCurrencies()
    {
        $A = new Price(100, 130, 'USD');
        $B = new Price(300, 330, 'GBP');
        $A->add($B);
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Nett must not be greater than gross
     */
    public function testNettGreaterThanGross()
    {
        new Price(100.00, 90.00, 'USD');
    }

    public function testNettSameAsGross()
    {
        $price = new Price(100.00, 100.00, 'USD');
        $this->assertEquals(0, $price->getTax());
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Invalid currency symbol: "PLNG"
     */
    public function testInvalidCurrencySymbol()
    {
        new Price(100, 200, 'PLNG');
    }

    public function testSubtractGrossBiggerThanPrice()
    {
        $price = new Price(120, 140, 'PLN');
        $newPrice = $price->subtractGross(150.00, 'PLN');

        $this->assertEquals(0.00, $newPrice->getGross());
        $this->assertEquals(0.00, $newPrice->getNett());
        $this->assertEquals(0, $newPrice->getTax());
    }

    public function testSubstractingGraterPrice()
    {
        $smaller = new Price(0.59, 0.72, 'EUR');
        $bigger = new Price(1.12, 1.32, 'EUR');

        $result = $smaller->subtract($bigger);
        $this->assertEquals(0.00, $result->getGross());
        $this->assertEquals(0.00, $result->getNett());
        $this->assertEquals(0.00, $result->getTax());

    }

    /**
     * @dataProvider testIsEqualDataProvider
     */
    public function testIsEqual($nettA, $grossA, $nettB, $grossB, $expectIsEqual)
    {
        $A = new Price($nettA, $grossA, 'USD');
        $B = new Price($nettB, $grossB, 'USD');

        if ($expectIsEqual)
        {
            $this->assertTrue($A->isEqual($B));
            $this->assertTrue($B->isEqual($A));
            $this->assertEquals($A->getTax(), $B->getTax());
        }
        else
        {
            $this->assertFalse($A->isEqual($B));
            $this->assertFalse($B->isEqual($A));
        }
    }

    public function testIsEqualDataProvider()
    {
        return [
            [100.00,    123.00,     100.00,     123.00,     true],
            [100.00,    123.00,     100.01,     123.02,     false],
            [100.00,    123.00,     100.0014,   123.0021,   true], //fails with precision 4
        ];
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Nett must be positive
     */
    public function testNegativeNett()
    {
        new Price(-10.00, 20, 'USD');
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Gross must be positive
     */
    public function testNegativeNettAndGross()
    {
        new Price(10.00, -15.00, 'USD');
    }

    public function  testSubtractGross()
    {
        $price = new Price(13.34, 15.53, 'USD');
        $result = $price->subtractGross(10.00, 'USD');

        $this->assertEquals(5.53, $result->getGross());
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Value must be greater than zero
     */
    public function testSubtractNegativeGross()
    {
        $price = new Price(13.34, 15.53, 'USD');
        $price->subtractGross(-10.00, 'USD');
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Value must be greater than zero
     */
    public function testSubtractZeroGross()
    {
        $price = new Price(13.34, 15.53, 'USD');
        $price->subtractGross(0.00, 'USD');
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Value must be numeric
     */
    public function testSubtractString()
    {
        $price = new Price(13.34, 15.53, 'USD');
        $price->subtractGross("number", 'USD');
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
        $this->assertEquals(23, $result->getTax());
        $this->assertEquals('USD', $result->getCurrencySymbol());

    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Tax percent must positive
     */
    public function testBuildByNettUsingNegativeTax()
    {
        Price::buildByNett(100.00, -2);
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Tax percent must be integer
     */
    public function testBuildByNettUsingNonIntegerLikeTax()
    {
        Price::buildByNett(100.00, 2.02);
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Tax percent must be integer
     */
    public function testBuildByNettUsingNonIntegerLikeStringTax()
    {
        Price::buildByNett(100.00, "2.02", 'USD');
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

    public function testDivide()
    {
        $price = Price::buildByGross(233.29, 23, 'PLN');
        $price = $price->divide(3);
        $this->assertEquals(77.76, $price->getGross());
    }

    /**
     * @expectedException           \LogicException
     * @expectedExceptionMessage    Can not operate on different currencies ("USD" and "GBP")
     */
    public function testAddDifferentCurrencies()
    {
        $usd = Price::buildByGross(100.00, 8, 'USD');
        $eur = Price::buildByGross(100.00, 8, 'GBP');
        $usd->add($eur);
    }

    //todo: test float tax
}