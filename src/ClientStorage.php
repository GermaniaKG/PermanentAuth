<?php
namespace Germania\PermanentAuth;

use Germania\PermanentAuth\Exceptions\StorageException;


/**
 * This Callable stores on the client side a selector and token pair for Permanent Login.
 *
 * The constructor expects a cookie name, an encrypting Callable and a Cookie setter Callable.
 */
class ClientStorage
{

    /**
     * @var Callable
     */
    public $encryptor;

    /**
     * @var Callable
     */
    public $cookie_setter;

    /**
     * @var string
     */
    public $cookie_name;


    /**
     * @param string   $cookie_name   The Login Cookie Name
     * @param Callable $encryptor     Any callable that encrypts selector and token pair
     * @param Callable $cookie_setter Any callable that accepts cookie name, cookie text and expiration timestamp.
     */
    public function __construct( $cookie_name, Callable $encryptor, Callable $cookie_setter)
    {
        $this->cookie_name   = $cookie_name;
        $this->encryptor     = $encryptor;
        $this->cookie_setter = $cookie_setter;
    }


    /**
     * @param  string $selector
     * @param  string $token
     * @param  int    $valid_until Timestamp
     *
     * @return mixed  cookie_setter result
     *
     * @throws StorageException if setting cookie fails.
     */
    public function __invoke( $selector, $token, $valid_until )
    {
        // Encrypt first
        $encryptor      = $this->encryptor;
        $crypted        = $encryptor( $selector, $token);

        // Set cookie
        $cookie_setter  = $this->cookie_setter;
        $storage_result = $cookie_setter(
            $this->cookie_name,
            $crypted,
            $valid_until
        );

        if (!$storage_result) {
            throw new StorageException("Could not store persistent login cookie on client: " . $this->cookie_name . " " . $valid_until);
        }
        return $storage_result;
    }
}
