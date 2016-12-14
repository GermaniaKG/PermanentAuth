<?php
namespace tests;

use Germania\PermanentAuth\ClientAuthentication;
use Psr\Log\NullLogger;

class ClientAuthenticationTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->logger = new NullLogger;
    }


    /**
     * @dataProvider provideCtorArgs
     */
    public function testInstantiation( $cookie_name, $cookie_value, $result, $expected_status)
    {
        $cookie_getter_mock = function( $name ) use ($cookie_value) {
            return $cookie_value;
        };

        $cookie_parser_mock = function( $value ) use ($result) {
            return $result;
        };

        $sut = new ClientAuthentication( $cookie_getter_mock,  $cookie_parser_mock, $cookie_name, $this->logger );
        $this->assertEquals( $sut(), $result);
        $this->assertEquals( $sut->status, $expected_status );

    }


    public function provideCtorArgs()
    {
        return array(
            //     $cookie_name, $cookie_value, $result,          $status
            array( "cookie",     "foobar",      "validresult",    ClientAuthentication::FOUND | ClientAuthentication::VALID),
            array( "cookie",     false,         false,            0 ),
            array( "cookie",     "foobar",      null,             ClientAuthentication::FOUND )
        );
    }


}
