<?php

namespace SilverCommerce\TaxableCurrency\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverCommerce\TaxAdmin\Model\TaxRate;
use SilverCommerce\TaxAdmin\Model\TaxCategory;
use SilverCommerce\TaxableCurrency\Tests\Model\TestProduct;
use SilverStripe\i18n\i18n;

/**
 * Test functionality of postage extension
 *
 */
class TaxableCurrencyTest extends SapphireTest
{

    protected static $fixture_file = 'TaxableCurrency.yml';
    
    /**
     * Setup test only objects
     *
     * @var array
     */
    protected static $extra_dataobjects = [
        TestProduct::class
    ];

    public function testExists()
    {
        $new = TestProduct::create();
        $noprice = $this->objFromFixture(TestProduct::class, 'noprice');
        $notax = $this->objFromFixture(TestProduct::class, 'notax');
        $vat = $this->objFromFixture(TestProduct::class, 'vat');

        $this->assertFalse($new->dbObject('Price')->exists());
        $this->assertTrue($noprice->dbObject('Price')->exists());
        $this->assertTrue($notax->dbObject('Price')->exists());
        $this->assertTrue($vat->dbObject('Price')->exists());
    }

    public function testGetTaxCategory()
    {
        $noprice = $this->objFromFixture(TestProduct::class, 'noprice');
        $notax = $this->objFromFixture(TestProduct::class, 'notax');
        $cat = $this->objFromFixture(TestProduct::class, 'category');
        $vat = $this->objFromFixture(TestProduct::class, 'vat');

        $category = $noprice->dbObject('Price')->getTaxCategory();
        $this->assertFalse($category->exists());
        $this->assertInstanceOf(TaxCategory::class, $category);

        $category = $notax->dbObject('Price')->getTaxCategory();
        $this->assertFalse($category->exists());
        $this->assertInstanceOf(TaxCategory::class, $category);

        $category = $vat->dbObject('Price')->getTaxCategory();
        $this->assertFalse($category->exists());
        $this->assertInstanceOf(TaxCategory::class, $category);

        $category = $cat->dbObject('Price')->getTaxCategory();
        $this->assertTrue($category->exists());
        $this->assertInstanceOf(TaxCategory::class, $category);
        $this->assertEquals("UK", $category->Title);
    }

    public function testGetTaxRate()
    {
        $curr = i18n::get_locale();
        i18n::set_locale('en_GB');
    
        $noprice = $this->objFromFixture(TestProduct::class, 'noprice');
        $notax = $this->objFromFixture(TestProduct::class, 'notax');
        $category = $this->objFromFixture(TestProduct::class, 'category');
        $vat = $this->objFromFixture(TestProduct::class, 'vat');

        $rate = $noprice->dbObject('Price')->getTaxRate();
        $this->assertFalse($rate->exists());
        $this->assertInstanceOf(TaxRate::class, $rate);

        $rate = $notax->dbObject('Price')->getTaxRate();
        $this->assertFalse($rate->exists());
        $this->assertInstanceOf(TaxRate::class, $rate);

        $rate = $category->dbObject('Price')->getTaxRate();
        $this->assertTrue($rate->exists());
        $this->assertInstanceOf(TaxRate::class, $rate);
        $this->assertEquals(20, $rate->Rate);

        $rate = $vat->dbObject('Price')->getTaxRate();
        $this->assertTrue($rate->exists());
        $this->assertInstanceOf(TaxRate::class, $rate);
        $this->assertEquals(20, $rate->Rate);

        i18n::set_locale($curr);
    }

    public function testGetCurrency()
    {
        $curr = i18n::get_locale();
        $product = TestProduct::create();

        i18n::set_locale('en_GB');
        $this->assertEquals("GBP", $product->dbObject('Price')->getCurrency());

        i18n::set_locale('de_DE');
        $this->assertEquals("EUR", $product->dbObject('Price')->getCurrency());

        i18n::set_locale('de_US');
        $this->assertEquals("USD", $product->dbObject('Price')->getCurrency());

        i18n::set_locale($curr);
    }

    public function testGetCurrencySymbol()
    {
        $curr = i18n::get_locale();
        $product = TestProduct::create();

        i18n::set_locale('en_GB');
        $this->assertEquals("£", $product->dbObject('Price')->getCurrencySymbol());

        i18n::set_locale('de_DE');
        $this->assertEquals("€", $product->dbObject('Price')->getCurrencySymbol());

        i18n::set_locale('de_US');
        $this->assertEquals("$", $product->dbObject('Price')->getCurrencySymbol());

        i18n::set_locale($curr);
    }

    public function testGetTaxAmount()
    {
        $curr = i18n::get_locale();
        i18n::set_locale('en_GB');

        $noprice = $this->objFromFixture(TestProduct::class, 'noprice');
        $notax = $this->objFromFixture(TestProduct::class, 'notax');
        $category = $this->objFromFixture(TestProduct::class, 'category');
        $vat = $this->objFromFixture(TestProduct::class, 'vat');
        $vat_two = $this->objFromFixture(TestProduct::class, 'vattwo');

        $rate = $noprice->dbObject('Price')->getTaxAmount();
        $this->assertEquals(0, $rate);

        $rate = $notax->dbObject('Price')->getTaxAmount();
        $this->assertEquals(0, $rate);

        $rate = $category->dbObject('Price')->getTaxAmount();
        $this->assertEquals(0.9333, $rate);

        $rate = $vat->dbObject('Price')->getTaxAmount();
        $this->assertEquals(0.9333, $rate);

        $rate = $vat_two->dbObject('Price')->getTaxAmount();
        $this->assertEquals(8.3333, $rate);

        i18n::set_locale($curr);
    }

    public function testGetPriceAndTax()
    {
        $curr = i18n::get_locale();
        i18n::set_locale('en_GB');

        $noprice = $this->objFromFixture(TestProduct::class, 'noprice');
        $notax = $this->objFromFixture(TestProduct::class, 'notax');
        $category = $this->objFromFixture(TestProduct::class, 'category');
        $vat = $this->objFromFixture(TestProduct::class, 'vat');
        $vat_two = $this->objFromFixture(TestProduct::class, 'vattwo');

        $rate = $noprice->dbObject('Price')->getPriceAndTax();
        $this->assertEquals(0, $rate);

        $rate = $notax->dbObject('Price')->getPriceAndTax();
        $this->assertEquals(5.99, $rate);

        $rate = $category->dbObject('Price')->getPriceAndTax();
        $this->assertEquals(5.5999, $rate);

        $rate = $vat->dbObject('Price')->getPriceAndTax();
        $this->assertEquals(5.5999, $rate);

        $rate = $vat_two->dbObject('Price')->getPriceAndTax();
        $this->assertEquals(49.9999, $rate);

        i18n::set_locale($curr);
    }

    public function getTaxString()
    {
        $locale = i18n::get_locale();
        i18n::set_locale('en_GB');

        $product = $this->objFromFixture(TestProduct::class, 'vat');

        $curr = $product->dbObject('Price')->getShowPriceWithTax();

        $product->dbObject('Price')->setShowPriceWithTax(true);
        $this->assertEquals("inc. VAT", $product->dbObject('Price')->TaxString);

        $product->dbObject('Price')->setShowPriceWithTax(false);
        $this->assertEquals("ex. VAT", $product->dbObject('Price')->TaxString);
    
        $product->dbObject('Price')->setShowPriceWithTax($curr);
        i18n::set_locale($locale);
    }
}
