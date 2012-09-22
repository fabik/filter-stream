<?php

namespace FilterStream;



/**
 * Filter stream.
 *
 * @author     Jan-Sebastian Fabik
 */
class FilterStream
{
	/** Filter Stream version identification */
	const NAME = 'Filter Stream',
		VERSION = '1.0-dev',
		REVISION = '$WCREV$ released on $WCDATE$';

	/** @var IFilter[] */
	protected static $filters = array();

	/** @var string   path without stream protocol */
	protected $path;

	/** @var resource  file or directory handle */
	protected $handle;

	/** @var string  filtered data */
	protected $data;

	/** @var int */
	protected $offset;

	/** @var int */
	protected $length;

	/** @var array */
	protected $stat;



	/**
	 * Registers a new stream with the given protocol and filter.
	 * @param  string
	 * @param  IFilter
	 * @return void
	 */
	public static function register($protocol, IFilter $filter)
	{
		if (isset(self::$filters[$protocol])) {
			throw new Exception("Protocol '$protocol' is already registered.");
		}
		stream_wrapper_register($protocol, __CLASS__);
		self::$filters[$protocol] = $filter;
	}



	/**
	 * Unregisters a stream with the given protocol.
	 * @param  string
	 * @return void
	 */
	public static function unregister($protocol)
	{
		if (!isset(self::$filters[$protocol])) {
			throw new Exception("Protocol '$protocol' has not been registered yet.");
		}
		stream_wrapper_unregister($protocol);
		unset(self::$filters[$protocol]);
	}




	/**
	 * Opens file.
	 * @param  string  path with stream protocol
	 * @param  string  mode - see fopen()
	 * @param  int     STREAM_USE_PATH, STREAM_REPORT_ERRORS
	 * @param  string  full path
	 * @return bool    TRUE on success or FALSE on failure
	 */
	public function stream_open($path, $mode, $options, &$opened_path)
	{
		list($protocol, $this->path) = explode('://', $path, 2);
		$filter = self::getFilter($protocol);
		$this->data = $filter->processFile($this->path);

		$flag = trim($mode, 'crwax+');  // text | binary mode
		$mode = trim($mode, 'tb');     // mode
		$usePath = (bool) ($options & STREAM_USE_PATH); // use include_path?

		if ($mode === 'r') {
			if ($this->data !== NULL) {
				$this->offset = 0;
				$this->length = strlen($this->data);

			} else {
				$handle = fopen($this->path, 'r' . $flag, $usePath);

				if (!$handle) {
					return FALSE;
				}

				$this->handle = $handle;
			}

			return TRUE;

		} elseif ($mode === 'r+' || $mode[0] === 'x' || $mode[0] === 'w'
			|| $mode[0] === 'a' || $mode[0] === 'c') {

			if ($this->data !== NULL) {
				trigger_error("Writing is allowed only for files that are not filtered.", E_USER_WARNING);
				return FALSE;

			} else {
				$handle = fopen($this->path, $mode . $flag, $usePath);

				if (!$handle) {
					return FALSE;

				} elseif (!flock($handle, LOCK_EX)) {
					fclose($handle);
					return FALSE;
				}

				$this->handle = $handle;
				return TRUE;
			}

		} else {
			trigger_error("Unsupported mode $mode.", E_USER_WARNING);
			return FALSE;
		}
	}



	/**
	 * Closes file.
	 * @return void
	 */
	public function stream_close()
	{
		if ($this->data === NULL) {
			fclose($this->handle);
		}

		unset($this->data, $this->handle);
	}



	/**
	 * Locks file.
	 * @param  int     LOCK_SH, LOCK_EX, LOCK_UN
	 * @return void
	 */
	public function stream_lock($operation)
	{
		if ($this->data === NULL) {
			 return flock($this->handle, $operation);

		} else {
			return TRUE;
		}
	}



	/**
	 * Reads up to length bytes from the file.
	 * @param  int     length
	 * @return string
	 */
	public function stream_read($length)
	{
		if ($this->data === NULL) {
			return fread($this->handle, $length);

		} else {
			if ($this->offset === $this->length) {
				return "";
			} else {
				$length = max($length, $this->length - $this->offset);
				$buffer = substr($this->data, $this->offset, $length);
				$this->offset += $length;
				return $buffer;
			}
		}
	}



	/**
	 * Writes the string to the file.
	 * @param  string  data to write
	 * @return int     number of bytes that were successfully stored
	 */
	public function stream_write($data)
	{
		if ($this->data === NULL) {
			return fwrite($this->handle, $data, strlen($data));

		} else {
			return 0;
		}
	}



	/**
	 * Flushes the output.
	 * @return bool    TRUE on success or FALSE on failure
	 */
	public function stream_flush()
	{
		if ($this->data === NULL) {
			 return fflush($this->handle);

		} else {
			return FALSE;
		}
	}



	/**
	 * Truncates file.
	 * @param  int     size
	 * @return bool    TRUE on success or FALSE on failure
	 */
	public function stream_truncate($size)
	{
		if ($this->data === NULL) {
			 return ftruncate($this->handle, $size);

		} else {
			return FALSE;
		}
	}



	/**
	 * Returns the position of the file.
	 * @return int
	 */
	public function stream_tell()
	{
		if ($this->data === NULL) {
			return ftell($this->handle);

		} else {
			return $this->offset;
		}
	}



	/**
	 * Returns TRUE if the file pointer is at end-of-file.
	 * @return bool
	 */
	public function stream_eof()
	{
		if ($this->data === NULL) {
			return feof($this->handle);

		} else {
			return $this->offset === $this->length;
		}
	}



	/**
	 * Sets the file position indicator for the file.
	 * @param  int     position
	 * @param  int     SEEK_SET, SEEK_CUR, SEEK_END
	 * @return bool    TRUE on success or FALSE on failure
	 */
	public function stream_seek($offset, $whence)
	{
		if ($this->data === NULL) {
			return fseek($this->handle, $offset, $whence) === 0;

		} else {
			switch ($whence) {
				case SEEK_SET:
					$this->offset = $offset;
					break;
				case SEEK_CUR:
					$this->offset += $offset;
					break;
				case SEEK_END:
					$this->offset = $this->length + $offset;
					break;
				default:
					return FALSE;
			}
			$this->offset = max(0, min($this->length, $this->offset));
			return TRUE;
		}
	}



	/**
	 * Gets information about a file referenced by $this->tempHandle.
	 * @return array
	 */
	public function stream_stat()
	{
		$stat = & $this->stat;
		if ($stat === NULL) {
			if ($this->data === NULL) {
				return fstat($this->handle);

			} else {
				$stat = stat($this->path);
				$stat[7] = $stat['size'] = $this->length;
			}
		}
		return $stat;
	}



	/**
	 * Gets information about a file referenced by filename.
	 * @param  string  path with stream protocol
	 * @param  int     STREAM_URL_STAT_LINK, STREAM_URL_STAT_QUIET
	 * @return array
	 */
	public function url_stat($path, $flags)
	{
		list($protocol, $path) = explode('://', $path, 2);
		$filter = self::getFilter($protocol);

		$stat = ($flags & STREAM_URL_STAT_LINK) ? @lstat($path) : @stat($path); // intentionally @

		return $stat;
	}



	/**
	 * Deletes a file.
	 * On Windows unlink is not allowed till file is opened
	 * @param  string  path
	 * @return bool    TRUE on success or FALSE on failure
	 */
	public function unlink($path)
	{
		list($protocol, $path) = explode('://', $path, 2);
		return unlink($path);
	}



	/**
	 * Opens directory.
	 * @param  string  path
	 * @param  int
	 * @return bool    TRUE on success or FALSE on failure
	 */
	public function dir_opendir($path, $options)
	{
		list($protocol, $path) = explode('://', $path, 2);

		$handle = opendir($path);

		if (!$handle) {
			return FALSE;
		}

		$this->handle = $handle;
		return TRUE;
	}



	/**
	 * Closes directory.
	 * @return bool    TRUE on success or FALSE on failure
	 */
	public function dir_closedir()
	{
		closedir($this->handle);
		return TRUE;
	}



	/**
	 * Reads entry from directory.
	 * @return string|FALSE
	 */
	public function dir_readdir()
	{
		return readdir($this->handle);
	}



	/**
	 * Rewinds directory handle.
	 * @return bool    TRUE on success or FALSE on failure
	 */
	public function dir_rewinddir()
	{
		rewinddir($this->handle);
		return TRUE;
	}



	/**
	 * Creates directory.
	 * @param  string  path
	 * @param  int     mode
	 * @param  int
	 * @return bool    TRUE on success or FALSE on failure
	 */
	public function mkdir($path, $mode, $options)
	{
		list($protocol, $path) = explode('://', $path, 2);
		return mkdir($path, $mode, $options & STREAM_MKDIR_RECURSIVE);
	}



	/**
	 * Removes directory.
	 * @param  string  path
	 * @param  int
	 * @return bool    TRUE on success or FALSE on failure
	 */
	public function rmdir($path, $options)
	{
		list($protocol, $path) = explode('://', $path, 2);
		return rmdir($path);
	}



	/**
	 * Renames file.
	 * @param  string  source path
	 * @param  string  target path
	 * @return bool    TRUE on success or FALSE on failure
	 */
	public function rename($source, $target)
	{
		list(, $source) = explode('://', $source, 2);
		list(, $target) = explode('://', $target, 2);
		return rename($source, $target);
	}



	/**
	 * Returns a filter for the given protocol.
	 * @param  string
	 * @return IFilter
	 */
	protected static function getFilter($protocol)
	{
		if (!isset(self::$filters[$protocol])) {
			trigger_error("Unsupported protocol $protocol.", E_USER_WARNING);
			return FALSE;
		}

		return self::$filters[$protocol];
	}
}
