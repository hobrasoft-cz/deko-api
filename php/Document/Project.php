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
 * Project document class.
 */
class Project extends Document {

	const
		HASH_PROJECT_VIEW = 'hash-project',
		PROJECT_HASH_VIEW = 'project-hash';

	public
		$companies,
		$events,
		$files,
		$notes,
		$persons,
		$projects,
		$projectsHashes,
		$tasks,
		$timesheets;

	protected
		$hash,
		$companiesLinks,
		$eventsLinks,
		$filesLinks,
		$notesLinks,
		$personsLinks,
		$projectsLinks,
		$tasksLinks,
		$timesheetsLinks;

	/**
	 * Project document constructor.
	 *
	 * @public
	 * @param StdClass Data of document to encapsulate
	 * @param Deko\Api Intance of Deko API
	 */
	public function __construct(\StdClass $data, Deko\Api $api) {

		parent::__construct($data, $api);

		$this->hash = Deko\Utils::dekoHash($this->_id);
		$this->loadHashes();
	} // __construct()

	/**
	 * Loads hashes of linked (sub)projects.
	 *
	 * @public
	 */
	public function loadHashes() {

		if (empty($this->projectsLinks)) {

			return;
		} // if

		foreach ($this->projectsLinks as $project) {
			$this->projectsHashes[$project] = Deko\Utils::dekoHash($project);
		} // foreach
	} // loadHashes()

} // Project
