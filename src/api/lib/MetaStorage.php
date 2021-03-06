<?php
/**
 * This interface represents storage of metadata of users and files
 */
interface MetaStorage
{
	/**
	 * Fetches user from metadata storage
	 *
	 * @param string $hash sha256 made of user credentials
	 * @return MetaUser user data
	 */
	public function getUser($hash);

	/**
	 * Saves user data
	 *
	 * @param MetaUser $user
	 */
	public function setUser($user);
	
	
	/**
	 * Fetches data about specified path
	 *
	 * @param MetaUser $user path owner
	 * @param string $path path to fetch
	 * @returm MetaPath
	 */
	public function getPath($user, $path = null, $recursive = false);
	
	/**
	 * Fetches data about specified path
	 *
	 * @param MetaUser $user path owner
	 * @param int $id path to fetch
	 * @returm MetaPath
	 */
	public function getPathById($user, $id, $recursive = false);
	
	/**
	 * Saves folder data
	 *
	 * @param MetaUser $user
	 * @param MetaPath $folder
	 */
	public function setPath($user, $folder);
	
	/**
	 * Deletes path
	 *
	 * @param MetaUser $user
	 * @param MetaPath $folder
	 */
	public function deletePath($user, $folder);
	
	/**
	 * Loads file.
	 *
	 * @param MetaUser $user
	 * @param int $id
	 */
	public function getFileById($user, $id);
	
	/**
	 * Saves file data
	 *
	 * @param MetaUser $user
	 * @param MetaFile $file
	 */
	public function setFile($user, $file);
	
	/**
	 * Deletes file from meta storage
	 *
	 * @param MetaUser $user
	 * @param MetaFile $file
	 */
	public function deleteFile($user, $file);
	
	/**
	 * Adds new version to file
	 *
	 * @param MetaUser $user
	 * @param MetaFile $file
	 */
	public function addFileVersion($user, $file);
}
