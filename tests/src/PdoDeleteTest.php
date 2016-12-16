<?php
namespace tests;

use Germania\PermanentAuth\PdoDelete;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Prophecy\Argument;

class PdoDeleteTest extends DatabaseTestCaseAbstract  #\PHPUnit_Framework_TestCase
{

    public function getDataSet()
    {
        # return $this->createMySQLXMLDataSet(__DIR__ . '/../sql/auth_logins.sql');
        return $this->createMySQLXMLDataSet('tests/auth_logins-dataset.xml');
        return $this->createFlatXmlDataSet(__DIR__ . '/auth_logins-dataset.xml');
    }

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
        return;
        $stmt = $this->prophesize(\PDOStatement::class);
        $stmt->execute( Argument::type('array') )->willReturn( $execution_result );
        $stmt->rowCount( )->willReturn( $row_count );
        $stmt_mock = $stmt->reveal();

        $pdo = $this->prophesize(\PDO::class);
        $pdo->prepare( Argument::type('string') )->willReturn( $stmt_mock );

        $sut = new PdoDelete( $pdo->reveal(), $this->logger);
        $this->assertEquals( $row_count, $sut($user_id) );
    }


    /**
     * @dataProvider provideData
     */
    public function testMySQLTable( $user_id, $execution_result, $row_count )
    {
        $pdo = $this->getPdo();


        $sut = new PdoDelete( $pdo, $this->logger);
        $this->assertEquals( $row_count, $sut($user_id) );
    }


    public function provideData()
    {
        return array(
            [ 1, true,  1 ],
            [ 3, false, 0 ]
        );
    }
}
