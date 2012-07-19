<?php
require_once('classes/ordermoduleexcelrender.class.php');
require_once('classes/ordermodule.class.php');

if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}
$AppUI->savePlace();
$acl = $AppUI->acl();
if ($acl->checkModule('ordermgmt', 'view')) {

    // Use module id from query string
    $id = intval(w2PgetParam($_GET, "module_id"));

    $module = COrderModule::createFromDb($id);
    $test = new COrderModuleExcelRender($module);
    $test->stream();
}