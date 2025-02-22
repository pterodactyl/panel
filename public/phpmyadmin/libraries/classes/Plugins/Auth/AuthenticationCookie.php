<?php
/**
 * Cookie Authentication plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Auth;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Select;
use PhpMyAdmin\Session;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\SessionCache;
use ReCaptcha;
use Throwable;

use function __;
use function array_keys;
use function base64_decode;
use function base64_encode;
use function count;
use function defined;
use function explode;
use function function_exists;
use function in_array;
use function ini_get;
use function intval;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function random_bytes;
use function session_id;
use function sodium_crypto_secretbox;
use function sodium_crypto_secretbox_open;
use function strlen;
use function time;

use const SODIUM_CRYPTO_SECRETBOX_KEYBYTES;
use const SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

/**
 * Handles the cookie authentication method
 */
class AuthenticationCookie extends AuthenticationPlugin
{
    /**
     * Displays authentication form
     *
     * this function MUST exit/quit the application
     *
     * @global string $conn_error the last connection error
     */
    public function showLoginForm(): bool
    {
        global $conn_error, $route;

        $response = ResponseRenderer::getInstance();

        /**
         * When sending login modal after session has expired, send the
         * new token explicitly with the response to update the token
         * in all the forms having a hidden token.
         */
        $session_expired = isset($_REQUEST['check_timeout']) || isset($_REQUEST['session_timedout']);
        if (! $session_expired && $response->loginPage()) {
            if (defined('TESTSUITE')) {
                return true;
            }

            exit;
        }

        /**
         * When sending login modal after session has expired, send the
         * new token explicitly with the response to update the token
         * in all the forms having a hidden token.
         */
        if ($session_expired) {
            $response->setRequestStatus(false);
            $response->addJSON('new_token', $_SESSION[' PMA_token ']);
        }

        /**
         * logged_in response parameter is used to check if the login,
         * using the modal was successful after session expiration.
         */
        if (isset($_REQUEST['session_timedout'])) {
            $response->addJSON('logged_in', 0);
        }

        // No recall if blowfish secret is not configured as it would produce
        // garbage
        if ($GLOBALS['cfg']['LoginCookieRecall'] && ! empty($GLOBALS['cfg']['blowfish_secret'])) {
            $default_user = $this->user;
            $default_server = $GLOBALS['pma_auth_server'];
            $hasAutocomplete = true;
        } else {
            $default_user = '';
            $default_server = '';
            $hasAutocomplete = false;
        }

        // wrap the login form in a div which overlays the whole page.
        if ($session_expired) {
            $loginHeader = $this->template->render('login/header', [
                'add_class' => ' modal_form',
                'session_expired' => 1,
            ]);
        } else {
            $loginHeader = $this->template->render('login/header', [
                'add_class' => '',
                'session_expired' => 0,
            ]);
        }

        $errorMessages = '';
        // Show error message
        if (! empty($conn_error)) {
            $errorMessages = Message::rawError((string) $conn_error)->getDisplay();
        } elseif (isset($_GET['session_expired']) && intval($_GET['session_expired']) == 1) {
            $errorMessages = Message::rawError(
                __('Your session has expired. Please log in again.')
            )->getDisplay();
        }

        $languageManager = LanguageManager::getInstance();
        $availableLanguages = [];
        if (empty($GLOBALS['cfg']['Lang']) && $languageManager->hasChoice()) {
            $availableLanguages = $languageManager->sortedLanguages();
        }

        $serversOptions = '';
        $hasServers = count($GLOBALS['cfg']['Servers']) > 1;
        if ($hasServers) {
            $serversOptions = Select::render(false, false);
        }

        $_form_params = [];
        if (strlen($GLOBALS['db'])) {
            $_form_params['db'] = $GLOBALS['db'];
        }

        if (strlen($GLOBALS['table'])) {
            $_form_params['table'] = $GLOBALS['table'];
        }

        $errors = '';
        if ($GLOBALS['errorHandler']->hasDisplayErrors()) {
            $errors = $GLOBALS['errorHandler']->getDispErrors();
        }

        // close the wrapping div tag, if the request is after session timeout
        if ($session_expired) {
            $loginFooter = $this->template->render('login/footer', ['session_expired' => 1]);
        } else {
            $loginFooter = $this->template->render('login/footer', ['session_expired' => 0]);
        }

        $configFooter = Config::renderFooter();

        echo $this->template->render('login/form', [
            'login_header' => $loginHeader,
            'is_demo' => $GLOBALS['cfg']['DBG']['demo'],
            'error_messages' => $errorMessages,
            'available_languages' => $availableLanguages,
            'is_session_expired' => $session_expired,
            'has_autocomplete' => $hasAutocomplete,
            'session_id' => session_id(),
            'is_arbitrary_server_allowed' => $GLOBALS['cfg']['AllowArbitraryServer'],
            'default_server' => $default_server,
            'default_user' => $default_user,
            'has_servers' => $hasServers,
            'server_options' => $serversOptions,
            'server' => $GLOBALS['server'],
            'lang' => $GLOBALS['lang'],
            'has_captcha' => ! empty($GLOBALS['cfg']['CaptchaApi'])
                && ! empty($GLOBALS['cfg']['CaptchaRequestParam'])
                && ! empty($GLOBALS['cfg']['CaptchaResponseParam'])
                && ! empty($GLOBALS['cfg']['CaptchaLoginPrivateKey'])
                && ! empty($GLOBALS['cfg']['CaptchaLoginPublicKey']),
            'use_captcha_checkbox' => ($GLOBALS['cfg']['CaptchaMethod'] ?? '') === 'checkbox',
            'captcha_api' => $GLOBALS['cfg']['CaptchaApi'],
            'captcha_req' => $GLOBALS['cfg']['CaptchaRequestParam'],
            'captcha_resp' => $GLOBALS['cfg']['CaptchaResponseParam'],
            'captcha_key' => $GLOBALS['cfg']['CaptchaLoginPublicKey'],
            'form_params' => $_form_params,
            'errors' => $errors,
            'login_footer' => $loginFooter,
            'config_footer' => $configFooter,
        ]);

        if (! defined('TESTSUITE')) {
            exit;
        }

        return true;
    }

    /**
     * Gets authentication credentials
     *
     * this function DOES NOT check authentication - it just checks/provides
     * authentication credentials required to connect to the MySQL server
     * usually with $dbi->connect()
     *
     * it returns false if something is missing - which usually leads to
     * showLoginForm() which displays login form
     *
     * it returns true if all seems ok which usually leads to auth_set_user()
     *
     * it directly switches to showFailure() if user inactivity timeout is reached
     */
    public function readCredentials(): bool
    {
        global $conn_error;

        // Initialization
        /**
         * @global $GLOBALS['pma_auth_server'] the user provided server to
         * connect to
         */
        $GLOBALS['pma_auth_server'] = '';

        $this->user = $this->password = '';
        $GLOBALS['from_cookie'] = false;

        if (isset($_POST['pma_username']) && strlen($_POST['pma_username']) > 0) {
            // Verify Captcha if it is required.
            if (
                ! empty($GLOBALS['cfg']['CaptchaApi'])
                && ! empty($GLOBALS['cfg']['CaptchaRequestParam'])
                && ! empty($GLOBALS['cfg']['CaptchaResponseParam'])
                && ! empty($GLOBALS['cfg']['CaptchaLoginPrivateKey'])
                && ! empty($GLOBALS['cfg']['CaptchaLoginPublicKey'])
            ) {
                if (empty($_POST[$GLOBALS['cfg']['CaptchaResponseParam']])) {
                    $conn_error = __('Missing reCAPTCHA verification, maybe it has been blocked by adblock?');

                    return false;
                }

                $captchaSiteVerifyURL = $GLOBALS['cfg']['CaptchaSiteVerifyURL'] ?? '';
                $captchaSiteVerifyURL = empty($captchaSiteVerifyURL) ? null : $captchaSiteVerifyURL;
                if (function_exists('curl_init') && function_exists('curl_exec')) {
                    $reCaptcha = new ReCaptcha\ReCaptcha(
                        $GLOBALS['cfg']['CaptchaLoginPrivateKey'],
                        new ReCaptcha\RequestMethod\CurlPost(null, $captchaSiteVerifyURL)
                    );
                } elseif (ini_get('allow_url_fopen')) {
                    $reCaptcha = new ReCaptcha\ReCaptcha(
                        $GLOBALS['cfg']['CaptchaLoginPrivateKey'],
                        new ReCaptcha\RequestMethod\Post($captchaSiteVerifyURL)
                    );
                } else {
                    $reCaptcha = new ReCaptcha\ReCaptcha(
                        $GLOBALS['cfg']['CaptchaLoginPrivateKey'],
                        new ReCaptcha\RequestMethod\SocketPost(null, $captchaSiteVerifyURL)
                    );
                }

                // verify captcha status.
                $resp = $reCaptcha->verify(
                    $_POST[$GLOBALS['cfg']['CaptchaResponseParam']],
                    Core::getIp()
                );

                // Check if the captcha entered is valid, if not stop the login.
                if ($resp == null || ! $resp->isSuccess()) {
                    $codes = $resp->getErrorCodes();

                    if (in_array('invalid-json', $codes)) {
                        $conn_error = __('Failed to connect to the reCAPTCHA service!');
                    } else {
                        $conn_error = __('Entered captcha is wrong, try again!');
                    }

                    return false;
                }
            }

            // The user just logged in
            $this->user = Core::sanitizeMySQLUser($_POST['pma_username']);

            $password = $_POST['pma_password'] ?? '';
            if (strlen($password) >= 2000) {
                $conn_error = __('Your password is too long. To prevent denial-of-service attacks, ' .
                    'phpMyAdmin restricts passwords to less than 2000 characters.');

                return false;
            }

            $this->password = $password;

            if ($GLOBALS['cfg']['AllowArbitraryServer'] && isset($_REQUEST['pma_servername'])) {
                if ($GLOBALS['cfg']['ArbitraryServerRegexp']) {
                    $parts = explode(' ', $_REQUEST['pma_servername']);
                    if (count($parts) === 2) {
                        $tmp_host = $parts[0];
                    } else {
                        $tmp_host = $_REQUEST['pma_servername'];
                    }

                    $match = preg_match($GLOBALS['cfg']['ArbitraryServerRegexp'], $tmp_host);
                    if (! $match) {
                        $conn_error = __('You are not allowed to log in to this MySQL server!');

                        return false;
                    }
                }

                $GLOBALS['pma_auth_server'] = Core::sanitizeMySQLHost($_REQUEST['pma_servername']);
            }

            /* Secure current session on login to avoid session fixation */
            Session::secure();

            return true;
        }

        // At the end, try to set the $this->user
        // and $this->password variables from cookies

        // check cookies
        $serverCookie = $GLOBALS['config']->getCookie('pmaUser-' . $GLOBALS['server']);
        if (empty($serverCookie)) {
            return false;
        }

        $value = $this->cookieDecrypt(
            $serverCookie,
            $this->getEncryptionSecret()
        );

        if ($value === null) {
            return false;
        }

        $this->user = $value;
        // user was never logged in since session start
        if (empty($_SESSION['browser_access_time'])) {
            return false;
        }

        // User inactive too long
        $last_access_time = time() - $GLOBALS['cfg']['LoginCookieValidity'];
        foreach ($_SESSION['browser_access_time'] as $key => $value) {
            if ($value >= $last_access_time) {
                continue;
            }

            unset($_SESSION['browser_access_time'][$key]);
        }

        // All sessions expired
        if (empty($_SESSION['browser_access_time'])) {
            SessionCache::remove('is_create_db_priv');
            SessionCache::remove('is_reload_priv');
            SessionCache::remove('db_to_create');
            SessionCache::remove('dbs_where_create_table_allowed');
            SessionCache::remove('dbs_to_test');
            SessionCache::remove('db_priv');
            SessionCache::remove('col_priv');
            SessionCache::remove('table_priv');
            SessionCache::remove('proc_priv');

            $this->showFailure('no-activity');
            if (! defined('TESTSUITE')) {
                exit;
            }

            return false;
        }

        // check password cookie
        $serverCookie = $GLOBALS['config']->getCookie('pmaAuth-' . $GLOBALS['server']);

        if (empty($serverCookie)) {
            return false;
        }

        $value = $this->cookieDecrypt(
            $serverCookie,
            $this->getSessionEncryptionSecret()
        );
        if ($value === null) {
            return false;
        }

        $auth_data = json_decode($value, true);

        if (! is_array($auth_data) || ! isset($auth_data['password'])) {
            return false;
        }

        $this->password = $auth_data['password'];
        if ($GLOBALS['cfg']['AllowArbitraryServer'] && ! empty($auth_data['server'])) {
            $GLOBALS['pma_auth_server'] = $auth_data['server'];
        }

        $GLOBALS['from_cookie'] = true;

        return true;
    }

    /**
     * Set the user and password after last checkings if required
     *
     * @return bool always true
     */
    public function storeCredentials(): bool
    {
        global $cfg;

        if ($GLOBALS['cfg']['AllowArbitraryServer'] && ! empty($GLOBALS['pma_auth_server'])) {
            /* Allow to specify 'host port' */
            $parts = explode(' ', $GLOBALS['pma_auth_server']);
            if (count($parts) === 2) {
                $tmp_host = $parts[0];
                $tmp_port = $parts[1];
            } else {
                $tmp_host = $GLOBALS['pma_auth_server'];
                $tmp_port = '';
            }

            if ($cfg['Server']['host'] != $GLOBALS['pma_auth_server']) {
                $cfg['Server']['host'] = $tmp_host;
                if (! empty($tmp_port)) {
                    $cfg['Server']['port'] = $tmp_port;
                }
            }

            unset($tmp_host, $tmp_port, $parts);
        }

        return parent::storeCredentials();
    }

    /**
     * Stores user credentials after successful login.
     */
    public function rememberCredentials(): void
    {
        // Name and password cookies need to be refreshed each time
        // Duration = one month for username
        $this->storeUsernameCookie($this->user);

        // Duration = as configured
        // Do not store password cookie on password change as we will
        // set the cookie again after password has been changed
        if (! isset($_POST['change_pw'])) {
            $this->storePasswordCookie($this->password);
        }

        // any parameters to pass?
        $url_params = [];
        if (strlen($GLOBALS['db']) > 0) {
            $url_params['db'] = $GLOBALS['db'];
        }

        if (strlen($GLOBALS['table']) > 0) {
            $url_params['table'] = $GLOBALS['table'];
        }

        // user logged in successfully after session expiration
        if (isset($_REQUEST['session_timedout'])) {
            $response = ResponseRenderer::getInstance();
            $response->addJSON('logged_in', 1);
            $response->addJSON('success', 1);
            $response->addJSON('new_token', $_SESSION[' PMA_token ']);

            if (! defined('TESTSUITE')) {
                exit;
            }

            return;
        }

        // Set server cookies if required (once per session) and, in this case,
        // force reload to ensure the client accepts cookies
        if ($GLOBALS['from_cookie']) {
            return;
        }

        /**
         * Clear user cache.
         */
        Util::clearUserCache();

        ResponseRenderer::getInstance()->disable();

        Core::sendHeaderLocation(
            './index.php?route=/' . Url::getCommonRaw($url_params, '&'),
            true
        );

        if (! defined('TESTSUITE')) {
            exit;
        }
    }

    /**
     * Stores username in a cookie.
     *
     * @param string $username User name
     */
    public function storeUsernameCookie($username): void
    {
        // Name and password cookies need to be refreshed each time
        // Duration = one month for username
        $GLOBALS['config']->setCookie(
            'pmaUser-' . $GLOBALS['server'],
            $this->cookieEncrypt(
                $username,
                $this->getEncryptionSecret()
            )
        );
    }

    /**
     * Stores password in a cookie.
     *
     * @param string $password Password
     */
    public function storePasswordCookie($password): void
    {
        $payload = ['password' => $password];
        if ($GLOBALS['cfg']['AllowArbitraryServer'] && ! empty($GLOBALS['pma_auth_server'])) {
            $payload['server'] = $GLOBALS['pma_auth_server'];
        }

        // Duration = as configured
        $GLOBALS['config']->setCookie(
            'pmaAuth-' . $GLOBALS['server'],
            $this->cookieEncrypt(
                (string) json_encode($payload),
                $this->getSessionEncryptionSecret()
            ),
            null,
            (int) $GLOBALS['cfg']['LoginCookieStore']
        );
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * prepares error message and switches to showLoginForm() which display the error
     * and the login form
     *
     * this function MUST exit/quit the application,
     * currently done by call to showLoginForm()
     *
     * @param string $failure String describing why authentication has failed
     */
    public function showFailure($failure): void
    {
        global $conn_error;

        parent::showFailure($failure);

        // Deletes password cookie and displays the login form
        $GLOBALS['config']->removeCookie('pmaAuth-' . $GLOBALS['server']);

        $conn_error = $this->getErrorMessage($failure);

        $response = ResponseRenderer::getInstance();

        // needed for PHP-CGI (not need for FastCGI or mod-php)
        $response->header('Cache-Control: no-store, no-cache, must-revalidate');
        $response->header('Pragma: no-cache');

        $this->showLoginForm();
    }

    /**
     * Returns blowfish secret or generates one if needed.
     */
    private function getEncryptionSecret(): string
    {
        /** @var mixed $key */
        $key = $GLOBALS['cfg']['blowfish_secret'] ?? null;
        if (! is_string($key)) {
            return $this->getSessionEncryptionSecret();
        }

        $length = mb_strlen($key, '8bit');
        if ($length === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $key;
        }

        if ($length > SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return mb_substr($key, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES, '8bit');
        }

        return $this->getSessionEncryptionSecret();
    }

    /**
     * Returns blowfish secret or generates one if needed.
     */
    private function getSessionEncryptionSecret(): string
    {
        /** @var mixed $key */
        $key = $_SESSION['encryption_key'] ?? null;
        if (is_string($key) && mb_strlen($key, '8bit') === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $key;
        }

        $key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $_SESSION['encryption_key'] = $key;

        return $key;
    }

    public function cookieEncrypt(string $data, string $secret): string
    {
        try {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $ciphertext = sodium_crypto_secretbox($data, $nonce, $secret);
        } catch (Throwable $throwable) {
            return '';
        }

        return base64_encode($nonce . $ciphertext);
    }

    public function cookieDecrypt(string $encryptedData, string $secret): ?string
    {
        $encrypted = base64_decode($encryptedData);
        $nonce = mb_substr($encrypted, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($encrypted, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        try {
            $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $secret);
        } catch (Throwable $throwable) {
            return null;
        }

        if (! is_string($decrypted)) {
            return null;
        }

        return $decrypted;
    }

    /**
     * Callback when user changes password.
     *
     * @param string $password New password to set
     */
    public function handlePasswordChange($password): void
    {
        $this->storePasswordCookie($password);
    }

    /**
     * Perform logout
     */
    public function logOut(): void
    {
        global $config;

        // -> delete password cookie(s)
        if ($GLOBALS['cfg']['LoginCookieDeleteAll']) {
            foreach (array_keys($GLOBALS['cfg']['Servers']) as $key) {
                $config->removeCookie('pmaAuth-' . $key);
                if (! $config->issetCookie('pmaAuth-' . $key)) {
                    continue;
                }

                $config->removeCookie('pmaAuth-' . $key);
            }
        } else {
            $cookieName = 'pmaAuth-' . $GLOBALS['server'];
            $config->removeCookie($cookieName);
            if ($config->issetCookie($cookieName)) {
                $config->removeCookie($cookieName);
            }
        }

        parent::logOut();
    }
}
