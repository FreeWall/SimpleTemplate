<?php
namespace SimpleTemplate;

Cache::$cacheFolder = __DIR__."/Cache/";

class Cache {
	public static $cacheFolder;
	private static $enabled = true;

	public static function enabled($bool){
		self::$enabled = (bool)$bool;
	}

	public static function loadTemplate($hash){
		if(!self::$enabled) return false;
		$cacheFile = self::$cacheFolder.$hash.".cache.tpl";
		if(file_exists($cacheFile)) return file_get_contents($cacheFile);
		return false;
	}

	public static function saveTemplate($hash,$content){
		if(!self::$enabled) return false;
		$cacheFile = self::$cacheFolder.$hash.".cache.tpl";
		$fp = fopen($cacheFile,"w");
		fwrite($fp,$content);
		fclose($fp);
		return true;
	}
}
?>