<?php
/**
 * User: dpeuscher
 * Date: 02.04.13
 */

namespace DpOpenGis\ModelInterface;

use Doctrine\Common\Collections\Collection;
use DpDoctrineExtensions\Collection\IFreezableCollection;

/**
 * Class IPointCollection
 *
 * @package DpOpenGis\ModelInterface
 */
interface IReversePointCollection extends Collection,IFreezableCollection,IPointCollection {
	public function setOriginalPointCollection(IPointCollection $original);
}