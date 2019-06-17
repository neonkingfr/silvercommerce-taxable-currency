<?php

namespace SilverCommerce\TaxableCurrency\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestProduct extends DataObject implements TestOnly
{
    private static $table_name = "TestProduct";

    private static $db = [
        "Title" => "Varchar",
        "StockID" => "Varchar",
        "Price" => "TaxableCurrency",
        "StockLevel" => "Int"
    ];
}
