<?php

/**
 * COrderComponent contains all the information required to describe the
 * components belonging to a COrder. These components are used to describe the
 * content and price of an order.
 */
class COrderComponent {

    // Class components
    public $id;
    public $price;
    public $amount;
    public $description;
    public $requisitionId;
    public $total;
    
    const NO_ID = -1;

    /**
     * Basic constructor. Populates the internal variables.
     * 
     * @param Int $id
     * @param Int $price
     * @param Int $amount
     * @param String $description
     * @param Int $requisitionId 
     */
    public function __construct($id, $price, $amount, $description, $requisitionId) {

        aclCheck('view', 'Access denied');

        // Populate internal variables
        $this->id = $id;
        $this->price = $price;
        $this->amount = $amount;
        $this->description = $description;
        $this->requisitionId = $requisitionId;
        $this->total = $amount * $price;
    }

    /**
     * Permanently removes the component with the given id from the database.
     *
     * @param $signaling bool
     * @see deleteComponent()
     * @return resource
     */
    public function delete($signaling=true) {
        return COrderComponent::deleteComponent($this->id, $signaling);
    }

    /**
     * Permanently removes the component with the given id from the database.
     * 
     * @param Int $id
     * @param Int $orderId
     * @param bool $signaling
     * @return bool 
     */
    public static function deleteComponent($id, $signaling=true) {

        // Create component from db
        $component = COrderComponent::createFromDb($id);
        
        $order = COrder::createFromDatabase($component->requisitionId);
        if(!$order->canChangeComponents()) {
            throw new Exception("Failed user is not allowed to make changes to this order");
        }

        if(($order->latestStatus()->status != COrderStatus::ORDER_STATUS_NEW) && $signaling) {
            
            // Set order status to changed
            $componentName = $component->description;
            $componentId = $component->id;
            $order->updateStatus(8, "Component #$componentId \"$componentName\" deleted");
        }
        
        // Create and execute database query
        $q = new w2p_Database_Query();
        $q->setDelete(COrder::_TBL_PREFIKS_ . '_components');
        $q->addWhere("component_id = $id");
        $q->exec();

        return true;
    }

    public static function createNewComponent($requisitionId, $price, $amount, $description) {

        $order = COrder::createFromDatabase($requisitionId);
        if(!$order->canChangeComponents()) {
            throw new Exception("Failed user is not allowed to make changes to this order");
        }
        
        // Update status
        if($order->latestStatus()->status != COrderStatus::ORDER_STATUS_NEW) {
            
            // Set order status to changed
            $order->updateStatus(8, "Component \"$description\" added");
        }

        // Find ID
        $q = new w2p_Database_Query();
        $q->addTable(COrder::_TBL_PREFIKS_ . '_components');
        $q->addQuery('max(component_id) as num');
        $q->exec();
        $r = $q->loadHash();
        $id = $r['num'] + 1;

        // Generate hash and insert into database
        $a = array(
            'component_id' => $id,
            'component_price' => $price,
            'component_amount' => $amount,
            'component_description' => $description,
            'order_id' => $requisitionId
        );
        $q->clear();
        $q->insertArray(COrder::_TBL_PREFIKS_ . '_components', $a);
        
        // Return new component
        return COrderComponent::createFromDb($id);
    }

    /**
     * Generates a COrderCompoent instance based on the record with the given
     * id in the database
     * 
     * @global CAppUI $AppUI
     * @param Int $id
     * @return COrderComponent 
     */
    public static function createFromDb($id) {

        global $AppUI;

        aclCheck('view', 'Access denied');

        // Fetch single row containing the requested id
        $q = new w2p_database_query();
        $q->addTable(COrder::_TBL_PREFIKS_ . '_components', 'rc');
        $q->addQuery('*');
        $q->addWhere("rc.`component_id` = $id");
        $q->exec();
        $results = $q->loadList();

        // Expect only one result, if more are returned the database is corrupt
        if (count($results) == 1) {
            $result = $results[0];
            return new COrderComponent($result['component_id'], $result['component_price'], $result['component_amount'], $result['component_description'], $result['order_id']);
        } else {
            $AppUI->setMsg("Failed to create COrderComponent from database. Multiple rows selected from same id. Database corrupt?");
            return false;
        }
    }

    /**
     * Fetches data and creates COrderComponent objects of all database records
     * belonging to the given requisition id.
     * 
     * @param Int $id
     * @return COrderComponent 
     */
    public static function createFromReqId($id) {

        aclCheck('view', 'Access denied');

        // Fetch single row containing the requested id
        $q = new w2p_database_query();
        $q->addTable(COrder::_TBL_PREFIKS_ . '_components');
        $q->addQuery('*');
        $q->addWhere("`order_id` = $id");
        $q->exec();
        $results = $q->loadList();

        // Populate object array
        $components = array(); // Preallocate array space
        foreach ($results as $r) {
            $components[] = new COrderComponent($r['component_id'], $r['component_price'], $r['component_amount'], $r['component_description'], $r['order_id']);
        }

        return $components;
    }

    public static function getDefaultComponentList($limit=1000, $offset=0) {
        
        aclCheck('view', 'Access denied');
        
        // Fetch and build all component objects stored
        $q = new w2p_database_query();
        $q->addTable(COrder::_TBL_PREFIKS_ . '_default_components');
        $q->addQuery('*');
        $q->setLimit($limit, $offset);
        $q->addOrder('supplier');
        $results = $q->loadList();
        
        return $results;
    }
}
?>
