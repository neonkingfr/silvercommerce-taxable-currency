<?php

namespace SilverCommerce\TaxableCurrency;

use NumberFormatter;
use SilverStripe\i18n\i18n;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
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
     * Should this field automatically show the price including Tax
     * for the current field?
     *
     * @var boolean|null
     */
    protected $show_price_with_tax;

    /**
     * Should a string (eg "Includes VAT") be added to the end of the price
     * when rendered?
     *
     * @var boolean|null
     */
    protected $show_tax_string;

    /**
     * Default behaviour for price with tax (if current instance not set)
     *
     * @var boolean
     */
    private static $default_price_with_tax = false;

    /**
     * Default behaviour for adding the tax string to the rendered currency.
     *
     * @var boolean
     */
    private static $default_tax_string = false;

    /**
     * @param array
     */
    private static $composite_db = [
        'Amount' => 'Decimal(19,4)',
        'TaxCategoryID' => 'Int',
        'TaxRateID' => 'Int'
    ];

    /**
     * @param array
     */
    private static $casting = [
        'CurrencySymbol' => 'Varchar(1)',
        'Currency' => 'Varchar(3)',
        'TaxAmount' => 'Decimal',
        'TaxString' => 'Varchar',
        'TaxPercentage' => 'Decimal',
        'PriceAndTax' => 'Decimal',
        'ShowPriceWithTax' => 'Boolean',
        'ShowTaxString' => 'Boolean'
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
     * Get the percentage tax rate assotiated with this field
     *
     * @return float
     */
    public function getTaxPercentage()
    {
        return $this->getTaxRate()->Rate;
    }

    /**
     * Get should this field automatically show the price including TAX?
     *
     * @return boolean
     */
    public function getShowPriceWithTax()
    {
        if (!empty($this->show_price_with_tax)) {
            return $this->show_price_with_tax;
        }

        return $this->config()->get('default_price_with_tax');
    }

    /**
     * Set should this field automatically show the price including TAX?
     *
     * @param boolean $show Should this field render the price with TAX?
     *
     * @return self
     */
    public function setShowPriceWithTax($show)
    {
        $this->show_price_with_tax = $show;
        return $this;
    }

    /**
     * Get is this field should add a "Tax String" (EG Includes VAT) to the rendered
     * currency?
     *
     * @return boolean|null
     */
    public function getShowTaxString()
    {
        if (!empty($this->show_tax_string)) {
            return $this->show_tax_string;
        }

        return $this->config()->get('default_tax_string');
    }

    /**
     * Set if we should include a Tax String to the end of the rendered price?
     *
     * @param boolean|null $show Add string when rendered?
     *
     * @return self
     */
    public function setShowTaxString($show)
    {
        $this->show_tax_string = $show;
        return $this;
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
     * Get ISO 4217 currency code from curent locale
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
     * @param bool|null $include_tax Should this include tax or not?
     *
     * @return string
     */
    public function getTaxString(bool $include_tax = null)
    {
        $string = "";
        $rate = $this->getTaxRate();

        if (empty($include_tax)) {
            $include_tax = $this->ShowPriceWithTax;
        }

        if ($rate->exists() && $include_tax) {
            $string = _t(
                self::class . ".TaxIncludes",
                "inc. {title}",
                ["title" => $rate->Title]
            );
        } elseif ($rate->exists() && !$include_tax) {
            $string = _t(
                self::class . ".TaxExcludes",
                "ex. {title}",
                ["title" => $rate->Title]
            );
        }

        $this->extend("updateTaxString", $string);

        return $string;
    }

    /**
     * Return a formatted price (based on locale)
     *
     * @param bool $include_tax Should the formatted price include tax?
     *
     * @return string
     */
    public function getFormattedPrice(bool $include_tax = false)
    {
        $currency = $this->Currency;
        $formatter = $this->getFormatter();

        if ($include_tax) {
            $amount = $this->PriceAndTax;
        } else {
            $amount = $this->Amount;
        }

        // Without currency, format as basic localised number
        if (!$currency) {
            return $formatter->format($amount);
        }

        return $formatter->formatCurrency($amount, $currency);
    }

    /**
     * Get nicely formatted currency (based on current locale)
     *
     * @return string
     */
    public function Nice()
    {
        return $this->renderWith(__CLASS__ . "_Nice");
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
