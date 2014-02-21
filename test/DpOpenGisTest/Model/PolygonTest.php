<?php
namespace DpOpenGisTest\Model;

use DpOpenGis\Collection\LineStringCollection;
use DpOpenGis\Collection\PointCollection;
use DpOpenGis\Factory\LineStringFactory;
use DpOpenGis\Factory\PointFactory;
use DpOpenGis\Factory\PolygonFactory;
use DpOpenGis\Model\Polygon;
use DpOpenGis\Collection\PointCollection as Points;
use DpOpenGis\Collection\LineStringCollection as LineStrings;
use DpOpenGis\Model\LineString;
use DpPHPUnitExtensions\PHPUnit\TestCase;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

/**
 * Class PolygonTest
 *
 * @package DpOpenGisTest\Model
 */
class PolygonTest extends TestCase {
	const SUT = 'DpOpenGis\Model\Polygon';
	/**
	 * @var \DpOpenGis\Model\Polygon
	 */
	protected $_polygon;
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

	public function setUp() {
		parent::setUp();
		$this->_polygon = new Polygon();
		$this->_emptyState = array('outer' => null,'inners' => null);
		$this->_pointFactory = PointFactory::getInstance();
		$this->_pointFactory->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
			'DpOpenGis\Model\Point' => 'DpOpenGis\Model\Point',
			'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point',
		)))));
		$this->_lineStringFactory = LineStringFactory::getInstance();
		$this->_lineStringFactory->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
			'DpOpenGis\Model\LineString' => 'DpOpenGis\Model\LineString',
			'DpOpenGis\ModelInterface\IPointCollection' => 'DpOpenGis\Collection\PointCollection',
			'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point',
			'DpOpenGis\Validator\LineString' => 'DpOpenGis\Validator\LineString',
		)))));
		$this->_polygonFactory = PolygonFactory::getInstance();
		$this->_polygonFactory->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
			'DpOpenGis\Model\Polygon' => 'DpOpenGis\Model\Polygon',
			'DpOpenGis\Model\LineString' => 'DpOpenGis\Model\LineString',
			'DpOpenGis\ModelInterface\IPointCollection' => 'DpOpenGis\Collection\PointCollection',
			'DpOpenGis\ModelInterface\ILineStringCollection' => 'DpOpenGis\Collection\LineStringCollection',
			'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point',
			'DpOpenGis\Validator\LineString' => 'DpOpenGis\Validator\LineString',
			'DpOpenGis\Validator\Polygon' => 'DpOpenGis\Validator\Polygon',
		)))));
	}
	public function testInitViaService()
	{
		$lines = new LineStringCollection();
		$points = new PointCollection();
		$points->add(PointFactory::getInstance()->create('Point',array('lon' => 0.0,'lat' => 0.0)));
		$points->add(PointFactory::getInstance()->create('Point',array('lon' => 0.0,'lat' => 1.0)));
		$points->add(PointFactory::getInstance()->create('Point',array('lon' => 1.0,'lat' => 0.0)));
		$points->add(PointFactory::getInstance()->create('Point',array('lon' => 0.0,'lat' => 0.0)));
		$outer = LineStringFactory::getInstance()->create('LineString',array('points' => $points));
		$polygon = PolygonFactory::getInstance()->create('Polygon',array('outer' => $outer,'inners' => $lines));
		$this->assertEquals($outer,$polygon->getOuter());
		$this->assertEquals($lines,$polygon->getInners());
		$this->assertEquals(array('outer','inners'),$polygon->getStateVars());
	}
	/**
	 * @param array $points
	 * @return LineString
	 */
	protected function getLine(array $points) {
		$collection = new Points();
		for ($i = 0;$i < count($points);$i++)
			$collection->set($i,$this->_pointFactory->create('Point',
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
		for ($i = 1;$i < count($lines);$i++)
			$collection->set($i-1,$this->getLine($lines[$i]));
		$polygon = PolygonFactory::getInstance()->create('Polygon',array('outer' => $this->getLine($lines[0]),
		                                                                 'inners' => $collection) + $this->_emptyState);
		return $polygon;
	}
	public function testContains() {
		$polygon = $this->getPoly(array(
		                                  array(
			                                  array(0,1),
			                                  array(1,1),
			                                  array(1,0),
			                                  array(0,0),
			                                  array(0,1)
			                              ),
		                                  array(
			                                  array(0,0.5),
			                                  array(0.5,0),
			                                  array(0,0),
			                                  array(0,0.5)
		                                  ),
		                             ));

		$innerPolygon1 = $this->getPoly(array(
		                                    array(
			                                    array(0,1),
			                                    array(1,1),
			                                    array(1,0),
			                                    array(0,0),
			                                    array(0,1)
		                                    ),
		                                    array(
			                                    array(0,0.6),
			                                    array(0.6,0),
			                                    array(0,0),
			                                    array(0,0.6)
		                                    ),
		                               ));
		$this->assertTrue($polygon->Contains($innerPolygon1));
		$innerPolygon2 = $this->getPoly(array(
		                                     array(
			                                     array(0,1),
			                                     array(1,1),
			                                     array(1,0),
			                                     array(0,0),
			                                     array(0,1)
		                                     ),
		                                     array(
			                                     array(0.1,0.3),
			                                     array(0.3,0.1),
			                                     array(0.1,0.1),
			                                     array(0.1,0.3)
		                                     ),
		                                ));
		$this->assertFalse($polygon->Contains($innerPolygon2));
		$innerPolygon3 = $this->getPoly(array(
		                                     array(
			                                     array(0,1),
			                                     array(1,1),
			                                     array(1,0),
			                                     array(0,0),
			                                     array(0,1)
		                                     ),
		                                     array(
			                                     array(0,0.6),
			                                     array(0.1,0),
			                                     array(0,0),
			                                     array(0,0.6)
		                                     ),
		                                ));
		$this->assertFalse($polygon->Contains($innerPolygon3));
		$innerPolygon4 = $this->getPoly(array(
		                                     array(
			                                     array(0,1),
			                                     array(1,1),
			                                     array(1,0),
			                                     array(0,0),
			                                     array(0,1)
		                                     ),
		                                     array(
			                                     array(0,0.5),
			                                     array(1,0.5),
			                                     array(0.5,1),
			                                     array(0,0.5)
		                                     ),
		                                ));
		$this->assertFalse($polygon->Contains($innerPolygon4));
		$innerPolygon5 = $this->getPoly(array(
		                                     array(
			                                     array(0,1),
			                                     array(1,1),
			                                     array(1,0),
			                                     array(0,0),
			                                     array(0,1)
		                                     ),
		                                     array(
			                                     array(1,0.5),
			                                     array(1,1),
			                                     array(0.5,1),
			                                     array(1,0.5)
		                                     ),
		                                ));
		$this->assertFalse($polygon->Contains($innerPolygon5));
	}
}
