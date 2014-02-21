<?php
/**
 * User: dpeuscher
 * Date: 03.04.13
 */

namespace DpOpenGis\MappingType;

use DpOpenGis\Model\LineString;
use DpOpenGis\Model\Polygon;
use DpOpenGis\Model\Point;
use DpZFExtensions\Cache\ICacheAware;
use DpZFExtensions\Cache\TCacheAware;
use DpZFExtensions\ServiceManager\TServiceLocator;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use DpOpenGis\ModelInterface\ILineStringCollection;
use Exception;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class PolygonType
 *
 * @package AibLocation\OsmParser\OpenGisMappingType
 */
class PolygonType extends Type implements ServiceLocatorAwareInterface,ICacheAware {
	use TServiceLocator,TCacheAware;
	const POLYGON = 'polygon';
    //const REGEX = '#\(\s*(\s*\(\s*[0-9.]+\s+[0-9.]+(\s*,\s*[0-9.]+\s+[0-9.]+\s*)+\))((\s*,\s*(\s*\(\s*[0-9.]+\s+[0-9.]+(\s*,\s*[0-9.]+\s+[0-9.]+\s*)+\s*\)\s*))*)\s*\)#';
    //const REGEX_INNER = '#(\s*,\s*(\s*\(\s*[0-9.]+\s+[0-9.]+(\s*,\s*[0-9.]+\s+[0-9.]+\s*)+\s*\)\s*))#';

	/**
	 * @return string
	 */
	public function getName()
	{
		return self::POLYGON;
	}

	/**
	 * @param array            $fieldDeclaration
	 * @param AbstractPlatform $platform
	 * @return string
	 */
	public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
	{
		return 'POLYGON';
	}

	/**
	 * @param mixed            $value
	 * @param AbstractPlatform $platform
	 * @throws \Exception
	 * @return Polygon|mixed
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		if (is_null($value)) return null;
		$hash = 'DpOpenGis\MappingType\PolygonType->convertToPHPValue('.md5($value).')';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);

		$type = Type::getType('linestring');

		$pointWithControlArray = explode(',',$value);
		$outer = '';
		$entity = '';
		$inners = array();
		$opened = 0;
		foreach ($pointWithControlArray as $pointWithControl) {
			if (preg_match('#((?:POLYGON)\s*\()?(\s*\()?\s*(-?[0-9.]+\s+-?[0-9.]+)?\s*(\)\s*)?(\)\s*)?$#i',$pointWithControl,$matches)) {
				$openedBeforeOpen = $opened;
				if (!empty($matches[1])) $opened++;
				if (!empty($matches[2])) $opened++;
				$openedBetween = $opened;
				if (!empty($matches[4])) $opened--;
				if (!empty($matches[5])) $opened--;
				$openedAfterClose = $opened;
				if ($openedBeforeOpen <= 1) {
					$openedBeforeOpen = 1;
					if (!empty($entity) && empty($outer))
						$outer = $entity;
					elseif (!empty($entity))
						$inners[] = $entity;
					$entity = 'LINESTRING';
					$first = true;
				}
				else
					$first = false;
				$entity .= ($first?'':',').str_repeat('(',($openedBetween-$openedBeforeOpen)).$matches[3].
					str_repeat(')',($openedBetween-(!$openedAfterClose?1:$openedAfterClose)));
			}
		}
		if (!empty($entity) && empty($outer))
			$outer = $entity;
		elseif (!empty($entity))
			$inners[] = $entity;
		if ($opened)
			throw new Exception("Something went wrong (opened braces left)");

		// Reached limit for string-length in pcre-functions
		//preg_match_all(self::REGEX,$value,$matches);
        //$outer = $matches[1];
        //$innersString = $matches[3];

        $outerLineString = $type->convertToPHPValue($outer,$platform);

        /** @var ILineStringCollection $polygonCollection  */
        $polygonCollection = clone $this->getServiceLocator()->get('DpOpenGis\ModelInterface\ILineStringCollection');

        //$matches = array();
        //preg_match_all(self::REGEX_INNER,$innersString,$matches);
        foreach ($inners as $lineStringString)
            $polygonCollection->add($type->convertToPHPValue($lineStringString,$platform));

		/** @var Polygon $polygon  */
		$polygon = $this->getServiceLocator()->get('DpOpenGis\Factory\PolygonFactory')->create('Polygon',array(
		                                                                'outer' => $outerLineString,
		                                                                'inners' => $polygonCollection
		                                                           ));
		$this->getLongTermCache()->setItem($hash,$polygon);
		return $polygon;
	}

	/**
	 * @param mixed            $value
	 * @param AbstractPlatform $platform
	 * @return mixed|string
	 */
	public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
        if ($value instanceof Polygon) {
            $lineString = array();

            $pointString = array();
            foreach ($value->getOuter()->getPoints() as $point)
                /** @var Point $point */
                $pointString[] = $point->getLon().' '.$point->getLat();
            $lineString[] = '('.implode(',',$pointString).')';

            foreach ($value->getInners() as $innerLineString) {
                /** @var LineString $innerLineString */
                $pointString = array();
                foreach ($innerLineString->getPoints() as $point)
                    /** @var Point $point */
                    $pointString[] = $point->getLon().' '.$point->getLat();
                $lineString[] = '('.implode(',',$pointString).')';
            }
            $value = 'POLYGON('.implode(',',$lineString).')';
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
	public function convertToPHPValueSQL($sqlExpr,  $platform)
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
		return sprintf('PolygonFromText(%s)', $sqlExpr);
	}
}
