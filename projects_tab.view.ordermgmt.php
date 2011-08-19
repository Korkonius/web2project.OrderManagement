<?php

require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/order.class.php');

// Create template object
$tbs = & new clsTinyButStrong();
$tbs->LoadTemplate(dirname(__FILE__) . '/templates/order_list.html');
$ol = COrder::createListFromProjectId(w2PgetParam($_GET, 'project_id'));
//$ol[0]->latestStatus();
$tbs->MergeBlock('order', $ol);
$tbs->Show(TBS_OUTPUT);
?>
