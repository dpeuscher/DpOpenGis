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
 * Class MultiPolygonFactory
 *
 * @package AibLocation\OsmParser\Factory
 */
class MultiPolygonFactory extends AbstractModelFactory {
	/**
	 * @var AbstractModelFactory
	 */
	protected static $_instance;
	/**
	 * @var array
	 */
	protected $_buildInModels = array(
		'MultiPolygon' => 'DpOpenGis\Model\MultiPolygon',
		'DpOpenGis\Model\MultiPolygon' => 'DpOpenGis\Model\MultiPolygon',
	);
	/**
	 * @var string
	 */
	protected $_modelInterface = 'DpOpenGis\Model\MultiPolygon';

}