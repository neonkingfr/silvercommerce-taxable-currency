# Log of changes for Tax Admin module

## 1.0.0

* First initial release

## 1.1.0

* Add ability to map rates to geo zones
* Add ability to filter rates in a category by provided (or default) locale data

## 1.2.0

* Fixed adding TagRates to TagCategories
* Removed Locations dropdown from TaxRates on TaxCategories

## 1.2.1 

* Made TaxCategory subsite aware
* added support for global taxrates

## 1.2.2

* Add ability to round up or down when calculating tax (settable via config)

## 1.2.3

* PHPCBF

## 1.3.0

* Set `MathsHelper` to use standard rounding by default
* Ensure proper rounding of whole numbers
* Add unit tests for `MathsHelper`

## 1.3.1

* PHPCBF

## 1.3.2

* Tweak Unit tests for better PostgreSQL support