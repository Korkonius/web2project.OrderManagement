<?php

function getFolderSelectList2() {
    global $AppUI;
    $folders = array(0 => '');
    $q = new w2p_Database_Query();
    $q->addTable('file_folders');
    $q->addQuery('file_folder_id, file_folder_name, file_folder_parent');
    $q->addOrder('file_folder_name');
    $folders = $q->loadHashList('file_folder_id');
    return $folders;
}

if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/order.class.php');

include_once(dirname(__FILE__) . '/do_ordermgmt_aed.php'); // FIXME Should be someway to do this automaticly

$AppUI->savePlace();
$filter = new CInputFilter();
$acl = $AppUI->acl();
if ($acl->checkModule('ordermgmt', 'view')) {


// Get parameters to act on input
    $orderId = w2PgetParam($_GET, 'order_id');
    $showNewOrderForm = w2PgetParam($_GET, 'newOrder'); // NOT validated. Never use directly!
    $newComponent = w2PgetParam($_GET, 'componentForm');
    $newFile = w2PgetParam($_GET, 'fileAddForm');

// Verify that the parameters contain expected values
    $filter->patternVerification($orderId, CInputFilter::W2P_FILTER_NUMBERS);
    $filter->patternVerification($newFile, CInputFilter::W2P_FILTER_NUMBERS);

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
        if (COrder::canAdd())
            $titleBlock->addCell('<a class="button" href="?m=ordermgmt&newOrder=1"><span>New Order</span></a>', '', '', '');
        if ($o->canDelete())
            $titleBlock->addCell("<a class=\"button\" href=\"?m=ordermgmt&deleteOrder=$o->id\"><span>Delete Order</span></a>", '', '', '');
        $titleBlock->show();

        $tbs->Show(TBS_OUTPUT);
    } else if (!empty($showNewOrderForm)) {

        // Show the new order form
        // Set up the title block
        $titleBlock = new CTitleBlock('Order Management :: New Order', 'folder5.png', $m, "$m.$a");
        $titleBlock->show();

        // Prepare template
        $tbs->LoadTemplate(dirname(__FILE__) . '/templates/order_form.html');
        $orderid = COrder::nextOrderId();
        $defaultComponents = COrderComponent::getDefaultComponentList();

        // Load and merge company and project data
        $projects = new CProject();
        $tbs->MergeBlock('project', $projects->getAllowedProjects($AppUI->user_id));
        $companies = new CCompany();
        $tbs->MergeBlock('company', $companies->getCompanyList($AppUI));
        $tbs->MergeBlock('defaultComponents', $defaultComponents);
        $tbs->MergeField('nextid', $orderid);
        
        // Load and merge file form data
        $folders  = getFolderSelectList2();

        $tbs->MergeBlock('folders', $folders);
        $tbs->MergeBlock('categories', w2PgetSysVal('FileType'));

        // Output
        $tbs->Show(TBS_OUTPUT);
    } else if (!empty($newComponent)) {

        // Validate input
        $filter->patternVerification($newComponent, CInputFilter::W2P_FILTER_NUMBERS);

        // Show the new component form
        // Set up the title block
        $titleBlock = new CTitleBlock('Order Management :: Add Components to order # ' . $newComponent, 'folder5.png', $m, "$m.$a");
        $titleBlock->show();

        $tbs->LoadTemplate(dirname(__FILE__) . '/templates/component_form.html');
        $tbs->MergeField('orderid', $newComponent);
        $tbs->Show(TBS_OUTPUT);
    
    } else if(!empty($newFile)) {
        
        // Show new file form
        $titleBlock = new CTitleBlock('Order Management :: New File', 'folder5.png', $m, "$m.$a");
        $titleBlock->show();
        
        // Prepare template
        $tbs->LoadTemplate(dirname(__FILE__) . '/templates/file_form.html');
        
        // Load and merge file form data
        $folders  = getFolderSelectList2();
        $order = COrder::createFromDatabase($newFile);

        $tbs->MergeBlock('folders', $folders);
        $tbs->MergeField('order', $order);
        $tbs->MergeBlock('categories', w2PgetSysVal('FileType'));
        
        // Display template
        $tbs->Show(TBS_OUTPUT);
        
    } else {

        // Set up the title block
        $titleBlock = new CTitleBlock('Order Management', 'folder5.png', $m, "$m.$a");
        if (COrder::canAdd())
            $titleBlock->addCell('<a class="button" href="?m=ordermgmt&newOrder=1"><span>New Order</span></a>', '', '', '');
        $titleBlock->show();

        $tbs->LoadTemplate(dirname(__FILE__) . '/templates/order_list.html');
        $ol = COrder::createListFromDatabase();
        //$ol[0]->latestStatus();
        $tbs->MergeBlock('order', $ol);
        $tbs->Show(TBS_OUTPUT);
    }
} else {
    $AppUI->setMsg("Access denied: Insufficient privilegies to view order list", UI_MSG_ERROR);
    $AppUI->redirect('m=calendar&a=day_view');
}
?>