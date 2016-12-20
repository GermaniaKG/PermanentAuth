<?php
namespace Germania\PermanentAuth;

use Germania\PermanentAuth\Exceptions\StorageExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RandomLib\Generator;


/**
 * This Callable creates a permanent login for a User ID on both Client and Server side.
 *
 * - Creates a selector and token pair.
 * - Stores hashed token with Expiration date in the server-side database, using $store_on_server Callable
 * - Stores selector and token with Expiration date in a client-side cookie, using $store_on_client Callable
 *
 * In case one of the Callables throws a `StorageExceptionInterface` it will give another try,
 * up to 5 attempts until it returns false.
 */
class CreatePersistentLogin
{
    /**
     * @var \RandomLib\Generator
     */
    public $generator;

    /**
     * @var Callable
     */
    public $store_on_client;

    /**
     * @var Callable
     */
    public $hash_callable;

    /**
     * @var Callable
     */
    public $store_on_server;

    /**
     * @var \DateTime
     */
    public $valid_until;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * Set length of cookie selector
     * @var integer
     */
    public $selector_length = 32;

    /**
     * Set length of token
     * @var integer
     */
    public $token_length = 256;



    /**
     * Counts the attemtps it took for creating a unique selector
     * @var integer
     */
    public $attempts = 0;

    /**
     * Limit selector creation to max. attempts.
     * @var integer
     */
    public $max_attempts = 5;



    /**
     * @param Generator         $generator        ircmaxells' RandomLib\Generator instance
     * @param Callable          $store_on_client  Callable for setting Authentication cookie
     * @param Callable          $hash_callable    Callable for securely hashing the token
     * @param Callable          $store_on_server  Callable for storing the Permanent Login
     * @param DateTime          $valid_until      How long permanent login shall be valid
     * @param LoggerInterface   $logger           Optional: PSR-3 Logger
     */
    public function __construct( Generator $generator, Callable $store_on_client, Callable $hash_callable, Callable $store_on_server, \DateTime $valid_until, LoggerInterface $logger = null)
    {
        $this->generator        = $generator;
        $this->store_on_client  = $store_on_client;
        $this->hash_callable    = $hash_callable;
        $this->store_on_server  = $store_on_server;
        $this->valid_until      = $valid_until;
        $this->logger           = $logger ?: new NullLogger;
    }


    /**
     * @param  $user_id The user ID to create a persistent login for
     *
     * @return bool FALSE when maximum attenpts exceeded
     *
     * @throws   description
     */
    public function __invoke( $user_id )
    {
        $this->attempts++;
        try {

            // ------------------------------------------
            // 1. Create cookie selector and auth token:
            // ------------------------------------------

            $selector = $this->generator->generateString( $this->selector_length );
            $token    = $this->generator->generate( $this->token_length );



            // ------------------------------------------
            // 2. Store in database.
            //    May throw StorageExceptionInterface exception
            // ------------------------------------------

            $hash_callable = $this->hash_callable;
            $token_hash     = $hash_callable( $token );

            $store_on_server = $this->store_on_server;
            $store_on_server( $user_id, $selector, $token_hash, $this->valid_until );



            // ------------------------------------------
            // 3. Store on Client
            //    May throw StorageExceptionInterface exception
            // ------------------------------------------

            $store_on_client = $this->store_on_client;
            $store_on_client(
                $selector,
                $token,
                $this->valid_until->getTimestamp()
            );


            // ------------------------------------------
            // Well done.
            // ------------------------------------------

            $this->logger->info("Created persistent login", [
                'attempts'    => $this->attempts,
                'user_id'     => $user_id,
                'selector'    => $selector,
                'valid_until' => $this->valid_until->format('Y-m-d, H:i:s')
            ]);

            // Reset counter
            $this->attempts = 0;
            return true;
        }

        catch( StorageExceptionInterface $e) {
            $this->logger->warning("Could not store permanent login.", [
                'exception'   => $e->getMessage(),
                'user_id'     => $user_id,
                'selector'    => $selector
            ]);
        }

        //
        // Try again
        //
        if ($this->attempts < $this->max_attempts):
            return $this->__invoke( $user_id );
        endif;

        //
        // Error on too much attempts
        //
        $this->logger->error("Could not create permanent login; stop after {$this->attempts} times.", [
            'user_id'     => $user_id,
            'selector'    => $selector
        ]);

        return false;

    }

}
