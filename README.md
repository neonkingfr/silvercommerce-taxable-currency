# TaxableCurrency

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/silvercommerce/taxable-currency/badges/quality-score.png?b=1.0)](https://scrutinizer-ci.com/g/silvercommerce/taxable-currency/?branch=1.0)
[![Build Status](https://travis-ci.org/silvercommerce/taxable-currency.svg?branch=1.0)](https://travis-ci.org/silvercommerce/taxable-currency)


Composite field that allows setting an amount and tax rate/category it will then
render the final value

## Instalation

Install via composer: `composer require silvercommerce/taxable-currency`

## Usage

`TaxableCurrency` is registered as a possible DB field, so to make use of this field
just add the following to your `Page`/`DataObject`:

```php
    private static $db = [
        'Price' => 'TaxableCurrency'
    ];
```

## Setting Up Tax

Tax rates are defined via the [Tax Admin module](https://github.com/silvercommerce/tax-admin),
you will need to define rates/categories before you can use this field correctly.