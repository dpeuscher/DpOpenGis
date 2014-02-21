<?php
namespace DpOpenGisTest\MappingType;

use DpPHPUnitExtensions\PHPUnit\TestCase;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use DpOpenGis\Collection\PointCollection;
use DpOpenGis\Factory\PointFactory;
use DpOpenGis\Factory\LineStringFactory;
use DpOpenGis\MappingType\LineStringType;
use DpOpenGis\Model\LineString;
use DpOpenGis\Model\Point;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * Class LineStringTypeTest
 *
 * @package DpOpenGisTest\MappingType
 */
class LineStringTypeTest extends TestCase
{
	const SUT = 'DpOpenGis\MappingType\LineStringType';
	/**
	 * @var EntityManager
	 */
	static protected $_em;
	/**
	 * @var \DpOpenGis\MappingType\LineStringType
	 */
	protected $_lineStringType;
	/**
	 * @var array
	 */
	protected $_emptyState;

	public function setUp() {
		parent::setUp();
		if (!Type::hasType('lineString')) {
			$isDevMode = true;
			$doctrineConfig = Setup::createYAMLMetadataConfiguration(array(getcwd()), $isDevMode);
			// database configuration parameters
			$conn = array('driver'   => 'pdo_mysql');
			// obtaining the entity manager
			self::$_em = EntityManager::create($conn, $doctrineConfig);
			Type::addType('lineString', 'DpOpenGis\MappingType\LineStringType');
			self::$_em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('lineString', 'lineString');
			PointFactory::getInstance()->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
				'DpOpenGis\Model\Point' => 'DpOpenGis\Model\Point',
				'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point'
			)))));
			LineStringFactory::getInstance()->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
				'DpOpenGis\Model\Point' => 'DpOpenGis\Model\Point',
				'DpOpenGis\ModelInterface\IPointCollection' => 'DpOpenGis\Collection\PointCollection',
				'DpOpenGis\Model\LineString' => 'DpOpenGis\Model\LineString',
				'DpOpenGis\Validator\LineString' => 'DpOpenGis\Validator\LineString'
			)))));
		}
		$lineStringType = Type::getType('lineString');
		/** @var LineStringType $lineStringType */
		$lineStringType->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
			'DpOpenGis\ModelInterface\IPointCollection' => 'DpOpenGis\Collection\PointCollection',
			'DpOpenGis\Model\Point' => 'DpOpenGis\Model\Point',
			'DpOpenGis\Model\LineString' => 'DpOpenGis\Model\LineString',
			'DpOpenGis\Validator\LineString' => 'DpOpenGis\Validator\LineString',
			'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point'
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
		                                                                 )))));
		$this->_lineStringType = $lineStringType;
	}

	public function testName() {
		$this->assertSame('lineString', $this->_lineStringType->getName());
	}

	public function testSqlDeclaration() {
		$this->assertSame('LINESTRING',
		                  $this->_lineStringType->getSqlDeclaration(array(),
		                                                       self::$_em->getConnection()->getDatabasePlatform()));
		$this->assertTrue($this->_lineStringType->canRequireSQLConversion());
	}

	public function testConvertSql() {
		$this->assertSame('AsText(test)',
		                  $this->_lineStringType->convertToPHPValueSQL('test',
		                                                          self::$_em->getConnection()->getDatabasePlatform()));
		$this->assertSame('LineStringFromText(test)',
		                  $this->_lineStringType->convertToDatabaseValueSQL('test',
	                                                               self::$_em->getConnection()->getDatabasePlatform()));
	}

	public function testConvertToPhp() {
		$points = array(array(4.0,5.0),array(8.0,2.0));
		$lineString = $this->_lineStringType->convertToPHPValue('LINESTRING('.$points[0][0].' '.$points[0][1].','.
			                                                   $points[1][0].' '.$points[1][1].')',
		                                              self::$_em->getConnection()->getDatabasePlatform());
		foreach ($lineString->getPoints() as $nr => $point) {
			/** @var Point $point */
			$this->assertSame($points[$nr][0],$point->getLon());
			$this->assertSame($points[$nr][1],$point->getLat());
		}
	}
	public function testConvertToDatabase() {
		$points = array(array(4.0,5.0),array(8.0,2.0));

		$collection = new PointCollection();
		$collection->add(PointFactory::getInstance()->create('Point',
		                 array('lon' => $points[0][0],'lat' => $points[0][1])));
		$collection->add(PointFactory::getInstance()->create('Point',
		                 array('lon' => $points[1][0],'lat' => $points[1][1])));

		/** @var LineString $lineString */
		$lineString = LineStringFactory::getInstance()->create('LineString',array('points' => $collection));

		$lineStringSql = $this->_lineStringType->convertToDatabaseValue($lineString,
		                                              self::$_em->getConnection()->getDatabasePlatform());
		list($lonResult1,$latResult1,$lonResult2,$latResult2) = sscanf($lineStringSql,'LINESTRING(%f %f,%f %f)');
		$this->assertSame($points[0][0],$lonResult1);
		$this->assertSame($points[0][1],$latResult1);
		$this->assertSame($points[1][0],$lonResult2);
		$this->assertSame($points[1][1],$latResult2);
	}
}
