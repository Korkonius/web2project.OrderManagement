<?php
/* $Id$ $URL$ */
if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly.');
}

global $AppUI;

// Check to see if data has been posted to page
$addStatus = w2PgetParam($_POST, 'status_submit'); // TODO CSRF token protection
$addOrder = w2PgetParam($_POST, 'orderSubmit');

// If a new status is submitted
if(!empty($addStatus)) {
    $requisitionId = w2PgetParam($_GET, 'order_id'); // TODO Proper data filtering
    $statusId = w2PgetParam($_POST, 'statusCombo');
    $comment = w2PgetParam($_POST, 'orderComment');
    
    COrderStatus::createNewStatus($requisitionId, $statusId, $comment);
    $AppUI->setMsg('Order status was updated!', UI_MSG_OK, true);
}

// If a new order is submitted
if(!empty($addOrder)) {
    $projectId = w2PgetParam($_POST, 'projectSelect');
    $companyId = w2PgetParam($_POST, 'companySelect');
    $cAmounts = w2PgetParam($_POST, 'componentAmount');
    $cPrices = w2PgetParam($_POST, 'componentPrice');
    $cLabels = w2PgetParam($_POST, 'componentLabel');
    
    // Create new order
    $o = COrder::createNewOrder($companyId, $projectId);
    
    // For each component add it to order
    for($i = 0; $i < count($cAmounts); $i++) {
        $price = $cPrices[$i];
        $amount = $cAmounts[$i];
        $description = $cLabels[$i];
        
        echo "Found component $price $amount $description <br />";
        
        if(!empty($price) && !empty($amount) && !empty($description)) {
            $o->addComponent($price, $amount, $description);
        }
    }
    
    $AppUI->setMsg('Order was created!', UI_MSG_OK, true);
}
?>
