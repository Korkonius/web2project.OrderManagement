<?php
require_once('ordermgmt.class.php');
require_once(dirname(__FILE__) . '/../lib/tcpdf/config/lang/eng.php');
require_once(dirname(__FILE__) . '/../lib/tcpdf/tcpdf.php');
require_once(dirname(__FILE__) . '/../lib/tbs_class.php');

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
    protected $fullCountryName;
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
        $this->email = $this->user->contact_email;
        $countries = w2PgetSysVal('GlobalCountries');
        $this->fullCountryName = $countries[$this->sender->company_country];
        
    }
    
    protected function prepare() {
        
        $oidf = $this->order->getFormattedId();
        
        // Set up document metadata
        $this->pdf->SetCreator($this->userFullName);
        $this->pdf->SetTitle('Quote ' . $this->order->prefix . $this->order->id);
        $this->pdf->SetSubject('Quote request created by the Order Management for Web2Project');
        $this->pdf->SetKeywords('Order,Quote,Bill');
        
        // Set default data as in example
        $logoPath = PDF_HEADER_LOGO;
        $this->pdf->SetHeaderData($logoPath, PDF_HEADER_LOGO_WIDTH, "Reference $oidf", '');
        $this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        $this->pdf->SetAutoPageBreak(FALSE, PDF_MARGIN_BOTTOM);
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->pdf->setLanguageArray($l);
        $this->pdf->setFontSubsetting(true);
        $this->pdf->SetFont('helvetica', '', 12, '', true);
        $this->pdf->AddPage();
        
        // Create content from template
        $template =& new clsTinyButStrong();
        $template->LoadTemplate(dirname(__FILE__) . '/templates/quote_template.html');
        
        // Merge all fields
        $template->MergeBlock('components', $this->order->getComponents());
        $template->MergeField('order', $this->order);
              
        // Make sure all automatic fields are merged and set content
        $template->Show(TBS_NOTHING);
        $content = $template->Source;
        
        // Output passed data
        $this->pdf->writeHTMLCell(0, 0, '', '', $content, 0, 1, 0, true, '', true);
        
        $template->LoadTemplate(dirname(__FILE__) . '/templates/quote_template_contact.html');
        $template->MergeField('sender', $this->sender);
        $template->MergeField('person', $this->user);
        $template->MergeField('senderFullCountry', $this->fullCountryName);
        
        $template->Show(TBS_NOTHING);
        $content = $template->Source;
        
        // Insert contact information at bottom of last page
        $this->pdf->writeHTMLCell(0, 0, '', 232.0, $content, 'T', 1, 0, true, true);
        
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
