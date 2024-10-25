<?php
/**
 * Abstract class for the authentication plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\IpAllowDeny;
use PhpMyAdmin\Logging;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Session;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_keys;
use function defined;
use function htmlspecialchars;
use function intval;
use function max;
use function min;
use function session_destroy;
use function session_unset;
use function sprintf;
use function time;

/**
 * Provides a common interface that will have to be implemented by all of the
 * authentication plugins.
 */
abstract class AuthenticationPlugin
{
    /**
     * Username
     *
     * @var string
     */
    public $user = '';

    /**
     * Password
     *
     * @var string
     */
    public $password = '';

    /** @var IpAllowDeny */
    protected $ipAllowDeny;

    /** @var Template */
    public $template;

    public function __construct()
    {
        $this->ipAllowDeny = new IpAllowDeny();
        $this->template = new Template();
    }

    /**
     * Displays authentication form
     */
    abstract public function showLoginForm(): bool;

    /**
     * Gets authentication credentials
     */
    abstract public function readCredentials(): bool;

    /**
     * Set the user and password after last checkings if required
     */
    public function storeCredentials(): bool
    {
        global $cfg;

        $this->setSessionAccessTime();

        $cfg['Server']['user'] = $this->user;
        $cfg['Server']['password'] = $this->password;

        return true;
    }

    /**
     * Stores user credentials after successful login.
     */
    public function rememberCredentials(): void
    {
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @param string $failure String describing why authentication has failed
     */
    public function showFailure($failure): void
    {
        Logging::logUser($this->user, $failure);
    }

    /**
     * Perform logout
     */
    public function logOut(): void
    {
        global $config;

        /* Obtain redirect URL (before doing logout) */
        if (! empty($GLOBALS['cfg']['Server']['LogoutURL'])) {
            $redirect_url = $GLOBALS['cfg']['Server']['LogoutURL'];
        } else {
            $redirect_url = $this->getLoginFormURL();
        }

        /* Clear credentials */
        $this->user = '';
        $this->password = '';

        /*
         * Get a logged-in server count in case of LoginCookieDeleteAll is disabled.
         */
        $server = 0;
        if ($GLOBALS['cfg']['LoginCookieDeleteAll'] === false && $GLOBALS['cfg']['Server']['auth_type'] === 'cookie') {
            foreach (array_keys($GLOBALS['cfg']['Servers']) as $key) {
                if (! $config->issetCookie('pmaAuth-' . $key)) {
                    continue;
                }

                $server = $key;
            }
        }

        if ($server === 0) {
            /* delete user's choices that were stored in session */
            if (! defined('TESTSUITE')) {
                session_unset();
                session_destroy();
            }

            /* Redirect to login form (or configured URL) */
            Core::sendHeaderLocation($redirect_url);
        } else {
            /* Redirect to other authenticated server */
            $_SESSION['partial_logout'] = true;
            Core::sendHeaderLocation(
                './index.php?route=/' . Url::getCommonRaw(['server' => $server], '&')
            );
        }
    }

    /**
     * Returns URL for login form.
     *
     * @return string
     */
    public function getLoginFormURL()
    {
        return './index.php?route=/';
    }

    /**
     * Returns error message for failed authentication.
     *
     * @param string $failure String describing why authentication has failed
     *
     * @return string
     */
    public function getErrorMessage($failure)
    {
        global $dbi;

        if ($failure === 'empty-denied') {
            return __('Login without a password is forbidden by configuration (see AllowNoPassword)');
        }

        if ($failure === 'root-denied' || $failure === 'allow-denied') {
            return __('Access denied!');
        }

        if ($failure === 'no-activity') {
            return sprintf(
                __('You have been automatically logged out due to inactivity of %s seconds.'
                . ' Once you log in again, you should be able to resume the work where you left off.'),
                intval($GLOBALS['cfg']['LoginCookieValidity'])
            );
        }

        $dbi_error = $dbi->getError();
        if (! empty($dbi_error)) {
            return htmlspecialchars($dbi_error);
        }

        if (isset($GLOBALS['errno'])) {
            return '#' . $GLOBALS['errno'] . ' '
            . __('Cannot log in to the MySQL server');
        }

        return __('Cannot log in to the MySQL server');
    }

    /**
     * Callback when user changes password.
     *
     * @param string $password New password to set
     */
    public function handlePasswordChange($password): void
    {
    }

    /**
     * Store session access time in session.
     *
     * Tries to workaround PHP 5 session garbage collection which
     * looks at the session file's last modified time
     */
    public function setSessionAccessTime(): void
    {
        if (isset($_REQUEST['guid'])) {
            $guid = (string) $_REQUEST['guid'];
        } else {
            $guid = 'default';
        }

        if (isset($_REQUEST['access_time'])) {
            // Ensure access_time is in range <0, LoginCookieValidity + 1>
            // to avoid excessive extension of validity.
            //
            // Negative values can cause session expiry extension
            // Too big values can cause overflow and lead to same
            $time = time() - min(max(0, intval($_REQUEST['access_time'])), $GLOBALS['cfg']['LoginCookieValidity'] + 1);
        } else {
            $time = time();
        }

        $_SESSION['browser_access_time'][$guid] = $time;
    }

    /**
     * High level authentication interface
     *
     * Gets the credentials or shows login form if necessary
     */
    public function authenticate(): void
    {
        $success = $this->readCredentials();

        /* Show login form (this exits) */
        if (! $success) {
            /* Force generating of new session */
            Session::secure();
            $this->showLoginForm();
        }

        /* Store credentials (eg. in cookies) */
        $this->storeCredentials();
        /* Check allow/deny rules */
        $this->checkRules();
        /* clear user cache */
        Util::clearUserCache();
    }

    /**
     * Check configuration defined restrictions for authentication
     */
    public function checkRules(): void
    {
        global $cfg;

        // Check IP-based Allow/Deny rules as soon as possible to reject the
        // user based on mod_access in Apache
        if (isset($cfg['Server']['AllowDeny']['order'])) {
            $allowDeny_forbidden = false; // default
            if ($cfg['Server']['AllowDeny']['order'] === 'allow,deny') {
                $allowDeny_forbidden = true;
                if ($this->ipAllowDeny->allow()) {
                    $allowDeny_forbidden = false;
                }

                if ($this->ipAllowDeny->deny()) {
                    $allowDeny_forbidden = true;
                }
            } elseif ($cfg['Server']['AllowDeny']['order'] === 'deny,allow') {
                if ($this->ipAllowDeny->deny()) {
                    $allowDeny_forbidden = true;
                }

                if ($this->ipAllowDeny->allow()) {
                    $allowDeny_forbidden = false;
                }
            } elseif ($cfg['Server']['AllowDeny']['order'] === 'explicit') {
                if ($this->ipAllowDeny->allow() && ! $this->ipAllowDeny->deny()) {
                    $allowDeny_forbidden = false;
                } else {
                    $allowDeny_forbidden = true;
                }
            }

            // Ejects the user if banished
            if ($allowDeny_forbidden) {
                $this->showFailure('allow-denied');
            }
        }

        // is root allowed?
        if (! $cfg['Server']['AllowRoot'] && $cfg['Server']['user'] === 'root') {
            $this->showFailure('root-denied');
        }

        // is a login without password allowed?
        if ($cfg['Server']['AllowNoPassword'] || $cfg['Server']['password'] !== '') {
            return;
        }

        $this->showFailure('empty-denied');
    }

    /**
     * Checks whether two factor authentication is active
     * for given user and performs it.
     */
    public function checkTwoFactor(): void
    {
        $twofactor = new TwoFactor($this->user);

        /* Do we need to show the form? */
        if ($twofactor->check()) {
            return;
        }

        $response = ResponseRenderer::getInstance();
        if ($response->loginPage()) {
            if (defined('TESTSUITE')) {
                return;
            }

            exit;
        }

        echo $this->template->render('login/header');
        echo Message::rawNotice(
            __('You have enabled two factor authentication, please confirm your login.')
        )->getDisplay();
        echo $this->template->render('login/twofactor', [
            'form' => $twofactor->render(),
            'show_submit' => $twofactor->showSubmit(),
        ]);
        echo $this->template->render('login/footer');
        echo Config::renderFooter();
        if (! defined('TESTSUITE')) {
            exit;
        }
    }
}
