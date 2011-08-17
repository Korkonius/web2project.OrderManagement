<?php
if(!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

class COrderMgmt extends CW2pObject {
    
    public function __construct() {
        parent::__construct('ordermgmt', 'ordermgmt_id');
    }
    
    public function check() {
        return array();
    }
    
    public function store() {
        return parent::store(); // No logic yet in this class
    }
    
    public function delete() {
        return parent::delete();
    }
}
?>