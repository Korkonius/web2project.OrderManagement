<?php
/**
 * This file supplements the default view of incoming orders by providing an additional view for components stored
 * in the database
 * @date 07/04/12
 * @author Eirik E. Ottesen
 */

require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/order.class.php');
require_once(dirname(__FILE__) . '/orderpdf.class.php');

// Get component listing template
$tbs = & new clsTinyButStrong();
$tbs->LoadTemplate(dirname(__FILE__) . "/templates/component_list.html");

// Get component list
$components = COrderComponent::getDefaultComponentList();
$tbs->MergeBlock('components', $components);

// Output component list
$tbs->Show(TBS_OUTPUT);