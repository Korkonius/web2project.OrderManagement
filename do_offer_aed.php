<?php
require_once(dirname(__FILE__) . "/classes/orderoffer.class.php");

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

COrderOffer::createNewOffer($projectId, $ownerId, $contactId, $offeredBy, $offeredTo, $deliveryDate, $notes);

echo json_encode(array(
    "message" => "Routing ok!"
));
die;