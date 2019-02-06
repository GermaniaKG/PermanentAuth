<?php
namespace tests;

use Germania\PermanentAuth\Middleware;
use Germania\PermanentAuth\AuthUserInterface;
use Psr\Log\NullLogger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Prophecy\Argument;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MiddlewareTest extends \PHPUnit\Framework\TestCase
{

    public $logger;
    public $request;
    public $response;

    public function setUp()
    {
        $this->logger = new NullLogger;

        // $this->logger = new Logger('name');
        // $this->logger->pushHandler(new StreamHandler('php://stdout')); // <<< uses a stream
    }


    /**
     * @dataProvider provideCtorArguments
     */
    public function testSimpleUsage( $status, $user_id, $client_auth_result, $server_auth_result )
    {

        $request_mock = $this->prophesize( ServerRequestInterface::class );
        $response_mock = $this->prophesize( ResponseInterface::class );

        $user = $this->prophesize( AuthUserInterface::class );
        $user->getId( )->willReturn( $user_id );

        if ($server_auth_result) {
            $user->setId( Argument::exact($server_auth_result) )->will(function ($args, $itself) {
                return $itself;
            });
            $response_mock->withStatus( Argument::any() )->will(function ($args, $itself) {
                return $itself;
            });
            $response_mock->withHeader( Argument::any(), Argument::any() )->will(function ($args, $itself) {
                return $itself;
            });
        }



        $request   = $request_mock->reveal();
        $response  = $response_mock->reveal();
        $user_mock = $user->reveal();


        $client_authenticator = function() use ($client_auth_result) {
            return $client_auth_result;
        };

        $client_auth_remover = function()  {};

        $server_authenticator = function( $selector, $token) use ($server_auth_result) {
            return $server_auth_result;
        };

        $sut = new Middleware( $user_mock, $client_authenticator, $client_auth_remover, $server_authenticator, $this->logger);

        $next_middleware = function ($request, $respone) use ($response) {
            return $response;
        };


        $result = $sut( $request, $response, $next_middleware);

        $this->assertInstanceOf( ResponseInterface::class, $result);
        $this->assertEquals( $sut->status, $status);
    }


    public function provideCtorArguments()
    {
        $cient_auth_result = (object) [
            'selector' => "foo",
            'token'    => "bar"
        ];

        $known_user_id_on_server = 66;

        $user_known   = 0;
        $user_unknown = Middleware::ACTIVE;
        $client_auth  = Middleware::ACTIVE | Middleware::CLIENT_DATA;
        $server_ok    = Middleware::ACTIVE | Middleware::CLIENT_DATA | Middleware::SERVER_MATCH;

        return array(
            //     $status,       $user_id,                 $client_auth_result,  $server_auth_result
            array( $user_known,   $known_user_id_on_server, null,                 null),
            array( $user_unknown, null,                     null,                 null),
            array( $user_unknown, null,                     false,                false),
            array( $client_auth,  null,                     $cient_auth_result,   null),
            array( $client_auth,  null,                     $cient_auth_result,   false),
            array( $server_ok,    null,                     $cient_auth_result,   $known_user_id_on_server)
        );
    }


}
