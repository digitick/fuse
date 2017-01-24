<?php


namespace Digitick\Foundation\Tests\Fuse\Command\Http;


use Digitick\Foundation\Fuse\Command\Http\HttpCommand;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;

class HttpCommandTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @dataProvider exceptionProvider
     */
    public function testException ($code, $exceptionClass) {
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
            ->setScheme($scheme)
        ;

        $command->setHttpClient($httpClient);

        $this->setExpectedException($exceptionClass);

        $result = $command->run();
    }

    public function exceptionProvider () {
        return [
            [400, 'Digitick\Foundation\Fuse\Command\Http\Exception\BadRequestException'],
            [401, 'Digitick\Foundation\Fuse\Command\Http\Exception\ClientException'],
            [403, 'Digitick\Foundation\Fuse\Command\Http\Exception\ForbiddenException'],
            [404, 'Digitick\Foundation\Fuse\Command\Http\Exception\NotFoundException'],
            [405, 'Digitick\Foundation\Fuse\Command\Http\Exception\MethodNotAllowedException'],

            [500, 'Digitick\Foundation\Fuse\Command\Http\Exception\InternalErrorException'],
            [501, 'Digitick\Foundation\Fuse\Command\Http\Exception\NotImplementedException'],
            [503, 'Digitick\Foundation\Fuse\Command\Http\Exception\TemporaryUnavailableException'],
            [502, 'Digitick\Foundation\Fuse\Command\Http\Exception\ServerException'],
        ];
    }

    /*
     * Tests à faire :
     *
     */

    public function testCall200 () {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = "http";

        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, $validResult, $history);

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme)
            ;

        $command->setHttpClient($httpClient);

        $result = $command->run();

        $request = $history->getLastRequest();

        $this->assertEquals($host, $request->getHost());
        $this->assertEquals($port, $request->getPort());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals($path, $request->getPath());
        $this->assertEquals($scheme, $request->getScheme());

        $this->assertEquals($validResult, $result);
        $this->assertEquals($validResult, $command->getContent());
        $this->assertEquals(200, $command->getStatusCode());
    }

    public function methodProvider () {
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
    public function testMethod ($methodExpected) {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = HttpCommand::HTTP_SCHEME_HTTP;
        $method = $methodExpected;

        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, $validResult, $history);

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme)
            ->setMethod($method)
            ;

        $command->setHttpClient($httpClient);

        $result = $command->run();

        $request = $history->getLastRequest();

        $this->assertEquals($methodExpected, $request->getMethod());
        $this->assertEquals($methodExpected, $command->getMethod());

        $this->assertEquals($validResult, $result);
        $this->assertEquals($validResult, $command->getContent());
        $this->assertEquals(200, $command->getStatusCode());
    }

    public function testQuery () {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = HttpCommand::HTTP_SCHEME_HTTP;
        $method = HttpCommand::HTTP_METHOD_GET;
        $query = http_build_query([
            'a' => 1,
            'b' => 2,
        ]);

        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, $validResult, $history);

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme)
            ->setMethod($method)
            ->setQuery($query);
        ;

        $command->setHttpClient($httpClient);

        $result = $command->run();

        $request = $history->getLastRequest();

        $this->assertEquals($query, $command->getQuery());
        $this->assertEquals($query, $request->getQuery());

        $this->assertEquals($validResult, $result);
        $this->assertEquals($validResult, $command->getContent());
        $this->assertEquals(200, $command->getStatusCode());
    }

    public function testHeaders () {
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

        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, $validResult, $history);

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme)
            ->setMethod($method)
            ->setHeaders($headers);
        ;

        $command->setHttpClient($httpClient);

        $result = $command->run();

        $request = $history->getLastRequest();
        $this->assertEquals("tata", $request->getHeader("tonton"));
        $this->assertEquals("def", $request->getHeader("x-abc"));
        $this->assertEquals(["x-response" => ["test-u"]], $command->getResponseHeaders());


        $this->assertEquals($validResult, $result);
        $this->assertEquals($validResult, $command->getContent());
        $this->assertEquals(200, $command->getStatusCode());
    }

    public function testCallBodyWithPost () {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = "http";
        $method = HttpCommand::HTTP_METHOD_POST;
        $data = "Data in body request";

        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, $validResult, $history);

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme)
            ->setMethod($method)
            ->setBody($data)
        ;

        $command->setHttpClient($httpClient);

        $result = $command->run();

        $request = $history->getLastRequest();

        $this->assertEquals($data, $request->getBody());
    }

    public function testCallBodyWithoutPost () {
        $validResult = "Response from server";
        $host = "www.test.com";
        $port = "8080";
        $path = "/unit/test";
        $scheme = "http";
        $method = HttpCommand::HTTP_METHOD_GET;
        $data = "Data in body request";

        /** @var History $history */
        $history = null;
        $httpClient = $this->getHttpClient(200, $validResult, $history);

        $command = new HttpCommand("test");
        $command
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setScheme($scheme)
            ->setMethod($method)
            ->setBody($data)
        ;

        $command->setHttpClient($httpClient);

        $result = $command->run();

        $request = $history->getLastRequest();

        $this->assertEquals($data, $request->getBody());
    }
    /**
     * @expectedException \RuntimeException
     */
    public function testCallWithoutHttpClient ()
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