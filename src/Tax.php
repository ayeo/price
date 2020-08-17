<?php

namespace Ayeo\Price;

class Tax
{
    private int $value;

    public function __construct(int $tax)
    {
        if ($tax < 0) {
            throw new \LogicException('Tax percent must positive');
        }

        $this->value = $tax;
    }

    static public function build(float $nett, float $gross): Tax
    {
        //todo validate?
        if ($nett > 0) {
            $taxValue =  (int) round($gross / $nett * 100 - 100, 0);
        } else {
            $taxValue = 0;
        }

        return new Tax($taxValue);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Calculate gross value based on given nett
     */
    public function calculateGross(float $nett): float
    {
        return $nett * ($this->getValue() + 100) / 100;
    }

    /**
     * Calculate nett value based on given grossfloat
     */
    public function calculateNett(float $gross): float
    {
        return $gross * 100 / ($this->getValue() + 100);
    }

    public function validate(float $nett, float $gross): void
    {
        //can not throw exception couse dp catch it and not working
//        if (round($gross, 2) !== round($nett * (1 + $this->getValue()/ 100), 2)) {
//            throw new \LogicException("Invalid tax rate");
//        }
    }
}
