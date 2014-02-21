<?php
/**
 * User: dpeuscher
 * Date: 03.04.13
 */

namespace DpOpenGis\MappingType;

use DpOpenGis\Model\Point;
use DpZFExtensions\Cache\ICacheAware;
use DpZFExtensions\Cache\TCacheAware;
use DpZFExtensions\ServiceManager\TServiceLocator;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class PointType
 *
 * @package AibLocation\OsmParser\OpenGisMappingType
 */
class PointType extends Type implements ServiceLocatorAwareInterface,ICacheAware {
	use TServiceLocator,TCacheAware;
	const POINT = 'point';

	/**
	 * @return string
	 */
	public function getName()
	{
		return self::POINT;
	}

	/**
	 * @param array            $fieldDeclaration
	 * @param AbstractPlatform $platform
	 * @return string
	 */
	public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
	{
		return 'POINT';
	}

	/**
	 * @param mixed            $value
	 * @param AbstractPlatform $platform
	 * @return Point|mixed
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		if (is_null($value)) return null;
		$hash = 'DpOpenGis\MappingType\PointType->convertToPHPValue('.md5($value).')';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);

		preg_match('#POINT\((-?[0-9]+(\.[0-9]+)?) (-?[0-9]+(\.[0-9]+)?)\)#',$value,$matches);
		$longitude = $matches[1];
		$latitude = $matches[3];

		$point = $this->getServiceLocator()->get('DpOpenGis\Factory\PointFactory')->
			create('Point',array('lon' => (double) $longitude,'lat' => (double) $latitude));
		$this->getLongTermCache()->setItem($hash,$point);
		return $point;
	}

	/**
	 * @param mixed            $value
	 * @param AbstractPlatform $platform
	 * @return mixed|string
	 */
	public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
		if ($value instanceof Point) {
			$value = 'POINT('.$value->getLon().' '.$value->getLat().')';
		}

		return $value;
	}

	/**
	 * @return bool
	 */
	public function canRequireSQLConversion()
	{
		return true;
	}

	/**
	 * @param string           $sqlExpr
	 * @param AbstractPlatform $platform
	 * @return string
	 */
	public function convertToPHPValueSQL($sqlExpr, $platform)
	{
		return sprintf('AsText(%s)', $sqlExpr);
	}

	/**
	 * @param string           $sqlExpr
	 * @param AbstractPlatform $platform
	 * @return string
	 */
	public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
	{
		return sprintf('PointFromText(%s)', $sqlExpr);
	}
}
