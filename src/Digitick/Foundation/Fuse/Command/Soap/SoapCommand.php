<?php


namespace Digitick\Foundation\Fuse\Command\Soap;


use Digitick\Foundation\Fuse\Command\AbstractCommand;
use SoapClient;
use SoapFault;

class SoapCommand extends AbstractCommand
{
    const STATUS_SUCCESS = 200;

    /** @var  SoapClient */
    protected $soapClient = null;
    protected $headers;
    protected $query = [];
    protected $methodName;
    protected $statusCode;
    protected $content;
    protected $responseHeaders;
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

    public function run()
    {
        if ($this->getSoapClient() === null) {
            throw new \RuntimeException();
        }

        if ($this->headers != null)
            $this->soapClient->__setSoapHeaders($this->getHeaders());
        if ($this->methodName == null)
            throw new \RuntimeException();

        try {
            $this->debug("Send request" . $this->getMethodName());
            $response = $this->soapClient->__soapCall($this->getMethodName(), $this->getQuery());
            $this->logSoapRequestAndResponse();
        } catch (SoapFault $fault) {
            $this->logSoapRequestAndResponse();
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
     * logs SOAP request and SOAP Response
     */
    private function logSoapRequestAndResponse()
    {
        $this->debug("Request:");
        $this->debug($this->soapClient->__getLastRequest());
        $this->debug("Response:");
        $this->debug($this->soapClient->__getLastResponse());
    }
}