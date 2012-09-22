<?php

/**
 * Test: FilterStream basic usage.
 *
 * @author     Jan-Sebastian Fabik
 * @package    FilterStream
 * @subpackage UnitTests
 */

use Nette\Caching\Storages\FileStorage,
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



Assert::same('OK', file_get_contents('filter://' . __DIR__ . '/files/error.txt'));

Assert::same('OK', file_get_contents('filter://' . __DIR__ . '/files/ok.txt'));

Assert::same('OK', include 'filter://' . __DIR__ . '/files/error.php');

Assert::same('OK', include 'filter://' . __DIR__ . '/files/ok.php');
