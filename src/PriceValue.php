<?php

namespace Ayeo\Price;

class PriceValue
{
    private Money $nett;
    private Money $gross;
    private Tax $tax;
    private bool $mixedTax;
    private ?Currency $currency;

    public function __construct(Money $nett, Money $gross, Tax $tax, bool $mixedTax, ?Currency $currency)
    {
        $this->nett = $nett;
        $this->gross = $gross;
        $this->tax = $tax;
        $this->mixedTax = $mixedTax;
        $this->currency = $currency;
    }

    public function getNett(): Money
    {
        return $this->nett;
    }

    public function getGross(): Money
    {
        return $this->gross;
    }

    public function getTax(): Tax
    {
        return $this->tax;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function hasCurrency(): bool
    {
        return $this->currency !== null;
    }

    public function isMixedTax(): bool
    {
        return $this->mixedTax;
    }
}
