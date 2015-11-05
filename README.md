[![Build Status](http://img.shields.io/travis/ayeo/price.svg?style=flat-square)](https://travis-ci.org/ayeo/price)
[![Scrutinizer Code Quality](http://img.shields.io/scrutinizer/g/ayeo/price.svg?style=flat-square)](https://scrutinizer-ci.com/g/ayeo/price/build-status/master)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](license.md)
[![Packagist Version](https://img.shields.io/packagist/v/ayeo/price.svg?style=flat-square)](https://packagist.org/packages/ayeo/price)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/ayeo/price/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/ayeo/price/?branch=master)

# Price

Simple DDD price model. The goal is to make model usage as easy as possible. Creating object is 
easy and dont require any additional objects. Object acts as Value Object - is immutable and 
self-validating. It is designed to be side effect free.

API
===

Building
--------

```php
$price = new Price(float $nett, float $gross, 'GBP')
$price = Price::buildByNett(float $nett, integer $tax, 'USD') - returns Price
$price = Price::buildByGross(float $gross, integer $tax, 'EUR') - returns Price
```

Operations
----------

```php
$priceA->add(Price $priceB) - returns Price
$priceA->subtract(Price $priceB) - returns Price
$priceA->multiply(integer $times) - returns Price

$priceA->addGross(float $value) - returns Price
$priceA->subtractGross(float $value) - returns Price
```

Immutable
=========

Operations creates new instances

```php
$A = new Price(100.00, 120.00, 'USD');
$B = new Price(10.00, 12.00, 'USD');

$sum = $A->add($B);
$sum->getGross(); //returns 132.00
$A->getGross(); //returns 120.00
$B->getGross(); //returns 12.00
```

Comparing
---------

```php
$priceA->isEqual(Price $priceB) - returns bool
$priceA->isLower(Price $priceB) - returns bool
$priceA->isGreater(Price $priceB) - returns bool
```

Constraints
===========

- Nett and gross must be positive
- Gross must not be lower than nett
- Tax must be integer
- Currency symbol is optional but if appears must follow iso 4217 (3 uppercase chars)

Todo
====

There exists currencies with different precision than 2. The map must be developed.
https://en.wikipedia.org/wiki/ISO_4217#Active_codes

Contributing
============

Feel free to PR, tests must pass. 


