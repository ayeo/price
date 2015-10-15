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


