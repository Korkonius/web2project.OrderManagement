<?php
require_once(dirname(__FILE__) . "/classes/orderoffer.class.php");

if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

// Check permissions
$acl = $AppUI->acl();
if (!$acl->checkModule('files', 'add')) {
    $AppUI->redirect('m=public&a=access_denied');
}

// Receive and process parameters from client
$deliveryDate   = w2PgetParam($_POST, "estDeliveryDate");
$projectId      = w2PgetParam($_POST, "projectId");
$offeredBy      = w2PgetParam($_POST, "offeredBy");
$offeredTo      = w2PgetParam($_POST, "offeredTo");
$ownerId        = w2PgetParam($_POST, "offerOwner");
$contactId      = w2PgetParam($_POST, "offerContact");
$notes          = w2PgetParam($_POST, "offerNotes");
$moduleIds      = json_decode(w2PgetParam($_POST, "offerModules"), true);
$moduleAmounts  = json_decode(w2PgetParam($_POST, "offerAmounts"), true);

$offer = COrderOffer::createNewOffer($projectId, $ownerId, $contactId, $offeredBy, $offeredTo, $deliveryDate, $notes);

$ids = array();
foreach($moduleIds as $mId) {
    $ids[] = $mId['id'];
}
$offer->addModules($moduleAmounts, $ids);

echo json_encode(array(
    "message" => "Routing ok!"
));
die;