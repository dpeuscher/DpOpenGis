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
 * Class LineStringFactory
 *
 * @package AibLocation\OsmParser\Factory
 */
class LineStringFactory extends AbstractModelFactory {
	/**
	 * @var AbstractModelFactory
	 */
	protected static $_instance;
	/**
	 * @var array
	 */
	protected $_buildInModels = array(
		'LineString' => 'DpOpenGis\Model\LineString',
		'DpOpenGis\Model\LineString' => 'DpOpenGis\Model\LineString',
	);
	/**
	 * @var string
	 */
	protected $_modelInterface = 'DpOpenGis\Model\LineString';

}