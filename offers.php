<?php
require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/classes/orderoffer.class.php');

// Check ACL to see if the user is allowed to view items in the order module
if (!$acl->checkModule('ordermgmt', 'view')) {
    $AppUI->setMsg("Access denied: Insufficient privilegies to access module view", UI_MSG_ERROR);
    $AppUI->redirect('index.php');
}

// Set up and display title block
$titleBlock = new w2p_Theme_TitleBlock('Offer Management', 'folder5.png', $m, "$m.$a");
$titleBlock->addCrumb("?m=ordermgmt", "Back to orders");
$titleBlock->addCrumb("?m=ordermgmt&a=modules", "Module View");
$titleBlock->addCell(
    "<a class=\"button\" href=\"?m=ordermgmt&a=offers&edit=-1\"><span>New Offer</span></a>", // NOTE edit 0 would fail check further down
    '', '',
    ''
);
$titleBlock->show();

// Output main content
$offerId = w2PgetParam($_GET, 'offerId');
$aeOffer = w2PgetParam($_GET, 'edit');
$tbs = & new clsTinyButStrong();
if(!empty($offerId)) { // Show order listing
    $offer = COrderOffer::createFromDb($offerId);
    $tbs->LoadTemplate(dirname(__FILE__) . "/templates/offer_view.html");
    $tbs->MergeField('offer', $offer);
    $tbs->MergeBlock('history', $offer->history);
    $tbs->MergeBlock('modules', $offer->modules);
    $tbs->MergeBlock('components', $offer->components);
    $tbs->MergeBlock('files', $offer->files);
} else if(!empty($aeOffer)) { // Show order form
    $tbs->LoadTemplate(dirname(__FILE__) . "/templates/offer_form.html");
} else { // Show order details
    $offers = COrderOffer::createListFromDb(0, 1000);
    $tbs->LoadTemplate(dirname(__FILE__) . "/templates/offer_list.html");
    $tbs->MergeBlock('offers', $offers);
}
$tbs->Show(TBS_OUTPUT);