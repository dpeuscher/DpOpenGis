<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dpeuscher
 * Date: 02.04.13
 * Time: 14:46
 * To change this template use File | Settings | File Templates.
 */

namespace DpOpenGis\Model;


use DpZFExtensions\ServiceManager\TServiceLocator;
use DpZFExtensions\Validator\AbstractValueObject;
use DpZFExtensions\Validator\IExchangeState;
use DpZFExtensions\Validator\IValidatorAware;
use DpZFExtensions\Validator\TExchangeState;
use DpZFExtensions\Validator\TValidatorAware;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use DpOpenGis\MappingType\PointType;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class Point
 *
 * @package DpOpenGis\Model
 */
class Point extends AbstractValueObject implements IExchangeState,IValidatorAware,ServiceLocatorAwareInterface {
	use TExchangeState,TValidatorAware,TServiceLocator;
	/**
	 * @var string
	 */
	protected $_validatorClassName = 'DpOpenGis\Validator\Point';
	/**
	 * @var float
	 */
	protected $_lat;
	/**
	 * @var float
	 */
	protected $_lon;
	/**
	 * @var string|null
	 */
	protected $_serialized;

	/**
	 * @return array
	 */
	public function getStateVars() {
		return array(
			'lat',
			'lon'
		);
	}

	/**
	 * @return float
	 */
	public function getLat() {
		return $this->_lat;
	}

	/**
	 * @return float
	 */
	public function getLon() {
		return $this->_lon;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		if (!isset($this->_serialized)) {
			if ($this->getServiceLocator()->has('DpOpenGis\MappingType\PointType')) {
				/** @var PointType $type */
				$type = $this->getServiceLocator()->get('DpOpenGis\MappingType\PointType');
			}
			else {
				if (!Type::hasType('point'))
					Type::addType('point', 'DpOpenGis\MappingType\PointType');
				$type = Type::getType('point');
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
	public function __sleep() {
		$this->__toString();
		$return = array_merge(array('_serialized','_validator'),array_map(function($field){return '_'.$field;},$this->getStateVarsWithAll()));
		return $return;
	}
}
