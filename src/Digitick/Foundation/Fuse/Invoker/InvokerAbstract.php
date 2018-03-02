<?php

namespace Digitick\Foundation\Fuse\Invoker;


use Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface;
use Digitick\Foundation\Fuse\Command\{
    AbstractCommand, CacheableCommand
};
use Psr\Log\{
    LoggerInterface, LogLevel
};
use Psr\SimpleCache\CacheInterface;

abstract class InvokerAbstract
{
    /** @var  CircuitBreakerInterface */
    protected $circuitBreaker;
    /** @var  CacheInterface */
    protected $cacheManager;
    /** @var  LoggerInterface */
    protected $logger;

    /**
     * HandlerAbstract constructor.
     * @param CircuitBreakerInterface $circuitBreaker
     * @param CacheInterface $cacheManager
     * @param LoggerInterface $logger
     */
    public function __construct(CircuitBreakerInterface $circuitBreaker, CacheInterface $cacheManager = null, LoggerInterface $logger = null)
    {
        $this->circuitBreaker = $circuitBreaker;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
    }

    protected function info($message)
    {
        $this->log(LogLevel::INFO, $message);
    }

    protected function log($level = LoggerInterface::INFO, $message)
    {
        if ($this->logger == null)
            return;
        $this->logger->log($level, $message);
    }

    protected function getCommandCacheKey(AbstractCommand $command)
    {
        if (!$this->isCacheable($command)) {
            throw new \RuntimeException("Getting cache from non cacheable command");
        }
        return sprintf("%s:%s", $command->getKey(), $command->getCacheKey());
    }

    protected function isCacheable(AbstractCommand $command)
    {
        return $command instanceof CacheableCommand;
    }

    protected function debug($message)
    {
        $this->log(LogLevel::DEBUG, $message);
    }

    protected function critical($message)
    {
        $this->log(LogLevel::CRITICAL, $message);
    }

    protected function error($message)
    {
        $this->log(LogLevel::ERROR, $message);
    }

    protected function warning($message)
    {
        $this->log(LogLevel::WARNING, $message);
    }

    protected function notice($message)
    {
        $this->log(LogLevel::NOTICE, $message);
    }
}