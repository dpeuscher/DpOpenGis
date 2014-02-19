<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dpeuscher
 * Date: 02.04.13
 * Time: 14:48
 * To change this template use File | Settings | File Templates.
 */

namespace DpOpenGis\Model;


use Closure;
use Doctrine\ORM\EntityManager;
use DpOpenGis\Collection\PointCollection;
use DpOpenGis\Exception\NotOptimizedException;
use DpOpenGis\Exception\WrongDirectionException;
use DpOpenGis\Factory\MultiPolygonFactory;
use DpOpenGis\Model\Point;
use DpZFExtensions\ServiceManager\TServiceLocator;
use DpZFExtensions\Validator\AbstractValueObject;
use DpZFExtensions\Validator\IExchangeState;
use DpZFExtensions\Validator\IValidatorAware;
use DpZFExtensions\Validator\TValidatorAware;
use DpZFExtensions\Validator\TExchangeState;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use DpOpenGis\Factory\PointFactory;
use DpOpenGis\MappingType\LineStringType;
use DpOpenGis\ModelInterface\IPointCollection;
use Exception;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class LineString
 *
 * @package AibLocation\OsmParser\OpenGisModel
 */
class LineString extends AbstractValueObject implements IExchangeState,IValidatorAware,ServiceLocatorAwareInterface {
	use TExchangeState,TValidatorAware,TServiceLocator;
	/**
	 * @var string
	 */
	protected $_validatorClassName = 'DpOpenGis\Validator\LineString';
	/**
	 * @var IPointCollection
	 */
	protected $_points;
	/**
	 * @var boolean
	 */
	protected $_optimized;
	/**
	 * @var string|null
	 */
	protected $_serialized;
    /**
     * @return float
     */
    public function GLength() {
        /** @var IPointCollection $points  */
        $points = $this->getPoints();
        $length = 0.0;
        /** @var Point $pre */
        $pre = $points->first();
        foreach ($points as $point) {
            /** @var Point $point */
            if ($points->first() === $point)
                continue;
            $length += sqrt(pow($point->getLon()-$pre->getLon(),2)+pow($point->getLat()-$pre->getLat(),2));
	        $pre = $point;
        }
        return $length;
    }
    /**
     * @return Point
     */
    public function EndPoint() {
        /** @var IPointCollection $points  */
        $points = $this->getPoints();
        return $points->last();
    }
    /**
     * @return Point
     */
    public function StartPoint() {
        /** @var IPointCollection $points  */
        $points = $this->getPoints();
        return $points->first();
    }
    /**
     * @return int
     */
    public function NumPoints() {
        return count($this->getPoints());
    }
    /**
     * @param int $n
     * @return Point
     */
    public function PointN($n) {
        /** @var IPointCollection $points  */
        $points = $this->getPoints();
        return $points->get($n);
    }
	public function isOptimized() {
		if (!isset($this->_optimized)) {
			$points = $this->getPoints();
			$pointsCount = $points->count();
			$this->_optimized = true;
			if ($this->IsRing())
				if ($this->_isSameLine($points->get($points->count()-2),$points->get(0),$points->get(0),$points->get(1))) {
					$this->_optimized = false;
					return false;
				}
			for ($i = 0; $i < $pointsCount-2;$i++)
				if ($this->_isSameLine($points->get($i),$points->get($i+1),$points->get($i+1),$points->get($i+2))) {
					$this->_optimized = false;
					return false;
				}
		}
		return $this->_optimized;
	}
	public function optimizeLineString() {
		if (!$this->isOptimized()) {
			/** @var IPointCollection $newPointCollection  */
			$newPointCollection = clone $this->getServiceLocator()->get('DpOpenGis\ModelInterface\IPointCollection');
			/** @var IPointCollection $points  */
			$points = $this->getPoints();
			$skipFirst = 0;
			$modified = false;
			if ($this->IsRing()) {
				$count = $points->count();
				while ($this->_isSameLine($points->get($skipFirst),$points->get($skipFirst+1),
				                          $points->get($count-2),$points->get($count-1)))
					$skipFirst++;
			}
			$newPointCollection->add($points->get($skipFirst));
			for ($i = $skipFirst+1;$i < $points->count()-1;$i++) {
				if (!$this->_isSameLine($newPointCollection->last(),$points->get($i),$points->get($i),
				                        $points->get($i+1)))
					$newPointCollection->add($points->get($i));
				else
					$modified = true;
			}
			if ($modified) {
				$newPointCollection->add($points->get($skipFirst));
				$this->_points = $newPointCollection;
			}
			$this->_optimized = true;
		}
	}
    /**
     * @return bool
     */
    public function IsRing() {
        /** @var IPointCollection $points  */
        $points = $this->getPoints();
	    /** @var Point $firstPoint */
	    $firstPoint = $points->first();
	    /** @var Point $lastPoint */
	    $lastPoint = $points->last();

        $pointData = array();
        foreach ($points as $point) {
            /** @var Point $point */
            if (!in_array(array((string)$point->getLon(),(string)$point->getLat()),$pointData,true) || (
	                $point->getLon() == $lastPoint->getLon() &&
		            $point->getLat() == $lastPoint->getLat()))
                $pointData[] = array((string) $point->getLon(),(string) $point->getLat());
            else
                return false;
        }
	    return $firstPoint->getLon() == $lastPoint->getLon() && $firstPoint->getLat() == $lastPoint->getLat();
    }
	/**
	 * @param Point $point1
	 * @param Point $point2
	 * @return bool
	 */
	protected static function _straightLine(Point $point1,Point $point2) {
		return $point1->getLon() == $point2->getLon();
	}
	/**
	 * @param Point $start
	 * @param Point $end
	 * @return array
	 */
	protected static function _getFunctionParams(Point $start,Point $end) {
		$a = ($start->getLat()-$end->getLat())/($start->getLon() - $end->getLon());
		$b = $start->getLat()-$a*$start->getLon();
		return array($a,$b);
	}

	/**
	 * @param Point $p
	 * @throws \Exception
	 * @throws NotARingException
	 * @return bool
	 */
	public function ContainsPoint(Point $p) {
		if (!$this->IsRing())
			throw new NotARingException("LineString is no ring and therefor cannot contain points");
		if ($this->IsOnBorder($p))
			return true;
		$steps = $this->getPoints()->count()+1;
		for ($i = 0; $i < $steps;$i++) {
			$a = tan(deg2rad($i/($steps)*90));
			$b = $p->getLat()-$a*$p->getLon();
			/** @var IPointCollection $outerPoints  */
			$outerPoints = $this->getPoints();
			/** @var Point $outerPrev */
			$outerPrevTmp = $outerPoints->first();
			$cutPoints = 0;
			foreach ($outerPoints as $outerCurrent) {
				/** @var Point $outerCurrent */
				if ($outerPoints->first() === $outerCurrent)
					continue;

				$outerPrev = $outerPrevTmp;
				$outerPrevTmp = $outerCurrent;

				if (bccomp($a*$outerPrev->getLon()+$b,$outerPrev->getLat()) == 0 ||
					bccomp($a*$outerCurrent->getLon()+$b,$outerCurrent->getLat()) == 0)
					continue 2;

				if (self::_straightLine($outerPrev,$outerCurrent))
					$cutX = $outerPrev->getLon();
				else {
					list($a2,$b2) = self::_getFunctionParams($outerPrev,$outerCurrent);
					if ($a == $a2) {
						if ($b == $b2) {
							if ($p->getLon() < $outerPrev->getLon() &&
								$p->getLon() < $outerCurrent->getLon())
								continue;
							else {
								$steps++;
								continue 2;
							}
						}
						else
							continue;
					}
					else
						$cutX = ($b2-$b)/($a-$a2);
				}

				$higher = $outerPrev->getLat() > $outerCurrent->getLat()?$outerPrev:$outerCurrent;
				$lower = $outerCurrent === $higher?$outerPrev:$outerCurrent;

				$righter = $outerPrev->getLon() > $outerCurrent->getLon()?$outerPrev:$outerCurrent;
				$lefter = $outerCurrent === $righter?$outerPrev:$outerCurrent;

				if ($cutX == $p->getLon() &&
					$p->getLat() <= $higher->getLat() && $p->getLat() >= $lower->getLat() &&
					$p->getLon() <= $righter->getLon() && $p->getLon() >= $lefter->getLon())
					return true;

				$prev = bccomp($a*$outerPrev->getLon()+$b,$outerPrev->getLat()) > 0;
				$current = bccomp($a*$outerCurrent->getLon()+$b,$outerCurrent->getLat()) > 0;

				if ($prev != $current && $cutX > $p->getLon())
					$cutPoints++;
			}
			return (boolean) ($cutPoints % 2);
		}
		$points = array();
		$this->getPoints()->forAll(
			function ($key,Point $point) use (&$points) {
				return $points[$key] = $point->getLon().' '.$point->getLat();});
		print("Could not find line that does not touch a polygon-point (should not happen): ".
			'('.implode(',',$points)."),(".$p->getLon().' '.$p->getLat().')'."\n\n");
		throw new Exception("Could not find line that does not touch a polygon-point (should not happen): ".
			'('.implode(',',$points)."),(".$p->getLon().' '.$p->getLat().')'."\n\n");
	}
	/**
	 * @throws \Exception
	 * @throws NotARingException
	 * @return string
	 */
	public function GetSpinDirection() {
		if (!$this->IsRing())
			throw new NotARingException("LineString is no ring and therefor has no spin direction");
		$steps = $this->getPoints()->count()+6;

		$first = $this->getPoints()->first();
		$second = $this->getPoints()->get(1);
		/** @var Point $middlePoint  */
		$middlePoint = PointFactory::getInstance()->
			create('Point',
			       array(
			            'lat' => (double) ($first->getLat()-$second->getLat())/2+
				            $second->getLat(),
			            'lon' => (double) ($first->getLon()-$second->getLon())/2+
				            $second->getLon()
			       ));
		while ($this->IsOnCorner($middlePoint))
			$middlePoint = PointFactory::getInstance()->
				create('Point',
				       array(
				            'lat' => (double) ($first->getLat()-$middlePoint->getLat())/2+
					            $middlePoint->getLat(),
				            'lon' => (double) ($first->getLon()-$middlePoint->getLon())/2+
					            $middlePoint->getLon()
				       ));
		if (!self::_straightLine($first,$second)) {
			list($firstA) = self::_getFunctionParams($first,$second);
			if ($first->getLon() > $second->getLon())
				$reverse = true;
			else
				$reverse = false;
		}
		$higherFirst = $first->getLat() > $second->getLat()?$first:$second;

		$p = $middlePoint;

		for ($i = 0; $i < $steps;$i++) {
			$a = tan(deg2rad(($i+1)/($steps+2)*90));
			$b = $p->getLat()-$a*$p->getLon();
			if (isset($firstA) && $a == $firstA) continue;
			if (isset($firstA) && isset($reverse))
				$odd = ($a > $firstA)?(!$reverse?'l':'r'):(!$reverse?'r':'l');
			else
				if ($higherFirst == $first)
					$odd = 'l';
				else
					$odd = 'r';
			$even = self::_otherSide($odd);

			/** @var IPointCollection $outerPoints  */
			$outerPoints = $this->getPoints();
			/** @var Point $outerPrev */
			$outerPrevTmp = $outerPoints->first();
			$cutPoints = 0;
			foreach ($outerPoints as $outerCurrent) {
				/** @var Point $outerCurrent */
				if ($outerPoints->first() === $outerCurrent)
					continue;

				$outerPrev = $outerPrevTmp;
				$outerPrevTmp = $outerCurrent;

				if ($outerPrev->__toString() == $first->__toString()) continue;
				if ($outerCurrent->__toString() == $second->__toString()) continue;

				if (bccomp($a*$outerPrev->getLon()+$b,$outerPrev->getLat()) == 0 ||
					bccomp($a*$outerCurrent->getLon()+$b,$outerCurrent->getLat()) == 0)
					continue 2;

				if (self::_straightLine($outerPrev,$outerCurrent))
					$cutX = $outerPrev->getLon();
				else {
					list($a2,$b2) = self::_getFunctionParams($outerPrev,$outerCurrent);
					if ($a == $a2) {
						if ($b == $b2) {
							if ($p->getLon() < $outerPrev->getLon() &&
								$p->getLon() < $outerCurrent->getLon())
								continue;
							else {
								$steps++;
								continue 2;
							}
						}
						else
							continue;
					}
					else
						$cutX = ($b2-$b)/($a-$a2);
				}

				$higher = $outerPrev->getLat() > $outerCurrent->getLat()?$outerPrev:$outerCurrent;
				$lower = $outerCurrent === $higher?$outerPrev:$outerCurrent;

				$righter = $outerPrev->getLon() > $outerCurrent->getLon()?$outerPrev:$outerCurrent;
				$lefter = $outerCurrent === $righter?$outerPrev:$outerCurrent;

				if ($cutX == $p->getLon() &&
					$p->getLat() <= $higher->getLat() && $p->getLat() >= $lower->getLat() &&
					$p->getLon() <= $righter->getLon() && $p->getLon() >= $lefter->getLon())
					return $odd;

				$prev = bccomp($a*$outerPrev->getLon()+$b,$outerPrev->getLat()) > 0;
				$current = bccomp($a*$outerCurrent->getLon()+$b,$outerCurrent->getLat()) > 0;

				if ($prev != $current && $cutX > $p->getLon())
					$cutPoints++;
			}
			return ((boolean) ($cutPoints % 2))?$odd:$even;
		}
		$points = array();
		$this->getPoints()->forAll(
			function ($key,Point $point) use (&$points) {
				return $points[$key] = $point->getLon().' '.$point->getLat();});
		print("Could not find line that does not touch a polygon-point (should not happen): ".
			'('.implode(',',$points)."),(".$p->getLon().' '.$p->getLat().')'."\n\n");
		throw new Exception("Could not find line that does not touch a polygon-point (should not happen): ".
			                    '('.implode(',',$points)."),(".$p->getLon().' '.$p->getLat().')'."\n\n");
	}
	/**
	 * @param Point $p1start
	 * @param Point $p1end
	 * @param Point $p2start
	 * @param Point $p2end
	 * @return bool
	 */
	protected static function _isParallel(Point $p1start, Point $p1end,Point $p2start,Point $p2end) {
		if (!self::_straightLine($p1start,$p1end) && !self::_straightLine($p2start,$p2end)) {
			list($a1) = self::_getFunctionParams($p1start,$p1end);
			list($a2) = self::_getFunctionParams($p2start,$p2end);
			if ($a1 == $a2)
				return true;
			return false;
		}
		return (self::_straightLine($p1start,$p1end) && self::_straightLine($p2start,$p2end));
	}
	/**
	 * @param Point $p1start
	 * @param Point $p1end
	 * @param Point $p2start
	 * @param Point $p2end
	 * @return bool
	 */
	protected static function _isSameLine(Point $p1start, Point $p1end,Point $p2start,Point $p2end) {

		if (!self::_straightLine($p1start,$p1end) && !self::_straightLine($p2start,$p2end)) {
            list($a1,$b1) = self::_getFunctionParams($p1start,$p1end);
            list($a2,$b2) = self::_getFunctionParams($p2start,$p2end);
            if ($a1 == $a2 && $b1 == $b2)
	            return true;
			return false;
        }
        return (self::_straightLine($p1start,$p1end) && self::_straightLine($p2start,$p2end) &&
	        $p1start->getLon() == $p2start->getLon());
	}
	/**
	 * @param Point $p1start
	 * @param Point $p1end
	 * @param Point $p2start
	 * @param Point $p2end
	 * @return bool
	 */
	protected static function _outOfRange(Point $p1start,Point $p1end,Point $p2start,Point $p2end) {
		if ($p2start->getLon() < $p1start->getLon() &&
			$p2end->getLon() < $p1start->getLon() &&
			$p2start->getLon() < $p1end->getLon() &&
			$p2end->getLon() < $p1end->getLon())
			return true;
		if ($p2start->getLon() > $p1start->getLon() &&
			$p2end->getLon() > $p1start->getLon() &&
			$p2start->getLon() > $p1end->getLon() &&
			$p2end->getLon() > $p1end->getLon())
			return true;
		if ($p2start->getLat() < $p1start->getLat() &&
			$p2end->getLat() < $p1start->getLat() &&
			$p2start->getLat() < $p1end->getLat() &&
			$p2end->getLat() < $p1end->getLat())
			return true;
		if ($p2start->getLat() > $p1start->getLat() &&
			$p2end->getLat() > $p1start->getLat() &&
			$p2start->getLat() > $p1end->getLat() &&
			$p2end->getLat() > $p1end->getLat())
			return true;
		return false;
	}

	/**
	 * @param Point $point
	 * @param Point $start
	 * @param Point $end
	 * @return bool
	 */
	protected static function _pointOutOfRange(Point $point,Point $start,Point $end) {
		if ($start->getLon() < $point->getLon() &&
			$end->getLon() < $point->getLon())
			return true;
		if ($start->getLon() > $point->getLon() &&
			$end->getLon() > $point->getLon())
			return true;
		if ($start->getLat() < $point->getLat() &&
			$end->getLat() < $point->getLat())
			return true;
		if ($start->getLat() > $point->getLat() &&
			$end->getLat() > $point->getLat())
			return true;
		return false;
	}
	/**
	 * @param Point $p1
	 * @param Point $p2
	 * @return bool
	 */
	protected static function _twoEqualPoints($p1,$p2) {
		return $p1->getLon() == $p2->getLon() && $p1->getLat() == $p2->getLat();
	}
	/**
	 * @param Point $p1
	 * @param Point $p2
	 * @param Point $p3
	 * @param Point $p4
	 * @return array
	 */
	protected static function _equalPoints(Point $p1,Point $p2,Point $p3,Point $p4) {
		$equals = array();
		$tests = array(array($p1,$p2),array($p1,$p3),array($p1,$p4),array($p2,$p3),array($p2,$p4),array($p3,$p4));
		foreach ($tests as $testPoints)
			if (self::_twoEqualPoints($testPoints[0],$testPoints[1])) {
				$firstIn = false;
				$secondIn = false;
				array_walk($equals,function(Point $value,$key,$testPoints) use(&$firstIn,&$secondIn) {
					/** @var Point[] $testPoints */
					if ($testPoints[0]->getLon() == $value->getLon() &&
						$testPoints[0]->getLat() == $value->getLat())
						$firstIn = true;
					if ($testPoints[1]->getLon() == $value->getLon() &&
						$testPoints[1]->getLat() == $value->getLat())
						$secondIn = true;
				},$testPoints);
				if (!$firstIn)
					$equals[] = $testPoints[0];
				if (!$secondIn)
					$equals[] = $testPoints[1];
			}
		return $equals;
	}

	/**
	 * @param Point $p
	 * @param Point $start
	 * @param Point $end
	 * @return bool
	 */
	protected static function _isOnLine(Point $p, Point $start,Point $end) {
		if (self::_pointOutOfRange($p,$start,$end))
			return false;
		list($a,$b) = self::_getFunctionParams($start,$end);
		return $p->getLat() == $p->getLon()*$a+$b;
	}
		/**
	 * @param Point $p1start
	 * @param Point $p1end
	 * @param Point $p2start
	 * @param Point $p2end
	 * @return bool
	 */
	protected static function _couldIntersect(Point $p1start, Point $p1end,Point $p2start,Point $p2end) {
		if (self::_outOfRange($p1start,$p1end,$p2start,$p2end)) return false;
		if (!self::_straightLine($p1start,$p1end) && !self::_straightLine($p2start,$p2end)) {
			list($a1,$b1) = self::_getFunctionParams($p1start,$p1end);
			list($a2,$b2) = self::_getFunctionParams($p2start,$p2end);
			// Parallel lines
			if ($a1 == $a2 && $b1 != $b2)
				return false;
			if ($a1 == $a2 && $b1 == $b2)
				return true;
			// Both points above/below
			if ($p2start->getLat() > $a1*$p2start->getLon()+$b1 &&
				$p2end->getLat() > $a1*$p2end->getLon()+$b1)
				return false;
			if ($p2start->getLat() < $a1*$p2start->getLon()+$b1 &&
				$p2end->getLat() < $a1*$p2end->getLon()+$b1)
				return false;
			// X-value of cutting point
			$cutX = ($b2-$b1)/($a1-$a2);
			// Is the cutting point of the lines within the points?
			if ($p1start->getLon() < $p1end->getLon() && $cutX < $p1start->getLon())
				return false;
			elseif ($p1start->getLon() > $p1end->getLon() && $cutX < $p1end->getLon())
				return false;
			elseif ($p2start->getLon() < $p2end->getLon() && $cutX < $p2start->getLon())
				return false;
			elseif ($p2start->getLon() > $p2end->getLon() && $cutX < $p2end->getLon())
				return false;
			elseif ($p1start->getLon() > $p1end->getLon() && $cutX > $p1start->getLon())
				return false;
			elseif ($p1start->getLon() < $p1end->getLon() && $cutX > $p1end->getLon())
				return false;
			elseif ($p2start->getLon() > $p2end->getLon() && $cutX > $p2start->getLon())
				return false;
			elseif ($p2start->getLon() < $p2end->getLon() && $cutX > $p2end->getLon())
				return false;
			return true;
		}
		elseif (self::_straightLine($p1start,$p1end) && self::_straightLine($p2start,$p2end)) {
			// possible obsolete because of the out-of-range-checks
			if (!self::_straightLine($p1start,$p2start))
				return false;
			$higher1 = $p1start->getLat() > $p1end->getLat()?$p1start:$p1end;
			$lower1 = ($higher1 === $p1end)?$p1start:$p1end;
			$higher2 = $p2start->getLat() > $p2end->getLat()?$p2start:$p2end;
			$lower2 = ($higher2 === $p2end)?$p2start:$p2end;
			if ($higher1->getLat() < $lower2->getLat())
				return false;
			if ($higher2->getLat() < $lower1->getLat())
				return false;
			return true;
		}
		list($straight1,$straight2) =
			(self::_straightLine($p1start,$p1end))?array($p1start,$p1end):array($p2start,$p2end);
		/** @var Point $straight1 */
		/** @var Point $straight2 */
		$higher = $straight1->getLat() > $straight2->getLat()?$straight1:$straight2;
		$lower = ($higher === $straight2)?$straight1:$straight2;

		list($nStraight1,$nStraight2) = ($straight1 === $p1start)?array($p2start,$p2end):array($p1start,$p1end);
		list($a,$b) = self::_getFunctionParams($nStraight1,$nStraight2);
		return bccomp($a*$straight1->getLon()+$b,$lower->getLat()) >= 0 &&
			bccomp($a*$straight1->getLon()+$b,$higher->getLat()) <= 0;
	}

	/**
	 * @param Point $p1start
	 * @param Point $p1end
	 * @param Point $p2start
	 * @param Point $p2end
	 * @return Point
	 */
	protected static function _getIntersectingPoint(Point $p1start, Point $p1end,Point $p2start,Point $p2end) {
		if (self::_outOfRange($p1start,$p1end,$p2start,$p2end)) return null;
		if (!self::_straightLine($p1start,$p1end) && !self::_straightLine($p2start,$p2end)) {
			list($a1,$b1) = self::_getFunctionParams($p1start,$p1end);
			list($a2,$b2) = self::_getFunctionParams($p2start,$p2end);
			$cutX = ($b2-$b1)/($a1-$a2);
			return PointFactory::getInstance()->create('Point',array('lon' => $cutX,'lat' => $a1*$cutX+$b1));
		}
		elseif (self::_straightLine($p1start,$p1end) && self::_straightLine($p2start,$p2end)) {
			if ($p1start == $p2start) return $p1start;
			elseif ($p1end == $p2start) return $p1end;
			elseif ($p1start == $p2end) return $p1start;
			elseif ($p1end == $p2end) return $p1end;
			return null;
		}
		list($straight1) =
			(self::_straightLine($p1start,$p1end))?array($p1start,$p1end):array($p2start,$p2end);
		/** @var Point $straight1 */
		list($nStraight1,$nStraight2) = ($straight1 === $p1start)?array($p2start,$p2end):array($p1start,$p1end);
		list($a,$b) = self::_getFunctionParams($nStraight1,$nStraight2);
		return PointFactory::getInstance()->create('Point',
		                                           array(
		                                                'lon' => $straight1->getLon(),
		                                                'lat' => $a*$straight1->getLon()+$b));
	}
	/**
	 * @param Point $p1start
	 * @param Point $p1end
	 * @param Point $p2start
	 * @param Point $p2end
	 * @return array
	 */
	protected static function _whoTouches(Point $p1start, Point $p1end,Point $p2start,Point $p2end) {
		$touches = self::_equalPoints($p1start,$p1end,$p2start,$p2end);
		if (count($touches) == 4)
			return $touches;
		if (self::_outOfRange($p1start,$p1end,$p2start,$p2end) && empty($equalPoints)) return array();
		if (!self::_straightLine($p1start,$p1end) && !self::_straightLine($p2start,$p2end)) {
			list($a1,$b1) = self::_getFunctionParams($p1start,$p1end);
			list($a2,$b2) = self::_getFunctionParams($p2start,$p2end);
			// Parallel lines
			if ($a1 == $a2 && $b1 != $b2)
				return array();
			if ($a1 == $a2 && $b1 == $b2) {
				if ($a1 != 0) {
					$higher1 = $p1start->getLat() > $p1end->getLat()?$p1start:$p1end;
					$lower1 = ($higher1 === $p1end)?$p1start:$p1end;
					$higher2 = $p2start->getLat() > $p2end->getLat()?$p2start:$p2end;
					$lower2 = ($higher2 === $p2end)?$p2start:$p2end;
					if ($higher1->getLat() < $higher2->getLat()) {
						if ($higher1->getLat() > $lower2->getLat() && !in_array($higher1,$touches,true))
							$touches[] = $higher1;
						if ($lower1->getLat() > $lower2->getLat() && !in_array($lower1,$touches,true))
							$touches[] = $lower1;
						if ($lower2->getLat() > $lower1->getLat() && !in_array($lower2,$touches,true))
							$touches[] = $lower2;
					}
					elseif ($higher1->getLat() > $higher2->getLat()) {
						if ($higher2->getLat() > $lower1->getLat() && !in_array($higher2,$touches,true))
							$touches[] = $higher2;
						if ($lower2->getLat() > $lower1->getLat() && !in_array($lower2,$touches,true))
							$touches[] = $lower2;
						if ($lower1->getLat() > $lower2->getLat() && !in_array($lower1,$touches,true))
							$touches[] = $lower1;
					}
					else {
						if ($lower1->getLat() > $lower2->getLat() && !in_array($lower1,$touches,true))
							$touches[] = $lower1;
						if ($lower1->getLat() < $lower2->getLat() && !in_array($lower2,$touches,true))
							$touches[] = $lower2;
					}
				}
				else {
					$righter1 = $p1start->getLon() > $p1end->getLon()?$p1start:$p1end;
					$lefter1 = ($righter1 === $p1end)?$p1start:$p1end;
					$righter2 = $p2start->getLon() > $p2end->getLon()?$p2start:$p2end;
					$lefter2 = ($righter2 === $p2end)?$p2start:$p2end;
					if ($righter1->getLon() < $righter2->getLon()) {
						if ($righter1->getLon() > $lefter2->getLon() && !in_array($righter1,$touches,true))
							$touches[] = $righter1;
						if ($lefter1->getLon() > $lefter2->getLon() && !in_array($lefter1,$touches,true))
							$touches[] = $lefter1;
						if ($lefter2->getLon() > $lefter1->getLon() && !in_array($lefter2,$touches,true))
							$touches[] = $lefter2;
					}
					elseif ($righter1->getLon() > $righter2->getLon()) {
						if ($righter2->getLon() > $righter1->getLon() && !in_array($righter2,$touches,true))
							$touches[] = $righter2;
						if ($lefter2->getLon() > $lefter1->getLon() && !in_array($lefter2,$touches,true))
							$touches[] = $lefter2;
						if ($lefter1->getLon() > $lefter2->getLon() && !in_array($lefter1,$touches,true))
							$touches[] = $lefter1;
					}
					else {
						if ($lefter1->getLon() > $lefter2->getLon() && !in_array($lefter1,$touches,true))
							$touches[] = $lefter1;
						if ($lefter1->getLon() < $lefter2->getLon() && !in_array($lefter2,$touches,true))
							$touches[] = $lefter2;
					}
				}
				return $touches;
			}
			$cutX = ($b2-$b1)/($a1-$a2);
			if ($p1start->getLon() == $cutX)
				if (!in_array($p1start,$touches,true)) $touches[] = $p1start;
			if ($p1end->getLon() == $cutX)
				if (!in_array($p1end,$touches,true)) $touches[] = $p1end;
			if ($p2start->getLon() == $cutX)
				if (!in_array($p2start,$touches,true)) $touches[] = $p2start;
			if ($p2end->getLon() == $cutX)
				if (!in_array($p2end,$touches,true)) $touches[] = $p2end;
			return $touches;
		}
		elseif (self::_straightLine($p1start,$p1end) && self::_straightLine($p2start,$p2end)) {
			if (!self::_straightLine($p1start,$p2start))
				return array();
			$higher1 = $p1start->getLat() > $p1end->getLat()?$p1start:$p1end;
			$lower1 = ($higher1 === $p1end)?$p1start:$p1end;
			$higher2 = $p2start->getLat() > $p2end->getLat()?$p2start:$p2end;
			$lower2 = ($higher2 === $p2end)?$p2start:$p2end;
			if ($higher1->getLat() < $lower2->getLat())
				return array();
			if ($higher2->getLat() < $lower1->getLat())
				return array();
			if ($higher1->getLat() <= $higher2->getLat() && $higher1->getLat() >= $lower2->getLat())
				if (!in_array($higher1,$touches,true)) $touches[] = $higher1;
			if ($lower1->getLat() <= $higher2->getLat() && $lower1->getLat() >= $lower2->getLat())
				if (!in_array($lower1,$touches,true)) $touches[] = $lower1;
			if ($higher2->getLat() <= $higher1->getLat() && $higher2->getLat() >= $lower1->getLat())
				if (!in_array($higher2,$touches,true)) $touches[] = $higher2;
			if ($lower2->getLat() <= $higher1->getLat() && $lower2->getLat() >= $lower1->getLat())
				if (!in_array($lower2,$touches,true)) $touches[] = $lower2;
			return $touches;
		}
		list($straight1,$straight2) =
			(self::_straightLine($p1start,$p1end))?array($p1start,$p1end):array($p2start,$p2end);
		/** @var Point $straight1 */
		/** @var Point $straight2 */
		$higher = $straight1->getLat() > $straight2->getLat()?$straight1:$straight2;
		$lower = $higher === $straight2?$straight1:$straight2;

		/** @var Point $nStraight1 */
		/** @var Point $nStraight2 */
		list($nStraight1,$nStraight2) = ($straight1 === $p1start)?array($p2start,$p2end):array($p1start,$p1end);
		list($a,$b) = self::_getFunctionParams($nStraight1,$nStraight2);

		// Is the touching point the end of the straight line or the end of the non-straight line or both?
		if ($nStraight1->getLon() == $straight1->getLon() &&
			$nStraight1->getLat() >= $lower->getLat() &&
			$nStraight1->getLat() <= $higher->getLat())
			if (!in_array($nStraight1,$touches,true)) $touches[] = $nStraight1;
		if ($nStraight2->getLon() == $straight1->getLon() &&
			$nStraight2->getLat() >= $lower->getLat() &&
			$nStraight2->getLat() <= $higher->getLat())
			if (!in_array($nStraight2,$touches,true)) $touches[] = $nStraight2;

		if (bccomp($a*$straight1->getLon()+$b,$straight1->getLat()) == 0)
			if (!in_array($straight1,$touches,true)) $touches[] = $straight1;
		if (bccomp($a*$straight2->getLon()+$b,$straight2->getLat()) == 0)
			if (!in_array($straight2,$touches,true)) $touches[] = $straight2;
		return $touches;
	}
	/**
	 * @param Point $startPoint
	 * @param Point $endPoint
	 * @param Point $point
	 * @return null|string
	 * @throws \Exception
	 */
	protected static function _onWhichSide(Point $startPoint,Point $endPoint,Point $point) {
		if ($startPoint->getLon() == $endPoint->getLon() && $startPoint->getLat() == $endPoint->getLat())
			throw new Exception("Unable to decide on which side a point is, ".
				                    "because the line is described by the same points");
		if (self::_straightLine($startPoint,$endPoint)) {
			if ($startPoint->getLat() > $endPoint->getLat())
				$reverse = true;
			else
				$reverse = false;
			if ($point->getLon() > $startPoint->getLon())
				return $reverse?'l':'r';
			elseif ($point->getLon() < $startPoint->getLon())
				return $reverse?'r':'l';
			return null;
		}
		else {
			if ($startPoint->getLon() > $endPoint->getLon())
				$reverse = true;
			else
				$reverse = false;
			list ($a,$b) = self::_getFunctionParams($startPoint,$endPoint);
			switch (bccomp($point->getLon()*$a+$b,$point->getLat())) {
				case -1: $return = ($reverse?'r':'l'); break;
				case 1: $return = ($reverse?'l':'r'); break;
				default: $return = null; break;
			}
			return $return;
		}
	}
	/**
	 * @param Point $p1
	 * @param Point $p2
	 * @return float
	 */
	protected static function _distance(Point $p1,Point $p2) {
		if ($p1 == $p2)
			return 0;
		if (self::_straightLine($p1,$p2))
			return abs($p2->getLat()-$p1->getLat());
		return abs(sqrt(pow($p2->getLon()-$p1->getLon(),2)+pow($p2->getLat()-$p1->getLat(),2)));
	}

	/**
	 * @param null|string $side
	 * @return null|string
	 */
	protected static function _otherSide($side) {
		return is_null($side)?null:($side == 'l')?'r':'l';
	}

	/**
	 * @param array $touches
	 * @return array
	 */
	protected static function _cleanTouchLine(array $touches) {
		/** @var Point[] $cleanTouches */
		$cleanTouches = array();
		$countBefore = count($touches);
		/** @var Point[] $touches */
		foreach ($touches as $nr => $touch)
			if (isset($touches[$nr+1]) &&
				$touches[$nr+1]->__toString() == $touch->__toString())
				continue;
			elseif ($countBefore-1 != $nr || !isset($cleanTouches[0])
				|| $cleanTouches[0]->__toString() != $touch->__toString())
				$cleanTouches[] = $touch;
		if ($countBefore > count($cleanTouches))
			return self::_cleanTouchLine($cleanTouches);
		else
			return $cleanTouches;
	}

	/**
	 * @param Point $p1
	 * @param Point $p2
	 * @param Point $p3
	 * @param Point $t1
	 * @param Point $t2
	 * @param Point $t3
	 * @return array
	 */
	protected static function _twoTouchesStraight(Point $p1,Point $p2,Point $p3,Point $t1,Point $t2,Point $t3) {
		if (is_null($p3) || is_null($t3))
			return array('intersects' => false,'touches' => array($p2));
		if (self::_onWhichSide($t1,$t2,$t3) == self::_onWhichSide($p1,$p2,$p3))
			return array('intersects' => false,'touches' => array($p2),
			             'side' => self::_onWhichSide($t1,$t2,$p3));
		$before = self::_onWhichSide($t2,$t3,$p1);
		if (is_null(self::_onWhichSide($t2,$t3,$p3)))
			return array('side' => $before,'touches' => array($p2));
		return array('intersects' => $intersects = ((self::_onWhichSide($t2,$t3,$p3) !=
			$before)?true:false),
		             'touches' => array($p2),'side' => $intersects
													   ?array($before,self::_otherSide($before))
													   :$before);
	}

	/**
	 * @param Point $point
	 * @param Point $onThisSide
	 * @param Point $notOnThisSide
	 * @return bool
	 */
	protected static function _pointOnLineAndSide(Point $point,Point $onThisSide,Point $notOnThisSide) {
		return is_null(self::_onWhichSide($onThisSide,$notOnThisSide,$point)) &&
			(self::_distance($notOnThisSide,$onThisSide) > self::_distance($onThisSide,$point) ||
				self::_distance($onThisSide,$point) < self::_distance($notOnThisSide,$point));
	}

	/**
	 * @param string $side
	 * @param Point $linePoint1
	 * @param Point $linePoint2
	 * @param Point $linePoint3
	 * @param Point $point
	 * @return string
	 */
	protected static function _onSameSideTriangle($side,Point $linePoint1,Point $linePoint2,Point $linePoint3,
	                                              Point $point) {
		return (self::_onWhichSide($linePoint1,$linePoint2,$point) !=
			$side || self::_onWhichSide($linePoint2,$linePoint3,$point) !=
			$side)?self::_otherSide($side):$side;
	}

	/**
	 * @param Point $p1
	 * @param Point $p2
	 * @param Point $p3
	 * @param Point $t1
	 * @param Point $t2
	 * @param Point $t3
	 * @return array
	 * @throws \Exception
	 */
	protected static function _twoTouchesNotStraight(Point $p1,Point $p2,Point $p3,Point $t1,Point $t2,Point $t3) {
		if (is_null($p3) || is_null($t3))
			return array('intersects' => false,'touches' => array($p2));
		$triangle = self::_onWhichSide($t1,$t2,$t3);
		$p1t1 = self::_pointOnLineAndSide($p1,$t1,$t2);
		$p1t3 = self::_pointOnLineAndSide($p1,$t3,$t2);
		$p3t1 = self::_pointOnLineAndSide($p3,$t1,$t2);
		$p3t3 = self::_pointOnLineAndSide($p3,$t3,$t2);
		$before = null;
		$after = null;
		$reverse = false;
		if (!$p1t1 && !$p1t3)
			$before = self::_onSameSideTriangle($triangle,$t1,$t2,$t3,$p1);
		if (!$p3t1 && !$p3t3)
			$after = self::_onSameSideTriangle($triangle,$t1,$t2,$t3,$p3);
		if ($p1t3 || $p3t1)
			$reverse = true;
		if (is_null($before) && is_null($after))
			return array('touches' => array($p2),'reverse' => $reverse);
		elseif (is_null($before) && !is_null($after))
			return array('side' => $after,'touches' => array($p2),'direction' => 'out','reverse' => $reverse);
		elseif (!is_null($before) && is_null($after))
			return array('side' => $before,'touches' => array($p2),'direction' => 'in','reverse' => $reverse);
		elseif (!is_null($before) && !is_null($after) && $before != $after)
			return array('side' => array($before,$after),'intersects' => true,'touches' => array($p2));
		else // if (!is_null($before) && !is_null($after) && $before == $after)
			return array('side' => $before,'intersects' => false,'touches' => array($p2));
	}

	/**
	 * @param Point    $p1
	 * @param Point    $t1
	 * @param callable $next1
	 * @param callable $prev1
	 * @param callable $next2
	 * @param callable $prev2
	 * @throws \DpOpenGis\Exception\WrongDirectionException
	 * @throws \Exception
	 * @return array|null
	 */
	protected static function _getStateOfTouch(Point $p1,Point $t1,Closure $next1,Closure $prev1,Closure $next2,Closure $prev2) {
		/** @var Point $p2 */
		$p2 = $next1($p1);
		/** @var Point $t2 */
		$t2 = $next2($t1);
		/** @return Point */
		$getP3 = function() use (&$p3,$next1,$p2) { if (is_null($p3)) $p3 = $next1($p2); return $p3;};
		/** @return Point */
		$getT3 = function() use (&$t3,$next2,$t2) { if (is_null($t3)) $t3 = $next2($t2); return $t3;};
		/** @return Point */
		$getP0 = function() use (&$p0,$prev1,$p1) { if (is_null($p0)) $p0 = $prev1($p1); return $p0;};
		/** @return Point */
		$getT0 = function() use (&$t0,$prev2,$t1) { if (is_null($t0)) $t0 = $prev2($t1); return $t0;};
		/** @var Point[] $touches */
		$touches = self::_whoTouches($p1,$p2,$t1,$t2);
		if (count($touches) == 1) {
			if (in_array($p1,$touches,true))
				return array('side' => self::_onWhichSide($t1,$t2,$p2),'touches' => array($p1),'direction' => 'out',
				             'nonDirectionHold' => 2);
			elseif (in_array($p2,$touches,true))
				return array('side' => self::_onWhichSide($t1,$t2,$p1),'touches' => array($p2),'direction' => 'in',
				             'nonDirectionHold' => 2);
			elseif (in_array($t1,$touches,true)) {
				if (is_null($getT0()))
					return array('intersects' => false,'touches' => $touches);
				if (!self::_isSameLine($getT0(),$t1,$p1,$p2))
					return array(
						'intersects' => $intersects = (self::_onWhichSide($getT0(),$t1,$p1) !=
							self::_onWhichSide($t1,$t2,$p2)),
						'touches' => $touches,'side' => $intersects
							?array(self::_onWhichSide($getT0(),$t1,$p1),self::_onWhichSide($getT0(),$t1,$p2))
							:self::_onWhichSide($getT0(),$t1,$p1));
				if (self::_distance($getT0(),$p1) > self::_distance($getT0(),$p2))
					throw new WrongDirectionException("Wrong direction");
				return array('side' => self::_onWhichSide($t1,$t2,$p2),'touches' => array($t1),'direction' => 'out',
				             'nonDirectionHold' => 1);
			}
			else { // if (in_array($t2,$touches,true)) {
				if (is_null($getT3()))
					return array('intersects' => false,'touches' => $touches);
				if (!self::_isSameLine($getT3(),$t2,$p1,$p2))
					return array(
						'intersects' => $intersects = (self::_onWhichSide($p1,$p2,$t1) !=
							self::_onWhichSide($p1,$p2,$getT3())),
						'touches' => $touches,'side' => $intersects
							?array(self::_onWhichSide($t1,$t2,$p1),self::_onWhichSide($t1,$getT3(),$p2))
							:self::_onWhichSide($t1,$t2,$p1));
				if (self::_distance($getT3(),$p1) < self::_distance($getT3(),$p2))
					throw new WrongDirectionException("Wrong direction");
				return array('side' => self::_onWhichSide($t1,$t2,$p1),'touches' => array($t2),'direction' => 'in');
			}
		}
		elseif (count($touches) == 2) {
			// Special case: Xi---Yj===Yk---Xl
			if (in_array($p1,$touches,true) && in_array($p2,$touches,true)) {
				$touchesDirection = self::_distance($t1,$p1) < self::_distance($t1,$p2)?array($p1,$p2):array($p2,$p1);
				if (is_null($getP0()) || is_null($getP3()))
					return array('intersects' => false,'touches' => $touchesDirection);
				else
					return array('intersects' => $intersects = (self::_onWhichSide($t1,$t2,$getP0()) !=
						self::_onWhichSide($t1,$t2,$getP3())),'touches' => $touchesDirection,'side' =>
						$intersects
							?array(self::_onWhichSide($t1,$t2,$getP0()),self::_onWhichSide($t1,$t2,$getP3()))
							:self::_onWhichSide($t1,$t2,$getP0()));
			}
			elseif (in_array($t1,$touches,true) && in_array($t2,$touches,true)) {
				$touchesDirection = self::_distance($p1,$t1) < self::_distance($p1,$t2)?array($t1,$t2):array($t2,$t1);
				if (is_null($getT0()) || is_null($getT3()))
					return array('intersects' => false,'touches' => $touchesDirection);
				else
					return array('intersects' => $intersects = (self::_onWhichSide($p1,$p2,$getT0()) !=
						self::_onWhichSide($p1,$p2,$getT3())),'touches' => $touchesDirection,'side' =>
						$intersects
							?(self::_distance($p1,$t1) < self::_distance($p1,$t2)
								?array(self::_onWhichSide($getT0(),$t1,$p1),self::_onWhichSide($t2,$getT3(),$p2))
								:array(self::_onWhichSide($getT0(),$t1,$p2),self::_onWhichSide($t2,$getT3(),$p1)))
							:self::_onWhichSide($p1,$p2,$getT0()));
			}
			// Special case: Xi---Yj===Xk---Yl
			elseif ($touches[0]->__toString() != $touches[1]->__toString() && self::_isSameLine($p1,$p2,$t1,$t2)) {
				if (in_array($p2,$touches,true) && in_array($t2,$touches,true)) {
					if (is_null($getP3()) || is_null($getT3()))
						return array('intersects' => false,'touches' => array($t2,$p2));
					else
						return array('intersects' => $intersects = (self::_onWhichSide($p1,$p2,$getP3()) !=
							self::_onWhichSide($t1,$t2,$getT3())),'touches' => array($t2,$p2),'side' =>
							$intersects
								?array(self::_otherSide(self::_onWhichSide($t1,$t2,$getT3())),
								       self::_onWhichSide($t1,$t2,$getT3()))
								:self::_onWhichSide($p1,$p2,$getP3()));
				}
				elseif (in_array($p1,$touches,true) && in_array($t2,$touches,true)) {
					if (is_null($getP0()) || is_null($getT3()))
						return array('intersects' => false,'touches' => array($p1,$t2));
					else
						return array('intersects' => $intersects = (self::_onWhichSide($t1,$t2,$getP0()) !=
							self::_onWhichSide($t2,$getT3(),$p2)),'touches' => array($p1,$t2),'side' =>
							$intersects
								?array(self::_onWhichSide($t1,$t2,$getP0()),self::_onWhichSide($t2,$getT3(),$p2))
								:self::_onWhichSide($t1,$t2,$getP0()));
				}
				elseif (in_array($p2,$touches,true) && in_array($t1,$touches,true))
					if (is_null($getP3()) || is_null($getT0()))
						return array('intersects' => false,'touches' => array($t1,$p2));
					else
						return array('intersects' => $intersects = (self::_onWhichSide($getT0(),$t1,$p1) !=
							self::_onWhichSide($t1,$t2,$getP3())),'touches' => array($t1,$p2),'side' =>
							$intersects
								?array(self::_onWhichSide($getT0(),$t1,$p1),self::_onWhichSide($t1,$t2,$getP3()))
								:self::_onWhichSide($p1,$p2,$getP3()));
				else { //if (in_array($p1,$touches,true) && in_array($t1,$touches,true)) {
					if (is_null($getP0()) || is_null($getT0()))
						return array('intersects' => false,'touches' => array($p1,$t1));
					else
						return array('intersects' => $intersects = (self::_onWhichSide($p2,$p1,$getP0()) !=
							self::_onWhichSide($t2,$t1,$getT0())),'touches' => array($p1,$t1),'side' =>
							$intersects
								?array(self::_onWhichSide($t1,$t2,$getP0()),
								       self::_otherSide(self::_onWhichSide($t1,$t2,$getP0())))
								:self::_onWhichSide($p2,$p1,$getP0()));
				}
			}
			elseif ($touches[0]->__toString() != $touches[1]->__toString() && !self::_isSameLine($p1,$p2,$t1,$t2))
				throw new Exception("Should not happen (".__LINE__.")");
			// Special case: Xi---YkXj---Yl, Yk == Xj
			elseif (self::_isSameLine($p1,$p2,$t1,$t2)) {
				if (in_array($p2,$touches,true) && in_array($t2,$touches,true)) {
					$result = self::_twoTouchesStraight($p1,$p2,$getP3(),$t1,$t2,$getT3());
					if (!isset($result['intersects']))
						$result = array('side' => $result['side'],'touches' => $result['touches'],'direction' => 'in');
					return $result;
				}
				elseif (in_array($p1,$touches,true) && in_array($t2,$touches,true)) {
					$result = self::_twoTouchesStraight($p2,$p1,$getP0(),$t1,$t2,$getT3());
					if (!isset($result['intersects']))
						throw new WrongDirectionException();
					elseif ($result['intersects'] && is_array($result['side']))
						$result['side'] = array_reverse($result['side']);
					elseif ($result['intersects'] || is_array($result['side']))
						throw new Exception("Should not happen (".__LINE__.")");
					return $result;
				}
				elseif (in_array($p1,$touches,true) && in_array($t1,$touches,true)) {
					$result = self::_twoTouchesStraight($p2,$p1,$getP0(),$t2,$t1,$getT0());
					if (!isset($result['intersects']))
						$result = array('side' => self::_otherSide($result['side']),'touches' => $result['touches'],
						                'direction' => 'out');
					elseif (!$result['intersects'] && !is_array($result['side']))
						$result['side'] = self::_otherSide($result['side']);
					elseif (!$result['intersects'] || !is_array($result['side']))
						throw new Exception("Should not happen");
					return $result;
				}
				else { //if (in_array($p2,$touches,true) && in_array($t1,$touches,true)) {
					$result = self::_twoTouchesStraight($p1,$p2,$getP3(),$t2,$t1,$getT0());
					if (!isset($result['intersects']))
						throw new WrongDirectionException();
					elseif ($result['intersects'] && is_array($result['side']))
						$result['side'] = array_reverse($result['side']);
					elseif (!$result['intersects'] && !is_array($result['side']))
						$result['side'] = self::_otherSide($result['side']);
					else
						throw new Exception("Should not happen (".__LINE__.")");
					return $result;
				}
			}
			elseif (in_array($p2,$touches,true) && in_array($t2,$touches,true)) {
				if (is_null($getP3()) || is_null($getT3()))
					return array('intersects' => false,'touches' => array($p2));
				$result = self::_twoTouchesNotStraight($p1,$p2,$getP3(),$t1,$t2,$getT3());
				if (isset($result['reverse']) && $result['reverse'])
					throw new WrongDirectionException();
				//if (isset($result['direction']) && $result['direction'] == 'out')
					//throw new Exception("Should not happen (".__LINE__.")");
				if (isset($result['reverse'])) unset($result['reverse']);
				$result['touches'] = array($p2);
				return $result;
				/*$triangle = self::_onWhichSide($t1,$t2,$getT3());
				$before = (self::_onWhichSide($t1,$t2,$p1) !=
					$triangle || self::_onWhichSide($t2,$getT3(),$p1) !=
					$triangle)?self::_otherSide($triangle):$triangle;
				if (is_null(self::_onWhichSide($t2,$getT3(),$getP3())) &&
					(self::_distance($getT3(),$t2) > self::_distance($getT3(),$getP3()) ||
						self::_distance($getP3(),$getT3()) < self::_distance($getP3(),$t2)))
					return array('side' => $before,'touches' => array($p2),'direction' => 'in');
				if (is_null(self::_onWhichSide($t1,$t2,$getP3())) &&
					(self::_distance($t1,$t2) > self::_distance($t1,$getP3()) ||
						self::_distance($getP3(),$t1) < self::_distance($getP3(),$t2)))
					throw new WrongDirectionException();
				return array('intersects' => $intersects = (((self::_onWhichSide($t1,$t2,$getP3()) !=
					$triangle || self::_onWhichSide($t2,$getT3(),$getP3()) !=
					$triangle)?self::_otherSide($triangle):$triangle) != $before?true:false),
				             'touches' => array($p2),'side' => $intersects
						?array($before,self::_otherSide($before))
						:$before);*/
			}
			elseif (in_array($p1,$touches,true) && in_array($t2,$touches,true)) {
				if (is_null($getP0()) || is_null($getT3()))
					return array('intersects' => false,'touches' => array($p1));
				$result = self::_twoTouchesNotStraight($getP0(),$p1,$p2,$t1,$t2,$getT3());
				if (isset($result['reverse']) && $result['reverse'])
					throw new WrongDirectionException();
				if (isset($result['direction']) && $result['direction'] == 'in'
					|| !isset($result['direction']) && !isset($result['intersects']))
					$result['directionHold'] = 1;
				elseif (isset($result['direction']) && $result['direction'] == 'out')
					$result['directionHold'] = 1;
				if (isset($result['reverse'])) unset($result['reverse']);
				$result['touches'] = array($p1);
				return $result;
				/*$triangle = self::_onWhichSide($t1,$t2,$getT3());
				if (is_null(self::_onWhichSide($t2,$getT3(),$p2)) &&
					(self::_distance($t2,$getT3()) > self::_distance($getT3(),$p2) ||
						self::_distance($getT3(),$p2) < self::_distance($t1,$p2))) {
					if (is_null(self::_onWhichSide($t1,$t2,$getP0())) &&
						(self::_distance($t1,$t2) > self::_distance($t1,$getP0()) ||
							self::_distance($getP0(),$t1) < self::_distance($getP0(),$t2)))
						return array('touches' => array($p1),'hold' => 1);
					else
						return array('side' => (self::_onWhichSide($t1,$t2,$getP0()) !=
							$triangle || self::_onWhichSide($t2,$t3,$getP0()) !=
							$triangle)?self::_otherSide($triangle):$triangle,'touches' => array($p1),
						             'direction' => 'in','hold' => 1);
				}
				$before = (self::_onWhichSide($t1,$t2,$p2) !=
					$triangle || self::_onWhichSide($t2,$getT3(),$p2) !=
					$triangle)?self::_otherSide($triangle):$triangle;
				if (is_null(self::_onWhichSide($t2,$getT3(),$getP0())) &&
					(self::_distance($getT3(),$t2) > self::_distance($getT3(),$getP0()) ||
						self::_distance($getP0(),$getT3()) < self::_distance($getP0(),$t2)))
					throw new WrongDirectionException();
				if (is_null(self::_onWhichSide($t1,$t2,$getP0())) &&
					(self::_distance($t1,$t2) > self::_distance($t1,$getP0()) ||
						self::_distance($getP0(),$t1) < self::_distance($getP0(),$t2)))
					return array('side' => $before,'touches' => array($p1),'direction' => 'out','hold' => 2);
				return array('intersects' => $intersects = (((self::_onWhichSide($t1,$t2,$getP0()) !=
					$triangle || self::_onWhichSide($t2,$getT3(),$getP0()) !=
					$triangle)?self::_otherSide($triangle):$triangle) != $before?true:false),
				             'touches' => array($p1),'side' => $intersects
						?array($before,self::_otherSide($before))
						:$before);
				*/
			}
			elseif (in_array($p2,$touches,true) && in_array($t1,$touches,true)) {
				if (is_null($getP3()) || is_null($getT0()))
					return array('intersects' => false,'touches' => array($p2));
				$result = self::_twoTouchesNotStraight($p1,$p2,$getP3(),$getT0(),$t1,$t2);
				if (isset($result['reverse']) && $result['reverse'])
					throw new WrongDirectionException();
				if (isset($result['direction']) && $result['direction'] == 'out'
					|| !isset($result['direction']) && !isset($result['intersects']))
					$result['directionHold'] = 2;
				elseif (isset($result['direction']) && $result['direction'] == 'in')
					$result['directionHold'] = 2;
				if (isset($result['reverse'])) unset($result['reverse']);
				$result['touches'] = array($p2);
				return $result;

				/*$triangle = self::_onWhichSide($getT0(),$t1,$t2);
				if (is_null(self::_onWhichSide($p2,$getP3(),$t2)) &&
					(self::_distance($p2,$getP3()) > self::_distance($p2,$t2) ||
						self::_distance($getP3(),$t2) < self::_distance($t2,$p2))) {
					if (is_null(self::_onWhichSide($p1,$p2,$getT0())) &&
						(self::_distance($p1,$p2) > self::_distance($p1,$getT0()) ||
							self::_distance($getT0(),$p1) < self::_distance($getT0(),$p2)))
						return array('touches' => array($p1),'hold' => 2);
					else
						return array('side' => (self::_onWhichSide($getT0(),$t1,$p1) !=
							$triangle || self::_onWhichSide($t1,$t2,$p1) !=
							$triangle)?self::_otherSide($triangle):$triangle,'touches' => array($p1),
						             'direction' => 'in','hold' => 2);
				}
				if (is_null(self::_onWhichSide($getT0(),$t1,$p1)) &&
					(self::_distance($getT0(),$t1) > self::_distance($t1,$p1) ||
						self::_distance($getT0(),$p1) < self::_distance($t1,$p1)))
					return array('side' => (self::_onWhichSide($getT0(),$t1,$getP3()) !=
						$triangle || self::_onWhichSide($t1,$t2,$getP3()) !=
						$triangle)?self::_otherSide($triangle):$triangle,'touches' => array($p2),'direction' => 'out',
					             'hold' => 1);
				$before = (self::_onWhichSide($getT0(),$t1,$p1) !=
					$triangle || self::_onWhichSide($t1,$t2,$p1) !=
					$triangle)?self::_otherSide($triangle):$triangle;
				if (is_null(self::_onWhichSide($getT0(),$t1,$getP3())) &&
					(self::_distance($getT0(),$t1) > self::_distance($getT0(),$getP3()) ||
						self::_distance($getP3(),$getT0()) < self::_distance($getP3(),$t1)))
					throw new WrongDirectionException();
				return array('intersects' => $intersects = (((self::_onWhichSide($getT0(),$t1,$getP3()) !=
					$triangle || self::_onWhichSide($t1,$t2,$getP3()) !=
					$triangle)?self::_otherSide($triangle):$triangle) != $before?true:false),
				             'touches' => array($p2),'side' => $intersects
						?array($before,self::_otherSide($before))
						:$before);*/
			}
			else { //if (in_array($p1,$touches,true) && in_array($t1,$touches,true)) {
				if (is_null($getP0()) || is_null($getT0()))
					return array('intersects' => false,'touches' => array($p1));
				$result = self::_twoTouchesNotStraight($getP0(),$p1,$p2,$getT0(),$t1,$t2);
				if (isset($result['reverse']) && $result['reverse'])
					throw new WrongDirectionException();
				//if (isset($result['direction']) && $result['direction'] == 'in')
					//throw new Exception("Should not happen (".__LINE__.")");
				if (isset($result['reverse'])) unset($result['reverse']);
				$result['touches'] = array($p1);
				return $result;

				/*$triangle = self::_onWhichSide($getT0(),$t1,$t2);
				$before = (self::_onWhichSide($getT0(),$t1,$p2) !=
					$triangle || self::_onWhichSide($t1,$t2,$p2) !=
					$triangle)?self::_otherSide($triangle):$triangle;
				if (is_null(self::_onWhichSide($getT0(),$t1,$getP0())) &&
					(self::_distance($getT0(),$t1) > self::_distance($getT0(),$getP0()) ||
						self::_distance($getP0(),$getT0()) < self::_distance($getP0(),$t1)))
					return array('side' => $before,'touches' => array($p1),'direction' => 'out');
				if (is_null(self::_onWhichSide($t1,$t2,$getP0())) &&
					(self::_distance($t2,$t1) > self::_distance($t2,$getP0()) ||
						self::_distance($getP0(),$t2) < self::_distance($getP0(),$t1)))
					throw new WrongDirectionException();
				return array('intersects' => $intersects = (((self::_onWhichSide($getT0(),$t1,$getP0()) !=
					$triangle || self::_onWhichSide($t1,$t2,$getP0()) !=
					$triangle)?self::_otherSide($triangle):$triangle) != $before?true:false),
				             'touches' => array($p1),'side' => $intersects
						?array($before,self::_otherSide($before))
						:$before);*/
			}
		}
		elseif (count($touches) == 3) {
			if (!in_array($p1,$touches,true)) {
				if (is_null($getP3()) || is_null($getT0()) || is_null($getT3()))
					return array('intersects' => false,
					             'touches' => array($p2->__toString() == $t2->__toString()?$t1:$t2,$p2));
				if ($p2->__toString() != $t2->__toString())
					throw new WrongDirectionException();
				return array('side' => self::_onWhichSide($getT0(),$t1,$p1),'touches' => array($t1,$p2),
				             'direction' => 'in');
			}
			elseif (!in_array($p2,$touches,true)) {
				if (is_null($getP0()) || is_null($getT0()) || is_null($getT3()))
					return array('intersects' => false,
					             'touches' => array($p1,$p1->__toString() == $t2->__toString()?$t1:$t2));
				if ($p1->__toString() != $t1->__toString())
					throw new WrongDirectionException();
				return array('side' => self::_onWhichSide($t2,$getT3(),$p2),'touches' => array($p1,$t2),
				             'direction' => 'out');
			}
			elseif (!in_array($t1,$touches,true)) {
				if (is_null($getP0()) || is_null($getP3()) || is_null($getT3()))
					return array('intersects' => false,'touches' => array($p1,$p2));
				if ($p2->__toString() != $t2->__toString())
					throw new WrongDirectionException();
				return array('side' => self::_onWhichSide($t1,$t2,$getP0()),'touches' => array($p1,$p2),
				             'direction' => 'in');
			}
			else { //if (!in_array($t2,$touches,true)) {
				if (is_null($getP0()) || is_null($getP3()) || is_null($getT0()))
					return array('intersects' => false,'touches' => array($p1,$p2));
				if ($p1->__toString() != $t1->__toString())
					throw new WrongDirectionException();
				return array('side' => self::_onWhichSide($t1,$t2,$getP3()),'touches' => array($p1,$p2),
				             'direction' => 'out');
			}
		}
		elseif (count($touches) == 4) {
			if ($p1->__toString() == $t2->__toString())
				throw new WrongDirectionException();
			return array('touches' => array($p1,$p2));
		}
		else
			return array('touches' => array());
	}

	/**
	 * @param IPointCollection $line1
	 * @param IPointCollection $line2
	 * @param int              $nr1
	 * @param int              $nr2
	 * @throws \DpOpenGis\Exception\WrongDirectionException
	 * @throws \Exception
	 * @return array|null
	 */
	protected static function _getTouchingLine(IPointCollection $line1,IPointCollection $line2,$nr1,$nr2) {
		if (is_null($line1->get($nr1)) || is_null($line1->get($nr1+1)) || is_null($line2->get($nr2)) ||
			is_null($line2->get($nr2+1)))
			throw new Exception("Should not happen (".__LINE__.")");
		$line1Ring = $line1->first()->__toString() == $line1->last()->__toString();
		$line2Ring = $line2->first()->__toString() == $line2->last()->__toString();
		$next1 = function (Point $p) use ($nr1,$line1,$line1Ring) {
			$count = $line1->count();
			if ($line1Ring)
				for ($i = 0;$i < $count;$i++) {
					if ($line1->get(($i+$nr1)%($count-1)) === $p && !is_null($line1->get(($i+1+$nr1)%($count-1))))
						return $line1->get(($i+1+$nr1)%($count-1));
					elseif ($line1->get(($i+$nr1)%($count-1)) === $p)
						return null;
				}
			else
				for ($i = 0;$i < $count;$i++)
					if ($line1->get($i) === $p && $i+1 < $count && !is_null($line1->get($i+1)))
						return $line1->get($i+1);
			return null;
		};
		$next2 = function (Point $p) use ($nr2,$line2,$line2Ring) {
			$count = $line2->count();
			if ($line2Ring)
				for ($i = 0;$i < $count;$i++) {
					if ($line2->get(($i+$nr2)%($count-1)) === $p && !is_null($line2->get(($i+1+$nr2)%($count-1))))
						return $line2->get(($i+1+$nr2)%($count-1));
					elseif ($line2->get(($i+$nr2)%($count-1)) === $p)
						return null;
				}
			else
				for ($i = 0;$i < $count;$i++)
					if ($line2->get($i) === $p && $i+1 < $count && !is_null($line2->get($i+1)))
						return $line2->get($i+1);
			return null;
		};
		$prev1 = function (Point $p) use ($nr1,$line1,$line1Ring) {
			$count = $line1->count();
			if ($line1Ring)
				for ($i = $count-1;$i > 0;$i--) {
					if ($line1->get(($i+$nr1)%($count-1)) === $p && !is_null($line1->get(($i-1+$nr1)%($count-1))))
						return $line1->get(($i-1+$nr1)%($count-1));
					elseif ($line1->get(($i+$nr1)%($count-1)) === $p)
						return null;
				}
			else
				for ($i = 0;$i < $count;$i++)
					if ($line1->get($i) === $p && $i-1 >= 0 && !is_null($line1->get($i-1)))
						return $line1->get($i-1);
			return null;
		};
		$prev2 = function (Point $p) use ($nr2,$line2,$line2Ring) {
			$count = $line2->count();
			if ($line2Ring)
				for ($i = $count-1;$i > 0;$i--) {
					if ($line2->get(($i+$nr2)%($count-1)) === $p && !is_null($line2->get(($i-1+$nr2)%($count-1))))
						return $line2->get(($i-1+$nr2)%($count-1));
					elseif ($line2->get(($i+$nr2)%($count-1)) === $p)
						return null;
				}
			else
				for ($i = 0;$i < $count;$i++)
					if ($line2->get($i) === $p && $i-1 >= 0 && !is_null($line2->get($i-1)))
						return $line2->get($i-1);
			return null;
		};
		$tries = 0;
		$p1 = $line1->get($nr1);
		$p2 = $line2->get($nr2);
		$side = function($side) {return $side;};
		do {
			try {
				$first = self::_getStateOfTouch($p1,$p2,$next1,$prev1,$next2,$prev2);
				if (isset($first['intersects'])) {
					if (isset($first['side'])) $first['side'] = $side($first['side']);
					if (isset($first['touches'])) $first['touches'] = self::_cleanTouchLine($first['touches']);
					return $first;
				}
				$firstP1 = $p1;
				$touchesFirst = array();
				$endNotIntersecting = false;
				while (!isset($first['direction']) && !empty($first['touches'])) {
					// Hold on next-method means not hold on prev-method. We are trying to get the first occurrence of
					// the touch, so we have to prev until we get a 'direction'
					if (isset($first['nonDirectionHold']))
						$first['hold'] = $first['nonDirectionHold'];
					elseif (isset($first['directionHold']))
						$first['hold'] = $first['directionHold'];
					if (!isset($first['hold']) || $first['hold'] == 1)
						$prevP1 = $prev1($p1);
					else
						$prevP1 = $p1;
					if (!isset($first['hold']) || $first['hold'] == 2)
						$prevP2 = $prev2($p2);
					else
						$prevP2 = $p2;
					if (is_null($prevP1) || is_null($prevP2)) {
						$endNotIntersecting = true;
						break;
					}
					$p1 = $prevP1;
					$p2 = $prevP2;
					// Special Case: line1 == line2
					if ($p1->__toString() == $firstP1->__toString() && (!isset($first['hold']) || $first['hold'] == 1))
						return array('intersects' => false,'touches' => self::_cleanTouchLine($touchesFirst));
					$first = self::_getStateOfTouch($p1,$p2,$next1,$prev1,$next2,$prev2);
					$touchesFirst = array_merge($first['touches'],$touchesFirst);
					if (isset($first['intersects']))
						return array('intersects' => $first['intersects'],
						             'touches' => self::_cleanTouchLine($touchesFirst),'side' => $side($first['side']));
				}
				// Not a ring at beginning touching
				if ($endNotIntersecting) {
					$openTouches = array();
					do {
						$test = self::_getStateOfTouch($p1,$p2,$next1,$prev1,$next2,$prev2);
						$openTouches = array_merge($openTouches,$test['touches']);
						if (isset($first['nonDirectionHold']))
							$first['hold'] = $first['nonDirectionHold'];
						elseif (isset($first['directionHold']))
							$first['hold'] = $first['directionHold'];
						if (!isset($first['hold']) || $first['hold'] == 1)
							$nextP1 = $next1($p1);
						else
							$nextP1 = $p1;
						if (!isset($first['hold']) || $first['hold'] == 2)
							$nextP2 = $next2($p2);
						else
							$nextP2 = $p2;
						if (is_null($nextP1) || is_null($nextP2))
							break;
						$p1 = $nextP1;
						$p2 = $nextP2;
					} while (!isset($test['direction']));
					return array('intersects' => false,'touches' => self::_cleanTouchLine($openTouches),
					             'side' => $side($test['side']));
				}
				if (!isset($first['direction']))
					throw new Exception("Should not happen (".__LINE__.")");
				$fromDirection = $first['direction'];
				$fromSide = $first['side'];
				$touches = $first['touches'];
				if ($fromDirection == 'in') {
					$op1 = $next1;
					$op2 = $next2;
					$opHold = function ($hold) {return $hold == 1?1:2;};
				}
				else {
					$op1 = $prev1;
					$op2 = $prev2;
					$opHold = function ($hold) {return $hold == 1?2:1;};
				}
				$middle = $first;
				$redundantDirections = true;
				do {
					if (isset($middle['nonDirectionHold']))
						$middle['hold'] = $middle['nonDirectionHold'];
					elseif (isset($middle['directionHold']))
						$middle['hold'] = $opHold($middle['directionHold']);
					if (!isset($middle['hold']) || $middle['hold'] != 1)
						$opP1 = $op1($p1);
					else
						$opP1 = $p1;
					if (!isset($middle['hold']) || $middle['hold'] != 2)
						$opP2 = $op2($p2);
					else
						$opP2 = $p2;
					if (is_null($opP1) || is_null($opP2) || is_null($next1($opP1)) || is_null($next2($opP2)))
						return array('intersects' => false,'touches' => self::_cleanTouchLine($touches),
						             'side' => $side($fromSide));
					$p1 = $opP1;
					$p2 = $opP2;
					$middle = self::_getStateOfTouch($p1,$p2,$next1,$prev1,$next2,$prev2);
					$touches = array_merge($touches,$middle['touches']);
					if (isset($middle['intersects']))
						return array('intersects' => $middle['intersects'],
						             'touches' => self::_cleanTouchLine($touches),'side' => $side($middle['side']));
					if (!isset($middle['direction']) || $middle['direction'] != $fromDirection)
						$redundantDirections = false;
				} while (($redundantDirections || !isset($middle['direction'])) && !empty($middle['touches']));
				if (!isset($middle['direction']))
					throw new Exception("Should not happen (".__LINE__.")");
				$toDirection = $middle['direction'];
				$toSide = $middle['side'];
				if ($fromDirection == $toDirection)
					throw new Exception("Should not happen (".__LINE__.")");
				if ($toSide != $fromSide)
					return array('intersects' => true,
					             'touches' => self::_cleanTouchLine($touches),
					             'side' => array($side($fromSide),$side($toSide)));
				else
					return array('intersects' => false,
				                 'touches' => self::_cleanTouchLine($touches),
				                 'side' => $side($fromSide));
			} catch (WrongDirectionException $e) {
				$tries++;
				$p1 = $next1($line1->get($nr1));
				$p2 = $line2->get($nr2);
				$tmp1 = $next1;
				$next1 = $prev1;
				$prev1 = $tmp1;
				//$side = function($side) {return self::_otherSide($side);};
			}
		} while ($tries == 1);
		throw new WrongDirectionException("Should not happen (".__LINE__.")");
	}
	/**
	 * @param Point $p
	 * @return bool
	 */
	public function IsOnCorner(Point $p) {
		/** @var IPointCollection $outerPoints  */
		$outerPoints = $this->getPoints();
		foreach ($outerPoints as $outerCurrent)
			/** @var Point $outerCurrent */
			if ($outerCurrent->__toString() == $p->__toString())
				return true;
		return false;
	}

	/**
	 * @param Point $p
	 * @return bool
	 */
	public function IsOnBorder(Point $p) {
		/** @var IPointCollection $outerPoints  */
		$outerPoints = $this->getPoints();
		/** @var Point $outerPrev */
		$outerPrevTmp = $outerPoints->first();
		foreach ($outerPoints as $outerCurrent) {
			/** @var Point $outerCurrent */
			if ($outerPoints->first() === $outerCurrent)
				continue;

			$outerPrev = $outerPrevTmp;
			$outerPrevTmp = $outerCurrent;

			$higher = $outerPrev->getLat() > $outerCurrent->getLat()?$outerPrev:$outerCurrent;
			$lower = ($higher === $outerCurrent)?$outerPrev:$outerCurrent;
			$righter = $outerPrev->getLon() > $outerCurrent->getLon()?$outerPrev:$outerCurrent;
			$lefter = ($righter === $outerCurrent)?$outerPrev:$outerCurrent;

			if (!self::_straightLine($outerPrev,$outerCurrent)) {
				list($a,$b) = self::_getFunctionParams($outerPrev,$outerCurrent);
				if (bccomp($a*$p->getLon()+$b,$p->getLat()) != 0)
					continue;
				if ($p->getLat() <= $higher->getLat() && $p->getLat() >= $lower->getLat() &&
					$p->getLon() <= $righter->getLon() && $p->getLon() >= $lefter->getLon())
					return true;
			}
			else {
				if ($p->getLon() != $outerCurrent->getLon())
					continue;
				if ($p->getLat() <= $higher->getLat() && $p->getLat() >= $lower->getLat())
					return true;
			}
		}
		return false;
	}
	/**
	 * @param LineString $line
	 * @param bool       $fasterOnTrue
	 * @return bool
	 */
	public function Intersects(LineString $line,$fasterOnTrue = false) {
		$containsPossible = true;
		$reverseContainsPossible = true;
		if ($fasterOnTrue) {
			if ($this->IsRing()) {
				foreach ($line->getPoints() as $point)
					if ($this->ContainsPoint($point)) {
						if (!$this->IsOnBorder($point))
							return true;
					}
					else
						$containsPossible = false;
			}
			else
				$containsPossible = false;
			if ($line->IsRing()) {
				foreach ($this->getPoints() as $point)
					if ($line->ContainsPoint($point)) {
						if (!$line->IsOnBorder($point))
							return true;
					}
					else
						$reverseContainsPossible = false;
			}
			else
				$reverseContainsPossible = false;
		}
		if (($containsPossible || $reverseContainsPossible) && $line->IsRing() && $line->Contains($this))
			return true;
		if (($containsPossible || $reverseContainsPossible) && $this->IsRing() && $this->Contains($line))
			return true;

		$touchingPoints = array();

		/** @var IPointCollection $outerPoints  */
		$outerPoints = $line->getPoints();
		for ($i = 1;$i < $outerPoints->count();$i++) {
			/** @var Point $outerPrev */
			$outerPrev = $outerPoints->get($i-1);
			/** @var Point $outerCurrent */
			$outerCurrent = $outerPoints->get($i);

			/** @var IPointCollection $innerPoints  */
			$innerPoints = $this->getPoints();
			for ($j = 1;$j < $innerPoints->count();$j++) {
				/** @var Point $innerPrev */
				$innerPrev = $innerPoints->get($j-1);
				/** @var Point $innerCurrent */
				$innerCurrent = $innerPoints->get($j);

				if (!self::_couldIntersect($outerPrev,$outerCurrent,$innerPrev,$innerCurrent))
					continue;
				if (self::_isSameLine($outerPrev,$outerCurrent,$innerPrev,$innerCurrent))
					continue;
				/** @var Point[] $touches */
				$touches = self::_whoTouches($outerPrev,$outerCurrent,$innerPrev,$innerCurrent);
				if (empty($touches))
					return true;
				foreach ($touches as $touch) $touch->__toString();
				//$touches = array_diff($touches,$touchingPoints);
				//if (empty($touches))
				//	continue;
				$result = self::_getTouchingLine($innerPoints,$outerPoints,$j-1,$i-1);
				if ($result['intersects'])
					return true;
				else {
					/** @var Point $touch */
					foreach ($result['touches'] as $touch) $touch->__toString();
					$touchingPoints = array_merge($touchingPoints,$result['touches']);
				}
			}
		}
		return false;
	}

	/**
	 * @param LineString $line
	 * @return MultiPolygon
	 */
	public function getIntersection(LineString $line) {
		if (!$this->Intersects($line))
			return MultiPolygonFactory::getInstance()->create('MultiPolygon',array('polygons' => array()));

		$touchingPoints = array();

		/** @var IPointCollection $outerPoints  */
		$outerPoints = $line->getPoints();
		/** @var IPointCollection $innerPoints  */
		$innerPoints = $this->getPoints();
		$outerSpin = $line->GetSpinDirection();
		$innerSpin = $this->GetSpinDirection();

		$lineString = array();
		$newLineStrings = array($lineString);

		$onBorder = false;
		$contains = $this->ContainsPoint($outerPoints->first());
		if ($contains) $onBorder = $this->IsOnBorder($outerPoints->first());
		if (!$contains)
			$state = 'out';
		elseif (!$onBorder) {
			$state = 'in';
			$newLineStrings = array_merge($newLineStrings,$outerPoints->first());
		}
		else {
			$intersectionPoints = array();
			for ($i = 1;$i < $innerPoints->count();$i++) {
				if (!self::_isOnLine($outerPoints->get(0),$innerPoints->get($i-1),$innerPoints->get($i)))
					continue;
				$intersectionPoints[] = $this->_getIntersectingPoint($outerPoints->get(0),$outerPoints->get(0+1),
				                                                     $innerPoints->get($i-1),$innerPoints->get($i));
			}
			$result = self::_getTouchingLine($innerPoints,$outerPoints,0,$i-1);
			if ($result['intersects'] || $outerSpin == $result['side']) {
				$newLineStrings = array_merge($newLineStrings,$result['touches']);
				$state = 'in';
			}
			elseif ($result['intersects']) {
				$state = 'out';
			}
		}

		for ($i = 1;$i < $outerPoints->count();$i++) {
			/** @var Point $outerPrev */
			$outerPrev = $outerPoints->get($i-1);
			/** @var Point $outerCurrent */
			$outerCurrent = $outerPoints->get($i);
			if (in_array($outerPrev,$newLineStrings))
			for ($j = 1;$j < $innerPoints->count();$j++) {
				/** @var Point $innerPrev */
				$innerPrev = $innerPoints->get($j-1);
				/** @var Point $innerCurrent */
				$innerCurrent = $innerPoints->get($j);

				if (!self::_couldIntersect($outerPrev,$outerCurrent,$innerPrev,$innerCurrent)) {
					if ($state == 'in')
						$newLineStrings[] = $outerCurrent;
					continue;
				}
				if (self::_isSameLine($outerPrev,$outerCurrent,$innerPrev,$innerCurrent))
					continue;
				/** @var Point[] $touches */
				$touches = self::_whoTouches($outerPrev,$outerCurrent,$innerPrev,$innerCurrent);
				if (empty($touches))
					return true;
				foreach ($touches as $touch) $touch->__toString();
				$result = self::_getTouchingLine($innerPoints,$outerPoints,$j-1,$i-1);
				if ($result['intersects']) {

				}
				else {

				}
			}

		}
	}
	/**
	 * @param LineString $line
	 * @return bool
	 */
	public function Contains(LineString $line) {
		foreach ($line->getPoints() as $point)
			if (!$this->ContainsPoint($point))
				return false;
			elseif (!$this->IsOnBorder($point))
				break;

		$touchingLines = array();
		$spinDirection = $this->GetSpinDirection();
		/** @var IPointCollection $outerPoints  */
		$outerPoints = $line->getPoints();
		for ($i = 1;$i < $outerPoints->count();$i++) {
			/** @var Point $outerPrev */
			$outerPrev = $outerPoints->get($i-1);
			/** @var Point $outerCurrent */
			$outerCurrent = $outerPoints->get($i);

			/** @var IPointCollection $innerPoints  */
			$innerPoints = $this->getPoints();
			for ($j = 1;$j < $innerPoints->count();$j++) {
				/** @var Point $innerPrev */
				$innerPrev = $innerPoints->get($j-1);
				/** @var Point $innerCurrent */
				$innerCurrent = $innerPoints->get($j);

				if (!self::_couldIntersect($outerPrev,$outerCurrent,$innerPrev,$innerCurrent))
					continue;
				if (self::_isSameLine($outerPrev,$outerCurrent,$innerPrev,$innerCurrent))
					continue;
				/** @var Point[] $touches */
				$touches = self::_whoTouches($outerPrev,$outerCurrent,$innerPrev,$innerCurrent);
				if (empty($touches))
					return false;
				$result = self::_getTouchingLine($outerPoints,$innerPoints,$i-1,$j-1);
				if ($result['intersects'])
					return false;
				else {
					/** @var Point $touch */
					foreach ($result['touches'] as $touch) $touch->__toString();
					$touchingLines[] = array('line' => $result['touches'],
					                         'side' => isset($result['side'])?$result['side']:null);
				}
				if (in_array($innerPrev,$touches,true) || in_array($outerPrev,$touches,true)
					|| in_array($outerCurrent,$touches,true)) {
					if (!$this->ContainsPoint($innerCurrent))
						return false;
					/** @var Point $middlePoint  */
					$middlePoint = PointFactory::getInstance()->
						create('Point',
						       array(
						            'lat' => (double) ($innerPrev->getLat()-$innerCurrent->getLat())/2+
							            $innerCurrent->getLat(),
						            'lon' => (double) ($innerPrev->getLon()-$innerCurrent->getLon())/2+
							            $innerCurrent->getLon()
						       ));
					if (!$this->ContainsPoint($middlePoint))
						return false;
				}
			}
		}
		foreach ($touchingLines as $nr => $line) {
			if (isset($line['side']) && $line['side'] == self::_otherSide($spinDirection)) {
				foreach ($touchingLines as $nr2 => $line2) {
					if ($nr == $nr2)
						continue;
					if (isset($line2['side']) && $line2['side'] == self::_otherSide($spinDirection))
						continue;
					if ((count($line['line']) == 1) && (count($line2['line']) > 1)) {
						/** @var Point $point */
						$point = $line['line'][0];
						for ($j = 1;$j < count($line2['line']);$j++) {
							/** @var Point $innerPrev */
							$innerPrev = $line2['line'][$j-1];
							/** @var Point $innerCurrent */
							$innerCurrent = $line2['line'][$j];
							if (!self::_straightLine($innerPrev,$innerCurrent))
								list($a,$b) = self::_getFunctionParams($innerPrev,$innerCurrent);
							else
								list($a,$b) = array(null,null);
							if (
								(isset($a) && isset($b))?($point->getLon()*$a+$b == $point->getLat()):true &&
								(
								($point->getLon() >= $innerPrev->getLon() && $point->getLon() <= $innerCurrent->getLon())
								|| ($point->getLon() <= $innerPrev->getLon() && $point->getLon() >= $innerCurrent->getLon())
								) && (
								($point->getLat() >= $innerPrev->getLat() && $point->getLat() <= $innerCurrent->getLat())
								|| ($point->getLat() <= $innerPrev->getLat() && $point->getLat() >= $innerCurrent->getLat())
								)
							) {
								unset($touchingLines[$nr]);
								continue 3;
							}
						}
					}
					elseif ((count($line['line']) > 1) && (count($line2['line']) == 1)) {
						continue;
					}
					elseif ((count($line['line']) == 1) && (count($line2['line']) == 1)) {
						/** @var Point $point1 */
						$point1 = $line['line'][0];
						/** @var Point $point2 */
						$point2 = $line2['line'][0];
						if ($point1->getLon() == $point2->getLon() &&
							$point1->getLat() == $point2->getLat()) {
							unset($touchingLines[$nr]);
							continue 2;
						}
						continue;
					}
					else {
						$newLines = array($line['line']);
						while (!empty($newLines)) {
							foreach ($newLines as $newLineNr => $newLine) {
								for ($i = 1;$i < count($newLine);$i++) {
									/** @var Point $outerPrev */
									$outerPrev = $newLine[$i-1];
									/** @var Point $outerCurrent */
									$outerCurrent = $newLine[$i];

									for ($j = 1;$j < count($line2['line']);$j++) {
										/** @var Point $innerPrev */
										$innerPrev = $line2['line'][$j-1];
										/** @var Point $innerCurrent */
										$innerCurrent = $line2['line'][$j];

										if (!self::_couldIntersect($outerPrev,$outerCurrent,$innerPrev,$innerCurrent))
											continue;
										if (self::_isSameLine($outerPrev,$outerCurrent,$innerPrev,$innerCurrent))
											continue;
										$pointCollection1 = new PointCollection($newLine);
										$pointCollection2 = new PointCollection($line2['line']);
										$result = self::_getTouchingLine($pointCollection1,$pointCollection2,$i-1,$j-1);
										if (isset($result['touches']) && !empty($result['touches'])) {
											$nextNewLine = array();
											for ($k = 0;$i < count($newLine);$k++) {
												if ($newLine[$k] == $result['touches'][0]) {
													for ($l = 0;$l < count($result['touches']);$l++)
														if ($newLine[$k+$l] != $result['touches'][$l])
															continue 2;
													if (!empty($nextNewLine))
														$newLines[] = $nextNewLine;
													$nextNewLine = array();
													for ($l = $k+count($result['touches'])+1;$l < count($newLine);$l++)
														$nextNewLine[] = $newLine[$l];
													if (!empty($nextNewLine))
														$newLines[] = self::_cleanTouchLine($nextNewLine);
													unset($newLines[$newLineNr]);
													continue 5;
												}
												else
													$nextNewLine[] = self::_cleanTouchLine(array($newLine[$k]));
											}
										}

									}
								}
							}
							break;
						}
						unset($touchingLines[$nr]);
						continue 2;
					}
				}
			}
		}
		foreach ($touchingLines as $line)
			if (isset($line['side']) && $line['side'] == self::_otherSide($spinDirection))
				return false;
		return true;
	}
	public function equals(LineString $otherLineString) {
		if ($this === $otherLineString)
			return true;
		if (!$this->isOptimized() || !$otherLineString->isOptimized())
			throw new NotOptimizedException();
		if ($this->NumPoints() != $otherLineString->NumPoints())
			return false;
		$thisPoints = $this->getPoints();
		$otherPoints = $otherLineString->getPoints();
		$first = $thisPoints->first();
		$thisRing = $this->IsRing();
		$otherRing = $otherLineString->IsRing();
		if ($thisRing != $otherLineString) return false;
		if (!$otherRing) $start = 0;
		for ($i = 0; $i < $otherLineString->NumPoints();$i++)
			if ($first->__toString() == $otherPoints->get($i)->__toString())
				$start = $i;
		if (!isset($start))
			return false;
		$otherNumPoints = $otherLineString->NumPoints();
		$thisSpinDirection = $this->GetSpinDirection();
		$otherSpinDirection = $otherLineString->GetSpinDirection();
		if ($thisSpinDirection == $otherSpinDirection) {
			for ($i = $start+1;$i < $start+$otherNumPoints-1;$i++)
				if ($thisPoints->get(($i-$start) % ($otherNumPoints-1))->__toString()
						!= $otherPoints->get($i % ($otherNumPoints-1))->__toString())
					return false;
		}
		else {
			for ($i = ($start-1 + $otherNumPoints-1),$j = $start+1;$i > $start;$i--,$j++)
				if ($thisPoints->get(($j-$start) % ($otherNumPoints-1))->__toString()
						!= $otherPoints->get($i % ($otherNumPoints-1))->__toString())
					return false;
		}
		return true;
	}

	/**
	 * @return IPointCollection
	 */
	public function getPoints() {
		return $this->_points;
	}
	/**
	 * @return array
	 */
	public function getStateVars() {
		return array('points');
	}
	public function __toString() {
		if (!isset($this->_serialized)) {
			if ($this->getServiceLocator()->has('DpOpenGis\MappingType\LineStringType')) {
				/** @var LineStringType $type */
				$type = $this->getServiceLocator()->get('DpOpenGis\MappingType\LineStringType');
			}
			else {
				if (!Type::hasType('linestring'))
					Type::addType('linestring', 'DpOpenGis\MappingType\LineStringType');
				$type = Type::getType('linestring');
			}
			if ($this->getServiceLocator()->has('doctrine.entitymanager.orm_default')) {
				/** @var EntityManager $entityManager */
				$entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
				$platform = $entityManager->getConnection()->getDatabasePlatform();
			}
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
