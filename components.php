<?php
/**
 * This file supplements the default view of incoming orders by providing an additional view for components stored
 * in the database
 * @date 07/04/12
 * @author Eirik E. Ottesen
 */

require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/classes/order.class.php');
require_once(dirname(__FILE__) . '/classes/orderpdf.class.php');
require_once(dirname(__FILE__) . '/classes/orderstoredcomponent.class.php');

if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

// Check ACL to see if the user is allowed to view items in the order module
if (!$acl->checkModule('ordermgmt', 'view')) {
    $AppUI->setMsg("Access denied: Insufficient privilegies to view order list", UI_MSG_ERROR);
    $AppUI->redirect('index.php');
}

// Output titleblock
$titleBlock = new w2p_Theme_TitleBlock('Order Management :: Stored Components', 'folder5.png', $m, "$m.$a");
$titleBlock->addCrumb("?m=ordermgmt", "To Order List");
$titleBlock->show();

// Get component listing template
$tbs = & new clsTinyButStrong();
$tbs->LoadTemplate(dirname(__FILE__) . "/templates/component_list.html");

// Output component list
$tbs->Show(TBS_OUTPUT);