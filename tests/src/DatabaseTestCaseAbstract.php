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





    /**
     * Creates a temporary copy of the given table
     * May be useful one day.
     *
     * @param $tableName
     */
    public function createTmpTable($tableName)
    {
        $this->getPdo()->query( sprintf('DROP TEMPORARY TABLE IF EXISTS tmp_%1$s;', $tableName));
        $this->getPdo()->query( sprintf('CREATE TEMPORARY TABLE tmp_%1$s LIKE %1$s;', $tableName));
        $this->getPdo()->query( sprintf('INSERT tmp_%1$s SELECT * FROM %1$s;', $tableName));
    }

    /**
     * Restores a given table from a previously copied temporary table
     * May be useful one day.
     *
     * @param $tableName
     */
    public function restoreFromTmpTable($tableName)
    {
        $this->getPdo()->query( sprintf('DROP TABLE %1$s;', $tableName));
        $this->getPdo()->query( sprintf('CREATE TABLE %1$s LIKE tmp_%1$s;', $tableName));
        $this->getPdo()->query( sprintf('INSERT %1$s SELECT * FROM tmp_%1$s;', $tableName));
        $this->getPdo()->query( sprintf('DROP TEMPORARY TABLE IF EXISTS tmp_%1$s;', $tableName));
    }


    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createMySQLXMLDataSet( __DIR__ . "/auth_logins-dataset.xml");
    }

}
