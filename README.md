# Germania\PermanentAuth

**This package was destilled from legacy code!**   

[![Build Status](https://travis-ci.org/GermaniaKG/PermanentAuth.svg?branch=master)](https://travis-ci.org/GermaniaKG/PermanentAuth)
[![Code Coverage](https://scrutinizer-ci.com/g/GermaniaKG/PermanentAuth/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/PermanentAuth/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/GermaniaKG/PermanentAuth/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/PermanentAuth/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/GermaniaKG/PermanentAuth/badges/build.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/PermanentAuth/build-status/master)


## Requirements

- Anthony Ferrara's [ircmaxell/RandomLib](https://github.com/ircmaxell/RandomLib)

## Installation

```bash
$ composer require germania-kg/permanent-authentication
```

**MySQL users** may install the table *auth\_logins* using `auth_logins.sql.txt` in `sql/` directory.



## Create a persistent login

This Callable stores a selector and token pair in a cookie on the client side
and stores the selected and hashed token in the database. Five ingredients are required:

**rnd:** An instance of [RandomLib](https://github.com/ircmaxell/RandomLib) Generator for creating secure random numbers and strings.

**client_storage:** A Callable that stores the selector and token pair on the client-side. On error, it should throw an Exception implementing *Germania\PermanentAuth\Exceptions\StorageExceptionInterface.*  – Create your own or try the implementation described in [ClientStorage](#ClientStorage) section below

**hasher:** A Callable that securely hashes the random token created by RandomLib. It is recommended to use PHP's [password_hash](http://php.net/manual/de/function.password-hash.php).

**server_storage:** A Callable that stores selector and token hash in the database. On error, it should throw an Exception implementing *Germania\PermanentAuth\Exceptions\StorageExceptionInterface.* – Create your own or try the implementation described in [PdoStorage](#PdoStorage) section below.

**valid_until:** A PHP [DateTime](http://php.net/manual/de/class.datetime.php) object which holds the expiration date and time.

```php
<?php
use Germania\PermanentAuth\CreatePersistentLogin;
use RandomLib\Factory;
use RandomLib\Generator;

// Create Random generator
$factory = new RandomLib\Factory;
$random = $factory->getMediumStrengthGenerator();

// Setup expiration
$expire = new \DateTime;
date_add($expire, date_interval_create_from_date_string('10 days'));

// Setup hash function; this goes to database
$hasher = function( $token ) {	return password_hash( $token) };

// On error, throw Germania\PermanentAuth\Exceptions\StorageExceptionInterface
$client_storage = function( $selector, $token, $expire) { return setcookie( ... ); };

$server_storage = function($user_id, $selector, $token_hash, $expire) {
	$sql = 'INSERT INTO ...';
};


// Optional: PSR-3 Logger
$create = new CreatePersistentLogin( $random, $client_storage, $hasher, $server_storage, $expire);
$create = new CreatePersistentLogin( $random, $client_storage, $hasher, $server_storage, $expire, $logger);

// Action
$user_id = 99;
$created = $create( $user_id )

// Evaluate
if ($created):
	// Success!
endif;
```




## Authenticate a user with permanent login

This Callable tries to retrieve and return a persistent login selector and token. *It does not validate the user!* — In other words, it tells you who the re-visiting user claims to be.


```php
<?php
use Germania\PermanentAuth\ClientAuthentication;

// Setup:
// 1. Retrieve the cookie value
$cookie_getter = function( $cookie_name ) {
	// return $_COOKIE[ $name ]
	return "foo:bar";
};

// 2: Split into selector and token part
$cookie_parser = function( $value ) {
	$parts = explode(":", $value);
	return (object) array(
		'selector' => $parts[0],
		'token'    => $parts[1]
	);
};

$auth = new ClientAuthentication( $cookie_getter, $cookie_parser, "persistent");

// Invoke
$selector_token = $auth();

// Evaluate
if ($selector_token):
	// Check if selector and token are valid on server-side
endif;

```

## Helpers

### AuthUserInterface

Defines interceptors for the User ID. Required by **PermanentAuth\Middleware** which expects a user object-

```php
<?php
use Germania\PermanentAuth\AuthUserInterface;

class AppUser implements AuthUserInterface
{
	public $id;

    /**
     * Returns the User ID.
     * @return mixed
     */
    public function getId() {
    	return $this->id;
    }

    /**
     * Sets the User ID.
     * @param mixed $id
     */
    public function setId( $id )
    {
		$this->id = $id;
	}
}
```


### Middleware
This PSR-style Middleware identifies a user and validates the claimed login selector against database. On success, assign found User ID to user object.

Requires a **PermanentAuth\AuthUserInterface** instance.

```php
<?php
use Germania\PermanentAuth\Middleware;
use Slim\App;

$app = new App;

$user = new AppUser;

$middleware = new Middleware( $user, ... );
$app->add( $middleware );

```

### ClientStorage

Store selector and token on the client-side.
Random-generated selector and token are base64-encoded and sent to the Client as cookie, together with expiration date.


```php
<?php
use Germania\PermanentAuth\ClientStorage;
```

### PdoStorage
Store selector and token hash in the database, together with expiration date.

```php
<?php
use Germania\PermanentAuth\PdoStorage;
```

### PdoValidator
Validate a login selector and token against token hash in the database.

```php
<?php
use Germania\PermanentAuth\PdoValidator;
```

### PdoDelete

Remove all permanent logins for a given user.

```php
<?php
use Germania\PermanentAuth\PdoDelete;
```

## Development

```bash
$ git clone git@github.com:GermaniaKG/PermanentAuth.git permanent-authentication
$ cd permanent-authentication
$ composer install
```

## Unit tests

Either copy `phpunit.xml.dist` to `phpunit.xml` and adapt to your needs, or leave as is.

Setup a MySQL table `auth_logins` as in `sql/auth_logins.sql.txt`.
In `phpunit.xml`, edit the database credentials:

```xml
<php>
	<var name="DB_DSN"    value="mysql:host=localhost;dbname=DBNAME;charset=utf8" />
	<var name="DB_USER"   value="DBUSER" />
	<var name="DB_PASSWD" value="DBPASS" />
	<var name="DB_DBNAME" value="DBNAME" />
</php>
```

After all, run [PhpUnit](https://phpunit.de/) like this:

```bash
$ vendor/bin/phpunit
```
