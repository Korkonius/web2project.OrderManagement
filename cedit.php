<?php
require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/order.class.php');
require_once(dirname(__FILE__) . '/inputfilter.class.php');

// Check ACL to see if the user is allowed to view items in the order module
if (!$acl->checkModule('ordermgmt', 'view')) {
    $AppUI->setMsg("Access denied: Insufficient privilegies to view order list", UI_MSG_ERROR);
    $AppUI->redirect('index.php');
}

$filter = new CInputFilter();
$componentId = w2PgetParam($_GET, 'cid', null);

// If id is empty or fails to validate do nothing
//if(empty($componentId) || !$filter->patternVerification($componentId, CInputFilter::W2P_FILTER_NUMBERS)) die;

$op = w2PgetParam($_GET, 'op');
switch($op){
    case "get": // TODO Mode ACL checks into these methods to have more granulated access control...
        $components = COrderComponent::getDefaultComponentList();
        echo json_encode(array(
            "items" => $components
        ));
        break;
    case "edit":

        // Fetch all parameters from client
        $id = w2PgetParam($_POST, 'componentId');
        $description = w2PgetParam($_POST, 'componentName');
        $material = w2PgetParam($_POST, 'componentMaterial');
        $brand = w2PgetParam($_POST, 'componentBrand');
        $supplier = w2PgetParam($_POST, 'componentSupplier');


        // Reply to client
        echo json_encode(array(
            "message" => "Recieved #" . $id
        ));
}