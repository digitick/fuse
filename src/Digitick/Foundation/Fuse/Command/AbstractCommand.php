<?php


namespace Digitick\Foundation\Fuse\Command;


use Digitick\Foundation\Fuse\Exception\LogicException;
use Digitick\Foundation\Fuse\Exception\ServiceException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

abstract class AbstractCommand
{
    /** @var  string */
    protected $key;

    /** @var LoggerInterface */
    protected $logger = null;

    /**
     * AbstractCommand constructor.
     * @param string $key
     * @param LoggerInterface $logger
     */
    public function __construct($key, LoggerInterface $logger = null)
    {
        $this->key = $key;
        $this->setLogger($logger);
    }

    /**
     * @return null
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param null $logger
     * @return AbstractCommand
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }


    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }


    abstract public function run ();

    /**
     * @return mixed
     */
    public function onServiceUnavailable () {
        $this->debug("Default handler for callback onServiceUnavailable");
        return null;
    }

    /**
     * @param LogicException $exception
     * @return mixed
     * @throws LogicException
     */
    public function onLogicError (LogicException $exception) {
        $this->debug("Default handler for callback onLogicError");
        throw $exception;
    }

    /**
     * @param ServiceException $exception
     * @return mixed
     * @throws ServiceException
     */
    public function onServiceError (ServiceException $exception) {
        $this->debug("Default handler for callback onServiceError");
        throw $exception;
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
        $this->logger->log ($level, sprintf("[%s][%s] %s", get_class($this), $this->key, $message));
    }
}
