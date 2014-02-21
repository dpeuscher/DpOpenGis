<?php
/**
 * User: dpeuscher
 * Date: 03.04.13
 */

namespace DpOpenGis\MappingType;

use DpOpenGis\Model\LineString;
use DpOpenGis\Model\Point;
use DpZFExtensions\Cache\ICacheAware;
use DpZFExtensions\Cache\TCacheAware;
use DpZFExtensions\ServiceManager\TServiceLocator;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use DpOpenGis\ModelInterface\IPointCollection;
use Exception;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class LineStringType
 *
 * @package AibLocation\OsmParser\OpenGisMappingType
 */
class LineStringType extends Type implements ServiceLocatorAwareInterface,ICacheAware {
	use TServiceLocator,TCacheAware;
	const LINESTRING = 'lineString';
    //const REGEX = '#[\(,]\s*([0-9.]+)\s+([0-9.]+)\s*#';

	/**
	 * @return string
	 */
	public function getName()
	{
		return self::LINESTRING;
	}

	/**
	 * @param array            $fieldDeclaration
	 * @param AbstractPlatform $platform
	 * @return string
	 */
	public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
	{
		return 'LINESTRING';
	}

	/**
	 * @param mixed            $value
	 * @param AbstractPlatform $platform
	 * @throws \Exception
	 * @return LineString|mixed
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		if (is_null($value)) return null;
		$hash = 'DpOpenGis\MappingType\LineStringType->convertToPHPValue('.md5($value).')';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);

		/** @var IPointCollection $pointCollection  */
		$pointCollection = clone $this->getServiceLocator()->get('DpOpenGis\ModelInterface\IPointCollection');

		$pointWithControlArray = explode(',',$value);
		$points = array();
		$opened = 0;
		foreach ($pointWithControlArray as $pointWithControl) {
			if (preg_match('#((?:LINESTRING)\s*\()?\s*(-?[0-9.]+)\s+(-?[0-9.]+)\s*(\)\s*)?$#i',$pointWithControl,$matches)) {
				if (!empty($matches[1])) $opened++;
				if (!empty($matches[4])) $opened--;
				$points[] = array($matches[2],$matches[3]);
			}
		}
		if ($opened)
			throw new Exception("Something went wrong (opened braces left)");

		// Reached limit for string-length in pcre-functions
		//preg_match_all(self::REGEX,$value,$matches);
		//$points = $matches[1];

		foreach ($points as $nr => $match)
			$pointCollection->add(
				$this->getServiceLocator()->get('DpOpenGis\Factory\PointFactory')->
					create('Point',array('lon' => (double) $match[0],'lat' => (double) $match[1])));

		/** @var LineString $lineString  */
		$lineString = $this->getServiceLocator()->get('DpOpenGis\Factory\LineStringFactory')->
			create('LineString',array('points' => $pointCollection));

		$this->getLongTermCache()->setItem($hash,$lineString);
		return $lineString;
	}

	/**
	 * @param mixed            $value
	 * @param AbstractPlatform $platform
	 * @return mixed|string
	 */
	public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
		if ($value instanceof LineString) {
			$points = array();

			foreach ($value->getPoints() as $point)
				/** @var Point $point */
				$points[] = $point->getLon().' '.$point->getLat();
			$value = 'LINESTRING('.implode(',',$points).')';
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
		return sprintf('LineStringFromText(%s)', $sqlExpr);
	}
}
