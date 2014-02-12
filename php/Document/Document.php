<?php

/**
 * This software is intelectual property of Hobrasoft s.r.o. (http://hobrasoft.cz).
 * It is released under GNU Lesser General Public License.
 * 
 */
namespace Hobrasoft\Deko\Document;

use Hobrasoft\Deko,
    Hobrasoft\Deko\Structure;

abstract class Document implements \ArrayAccess {

	const COMPANY = 'company';

	const EVENT = 'event';

	const FILE = 'file';

	const LINK = 'link';

	const NOTE = 'note';

	const PERSON = 'person';

	const PROJECT = 'project';

	const TASK = 'task';

	const TIMESHEET = 'timesheet';

	const ZONE = 'zone';


	const LINKS_FROM_ME_VIEW = 'links-from-me';


	protected $data;

	protected $api;


	public function __construct(\StdClass $data, Deko\Api $api) {

		$this->data = (array) $data;
		$this->api   = $api;

		$this->loadLinks();
	} // __construct()


	public function __destruct() {

		unset($this->api);
		unset($this->data);
	} // __destruct()

	public function __get($name) {

		return $this->data[$name];
	} // __get()

	public function __set($name, $value) {

		$this->data[$name] = $value;
	} // __set()


	public function offsetExists($offset) {

		return isset($this->data[$offset]);
	} // if

	public function offsetGet($offset) {
		
		return $this->data[$offset];
	} // if

	public function offsetSet($offset, $value) {

		if (!empty($offset)) {
			$this->data[$offset] = $value;
		} // if
	} // offsetSet()

	public function offsetUnset($offset) {

		unset($this->data[$offset]);
	} // if


	public function loadCompanies() {

		$this->companies = new Structure\Collection;
		$this->loadDocuments($this->companies, $this->companiesLinks);
	} // loadCompanies()

	public function loadEvents() {

		$this->events = new Structure\Collection;
		$this->loadDocuments($this->events, $this->eventsLinks);
	} // loadEvents()

	public function loadFiles() {

		$this->files = new Structure\Collection;
		$this->loadDocuments($this->files, $this->filesLinks);
	} // loadFiles()

	public function loadNotes() {

		$this->notes = new Structure\Collection;
		$this->loadDocuments($this->notes, $this->notesLinks);
	} // loadNotes()

	public function loadPersons() {

		$this->persons = new Structure\Collection;
		$this->loadDocuments($this->persons, $this->personsLinks);
	} // loadPersons()

	public function loadProjects() {

		$this->projects = new Structure\Collection;
		$this->loadDocuments($this->projects, $this->projectsLinks);
	} // loadProjects()

	public function loadTasks() {

		$this->tasks = new Structure\Collection;
		$this->loadDocuments($this->tasks, $this->tasksLinks);
	} // loadTasks()

	public function loadTimesheets() {

		$this->timesheets = new Structure\Collection;
		$this->loadDocuments($this->timesheets, $this->timesheetsLinks);
	} // loadTimesheets()


	public function getCompaniesLinks() {

		return $this->companiesLinks;
	} // getCompaniesLinks()

	public function getEventsLinks() {

		return $this->eventsLinks;
	} // getEventsLinks()

	public function getFilesLinks() {

		return $this->filesLinks;
	} // getFilesLinks()

	public function getNotesLinks() {

		return $this->notesLinks;
	} // getNotesLinks()

	public function getPersonsLinks() {

		return $this->personsLinks;
	} // getPersonsLinks()

	public function getProjectsLinks() {

		return $this->projectsLinks;
	} // getProjectsLinks()

	public function getTasksLinks() {

		return $this->tasksLinks;
	} // getTasksLinks()

	public function getTimesheetsLinks() {

		return $this->timesheetsLinks;
	} // getTimesheetsLinks()


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

	protected function loadDocuments(Structure\Collection $collection, $links) {

		if (empty($links)) {

			return;
		} // if

		foreach ($links as $id) {
			$document = $this->api->get($id);
			$collection->addDocument($id, $document);
		} // foreach
	} // loadDocuments()

} // Document