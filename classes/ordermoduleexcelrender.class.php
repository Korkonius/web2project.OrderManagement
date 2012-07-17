<?php
require_once(dirname(__FILE__) . "/../lib/PHPExcel.php");

/**
 * This class takes a COrderModule instance as input and outputs the object serialized to an Excel Workbook. The
 * workbook can either be streamed directly to the browser, or it can be saved as a file on the server.
 */
class COrderModuleExcelRender
{
    protected $module;
    protected $phpexcel;
    protected $mainSheet;

    // Settings
    protected $headerFontColor = "FF000000";
    protected $headerBgColor   = "FFCCCCCC";

    public function __construct(COrderModule $module) {

        // Create required objects
        $this->phpexcel = new PHPExcel();
        $this->mainSheet = $this->phpexcel->getActiveSheet();

        // Set module as this module
        $this->module = $module;
    }

    public function processHeaders() {

        // Write module name and colour header cells
        $this->mainSheet->mergeCells('B2:E2');

        // Prepare module name cell
        $nameStyle = $this->mainSheet->getStyle('B2');
        $nameStyle->getFont()->getColor()->setARGB($this->headerFontColor);
        $nameStyle->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($this->headerBgColor);
        $this->mainSheet->setCellValue('B2', $this->module->name);
    }

    public function processFooter() {

    }

    public function stream() {

        // Render file
        $this->processHeaders();
        $name = $this->module->name;

        // Set headers for the browser to recognise Excel spreadsheet
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"$name.xlsx\"");
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($this->phpexcel, "Excel2007");
        $objWriter->save("php://output");
    }
}
