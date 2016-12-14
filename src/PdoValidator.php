<?php
namespace Germania\PermanentAuth;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * This Callable finds on the server-side the user ID for a given selector and token pair.
 */
class PdoValidator
{
    /**
     * @var LoggerInterface
     */
    public $logger;


    /**
     * @var PDOStatement
     */
    public $stmt;

    /**
     * @var Callable
     */
    public $verifier;


    /**
     * @var string
     */
    public $table = "auth_logins";


    /**
     * @param \PDO                 $pdo      PDO instance
     * @param Callable             $verifier Token authentication that accepts selector and token
     * @param LoggerInterface|null $logger   Optional: PSR-3 Logger
     * @param string               $table    Optional: Custom table name, default: `auth_logins`
     */
    public function __construct( \PDO $pdo, Callable $verifier, LoggerInterface $logger = null, $table = null)
    {
        $this->verifier = $verifier;
        $this->logger   = $logger;
        $this->table    = $table ?: $this->table;

        $sql = "SELECT
        user_id,
        token_hash
        FROM {$this->table}
        WHERE selector = :selector
        AND valid_until >= NOW()
        LIMIT 1";

        $this->stmt = $pdo->prepare( $sql );
    }


    /**
     * Finds the user ID for a matching selector and token pair.
     *
     * @param  string $selector Login selector
     * @param  string $token    Login token
     * @return mixed            User ID or FALSE
     */
    public function __invoke( $selector, $token )
    {
        $this->stmt->execute([
            'selector' => $selector
        ]);

        if (!$row = $this->stmt->fetch( \PDO::FETCH_OBJ )):
            $this->logger->info("No matching row found");
            return false;
        else:
            $this->logger->debug("Found row", [
                'user_id' => $row->user_id
            ]);
        endif;

        $verifier = $this->verifier;

        $result = $verifier($token, $row->token_hash);
        $this->logger->info("Token match: " . ($result ? "YES" : "NO"));

        return $result ? $row->user_id : false;
    }
}
