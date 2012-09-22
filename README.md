Filter Stream
=============

Filter Stream is an utility for files pre-processing.

## Usage

```php
use Nette\Caching\Storages\FileStorage,
	FilterStream\CallbackFilter,
	FilterStream\CachedFilter,
	FilterStream\FilterStream;

$cacheStorage = new FileStorage(APP_DIR . '/../temp');

$filter = new CallbackFilter(function ($path) {
	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
	if ($ext === 'php') {
		$source = file_get_contents($path);
		// ...
		return $source;
	} else {
		return NULL;
	}
});

$filter = new CachedFilter($filter, $cacheStorage);

FilterStream::register('filter', $filter);
```
