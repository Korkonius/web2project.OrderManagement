<?php
//require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/order.class.php');

$order = COrder::createFromDatabase(1);
$jsonObj = json_encode($order);
echo $jsonObj;

// This controller is designed to handle ajax requests, so the hack below prevents unwanted output...
die;