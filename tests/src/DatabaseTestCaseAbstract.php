<?php
namespace tests;


abstract class DatabaseTestCaseAbstract extends \PHPUnit_Extensions_Database_TestCase
{

    // only instantiate pdo once for test clean-up/fixture load
    static protected $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    protected $conn = null;



    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    final public function getConnection()
    {
        if ($this->conn === null) {
            if (static::$pdo == null) {
                static::$pdo = new \PDO( $GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD'] );

                if ($db_setup = implode(\DIRECTORY_SEPARATOR, [ getcwd(), $GLOBALS['DB_SETUP']])
                and is_readable( $db_setup) ) {
                    static::$pdo->query( file_get_contents( $db_setup ));
                }

            }
            $this->conn = $this->createDefaultDBConnection(static::$pdo, $GLOBALS['DB_DBNAME']);

        }

        return $this->conn;
    }


    /**
     * @return PDO
     */
    final public function getPdo()
    {
        return $this->getConnection()->getConnection();
    }



}
