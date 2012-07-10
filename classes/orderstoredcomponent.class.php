<?php
/**
 * COrderStoredComponents are components that are commonly recurring in COrders. They provide an easy way for
 * users to fill out and order with existing components. The class is designed to handle suppliers from all countries,
 * and provide options to store both the vendor price, currency, exchange rate and local price to keep track of all
 * information related to the component.
 */
class COrderStoredComponent
{

    // Members internally and that can be accessed by other classes
    public $id;
    public $description;
    public $catalogNumber;
    public $wetMaterial;
    public $brand;
    public $supplier;
    public $discount;
    public $vendorPrice;
    public $vendorCurrency;
    public $exchangeRate;
    public $localPrice;
    public $quoteDate;
    public $notes;


    protected function __construct(array $params) {

        // Initialize object using the information in the parameters
        foreach($params as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public static function createListFromDb($offset=0, $num=10, array $filter=array()) {

        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_default_components");
        $query->addQuery("*");
        $query->setLimit($num, $offset);
        $results = $query->loadList();

        // Transform results to something that is accepted by the constructor
        $components = array();
        foreach($results as $result) {
            self::fromDbArray($result); // Convert to something constructor can handle
            $components[] = new COrderStoredComponent($result);
        }
        unset($result);
        return $components;
    }

    protected static function fromDbArray(&$row) {

        // Iterate through all rows and change row names
        $colNames = array(
            "component_id"      => "id",
            "catalog_number"    => "catalogNumber",
            "wet_material"      => "wetMaterial",
            "vendor_price"      => "vendorPrice",
            "vendor_currency"   => "vendorCurrency",
            "exchange_rate"     => "exchangeRate",
            "local_price"       => "localPrice",
            "quote_date"        => "quoteDate"
        );
        foreach($colNames as $old => $new) { // Change entries that need changing
            $row[$new] = $row[$old];
            unset($row[$old]);
        }

    }
}
