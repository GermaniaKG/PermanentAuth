<?php
namespace tests;

use Germania\PermanentAuth\PdoDelete;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Prophecy\Argument;

class PdoDeleteTest extends \PHPUnit\Framework\TestCase
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

        $sut = new PdoDelete( $pdo->reveal(), $this->logger);
        $this->assertEquals( $row_count, $sut($user_id) );
    }


    public function provideData()
    {
        return array(
            // This entry exists
            [ 1, true,  1 ],
            // and this does not.
            [ 3, false, 0 ]
        );
    }

}
