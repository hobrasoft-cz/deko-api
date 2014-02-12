<?php

require_once __DIR__.'/../../Api.php';

use Hobrasoft\Deko,
    Hobrasoft\Deko\Exceptions;

$configuration = array(
	'database' => 'hobrasoft',
	'debug'    => TRUE,
	'user'     => 'hofman',
	'password' => 'Bitov7+Sv8b',
	'authMethod' => 'AUTH_COOKIE'
);

try {
	$api = new Deko\Api($configuration);
	$api->login();

	$personHofik = $api->get('462f8508d3dd59e5f4833fad7e037549');
	var_dump($personHofik->timesheets);
	//$api->view('zones');
} catch (Exceptions\ApiException $e) {
	echo "EXCEPTION {$e->getCode()}\n";
	echo "{$e->getMessage()}\n";
	echo "{$e->getFile()} on line {$e->getLine()}\n";
	echo "Trace: ";
	var_dump($e->getTrace());
	if ($api->getDebug() === TRUE) {
		echo "Debug info:\n";
		$di = $api->getDebugInfo();
		foreach ($di as $info) {
			echo "{$info}\n";
		} // foreach
	} // if
} // try

unset($api);
