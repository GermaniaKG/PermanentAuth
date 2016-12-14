<?php
namespace Germania\PermanentAuth;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;


/**
 * This Callable reads and parses a "permanent login" cookie and returns parsed cookie values.
 *
 * Returns the parser result, if invoked, or FALSE if cookie missing or persing failed.
 *
 * The constructor expects two Callabes; one for getting the cookie value and one for parsing.
 */
class ClientAuthentication
{

    /**
     * @var Callable
     */
    public $cookie_getter;

    /**
     * @var Callable
     */
    public $cookie_parser;

    /**
     * @var string
     */
    public $cookie_name;

    /**
     * @var LoggerInterface
     */
    public $logger;


    /**
     * Added to $status when cookie was found
     */
    CONST FOUND   = 1;

    /**
     * Added to $status when parsing succeeded
     */
    CONST VALID    = 2;

    /**
     * Hold status information
     * @var integer
     */
    public $status = 0;

    /**
     * @param Callable             $cookie_getter  Any callable that accepts a cookie name and returns its value
     * @param Callable             $cookie_parser  Any callable that parses a cookie value into a selector and token pair
     * @param string               $cookie_name    The permanent login cookie name
     * @param LoggerInterface|null $logger         Optional: PSR-3 Logger
     */
    public function __construct( Callable $cookie_getter, Callable $cookie_parser, $cookie_name, LoggerInterface $logger = null)
    {
        $this->cookie_getter = $cookie_getter;
        $this->cookie_name   = $cookie_name;
        $this->cookie_parser = $cookie_parser;
        $this->logger        = $logger ?: new NullLogger;
    }



    /**
     * Returns a selector and token pair like this:
     *
     *     <?php
     *     $result = (object) [
     *         'selector' => 'foo',
     *         'token'    => 'fdskfkqrqgqfi4t2iq89fu3uf8fhq'
     *     ];
     *     ?>
     *
     * If no cookie was sent along, or parsing failed, FALSE will be returned.
     *
     * @return mixed Success: StdClass object with selector and token or FALSE
     */
    public function __invoke()
    {

        // Read Cookie
        $cookie_getter = $this->cookie_getter;
        if (!$cookie_value = $cookie_getter( $this->cookie_name )):
            // Also change message in unit test assertions!
            $this->logger->debug("No Permanent Login cookie sent along");
            return false;
        endif;

        // Set status
        $this->status = $this->status | static::FOUND;

        // Also change message in unit test assertions!
        $this->logger->info("Received Permanent Login cookie");


        // Parse Cookie
        $cookie_parser = $this->cookie_parser;
        $selector_token_pair = $cookie_parser( $cookie_value );
        if (!$selector_token_pair) {
            // Also change message in unit test assertions!
            $this->logger->debug("Could not decrypt selector and token pair");
            return false;
        }

        // Set status
        $this->status = $this->status | static::VALID;

        // Return parsed value
        return $selector_token_pair;


    }
}
