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

    protected function __construct(array $values) {

        // Initialize object using the information in the parameters
        foreach($values as $key => $value) {
            $this->{$key} = $value;
        }

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