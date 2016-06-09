<?php
namespace mod_coursereadings\table;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

class compliance extends \table_sql {
    function col_type($row) {
        global $CFG;
        $type = 'other';
        switch($row->type) {
            case 'book':
                $type = 'book';
                break;
            case 'journal':
                $type = 'periodical/journal';
                break;
            case 'other':
                switch($row->subtype) {
                    case 'Newspaper':
                    case 'Website':
                        $type = strtolower($row->subtype);
                        break;
                }
                break;
        }
        return $type;
    }
    function col_subtype($row) {
        global $CFG;
        $subtype = 'unknown';
        switch($row->type) {
            case 'book':
                $subtype = 'chapter/part';
                break;
            case 'journal':
                $subtype = 'article';
                break;
            case 'other':
                switch($row->subtype) {
                    case 'Newspaper':
                    case 'Magazine':
                        $subtype = 'article';
                        break;
                    case 'Conference paper':
                    case 'Music':
                        $subtype = strtolower($row->subtype);
                        break;
                    case 'Website':
                        $subtype = 'webpage';
                        break;
                    case 'Artwork':
                        $subtype = 'artistic work';
                        break;
                }
                break;
        }
        return $subtype;
    }
    function col_publisher($row) {
        if (empty($row->publisher)) {
            return 'UNKNOWN';
        }
        return $row->publisher;
    }

    /**
     * Take the data returned from the db_query and go through all the rows,
     * first splitting out the page range into "from" and "to" values (generating
     * multiple data rows for multiple page ranges, per the CLNZ column specification),
     * then processing each col using either col_{columnname} method or other_cols
     * method or if other_cols returns NULL then put the data straight into the
     * table.
     *
     * @return void
     */
    function build_table() {

        if ($this->rawdata instanceof \Traversable && !$this->rawdata->valid()) {
            return;
        }
        if (!$this->rawdata) {
            return;
        }

        $range_delim = '/\s*(,|&| and |;|\.|\)\s+\()\s*/';
        $num_delim = '/\s*(-| to )\s*/';
        foreach ($this->rawdata as $row) {
            $row->pagefrom = '';
            $row->pageto = '';
            if (!empty($row->pagerange)) {
                // Clean off bits which aren't page numbers - surrounding brackets, "pp", etc.
                $ranges = preg_replace('/^\((.*)\)$/', '$1', $row->pagerange);
                $ranges = preg_replace('/(^|\s|\()(p[pg]?\.?|pages?)(\d|\s)/i', '$1$3', $ranges);
                // Split out sub-ranges, iterate over them adding rows where necessary.
                $ranges = preg_split($range_delim, $ranges);
                foreach ($ranges as $range) {
                    $frags = preg_split($num_delim, $range, 2);
                    $row->pagefrom = trim($frags[0]);
                    if (!empty($frags[1])) {
                        $row->pageto = trim($frags[1]);
                    } else {
                        // For single-page ranges, populate both from and to with same value.
                        $row->pageto = $row->pagefrom;
                    }
                    $formattedrow = $this->format_row($row);
                    $this->add_data_keyed($formattedrow,
                        $this->get_row_class($row));
                }
            } else {
                // Page range field is empty, just output a single row.
                $formattedrow = $this->format_row($row);
                $this->add_data_keyed($formattedrow,
                    $this->get_row_class($row));
            }
        }

        if ($this->rawdata instanceof \core\dml\recordset_walk ||
                $this->rawdata instanceof moodle_recordset) {
            $this->rawdata->close();
        }
    }

    /**
     * Overridden to use a custom Excel export format which outputs ISBN column as text, to avoid Excel display issues.
     */
    function export_class_instance($exportclass = null) {
        if (is_null($this->exportclass) && !empty($this->download) && $this->download === 'excel') {
            $classname = '\\mod_coursereadings\\table\\compliance_excel_export_format';
            $this->exportclass = new $classname($this);
            if (!$this->exportclass->document_started()) {
                $this->exportclass->start_document($this->filename);
            }
        }
        return parent::export_class_instance($exportclass);
    }
}