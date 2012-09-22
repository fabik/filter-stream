<?php

namespace FilterStream;



/**
 * Provides files pre-processing.
 *
 * @author     Jan-Sebastian Fabik
 */
interface IFilter
{
	/**
	 * Filters the given file.
	 * @param  string
	 * @return string|NULL
	 */
	function processFile($path);
}
