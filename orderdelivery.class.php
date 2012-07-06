<?php
// TODO Implement proper ACL checks
class COrderDelivery
{
    public $delivery_id;
    public $order_id;
    public $delivery_start_date;
    public $delivery_end_date;
    public $company;
    public $arrived;

    protected function __construct($delivery_id, $order_id, $delivery_start_date, $delivery_end_date, $company, $arrived) {

        // Update internal fields with values
        $this->delivery_id = $delivery_id;
        $this->order_id = $order_id;
        $this->delivery_end_date = $delivery_end_date;
        $this->delivery_start_date = $delivery_start_date;
        $this->company = $company;
        $this->arrived = $arrived;
    }

    public function delete() {

        $query = new w2p_Database_Query();
        $query->setDelete(COrder::_TBL_PREFIKS_ . "_deliveries");
        $query->addWhere("delivery_id = $this->delivery_id");

        return $query->exec();
    }

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

    public function hasArrived() {
        if(!empty($this->arrived)) {
            return true;
        }
    }

    public function justArrived() {
        return $this->setArrived(date('Y-m-d H:i:s'));
    }

    public function setArrived($time) {

        // Update record with arrived time
        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_deliveries", "del");
        $query->addUpdate('arrived', $time);
        $query->addWhere("delivery_id = " . $this->delivery_id);

        return $query->exec();
    }

    public static function createNewDelivery($orderId, $companyId, $startDate, $endDate) {

        $query = new w2p_Database_Query();
        $values = array(
            "delivery_id" => self::getNextDeliveryId(),
            "order_id" => $orderId,
            "company" => $companyId,
            "start_date" => $startDate,
            "end_date" => $endDate
        );
        $query->insertArray(COrder::_TBL_PREFIKS_ . "_deliveries", $values);
    }

    public static function fetchOrderDeliveries($orderId, array $filter=array()) {

        // Set up query filter
        $filter[order_id] = $orderId;

        return self::fetchFromDeliveryTbl($filter);
    }

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
