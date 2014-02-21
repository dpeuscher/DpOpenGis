<?php
/**
 * User: dpeuscher
 * Date: 16.04.13
 */

namespace DpOpenGis\Collection;

use DpZFExtensions\ServiceManager\TServiceLocator;
use Closure;
use Doctrine\Common\Collections\Criteria;
use DpDoctrineExtensions\Collection\AForceTypeFreezableCollection;
use DpOpenGis\ModelInterface\IPointCollection;
use DpOpenGis\ModelInterface\IReversePointCollection;
use Exception;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class PointCollection
 *
 * @package DpOpenGis\Collection
 */
class ReversePointCollection extends AForceTypeFreezableCollection implements IReversePointCollection,
                                                                              ServiceLocatorAwareInterface,
																			  \Serializable
	{
	use TServiceLocator;
    protected $_entityType = 'DpOpenGis\Model\Point';
	/**
	 * @var IPointCollection
	 */
	protected $_original;
	public function setOriginalPointCollection(IPointCollection $original) {
		if ($this->isFrozen())
			$this->rejectFrozen();
		$this->_original = $original;
		$this->_decoree = null;
	}
	protected function _lazyLoad() {
		if (!isset($this->_decoree) && $this->getServiceLocator()->has('DpOpenGis\ModelInterface\IPointCollection')) {
			/** @var IPointCollection $points */
			$points = clone $this->getServiceLocator()->get('DpOpenGis\ModelInterface\IPointCollection');
			$tmp = array();
			foreach ($this->_original as $nr => $point)
				$tmp[($this->_original->count()-($nr+1))] = $point;
			ksort($tmp);
			foreach ($tmp as $point)
				$points->add($point);
			$this->setDecoree($points);
		}
		elseif (!isset($this->_decoree))
			throw new Exception('Could not find DpOpenGis\ModelInterface\IPointCollection in ServiceLocator');
	}
	public function toArray() {
		$this->_lazyLoad();
		return $this->_decoree->toArray();
	}

	public function first() {
		$this->_lazyLoad();
		return $this->_decoree->first();
	}

	public function last() {
		$this->_lazyLoad();
		return $this->_decoree->last();
	}

	public function key() {
		$this->_lazyLoad();
		return $this->_decoree->key();
	}

	public function next() {
		$this->_lazyLoad();
		return $this->_decoree->next();
	}

	public function current() {
		$this->_lazyLoad();
		return $this->_decoree->current();
	}

	public function remove($key) {
		$this->_lazyLoad();
		return $this->_decoree->remove($key);
	}

	public function removeElement($element) {
		$this->_lazyLoad();
		return $this->_decoree->removeElement($element);
	}

	public function offsetExists($offset) {
		$this->_lazyLoad();
		return $this->_decoree->offsetExists($offset);
	}

	public function offsetGet($offset) {
		$this->_lazyLoad();
		return $this->_decoree->offsetGet($offset);
	}

	public function offsetSet($offset, $value) {
		$this->_lazyLoad();
		return $this->_decoree->offsetSet($offset, $value);
	}

	public function offsetUnset($offset) {
		$this->_lazyLoad();
		return $this->_decoree->offsetUnset($offset);
	}

	public function containsKey($key) {
		$this->_lazyLoad();
		return $this->_decoree->containsKey($key);
	}

	public function contains($element) {
		$this->_lazyLoad();
		return $this->_decoree->contains($element);
	}

	public function exists(Closure $p) {
		$this->_lazyLoad();
		return $this->_decoree->exists($p);
	}

	public function indexOf($element) {
		$this->_lazyLoad();
		return $this->_decoree->indexOf($element);
	}

	public function get($key) {
		$this->_lazyLoad();
		return $this->_decoree->get($key);
	}

	public function getKeys() {
		$this->_lazyLoad();
		return $this->_decoree->getKeys();
	}

	public function getValues() {
		$this->_lazyLoad();
		return $this->_decoree->getValues();
	}

	public function count() {
		$this->_lazyLoad();
		return $this->_decoree->count();
	}

	public function set($key, $value) {
		$this->_lazyLoad();
		$this->_decoree->set($key, $value);
	}

	public function add($value) {
		$this->_lazyLoad();
		return $this->_decoree->add($value);
	}

	public function isEmpty() {
		$this->_lazyLoad();
		return $this->_decoree->isEmpty();
	}

	public function getIterator() {
		$this->_lazyLoad();
		return $this->_decoree->getIterator();
	}

	public function map(Closure $func) {
		$this->_lazyLoad();
		return $this->_decoree->map($func);
	}

	public function filter(Closure $p) {
		$this->_lazyLoad();
		return $this->_decoree->filter($p);
	}

	public function forAll(Closure $p) {
		$this->_lazyLoad();
		return $this->_decoree->forAll($p);
	}

	public function partition(Closure $p) {
		$this->_lazyLoad();
		return $this->_decoree->partition($p);
	}

	public function clear() {
		$this->_lazyLoad();
		$this->_decoree->clear();
	}

	public function slice($offset, $length = null) {
		$this->_lazyLoad();
		return $this->_decoree->slice($offset, $length);
	}

	public function matching(Criteria $criteria) {
		$this->_lazyLoad();
		return $this->_decoree->matching($criteria);
	}
	public function serialize() {
		return serialize(array($this->toArray(),$this->_frozen,$this->_original,$this->_decoree));
	}
	public function unserialize($serialized) {
		$array = unserialize($serialized);
		$this->__construct($array[0]);
		$this->_frozen = $array[1];
		$this->_original = $array[2];
		$this->_decoree = $array[3];
	}
}