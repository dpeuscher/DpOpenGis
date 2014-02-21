<?php
/**
 * User: dpeuscher
 * Date: 02.04.13
 */

namespace DpOpenGis\Factory;


use DpZFExtensions\ServiceManager\AbstractModelFactory;

/**
 * Class PolygonFactory
 *
 * @package AibLocation\OsmParser\Factory
 */
class PolygonFactory extends AbstractModelFactory {
	/**
	 * @var AbstractModelFactory
	 */
	protected static $_instance;
	/**
	 * @var array
	 */
	protected $_buildInModels = array(
		'Polygon' => 'DpOpenGis\Model\Polygon',
		'DpOpenGis\Model\Polygon' => 'DpOpenGis\Model\Polygon',
	);
	/**
	 * @var string
	 */
	protected $_modelInterface = 'DpOpenGis\Model\Polygon';

}