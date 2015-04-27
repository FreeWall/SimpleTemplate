<?php

/**
 * SimpleTemplate - Validate
 *
 * @author Michal Vaněk
 */

namespace SimpleTemplate;

class Validate {
	/**
	 * Check for numeric character(s).
	 * @param string
	 * @return bool
	 */
	public static function isNumber($value){
		return ctype_digit($value);
	}

	/**
	 * Finds whether a variable is a number or a numeric string.
	 * @param string
	 * @return bool
	 */
	public static function isNumeric($value){
		return is_numeric($value);
	}

	/**
	 * Check for empty string.
	 * @param string
	 * @return bool
	 */
	public static function isEmpty($value){
		return (!preg_match('/\S/',$value));
	}
}