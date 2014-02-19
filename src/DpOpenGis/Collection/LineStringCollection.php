<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dpeuscher
 * Date: 16.04.13
 * Time: 18:58
 * To change this template use File | Settings | File Templates.
 */

namespace DpOpenGis\Collection;

use DpDoctrineExtensions\Collection\AForceTypeFreezableCollection;
use DpOpenGis\ModelInterface\ILineStringCollection;

/**
 * Class LineStringCollection
 *
 * @package DpOpenGis\Collection
 */
class LineStringCollection extends AForceTypeFreezableCollection implements ILineStringCollection, \Serializable {
    protected $_entityType = 'DpOpenGis\Model\LineString';
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