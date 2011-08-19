<?php

if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}
global $AppUI;

$uistyle = $AppUI->getPref('UISTYLE') ? $AppUI->getPref('UISTYLE') : w2PgetConfig('host_style');
define('IMAGE_URL', "./style/$uistyle/images/modules/ordermgmt/img/");

function aclCheck($op, $deniedStr) {
    
    global $AppUI;
    $acl = $AppUI->acl();
    
    // If op is allowed do nothing. If denied redirect.
    if($acl->checkModule('requisitions', $op)) {
        return true;
    }
    else {
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

    // Simple objects containing basic order information
    public $id;
    public $created;
    public $historyBuffered = false;
    public $componentsBuffered = false;
    public $filesBuffered = false;
    public $owner = null;
    public $ownerId = null;
    public $ownerName = null;
    public $company = null;
    public $project = null;
    
    // Complex objects holding extra information
    protected $history = array();
    protected $files = array();
    protected $components = array();
    protected $acl;

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
     */
    public function __construct($id, $created, $ownerId, $companyId, $projectId) {

        global $AppUI;
        $this->acl = $AppUI->acl();

        $this->id = $id;
        $this->created = $created;
        $this->owner = new CContact();
        $this->owner->load($ownerId);
        $this->ownerId = $ownerId;
        $this->ownerName = $this->owner->contact_first_name . ' ' . $this->owner->contact_last_name;

        // Load company information
        $c = new CCompany();
        $allCompanies = $c->load($companyId);
        $this->company = $allCompanies;

        // Load project information
        $p = new CProject();
        $p->loadFull($AppUI, $projectId);
        $this->project = $p;
    }
    
    public static function canAdd() {
        
        global $AppUI;
        return $AppUI->acl()->checkModule('requisitions', 'add');
    }
    
    public function canEdit() {
        return $this->acl->checkModule('requisitions', 'edit');
    }
    
    public function canDelete() {
        return $this->acl->checkModule('requisitions', 'delete');
    }

    /**
     * TODO: Implement this through core functionality
     * @param type $fileId 
     */
    public function attachFile($fileId) {
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
        foreach($statuses as $s) {
            $s->delete();
        }
        
        $components = $this->getComponents();
        foreach($components as $c) {
            $c->delete();
        }
        
        // Dereference files
        $q = new w2p_Database_Query();
        $q->setDelete('requisition_files');
        $q->addWhere("requisition_id = $this->id");
        $q->exec();
        $q->clear();
        
        // Remove self
        $q->setDelete('requisitions');
        $q->addWhere("requisition_id = $this->id");
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
        $q->addTable('requisitions', 'r');
        $q->addQuery('*');
        $q->addWhere("requisition_id = $id");
        $q->exec();

        // Parse result
        $r = $q->loadList();
        if (count($r) == 1) {
            return new COrder($r[0]['requisition_id'], $r[0]['date_created'], $r[0]['requisitioned_by'], $r[0]['company'], $r[0]['project']);
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
    public static function createListFromDatabase($start=0, $limit=10) {
        
        global $AppUI;
        
        // Check acl
        aclCheck('view', "Insufficient permissions", $AppUI->acl());

        // Query the database to fetch multiple objects
        $q = new w2p_database_query();
        $q->addTable('requisitions', 'r');
        $q->addQuery('*');
        $q->addClause("LIMIT", "$start,$limit", false);
        $q->exec();

        // Parse results
        $results = $q->loadList();
        $retArray = array();
        foreach ($results as $r) {
            $retArray[] = new COrder($r['requisition_id'], $r['date_created'], $r['requisitioned_by'], $r['company'], $r['project']);
        }

        return $retArray;
    }
    
    public static function createNewOrder($companyId, $projectId) {
        
        global $AppUI;
        
        // Check acl
        aclCheck('view', "Insufficient permissions", $AppUI->acl());
        
        // Compute order id
        $q = new w2p_Database_Query();
        $q->addTable('requisitions');
        $q->addQuery('max(requisition_id) as id');
        $r = $q->loadHash();
        $id = $r['id']+1;
        
        // The rest of the fields
        $created = date('Y-m-d H:i:s', time());
        $userId = $AppUI->user_id;
        
        // Generate hash and insert into database
        $a = array(
            'requisition_id' => $id,
            'requisitioned_by' => $userId,
            'company' => $companyId,
            'project' => $projectId,
            'date_created' => $created
        );
        $q->clear();
        $q->insertArray('requisitions', $a);
        
        COrderStatus::createNewStatus($id, COrderStatus::ORDER_STATUS_NEW, 'New order');
        
        // Return new order
        return COrder::createFromDatabase($id);
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
            $q->addTable('requisition_files', 'rf');
            $q->addQuery('*');
            $q->addJoin('files', 'f', 'rf.file_id = f.file_id');
            $q->addWhere("rf.requisition_id = $this->id");
            $q->exec();
            $this->files = $q->loadList();
            
            $this->filesBuffered = true;
        }
        
        return $this->files;
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
        foreach($this->getComponents() as $c) {
            $tot += $c->total;
        }
        
        return $tot;
    }

    public static function getOwnerOfId($requisitionId) {
        
        global $AppUI;
        
        // Check acl
        aclCheck('view', "Insufficient permissions", $AppUI->acl());
        
        // Query database for the owner of the given requisition id
        $q = new w2p_Database_Query();
        $q->addTable('requisitions');
        $q->addQuery('*');
        $q->addWhere("requisition_id = $requisitionId");
        $r = $q->loadHash();
        
        $owner = new CContact();
        $owner->load($r['requisitioned_by']);
        return $owner;
    }
}

/**
 * COrderStatus is an object created to support the COrder object by structuring
 * complex status data in an intuitive and easily documentable fashion. It also
 * provides the nessesary facilities to store new status data in the database
 * and load existing data from database.
 * 
 * @author Eirik Eggesbø Ottesen
 * @package web2project.OrderManagement
 * @version 1.0.0
 */
class COrderStatus {

    public $id;
    public $requisitionId;
    public $creatorId;
    public $creatorName;
    public $status;
    public $statusName;
    public $iconPath;
    public $created;
    public $comments;
    
    // Status constants
    const ORDER_STATUS_NEW = 1;
    const ORDER_STATUS_APPROVED = 2;
    const ORDER_STATUS_DENIED = 3;
    const ORDER_STATUS_PENDING = 4;
    const ORDER_STATUS_RECIEVED = 5;
    const ORDER_STATUS_MISSING = 6;
    const ORDER_STATUS_COMPLETED = 7;

    /**
     * Basic constructor that populates all internal variables. Most commonly
     * called by static methods of this class to generate a meaningful instance
     * based on database data.
     * 
     * @param Int $id
     * @param Int $requisitionId
     * @param Int $creator
     * @param Int $status
     * @param String $statusName
     * @param TimeStamp $created
     * @param String $comments
     * @param String $iconPath 
     */
    public function __construct($id, $requisitionId, $creator, $status, $statusName, $created, $comments, $iconPath) {
       
        // Set all internal variables
        $this->id = $id;
        $this->requisitionId = $requisitionId;
        $this->creatorId = $creator;
        $this->creatorName = CContact::getContactByUserid($creator);
        $this->status = $status;
        $this->statusName = $statusName;
        $this->created = $created;
        $this->comments = $comments;
        $this->iconPath = IMAGE_URL . $iconPath;
    }
    
    /**
     * Permanently removes the component with the given id from the database.
     * 
     * @see deleteComponent()
     * @return resource
     */
    public function delete() {
        return COrderStatus::deleteComponent($this->id);
    }
    
    /**
     * Simple local function to provide known status information to whoever requires it
     * 
     * @param Hash $const 
     */
    public static function getAllStatusinfo() {
        
        // Query database for known statuses
        $q = new w2p_Database_Query();
        $q->addTable('requisition_status_info');
        $q->addQuery('*');
        
        return $q->loadList();
    }
    
    /**
     * Creates a new COrderStatus in the database by requiering a minimum of
     * developer input. The rest of the values are looked up and filled in
     * programmaticly. $silent specifies if the owner of the requisition should
     * be notified of the status change.
     * 
     * @global CAppUI $AppUI
     * @param Int $requisitionId
     * @param Int $statusId
     * @param String $comment
     * @param Bool $silent
     * @return COrderStatus
     */
    public static function createNewStatus($requisitionId, $statusId, $comment, $silent=false) {

        global $AppUI;
        
        // Complete status data using known values
        $creator = $AppUI->user_id;
        $created = date('Y-m-d H:i:s', time());
        
        // Find ID
        $q = new w2p_Database_Query();
        $q->addTable('requisition_status');
        $q->addQuery('max(requisition_status_id) as num');
        $q->exec();
        $r = $q->loadHash();
        $id = $r['num']+1;
        
        // Required data known, insert data
        $insert = array(
            'requisition_status_id' => $id,
            'requisition_id' => $requisitionId,
            'user_id' => $creator,
            'status_id' => $statusId,
            'date_changed' => $created,
            'comments' => $comment
        );
        $q->clear();
        $q->insertArray('requisition_status', $insert);
        
        $newStatus = COrderStatus::createFromDb($id);
        
        // Attempt to use TinyButStrong to generate an e-mail
        if(include_once(dirname(__FILE__) . '/lib/tbs_class.php')) {
            
            // Get owner data
            $owner = COrder::getOwnerOfId($requisitionId);
            $url = W2P_BASE_URL . "?m=ordermgmt&order_id=$requisitionId";
            
            // Generate e-mail
            $tbs = new clsTinyButStrong();
            $tbs->LoadTemplate(dirname(__FILE__) . '/templates/emailStatusChange.tmpl');
            $tbs->MergeField('status', $newStatus->statusName);
            $tbs->MergeField('editor', $newStatus->creatorName);
            $tbs->MergeField('comment', $newStatus->comments);
            $tbs->MergeField('url', $url);
            $tbs->Show(TBS_NOTHING);
            
            // Send e-mail to owner
            $mailer = new w2p_Utilities_Mail();
            $mailer->To($owner->contact_email);
            $mailer->From('admin@web2project.com');
            $mailer->Subject("Status of order # $requisitionId has been updated");
            $mailer->Body($tbs->Source);
            $mailer->Send();
            
        } else {
            // NOTHING! Templater not found so cant generate e-mail
        }
        
        // Return the new order status
        return $newStatus;
    }

    /**
     * Fetches the COrderStatus with the given id from the database.
     * 
     * @param Int $id
     * @return COrderStatus 
     */
    public static function createFromDb($id) {

        // Query database
        $q = new w2p_database_query();
        $q->addTable('requisition_status', 'rs');
        $q->addQuery('*');
        $q->addJoin('requisition_status_info', 'rsi', 'rs.status_id = rsi.requisition_status_info_id');
        $q->addWhere("`requisition_status_id` = $id");
        $q->exec();

        // Parse results
        $results = $q->loadList();
        if (count($results) == 1) {
            $r = $results[0];
            return new COrderStatus($r['requisition_status_id'], $r['requisition_id'], $r['user_id'], $r['status_id'], $r['status_title'], $r['date_changed'], $r['comments'], $r['icon_path']);
        } else {
            $AppUI->setMsg("Failed to create COrderStatus from database. Multiple rows selected from same id. Database corrupt?");
            return false;
        }
    }

    /**
     * Fetches all COrderStatus objects related to the given requisition id.
     * 
     * @param Int $id
     * @return COrderStatus[]
     */
    public static function createFromReqId($id) {

        // Query database
        $q = new w2p_database_query();
        $q->addTable('requisition_status', 'rs');
        $q->addQuery('*');
        $q->addJoin('requisition_status_info', 'rsi', 'rs.status_id = rsi.requisition_status_info_id');
        $q->addWhere("`requisition_id` = $id");
        $q->exec();

        // Parse results
        $results = $q->loadList();
        $statuses = array();
        foreach ($results as $r) {
            $statuses[] = new COrderStatus($r['requisition_status_id'], $r['requisition_id'], $r['user_id'], $r['status_id'], $r['status_title'], $r['date_changed'], $r['comments'], $r['icon_path']);
        }

        return $statuses;
    }

    /**
     * Permanently removes the component with the given id from the database.
     * 
     * @param Int $id
     * @return resource 
     */
    public static function deleteComponent($id) {
        
        // Create and execute database query
        $q = new w2p_Database_Query();
        $q->setDelete('requisition_status');
        $q->addWhere("requisition_status_id = $id");
        
        return $q->exec();
    }
}

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
     * @see deleteComponent()
     * @return resource
     */
    public function delete() {
        return COrderComponent::deleteComponent($this->id);
    }
    
    /**
     * Permanently removes the component with the given id from the database.
     * 
     * @param Int $id
     * @return resource 
     */
    public static function deleteComponent($id) {
        
        // Create and execute database query
        $q = new w2p_Database_Query();
        $q->setDelete('requisition_components');
        $q->addWhere("component_id = $id");
        
        return $q->exec();
    }
    
    public static function createNewComponent($requisitionId, $price, $amount, $description) {
        
        // Find ID
        $q = new w2p_Database_Query();
        $q->addTable('requisition_components');
        $q->addQuery('max(component_id) as num');
        $q->exec();
        $r = $q->loadHash();
        $id = $r['num']+1;
        
        // Generate hash and insert into database
        $a = array(
            'component_id' => $id,
            'component_price' => $price,
            'component_amount' => $amount,
            'component_description' => $description,
            'requisition_id' => $requisitionId
        );
        $q->clear();
        $q->insertArray('requisition_components', $a);
        
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

        // Fetch single row containing the requested id
        $q = new w2p_database_query();
        $q->addTable('requisition_components', 'rc');
        $q->addQuery('*');
        $q->addWhere("rc.`component_id` = $id");
        $q->exec();
        $results = $q->loadList();

        // Expect only one result, if more are returned the database is corrupt
        if (count($results) == 1) {
            $result = $results[0];
            return new COrderComponent($result['component_id'], $result['component_price'], $result['component_amount'], $result['component_description'], $result['requisition_id']);
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

        // Fetch single row containing the requested id
        $q = new w2p_database_query();
        $q->addTable('requisition_components');
        $q->addQuery('*');
        $q->addWhere("`requisition_id` = $id");
        $q->exec();
        $results = $q->loadList();

        // Populate object array
        $components = array(); // Preallocate array space
        foreach ($results as $r) {
            $components[] = new COrderComponent($r['component_id'], $r['component_price'], $r['component_amount'], $r['component_description'], $r['requisition_id']);
        }

        return $components;
    }

}

?>