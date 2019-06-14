<?php

namespace SilverCommerce\TaxableCurrency;

use NumberFormatter;
use SilverStripe\i18n\i18n;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverCommerce\TaxAdmin\Model\TaxRate;
use SilverStripe\ORM\FieldType\DBComposite;
use SilverCommerce\TaxAdmin\Model\TaxCategory;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;

/**
 * Custom data type that allows setting of a tax category as well as a price
 * and also performs tax based calculations.
 * 
 */
class DBTaxableCurrency extends DBComposite
{
    /**
     * @var string $locale
     */
    protected $locale = null;

    /**
     * @param array
     */
    private static $composite_db = array(
        'Amount' => 'Decimal(19,4)',
        'TaxCategoryID' => 'Int',
        'TaxRateID' => 'Int'
    );

    /**
     * @param array
     */
    private static $casting = [
        'CurrencySymbol' => 'Varchar(1)',
        'Currency' => 'Varchar(3)',
        'TaxAmount' => 'Decimal',
        'PriceAndTax' => 'Decimal',
        'IncludesTax' => 'Boolean'
    ];

    /**
     * @return boolean
     */
    public function exists()
    {
        return is_numeric($this->getField('Amount'));
    }

    /**
     * Find a tax category based on the selected ID
     * 
     * @return \SilverCommerce\TaxAdmin\Model\TaxCategory
     */
    public function getTaxCategory()
    {
        $tax = TaxCategory::get()
            ->byID($this->dbObject("TaxCategoryID")->getValue());

        if (empty($tax)) {
            $tax = TaxCategory::create();
            $tax->ID = -1;
        }

        return $tax;
    }

    /**
     * Find a tax rate based on the selected ID, or revert to using the valid tax
     * from the current category
     * 
     * @return \SilverCommerce\TaxAdmin\Model\TaxRate
     */
    public function getTaxRate()
    {
        $tax = TaxRate::get()
            ->byID($this->dbObject("TaxRateID")->getValue());

        // If no tax explicity set, try to get from category
        if (empty($tax)) {
            $category = TaxCategory::get()
                ->byID($this->dbObject("TaxCategoryID")->getValue());

            $tax = (!empty($category)) ? $category->ValidTax() : null ;
        }

        if (empty($tax)) {
            $tax = TaxRate::create();
            $tax->ID = -1;
        }

        return $tax;
    }

    /**
     * Set the local for this instance
     *
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Either get the selected local or the default for the site
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale ?: i18n::get_locale();
    }

    /**
     * Get currency formatter
     *
     * @return NumberFormatter
     */
    public function getFormatter()
    {
        return NumberFormatter::create(
            $this->getLocale(),
            NumberFormatter::CURRENCY
        );
    }

    /**
     * Method that allows us to define in templates if we should show
     * price including tax, or excluding tax
     * 
     * @return boolean
     */
    public function getIncludesTax()
    {
        $config = SiteConfig::current_site_config();
        return $config->ShowPriceAndTax;
    }

    /**
     * Get a currency symbol from the current site local
     *
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this
            ->getFormatter()
            ->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * Get ISO 4217 currecny code from curent locale
     * 
     * @return string
     */
    public function getCurrency()
    {
        return $this
            ->getFormatter()
            ->getTextAttribute(NumberFormatter::CURRENCY_CODE);
    }

    /**
     * Get a final tax amount for this product. You can extend this
     * method using "UpdateTax" allowing third party modules to alter
     * tax amounts dynamically.
     * 
     * @return float
     */
    public function getTaxAmount()
    {
        if (!$this->exists()) {
            return 0;
        }

        // Round using default rounding defined on MathsHelper
        $tax = MathsHelper::round(
            ($this->getField('Amount') / 100) * $this->getTaxRate()->Rate,
            4
        );
        $this->extend("updateTaxAmount", $tax);

        return $tax;
    }

    /**
     * Get the final price of this product, including tax (if any)
     *
     * @return Float
     */
    public function getPriceAndTax()
    {
        $price = $this->Amount + $this->TaxAmount;
        $this->extend("updatePriceAndTax", $price);

        return $price;
    }

    /**
     * Generate a string to go with the the product price. We can
     * overwrite the wording of this by using Silverstripes language
     * files
     *
     * @return String
     */
    public function getTaxString()
    {
        $string = "";
        $rate = $this->getTaxRate();
        $config = SiteConfig::current_site_config();

        if ($config->ShowPriceTaxString) {
            if ($rate && $this->IncludesTax) {
                $string = _t(
                    self::class . ".TaxIncludes",
                    "inc. {title}",
                    ["title" => $rate->Title]
                );
            } elseif ($rate && !$this->IncludesTax) {
                $string = _t(
                    self::class . ".TaxExcludes",
                    "ex. {title}",
                    ["title" => $rate->Title]
                );
            }
        }

        $this->extend("updateTaxString", $string);

        return $string;
    }

    /**
     * Get nicely formatted currency (based on current locale)
     *
     * @return string
     */
    public function Nice()
    {
        if (!$this->exists()) {
            return null;
        }

        if ($this->IncludesTax) {
            $amount = $this->PriceAndTax;
        } else {
            $amount = $this->getField('Amount');
        }

        $currency = $this->Currency;

        // Without currency, format as basic localised number
        $formatter = $this->getFormatter();
        if (!$currency) {
            return $formatter->format($amount);
        }

        // Localise currency
        return $formatter->formatCurrency($amount, $currency);
    }

    /**
     * Returns a group of fields
     *
     * @param  string $title  Optional. Localized title of the generated instance
     * @param  array  $params
     * @return FormField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        return FieldGroup::create(
            $this->dbObject("Amount")->scaffoldFormField("", $params),
            DropdownField::create(
                $this->dbObject("TaxCategoryID")->getName(),
                "",
                TaxCategory::get()
            )->setEmptyString(
                _t(self::class . '.SelectTaxCategory', 'Select a Tax Category')
            ),
            ReadonlyField::create("PriceOr", "")
                ->addExtraClass("text-center")
                ->setValue(_t(self::class . '.OR', ' OR ')),
            DropdownField::create(
                $this->dbObject("TaxRateID")->getName(),
                "",
                TaxRate::get()
            )->setEmptyString(
                _t(self::class . '.SelectTaxRate', 'Select a Tax Rate')
            )
        )->setName($this->Name)
        ->setTitle($this->Name);
    }
}