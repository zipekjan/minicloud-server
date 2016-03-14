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
	 * Fetches data about specified path
	 *
	 * @param MetaUser $user path owner
	 * @param string $path path to fetch
	 * @returm MetaPath
	 */
	public function getPath($user, $path = null);
	
	/**
	 * Saves user data
	 *
	 * @param MetaUser $user
	 */
	public function setUser($user);
	
	/**
	 * Saves file data
	 *
	 * @param MetaFile $file
	 */
	public function setFile($user, $file);
	
	/**
	 * Saves folder data
	 *
	 * @param MetaFolder $folder
	 */
	public function setFolder($user, $folder);
}
