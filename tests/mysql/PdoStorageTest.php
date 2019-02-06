<?php
namespace mysql;

use Germania\PermanentAuth\PdoStorage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Prophecy\Argument;
use Germania\PermanentAuth\Exceptions\DuplicateSelectorException;
use Germania\PermanentAuth\Exceptions\StorageException;

class PdoStorageTest extends DatabaseTestCaseAbstract
{

    public $logger;

    public function setUp() : void
    {
        parent::setUp();
        $this->logger = new NullLogger;
    }

    public function testMysqlFailedInsertion(  )
    {
        $pdo = $this->getPdo();

        // Find some existing selector
        $find_stmt = $pdo->prepare("SELECT selector FROM auth_logins WHERE 1 LIMIT 1");
        $find_stmt->execute();
        $existing_selector = $find_stmt->fetchColumn();

        $sut = new PdoStorage( $pdo, $this->logger);

        $this->expectException( StorageException::class );
        $result = $sut( 99, $existing_selector, "token_hash", new \DateTime);
    }

    public function testMysqlInsertion(  )
    {
        $pdo = $this->getPdo();

        $sut = new PdoStorage( $pdo, $this->logger);

        $result = $sut( 99, "selector", "token_hash", new \DateTime);
        $this->assertTrue( $result );
    }



    public function provideData()
    {
        return array(
            [ 1, true,  1 ],
            [ 2, false, 0 ]
        );
    }


}
