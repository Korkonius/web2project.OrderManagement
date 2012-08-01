<?php
require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/classes/orderoffer.class.php');

// Check ACL to see if the user is allowed to view items in the order module
if (!$acl->checkModule('ordermgmt', 'view')) {
    $AppUI->setMsg("Access denied: Insufficient privilegies to access module view", UI_MSG_ERROR);
    $AppUI->redirect('index.php');
}
$tbs = & new clsTinyButStrong();
$offers = COrderOffer::createListFromDb(0, 1000);
$tbs->LoadTemplate(dirname(__FILE__) . "/templates/offer_list.html");
$tbs->MergeBlock('offers', $offers);
$tbs->Show(TBS_OUTPUT);