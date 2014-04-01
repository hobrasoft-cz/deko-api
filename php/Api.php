<?php

/**
 * This software is intelectual property of Hobrasoft s.r.o. (http://hobrasoft.cz).
 * It is released under GNU Lesser General Public License.
 * 
 */
namespace Hobrasoft\Deko;

/* Include database library */
require_once __DIR__.'/Database/CouchDb.php';

/* Include document types definitions */
require_once __DIR__.'/Document/Document.php';
require_once __DIR__.'/Document/Company.php';
require_once __DIR__.'/Document/Event.php';
require_once __DIR__.'/Document/File.php';
require_once __DIR__.'/Document/Note.php';
require_once __DIR__.'/Document/Person.php';
require_once __DIR__.'/Document/Project.php';
require_once __DIR__.'/Document/Task.php';
require_once __DIR__.'/Document/Timesheet.php';
require_once __DIR__.'/Document/Zone.php';

/* Include structure definitions */
require_once __DIR__.'/Structure/Collection.php';
require_once __DIR__.'/Structure/Tree.php';

/* Include exceptions defintions */
require_once __DIR__.'/Exceptions/ApiException.php';

use Hobrasoft\Deko\Database,
    Hobrasoft\Deko\Document,
    Hobrasoft\Deko\Exceptions,
    Hobrasoft\Deko\Structure;

/** Default database host */
define('DEKO_API_DEFAULT_HOST', '127.0.0.1');
/** Default database port */
define('DEKO_API_DEFAULT_PORT', '5984');
/** Default database name */
define('DEKO_API_DEFAULT_DATABASE_NAME', 'deko');

/**
 * Class implements Deko API operations
 */
class Api {

	const
		/** Basic authentication method */
		AUTH_BASIC = 'AUTH_BASIC',
		/** Cookie authentication method */
		AUTH_COOKIE = 'AUTH_COOKIE',
		/** Memory caching mechanism */
		CACHE_MEMORY = 'CouchDbMemoryCache',
		/** File caching mechanism */
		CACHE_FILE = 'CouchDbFileCache',
		/** CURL HTTP adapter */
		HTTP_CURL = 'HTTP_CURL',
		/** Native HTTP adapter */
		HTTP_NATIVE_SOCKETS = 'HTTP_NATIVE_SOCKETS',
		/** HTTP schema */
		SCHEMA_HTTP = 'http:',
		/** HTTPS schema */
		SCHEMA_HTTPS = 'https:',
		/** Database view default URL path */
		URL_VIEWS = '/_design/lists/_view/';

	public
		$db = NULL;

	protected
		$authMethod = self::AUTH_BASIC,
		$cacheType = self::CACHE_MEMORY,
		$cacheDir = '/tmp',
		$database = DEKO_API_DEFAULT_DATABASE_NAME,
		$debug = FALSE,
		$debugInfo = array(),
		$host = DEKO_API_DEFAULT_HOST,
		$httpAdapter = self::HTTP_NATIVE_SOCKETS,
		$password = NULL,
		$port = DEKO_API_DEFAULT_PORT,
		$schema = self::SCHEMA_HTTP,
		$sslCertificate = NULL,
		$useCache = TRUE,
		$user = NULL,
		$useSsl = FALSE;

	/**
	 * Create API instance. Optionaly configuration (in array) can be specified.
	 *
	 * @public
	 * @param array $configuration Connection configuration
	 */
	public function __construct(array $configuration = array()) {

		if (!empty($configuration)) {
			$this->connect($configuration);
		} // if
	} // __construct()

	/**
	 * Destroy API instance.
	 *
	 * @public
	 */
	public function __destruct() {

		$this->disconnect();
	} // __destruct()

	/**
	 * Connect to database. Optionaly configuration (in array) can be specified.
	 *
	 * @public
	 * @param array $configuration Connection configuration
	 * @throw ApiException Connection error
	 */
	public function connect(array $configuration = array()) {

		/** Configuration setup */
		if (!empty($configuration)) {
			$this->setConfiguration($configuration);
		} // if

		/** Creation of database instance */
		$this->db = new Database\CouchDb($this->host, $this->port);
		if (!($this->db instanceof Database\CouchDb)) {
			throw new Exceptions\ApiException("Cannot create database class instance.", 1000);
		} // if

		try {
			/** HTTP adapter setup */
			$this->db->setHttpAdapter($this->httpAdapter);

			/** SSL setup */
			if ($this->useSsl == TRUE && !empty($this->sslCertificate)) {
				$this->db->useSsl(TRUE);
				$this->db->sslCert($this->sslCertificate);
				$this->schema = self::SCHEMA_HTTPS;
			} // if

			/** Cache setup */
			if ($this->useCache == TRUE) {
				switch ($this->cacheType) {

				case self::CACHE_FILE:
					$cache = new Database\CouchDbFileCache($this->cacheDir);
					break;

				default:
				case self::CACHE_MEMORY:
					$cache = new Database\CouchDBMemoryCache;
					break;
				} // switch
				$this->db->setCache($cache);
			} // if

			/** Database name setup */
			$this->db->setDatabase($this->database);

		} catch (\Exception $e) {
			throw new Exceptions\ApiException($e->getMessage(), $e->getCode());
		} // try
	} // connect()

	/**
	 * Disconnect from database.
	 * 
	 * @public
	 */
	public function disconnect() {

		unset($this->db);
	} // disconnect()

	/**
	 * Returns document by its id and optionaly its revision.
	 *
	 * @public
	 * @param string $documentId Document id
	 * @param string $documentRevision Revision revision id
	 * @throw ApiException when document not found, or other processing error
	 * @return object Document object
	 */
	public function get($documentId, $documentRevision = NULL) {

		$this->addDebugInfo("get({$documentId}, {$documentRevision})");

		if (!is_string($documentId) || strlen($documentId) < 1) {
			throw new Exceptions\ApiException("Document Id '{$documentId}' is invalid.", 1002);
		} // if

		$url  = "/{$documentId}";
		$url .= !empty($documentRevision)
			? "?rev={$documentRevision}"
			: '';

		$this->addDebugInfo("URL: {$this->schema}//{$this->host}:{$this->port}/{$this->database}{$url}");

		try {
			$response = $this->db->get($url);
		} catch (\Exception $e) {
			throw new Exceptions\ApiException($e->getMessage(), $e->getCode());
		} // try

		$this->checkResponse($response);

		return $this->documentFactory($response->body);
	} // get()


	/**
	 * Returns database authentication method.
	 *
	 * @public
	 * @return string Authentication method code
	 */
	public function getAuthMethod() {

		return $this->authMethod;
	} // getAuthMethod()

	/**
	 * Returns API configuration as array.
	 * 
	 * @public
	 * @return array Array with API configuration
	 */
	public function getConfiguration() {

		return array(
			'authMethod'     => $this->getAuthMethod(),
			'cacheType'      => $this->getCacheType(),
			'cacheDir'       => $this->getCacheDir(),
			'host'           => $this->getHost(),
			'database'       => $this->getDatabase(),
			'httpAdapter'    => $this->getHttpAdapter(),
			'password'       => $this->getPassword(),
			'port'           => $this->getPort(),
			'schema'         => $this->getSchema(),
			'sslCertificate' => $this->getSslCertificate(),
			'useCache'       => $this->getUseCache(),
			'user'           => $this->getUser(),
			'useSsl'         => $this->getUseSsl()
		); // array()
	} // getConfiguration()

	/**
	 * Returns used cache type.
	 *
	 * @public
	 * @return string Cache type
	 */
	public function getCacheType() {

		return $this->cacheType;
	} // getCacheType()

	/**
	 * Returns database name.
	 *
	 * @public
	 * @return string Database name
	 */
	public function getDatabase() {

		return $this->database;
	} // getDatabase()

	/**
	 * Returns debugging mode
	 *
	 * @public
	 * @return bool Debugging mode
	 */
	public function getDebug() {

		return $this->debug;
	} // getDebug()

	/**
	 * Returns debugging information as array.
	 *
	 * @public
	 * @return array Debugging informations
	 */
	public function getDebugInfo() {

		return $this->debugInfo;
	} // getDebugInfo()

	/**
	 * Returns database connection host name
	 *
	 * @public
	 * @return string Database host name
	 */
	public function getHost() {

		return $this->host;
	} // getHost()

	/**
	 * Returns database connection HTTP adapter name
	 *
	 * @public
	 * @return string HTTP adapter name
	 */
	public function getHttpAdapter() {

		return $this->httpAdapter;
	} // getHttpAdapter()

	/**
	 * Returns database connection password.
	 *
	 * @public
	 * @return string Database password
	 */
	public function getPassword() {

		return $this->password;
	} // getPassword()

	/**
	 * Returns Database connection port number
	 *
	 * @public
	 * @return integer Database port number
	 */
	public function getPort() {

		return $this->port;
	} // getPort()

	/**
	 * Returns used schema for database connection
	 *
	 * @public
	 * @return string Used HTTP schema
	 */
	public function getSchema() {

		return $this->schema;
	} // if

	/**
	 * Returns SSL certificate file name used by database connection.
	 *
	 * @public
	 * @return string SSL certificate file name
	 */
	public function getSslCertificate() {

		return $this->sslCertificate;
	} // getSslCertificate()

	/**
	 * Returns caching mode.
	 *
	 * @public
	 * @return bool Caching mode
	 */
	public function getUseCache() {

		return $this->useCache;
	} // getUseCache()

	/**
	 * Returns database connection user name.
	 *
	 * @public
	 * @return string Database user name
	 */
	public function getUser() {

		return $this->user;
	} // getUser()

	/**
	 * Returns database connection SSL mode
	 *
	 * @public
	 * @return bool Database SSL mode
	 */
	public function getUseSsl() {

		return $this->useSsl;
	} // getUseSsl()

	/**
	 * Login into database with user name, password and authentication method.
	 *
	 * @public
	 * @param string $user User name
	 * @param string $password Password
	 * @param string $authMethod Authentication method
	 * @throw ApiException on authentication error
	 */
	public function login($user = NULL, $password = NULL, $authMethod = NULL) {

		if (!empty($user)) {
			$this->setUser($user);
		} // if

		if (!empty($password)) {
			$this->setPassword($password);
		} // if

		if (!empty($authMethod)) {
			$this->setAuthMethod($authMethod);
		} // if
		try {
			$response = $this->db->login(
				$this->user,
				$this->password,
				$this->authMethod
			); // login()
		} catch (\SagCouchException $e) {
			throw new Exceptions\ApiException($e->getMessage(), $e->getCode());
		} // try

	} // login()

	/**
	 * Set authentication method. Use one of Api::AUTH_* constants.
	 *
	 * @public
	 * @param string $authMethod Authentication method
	 * @throw ApiException on invalid authentication method
	 */
	public function setAuthMethod($authMethod) {

		$validAuthMethods = array(
			self::AUTH_BASIC,
			self::AUTH_COOKIE
		); // array()

		if (!in_array($authMethod, $validAuthMethods)) {
			throw new Exceptions\ApiException("Authentication method '{$authMethod}' is invalid.", 1017);
		} // if

		$this->authMethod = $authMethod;
	} // setAuthMethod()

	/**
	 * Set API configuration as array.
	 *
	 * @public
	 * @param array $configuration API configuration
	 * @throw ApiException on invalid settings
	 */
	public function setConfiguration(array $configuration) {

		if (isset($configuration['authMethod'])) {
			$this->setAuthMethod($configuration['authMethod']);
		} // if

		if (isset($configuration['cacheType'])) {
			$this->setCacheType($configuration['cacheType']);
		} // if

		if (isset($configuration['cacheDir'])) {
			$this->setCacheDir($configuration['cacheDir']);
		} // if

		if (isset($configuration['database'])) {
			$this->setDatabase($configuration['database']);
		} // if

		if (isset($configuration['debug'])) {
			$this->setDebug($configuration['debug']);
		} // if

		if (isset($configuration['host'])) {
			$this->setHost($configuration['host']);
		} // if

		if (isset($configuration['httpAdapter'])) {
			$this->setHttpAdapter($configuration['httpAdapter']);
		} // if

		if (isset($configuration['password'])) {
			$this->setPassword($configuration['password']);
		} // if

		if (isset($configuration['port'])) {
			$this->setPort($configuration['port']);
		} // if

		if (isset($configuration['schema'])) {
			$this->setSchema($configuration['schema']);
		} // if

		if (isset($configuration['sslCertificate'])) {
			$this->sslCertificate($configuration['sslCertificate']);
		} // if

		if (isset($configuration['useCache'])) {
			$this->setUseCache($configuration['useCache']);
		} // if

		if (isset($configuration['user'])) {
			$this->setUser($configuration['user']);
		} // if

		if (isset($configuration['useSsl'])) {
			$this->setUseSsl($configuration['useSsl']);
		} // if

	} // setConfiguration()

	/**
	 * Set cache type. Use one of Api::CACHE_* constants.
	 *
	 * @public
	 * @param string $cacheType Cache type
	 * @throw ApiException on invalid cache type error
	 */
	public function setCacheType($cacheType) {

		$validCacheTypes = array(
			self::CACHE_FILE,
			self::CACHE_MEMORY
		); // array()

		if (!in_array($cacheType, $validCacheTypes)) {
			throw new Exceptions\ApiException("Cache type '{$cacheType}' is invalid.", 1019);
		} // if

		$this->cacheType = $cacheType;
	} // setHttpAdapter()

	/**
	 * Set cache directory. Use if CACHE_FILE type is set.
	 * Cache directory must be writable.
	 *
	 * @param string $cacheDir Cache directory
	 * @throw ApiException if directory doesn't exists or is not writable
	 */
	public function setCacheDir($cacheDir) {

		if (!is_string($cacheDir) || strlen($cacheDir) < 1) {
			throw new Exceptions\ApiException("Cache name '{$database}' is invalid.", 1011);
		} // if

		if (!($path = realpath($cacheDir))) {
			throw new Exceptions\ApiException("Cache directory '{$cacheDir}' doesn't exists.", 1011);
		}  // if

		if (!is_writeable($path)) {
			$permissions = fileperms($cacheDir);
			throw new Exceptions\ApiException(sprintf("Cache directory '%d' have insufficient perissions (%o) for write.", $path, $permissions));
		} // if

		$this->cacheDir = $path;
	} // setDatabase()

	/**
	 * Set database name.
	 *
	 * @public
	 * @param string $database Database name
	 * @throw ApiException on database name is invalid
	 */
	public function setDatabase($database) {

		if (!is_string($database) || strlen($database) < 1) {
			throw new Exceptions\ApiException("Database name '{$database}' is invalid.", 1011);
		} // if

		$this->database = $database;
	} // setDatabase()

	/**
	 * Set API debug mode.
	 *
	 * @param bool $debug Debug mode flag
	 */
	public function setDebug($debug) {

		$this->debug = (bool) $debug;
	} // setDebug()

	/**
	 * Set database host name.
	 *
	 * @public
	 * @param string $host Host name
	 * @throw ApiException on host name is invalid
	 */
	public function setHost($host) {

		if (!is_string($host) || strlen($host) < 1) {
			throw new Exceptions\ApiException("Host name '{$host}' is invalid.", 1012);
		} // if

		$this->host = $host;
	} // setHost()

	/**
	 * Set HTTP adapter. Use one of API::HTTP_* constants.
	 *
	 * @public
	 * @param string $httpAdapter HTTP adapter
	 * @throw ApiException on invalid HTTP adapter
	 */
	public function setHttpAdapter($httpAdapter) {

		$validHttpAdapters = array(
			self::HTTP_CURL,
			self::HTTP_NATIVE_SOCKETS
		); // array()

		if (!in_array($httpAdapter, $validHttpAdapters)) {
			throw new Excpetions\ApiException("HTTP adapter '{$httpAdapter}' is invalid.", 1013);
		} // if

		$this->httpAdapter = $httpAdapter;
	} // setHttpAdapter()

	/**
	 * Set password for database connection.
	 *
	 * @public
	 * @param string $password Database connection password
	 */
	public function setPassword($password) {

		$this->password = $password;
	} // setPassword()

	/**
	 * Set port number for database connection.
	 *
	 * @public
	 * @param integer $port Port number
	 * @throw ApiException on invalid database port
	 */
	public function setPort($port) {

		if (!is_numeric($port) || $port < 1 || $port > 65535) {
			throw new Exceptions\ApiException("Port number '{$port}' is invalid.", 1015);
		} // if

		$this->port = $port;
	} // setPort()

	/**
	 * Set schema for database connection.
	 *
	 * @public
	 * @param string $schema Schema for database connection
	 * @throw ApiException on invalid schema
	 */
	public function setSchema($schema) {

		$validSchemas = array(
			self::SCHEMA_HTTP,
			self::SCHEMA_HTTPS
		); // array()

		if (!in_array($schema, $validSchemas)) {
			throw new Exceptions\ApiException("Schema '{$schema}' is invalid.", 1016);
		} // if

		$this->schema = $schema;
	} // setSchema()

	/**
	 * Set SSL certificate file for database connection.
	 *
	 * @public
	 * @param string $sslCertificate SSL certificate file path
	 * @throw ApiException on invalid SSL certificate file path
	 */
	public function setSslCertificate($sslCertificate) {

		if (!is_string($sslCertificate) || strlen($sslCertificate) < 1) {
			throw new Exceptions\ApiException("SSL certificate path '{$sslCertificate}' is invalid.", 1017);
		} // if

		$this->sslCertificate = $sslCertificate;
	} // setSslCertificate()

	/**
	 * Set use cache flag.
	 *
	 * @public
	 * @param bool $useCache Use cache flag
	 */
	public function setUseCache($useCache = TRUE) {

		$this->useCache = (bool) $useCache;
	} // setUseCache

	/**
	 * Set database user name.
	 *
	 * @public
	 * @param string $user Database user name
	 * @throw ApiException on invalid user name
	 */
	public function setUser($user) {

		if (!empty($user) && (!is_string($user) || strlen($user) < 1)) {
			throw new Exceptions\ApiException("User name '{$user}' is invalid.", 1018); 
		} // if

		$this->user = $user;
	} // setUser()

	/**
	 * Set use SSL flag.
	 *
	 * @public
	 * @param bool $useSsl Use SSL flag
	 */
	public function setUseSsl($useSsl) {

		$this->useSsl = (bool) $useSsl;
	} // setUseSsl()

	/**
	 * Perform query to view and return data as array.
	 * For more information see http://wiki.apache.org/couchdb/HTTP_view_API
	 *
	 * @public
	 * @param string $name View name
	 * @param string|array $keys Key or array of keys of document to retrieve
	 * @param array $params Other parameters
	 * @throw ApiException on view query preparation or execution error
	 * @return array Array with data
	 */
	public function view($name, $keys = NULL, $params = array()) {

		if (!is_string($name) || strlen($name) < 1) {
			throw new Exceptions\ApiException("View name '{$name}' is invalid.", 1006);
		} // if

		$urlParts  = array();
		$debugInfo = array();

		if (is_string($keys)) {
			$urlParts[]  = 'key="'.urlencode($keys).'"';
			$debugInfo[] = 'key="'.$keys.'"';
		} elseif (is_array($keys)) {
			foreach ($keys as $key) {
				$keyStrings[] = '"'.$key.'"';
			} // if
			$urlParts[]  = 'keys='.urlencode('['.implode(',', $keyStrings).']');
			$debugInfo[] = 'keys=['.implode(',', $keyStrings).']';
		} elseif (!empty($keys)) {
			throw new Exceptions\ApiException("Unexpected keys value '".var_export($keys, TRUE)."'.", 1007);
		} // if

		if (isset($params['startkey'])) {
			$urlParts[]  = 'startkey='.urlencode($params['startkey']);
			$debugInfo[] = 'startkey='.$params['startkey'];
		} // if

		if (isset($params['startkey_docid'])) {
			$urlParts[]  = 'startkey_docid='.urlencode($params['startkey_docid']);
			$debugInfo[] = 'startkey='.$params['startkey_docid'];
		} // if

		if (isset($params['endkey'])) {
			$urlParts[]  = 'endkey='.urlencode($params['endkey']);
			$debugInfo[] = 'endkey='.$params['endkey'];
		} // if

		if (isset($params['endkey_docid'])) {
			$urlParts[]  = 'endkey_docid='.urlencode($params['endkey_docid']);
			$debugInfo[] = 'endkey_docid='.$params['endkey_docid'];
		} // if

		if (isset($params['limit'])) {
			$urlParts[]  = 'limit='.urlencode($params['limit']);
			$debugInfo[] = 'limit='.$params['limit'];
		} // if

		if (isset($params['stale'])) {
			$urlParts[]  = 'stale='.urlencode($params['stale']);
			$debugInfo[] = 'stale='.$params['stale'];
		} // if

		if (isset($params['descending'])) {
			$debugInfo[] = $urlParts[] = 'descending='.($params['descending'] === TRUE)
				? 'true'
				: 'false';
		} // if

		if (isset($params['skip'])) {
			$urlParts[]  = 'skip='.urlencode($params['skip']);
			$debugInfo[] = 'skip='.$params['skip'];
		} // if

		if (isset($params['group'])) {
			$debugInfo[] = $urlParts[] = 'group='.($params['group'] === TRUE)
				? 'true'
				: 'false';
		} // if

		if (isset($params['group_level'])) {
			$urlParts[]  = 'group_level='.urlencode($params['group_level']);
			$debugInfo[] = 'group_level='.$params['group_level'];
		} // if

		if (isset($params['reduce'])) {
			$debugInfo[] = $urlParts[] = 'reduce='.($params['reduce'] === FALSE)
				? 'true'
				: 'false';
		} // if

		if (isset($params['include_docs'])) {
			$debugInfo[] = $urlParts[] = 'include_docs='.($params['include_docs'] === TRUE)
				? 'true'
				: 'false';
		} // if

		if (isset($params['inclusive_end'])) {
			$debugInfo[] = $urlParts[] = 'inclusive_end='.($params['inclusive_end'] === TRUE)
				? 'true'
				: 'false';
		} // if

		if (isset($params['update_seq'])) {
			$debugInfo[] = $urlParts[] = 'update_seq='.($params['update_seq'] === TRUE)
				? 'true'
				: 'false';
		} // if

		$this->addDebugInfo("view({$name}, ".implode(', ', $debugInfo).")");

		if (substr($name, 0, 1) !== '_') {
			$url = self::URL_VIEWS."{$name}?".implode('&', $urlParts);
		} else {
			$url = "{$name}?".implode('', $urlParts);
		} // if

		$this->addDebugInfo("URL: {$this->schema}//{$this->host}:{$this->port}/{$this->database}{$url}");

		try {
			$response = $this->db->get($url);
		} catch (\Exception $e) {
			throw new Exceptions\ApiException($e->getMessage(), $e->getCode());
		} // try

		$this->checkResponse($response);

		return $response->body;
	} // view()

	/**
	 * Check if response is valid.
	 *
	 * @protected
	 * @param StdClass $response Response to check
	 * @throw ApiException on invalid response
	 */
	protected function checkResponse(\StdClass $response) {

		if ($response->headers->_HTTP->status != 200) {
			$matches = array();
			preg_match('#^HTTP/(\d\.\d)\s(\d)\s([\w\s\d]+)#i', $response->headers->_HTTP->raw, $matches);
			$message = $matches[3];

			throw new Exceptions\ApiException($message, $response->headers->_HTTP->status);
		} // if
	} // checkResponse

	/**
	 * Add debug information into stack (retrieved by getDebugInfo()).
	 *
	 * @protected
	 * @param string $info Debug information
	 */
	protected function addDebugInfo($info) {

		if ($this->debug === TRUE) {
			$this->debugInfo[] = $info;
		} // if
	} // addDebugInfo()

	/**
	 * Returns apropriate document type object from given data.
	 *
	 * @protected
	 * @param StdClass $data Document data
	 * @throw ApiException on invalid data
	 * @return object Object with apropriate document type
	 */
	protected function documentFactory($data) {

		if (!($data instanceof \StdClass) || empty($data)) {
			throw new Exceptions\ApiException("Document is malformed or empty.", 1003);
		} // if

		if (!isset($data->doctype) || empty($data->doctype)) {
			throw new Exceptions\ApiException("Document doesn't have any doctype, or doctype is empty.", 1004);
		} // if

		switch ($data->doctype) {
		case Document\Document::COMPANY:

			$document = $this->createCompany($data);
			break;

		case Document\Document::EVENT:

			$document = $this->createEvent($data);
			break;

		case Document\Document::FILE:

			$document = $this->createFile($data);
			break;

		case Document\Document::LINK:

			$document = $this->createLink($data);
			break;

		case Document\Document::NOTE:

			$document = $this->createNote($data);
			break;

		case Document\Document::PERSON:

			$document = $this->createPerson($data);
			break;

		case Document\Document::PROJECT:

			$document = $this->createProject($data);
			break;

		case Document\Document::TASK:

			$document = $this->createTask($data);
			break;

		case Document\Document::TIMESHEET:

			$document = $this->createTimesheet($data);
			break;

		case Document\Document::ZONE:

			$document = $this->createZone($data);
			break;

		default:
			throw new Exceptions\ApiException("Document type {$data->doctype} is invalid", 1005);
			break;
		} // switch

		return $document;
	} // documentFactory()

	/**
	 * Returns company type object from document.
	 *
	 * @public
	 * @param StdClass $data Document data
	 * @return object Object of company data type
	 */
	public function createCompany(\StdClass $data = NULL) {

		return new Document\Company($data, $this);
	} // createCompany()

	/**
	 * Returns event type object from document.
	 *
	 * @public
	 * @param StdClass $data Document data
	 * @return object Object of event data type
	 */
	public function createEvent(\StdClass $data = NULL) {

		return new Document\Event($data, $this);
	} // createEvent()

	/**
	 * Returns file type object from document.
	 *
	 * @public
	 * @param StdClass $data Document data
	 * @return object Object of file data type
	 */
	public function createFile(\StdClass $data = NULL) {

		return new Document\File($data, $this);
	} // createFile()

	/**
	 * Returns link type object from document.
	 *
	 * @public
	 * @param StdClass $data Document data
	 * @return object Object of link data type
	 */
	public function createLink(\StdClass $data = NULL) {

		return new Document\Link($data, $this);
	} // createLink()

	/**
	 * Returns note type object from document.
	 *
	 * @public
	 * @param StdClass $data Document data
	 * @return object Object of note data type
	 */
	public function createNote(\StdClass $data = NULL) {

		return new Document\Note($data, $this);
	} // createNote()

	/**
	 * Returns person type object from document.
	 *
	 * @public
	 * @param StdClass $data Document data
	 * @return object Object of person data type
	 */
	public function createPerson(\StdClass $data = NULL) {

		return new Document\Person($data, $this);
	} // createPerson()

	/**
	 * Returns project type object from document.
	 *
	 * @public
	 * @param StdClass $data Document data
	 * @return object Object of project data type
	 */
	public function createProject(\StdClass $data = NULL) {

		return new Document\Project($data, $this);
	} // createProject()

	/**
	 * Returns task type object from document.
	 *
	 * @public
	 * @param StdClass $data Document data
	 * @return object Object of task data type
	 */
	public function createTask(\StdClass $data = NULL) {

		return new Document\Task($data, $this);
	} // createTask()

	/**
	 * Returns timesheet type object from document.
	 *
	 * @public
	 * @param StdClass $data Document data
	 * @return object Object of timesheet data type
	 */
	public function createTimesheet(\StdClass $data = NULL) {

		return new Document\Timesheet($data, $this);
	} // createTimesheet()

	/**
	 * Returns zone type object from document.
	 *
	 * @public
	 * @param StdClass $data Document data
	 * @return object Object of zone data type
	 */
	public function createZone(\StdClass $data = NULL) {

		return new Document\Zone($data, $this);
	} // createZone()

	/**
	 * Return project id by its identifier (either project id or hash).
	 *
	 * @public
	 * @param string $identifier Project identifier (project id or hash)
	 * @return string Project id
	 */
	public function resolveProjectId($identifier) {

		try {

			return $this->getProjectIdByHash($identifier);
		} catch (Exceptions\ApiException $e) {
		} // try

		try {
			$result = $this->get($identifier);
			if (count($result->rows) > 0) {

				return $identifier;
			} // if
		} catch (Exceptions\ApiException $e) {
		} // try
	} // resolveProject()

	/**
	 * Returns project id by its hash
	 *
	 * @public
	 * @param string $hash Project hash
	 * @throw ApiException on invalid hash or nonexisting project
	 * @return string Project id
	 */
	public function getProjectIdByHash($hash) {

		if (!is_string($hash) || strlen($hash) === 0) {
			throw new Exceptions\ApiException("Invalid hash {$hash}.", 1020);
		} // if

		$result = $this->view(Document\Project::HASH_PROJECT_VIEW, $hash, array('skip' => 0));
		if (count($result->rows) === 0) {
			throw new Exceptions\ApiException("Project with hash {$hash} doesn't exists.", 404);
		} // if

		return $result->rows[0]->value;
	} // getProjectIdByHash()

	/**
	 * Returns project by it hash.
	 *
	 * @public
	 * @param string $hash Project hash
	 * @return object Project object
	 */
	public function getProjectByHash($hash) {

		return $this->get($this->getProjectIdByHash($hash));
	} // getProjectByHash()

	/**
	 * Returns project hash from its id.
	 *
	 * @public
	 * @param string $id Project id
	 * @return string Project hash
	 */
	public function getHashByProjectId($id) {

		if (!is_string($id) || strlen($id) === 0) {
			throw new Exceptions\ApiException("Invalid project id {$hash}.", 1020);
		} // if

		return Deko\Utils::dekoHash($id);
	} // getHashProjectId()

} // DekoApi
