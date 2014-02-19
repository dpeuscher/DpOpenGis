<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dpeuscher
 * Date: 02.04.13
 * Time: 14:55
 * To change this template use File | Settings | File Templates.
 */

namespace DpOpenGis\Model;


use DpZFExtensions\ServiceManager\TServiceLocator;
use DpZFExtensions\Validator\AbstractValueObject;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use DpOpenGis\MappingType\MultiPolygonType;
use DpOpenGis\ModelInterface\IPolygonCollection;
use DpZFExtensions\Validator\IExchangeState;
use DpZFExtensions\Validator\IValidatorAware;
use DpZFExtensions\Validator\TValidatorAware;
use DpZFExtensions\Validator\TExchangeState;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class MultiPolygon
 *
 * @package AibLocation\OsmParser\OpenGisModel
 */
class MultiPolygon extends AbstractValueObject implements IExchangeState,IValidatorAware,ServiceLocatorAwareInterface {
	use TExchangeState,TValidatorAware,TServiceLocator;
	/**
	 * @var string
	 */
	protected $_validatorClassName = 'DpOpenGis\Validator\MultiPolygon';
	/**
	 * @var IPolygonCollection
	 */
	protected $_polygons;
	/**
	 * @var string|null
	 */
	protected $_serialized;

	/**
	 * @param MultiPolygon $innerMultiPolygon
	 * @return bool
	 */
	public function Contains(MultiPolygon $innerMultiPolygon) {
		$polygonsIn = array();

		foreach ($innerMultiPolygon->getPolygons() as $innerPolygon)
			/** @var Polygon $innerPolygon */
			foreach ($this->_polygons as $polygon)
				/** @var Polygon $polygon */
				if ($polygon->Contains($innerPolygon)) {
					$polygonsIn[] = $innerPolygon;
					continue 2;
				}

		if (count($polygonsIn) == $innerMultiPolygon->getPolygons()->count())
			return true;
		return false;
	}

	/**
     * @return \DpOpenGis\ModelInterface\IPolygonCollection
     */
    public function getPolygons()
    {
	    return $this->_polygons;
    }
	/**
	 * @return array
	 */
	public function getStateVars() {
		return array('polygons');
	}
	/**
	 * @return string
	 */
	public function __toString() {
		if (!isset($this->_serialized)) {
			if ($this->getServiceLocator()->has('DpOpenGis\MappingType\MultiPolygonType')) {
				/** @var MultiPolygonType $type */
				$type = $this->getServiceLocator()->get('DpOpenGis\MappingType\MultiPolygonType');
			}
			else {
				if (!Type::hasType('multipolygon'))
					Type::addType('multipolygon', 'DpOpenGis\MappingType\MultiPolygonType');
				$type = Type::getType('multipolygon');
			}
			if ($this->getServiceLocator()->has('doctrine.entitymanager.orm_default'))
				$platform = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default')->getConnection()->
					getDatabasePlatform();
			else
				$platform = new MySqlPlatform();

			$this->_serialized = md5($type->convertToDatabaseValue($this,$platform));
		}
		return $this->_serialized;
	}
public function serialize() {
	$array = array();
	foreach ($this->__sleep() as $field)
		$array[$field] = serialize($this->$field);
	return $array;
}
	public function __sleep() {
		$this->__toString();
		$return = array_merge(array('_serialized','_validator'),array_map(function($field){return '_'.$field;},$this->getStateVarsWithAll()));
		return $return;
	}
}
