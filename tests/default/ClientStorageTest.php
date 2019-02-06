<?php
namespace tests;

use Germania\PermanentAuth\ClientStorage;
use Germania\PermanentAuth\Exceptions\StorageException;

class ClientStorageTest extends \PHPUnit\Framework\TestCase
{



    /**
     * @dataProvider provideCookieSetterResults
     */
    public function testSuccessfullUsage( $setter_result)
    {

        $cookie_setter_mock = function( $name, $value, $timestamp ) use ($setter_result) {
            return $setter_result;
        };

        $encryptor = function( $selector, $token ) {
            return true;
        };

        $sut = new ClientStorage( "cookie", $encryptor, $cookie_setter_mock);

        if (!$setter_result) {
            $this->expectException( StorageException::class );
        }
        $this->assertEquals( $sut( "foo", "bar", 3600), $setter_result);
    }




    public function provideCookieSetterResults()
    {
        return array(
            array( true),
            array( false)
        );
    }


}
