<?php

define('CLI_SCRIPT', 1);
require_once(__DIR__.'/../../../config.php');
// require_once($CFG->libdir.'/pluginlib.php');
require_once($CFG->libdir.'/clilib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'preview' => false,
        'update' => false,
        'help' => false
    ),
    array(
        'h' => 'help',
        'u' => 'update',
        'p' => 'preview'
    )
);

if ($options['help']) {
    print_help();
}

if (!$options['preview'] && !$options['update']) {
    print_help();
}

// Get data.
$fields = "id, pagerange, totalpages";
$where = "pagerange IS NOT NULL AND pagerange <> '' AND totalpages IS NULL";
$records = $DB->get_records_select('coursereadings_article', $where, null, 'id ASC', $fields);

echo "ID        Total     Page range\n";
// Process data.
$calculated = 0;
$total = count($records);
$range_delim = '/\s*(,|&| and |;|\.|\)\s+\()\s*/';
$num_delim = '/\s*(-| to )\s*/';
foreach ($records as $row) {
    $totalpages = 0;
    $numeric = true;
    if (!empty(trim($row->pagerange))) {
        // Clean off bits which aren't page numbers - surrounding brackets, "pp", etc.
        $ranges = preg_replace('/^\((.*)\)$/', '$1', $row->pagerange);
        $ranges = preg_replace('/(^|\s|\()(p[pg]?\.?|pages?)(\d|\s)/i', '$1$3', $ranges);
        // Split out sub-ranges, iterate over them adding rows where necessary.
        $ranges = preg_split($range_delim, $ranges);
        foreach ($ranges as $range) {
            $frags = preg_split($num_delim, $range, 2);
            $pagefrom = trim($frags[0]);
            if (!empty($frags[1])) {
                $pageto = trim($frags[1]);
            } else {
                // For single-page ranges, populate both from and to with same value.
                $pageto = $pagefrom;
            }
            if (is_numeric($pagefrom) && is_numeric($pageto) && ($pageto >= $pagefrom)) {
                $totalpages += ($pageto - $pagefrom) + 1;
            } else {
                // Something non-numeric found - we can't be sure we're calculating properly, so skip.
                $numeric = false;
                break;
            }
        }
        if ($numeric) {
            $calculated++;
            echo str_pad($row->id, 9) . ' ' . str_pad($totalpages, 9) . $row->pagerange . "\n";
            if ($options['update']) {
                $DB->set_field('coursereadings_article', 'totalpages', $totalpages, array('id' => $row->id));
            }
        } else {
            echo str_pad($row->id, 9) . ' ' . str_pad('-', 9) . $row->pagerange . "\n";
        }
    }
}
echo "\n$calculated totals calculated from $total records.\n";

function print_help() {
    echo "Purges all copies of articles with copyright warning notices prepended.  Does not touch original copies.

Options:
-h, --help            Print out this help
-p, --preview         Display a preview of changes which will be made (don't save anything)
-u, --update          Actually make the changes displayed by --preview
";
    exit(0);
}