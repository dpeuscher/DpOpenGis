<?php
/**
 * User: dpeuscher
 * Date: 02.04.13
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