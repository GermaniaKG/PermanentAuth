<?php
namespace mysql;

use Germania\PermanentAuth\PdoValidator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Prophecy\Argument;


class PdoValidatorTest extends DatabaseTestCaseAbstract
{

    public $logger;


    public function setUp() : void
    {
        parent::setUp();
        $this->logger = new NullLogger;
    }



    public function testMysqlNoMatchingRow(  )
    {
        $verifier  = function() { return true; };

        $pdo = $this->getPdo();

        // Make sure all stored logins are in the future
        $dt = new \DateTime;
        $s = $pdo->prepare("UPDATE auth_logins SET valid_until = :futuredate WHERE 1");
        $f = $s->execute([
            'futuredate' => $dt->add(new \DateInterval('P10D'))->format('Y-m-d H:i:s:')
        ]);

        $sut = new PdoValidator( $pdo, $verifier, $this->logger);
        $this->assertFalse( $sut( "do-not-find-this", "token" ) );
    }



    public function testMysqlMatchingRow(  )
    {
        $verifier  = function() { return true; };

        $pdo = $this->getPdo();

        // Make sure all stored logins are in the future
        $dt = new \DateTime;
        $s = $pdo->prepare("UPDATE auth_logins SET valid_until = :futuredate WHERE 1");
        $f = $s->execute([
            'futuredate' => $dt->add(new \DateInterval('P10D'))->format('Y-m-d H:i:s:')
        ]);

        $sut = new PdoValidator( $pdo, $verifier, $this->logger);
        $user_id = $sut( "foobar", "token" );
        $this->assertEquals(1, $user_id );
    }


}
