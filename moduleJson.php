<?php
require_once(dirname(__FILE__) . '/classes/ordermodule.class.php');
require_once(dirname(__FILE__) . '/classes/inputfilter.class.php');

// Check ACL to see if the user is allowed to view items in the order module
if (!$acl->checkModule('ordermgmt', 'view')) {
    $AppUI->setMsg("Access denied: Insufficient privilegies to access module view", UI_MSG_ERROR);
    $AppUI->redirect('index.php');
}
$filter = new CInputFilter();
$id = intval(w2PgetParam($_GET, "id"));
$module = COrderModule::createFromDb($id);
echo json_encode($module);