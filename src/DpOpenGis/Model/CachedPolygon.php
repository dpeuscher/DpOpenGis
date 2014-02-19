<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Dominik
 * Date: 24.06.13
 * Time: 17:43
 * To change this template use File | Settings | File Templates.
 */

namespace DpOpenGis\Model;

use DpZFExtensions\Cache\ICacheAware;
use DpZFExtensions\Cache\TCacheAware;

class CachedPolygon extends Polygon implements ICacheAware{
	use TCacheAware;
	public function Contains(Polygon $innerPolygon) {
		$hash = $this.'->Contains('.$innerPolygon.')';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);
		$result = parent::Contains($innerPolygon);
		$this->getLongTermCache()->setItem($hash,$result);
		return $result;
	}
	public function ContainsLineString(LineString $lineString) {
		$hash = $this.'->ContainsLineString('.$lineString.')';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);
		$result = parent::ContainsLineString($lineString);
		$this->getLongTermCache()->setItem($hash,$result);
		return $result;
	}
	public function IntersectsLineString(LineString $lineString,$fasterOnTrue = false) {
		$hash = $this.'->IntersectsLineString('.$lineString.')';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);
		$result = parent::IntersectsLineString($lineString,$fasterOnTrue);
		$this->getLongTermCache()->setItem($hash,$result);
		return $result;
	}
}
