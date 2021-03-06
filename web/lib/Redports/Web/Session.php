<?php

namespace Redports\Web;

/**
 * Manage PHP sessions and authenticate users.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2015 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://freebsd.github.io/redports/
 */
class Session
{
    public function __construct()
    {
        self::initialize();

        if (isset($_COOKIE['SESSIONID'])) {
            session_start();

            if (isset($_SESSION['loginip']) && $_SESSION['loginip'] != $_SERVER['REMOTE_ADDR']) {
                $this->logout();
            }
        }
    }

    public static function initialize()
    {
        // Set Redis as session handler
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', 'unix:///var/run/redis/redis.sock?persistent=1');

        // Specify hash function used for session ids. Usually does not
        // work on FreeBSD unless hash functions are compiled into the binary
        //ini_set('session.hash_function', 'sha256');
        ini_set('session.hash_bits_per_character', 5);
        ini_set('session.entropy_length', 512);

        // Set session lifetime in redis (8h)
        ini_set('session.gc_maxlifetime', 28800);

        // Set cookie lifetime on client
        ini_set('session.cookie_lifetime', 0);

        // do not expose Cookie value to JavaScript (enforced by browser)
        ini_set('session.cookie_httponly', 1);

        if (Config::get('https_only') === true) {
            // only send cookie over https
            ini_set('session.cookie_secure', 1);
        }

        // prevent caching by sending no-cache header
        session_cache_limiter('nocache');

        // rename session
        session_name('SESSIONID');
    }

    public static function getSessionId()
    {
        return session_id();
    }

    public static function login($username)
    {
        if (session_id() === '') {
            session_start();
        }

        /* login assumed to be successfull */
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['loginip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['useragent'] = $_SERVER['HTTP_USER_AGENT'];

        return true;
    }

    public static function getUsername()
    {
        if (isset($_SESSION['username'])) {
            return $_SESSION['username'];
        }

        return false;
    }

    public static function isAuthenticated()
    {
        return isset($_SESSION['authenticated']);
    }

    public static function logout()
    {
        $_SESSION = array();

        /* also destroy session cookie on client */
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );

        session_destroy();

        return true;
    }
}
