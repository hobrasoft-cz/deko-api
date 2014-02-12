<?php

/**
 * This software is intelectual property of Hobrasoft s.r.o. (http://hobrasoft.cz).
 * It is released under GNU Lesser General Public License.
 * 
 */
namespace Hobrasoft\Deko\Structure;

use Hobrasoft\Deko;

class Collection implements \Iterator, \ArrayAccess {

	protected $collection = array();

	protected $currentKey = NULL;

	public function __construct(array $items = array()) {

		if (!empty($items)) {
			$this->collection = $items;
			$this->rewind();
		} // if
	} // __construct()

	public function rewind() {

		reset($this->collection);
		$this->currentKey = key($this->collection);
	} // rewind()

	public function current() {

		return $this->collection[$this->currentKey];
	} // current()

	public function key() {

		$this->currentKey = key($this->collection);

		return $this->currentKey;
	} // key()

	public function next() {

		next($this->collection);
		$this->key();
	} // next()

	public function valid() {

		return isset($this->collection[$this->currentKey]);
	} // valid()

	public function offsetExists($offset) {

		return isset($this->collection[$offset]);
	} // offsetExists()

	public function offsetGet($offset) {

		return isset($this->collection[$offset])
			? $this->collection[$offset]
			: NULL;
	} // offsetGet()

	public function offsetSet($offset, $value) {

		if (is_null($offset)) {
			$this->collection[] = $value;
		} else {
			$this->collection[$offset] = $value;
		} // if
	} // offsetSet()

	public function offsetUnset($offset) {

		unset($this->collection[$offset]);
	} // offsetUnset()

	public function isEmpty() {

		return empty($this->collection);
	} // isEmpty()

	public function addDocument($key, $document = NULL) {

		if ($key instanceof \Traversable) {
			$this->collection[$document->_id] = $document;
		} else {
			$this->collection[$key] = $document;
		} // if
	} // addDocument()

	public function removeDocument($key) {

		if (isset($this->collection[$key])) {
			unset($this->collection[$key]);
		} // if
	} // removeDocument()

	public function load(array $documentView, $key = '_id') {

		if (empty($documentView)) {

			return;
		} // array()

		foreach ($documentView as $viewItem) {
			$document = $viewItem->value;
			$this->collection[$document->{$key}] = $document;
		} // foreach
	} // load()

} // Collection
