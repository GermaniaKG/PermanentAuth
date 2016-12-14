<?php
namespace Germania\PermanentAuth;

use Germania\PermanentAuth\Exceptions\DuplicateSelectorException;
use Germania\PermanentAuth\Exceptions\StorageException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * This Callable stores a persistent login for a given User ID with selector, token hash and expiration date.
 *
 * If a selector exists, a `DuplicateSelectorException` will be thrown.
 * If the insert statement execution fails, a `StorageException` will be thrown.
 */
class PdoStorage
{

    /**
     * @var string
     */
    public $table     = "auth_logins";

    /**
     * @var PDOStatement
     */
    public $avoid_stmt;

    /**
     * @var PDOStatement
     */
    public $insert_stmt;

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

        $this->table  = $table ?: $this->table;
        $this->logger = $logger ?: new NullLogger;


        // ------------------------------------------
        // 1. Prepare avoid duplicates statement
        // ------------------------------------------
        $avoid_duplicate_sql = "SELECT selector
        FROM  {$this->table}
        WHERE selector = :selector
        LIMIT 1";

        $this->avoid_stmt = $pdo->prepare($avoid_duplicate_sql);


        // ------------------------------------------
        // 2. Prepare Insert statements
        // ------------------------------------------
        $insert_sql = "INSERT INTO {$this->table} (
          user_id,
          selector,
          token_hash,
          valid_until
        ) VALUES (
          :user_id,
          :selector,
          :token_hash,
          :valid_until
        )";

        $this->insert_stmt = $pdo->prepare( $insert_sql );


    }

    /**
     * @param  int       $user_id     User ID
     * @param  string    $selector    Cookie selector
     * @param  string    $token_hash  Token hash
     * @param  \DateTime $valid_until Valid until DateTime
     *
     * @return boolean
     *
     * @throws StorageException if Insert Statement executions returns FALSE.
     */
    public function __invoke($user_id, $selector, $token_hash, \DateTime $valid_until)
    {

        // ------------------------------------------
        // 1. Find out if selector already exists
        // ------------------------------------------
        $way_clear = $this->avoid_stmt->execute([':selector' => $selector]);
        if (!$way_clear) {
            throw new StorageException("Could not check if selector is existing.");
        }
        if ($this->avoid_stmt->fetchColumn()) {
            throw new DuplicateSelectorException;
        }

        // ------------------------------------------
        // 2. Insert
        // ------------------------------------------

        $result = $this->insert_stmt->execute([
            ':user_id'          => $user_id,
            ':selector'         => $selector,
            ':token_hash'       => $token_hash,
            ':valid_until'      => $valid_until->format('Y-m-d H:i:s')
        ]);


        if ($result) :
            $this->logger->info("Stored in database", [
                'user_id'  => $user_id,
                'selector' => $selector
            ]);
        else:
            $error_info = $this->insert_stmt->errorInfo();
            $this->logger->error('Could not store persistent login', [
                'user_id'    => $user_id,
                'selector'   => $selector,
                'stmt_error' => $error_info
            ]);

            throw new StorageException("Could not store persistent login on server: " . implode("/", $error_info));
        endif;

        return true;
    }


}
