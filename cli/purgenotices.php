<?php

define('CLI_SCRIPT', 1);
require_once(__DIR__.'/../../../config.php');
// require_once($CFG->libdir.'/pluginlib.php');
require_once($CFG->libdir.'/clilib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'purge' => false,
        'list' => false,
        'help' => false
    ),
    array(
        'h' => 'help',
        'l' => 'list',
        'p' => 'purge'
    )
);

if ($options['help']) {
    print_help();
}

if (!$options['purge'] && !$options['list']) {
    print_help();
}

$fs = get_file_storage();

$files = $fs->get_area_files(context_system::instance()->id, 'mod_coursereadings', 'articleswithnotice', false, 'sortorder', false);

if ($options['list']) {
    echo "Content hash                             ID       File name\n";
}
foreach ($files as $file) {
    if ($options['list']) {
        echo $file->get_contenthash() . ' ' . str_pad($file->get_itemid(), 9) . $file->get_filename() . "\n";
    }
    if ($options['purge']) {
        $file->delete();
    }
}


function print_help() {
    echo "Purges all copies of articles with copyright warning notices prepended.  Does not touch original copies.

Options:
-h, --help            Print out this help
--list                List articles which may be purged (don't actually purge)
--purge               Actually purge the articles listed by --list
";
    exit(0);
}