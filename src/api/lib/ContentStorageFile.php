<?php
/**
 * Represents file in content storage
 */
interface ContentStorageFile
{
	/**
	 * Reads contents of specified length
	 *
	 * @param int $length length to be red
	 * @return string data
	 */
	public function read($length);
	
	/**
	 * Writes data to file
	 *
	 * @param string $data data to be written
	 */
	public function write($data);
	
	/**
	 * Returns if pointer is at EOF. For reading only.
	 *
	 * @return bool is pointer at EOF
	 */
	public function eof();
	
	/**
	 * Closes file
	 */
	public function close();
}