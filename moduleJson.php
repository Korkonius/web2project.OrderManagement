<?php
require_once(dirname(__FILE__) . '/classes/ordermodule.class.php');
require_once(dirname(__FILE__) . '/classes/inputfilter.class.php');

// Check ACL to see if the user is allowed to view items in the order module
if (!$acl->checkModule('ordermgmt', 'view')) {
    $AppUI->setMsg("Access denied: Insufficient privilegies to access module view", UI_MSG_ERROR);
    $AppUI->redirect('index.php');
}
$filter = new CInputFilter();
$op = w2PgetParam($_GET, "op", "");
switch($op) {

    // Add/Edit data recieved
    case "ae":
        $id = intval(w2PgetParam($_POST, "orderModuleId"));
        $name = w2PgetParam($_POST, "orderModuleName");
        $descr = w2PgetParam($_POST, "orderModuleDescr");
        $buildTime = intval(w2PgetParam($_POST, "orderModuleBuild"));

        // Sanitize text data
        $name = $filter->removeUnsafeAttributes($name);
        $descr = $filter->removeUnsafeAttributes($descr);

        // If id is null insert new items
        if($id == 0) {
            COrderModule::createNewModule($name, $descr, $buildTime);
        } else {
            COrderModule::alterModule($id, $name, $descr, $buildTime);
        }

        echo json_encode(array(
            "error" => ""
        ));
        break;

    // Increment instruction recieved
    case "deliverAdd":
        $id = intval(w2PgetParam($_POST, "orderModuleId"));
        $module = COrderModule::createFromDb($id);
        $module->addDelivery();
        echo json_encode(array(
            "error" => ""
        ));
        break;

    default:
        $id = intval(w2PgetParam($_GET, "id"));
        $module = COrderModule::createFromDb($id);
        echo json_encode($module);
        break;
}