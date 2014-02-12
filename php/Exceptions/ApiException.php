<?php

/**
 * This software is intelectual property of Hobrasoft s.r.o. (http://hobrasoft.cz).
 * It is released under GNU Lesser General Public License.
 * 
 * ApiException implementation
 */

namespace Hobrasoft\Deko\Exceptions;

class ApiException extends \Exception {

	public function __construct($message = '', $code = 0) {

		parent::__construct("DekoApi Error: {$message}", $code);
	} // __construct()

} // ApiException