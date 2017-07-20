<?php


namespace Digitick\Foundation\Tests\Fuse\Command\Http;


use Digitick\Foundation\Fuse\Command\Http\HttpCommand;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class HttpCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider exceptionProvider
     */
    public function testException($code, $exceptionClass)
    {
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = "http";

        $httpClient = $this->getHttpClient($code);

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme);

        $command->setHttpClient($httpClient);

        $this->setExpectedException(interface_exists('Throwable') ? 'Throwable' : $exceptionClass);

        $result = $command->run();
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

    public function exceptionProvider()
    {
        return [
            [400, '\Digitick\Foundation\Fuse\Command\Http\Exception\BadRequestException'],
            [401, '\Digitick\Foundation\Fuse\Command\Http\Exception\ClientException'],
            [403, '\Digitick\Foundation\Fuse\Command\Http\Exception\ForbiddenException'],
            [404, '\Digitick\Foundation\Fuse\Command\Http\Exception\NotFoundException'],
            [405, '\Digitick\Foundation\Fuse\Command\Http\Exception\MethodNotAllowedException'],

            [500, '\Digitick\Foundation\Fuse\Command\Http\Exception\InternalErrorException'],
            [501, '\Digitick\Foundation\Fuse\Command\Http\Exception\NotImplementedException'],
            [503, '\Digitick\Foundation\Fuse\Command\Http\Exception\TemporaryUnavailableException'],
            [502, '\Digitick\Foundation\Fuse\Command\Http\Exception\ServerException'],
        ];
    }

    /*
     * Tests à faire :
     *
     */

    public function testCall200()
    {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = "http";
        $mockClient = null;

        /** @var Middleware $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, $validResult, $history, $mockClient);
        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme);

        $command->setHttpClient($httpClient);

        $result = $command->run();
        $request = $mockClient->getLastRequest();

        $this->assertEquals($host, $request->getUri()->getHost());
        $this->assertEquals($port, $request->getUri()->getPort());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals($path, $request->getUri()->getPath());
        $this->assertEquals($scheme, $request->getUri()->getScheme());

        $this->assertEquals($validResult, $result);
        $this->assertEquals($validResult, $command->getContent());
        $this->assertEquals(200, $command->getStatusCode());
    }

    /*
     * Tests à faire :
     *
     */

    public function testCall200Async()
    {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = "http";

        /** @var Promise $promise */
        $promises = [];
        for($i=0; $i<5; $i++) {
            $mockClient = null;

            /** @var Middleware $history */
            $history = null;
            $httpClient = $this->getHttpClient(200, $validResult, $history, $mockClient);
            $command = new HttpCommand("test");
            $command
                ->setHost($host)
                ->setPort($port)
                ->setPath($path)
                ->setScheme($scheme);

            $command->setHttpClient($httpClient);
            $promises[] = $command->buildPromise();
        }
        \GuzzleHttp\Promise\unwrap($promises);

        for($i=0; $i<5; $i++) {
            $this->assertEquals(PromiseInterface::FULFILLED, $promises[$i]->getState());
        }
    }

    public function methodProvider()
    {
        return [
            [HttpCommand::HTTP_METHOD_GET],
            [HttpCommand::HTTP_METHOD_PUT],
            [HttpCommand::HTTP_METHOD_POST],
            [HttpCommand::HTTP_METHOD_DELETE],
        ];
    }

    /**
     * @dataProvider methodProvider
     */
    public function testMethod($methodExpected)
    {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = HttpCommand::HTTP_SCHEME_HTTP;
        $method = $methodExpected;
        $mockClient = null;

        /** @var Middleware $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, $validResult, $history, $mockClient);

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme)
            ->setMethod($method);

        $command->setHttpClient($httpClient);

        $result = $command->run();

        $request = $mockClient->getLastRequest();

        $this->assertEquals($methodExpected, $request->getMethod());
        $this->assertEquals($methodExpected, $command->getMethod());

        $this->assertEquals($validResult, $result);
        $this->assertEquals($validResult, $command->getContent());
        $this->assertEquals(200, $command->getStatusCode());
    }

    public function testQuery()
    {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = HttpCommand::HTTP_SCHEME_HTTP;
        $method = HttpCommand::HTTP_METHOD_GET;
        $query = [
            'a' => 1,
            'b' => 2,
        ];
        $mockClient = null;

        /** @var Middleware $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, $validResult, $history, $mockClient);

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme)
            ->setMethod($method)
            ->setQuery($query);;

        $command->setHttpClient($httpClient);

        $result = $command->run();

        /** @var Request $request */
        $request = $mockClient->getLastRequest();
        $this->assertEquals($query, $command->getQuery());
        parse_str($request->getUri()->getQuery(), $lastRequestQuery);
        $this->assertEquals($query, $lastRequestQuery);

        $this->assertEquals($validResult, $result);
        $this->assertEquals($validResult, $command->getContent());
        $this->assertEquals(200, $command->getStatusCode());
    }

    public function testHeaders()
    {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = HttpCommand::HTTP_SCHEME_HTTP;
        $method = HttpCommand::HTTP_METHOD_GET;
        $headers = [
            "tonton" => 'tata',
            "x-abc" => "def"
        ];
        $mockClient = null;

        /** @var Middleware $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, $validResult, $history, $mockClient);

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme)
            ->setMethod($method)
            ->setHeaders($headers);;

        $command->setHttpClient($httpClient);

        $result = $command->run();

        $request = $mockClient->getLastRequest();
        $this->assertEquals("tata", $request->getHeader("tonton")[0]);
        $this->assertEquals("def", $request->getHeader("x-abc")[0]);
        $this->assertEquals(["x-response" => ["test-u"]], $command->getResponseHeaders());


        $this->assertEquals($validResult, $result);
        $this->assertEquals($validResult, $command->getContent());
        $this->assertEquals(200, $command->getStatusCode());
    }

    public function testCallBodyWithPost()
    {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = "http";
        $method = HttpCommand::HTTP_METHOD_POST;
        $data = "Data in body request";
        $mockClient = null;

        /** @var Middleware $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, $validResult, $history, $mockClient);

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme)
            ->setMethod($method)
            ->setBody($data);

        $command->setHttpClient($httpClient);

        $result = $command->run();

        $request = $mockClient->getLastRequest();

        $this->assertEquals($data, $request->getBody());
    }

    public function testCallBodyWithoutPost()
    {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = "http";
        $method = HttpCommand::HTTP_METHOD_GET;
        $data = "Data in body request";
        $mockClient = null;

        /** @var Middleware $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, $validResult, $history, $mockClient);

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme)
            ->setMethod($method)
            ->setBody($data);

        $command->setHttpClient($httpClient);

        $result = $command->run();

        $request = $mockClient->getLastRequest();

        $this->assertEquals($data, $request->getBody());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCallWithoutHttpClient()
    {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = "http";

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme);

        $result = $command->run();
    }
}