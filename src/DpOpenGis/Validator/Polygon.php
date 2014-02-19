<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dpeuscher
 * Date: 12.03.13
 * Time: 14:26
 * To change this template use File | Settings | File Templates.
 */
namespace DpOpenGis\Validator;

// Framework usage
use DpZFExtensions\Validator\AbstractValidator;
use DpOpenGis\Model\LineString as LineStringObject;
use DpOpenGis\Model\Point as PointObject;
use DpOpenGis\ModelInterface\ILineStringCollection;
use Zend\Validator\ValidatorInterface;

/**
 * Represents a validator that checks if a Polygon is in a valid state
 */
class Polygon extends AbstractValidator implements ValidatorInterface {
	const OUTERINVALID = 'pointsInvalid';
	const INNERSINVALID = 'innersInvalid';
	const OUTERNOTARING = 'outerNotARing';
	const INNERNOTARING = 'innerNotARing';
	const INNERSNOTINOUTER = 'innersNotInOuter';
	const INNERSINTERSECT = 'innersIntersect';

	/**
	 * @var array
	 */
	protected $messageTemplates = array(
		self::OUTERINVALID => "Invalid value for outer: %value% is not an instance of LineString",
		self::INNERSINVALID => "Invalid value for inners: %value% is not an instance of ILineStringCollection",

		self::OUTERNOTARING => "Wrong value for outer: %value% is not a ring",
		self::INNERNOTARING => "Wrong value for one of the inners: %value% is not a ring",
		self::INNERSNOTINOUTER => "Wrong value for one of the inners: %value% not (completely) in outer",
		self::INNERSINTERSECT => "Wrong value for two of the inners: %value% are intersecting",
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
		/** @var LineStringObject $outer */
		/** @var ILineStringCollection $inners */
		if (!$outer instanceof LineStringObject) $this->error(self::OUTERINVALID,var_export($outer,true));
		if (!$inners instanceof ILineStringCollection) $this->error(self::INNERSINVALID,var_export($inners,true));

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
		/** @var LineStringObject $outer */
		/** @var ILineStringCollection $inners */
		if (!$outer->IsRing())
			$this->error(self::OUTERNOTARING,var_export($outer,true));
		foreach ($inners as $inner)
			/** @var LineStringObject $inner */
			if (!$inner->IsRing())
				$this->error(self::INNERNOTARING,var_export($inner,true));
		foreach ($inners as $inner)
			/** @var LineStringObject $inner */
			if (!$outer->Contains($inner)) {
				ob_start();
				var_dump($inner,true);
				$contents = ob_get_contents();
				ob_end_clean();
				$this->error(self::INNERSNOTINOUTER,$contents);
			}
		foreach ($inners as $nr => $inner)
			/** @var LineStringObject $inner */
			foreach ($inners as $nr2 => $inner2) {
				/** @var LineStringObject $inner2 */
				if ($nr2 <= $nr)
					continue;
				if ($inner !== $inner2 && $inner->Intersects($inner2)) {
					$points = array();
					$inner->getPoints()->forAll(
						function ($key,$point) use (&$points) { if (!$point instanceof PointObject) var_dump($point); return $points[$key] = $point->getLon().' '.$point->getLat();});
					$points2 = array();
					$inner2->getPoints()->forAll(
						function ($key,$point) use (&$points2) { return $points2[$key] = $point->getLon().' '.$point->getLat();});
					$this->error(self::INNERSINTERSECT,'('.implode(',',$points)."),(".implode(',',$points2).')'."\n\n");
				}
			}

		if (array() !== $this->getMessages())
			return false;

		return true;
	}

}
