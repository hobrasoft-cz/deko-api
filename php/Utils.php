<?php

namespace Hobrasoft\Deko;

/**
 * Implementation of supporting methods.
 */
class Utils {

	/**
	 * Splits multibyte encoded strings into array of characters.
	 * See more at http://www.php.net/manual/en/function.mb-split.php#99851
	 *
	 * @public
	 * @param string $string String to split
	 * @param integer $length Length of string part to be splited
	 * @return array Array of characters
	 */
	public static function mb_str_split($string, $length = 1) {

		mb_internal_encoding('UTF-8');
		mb_regex_encoding('UTF-8');

		if ($length < 1) {

			return FALSE;
		} // if

		$result = array();

		$mb_strlen = mb_strlen($string, 'UTF-8');
		for ($i = 0; $i < $mb_strlen; $i += $length) {
			$result[] = mb_substr($string, $i, $length);
		} // for

		return $result;
	} // mb_str_split()

	/**
	 * Returns UTF-8 character ordinary code
	 * See more at http://eqcode.com/wiki/CharCodeAt
	 *
	 * @public
	 * @param string $char Character to get code of
	 * @return mixed Integer value of character, or FALSE on fail
	 */
	public static function utf8_ord($char) {

		$length = strlen($char);

		if ($length <= 0) {

			return FALSE;
		} // if

		$h = ord($char{0});

		if ($h <= 0x7F) {

			return $h;
		} // if

		if ($h < 0xC2) {

			return FALSE;
		} // if

		if ($h <= 0xDF && $length > 1) {

			return ($h & 0x1F) << 6 | (ord($char{1}) & 0x3F);
		} // if

		if ($h <= 0xEF && $length > 2) {

			return ($h & 0x0F) << 12 | (ord($char{1}) & 0x3F) << 6 | (ord($char{2}) & 0x3F);
		} // if

		if ($h <= 0xF4 && $length > 3) {

			return ($h & 0x0F) << 18 | (ord($char{1}) & 0x3F) << 12 | (ord($char{2}) & 0x3F) << 6 | (ord($char{3}) & 0x3F);
		} // if

		return FALSE;
	} // utf8_ord()

	/**
	 * DekoHash uses SDBM hash algorithm with modification to ensure positive
	 * values and convert them into 36based alphabet with uppercase output.
	 *
	 * @public
	 * @param string $string String to calculate hash from
	 * @return string String representing hash of input
	 */
	public static function dekoHash($string) {

		$value = self::sdbm($string);

		return strtoupper(base_convert(($value + 4294967296), 10, 36));
	} // dekoHash()

	/**
	 * This simulates 32bit integer overflow on 64bit systems.
	 * http://stackoverflow.com/questions/8804875/php-internal-hashcode-function
	 * 
	 * @protected
	 * @param integer $value Integer number value
	 * @return integer Overflowed integer value
	 */
	protected static function overflow32($value) {

		$value = $value % 4294967296;
		if ($value > 2147483647) {

			return $value - 4294967296;
		} elseif ($value < -2147483648) {

			return $value + 4294967296;
		} else {

			return $value;
		} // if
	} // overflow32()

	/**
	 * PHP implementation of SDBM hash algorithm described at
	 * http://www.cse.yorku.ca/~oz/hash.html
	 *
	 * @protected
	 * @param string $string String to calculate hash from
	 * @return string String representing hash of input
	 */
	protected static function sdbm($string) {

		$value = 0;
		$array = self::mb_str_split($string);
		foreach ($array as $char) {
			$value = self::overflow32($value * 65599 + self::utf8_ord($char));
		} // foreach

		return $value;
	} // sdbmHash()

} // Utils
