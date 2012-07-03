<?php
/**
 * OntoWiki test base file
 *
 * Sets the same include paths as OntoWiki uses and must be included
 * by all tests.
 *
 * @author     Norman Heino <norman.heino@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id: test_base.php 2327 2008-05-26 15:47:55Z norman.heino $
 */

define('BOOTSTRAP_FILE', basename(__FILE__));
define('ONTOWIKI_ROOT', realpath(dirname(__FILE__) . '/../..') . '/');
define('APPLICATION_PATH', ONTOWIKI_ROOT . 'application/');
define('APPLICATION_ENV', 'unittesting');
define('ONTOWIKI_REWRITE', false);
define('CACHE_PATH', ONTOWIKI_ROOT . 'cache'.DIRECTORY_SEPARATOR);

// path to tests
if (!defined('_TESTROOT')) {
    define('_TESTROOT', rtrim(dirname(__FILE__), '/') . '/');
}

// path to OntoWiki
define('_OWROOT', ONTOWIKI_ROOT);

// add libraries to include path
$includePath  = get_include_path()                              . PATH_SEPARATOR;
$includePath .= _TESTROOT                                       . PATH_SEPARATOR;
$includePath .= ONTOWIKI_ROOT . 'application/classes/'          . PATH_SEPARATOR;
$includePath .= ONTOWIKI_ROOT . 'libraries/'                    . PATH_SEPARATOR;

if (file_exists(ONTOWIKI_ROOT . 'libraries/Erfurt/Erfurt/App.php')) {
    $includePath .= ONTOWIKI_ROOT . 'libraries/Erfurt/' . PATH_SEPARATOR;
} else if (file_exists(ONTOWIKI_ROOT . 'libraries/Erfurt/library/Erfurt/App.php')) {
    $includePath .= ONTOWIKI_ROOT . 'libraries/Erfurt/library' . PATH_SEPARATOR;
}
set_include_path($includePath);

// start dummy session before any PHPUnit output
require_once 'Zend/Session/Namespace.php';
$session = new Zend_Session_Namespace('OntoWiki_Test');

// Zend_Loader for class autoloading
require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('OntoWiki_');
$loader->registerNamespace('Erfurt_');
$loader->registerNamespace('PHPUnit_');

/** OntoWiki */
require_once 'OntoWiki.php';
