<?php
namespace DpOpenGisTest\MappingType;

use DpPHPUnitExtensions\PHPUnit\TestCase;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use DpOpenGis\Factory\PointFactory;
use DpOpenGis\MappingType\PointType;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * Class PointTypeTest
 *
 * @package DpOpenGisTest\MappingType
 */
class PointTypeTest extends TestCase
{
	const SUT = 'DpOpenGis\MappingType\PointType';
	/**
	 * @var EntityManager
	 */
	static protected $_em;
	/**
	 * @var \DpOpenGis\MappingType\PointType
	 */
	protected $_pointType;
	/**
	 * @var array
	 */
	protected $_emptyState;

	public function setUp() {
		parent::setUp();
		$serviceManager = new ServiceManager(new Config(array('invokables' => array(
			'DpOpenGis\Model\Point' => 'DpOpenGis\Model\Point',
			'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point'
		),
          'factories' => array(
              'DpOpenGis\Factory\PointFactory' => function (ServiceLocatorInterface $sm) {
                  $factory = PointFactory::getInstance();
                  $factory->setServiceLocator($sm);
                  return $factory;
              }))));

		if (!isset(self::$_em)) {
			$isDevMode = true;
			$doctrineConfig = Setup::createYAMLMetadataConfiguration(array(getcwd()), $isDevMode);
			// database configuration parameters
			$conn = array('driver'   => 'pdo_mysql');
			// obtaining the entity manager
			self::$_em = EntityManager::create($conn, $doctrineConfig);
		}
		if (!Type::hasType('point')) {
			Type::addType('point', 'DpOpenGis\MappingType\PointType');
			self::$_em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('point', 'point');
			PointFactory::getInstance()->setServiceLocator($serviceManager);
		}
		Type::getType('point')->setServiceLocator($serviceManager);
		$this->_pointType = Type::getType('point');
	}

	public function testName() {
		$this->assertSame('point', $this->_pointType->getName());
	}

	public function testSqlDeclaration() {
		$this->assertSame('POINT',
		                  $this->_pointType->getSqlDeclaration(array(),
		                                                       self::$_em->getConnection()->getDatabasePlatform()));
		$this->assertTrue($this->_pointType->canRequireSQLConversion());
	}

	public function testConvertSql() {
		$this->assertSame('AsText(test)',
		                  $this->_pointType->convertToPHPValueSQL('test',
		                                                          self::$_em->getConnection()->getDatabasePlatform()));
		$this->assertSame('PointFromText(test)',
		                  $this->_pointType->convertToDatabaseValueSQL('test',
	                                                               self::$_em->getConnection()->getDatabasePlatform()));
	}

	public function testConvertToPhp() {
		$lon = (float) 4;
		$lat = (float) 5;
		$point = $this->_pointType->convertToPHPValue('POINT('.$lon.' '.$lat.')',
		                                              self::$_em->getConnection()->getDatabasePlatform());
		$this->assertSame($lon,$point->getLon());
		$this->assertSame($lat,$point->getLat());

		$lon2 = (float) 8;
		$lat2 = (float) 2;
		$point2 = $this->_pointType->convertToPHPValue('POINT('.$lon2.' '.$lat2.')',
		                                               self::$_em->getConnection()->getDatabasePlatform());
		$this->assertSame($lon2,$point2->getLon());
		$this->assertSame($lat2,$point2->getLat());
	}
	public function testConvertToDatabase() {
		$lon = (float) 4;
		$lat = (float) 5;
		$point = PointFactory::getInstance()->create('Point',array('lon' => $lon,'lat' => $lat));
		$pointSql = $this->_pointType->convertToDatabaseValue($point,
		                                              self::$_em->getConnection()->getDatabasePlatform());
		list($lonResult,$latResult) = sscanf($pointSql,'POINT(%f %f)');
		$this->assertSame($lon,$lonResult);
		$this->assertSame($lat,$latResult);

		$lon2 = (float) 8;
		$lat2 = (float) 2;
		$point2 = PointFactory::getInstance()->create('Point',array('lon' => $lon2,'lat' => $lat2));

		$pointSql2 = $this->_pointType->convertToDatabaseValue($point2,
		                                                      self::$_em->getConnection()->getDatabasePlatform());
		list($lonResult2,$latResult2) = sscanf($pointSql2,'POINT(%f %f)');

		$this->assertSame($lon2,$lonResult2);
		$this->assertSame($lat2,$latResult2);
	}
	public function testConvertToDatabasePrecision() {
		$lon = 10.3449816;
		$lat = 47.3198571;
		$point = PointFactory::getInstance()->create('Point',array('lon' => $lon,'lat' => $lat));
		$pointSql = $this->_pointType->convertToDatabaseValue($point,
		                                              self::$_em->getConnection()->getDatabasePlatform());
		$this->assertNotSame('POINT(10.344982 47.319857)',$pointSql);
		$this->assertSame('POINT(10.3449816 47.3198571)',$pointSql);

		list($lonResult,$latResult) = sscanf($pointSql,'POINT(%f %f)');
		$this->assertSame($lon,$lonResult);
		$this->assertSame($lat,$latResult);
	}

}
