<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dpeuscher
 * Date: 02.04.13
 * Time: 14:51
 * To change this template use File | Settings | File Templates.
 */

namespace DpOpenGis\ModelInterface;

use Doctrine\Common\Collections\Collection;
use DpDoctrineExtensions\Collection\IFreezableCollection;

/**
 * Class IPointCollection
 *
 * @package DpOpenGis\ModelInterface
 */
interface IPointCollection extends Collection,IFreezableCollection {

}