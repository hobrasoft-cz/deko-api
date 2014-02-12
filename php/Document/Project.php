<?php

/**
 * This software is intelectual property of Hobrasoft s.r.o. (http://hobrasoft.cz).
 * It is released under GNU Lesser General Public License.
 * 
 */
namespace Hobrasoft\Deko\Document;

use Hobrasoft\Deko,
    Hobrasoft\Deko\Structure;

class Project extends Document {

	const HASH_PROJECT_VIEW = 'hash-project';

	const PROJECT_HASH_VIEW = 'project-hash';

	public $companies;

	public $events;

	public $files;

	public $notes;

	public $persons;

	public $projects;

	public $projectsHashes;

	public $tasks;

	public $timesheets;

	protected $hash;

	protected $companiesLinks;

	protected $eventsLinks;

	protected $filesLinks;

	protected $notesLinks;

	protected $personsLinks;

	protected $projectsLinks;

	protected $tasksLinks;

	protected $timesheetsLinks;

	public function __construct(\StdClass $data, Deko\Api $api) {

		parent::__construct($data, $api);

		$this->hash = Deko\Utils::dekoHash($this->_id);
		$this->loadHashes();
	} // __construct()

	public function loadHashes() {

		if (empty($this->projectsLinks)) {

			return;
		} // if

		foreach ($this->projectsLinks as $project) {
			$this->projectsHashes[$project] = Deko\Utils::dekoHash($project);
		} // foreach
	} // loadHashes()

} // Project
