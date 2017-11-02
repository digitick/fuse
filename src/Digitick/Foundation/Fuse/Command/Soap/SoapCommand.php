<?php


namespace Digitick\Foundation\Fuse\Command\Soap;


use Digitick\Foundation\Fuse\Command\AbstractCommand;
use SoapClient;
use SoapFault;

/**
 * Class SoapCommand
 * @package Digitick\Foundation\Fuse\Command\Soap
 */
class SoapCommand extends AbstractCommand
{
    /**
     *
     */
    const STATUS_SUCCESS = 200;

    /** @var  SoapClient */
    protected $soapClient = null;
    /**
     * @var
     */
    protected $headers;
    /**
     * @var array
     */
    protected $query = [];
    /**
     * @var
     */
    protected $methodName;
    /**
     * @var
     */
    protected $statusCode;
    /**
     * @var
     */
    protected $content;
    /**
     * @var
     */
    protected $responseHeaders;
    /**
     * @var
     */
    protected $soapResponse;

    /**
     * SoapCommand constructor.
     * @param string $key
     */
    public function __construct($key)
    {
        parent::__construct($key);
    }

    /**
     * @param \SoapHeader $header
     * @return $this
     */
    public function addHeader(\SoapHeader $header)
    {
        $this->headers[] = $header;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * @return mixed
     */
    public function getSoapResponse()
    {
        return $this->soapResponse;
    }

    /**
     * @return mixed
     * @throws SoapFault
     */
    public function send()
    {
        if ($this->getSoapClient() === null) {
            throw new \RuntimeException();
        }

        if ($this->headers != null)
            $this->soapClient->__setSoapHeaders($this->getHeaders());
        if ($this->methodName == null)
            throw new \RuntimeException();

        try {
            $this->debug("Send request");
            $response = $this->soapClient->__soapCall($this->getMethodName(), $this->getQuery());
            $this->info($this->soapClient->__getLastResponse());
        } catch (SoapFault $fault) {
            $this->error(sprintf("Transfer exception caught. Type : %s, status code = %s, message = %s",
                    get_class($fault),
                    $fault->getCode(),
                    $fault->getMessage()
                )
            );
            throw $fault;
        }

        $this->statusCode = self::STATUS_SUCCESS;
        $this->debug("Returned status code = " . $this->statusCode);
        $this->content = $response;
        $this->soapResponse = $this->soapClient->__getLastResponse();
        $this->responseHeaders = $this->soapClient->__getLastResponseHeaders();

        return $this->content;
    }

    /**
     * @return SoapClient
     */
    public function getSoapClient()
    {
        return $this->soapClient;
    }

    /**
     * @param SoapClient $soapClient
     * @return $this
     */
    public function setSoapClient(SoapClient $soapClient)
    {
        $this->soapClient = $soapClient;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMethodName()
    {
        return $this->methodName;
    }

    /**
     * @param $methodName
     * @return $this
     */
    public function setMethodName($methodName)
    {
        $this->methodName = $methodName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param mixed $query
     * @return SoapCommand
     */
    public function setQuery(array $query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return null
     */
    public function getCacheKey()
    {
        return null;
    }
}