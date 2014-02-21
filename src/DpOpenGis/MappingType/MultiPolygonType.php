<?php
/**
 * User: dpeuscher
 * Date: 03.04.13
 */

namespace DpOpenGis\MappingType;

use DpOpenGis\Model\MultiPolygon;
use DpZFExtensions\Cache\ICacheAware;
use DpZFExtensions\Cache\TCacheAware;
use DpZFExtensions\ServiceManager\TServiceLocator;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Exception;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class MultiPolygonType
 *
 * @package AibLocation\OsmParser\OpenGisMappingType
 */
class MultiPolygonType extends Type implements ServiceLocatorAwareInterface,ICacheAware {
	use TServiceLocator,TCacheAware;
	const MULTIPOLYGON = 'multipolygon';
    //const REGEX = '#(\(\s*(\s*\(\s*[0-9.]+\s+[0-9.]+(\s*,\s*[0-9.]+\s+[0-9.]+\s*)+\))((\s*,\s*(\s*\(\s*[0-9.]+\s+[0-9.]+(\s*,\s*[0-9.]+\s+[0-9.]+\s*)+\s*\)\s*))*)\s*\))[,)]#';

	/**
	 * @return string
	 */
	public function getName()
	{
		return self::MULTIPOLYGON;
	}

	/**
	 * @param array            $fieldDeclaration
	 * @param AbstractPlatform $platform
	 * @return string
	 */
	public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
	{
		return 'MULTIPOLYGON';
	}

	/**
	 * @param mixed            $value
	 * @param AbstractPlatform $platform
	 * @throws \Exception
	 * @return MultiPolygon|mixed
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		if (is_null($value)) return null;
		$hash = 'DpOpenGis\MappingType\MultiPolygonType->convertToPHPValue('.md5($value).')';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);
        /** @var Collection $polygonCollection  */
        $polygonCollection = clone $this->getServiceLocator()->get('DpOpenGis\ModelInterface\IPolygonCollection');
        $type = Type::getType('polygon');
		$pointWithControlArray = explode(',',$value);
		$entity = '';
		$polygons = array();
		$opened = 0;
		foreach ($pointWithControlArray as $pointWithControl) {
			if (preg_match('#((?:MULTIPOLYGON)\s*\()?(\s*\()?(\s*\()?\s*((?:-?[0-9.]+\s+-?[0-9.]+)*)\s*(\)\s*)?(\)\s*)?(\)\s*)?$#i',$pointWithControl,$matches)) {
				$openedBeforeOpen = $opened;
				if (!empty($matches[1])) $opened++;
				if (!empty($matches[2])) $opened++;
				if (!empty($matches[3])) $opened++;
				$openedBetween = $opened;
				if (!empty($matches[5])) $opened--;
				if (!empty($matches[6])) $opened--;
				if (!empty($matches[7])) $opened--;
				$openedAfterClose = $opened;
				if ($openedBeforeOpen <= 1) {
					$openedBeforeOpen = 1;
					if (!empty($entity))
						$polygons[] = $entity;
					$entity = 'POLYGON';
					$first = true;
				}
				else
					$first = false;
				$entity .= ($first?'':',').str_repeat('(',($openedBetween-$openedBeforeOpen)).$matches[4].
					str_repeat(')',($openedBetween-(!$openedAfterClose?1:$openedAfterClose)));
			}
		}
		if (isset($entity))
			$polygons[] = $entity;
		if ($opened)
			throw new Exception("Something went wrong (opened braces left)");
		// Reached limit for string-length in pcre-functions
		//preg_match_all(self::REGEX,$value,$matches);

		foreach ($polygons as $polygonString)
            $polygonCollection->add($type->convertToPHPValue($polygonString,$platform));

		/** @var MultiPolygon $multiPolygon  */
		$multiPolygon = $this->getServiceLocator()->get('DpOpenGis\Factory\MultiPolygonFactory')->create('MultiPolygon',
		                                                           array('polygons' => $polygonCollection));
		$this->getLongTermCache()->setItem($hash,$multiPolygon);
		return $multiPolygon;
	}

	/**
	 * @param mixed            $value
	 * @param AbstractPlatform $platform
	 * @return mixed|string
	 */
	public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
        if ($value instanceof MultiPolygon) {
            $type = Type::getType('polygon');
            $polygonStrings = array();
            foreach ($value->getPolygons() as $polygon)
                $polygonStrings[] = str_ireplace('POLYGON','',$type->convertToDatabaseValue($polygon,$platform));

            $value = 'MULTIPOLYGON('.implode(',',$polygonStrings).')';
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
		return sprintf('MPolyFromText(%s)', $sqlExpr);
	}
}
