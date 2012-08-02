<?php
require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/classes/order.class.php');
require_once(dirname(__FILE__) . '/classes/orderstoredcomponent.class.php');
require_once(dirname(__FILE__) . '/classes/ordermodule.class.php');
require_once(dirname(__FILE__) . '/classes/inputfilter.class.php');

// Check ACL to see if the user is allowed to view items in the order module
if (!$acl->checkModule('ordermgmt', 'view')) {
    $AppUI->setMsg("Access denied: Insufficient privilegies to view order list", UI_MSG_ERROR);
    $AppUI->redirect('index.php');
}

$filter = new CInputFilter();
$componentId = w2PgetParam($_GET, 'cid', null);

// If id is empty or fails to validate do nothing
//if(empty($componentId) || !$filter->patternVerification($componentId, CInputFilter::W2P_FILTER_NUMBERS)) die;

$op = w2PgetParam($_GET, 'op');

// Fetch all parameters from client
$id = w2PgetParam($_POST, 'componentId');
$filter->patternVerification($id, CInputFilter::W2P_FILTER_NUMBERS);


switch($op){
    case "get": // TODO Mode ACL checks into these methods to have more granulated access control...
        $components = COrderStoredComponent::createListFromDb(0, 2000);
        echo json_encode(array(
            "items" => $components
        ));
        break;
    case "getfilterlist":
        $components = COrderStoredComponent::createListFromDb(0, 2000);
        $listItems = array();
        foreach($components as $component) {

            $number = $component->catalogNumber;
            $description = $component->description;
            $brand = $component->brand;
            $supplier = $component->supplier;

            $listItems[] = array(
                "id"            => $component->id,
                "list_name"     => "$number $description $brand $name",
                "list_display"  => "<i>$number</i> :: <strong>$description</strong> :: <span style='color: silver'>$brand by $supplier</span>",
                "list_short"    => "$number $description $brand $name",
                "catalog_number"=> "$number",
                "description"   => "$description",
                "price"         => $component->localPrice
            );
        }
        echo json_encode(array(
            "items" => $listItems
        ));
        break;
    case "edit":

        // Get additional info
        $number = w2PgetParam($_POST, 'componentNumber');
        $description = w2PgetParam($_POST, 'componentName');
        $material = w2PgetParam($_POST, 'componentMaterial');
        $brand = w2PgetParam($_POST, 'componentBrand');
        $supplier = w2PgetParam($_POST, 'componentSupplier');
        $vendorPrice = w2PgetParam($_POST, 'componentPrice');
        $currency = w2PgetParam($_POST, 'componentCurrency');
        $vendorDiscount = w2PgetParam($_POST, 'componentDiscount');
        $vendorRate = w2PgetParam($_POST, 'componentRate');
        $vendorNotes = w2PgetParam($_POST, 'componentNotes');

        // Make sure all input is clean
        $number = $filter->removeUnsafeAttributes($number);
        $description = $filter->removeUnsafeAttributes($description);
        $material = $filter->removeUnsafeAttributes($material);
        $brand = $filter->removeUnsafeAttributes($brand);
        $supplier = $filter->removeUnsafeAttributes($supplier);
        $filter->patternVerification($vendorPrice, CInputFilter::W2P_FILTER_LETTERS_OR_NUMBERS);
        $filter->patternVerification($currency, CInputFilter::W2P_FILTER_LETTERS);
        $filter->patternVerification($vendorDiscount, CInputFilter::W2P_FILTER_LETTERS_OR_NUMBERS);
        $filter->patternVerification($vendorRate, CInputFilter::W2P_FILTER_LETTERS_OR_NUMBERS);
        $filter->patternVerification($vendorNotes, CInputFilter::W2P_FILTER_LETTERS_OR_NUMBERS);

        // Ensure the commas are db safe
        $vendorPrice = str_replace(',', '.', $vendorPrice);
        $vendorDiscount = str_replace(',', '.', $vendorDiscount);

        // Compute local price
        $localPrice = ($vendorPrice*$vendorDiscount)*$vendorRate;

        $query = new w2p_Database_Query();

        // If id is set to 'new' insert component, data should be clean now
        if($id === '0') {
            $data = array(
                'description'       => $description,
                'brand'             => $brand,
                'catalog_number'    => $number,
                'wet_material'      => $material,
                'supplier'          => $supplier,
                'vendor_price'      => $vendorPrice,
                'vendor_currency'   => $currency,
                'exchange_rate'     => $vendorRate,
                'discount'          => $vendorDiscount,
                'local_price'       => $localPrice,
                'notes'             => $vendorNotes
            );

            $id = $query->insertArray(COrder::_TBL_PREFIKS_ . "_default_components", $data);
        }
        // Update an existing id
        else {

            $data = array(
                'component_id'      => $id,
                'description'       => $description,
                'brand'             => $brand,
                'catalog_number'    => $number,
                'wet_material'      => $material,
                'supplier'          => $supplier,
                'vendor_price'      => $vendorPrice,
                'vendor_currency'   => $currency,
                'exchange_rate'     => $vendorRate,
                'discount'          => $vendorDiscount,
                'local_price'       => $localPrice,
                'notes'             => $vendorNotes
            );
            $query->updateArray(COrder::_TBL_PREFIKS_ . "_default_components", $data, 'component_id');
        }


        // Reply to client
        echo json_encode(array(
            "message" => "Successfully updated #" . $id,
            "error" => $query->_db->ErrorMsg()
        ));
        break;
    case "remove":

        // Remove command received delete from database
        $query = new w2p_Database_Query();
        $query->setDelete(COrder::_TBL_PREFIKS_ . "_default_components");
        $query->addWhere('component_id = ' . $id);
        $query->exec();

        // Reply to client
        echo json_encode(array(
            "message" => "Successfully removed #" . $id
        ));
        break;
    case "addComp":

        // Get and make vars safe
        $id = intval(w2PgetParam($_POST, "moduleId"));
        $componentId = intval(w2PgetParam($_POST, "componentId"));
        $amount = intval(w2PgetParam($_POST, "amount"));

        // Add components to order
        COrderModule::attachComponent($id, $componentId, $amount);
        // Reply to client
        echo json_encode(array(
            "message" => "Added component!"
        ));
        break;
    case "delComp":

        // Get and make vars safe
        $id = intval(w2PgetParam($_POST, "moduleId"));
        $componentId = intval(w2PgetParam($_POST, "componentId"));

        // Delete components from module
        COrderModule::deleteComponent($id, $componentId);
        // Reply to client
        echo json_encode(array(
            "message" => "Removed component!"
        ));
        break;

    case "delModule":

        // Get and make vars safe
        $id = intval(w2PgetParam($_POST, "moduleId"));
        $module = COrderModule::createFromDb($id);
        $module->delete();

        // Reply to client
        echo json_encode(array(
            "message" => "Removed module!"
        ));
        break;

    case "currency":

        // Test fetching currency
        COrderStoredComponent::updateAllExchangeRates();
        $AppUI->setMsg("Component exchange rates updated!", UI_MSG_OK);
        $AppUI->redirect('m=ordermgmt');
        break;

    case "getContacts":

        // Fetch contacts and create suitable array for Dojo's filteringselect
        $contact = new CContact();
        $contacts = $contact->loadAll();

        $contactList = array();
        foreach($contacts as $person) {
            //if($person['contact_id'] == 4) print_r($person);
            $contactList[] = array(
                "id" => $person['contact_id'],
                "display" => utf8_encode($person['contact_display_name'])
            );
        }

        // Output and break
        echo json_encode($contactList);
        break;

    case "getProjects":

        // Fetch projects and make a suitable array for Dojo's filteringselect
        $project = new CProject();
        $projects = $project->loadAll();

        $projectList = array();
        foreach($projects as $project) {
            $projectList[] = array(
                "id" => $project['project_id'],
                "display" => utf8_encode($project['project_name'])
            );
        }
        echo json_encode($projectList);
        break;
}