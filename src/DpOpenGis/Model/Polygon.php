<?php
/**
 * User: dpeuscher
 * Date: 02.04.13
 */

namespace DpOpenGis\Model;


use DpZFExtensions\ServiceManager\TServiceLocator;
use DpZFExtensions\Validator\AbstractValueObject;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use DpOpenGis\MappingType\PolygonType;
use DpOpenGis\ModelInterface\ILineStringCollection;
use DpZFExtensions\Validator\IExchangeState;
use DpZFExtensions\Validator\IValidatorAware;
use DpZFExtensions\Validator\TValidatorAware;
use DpZFExtensions\Validator\TExchangeState;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class Polygon
 *
 * @package AibLocation\OsmParser\OpenGisModel
 */
class Polygon extends AbstractValueObject implements IExchangeState,IValidatorAware,ServiceLocatorAwareInterface {
	use TExchangeState,TValidatorAware,TServiceLocator;
	/**
	 * @var string
	 */
	protected $_validatorClassName = 'DpOpenGis\Validator\Polygon';
	/**
	 * @var LineString
	 */
	protected $_outer;

	/**
	 * @var ILineStringCollection
	 */
	protected $_inners;
	/**
	 * @var string|null
	 */
	protected $_serialized;

	/**
	 * @param Polygon $innerPolygon
	 * @return bool
	 */
	public function Contains(Polygon $innerPolygon) {
		if (!$this->_outer->Contains($innerPolygon->getOuter()))
			return false;
		$inners = $this->_inners;
		foreach ($inners as $inner)
			/** @var LineString $inner */
			if ($inner->Intersects($innerPolygon->getOuter())) {
				foreach ($innerPolygon->getInners() as $subInner)
					/** @var LineString $subInner */
					if ($subInner->Contains($inner))
						continue 2;
				return false;
			}
		return true;
	}

	/**
	 * @param LineString $lineString
	 * @return bool
	 */
	public function ContainsLineString(LineString $lineString) {
		if (!$this->_outer->Contains($lineString))
			return false;
		return true;
	}
	/**
	 * @param Polygon $innerPolygon
	 * @return bool
	 */
	public function Intersects(Polygon $innerPolygon) {
		return $this->IntersectsLineString($innerPolygon->getOuter());
	}

	/**
	 * @param LineString $lineString
	 * @param bool       $fasterOnTrue
	 * @return bool
	 */
	public function IntersectsLineString(LineString $lineString,$fasterOnTrue = false) {
		if (!$this->_outer->Intersects($lineString,$fasterOnTrue))
			return false;
		$inners = $this->_inners;
		foreach ($inners as $inner)
			/** @var LineString $inner */
			if ($inner->Contains($lineString))
				return false;
			elseif ($inner->Intersects($lineString,false))
				return true;
		return true;
	}
    /**
     * @return \DpOpenGis\ModelInterface\ILineStringCollection
     */
    public function getInners() {
	    return $this->_inners;
    }

    /**
     * @return \DpOpenGis\Model\LineString
     */
    public function getOuter() {
        return $this->_outer;
    }

    /**
     * @return array
     */
    public function  getStateVars() {
		return array('outer','inners');
	}
	/**
	 * @return string
	 */
	public function __toString() {
		if (!isset($this->_serialized)) {
			if ($this->getServiceLocator()->has('DpOpenGis\MappingType\PolygonType')) {
				/** @var PolygonType $type */
				$type = $this->getServiceLocator()->get('DpOpenGis\MappingType\PolygonType');
			}
			else
				$type = Type::getType('polygon');
			if ($this->getServiceLocator()->has('doctrine.entitymanager.orm_default'))
				$platform = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default')->getConnection()->
					getDatabasePlatform();
			else
				$platform = new MySqlPlatform();

			$this->_serialized = md5($type->convertToDatabaseValue($this,$platform));
		}
		return $this->_serialized;
	}
	public function __sleep() {
		$this->__toString();
		$return = array_merge(array('_serialized','_validator'),array_map(function($field){return '_'.$field;},$this->getStateVarsWithAll()));
		return $return;
	}
}
