<?php
/**
 * User: dpeuscher
 * Date: 16.04.13
 */

namespace DpOpenGis\Collection;

use DpDoctrineExtensions\Collection\AForceTypeFreezableCollection;
use DpOpenGis\ModelInterface\IPointCollection;

/**
 * Class PointCollection
 *
 * @package DpOpenGis\Collection
 */
class PointCollection extends AForceTypeFreezableCollection implements IPointCollection {
    protected $_entityType = 'DpOpenGis\Model\Point';
	public function serialize() {
		return serialize(array($this->toArray(),$this->_frozen,$this->_decoree));
	}
	public function unserialize($serialized) {
		$array = unserialize($serialized);
		$this->__construct($array[0]);
		$this->_frozen = $array[1];
		$this->_decoree = $array[2];
	}
}