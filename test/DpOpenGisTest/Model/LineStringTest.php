<?php
namespace DpOpenGisTest\Model;

use DpOpenGis\Exception\NotOptimizedException;
use DpOpenGis\Factory\LineStringFactory;
use DpOpenGis\Factory\PointFactory;
use DpOpenGis\Collection\PointCollection as Points;
use DpOpenGis\Model\LineString;
use DpOpenGis\Model\MultiPolygon;
use DpOpenGis\Model\Point;
use DpOpenGis\Model\Polygon;
use DpOpenGis\ModelInterface\IPointCollection;
use DpPHPUnitExtensions\PHPUnit\TestCase;
use Zend\Cache\Storage\Adapter\Memory;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

/**
 * Class PointTest
 *
 * @package DpOpenGisTest\Model
 */
class LineStringTest extends TestCase {
	const SUT = 'DpOpenGis\Model\LineString';
	/**
	 * @var \DpOpenGis\Model\LineString
	 */
	protected $_lineString;
	/**
	 * @var array
	 */
	protected $_emptyState;
	/**
	 * @var PointFactory
	 */
	protected $_pointFactory;

	public function setUp() {
		parent::setUp();
		bcscale(7);
		$this->_emptyState = array('points' => null);
		$this->_pointFactory = PointFactory::getInstance();
		$this->_pointFactory->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
			'DpOpenGis\Model\Point' => 'DpOpenGis\Model\Point',
			'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point'
		)))));
		LineStringFactory::getInstance()->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
			'DpOpenGis\Model\LineString' => 'DpOpenGis\Model\LineString',
			'DpOpenGis\ModelInterface\IPointCollection' => 'DpOpenGis\Collection\PointCollection',
			'DpOpenGis\Validator\LineString' => 'DpOpenGis\Validator\LineString',
		),'factories' => array(
			'DpOpenGis\Factory\LineStringFactory' => function () {
				return LineStringFactory::getInstance();
			}
		)))));
	}
	public function testSettersGetters()
	{
		$collection = $this->getMock('DpOpenGis\ModelInterface\IPointCollection');
		$lineString = LineStringFactory::getInstance()->create('LineString',array('points' => $collection));
		$this->assertSame($collection,$lineString->getPoints());
	}
	public function testGetStateVars() {
		$collection = $this->getMock('DpOpenGis\ModelInterface\IPointCollection');
		$lineString = LineStringFactory::getInstance()->create('LineString',array('points' => $collection));
		$this->assertEquals(array('points'),$lineString->getStateVars());
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
	 * @param LineString $oldLineString
	 * @return LineString
	 */
	protected function _reverse(LineString $oldLineString) {
		$points = $oldLineString->getPoints();
		$collection = new Points();
		for ($i = count($points)-1;$i >= 0;$i--)
			$collection->set(count($points)-1-$i,$this->_pointFactory->create('Point',
			                                                 array('lon' => (float) $points->get($i)->getLon(),
			                                                       'lat' => (float) $points->get($i)->getLat())));
		$lineString = LineStringFactory::getInstance()->create('LineString',array('points' => $collection));
		return $lineString;
	}

	public function testGLength() {
		$lineString = $this->getLine(array(
		                                  array(0,0),
		                                  array(1,0),
		                                  array(1,1)
		                             ));
		$this->assertEquals(0,bccomp((float) 2,$lineString->GLength()));
		$lineString = $this->getLine(array(
		                                  array(0,0),
		                                  array(1,0),
		                                  array(1,1),
		                                  array(2,2)
		                             ));
		$this->assertEquals(0,bccomp((float) 2 + sqrt(2),$lineString->GLength()));
	}
	public function testEndPoint() {
		$lineString = $this->getLine(array(
		                                  array(0,0),
		                                  array(1,0),
		                                  array(1,1),
		                                  array(1,2)
		                             ));
		$this->assertSame(array(1.0,2.0),array($lineString->EndPoint()->getLon(),$lineString->EndPoint()->getLat()));
	}
	public function testStartPoint() {
		$lineString = $this->getLine(array(
		                                  array(0,1),
		                                  array(1,0),
		                                  array(1,1),
		                                  array(1,2)
		                             ));
		$this->assertSame(array(0.0,1.0),
		                  array($lineString->StartPoint()->getLon(),$lineString->StartPoint()->getLat()));
	}
	public function testNumPoints() {
		$lineString = $this->getLine(array(
		                                  array(0,1),
		                                  array(1,0),
		                                  array(1,1),
		                                  array(1,2)
		                             ));
		$this->assertSame(4,$lineString->NumPoints());
	}
	public function testPointN() {
		$lineString = $this->getLine(array(
		                                  array(0,1),
		                                  array(1,0),
		                                  array(1,1),
		                                  array(1,2)
		                             ));
		$this->assertSame(array(0.0,1.0),array($lineString->PointN(0)->getLon(),$lineString->PointN(0)->getLat()));
		$this->assertSame(array(1.0,0.0),array($lineString->PointN(1)->getLon(),$lineString->PointN(1)->getLat()));
		$this->assertSame(array(1.0,1.0),array($lineString->PointN(2)->getLon(),$lineString->PointN(2)->getLat()));
		$this->assertSame(array(1.0,2.0),array($lineString->PointN(3)->getLon(),$lineString->PointN(3)->getLat()));
	}
	public function testIsRing() {
		$lineString = $this->getLine(array(
		                                  array(0,1),
		                                  array(1,0),
		                                  array(1,1),
		                                  array(1,2)
		                             ));
		$this->assertFalse($lineString->IsRing());
		$lineString = $this->getLine(array(
		                                  array(0,1),
		                                  array(1,0),
		                                  array(1,1),
		                                  array(0,1)
		                             ));
		$this->assertTrue($lineString->IsRing());
		$lineString = $this->getLine(array(
		                                  array(0,1),
		                                  array(1,0),
		                                  array(1,1),
		                                  array(1,0),
		                                  array(0,1)
		                             ));
		$this->assertFalse($lineString->IsRing());
	}
	public function testContainsPoint() {
		$lineString = $this->getLine(array(
		                                  array(0,1),
		                                  array(1,0),
		                                  array(1,1),
		                                  array(0,1)
		                             ));
		$this->assertTrue($lineString->ContainsPoint(
			                  $this->_pointFactory->create('Point',array('lon' => 0.0,'lat' => 1.0))));
		$this->assertTrue($lineString->ContainsPoint(
			                  $this->_pointFactory->create('Point',array('lon' => 0.5,'lat' => 0.5))));
		$this->assertTrue($lineString->ContainsPoint(
			                  $this->_pointFactory->create('Point',array('lon' => 0.9,'lat' => 0.9))));
		$this->assertTrue($lineString->ContainsPoint(
			                  $this->_pointFactory->create('Point',array('lon' => 1.0,'lat' => 1.0))));
		$this->assertFalse($lineString->ContainsPoint(
			                   $this->_pointFactory->create('Point',array('lon' => 1.0,'lat' => 1.5))));
		$this->assertFalse($lineString->ContainsPoint(
			                   $this->_pointFactory->create('Point',array('lon' => 1.5,'lat' => 1.0))));
		$this->assertFalse($lineString->ContainsPoint(
			                   $this->_pointFactory->create('Point',array('lon' => 0.49,'lat' => 0.49))));
		$this->assertFalse($lineString->ContainsPoint(
			                   $this->_pointFactory->create('Point',array('lon' => 1.5,'lat' => 0.5))));

		$lineString = $this->getLine(array(
		                                  array(0,1),
		                                  array(1,0),
		                                  array(1,1),
		                                  array(1,0),
		                                  array(0,1)
		                             ));
		$this->setExpectedException('Exception');
		$lineString->ContainsPoint($this->_pointFactory->create('Point',array('lon' => 0.0,'lat' => 1.0)));
	}
	public function testContainsPointTriSplit() {
		$lineString = $this->getLine(array(
		                                  array(0,1),
		                                  array(1,1),
		                                  array(1,0),
		                                  array(0,0),
		                                  array(0,1)
		                             ));
		$this->assertTrue($lineString->ContainsPoint(
			                  $this->_pointFactory->create('Point',array('lon' => 0.0,'lat' => 1.0))));
		$this->assertTrue($lineString->ContainsPoint(
			                  $this->_pointFactory->create('Point',array('lon' => 0.5,'lat' => 0.5))));
		$this->assertTrue($lineString->ContainsPoint(
			                  $this->_pointFactory->create('Point',array('lon' => 0.9,'lat' => 0.9))));
		$this->assertTrue($lineString->ContainsPoint(
			                  $this->_pointFactory->create('Point',array('lon' => 1.0,'lat' => 1.0))));
		$this->assertTrue($lineString->ContainsPoint(
			                  $this->_pointFactory->create('Point',array('lon' => 0.49,'lat' => 0.49))));
		$this->assertTrue($lineString->ContainsPoint(
			                  $this->_pointFactory->create('Point',array('lon' => 0.0,'lat' => 0.0))));
		$this->assertFalse($lineString->ContainsPoint(
			                   $this->_pointFactory->create('Point',array('lon' => 1.0,'lat' => 1.5))));
		$this->assertFalse($lineString->ContainsPoint(
			                   $this->_pointFactory->create('Point',array('lon' => 1.5,'lat' => 1.0))));
		$this->assertFalse($lineString->ContainsPoint(
			                   $this->_pointFactory->create('Point',array('lon' => 1.5,'lat' => 0.5))));

		$lineString = $this->getLine(array(
		                                  array(0,1),
		                                  array(1,1),
		                                  array(1,0),
		                                  array(0,0),
		                                  array(1,0),
		                                  array(0,1)
		                             ));
		$this->setExpectedException('Exception');
		$lineString->ContainsPoint($this->_pointFactory->create('Point',array('lon' => 0.0,'lat' => 1.0)));
	}
    public function testContainsSimple() {
        $outerString = $this->getLine(array(
            array(0,1),
            array(1,1),
            array(1,0),
            array(0,0),
            array(0,1)
        ));
        $innerLineString = $this->getLine(array(
            array(0.25,0.75),
            array(0.75,0.75),
            array(0.75,0.25),
            array(0.25,0.25),
            array(0.25,0.75)
        ));
        $this->assertTrue($outerString->Contains($innerLineString));
	    $innerLineString2 = $this->getLine(array(
	                                       array(0,1),
	                                       array(1,1),
	                                       array(1,0),
	                                       array(0,0),
	                                       array(0,1)
	                                  ));
	    $this->assertTrue($outerString->Contains($innerLineString2));
    }
	public function testContainsComplex1() {
		$outerString = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(0,0.5),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$innerLineString1 = $this->getLine(array(
		                                        array(0,1),
		                                        array(1,1),
		                                        array(1,0),
		                                        array(0,0),
		                                        array(0,1)
		                                   ));
		$this->assertFalse($outerString->Contains($innerLineString1));
		$innerLineString2 = $this->getLine(array(
		                                        array(0,1),
		                                        array(1,1),
		                                        array(0,0.5),
		                                        array(1,0),
		                                        array(0,0),
		                                        array(0,1)
		                                   ));
		$this->assertTrue($outerString->Contains($innerLineString2));
		$innerLineString3 = $this->getLine(array(
		                                        array(0.25,0.75),
		                                        array(0.75,0.75),
		                                        array(0.75,0.25),
		                                        array(0.25,0.25),
		                                        array(0.25,0.75)
		                                   ));
		$this->assertFalse($outerString->Contains($innerLineString3));
		$innerLineString4 = $this->getLine(array(
		                                        array(0,1),
		                                        array(1,1),
		                                        array(0,0.75),
		                                        array(0,0.25),
		                                        array(1,0),
		                                        array(0,0),
		                                        array(0,1)
		                                   ));
		$this->assertTrue($outerString->Contains($innerLineString4));
		$outerNoRingString = $this->getLine(array(
		                                         array(0,1),
		                                         array(1,1),
		                                         array(0,0.5),
		                                         array(1,0),
		                                         array(0,0),
		                                    ));
		$this->setExpectedException('Exception');
		$outerNoRingString->Contains($innerLineString4);
	}
	public function testContainsComplex2() {
		$outerString = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(0.5,0.5),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$innerLineString1 = $this->getLine(array(
		                                        array(0,1),
		                                        array(2,1),
		                                        array(0,0),
		                                        array(0,1)
		                                   ));
		$this->assertFalse($outerString->Contains($innerLineString1));
		$outerString = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(0.5,0.5),
		                                   array(1.5,1),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$innerLineString2 = $this->getLine(array(
		                                        array(0,1),
		                                        array(1.5,1),
		                                        array(0,0.5),
		                                        array(0,1)
		                                   ));
		$outerString->optimizeLineString();
		$this->assertFalse($outerString->Contains($innerLineString2));
		$outerString = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$innerLineString3 = $this->getLine(array(
		                                        array(0,1),
		                                        array(0.5,1),
		                                        array(0.75,1),
		                                        array(1,0),
		                                        array(0.5,0),
		                                        array(0,0),
		                                        array(0,1)
		                                   ));
		$innerLineString3->optimizeLineString();
		$this->assertTrue($outerString->Contains($innerLineString3));
	}
	public function testIntersectsSimple() {
		$outerString = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$innerLineString = $this->getLine(array(
		                                       array(0.25,0.75),
		                                       array(0.75,0.75),
		                                       array(0.75,0.25),
		                                       array(0.25,0.25),
		                                       array(0.25,0.75)
		                                  ));
		$this->assertTrue($outerString->Intersects($innerLineString));
		$innerLineString2 = $this->getLine(array(
		                                        array(1,1),
		                                        array(2,1),
		                                        array(2,0),
		                                        array(1,0),
		                                        array(1,1)
		                                   ));
		$this->assertFalse($outerString->Intersects($innerLineString2));
		$innerLineString3 = $this->getLine(array(
		                                        array(2,1),
		                                        array(3,1),
		                                        array(3,0),
		                                        array(2,0),
		                                        array(2,1)
		                                   ));
		$this->assertFalse($outerString->Intersects($innerLineString3));
	}
	public function testIntersectsComplex1() {
		$outerString = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(0,0.5),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$innerLineString1 = $this->getLine(array(
		                                        array(1,1),
		                                        array(1,0),
		                                        array(0,0.5),
		                                        array(1,1)
		                                   ));
		$this->assertFalse($outerString->Intersects($innerLineString1));
		$innerLineString2 = $this->getLine(array(
		                                        array(0,1),
		                                        array(1,1),
		                                        array(0,0.5),
		                                        array(1,0),
		                                        array(0,0),
		                                        array(0,1)
		                                   ));
		$this->assertTrue($outerString->Intersects($innerLineString2));
	}
	public function testSpecialCase() {
		$lineString = $this->getMock('DpOpenGis\Model\LineString');
		$lineString->expects($this->any())->method('getCache')->will($this->returnValue(new Memory()));
		$this->assertFalse($this->_getPrivateMethod($lineString,'_couldIntersect',array(
                                                $this->_pointFactory->create('Point',array('lon' => 0.0,'lat' => 0.0)),
                                                $this->_pointFactory->create('Point',array('lon' => 0.0,'lat' => 0.5)),
                                                $this->_pointFactory->create('Point',array('lon' => 0.0,'lat' => 0.6)),
                                                $this->_pointFactory->create('Point',array('lon' => 0.6,'lat' => 0.0)),
		                                                   )));
	}
	public function testIsOnBorder() {
		$lineString = $this->getLine(array(
		                                  array(0,1),
		                                  array(1,1),
		                                  array(0,0.5),
		                                  array(1,0),
		                                  array(0,0),
		                                  array(0,1)
		                             ));
		/** @var Point $point1 */
		$point1 = $this->_pointFactory->create('Point',array('lon' => 0.0,'lat' => 0.25));
		/** @var Point $point2 */
		$point2 = $this->_pointFactory->create('Point',array('lon' => 1.0,'lat' => 1.0));
		/** @var Point $point3 */
		$point3 = $this->_pointFactory->create('Point',array('lon' => 0.25,'lat' => 0.25));
		/** @var Point $point4 */
		$point4 = $this->_pointFactory->create('Point',array('lon' => 0.0,'lat' => 2.0));
		/** @var Point $point5 */
		$point5 = $this->_pointFactory->create('Point',array('lon' => 2.0,'lat' => 0.0));
		/** @var Point $point6 */
		$point6 = $this->_pointFactory->create('Point',array('lon' => -1.0,'lat' => 1.0));
		$this->assertTrue($lineString->IsOnBorder($point1));
		$this->assertTrue($lineString->IsOnBorder($point2));
		$this->assertFalse($lineString->IsOnBorder($point3));
		$this->assertFalse($lineString->IsOnBorder($point4));
		$this->assertFalse($lineString->IsOnBorder($point5));
		$this->assertFalse($lineString->IsOnBorder($point6));
	}
	public function testIsOnBorder2() {
		$lineString = $this->getLine(array(
		                                  array(13.476242,52.52935),
		                                  array(13.476268,52.529301),
		                                  array(13.47629,52.529232),
		                                  array(13.476298,52.529153),
		                                  array(13.476295,52.529114),
		                                  array(13.47628,52.529077),
		                                  array(13.476249,52.529031),
		                                  array(13.476214,52.528985),
		                                  array(13.476155,52.528938),
		                                  array(13.476081,52.528889),
		                                  array(13.47601,52.528854),
		                                  array(13.475965,52.528836),
		                                  array(13.475939,52.528826),
		                                  array(13.475912,52.528817),
		                                  array(13.47586,52.528802),
		                                  array(13.475802,52.528788),
		                                  array(13.47577,52.528781),
		                                  array(13.475691,52.528767),
		                                  array(13.475539,52.528753),
		                                  array(13.475345,52.528739),
		                                  array(13.47518,52.528685),
		                                  array(13.475062,52.528614),
		                                  array(13.474999,52.528554),
		                                  array(13.474965,52.528496),
		                                  array(13.474937,52.528411),
		                                  array(13.474881,52.528314),
		                                  array(13.474789,52.528221),
		                                  array(13.474693,52.528121),
		                                  array(13.474566,52.528021),
		                                  array(13.474398,52.527926),
		                                  array(13.474308,52.52788),
		                                  array(13.474214,52.527844),
		                                  array(13.474083,52.527809),
		                                  array(13.473895,52.52779),
		                                  array(13.4738,52.527797),
		                                  array(13.473702,52.527824),
		                                  array(13.473591,52.527879),
		                                  array(13.473541,52.527951),
		                                  array(13.473514,52.528078),
		                                  array(13.473502,52.528138),
		                                  array(13.473489,52.52821),
		                                  array(13.473513,52.528307),
		                                  array(13.473561,52.528412),
		                                  array(13.473615,52.52849),
		                                  array(13.473644,52.528551),
		                                  array(13.473671,52.528571),
		                                  array(13.473725,52.52859),
		                                  array(13.474384,52.528733),
		                                  array(13.474618,52.528776),
		                                  array(13.474943,52.528851),
		                                  array(13.47503,52.528889),
		                                  array(13.475139,52.528944),
		                                  array(13.475201,52.528987),
		                                  array(13.475239,52.529034),
		                                  array(13.475254,52.529076),
		                                  array(13.475244,52.529146),
		                                  array(13.475293,52.529234),
		                                  array(13.475346,52.529313),
		                                  array(13.475352,52.529398),
		                                  array(13.475329,52.529499),
		                                  array(13.475313,52.529585),
		                                  array(13.475296,52.529621),
		                                  array(13.475272,52.52966),
		                                  array(13.475216,52.529699),
		                                  array(13.475158,52.529741),
		                                  array(13.475129,52.529787),
		                                  array(13.475081,52.529845),
		                                  array(13.475073,52.529882),
		                                  array(13.475065,52.529921),
		                                  array(13.475088,52.529987),
		                                  array(13.475069,52.530077),
		                                  array(13.47507,52.530132),
		                                  array(13.47507,52.530214),
		                                  array(13.475056,52.530272),
		                                  array(13.475022,52.530324),
		                                  array(13.474942,52.530375),
		                                  array(13.474859,52.530417),
		                                  array(13.474781,52.530425),
		                                  array(13.474673,52.530423),
		                                  array(13.474614,52.530428),
		                                  array(13.474555,52.530439),
		                                  array(13.47451,52.530461),
		                                  array(13.47446,52.53049),
		                                  array(13.474431,52.53051),
		                                  array(13.474421,52.530546),
		                                  array(13.474414,52.530583),
		                                  array(13.474424,52.53062),
		                                  array(13.474442,52.530646),
		                                  array(13.474466,52.530673),
		                                  array(13.474507,52.530702),
		                                  array(13.474562,52.530721),
		                                  array(13.474618,52.53073),
		                                  array(13.474749,52.530749),
		                                  array(13.474798,52.530758),
		                                  array(13.47485,52.53077),
		                                  array(13.474876,52.530782),
		                                  array(13.474911,52.5308),
		                                  array(13.474937,52.530814),
		                                  array(13.474966,52.530833),
		                                  array(13.475003,52.530861),
		                                  array(13.475121,52.530961),
		                                  array(13.475141,52.530972),
		                                  array(13.475165,52.530974),
		                                  array(13.475186,52.530972),
		                                  array(13.475264,52.530941),
		                                  array(13.475344,52.530913),
		                                  array(13.47542,52.530872),
		                                  array(13.475526,52.530803),
		                                  array(13.475576,52.530765),
		                                  array(13.4756,52.530746),
		                                  array(13.475634,52.530649),
		                                  array(13.475634,52.530603),
		                                  array(13.475595,52.530531),
		                                  array(13.475511,52.530447),
		                                  array(13.475371,52.53029),
		                                  array(13.475324,52.530202),
		                                  array(13.475319,52.530137),
		                                  array(13.475327,52.530037),
		                                  array(13.475347,52.529941),
		                                  array(13.475368,52.529896),
		                                  array(13.475388,52.529871),
		                                  array(13.47541,52.52985),
		                                  array(13.475447,52.52981),
		                                  array(13.475481,52.529779),
		                                  array(13.475533,52.52975),
		                                  array(13.475692,52.52969),
		                                  array(13.475872,52.529605),
		                                  array(13.475932,52.529576),
		                                  array(13.476092,52.529474),
		                                  array(13.476182,52.529412),
		                                  array(13.476209,52.529387)
		                             ));
		/** @var Point $point */
		$point = $this->_pointFactory->create('Point',
		                                      array('lon' => 13.475447,'lat' => 52.52981));
		$this->assertTrue($lineString->IsOnBorder($point));
	}
	public function testIntersectsLine() {
		$polygon1 = $this->getLine(
			array(
			     array(0,0.10309),
			     array(0,0.10175),
			     array(0,0.10891),
			     array(0,0.11005),
			     array(0,0.11119),
			     array(0,0.11395),
			     array(0,0.10227),
			     array(0,0.10117),
			     array(0,0.10002),
			     array(0,0.09825),
			     array(0,0.09713),
			     array(0,0.09564),
			     array(0,0.09467),
			     array(0,0.09374),
			     array(0,0.09330),
			     array(0,0.09405),
			     array(0,0.09890),
			     array(0,0.09956),
			     array(0,0.10088),
			     array(0,0.10119),
			     array(0,0.10049),
			     array(0,0.09335),
			     array(0,0.09269),
			     array(0,0.09220),
			     array(0,0.08387),
			     array(0,0.08313),
			     array(0,0.08285),
			     array(0,0.08186),
			     array(0,0.08151),
			     array(0,0.08653),
			     array(0,0.09287),
			     array(0,0.09429),
			     array(0,0.09958),
			     array(0,0.10309),
			));
		$polygon2 = $this->getLine(
			array(
			     array(0,0.24252),
			     array(0,0.22887),
			     array(0,0.18749),
			     array(0,0.20290),
			     array(0,0.24252),
			));
		$this->assertFalse($polygon1->Intersects($polygon2));
	}
	public function testIntersectsLine2() {
		$polygon1 = $this->getLine(
			array(
			     array(0.10178,0.1911),
			     array(0.08727,0.2949),
			     array(0.11405,0.3810),
			     array(0.11635,0.3543),
			     array(0.12397,0.3273),
			     array(0.10178,0.1911)
			));
		$polygon2 = $this->getLine(
			array(
			     array(0.10534,0.3762),
			     array(0.09098,0.4631),
			     array(0.10351,0.5348),
			     array(0.12142,0.4293),
			     array(0.10534,0.3762),
			));
		$this->assertFalse($polygon1->Intersects($polygon2));
	}
	public function testIntersectsLine3() {
		$polygon1 = $this->getLine(
			array(
			     array(7.81203,48.028198),
			     array(7.810234,48.029498),
			     array(7.809573,48.029859),
			     array(7.809533,48.030129),
			     array(7.808935,48.030227),
			     array(7.807555,48.031237),
			     array(7.806265,48.032195),
			     array(7.806267,48.032507),
			     array(7.805297,48.033162),
			     array(7.80485,48.033457),
			     array(7.804697,48.033808),
			     array(7.80415,48.033946),
			     array(7.803732,48.034265),
			     array(7.803441,48.034265),
			     array(7.803402,48.034453),
			     array(7.803101,48.034486),
			     array(7.802224,48.035121),
			     array(7.802207,48.035163),
			     array(7.802166,48.035198),
			     array(7.80208,48.035235),
			     array(7.801959,48.035297),
			     array(7.801898,48.035342),
			     array(7.802278,48.035707),
			     array(7.803193,48.036621),
			     array(7.804112,48.037581),
			     array(7.804998,48.038602),
			     array(7.805813,48.039602),
			     array(7.80676,48.04089),
			     array(7.807735,48.042264),
			     array(7.808516,48.043466),
			     array(7.80875,48.043382),
			     array(7.809213,48.043217),
			     array(7.809325,48.043189),
			     array(7.809724,48.04309),
			     array(7.80988,48.043051),
			     array(7.810632,48.042977),
			     array(7.81161,48.042964),
			     array(7.812241,48.042999),
			     array(7.812345,48.042838),
			     array(7.811971,48.042754),
			     array(7.811648,48.042784),
			     array(7.811066,48.042803),
			     array(7.810071,48.042547),
			     array(7.809151,48.042228),
			     array(7.80633,48.039101),
			     array(7.805944,48.037442),
			     array(7.80659,48.036162),
			     array(7.808222,48.034834),
			     array(7.808254,48.034826),
			     array(7.808329,48.034808),
			     array(7.808857,48.034431),
			     array(7.809193,48.034048),
			     array(7.809376,48.033847),
			     array(7.809898,48.032956),
			     array(7.810379,48.032756),
			     array(7.81084,48.03293),
			     array(7.815254,48.033856),
			     array(7.817529,48.033997),
			     array(7.822678,48.035827),
			     array(7.822134,48.036936),
			     array(7.821325,48.038067),
			     array(7.81984,48.037952),
			     array(7.819557,48.038165),
			     array(7.821256,48.038451),
			     array(7.819512,48.040971),
			     array(7.817393,48.040406),
			     array(7.817035,48.040965),
			     array(7.819117,48.041587),
			     array(7.817906,48.04209),
			     array(7.814657,48.042411),
			     array(7.814131,48.04225),
			     array(7.813904,48.042497),
			     array(7.812948,48.042621),
			     array(7.812837,48.042851),
			     array(7.813137,48.042912),
			     array(7.813141,48.043047),
			     array(7.813961,48.043021),
			     array(7.81404,48.043195),
			     array(7.814716,48.043349),
			     array(7.815031,48.043506),
			     array(7.815169,48.043383),
			     array(7.815172,48.043158),
			     array(7.81576,48.043211),
			     array(7.816102,48.043531),
			     array(7.815992,48.043876),
			     array(7.817178,48.044091),
			     array(7.817497,48.04331),
			     array(7.818247,48.043471),
			     array(7.818385,48.043501),
			     array(7.818483,48.043515),
			     array(7.819047,48.042484),
			     array(7.819374,48.042578),
			     array(7.819971,48.04275),
			     array(7.821899,48.04227),
			     array(7.822555,48.042138),
			     array(7.822891,48.042225),
			     array(7.823599,48.042072),
			     array(7.823906,48.042005),
			     array(7.824802,48.04158),
			     array(7.825145,48.041819),
			     array(7.825121,48.042164),
			     array(7.82499,48.042487),
			     array(7.824684,48.043361),
			     array(7.823882,48.044223),
			     array(7.82327,48.044623),
			     array(7.823398,48.044981),
			     array(7.823327,48.045495),
			     array(7.822379,48.046444),
			     array(7.821513,48.046593),
			     array(7.821129,48.046835),
			     array(7.821105,48.047184),
			     array(7.821224,48.047506),
			     array(7.821089,48.048093),
			     array(7.820403,48.048814),
			     array(7.819083,48.048408),
			     array(7.817973,48.048143),
			     array(7.817619,48.048064),
			     array(7.816701,48.04792),
			     array(7.816997,48.047145),
			     array(7.81682,48.047136),
			     array(7.8146,48.047025),
			     array(7.814439,48.047217),
			     array(7.814215,48.04717),
			     array(7.813915,48.047147),
			     array(7.813508,48.047109),
			     array(7.813246,48.047153),
			     array(7.812931,48.047212),
			     array(7.812881,48.047696),
			     array(7.812934,48.047747),
			     array(7.813202,48.048944),
			     array(7.81317,48.048984),
			     array(7.813186,48.049016),
			     array(7.813192,48.049059),
			     array(7.813111,48.04917),
			     array(7.812961,48.049319),
			     array(7.81304,48.049508),
			     array(7.813177,48.049835),
			     array(7.813244,48.050108),
			     array(7.813338,48.050488),
			     array(7.813333,48.050798),
			     array(7.813299,48.051052),
			     array(7.813299,48.051446),
			     array(7.813376,48.051851),
			     array(7.813703,48.052941),
			     array(7.813821,48.053604),
			     array(7.813966,48.054006),
			     array(7.814171,48.054417),
			     array(7.814354,48.054593),
			     array(7.814987,48.055317),
			     array(7.815363,48.055711),
			     array(7.815749,48.05597),
			     array(7.815831,48.055999),
			     array(7.815915,48.056029),
			     array(7.815591,48.056474),
			     array(7.815435,48.056549),
			     array(7.814511,48.057055),
			     array(7.814344,48.0571),
			     array(7.813965,48.05713),
			     array(7.813826,48.057175),
			     array(7.813642,48.057234),
			     array(7.813195,48.057424),
			     array(7.81333,48.058052),
			     array(7.813358,48.058156),
			     array(7.814259,48.058129),
			     array(7.815077,48.058166),
			     array(7.814934,48.058374),
			     array(7.814852,48.058497),
			     array(7.814783,48.058621),
			     array(7.814561,48.05883),
			     array(7.81459,48.058949),
			     array(7.814485,48.059106),
			     array(7.814325,48.059323),
			     array(7.814191,48.059509),
			     array(7.814135,48.05965),
			     array(7.814052,48.059839),
			     array(7.813851,48.060108),
			     array(7.81369,48.060312),
			     array(7.814037,48.060586),
			     array(7.814244,48.060853),
			     array(7.81443,48.060981),
			     array(7.814755,48.061051),
			     array(7.815071,48.06106),
			     array(7.815055,48.061001),
			     array(7.815552,48.060942),
			     array(7.815568,48.061001),
			     array(7.815746,48.060986),
			     array(7.81617,48.060938),
			     array(7.817091,48.060865),
			     array(7.817076,48.060795),
			     array(7.816679,48.060783),
			     array(7.816387,48.060728),
			     array(7.816272,48.060669),
			     array(7.816471,48.060351),
			     array(7.816978,48.060027),
			     array(7.817045,48.0599),
			     array(7.817215,48.059581),
			     array(7.817384,48.059266),
			     array(7.81758,48.058902),
			     array(7.817644,48.058805),
			     array(7.817795,48.058904),
			     array(7.818094,48.058962),
			     array(7.818438,48.05903),
			     array(7.818655,48.059027),
			     array(7.819,48.059024),
			     array(7.819329,48.058997),
			     array(7.819305,48.059653),
			     array(7.819404,48.059903),
			     array(7.819387,48.060206),
			     array(7.819269,48.060507),
			     array(7.820179,48.060332),
			     array(7.822243,48.059786),
			     array(7.82239,48.059945),
			     array(7.822724,48.059852),
			     array(7.823141,48.059943),
			     array(7.823431,48.059916),
			     array(7.823638,48.059863),
			     array(7.823841,48.05979),
			     array(7.824053,48.059709),
			     array(7.824215,48.059612),
			     array(7.825252,48.05921),
			     array(7.825387,48.059658),
			     array(7.825446,48.060212),
			     array(7.825396,48.060311),
			     array(7.82506,48.060366),
			     array(7.824992,48.060515),
			     array(7.825068,48.060538),
			     array(7.825584,48.060725),
			     array(7.825773,48.060739),
			     array(7.825799,48.061213),
			     array(7.825837,48.06138),
			     array(7.825797,48.061408),
			     array(7.82462,48.062237),
			     array(7.821445,48.064124),
			     array(7.820146,48.064709),
			     array(7.820238,48.064755),
			     array(7.821007,48.064421),
			     array(7.821943,48.064018),
			     array(7.822729,48.063626),
			     array(7.823681,48.062949),
			     array(7.824213,48.063372),
			     array(7.824527,48.063162),
			     array(7.824645,48.063083),
			     array(7.824929,48.062894),
			     array(7.825137,48.063115),
			     array(7.825341,48.06333),
			     array(7.825544,48.063545),
			     array(7.826685,48.062576),
			     array(7.826938,48.062392),
			     array(7.827967,48.061776),
			     array(7.829283,48.062936),
			     array(7.827278,48.06455),
			     array(7.825718,48.065878),
			     array(7.82516,48.066393),
			     array(7.824433,48.066995),
			     array(7.823483,48.067834),
			     array(7.822474,48.068625),
			     array(7.821578,48.06937),
			     array(7.82107,48.069528),
			     array(7.821425,48.070192),
			     array(7.822362,48.06996),
			     array(7.825273,48.068365),
			     array(7.825161,48.068293),
			     array(7.825263,48.068175),
			     array(7.825541,48.068058),
			     array(7.825432,48.067943),
			     array(7.825684,48.067744),
			     array(7.825895,48.067405),
			     array(7.826015,48.067195),
			     array(7.826287,48.066952),
			     array(7.827541,48.0657),
			     array(7.829448,48.064099),
			     array(7.830906,48.063156),
			     array(7.83261,48.06182),
			     array(7.832996,48.062094),
			     array(7.833405,48.061706),
			     array(7.834062,48.061904),
			     array(7.8346,48.062117),
			     array(7.834906,48.062294),
			     array(7.834716,48.062628),
			     array(7.835085,48.062427),
			     array(7.835394,48.062201),
			     array(7.835589,48.061977),
			     array(7.836176,48.062061),
			     array(7.836521,48.061997),
			     array(7.836965,48.061646),
			     array(7.837679,48.061878),
			     array(7.838668,48.062287),
			     array(7.839498,48.06268),
			     array(7.83974,48.062705),
			     array(7.840122,48.062113),
			     array(7.840337,48.061139),
			     array(7.841863,48.06132),
			     array(7.84268,48.060615),
			     array(7.842932,48.060423),
			     array(7.843136,48.06041),
			     array(7.843915,48.059641),
			     array(7.844696,48.059982),
			     array(7.844732,48.060033),
			     array(7.844725,48.060067),
			     array(7.844974,48.060225),
			     array(7.845057,48.060181),
			     array(7.845078,48.060112),
			     array(7.844843,48.059969),
			     array(7.844836,48.059895),
			     array(7.844558,48.059809),
			     array(7.84449,48.059771),
			     array(7.84444,48.059674),
			     array(7.844436,48.059602),
			     array(7.84449,48.059481),
			     array(7.844843,48.059269),
			     array(7.844931,48.059205),
			     array(7.845367,48.058892),
			     array(7.845969,48.05849),
			     array(7.846105,48.058447),
			     array(7.846412,48.058542),
			     array(7.846527,48.058398),
			     array(7.846671,48.058255),
			     array(7.846826,48.058157),
			     array(7.846984,48.058057),
			     array(7.847127,48.057966),
			     array(7.847305,48.05784),
			     array(7.847444,48.057734),
			     array(7.847485,48.057691),
			     array(7.847581,48.057591),
			     array(7.847696,48.05747),
			     array(7.847768,48.057388),
			     array(7.847856,48.057241),
			     array(7.847907,48.057098),
			     array(7.8475,48.056927),
			     array(7.847546,48.056804),
			     array(7.847537,48.056646),
			     array(7.847502,48.056524),
			     array(7.847439,48.056435),
			     array(7.847422,48.056312),
			     array(7.847408,48.056202),
			     array(7.847368,48.056107),
			     array(7.847319,48.056018),
			     array(7.847248,48.055901),
			     array(7.847159,48.055818),
			     array(7.847003,48.055761),
			     array(7.8469,48.055741),
			     array(7.846869,48.055794),
			     array(7.847012,48.055889),
			     array(7.847114,48.055976),
			     array(7.847137,48.056068),
			     array(7.847159,48.056154),
			     array(7.847168,48.056256),
			     array(7.847226,48.056396),
			     array(7.847261,48.056494),
			     array(7.847266,48.056577),
			     array(7.847283,48.056679),
			     array(7.847337,48.056753),
			     array(7.847229,48.056814),
			     array(7.84701,48.056668),
			     array(7.846627,48.056373),
			     array(7.846289,48.05634),
			     array(7.846115,48.056257),
			     array(7.845968,48.056203),
			     array(7.845888,48.056087),
			     array(7.845855,48.055955),
			     array(7.845798,48.055888),
			     array(7.845819,48.055824),
			     array(7.845801,48.055776),
			     array(7.845702,48.05569),
			     array(7.845595,48.055671),
			     array(7.845463,48.055669),
			     array(7.845421,48.055739),
			     array(7.845315,48.05574),
			     array(7.845114,48.055592),
			     array(7.845066,48.055554),
			     array(7.845706,48.055153),
			     array(7.845996,48.0552),
			     array(7.846356,48.055282),
			     array(7.84641,48.055327),
			     array(7.84645,48.05538),
			     array(7.846553,48.055458),
			     array(7.846615,48.055544),
			     array(7.8467,48.055648),
			     array(7.846771,48.055729),
			     array(7.84686,48.055699),
			     array(7.846851,48.055657),
			     array(7.846816,48.055607),
			     array(7.846816,48.05555),
			     array(7.846807,48.05549),
			     array(7.846727,48.055449),
			     array(7.846673,48.055351),
			     array(7.84648,48.055221),
			     array(7.846505,48.055128),
			     array(7.846479,48.055021),
			     array(7.846384,48.055046),
			     array(7.846273,48.055041),
			     array(7.846137,48.055024),
			     array(7.845996,48.054928),
			     array(7.845992,48.054862),
			     array(7.846025,48.054793),
			     array(7.846061,48.054695),
			     array(7.846004,48.054444),
			     array(7.846,48.054246),
			     array(7.846067,48.054114),
			     array(7.845939,48.05386),
			     array(7.845914,48.053695),
			     array(7.845902,48.053552),
			     array(7.845884,48.053278),
			     array(7.845859,48.053159),
			     array(7.845707,48.053044),
			     array(7.845674,48.052903),
			     array(7.845699,48.052801),
			     array(7.845765,48.05272),
			     array(7.845987,48.052613),
			     array(7.846297,48.05252),
			     array(7.846857,48.052412),
			     array(7.847278,48.052354),
			     array(7.847493,48.052357),
			     array(7.847678,48.052365),
			     array(7.847748,48.052385),
			     array(7.84767,48.052817),
			     array(7.847563,48.053019),
			     array(7.847546,48.053176),
			     array(7.847565,48.053263),
			     array(7.847706,48.05323),
			     array(7.847718,48.053165),
			     array(7.847736,48.052939),
			     array(7.847815,48.052653),
			     array(7.847962,48.052613),
			     array(7.849128,48.052594),
			     array(7.850671,48.052577),
			     array(7.850585,48.052806),
			     array(7.850452,48.052988),
			     array(7.850378,48.053172),
			     array(7.850224,48.053289),
			     array(7.850008,48.05337),
			     array(7.849732,48.053568),
			     array(7.849261,48.054108),
			     array(7.848723,48.054553),
			     array(7.848635,48.054432),
			     array(7.84848,48.054378),
			     array(7.848348,48.054412),
			     array(7.848586,48.054613),
			     array(7.848473,48.05467),
			     array(7.848439,48.054728),
			     array(7.848439,48.054814),
			     array(7.848457,48.054895),
			     array(7.848491,48.054963),
			     array(7.848551,48.055055),
			     array(7.848568,48.05513),
			     array(7.848577,48.055262),
			     array(7.84856,48.055497),
			     array(7.848568,48.055709),
			     array(7.848577,48.05587),
			     array(7.848602,48.056025),
			     array(7.848718,48.056011),
			     array(7.848783,48.055952),
			     array(7.84875,48.055849),
			     array(7.84875,48.055723),
			     array(7.848763,48.055557),
			     array(7.848776,48.055408),
			     array(7.848803,48.055215),
			     array(7.84877,48.055111),
			     array(7.848689,48.05499),
			     array(7.848655,48.054792),
			     array(7.848902,48.054662),
			     array(7.849152,48.054476),
			     array(7.849436,48.054342),
			     array(7.849798,48.053902),
			     array(7.849995,48.053664),
			     array(7.850425,48.053476),
			     array(7.850664,48.053311),
			     array(7.851163,48.053443),
			     array(7.851174,48.053327),
			     array(7.851222,48.053297),
			     array(7.851154,48.053224),
			     array(7.850858,48.053188),
			     array(7.850898,48.053035),
			     array(7.850878,48.052837),
			     array(7.851009,48.052524),
			     array(7.851124,48.05211),
			     array(7.851141,48.052017),
			     array(7.851156,48.051929),
			     array(7.851264,48.051799),
			     array(7.851335,48.051759),
			     array(7.851435,48.051724),
			     array(7.851655,48.051611),
			     array(7.851953,48.051342),
			     array(7.852459,48.050979),
			     array(7.852642,48.050954),
			     array(7.852927,48.050947),
			     array(7.853105,48.0509),
			     array(7.853331,48.050756),
			     array(7.853401,48.050482),
			     array(7.853498,48.050126),
			     array(7.85387,48.049967),
			     array(7.854128,48.049744),
			     array(7.852086,48.050357),
			     array(7.851081,48.050686),
			     array(7.850878,48.050947),
			     array(7.850809,48.051064),
			     array(7.850685,48.051189),
			     array(7.85064,48.051281),
			     array(7.85067,48.051352),
			     array(7.850778,48.051273),
			     array(7.850878,48.051218),
			     array(7.850933,48.051182),
			     array(7.850999,48.051117),
			     array(7.850992,48.05106),
			     array(7.85103,48.050986),
			     array(7.851192,48.050889),
			     array(7.851343,48.050843),
			     array(7.851529,48.050744),
			     array(7.851671,48.050647),
			     array(7.851795,48.050617),
			     array(7.851891,48.0507),
			     array(7.851998,48.050772),
			     array(7.852036,48.050857),
			     array(7.851922,48.05093),
			     array(7.851733,48.051041),
			     array(7.851671,48.051124),
			     array(7.851609,48.051221),
			     array(7.851581,48.051322),
			     array(7.851515,48.051384),
			     array(7.851429,48.051449),
			     array(7.851302,48.051488),
			     array(7.851123,48.051423),
			     array(7.85094,48.051552),
			     array(7.850668,48.051451),
			     array(7.850575,48.05135),
			     array(7.850585,48.051248),
			     array(7.850751,48.050917),
			     array(7.850857,48.050759),
			     array(7.849825,48.051094),
			     array(7.849299,48.051265),
			     array(7.848842,48.051444),
			     array(7.84878,48.051418),
			     array(7.845554,48.052454),
			     array(7.845193,48.052385),
			     array(7.850584,48.050611),
			     array(7.851395,48.050339),
			     array(7.852792,48.049875),
			     array(7.852796,48.049816),
			     array(7.852074,48.050043),
			     array(7.851978,48.050017),
			     array(7.85229,48.049452),
			     array(7.851805,48.049377),
			     array(7.852015,48.049045),
			     array(7.853119,48.049078),
			     array(7.853038,48.049524),
			     array(7.852812,48.049524),
			     array(7.852812,48.049585),
			     array(7.853001,48.049589),
			     array(7.852945,48.049826),
			     array(7.853131,48.049756),
			     array(7.853275,48.049009),
			     array(7.852002,48.048916),
			     array(7.852244,48.048608),
			     array(7.85241,48.048342),
			     array(7.852478,48.048152),
			     array(7.852541,48.047922),
			     array(7.852925,48.047248),
			     array(7.85299,48.047147),
			     array(7.853229,48.046614),
			     array(7.853555,48.045598),
			     array(7.853611,48.045211),
			     array(7.853662,48.045035),
			     array(7.853607,48.044971),
			     array(7.853607,48.044911),
			     array(7.853566,48.044859),
			     array(7.853529,48.044813),
			     array(7.853344,48.044901),
			     array(7.853518,48.04409),
			     array(7.85356,48.043901),
			     array(7.852483,48.0428),
			     array(7.852357,48.042095),
			     array(7.852069,48.041352),
			     array(7.852033,48.041258),
			     array(7.851918,48.040905),
			     array(7.852197,48.040881),
			     array(7.852494,48.040935),
			     array(7.852575,48.04085),
			     array(7.852625,48.04064),
			     array(7.851814,48.040512),
			     array(7.85152,48.040307),
			     array(7.851673,48.040116),
			     array(7.851778,48.039894),
			     array(7.851967,48.039619),
			     array(7.852053,48.039389),
			     array(7.853134,48.039791),
			     array(7.853923,48.040066),
			     array(7.854118,48.040005),
			     array(7.85428,48.040257),
			     array(7.854251,48.041235),
			     array(7.85415,48.042101),
			     array(7.85402,48.042563),
			     array(7.853999,48.04291),
			     array(7.853992,48.043174),
			     array(7.853949,48.043434),
			     array(7.85397,48.043699),
			     array(7.853857,48.044326),
			     array(7.853747,48.044542),
			     array(7.853575,48.044779),
			     array(7.853602,48.044809),
			     array(7.853617,48.044825),
			     array(7.853656,48.044868),
			     array(7.853893,48.044666),
			     array(7.854147,48.044311),
			     array(7.854276,48.043889),
			     array(7.854273,48.043591),
			     array(7.854243,48.043155),
			     array(7.854302,48.042514),
			     array(7.854394,48.042206),
			     array(7.854475,48.042041),
			     array(7.854534,48.041296),
			     array(7.854539,48.040987),
			     array(7.85454,48.040938),
			     array(7.854547,48.040832),
			     array(7.854574,48.040395),
			     array(7.854563,48.040253),
			     array(7.854684,48.040237),
			     array(7.85583,48.040257),
			     array(7.855853,48.040749),
			     array(7.855843,48.040818),
			     array(7.855821,48.040959),
			     array(7.855772,48.041285),
			     array(7.855697,48.041589),
			     array(7.855588,48.041878),
			     array(7.855479,48.042043),
			     array(7.8553,48.042155),
			     array(7.855237,48.042174),
			     array(7.855052,48.042151),
			     array(7.854954,48.042097),
			     array(7.854937,48.042201),
			     array(7.855364,48.042367),
			     array(7.855855,48.042436),
			     array(7.856057,48.042483),
			     array(7.85622,48.042825),
			     array(7.856406,48.043203),
			     array(7.856481,48.04329),
			     array(7.856623,48.043306),
			     array(7.856684,48.043186),
			     array(7.856959,48.042247),
			     array(7.857217,48.041667),
			     array(7.85746,48.041213),
			     array(7.856777,48.04132),
			     array(7.856429,48.041412),
			     array(7.856241,48.04147),
			     array(7.856075,48.041557),
			     array(7.855973,48.041644),
			     array(7.855901,48.041591),
			     array(7.856075,48.041006),
			     array(7.856089,48.040735),
			     array(7.856017,48.040261),
			     array(7.855944,48.039966),
			     array(7.855923,48.039821),
			     array(7.855928,48.039744),
			     array(7.855938,48.039649),
			     array(7.855996,48.039583),
			     array(7.85613,48.039496),
			     array(7.856482,48.039381),
			     array(7.857292,48.039172),
			     array(7.857882,48.039087),
			     array(7.859006,48.038999),
			     array(7.859122,48.038832),
			     array(7.858667,48.038878),
			     array(7.85831,48.038921),
			     array(7.858041,48.038953),
			     array(7.857849,48.038976),
			     array(7.857288,48.039043),
			     array(7.856795,48.039156),
			     array(7.856456,48.039227),
			     array(7.855775,48.039415),
			     array(7.85565,48.039658),
			     array(7.85571,48.039974),
			     array(7.855746,48.040137),
			     array(7.855671,48.040139),
			     array(7.855534,48.040142),
			     array(7.855526,48.039974),
			     array(7.855424,48.039759),
			     array(7.855341,48.040071),
			     array(7.855206,48.040092),
			     array(7.85505,48.040107),
			     array(7.854963,48.040142),
			     array(7.854679,48.040134),
			     array(7.854517,48.040057),
			     array(7.854465,48.039921),
			     array(7.854488,48.039798),
			     array(7.854604,48.039728),
			     array(7.85464,48.039711),
			     array(7.854832,48.039601),
			     array(7.854901,48.039566),
			     array(7.855203,48.039393),
			     array(7.855551,48.039499),
			     array(7.855646,48.039422),
			     array(7.855617,48.039352),
			     array(7.855524,48.039291),
			     array(7.855461,48.039229),
			     array(7.855548,48.039109),
			     array(7.855773,48.038919),
			     array(7.855982,48.038772),
			     array(7.856109,48.038772),
			     array(7.856184,48.038907),
			     array(7.85619,48.03895),
			     array(7.855999,48.039066),
			     array(7.856028,48.039105),
			     array(7.856254,48.039051),
			     array(7.856375,48.039182),
			     array(7.85648,48.039163),
			     array(7.856121,48.038683),
			     array(7.85582,48.038722),
			     array(7.855478,48.03902),
			     array(7.855113,48.039345),
			     array(7.854969,48.039426),
			     array(7.854477,48.039705),
			     array(7.854442,48.039596),
			     array(7.854377,48.038204),
			     array(7.854394,48.036757),
			     array(7.854377,48.036683),
			     array(7.850301,48.035355),
			     array(7.848386,48.034732),
			     array(7.845107,48.033566),
			     array(7.844531,48.033382),
			     array(7.841691,48.032338),
			     array(7.832717,48.029915),
			     array(7.82819,48.028866),
			     array(7.8272,48.028572),
			     array(7.827541,48.028139),
			     array(7.82758,48.02809),
			     array(7.827235,48.02798),
			     array(7.827001,48.027822),
			     array(7.826735,48.027573),
			     array(7.826619,48.027436),
			     array(7.826505,48.027169),
			     array(7.826352,48.026838),
			     array(7.826073,48.02651),
			     array(7.825577,48.026002),
			     array(7.825568,48.025839),
			     array(7.825566,48.02581),
			     array(7.825846,48.02533),
			     array(7.826443,48.024881),
			     array(7.826469,48.024717),
			     array(7.826502,48.024218),
			     array(7.826493,48.024004),
			     array(7.826556,48.02382),
			     array(7.826155,48.02346),
			     array(7.825718,48.02375),
			     array(7.824509,48.023283),
			     array(7.825122,48.022524),
			     array(7.825444,48.022514),
			     array(7.826357,48.021989),
			     array(7.827242,48.022294),
			     array(7.82727,48.022058),
			     array(7.827215,48.021855),
			     array(7.826837,48.021648),
			     array(7.825152,48.021309),
			     array(7.8248,48.021437),
			     array(7.823998,48.021012),
			     array(7.82347,48.020697),
			     array(7.823583,48.020612),
			     array(7.823914,48.020386),
			     array(7.823241,48.019998),
			     array(7.822892,48.02023),
			     array(7.820843,48.021646),
			     array(7.818748,48.023146),
			     array(7.818344,48.023433),
			     array(7.817228,48.024242),
			     array(7.814309,48.02636),
			     array(7.813143,48.027204),
			     array(7.812414,48.027733),
			     array(7.812519,48.027825),
			     array(7.81203,48.028198)
			));
		$polygon2 = $this->getLine(
			array(
			     array(7.81681,48.04088),
			     array(7.816209,48.040722),
			     array(7.816433,48.040315),
			     array(7.815426,48.040013),
			     array(7.814958,48.040773),
			     array(7.815572,48.040934),
			     array(7.815561,48.040985),
			     array(7.815843,48.041001),
			     array(7.815911,48.04104),
			     array(7.816188,48.041103),
			     array(7.816333,48.041164),
			     array(7.816135,48.041284),
			     array(7.81629,48.041364),
			     array(7.816528,48.04143),
			     array(7.81681,48.04088)
			));
		$this->assertFalse($polygon1->Intersects($polygon2));
	}

	/**
	 * @see http://pastebin.com/Z1Tus4a8
	 */
	public function testIntersectsLine4() {
		$polygon1 = $this->getLine(
			array(
			     array(7.058739,50.623086),
			     array(7.059033,50.622955),
			     array(7.059485,50.622798),
			     array(7.060153,50.623086),
			     array(7.062358,50.623801),
			     array(7.062512,50.623715),
			     array(7.063171,50.624021),
			     array(7.064249,50.624739),
			     array(7.064309,50.6249),
			     array(7.063755,50.625096),
			     array(7.064145,50.625565),
			     array(7.064086,50.626372),
			     array(7.063155,50.627769),
			     array(7.063099,50.62797),
			     array(7.062815,50.628828),
			     array(7.062702,50.629351),
			     array(7.062224,50.629698),
			     array(7.062467,50.630707),
			     array(7.06296,50.631695),
			     array(7.063597,50.632912),
			     array(7.063535,50.633223),
			     array(7.061665,50.633068),
			     array(7.061102,50.633048),
			     array(7.060711,50.633669),
			     array(7.059739,50.634869),
			     array(7.059231,50.635374),
			     array(7.057399,50.636846),
			     array(7.054154,50.639009),
			     array(7.052192,50.640305),
			     array(7.049111,50.642537),
			     array(7.048097,50.643534),
			     array(7.047224,50.644566),
			     array(7.0467,50.645559),
			     array(7.04625,50.646652),
			     array(7.046041,50.647723),
			     array(7.045951,50.649534),
			     array(7.046127,50.650267),
			     array(7.046717,50.651842),
			     array(7.047461,50.652939),
			     array(7.048277,50.653916),
			     array(7.049673,50.655473),
			     array(7.05002,50.655859),
			     array(7.050705,50.656608),
			     array(7.05281,50.658938),
			     array(7.054817,50.661098),
			     array(7.056978,50.663428),
			     array(7.05814,50.664704),
			     array(7.059358,50.666088),
			     array(7.060562,50.667547),
			     array(7.061259,50.668555),
			     array(7.061844,50.669591),
			     array(7.062189,50.670332),
			     array(7.062435,50.671081),
			     array(7.062534,50.671407),
			     array(7.062618,50.672063),
			     array(7.062681,50.67275),
			     array(7.062625,50.673415),
			     array(7.062569,50.674093),
			     array(7.062146,50.675534),
			     array(7.061337,50.676985),
			     array(7.060861,50.677619),
			     array(7.058828,50.6805),
			     array(7.058122,50.681734),
			     array(7.05909,50.68262),
			     array(7.060173,50.682214),
			     array(7.058825,50.68062),
			     array(7.059269,50.680123),
			     array(7.060069,50.679669),
			     array(7.060305,50.679472),
			     array(7.060452,50.679234),
			     array(7.060446,50.678467),
			     array(7.060925,50.677636),
			     array(7.061427,50.677044),
			     array(7.062967,50.676402),
			     array(7.064291,50.677099),
			     array(7.064972,50.677056),
			     array(7.065266,50.677996),
			     array(7.066416,50.677054),
			     array(7.066347,50.676746),
			     array(7.066201,50.675993),
			     array(7.06596,50.676089),
			     array(7.06581,50.676244),
			     array(7.065829,50.676713),
			     array(7.064848,50.676439),
			     array(7.064699,50.676292),
			     array(7.064852,50.675578),
			     array(7.065407,50.675626),
			     array(7.066134,50.675662),
			     array(7.066728,50.674423),
			     array(7.065836,50.673893),
			     array(7.065137,50.673114),
			     array(7.065944,50.672876),
			     array(7.065729,50.672632),
			     array(7.063903,50.670613),
			     array(7.070432,50.668339),
			     array(7.072075,50.668759),
			     array(7.074584,50.669656),
			     array(7.074586,50.6699),
			     array(7.075225,50.670214),
			     array(7.076972,50.670718),
			     array(7.077712,50.671506),
			     array(7.077916,50.671983),
			     array(7.078096,50.672086),
			     array(7.078333,50.672168),
			     array(7.078628,50.672197),
			     array(7.078628,50.67235),
			     array(7.078506,50.672558),
			     array(7.078597,50.672782),
			     array(7.079207,50.673043),
			     array(7.079483,50.673343),
			     array(7.078831,50.673662),
			     array(7.079483,50.674751),
			     array(7.079859,50.674784),
			     array(7.080105,50.675055),
			     array(7.080371,50.675145),
			     array(7.080194,50.675468),
			     array(7.081208,50.676275),
			     array(7.08123,50.676618),
			     array(7.08183,50.677026),
			     array(7.081489,50.677481),
			     array(7.081539,50.677935),
			     array(7.082007,50.678232),
			     array(7.082111,50.678809),
			     array(7.083497,50.67949),
			     array(7.083496,50.680262),
			     array(7.082466,50.680878),
			     array(7.082059,50.680756),
			     array(7.081637,50.680892),
			     array(7.080664,50.682318),
			     array(7.081384,50.682523),
			     array(7.084615,50.682835),
			     array(7.086103,50.683014),
			     array(7.086216,50.683014),
			     array(7.086572,50.683028),
			     array(7.090631,50.68316),
			     array(7.093638,50.678918),
			     array(7.096028,50.681089),
			     array(7.103945,50.678956),
			     array(7.102675,50.677585),
			     array(7.106195,50.677095),
			     array(7.107287,50.676914),
			     array(7.111844,50.676857),
			     array(7.111899,50.676382),
			     array(7.111905,50.676332),
			     array(7.113827,50.67641),
			     array(7.113827,50.676465),
			     array(7.11387,50.676856),
			     array(7.133899,50.67649),
			     array(7.134844,50.674511),
			     array(7.133797,50.673352),
			     array(7.132484,50.672629),
			     array(7.130982,50.673178),
			     array(7.128768,50.67209),
			     array(7.130416,50.671568),
			     array(7.130493,50.671388),
			     array(7.130871,50.670986),
			     array(7.132012,50.671367),
			     array(7.132644,50.670699),
			     array(7.132785,50.670551),
			     array(7.132012,50.670099),
			     array(7.134003,50.668576),
			     array(7.132952,50.666951),
			     array(7.133845,50.666777),
			     array(7.133857,50.666716),
			     array(7.13227,50.66473),
			     array(7.13168,50.664049),
			     array(7.131528,50.663506),
			     array(7.130818,50.662517),
			     array(7.130247,50.662092),
			     array(7.129562,50.661714),
			     array(7.128706,50.661447),
			     array(7.129997,50.661402),
			     array(7.127719,50.661117),
			     array(7.126648,50.661292),
			     array(7.126277,50.661539),
			     array(7.12316,50.662074),
			     array(7.122979,50.661553),
			     array(7.124575,50.660824),
			     array(7.125683,50.660324),
			     array(7.125348,50.660046),
			     array(7.124893,50.660052),
			     array(7.124335,50.65941),
			     array(7.124596,50.658841),
			     array(7.124208,50.658758),
			     array(7.123899,50.658483),
			     array(7.124217,50.658217),
			     array(7.123736,50.657624),
			     array(7.123195,50.657109),
			     array(7.122614,50.656759),
			     array(7.114374,50.657113),
			     array(7.109761,50.658058),
			     array(7.10944,50.657852),
			     array(7.108822,50.657932),
			     array(7.107831,50.656364),
			     array(7.108327,50.656094),
			     array(7.108195,50.655597),
			     array(7.108778,50.655309),
			     array(7.10899,50.6556),
			     array(7.108486,50.656095),
			     array(7.109767,50.655803),
			     array(7.111003,50.655909),
			     array(7.111198,50.655388),
			     array(7.112117,50.655347),
			     array(7.112469,50.655645),
			     array(7.113644,50.654691),
			     array(7.113387,50.654446),
			     array(7.111542,50.654691),
			     array(7.111065,50.65453),
			     array(7.111065,50.654287),
			     array(7.110109,50.654192),
			     array(7.109286,50.654199),
			     array(7.109223,50.654081),
			     array(7.108725,50.654122),
			     array(7.108313,50.653997),
			     array(7.107861,50.654192),
			     array(7.106312,50.655075),
			     array(7.106328,50.6554),
			     array(7.105808,50.655434),
			     array(7.105081,50.655785),
			     array(7.103971,50.6551),
			     array(7.103408,50.655182),
			     array(7.103739,50.655319),
			     array(7.104933,50.655997),
			     array(7.102249,50.656392),
			     array(7.101796,50.656514),
			     array(7.100369,50.656206),
			     array(7.10039,50.655974),
			     array(7.100157,50.655792),
			     array(7.10042,50.655611),
			     array(7.100477,50.65485),
			     array(7.101281,50.654877),
			     array(7.101278,50.654802),
			     array(7.101101,50.65469),
			     array(7.100903,50.654538),
			     array(7.100094,50.654879),
			     array(7.100003,50.655347),
			     array(7.099454,50.655776),
			     array(7.098531,50.655031),
			     array(7.097573,50.65378),
			     array(7.095837,50.652423),
			     array(7.094409,50.651601),
			     array(7.093301,50.651342),
			     array(7.091049,50.650406),
			     array(7.088395,50.648739),
			     array(7.087357,50.648885),
			     array(7.087121,50.64828),
			     array(7.083931,50.646789),
			     array(7.083799,50.646794),
			     array(7.083709,50.64688),
			     array(7.083579,50.647071),
			     array(7.08325,50.648213),
			     array(7.08243,50.648065),
			     array(7.081813,50.64795),
			     array(7.081779,50.647638),
			     array(7.081836,50.64693),
			     array(7.081862,50.646796),
			     array(7.081998,50.646513),
			     array(7.082182,50.646328),
			     array(7.082278,50.646206),
			     array(7.081879,50.645457),
			     array(7.081834,50.645159),
			     array(7.081802,50.644906),
			     array(7.081753,50.644239),
			     array(7.081809,50.643773),
			     array(7.081993,50.642443),
			     array(7.082148,50.641831),
			     array(7.082208,50.640525),
			     array(7.082699,50.64052),
			     array(7.082703,50.640361),
			     array(7.082817,50.640311),
			     array(7.082624,50.63791),
			     array(7.082607,50.637725),
			     array(7.081459,50.636376),
			     array(7.080897,50.635722),
			     array(7.082959,50.63564),
			     array(7.083886,50.635737),
			     array(7.084673,50.635879),
			     array(7.085416,50.63622),
			     array(7.086844,50.636163),
			     array(7.087042,50.636157),
			     array(7.087403,50.63614),
			     array(7.088089,50.636593),
			     array(7.088364,50.637058),
			     array(7.089079,50.63757),
			     array(7.089454,50.638465),
			     array(7.09016,50.638942),
			     array(7.091387,50.639824),
			     array(7.092072,50.639982),
			     array(7.093745,50.640249),
			     array(7.093915,50.639606),
			     array(7.092455,50.639273),
			     array(7.091138,50.638481),
			     array(7.090713,50.638068),
			     array(7.090741,50.637673),
			     array(7.09146,50.637863),
			     array(7.091801,50.637773),
			     array(7.092493,50.637966),
			     array(7.092606,50.637793),
			     array(7.092351,50.637683),
			     array(7.09198,50.637314),
			     array(7.091711,50.637768),
			     array(7.091445,50.637642),
			     array(7.090594,50.637023),
			     array(7.089873,50.63653),
			     array(7.089754,50.636421),
			     array(7.089727,50.636283),
			     array(7.089828,50.636096),
			     array(7.090076,50.635921),
			     array(7.089819,50.635867),
			     array(7.088662,50.635665),
			     array(7.087881,50.635381),
			     array(7.087478,50.635158),
			     array(7.08615,50.63501),
			     array(7.085468,50.63441),
			     array(7.08486,50.633221),
			     array(7.084793,50.632629),
			     array(7.081829,50.630761),
			     array(7.080886,50.629478),
			     array(7.078812,50.628545),
			     array(7.078634,50.627993),
			     array(7.078494,50.627879),
			     array(7.078425,50.626807),
			     array(7.077937,50.62652),
			     array(7.077981,50.627071),
			     array(7.077707,50.627566),
			     array(7.077158,50.627318),
			     array(7.076126,50.627698),
			     array(7.070579,50.625239),
			     array(7.071325,50.623867),
			     array(7.069187,50.623595),
			     array(7.069027,50.623789),
			     array(7.067497,50.623394),
			     array(7.066003,50.623038),
			     array(7.065535,50.622605),
			     array(7.064378,50.622307),
			     array(7.064597,50.621886),
			     array(7.065201,50.621602),
			     array(7.065745,50.620884),
			     array(7.065228,50.620598),
			     array(7.065437,50.620124),
			     array(7.06537,50.619765),
			     array(7.066737,50.618413),
			     array(7.066482,50.618228),
			     array(7.069241,50.61571),
			     array(7.068383,50.614337),
			     array(7.068054,50.614431),
			     array(7.068551,50.61554),
			     array(7.065498,50.618258),
			     array(7.065501,50.618229),
			     array(7.064985,50.618057),
			     array(7.062914,50.617482),
			     array(7.062281,50.617306),
			     array(7.060598,50.615984),
			     array(7.058756,50.615735),
			     array(7.057432,50.615804),
			     array(7.056841,50.615808),
			     array(7.056888,50.616461),
			     array(7.055515,50.616638),
			     array(7.055356,50.616624),
			     array(7.054786,50.616619),
			     array(7.054814,50.616051),
			     array(7.055392,50.61598),
			     array(7.055371,50.615792),
			     array(7.054909,50.615734),
			     array(7.053932,50.61446),
			     array(7.052918,50.612992),
			     array(7.051772,50.613108),
			     array(7.049285,50.613827),
			     array(7.045887,50.615101),
			     array(7.04427,50.615581),
			     array(7.045222,50.616595),
			     array(7.045993,50.617261),
			     array(7.046801,50.617942),
			     array(7.048583,50.619073),
			     array(7.050597,50.620098),
			     array(7.05763,50.623436),
			     array(7.058739,50.623086),
			));
		$polygon2 = $this->getLine(
			array(
			     array(7.049904,50.641233),
			     array(7.052244,50.639574),
			     array(7.051711,50.639595),
			     array(7.050625,50.639491),
			     array(7.049445,50.640011),
			     array(7.04853,50.640162),
			     array(7.046629,50.641089),
			     array(7.047938,50.641965),
			     array(7.04591,50.643952),
			     array(7.045335,50.644342),
			     array(7.043819,50.643662),
			     array(7.043329,50.643895),
			     array(7.04466,50.644688),
			     array(7.043978,50.64508),
			     array(7.043538,50.645308),
			     array(7.043571,50.645461),
			     array(7.042245,50.646395),
			     array(7.04275,50.64705),
			     array(7.043279,50.647579),
			     array(7.044067,50.648221),
			     array(7.045376,50.649149),
			     array(7.045381,50.648828),
			     array(7.045438,50.647679),
			     array(7.045676,50.646612),
			     array(7.046157,50.645485),
			     array(7.046729,50.644465),
			     array(7.048642,50.642366),
			     array(7.048902,50.642129),
			     array(7.048686,50.641984),
			     array(7.049663,50.641142),
			     array(7.049904,50.641233),
			));
		$this->assertFalse($polygon1->Intersects($polygon2));
	}
	/**
	 * @see http://pastebin.com/ew322iz8
	 */
	public function testIntersectsLine5() {
		$polygon1 = $this->getLine(
			array(
			     array(13.375946,52.520347),
			     array(13.373416,52.520346),
			     array(13.373411,52.520057),
			     array(13.375942,52.520047),
			     array(13.37595,52.519767),
			     array(13.376525,52.51977),
			     array(13.376582,52.519746),
			     array(13.373381,52.519748),
			     array(13.373373,52.520649),
			     array(13.375833,52.520662),
			     array(13.375943,52.520663),
			     array(13.375943,52.520605),
			     array(13.375946,52.520347),
			));
		$polygon2 = $this->getLine(
			array(
			     array(13.376003,52.5199),
			     array(13.376007,52.519929),
			     array(13.376023,52.519959),
			     array(13.376044,52.519982),
			     array(13.376073,52.520002),
			     array(13.376101,52.520016),
			     array(13.37614,52.520029),
			     array(13.376174,52.520036),
			     array(13.376222,52.52004),
			     array(13.376266,52.520038),
			     array(13.376318,52.520029),
			     array(13.376347,52.520019),
			     array(13.376385,52.520001),
			     array(13.376413,52.519982),
			     array(13.376435,52.519958),
			     array(13.376448,52.519935),
			     array(13.376455,52.519906),
			     array(13.376451,52.519878),
			     array(13.376435,52.519846),
			     array(13.376413,52.519823),
			     array(13.376386,52.519804),
			     array(13.376356,52.519789),
			     array(13.376323,52.519778),
			     array(13.376288,52.51977),
			     array(13.376233,52.519765),
			     array(13.376183,52.519768),
			     array(13.376142,52.519776),
			     array(13.376101,52.519789),
			     array(13.376071,52.519804),
			     array(13.376042,52.519825),
			     array(13.376024,52.519845),
			     array(13.376009,52.519872),
			     array(13.376003,52.5199),
			));
		$this->assertTrue($polygon1->Intersects($polygon2));
	}
	/**
	 * @see http://pastebin.com/Tp2pECQj
	 */
	public function testIntersectsLine6() {
		$polygon1 = $this->getLine(
			array(
			     array(13.191574,53.27085),
			     array(13.190434,53.269461),
			     array(13.191553,53.269245),
			     array(13.192545,53.270579),
			     array(13.191574,53.27085),
			));
		$polygon2 = $this->getLine(
			array(
			     array(13.192545,53.270579),
			     array(13.193275,53.270367),
			     array(13.192644,53.269465),
			     array(13.193083,53.269384),
			     array(13.192913,53.269321),
			     array(13.192616,53.269236),
			     array(13.192212,53.269173),
			     array(13.191759,53.269215),
			     array(13.191553,53.269245),
			     array(13.192545,53.270579),
			));
		$this->assertFalse($polygon1->Intersects($polygon2));
	}
	public function testIntersectsLine7() {
		$polygon1 = $this->getLine(
			array(
			     array(12.896374,52.2922),
			     array(12.897069,52.29178),
			     array(12.896611,52.291525),
			     array(12.895919,52.291948),
			     array(12.896374,52.2922)
			));
		$polygon2 = $this->getLine(
			array(
			     array(12.897069,52.29178),
			     array(12.897618,52.291817),
			     array(12.897661,52.291386),
			     array(12.897095,52.291392),
			     array(12.897069,52.29178)
			));
		$this->assertFalse($polygon1->Contains($polygon2));
		$this->assertFalse($polygon1->Intersects($polygon2));
	}
	public function testContainsTooFewTestLines() {
		$line = $this->getLine(
			array(
			     array(13.5860699,50.8617334),
			     array(13.5862738,50.8634805),
			     array(13.5843014,50.8639633),
			     array(13.5841665,50.8639471),
			     array(13.5860699,50.8617334)
			));
		/** @var Point $point */
		$point = $this->_pointFactory->create('Point',
		                                      array('lon' => (float) 13.5860699,
		                                            'lat' => (float) 50.8617334));
		$this->assertTrue($line->ContainsPoint($point));
	}
	public function testContainsNotARing() {
		$line = $this->getLine(
			array(
			     array(4,1),
			     array(3.5,5),
			     array(1,5.5),
			));
		$test1 = $this->getLine(
			array(
			     array(1,1),
			     array(3.5,5),
			     array(3,4),
			));
		$test2 = $this->getLine(
			array(
			     array(1,1),
			     array(3.5,5),
			     array(6,3),
			));
		$test3 = $this->getLine(
			array(
			     array(1,1),
			     array(3.5,5),
			     array(0,3),
			));
		$this->assertFalse($line->Intersects($test1));
		$this->assertTrue($line->Intersects($test2));
		$this->assertFalse($line->Intersects($test3));
	}
	public function testGetSpinDirection() {
		$outerString = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(0,0.5),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$this->assertSame('r',$outerString->GetSpinDirection());
		$outerString2 = $this->getLine(array(
		                                   array(0,1),
		                                   array(0,0),
		                                   array(1,0),
		                                   array(0,0.5),
		                                   array(1,1),
		                                   array(0,1),
		                              ));
		$this->assertSame('l',$outerString2->GetSpinDirection());
	}
	public function testIsTheSame() {
		$lineString1 = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(0,0.5),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$lineString2 = $this->getLine(array(
		                                   array(0,0.5),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1),
		                                   array(1,1),
		                                   array(0,0.5),
		                              ));
		$lineString3 = $this->getLine(array(
		                                   array(0,0.5),
		                                   array(1,0),
		                                   array(0.5,0),
		                                   array(0.25,0),
		                                   array(0,0),
		                                   array(0,1),
		                                   array(1,1),
		                                   array(0,0.5),
		                              ));
		$reversedLineString2 = $this->getLine(array(
		                                           array(0,0.5),
		                                           array(1,1),
		                                           array(0,1),
		                                           array(0,0),
		                                           array(1,0),
		                                           array(0,0.5),
		                              ));
		$reversedLineString3 = $this->getLine(array(
		                                           array(0,0.5),
		                                           array(1,1),
		                                           array(0,1),
		                                           array(0,0),
		                                           array(0.25,0),
		                                           array(0.5,0),
		                                           array(1,0),
		                                           array(0,0.5),
		                              ));

		$optimizedLineString3 = clone $lineString3;
		$optimizedLineString3->optimizeLineString();
		$reversedOptimizedLineString3 = clone $reversedLineString3;
		$reversedOptimizedLineString3->optimizeLineString();
		$lineString4 = $this->getLine(array(
		                                   array(0,0.5),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,0.5),
		                              ));

		$this->assertTrue($lineString1->equals($lineString1));
		$this->assertTrue($lineString2->equals($lineString2));
		$this->assertTrue($lineString3->equals($lineString3));
		$this->assertTrue($optimizedLineString3->equals($optimizedLineString3));
		$this->assertTrue($reversedLineString2->equals($reversedLineString2));
		$this->assertTrue($reversedLineString3->equals($reversedLineString3));
		$this->assertTrue($reversedOptimizedLineString3->equals($reversedOptimizedLineString3));
		$this->assertTrue($lineString4->equals($lineString4));

		$this->assertTrue($lineString1->equals($lineString2));
		$this->assertTrue($lineString2->equals($lineString1));
		$this->assertTrue($lineString1->equals($reversedLineString2));
		$this->assertTrue($reversedLineString2->equals($lineString1));
		$this->assertTrue($lineString2->equals($reversedLineString2));
		$this->assertTrue($reversedLineString2->equals($lineString2));
		try {
			$lineString1->equals($lineString3);
			$this->fail();
		} catch (NotOptimizedException $e) {
			$this->assertTrue($lineString1->equals($optimizedLineString3));
		}
		try {
			$lineString1->equals($reversedLineString3);
			$this->fail();
		} catch (NotOptimizedException $e) {
			$this->assertTrue($lineString1->equals($reversedOptimizedLineString3));
		}
		try {
			$lineString2->equals($lineString3);
			$this->fail();
		} catch (NotOptimizedException $e) {
			$this->assertTrue($lineString2->equals($optimizedLineString3));
		}
		try {
			$lineString2->equals($reversedLineString3);
			$this->fail();
		} catch (NotOptimizedException $e) {
			$this->assertTrue($lineString2->equals($reversedOptimizedLineString3));
		}
		try {
			$lineString3->equals($lineString2);
			$this->fail();
		} catch (NotOptimizedException $e) {
			$this->assertTrue($optimizedLineString3->equals($lineString2));
		}
		try {
			$lineString3->equals($lineString1);
			$this->fail();
		} catch (NotOptimizedException $e) {
			$this->assertTrue($optimizedLineString3->equals($lineString1));
		}
		try {
			$reversedLineString3->equals($lineString2);
			$this->fail();
		} catch (NotOptimizedException $e) {
			$this->assertTrue($reversedOptimizedLineString3->equals($lineString2));
		}
		try {
			$reversedLineString3->equals($lineString1);
			$this->fail();
		} catch (NotOptimizedException $e) {
			$this->assertTrue($reversedOptimizedLineString3->equals($lineString1));
		}

		$this->assertFalse($lineString1->equals($lineString4));
		$this->assertFalse($lineString2->equals($lineString4));
		$this->assertFalse($reversedLineString2->equals($lineString4));
		try {
			$lineString3->equals($lineString4);
			$this->fail();
		} catch (NotOptimizedException $e) {
			$this->assertFalse($optimizedLineString3->equals($lineString4));
		}
		try {
			$reversedLineString3->equals($lineString4);
			$this->fail();
		} catch (NotOptimizedException $e) {
			$this->assertFalse($reversedOptimizedLineString3->equals($lineString4));
		}
		$this->assertFalse($lineString4->equals($lineString1));
		$this->assertFalse($lineString4->equals($lineString2));
		$this->assertFalse($lineString4->equals($reversedLineString2));
		try {
			$lineString4->equals($lineString3);
			$this->fail();
		} catch (NotOptimizedException $e) {
			$this->assertFalse($lineString4->equals($optimizedLineString3));
		}
		try {
			$lineString4->equals($reversedLineString3);
			$this->fail();
		} catch (NotOptimizedException $e) {
			$this->assertFalse($lineString4->equals($reversedOptimizedLineString3));
		}
	}
	protected function _getOnlyLineString(MultiPolygon $multiPolygon) {
		$this->assertEquals(1,$multiPolygon->getPolygons()->count());
		/** @var Polygon $onlyPolygon */
		$onlyPolygon = $multiPolygon->getPolygons()->get(0);
		$this->assertEquals(0,$onlyPolygon->getInners()->count());
		return $onlyPolygon->getOuter();
	}
	public function testGetIntersection1() {
		$lineString1 = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(0,0.5),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$selfIntersect = $lineString1->getIntersection($lineString1);
		$this->assertTrue($lineString1->equals($this->_getOnlyLineString($selfIntersect)));
	}
	public function testGetIntersection2() {
		$lineString1 = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(0,0.5),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$lineString2 = $this->getLine(array(
		                                   array(0,0.5),
		                                   array(1,0.5),
		                                   array(1,1.5),
		                                   array(0,1.5),
		                                   array(0,0.5),
		                              ));
		$result = $this->getLine(array(
		                              array(0,1),
		                              array(1,1),
		                              array(0,0.5),
		                              array(0,1),
		                         ));
		$this->assertTrue($result->equals($this->_getOnlyLineString($lineString1->getIntersection($lineString2))));
		$this->assertTrue($result->equals($this->_getOnlyLineString($lineString1->getIntersection($result))));
	}
	public function testGetIntersection3() {
		$lineString1 = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(0,0.5),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$lineString2 = $this->getLine(array(
		                                   array(0.75,1.75),
		                                   array(1.75,1.75),
		                                   array(1.75,0.75),
		                                   array(0.75,0.75),
		                                   array(0.75,1.75)
		                              ));
		$result = $this->getLine(array(
		                              array(0.75,1),
		                              array(1,1),
		                              array(0.75,0.875),
		                              array(0.75,1),
		                         ));
		$this->assertTrue($result->equals($this->_getOnlyLineString($lineString1->getIntersection($lineString2))));
		$this->assertTrue($result->equals($this->_getOnlyLineString($lineString1->getIntersection($result))));
	}
	public function testGetIntersection4() {
		$lineString1 = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$lineString2 = $this->getLine(array(
		                                   array(0,2),
		                                   array(2,2),
		                                   array(2,0),
		                                   array(0,0),
		                                   array(0,2)
		                              ));
		$lineString3 = $this->getLine(array(
		                                   array(-1,2),
		                                   array(2,2),
		                                   array(2,-1),
		                                   array(-1,-1),
		                                   array(-1,2)
		                              ));
		$this->assertTrue($lineString1->equals($this->_getOnlyLineString($lineString1->getIntersection($lineString2))));
		$this->assertTrue($lineString1->equals($this->_getOnlyLineString($lineString2->getIntersection($lineString1))));
		$this->assertTrue($lineString1->equals($this->_getOnlyLineString($lineString1->getIntersection($lineString3))));
		$this->assertTrue($lineString1->equals($this->_getOnlyLineString($lineString3->getIntersection($lineString1))));
	}
	public function testGetIntersection5() {
		$lineString1 = $this->getLine(array(
		                                   array(0,2),
		                                   array(2,2),
		                                   array(2,3),
		                                   array(0,3),
		                                   array(0,5),
		                                   array(2,5),
		                                   array(2,0),
		                                   array(0,0),
		                                   array(0,2),
		                              ));
		$lineString2 = $this->getLine(array(
		                                   array(-1,1),
		                                   array(1,1),
		                                   array(1,4),
		                                   array(-1,4),
		                                   array(-1,1)
		                              ));
		$lineString3 = $this->getLine(array(
		                                   array(0,1),
		                                   array(0,2),
		                                   array(1,2),
		                                   array(1,1),
		                                   array(0,1)
		                              ));
		$lineString4 = $this->getLine(array(
		                                   array(0,3),
		                                   array(0,4),
		                                   array(1,4),
		                                   array(1,3),
		                                   array(0,3)
		                              ));
		$multiPolygon = $lineString1->getIntersection($lineString2);
		/** @var LineString[] $results */
		$results = array($lineString3,$lineString4);
		$this->assertInstanceOf('DpOpenGis\Model\MultiPolygon',$multiPolygon);
		$this->assertSame(2,$multiPolygon->getPolygons()->count());
		/** @var Polygon $polygon */
		foreach ($multiPolygon->getPolygons() as $polygon) {
			$this->assertSame(0,$polygon->getInners()->count());
			foreach ($results as $nr => $result)
				if ($result->equals($polygon->getOuter())) {
					unset($results[$nr]);
					break;
				}
		}
		$this->assertEmpty($results);
	}
	public function testGetIntersection6() {
		$lineString1 = $this->getLine(array(
		                                   array(0.1,1),
		                                   array(1,1.1),
		                                   array(1.1,0.1),
		                                   array(0,0),
		                                   array(0.1,1)
		                              ));
		$lineString2 = $this->getLine(array(
		                                   array(2,0.75),
		                                   array(1.035,0.75),
		                                   array(1.05,0.6),
		                                   array(1.5,-1),
		                                   array(0.5,-1),
		                                   array(0.5,4.5/99),
		                                   array(0.3,2.7/99),
		                                   array(0.3,0.3),
		                                   array(1.08,0.3),
		                                   array(1.035,0.75),
		                                   array(0.3,0.75),
		                                   array(0.3,8.3/9),
		                                   array(-1.1,6.9/9),
			                               array(0,-2),
		                                   array(2,-2),
		                                   array(2,0.75)
		                              ));
		$result = $this->getLine(array(
		                                   array(0.3,2.7/99),
		                                   array(0.3,0.3),
		                                   array(1.08,0.3),
		                                   array(1.035,0.75),
		                                   array(0.3,0.75),
		                                   array(0.3,8.3/9),
		                                   array(0.1,1),
		                                   array(0,0),
		                                   array(0.3,2.7/99),
		                              ));
		$this->assertTrue($result->equals($this->_getOnlyLineString($lineString1->getIntersection($lineString2))));
	}
	public function testIntersectState() {
		$lineString1 = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$reversedLineString1 = $this->_reverse($lineString1);
		$lineString2 = $this->getLine(array(
		                                   array(1,2),
		                                   array(2,2),
		                                   array(2,1),
		                                   array(1,1),
		                                   array(1,2)
		                              ));
		$reversedLineString2 = $this->_reverse($lineString2);

		$params = array($lineString1->getPoints(),$lineString2->getPoints(),1,3);
		$result = $this->_getPrivateMethod('DpOpenGis\Model\LineString','_getTouchingLine',$params);
		$this->assertSame(false,$result['intersects']);
		$this->assertSame('l',$result['side']);
		$this->assertCount(1,$result['touches']);
		$this->assertEquals($result['touches'][0]->__toString(),$lineString1->getPoints()->get(1)->__toString());

		$params2 = array($lineString1->getPoints(),$reversedLineString2->getPoints(),1,1);
		$result2 = $this->_getPrivateMethod('DpOpenGis\Model\LineString','_getTouchingLine',$params2);
		$this->assertSame(false,$result2['intersects']);
		$this->assertSame('r',$result2['side']);
		$this->assertCount(1,$result2['touches']);
		$this->assertEquals($result2['touches'][0]->__toString(),$lineString1->getPoints()->get(1)->__toString());

		$params3 = array($lineString2->getPoints(),$reversedLineString2->getPoints(),3,1);
		$result3 = $this->_getPrivateMethod('DpOpenGis\Model\LineString','_getTouchingLine',$params3);
		$this->assertSame(false,$result3['intersects']);
		$this->assertFalse(isset($result3['side']));
		$this->assertCount(4,$result3['touches']);
		$result3['touches'][] = $result3['touches'][0];
		/** @var LineString $resultLineString */
		$resultLineString = LineStringFactory::getInstance()->create(
			'LineString',
			array('points' => new Points($result3['touches'])));
		$this->assertTrue($lineString2->equals($reversedLineString2));
		$this->assertTrue($lineString2->equals($resultLineString));
		$this->assertTrue($reversedLineString2->equals($resultLineString));

		$params4 = array($lineString1->getPoints(),$lineString2->getPoints(),0,2);
		$result4 = $this->_getPrivateMethod('DpOpenGis\Model\LineString','_getTouchingLine',$params4);
		$this->assertSame(false,$result4['intersects']);
		$this->assertSame('l',$result4['side']);
		$this->assertCount(1,$result4['touches']);
		$this->assertEquals($result4['touches'][0]->__toString(),$lineString1->getPoints()->get(1)->__toString());

		$params5 = array($lineString1->getPoints(),$reversedLineString2->getPoints(),0,0);
		$result5 = $this->_getPrivateMethod('DpOpenGis\Model\LineString','_getTouchingLine',$params5);
		$this->assertSame(false,$result5['intersects']);
		$this->assertSame('r',$result5['side']);
		$this->assertCount(1,$result5['touches']);
		$this->assertEquals($result5['touches'][0]->__toString(),$lineString1->getPoints()->get(1)->__toString());

		$params6 = array($lineString2->getPoints(),$reversedLineString2->getPoints(),2,0);
		$result6 = $this->_getPrivateMethod('DpOpenGis\Model\LineString','_getTouchingLine',$params6);
		$this->assertSame(false,$result6['intersects']);
		$this->assertFalse(isset($result6['side']));
		$this->assertCount(4,$result6['touches']);
		/** @var LineString $resultLineString2 */
		$result6['touches'][] = $result6['touches'][0];
		$resultLineString2 = LineStringFactory::getInstance()->create(
			'LineString',
			array('points' => new Points($result6['touches'])));
		$this->assertTrue($lineString2->equals($reversedLineString2));
		$this->assertTrue($lineString2->equals($resultLineString2));
		$this->assertTrue($reversedLineString2->equals($resultLineString2));

		$params7 = array($reversedLineString1->getPoints(),$lineString2->getPoints(),3,3);
		$result7 = $this->_getPrivateMethod('DpOpenGis\Model\LineString','_getTouchingLine',$params7);
		$this->assertSame(false,$result7['intersects']);
		$this->assertSame('l',$result7['side']);
		$this->assertCount(1,$result7['touches']);
		$this->assertEquals($result7['touches'][0]->__toString(),$reversedLineString1->getPoints()->get(3)->__toString());

		$params8 = array($reversedLineString1->getPoints(),$reversedLineString2->getPoints(),3,1);
		$result8 = $this->_getPrivateMethod('DpOpenGis\Model\LineString','_getTouchingLine',$params8);
		$this->assertSame(false,$result8['intersects']);
		$this->assertSame('r',$result8['side']);
		$this->assertCount(1,$result8['touches']);
		$this->assertEquals($result8['touches'][0]->__toString(),$reversedLineString1->getPoints()->get(3)->__toString());

	}
	public function testIntersectState2() {
		$lineString1 = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(1,0),
		                                   array(0,0),
		                                   array(0,1)
		                              ));
		$lineString2 = $this->getLine(array(
		                                   array(1,2),
		                                   array(2,2),
		                                   array(2,1),
		                                   array(1,1),
		                                   array(0.5,0.5),
		                                   array(1,2)
		                              ));
		$reversedLineString2 = $this->_reverse($lineString2);

		$params = array($lineString1->getPoints(),$lineString2->getPoints(),1,3);
		$result = $this->_getPrivateMethod('DpOpenGis\Model\LineString','_getTouchingLine',$params);
		$this->assertSame(true,$result['intersects']);
		$this->assertSame(array('r','l'),$result['side']);
		$this->assertCount(1,$result['touches']);
		$this->assertEquals($result['touches'][0]->__toString(),$lineString1->getPoints()->get(1)->__toString());

		$params2 = array($lineString1->getPoints(),$reversedLineString2->getPoints(),1,2);
		$result2 = $this->_getPrivateMethod('DpOpenGis\Model\LineString','_getTouchingLine',$params2);
		$this->assertSame(true,$result2['intersects']);
		$this->assertSame(array('l','r'),$result2['side']);
		$this->assertCount(1,$result2['touches']);
		$this->assertEquals($result2['touches'][0]->__toString(),$lineString1->getPoints()->get(1)->__toString());
	}
	public function testOptimizeLineString() {
		$lineString1 = $this->getLine(array(
		                                   array(0,0),
		                                   array(0,1),
		                                   array(1,1),
		                                   array(1,0),
		                                   array(1,-1),
		                                   array(0,-1),
		                                   array(0,-0.5),
		                                   array(0,-0.25),
		                                   array(0,0),
		                              ));
		$lineString2 = clone $lineString1;
		$this->assertFalse($lineString1->isOptimized());
		$this->assertFalse($lineString2->isOptimized());
		$lineString1->optimizeLineString();
		$this->assertTrue($lineString1->isOptimized());
		$this->assertFalse($lineString2->isOptimized());
		$expectedResult = array(
			array(0,1),
			array(1,1),
			array(1,-1),
			array(0,-1),
			array(0,1),
		);
		/** @var Point[]|IPointCollection $actualResult */
		$actualResult = $lineString1->getPoints();
		foreach ($actualResult as $nr => $point)
			$this->assertEquals($expectedResult[$nr],array($point->getLon(),$point->getLat()));
		$this->assertSame(9,$lineString2->NumPoints());
		$this->assertSame(5,$lineString1->NumPoints());
		foreach ($actualResult as $nr => $point)
			foreach ($expectedResult as $nr2 => $coords)
				if ($point->getLat() == $coords[1] && $point->getLon() == $coords[0]) {
					unset($actualResult[$nr]);
					unset($expectedResult[$nr2]);
					continue 2;
				}
		$this->assertEmpty($expectedResult);
		$this->assertEmpty($actualResult->toArray());
	}
	public function testContains() {
		$lineString1 = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(1,0),
		                                   array(2,0),
		                                   array(2,2),
		                                   array(3,2),
		                                   array(3,-1),
		                                   array(0,-1),
		                                   array(0,1),
		                              ));
		$lineString2 = $this->getLine(array(
		                                   array(0,0),
		                                   array(2,0),
		                                   array(2,-1),
		                                   array(0,-1),
		                                   array(0,0),
		                              ));
		$lineString3 = $this->getLine(array(
		                                   array(0,0),
		                                   array(1,0),
		                                   array(1,-1),
		                                   array(0,-1),
		                                   array(0,0),
		                              ));
		$lineString4 = $this->getLine(array(
		                                   array(0,0),
		                                   array(0,-1),
		                                   array(2,-1),
		                                   array(2,0),
		                                   array(0,0),
		                              ));
		$lineString5 = $this->getLine(array(
		                                   array(0,0),
		                                   array(0,-1),
		                                   array(1,-1),
		                                   array(1,0),
		                                   array(0,0),
		                              ));
		$lineString6 = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,0),
		                                   array(2,2),
		                                   array(2,-1),
		                                   array(0,-1),
		                                   array(0,1),
		                              ));
		$lineString7 = $this->getLine(array(
		                                   array(0,1),
		                                   array(0,-1),
		                                   array(1,0),
		                                   array(0,1),
		                              ));
		$this->assertTrue($lineString1->Contains($lineString2));
		$this->assertTrue($lineString1->Contains($lineString3));
		$this->assertTrue($lineString1->Contains($lineString4));
		$this->assertTrue($lineString1->Contains($lineString5));
		$this->assertTrue($lineString2->Contains($lineString5));
		$this->assertTrue($lineString2->Contains($lineString3));
		$this->assertTrue($lineString4->Contains($lineString5));
		$this->assertTrue($lineString4->Contains($lineString3));
		$this->assertFalse($lineString1->Contains($lineString6));
		$this->assertTrue($lineString1->Contains($lineString7));
	}
	public function testContains2() {
		$lineString1 = $this->getLine(array(
		                                   array(0,1),
		                                   array(1,1),
		                                   array(1,0),
		                                   array(2,1),
		                                   array(2,2),
		                                   array(3,2),
		                                   array(3,-1),
		                                   array(0,-1),
		                                   array(0,1),
		                              ));
		$lineString2 = $this->getLine(array(
		                                   array(0,-1),
		                                   array(2,1),
		                                   array(2,-1),
		                                   array(0,-1),
		                              ));
		$lineString3 = $this->getLine(array(
		                                   array(0,-1),
		                                   array(2,-1),
		                                   array(2,1),
		                                   array(0,-1),
		                              ));
		$lineString4 = $this->getLine(array(
		                                   array(0,-1),
		                                   array(1.5,0.5),
		                                   array(1.5,-1),
		                                   array(0,-1),
		                              ));
		$lineString5 = $this->getLine(array(
		                                   array(0,-1),
		                                   array(1.5,-1),
		                                   array(1.5,0.5),
		                                   array(0,-1),
		                              ));
		$lineString6 = $this->getLine(array(
		                                   array(0,1),
		                                   array(0,-1),
		                                   array(3,-1),
		                                   array(3,2),
		                                   array(2,2),
		                                   array(2,1),
		                                   array(1,0),
		                                   array(1,1),
		                                   array(0,1),
		                              ));
		$this->assertTrue($lineString1->Contains($lineString2));
		$this->assertTrue($lineString1->Contains($lineString3));
		$this->assertTrue($lineString1->Contains($lineString4));
		$this->assertTrue($lineString1->Contains($lineString5));
		$this->assertTrue($lineString6->Contains($lineString2));
		$this->assertTrue($lineString6->Contains($lineString3));
		$this->assertTrue($lineString6->Contains($lineString4));
		$this->assertTrue($lineString6->Contains($lineString5));
		$this->assertFalse($lineString2->Contains($lineString1));
		$this->assertFalse($lineString3->Contains($lineString1));
		$this->assertFalse($lineString4->Contains($lineString1));
		$this->assertFalse($lineString5->Contains($lineString1));
		$this->assertFalse($lineString2->Contains($lineString6));
		$this->assertFalse($lineString3->Contains($lineString6));
		$this->assertFalse($lineString4->Contains($lineString6));
		$this->assertFalse($lineString5->Contains($lineString6));
	}
}