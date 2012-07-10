<?php
require_once(dirname(__FILE__) . '/lib/tbs_class.php');
require_once(dirname(__FILE__) . '/classes/order.class.php');
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
        $components = COrderComponent::getDefaultComponentList();
        echo json_encode(array(
            "items" => $components
        ));
        break;
    case "getfilterlist":
        $components = COrderComponent::getDefaultComponentList();
        $listItems = array();
        foreach($components as $component) {

            $number = $component['catalog_number'];
            $description = $component['description'];
            $brand = $component['brand'];
            $supplier = $component['supplier'];

            $listItems[] = array(
                "id"            => $component['component_id'],
                "list_name"     => "$number $description $brand $name",
                "list_display"  => "<i>$number</i> :: <strong>$description</strong> :: <span style='color: silver'>$brand by $supplier</span>",
                "list_short"    => "$number $description",
                "price"         => $component["local_price"]
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
}