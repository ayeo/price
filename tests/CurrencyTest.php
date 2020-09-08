<?php

namespace Ayeo\Price\Test;

use Ayeo\Price\Currency;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function validCurrencySymbol(): array
    {
        return [
            ["", false, null], // legacy isklep hacks
            ['PL', false, null],
            ['PLN', true, 'PLN'],
            ['pln', false, null],
        ];
    }

    /**
     * @dataProvider validCurrencySymbol
     */
    public function testCurrencySymbol(string $symbol, bool $valid, ?string $expectedSymbol): void
    {
        if (!$valid) {
            $this->expectException(\LogicException::class);
        }

        $currency = new Currency($symbol);
        $this->assertEquals($expectedSymbol, (string)$currency);
    }
}
