<?php
namespace DpOpenGisTest\MappingType;

use DpPHPUnitExtensions\PHPUnit\TestCase;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use DpOpenGis\Collection\LineStringCollection;
use DpOpenGis\Collection\PointCollection;
use DpOpenGis\Collection\PolygonCollection;
use DpOpenGis\Factory\MultiPolygonFactory;
use DpOpenGis\Factory\PointFactory;
use DpOpenGis\Factory\PolygonFactory;
use DpOpenGis\MappingType\MultiPolygonType;
use DpOpenGis\Factory\LineStringFactory;
use DpOpenGis\MappingType\LineStringType;
use DpOpenGis\MappingType\PolygonType;
use DpOpenGis\Model\LineString;
use DpOpenGis\Model\MultiPolygon;
use DpOpenGis\Model\Point;
use DpOpenGis\Model\Polygon;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * Class MultiPolygonTypeTest
 *
 * @package DpOpenGisTest\MappingType
 */
class MultiPolygonTypeTest extends TestCase
{
	const SUT = 'DpOpenGis\MappingType\MultiPolygonType';
	/**
	 * @var EntityManager
	 */
	static protected $_em;
	/**
	 * @var \DpOpenGis\MappingType\MultiPolygonType
	 */
	protected $_multiPolygonType;
	/**
	 * @var array
	 */
	protected $_emptyState;

	public function setUp() {
		parent::setUp();
		if (!Type::hasType('multipolygon')) {
			$isDevMode = true;
			$doctrineConfig = Setup::createYAMLMetadataConfiguration(array(getcwd()), $isDevMode);
			// database configuration parameters
			$conn = array('driver'   => 'pdo_mysql');
			// obtaining the entity manager
			self::$_em = EntityManager::create($conn, $doctrineConfig);
			Type::addType('multipolygon', 'DpOpenGis\MappingType\MultiPolygonType');
			self::$_em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('multipolygon',
                'multipolygon');
			Type::addType('polygon', 'DpOpenGis\MappingType\PolygonType');
			self::$_em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('polygon','polygon');
			Type::addType('linestring', 'DpOpenGis\MappingType\LineStringType');
			self::$_em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('linestring','linestring');
			$serviceManager = new ServiceManager(new Config(array('invokables' => array(
				'DpOpenGis\Model\Point' => 'DpOpenGis\Model\Point',
				'DpOpenGis\ModelInterface\IPointCollection' => 'DpOpenGis\Collection\PointCollection',
				'DpOpenGis\Model\LineString' => 'DpOpenGis\Model\LineString',
				'DpOpenGis\ModelInterface\ILineStringCollection' => 'DpOpenGis\Collection\LineStringCollection',
				'DpOpenGis\Model\Polygon' => 'DpOpenGis\Model\Polygon',
				'DpOpenGis\ModelInterface\IPolygonCollection' => 'DpOpenGis\Collection\PolygonCollection',
				'DpOpenGis\Model\MultiPolygon' => 'DpOpenGis\Model\MultiPolygon',
				'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point',
				'DpOpenGis\Validator\LineString' => 'DpOpenGis\Validator\LineString',
				'DpOpenGis\Validator\Polygon' => 'DpOpenGis\Validator\Polygon',
				'DpOpenGis\Validator\MultiPolygon' => 'DpOpenGis\Validator\MultiPolygon',
			),
		        'factories' => array(
		            'DpOpenGis\Factory\PointFactory' => function (ServiceLocatorInterface $sm) {
		                $factory = PointFactory::getInstance();
		                $factory->setServiceLocator($sm);
		                return $factory;
		            },
		            'DpOpenGis\Factory\LineStringFactory' => function (ServiceLocatorInterface $sm) {
		                $factory = LineStringFactory::getInstance();
		                $factory->setServiceLocator($sm);
		                return $factory;
		            },
		            'DpOpenGis\Factory\PolygonFactory' => function (ServiceLocatorInterface $sm) {
			            $factory = PolygonFactory::getInstance();
			            $factory->setServiceLocator($sm);
			            return $factory;
		            },
		            'DpOpenGis\Factory\MultiPolygonFactory' => function (ServiceLocatorInterface $sm) {
			            $factory = MultiPolygonFactory::getInstance();
			            $factory->setServiceLocator($sm);
			            return $factory;
		            },
			                                                ))));
			PointFactory::getInstance()->setServiceLocator($serviceManager);
			LineStringFactory::getInstance()->setServiceLocator($serviceManager);
			PolygonFactory::getInstance()->setServiceLocator($serviceManager);
            MultiPolygonFactory::getInstance()->setServiceLocator($serviceManager);
			$multiPolygonType = Type::getType('multipolygon');
			/** @var MultiPolygonType $multiPolygonType */
			$multiPolygonType->setServiceLocator($serviceManager);
			$polygonType = Type::getType('polygon');
			/** @var PolygonType $polygonType */
			$polygonType->setServiceLocator($serviceManager);
			$lineStringType = Type::getType('linestring');
			/** @var LineStringType $lineStringType */
			$lineStringType->setServiceLocator($serviceManager);
		}
		$multiPolygonType = Type::getType('multipolygon');
		/** @var MultiPolygonType $multiPolygonType */
		$this->_multiPolygonType = $multiPolygonType;
	}

	public function testName() {
		$this->assertSame('multipolygon', $this->_multiPolygonType->getName());
	}

	public function testSqlDeclaration() {
		$this->assertSame('MULTIPOLYGON',
		                  $this->_multiPolygonType->getSqlDeclaration(array(),
		                                                       self::$_em->getConnection()->getDatabasePlatform()));
		$this->assertTrue($this->_multiPolygonType->canRequireSQLConversion());
	}

	public function testConvertSql() {
		$this->assertSame('AsText(test)',
		                  $this->_multiPolygonType->convertToPHPValueSQL('test',
		                                                          self::$_em->getConnection()->getDatabasePlatform()));
		$this->assertSame('MPolyFromText(test)',
		                  $this->_multiPolygonType->convertToDatabaseValueSQL('test',
	                                                               self::$_em->getConnection()->getDatabasePlatform()));
	}

	public function testConvertToPhp() {
		$points = array(
            array(array(array(0.0,0.0),array(4.0,0.0),array(4.0,4.0),array(0.0,4.0),array(0.0,0.0)),
                array(array(0.0,0.0),array(1.0,0.0),array(1.0,1.0),array(0.0,1.0),array(0.0,0.0)),
                array(array(2.0,2.0),array(3.0,2.0),array(3.0,3.0),array(2.0,3.0),array(2.0,2.0))),
            array(array(array(-4.0,-4.0),array(0.0,-4.0),array(0.0,0.0),array(-4.0,0.0),array(-4.0,-4.0)),
                array(array(-4.0,-4.0),array(-3.0,-4.0),array(-3.0,-3.0),array(-4.0,-3.0),array(-4.0,-4.0)),
                array(array(-2.0,-2.0),array(-1.0,-2.0),array(-1.0,-1.0),array(-2.0,-1.0),array(-2.0,-2.0)))
        );
		$multiPolygon = $this->_multiPolygonType->convertToPHPValue('MULTIPOLYGON(('.
			 '('.$points[0][0][0][0].' '.$points[0][0][0][1].','.$points[0][0][1][0].' '.$points[0][0][1][1].','.
			     $points[0][0][2][0].' '.$points[0][0][2][1].','.$points[0][0][3][0].' '.$points[0][0][3][1].','.
			     $points[0][0][4][0].' '.$points[0][0][4][1].'),'.
             '('.$points[0][1][0][0].' '.$points[0][1][0][1].','.$points[0][1][1][0].' '.$points[0][1][1][1].','.
	             $points[0][1][2][0].' '.$points[0][1][2][1].','.$points[0][1][3][0].' '.$points[0][1][3][1].','.
	             $points[0][1][4][0].' '.$points[0][1][4][1].'),'.
             '('.$points[0][2][0][0].' '.$points[0][2][0][1].','.$points[0][2][1][0].' '.$points[0][2][1][1].','.
	             $points[0][2][2][0].' '.$points[0][2][2][1].','.$points[0][2][3][0].' '.$points[0][2][3][1].','.
	             $points[0][2][4][0].' '.$points[0][2][4][1].')),('.
                '('.$points[1][0][0][0].' '.$points[1][0][0][1].','.$points[1][0][1][0].' '.$points[1][0][1][1].','.
                $points[1][0][2][0].' '.$points[1][0][2][1].','.$points[1][0][3][0].' '.$points[1][0][3][1].','.
                $points[1][0][4][0].' '.$points[1][0][4][1].'),'.
                '('.$points[1][1][0][0].' '.$points[1][1][0][1].','.$points[1][1][1][0].' '.$points[1][1][1][1].','.
                $points[1][1][2][0].' '.$points[1][1][2][1].','.$points[1][1][3][0].' '.$points[1][1][3][1].','.
                $points[1][1][4][0].' '.$points[1][1][4][1].'),'.
                '('.$points[1][2][0][0].' '.$points[1][2][0][1].','.$points[1][2][1][0].' '.$points[1][2][1][1].','.
                $points[1][2][2][0].' '.$points[1][2][2][1].','.$points[1][2][3][0].' '.$points[1][2][3][1].','.
                $points[1][2][4][0].' '.$points[1][2][4][1].')))'
		                                              ,self::$_em->getConnection()->getDatabasePlatform());
		foreach ($multiPolygon->getPolygons() as $pnr => $polygon) {
            /** @var Polygon $polygon */
            $outer = $polygon->getOuter();
            $this->assertSame(5,$outer->NumPoints());
            foreach ($outer->getPoints() as $nr => $point) {
                /** @var Point $point */
                $this->assertSame($points[$pnr][0][$nr][0],$point->getLon());
                $this->assertSame($points[$pnr][0][$nr][1],$point->getLat());
            }
            $this->assertSame(2,$polygon->getInners()->count());
            foreach ($polygon->getInners() as $nr => $inner) {
                /** @var LineString $inner */
                $this->assertSame(5,$inner->NumPoints());
                foreach ($inner->getPoints() as $nr2 => $point) {
                    /** @var Point $point */
                    $this->assertSame($points[$pnr][$nr+1][$nr2][0],$point->getLon());
                    $this->assertSame($points[$pnr][$nr+1][$nr2][1],$point->getLat());
                }
            }
        }
	}
	public function testConvertToDatabase() {
        $points = array(
            array(array(array(0.0,0.0),array(4.0,0.0),array(4.0,4.0),array(0.0,4.0),array(0.0,0.0)),
                array(array(0.0,0.0),array(1.0,0.0),array(1.0,1.0),array(0.0,1.0),array(0.0,0.0)),
                array(array(2.0,2.0),array(3.0,2.0),array(3.0,3.0),array(2.0,3.0),array(2.0,2.0))),
            array(array(array(-4.0,-4.0),array(0.0,-4.0),array(0.0,0.0),array(-4.0,0.0),array(-4.0,-4.0)),
                array(array(-4.0,-4.0),array(-3.0,-4.0),array(-3.0,-3.0),array(-4.0,-3.0),array(-4.0,-4.0)),
                array(array(-2.0,-2.0),array(-1.0,-2.0),array(-1.0,-1.0),array(-2.0,-1.0),array(-2.0,-2.0)))
        );
        $polygons = new PolygonCollection();
        foreach ($points as $pnr => $polygonPoints) {
	        /** @var LineString $lineString */
	        $inners = new LineStringCollection();
            foreach ($polygonPoints as $nr => $line) {
                $collection = new PointCollection();
                foreach ($line as $point)
                    $collection->add(PointFactory::getInstance()->create('Point',
                                     array('lon' => $point[0],'lat' => $point[1])));
	            $lineString = LineStringFactory::getInstance()->create('LineString',array('points' => $collection));
	            if ($nr === 0)
		            $outer = $lineString;
		        else
                    $inners->add($lineString);
            }
	        /** @var Polygon $polygon */
	        $polygon = PolygonFactory::getInstance()->create('Polygon',array('outer' => $outer,
	                                                                         'inners' => $inners));
	        $polygons->add($polygon);
        }
		/** @var MultiPolygon $multiPolygon */
        $multiPolygon = MultiPolygonFactory::getInstance()->create('MultiPolygon',array('polygons' => $polygons));
        $multiPolygonSql = $this->_multiPolygonType->convertToDatabaseValue($multiPolygon,
                                                      self::$_em->getConnection()->getDatabasePlatform());
        $resultPoints = array(
            array(array(array(),array(),array(),array(),array()),
                array(array(),array(),array(),array(),array()),
                array(array(),array(),array(),array(),array())),
            array(array(array(),array(),array(),array(),array()),
                array(array(),array(),array(),array(),array()),
                array(array(),array(),array(),array(),array()))
        );

        list($resultPoints[0][0][0][0],$resultPoints[0][0][0][1],$resultPoints[0][0][1][0],$resultPoints[0][0][1][1],
            $resultPoints[0][0][2][0],$resultPoints[0][0][2][1],$resultPoints[0][0][3][0],$resultPoints[0][0][3][1],
            $resultPoints[0][0][4][0],$resultPoints[0][0][4][1],
            $resultPoints[0][1][0][0],$resultPoints[0][1][0][1],$resultPoints[0][1][1][0],$resultPoints[0][1][1][1],
            $resultPoints[0][1][2][0],$resultPoints[0][1][2][1],$resultPoints[0][1][3][0],$resultPoints[0][1][3][1],
            $resultPoints[0][1][4][0],$resultPoints[0][1][4][1],
            $resultPoints[0][2][0][0],$resultPoints[0][2][0][1],$resultPoints[0][2][1][0],$resultPoints[0][2][1][1],
            $resultPoints[0][2][2][0],$resultPoints[0][2][2][1],$resultPoints[0][2][3][0],$resultPoints[0][2][3][1],
            $resultPoints[0][2][4][0],$resultPoints[0][2][4][1],
            $resultPoints[1][0][0][0],$resultPoints[1][0][0][1],$resultPoints[1][0][1][0],$resultPoints[1][0][1][1],
            $resultPoints[1][0][2][0],$resultPoints[1][0][2][1],$resultPoints[1][0][3][0],$resultPoints[1][0][3][1],
            $resultPoints[1][0][4][0],$resultPoints[1][0][4][1],
            $resultPoints[1][1][0][0],$resultPoints[1][1][0][1],$resultPoints[1][1][1][0],$resultPoints[1][1][1][1],
            $resultPoints[1][1][2][0],$resultPoints[1][1][2][1],$resultPoints[1][1][3][0],$resultPoints[1][1][3][1],
            $resultPoints[1][1][4][0],$resultPoints[1][1][4][1],
            $resultPoints[1][2][0][0],$resultPoints[1][2][0][1],$resultPoints[1][2][1][0],$resultPoints[1][2][1][1],
            $resultPoints[1][2][2][0],$resultPoints[1][2][2][1],$resultPoints[1][2][3][0],$resultPoints[1][2][3][1],
            $resultPoints[1][2][4][0],$resultPoints[1][2][4][1])
            = sscanf($multiPolygonSql,'MULTIPOLYGON(('.
            '(%f %f,%f %f,%f %f,%f %f,%f %f),'.'(%f %f,%f %f,%f %f,%f %f,%f %f),'.'(%f %f,%f %f,%f %f,%f %f,%f %f)),'.
            '((%f %f,%f %f,%f %f,%f %f,%f %f),'.'(%f %f,%f %f,%f %f,%f %f,%f %f),'.'(%f %f,%f %f,%f %f,%f %f,%f %f)))');
        foreach ($points as $nr => $poly)
            foreach ($poly as $nr2 => $line)
                foreach ($line as $nr3 => $point) {
                    $this->assertSame($point[0],$resultPoints[$nr][$nr2][$nr3][0]);
                    $this->assertSame($point[1],$resultPoints[$nr][$nr2][$nr3][1]);
                }
	}
}
