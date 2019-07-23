<?php

namespace SilverCommerce\TaxableCurrency\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverCommerce\TaxAdmin\Model\TaxRate;
use SilverCommerce\TaxableCurrency\Tests\Model\TestProduct;
use SilverStripe\i18n\i18n;

/**
 * Test functionality of postage extension
 *
 */
class TaxableExtensionTest extends SapphireTest
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

    public function testGetNoTaxPrice()
    {
        $noprice = $this->objFromFixture(TestProduct::class, 'noprice');
        $notax = $this->objFromFixture(TestProduct::class, 'notax');
        $category = $this->objFromFixture(TestProduct::class, 'category');
        $vat = $this->objFromFixture(TestProduct::class, 'vat');
        $vat_two = $this->objFromFixture(TestProduct::class, 'vattwo');
        $reduced = $this->objFromFixture(TestProduct::class, 'reduced');

        $price = $noprice->getNoTaxPrice();
        $this->assertEquals(0, $price);

        $price = $notax->getNoTaxPrice();
        $this->assertEquals(5.9900, $price);

        $price = $category->getNoTaxPrice();
        $this->assertEquals(4.6666, $price);

        $price = $vat->getNoTaxPrice();
        $this->assertEquals(4.6666, $price);

        $price = $vat_two->getNoTaxPrice();
        $this->assertEquals(41.6666, $price);

        $price = $reduced->getNoTaxPrice();
        $this->assertEquals(49.6200, $price);
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
        $reduced = $this->objFromFixture(TestProduct::class, 'reduced');

        $rate = $noprice->getTaxAmount();
        $this->assertEquals(0, $rate);

        $rate = $notax->getTaxAmount();
        $this->assertEquals(0, $rate);

        $rate = $category->getTaxAmount();
        $this->assertEquals(0.9333, $rate);

        $rate = $vat->getTaxAmount();
        $this->assertEquals(0.9333, $rate);

        $rate = $vat_two->getTaxAmount();
        $this->assertEquals(8.3333, $rate);

        $rate = $reduced->getTaxAmount();
        $this->assertEquals(2.481, $rate);

        i18n::set_locale($curr);
    }

    public function testGetTaxPercentage()
    {
        $curr = i18n::get_locale();
        i18n::set_locale('en_GB');

        $noprice = $this->objFromFixture(TestProduct::class, 'noprice');
        $notax = $this->objFromFixture(TestProduct::class, 'notax');
        $category = $this->objFromFixture(TestProduct::class, 'category');
        $vat = $this->objFromFixture(TestProduct::class, 'vat');
        $vat_two = $this->objFromFixture(TestProduct::class, 'vattwo');
        $reduced = $this->objFromFixture(TestProduct::class, 'reduced');

        $rate = $noprice->getTaxPercentage();
        $this->assertEquals(0, $rate);

        $rate = $notax->getTaxPercentage();
        $this->assertEquals(0, $rate);

        $rate = $category->getTaxPercentage();
        $this->assertEquals(20, $rate);

        $rate = $vat->getTaxPercentage();
        $this->assertEquals(20, $rate);

        $rate = $vat_two->getTaxPercentage();
        $this->assertEquals(20, $rate);

        $rate = $reduced->getTaxPercentage();
        $this->assertEquals(5, $rate);

        i18n::set_locale($curr);
    }

    public function testGetTaxRate()
    {
        $curr = i18n::get_locale();
        i18n::set_locale('en_GB');
    
        $noprice = $this->objFromFixture(TestProduct::class, 'noprice');
        $notax = $this->objFromFixture(TestProduct::class, 'notax');
        $category = $this->objFromFixture(TestProduct::class, 'category');
        $vat = $this->objFromFixture(TestProduct::class, 'vat');

        $rate = $noprice->getTaxRate();
        $this->assertFalse($rate->exists());
        $this->assertInstanceOf(TaxRate::class, $rate);

        $rate = $notax->getTaxRate();
        $this->assertFalse($rate->exists());
        $this->assertInstanceOf(TaxRate::class, $rate);

        $rate = $category->getTaxRate();
        $this->assertTrue($rate->exists());
        $this->assertInstanceOf(TaxRate::class, $rate);
        $this->assertEquals(20, $rate->Rate);

        $rate = $vat->getTaxRate();
        $this->assertTrue($rate->exists());
        $this->assertInstanceOf(TaxRate::class, $rate);
        $this->assertEquals(20, $rate->Rate);

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

        $rate = $noprice->getPriceAndTax();
        $this->assertEquals(0, $rate);

        $rate = $notax->getPriceAndTax();
        $this->assertEquals(5.99, $rate);

        $rate = $category->getPriceAndTax();
        $this->assertEquals(5.5999, $rate);

        $rate = $vat->getPriceAndTax();
        $this->assertEquals(5.5999, $rate);

        $rate = $vat_two->getPriceAndTax();
        $this->assertEquals(49.9999, $rate);

        i18n::set_locale($curr);
    }
}
