<?php


namespace Digitick\Foundation\Tests\Fuse\Handler;


use Digitick\Foundation\Fuse\Handler\CommandHandler;
use Prophecy\Argument;

class CommandHandlerTest extends \PHPUnit_Framework_TestCase
{
    const CACHE_MANAGER_INTERFACE = 'Psr\SimpleCache\CacheInterface';
    const COMMAND_KEY = 'test';
    const CACHE_KEY = 'cache:key';
    const REAL_CACHE_KEY = 'test:cache:key';

    public function testExecuteNoCache () {
        $circuitBreaker = $this->prophesize('Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface');
        $circuitBreaker->isAvailable (self::COMMAND_KEY)->willReturn (true);
        $circuitBreaker->reportSuccess(self::COMMAND_KEY)->shouldBeCalled();
        $circuitBreaker->reportFailure(Argument::any())->shouldNotBeCalled();

        $cacheManager = $this->prophesize(self::CACHE_MANAGER_INTERFACE);

        $command = $this->prophesize('Digitick\Foundation\Fuse\Command\AbstractCommand');
        $command->getLogger()->shouldBeCalled();
        $command->setLogger(Argument::any())->shouldBeCalled();
        $command->getKey()->willReturn (self::COMMAND_KEY);
        $command->run()->willReturn ('result from command');


        $handler = new CommandHandler(
            $circuitBreaker->reveal(),
            $cacheManager->reveal()
        );

        $result = $handler->execute(
            $command->reveal()
        );

        $this->assertEquals('result from command', $result);
    }

    public function testExecuteWithCacheInCache () {
        $key = self::COMMAND_KEY;

        $circuitBreaker = $this->prophesize('Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface');
        $circuitBreaker->isAvailable (self::COMMAND_KEY)->shouldNotBeCalled ();
        $circuitBreaker->reportSuccess(self::COMMAND_KEY)->shouldNotBeCalled();
        $circuitBreaker->reportFailure(Argument::any())->shouldNotBeCalled();

        $cacheManager = $this->prophesize(self::CACHE_MANAGER_INTERFACE);
        $cacheManager->has (self::REAL_CACHE_KEY)->willReturn (true);
        $cacheManager->get(self::REAL_CACHE_KEY)->willReturn ('result from command in cache');
        $cacheManager->set (Argument::any(), Argument::cetera())->shouldNotBeCalled();

        $command = $this->prophesize('Digitick\Foundation\Fuse\Command\AbstractCommand');
        $command->willImplement('Digitick\Foundation\Fuse\Command\CacheableCommand');
        $command->getKey()->willReturn (self::COMMAND_KEY);
        $command->run()->shouldNotBeCalled();
        $command->getCacheKey()->willReturn (self::CACHE_KEY);
        $command->getTtl()->willReturn (10);
        $command->getLogger()->shouldBeCalled();
        $command->setLogger(Argument::any())->shouldBeCalled();

        $handler = new CommandHandler(
            $circuitBreaker->reveal(),
            $cacheManager->reveal()
        );

        $result = $handler->execute(
            $command->reveal()
        );

        $this->assertEquals('result from command in cache', $result);
    }

    public function testExecuteWithCacheNotInCache () {
        $key = self::COMMAND_KEY;

        $circuitBreaker = $this->prophesize('Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface');
        $circuitBreaker->isAvailable (self::COMMAND_KEY)->willReturn (true);
        $circuitBreaker->reportSuccess(self::COMMAND_KEY)->shouldBeCalled();
        $circuitBreaker->reportFailure(Argument::any())->shouldNotBeCalled();

        $cacheManager = $this->prophesize(self::CACHE_MANAGER_INTERFACE);
        $cacheManager->has (self::REAL_CACHE_KEY)->willReturn (false);
        $cacheManager->get(self::REAL_CACHE_KEY)->shouldNotBeCalled();
        $cacheManager->set (self::REAL_CACHE_KEY, 'result from command', 10)->shouldBeCalled();

        $command = $this->prophesize('Digitick\Foundation\Fuse\Command\AbstractCommand');
        $command->willImplement('Digitick\Foundation\Fuse\Command\CacheableCommand');
        $command->getKey()->willReturn (self::COMMAND_KEY);
        $command->run()->willReturn ('result from command');
        $command->getCacheKey()->willReturn ('cache:key');
        $command->getTtl()->willReturn (10);
        $command->getLogger()->shouldBeCalled();
        $command->setLogger(Argument::any())->shouldBeCalled();

        $handler = new CommandHandler(
            $circuitBreaker->reveal(),
            $cacheManager->reveal()
        );

        $result = $handler->execute(
            $command->reveal()
        );

        $this->assertEquals('result from command', $result);
    }

    public function testExecuteCircuitBroken () {
        $key = self::COMMAND_KEY;

        $circuitBreaker = $this->prophesize('Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface');
        $circuitBreaker->isAvailable (self::COMMAND_KEY)->willReturn (false);
        $circuitBreaker->reportSuccess(Argument::any())->shouldNotBeCalled();
        $circuitBreaker->reportFailure(Argument::any())->shouldNotBeCalled();

        $cacheManager = $this->prophesize(self::CACHE_MANAGER_INTERFACE);
        $cacheManager->has (Argument::any())->shouldNotBeCalled ();
        $cacheManager->get(Argument::any())->shouldNotBeCalled();
        $cacheManager->set (Argument::any(), Argument::cetera())->shouldNotBeCalled();

        $command = $this->prophesize('Digitick\Foundation\Fuse\Command\AbstractCommand');
        $command->getKey()->willReturn (self::COMMAND_KEY);
        $command->run()->shouldNotBeCalled();
        $command->onServiceUnavailable()->shouldBeCalled();
        $command->onLogicError(Argument::any())->shouldNotBeCalled();
        $command->onServiceError(Argument::any())->shouldNotBeCalled();
        $command->getLogger()->shouldBeCalled();
        $command->setLogger(Argument::any())->shouldBeCalled();

        $handler = new CommandHandler(
            $circuitBreaker->reveal(),
            $cacheManager->reveal()
        );

        $result = $handler->execute(
            $command->reveal()
        );

        $this->assertNull($result);
    }

    public function testExecuteLogicException () {
        $key = self::COMMAND_KEY;

        $circuitBreaker = $this->prophesize('Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface');
        $circuitBreaker->isAvailable (self::COMMAND_KEY)->willReturn (true);
        $circuitBreaker->reportSuccess(self::COMMAND_KEY)->shouldBeCalled();
        $circuitBreaker->reportFailure(Argument::any())->shouldNotBeCalled();

        $cacheManager = $this->prophesize(self::CACHE_MANAGER_INTERFACE);

        $command = $this->prophesize('Digitick\Foundation\Fuse\Command\AbstractCommand');
        $command->getKey()->willReturn (self::COMMAND_KEY);
        $command->run()->willThrow ('Digitick\Foundation\Fuse\Exception\LogicException');
        $command->onLogicError(Argument::any())->shouldBeCalled();
        $command->onLogicError(Argument::any())->willReturn ("a logic exception");
        $command->getLogger()->shouldBeCalled();
        $command->setLogger(Argument::any())->shouldBeCalled();

        $handler = new CommandHandler(
            $circuitBreaker->reveal(),
            $cacheManager->reveal()
        );

        $result = $handler->execute(
            $command->reveal()
        );

        $this->assertEquals('a logic exception', $result);
    }

    public function testExecuteServiceException () {
        $key = self::COMMAND_KEY;

        $circuitBreaker = $this->prophesize('Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface');
        $circuitBreaker->isAvailable (self::COMMAND_KEY)->willReturn (true);
        $circuitBreaker->reportSuccess(self::COMMAND_KEY)->shouldNotBeCalled();
        $circuitBreaker->reportFailure(self::COMMAND_KEY)->shouldBeCalled();

        $cacheManager = $this->prophesize(self::CACHE_MANAGER_INTERFACE);

        $command = $this->prophesize('Digitick\Foundation\Fuse\Command\AbstractCommand');
        $command->getKey()->willReturn (self::COMMAND_KEY);
        $command->run()->willThrow ('Digitick\Foundation\Fuse\Exception\ServiceException');
        $command->onServiceError(Argument::any())->shouldBeCalled();
        $command->onServiceError(Argument::any())->willReturn ("a service exception");
        $command->getLogger()->shouldBeCalled();
        $command->setLogger(Argument::any())->shouldBeCalled();

        $handler = new CommandHandler(
            $circuitBreaker->reveal(),
            $cacheManager->reveal()
        );

        $result = $handler->execute(
            $command->reveal()
        );

        $this->assertEquals('a service exception', $result);
    }
}