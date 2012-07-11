<?php
require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/classes/order.class.php');
require_once(dirname(__FILE__) . '/classes/ordermodule.class.php');
require_once(dirname(__FILE__) . '/classes/orderstoredcomponent.class.php');
require_once(dirname(__FILE__) . '/classes/inputfilter.class.php');

// Check ACL to see if the user is allowed to view items in the order module
if (!$acl->checkModule('ordermgmt', 'view')) {
    $AppUI->setMsg("Access denied: Insufficient privilegies to access module view", UI_MSG_ERROR);
    $AppUI->redirect('index.php');
}
$tbs = & new clsTinyButStrong();
$tbs->LoadTemplate(dirname(__FILE__) . "/templates/module_view.html");
$modules = COrderModule::createListFromDb(0, 1000);
$tbs->MergeBlock("modules", $modules);
$tbs->Show(TBS_OUTPUT);