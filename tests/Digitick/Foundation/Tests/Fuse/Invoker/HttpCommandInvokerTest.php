<?php


namespace Digitick\Foundation\Tests\Fuse\Handler;


use Digitick\Foundation\Fuse\Handler\HttpCommandInvoker;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Prophecy\Argument;

class HttpCommandInvokerTest extends \PHPUnit_Framework_TestCase
{
    const CACHE_MANAGER_INTERFACE = '\Psr\SimpleCache\CacheInterface';

    public function testExecute()
    {
        $guzzle = $this->prophesize('\GuzzleHttp\Client');

        $circuitBreaker = $this->prophesize('\Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface');
        $circuitBreaker->isAvailable('test')->willReturn(true);
        $circuitBreaker->reportSuccess('test')->shouldBeCalled();
        $circuitBreaker->reportFailure(Argument::any())->shouldNotBeCalled();

        $cacheManager = $this->prophesize(self::CACHE_MANAGER_INTERFACE);

        $command = $this->prophesize('\Digitick\Foundation\Fuse\Command\Http\HttpCommand');
        $command->setHttpClient(Argument::type('\GuzzleHttp\Client'))->shouldBeCalled();
        $command->getMethod()->shouldBeCalled();
        $command->getKey()->willReturn('test');
        $command->run()->willReturn('result from command');
        $command->getLogger()->shouldBeCalled();
        $command->setLogger(Argument::any())->shouldBeCalled();

        $handler = new HttpCommandInvoker(
            $guzzle->reveal(),
            $circuitBreaker->reveal(),
            $cacheManager->reveal()
        );

        $result = $handler->execute(
            $command->reveal()
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExecuteNonHttpCommand()
    {
        $guzzle = $this->prophesize('\GuzzleHttp\Client');

        $circuitBreaker = $this->prophesize('\Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface');


        $cacheManager = $this->prophesize(self::CACHE_MANAGER_INTERFACE);

        $command = $this->prophesize('\Digitick\Foundation\Fuse\Command\AbstractCommand');


        $handler = new HttpCommandInvoker(
            $guzzle->reveal(),
            $circuitBreaker->reveal(),
            $cacheManager->reveal()
        );

        $result = $handler->execute(
            $command->reveal()
        );
    }

    public function testExecuteAsync()
    {
        $guzzle = $this->prophesize('\GuzzleHttp\Client');

        $circuitBreaker = $this->prophesize('\Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface');
        $circuitBreaker->isAvailable('test')->willReturn(true);
        $circuitBreaker->reportSuccess('test')->shouldBeCalled();
        $circuitBreaker->reportFailure(Argument::any())->shouldNotBeCalled();

        $cacheManager = $this->prophesize(self::CACHE_MANAGER_INTERFACE);

        /** @var []HttpCommand $commands */
        $commands = [];
        $mockPromise = new Promise(function () use (&$mockPromise) {
            $mockPromise->resolve('10');
        });
        for ($i = 0; $i < 5; $i++) {
            $command = $this->prophesize('\Digitick\Foundation\Fuse\Command\Http\HttpCommand');
            $command->setHttpClient(Argument::type('\GuzzleHttp\Client'))->shouldBeCalled();
            $command->getMethod()->shouldBeCalled();
            $command->getKey()->willReturn('test');
            $command->buildPromise()->willReturn($mockPromise);
            $command->getLogger()->shouldBeCalled();
            $command->setLogger(Argument::any())->shouldBeCalled();
            $commands[] = $command->reveal();
        }
        $handler = new HttpCommandInvoker(
            $guzzle->reveal(),
            $circuitBreaker->reveal(),
            $cacheManager->reveal()
        );

        $results = $handler->executeAsync(
            $commands
        );

        foreach ($results as $result) {
            $this->assertEquals(PromiseInterface::FULFILLED, $result->getState());
        }
    }
}