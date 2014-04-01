<?php

/**
 * This software is intelectual property of Hobrasoft s.r.o. (http://hobrasoft.cz).
 * It is released under GNU Lesser General Public License.
 * 
 */
namespace Hobrasoft\Deko\Structure;

use Hobrasoft\Deko;

/**
 * Collection class
 */
class Collection implements \Iterator, \ArrayAccess {

	protected
		$collection = array(),
		$currentKey = NULL;

	/**
	 * Collection constructor. Optionaly assign items into it.
	 *
	 * @public
	 * @param array $items Items of collection
	 */
	public function __construct(array $items = array()) {

		if (!empty($items)) {
			$this->collection = $items;
			$this->rewind();
		} // if
	} // __construct()

	/**
	 * Rewind internal array cursor to begin of array.
	 * Implementation of Iterator interface.
	 * See http://www.php.net/manual/en/class.iterator.php
	 *
	 * @public
	 */
	public function rewind() {

		reset($this->collection);
		$this->currentKey = key($this->collection);
	} // rewind()

	/**
	 * Return current item.
	 * Implementation of Iterator interface.
	 * See http://www.php.net/manual/en/class.iterator.php
	 *
	 * @public
	 * @return mixed Current item
	 */
	public function current() {

		return $this->collection[$this->currentKey];
	} // current()

	/**
	 * Return current key.
	 * Implementation of Iterator interface.
	 * See http://www.php.net/manual/en/class.iterator.php
	 *
	 * @public
	 * @return mixed Current key
	 */
	public function key() {

		$this->currentKey = key($this->collection);

		return $this->currentKey;
	} // key()

	/**
	 * Advance internal array cursor to next item.
	 * Implementation of Iterator interface.
	 * See http://www.php.net/manual/en/class.iterator.php
	 *
	 * @public
	 */
	public function next() {

		next($this->collection);
		$this->key();
	} // next()

	/**
	 * Check if current key is valid.
	 * Implementation of Iterator interface.
	 * See http://www.php.net/manual/en/class.iterator.php
	 *
	 * @public
	 * @return bool TRUE if current key is valid
	 */
	public function valid() {

		return isset($this->collection[$this->currentKey]);
	} // valid()


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

		return isset($this->collection[$offset]);
	} // offsetExists()

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

		return isset($this->collection[$offset])
			? $this->collection[$offset]
			: NULL;
	} // offsetGet()

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

		if (is_null($offset)) {
			$this->collection[] = $value;
		} else {
			$this->collection[$offset] = $value;
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

		unset($this->collection[$offset]);
	} // offsetUnset()


	/**
	 * Return TRUE if collection is empty.
	 *
	 * @public
	 * @return bool TRUE if collection is empty
	 */
	public function isEmpty() {

		return empty($this->collection);
	} // isEmpty()

	/**
	 * Add new document into collection.
	 *
	 * @public
	 * @param mixed $key Collection item key. If is Traversable, then this is document and its _id is used as key
	 * @param object $document Optionaly document to add to collection.
	 */
	public function addDocument($key, $document = NULL) {

		if ($key instanceof \Traversable) {
			$this->collection[$document->_id] = $document;
		} else {
			$this->collection[$key] = $document;
		} // if
	} // addDocument()

	/**
	 * Remove document specified by key from collection.
	 *
	 * @public
	 * @param string $key Document key in collection, usually document id
	 */
	public function removeDocument($key) {

		if (isset($this->collection[$key])) {
			unset($this->collection[$key]);
		} // if
	} // removeDocument()

	/**
	 * Load collection from data from view.
	 *
	 * @public
	 * @param array $documentView Document view data
	 * @param string $key Document property name to be used as key (optional)
	 */
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
