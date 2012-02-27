<?php
require_once('ordermgmt.class.php');
require_once('lib/tcpdf/config/lang/eng.php');
require_once('lib/tcpdf/tcpdf.php');

/**
 * Description of COrderPDF
 *
 * @author Korkonius
 */
class COrderPDF {
    
    protected $pdf;
    
    public function __construct(COrder $order, w2p_Core_CAppUI $AppUI, $content) {

        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set up required variables
        $user = new CContact();
        $user->load($AppUI->user_id);
        $userFullName = $user->contact_first_name . " " . $user->contact_last_name;
        
        // Set up document metadata
        $this->pdf->SetCreator($userFullName);
        $this->pdf->SetTitle('Summary ' . $order->prefix . $order->id);
        $this->pdf->SetSubject('Summary of order created by the Order Management for Web2Project');
        $this->pdf->SetKeywords('Order,Quote,Bill');
        
        // Set default data as in example
        $this->pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'RS-Systems AS Faktura', 'by ' . $userFullName);
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
        
        // Output passed data
        $this->pdf->writeHTMLCell(0, 0, '', '', $content, 0, 1, 0, true, '', true);
        $this->pdf->Output('example_001.pdf', 'I');
        
    }
}

?>
