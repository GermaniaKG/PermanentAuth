<?php
namespace Germania\PermanentAuth;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * This Callable deletes any stored persistent login for a given User ID.
 */
class PdoDelete
{

    /**
     * @var string
     */
    public $table     = "auth_logins";

    /**
     * @var PDOStatement
     */
    public $stmt;


    /**
     * @var LoggerInterface
     */
    public $logger;



    /**
     * @param \PDO             $pdo    PDO instance
     * @param LoggerInterface  $logger Optional: PSR-3 Logger
     * @param string           $table  Optional: Custom table name, default: `auth_logins`
     */
    public function __construct( \PDO $pdo, LoggerInterface $logger = null, $table = null )
    {

        $this->table  = $table  ?: $this->table;
        $this->logger = $logger ?: new NullLogger;


        // ------------------------------------------
        // 1. Prepare statement
        // ------------------------------------------
        $sql = "DELETE FROM {$this->table}
        WHERE user_id = :user_id";

        $this->stmt = $pdo->prepare($sql);
    }


    /**
     * Deletes all logins for a given User ID.
     *
     * @param  int $user_id User ID
     * @return int Number of deleted rows
     */
    public function __invoke($user_id)
    {

        $this->stmt->execute([':user_id' => $user_id]);
        $deleted = $this->stmt->rowCount();

        $this->logger->info('Deleted persistent logins', [
            'user_id'      => $user_id,
            'deleted_rows' => $deleted
        ]);

        return $deleted;
    }


}
