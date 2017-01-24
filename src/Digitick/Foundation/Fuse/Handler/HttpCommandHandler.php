<?php


namespace Digitick\Foundation\Fuse\Handler;


use Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface;
use Digitick\Foundation\Fuse\Command\AbstractCommand;
use Digitick\Foundation\Fuse\Command\Http\HttpCommand;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\SimpleCache\CacheInterface;

class HttpCommandHandler extends CommandHandler
{
    /** @var  Client */
    protected $httpClient;

    public function __construct(Client $httpClient, CircuitBreakerInterface $circuitBreaker, CacheInterface $cacheManager = null, LoggerInterface $logger = null)
    {
        parent::__construct($circuitBreaker, $cacheManager, $logger);
        $this->httpClient = $httpClient;
    }

    public function execute(AbstractCommand $command)
    {
        if (!$command instanceof HttpCommand) {
            $this->log(LogLevel::CRITICAL, "Not an http command " . $command->getKey());
            throw new \InvalidArgumentException();
        }
        return parent::execute($command);
    }

    protected function executeCommand(AbstractCommand $command)
    {
        $command->setHttpClient ($this->httpClient);
        return parent::executeCommand($command);
    }

    /**
     * @param AbstractCommand|HttpCommand $command
     * @return bool
     */
    protected function isCacheable(AbstractCommand $command)
    {
        $isCacheable = ($command->getMethod() == HttpCommand::HTTP_METHOD_GET && parent::isCacheable($command));
        return $isCacheable;
    }

}