<?php
/**
 * User: dpeuscher
 * Date: 02.04.13
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