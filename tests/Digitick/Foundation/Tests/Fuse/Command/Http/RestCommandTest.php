<?php


namespace Digitick\Foundation\Tests\Fuse\Command\Http;

use Digitick\Foundation\Fuse\Command\Http\HttpCommand;
use Digitick\Foundation\Fuse\Command\Http\RestCommand;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History as History;
use GuzzleHttp\Subscriber\Mock;

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
    private function getHttpClient ($code, $body = '', &$history = null) {
        $httpClient = new Client();
        $httpMock = new Mock([
            new Response(
                $code,
                ["x-response" => "test-u"],
                Stream::factory($body)
            )
        ]);
        $history = new History();
        $httpClient->getEmitter()->attach($httpMock);
        $httpClient->getEmitter()->attach($history);
        return $httpClient;
    }

    public function testSimpleRetrieve () {
        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, 'tonton', $history);
        $route = '/tonton';

        $command = new RestCommand("rest", RestCommand::OPERATION_RETRIEVE);
        $command->setHttpClient($httpClient);
        $command->setRoute($route);

        $return = $command->run();
        $request = $history->getLastRequest();

        $this->assertEquals("tonton", $return);
    }

    public function testRouting () {
        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, 'tonton', $history);
        $route = '/tonton/{param1}/tata/{param2}';

        $command = new RestCommand("rest", RestCommand::OPERATION_RETRIEVE);
        $command->setHttpClient($httpClient);
        $command->setRoute($route);
        $command->bindArgument("param1", "val1");
        $command->bindArgument("param2", "val2");

        $return = $command->run();
        $request = $history->getLastRequest();

        $this->assertEquals("/tonton/val1/tata/val2", $request->getPath());
    }

    public function testRoutingWithQuery () {
        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, 'tonton', $history);
        $route = '/tonton/{param1}/tata/{param2}';

        $command = new RestCommand("rest", RestCommand::OPERATION_RETRIEVE);
        $command->setHttpClient($httpClient);
        $command->setRoute($route);
        $command->bindArgument("param1", "val1");
        $command->bindArgument("param2", "val2");
        $command->bindArgument("param3", "val3");
        $command->bindArgument("param4", "val4");

        $return = $command->run();
        $request = $history->getLastRequest();
        $this->assertEquals("val3", $request->getQuery()->get('param3'));
        $this->assertEquals("val4", $request->getQuery()->get('param4'));
        $this->assertEquals("/tonton/val1/tata/val2", $request->getPath());
    }

    public function testHttpMethodForRetrieve () {
        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, 'tonton', $history);
        $route = '/tonton';

        $command = new RestCommand("rest", RestCommand::OPERATION_RETRIEVE);
        $command->setHttpClient($httpClient);
        $command->setRoute($route);

        $return = $command->run();
        $request = $history->getLastRequest();
        $this->assertEquals(HttpCommand::HTTP_METHOD_GET, $request->getMethod());
    }

    public function testHttpMethodForCreate () {
        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, 'tonton', $history);
        $route = '/tonton';

        $command = new RestCommand("rest", RestCommand::OPERATION_CREATE);
        $command->setHttpClient($httpClient);
        $command->setRoute($route);

        $return = $command->run();
        $request = $history->getLastRequest();
        $this->assertEquals(HttpCommand::HTTP_METHOD_POST, $request->getMethod());
    }

    public function testHttpMethodForUpdate () {
        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, 'tonton', $history);
        $route = '/tonton';

        $command = new RestCommand("rest", RestCommand::OPERATION_UPDATE);
        $command->setHttpClient($httpClient);
        $command->setRoute($route);

        $return = $command->run();
        $request = $history->getLastRequest();
        $this->assertEquals(HttpCommand::HTTP_METHOD_PUT, $request->getMethod());
    }

    public function testHttpMethodForDelete () {
        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, 'tonton', $history);
        $route = '/tonton';

        $command = new RestCommand("rest", RestCommand::OPERATION_DELETE);
        $command->setHttpClient($httpClient);
        $command->setRoute($route);

        $return = $command->run();
        $request = $history->getLastRequest();
        $this->assertEquals(HttpCommand::HTTP_METHOD_DELETE, $request->getMethod());
    }

    public function testStringRequestContent () {
        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, 'tonton', $history);
        $route = '/tonton';
        $data = "Body data for request";

        $command = new RestCommand("rest", RestCommand::OPERATION_CREATE);
        $command
            ->setHttpClient($httpClient)
            ->setRoute($route)
            ->setRequestContent($data)
        ;

        $return = $command->run();
        $request = $history->getLastRequest();

        $this->assertEquals($data, $request->getBody());

    }

    public function testObjectRequestContent () {
        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, 'tonton', $history);
        $route = '/tonton';

        $serializedString = 'data serialized';
        $dataObject = $this->prophesize('Serializable');
        $dataObject->serialize ()->willReturn ($serializedString);


        $command = new RestCommand("rest", RestCommand::OPERATION_CREATE);
        $command
            ->setHttpClient($httpClient)
            ->setRoute($route)
            ->setRequestContent($dataObject->reveal())
        ;

        $return = $command->run();
        $request = $history->getLastRequest();

        $this->assertEquals($serializedString, $request->getBody());

    }

    /**
     * @expectedException Digitick\Foundation\Fuse\Command\Http\Exception\NonSerializableException
     */
    public function testObjectRequestContentNonSerializable () {
        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, 'tonton', $history);
        $route = '/tonton';

        $serializedString = 'data serialized';
        $dataObject = $this->prophesize('stdClass');

        $command = new RestCommand("rest", RestCommand::OPERATION_CREATE);
        $command
            ->setHttpClient($httpClient)
            ->setRoute($route)
            ->setRequestContent($dataObject->reveal())
        ;

        $return = $command->run();
    }

    public function testGetUnserialize () {
        /** @var History $history */
        $history = null;
        $dataFromServer = 'data from server';
        $httpClient = $this->getHttpClient(200, $dataFromServer, $history);
        $route = '/tonton';

        $dataObject = new Dummy();


        $command = new RestCommand("rest", RestCommand::OPERATION_CREATE);
        $command
            ->setHttpClient($httpClient)
            ->setRoute($route)
            ->setReturnClass('Digitick\Foundation\Tests\Fuse\Command\Http\Dummy')
        ;

        $return = $command->run();
        $request = $history->getLastRequest();

        $this->assertInstanceOf('Digitick\Foundation\Tests\Fuse\Command\Http\Dummy', $return);
        $this->assertEquals($dataFromServer, $return->unserializeData);
    }
}