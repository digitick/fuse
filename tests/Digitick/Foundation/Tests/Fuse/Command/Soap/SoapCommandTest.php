<?php


namespace Digitick\Foundation\Tests\Fuse\Command\Http;


use Digitick\Foundation\Fuse\Command\Soap\SoapCommand;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;

class SoapCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     *
     */
    public function testExceptionRuntime()
    {

        $soapClient = $this->getSoapClient();

        $command = new SoapCommand("test");

        $command->setSoapClient($soapClient);

        $this->expectException('\RuntimeException');

        $command->send();
    }

    private function getSoapClient()
    {
        $soapClientMock = $this->getMockFromWsdl('http://www.webservicex.com/globalweather.asmx?WSDL', '', '', ['GetWeather']);
        return $soapClientMock;
    }

    /**
     * @dataProvider methodProvider
     */
    public function testRun200($methodName)
    {

        $soapClient = $this->getSoapClient();

        $command = new SoapCommand("test");

        $getWeatherHttpPostIn = new \stdClass();
        $getWeatherHttpPostIn->CityName = "Foo";
        $getWeatherHttpPostIn->CountryName = 'Bar';

        $getWeatherHttpPostOut = new \stdClass();
        $getWeatherHttpPostOut->GetWeatherResult = 'Data Not Found';

        $command->setSoapClient($soapClient)
            ->setMethodName($methodName)
            ->setQuery([$getWeatherHttpPostIn]);

        $result = $command->send();
        $this->assertEquals($getWeatherHttpPostOut, $result);
    }

    /**
     * @dataProvider methodProvider
     */
    public function testMethod($methodName)
    {
        $command = new SoapCommand("test");
        $command
            ->setMethodName($methodName);

        $this->assertEquals($methodName, $command->getMethodName());
    }

    public function testHeaders()
    {
        $command = new SoapCommand("test");
        $soapHeaderParams = array(
            'login' => 'testLogin',
            'password' => 'testPwd');
        $headerObject = new \SoapHeader('foo', 'bar', $soapHeaderParams);
        $command->addHeader(
            $headerObject
        );

        $this->assertEquals($headerObject, $command->getHeaders()[0]);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCallWithoutSoapClient()
    {
        $command = new SoapCommand("test");

        $soapHeaderParams = array(
            'login' => 'testLogin',
            'password' => 'testPwd');
        $headerObject = new \SoapHeader('foo', 'bar', $soapHeaderParams);
        $command
            ->addHeader($headerObject)
            ->setMethodName('test');

        $this->expectException('\RuntimeException');

        $command->send();
    }

    public function methodProvider()
    {
        return [
            ['GetWeather']
        ];
    }
}