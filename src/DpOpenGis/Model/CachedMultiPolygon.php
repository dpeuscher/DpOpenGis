<?php
/**
 * User: Dominik
 * Date: 24.06.13
 */

namespace DpOpenGis\Model;

use DpZFExtensions\Cache\ICacheAware;
use DpZFExtensions\Cache\TCacheAware;

class CachedMultiPolygon extends MultiPolygon implements ICacheAware{
	use TCacheAware;
	public function Contains(MultiPolygon $innerMultiPolygon) {
		$hash = $this.'->Contains('.$innerMultiPolygon.')';
		if ($this->getLongTermCache()->hasItem($hash))
			return $this->getLongTermCache()->getItem($hash);
		$result = parent::Contains($innerMultiPolygon);
		$this->getLongTermCache()->setItem($hash,$result);
		return $result;
	}
}