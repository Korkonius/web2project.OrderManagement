<?php

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
    const ORDER_STATUS_CHANGED = 8;

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

        aclCheck('view', 'Access denied');

        // Set all internal variables
        $this->id = $id;
        $this->requisitionId = $requisitionId;
        $this->creatorId = $creator;
        $this->creatorName = CContact::getContactByUserid($creator);
        $this->status = $status;
        $this->statusName = $statusName;
        $this->created = $created;
        $this->comments = $comments;
        $this->iconPath = $iconPath;
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

        aclCheck('view', 'Access denied');

        // Query database for known statuses
        $q = new w2p_Database_Query();
        $q->addTable(COrder::_TBL_PREFIKS_ . '_status_info');
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
        aclCheck('edit', 'Access denied');

        // Complete status data using known values
        $creator = $AppUI->user_id;
        $created = date('Y-m-d H:i:s', time());

        // Find ID
        $q = new w2p_Database_Query();
        $q->addTable(COrder::_TBL_PREFIKS_ . '_status');
        $q->addQuery('max(order_status_id) as num');
        $q->exec();
        $r = $q->loadHash();
        $id = $r['num'] + 1;

        // Required data known, insert data
        $insert = array(
            'order_status_id' => $id,
            'order_id' => $requisitionId,
            'user_id' => $creator,
            'status_id' => $statusId,
            'date_changed' => $created,
            'comments' => $comment
        );
        $q->clear();
        $q->insertArray(COrder::_TBL_PREFIKS_ . '_status', $insert);

        $newStatus = COrderStatus::createFromDb($id);

        // Attempt to use TinyButStrong to generate an e-mail
        if (include_once(dirname(__FILE__) . '/lib/tbs_class.php')) {

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

        aclCheck('view', 'Access denied');

        // Query database
        $q = new w2p_database_query();
        $q->addTable(COrder::_TBL_PREFIKS_ . '_status', 'rs');
        $q->addQuery('*');
        $q->addJoin(COrder::_TBL_PREFIKS_ . '_status_info', 'rsi', 'rs.status_id = rsi.order_status_info_id');
        $q->addWhere("`order_status_id` = $id");
        $q->exec();

        // Build icon path properly
        $icon = w2PfindImage($r['icon_path'], 'ordermgmt');

        // Parse results
        $results = $q->loadList();
        if (count($results) == 1) {
            $r = $results[0];
            return new COrderStatus($r['order_status_id'], $r['order_id'], $r['user_id'], $r['status_id'], $r['status_title'], $r['date_changed'], $r['comments'], $icon);
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

        aclCheck('view', 'Access denied');

        // Query database
        $q = new w2p_database_query();
        $q->addTable(COrder::_TBL_PREFIKS_ . '_status', 'rs');
        $q->addQuery('*');
        $q->addJoin(COrder::_TBL_PREFIKS_ . '_status_info', 'rsi', 'rs.status_id = rsi.order_status_info_id');
        $q->addWhere("`order_id` = $id");
        $q->exec();

        // Parse results
        $results = $q->loadList();
        $statuses = array();
        foreach ($results as $r) {

            // Build icon path properly
            $icon = w2PfindImage($r['icon_path'], 'ordermgmt');

            $statuses[] = new COrderStatus($r['order_status_id'], $r['order_id'], $r['user_id'], $r['status_id'], $r['status_title'], $r['date_changed'], $r['comments'], $icon);
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

        aclCheck('delete', 'Access denied');

        // Create and execute database query
        $q = new w2p_Database_Query();
        $q->setDelete(COrder::_TBL_PREFIKS_ . '_status');
        $q->addWhere("order_status_id = $id");

        return $q->exec();
    }

}

?>
