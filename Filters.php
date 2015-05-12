<?php

/**
 * SimpleTemplate - Filters
 *
 * @author Michal Vaněk
 */

namespace SimpleTemplate;

class Filters {
	/**
	 * List of available filters.
	 * @var array
	 */
	private static $filters = array(
		"upper"      => "SimpleTemplate\\Filters::upper",
		"lower"      => "SimpleTemplate\\Filters::lower",
		"firstupper" => "SimpleTemplate\\Filters::firstUpper",
		"firstlower" => "SimpleTemplate\\Filters::firstLower",
		"truncate"   => "SimpleTemplate\\Filters::truncate",
		"repeat"     => "SimpleTemplate\\Filters::repeat",
		"date"       => "SimpleTemplate\\Filters::date",
		"number"     => "SimpleTemplate\\Filters::number",
		"round"      => "SimpleTemplate\\Filters::round",
		"toascii"    => "SimpleTemplate\\Filters::toAscii",
		"webalize"   => "SimpleTemplate\\Filters::webalize",
		"bytes"      => "SimpleTemplate\\Filters::bytes"
	);

	/**
	 * Apply filter to variable content.
	 * @param string variable
	 * @param string filter
	 * @return string filtered variable
	 */
	public static function applyFilter($s,$filter){
		$params = explode(":",$filter);
		$filter = strtolower(array_shift($params));
		array_unshift($params,$s);
		return (isset(self::$filters[$filter]) ? call_user_func_array(self::$filters[$filter],$params) : $s);
	}

	/**
	 * Add custom filter.
	 * @param string
	 * @param callback
	 */
	public static function addFilter($s,$callback){
		self::$filters[strtolower($s)] = $callback;
	}

	/**
	 * Convert to upper case.
	 * @param string
	 * @return string
	 */
	public static function upper($s){
		return mb_strtoupper($s,'UTF-8');
	}

	/**
	 * Convert to lower case.
	 * @param string
	 * @return string
	 */
	public static function lower($s){
		return mb_strtolower($s,'UTF-8');
	}

	/**
	 * Convert first character to upper case.
	 * @param string
	 * @return string
	 */
	public static function firstUpper($s){
		return self::upper(self::substring($s,0,1)).self::substring($s,1);
	}

	/**
	 * Convert first character to lower case.
	 * @param string
	 * @return string
	 */
	public static function firstLower($s){
		return self::lower(self::substring($s,0,1)).self::substring($s,1);
	}

	/**
	 * Truncates string to maximal length.
	 * @param string
	 * @param int
	 * @param string
	 * @return string
	 */
	public static function truncate($s,$maxLen,$append = "\xE2\x80\xA6"){
		if(Validate::isNumber($maxLen) && self::length($s) > $maxLen){
			$maxLen = $maxLen - self::length($append);
			if($maxLen < 1) return $append;
			else if(preg_match('#^.{1,'.$maxLen.'}(?=[\s\x00-/:-@\[-`{-~])#us',$s,$matches)) return $matches[0].$append;
			else return self::substring($s,0,$maxLen).$append;
		}
		return $s;
	}

	/**
	 * Repeat a string.
	 * @param string
	 * @param int
	 * @return string
	 */
	public static function repeat($s,$count){
		return str_repeat($s,(Validate::isNumber($count) ? $count : 1));
	}

	/**
	 * Returns date/time format.
	 * @param string|int|DateTime|DateInterval
	 * @param string format
	 * @return string
	 */
	public static function date($time,$format = null){
		if($time == null) return null;
		if(!isset($format)) $format = "d.m.Y";
		if($time instanceof \DateInterval) return $time->format($format);
		else if(Validate::isNumeric($time)){
			$time = new \DateTime('@'.$time);
			$time->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
		}
		else if(!$time instanceof \DateTime && !$time instanceof \DateTimeInterface){
			$time = new \DateTime($time);
		}
		return strpos($format,'%') === false ? $time->format($format) : strftime($format,$time->format('U'));
	}

	/**
	 * Format a number with grouped thousands.
	 * @param string
	 * @param int
	 * @param string
	 * @param string
	 * @return string
	 */
	public static function number($number,$decimals = 0,$dec_point = '.',$thousands_sep = ' '){
		return number_format((double)$number,(Validate::isNumber($decimals) ? $decimals : 0),$dec_point,$thousands_sep);
	}

	/**
	 * Rounds a float.
	 * @param string
	 * @return string
	 */
	public static function round($number,$precision = 0){
		return round($number,$precision);
	}

	/**
	 * Returns string without accents.
	 * @param string
	 * @return string
	 */
	public static function toAscii($s){
		$s = preg_replace('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{2FF}\x{370}-\x{10FFFF}]#u','',$s);
		$s = strtr($s,'`\'"^~?',"\x01\x02\x03\x04\x05\x06");
		$s = str_replace(
			array("\xE2\x80\x9E", "\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x9A", "\xE2\x80\x98", "\xE2\x80\x99", "\xC2\xB0"),
			array("\x03", "\x03", "\x03", "\x02", "\x02", "\x02", "\x04"), $s
		);
		if(class_exists('Transliterator') && $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII')){
			$s = $transliterator->transliterate($s);
		}
		if(ICONV_IMPL === 'glibc'){
			$s = str_replace(
				array("\xC2\xBB", "\xC2\xAB", "\xE2\x80\xA6", "\xE2\x84\xA2", "\xC2\xA9", "\xC2\xAE"),
				array('>>', '<<', '...', 'TM', '(c)', '(R)'), $s
			);
			$s = @iconv('UTF-8','WINDOWS-1250//TRANSLIT//IGNORE',$s);
			$s = strtr($s,"\xa5\xa3\xbc\x8c\xa7\x8a\xaa\x8d\x8f\x8e\xaf\xb9\xb3\xbe\x9c\x9a\xba\x9d\x9f\x9e"
				. "\xbf\xc0\xc1\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3"
				. "\xd4\xd5\xd6\xd7\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2\xe3\xe4\xe5\xe6\xe7\xe8"
				. "\xe9\xea\xeb\xec\xed\xee\xef\xf0\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xfe"
				. "\x96\xa0\x8b\x97\x9b\xa6\xad\xb7",
				"ALLSSSSTZZZallssstzzzRAAAALCCCEEEEIIDDNNOOOOxRUUUUYTsraaaalccceeeeiiddnnooooruuuuyt- <->|-."
			);
			$s = preg_replace('#[^\x00-\x7F]++#','',$s);
		}
		else $s = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
		$s = str_replace(array('`',"'",'"','^','~','?'),'',$s);
		return strtr($s,"\x01\x02\x03\x04\x05\x06",'`\'"^~?');
	}

	/**
	 * Returns cool URL form.
	 * @param string
	 * @return string
	 */
	public static function webalize($s){
		$s = self::toAscii($s);
		$s = preg_replace('#[^a-z0-9]+#i','-',$s);
		$s = trim($s,'-');
		return $s;
	}

	/**
	 * Returns human readable file size.
	 * @param string
	 * @param number
	 * @return string
	 */
	public static function bytes($s,$precision = 1){
		if($s == 0) return "0 B";
		if($s < 1024) return $s." B";
		$s /= 1024;
		if($s < 1024) return round($s,$precision)." kB";
		$s /= 1024;
		if($s < 1024) return round($s,$precision)." MB";
		$s /= 1024;
		if($s < 1024) return round($s,$precision)." GB";
		$s /= 1024;
		return round($s,$precision)." TB";
	}

	/**
	 * Returns UTF-8 string length.
	 * @param string
	 * @return string
	 */
	public static function length($s){
		return strlen(utf8_decode($s));
	}

	/**
	 * Returns a part of UTF-8 string.
	 * @param string
	 * @param int
	 * @param int
	 * @return string
	 */
	public static function substring($s,$start,$length = null){
		if($length === null) $length = self::length($s);
		if(function_exists('mb_substr')) return mb_substr($s,$start,$length,'UTF-8');
		return iconv_substr($s, $start, $length, 'UTF-8');
	}
}