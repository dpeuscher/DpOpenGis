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
use DpOpenGis\ModelInterface\IPolygonCollection;

/**
 * Class PolygonCollection
 *
 * @package DpOpenGis\Collection
 */
class PolygonCollection extends AForceTypeFreezableCollection implements IPolygonCollection,\Serializable {
    protected $_entityType = 'DpOpenGis\Model\Polygon';

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