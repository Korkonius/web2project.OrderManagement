<?php
require_once(dirname(__FILE__) . "/order.class.php");
class COrderModule
{
    // Data members reflecting information stored in the database
    public $id;
    public $name;
    public $description;
    public $buildtime;
    public $delivered;
    public $childModules;
    public $components;
    public $files;
    public $totalPrice = 0;
    public $modulePrice = 0;

    protected function __construct(array $values) {

        // Initialize object using the information in the parameters
        foreach($values as $key => $value) {
            $this->{$key} = $value;
        }

        // Load components related to this module, load before children to compute price!
        $this->loadComponents();

        // Load children related to this module
        $this->loadChildren();

        // Load all files related to this module
        $this->loadFiles();
    }

    protected function loadChildren() {

        // Load children
        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_module_rel", "r");
        $query->addJoin(COrder::_TBL_PREFIKS_ . "_modules", "m", "m.module_id = r.child_id");
        $query->addWhere("r.parent_id = $this->id");
        $results = $query->loadList();

        $children = array();
        foreach($results as $row) {
            self::fromPrepareDb($row);
            $newChild = new COrderModule($row);

            // Compute grand total of this module
            $this->totalPrice += $newChild->modulePrice;

            $children[] = $newChild;
        }

        $this->childModules = $children;
    }

    protected function loadComponents() {

        // Load components
        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_module_components", "mc");
        $query->addJoin(COrder::_TBL_PREFIKS_ . "_default_components", "dc", "mc.stored_component_id = dc.component_id");
        $query->addWhere("mc.module_id = $this->id");
        $results = $query->loadList();

        // Compute module price
        foreach($results as $row) {
            $this->modulePrice += $row["local_price"] * $row["amount"];
        }

        // Components loaded, without children this is correct
        $this->totalPrice = $this->modulePrice;
        $this->components = $results;
    }

    protected function loadFiles() {

        // Query for file ID's and construct file array
        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_module_files");
        $query->addWhere("module_id=$this->id");
        $results = $query->loadList();

        $files = array();
        foreach($results as $row) {
            $file = new CFile();
            $file->load($row["file_id"]);
            $files[] = $file;
        }

        $this->files = $files;
    }

    protected function fromPrepareDb(array & $entry) {

        // Array replacement values
        $colNames = array(
            "module_id"         => "id",
            "module_name"       => "name",
            "module_description"=> "description",
            "module_buildtime"  => "buildtime",
            "module_delivered"  => "delivered"
        );
        foreach($colNames as $old => $new) {
            $entry[$new] = $entry[$old];
            unset($entry[$old]);
        }
    }

    public static function createListFromDb($offset=0, $limit=10, $filter=array()) {

        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_modules");
        $query->addQuery("*");
        $query->setLimit($limit, $offset);
        if(!empty($filter)) $query->addWhere(self::buildWhereFromArray($filter));
        $results = $query->loadList();

        $modules = array();
        foreach($results as $entry) {
            self::fromPrepareDb($entry);
            $modules[] = new COrderModule($entry);
        }
        return $modules;
    }

    public static function createFromDb($id) {

        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_modules");
        $query->addQuery("*");
        $query->addWhere("module_id = $id");
        $result = $query->loadHash();
        self::fromPrepareDb($result);

        return new COrderModule($result);
    }

    public static function createNewModule($name, $description, $buildTime) {

        // Send new data to database
        $query = new w2p_Database_Query();
        $new = array(
            "module_id" => self::getNextId(),
            "module_name" => $name,
            "module_description" => $description,
            "module_buildtime" => $buildTime,
            "module_delivered" => 0
        );

        return $query->insertArray(COrder::_TBL_PREFIKS_ . "_modules", $new);
    }

    public static function alterModule($id, $name, $description, $buildTime) {

        $query = new w2p_Database_Query();
        $changed = array(
            "module_id" => $id,
            "module_name" => $name,
            "module_description" => $description,
            "module_buildtime" => $buildTime
        );
        return $query->updateArray(COrder::_TBL_PREFIKS_ . "_modules", $changed, "module_id");
    }

    public static function attachFile($id, $fileId) {

        $query = new w2p_Database_Query();
        $new = array(
            "file_id"   => $fileId,
            "module_id" => $id
        );
        return $query->insertArray(COrder::_TBL_PREFIKS_ . "_module_files", $new);
    }

    public static function attachComponent($id, $componentId, $amount) {

        $query = new w2p_Database_Query();
        $new = array(
            "stored_component_id" => $componentId,
            "module_id" => $id,
            "amount" => $amount
        );

        return $query->insertArray(COrder::_TBL_PREFIKS_ . "_module_components", $new);
    }

    public static function deleteComponent($id, $componentId) {

        $query = new w2p_Database_Query();
        $query->setDelete(COrder::_TBL_PREFIKS_ . "_module_components");
        $query->addWhere("stored_component_id = $componentId AND module_id = $id");
        return $query->exec();
    }

    public static function getNextId() {

        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_modules");
        $query->addQuery("MAX(module_id) as id");
        $r = $query->loadHash();

        return $r["id"]+1;
    }

    /**
     * Provides a shared way to add where clauses to queries in this class
     *
     * @static
     * @param array $conditions
     * @return string
     */
    protected static function buildWhereFromArray(array $conditions) {

        // Loop through conditions and create a string from all
        $parts = array();
        foreach($conditions as $key => $value) {

            // If value contains multiple values
            if(is_array($value)) {
                $parts[] = $key . " IN(" . implode(',', $value) . ")";

            }

            // Value is a simple type
            else {
                $parts[] = $key . " = " . $value;
            }
        }

        return implode(' AND ', $parts);
    }
}