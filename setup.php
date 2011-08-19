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
define(ORDERMGMT_DEBUG_DATA, true);

$config = array();
$config['mod_name'] = "Order Management";
$config['mod_version'] = "0.1.0";
$config['mod_directory'] = "ordermgmt";
$config['mod_setup_class'] = "CSetupOrderMgmt";
$config['mod_type'] = "user";
$config['mod_ui_name'] = "Orders";
$config['mod_ui_icon'] = "";
$config['mod_description'] = "Basic order management and inventory control";
$config['mod_config'] = false;
$config['mod_main_class'] = "COrderMgmt";
$config['permissions_item_table'] = 'requisitions';
$config['permissions_item_field'] = 'requisition_id';
$config['permissions_item_label'] = 'requisition_id';

class CSetupOrderMgmt {

    public function install() {
        global $AppUI;
        $acl = $AppUI->acl();

        // Create required head table in database
        $query = new w2p_Database_Query();
        $requisitionsDef = " (
            `requisition_id` INT NOT NULL COMMENT 'Identifying row' ,
            `requisitioned_by` INT NOT NULL COMMENT 'The userid of the person that generated this requisition' ,
            `company` INT NOT NULL COMMENT 'The id of the company related to the order',
            `project` INT COMMENT 'The id of the project this order belongs to',
            `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
            PRIMARY KEY (`requisition_id`) )
            ENGINE = InnoDB";
        $query->createTable("requisitions");
        $query->createDefinition($requisitionsDef);
        $query->exec();

        // Create requisition status information table
        $query->clear();
        $reqStatusInfoDef = " (
            `requisition_status_info_id` INT NOT NULL ,
            `status_title` VARCHAR(45) NOT NULL ,
            `status_information` TINYTEXT NULL ,
            `preferred_color` VARCHAR(6) NOT NULL DEFAULT '000000',
            `icon_path` VARCHAR(20) NOT NULL,
            PRIMARY KEY (`requisition_status_info_id`) )
            ENGINE = InnoDB";
        $query->createTable("requisition_status_info");
        $query->createDefinition($reqStatusInfoDef);
        $query->exec();

        // Create table to store requisition files
        $query->clear();
        $reqStatusInfoDef = " (
            `file_id` INT NOT NULL COMMENT 'Forign key to existing file storage structure, used to identify file.' ,
            `requisition_id` INT NOT NULL COMMENT 'Forign key used to reference the requisitions table to assosiate a file with a requisition.' ,
            PRIMARY KEY (`file_id`, `requisition_id`) ,
            INDEX `file_requisition_fk` (`requisition_id` ASC) ,
            CONSTRAINT `file_requisition_fk`
            FOREIGN KEY (`requisition_id` )
            REFERENCES `requisitions` (`requisition_id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
            ENGINE = InnoDB, 
            COMMENT = 'A table containing files assosiated with requisitions'";
        $query->createTable("requisition_files");
        $query->createDefinition($reqStatusInfoDef);
        $query->exec();

        // Create table to store requisition files
        $query->clear();
        $reqStatusInfoDef = " (
            `requisition_status_id` INT NOT NULL ,
            `requisition_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `status_id` INT NOT NULL,
            `date_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
            `comments` MEDIUMTEXT NULL ,
            PRIMARY KEY (`requisition_status_id`) ,
            INDEX `req_status_fk` (`requisition_id` ASC) ,
            CONSTRAINT `req_status_fk`
                FOREIGN KEY (`requisition_id` )
                REFERENCES `requisitions` (`requisition_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
            CONSTRAINT `status_statusinfo_fk`
                FOREIGN KEY (`status_id` )
                REFERENCES `requisition_status_info` (`requisition_status_info_id` ))
            ENGINE = InnoDB";
        $query->createTable("requisition_status");
        $query->createDefinition($reqStatusInfoDef);
        $query->exec();

        // Create table to store requisition components
        $query->clear();
        $reqStatusInfoDef = "(
        `component_id` INT NOT NULL ,
        `component_price` INT NOT NULL ,
        `component_amount` INT NOT NULL ,
        `component_description` TINYTEXT NOT NULL ,
        `requisition_id` INT NOT NULL ,
        PRIMARY KEY (`component_id`) ,
        INDEX `requisition_component_fk` (`requisition_id` ASC) ,
        CONSTRAINT `requisition_component_fk`
        FOREIGN KEY (`requisition_id` )
        REFERENCES `requisitions` (`requisition_id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
        ENGINE = InnoDB";
        $query->createTable("requisition_components");
        $query->createDefinition($reqStatusInfoDef);
        $query->exec();

        // Create inserts for order status information
        $query->clear();
        $status1 = array(
            'requisition_status_info_id' => '1',
            'status_title' => 'New',
            'status_information' => 'Newly created order',
            'preferred_color' => '000000',
            'icon_path' => 'new.png');
        $status2 = array(
            'requisition_status_info_id' => '2',
            'status_title' => 'Approved',
            'status_information' => 'Order is approved by authorized person',
            'preferred_color' => '000000',
            'icon_path' => 'thumb_up.png');
        $status3 = array(
            'requisition_status_info_id' => '3',
            'status_title' => 'Denied',
            'status_information' => 'Order was denied by authorized person',
            'preferred_color' => '000000',
            'icon_path' => 'exclamation.png');
        $status4 = array(
            'requisition_status_info_id' => '4',
            'status_title' => 'Pending',
            'status_information' => 'Order sent to third party',
            'preferred_color' => '000000',
            'icon_path' => 'time.png');
        $status5 = array(
            'requisition_status_info_id' => '5',
            'status_title' => 'Recieved',
            'status_information' => 'Order components have reached their destination',
            'preferred_color' => '000000',
            'icon_path' => 'package_go.png');
        $status6 = array(
            'requisition_status_info_id' => '6',
            'status_title' => 'Missing',
            'status_information' => 'Part of the original order is missing or have been damaged',
            'preferred_color' => '000000',
            'icon_path' => 'error.png');
        $status7 = array(
            'requisition_status_info_id' => '7',
            'status_title' => 'Completed',
            'status_information' => 'Order is completed',
            'preferred_color' => '000000',
            'icon_path' => 'accept.png');
        $query->insertArray('requisition_status_info', $status1);
        $query->insertArray('requisition_status_info', $status2);
        $query->insertArray('requisition_status_info', $status3);
        $query->insertArray('requisition_status_info', $status4);
        $query->insertArray('requisition_status_info', $status5);
        $query->insertArray('requisition_status_info', $status6);
        $query->insertArray('requisition_status_info', $status7);
        print_r(mysql_error());
        
        if (ORDERMGMT_DEBUG_DATA) {
            $this->debugData();
        }

        return (db_error() ? false : true);
    }

    public function upgrade($old_version) {
        return true;
    }

    public function remove() {
        
        global $AppUI;

        // Remove tables defined by this module
        $query = new w2p_Database_Query();
        $query->dropTable("requisition_status");
        $query->exec();
        $query->clear();

        $query->dropTable("requisition_files");
        $query->exec();
        $query->clear();

        $query->dropTable("requisition_components");
        $query->exec();
        $query->clear();

        $query->dropTable("requisition_status_info");
        $query->exec();
        $query->clear();

        $query->dropTable("requisitions");
        $query->exec();
        $query->clear();
        
        $perms = $AppUI->acl();
        $perms->unregisterModule('Order Management', 'requisitions');
        
        return true;
    }

    protected function debugData() {
        $statusId = 1;
        $compId = 1;
        $rsId = 1;
        $q = new w2p_Database_Query();

        // Create two base components
        $c = array(
            'component_id' => '0',
            'component_price' => '15',
            'component_amount' => '19',
            'component_description' => 'Autogenerated dummy component',
            'requisition_id' => '1'
        );

        for ($i = 1; $i < 8; $i++) {

            // Add a basic requisition for each status
            $a = array(
                'requisition_id' => $i,
                'requisitioned_by' => '1',
                'company' => '1',
                'project' => '1',
                'date_created' => date('Y-m-d H:i:s', time())
            );
            $q->insertArray('requisitions', $a);
            $q->clear();

            /// Create the new status and another one
            $n = array(
                'requisition_status_id' => $statusId,
                'requisition_id' => $i,
                'user_id' => '1',
                'status_id' => '1',
                'date_changed' => date('Y-m-d H:i:s', time()),
                'comments' => 'Setup dummy data'
            );
            $statusId++;
            $q->insertArray('requisition_status', $n);
            $q->clear();

            $ns = array(
                'requisition_status_id' => $statusId++,
                'requisition_id' => $i,
                'user_id' => '1',
                'status_id' => $i,
                'date_changed' => date('Y-m-d H:i:s', time()),
                'comments' => 'Setup dummy data'
            );
            $q->insertArray('requisition_status', $ns);
            $q->clear();

            // Do some modifications to components and insert some for each
            $c['requisition_id'] = $i;
            for ($j = 0; $j < rand(1, 15); $j++) {
                $c['component_id'] = $compId++;
                $c['component_price'] = rand(1, 100);
                $c['component_amount'] = rand(1, 100);
                $q->insertArray('requisition_components', $c);
                $q->clear();
            }
        }
    }

}

?>