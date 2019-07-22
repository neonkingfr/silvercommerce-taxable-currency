<?php

namespace SilverCommerce\TaxableCurrency;

use SilverStripe\ORM\DataExtension;

/**
 * Simple extension you can add to data objects that adds helper methods
 * for getting price, tax, etc.
 */
class TaxableExtension extends DataExtension
{
    private static $db = [
        'Price' => 'TaxableCurrency'
    ];

    private static $casting = [
        "NoTaxPrice"            => "Decimal(9,4)",
        "TaxAmount"             => "Decimal(9,4)",
        "TaxPercentage"         => "Decimal",
        "PriceAndTax"           => "Decimal(9,4)",
    ];

    /**
     * Filter the results returned by an extension
     *
     * @param mixed $results Possible results
     *
     * @return mixed
     */
    public function filterExtensionResults($results)
    {
        if (!empty($results) && is_array($results)) {
            $results = array_filter($results, function ($v) {
                return !is_null($v);
            });
            if (is_array($results) && count($results) > 0) {
                return $results[0];
            }
        }

        return;
    }

    /**
     * Shortcut to get the price of this product without tax
     * 
     * @return float
     */
    public function getNoTaxPrice()
    {
        $price = $this->getOwner()->dbObject('Price')->Amount;
        $result = $this->getOwner()->filterExtensionResults(
            $this->getOwner()->extend("updateNoTaxPrice", $price)
        );

        if (!empty($result)) {
            return $result;
        }

        return $price;
    }

    /**
     * Shortcut to get the amount of tax for this product
     *
     * @return float
     */
    public function getTaxAmount()
    {
        $amount = $this->getOwner()->dbObject('Price')->TaxAmount;
        $result = $this->getOwner()->filterExtensionResults(
            $this->getOwner()->extend("updateTaxAmount", $amount)
        );

        if (!empty($result)) {
            return $result;
        }

        return $amount;
    }

    /**
     * Shortcut to allow getting the percentage of tax currently applied
     *
     * @return float
     */
    public function getTaxPercentage()
    {
        $percent = $this->getOwner()->dbObject('Price')->TaxPercentage;
        $result = $this->getOwner()->filterExtensionResults(
            $this->getOwner()->extend("updateTaxPercentage", $percent)
        );

        if (!empty($result)) {
            return $result;
        }

        return $percent;
    }

    /**
     * Get the Tax Rate object applied to this product
     *
     * @return \SilverCommerce\TaxAdmin\Model\TaxRate
     */
    public function getTaxRate()
    {
        $rate = $this->getOwner()->dbObject('Price')->getTaxRate();
        $result = $this->getOwner()->filterExtensionResults(
            $this->getOwner()->extend("updateTaxRate", $rate)
        );

        if (!empty($result)) {
            return $result;
        }

        return $rate;
    }

    /**
     * Get the Tax Rate object applied to this product
     *
     * @return float
     */
    public function getPriceAndTax()
    {
        $price = $this->getOwner()->dbObject('Price')->getPriceAndTax();
        $result = $this->getOwner()->filterExtensionResults(
            $this->getOwner()->extend("updatePriceAndTax", $price)
        );

        if (!empty($result)) {
            return $result;
        }

        return $price;
    }
}