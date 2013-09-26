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

    protected function setUp()
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

        $this->sessionChain = new SessionStorageHandlerChain(
            array(
                $mockReader1,
                $mockReader2,
            ),
            array(
                $mockWriter1,
                $mockWriter2,
            )
        );
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
}
