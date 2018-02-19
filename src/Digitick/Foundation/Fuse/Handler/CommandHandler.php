<?php


namespace Digitick\Foundation\Fuse\Handler;


use Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface;
use Digitick\Foundation\Fuse\Command\AbstractCommand;
use Digitick\Foundation\Fuse\Command\CacheableCommand;
use Digitick\Foundation\Fuse\Exception\LogicException;
use Digitick\Foundation\Fuse\Exception\ServiceException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\SimpleCache\CacheInterface;

class CommandHandler
{
    /** @var  CircuitBreakerInterface */
    private $circuitBreaker;
    /** @var  CacheInterface */
    private $cacheManager;
    /** @var  LoggerInterface */
    private $logger;

    /**
     * CommandHandler constructor.
     * @param CircuitBreakerInterface $circuitBreaker
     * @param CacheInterface $cacheManager
     */
    public function __construct(CircuitBreakerInterface $circuitBreaker, CacheInterface $cacheManager = null, LoggerInterface $logger = null)
    {
        $this->circuitBreaker = $circuitBreaker;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
    }

    public function execute (AbstractCommand $command) {
        $result = null;
        $cacheKey = null;
        $isCacheable = $this->isCacheable($command);

        if ($command->getLogger() == null) {
            $command->setLogger($this->logger);
        }

        $this->debug("Execute command " . get_class($command));

        if ($isCacheable) {
            $cacheKey = $this->getCommandCacheKey($command);

            if ($this->cacheManager->has($cacheKey)) {
                $this->log(LogLevel::DEBUG, "Return from cache. Key = $cacheKey");
                return $this->cacheManager->get($cacheKey);
            }
            $this->log(LogLevel::DEBUG, "Key $cacheKey not found in cache");
        }

        if (!$this->circuitBreaker->isAvailable($command->getKey())) {
            $this->log(LogLevel::WARNING, "Circuit broken for command group " . $command->getKey());
            return $command->onServiceUnavailable();
        }

        $this->log(LogLevel::DEBUG, "Circuit is open for command group " . $command->getKey());

        try {
            $result = $this->executeCommand($command);

            $this->log(LogLevel::INFO, "Success for command group " . $command->getKey());
            $this->circuitBreaker->reportSuccess($command->getKey());
            if ($isCacheable) {
                $this->log(LogLevel::DEBUG, "Store result in cache. Key = $cacheKey");
                $this->cacheManager->set($cacheKey, $result, $command->getTtl());
            }
        } catch (LogicException $exc) {
            $this->log(LogLevel::DEBUG, "Logic exception. Call onLogicError");
            $this->log(LogLevel::INFO, "Success for command group " . $command->getKey());
            $this->circuitBreaker->reportSuccess($commaqnd->getKey());
            return $command->onLogicError($exc);
        } catch (ServiceException $exc) {
            $this->log(LogLevel::DEBUG, "Service exception. Call onServiceError");
            $this->log(LogLevel::ERROR, "Failure for command group " . $command->getKey());
            $this->circuitBreaker->reportFailure($command->getKey());
            return $command->onServiceError($exc);
        }

        return $result;
    }

    protected function isCacheable (AbstractCommand $command) {
        return $command instanceof CacheableCommand;
    }

    protected function executeCommand (AbstractCommand $command) {
        $this->debug("Running command " . get_class($command));
        return $command->run();
    }

    protected function getCommandCacheKey (AbstractCommand $command) {
        if (!$this->isCacheable($command)) {
            throw new \RuntimeException("Getting cache from non cacheable command");
        }
        return sprintf("%s:%s", $command->getKey(), $command->getCacheKey());
    }

    protected function critical ($message) {
        $this->log(LogLevel::CRITICAL, $message);
    }

    protected function error ($message) {
        $this->log(LogLevel::ERROR, $message);
    }

    protected function warning ($message) {
        $this->log(LogLevel::WARNING, $message);
    }

    protected function notice ($message) {
        $this->log(LogLevel::NOTICE, $message);
    }

    protected function info ($message) {
        $this->log(LogLevel::INFO, $message);
    }

    protected function debug ($message) {
        $this->log(LogLevel::DEBUG, $message);
    }

    protected function log ($level = LogLevel::INFO, $message) {
        if ($this->logger == null)
            return;
        $this->logger->log ($level, $message);
    }

}