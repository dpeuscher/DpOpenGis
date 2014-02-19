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