<?php
// TODO Implement proper ACL checks
/**
 * The COrderDelivery class provides functionality to represent deliveries for the COrder class. Primarily it handles
 * database communication and some utility methods to check composite data from different database tables.
 */
class COrderDelivery
{
    public $delivery_id;
    public $order_id;
    public $delivery_start_date;
    public $delivery_end_date;
    public $company;
    public $arrived;

    /**
     * Constructor is protected to ensure new instances of the class is generated through the static methods. This is
     * to ensure object integrity on creation.
     *
     * @param $delivery_id int
     * @param $order_id int
     * @param $delivery_start_date MySQL date
     * @param $delivery_end_date MySQL date
     * @param $company String
     * @param $arrived MySQL date
     */
    protected function __construct($delivery_id, $order_id, $delivery_start_date, $delivery_end_date, $company, $arrived) {

        // Update internal fields with values
        $this->delivery_id = $delivery_id;
        $this->order_id = $order_id;
        $this->delivery_end_date = $delivery_end_date;
        $this->delivery_start_date = $delivery_start_date;
        $this->company = $company;
        $this->arrived = $arrived;
    }

    /**
     * Deletes object data from database
     *
     * @return Handle
     */
    public function delete() {

        $query = new w2p_Database_Query();
        $query->setDelete(COrder::_TBL_PREFIKS_ . "_deliveries");
        $query->addWhere("delivery_id = $this->delivery_id");

        return $query->exec();
    }

    /**
     * Checks to see if object is overdue. Is overdue of now() > delivery_end_date
     *
     * @return bool
     */
    public function isOverdue() {

        // If the the delivery is recieved it cannot be overdue
        if($this->hasArrived()) {
            return false;
        }

        // Return end true if time now is greater than end date
        $endTime = new DateTime($this->delivery_end_date);
        $now = new DateTime('now');

        return ($endTime < $now);
    }

    /**
     * Simple test to see if the order is marked as arrived
     *
     * @return bool
     */
    public function hasArrived() {
        if(!empty($this->arrived)) {
            return true;
        }
    }

    /**
     * Sets the arrived date to now()
     *
     * @return Handle
     */
    public function justArrived() {
        return $this->setArrived(date('Y-m-d H:i:s'));
    }

    /**
     * Sets the arrived date to the time specified
     *
     * @param $time MySQL date
     * @return Handle
     */
    public function setArrived($time) {

        // Update record with arrived time
        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_deliveries", "del");
        $query->addUpdate('arrived', $time);
        $query->addWhere("delivery_id = " . $this->delivery_id);

        return $query->exec();
    }

    /**
     * Creates a new delivery entry in the database with the information specified
     *
     * @static
     * @param $orderId
     * @param $companyId
     * @param $startDate
     * @param $endDate
     * @return int ID of newly created order
     */
    public static function createNewDelivery($orderId, $companyId, $startDate, $endDate) {

        $query = new w2p_Database_Query();
        $values = array(
            "delivery_id" => self::getNextDeliveryId(),
            "order_id" => $orderId,
            "company" => $companyId,
            "start_date" => $startDate,
            "end_date" => $endDate
        );
        return $query->insertArray(COrder::_TBL_PREFIKS_ . "_deliveries", $values);
    }

    /**
     * Fetches all deliveries assosiated with the given order id. It is possible to specify additional database filters
     * using $filter with an array where keys are field names and values are values from that field.
     *
     * @static
     * @param $orderId
     * @param array $filter
     * @return array COrderDeliveries
     */
    public static function fetchOrderDeliveries($orderId, array $filter=array()) {

        // Set up query filter
        $filter[order_id] = $orderId;

        return self::fetchFromDeliveryTbl($filter);
    }

    /**
     * Fetches the COrderDelivery object assosiated with the given ID from the database.
     *
     * @static
     * @param $deliveryId
     * @return mixed
     * @throws Exception
     */
    public static function fetchOrderDeliveryFromDb($deliveryId) {

        // Fetch a single delivery from database
        $filter = array(
            "delivery_id" => $deliveryId
        );
        $result = self::fetchFromDeliveryTbl($filter);

        // Make sure you only get one result
        if(count($result) != 1) {
            throw new Exception("Database possibly corrupt. Expected 1 result.");
        }

        // Return ONLY result
        return $result[0];
    }

    /**
     * A lot of database operations shared similar code. Collected duplicate code here to reuse it in several methods
     *
     * @static
     * @param $filter
     * @return array
     */
    protected static function fetchFromDeliveryTbl($filter) {

        // Build where clause from filter
        $whereStr = self::buildWhereFromArray($filter);

        // Set up query and fetch info from database
        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_deliveries", "del");
        $query->addQuery('*');
        $query->addWhere($whereStr);
        $list = $query->loadList();
        $return = array();

        foreach($list as $row) {
            $return[] = new COrderDelivery($row['delivery_id'], $row['order_id'], $row['start_date'], $row['end_date'], $row['company'], $row['arrived']);
        }

        return $return;
    }

    /**
     * Replaces lots of different shorts in the fetch method and allows them to use a common interface for the where
     * filters used when selecting data in the database. $conditions should contain an array where keys are the database
     * field values and values are the database items to be selected from that field.
     *
     * @static
     * @param array $conditions
     * @return string
     */
    protected static function buildWhereFromArray(array $conditions) {

        // Loop through conditions and create a string from all
        $parts = array();
        foreach($conditions as $key => $value) {

            // If value contains multiple values
            if(is_array($value)) {
                $parts[] = $key . " IN(" . implode(',', $value) . ")";

            }

            // Value is a simple type
            else {
                $parts[] = $key . " = " . $value;
            }
        }

        return implode(' AND ', $parts);
    }

    /**
     * Method used to determine the next order id based on entries in the database
     *
     * @static
     * @return int
     */
    protected static function getNextDeliveryId() {

        // Set up query and fetch highest current ID from database
        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_deliveries", "d");
        $query->addQuery('max(d.delivery_id) as max');
        $result = $query->loadHash();

        // Compute next ID
        $id = intval($result['max'])+1;
        return $id;
    }
}
