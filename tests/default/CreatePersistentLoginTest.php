<?php
namespace tests;

use Germania\PermanentAuth\CreatePersistentLogin;
use Germania\PermanentAuth\Exceptions\StorageException;
use Germania\PermanentAuth\Exceptions\StorageExceptionInterface;
use RandomLib\Generator;
use Psr\Log\NullLogger;
use Prophecy\Argument;

class CreatePersistentLoginTest extends \PHPUnit_Framework_TestCase
{

    public $logger;
    public $generator;

    public function setUp()
    {
        $this->logger = new NullLogger;

        $gen = $this->prophesize( Generator::class);
        $gen->generateString( Argument::any() )->willReturn("AAA");
        $gen->generate( Argument::any() )->willReturn("A");
        $this->generator = $gen->reveal();
    }


    /**
     * @dataProvider provideInvokationResults
     */
    public function testSimpleUsage( $invoke_result, $client_exception, $server_exception)
    {
        $user_id     = 99;
        $valid_until = new \DateTime;

        $store_on_client = function( $selector, $token, $valid_until) use ($client_exception) {
            if ($client_exception) {
                throw $client_exception;
            }
        };

        $store_on_server = function($user_id, $selector, $token_hash, $valid_until) use ($server_exception) {
            if ($server_exception) {
                throw $server_exception;
            }
        };

        $hash_callable = function( $token ) { };

        $sut = new CreatePersistentLogin( $this->generator, $store_on_client, $hash_callable, $store_on_server, $valid_until, $this->logger);

        $this->assertEquals( $sut( $user_id ), $invoke_result);
    }


    public function provideInvokationResults()
    {
        $ex = new StorageException;

        return array(
            //     $invoke_result, $client_exception, $server_exception
            array( true,           null,              null),
            array( false,          null,              $ex),
            array( false,          $ex,               null)
        );
    }


}
