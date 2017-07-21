<?php

namespace Digitick\Foundation\Fuse\Command\Http;


use GuzzleHttp\Client;

/**
 * Interface HttpCommandInterface
 * @package Digitick\Foundation\Fuse\Command\Http
 */
interface HttpCommandInterface
{
    /**
     * @return mixed
     */
    public function prepare();

    /**
     * @return mixed
     */
    public function send();

    /**
     * @return mixed
     */
    public function promise();

    /**
     * @return mixed
     */
    public function sendAsync();

    /**
     * @return mixed
     */
    public function getContent();

    /**
     * @param Client $client
     * @return mixed
     */
    public function setHttpClient(Client $client);

}