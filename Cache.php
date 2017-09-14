<?php

/**
 * SimpleTemplate - Cache
 *
 * @author Michal Vaněk
 */

namespace SimpleTemplate;

/** Init cache folder */
Cache::$cacheFolder = CACHE_PATH;

class Cache {
	/** @var string */
	public static $cacheFolder;
	/** @var bool */
	private static $enabled = true;
	/** @var integer */
	private static $cacheAge = 3600;

	/**
	 * Cache settings.
	 * @param bool
	 */
	public static function setEnabled($bool){
		self::$enabled = (bool)$bool;
	}

	/**
	 * Set cache folder.
	 * @param path
	 */
	public static function setFolder($folder){
		self::$cacheFolder = $folder;
	}

	/**
	 * Try to load template cache file.
	 * @param hash template hash
	 * @return bool success/failure
	 */
	public static function loadTemplate($hash){
		if(!self::$enabled) return false;
		$cacheFile = self::$cacheFolder.$hash.".cache.tpl";
		if(file_exists($cacheFile)) return file_get_contents($cacheFile);
		return false;
	}

	/**
	 * Save template to cache file.
	 * @param hash template hash
	 * @return bool success/failure
	 */
	public static function saveTemplate($hash,$content){
		if(!self::$enabled) return false;
		$cacheFile = self::$cacheFolder.$hash.".cache.tpl";
		$fp = fopen($cacheFile,"w");
		fwrite($fp,$content);
		fclose($fp);
		return true;
	}

	/**
	 * Remove old template cache file
	 */
	public static function clearCacheFolder(){
		foreach(scandir(self::$cacheFolder) AS $values){
			if(strstr($values,".cache.tpl") && filemtime(self::$cacheFolder.$values)+self::$cacheAge < time()){
				@unlink(self::$cacheFolder.$values);
			}
		}
	}
}