<?php

// Perform initial minimal setup.
define('ABORT_AFTER_CONFIG', true);
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

// Debug levels - always keep the values in ascending order!
/** No warnings and errors at all */
define('DEBUG_NONE', 0);
/** Fatal errors only */
define('DEBUG_MINIMAL', E_ERROR | E_PARSE);
/** Errors, warnings and notices */
define('DEBUG_NORMAL', E_ERROR | E_PARSE | E_WARNING | E_NOTICE);
/** All problems except strict PHP warnings */
define('DEBUG_ALL', E_ALL & ~E_STRICT);
/** DEBUG_ALL with all debug messages and strict warnings */
define('DEBUG_DEVELOPER', E_ALL | E_STRICT);

if (stristr(PHP_OS, 'win') && !stristr(PHP_OS, 'darwin')) {
    $CFG->ostype = 'WINDOWS';
} else {
    $CFG->ostype = 'UNIX';
}
$CFG->os = PHP_OS;

// Define moodle_exception class so dmllib has something to subclas.
/**
 * Base Moodle Exception class
 *
 * Although this class is defined here, you cannot throw a moodle_exception until
 * after moodlelib.php has been included (which will happen very soon).
 *
 * @package    core
 * @subpackage lib
 * @copyright  2008 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class moodle_exception extends Exception {

    /**
     * @var string The name of the string from error.php to print
     */
    public $errorcode;

    /**
     * @var string The name of module
     */
    public $module;

    /**
     * @var mixed Extra words and phrases that might be required in the error string
     */
    public $a;

    /**
     * @var string The url where the user will be prompted to continue. If no url is provided the user will be directed to the site index page.
     */
    public $link;

    /**
     * @var string Optional information to aid the debugging process
     */
    public $debuginfo;

    /**
     * Constructor
     * @param string $errorcode The name of the string from error.php to print
     * @param string $module name of module
     * @param string $link The url where the user will be prompted to continue. If no url is provided the user will be directed to the site index page.
     * @param mixed $a Extra words and phrases that might be required in the error string
     * @param string $debuginfo optional debugging information
     */
    function __construct($errorcode, $module='', $link='', $a=NULL, $debuginfo=null) {
        if (empty($module) || $module == 'moodle' || $module == 'core') {
            $module = 'error';
        }

        $this->errorcode = $errorcode;
        $this->module    = $module;
        $this->link      = $link;
        $this->a         = $a;
        $this->debuginfo = is_null($debuginfo) ? null : (string)$debuginfo;

        if (get_string_manager()->string_exists($errorcode, $module)) {
            $message = get_string($errorcode, $module, $a);
            $haserrorstring = true;
        } else {
            $message = $module . '/' . $errorcode;
            $haserrorstring = false;
        }

        if (defined('PHPUNIT_TEST') and PHPUNIT_TEST and $debuginfo) {
            $message = "$message ($debuginfo)";
        }

        if (!$haserrorstring and defined('PHPUNIT_TEST') and PHPUNIT_TEST) {
            // Append the contents of $a to $debuginfo so helpful information isn't lost.
            // This emulates what {@link get_exception_info()} does. Unfortunately that
            // function is not used by phpunit.
            $message .= PHP_EOL.'$a contents: '.print_r($a, true);
        }

        parent::__construct($message, 0);
    }
}

// Quick debugging function - forces to log, no output.  Used by dmllib.
function debugging($message = '', $level = DEBUG_NORMAL, $backtrace = null) {
    global $CFG;

    if (empty($CFG->debug) || ($CFG->debug != -1 and $CFG->debug < $level)) {
        return false;
    }

    if (!isset($CFG->debugdisplay)) {
        $CFG->debugdisplay = ini_get_bool('display_errors');
    }

    if ($message) {
        if (!$backtrace) {
            $backtrace = debug_backtrace();
        }
        $from = format_backtrace_plain($backtrace);

        // Send the info to error log.
        error_log('Debugging: ' . $message . ' in '. PHP_EOL . $from);
    }
}

/**
 * Formats a backtrace ready for plaintext output.
 *
 * @param array $callers backtrace array, as returned by debug_backtrace().
 * @return string formatted backtrace, ready for output.
 */
function format_backtrace_plain($callers, $plaintext = false) {
    // do not use $CFG->dirroot because it might not be available in destructors
    $dirroot = dirname(dirname(dirname(__FILE__)));

    if (empty($callers)) {
        return '';
    }

    $from = '';
    foreach ($callers as $caller) {
        if (!isset($caller['line'])) {
            $caller['line'] = '?'; // probably call_user_func()
        }
        if (!isset($caller['file'])) {
            $caller['file'] = 'unknownfile'; // probably call_user_func()
        }
        $from .= '* line ' . $caller['line'] . ' of ' . str_replace($dirroot, '', $caller['file']);
        if (isset($caller['function'])) {
            $from .= ': call to ';
            if (isset($caller['class'])) {
                $from .= $caller['class'] . $caller['type'];
            }
            $from .= $caller['function'] . '()';
        } else if (isset($caller['exception'])) {
            $from .= ': '.$caller['exception'].' thrown';
        }
        $from .= "\n";
    }

    return $from;
}

require_once($CFG->libdir .'/moodlelib.php');       // Other general-purpose functions, such as optional_/required_param.
require_once($CFG->libdir .'/dmllib.php');          // Database access setup.
setup_DB(); // Actually initialise $DB.