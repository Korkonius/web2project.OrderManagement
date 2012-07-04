<?php
require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/order.class.php');

// Check ACL to see if the user is allowed to view items in the order module
if (!$acl->checkModule('ordermgmt', 'view')) {
    $AppUI->setMsg("Access denied: Insufficient privilegies to view order list", UI_MSG_ERROR);
    $AppUI->redirect('index.php');
}

$filter = new CInputFilter();
$componentId = w2PgetParam($_GET, 'cid', null);

// If id is empty or fails to validate do nothing
if(empty($componentId) || !$filter->patternVerification($componentId, CInputFilter::W2P_FILTER_NUMBERS)) die;

$op = w2PgetParam($_GET, 'field');
switch($op){
    case "name":
    case "catalog_nr":
    case "supplier":
    case "vprice":
    case "discount":
}