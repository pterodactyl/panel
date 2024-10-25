<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Http\ServerRequest;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

use function __;
use function array_keys;
use function array_pop;
use function array_walk_recursive;
use function chr;
use function count;
use function defined;
use function explode;
use function filter_var;
use function function_exists;
use function getenv;
use function gmdate;
use function hash_equals;
use function hash_hmac;
use function header;
use function header_remove;
use function htmlspecialchars;
use function http_build_query;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_replace;
use function session_write_close;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function strtr;
use function substr;
use function trigger_error;
use function unserialize;
use function urldecode;
use function vsprintf;

use const DATE_RFC1123;
use const E_USER_ERROR;
use const E_USER_WARNING;
use const FILTER_VALIDATE_IP;

/**
 * Core functions used all over the scripts.
 */
class Core
{
    /**
     * Removes insecure parts in a path; used before include() or
     * require() when a part of the path comes from an insecure source
     * like a cookie or form.
     *
     * @param string $path The path to check
     */
    public static function securePath(string $path): string
    {
        // change .. to .
        return (string) preg_replace('@\.\.*@', '.', $path);
    }

    /**
     * displays the given error message on phpMyAdmin error page in foreign language,
     * ends script execution and closes session
     *
     * loads language file if not loaded already
     *
     * @param string       $error_message the error message or named error message
     * @param string|array $message_args  arguments applied to $error_message
     */
    public static function fatalError(
        string $error_message,
        $message_args = null
    ): void {
        global $dbi;

        /* Use format string if applicable */
        if (is_string($message_args)) {
            $error_message = sprintf($error_message, $message_args);
        } elseif (is_array($message_args)) {
            $error_message = vsprintf($error_message, $message_args);
        }

        /*
         * Avoid using Response class as config does not have to be loaded yet
         * (this can happen on early fatal error)
         */
        if (
            isset($dbi, $GLOBALS['config'])
            && $GLOBALS['config']->get('is_setup') === false
            && ResponseRenderer::getInstance()->isAjax()
        ) {
            $response = ResponseRenderer::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', Message::error($error_message));

            if (! defined('TESTSUITE')) {
                exit;
            }

            return;
        }

        if (! empty($_REQUEST['ajax_request'])) {
            // Generate JSON manually
            self::headerJSON();
            echo json_encode(
                [
                    'success' => false,
                    'message' => Message::error($error_message)->getDisplay(),
                ]
            );

            if (! defined('TESTSUITE')) {
                exit;
            }

            return;
        }

        $error_message = strtr($error_message, ['<br>' => '[br]']);
        $template = new Template();

        echo $template->render('error/generic', [
            'lang' => $GLOBALS['lang'] ?? 'en',
            'dir' => $GLOBALS['text_dir'] ?? 'ltr',
            'error_message' => Sanitize::sanitizeMessage($error_message),
        ]);

        if (! defined('TESTSUITE')) {
            exit;
        }
    }

    /**
     * Returns a link to the PHP documentation
     *
     * @param string $target anchor in documentation
     *
     * @return string  the URL
     */
    public static function getPHPDocLink(string $target): string
    {
        /* List of PHP documentation translations */
        $php_doc_languages = [
            'pt_BR',
            'zh_CN',
            'fr',
            'de',
            'ja',
            'ru',
            'es',
            'tr',
        ];

        $lang = 'en';
        if (isset($GLOBALS['lang']) && in_array($GLOBALS['lang'], $php_doc_languages)) {
            if ($GLOBALS['lang'] === 'zh_CN') {
                $lang = 'zh';
            } else {
                $lang = $GLOBALS['lang'];
            }
        }

        return self::linkURL('https://www.php.net/manual/' . $lang . '/' . $target);
    }

    /**
     * Warn or fail on missing extension.
     *
     * @param string $extension Extension name
     * @param bool   $fatal     Whether the error is fatal.
     * @param string $extra     Extra string to append to message.
     */
    public static function warnMissingExtension(
        string $extension,
        bool $fatal = false,
        string $extra = ''
    ): void {
        global $errorHandler;

        $message = 'The %s extension is missing. Please check your PHP configuration.';

        /* Gettext does not have to be loaded yet here */
        if (function_exists('__')) {
            $message = __('The %s extension is missing. Please check your PHP configuration.');
        }

        $doclink = self::getPHPDocLink('book.' . $extension . '.php');
        $message = sprintf($message, '[a@' . $doclink . '@Documentation][em]' . $extension . '[/em][/a]');
        if ($extra != '') {
            $message .= ' ' . $extra;
        }

        if ($fatal) {
            self::fatalError($message);

            return;
        }

        $errorHandler->addError($message, E_USER_WARNING, '', 0, false);
    }

    /**
     * returns count of tables in given db
     *
     * @param string $db database to count tables for
     *
     * @return int count of tables in $db
     */
    public static function getTableCount(string $db): int
    {
        global $dbi;

        $tables = $dbi->tryQuery('SHOW TABLES FROM ' . Util::backquote($db) . ';');

        if ($tables) {
            return $tables->numRows();
        }

        return 0;
    }

    /**
     * Converts numbers like 10M into bytes
     * Used with permission from Moodle (https://moodle.org) by Martin Dougiamas
     * (renamed with PMA prefix to avoid double definition when embedded
     * in Moodle)
     *
     * @param string|int $size size (Default = 0)
     */
    public static function getRealSize($size = 0): int
    {
        if (! $size) {
            return 0;
        }

        $binaryprefixes = [
            'T' => 1099511627776,
            't' => 1099511627776,
            'G' => 1073741824,
            'g' => 1073741824,
            'M' => 1048576,
            'm' => 1048576,
            'K' => 1024,
            'k' => 1024,
        ];

        if (preg_match('/^([0-9]+)([KMGT])/i', (string) $size, $matches)) {
            return (int) ($matches[1] * $binaryprefixes[$matches[2]]);
        }

        return (int) $size;
    }

    /**
     * Checks given $page against given $allowList and returns true if valid
     * it optionally ignores query parameters in $page (script.php?ignored)
     *
     * @param string $page      page to check
     * @param array  $allowList allow list to check page against
     * @param bool   $include   whether the page is going to be included
     */
    public static function checkPageValidity(&$page, array $allowList = [], $include = false): bool
    {
        if (empty($allowList)) {
            $allowList = ['index.php'];
        }

        if (empty($page)) {
            return false;
        }

        if (in_array($page, $allowList)) {
            return true;
        }

        if ($include) {
            return false;
        }

        $_page = mb_substr(
            $page,
            0,
            (int) mb_strpos($page . '?', '?')
        );
        if (in_array($_page, $allowList)) {
            return true;
        }

        $_page = urldecode($page);
        $_page = mb_substr(
            $_page,
            0,
            (int) mb_strpos($_page . '?', '?')
        );

        return in_array($_page, $allowList);
    }

    /**
     * tries to find the value for the given environment variable name
     *
     * searches in $_SERVER, $_ENV then tries getenv() and apache_getenv()
     * in this order
     *
     * @param string $var_name variable name
     *
     * @return string  value of $var or empty string
     */
    public static function getenv(string $var_name): string
    {
        if (isset($_SERVER[$var_name])) {
            return (string) $_SERVER[$var_name];
        }

        if (isset($_ENV[$var_name])) {
            return (string) $_ENV[$var_name];
        }

        if (getenv($var_name)) {
            return (string) getenv($var_name);
        }

        if (function_exists('apache_getenv') && apache_getenv($var_name, true)) {
            return (string) apache_getenv($var_name, true);
        }

        return '';
    }

    /**
     * Send HTTP header, taking IIS limits into account (600 seems ok)
     *
     * @param string $uri         the header to send
     * @param bool   $use_refresh whether to use Refresh: header when running on IIS
     */
    public static function sendHeaderLocation(string $uri, bool $use_refresh = false): void
    {
        if ($GLOBALS['config']->get('PMA_IS_IIS') && mb_strlen($uri) > 600) {
            ResponseRenderer::getInstance()->disable();

            $template = new Template();
            echo $template->render('header_location', ['uri' => $uri]);

            return;
        }

        /*
         * Avoid relative path redirect problems in case user entered URL
         * like /phpmyadmin/index.php/ which some web servers happily accept.
         */
        if ($uri[0] === '.') {
            $uri = $GLOBALS['config']->getRootPath() . substr($uri, 2);
        }

        $response = ResponseRenderer::getInstance();

        session_write_close();
        if ($response->headersSent()) {
            trigger_error('Core::sendHeaderLocation called when headers are already sent!', E_USER_ERROR);
        }

        // bug #1523784: IE6 does not like 'Refresh: 0', it
        // results in a blank page
        // but we need it when coming from the cookie login panel)
        if ($GLOBALS['config']->get('PMA_IS_IIS') && $use_refresh) {
            $response->header('Refresh: 0; ' . $uri);

            return;
        }

        $response->header('Location: ' . $uri);
    }

    /**
     * Outputs application/json headers. This includes no caching.
     */
    public static function headerJSON(): void
    {
        if (defined('TESTSUITE')) {
            return;
        }

        // No caching
        $headers = self::getNoCacheHeaders();

        // Media type
        $headers['Content-Type'] = 'application/json; charset=UTF-8';

        /**
         * Disable content sniffing in browser.
         * This is needed in case we include HTML in JSON, browser might assume it's html to display.
         */
        $headers['X-Content-Type-Options'] = 'nosniff';

        foreach ($headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
    }

    /**
     * Outputs headers to prevent caching in browser (and on the way).
     */
    public static function noCacheHeader(): void
    {
        if (defined('TESTSUITE')) {
            return;
        }

        $headers = self::getNoCacheHeaders();

        foreach ($headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
    }

    /**
     * @return array<string, string>
     */
    public static function getNoCacheHeaders(): array
    {
        $headers = [];
        $date = (string) gmdate(DATE_RFC1123);

        // rfc2616 - Section 14.21
        $headers['Expires'] = $date;

        // HTTP/1.1
        $headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0';

        // HTTP/1.0
        $headers['Pragma'] = 'no-cache';

        // test case: exporting a database into a .gz file with Safari
        // would produce files not having the current time
        // (added this header for Safari but should not harm other browsers)
        $headers['Last-Modified'] = $date;

        return $headers;
    }

    /**
     * Sends header indicating file download.
     *
     * @param string $filename Filename to include in headers if empty,
     *                         none Content-Disposition header will be sent.
     * @param string $mimetype MIME type to include in headers.
     * @param int    $length   Length of content (optional)
     * @param bool   $no_cache Whether to include no-caching headers.
     */
    public static function downloadHeader(
        string $filename,
        string $mimetype,
        int $length = 0,
        bool $no_cache = true
    ): void {
        $headers = [];

        if ($no_cache) {
            $headers = self::getNoCacheHeaders();
        }

        /* Replace all possibly dangerous chars in filename */
        $filename = Sanitize::sanitizeFilename($filename);
        if (! empty($filename)) {
            $headers['Content-Description'] = 'File Transfer';
            $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        }

        $headers['Content-Type'] = $mimetype;

        // The default output in PMA uses gzip,
        // so if we want to output uncompressed file, we should reset the encoding.
        // See PHP bug https://github.com/php/php-src/issues/8218
        header_remove('Content-Encoding');

        $headers['Content-Transfer-Encoding'] = 'binary';

        if ($length > 0) {
            $headers['Content-Length'] = (string) $length;
        }

        foreach ($headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
    }

    /**
     * Returns value of an element in $array given by $path.
     * $path is a string describing position of an element in an associative array,
     * eg. Servers/1/host refers to $array[Servers][1][host]
     *
     * @param string $path    path in the array
     * @param array  $array   the array
     * @param mixed  $default default value
     *
     * @return array|mixed|null array element or $default
     */
    public static function arrayRead(string $path, array $array, $default = null)
    {
        $keys = explode('/', $path);
        $value =& $array;
        foreach ($keys as $key) {
            if (! isset($value[$key])) {
                return $default;
            }

            $value =& $value[$key];
        }

        return $value;
    }

    /**
     * Stores value in an array
     *
     * @param string $path  path in the array
     * @param array  $array the array
     * @param mixed  $value value to store
     */
    public static function arrayWrite(string $path, array &$array, $value): void
    {
        $keys = explode('/', $path);
        $last_key = array_pop($keys);
        $a =& $array;
        foreach ($keys as $key) {
            if (! isset($a[$key])) {
                $a[$key] = [];
            }

            $a =& $a[$key];
        }

        $a[$last_key] = $value;
    }

    /**
     * Removes value from an array
     *
     * @param string $path  path in the array
     * @param array  $array the array
     */
    public static function arrayRemove(string $path, array &$array): void
    {
        $keys = explode('/', $path);
        $keys_last = array_pop($keys);
        $path = [];
        $depth = 0;

        $path[0] =& $array;
        $found = true;
        // go as deep as required or possible
        foreach ($keys as $key) {
            if (! isset($path[$depth][$key])) {
                $found = false;
                break;
            }

            $depth++;
            $path[$depth] =& $path[$depth - 1][$key];
        }

        // if element found, remove it
        if ($found) {
            unset($path[$depth][$keys_last]);
            $depth--;
        }

        // remove empty nested arrays
        for (; $depth >= 0; $depth--) {
            if (isset($path[$depth + 1]) && count($path[$depth + 1]) !== 0) {
                break;
            }

            unset($path[$depth][$keys[$depth]]);
        }
    }

    /**
     * Returns link to (possibly) external site using defined redirector.
     *
     * @param string $url URL where to go.
     *
     * @return string URL for a link.
     */
    public static function linkURL(string $url): string
    {
        if (! preg_match('#^https?://#', $url)) {
            return $url;
        }

        $params = [];
        $params['url'] = $url;

        $url = Url::getCommon($params);
        //strip off token and such sensitive information. Just keep url.
        $arr = parse_url($url);

        if (! is_array($arr)) {
            $arr = [];
        }

        parse_str($arr['query'] ?? '', $vars);
        $query = http_build_query(['url' => $vars['url']]);

        if ($GLOBALS['config'] !== null && $GLOBALS['config']->get('is_setup')) {
            return '../url.php?' . $query;
        }

        return './url.php?' . $query;
    }

    /**
     * Checks whether domain of URL is an allowed domain or not.
     * Use only for URLs of external sites.
     *
     * @param string $url URL of external site.
     */
    public static function isAllowedDomain(string $url): bool
    {
        $arr = parse_url($url);

        if (! is_array($arr)) {
            $arr = [];
        }

        // We need host to be set
        if (! isset($arr['host']) || strlen($arr['host']) == 0) {
            return false;
        }

        // We do not want these to be present
        $blocked = [
            'user',
            'pass',
            'port',
        ];
        foreach ($blocked as $part) {
            if (isset($arr[$part]) && strlen((string) $arr[$part]) != 0) {
                return false;
            }
        }

        $domain = $arr['host'];
        $domainAllowList = [
            /* Include current domain */
            $_SERVER['SERVER_NAME'],
            /* phpMyAdmin domains */
            'wiki.phpmyadmin.net',
            'www.phpmyadmin.net',
            'phpmyadmin.net',
            'demo.phpmyadmin.net',
            'docs.phpmyadmin.net',
            /* mysql.com domains */
            'dev.mysql.com',
            'bugs.mysql.com',
            /* mariadb domains */
            'mariadb.org',
            'mariadb.com',
            /* php.net domains */
            'php.net',
            'www.php.net',
            /* Github domains*/
            'github.com',
            'www.github.com',
            /* Percona domains */
            'www.percona.com',
            /* Following are doubtful ones. */
            'mysqldatabaseadministration.blogspot.com',
        ];

        return in_array($domain, $domainAllowList);
    }

    /**
     * Replace some html-unfriendly stuff
     *
     * @param string $buffer String to process
     *
     * @return string Escaped and cleaned up text suitable for html
     */
    public static function mimeDefaultFunction(string $buffer): string
    {
        $buffer = htmlspecialchars($buffer);
        $buffer = str_replace('  ', ' &nbsp;', $buffer);

        return (string) preg_replace("@((\015\012)|(\015)|(\012))@", '<br>' . "\n", $buffer);
    }

    /**
     * Displays SQL query before executing.
     *
     * @param array|string $query_data Array containing queries or query itself
     */
    public static function previewSQL($query_data): void
    {
        $retval = '<div class="preview_sql">';
        if (empty($query_data)) {
            $retval .= __('No change');
        } elseif (is_array($query_data)) {
            foreach ($query_data as $query) {
                $retval .= Html\Generator::formatSql($query);
            }
        } else {
            $retval .= Html\Generator::formatSql($query_data);
        }

        $retval .= '</div>';
        $response = ResponseRenderer::getInstance();
        $response->addJSON('sql_data', $retval);
    }

    /**
     * recursively check if variable is empty
     *
     * @param mixed $value the variable
     */
    public static function emptyRecursive($value): bool
    {
        if (is_array($value)) {
            $empty = true;
            array_walk_recursive(
                $value,
                /**
                 * @param mixed $item
                 */
                static function ($item) use (&$empty): void {
                    $empty = $empty && empty($item);
                }
            );

            return $empty;
        }

        return empty($value);
    }

    /**
     * Creates some globals from $_POST variables matching a pattern
     *
     * @param array $post_patterns The patterns to search for
     */
    public static function setPostAsGlobal(array $post_patterns): void
    {
        global $containerBuilder;

        foreach (array_keys($_POST) as $post_key) {
            foreach ($post_patterns as $one_post_pattern) {
                if (! preg_match($one_post_pattern, $post_key)) {
                    continue;
                }

                $GLOBALS[$post_key] = $_POST[$post_key];
                $containerBuilder->setParameter($post_key, $GLOBALS[$post_key]);
            }
        }
    }

    /**
     * Gets the "true" IP address of the current user
     *
     * @return string|bool the ip of the user
     */
    public static function getIp()
    {
        /* Get the address of user */
        if (empty($_SERVER['REMOTE_ADDR'])) {
            /* We do not know remote IP */
            return false;
        }

        $direct_ip = $_SERVER['REMOTE_ADDR'];

        /* Do we trust this IP as a proxy? If yes we will use it's header. */
        if (! isset($GLOBALS['cfg']['TrustedProxies'][$direct_ip])) {
            /* Return true IP */
            return $direct_ip;
        }

        /**
         * Parse header in form:
         * X-Forwarded-For: client, proxy1, proxy2
         */
        // Get header content
        $value = self::getenv($GLOBALS['cfg']['TrustedProxies'][$direct_ip]);
        // Grab first element what is client adddress
        $value = explode(',', $value)[0];
        // checks that the header contains only one IP address,
        $is_ip = filter_var($value, FILTER_VALIDATE_IP);

        if ($is_ip !== false) {
            // True IP behind a proxy
            return $value;
        }

        // We could not parse header
        return false;
    }

    /**
     * Sanitizes MySQL hostname
     *
     * * strips p: prefix(es)
     *
     * @param string $name User given hostname
     */
    public static function sanitizeMySQLHost(string $name): string
    {
        while (strtolower(substr($name, 0, 2)) === 'p:') {
            $name = substr($name, 2);
        }

        return $name;
    }

    /**
     * Sanitizes MySQL username
     *
     * * strips part behind null byte
     *
     * @param string $name User given username
     */
    public static function sanitizeMySQLUser(string $name): string
    {
        $position = strpos($name, chr(0));
        if ($position !== false) {
            return substr($name, 0, $position);
        }

        return $name;
    }

    /**
     * Safe unserializer wrapper
     *
     * It does not unserialize data containing objects
     *
     * @param string $data Data to unserialize
     *
     * @return mixed|null
     */
    public static function safeUnserialize(string $data)
    {
        /* validate serialized data */
        $length = strlen($data);
        $depth = 0;
        for ($i = 0; $i < $length; $i++) {
            $value = $data[$i];

            switch ($value) {
                case '}':
                    /* end of array */
                    if ($depth <= 0) {
                        return null;
                    }

                    $depth--;
                    break;
                case 's':
                    /* string */
                    // parse sting length
                    $strlen = intval(substr($data, $i + 2));
                    // string start
                    $i = strpos($data, ':', $i + 2);
                    if ($i === false) {
                        return null;
                    }

                    // skip string, quotes and ;
                    $i += 2 + $strlen + 1;
                    if ($data[$i] !== ';') {
                        return null;
                    }

                    break;

                case 'b':
                case 'i':
                case 'd':
                    /* bool, integer or double */
                    // skip value to separator
                    $i = strpos($data, ';', $i);
                    if ($i === false) {
                        return null;
                    }

                    break;
                case 'a':
                    /* array */
                    // find array start
                    $i = strpos($data, '{', $i);
                    if ($i === false) {
                        return null;
                    }

                    // remember nesting
                    $depth++;
                    break;
                case 'N':
                    /* null */
                    // skip to end
                    $i = strpos($data, ';', $i);
                    if ($i === false) {
                        return null;
                    }

                    break;
                default:
                    /* any other elements are not wanted */
                    return null;
            }
        }

        // check unterminated arrays
        if ($depth > 0) {
            return null;
        }

        return unserialize($data);
    }

    /**
     * Sign the sql query using hmac using the session token
     *
     * @param string $sqlQuery The sql query
     *
     * @return string
     */
    public static function signSqlQuery($sqlQuery)
    {
        global $cfg;

        $secret = $_SESSION[' HMAC_secret '] ?? '';

        return hash_hmac('sha256', $sqlQuery, $secret . $cfg['blowfish_secret']);
    }

    /**
     * Check that the sql query has a valid hmac signature
     *
     * @param string $sqlQuery  The sql query
     * @param string $signature The Signature to check
     */
    public static function checkSqlQuerySignature($sqlQuery, $signature): bool
    {
        global $cfg;

        $secret = $_SESSION[' HMAC_secret '] ?? '';
        $hmac = hash_hmac('sha256', $sqlQuery, $secret . $cfg['blowfish_secret']);

        return hash_equals($hmac, $signature);
    }

    /**
     * Get the container builder
     */
    public static function getContainerBuilder(): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();
        $loader = new PhpFileLoader($containerBuilder, new FileLocator(ROOT_PATH . 'libraries'));
        $loader->load('services_loader.php');

        return $containerBuilder;
    }

    public static function populateRequestWithEncryptedQueryParams(ServerRequest $request): ServerRequest
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        unset($_GET['eq'], $_POST['eq'], $_REQUEST['eq']);

        if (! isset($queryParams['eq']) && (! is_array($parsedBody) || ! isset($parsedBody['eq']))) {
            return $request;
        }

        $encryptedQuery = '';
        if (
            is_array($parsedBody)
            && isset($parsedBody['eq'])
            && is_string($parsedBody['eq'])
            && $parsedBody['eq'] !== ''
        ) {
            $encryptedQuery = $parsedBody['eq'];
            unset($parsedBody['eq'], $queryParams['eq']);
        } elseif (isset($queryParams['eq']) && is_string($queryParams['eq']) && $queryParams['eq'] !== '') {
            $encryptedQuery = $queryParams['eq'];
            unset($queryParams['eq']);
        }

        $decryptedQuery = null;
        if ($encryptedQuery !== '') {
            $decryptedQuery = Url::decryptQuery($encryptedQuery);
        }

        if ($decryptedQuery === null) {
            $request = $request->withQueryParams($queryParams);
            if (is_array($parsedBody)) {
                $request = $request->withParsedBody($parsedBody);
            }

            return $request;
        }

        $urlQueryParams = (array) json_decode($decryptedQuery);
        foreach ($urlQueryParams as $urlQueryParamKey => $urlQueryParamValue) {
            if (is_array($parsedBody)) {
                $parsedBody[$urlQueryParamKey] = $urlQueryParamValue;
                $_POST[$urlQueryParamKey] = $urlQueryParamValue;
            } else {
                $queryParams[$urlQueryParamKey] = $urlQueryParamValue;
                $_GET[$urlQueryParamKey] = $urlQueryParamValue;
            }

            $_REQUEST[$urlQueryParamKey] = $urlQueryParamValue;
        }

        $request = $request->withQueryParams($queryParams);
        if (is_array($parsedBody)) {
            $request = $request->withParsedBody($parsedBody);
        }

        return $request;
    }
}
