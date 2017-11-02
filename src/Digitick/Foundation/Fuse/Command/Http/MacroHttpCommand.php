<?php


namespace Digitick\Foundation\Fuse\Command\Http;


use Digitick\Foundation\Fuse\Command\AbstractCommand;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Stream\Stream;

/**
 * Class MacroHttpCommand
 * @package Digitick\Foundation\Fuse\Command\Http
 */
class MacroHttpCommand extends AbstractCommand implements HttpCommandInterface
{
    /** @var []HttpCommandInterface $commands */
    private $commands = null;
    /**
     * @var int
     */
    private $concurrency = 4;

    /**
     * @param $value
     * @return $this
     */
    public function setConcurrency($value)
    {
        $this->concurrency = $value;

        return $this;
    }


    /**
     * @param HttpCommandInterface $command
     * @return $this
     */
    public function addCommand(HttpCommandInterface $command)
    {
        $this->commands[] = $command;

        return $this;
    }


    /**
     *
     */
    public function prepare()
    {
        if ($this->commands === null) {
            throw new \RuntimeException();
        }

        /** @var HttpCommandInterface $command */
        foreach ($this->commands as $command) {
            $command->prepare();
        }
    }

    /**
     * @return array
     */
    public function send()
    {
        if ($this->commands === null) {
            throw new \RuntimeException();
        }

        /** @var HttpCommandInterface $command */
        foreach ($this->commands as $command) {
            yield $command->send();
        }
    }

    /**
     * @return array
     */
    public function sendAsync()
    {
        if ($this->commands === null) {
            throw new \RuntimeException();
        }
        $promises = $this->promise();
        (new EachPromise($promises, [
            'concurrency' => $this->concurrency
        ]))->promise()->wait();
        /** @var HttpCommandInterface $command */
        foreach ($this->commands as $command) {
            yield $command->getContent();
        }
    }

    /**
     * @return \GuzzleHttp\Promise\Promise|\GuzzleHttp\Promise\PromiseInterface
     */
    public function promise()
    {
        if ($this->commands === null) {
            throw new \RuntimeException();
        }

        /** @var HttpCommandInterface $command */
        foreach ($this->commands as $command) {
            yield $command->promise();
        }
    }

    /**
     *
     */
    public function getContent()
    {
        if ($this->commands === null) {
            throw new \RuntimeException();
        }

        /** @var HttpCommandInterface $command */
        foreach ($this->commands as $command) {
            yield $command->getContent();
        }
    }

    /**
     * @param Client $client
     */
    public function setHttpClient(Client $client)
    {
        if ($this->commands === null) {
            throw new \RuntimeException();
        }

        /** @var HttpCommandInterface $command */
        foreach ($this->commands as $command) {
            $command->setHttpClient($client);
        }
    }

    /**
     * @return null
     */
    public function getCacheKey()
    {
        return null;
    }
}