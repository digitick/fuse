<?php


namespace Digitick\Foundation\Tests\Fuse\Command;


use Digitick\Foundation\Fuse\Exception\LogicException;
use Digitick\Foundation\Fuse\Exception\ServiceException;

class AbstractCommandTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = '\Digitick\Foundation\Fuse\Command\AbstractCommand';

    public function testGetKey()
    {
        $instance = $this->getMockForAbstractClass(self::CLASS_NAME, ['test']);
        $this->assertEquals('test', $instance->getKey());
    }

    public function testOnServiceUnavailable()
    {
        $instance = $this->getMockForAbstractClass(self::CLASS_NAME, ['test']);

        $this->assertNull($instance->onServiceUnavailable());
    }

    /**
     * @expectedException \Digitick\Foundation\Fuse\Exception\LogicException
     */
    public function testOnLogicError()
    {
        $instance = $this->getMockForAbstractClass(self::CLASS_NAME, ['test']);

        $instance->onLogicError(new LogicException());
    }

    /**
     * @expectedException \Digitick\Foundation\Fuse\Exception\ServiceException
     */
    public function testOnServiceError()
    {
        $instance = $this->getMockForAbstractClass(self::CLASS_NAME, ['test']);

        $instance->onServiceError(new ServiceException());
    }

}