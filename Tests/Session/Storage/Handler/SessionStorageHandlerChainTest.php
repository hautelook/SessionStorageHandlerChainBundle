<?php

namespace Hautelook\SessionStorageChainBundle\Tests\Session\Storage\Handler;

use Hautelook\SessionStorageChainBundle\Session\Storage\Handler\SessionStorageHandlerChain;

/**
 * @author Baldur Rensch <baldur.rensch@gmail.com>
 */
class SessionStorageHandlerChainTest extends \PHPUnit_Framework_TestCase
{
	public function testConfiguration()
	{
		$mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();

		$mockContainer->expects($this->any())->method('has')
			->will($this->returnValue(true));

		$mockContainer->expects($this->any())->method('getParameter')
			->will($this->returnValue(
				array(
					'reader' => array(),
					'writer' => array()
				)
			)
		);

		$mockContainer->expects($this->any())->method('get')
			->will($this->returnValue(true));

		$sessionChain = new SessionStorageHandlerChain($mockContainer);
	}
}
