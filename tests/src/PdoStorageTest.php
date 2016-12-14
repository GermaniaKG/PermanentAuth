<?php
namespace tests;

use Germania\PermanentAuth\PdoStorage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Prophecy\Argument;
use Germania\PermanentAuth\Exceptions\DuplicateSelectorException;
use Germania\PermanentAuth\Exceptions\StorageException;

class PdoStorageTest extends \PHPUnit_Framework_TestCase
{

    public $logger;

    public function setUp()
    {
        $this->logger = new NullLogger;
    }

    /**
     * @dataProvider provideData
     */
    public function testInstantiation( $user_id, $execution_result, $row_count )
    {
        $stmt = $this->prophesize(\PDOStatement::class);
        $stmt->execute( Argument::type('array') )->willReturn( $execution_result );
        $stmt->rowCount( )->willReturn( $row_count );
        $stmt_mock = $stmt->reveal();

        $pdo = $this->prophesize(\PDO::class);
        $pdo->prepare( Argument::type('string') )->willReturn( $stmt_mock );

        $sut = new PdoStorage( $pdo->reveal(), $this->logger);
        $this->assertInstanceOf( \PDOStatement::class, $sut->avoid_stmt );
        $this->assertInstanceOf( \PDOStatement::class, $sut->insert_stmt );
    }

    /**
     * @dataProvider provideDuplicateSelectorData
     */
    public function testDuplicateSelectorException( $avoid_result, $insert_result )
    {
        $insert_stmt = $this->prophesize(\PDOStatement::class);
        $insert_stmt->execute( Argument::type('array') )->willReturn( $insert_result );
        $insert_stmt_mock = $insert_stmt->reveal();

        $pdo_mock  = $this->createPdoMock( $insert_stmt_mock );

        $avoid_stmt = $this->prophesize(\PDOStatement::class);
        $avoid_stmt->execute( Argument::type('array') )->willReturn( true );
        $avoid_stmt->fetchColumn( )->willReturn( true );
        $avoid_stmt_mock = $avoid_stmt->reveal();

        $sut = new PdoStorage( $pdo_mock, $this->logger);
        // Change avoid_stmt, because SUT internally works with two PDOStatements
        // created by same PDO instance, but in this test we need two different PDOStatements
        $sut->avoid_stmt = $avoid_stmt_mock;

        $this->expectException( DuplicateSelectorException::class );
        $sut( 99, "selector", "token_hash", new \DateTime);

    }
    /**
     * @dataProvider provideDuplicateSelectorData
     */
    public function testErrorOnAvoidStatementExecution( $avoid_result, $insert_result )
    {
        $insert_stmt = $this->prophesize(\PDOStatement::class);
        $insert_stmt->execute( Argument::type('array') )->willReturn( $insert_result );
        $insert_stmt_mock = $insert_stmt->reveal();

        $pdo_mock  = $this->createPdoMock( $insert_stmt_mock );

        $avoid_stmt = $this->prophesize(\PDOStatement::class);
        $avoid_stmt->execute( Argument::type('array') )->willReturn( false );
        $avoid_stmt->fetchColumn( )->willReturn( true );
        $avoid_stmt_mock = $avoid_stmt->reveal();

        $sut = new PdoStorage( $pdo_mock, $this->logger);
        // Change avoid_stmt, because SUT internally works with two PDOStatements
        // created by same PDO instance, but in this test we need two different PDOStatements
        $sut->avoid_stmt = $avoid_stmt_mock;

        $this->expectException( StorageException::class );
        $sut( 99, "selector", "token_hash", new \DateTime);

    }


    public function testSuccessfulInsertion(  )
    {
        $insert_stmt = $this->prophesize(\PDOStatement::class);
        $insert_stmt->execute( Argument::type('array') )->willReturn( true );
        $insert_stmt_mock = $insert_stmt->reveal();


        $pdo_mock  = $this->createPdoMock( $insert_stmt_mock );

        $avoid_stmt = $this->prophesize(\PDOStatement::class);
        $avoid_stmt->execute( Argument::type('array') )->willReturn( true );
        $avoid_stmt->fetchColumn( )->willReturn( null );
        $avoid_stmt_mock = $avoid_stmt->reveal();

        $sut = new PdoStorage( $pdo_mock, $this->logger);
        // Change avoid_stmt, because SUT internally works with two PDOStatements
        // created by same PDO instance, but in this test we need two different PDOStatements
        $sut->avoid_stmt = $avoid_stmt_mock;


        $result = $sut( 99, "selector", "token_hash", new \DateTime);
        $this->assertTrue( $result );

    }

    public function testFailedInsertion(  )
    {
        $insert_stmt = $this->prophesize(\PDOStatement::class);
        $insert_stmt->execute( Argument::type('array') )->willReturn( false );
        $insert_stmt->errorInfo(  )->willReturn( array() );
        $insert_stmt_mock = $insert_stmt->reveal();

        $pdo_mock  = $this->createPdoMock( $insert_stmt_mock );

        $avoid_stmt = $this->prophesize(\PDOStatement::class);
        $avoid_stmt->execute( Argument::type('array') )->willReturn( true );
        $avoid_stmt->fetchColumn( )->willReturn( null );
        $avoid_stmt_mock = $avoid_stmt->reveal();

        $sut = new PdoStorage( $pdo_mock, $this->logger);
        // Change avoid_stmt, because SUT internally works with two PDOStatements
        // created by same PDO instance, but in this test we need two different PDOStatements
        $sut->avoid_stmt = $avoid_stmt_mock;

        $this->expectException( StorageException::class );
        $result = $sut( 99, "selector", "token_hash", new \DateTime);
    }



    protected function createPdoMock( $stmt )
    {
        $pdo = $this->prophesize(\PDO::class);
        $pdo->prepare( Argument::type('string') )->willReturn( $stmt );
        return $pdo->reveal();
    }


    public function provideData()
    {
        return array(
            [ 1, true,  1 ],
            [ 2, false, 0 ]
        );
    }

    public function provideDuplicateSelectorData()
    {
        return array(
            [ true,  true ],
            [ false, true ]
        );
    }
}
