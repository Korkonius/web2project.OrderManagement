<?php

require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/classes/order.class.php');

if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

$filter = array(
    "company" => w2PgetParam($_GET, 'company_id')
);
$orders = COrder::createListFromDatabase(0, 2000, $filter);

// Create template object
$tbs = & new clsTinyButStrong();
$tbs->LoadTemplate(dirname(__FILE__) . '/templates/order_list.html');
$tbs->MergeBlock('order', $orders);
$tbs->Show(TBS_OUTPUT);