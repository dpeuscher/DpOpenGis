<?php
namespace DpOpenGis;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use DpOpenGis\Factory\LineStringFactory;
use DpOpenGis\Factory\MultiPolygonFactory;
use DpOpenGis\Factory\PointFactory;
use DpOpenGis\Factory\PolygonFactory;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class Module
 *
 * @package DpOpenGis
 */
class Module
{
	public function onBootstrap(MvcEvent $e) {
		$serviceManager = $e->getApplication()->getServiceManager();
		/** @var EntityManager $entityManager */
		$entityManager = $serviceManager->get('doctrine.entitymanager.orm_default');
		$serviceManager->get('DpOpenGis\MappingType\PointType');
		$serviceManager->get('DpOpenGis\MappingType\LineStringType');
		$serviceManager->get('DpOpenGis\MappingType\PolygonType');
		$serviceManager->get('DpOpenGis\MappingType\MultiPolygonType');
		$entityManager->getConnection()->getDatabasePlatform()
			->registerDoctrineTypeMapping('point', 'point');
		$entityManager->getConnection()->getDatabasePlatform()
			->registerDoctrineTypeMapping('linestring', 'linestring');
		$entityManager->getConnection()->getDatabasePlatform()
			->registerDoctrineTypeMapping('polygon', 'polygon');
		$entityManager->getConnection()->getDatabasePlatform()
			->registerDoctrineTypeMapping('multipolygon', 'multipolygon');
	}
	/**
	 * @return array
	 */
	public function getConfig()
    {
        return array();
    }

	/**
	 * @return array
	 */
	public function getServiceConfig()
    {
	    bcscale(7);
	    return array(
		    'invokables' => array(
			    'DpOpenGis\Model\MultiPolygon'                   => 'DpOpenGis\Model\CachedMultiPolygon',
			    'DpOpenGis\Model\Polygon'                        => 'DpOpenGis\Model\CachedPolygon',
			    'DpOpenGis\Model\LineString'                     => 'DpOpenGis\Model\CachedLineString',
			    'DpOpenGis\Model\Point'                          => 'DpOpenGis\Model\Point',
			    'DpOpenGis\ModelInterface\IPointCollection'      => 'DpOpenGis\Collection\PointCollection',
			    'DpOpenGis\ModelInterface\IReversePointCollection' => 'DpOpenGis\Collection\ReversePointCollection',
			    'DpOpenGis\ModelInterface\ILineStringCollection' => 'DpOpenGis\Collection\LineStringCollection',
			    'DpOpenGis\ModelInterface\IPolygonCollection'    => 'DpOpenGis\Collection\PolygonCollection',
			    'DpOpenGis\Validator\MultiPolygon'               => 'DpOpenGis\Validator\MultiPolygon',
			    'DpOpenGis\Validator\Polygon'                    => 'DpOpenGis\Validator\Polygon',
			    'DpOpenGis\Validator\LineString'                 => 'DpOpenGis\Validator\LineString',
			    'DpOpenGis\Validator\Point'                      => 'DpOpenGis\Validator\Point',
		    ),
		    'factories' => array(
			    'DpOpenGis\MappingType\MultiPolygonType'         => function (ServiceLocatorInterface $sm) {
				    if (!Type::hasType('multipolygon'))
				        Type::addType('multipolygon', 'DpOpenGis\MappingType\MultiPolygonType');
				    return Type::getType('multipolygon');
				},
			    'DpOpenGis\MappingType\PolygonType'              => function (ServiceLocatorInterface $sm) {
				    if (!Type::hasType('polygon'))
				        Type::addType('polygon', 'DpOpenGis\MappingType\PolygonType');
				    return Type::getType('polygon');
			    },
			    'DpOpenGis\MappingType\LineStringType'           => function (ServiceLocatorInterface $sm) {
				    if (!Type::hasType('linestring'))
				        Type::addType('linestring', 'DpOpenGis\MappingType\LineStringType');
				    return Type::getType('linestring');
			    },
			    'DpOpenGis\MappingType\PointType'                => function (ServiceLocatorInterface $sm) {
				    if (!Type::hasType('point'))
				        Type::addType('point', 'DpOpenGis\MappingType\PointType');
				    return Type::getType('point');
			    },
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

		    ),
		    'initializers' => array(
			    function($instance, $serviceManager) {
				    if ($instance instanceof ServiceLocatorAwareInterface) {
					    $instance->setServiceLocator($serviceManager);
				    }
			    }
		    )
	    );
    }
	/**
	 * @return array
	 */
	public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}
