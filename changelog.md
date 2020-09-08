This is draft, will polish it later

2.0.4
- Add currency, calculator and price unit tests
- Remove price unused if statements

2.0.3
- Fix dividing in StandardCalculator
- Fix math operations on price without currency symbol

2.0.2
- Change travis CI to github CI

2.0.1
- Restore substractNett/substractGross calculation logic
- Add checks for hasTaxRate during test substractNett/substractGross

2.0.0
- Add calculators for math operations
- Add money decorators

1.1.0
- Updated code to php 7.4 standards

1.0.16
- subtractNett() method added

1.0.15
- Fix building empty price, now returns one class instance per currency

1.0.3
- New tax policy

1.0.2 
- getTaxPrice() method added

1.0.1
- Currency symbol is reguired
