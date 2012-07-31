<?php
require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/classes/orderoffer.class.php');

// Check ACL to see if the user is allowed to view items in the order module
if (!$acl->checkModule('ordermgmt', 'view')) {
    $AppUI->setMsg("Access denied: Insufficient privilegies to access module view", UI_MSG_ERROR);
    $AppUI->redirect('index.php');
}
$tbs = & new clsTinyButStrong();
$offer = COrderOffer::createFromDb(1);
$tbs->LoadTemplate(dirname(__FILE__) . "/templates/offer_view.html");
$tbs->MergeField('offer', $offer);
$tbs->MergeBlock('history', $offer->history);
$tbs->MergeBlock('modules', $offer->modules);
$tbs->Show(TBS_OUTPUT);