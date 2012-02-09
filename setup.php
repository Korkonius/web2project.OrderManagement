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
$config['permissions_item_table'] = 'orders';
$config['permissions_item_field'] = 'order_id';
$config['permissions_item_label'] = 'order_id';

class CSetupOrderMgmt {
    const _TBL_PREFIKS_ = "ordermgmt"; // This line determines tables identificator

    public function install() {
        global $AppUI;
        $acl = $AppUI->acl();

        // Create required head table in database
        $query = new w2p_Database_Query();
        $requisitionsDef = " (
            `order_id` INT NOT NULL COMMENT 'Identifying row' ,
            `ordered_by` INT NOT NULL COMMENT 'The userid of the person that generated this requisition' ,
            `company` INT NOT NULL COMMENT 'The id of the company related to the order',
            `project` INT COMMENT 'The id of the project this order belongs to',
            `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
            PRIMARY KEY (`order_id`))
            ENGINE = InnoDB";
        $query->createTable(self::_TBL_PREFIKS_);
        $query->createDefinition($requisitionsDef);
        $query->exec();

        // Create requisition status information table
        $query->clear();
        $reqStatusInfoDef = " (
            `order_status_info_id` INT NOT NULL ,
            `status_title` VARCHAR(45) NOT NULL ,
            `status_information` TINYTEXT NULL ,
            `preferred_color` VARCHAR(6) NOT NULL DEFAULT '000000',
            `icon_path` VARCHAR(20) NOT NULL,
            PRIMARY KEY (`order_status_info_id`) )
            ENGINE = InnoDB";
        $query->createTable(self::_TBL_PREFIKS_ . "_status_info");
        $query->createDefinition($reqStatusInfoDef);
        $query->exec();

        // Create table to store requisition files
        $query->clear();
        $reqStatusInfoDef = " (
            `file_id` INT NOT NULL COMMENT 'Forign key to existing file storage structure, used to identify file.' ,
            `order_id` INT NOT NULL COMMENT 'Forign key used to reference the requisitions table to assosiate a file with a requisition.' ,
            PRIMARY KEY (`file_id`, `order_id`) ,
            INDEX `file_order_fk` (`order_id` ASC) ,
            CONSTRAINT `file_order_fk`
            FOREIGN KEY (`order_id` )
            REFERENCES `" . self::_TBL_PREFIKS_ . "` (`order_id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
            ENGINE = InnoDB, 
            COMMENT = 'A table containing files assosiated with requisitions'";
        $query->createTable(self::_TBL_PREFIKS_ . "_files");
        $query->createDefinition($reqStatusInfoDef);
        $query->exec();

        // Create table to store requisition files
        $query->clear();
        $reqStatusInfoDef = " (
            `order_status_id` INT NOT NULL ,
            `order_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `status_id` INT NOT NULL,
            `date_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
            `comments` MEDIUMTEXT NULL ,
            PRIMARY KEY (`order_status_id`) ,
            CONSTRAINT `order_status2_fk`
                FOREIGN KEY (`order_id` )
                REFERENCES `" . self::_TBL_PREFIKS_ . "` (`order_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
            CONSTRAINT `status_statusinfo2_fk`
                FOREIGN KEY (`status_id` )
                REFERENCES `" . self::_TBL_PREFIKS_ . "_status_info` (`order_status_info_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION )
            ENGINE = InnoDB";
        $query->createTable(self::_TBL_PREFIKS_ . "_status");
        $query->createDefinition($reqStatusInfoDef);
        $query->exec();

        // Create table to store requisition components
        $query->clear();
        $reqStatusInfoDef = "(
        `component_id` INT NOT NULL ,
        `component_price` INT NOT NULL ,
        `component_amount` INT NOT NULL ,
        `component_description` TINYTEXT NOT NULL ,
        `order_id` INT NOT NULL ,
        PRIMARY KEY (`component_id`) ,
        INDEX `order_component_fk` (`order_id` ASC) ,
        CONSTRAINT `order_component_fk`
        FOREIGN KEY (`order_id` )
        REFERENCES `" . self::_TBL_PREFIKS_ . "` (`order_id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
        ENGINE = InnoDB";
        $query->createTable(self::_TBL_PREFIKS_ . "_components");
        $query->createDefinition($reqStatusInfoDef);
        $query->exec();

        // Create inserts for order status information
        $query->clear();
        $status1 = array(
            'order_status_info_id' => '1',
            'status_title' => 'New',
            'status_information' => 'Newly created order',
            'preferred_color' => '000000',
            'icon_path' => 'new.png');
        $status2 = array(
            'order_status_info_id' => '2',
            'status_title' => 'Approved',
            'status_information' => 'Order is approved by authorized person',
            'preferred_color' => '000000',
            'icon_path' => 'thumb_up.png');
        $status3 = array(
            'order_status_info_id' => '3',
            'status_title' => 'Denied',
            'status_information' => 'Order was denied by authorized person',
            'preferred_color' => '000000',
            'icon_path' => 'exclamation.png');
        $status4 = array(
            'order_status_info_id' => '4',
            'status_title' => 'Pending',
            'status_information' => 'Order sent to third party',
            'preferred_color' => '000000',
            'icon_path' => 'time.png');
        $status5 = array(
            'order_status_info_id' => '5',
            'status_title' => 'Recieved',
            'status_information' => 'Order components have reached their destination',
            'preferred_color' => '000000',
            'icon_path' => 'package_go.png');
        $status6 = array(
            'order_status_info_id' => '6',
            'status_title' => 'Missing',
            'status_information' => 'Part of the original order is missing or have been damaged',
            'preferred_color' => '000000',
            'icon_path' => 'error.png');
        $status7 = array(
            'order_status_info_id' => '7',
            'status_title' => 'Completed',
            'status_information' => 'Order is completed',
            'preferred_color' => '000000',
            'icon_path' => 'accept.png');
        $status8 = array(
            'order_status_info_id' => '8',
            'status_title' => 'Changed',
            'status_information' => 'Order components has changed',
            'preferred_color' => '000000',
            'icon_path' => 'information.png');
        $query->insertArray(self::_TBL_PREFIKS_ . '_status_info', $status1);
        $query->insertArray(self::_TBL_PREFIKS_ . '_status_info', $status2);
        $query->insertArray(self::_TBL_PREFIKS_ . '_status_info', $status3);
        $query->insertArray(self::_TBL_PREFIKS_ . '_status_info', $status4);
        $query->insertArray(self::_TBL_PREFIKS_ . '_status_info', $status5);
        $query->insertArray(self::_TBL_PREFIKS_ . '_status_info', $status6);
        $query->insertArray(self::_TBL_PREFIKS_ . '_status_info', $status7);
        $query->insertArray(self::_TBL_PREFIKS_ . '_status_info', $status8);
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
        $query->dropTable(self::_TBL_PREFIKS_ . "_status");
        $query->exec();
        $query->clear();

        $query->dropTable(self::_TBL_PREFIKS_ . "_files");
        $query->exec();
        $query->clear();

        $query->dropTable(self::_TBL_PREFIKS_ . "_components");
        $query->exec();
        $query->clear();

        $query->dropTable(self::_TBL_PREFIKS_ . "_status_info");
        $query->exec();
        $query->clear();

        $query->dropTable(self::_TBL_PREFIKS_);
        $query->exec();
        $query->clear();
        
        $perms = $AppUI->acl();
        $perms->unregisterModule('Order Management', self::_TBL_PREFIKS_);
        
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
            'order_id' => '1'
        );

        for ($i = 1; $i < 8; $i++) {

            // Add a basic requisition for each status
            $a = array(
                'order_id' => $i,
                'ordered_by' => '1',
                'company' => '1',
                'project' => '1',
                'date_created' => date('Y-m-d H:i:s', time())
            );
            $q->insertArray(self::_TBL_PREFIKS_, $a);
            $q->clear();

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
            $q->insertArray(self::_TBL_PREFIKS_ . '_status', $n);
            $q->clear();

            $ns = array(
                'order_status_id' => $statusId++,
                'order_id' => $i,
                'user_id' => '1',
                'status_id' => $i,
                'date_changed' => date('Y-m-d H:i:s', time()),
                'comments' => 'Setup dummy data'
            );
            $q->insertArray(self::_TBL_PREFIKS_ . '_status', $ns);
            $q->clear();

            // Do some modifications to components and insert some for each
            $c['order_id'] = $i;
            for ($j = 0; $j < rand(1, 15); $j++) {
                $c['component_id'] = $compId++;
                $c['component_price'] = rand(1, 100);
                $c['component_amount'] = rand(1, 100);
                $q->insertArray(self::_TBL_PREFIKS_ . '_components', $c);
                $q->clear();
            }
        }
    }

}

?>