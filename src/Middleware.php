<?php
namespace Germania\PermanentAuth;

use Germania\PermanentAuth\Exceptions\RequestException;
use Germania\PermanentAuth\AuthUserInterface;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;


/**
 * This PSR-style Middleware tries to distill a User ID from "permanent login" data.
 *
 * - Tries to retrieve selector and token from client-side authentication (i.e. cookie)
 * - Check against database and get User ID
 * - Assign User ID to the user object passed into constructor
 */
class Middleware
{
    /**
     * @var Callable
     */
    public $client_authenticator;

    /**
     * @var Callable
     */
    public $client_auth_remover;

    /**
     * @var Callable
     */
    public $server_authenticator;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * HTTP Status code to use on redirect after successful login
     * @var int
     */
    public $authenticated_status_code = 200;

    /**
     * @var AuthUserInterface
     */
    public $user;

    /**
     * Reflects current working status
     */
    public $status = 0;

    /**
     * @var int
     */
    const ACTIVE = 1;

    /**
     * @var int
     */
    const CLIENT_DATA  = 2;

    /**
     * @var int
     */
    const SERVER_MATCH = 4;



    /**
     * @param AuthUserInterface     $user                  Authentication User Object
     * @param Callable              $client_authenticator  Callable that returns selector and token sent with request
     * @param Callable              $client_auth_remover   Callable that deletes errorneous Client Authentication
     * @param Callable              $server_authenticator  Callable that accepts selector and token and returns User ID
     * @param LoggerInterface|null  $logger                Optional: PSR-3 Logger
     */
    public function __construct( AuthUserInterface $user, Callable $client_authenticator, Callable $client_auth_remover, Callable $server_authenticator, LoggerInterface $logger = null)
    {
        $this->user = $user;
        $this->client_authenticator = $client_authenticator;
        $this->client_auth_remover  = $client_auth_remover;
        $this->server_authenticator = $server_authenticator;

        $this->logger = $logger ?: new NullLogger;
    }


    /**
     * @param  Psr\Http\Message\ServerRequestInterface  $request  PSR7 request
     * @param  Psr\Http\Message\ResponseInterface       $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return Psr\Http\Message\ResponseInterface
     *
     * @throws RuntimeException if Request has no 'user' attribute  with AuthUserInterface instance.
     */
    public function __invoke(Request $request, Response $response, $next)
    {

        // ---------------------------------
        // Prerequisites
        // ---------------------------------

        if ($uid = $this->user->getId()):
            $this->logger->debug("Before Route: User has ID, proceeding", [
                'user_id' => $uid
            ]);
            // Call next middleware
            return $next($request, $response);
        else:
            $this->status = $this->status | static::ACTIVE;
        endif;



        // ---------------------------------
        // 2. Retrieve Client Authentication data
        //    do nothing else if none set.
        // ---------------------------------

        $client_authenticator = $this->client_authenticator;
        if (!$client_authentication = $client_authenticator()):
            $this->logger->debug("Before Route: No Client Authentication found");
            // Call next middleware
            return $next($request, $response);
        else:
            $this->status = $this->status | static::CLIENT_DATA;
            $this->logger->info("Before Route: Received Client Authentication");
        endif;



        // ---------------------------------
        // 3. Find User ID on Server using
        //    client selector
        // ---------------------------------

        $server_authenticator = $this->server_authenticator;
        if (!$user_id = $server_authenticator( $client_authentication->selector, $client_authentication->token )):
            $this->logger->warning( "Before Route: Client Authentication did not match any database entry, delete it." );

            // Delete Client authentication (cookie)
            $client_auth_remover = $this->client_auth_remover;
            $client_auth_remover();

            // Call next middleware
            return $next($request, $response);
        else:
            $this->logger->info("Before Route: Client Auth matches revord on server");
            $this->status = $this->status | static::SERVER_MATCH;
        endif;



        // ---------------------------------
        // 4. Assign User ID to user, reload page
        // ---------------------------------
        $this->logger->info("Before Route: Assign ID to user, reload page", [
            "user_id" => $user_id
        ]);

        $this->user->setId( $user_id );

        return $response->withStatus( $this->authenticated_status_code )
                        ->withHeader('Location', (string) $request->getUri());
    }



}
