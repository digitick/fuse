<?php


namespace Digitick\Foundation\Tests\Fuse\CircuitBreaker;


use Digitick\Foundation\Fuse\CircuitBreaker\ApcCircuitBreaker;

class ApcCircuitBreakerTest extends \PHPUnit_Framework_TestCase
{
    public function testSingleIsAvailable()
    {
        $instance = new ApcCircuitBreaker(3, 10);

        $this->assertTrue($instance->isAvailable("test"));
    }

    public function testBrokenCircuit()
    {
        $retryTimeout = 3;
        $key = 'test';

        $instance = new ApcCircuitBreaker(3, $retryTimeout);

        $this->assertTrue($instance->isAvailable($key));

        $instance->reportFailure($key);
        $this->assertTrue($instance->isAvailable($key));
        $instance->reportFailure($key);
        $this->assertTrue($instance->isAvailable($key));
        $instance->reportFailure($key);
        $this->assertFalse($instance->isAvailable($key));

        sleep(1);
        $this->assertFalse($instance->isAvailable($key));

        sleep($retryTimeout);
        $this->assertTrue($instance->isAvailable($key));
        $this->assertFalse($instance->isAvailable($key));
        $this->assertFalse($instance->isAvailable($key));

        $instance->reportSuccess($key);
        $this->assertTrue($instance->isAvailable($key));
        $instance->reportSuccess($key);
        $this->assertTrue($instance->isAvailable($key));
        $instance->reportFailure($key);
        $this->assertTrue($instance->isAvailable($key));
    }

    public function testSetServiceSettings()
    {
        $retryTimeout = 3;
        $maxFail = 3;
        $key1 = 'test1';
        $key2 = 'test2';
        $key3 = 'test3';

        $instance = new ApcCircuitBreaker($maxFail, $retryTimeout);

        $instance->setServiceSettings($key2, 5, 10);
        $instance->setServiceSettings($key3, 1, 2);

        // Key 1
        $this->assertTrue($instance->isAvailable($key1));
        $instance->reportSuccess($key1);
        $this->assertTrue($instance->isAvailable($key1));
        $instance->reportFailure($key1);
        $this->assertTrue($instance->isAvailable($key1));
        $instance->reportFailure($key1);
        $this->assertTrue($instance->isAvailable($key1));
        $instance->reportFailure($key1);
        $this->assertFalse($instance->isAvailable($key1));

        // Key 2
        $this->assertTrue($instance->isAvailable($key2));
        $instance->reportFailure($key2);
        $instance->reportFailure($key2);
        $instance->reportFailure($key2);
        $instance->reportFailure($key2);
        $instance->reportFailure($key2);
        $this->assertFalse($instance->isAvailable($key2));

        // Key 3
        $this->assertTrue($instance->isAvailable($key3));
        $instance->reportFailure($key3);
        $this->assertFalse($instance->isAvailable($key3));
    }
}