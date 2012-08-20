<?php
require_once(dirname(__FILE__). "/classes/ordermodule.class.php");

if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

// Check permissions
$acl = $AppUI->acl();
if (!$acl->checkModule('files', 'add')) {
    $AppUI->redirect('m=public&a=access_denied');
}

$paramName = "orderModuleFileIn";

$descr = w2PgetParam($_POST, "orderModuleFileDescr");

$fileObj = new CFile();

// Handle uploaded file
if(isset($_FILES[$paramName])) {

    $file = $_FILES[$paramName];
    if($file['size'] < 1) {
        $AppUI->setMsg("Uploaded file size is 0. Process aborted?", UI_MSG_ERROR);
        $AppUI->redirect();
    }

    $fileObj->file_name = $file['name'];
    $fileObj->file_type = $file['type'];
    $fileObj->file_size = $file['size'];
    $fileObj->file_description = $descr;
    $fileObj->file_parent = 0;
    $fileObj->file_owner = $AppUI->user_id;
    $fileObj->file_date = str_replace("'", '', $db->DBTimeStamp(time()));
    $fileObj->file_real_filename = uniqid(rand());

    // Move tmp file
    $result = $fileObj->moveTemp($file);
    if(!$result) {
        $AppUI->setMsg("Failed to move uploaded file.", UI_MSG_ERROR);
        $AppUI->redirect();
    }

    // Store file in database
    $dbResult = $fileObj->store();
    if(!$dbResult === true) {
        $AppUI->setMsg($dbResult, UI_MSG_ERROR);
        $AppUI->redirect();
    } else {
        $moduleId = w2PgetParam($_POST, 'orderModuleId');
        COrderModule::attachFile($moduleId, $fileObj->file_id);

        $AppUI->setMsg("File successfully uploaded and attached to order", UI_MSG_OK, true);
    }
}
