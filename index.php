<?php

/* if(!defined('W2P_BASE_DIR')) {
  die('You should not access this file directly');
  } */

require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/order.class.php');

include_once(dirname(__FILE__) . '/do_ordermgmt_aed.php'); // FIXME Should be someway to do this automaticly

$AppUI->savePlace();
$filter = new CInputFilter();

// Get parameters to act on input
$orderId = w2PgetParam($_GET, 'order_id');
$showNewOrderForm = w2PgetParam($_GET, 'newOrder'); // NOT validated. Never use directly!

// Verify that the parameters contain expected values
$filter->patternVerification($orderId, CInputFilter::W2P_FILTER_NUMBERS);

// Create template object
$tbs = & new clsTinyButStrong();

// List template test
if (!empty($orderId) && empty($showNewOrderForm)) {

// Detail template test
    $tbs->LoadTemplate(dirname(__FILE__) . '/templates/order_details.html');
    $o = COrder::createFromDatabase($orderId);
    $tbs->MergeField('order', $o);
    $tbs->MergeBlock('component', $o->getComponents());
    $tbs->MergeBlock('history', $o->getHistory());
    $tbs->MergeBlock('file', $o->getFiles());
    $tbs->MergeBlock('status', COrderStatus::getAllStatusinfo());

    // Set up the title block
    $titleBlock = new CTitleBlock("Order Management :: Order #$o->id :: $o->created", 'folder5.png', $m, "$m.$a");
    $titleBlock->addCell('<a class="button" href="?m=ordermgmt&newOrder=1"><span>New Order</span></a>', '', '', '');
    $titleBlock->addCell("<a class=\"button\" href=\"?m=ordermgmt&deleteOrder=$o->id\"><span>Delete Order</span></a>", '', '', '');
    $titleBlock->show();

    $tbs->Show(TBS_OUTPUT);
} else if (!empty($showNewOrderForm)) {

    // Show the new order form
    // Set up the title block
    $titleBlock = new CTitleBlock('Order Management :: New Order', 'folder5.png', $m, "$m.$a");
    $titleBlock->addCell('<a class="button" href="?m=ordermgmt&newOrder=1"><span>New Order</span></a>', '', '', '');
    $titleBlock->show();

    // Prepare template
    $tbs->LoadTemplate(dirname(__FILE__) . '/templates/order_form.html');

    // Load and merge company and project data
    $projects = new CProject;
    $tbs->MergeBlock('project', $projects->getAllowedProjects($AppUI->user_id));
    $companies = new CCompany;
    $tbs->MergeBlock('company', $companies->getCompanyList($AppUI));

    // Output
    $tbs->Show(TBS_OUTPUT);
} else if (empty($orderId)) {

    // Set up the title block
    $titleBlock = new CTitleBlock('Order Management', 'folder5.png', $m, "$m.$a");
    $titleBlock->addCell('<a class="button" href="?m=ordermgmt&newOrder=1"><span>New Order</span></a>', '', '', '');
    $titleBlock->show();

    $tbs->LoadTemplate(dirname(__FILE__) . '/templates/order_list.html');
    $ol = COrder::createListFromDatabase();
    //$ol[0]->latestStatus();
    $tbs->MergeBlock('order', $ol);
    $tbs->Show(TBS_OUTPUT);
}
?>