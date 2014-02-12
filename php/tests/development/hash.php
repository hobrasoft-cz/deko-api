<?php

/*
http://stackoverflow.com/questions/7616461/generate-a-hash-from-string-in-javascript-jquery
*/

/**
 * http://stackoverflow.com/questions/8804875/php-internal-hashcode-function
 */
function overflow32($value) {

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
 *
 */
function mb_str_split($string, $length = 1) {

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
 * http://eqcode.com/wiki/CharCodeAt
 */
function utf8_ord($char) {

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
/*
function h2($string) {

	$value = 0;
	$array = mb_str_split($string);
	foreach ($array as $char) {
		$value = overflow32(31 * $value + utf8_ord($char));
	} // foreach

	$hash = strtoupper(base_convert(abs($value), 10, 36));

	return $hash;
} // h2()
*/

function h2($string) {

	$value = 0;
	$array = mb_str_split($string);
	foreach ($array as $char) {
		$value = overflow32($value * 65599 + utf8_ord($char));
	} // foreach

	return strtoupper(base_convert(($value + 4294967296), 10, 36));
} // h2

//echo h2('Příliš žluťoučký kůň úpěl ďábelské ódy!')."\n";
$startime = microtime(TRUE);
//$h = h2('b847d4eb8966ca68874668c1a0002ab3')."\n";
  $h = h2('f23440027dca1cfc368d05ef13001d14')."\n";
//$h = h2('ěščřžýáíéťóďúůľĺĚŚĆŘŽÝÁÍÉŤÓĎÚŮĽĹ')."\n";
$endtime  = microtime(TRUE);
echo $h;
$duration = $endtime - $startime;
echo "Duration: {$duration}s\n";

/*
$startime = microtime(TRUE);
for ($x = 0; $x < 1000; ++$x) {
	h2('Příliš žluťoučký kůň úpěl ďábelské ódy!')."\n";
} // for
$endtime  = microtime(TRUE);

$duration = $endtime - $startime;
echo "UTF8 duration: {$duration}s\n";

$startime = microtime(TRUE);
for ($x = 0; $x < 1000; ++$x) {
	h2('b847d4eb8966ca68874668c1a0002ab3')."\n";
} // for
$endtime  = microtime(TRUE);

$duration = $endtime - $startime;
echo "ASCII duration: {$duration}s\n";
 */
