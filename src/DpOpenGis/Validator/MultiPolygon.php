<?php
/**
 * User: dpeuscher
 * Date: 12.03.13
 */
namespace DpOpenGis\Validator;

// Framework usage
use DpZFExtensions\Validator\AbstractValidator;
use DpOpenGis\ModelInterface\IPolygonCollection;
use DpOpenGis\Model\Polygon as PolygonObject;
use DpOpenGis\Model\LineString as LineStringObject;
use DpOpenGis\Model\Point as PointObject;
use Zend\Validator\ValidatorInterface;

/**
 * Represents a validator that checks if a Point is in a valid state
 */
class MultiPolygon extends AbstractValidator implements ValidatorInterface {
	const POLYGONSINVALID = 'polygonsInvalid';
	const POLYGONSINTERSECT = 'polygonsIntersect';

	/**
	 * @var array
	 */
	protected $messageTemplates = array(
		self::POLYGONSINVALID => "Invalid value for polygons: %value% is not an instance of IPolygonCollection",
		self::POLYGONSINTERSECT => "Wrong value for polygons: %value% are intersecting",
	);

	/**
	 * @param  mixed $value
	 * @return bool
	 * @throws \Zend\Validator\Exception\RuntimeException If validation of $value is impossible
	 */
	public function _isValidByTypes($value) {
		/**
		 * @var array $value
		 */
		extract($value);
		/** @var IPolygonCollection $polygons */
		if (!$polygons instanceof IPolygonCollection) $this->error(self::POLYGONSINVALID,var_export($polygons,true));

		if (array() !== ($this->getMessages()))
			return false;

		return true;
	}
	/**
	 * @param  mixed $value
	 * @return bool
	 * @throws \Zend\Validator\Exception\RuntimeException If validation of $value is impossible
	 */
	public function _isValidByDependencies($value) {
		/**
		 * @var array $value
		 */
		extract($value);
		/** @var IPolygonCollection $polygons */
		foreach ($polygons as $nr => $polygon)
			/** @var PolygonObject $polygon */
			foreach ($polygons as $nr2 => $polygon2) {
				if ($nr2 <= $nr)
					continue;
				/** @var PolygonObject $polygon2 */
				if ($polygon->getOuter()->Intersects($polygon2->getOuter())) {
					$outer = array();
					$polygon->getOuter()->getPoints()->forAll(
						function ($key,PointObject $point) use (&$outer)
						{ return $outer[$key] = $point->getLon().' '.$point->getLat();});
					$outer2 = array();
					$polygon2->getOuter()->getPoints()->forAll(
						function ($key,PointObject $point) use (&$outer2)
						{ return $outer2[$key] = $point->getLon().' '.$point->getLat();});

					$inners = array();
					$polygon->getInners()->forAll(function ($key,$lineString) use (&$inners) {
						$points = array();
						/** @var LineStringObject $lineString */
						$lineString->getPoints()->forAll(
							function ($key2,PointObject $point) use (&$points)
							{ return $points[$key2] = $point->getLon().' '.$point->getLat();});
						$inners[$key] = '('.implode(',',$points).')';
					});

					$inners2 = array();
					$polygon2->getInners()->forAll(function ($key,$lineString) use (&$inners2) {
						$points = array();
						/** @var LineStringObject $lineString */
						$lineString->getPoints()->forAll(
							function ($key2,PointObject $point) use (&$points)
							{ return $points[$key2] = $point->getLon().' '.$point->getLat();});
						$inners2[$key] = '('.implode(',',$points).')';
					});

					$this->error(self::POLYGONSINTERSECT,
					             '(('.implode(',',$outer)."),".implode(',',$inners).'),'.
					             '(('.implode(',',$outer2)."),".implode(',',$inners2).')'
								);
				}
			}
		if (array() !== $this->getMessages())
			return false;

		return true;
	}

}