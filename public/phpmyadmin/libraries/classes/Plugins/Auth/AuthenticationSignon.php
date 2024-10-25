<?php
/**
 * SignOn Authentication plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Auth;

use PhpMyAdmin\Core;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Util;

use function __;
use function array_merge;
use function defined;
use function file_exists;
use function in_array;
use function session_get_cookie_params;
use function session_id;
use function session_name;
use function session_set_cookie_params;
use function session_start;
use function session_write_close;
use function version_compare;

use const PHP_VERSION;

/**
 * Handles the SignOn authentication method
 */
class AuthenticationSignon extends AuthenticationPlugin
{
    /**
     * Displays authentication form
     *
     * @return bool always true (no return indeed)
     */
    public function showLoginForm(): bool
    {
        ResponseRenderer::getInstance()->disable();
        unset($_SESSION['LAST_SIGNON_URL']);
        if (empty($GLOBALS['cfg']['Server']['SignonURL'])) {
            Core::fatalError('You must set SignonURL!');
        } else {
            Core::sendHeaderLocation($GLOBALS['cfg']['Server']['SignonURL']);
        }

        if (! defined('TESTSUITE')) {
            exit;
        }

        return false;
    }

    /**
     * Set cookie params
     *
     * @param array $sessionCookieParams The cookie params
     */
    public function setCookieParams(?array $sessionCookieParams = null): void
    {
        /* Session cookie params from config */
        if ($sessionCookieParams === null) {
            $sessionCookieParams = (array) $GLOBALS['cfg']['Server']['SignonCookieParams'];
        }

        /* Sanitize cookie params */
        $defaultCookieParams = /** @return mixed */ static function (string $key) {
            switch ($key) {
                case 'lifetime':
                    return 0;

                case 'path':
                    return '/';

                case 'domain':
                    return '';

                case 'secure':
                case 'httponly':
                    return false;
            }

            return null;
        };

        foreach (['lifetime', 'path', 'domain', 'secure', 'httponly'] as $key) {
            if (isset($sessionCookieParams[$key])) {
                continue;
            }

            $sessionCookieParams[$key] = $defaultCookieParams($key);
        }

        if (
            isset($sessionCookieParams['samesite'])
            && ! in_array($sessionCookieParams['samesite'], ['Lax', 'Strict'])
        ) {
            // Not a valid value for samesite
            unset($sessionCookieParams['samesite']);
        }

        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            /** @psalm-suppress InvalidArgument */
            session_set_cookie_params($sessionCookieParams);
        } else {
            session_set_cookie_params(
                $sessionCookieParams['lifetime'],
                $sessionCookieParams['path'],
                $sessionCookieParams['domain'],
                $sessionCookieParams['secure'],
                $sessionCookieParams['httponly']
            );
        }
    }

    /**
     * Gets authentication credentials
     */
    public function readCredentials(): bool
    {
        /* Check if we're using same signon server */
        $signon_url = $GLOBALS['cfg']['Server']['SignonURL'];
        if (isset($_SESSION['LAST_SIGNON_URL']) && $_SESSION['LAST_SIGNON_URL'] != $signon_url) {
            return false;
        }

        /* Script name */
        $script_name = $GLOBALS['cfg']['Server']['SignonScript'];

        /* Session name */
        $session_name = $GLOBALS['cfg']['Server']['SignonSession'];

        /* Current host */
        $single_signon_host = $GLOBALS['cfg']['Server']['host'];

        /* Current port */
        $single_signon_port = $GLOBALS['cfg']['Server']['port'];

        /* No configuration updates */
        $single_signon_cfgupdate = [];

        /* Handle script based auth */
        if ($script_name !== '') {
            if (! @file_exists($script_name)) {
                Core::fatalError(
                    __('Can not find signon authentication script:')
                    . ' ' . $script_name
                );
            }

            include $script_name;

            [$this->user, $this->password] = get_login_credentials($GLOBALS['cfg']['Server']['user']);
        } elseif (isset($_COOKIE[$session_name])) { /* Does session exist? */
            /* End current session */
            $old_session = session_name();
            $old_id = session_id();
            $oldCookieParams = session_get_cookie_params();
            if (! defined('TESTSUITE')) {
                session_write_close();
            }

            /* Load single signon session */
            if (! defined('TESTSUITE')) {
                $this->setCookieParams();
                session_name($session_name);
                session_id($_COOKIE[$session_name]);
                session_start();
            }

            /* Clear error message */
            unset($_SESSION['PMA_single_signon_error_message']);

            /* Grab credentials if they exist */
            if (isset($_SESSION['PMA_single_signon_user'])) {
                $this->user = $_SESSION['PMA_single_signon_user'];
            }

            if (isset($_SESSION['PMA_single_signon_password'])) {
                $this->password = $_SESSION['PMA_single_signon_password'];
            }

            if (isset($_SESSION['PMA_single_signon_host'])) {
                $single_signon_host = $_SESSION['PMA_single_signon_host'];
            }

            if (isset($_SESSION['PMA_single_signon_port'])) {
                $single_signon_port = $_SESSION['PMA_single_signon_port'];
            }

            if (isset($_SESSION['PMA_single_signon_cfgupdate'])) {
                $single_signon_cfgupdate = $_SESSION['PMA_single_signon_cfgupdate'];
            }

            /* Also get token as it is needed to access subpages */
            if (isset($_SESSION['PMA_single_signon_token'])) {
                /* No need to care about token on logout */
                $pma_token = $_SESSION['PMA_single_signon_token'];
            }

            $HMACSecret = Util::generateRandom(16);
            if (isset($_SESSION['PMA_single_signon_HMAC_secret'])) {
                $HMACSecret = $_SESSION['PMA_single_signon_HMAC_secret'];
            }

            /* End single signon session */
            if (! defined('TESTSUITE')) {
                session_write_close();
            }

            /* Restart phpMyAdmin session */
            if (! defined('TESTSUITE')) {
                $this->setCookieParams($oldCookieParams);
                if ($old_session !== false) {
                    session_name($old_session);
                }

                if (! empty($old_id)) {
                    session_id($old_id);
                }

                session_start();
            }

            /* Set the single signon host */
            $GLOBALS['cfg']['Server']['host'] = $single_signon_host;

            /* Set the single signon port */
            $GLOBALS['cfg']['Server']['port'] = $single_signon_port;

            /* Configuration update */
            $GLOBALS['cfg']['Server'] = array_merge($GLOBALS['cfg']['Server'], $single_signon_cfgupdate);

            /* Restore our token */
            if (! empty($pma_token)) {
                $_SESSION[' PMA_token '] = $pma_token;
                $_SESSION[' HMAC_secret '] = $HMACSecret;
            }

            /**
             * Clear user cache.
             */
            Util::clearUserCache();
        }

        // Returns whether we get authentication settings or not
        if (empty($this->user)) {
            unset($_SESSION['LAST_SIGNON_URL']);

            return false;
        }

        $_SESSION['LAST_SIGNON_URL'] = $GLOBALS['cfg']['Server']['SignonURL'];

        return true;
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @param string $failure String describing why authentication has failed
     */
    public function showFailure($failure): void
    {
        parent::showFailure($failure);

        /* Session name */
        $session_name = $GLOBALS['cfg']['Server']['SignonSession'];

        /* Does session exist? */
        if (isset($_COOKIE[$session_name])) {
            if (! defined('TESTSUITE')) {
                /* End current session */
                session_write_close();

                /* Load single signon session */
                $this->setCookieParams();
                session_name($session_name);
                session_id($_COOKIE[$session_name]);
                session_start();
            }

            /* Set error message */
            $_SESSION['PMA_single_signon_error_message'] = $this->getErrorMessage($failure);
        }

        $this->showLoginForm();
    }

    /**
     * Returns URL for login form.
     *
     * @return string
     */
    public function getLoginFormURL()
    {
        return $GLOBALS['cfg']['Server']['SignonURL'];
    }
}
