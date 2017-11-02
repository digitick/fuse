<?php


namespace Digitick\Foundation\Tests\Fuse\Command\Http;

use Digitick\Foundation\Fuse\Command\Http\HttpCommand;
use Digitick\Foundation\Fuse\Command\Http\RestCommand;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;


class Dummy implements \Serializable
{
    public $serializeReturn;
    public $unserializeData;

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return $this->serializeReturn;
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $this->unserializeData = $serialized;
    }

}

class RestCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleRetrieve()
    {
        /** @var Middleware $history */
        $history = null;
        $httpMock = null;
        $host = "www.test.com";
        $httpClient = $this->getHttpClient(200, 'tonton', $history, $httpMock);
        $route = '/tonton';

        $command = new RestCommand("rest", RestCommand::OPERATION_RETRIEVE);
        $command->setHttpClient($httpClient);
        $command->setHost($host);
        $command->setRoute($route);

        $return = $command->run();
        $request = $httpMock->getLastRequest();

        $this->assertEquals("tonton", $return);
    }

    private function getHttpClient($code, $body = '', &$history = null, &$httpMock = null)
    {
        $httpMock = new MockHandler([
            new Response(
                $code,
                ["x-response" => "test-u"],
                $body
            )
        ]);
        $historyContainer = [];
        $history = Middleware::history($historyContainer);
        // Create a handler stack that has all of the default middlewares attached
        $handler = HandlerStack::create();
        $handler->setHandler($history);
        $handler->setHandler($httpMock);

        $httpClient = new Client(['handler' => $handler]);
        return $httpClient;
    }

    public function testRouting()
    {
        /** @var Middleware $history */
        $history = null;
        $httpMock = null;
        $host = "www.test.com";
        $httpClient = $this->getHttpClient(200, 'tonton', $history, $httpMock);
        $route = '/tonton/{param1}/tata/{param2}';
        $command = new RestCommand("rest", RestCommand::OPERATION_RETRIEVE);
        $command->setHttpClient($httpClient);
        $command->setHost($host);
        $command->setRoute($route);
        $command->bindArgument("param1", "val1");
        $command->bindArgument("param2", "val2");

        $return = $command->run();
        $request = $httpMock->getLastRequest();

        $this->assertEquals("/tonton/val1/tata/val2", $request->getUri()->getPath());
    }

    public function testRoutingWithQuery()
    {
        /** @var Middleware $history */
        $history = null;
        $httpMock = null;
        $host = "www.test.com";
        $httpClient = $this->getHttpClient(200, 'tonton', $history, $httpMock);
        $route = '/tonton/{param1}/tata/{param2}';

        $command = new RestCommand("rest", RestCommand::OPERATION_RETRIEVE);
        $command->setHttpClient($httpClient);
        $command->setHost($host);
        $command->setRoute($route);
        $command->bindArgument("param1", "val1");
        $command->bindArgument("param2", "val2");
        $command->bindArgument("param3", "val3");
        $command->bindArgument("param4", "val4");

        $return = $command->run();
        $request = $httpMock->getLastRequest();
        parse_str($request->getUri()->getQuery(), $lastRequestQuery);
        $this->assertEquals("val3", $lastRequestQuery['param3']);
        $this->assertEquals("val4", $lastRequestQuery['param4']);
        $this->assertEquals("/tonton/val1/tata/val2", $request->getUri()->getPath());
    }

    public function testHttpMethodForRetrieve()
    {
        /** @var Middleware $history */
        $history = null;
        $httpMock = null;
        $host = "www.test.com";
        $httpClient = $this->getHttpClient(200, 'tonton', $history, $httpMock);
        $route = '/tonton';

        $command = new RestCommand("rest", RestCommand::OPERATION_RETRIEVE);
        $command->setHttpClient($httpClient);
        $command->setHost($host);
        $command->setRoute($route);

        $return = $command->run();
        $request = $httpMock->getLastRequest();
        $this->assertEquals(HttpCommand::HTTP_METHOD_GET, $request->getMethod());
    }

    public function testHttpMethodForCreate()
    {
        /** @var Middleware $history */
        $history = null;
        $httpMock = null;
        $host = "www.test.com";
        $httpClient = $this->getHttpClient(200, 'tonton', $history, $httpMock);
        $route = '/tonton';

        $command = new RestCommand("rest", RestCommand::OPERATION_CREATE);
        $command->setHttpClient($httpClient);
        $command->setHost($host);
        $command->setRoute($route);

        $return = $command->run();
        $request = $httpMock->getLastRequest();
        $this->assertEquals(HttpCommand::HTTP_METHOD_POST, $request->getMethod());
    }

    public function testHttpMethodForUpdate()
    {
        /** @var Middleware $history */
        $history = null;
        $httpMock = null;
        $host = "www.test.com";
        $httpClient = $this->getHttpClient(200, 'tonton', $history, $httpMock);
        $route = '/tonton';

        $command = new RestCommand("rest", RestCommand::OPERATION_UPDATE);
        $command->setHttpClient($httpClient);
        $command->setHost($host);
        $command->setRoute($route);

        $return = $command->run();
        $request = $httpMock->getLastRequest();
        $this->assertEquals(HttpCommand::HTTP_METHOD_PUT, $request->getMethod());
    }

    public function testHttpMethodForDelete()
    {
        /** @var Middleware $history */
        $history = null;
        $httpMock = null;
        $host = "www.test.com";
        $httpClient = $this->getHttpClient(200, 'tonton', $history, $httpMock);
        $route = '/tonton';

        $command = new RestCommand("rest", RestCommand::OPERATION_DELETE);
        $command->setHttpClient($httpClient);
        $command->setHost($host);
        $command->setRoute($route);

        $return = $command->run();
        $request = $httpMock->getLastRequest();
        $this->assertEquals(HttpCommand::HTTP_METHOD_DELETE, $request->getMethod());
    }

    public function testStringRequestContent()
    {
        /** @var Middleware $history */
        $history = null;
        $httpMock = null;
        $host = "www.test.com";
        $httpClient = $this->getHttpClient(200, 'tonton', $history, $httpMock);
        $route = '/tonton';
        $data = "Body data for request";

        $command = new RestCommand("rest", RestCommand::OPERATION_CREATE);
        $command
            ->setHttpClient($httpClient)
            ->setHost($host)
            ->setRoute($route)
            ->setRequestContent($data);

        $return = $command->run();
        $request = $httpMock->getLastRequest();

        $this->assertEquals($data, $request->getBody());

    }

    public function testObjectRequestContent()
    {
        /** @var Middleware $history */
        $history = null;
        $httpMock = null;
        $host = "www.test.com";
        $httpClient = $this->getHttpClient(200, 'tonton', $history, $httpMock);
        $route = '/tonton';

        $serializedString = 'data serialized';
        $dataObject = $this->prophesize('Serializable');
        $dataObject->serialize()->willReturn($serializedString);


        $command = new RestCommand("rest", RestCommand::OPERATION_CREATE);
        $command
            ->setHttpClient($httpClient)
            ->setHost($host)
            ->setRoute($route)
            ->setRequestContent($dataObject->reveal());

        $return = $command->run();
        $request = $httpMock->getLastRequest();

        $this->assertEquals($serializedString, $request->getBody());

    }

    /**
     * @expectedException \Digitick\Foundation\Fuse\Command\Http\Exception\NonSerializableException
     */
    public function testObjectRequestContentNonSerializable()
    {
        /** @var Middleware $history */
        $history = null;
        $httpMock = null;
        $host = "www.test.com";
        $httpClient = $this->getHttpClient(200, 'tonton', $history, $httpMock);
        $route = '/tonton';

        $serializedString = 'data serialized';
        $dataObject = $this->prophesize('stdClass');

        $command = new RestCommand("rest", RestCommand::OPERATION_CREATE);
        $command
            ->setHttpClient($httpClient)
            ->setHost($host)
            ->setRoute($route)
            ->setRequestContent($dataObject->reveal());

        $return = $command->run();
    }

    public function testGetUnserialize()
    {
        /** @var Middleware $history */
        $history = null;
        $httpMock = null;
        $host = "www.test.com";
        $dataFromServer = 'data from server';
        $httpClient = $this->getHttpClient(200, $dataFromServer, $history, $httpMock);
        $route = '/tonton';

        $dataObject = new Dummy();


        $command = new RestCommand("rest", RestCommand::OPERATION_CREATE);
        $command
            ->setHttpClient($httpClient)
            ->setHost($host)
            ->setRoute($route)
            ->setReturnClass('Digitick\Foundation\Tests\Fuse\Command\Http\Dummy');

        $return = $command->run();
        $request = $httpMock->getLastRequest();

        $this->assertInstanceOf('Digitick\Foundation\Tests\Fuse\Command\Http\Dummy', $return);
        $this->assertEquals($dataFromServer, $return->unserializeData);
    }
}