<?php
namespace Ayeo\Price\Test;

use Ayeo\Price\Tax;

class TaxTest extends \PHPUnit_Framework_TestCase
{
	public function testGrossCalculations()
	{
		$tax = new Tax(23);
		$this->assertEquals(123, $tax->calculateGross(100));
		$this->assertEquals(246, $tax->calculateGross(200));
	}
}