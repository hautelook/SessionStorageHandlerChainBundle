<?php

namespace Hautelook\SessionStorageChainBundle\Tests\Session\Storage\Handler;

use Hautelook\SessionStorageChainBundle\Session\Storage\Handler\SessionStorageHandlerChain;
use Symfony\Component\DependencyInjection\Container;

use SessionHandlerInterface;

/**
 * @author Baldur Rensch <baldur.rensch@gmail.com>
 */
class SessionStorageHandlerChainTest extends \PHPUnit_Framework_TestCase
{
	private $sessionChain;

	public function setUp()
	{
		$mockContainer = $this->getMockContainer();

		$this->sessionChain = new SessionStorageHandlerChain($mockContainer);
	}

	public function testRead()
	{
		$this->assertEquals('dummySessionData', $this->sessionChain->read('abc'));
	}

	public function testWrite()
	{
		$this->assertTrue($this->sessionChain->write('abc', 'dummySessionData'));
	}

	public function testDestroy()
	{
		$this->assertTrue($this->sessionChain->destroy('abc'));
	}

	public function testGc()
	{
		$this->assertTrue($this->sessionChain->gc(1000));
	}

	/**
	 * Creates a mock container with 2 reader, and 2 writer services defined.
	 * - Only the first reader is expected to be called on a read
	 * - Both writers are expected to be called on a write
	 *
	 * @return Container
	 */
	private function getMockContainer()
	{
		$mockReader1 = $this->getMockBuilder('SessionHandlerInterface')
			->disableOriginalConstructor()
			->getMock();

		$mockReader1->expects($this->any())->method('read')
			->with($this->equalTo('abc'))
			->will($this->returnValue('dummySessionData'));

		$mockReader1->expects($this->never())->method('write');
		$mockReader1->expects($this->never())->method('destroy');
		$mockReader1->expects($this->never())->method('gc');

		$mockReader2 = $this->getMockBuilder('SessionHandlerInterface')
			->disableOriginalConstructor()
			->getMock();

		$mockReader2->expects($this->never())->method('read');
		$mockReader2->expects($this->never())->method('write');
		$mockReader2->expects($this->never())->method('destroy');
		$mockReader2->expects($this->never())->method('gc');

		$mockWriter1 = $this->getMockBuilder('SessionHandlerInterface')
			->disableOriginalConstructor()
			->getMock();

		$mockWriter1->expects($this->any())->method('write')
			->with(
				$this->equalTo('abc'),
				$this->equalTo('dummySessionData')
			)
			->will($this->returnValue(true));

		$mockWriter1->expects($this->any())->method('destroy')
			->with(
				$this->equalTo('abc')
			)
			->will($this->returnValue(true));

		$mockWriter1->expects($this->any())->method('gc')
			->with(
				$this->equalTo(1000)
			)
			->will($this->returnValue(true));

		$mockWriter2 = $this->getMockBuilder('SessionHandlerInterface')
			->disableOriginalConstructor()
			->getMock();

		$mockWriter2->expects($this->any())->method('write')
			->with(
				$this->equalTo('abc'),
				$this->equalTo('dummySessionData')
			)
			->will($this->returnValue(true));

		$mockWriter2->expects($this->any())->method('destroy')
			->with(
				$this->equalTo('abc')
			)
			->will($this->returnValue(true));

		$mockWriter2->expects($this->any())->method('gc')
			->with(
				$this->equalTo(1000)
			)
			->will($this->returnValue(true));

		$services = array(
			'mockReader1' => $mockReader1,
			'mockWriter1' => $mockWriter1,
			'mockReader2' => $mockReader2,
			'mockWriter2' => $mockWriter2,
		);

		$mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();

		$mockContainer->expects($this->any())->method('has')
			->will($this->returnCallback(
				function ($serviceName) use ($services) {
					return !empty($services[$serviceName]);
				}
			));

		$mockContainer->expects($this->any())->method('getParameter')
			->will($this->returnValue(
				array(
					'reader' => array('mockReader1', 'mockReader2'),
					'writer' => array('mockWriter1', 'mockWriter2'),
				)
			)
		);

		$mockContainer->expects($this->any())->method('get')
			->will($this->returnCallback(
				function ($serviceName) use ($services) {
					return $services[$serviceName];
				}
			));

		return $mockContainer;
	}
}
