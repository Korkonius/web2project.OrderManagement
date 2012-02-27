<?php
require_once('ordermgmt.class.php');
require_once('lib/tcpdf/config/lang/eng.php');
require_once('lib/tcpdf/tcpdf.php');
require_once('lib/tbs_class.php');

/**
 * Description of COrderPDF
 *
 * @author Korkonius
 */
class COrderPDF {
    
    protected $pdf;
    protected $user;
    protected $sender;
    protected $recipient;
    protected $order;
    
    // Fields that come in handy when creating strings
    protected $fullUserName;
    protected $orderFormatted;
    protected $emailLink;
    
    public function __construct(COrder $order, CCompany $sender, CCompany $recipient, w2p_Core_CAppUI $AppUI) {

        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set up required variables
        $this->user = new CContact();
        $this->user->load($AppUI->user_id);
        $this->order = $order;
        
        $this->sender = $sender;
        $this->recipient = $recipient;
        
        // Generate fields ready to merge
        $this->fullUserName = $this->user->contact_first_name . " " . $this->user->contact_last_name;
        $this->orderFormatted = $order->prefix . sprintf("%1$04d", $this->order->id);
        $this->email = $this->user->contact_email;
    }
    
    protected function prepare() {
        
        // Set up document metadata
        $this->pdf->SetCreator($this->userFullName);
        $this->pdf->SetTitle('Quote ' . $this->order->prefix . $this->order->id);
        $this->pdf->SetSubject('Quote request created by the Order Management for Web2Project');
        $this->pdf->SetKeywords('Order,Quote,Bill');
        
        // Set default data as in example
        $logoPath = '/../../../images/logo.png';
        $this->pdf->SetHeaderData($logoPath, PDF_HEADER_LOGO_WIDTH, "RS-Systems AS - Quote $this->orderFormatted", 'Contact: ' . $this->fullUserName . " ($this->email)");
        $this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        $this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->pdf->setLanguageArray($l);
        $this->pdf->setFontSubsetting(true);
        $this->pdf->SetFont('dejavusans', '', 14, '', true);
        $this->pdf->AddPage();
        
        $content = "Request for quotation";
        
        // Output passed data
        $this->pdf->writeHTMLCell(0, 0, '', '', $content, 0, 1, 0, true, '', true);
        
    }
    
    public function render() {
        
        $this->prepare();
        
        $this->pdf->Output('example_001.pdf', 'I');
    }
    
    public function save(string $path) {
        // TODO: Implement this!
    }
}

?>
