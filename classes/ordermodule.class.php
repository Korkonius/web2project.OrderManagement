<?php
require_once(dirname(__FILE__) . "/order.class.php");
/**
 * COrderModule is a representation of order modules. These modules are groupings of components that represent
 * a module offered by the company. Modules are supposed to help users determine the cost of a common module
 * configuration and options to create a strong relation between commonly used components, files such as drawings or
 * specifications of a module and estimated completion time of the module. This helps project planners generate offers
 * and keep track of important aspects of each module.
 */
class COrderModule
{
    // Data members reflecting information stored in the database
    public $id;
    public $name;
    public $description;
    public $buildtime;
    public $delivered = 0;
    public $components;
    public $files;
    public $totalPrice = 0;
    public $modulePrice = 0;

    /**
     * Removes this object from the database.
     *
     * @return Handle
     */
    public function delete() {
        $query = new w2p_Database_Query();

        $query->setDelete(COrder::_TBL_PREFIKS_ . "_module_components");
        $query->addWhere("module_id = $this->id");
        $query->exec();
        $query->clear();

        $query->setDelete(COrder::_TBL_PREFIKS_ . "_module_files");
        $query->addWhere("module_id = $this->id");
        $query->exec();
        $query->clear();

        $query->setDelete(COrder::_TBL_PREFIKS_ . "_modules");
        $query->addWhere("module_id = $this->id");

        return $query->exec();
    }

    /**
     * Increments the delivered value of this object by one. Also updates the database with the new value
     */
    public function addDelivery() {

        // Query the database and set a new value
        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_modules");
        $query->addUpdate("module_delivered", $this->delivered+1);
        $query->addWhere("module_id = $this->id");
        $query->exec();

        $this->delivered++;
    }

    /**
     * Protected constructor. Use static methods to create this object from different sources.
     * Also loads related objects like components and files from the database.
     *
     * @param array $values Parameters used to initialize object attributes
     */
    protected function __construct(array $values) {

        // Initialize object using the information in the parameters
        foreach($values as $key => $value) {
            $this->{$key} = $value;
        }

        // Load components related to this module, load before children to compute price!
        $this->loadComponents();

        // Load all files related to this module
        $this->loadFiles();
    }

    /**
     * Loads components related to this ID from database
     */
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

    /**
     * Load files related to this object from database
     */
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

    /**
     * Prepares an array recieved from the database and renames indices so the hash can be passed to the constructor.
     *
     * @param array $entry
     */
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

    /**
     * Fetches a list of COrderModules from the database. The filter can be used to specify a subset of modules.
     *
     * @static
     * @param int $offset
     * @param int $limit
     * @param array $filter
     * @return array
     */
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

    /**
     * Initializes a single object with the given ID from the database
     *
     * @static
     * @param $id
     * @return COrderModule
     */
    public static function createFromDb($id) {

        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_modules");
        $query->addQuery("*");
        $query->addWhere("module_id = $id");
        $result = $query->loadHash();
        self::fromPrepareDb($result);

        return new COrderModule($result);
    }

    /**
     * Creates a new module object and stores it in the database
     *
     * @static
     * @param $name
     * @param $description
     * @param $buildTime
     * @return bool
     */
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

    /**
     * Alters an existing module
     *
     * @static
     * @param $id
     * @param $name
     * @param $description
     * @param $buildTime
     * @return Handle
     */
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

    /**
     * Attaches a file to the specified module module
     *
     * @static
     * @param $id
     * @param $fileId
     * @return bool
     */
    public static function attachFile($id, $fileId) {

        $query = new w2p_Database_Query();
        $new = array(
            "file_id"   => $fileId,
            "module_id" => $id
        );
        return $query->insertArray(COrder::_TBL_PREFIKS_ . "_module_files", $new);
    }

    /**
     * Attaches a component to specified module
     *
     * @static
     * @param $id
     * @param $componentId
     * @param $amount
     * @return bool
     */
    public static function attachComponent($id, $componentId, $amount) {

        $query = new w2p_Database_Query();
        $new = array(
            "stored_component_id" => $componentId,
            "module_id" => $id,
            "amount" => $amount
        );

        return $query->insertArray(COrder::_TBL_PREFIKS_ . "_module_components", $new);
    }

    /**
     * Deletes a component from the specified module
     *
     * @static
     * @param $id
     * @param $componentId
     * @return Handle
     */
    public static function deleteComponent($id, $componentId) {

        $query = new w2p_Database_Query();
        $query->setDelete(COrder::_TBL_PREFIKS_ . "_module_components");
        $query->addWhere("stored_component_id = $componentId AND module_id = $id");
        return $query->exec();
    }

    /**
     * Calculates the next id based on the maximum id in the database
     *
     * @static
     * @return mixed
     */
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