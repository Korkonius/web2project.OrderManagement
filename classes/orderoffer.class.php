<?php
require_once(dirname(__FILE__) . "/order.class.php");
class COrderOffer
{

    // Primitive fields containing strings or numeric values
    public $id;
    public $created;
    public $cost;
    public $buildTime;
    public $targetDate;
    public $notes;
    public $totalCost;

    // Id's referring to external fields
    public $ownerId, $contactId, $offeredById, $offeredToId, $projectId;

    // Composite fields containing complex objects
    public $project;    // CProject object with project information
    public $owner;     // CContact object with contact information
    public $contact;   // CContact object with contact information
    public $offeredBy; // CCompany object with owning company information
    public $offeredTo; // CCompany object with receiving company information

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
    }

    protected function loadMemberObjects() {

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