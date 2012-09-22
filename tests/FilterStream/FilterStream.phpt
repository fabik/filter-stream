<?php

/**
 * Test: FilterStream basic usage.
 *
 * @author     Jan-Sebastian Fabik
 * @package    FilterStream
 * @subpackage UnitTests
 */

use Nette\Caching\Storages\FileStorage,
	Nette\Utils\Finder,
	Nette\Utils\Strings,
	FilterStream\CallbackFilter,
	FilterStream\CachedFilter,
	FilterStream\FilterStream;

require __DIR__ . '/bootstrap.php';



$cacheStorage = new FileStorage(TEMP_DIR);

$filter = new CallbackFilter(function ($path) {
	if (basename($path) === 'error.txt') {
		return 'OK';
	} elseif (basename($path) === 'error.php') {
		return "<?php return 'OK';";
	} else {
		return NULL;
	}
});

$filter = new CachedFilter($filter, $cacheStorage);

FilterStream::register('filter', $filter);


$filesDir = 'filter://' . __DIR__ . '/files';


Assert::same('OK', file_get_contents("$filesDir/error.txt"));

Assert::same('OK', file_get_contents("$filesDir/ok.txt"));

Assert::same('OK', include "$filesDir/error.php");

Assert::same('OK', include "$filesDir/ok.php");


$files = array();
$dh = opendir($filesDir);
while ($item = readdir($dh)) {
	$files[] = $item;
}
closedir($dh);
Assert::true(in_array('ok.txt', $files));


$files = array();
foreach (new DirectoryIterator($filesDir) as $item) {
	$files[] = $item->getPathname();
}
Assert::true(in_array("$filesDir/ok.txt", $files));


$files = array();
foreach (Finder::findFiles('*.txt')->in($filesDir) as $item) {
	$files[] = $item->getPathname();
}
Assert::true(in_array("$filesDir/ok.txt", $files));


$files = glob("$filesDir/*.txt");
Assert::true(in_array("$filesDir/ok.txt", $files));
