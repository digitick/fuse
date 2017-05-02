<?php


namespace Digitick\Foundation\Tests\Fuse\Command\Http;


use Digitick\Foundation\Fuse\Command\Soap\SoapCommand;
use SoapClient;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;

class SoapCommandTest extends \PHPUnit_Framework_TestCase
{
    private function getSoapClient () {
        $soapClient = new SoapClient('http://www.webservicex.net/geoipservice.asmx?WSDL');
        return $soapClient;
    }

    /**
     *
     */
    public function testExceptionHeaders () {

        $soapClient = $this->getSoapClient();

        $command = new SoapCommand("test");

        $command->setSoapClient($soapClient);

        $this->setExpectedException('\RuntimeException');

        $result = $command->run();
    }

    /**
     *
     */
    public function testExceptionMethod () {

        $soapClient = $this->getSoapClient();

        $command = new SoapCommand("test");
        $soapHeaderParams = array(
            'login'    =>    'testLogin',
            'password'    =>    'testPwd');
        $command->addHeader(
            new \SoapHeader('foo', 'bar', $soapHeaderParams)
        );
        $command->setSoapClient($soapClient);

        $this->setExpectedException('\RuntimeException');

        $result = $command->run();
    }

    /**
     * @dataProvider methodProvider
     */
    public function testMethod($methodName) {
        $command = new SoapCommand("test");
        $command
            ->setMethodName($methodName);

        $this->assertEquals($methodName, $command->getMethodName());
    }

    public function testHeaders () {
        $command = new SoapCommand("test");
        $soapHeaderParams = array(
            'login'    =>    'testLogin',
            'password'    =>    'testPwd');
        $headerObject = new \SoapHeader('foo', 'bar', $soapHeaderParams);
        $command->addHeader(
            $headerObject
        );

        $this->assertEquals($headerObject, $command->getHeaders()[0]);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCallWithoutSoapClient ()
    {
        $command = new SoapCommand("test");

        $soapHeaderParams = array(
            'login'    =>    'testLogin',
            'password'    =>    'testPwd');
        $headerObject = new \SoapHeader('foo', 'bar', $soapHeaderParams);
        $command
            ->addHeader($headerObject)
            ->setMethodName('test');

        $result = $command->run();
    }

    public function methodProvider () {
        return [
            ['GetGeoIP']
        ];
    }
}