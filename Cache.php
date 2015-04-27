<?php

/**
 * SimpleTemplate - Cache
 *
 * @author Michal Vaněk
 */

namespace SimpleTemplate;

/** Init cache folder */
Cache::$cacheFolder = __DIR__."/Cache/";

class Cache {
	/** @var string */
	public static $cacheFolder;
	/** @var bool */
	private static $enabled = true;

	/**
	 * Cache settings.
	 * @param bool
	 */
	public static function enabled($bool){
		self::$enabled = (bool)$bool;
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
}