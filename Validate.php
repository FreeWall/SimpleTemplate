<?php
namespace SimpleTemplate;

class Validate {
	public static function isNumber($value){
		return ctype_digit($value);
	}

	public static function isNumeric($value){
		return is_numeric($value);
	}

	public static function isEmpty($value){
		return (!preg_match('/\S/',$value));
	}
}
?>