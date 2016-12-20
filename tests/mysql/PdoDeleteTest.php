<?php
namespace mysql;

use Germania\PermanentAuth\PdoDelete;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Prophecy\Argument;

class PdoDeleteTest extends DatabaseTestCaseAbstract  #\PHPUnit_Framework_TestCase
{

    public $logger;

    public function setUp()
    {
        parent::setUp();
        $this->logger = new NullLogger;
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
            // This entry exists
            [ 1, true,  1 ],
            // and this does not.
            [ 3, false, 0 ]
        );
    }

}
