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

        // Load required template from template dir
        $this->phpexcel = PHPExcel_IOFactory::load(dirname(__FILE__). "/../templates/module_workbook_template.xlsx");
        $this->mainSheet = $this->phpexcel->getActiveSheet();

        // Set module as this module
        $this->module = $module;
    }

    public function writeModuleData() {

        $sheet = $this->mainSheet;
        $module = $this->module;
        $sheet->setCellValue('B2', $module->name);
        $sheet->setCellValue('B3', $module->description);

        // Write components
        if(!empty($module->components)) {

            // Set first row in table manually
            $row = 10;
            foreach($module->components as $component) {
                $sheet->insertNewRowBefore($row);
                $sheet->setCellValue('B'.$row, $component['amount']);
                $sheet->setCellValue('C'.$row, $component['catalog_number']);
                $sheet->setCellValue('D'.$row, $component['description']);
                $sheet->setCellValue('E'.$row, $component['local_price']*$component['amount']);
                $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode("kr #,##0.00");
                $row++;
            }
        }

        // Write total price formula
        $sheet->setCellValue('E'.$row, "=SUM(E8:E".($row-1).")");
        $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode("kr #,##0.00");
        $sheet->removeRow(9, 1); // Remove initial row in template
    }

    public function stream() {

        // Render file
        $this->writeModuleData();
        $name = $this->module->name;

        // Set headers for the browser to recognise Excel spreadsheet
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"$name.xlsx\"");
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($this->phpexcel, "Excel2007");
        $objWriter->save("php://output");
    }
}
