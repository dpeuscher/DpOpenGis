<?php
namespace DpOpenGisTest\Model;

use DpOpenGis\Model\Point;
use DpPHPUnitExtensions\PHPUnit\TestCase;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

/**
 * Class PointTest
 *
 * @package DpOpenGisTest\Model
 */
class PointTest extends TestCase {
	const SUT = 'DpOpenGis\Model\Point';
	/**
	 * @var \DpOpenGis\Model\Point
	 */
	protected $_point;
	/**
	 * @var array
	 */
	protected $_emptyState;
	public function setUp() {
		parent::setUp();
		$this->_point = new Point();
		$this->_point->setServiceLocator(new ServiceManager(new Config(array('invokables' => array(
			'DpOpenGis\Validator\Point' => 'DpOpenGis\Validator\Point'
		)))));
		$this->_emptyState = array('lat' => null,'lon' => null);
	}
	public function testInitialState()
	{
		$point = clone $this->_point;

		$this->assertNull($point->getLat());
		$this->assertNull($point->getLon());
	}
	public function testSettersGetters()
	{
		$point = clone $this->_point;
        $lat = 3.4889654;
        $lon = 80.456662;
		$point->exchangeArray(array('lat' => $lat,
                                    'lon' => $lon
		                      ) + $this->_emptyState);
		$this->assertSame($lat,$point->getLat());
		$this->assertSame($lon,$point->getLon());
	}
	public function testGetStateVars() {
		$point = clone $this->_point;
		$this->assertEquals(array('lat','lon'),$point->getStateVars());
	}
}
