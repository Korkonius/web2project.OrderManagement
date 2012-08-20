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
    public $inStock;
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
            "quote_date"        => "quoteDate",
            "in_stock"          => "inStock"
        );
        foreach($colNames as $old => $new) { // Change entries that need changing
            $row[$new] = $row[$old];
            unset($row[$old]);
        }

    }

    public static function updateAllExchangeRates() {

        // Fetch JSON data from OpenExchangeRates.org if CURL is enabled
        if(!function_exists("curl_init")){
            throw new Exception("cURL extension must be enabled to use this feature...");
        }

        // Set up and fetch rates
        $url = "http://openexchangerates.org/latest.json";
        $curlSession = curl_init($url);
        $curlOpt = array(
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_USERAGENT       => $_SERVER['HTTP_USER_AGENT'],
            CURLOPT_FAILONERROR     => true
        );
        curl_setopt_array($curlSession, $curlOpt);
        $rawData = curl_exec($curlSession);
        curl_close($curlSession);

        if(!rawData) {
            echo("Request failed! Curl error: " . curl_error($curlSession));
        }

        $jsonResult = json_decode($rawData, true);
        $newRates = $jsonResult['rates'];
        $local = $newRates[COrder::createFromDatabase(1)->currency]; // TODO Fix this when the currency is available from some config

        // Fetch all currencies in database
        $query = new w2p_Database_Query();
        $query->addQuery("DISTINCT(`vendor_currency`) as curr");
        $query->addTable(COrder::_TBL_PREFIKS_ . "_default_components");
        $result = $query->loadList();

        foreach($result as $row) {
            $vendorCurr = $row['curr'];
            $vendor = $newRates[$vendorCurr];
            if($vendor != 0) {

                $newCurr = $local/$vendor;
                $query->clear();
                $query->addTable(COrder::_TBL_PREFIKS_ . "_default_components");
                $query->addUpdate("exchange_rate", $newCurr);
                $query->addWhere("vendor_currency = '$vendorCurr'");
                $query->exec();
            }
        }
    }
}
