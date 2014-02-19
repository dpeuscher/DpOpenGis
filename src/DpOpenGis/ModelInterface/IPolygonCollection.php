<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dpeuscher
 * Date: 02.04.13
 * Time: 14:56
 * To change this template use File | Settings | File Templates.
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