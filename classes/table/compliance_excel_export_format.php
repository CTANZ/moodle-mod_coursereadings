<?php
namespace mod_coursereadings\table;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

class compliance_excel_export_format extends \table_excel_export_format {
/*    var $fileextension = 'xls';

    function define_workbook() {
        global $CFG;
        require_once("$CFG->libdir/excellib.class.php");
        // Creating a workbook
        $this->workbook = new \MoodleExcelWorkbook("-");
    }*/
    function add_data($row) {
        if (empty($this->table->columns['isbn'])) {
            return parent::add_data($row);
        }

        $colnum = 0;
        $isbncol = $this->table->columns['isbn'];
        foreach ($row as $col=>$item) {
            if ($col === $isbncol) {
                // Force ISBN to be written as a string, to prevent weird Excel display issues.
                $this->worksheet->write_string($this->currentrow,$colnum,$item,$this->formatnormal);
            } else {
                $this->worksheet->write($this->currentrow,$colnum,$item,$this->formatnormal);
            }
            $colnum++;
        }
        $this->currentrow++;
        return true;
    }
}