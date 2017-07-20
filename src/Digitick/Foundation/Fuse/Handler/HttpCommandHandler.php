<?php


namespace Digitick\Foundation\Fuse\Handler;


use Digitick\Foundation\Fuse\CircuitBreaker\CircuitBreakerInterface;
use Digitick\Foundation\Fuse\Command\AbstractCommand;
use Digitick\Foundation\Fuse\Command\Http\HttpCommand;
use Digitick\Foundation\Fuse\Exception\LogicException;
use Digitick\Foundation\Fuse\Exception\ServiceException;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\SimpleCache\CacheInterface;

/**
 * Class HttpCommandHandler
 * @package Digitick\Foundation\Fuse\Handler
 */
class HttpCommandHandler extends CommandHandler
{
    /** @var  Client */
    protected $httpClient;

    /**
     * HttpCommandHandler constructor.
     * @param Client $httpClient
     * @param CircuitBreakerInterface $circuitBreaker
     * @param CacheInterface|null $cacheManager
     * @param LoggerInterface|null $logger
     */
    public function __construct(Client $httpClient, CircuitBreakerInterface $circuitBreaker, CacheInterface $cacheManager = null, LoggerInterface $logger = null)
    {
        parent::__construct($circuitBreaker, $cacheManager, $logger);
        $this->httpClient = $httpClient;
    }

    /**
     * @param AbstractCommand $command
     * @return mixed|null
     */
    public function execute(AbstractCommand $command)
    {
        if (!$command instanceof HttpCommand) {
            $this->log(LogLevel::CRITICAL, "Not an http command " . $command->getKey());
            throw new \InvalidArgumentException();
        }
        return parent::execute($command);
    }

    /**
     * @param array $commandList
     * @return array|mixed
     */
    public function executeAsync(array $commandList)
    {
        /** @var []Promise $promises */
        $promises = [];
        foreach ($commandList as $command) {
            if (!$command instanceof HttpCommand) {
                $this->log(LogLevel::CRITICAL, "Not an http command " . $command->getKey());
                throw new \InvalidArgumentException();
            }

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
                $promises[] = $this->prepareCommandAsync($command);

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
        }
        \GuzzleHttp\Promise\unwrap($promises);
        return $promises;
    }

    /**
     * @param AbstractCommand $command
     * @return mixed
     */
    protected function executeCommand(AbstractCommand $command)
    {
        $command->setHttpClient($this->httpClient);
        return parent::executeCommand($command);
    }

    /**
     * @param HttpCommand $command
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    protected function prepareCommandAsync(HttpCommand $command)
    {
        $command->setHttpClient($this->httpClient);
        return $command->buildPromise();
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