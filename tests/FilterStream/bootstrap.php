<?php

/**
 * Test initialization and helpers.
 *
 * @author     Jan-Sebastian Fabik
 * @package    FilterStream\Test
 */

use Nette\Diagnostics\Debugger;

require __DIR__ . '/../../vendor/nette/tester/Tester/bootstrap.php';
require __DIR__ . '/../../vendor/nette/nette/Nette/loader.php';
require __DIR__ . '/../../FilterStream/loader.php';


// configure environment
date_default_timezone_set('Europe/Prague');


// temporary directory garbage collection
if (lcg_value() < 0.01) {
	foreach (glob(__DIR__ . '/../log/*[0-9]', GLOB_ONLYDIR) as $dir) {
		if (time() - @filemtime($dir) > 300 && @rename($dir, $dir . '-delete')) {
			TestHelpers::purge($dir . '-delete');
			rmdir($dir . '-delete');
		}
	}
	foreach (glob(__DIR__ . '/../tmp/*[0-9]', GLOB_ONLYDIR) as $dir) {
		if (time() - @filemtime($dir) > 300 && @rename($dir, $dir . '-delete')) {
			TestHelpers::purge($dir . '-delete');
			rmdir($dir . '-delete');
		}
	}
}


// create log directory debugger
define('LOG_DIR', __DIR__ . '/../log/' . getmypid());
TestHelpers::purge(LOG_DIR);


// create temporary directory
define('TEMP_DIR', __DIR__ . '/../tmp/' . getmypid());
TestHelpers::purge(TEMP_DIR);


// enable debugger
Debugger::enable(Debugger::DEVELOPMENT, LOG_DIR);


// configure environment
$_SERVER = array_intersect_key($_SERVER, array_flip(array('PHP_SELF', 'SCRIPT_NAME', 'SERVER_ADDR', 'SERVER_SOFTWARE', 'HTTP_HOST', 'DOCUMENT_ROOT', 'OS', 'argc', 'argv')));
$_SERVER['REQUEST_TIME'] = 1234567890;
$_ENV = $_GET = $_POST = array();

if (PHP_SAPI !== 'cli') {
	header('Content-Type: text/plain; charset=utf-8');
}


// xdebug
if (extension_loaded('xdebug')) {
	xdebug_disable();
	TestHelpers::startCodeCoverage(__DIR__ . '/coverage.dat');
}


function id($val) {
	return $val;
}
