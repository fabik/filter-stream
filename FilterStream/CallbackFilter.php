<?php

namespace FilterStream;



/**
 * Provides files pre-processing using a callback.
 *
 * @author     Jan-Sebastian Fabik
 */
class CallbackFilter implements IFilter
{
	/** @var callable */
	public $callback;



	/**
	 * @param  callable
	 */
	public function __construct($callback)
	{
		$this->callback = $callback;
	}



	/**
	 * Filters the given file.
	 * @param  string
	 * @return string|NULL
	 */
	public function processFile($path)
	{
		return call_user_func($this->callback, $path);
	}
}
