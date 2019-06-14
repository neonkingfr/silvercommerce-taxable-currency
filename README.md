# TaxableCurrency

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