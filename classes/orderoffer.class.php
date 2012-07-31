<?php
require_once(dirname(__FILE__) . "/order.class.php");
require_once(dirname(__FILE__) . "/ordermodule.class.php");
class COrderOffer
{

    // Primitive fields containing strings or numeric values
    public $id;
    public $created;
    public $cost;
    public $buildTime;
    public $targetDate;
    public $notes;
    public $totalCost = 0;
    public $history;

    // Id's referring to external fields
    public $ownerId, $contactId, $offeredById, $offeredToId, $projectId;

    // Composite fields containing complex objects
    public $project;    // CProject object with project information
    public $owner;     // CContact object with contact information
    public $contact;   // CContact object with contact information
    public $offeredBy; // CCompany object with owning company information
    public $offeredTo; // CCompany object with receiving company information
    public $modules;    // COrderModule array with modules associated with this object
    public $components = array(); // COrderStoredComponent array with a list of all components in this offer
    public $files;      // CFile array with files associated with this object

    const ID_FORMAT = "RSS-O-%1$04d";

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

        // Load dependencies
        $this->loadMemberObjects();
        $this->loadHistory();
    }

    protected function loadMemberObjects() {

        // Load contact information
        $this->owner = new CContact();
        $this->owner->load($this->contactId);
        $this->contact = new CContact();
        $this->contact->load($this->contactId);

        $this->offeredBy = new CCompany();
        $this->offeredBy->load($this->offeredById);
        $this->offeredTo = new CCompany();
        $this->offeredTo->load($this->offeredToId);

        $this->project = new CProject();
        $this->project->load($this->projectId);

        // Load modules
        $this->modules = COrderModule::createFromOfferId($this->id);
        foreach($this->modules as $module) {
            $this->totalCost += $module->totalPrice;

            // Loop through modules and add components from each module
            foreach($module->components as $component) {
                $id = $component['stored_component_id'];
                if(isset($this->components[$id])) {
                    $this->components[$id]['amount'] += $module->amount * $component['amount'];
                } else {
                    $this->components[$id] = $component;
                    $this->components[$id]['amount'] = $module->amount * $component['amount'];
                }
            }
        }

        // Load files
        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_offer_files");
        $query->addWhere("offer_id=$this->id");
        $results = $query->loadList();

        $files = array();
        foreach($results as $row) {
            $file = new CFile();
            $file->load($row["file_id"]);
            $files[] = $file;
        }

        $this->files = $files;
    }

    protected function loadHistory() {

        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_offers_history");
        $query->addQuery("*");
        $query->addWhere("offer_id = $this->id");
        $query->addOrder("history_id DESC");
        $this->history = $query->loadList();

        // Buffer user identities when loading the history
        for($i = 0; $i < count($this->history); $i++) { // TODO Consider cache scheme when doing this?
            $user = new CContact();
            $user->load($this->history[$i]['user_id']);
            $this->history[$i]['user'] = $user;
        }
    }

    public function getFormattedId() {
        return sprintf(self::ID_FORMAT, $this->id);
    }

    /**
     * Prepares an array recieved from the database and renames indices so the hash can be passed to the constructor.
     *
     * @param array $entry
     */
    protected static function fromPrepareDb(array & $entry) {

        // Array replacement values
        $colNames = array(
            "offer_id"      => "id",
            "build_time"    => "buildTime",
            "target_date"   => "targetDate",
            "offered_by"    => "offeredById",
            "offered_to"    => "offeredToId",
            "owner"         => "ownerId",
            "contact"       => "contactId",
            "project"       => "projectId"
        );
        foreach($colNames as $old => $new) {
            $entry[$new] = $entry[$old];
            unset($entry[$old]);
        }
    }

    public static function createFromDb($offerId){

        // Open database connection and ask it nicely for information
        $query = new w2p_Database_Query();
        $query->addTable(COrder::_TBL_PREFIKS_ . "_offers");
        $query->addQuery("*");
        $query->addWhere("offer_id = $offerId");
        $result = $query->loadHash();

        self::fromPrepareDb($result);

        return new COrderOffer($result);
    }
}
