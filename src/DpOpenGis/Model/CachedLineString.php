<?php
/**
 * User: Dominik
 * Date: 24.06.13
 */

namespace DpOpenGis\Model;


use DpZFExtensions\Cache\ICacheAware;
use DpZFExtensions\Cache\TCacheAware;

class CachedLineString extends LineString implements ICacheAware{
	use TCacheAware;
	public function IsRing() {
		$hash = $this.'->IsRing()';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);
		$result = parent::IsRing();
		$this->getLongTermCache()->setItem($hash,$result);
		return $result;
	}
	protected static function _getFunctionParams(Point $start,Point $end) {
		$hash = get_called_class().'::_getFunctionParams('.$start.','.$end.')';
		if (self::getStaticShortTermCache()->hasItem($hash))
			return self::getStaticShortTermCache()->getItem($hash);
		$result = parent::_getFunctionParams($start,$end);
		self::getStaticShortTermCache()->setItem($hash,$result);
		return $result;
	}
	public function ContainsPoint(Point $p) {
		$hash = $this.'->ContainsPoint('.$p.')';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);
		$result = parent::ContainsPoint($p);
		$this->getLongTermCache()->setItem($hash,$result);
		return $result;
	}
	protected static function _isParallel(Point $p1start, Point $p1end,Point $p2start,Point $p2end) {
		$hash = get_called_class().'::_isParallel('.$p1start.','.$p1end.','.$p2start.','.$p2end.')';
		if (self::getStaticShortTermCache()->hasItem($hash))
			return self::getStaticShortTermCache()->getItem($hash);
		$result = parent::_isParallel($p1start,$p1end,$p2start,$p2end);
		self::getStaticShortTermCache()->setItem($hash,$result);
		return $result;
	}
	protected static function _isSameLine(Point $p1start, Point $p1end,Point $p2start,Point $p2end) {
		$hash = get_called_class().'::_isSameLine('.$p1start.','.$p1end.','.$p2start.','.$p2end.')';
		if (self::getStaticShortTermCache()->hasItem($hash))
			return self::getStaticShortTermCache()->getItem($hash);
		$result = parent::_isSameLine($p1start,$p1end,$p2start,$p2end);
		self::getStaticShortTermCache()->setItem($hash,$result);
		return $result;
	}
	protected static function _outOfRange(Point $p1start,Point $p1end,Point $p2start,Point $p2end) {
		$hash = get_called_class().'::_outOfRange('.$p1start.','.$p1end.','.$p2start.','.$p2end.')';
		if (self::getStaticShortTermCache()->hasItem($hash))
			return self::getStaticShortTermCache()->getItem($hash);
		$result = parent::_outOfRange($p1start,$p1end,$p2start,$p2end);
		self::getStaticShortTermCache()->setItem($hash,$result);
		return $result;
	}
	protected static function _equalPoints(Point $p1,Point $p2,Point $p3,Point $p4) {
		$hash = get_called_class().'::_equalPoints('.$p1.','.$p2.','.$p3.','.$p4.')';
		if (self::getStaticShortTermCache()->hasItem($hash))
			return self::getStaticShortTermCache()->getItem($hash);
		$result = parent::_equalPoints($p1,$p2,$p3,$p4);
		self::getStaticShortTermCache()->setItem($hash,$result);
		return $result;
	}
	protected static function _couldIntersect(Point $p1start, Point $p1end,Point $p2start,Point $p2end) {
		$hash = get_called_class().'::_intersects('.$p1start.','.$p1end.','.$p2start.','.$p2end.')';
		if (self::getStaticShortTermCache()->hasItem($hash))
			return self::getStaticShortTermCache()->getItem($hash);
		$result = parent::_couldIntersect($p1start,$p1end,$p2start,$p2end);
		self::getStaticShortTermCache()->setItem($hash,$result);
		return $result;
	}
	protected static function _whoTouches(Point $p1start, Point $p1end,Point $p2start,Point $p2end) {
		$hash = get_called_class().'::_whoTouches('.$p1start.','.$p1end.','.$p2start.','.$p2end.')';
		if (self::getStaticShortTermCache()->hasItem($hash))
			return self::getStaticShortTermCache()->getItem($hash);
		$result = parent::_whoTouches($p1start,$p1end,$p2start,$p2end);
		self::getStaticShortTermCache()->setItem($hash,$result);
		return $result;

	}
	public function IsOnBorder(Point $p) {
		$hash = $this.'->IsOnBorder('.$p.')';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);
		$result = parent::IsOnBorder($p);
		$this->getLongTermCache()->setItem($hash,$result);
		return $result;
	}
	public function Intersects(LineString $line,$fasterOnTrue = false) {
		$hash = $this.'->Intersects('.$line.')';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);
		$result = parent::Intersects($line,$fasterOnTrue);
		$this->getLongTermCache()->setItem($hash,$result);
		return $result;
	}
	public function Contains(LineString $line) {
		$hash = $this.'->Contains('.$line.')';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);
		$result = parent::Contains($line);
		$this->getLongTermCache()->setItem($hash,$result);
		return $result;
	}
}
