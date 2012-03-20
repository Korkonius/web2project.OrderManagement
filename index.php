<?php

function getFolderSelectList2()
{

    $folders = array(0 => '');
    $query = new w2p_Database_Query();
    $query->addTable('file_folders');
    $query->addQuery('file_folder_id, file_folder_name, file_folder_parent');
    $query->addOrder('file_folder_name');
    $folders = $query->loadHashList('file_folder_id');
    return $folders;
}

if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/order.class.php');
require_once(dirname(__FILE__) . '/orderpdf.class.php');

include_once(dirname(__FILE__) . '/do_ordermgmt_aed.php'); // FIXME Should be someway to do this automaticly

$AppUI->savePlace();
$filter = new CInputFilter();
$acl = $AppUI->acl();
if ($acl->checkModule('ordermgmt', 'view')) {


    // Get parameters to act on
    $orderId = w2PgetParam($_GET, 'order_id');
    $showNewOrderForm = w2PgetParam($_GET, 'newOrder'); // NOT validated. Never use directly!
    $newComponent = w2PgetParam($_GET, 'componentForm');
    $newFile = w2PgetParam($_GET, 'fileAddForm');
    $outputJsonComponents = w2PgetParam($_GET, 'getDefaultJSON');
    $outputPdfOrder = w2PgetParam($_GET, 'pdfProject');
    $addDelivery = w2PgetParam($_GET, 'addDelivery');

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
        $oidf = $o->getFormattedId();
        $titleBlock = new w2p_Theme_TitleBlock("Order Management :: $oidf", 'folder5.png', $m, "$m.$a");
        $titleBlock->addCell(
            '<a class="button" href="?m=ordermgmt&pdfProject=' . $orderId
                . '&suppressHeaders=true"><span>Create PDF</span></a>', '', '', ''
        );
        if (COrder::canAdd()) {
            $titleBlock->addCell(
                '<a class="button" href="?m=ordermgmt&newOrder=1"><span>New Order</span></a>', '', '', ''
            );
            $titleBlock->addCell(
            );
        }
        if ($o->canDelete()) {
            $titleBlock->addCell(
                "<a class=\"button\" href=\"?m=ordermgmt&deleteOrder=$orderId\"><span>Delete Order</span></a>",
                '', '',
                ''
            );
        }
        $titleBlock->show();

        $tbs->Show(TBS_OUTPUT);
    } else if (!empty($showNewOrderForm)) {

        // Show the new order form
        // Set up the title block
        $titleBlock = new w2p_Theme_TitleBlock('Order Management :: New Order', 'folder5.png', $m, "$m.$a");
        $titleBlock->show();

        // Prepare template
        $tbs->LoadTemplate(dirname(__FILE__) . '/templates/order_form.html');
        $orderid = COrder::nextOrderId();
        $orderidf = sprintf(COrder::ID_FORMAT, $orderid);
        $defaultComponents = COrderComponent::getDefaultComponentList();

        // Load and merge company and project data
        $projects = new CProject();
        $tbs->MergeBlock('project', $projects->getAllowedProjects($AppUI->user_id));
        $companies = new CCompany();
        $tbs->MergeBlock('company', $companies->getCompanyList($AppUI));
        $tbs->MergeBlock('defaultComponents', $defaultComponents);
        $tbs->MergeField('nextid', $orderid);
        $tbs->MergeField('nextidf', $orderidf);

        // Load and merge file form data
        $folders = getFolderSelectList2();

        $tbs->MergeBlock('folders', $folders);
        $tbs->MergeBlock('categories', w2PgetSysVal('FileType'));

        // Output
        $tbs->Show(TBS_OUTPUT);
    } else if(!empty($addDelivery)) {

        aclCheck('edit', 'Access denied: You need Edit privilegies to add a component');

        // Determine if form data is appended
        $formSubmitted = w2PgetParam($_POST, 'deliverySubmit');
        if(!empty($formSubmitted)) {

            // Handle delivery form
            // FIXME Add proper input filtering
            $companyId = w2PgetParam($_POST, 'companySelect');
            $orderId = w2PgetParam($_POST, 'orderId');
            $startDate = w2PgetParam($_POST, 'startDate');
            $endDate = w2PgetParam($_POST, 'endDate');

            $order = new COrder($orderId);
            $order->addDelivery($companyId, $startDate, $endDate);

            $AppUI->setMsg("Delivery added!", UI_MSG_OK);
            $AppUI->redirect('m=ordermgmt');
        } else {

            // Show the new delivery form
            $titleBlock = new w2p_Theme_TitleBlock("Order Management :: New Delivery", 'folder5.png', $m, "$m.$a");
            $titleBlock->show();

            // Prepare template
            $company = new CCompany();
            $tbs->LoadTemplate(dirname(__FILE__) . '/templates/delivery_form.html');
            $tbs->MergeBlock('companies', $company->getCompanyList($AppUI));
            $tbs->MergeField('orderId', $addDelivery);

            $tbs->Show(TBS_OUTPUT);
        }
    } else {
        if (!empty($newComponent)) {

            // Validate input
            $filter->patternVerification($newComponent, CInputFilter::W2P_FILTER_NUMBERS);
            $o = COrder::createFromDatabase($newComponent);
            $oidf = $o->getFormattedId();

            // Show the new component form
            // Set up the title block
            $titleBlock = new w2p_Theme_TitleBlock(
                'Order Management :: Add Components to ' . $oidf, 'folder5.png', $m, "$m.$a");
            $titleBlock->show();

            $tbs->LoadTemplate(dirname(__FILE__) . '/templates/component_form.html');
            $tbs->MergeField('orderid', $newComponent);
            $tbs->Show(TBS_OUTPUT);

        } else {
            if (!empty($newFile)) {

                // Show new file form
                $titleBlock = new w2p_Theme_TitleBlock('Order Management :: New File', 'folder5.png', $m, "$m.$a");
                $titleBlock->show();

                // Prepare template
                $tbs->LoadTemplate(dirname(__FILE__) . '/templates/file_form.html');

                // Load and merge file form data
                $folders = getFolderSelectList2();
                $order = COrder::createFromDatabase($newFile);

                $tbs->MergeBlock('folders', $folders);
                $tbs->MergeField('order', $order);
                $tbs->MergeBlock('categories', w2PgetSysVal('FileType'));

                // Display template
                $tbs->Show(TBS_OUTPUT);

            } else {
                if (!empty($outputJsonComponents) && isset($_GET['suppressHeaders'])) {

                    // Turn off error reporting
                    error_reporting(E_ERROR);

                    // Output JSON
                    $dc = COrderComponent::getDefaultComponentList();

                    // Escape all descriptions
                    for ($i = 0; $i < count($dc); $i++) {
                        $dc[$i]["description"] = htmlspecialchars($dc[$i]["description"]);
                    }

                    echo json_encode($dc);
                } else {
                    if (!empty($outputPdfOrder) && isset($_GET['suppressHeaders'])) {

                        // Detail template test
                        $order = COrder::createFromDatabase($outputPdfOrder);
                        $sender = new CCompany();
                        $sender->load(1);

                        $test = new COrderPDF($order, $sender, $sender, $AppUI);
                        $test->render();
                    } else {

                        // Set up the title block
                        $titleBlock = new w2p_Theme_TitleBlock('Order Management', 'folder5.png', $m, "$m.$a");
                        if (COrder::canAdd()) {
                            $titleBlock->addCell(
                                '<a class="button" href="?m=ordermgmt&newOrder=1"><span>New Order</span></a>', '', '',
                                ''
                            );
                        }
                        $titleBlock->show();

                        $tbs->LoadTemplate(dirname(__FILE__) . '/templates/order_list.html');
                        $ol = COrder::createListFromDatabase();
                        //print_r($ol[0]->deliveries[1]->isOverdue());
                        $tbs->MergeBlock('order', $ol);
                        $tbs->MergeField('deliveryIcon', w2PfindImage('/lorry_go.png', 'ordermgmt'));
                        $tbs->MergeField('deliveryOverdueIcon', w2PfindImage('/lorry_error.png', 'ordermgmt'));
                        $tbs->Show(TBS_OUTPUT);
                    }
                }
            }
        }
    }
} else {
    $AppUI->setMsg("Access denied: Insufficient privilegies to view order list", UI_MSG_ERROR);
    $AppUI->redirect('m=calendar&a=day_view');
}
?>