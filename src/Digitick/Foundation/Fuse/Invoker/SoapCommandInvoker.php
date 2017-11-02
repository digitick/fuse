<?php


namespace Digitick\Foundation\Fuse\Handler;

use Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface;
use Digitick\Foundation\Fuse\Command\AbstractCommand;
use Digitick\Foundation\Fuse\Command\Soap\SoapCommand;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\SimpleCache\CacheInterface;
use SoapClient;

class SoapCommandInvoker extends InvokerAbstract
{
    /** @var  SoapClient */
    protected $soapClient;

    public function __construct
    (
        SoapClient $soapClient,
        CircuitBreakerInterface $circuitBreaker,
        CacheInterface $cacheManager = null,
        LoggerInterface $logger = null
    )
    {
        parent::__construct($circuitBreaker, $cacheManager, $logger);
        $this->soapClient = $soapClient;
    }

    public function execute(AbstractCommand $command)
    {
        if (!$command instanceof SoapCommand) {
            $this->log(LogLevel::CRITICAL, "Not a soap command " . $command->getKey());
            throw new \InvalidArgumentException();
        }
        $command->setSoapClient($this->soapClient);
        return $command->send();
    }

    /**
     * @param AbstractCommand $command
     * @return bool
     */
    protected function isCacheable(AbstractCommand $command)
    {
        return false;
    }

}