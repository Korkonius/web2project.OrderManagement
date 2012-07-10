<?php
require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/classes/order.class.php');

// Parse data to determine the page to create
$offset = w2PgetConfig('page_size', 50) * (w2pgetParam($_POST, 'page')-1);
$filter = w2PgetCleanParam($_POST, 'filter', 'open');

// Load list based on selected filter
switch($filter) {
case 'open':
    $ol = COrder::listOfOpenOrders($offset, w2PgetConfig('page_size', 50));
    break;
case 'all':
    $ol = COrder::createListFromDatabase($offset, w2PgetConfig('page_size', 50));
    break;
default:
    $ol = COrder::listOfOpenOrders($offset, w2PgetConfig('page_size', 50));
    break;
}


$tbs = & new clsTinyButStrong();
$tbs->LoadTemplate(dirname(__FILE__) . '/templates/order_list_core.html');
$tbs->MergeBlock('order', $ol);
$tbs->MergeField('deliveryIcon', w2PfindImage('/lorry_go.png', 'ordermgmt'));
$tbs->MergeField('recievedIcon', w2pfindImage('/thumb_up.png', 'ordermgmt'));
$tbs->MergeField('deliveryOverdueIcon', w2PfindImage('/lorry_error.png', 'ordermgmt'));
$tbs->Show(TBS_OUTPUT);

// This controller is designed to handle ajax requests, so the hack below prevents unwanted output...
die;