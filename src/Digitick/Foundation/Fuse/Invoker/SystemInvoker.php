<?php


namespace Digitick\Foundation\Fuse\Invoker;

use Digitick\Foundation\Fuse\Command\SystemCommand;
use Digitick\Foundation\Fuse\Exception\{
    LogicException, ServiceException
};
use Psr\Log\LogLevel;

class SystemInvoker extends InvokerAbstract
{
    public function execute(SystemCommand $command)
    {
        $result = null;
        $cacheKey = null;
        $isCacheable = $this->isCacheable($command);

        if ($command->getLogger() == null) {
            $command->setLogger($this->logger);
        }

        $this->info("Execute command " . get_class($command));

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
            $result = $command->run();

            $this->log(LogLevel::INFO, "Success for command group " . $command->getKey());
            $this->circuitBreaker->reportSuccess($command->getKey());
            if ($isCacheable) {
                $this->log(LogLevel::DEBUG, "Store result in cache. Key = $cacheKey");
                $this->cacheManager->set($cacheKey, $result, $command->getTtl());
            }
        } catch (LogicException $exc) {
            $this->log(LogLevel::DEBUG, "Logic exception. Call onLogicError");
            $this->log(LogLevel::INFO, "Success for command group " . $command->getKey());
            $this->circuitBreaker->reportSuccess($command->getKey());
            return $command->onLogicError($exc);
        } catch (ServiceException $exc) {
            $this->log(LogLevel::DEBUG, "Service exception. Call onServiceError");
            $this->log(LogLevel::ERROR, "Failure for command group " . $command->getKey());
            $this->circuitBreaker->reportFailure($command->getKey());
            return $command->onServiceError($exc);
        }

        return $result;
    }


}