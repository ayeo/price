[![Build Status](http://img.shields.io/travis/ayeo/price.svg?style=flat-square)](https://travis-ci.org/ayeo/price)
[![Scrutinizer Code Quality](http://img.shields.io/scrutinizer/g/ayeo/price.svg?style=flat-square)](https://scrutinizer-ci.com/g/ayeo/price/build-status/master)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](license.md)
[![Packagist Version](https://img.shields.io/packagist/v/ayeo/price.svg?style=flat-square)](https://packagist.org/packages/ayeo/price)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/ayeo/price/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/ayeo/price/?branch=master)

# Price

Simple DDD price model

Usage
=====

Objects are immutable. Operations creates new instances
```php
$A = new Price(100.00, 120.00, 'USD');
$B = new Price(10.00, 12.00, 'USD');

$sum = $A->add($B);
```


