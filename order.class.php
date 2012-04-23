<?php

/*if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}*/
global $AppUI;

// Load dependancies
require_once(dirname(__FILE__) . "/ordercomponent.class.php");
require_once(dirname(__FILE__) . "/orderstatus.class.php");
require_once(dirname(__FILE__) . "/orderdelivery.class.php");

$uistyle = $AppUI->getPref('UISTYLE') ? $AppUI->getPref('UISTYLE') : w2PgetConfig('host_style');

function aclCheck($op, $deniedStr) {

    global $AppUI;
    $acl = $AppUI->acl();

    // If op is allowed do nothing. If denied redirect.
    if ($acl->checkModule('ordermgmt', $op)) {
        return true;
    } else {
        $AppUI->setMsg("Access denied: $deniedStr", UI_MSG_ERROR);
        $AppUI->redirect('m=ordermgmt');
    }
}

/**
 * This is the basic order class designed as an OO approach to creating the
 * module. The class contains all information about one particular order and
 * all logic required to load the object from the database and alter it.
 * 
 * In order to minimize impact on performance when only changes to the database
 * are required, like when adding a status without printing the object, the
 * implementation delays loading of file lists, order history and order
 * components untill they are requested.
 * 
 * @version 1.0.0
 * @author Eirik Eggesbø Ottesen
 * @package web2project.OrderManagement
 */
class COrder {
    
    const _TBL_PREFIKS_ = "ordermgmt"; // Must match constant in setup script!!

    // Simple objects containing basic order information
    public $id;
    public $created;
    public $historyBuffered = false;
    public $componentsBuffered = false;
    public $filesBuffered = false;
    public $deliveryBuffered = false;
    public $owner = null;
    public $ownerId = null;
    public $ownerName = null;
    public $company = null;
    public $project = null;
    public $notes = null;
    
    // Complex objects holding extra information
    protected $history = array();
    protected $files = array();
    protected $components = array();
    public $deliveries = array();
    protected $acl;
    
    // Public shared information about orders
    const ID_FORMAT = "RSS-%1$04d";
    public $currency = 'NOK';

    /**
     * Basic constructor. Sets up all "simple" internal variables, and fills
     * out commonly required information. It does not set up some protected
     * variables. These are loaded when requested through the get[var] methods.
     * 
     * @global CAppUI $AppUI
     * @param Int $id
     * @param TimeStamp $created
     * @param Int $ownerId
     * @param Int $companyId
     * @param Int $projectId
     * @param string $notes
     */
    public function __construct($id, $created, $ownerId, $companyId, $projectId, $notes) {

        global $AppUI;
        $this->acl = $AppUI->acl();

        $this->id = $id;
        $this->created = $created;
        $this->owner = new CContact();
        $this->owner->load($ownerId);
        $this->ownerId = $ownerId;
        $this->ownerName = $this->owner->contact_first_name . ' ' . $this->owner->contact_last_name;
        $this->notes = $notes;

        // Load company information
        $c = new CCompany();
        $allCompanies = $c->load($companyId);
        $this->company = $allCompanies;

        // Load project information
        $p = new CProject();
        $p->loadFull($AppUI, $projectId);
        $this->project = $p;

        // Load delivery information
        $this->loadDeliveries();
    }

    /**
     * Soft check to see if a user is allowed to add an COrder to the system.
     * 
     * @return Bool
     */
    public static function canAdd() {
        global $AppUI;
        return $AppUI->acl()->checkModule('ordermgmt', 'add');
    }

    /**
     * Soft check to see if a user is allowed to edit current COrder.
     * 
     * @return Bool
     */
    public function canEdit() {
        return $this->acl->checkModule('ordermgmt', 'edit');
    }

    /**
     * Soft check to see if a user is allowed to delete current COrder.
     * 
     * @return Bool
     */
    public function canDelete() {
        return $this->acl->checkModule('ordermgmt', 'delete');
    }

    /**
     * Attaches a new CFile to this object. Returns the result of the database
     * query.
     * 
     * @param Int $fileId
     * @return Resource
     */
    public function addExistingFile($fileId) {

        // Call static function to attach file to this object
        return COrder::attachFile($this->orderId, $fileId);
    }

    /**
     * Attaches a new COrderStatus to this object.
     * 
     * @param Int $statusId
     * @param String $comment
     * @return COrderStatus
     */
    public function updateStatus($statusId, $comment) {

        // Check acl
        aclCheck('edit', "Insufficient permissions", $this->acl);

        // Use status object to create new status
        return COrderStatus::createNewStatus($this->id, $statusId, $comment);
    }

    /**
     * Creates and adds a new component to this order with the given
     * specifications.
     * 
     * @param Int $price
     * @param Int $amount
     * @param String $description
     * @return COrderComponent
     */
    public function addComponent($price, $amount, $description) {

        // Check acl
        aclCheck('edit', "Insufficient permissions", $this->acl);

        // Use component object to create new component
        return COrderComponent::createNewComponent($this->id, $price, $amount, $description);
    }

    /**
     * Fetches the newest COrderStatus object related to this object.
     * 
     * @return COrderStatus
     */
    public function latestStatus() {

        // Check acl
        aclCheck('view', "Insufficient permissions", $this->acl);

        $a = $this->getHistory();
        end($a);
        return current($a);
    }

    /**
     * Permanently removes this, and related components from the database.
     * NOTE! Related files are not removed as these might be used by other
     * components.
     * 
     * @return resource
     */
    public function delete() {

        // Check acl
        aclCheck('delete', "Insufficient permissions", $this->acl);

        // Loop through all related database object and delete them
        $statuses = $this->getHistory();
        foreach ($statuses as $s) {
            $s->delete();
        }

        $components = $this->getComponents();
        foreach ($components as $c) {
            $c->delete();
        }

        // Dereference files
        $q = new w2p_Database_Query();
        $q->setDelete(self::_TBL_PREFIKS_ . '_files');
        $q->addWhere("order_id = $this->id");
        $q->exec();
        $q->clear();

        // Remove self
        $q->setDelete(self::_TBL_PREFIKS_);
        $q->addWhere("order_id = $this->id");
        return $q->exec();
    }

    /**
     * Magic method that outputs this object.
     * 
     * @return String
     */
    public function __toString() {
        try {
            return print_r($this, true);
        } catch (Exception $e) {
            return "An error occured: " . $e->getMessage();
        }
    }

    /**
     * Creates a new COrder object by querying the database after data related
     * to the given order id.
     * 
     * @global CAppUI $AppUI
     * @param Int $id
     * @return COrder 
     */
    public static function createFromDatabase($id) {

        global $AppUI;

        // Check acl
        aclCheck('view', "Insufficient permissions", $AppUI->acl());

        // Query the database for one object based on id
        $q = new w2p_database_query();
        $q->addTable(self::_TBL_PREFIKS_, 'r');
        $q->addQuery('*');
        $q->addWhere("order_id = $id");
        $q->exec();

        // Parse result
        $r = $q->loadList();
        if (count($r) == 1) {
            return new COrder($r[0]['order_id'], $r[0]['date_created'], $r[0]['ordered_by'], $r[0]['company'], $r[0]['main_project'], $r[0]['notes']);
        } else {
            $AppUI->setMsg("Failed to create COrder from database. Multiple rows selected from same id. Database corrupt?");
            return false;
        }
    }

    /**
     * Fetches a number of COrder objects from the database, starting with the
     * entry number given by $start and returning a maximum $number objects.
     * Less objects are returned when end of data is reached before $limit.
     * 
     * @param Int $start
     * @param Int $limit
     * @return COrder[]
     */
    public static function createListFromDatabase($start=0, $limit=10, $filter='') {

        global $AppUI;

        // Check acl
        aclCheck('view', "Insufficient permissions", $AppUI->acl());

        // Query the database to fetch multiple objects
        $q = new w2p_database_query();
        $q->addTable(self::_TBL_PREFIKS_, 'r');

        // Add query and limit
        $q->addQuery('*');
        $q->setLimit($limit, $start);
        $q->addOrder('r.order_id');

        // Build where using filters
        if(!empty($filter)) $q->addWhere(COrder::buildWhereFromArray($filter));

        $q->exec();

        // Parse results
        $results = $q->loadList();
        $retArray = array();
        foreach ($results as $r) {
            $retArray[] = new COrder($r['order_id'], $r['date_created'], $r['ordered_by'], $r['company'], $r['main_project'], $r['notes']);
        }

        return $retArray;
    }

    public static function listOfOpenOrders($start=0, $limit=10) {

        // TODO w2p_Database_Query does not support subquery joins... This is hack is devoted to avoid that problem
        global $db;

        // Fetch ids from status list
        $query = 'SELECT s.*
              FROM ordermgmt_status s
              INNER JOIN (SELECT order_id, status_id, MAX(date_changed) as maxdate FROM ordermgmt_status GROUP BY order_id) sub
              ON sub.order_id = s.order_id AND s.date_changed = sub.maxdate
              WHERE NOT(s.status_id = 7) AND NOT(s.status_id = 3)
              ORDER BY s.order_id';
        $res = $db->Execute($query);

        $indexed = $res->GetArray();


        $openOrderIds = array();
        foreach($indexed as $record) {
            $openOrderIds[] = $record['order_id'];
        }

        $filter = array(
            'order_id' => $openOrderIds
        );

        return self::createListFromDatabase($start, $limit, $filter);
    }

    /**
     * Creates an array of COrders based on a project id. This function returns
     * a number of orders specified by $limit and starting with record # $start
     * belonging to the given project.
     * 
     * @global CAppUI $AppUI
     * @param Int $projectId
     * @param Int $start
     * @param Int $limit
     * @return COrder[]
     */
    public static function createListFromProjectId($projectId, $start=0, $limit=10) {

        global $AppUI;

        // Check acl
        aclCheck('view', "Insufficient permissions", $AppUI->acl());

        // Query the database to fetch multiple objects
        $q = new w2p_database_query();
        $q->addTable(self::_TBL_PREFIKS_, 'r');
        $q->addQuery('*');
        $q->addClause("LIMIT", "$start,$limit", false);
        $q->addWhere("main_project = $projectId");
        $q->exec();

        // Parse results
        $results = $q->loadList();
        $retArray = array();
        foreach ($results as $r) {
            $retArray[] = new COrder($r['order_id'], $r['date_created'], $r['requisitioned_by'], $r['company'], $r['main_project'], $r['notes']);
        }

        return $retArray;
    }

    /**
     * This function creates a new order in the database belonging to the
     * specified project and specified company.
     * 
     * @global CAppUI $AppUI
     * @param Int $companyId
     * @param Int $projectId
     * @return COrder 
     */
    public static function createNewOrder($companyId, $projectId, $notes) {

        global $AppUI;

        // Check acl
        aclCheck('view', "Insufficient permissions", $AppUI->acl());

        // Get next Id
        $id = self::nextOrderId();

        // The rest of the fields
        $created = date('Y-m-d H:i:s', time());
        $userId = $AppUI->user_id;

        // Generate hash and insert into database
        $q = new w2p_Database_Query();
        $a = array(
            'order_id' => $id,
            'ordered_by' => $userId,
            'company' => $companyId,
            'main_project' => $projectId,
            'notes' => $notes, // FIXME Make this var safe!!
            'date_created' => $created
        );
        $q->clear();
        $q->insertArray(self::_TBL_PREFIKS_, $a);

        COrderStatus::createNewStatus($id, COrderStatus::ORDER_STATUS_NEW, 'New order');

        // Return new order
        return COrder::createFromDatabase($id);
    }

    /**
     * Checks the maximum inserted order id and returns max id + 1
     * 
     * @return int
     */
    public static function nextOrderId() {
        // Compute order id
        $q = new w2p_Database_Query();
        $q->addTable(self::_TBL_PREFIKS_);
        $q->addQuery('max(order_id) as id');
        $r = $q->loadHash();
        return $r['id'] + 1;
    }
    
    public function canChangeComponents() {
        
        // Check the order status and user privilegies
        $latestStatus = $this->latestStatus();
        if(($latestStatus->status == COrderStatus::ORDER_STATUS_NEW ||
           $latestStatus->status == COrderStatus::ORDER_STATUS_CHANGED) && 
           $this->canEdit()) {
            return TRUE;
        } else if($latestStatus->status != COrderStatus::ORDER_STATUS_NEW && $this->canDelete()){
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Fetches an array of all COrderStatus objects related to the current
     * COrder.
     * 
     * @return COrderStatus[]
     */
    public function getHistory() {

        // Check acl
        aclCheck('view', "Insufficient permissions", $this->acl);

        // Check to see if history is already loaded
        if (!$this->historyBuffered) {
            $this->history = COrderStatus::createFromReqId($this->id);
            $this->historyBuffered = true;
        }

        return $this->history;
    }

    /**
     * Fetches an array of all CFile objects related to the current
     * COrder.
     * 
     * @return CFile[]
     */
    public function getFiles() {

        // Check acl
        aclCheck('view', "Insufficient permissions", $this->acl);

        if (!$this->filesBuffered) {
            // Query database for files
            $q = new w2p_Database_Query();
            $q->addTable(self::_TBL_PREFIKS_ . '_files', 'rf');
            $q->addQuery('*');
            $q->addJoin('files', 'f', 'rf.file_id = f.file_id');
            $q->addWhere("rf.order_id = $this->id");
            $q->exec();
            $this->files = $q->loadList();

            $this->filesBuffered = true;
        }

        return $this->files;
    }

    public function addDelivery($companyId, $startDate, $endDate) {

        // Create new delivery with this task id
        return COrderDelivery::createNewDelivery($this->id, $companyId, $startDate, $endDate);
    }

    protected function loadDeliveries() {

        // Check acl
        aclCheck('view', "Insufficient permissions", $this->acl);

        // Update with delivery
        $deliveries = COrderDelivery::fetchOrderDeliveries($this->id);
        if ($deliveries) {
            $this->deliveries = $deliveries;
        }
    }

    /**
     * Fetches an array of all COrderCompoents related to the current COrder.
     * 
     * @return COrderComponent[]
     */
    public function getComponents() {

        // Check acl
        aclCheck('view', "Insufficient permissions", $this->acl);

        // Check to see if components are loaded
        if (!$this->componentsBuffered) {
            $this->components = COrderComponent::createFromReqId($this->id);
            $this->componentsBuffered = true;
        }

        return $this->components;
    }

    public function getDeliveries() {

        // Check acl
        aclCheck('view', "Insufficient permissions", $this->acl);

        // Check to see if components are loaded
        if(!$this->deliveryBuffered) {
            $this->loadDeliveries();
            $this->deliveryBuffered = true;
        }

        return $this->deliveries;
    }

    /**
     * Returns the total price of the order. This is the sum of all component
     * prices.
     * 
     * @return Int
     */
    public function getOrderTotal() {

        // Check acl
        aclCheck('view', "Insufficient permissions", $this->acl);

        $tot = 0;
        foreach ($this->getComponents() as $c) {
            $tot += $c->total;
        }

        return $tot;
    }
    
    public function getFormattedId() {
        return sprintf(self::ID_FORMAT, $this->id);
    }

    /**
     * Static function to fetch the owner of an order id without loading the
     * entire order. Available so that it is not nessesary to load an entire
     * order to learn the owner id.
     * 
     * @global CAppUI $AppUI
     * @param Int $requisitionId
     * @return CContact 
     */
    public static function getOwnerOfId($requisitionId) {

        global $AppUI;

        // Check acl
        aclCheck('view', "Insufficient permissions", $AppUI->acl());

        // Query database for the owner of the given requisition id
        $q = new w2p_Database_Query();
        $q->addTable(self::_TBL_PREFIKS_);
        $q->addQuery('*');
        $q->addWhere("order_id = $requisitionId");
        $r = $q->loadHash();

        $owner = new CContact();
        $owner->load($r['ordered_by']);
        return $owner;
    }

    /**
     * Static attach file function. This is available to simplify the process
     * of adding a file to an order. It is not nessecary to load an entire
     * order when the order id and file id is known. This is available to ease
     * load in aed scripts.
     * 
     * @param Int $orderId
     * @param Int $fileId
     * @return resource
     */
    public static function attachFile($orderId, $fileId) {

        // Check acl
        aclCheck('edit', "Unable to attach file to order #$orderId. Access denied");

        // Add a record to database connecting file and order
        $q = new w2p_Database_Query();
        $h = array(
            'file_id' => $fileId,
            'order_id' => $orderId
        );
        return $q->insertArray(self::_TBL_PREFIKS_ . '_files', $h);
    }

    public static function countOrders(array $filter=array()) {

        // Check acl
        aclCheck('view', "Insufficient privilegies to view orders. Access denied");

        // Count the total number of records
        $query = new w2p_Database_Query();
        $query->addQuery("count(order_id) as total");
        $query->addTable(self::_TBL_PREFIKS_);

        $result = $query->loadHash();
        return intval($result['total']);
    }

    public static function countOpenOrders() {

        global $db;

        // Fetch ids from status list
        $query = 'SELECT count(s.order_id) as count
              FROM ordermgmt_status s
              INNER JOIN (SELECT order_id, status_id, MAX(date_changed) as maxdate FROM ordermgmt_status GROUP BY order_id) sub
              ON sub.order_id = s.order_id AND s.date_changed = sub.maxdate
              WHERE NOT(s.status_id = 7) AND NOT(s.status_id = 3)
              ORDER BY s.order_id';
        $res = $db->Execute($query);
        $rows = $res->getArray();

        return intval($rows[0]['count']);
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
}


?>