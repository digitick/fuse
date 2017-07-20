<?php


namespace Digitick\Foundation\Fuse\Command\Http;


use Digitick\Foundation\Fuse\Command\Http\Exception\NonSerializableException;

class RestCommand extends HttpCommand
{
    const OPERATION_CREATE = 'create';
    const OPERATION_RETRIEVE = 'retrieve';
    const OPERATION_UPDATE = 'update';
    const OPERATION_DELETE = 'delete';

    protected $route = '/';
    protected $arguments;

    protected $operation;

    protected $requestContent = null;

    /** @var string */
    protected $returnClass = null;
    /** @var bool */
    protected $implementSerializable = false;
    /** @var  \ReflectionClass */
    protected $reflection;

    public function __construct($key, $operation = self::OPERATION_RETRIEVE, $returnClass = null)
    {

        parent::__construct($key);

        $this->route = '/';
        $this->arguments = [];

        $this->setReturnClass($returnClass);
        $this->setOperation($operation);
    }

    /**
     * @param $operation
     * @return $this
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
        return $this;
    }

    /**
     * @return string
     */
    public function getReturnClass()
    {
        return $this->returnClass;
    }

    /**
     * @param string $returnClass
     * @return RestCommand
     */
    public function setReturnClass($returnClass)
    {
        $this->returnClass = $returnClass;
        $this->parseReturnClass();
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param mixed $route
     * @return $this
     */
    public function setRoute($route)
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @param string $argumentName
     * @param string|int $argumentValue
     * @return $this
     */
    public function bindArgument($argumentName, $argumentValue)
    {
        $this->arguments [$argumentName] = $argumentValue;
        return $this;
    }

    public function run()
    {
        $this->setPath($this->buildRoute());

        $this->defineHttpMethodFromOperation();

        $this->defineRequestContent();

        $serialized = parent::run();

        if ($this->implementSerializable) {
            $instance = $this->reflection->newInstance();
            $instance->unserialize($serialized);
            return $instance;
        }

        return $serialized;

    }

    protected function buildRoute()
    {
        $route = $this->route;
        $queryString = [];

        foreach ($this->arguments as $argName => $argValue) {
            $routeArg = '{' . $argName . '}';
            if (strstr($route, $routeArg)) {
                $route = str_replace($routeArg, $argValue, $route);
            } else {
                $queryString [$argName] = $argValue;
            }
        }
        if (!empty($queryString)) {
            $qs = http_build_query($queryString);
            $this->setQuery($qs);
        }

        $this->info("Request with route : " . $route);
        return $route;
    }

    private function defineHttpMethodFromOperation()
    {
        switch ($this->operation) {
            case self::OPERATION_CREATE :
                $this->setMethod(static::HTTP_METHOD_POST);
                break;
            case self::OPERATION_RETRIEVE :
                $this->setMethod(static::HTTP_METHOD_GET);
                break;
            case self::OPERATION_UPDATE :
                $this->setMethod(static::HTTP_METHOD_PUT);
                break;
            case self::OPERATION_DELETE :
                $this->setMethod(static::HTTP_METHOD_DELETE);
                break;
        }
    }

    private function defineRequestContent()
    {
        $bodyData = $this->getRequestContent();
        if ($bodyData !== null) {
            if ($bodyData instanceof \Serializable) { // Si la classe peut etre serialisé, alors on demande le contenu sérialisé
                $this->setBody($bodyData->serialize());
            } else if (is_string($bodyData)) { // Sinon si c'est un string, on le prend tel quel
                $this->setBody($bodyData);
            } else { // Sinon on renvoie une exception car le contenu ne peut etre envoyé
                $this->error("Object is not serializable nor a string");
                throw new NonSerializableException("Object is non serializable");
            }
        }
    }

    /**
     * @return mixed
     */
    public function getRequestContent()
    {
        return $this->requestContent;
    }

    /**
     * @param mixed $requestContent
     * @return RestCommand
     */
    public function setRequestContent($requestContent)
    {
        $this->requestContent = $requestContent;
        return $this;
    }

    private function parseReturnClass()
    {
        if ($this->returnClass == null) {
            $this->implementSerializable = false;
            return;
        }

        $this->reflection = new \ReflectionClass($this->returnClass);
        $this->implementSerializable = $this->reflection->implementsInterface('\Serializable');
    }

}