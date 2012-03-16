<?php
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

    public static function fetchOrderDelivery($orderId) {

        // Set up query and fetch info from database
        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_deliveries", "del");
        $query->addQuery('*');
        $query->addWhere("order_id = $orderId");
        $row = $query->loadList();

        if(count($row) == 1) {
            return new COrderDelivery($row[0]['delivery_id'], $row[0]['order_id'], $row[0]['start_date'], $row[0]['end_date'], $row[0]['company'], $row[0]['arrived']);
        } else {

            // Return null. Assuming no deliveries found, implying that no deliveries are registered for the order
            return null;
        }

    }
}
