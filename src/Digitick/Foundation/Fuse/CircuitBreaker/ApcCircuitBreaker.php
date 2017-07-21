<?php


namespace Digitick\Foundation\Fuse\CircuitBreaker;


use Ejsmont\CircuitBreaker\Factory;

class ApcCircuitBreaker implements CircuitBreakerInterface
{
    const MAX_FAILURES_DEFAULT = 20;
    const MAX_RETRY_TIMEOUT_DEFAULT = 5;

    /**
     * @var \Ejsmont\CircuitBreaker\CircuitBreakerInterface
     */
    private $circuitBreaker = null;

    private $maxFailures;
    private $retryTimeout;

    /**
     * CircuitBreaker constructor using APC as storage.
     * @param int $maxFailures How many times do we allow service to fail before considering it unavailable
     * @param int $retryTimeout How many seconds should we wait before attempting retry
     */
    public function __construct($maxFailures = self::MAX_FAILURES_DEFAULT,
                                $retryTimeout = self::MAX_RETRY_TIMEOUT_DEFAULT)
    {
        $this->maxFailures = $maxFailures;
        $this->retryTimeout = $retryTimeout;

        $factory = new Factory();
        $this->circuitBreaker = $factory->getSingleApcInstance($this->maxFailures, $this->retryTimeout);
    }

    /**
     * @inheritdoc
     */
    public function isAvailable($key)
    {
        return $this->circuitBreaker->isAvailable($key);
    }

    /**
     * @inheritdoc
     */
    public function reportSuccess($key)
    {
        $this->circuitBreaker->reportSuccess($key);
    }

    /**
     * @inheritdoc
     */
    public function reportFailure($key)
    {
        $this->circuitBreaker->reportFailure($key);
    }

    public function setServiceSettings($key, $maxFailures, $retryTimeout)
    {
        return $this->circuitBreaker->setServiceSettings($key, $maxFailures, $retryTimeout);
    }
}