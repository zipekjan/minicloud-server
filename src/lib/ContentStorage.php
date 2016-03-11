<?php
/**
 * This interface represents storage of raw file contents
 */
interface ContentStorage
{
	/**
	 * Loads file contents
	 *
	 * @param MetaFile $file
	 * @param string $mode file mode, rb or wb
	 * @return ContentStorageFile file
	 */
	public function getFile($file, $mode);
}