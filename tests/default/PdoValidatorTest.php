<?php
namespace tests;

use Germania\PermanentAuth\PdoValidator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Prophecy\Argument;


class PdoValidatorTest extends \PHPUnit_Framework_TestCase
{

    public $logger;


    public function setUp()
    {
        parent::setUp();
        $this->logger = new NullLogger;
    }

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(__DIR__ . '/../auth_logins-dataset.xml');
    }



    public function testInstantiation(  )
    {
        $stmt = $this->prophesize(\PDOStatement::class);
        $stmt_mock = $stmt->reveal();

        $pdo_mock = $this->createPdoMock( $stmt_mock );
        $verifier = $this->createVerifier( true );

        $sut = new PdoValidator( $pdo_mock, $verifier, $this->logger);
        $this->assertInstanceOf( \PDOStatement::class, $sut->stmt );
    }


    public function testNoMatchingRow(  )
    {
        $stmt_mock = $this->createStatementMock( false );
        $pdo_mock  = $this->createPdoMock( $stmt_mock );
        $verifier  = function() {};

        $sut = new PdoValidator( $pdo_mock, $verifier, $this->logger);
        $this->assertFalse( $sut( "selector", "token" ) );
    }



    /**
     * @dataProvider provideVerificationResults
     */
    public function testVerification( $verification_result, $user_id )
    {
        $fetch_result = (object) [
            'token_hash' => "some",
            'user_id'    => $user_id
        ];

        $stmt_mock = $this->createStatementMock( $fetch_result );
        $pdo_mock  = $this->createPdoMock( $stmt_mock );
        $verifier  = $this->createVerifier( $verification_result );

        $sut = new PdoValidator( $pdo_mock, $verifier, $this->logger);
        $this->assertEquals( $user_id, $sut("selector", "token") );
    }



    public function provideVerificationResults()
    {
        return array(
            [ true,  99],
            [ false, false]
        );
    }

    protected function createVerifier( $result )
    {
        return function( $token, $token_hash) use ($result) {
            return $result;
        };
    }

    protected function createPdoMock( $stmt )
    {
        $pdo = $this->prophesize(\PDO::class);
        $pdo->prepare( Argument::type('string') )->willReturn( $stmt );
        return $pdo->reveal();
    }

    protected function createStatementMock( $fetch_result )
    {
        $stmt = $this->prophesize(\PDOStatement::class);
        $stmt->execute( Argument::type('array') )->willReturn( true );
        $stmt->fetch( Argument::any() )->willReturn( $fetch_result );
        return $stmt->reveal();
    }

}
