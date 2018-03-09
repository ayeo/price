<?php
namespace Ayeo\Price\Test;

use Ayeo\Price\Tax;

class TaxTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider grossCalculationsProvider
     */
    public function testGrossCalculations($tax, $value, $expectedValue)
    {
        $tax = new Tax($tax);
        $this->assertEquals($expectedValue, $tax->calculateGross($value));
    }

    public function grossCalculationsProvider()
    {
        return [
            [7, 3456, 3697.92],
            [0.07 * 100, 3456, 3697.92]
        ];
    }
}