<?php

/**
 * Setup file for web2project addon OrderManagement for basic invoice and inventory control.
 * 
 * TODO: Customize ACL table for more detailed module checks
 * 
 * @author Eirik Ottesen
 * @copyright 2011
 */
if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

// This constant determines if debug data should be inserted into array on setup
define(ORDERMGMT_DEBUG_DATA, false);

$config = array();
$config['mod_name'] = "Order Management";
$config['mod_version'] = "0.3.0";
$config['mod_directory'] = "ordermgmt";
$config['mod_setup_class'] = "CSetupOrderMgmt";
$config['mod_type'] = "user";
$config['mod_ui_name'] = "Orders";
$config['mod_ui_icon'] = "";
$config['mod_description'] = "Basic order management and inventory control";
$config['mod_config'] = false;
$config['mod_main_class'] = "COrderMgmt";
$config['permissions_item_table'] = 'orders';
$config['permissions_item_field'] = 'order_id';
$config['permissions_item_label'] = 'order_id';

class CSetupOrderMgmt {
    const _TBL_PREFIKS_ = "ordermgmt"; // This line determines tables identificator

    public function install() {

        $installScript = dirname(__FILE__) . "/sql/install.sql";
        $this->executeSqlFile($installScript);
    
        if (ORDERMGMT_DEBUG_DATA) {
            $this->debugData();
        }

        return (db_error() ? false : true);
    }

    public function upgrade($old_version) {
            
        switch($old_version) {
            
            case '0.1.0':

                // Load and execute 0_1_0to0_2_0.sql
                $upgradeScript = dirname(__FILE__) . "/sql/0_1_0to0_2_0.sql";
                $this->executeSqlFile($upgradeScript);

            case '0.2.0':

                // Load and execure 0_2_0to0_3_0.sql
                $upgradeScript = dirname(__FILE__) . "/sql/0_2_0to0_3_0.sql";
                $this->executeSqlFile($upgradeScript);
                break;
            
            default:
                return false;
        }
        return true;
    }

    public function remove() {
        
        $removeScript = dirname(__FILE__) . "/sql/remove.sql";
        return $this->executeSqlFile($removeScript);
    }
    
    protected function executeSqlFile($filename) {
        
        global $db;
        
        // File exists?
        if(!file_exists($filename)) return false;
        
        // Real all file contents
        $allSql = file_get_contents($filename);
              
        // Replace magic prefix
        $allSql = str_replace('[PREFIX]', self::_TBL_PREFIKS_, $allSql);
        
        // Exec all statements
        $sqlArray = explode(';', $allSql);
        foreach ($sqlArray as $q) {
            $db->Execute($q);
        }
        
        return true;
    }

    protected function debugData() {
        $statusId = 1;
        $compId = 1;
        $query = new w2p_Database_Query();

        // Create two base components
        $c = array(
            'component_id' => '0',
            'component_price' => '15',
            'component_amount' => '19',
            'component_description' => 'Autogenerated dummy component',
            'order_id' => '1'
        );

        for ($i = 1; $i < 8; $i++) {

            // Add a basic requisition for each status
            $a = array(
                'order_id' => $i,
                'ordered_by' => '1',
                'company' => '1',
                'main_project' => '1',
                'date_created' => date('Y-m-d H:i:s', time())
            );
            $query->insertArray(self::_TBL_PREFIKS_, $a);
            $query->clear();

            /// Create the new status and another one
            $n = array(
                'order_status_id' => $statusId,
                'order_id' => $i,
                'user_id' => '1',
                'status_id' => '1',
                'date_changed' => date('Y-m-d H:i:s', time()),
                'comments' => 'Setup dummy data'
            );
            $statusId++;
            $query->insertArray(self::_TBL_PREFIKS_ . '_status', $n);
            $query->clear();

            $ns = array(
                'order_status_id' => $statusId++,
                'order_id' => $i,
                'user_id' => '1',
                'status_id' => $i,
                'date_changed' => date('Y-m-d H:i:s', time()),
                'comments' => 'Setup dummy data'
            );
            $query->insertArray(self::_TBL_PREFIKS_ . '_status', $ns);
            $query->clear();

            // Do some modifications to components and insert some for each
            $c['order_id'] = $i;
            for ($j = 0; $j < rand(1, 15); $j++) {
                $c['component_id'] = $compId++;
                $c['component_price'] = rand(1, 100);
                $c['component_amount'] = rand(1, 100);
                $query->insertArray(self::_TBL_PREFIKS_ . '_components', $c);
                $query->clear();
            }
        }
    }

}

?>