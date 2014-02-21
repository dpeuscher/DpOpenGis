<?php
/**
 * User: dpeuscher
 * Date: 02.04.13
 */

namespace DpOpenGis\ModelInterface;

use Doctrine\Common\Collections\Collection;
use DpDoctrineExtensions\Collection\IFreezableCollection;

/**
 * Class IPolygonCollection
 *
 * @package DpOpenGis\ModelInterface
 */
interface IPolygonCollection extends Collection,IFreezableCollection {

}