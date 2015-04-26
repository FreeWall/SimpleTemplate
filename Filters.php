<?php
namespace SimpleTemplate;

class Filters {
	private static $filters = array(
		"upper"      => "SimpleTemplate\\Filters::upper",
		"lower"      => "SimpleTemplate\\Filters::lower",
		"firstupper" => "SimpleTemplate\\Filters::firstUpper",
		"truncate"   => "SimpleTemplate\\Filters::truncate",
		"repeat"     => "SimpleTemplate\\Filters::repeat",
		"date"       => "SimpleTemplate\\Filters::date",
		"number"     => "SimpleTemplate\\Filters::number"
	);

	public static function applyFilter($s,$filter){
		$params = explode(":",$filter);
		$filter = strtolower(array_shift($params));
		array_unshift($params,$s);
		return (isset(self::$filters[$filter]) ? call_user_func_array(Filters::$filters[$filter],$params) : $s);
	}

	public static function upper($s){
		return mb_strtoupper($s,'UTF-8');
	}

	public static function lower($s){
		return mb_strtolower($s,'UTF-8');
	}

	public static function firstUpper($s){
		return self::upper(self::substring($s,0,1)).self::substring($s,1);
	}

	public static function truncate($s,$maxLen,$append = "\xE2\x80\xA6"){
		if(Validate::isNumber($maxLen) && self::length($s) > $maxLen){
			$maxLen = $maxLen - self::length($append);
			if($maxLen < 1) return $append;
			else if(preg_match('#^.{1,'.$maxLen.'}(?=[\s\x00-/:-@\[-`{-~])#us',$s,$matches)) return $matches[0].$append;
			else return self::substring($s,0,$maxLen).$append;
		}
		return $s;
	}

	public static function repeat($s,$count){
		return str_repeat($s,(Validate::isNumber($count) ? $count : 1));
	}

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

	public static function number($number,$decimals = 0,$dec_point = '.',$thousands_sep = ' '){
		return number_format((double)$number,(Validate::isNumber($decimals) ? $decimals : 0),$dec_point,$thousands_sep);
	}

	public static function length($s){
		return strlen(utf8_decode($s));
	}

	public static function substring($s,$start,$length = null){
		if($length === null) $length = self::length($s);
		if(function_exists('mb_substr')) return mb_substr($s,$start,$length,'UTF-8');
		return iconv_substr($s, $start, $length, 'UTF-8');
	}
}
?>