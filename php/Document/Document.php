<?php

/**
 * This software is intelectual property of Hobrasoft s.r.o. (http://hobrasoft.cz).
 * It is released under GNU Lesser General Public License.
 * 
 */
namespace Hobrasoft\Deko\Document;

use Hobrasoft\Deko,
    Hobrasoft\Deko\Structure;

/**
 * Base class for Deko documents.
 */
abstract class Document implements \ArrayAccess {

	const
		/** Company document type */
		COMPANY = 'company',
		/** Event document type */
		EVENT = 'event',
		/** File document type */
		FILE = 'file',
		/** Link document type */
		LINK = 'link',
		/** Note document type */
		NOTE = 'note',
		/** Person document type */
		PERSON = 'person',
		/** Project document type */
		PROJECT = 'project',
		/** Task document type */
		TASK = 'task',
		/** Timesheet document type */
		TIMESHEET = 'timesheet',
		/** Zone document type */
		ZONE = 'zone',

		/** Links-from-me view */
		LINKS_FROM_ME_VIEW = 'links-from-me';

	protected
		$data,
		$api;

	private
		$configuration;

	/**
	 * Base document constructor.
	 *
	 * @public
	 * @param StdClass $data Data of document to encapsulate
	 * @param Deko\Api $api Instance of Deko API
	 * @throws ApiException on links loading problems
	 */
	public function __construct(\StdClass $data, Deko\Api $api) {

		$this->data = (array) $data;
		$this->api   = $api;
		$this->loadLinks();
	} // __construct()

	/**
	 * Free resources.
	 *
	 * @public
	 */
	public function __destruct() {

		unset($this->api);
		unset($this->data);
	} // __destruct()

	/**
	 * Returns encapsulated document properties.
	 *
	 * @public
	 * @param string $name Property name
	 * @return mixed Property value
	 */
	public function __get($name) {

		return $this->data[$name];
	} // __get()

	/**
	 * Set encapsulated document properties.
	 *
	 * @public
	 * @param string $name Property name
	 * @param mixed $value Property value
	 */
	public function __set($name, $value) {

		$this->data[$name] = $value;
	} // __set()

	/**
	 * Check if given key exists.
	 * Implementation of ArrayAccess interface.
	 * http://www.php.net/manual/en/class.arrayaccess.php
	 *
	 * @public
	 * @param mixed $offset Key to check
	 * @return bool TRUE if key exists in array
	 */
	public function offsetExists($offset) {

		return isset($this->data[$offset]);
	} // if

	/**
	 * Return value of given key.
	 * Implementation of ArrayAccess interface.
	 * http://www.php.net/manual/en/class.arrayaccess.php
	 *
	 * @public
	 * @param mixed $offset Key of value to return
	 * @return bool Value of given key or NULL
	 */
	public function offsetGet($offset) {
		
		return $this->data[$offset];
	} // if

	/**
	 * Set value of given key.
	 * Implementation of ArrayAccess interface.
	 * http://www.php.net/manual/en/class.arrayaccess.php
	 *
	 * @public
	 * @param mixed $offset Key to set
	 * @param mixed $value Value to set
	 */
	public function offsetSet($offset, $value) {

		if (!empty($offset)) {
			$this->data[$offset] = $value;
		} // if
	} // offsetSet()

	/**
	 * Remove given key.
	 * Implementation of ArrayAccess interface.
	 * http://www.php.net/manual/en/class.arrayaccess.php
	 *
	 * @public
	 * @param mixed $offset Key to remove
	 */
	public function offsetUnset($offset) {

		unset($this->data[$offset]);
	} // offsetUnset()


	/**
	 * Load linked companies of document.
	 *
	 * @public
	 */
	public function loadCompanies() {

		$this->companies = new Structure\Collection;
		$this->loadDocuments($this->companiesLinks, $this->companies);
	} // loadCompanies()

	/**
	 * Load linked events of document.
	 *
	 * @public
	 */
	public function loadEvents() {

		$this->events = new Structure\Collection;
		$this->loadDocuments($this->eventsLinks, $this->events);
	} // loadEvents()

	/**
	 * Load linked files of document.
	 *
	 * @public
	 */
	public function loadFiles() {

		$this->files = new Structure\Collection;
		$this->loadDocuments($this->filesLinks, $this->files);
	} // loadFiles()

	/**
	 * Load linked notes of document.
	 *
	 * @public
	 */
	public function loadNotes() {

		$this->notes = new Structure\Collection;
		$this->loadDocuments($this->notesLinks, $this->notes);
	} // loadNotes()

	/**
	 * Load linked persons of document.
	 *
	 * @public
	 */
	public function loadPersons() {

		$this->persons = new Structure\Collection;
		$this->loadDocuments($this->personsLinks, $this->persons);
	} // loadPersons()

	/**
	 * Load linked projects of document.
	 *
	 * @public
	 */
	public function loadProjects() {

		$this->projects = new Structure\Collection;
		$this->loadDocuments($this->projectsLinks, $this->projects);
	} // loadProjects()

	/**
	 * Load linked tasks of document.
	 *
	 * @public
	 */
	public function loadTasks() {

		$this->tasks = new Structure\Collection;
		$this->loadDocuments($this->tasksLinks, $this->tasks);
	} // loadTasks()

	/**
	 * Load linked timesheets of document.
	 *
	 * @public
	 */
	public function loadTimesheets() {

		$this->timesheets = new Structure\Collection;
		$this->loadDocuments($this->timesheetsLinks, $this->timesheets);
	} // loadTimesheets()


	/**
	 * Return document companies links
	 *
	 * @public
	 * @return array Array of companies links
	 */
	public function getCompaniesLinks() {

		return $this->companiesLinks;
	} // getCompaniesLinks()

	/**
	 * Return document events links
	 *
	 * @public
	 * @return array Array of events links
	 */
	public function getEventsLinks() {

		return $this->eventsLinks;
	} // getEventsLinks()

	/**
	 * Return document files links
	 *
	 * @public
	 * @return array Array of files links
	 */
	public function getFilesLinks() {

		return $this->filesLinks;
	} // getFilesLinks()

	/**
	 * Return document notes links
	 *
	 * @public
	 * @return array Array of notes links
	 */
	public function getNotesLinks() {

		return $this->notesLinks;
	} // getNotesLinks()

	/**
	 * Return document persons links
	 *
	 * @public
	 * @return array Array of persons links
	 */
	public function getPersonsLinks() {

		return $this->personsLinks;
	} // getPersonsLinks()

	/**
	 * Return document projects links
	 *
	 * @public
	 * @return array Array of projects links
	 */
	public function getProjectsLinks() {

		return $this->projectsLinks;
	} // getProjectsLinks()

	/**
	 * Return document tasks links
	 *
	 * @public
	 * @return array Array of tasks links
	 */
	public function getTasksLinks() {

		return $this->tasksLinks;
	} // getTasksLinks()

	/**
	 * Return document timesheets links
	 *
	 * @public
	 * @return array Array of timesheets links
	 */
	public function getTimesheetsLinks() {

		return $this->timesheetsLinks;
	} // getTimesheetsLinks()


	/**
	 * Perform loading links and their sorting.
	 *
	 * @protected
	 */
	protected function loadLinks() {

		if (empty($this->data['_id'])) {

			return;
		} // if

		$links = $this->api->view(self::LINKS_FROM_ME_VIEW, $this->_id);
		if ($links->total_rows === 0) {

			return;
		} // if

		foreach ($links->rows as $link) {
			$value = $link->value;

			switch ($value->doctype) {

			case self::COMPANY:
				$this->companiesLinks[] = $value->docid;
				break;

			case self::EVENT:
				$this->eventsLinks[] = $value->docid;
				break;

			case self::FILE:
				$this->filesLinks[] = $value->docid;
				break;

			case self::NOTE:
				$this->notesLinks[] = $value->docid;
				break;

			case self::PERSON:
				$this->personsLinks[] = $value->docid;
				break;

			case self::PROJECT:
				$this->projectsLinks[] = $value->docid;
				break;

			case self::TASK:
				$this->tasksLinks[] = $value->docid;
				break;

			case self::TIMESHEET:
				$this->timesheetsLinks[] = $value->docid;
				break;
			} // switch
		} // foreach

	} // loadLinks()

	/**
	 * Perform loading linked documents into given collection.
	 *
	 * @protected
	 * @param array $links Links to load
	 * @param Structure\Collection $collection Collection to load documents into
	 */
	protected function loadDocuments($links, Structure\Collection $collection) {

		if (empty($links)) {

			return;
		} // if

		foreach ($links as $id) {
			$document = $this->api->get($id);
			$collection->addDocument($id, $document);
		} // foreach
	} // loadDocuments()

} // Document
