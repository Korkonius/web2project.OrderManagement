<?php
require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/order.class.php');

$tbs = & new clsTinyButStrong();
$tbs->LoadTemplate(dirname(__FILE__) . '/templates/order_list_core.html');
$ol = COrder::createListFromDatabase();
//print_r($ol[0]->deliveries[1]->isOverdue());
$tbs->MergeBlock('order', $ol);
$tbs->MergeField('deliveryIcon', w2PfindImage('/lorry_go.png', 'ordermgmt'));
$tbs->MergeField('recievedIcon', w2pfindImage('/thumb_up.png', 'ordermgmt'));
$tbs->MergeField('deliveryOverdueIcon', w2PfindImage('/lorry_error.png', 'ordermgmt'));
$tbs->Show(TBS_OUTPUT);

// This controller is designed to handle ajax requests, so the hack below prevents unwanted output...
die;