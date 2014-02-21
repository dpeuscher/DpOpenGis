<?php
namespace DpOpenGisTest\MappingType;

use DpPHPUnitExtensions\PHPUnit\TestCase;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use DpOpenGis\Collection\LineStringCollection;
use DpOpenGis\Collection\PointCollection;
use DpOpenGis\Factory\PointFactory;
use DpOpenGis\Factory\PolygonFactory;
use DpOpenGis\Factory\LineStringFactory;
use DpOpenGis\MappingType\LineStringType;
use DpOpenGis\MappingType\PolygonType;
use DpOpenGis\Model\LineString;
use DpOpenGis\Model\Point;
use DpOpenGis\Model\Polygon;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

/**
 * Class PolygonTypeTest
 *
 * @package DpOpenGisTest\MappingType
 */
class PolygonTypeTest extends TestCase
{
	const SUT = 'DpOpenGis\MappingType\PolygonType';
	/**
	 * @var EntityManager
	 */
	static protected $_em;
	/**
	 * @var \DpOpenGis\MappingType\PolygonType
	 */
	protected $_polygonType;
	/**
	 * @var array
	 */
	protected $_emptyState;

	public function setUp() {
		parent::setUp();
        $isDevMode = true;
        $doctrineConfig = Setup::createYAMLMetadataConfiguration(array(getcwd()), $isDevMode);
        // database configuration parameters
        $conn = array('driver'   => 'pdo_mysql');

        self::$_em = EntityManager::create($conn, $doctrineConfig);
        self::$_em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('polygon', 'polygon');
		if (!Type::hasType('polygon')) {
			Type::addType('polygon', 'DpOpenGis\MappingType\PolygonType');
			$serviceManager = new ServiceManager(new Config(array('invokables' => array(
				'DpOpenGis\Model\Point' => 'DpOpenGis\Model\Point',
				'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point',
				'DpOpenGis\ModelInterface\IPointCollection' => 'DpOpenGis\Collection\PointCollection',
				'DpOpenGis\Model\LineString' => 'DpOpenGis\Model\LineString',
				'DpOpenGis\ModelInterface\ILineStringCollection' => 'DpOpenGis\Collection\LineStringCollection',
				'DpOpenGis\Validator\LineString' => 'DpOpenGis\Validator\LineString',
				'DpOpenGis\Model\Polygon' => 'DpOpenGis\Model\Polygon'
			))));
			PointFactory::getInstance()->setServiceLocator($serviceManager);
			LineStringFactory::getInstance()->setServiceLocator($serviceManager);
			PolygonFactory::getInstance()->setServiceLocator($serviceManager);
			PointFactory::getInstance()->setServiceLocator($serviceManager);
			LineStringFactory::getInstance()->setServiceLocator($serviceManager);
			PolygonFactory::getInstance()->setServiceLocator($serviceManager);
			$polygonType = Type::getType('polygon');
			/** @var PolygonType $polygonType */
			$polygonType->setServiceLocator($serviceManager);
			$lineStringType = Type::getType('linestring');
			/** @var LineStringType $lineStringType */
			$lineStringType->setServiceLocator($serviceManager);
		}
		$this->_polygonType = Type::getType('polygon');
	}

	public function testName() {
		$this->assertSame('polygon', $this->_polygonType->getName());
	}

	public function testSqlDeclaration() {
		$this->assertSame('POLYGON',
		                  $this->_polygonType->getSqlDeclaration(array(),
		                                                       self::$_em->getConnection()->getDatabasePlatform()));
		$this->assertTrue($this->_polygonType->canRequireSQLConversion());
	}

	public function testConvertSql() {
		$this->assertSame('AsText(test)',
		                  $this->_polygonType->convertToPHPValueSQL('test',
		                                                          self::$_em->getConnection()->getDatabasePlatform()));
		$this->assertSame('PolygonFromText(test)',
		                  $this->_polygonType->convertToDatabaseValueSQL('test',
	                                                               self::$_em->getConnection()->getDatabasePlatform()));
	}

	public function testConvertToPhp() {
		$points = array(array(array(0.0,0.0),array(4.0,0.0),array(4.0,4.0),array(0.0,4.0),array(0.0,0.0)),
		                array(array(0.0,0.0),array(1.0,0.0),array(1.0,1.0),array(0.0,1.0),array(0.0,0.0)),
		                array(array(2.0,2.0),array(3.0,2.0),array(3.0,3.0),array(2.0,3.0),array(2.0,2.0)));
		$polygon = $this->_polygonType->convertToPHPValue('POLYGON('.
			 '('.$points[0][0][0].' '.$points[0][0][1].','.$points[0][1][0].' '.$points[0][1][1].','.
			     $points[0][2][0].' '.$points[0][2][1].','.$points[0][3][0].' '.$points[0][3][1].','.
			     $points[0][4][0].' '.$points[0][4][1].'),'.
             '('.$points[1][0][0].' '.$points[1][0][1].','.$points[1][1][0].' '.$points[1][1][1].','.
	             $points[1][2][0].' '.$points[1][2][1].','.$points[1][3][0].' '.$points[1][3][1].','.
	             $points[1][4][0].' '.$points[1][4][1].'),'.
             '('.$points[2][0][0].' '.$points[2][0][1].','.$points[2][1][0].' '.$points[2][1][1].','.
	             $points[2][2][0].' '.$points[2][2][1].','.$points[2][3][0].' '.$points[2][3][1].','.
	             $points[2][4][0].' '.$points[2][4][1].'))'
		                                              ,self::$_em->getConnection()->getDatabasePlatform());
		$outer = $polygon->getOuter();
		$this->assertSame(5,$outer->NumPoints());
		foreach ($outer->getPoints() as $nr => $point) {
			/** @var Point $point */
			$this->assertSame($points[0][$nr][0],$point->getLon());
			$this->assertSame($points[0][$nr][1],$point->getLat());
		}
		$this->assertSame(2,$polygon->getInners()->count());
		foreach ($polygon->getInners() as $nr => $inner) {
			/** @var LineString $inner */
			$this->assertSame(5,$inner->NumPoints());
			foreach ($inner->getPoints() as $nr2 => $point) {
				/** @var Point $point */
				$this->assertSame($points[$nr+1][$nr2][0],$point->getLon());
				$this->assertSame($points[$nr+1][$nr2][1],$point->getLat());
			}
		}
	}
	public function testConvertToDatabase() {
		$points = array(array(array(0.0,0.0),array(4.0,0.0),array(4.0,4.0),array(0.0,4.0),array(0.0,0.0)),
		                array(array(0.0,0.0),array(1.0,0.0),array(1.0,1.0),array(0.0,1.0),array(0.0,0.0)),
		                array(array(2.0,2.0),array(3.0,2.0),array(3.0,3.0),array(2.0,3.0),array(2.0,2.0)));
		/** @var LineString $outer */
		$inners = new LineStringCollection();
		foreach ($points as $nr => $line) {
			$collection = new PointCollection();
			foreach ($line as $point)
				$collection->add(PointFactory::getInstance()->create('Point',
				                 array('lon' => $point[0],'lat' => $point[1])));
			/** @var LineString $lineString */
			$lineString = LineStringFactory::getInstance()->create('LineString',array('points' => $collection));
			if ($nr === 0)
				$outer = $lineString;
			else
				$inners->add($lineString);
		}
		$polygon = PolygonFactory::getInstance()->create('Polygon',array('outer' => $outer,'inners' => $inners));
		/** @var Polygon $polygon */
		$polygonSql = $this->_polygonType->convertToDatabaseValue($polygon,
		                                              self::$_em->getConnection()->getDatabasePlatform());
		$resultPoints = array(array(array(),array(),array(),array(),array()),
		                      array(array(),array(),array(),array(),array()),
		                      array(array(),array(),array(),array(),array()));

		list($resultPoints[0][0][0],$resultPoints[0][0][1],$resultPoints[0][1][0],$resultPoints[0][1][1],
			$resultPoints[0][2][0],$resultPoints[0][2][1],$resultPoints[0][3][0],$resultPoints[0][3][1],
			$resultPoints[0][4][0],$resultPoints[0][4][1],
			$resultPoints[1][0][0],$resultPoints[1][0][1],$resultPoints[1][1][0],$resultPoints[1][1][1],
			$resultPoints[1][2][0],$resultPoints[1][2][1],$resultPoints[1][3][0],$resultPoints[1][3][1],
			$resultPoints[1][4][0],$resultPoints[1][4][1],
			$resultPoints[2][0][0],$resultPoints[2][0][1],$resultPoints[2][1][0],$resultPoints[2][1][1],
			$resultPoints[2][2][0],$resultPoints[2][2][1],$resultPoints[2][3][0],$resultPoints[2][3][1],
			$resultPoints[2][4][0],$resultPoints[2][4][1])
			= sscanf($polygonSql,'POLYGON('.
			'(%f %f,%f %f,%f %f,%f %f,%f %f),'.'(%f %f,%f %f,%f %f,%f %f,%f %f),'.'(%f %f,%f %f,%f %f,%f %f,%f %f))');
		foreach ($points as $nr => $line)
			foreach ($line as $nr2 => $point) {
				$this->assertSame($point[0],$resultPoints[$nr][$nr2][0]);
				$this->assertSame($point[1],$resultPoints[$nr][$nr2][1]);
			}
	}
}
