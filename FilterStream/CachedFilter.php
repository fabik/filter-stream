<?php

namespace FilterStream;

use Nette\Caching\Cache,
	Nette\Caching\IStorage;



/**
 * Provides files pre-processing with a cache.
 *
 * @author     Jan-Sebastian Fabik
 */
class CachedFilter implements IFilter
{
	/** @var IFilter */
	public $filter;

	/** @var NCache */
	public $cache;



	/**
	 * @param  IFilter
	 * @param  IStorage
	 */
	public function __construct(IFilter $filter, IStorage $storage)
	{
		$this->filter = $filter;
		$this->cache = new Cache($storage, 'FilterStream.CachedFilter');
	}



	/**
	 * Filters the given file.
	 * @param  string
	 * @return string|NULL
	 */
	public function processFile($path)
	{
		$data = $this->cache->load($path);
		if ($data === NULL) {
			$data = $this->filter->processFile($path);
			if ($data !== NULL) {
				$this->cache->save($path, $data, array(
					Cache::CONSTS => 'FilterStream\FilterStream::REVISION',
					Cache::FILES => array($path)
				));
			}
		}
		return $data;
	}
}
