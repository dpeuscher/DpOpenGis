<?php
namespace DpOpenGisTest\Model;

use DpOpenGis\Collection\PolygonCollection;
use DpOpenGis\Factory\LineStringFactory;
use DpOpenGis\Factory\MultiPolygonFactory;
use DpOpenGis\Factory\PointFactory;
use DpOpenGis\Factory\PolygonFactory;
use DpOpenGis\Model\MultiPolygon;
use DpOpenGis\Model\Polygon;
use DpOpenGis\Collection\PointCollection as Points;
use DpOpenGis\Collection\LineStringCollection as LineStrings;
use DpOpenGis\Collection\PolygonCollection as Polygons;
use DpOpenGis\Model\LineString;
use DpPHPUnitExtensions\PHPUnit\TestCase;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

/**
 * Class MultiPolygonTest
 *
 * @package DpOpenGisTest\Model
 */
class MultiPolygonTest extends TestCase
{
	const SUT = 'DpOpenGis\Model\MultiPolygon';
	/**
	 * @var \DpOpenGis\Model\MultiPolygon
	 */
	protected $_multiPolygon;
	/**
	 * @var array
	 */
	protected $_emptyState;
	/**
	 * @var PointFactory
	 */
	protected $_pointFactory;
	/**
	 * @var LineStringFactory
	 */
	protected $_lineStringFactory;
	/**
	 * @var PolygonFactory
	 */
	protected $_polygonFactory;
	/**
	 * @var PolygonFactory
	 */
	protected $_multiPolygonFactory;

	public function setUp() {
		parent::setUp();
		$this->_emptyState = array('polygons' => null);
		$this->_pointFactory = PointFactory::getInstance();
		$this->_pointFactory->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
			'DpOpenGis\Model\Point' => 'DpOpenGis\Model\Point',
			'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point',
		)))));
		$this->_lineStringFactory = LineStringFactory::getInstance();
		$this->_lineStringFactory->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
			'DpOpenGis\Model\LineString'                => 'DpOpenGis\Model\LineString',
			'DpOpenGis\ModelInterface\IPointCollection' => 'DpOpenGis\Collection\PointCollection',
			'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point',
			'DpOpenGis\Validator\LineString' => 'DpOpenGis\Validator\LineString',
		)))));
		$this->_polygonFactory = PolygonFactory::getInstance();
		$this->_polygonFactory->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
			'DpOpenGis\Model\Polygon'                        => 'DpOpenGis\Model\Polygon',
			'DpOpenGis\Model\LineString'                     => 'DpOpenGis\Model\LineString',
			'DpOpenGis\ModelInterface\IPointCollection'      => 'DpOpenGis\Collection\PointCollection',
			'DpOpenGis\ModelInterface\ILineStringCollection' => 'DpOpenGis\Collection\LineStringCollection',
			'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point',
			'DpOpenGis\Validator\LineString' => 'DpOpenGis\Validator\LineString',
			'DpOpenGis\Validator\Polygon' => 'DpOpenGis\Validator\Polygon',
		)))));
		$this->_multiPolygonFactory = MultiPolygonFactory::getInstance();
		$this->_multiPolygonFactory->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
			'DpOpenGis\Model\MultiPolygon'                   => 'DpOpenGis\Model\MultiPolygon',
			'DpOpenGis\Model\Polygon'                        => 'DpOpenGis\Model\Polygon',
			'DpOpenGis\Model\LineString'                     => 'DpOpenGis\Model\LineString',
			'DpOpenGis\ModelInterface\IPointCollection'      => 'DpOpenGis\Collection\PointCollection',
			'DpOpenGis\ModelInterface\ILineStringCollection' => 'DpOpenGis\Collection\LineStringCollection',
			'DpOpenGis\ModelInterface\IPolygonCollection'    => 'DpOpenGis\Collection\PolygonCollection',
			'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point',
			'DpOpenGis\Validator\LineString' => 'DpOpenGis\Validator\LineString',
			'DpOpenGis\Validator\Polygon' => 'DpOpenGis\Validator\Polygon',
			'DpOpenGis\Validator\MultiPolygon' => 'DpOpenGis\Validator\MultiPolygon',
		)))));
	}

	public function testInitViaService() {
		$collection = new PolygonCollection();
		$multiPolygon = MultiPolygonFactory::getInstance()->create('MultiPolygon',array('polygons' => $collection));
		$this->assertEquals($collection, $multiPolygon->getPolygons());
		$this->assertInstanceOf('DpOpenGis\ModelInterface\IPolygonCollection', $multiPolygon->getPolygons());
	}

	public function testGetStateVars() {
		$collection = new PolygonCollection();
		$multiPolygon = MultiPolygonFactory::getInstance()->create('MultiPolygon',array('polygons' => $collection));
		$this->assertEquals(array('polygons'), $multiPolygon->getStateVars());
	}

	/**
	 * @param array $points
	 * @return LineString
	 */
	protected function getLine(array $points) {
		$collection = new Points();
		for ($i = 0; $i < count($points); $i++)
			$collection->set($i, $this->_pointFactory->create('Point',
			                                                  array('lon' => (float) $points[$i][0],
			                                                        'lat' => (float) $points[$i][1])));
		$lineString = LineStringFactory::getInstance()->create('LineString',array('points' => $collection));
		return $lineString;
	}

	/**
	 * @param array $lines
	 * @return Polygon
	 */
	protected function getPoly(array $lines) {
		$collection = new LineStrings();
		for ($i = 1; $i < count($lines); $i++)
			$collection->set($i - 1, $this->getLine($lines[$i]));
		$polygon = PolygonFactory::getInstance()->create('Polygon',array('outer' => $this->getLine($lines[0]),
		                                                      'inners' => $collection));
		return $polygon;
	}

	/**
	 * @param array $lines
	 * @return MultiPolygon
	 */
	protected function getMultiPoly(array $lines) {
		$collection = new Polygons();
		for ($i = 0; $i < count($lines); $i++)
			$collection->set($i, $this->getPoly($lines[$i]));
		$multiPolygon = MultiPolygonFactory::getInstance()->create('MultiPolygon',
		                                                        array('polygons' => $collection) + $this->_emptyState);
		return $multiPolygon;
	}

	public function testContains() {
		$multiPolygon = $this->getMultiPoly(array(
		                               array(
			                               array(
				                               array(0, 1),
				                               array(1, 1),
				                               array(1, 0),
				                               array(0, 0),
				                               array(0, 1)
			                               ),
			                               array(
				                               array(0, 0.5),
				                               array(0.5, 0),
				                               array(0, 0),
				                               array(0, 0.5)
			                               ),
		                               ),
		                               array(
			                               array(
				                               array(1, 2),
				                               array(2, 2),
				                               array(2, 1),
				                               array(1, 1),
				                               array(1, 2)
			                               ),
			                               array(
				                               array(1, 1.6),
				                               array(1.6, 1),
				                               array(1, 1),
				                               array(1, 1.6)
			                               ),
		                               ))
		);
		$innerMultiPolygon1 = $this->getMultiPoly(array(array(
			                                                array(
				                                                array(0, 1),
				                                                array(1, 1),
				                                                array(1, 0),
				                                                array(0, 0),
				                                                array(0, 1)
			                                                ),
			                                                array(
				                                                array(0.1, 0.3),
				                                                array(0.3, 0.1),
				                                                array(0.1, 0.1),
				                                                array(0.1, 0.3)
			                                                ),
		                                                )));
		$this->assertFalse($multiPolygon->Contains($innerMultiPolygon1));
		$innerMultiPolygon2 = $this->getMultiPoly(array(array(
			                                                array(
				                                                array(0, 1),
				                                                array(1, 1),
				                                                array(1, 0),
				                                                array(0, 0),
				                                                array(0, 1)
			                                                ),
			                                                array(
				                                                array(0, 0.6),
				                                                array(0.6, 0),
				                                                array(0, 0),
				                                                array(0, 0.6)
			                                                ),
		                                                )));
		$this->assertTrue($multiPolygon->Contains($innerMultiPolygon2));
		$innerMultiPolygon3 = $this->getMultiPoly(array(array(
			                                                array(
				                                                array(0, 1),
				                                                array(1, 1),
				                                                array(1, 0),
				                                                array(0, 0),
				                                                array(0, 1)
			                                                ),
			                                                array(
				                                                array(0, 0.6),
				                                                array(0.6, 0),
				                                                array(0, 0),
				                                                array(0, 0.6)
			                                                ),
		                                                ),
		                                                array(
			                                                array(
				                                                array(1, 2),
				                                                array(2, 2),
				                                                array(2, 1),
				                                                array(1, 1),
				                                                array(1, 2)
			                                                ),
			                                                array(
				                                                array(1.1, 1.3),
				                                                array(1.3, 1.1),
				                                                array(1.1, 1.1),
				                                                array(1.1, 1.3)
			                                                ),
		                                                )
		                                          ));
		$this->assertFalse($multiPolygon->Contains($innerMultiPolygon3));
		$innerMultiPolygon4 = $this->getMultiPoly(array(array(
			                                                array(
				                                                array(0, 1),
				                                                array(1, 1),
				                                                array(1, 0),
				                                                array(0, 0),
				                                                array(0, 1)
			                                                ),
			                                                array(
				                                                array(0, 0.6),
				                                                array(0.6, 0),
				                                                array(0, 0),
				                                                array(0, 0.6)
			                                                ),
		                                                ),
		                                                array(
			                                                array(
				                                                array(1, 2),
				                                                array(2, 2),
				                                                array(2, 1),
				                                                array(1, 1),
				                                                array(1, 2)
			                                                ),
			                                                array(
				                                                array(1, 1.6),
				                                                array(1.6, 1),
				                                                array(1, 1),
				                                                array(1, 1.6)
			                                                ),
		                                                )
		                                          ));
		$this->assertTrue($multiPolygon->Contains($innerMultiPolygon4));
	}
}
