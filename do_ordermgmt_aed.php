<?php

require_once(dirname(__FILE__) . '/inputfilter.class.php');

/* $Id$ $URL$ */
if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly.');
}

global $AppUI;
$filter = new CInputFilter();

// Check to see if data has been posted to page
$addStatus = w2PgetParam($_POST, 'status_submit'); // TODO CSRF token protection
$addOrder = w2PgetParam($_POST, 'orderSubmit');
$orderDeleteId = w2PgetParam($_GET, 'deleteOrder');
$addComponents = w2PgetParam($_POST, 'componentSubmit');
$removeComponent = w2PgetParam($_GET, 'removeComponent');
$addFile = w2PgetParam($_GET, 'addFile') === '1';

// If a new status is submitted
if (!empty($addStatus)) {
    $requisitionId = w2PgetParam($_GET, 'order_id');
    $statusId = w2PgetParam($_POST, 'statusCombo');
    $comment = w2PgetParam($_POST, 'orderComment');

    // Validate parameters
    $filter->patternVerification($requisitionId, CInputFilter::W2P_FILTER_NUMBERS);
    $filter->patternVerification($statusId, CInputFilter::W2P_FILTER_NUMBERS);

    // Blacklisting... Bad, but better than nothing... Will stop most attacks...
    // TODO Checkout something like http://grom.zeminvaders.net/html-sanitizer as alternative
    $comment = $filter->removeUnsafeAttributes($filter->stripOnly($comment, 'script'));

    COrderStatus::createNewStatus($requisitionId, $statusId, $comment);
    $AppUI->setMsg('Order status was updated!', UI_MSG_OK, true);
}

// If an order is to be deleted
if(!empty($orderDeleteId)) {
    
    // Validate parameter
    $filter->patternVerification($orderDeleteId, CInputFilter::W2P_FILTER_NUMBERS);
    
    // Id sanitized, delete order
    $order = COrder::createFromDatabase($orderDeleteId);
    $order->delete();
    
    $AppUI->setMsg("Order #$order->id was deleted!", UI_MSG_OK, true);
}

// If a new order is submitted
if (!empty($addOrder)) {
    $projectId = w2PgetParam($_POST, 'projectSelect');
    $companyId = w2PgetParam($_POST, 'companySelect');
    $cAmounts = w2PgetParam($_POST, 'componentAmount');
    $cPrices = w2PgetParam($_POST, 'componentPrice');
    $cLabels = w2PgetParam($_POST, 'componentLabel');
    $note = w2PgetParam($_POST, 'orderNotes', '');

    // Validate parameters
    $filter->patternVerification($projectId, CInputFilter::W2P_FILTER_NUMBERS);
    $filter->patternVerification($companyId, CInputFilter::W2P_FILTER_NUMBERS);

    // Create new order
    $o = COrder::createNewOrder($companyId, $projectId, $note);

    // For each component add it to order
    for ($i = 0; $i < count($cAmounts); $i++) {
        $price = $cPrices[$i];
        $amount = $cAmounts[$i];
        $description = $cLabels[$i];

        // Validate parameters
        $filter->patternVerification($price, CInputFilter::W2P_FILTER_PRICE);
        $filter->patternVerification($amount, CInputFilter::W2P_FILTER_NUMBERS);

        // Make price understandable by the database
        $price = COrder::dbsafeNumber($price);
        
        // TODO: Same issue as with the status comment
        $description = $filter->removeUnsafeAttributes($filter->stripOnly($description, 'script'));

        if (!empty($price) && !empty($amount) && !empty($description)) {
            $o->addComponent($price, $amount, $description);
        }
    }
    
    // Check to see if order contains a file
    $addFile = (!empty($_FILES['file']['name'])) ? true : false;

    $AppUI->setMsg('Order was created!', UI_MSG_OK, true);
}
if(!empty($addComponents)) {
    
    // Fetch and validate order id
    $orderId = w2PgetParam($_GET, 'orderId');
    $filter->patternVerification($orderId, CInputFilter::W2P_FILTER_NUMBERS);
    
    $cAmounts = w2PgetParam($_POST, 'componentAmount');
    $cPrices = w2PgetParam($_POST, 'componentPrice');
    $cLabels = w2PgetParam($_POST, 'componentLabel');
    
    // For each component add it to order
    for ($i = 0; $i < count($cAmounts); $i++) {
        $price = $cPrices[$i];
        $amount = $cAmounts[$i];
        $description = $cLabels[$i];

        // Validate parameters
        $filter->patternVerification($price, CInputFilter::W2P_FILTER_PRICE);
        $filter->patternVerification($amount, CInputFilter::W2P_FILTER_NUMBERS);

        // Make price understandable by the database
        $price = COrder::dbsafeNumber($price);

        // TODO: Same issue as with the status comment
        $description = $filter->removeUnsafeAttributes($filter->stripOnly($description, 'script'));

        if (!empty($price) && !empty($amount) && !empty($description)) {
            COrderComponent::createNewComponent($orderId, $price, $amount, $description);
        }
    }
    
    $AppUI->setMsg("Components where added to order number $orderId!", UI_MSG_OK, true);
}
if ($addFile) {
    // Check permissions
    $acl = $AppUI->acl();
    if (!$acl->checkModule('files', 'add')) {
        $AppUI->redirect('m=public&a=access_denied');
    }

    // Bind POST to a file object
    $obj = new CFile();
    if (!$obj->bind($_POST)) {
        $AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
        $AppUI->redirect();
    }
    
    // File parameters recieved handle upload
    if(!ini_get('safe_mode')) {
        set_time_limit(600);
    }
    ignore_user_abort(1);
    
    // Handle file upload
    if(isset($_FILES['file'])) {
        
        $file = $_FILES['file'];
        if($file['size'] < 1) {
            $AppUI->setMsg("Uploaded file size is 0. Process aborted?", UI_MSG_ERROR);
            $AppUI->redirect();
        }
        
        $obj->file_name = $file['name'];
        $obj->file_type = $file['type'];
        $obj->file_size = $file['size'];
        $obj->file_parent = 0;
        $obj->file_owner = $AppUI->user_id;
        $obj->file_date = str_replace("'", '', $db->DBTimeStamp(time()));
        $obj->file_real_filename = uniqid(rand());
        
        $result = $obj->moveTemp($file);
        if(!$result){
            $AppUI->setMsg("Failed to move uploaded file.", UI_MSG_ERROR);
            $AppUI->redirect();
        }
        
        
        $dbResult = $obj->store();

        if(!$dbResult === true) {
            $AppUI->setMsg($dbResult, UI_MSG_ERROR);
            $AppUI->redirect();
        } else {
            $orderId = w2PgetParam($_POST, 'order_id');
            COrder::attachFile($orderId, $obj->file_id);
            
            $AppUI->setMsg("File successfully uploaded and attached to order", UI_MSG_OK, true);
            $AppUI->redirect();
        }
        
    } else {
        $AppUI->setMsg("File was not set", UI_MSG_ERROR);
        $AppUI->redirect();
    }
    
    print_r($obj->getError());
}

// Remove component from order
if(!empty($removeComponent)) {
    
    // Validate input
    $componentId = $removeComponent;
    $filter->patternVerification($componentId, CInputFilter::W2P_FILTER_NUMBERS);
    
    COrderComponent::deleteComponent($componentId);
    $AppUI->setMsg("Component with id $componentId was removed!", UI_MSG_OK, true);
}
?>
