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
		$price = new Price($nett, $gross);
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

		$A = new Price($nettA, $grossA);
		$B = new Price($nettB, $grossB);

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
     * @expectedExceptionMessage    Can not do operate on different currencies ("USD" and "GBP")
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
        new Price(100.00, 90.00);
    }

    public function testNettSameAsGross()
    {
        $price = new Price(100.00, 100.00);
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
        $newPrice = $price->subtractGross(150.00);

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
        $A = new Price($nettA, $grossA);
        $B = new Price($nettB, $grossB);

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
        new Price(-10.00, 20);
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Gross must be positive
     */
    public function testNegativeNettAndGross()
    {
        new Price(10.00, -15.00);
    }

    public function  testSubtractGross()
    {
        $price = new Price(13.34, 15.53);
        $result = $price->subtractGross(10.00);

        $this->assertEquals(5.53, $result->getGross());
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Value must be greater than zero
     */
    public function testSubtractNegativeGross()
    {
        $price = new Price(13.34, 15.53);
        $price->subtractGross(-10.00);
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Value must be greater than zero
     */
    public function testSubtractZeroGross()
    {
        $price = new Price(13.34, 15.53);
        $price->subtractGross(0.00);
    }

    /**
     * @expectedException           LogicException
     * @expectedExceptionMessage    Value must be numeric
     */
    public function testSubtractString()
    {
        $price = new Price(13.34, 15.53);
        $price->subtractGross("number");
    }

    public function testAddGross()
    {
        $A = new Price(13.24, 20.99, 'USD');
        $result = $A->addGross(10.01);

        $this->assertEquals(31.00, $result->getGross());
        $this->assertEquals('USD', $result->getCurrencySymbol());
        $this->assertEquals(20.99, $A->getGross());

    }

}