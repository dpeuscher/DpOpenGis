<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dpeuscher
 * Date: 02.04.13
 * Time: 15:20
 * To change this template use File | Settings | File Templates.
 */

namespace DpOpenGis\Factory;


use DpZFExtensions\ServiceManager\AbstractModelFactory;

/**
 * Class PointFactory
 *
 * @package AibLocation\OsmParser\Factory
 */
class PointFactory extends AbstractModelFactory {
	/**
	 * @var AbstractModelFactory
	 */
	protected static $_instance;
	/**
	 * @var array
	 */
	protected $_buildInModels = array(
		'Point' => 'DpOpenGis\Model\Point',
		'DpOpenGis\Model\Point' => 'DpOpenGis\Model\Point',
	);
	/**
	 * @var string
	 */
	protected $_modelInterface = 'DpOpenGis\Model\Point';

}