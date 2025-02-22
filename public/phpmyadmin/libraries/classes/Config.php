<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Config\Settings;
use Throwable;

use function __;
use function array_filter;
use function array_merge;
use function array_replace_recursive;
use function array_slice;
use function count;
use function defined;
use function error_get_last;
use function error_reporting;
use function explode;
use function fclose;
use function file_exists;
use function filemtime;
use function fileperms;
use function fopen;
use function fread;
use function function_exists;
use function gd_info;
use function get_object_vars;
use function implode;
use function ini_get;
use function intval;
use function is_dir;
use function is_int;
use function is_numeric;
use function is_readable;
use function is_string;
use function is_writable;
use function mb_strstr;
use function mb_strtolower;
use function md5;
use function min;
use function mkdir;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function parse_url;
use function preg_match;
use function realpath;
use function rtrim;
use function setcookie;
use function sprintf;
use function str_contains;
use function str_replace;
use function stripos;
use function strlen;
use function strtolower;
use function substr;
use function sys_get_temp_dir;
use function time;
use function trigger_error;
use function trim;

use const ARRAY_FILTER_USE_KEY;
use const DIRECTORY_SEPARATOR;
use const E_USER_ERROR;
use const PHP_OS;
use const PHP_URL_PATH;
use const PHP_URL_SCHEME;
use const PHP_VERSION_ID;

/**
 * Configuration handling
 */
class Config
{
    /** @var array   default configuration settings */
    public $default = [];

    /** @var array   configuration settings, without user preferences applied */
    public $baseSettings = [];

    /** @var array   configuration settings */
    public $settings = [];

    /** @var string  config source */
    public $source = '';

    /** @var int     source modification time */
    public $sourceMtime = 0;

    /** @var int */
    public $setMtime = 0;

    /** @var bool */
    public $errorConfigFile = false;

    /** @var array */
    public $defaultServer = [];

    /**
     * @var bool whether init is done or not
     * set this to false to force some initial checks
     * like checking for required functions
     */
    public $done = false;

    /**
     * @param string $source source to read config from
     */
    public function __construct(?string $source = null)
    {
        $this->settings = ['is_setup' => false];

        // functions need to refresh in case of config file changed goes in
        // PhpMyAdmin\Config::load()
        $this->load($source);

        // other settings, independent from config file, comes in
        $this->checkSystem();

        $this->baseSettings = $this->settings;
    }

    /**
     * sets system and application settings
     */
    public function checkSystem(): void
    {
        $this->checkWebServerOs();
        $this->checkWebServer();
        $this->checkGd2();
        $this->checkClient();
        $this->checkUpload();
        $this->checkUploadSize();
        $this->checkOutputCompression();
    }

    /**
     * whether to use gzip output compression or not
     */
    public function checkOutputCompression(): void
    {
        // If zlib output compression is set in the php configuration file, no
        // output buffering should be run
        if (ini_get('zlib.output_compression')) {
            $this->set('OBGzip', false);
        }

        // enable output-buffering (if set to 'auto')
        if (strtolower((string) $this->get('OBGzip')) !== 'auto') {
            return;
        }

        $this->set('OBGzip', true);
    }

    /**
     * Sets the client platform based on user agent
     *
     * @param string $user_agent the user agent
     */
    private function setClientPlatform(string $user_agent): void
    {
        if (mb_strstr($user_agent, 'Win')) {
            $this->set('PMA_USR_OS', 'Win');
        } elseif (mb_strstr($user_agent, 'Mac')) {
            $this->set('PMA_USR_OS', 'Mac');
        } elseif (mb_strstr($user_agent, 'Linux')) {
            $this->set('PMA_USR_OS', 'Linux');
        } elseif (mb_strstr($user_agent, 'Unix')) {
            $this->set('PMA_USR_OS', 'Unix');
        } elseif (mb_strstr($user_agent, 'OS/2')) {
            $this->set('PMA_USR_OS', 'OS/2');
        } else {
            $this->set('PMA_USR_OS', 'Other');
        }
    }

    /**
     * Determines platform (OS), browser and version of the user
     * Based on a phpBuilder article:
     *
     * @see http://www.phpbuilder.net/columns/tim20000821.php
     */
    public function checkClient(): void
    {
        $HTTP_USER_AGENT = '';
        if (Core::getenv('HTTP_USER_AGENT')) {
            $HTTP_USER_AGENT = Core::getenv('HTTP_USER_AGENT');
        }

        // 1. Platform
        $this->setClientPlatform($HTTP_USER_AGENT);

        // 2. browser and version
        // (must check everything else before Mozilla)

        $is_mozilla = preg_match('@Mozilla/([0-9]\.[0-9]{1,2})@', $HTTP_USER_AGENT, $mozilla_version);

        if (preg_match('@Opera(/| )([0-9]\.[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[2]);
            $this->set('PMA_USR_BROWSER_AGENT', 'OPERA');
        } elseif (preg_match('@(MS)?IE ([0-9]{1,2}\.[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[2]);
            $this->set('PMA_USR_BROWSER_AGENT', 'IE');
        } elseif (preg_match('@Trident/(7)\.0@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', intval($log_version[1]) + 4);
            $this->set('PMA_USR_BROWSER_AGENT', 'IE');
        } elseif (preg_match('@OmniWeb/([0-9]{1,3})@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'OMNIWEB');
            // Konqueror 2.2.2 says Konqueror/2.2.2
            // Konqueror 3.0.3 says Konqueror/3
        } elseif (preg_match('@(Konqueror/)(.*)(;)@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[2]);
            $this->set('PMA_USR_BROWSER_AGENT', 'KONQUEROR');
            // must check Chrome before Safari
        } elseif ($is_mozilla && preg_match('@Chrome/([0-9.]*)@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'CHROME');
            // newer Safari
        } elseif ($is_mozilla && preg_match('@Version/(.*) Safari@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'SAFARI');
            // older Safari
        } elseif ($is_mozilla && preg_match('@Safari/([0-9]*)@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', $mozilla_version[1] . '.' . $log_version[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'SAFARI');
            // Firefox
        } elseif (
            ! mb_strstr($HTTP_USER_AGENT, 'compatible')
            && preg_match('@Firefox/([\w.]+)@', $HTTP_USER_AGENT, $log_version)
        ) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'FIREFOX');
        } elseif (preg_match('@rv:1\.9(.*)Gecko@', $HTTP_USER_AGENT)) {
            $this->set('PMA_USR_BROWSER_VER', '1.9');
            $this->set('PMA_USR_BROWSER_AGENT', 'GECKO');
        } elseif ($is_mozilla) {
            $this->set('PMA_USR_BROWSER_VER', $mozilla_version[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'MOZILLA');
        } else {
            $this->set('PMA_USR_BROWSER_VER', 0);
            $this->set('PMA_USR_BROWSER_AGENT', 'OTHER');
        }
    }

    /**
     * Whether GD2 is present
     */
    public function checkGd2(): void
    {
        if ($this->get('GD2Available') === 'yes') {
            $this->set('PMA_IS_GD2', 1);

            return;
        }

        if ($this->get('GD2Available') === 'no') {
            $this->set('PMA_IS_GD2', 0);

            return;
        }

        if (! function_exists('imagecreatetruecolor')) {
            $this->set('PMA_IS_GD2', 0);

            return;
        }

        if (function_exists('gd_info')) {
            $gd_nfo = gd_info();
            if (mb_strstr($gd_nfo['GD Version'], '2.')) {
                $this->set('PMA_IS_GD2', 1);

                return;
            }
        }

        $this->set('PMA_IS_GD2', 0);
    }

    /**
     * Whether the Web server php is running on is IIS
     */
    public function checkWebServer(): void
    {
        // some versions return Microsoft-IIS, some Microsoft/IIS
        // we could use a preg_match() but it's slower
        if (
            Core::getenv('SERVER_SOFTWARE')
            && stripos(Core::getenv('SERVER_SOFTWARE'), 'Microsoft') !== false
            && stripos(Core::getenv('SERVER_SOFTWARE'), 'IIS') !== false
        ) {
            $this->set('PMA_IS_IIS', 1);

            return;
        }

        $this->set('PMA_IS_IIS', 0);
    }

    /**
     * Whether the os php is running on is windows or not
     */
    public function checkWebServerOs(): void
    {
        // Default to Unix or Equiv
        $this->set('PMA_IS_WINDOWS', false);
        // If PHP_OS is defined then continue
        if (! defined('PHP_OS')) {
            return;
        }

        if (stripos(PHP_OS, 'win') !== false && stripos(PHP_OS, 'darwin') === false) {
            // Is it some version of Windows
            $this->set('PMA_IS_WINDOWS', true);
        } elseif (stripos(PHP_OS, 'OS/2') !== false) {
            // Is it OS/2 (No file permissions like Windows)
            $this->set('PMA_IS_WINDOWS', true);
        }
    }

    /**
     * loads default values from default source
     */
    public function loadDefaults(): void
    {
        $settings = new Settings([]);
        $cfg = $settings->toArray();

        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->defaultServer = get_object_vars($settings->Servers[1]);
        unset($cfg['Servers']);

        $this->default = $cfg;
        $this->settings = array_replace_recursive($this->settings, $cfg);
    }

    /**
     * loads configuration from $source, usually the config file
     * should be called on object creation
     *
     * @param string $source config file
     */
    public function load(?string $source = null): bool
    {
        global $isConfigLoading;

        $this->loadDefaults();

        if ($source !== null) {
            $this->setSource($source);
        }

        if (! $this->checkConfigSource()) {
            return false;
        }

        $cfg = [];

        /**
         * Parses the configuration file, we throw away any errors or
         * output.
         */
        $canUseErrorReporting = Util::isErrorReportingAvailable();
        $oldErrorReporting = null;
        if ($canUseErrorReporting) {
            $oldErrorReporting = error_reporting(0);
        }

        ob_start();
        $isConfigLoading = true;
        try {
            /** @psalm-suppress UnresolvableInclude */
            $eval_result = include $this->getSource();
        } catch (Throwable $exception) {
            $eval_result = false;
        }

        $isConfigLoading = false;
        ob_end_clean();

        if ($canUseErrorReporting) {
            error_reporting($oldErrorReporting);
        }

        if ($eval_result === false) {
            $this->errorConfigFile = true;
        } else {
            $this->errorConfigFile = false;
            $this->sourceMtime = (int) filemtime($this->getSource());
        }

        /**
         * Ignore keys with / as we do not use these
         *
         * These can be confusing for user configuration layer as it
         * flatten array using / and thus don't see difference between
         * $cfg['Export/method'] and $cfg['Export']['method'], while rest
         * of the code uses the setting only in latter form.
         *
         * This could be removed once we consistently handle both values
         * in the functional code as well.
         */
        $cfg = array_filter(
            $cfg,
            static function (string $key): bool {
                return ! str_contains($key, '/');
            },
            ARRAY_FILTER_USE_KEY
        );

        $this->settings = array_replace_recursive($this->settings, $cfg);

        return true;
    }

    /**
     * Sets the connection collation
     */
    private function setConnectionCollation(): void
    {
        global $dbi;

        $collation_connection = $this->get('DefaultConnectionCollation');
        if (empty($collation_connection) || $collation_connection == $GLOBALS['collation_connection']) {
            return;
        }

        $dbi->setCollation($collation_connection);
    }

    /**
     * Loads user preferences and merges them with current config
     * must be called after control connection has been established
     */
    public function loadUserPreferences(): void
    {
        global $isMinimumCommon;

        // index.php should load these settings, so that phpmyadmin.css.php
        // will have everything available in session cache
        $server = $GLOBALS['server'] ?? (! empty($GLOBALS['cfg']['ServerDefault'])
                ? $GLOBALS['cfg']['ServerDefault']
                : 0);
        $cache_key = 'server_' . $server;
        if ($server > 0 && ! isset($isMinimumCommon)) {
            // cache user preferences, use database only when needed
            if (
                ! isset($_SESSION['cache'][$cache_key]['userprefs'])
                || $_SESSION['cache'][$cache_key]['config_mtime'] < $this->sourceMtime
            ) {
                $userPreferences = new UserPreferences();
                $prefs = $userPreferences->load();
                $_SESSION['cache'][$cache_key]['userprefs'] = $userPreferences->apply($prefs['config_data']);
                $_SESSION['cache'][$cache_key]['userprefs_mtime'] = $prefs['mtime'];
                $_SESSION['cache'][$cache_key]['userprefs_type'] = $prefs['type'];
                $_SESSION['cache'][$cache_key]['config_mtime'] = $this->sourceMtime;
            }
        } elseif ($server == 0 || ! isset($_SESSION['cache'][$cache_key]['userprefs'])) {
            $this->set('user_preferences', false);

            return;
        }

        $config_data = $_SESSION['cache'][$cache_key]['userprefs'];
        // type is 'db' or 'session'
        $this->set('user_preferences', $_SESSION['cache'][$cache_key]['userprefs_type']);
        $this->set('user_preferences_mtime', $_SESSION['cache'][$cache_key]['userprefs_mtime']);

        // load config array
        $this->settings = array_replace_recursive($this->settings, $config_data);
        $GLOBALS['cfg'] = array_replace_recursive($GLOBALS['cfg'], $config_data);

        if (isset($isMinimumCommon)) {
            return;
        }

        // settings below start really working on next page load, but
        // changes are made only in index.php so everything is set when
        // in frames

        // save theme
        $tmanager = ThemeManager::getInstance();
        if ($tmanager->getThemeCookie() || isset($_REQUEST['set_theme'])) {
            if (
                (! isset($config_data['ThemeDefault'])
                && $tmanager->theme->getId() !== 'original')
                || isset($config_data['ThemeDefault'])
                && $config_data['ThemeDefault'] != $tmanager->theme->getId()
            ) {
                $this->setUserValue(
                    null,
                    'ThemeDefault',
                    $tmanager->theme->getId(),
                    'original'
                );
            }
        } else {
            // no cookie - read default from settings
            if (
                $tmanager->theme !== null
                && $this->settings['ThemeDefault'] != $tmanager->theme->getId()
                && $tmanager->checkTheme($this->settings['ThemeDefault'])
            ) {
                $tmanager->setActiveTheme($this->settings['ThemeDefault']);
                $tmanager->setThemeCookie();
            }
        }

        // save language
        if ($this->issetCookie('pma_lang') || isset($_POST['lang'])) {
            if (
                (! isset($config_data['lang'])
                && $GLOBALS['lang'] !== 'en')
                || isset($config_data['lang'])
                && $GLOBALS['lang'] != $config_data['lang']
            ) {
                $this->setUserValue(null, 'lang', $GLOBALS['lang'], 'en');
            }
        } else {
            // read language from settings
            if (isset($config_data['lang'])) {
                $language = LanguageManager::getInstance()->getLanguage($config_data['lang']);
                if ($language !== false) {
                    $language->activate();
                    $this->setCookie('pma_lang', $language->getCode());
                }
            }
        }

        // set connection collation
        $this->setConnectionCollation();
    }

    /**
     * Sets config value which is stored in user preferences (if available)
     * or in a cookie.
     *
     * If user preferences are not yet initialized, option is applied to
     * global config and added to a update queue, which is processed
     * by {@link loadUserPreferences()}
     *
     * @param string|null $cookie_name   can be null
     * @param string      $cfg_path      configuration path
     * @param string      $new_cfg_value new value
     * @param string|null $default_value default value
     *
     * @return true|Message
     */
    public function setUserValue(
        ?string $cookie_name,
        string $cfg_path,
        $new_cfg_value,
        $default_value = null
    ) {
        $userPreferences = new UserPreferences();
        $result = true;
        // use permanent user preferences if possible
        $prefs_type = $this->get('user_preferences');
        if ($prefs_type) {
            if ($default_value === null) {
                $default_value = Core::arrayRead($cfg_path, $this->default);
            }

            $result = $userPreferences->persistOption($cfg_path, $new_cfg_value, $default_value);
        }

        if ($prefs_type !== 'db' && $cookie_name) {
            // fall back to cookies
            if ($default_value === null) {
                $default_value = Core::arrayRead($cfg_path, $this->settings);
            }

            $this->setCookie($cookie_name, $new_cfg_value, $default_value);
        }

        Core::arrayWrite($cfg_path, $GLOBALS['cfg'], $new_cfg_value);
        Core::arrayWrite($cfg_path, $this->settings, $new_cfg_value);

        return $result;
    }

    /**
     * Reads value stored by {@link setUserValue()}
     *
     * @param string $cookie_name cookie name
     * @param mixed  $cfg_value   config value
     *
     * @return mixed
     */
    public function getUserValue(string $cookie_name, $cfg_value)
    {
        $cookie_exists = ! empty($this->getCookie($cookie_name));
        $prefs_type = $this->get('user_preferences');
        if ($prefs_type === 'db') {
            // permanent user preferences value exists, remove cookie
            if ($cookie_exists) {
                $this->removeCookie($cookie_name);
            }
        } elseif ($cookie_exists) {
            return $this->getCookie($cookie_name);
        }

        // return value from $cfg array
        return $cfg_value;
    }

    /**
     * set source
     *
     * @param string $source source
     */
    public function setSource(string $source): void
    {
        $this->source = trim($source);
    }

    /**
     * check config source
     */
    public function checkConfigSource(): bool
    {
        if (! $this->getSource()) {
            // no configuration file set at all
            return false;
        }

        if (! @file_exists($this->getSource())) {
            $this->sourceMtime = 0;

            return false;
        }

        if (! @is_readable($this->getSource())) {
            // manually check if file is readable
            // might be bug #3059806 Supporting running from CIFS/Samba shares

            $contents = false;
            $handle = @fopen($this->getSource(), 'r');
            if ($handle !== false) {
                $contents = @fread($handle, 1); // reading 1 byte is enough to test
                fclose($handle);
            }

            if ($contents === false) {
                $this->sourceMtime = 0;
                Core::fatalError(
                    sprintf(
                        function_exists('__')
                        ? __('Existing configuration file (%s) is not readable.')
                        : 'Existing configuration file (%s) is not readable.',
                        $this->getSource()
                    )
                );

                return false;
            }
        }

        return true;
    }

    /**
     * verifies the permissions on config file (if asked by configuration)
     * (must be called after config.inc.php has been merged)
     */
    public function checkPermissions(): void
    {
        // Check for permissions (on platforms that support it):
        if (! $this->get('CheckConfigurationPermissions') || ! @file_exists($this->getSource())) {
            return;
        }

        $perms = @fileperms($this->getSource());
        if ($perms === false || (! ($perms & 2))) {
            return;
        }

        // This check is normally done after loading configuration
        $this->checkWebServerOs();
        if ($this->get('PMA_IS_WINDOWS') === true) {
            return;
        }

        $this->sourceMtime = 0;
        Core::fatalError(
            __(
                'Wrong permissions on configuration file, should not be world writable!'
            )
        );
    }

    /**
     * Checks for errors
     * (must be called after config.inc.php has been merged)
     */
    public function checkErrors(): void
    {
        if (! $this->errorConfigFile) {
            return;
        }

        $error = '[strong]' . __('Failed to read configuration file!') . '[/strong]'
            . '[br][br]'
            . __('This usually means there is a syntax error in it, please check any errors shown below.')
            . '[br][br]'
            . '[conferr]';
        trigger_error($error, E_USER_ERROR);
    }

    /**
     * returns specific config setting
     *
     * @param string $setting config setting
     *
     * @return mixed|null value
     */
    public function get(string $setting)
    {
        if (isset($this->settings[$setting])) {
            return $this->settings[$setting];
        }

        return null;
    }

    /**
     * sets configuration variable
     *
     * @param string $setting configuration option
     * @param mixed  $value   new value for configuration option
     */
    public function set(string $setting, $value): void
    {
        if (isset($this->settings[$setting]) && $this->settings[$setting] === $value) {
            return;
        }

        $this->settings[$setting] = $value;
        $this->setMtime = time();
    }

    /**
     * returns source for current config
     *
     * @return string  config source
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * checks if upload is enabled
     */
    public function checkUpload(): void
    {
        if (! ini_get('file_uploads')) {
            $this->set('enable_upload', false);

            return;
        }

        $this->set('enable_upload', true);
        // if set "php_admin_value file_uploads Off" in httpd.conf
        // ini_get() also returns the string "Off" in this case:
        if (strtolower((string) ini_get('file_uploads')) !== 'off') {
            return;
        }

        $this->set('enable_upload', false);
    }

    /**
     * Maximum upload size as limited by PHP
     * Used with permission from Moodle (https://moodle.org/) by Martin Dougiamas
     *
     * this section generates max_upload_size in bytes
     */
    public function checkUploadSize(): void
    {
        $fileSize = ini_get('upload_max_filesize');

        if (! $fileSize) {
            $fileSize = '5M';
        }

        $size = Core::getRealSize($fileSize);
        $postSize = ini_get('post_max_size');

        if ($postSize) {
            $size = min($size, Core::getRealSize($postSize));
        }

        $this->set('max_upload_size', $size);
    }

    /**
     * Checks if protocol is https
     *
     * This function checks if the https protocol on the active connection.
     */
    public function isHttps(): bool
    {
        if ($this->get('is_https') !== null) {
            return (bool) $this->get('is_https');
        }

        $url = $this->get('PmaAbsoluteUri');

        $is_https = false;
        if (! empty($url) && parse_url($url, PHP_URL_SCHEME) === 'https') {
            $is_https = true;
        } elseif (strtolower(Core::getenv('HTTP_SCHEME')) === 'https') {
            $is_https = true;
        } elseif (strtolower(Core::getenv('HTTPS')) === 'on') {
            $is_https = true;
        } elseif (strtolower(substr(Core::getenv('REQUEST_URI'), 0, 6)) === 'https:') {
            $is_https = true;
        } elseif (strtolower(Core::getenv('HTTP_HTTPS_FROM_LB')) === 'on') {
            // A10 Networks load balancer
            $is_https = true;
        } elseif (strtolower(Core::getenv('HTTP_FRONT_END_HTTPS')) === 'on') {
            $is_https = true;
        } elseif (strtolower(Core::getenv('HTTP_X_FORWARDED_PROTO')) === 'https') {
            $is_https = true;
        } elseif (strtolower(Core::getenv('HTTP_CLOUDFRONT_FORWARDED_PROTO')) === 'https') {
            // Amazon CloudFront, issue #15621
            $is_https = true;
        } elseif (Util::getProtoFromForwardedHeader(Core::getenv('HTTP_FORWARDED')) === 'https') {
            // RFC 7239 Forwarded header
            $is_https = true;
        } elseif (Core::getenv('SERVER_PORT') == 443) {
            $is_https = true;
        }

        $this->set('is_https', $is_https);

        return $is_https;
    }

    /**
     * Get phpMyAdmin root path
     *
     * @staticvar string|null $cookie_path
     */
    public function getRootPath(): string
    {
        static $cookie_path = null;

        if ($cookie_path !== null && ! defined('TESTSUITE')) {
            return $cookie_path;
        }

        $url = $this->get('PmaAbsoluteUri');

        if (! empty($url)) {
            $path = parse_url($url, PHP_URL_PATH);
            if (! empty($path)) {
                if (substr($path, -1) !== '/') {
                    return $path . '/';
                }

                return $path;
            }
        }

        $parsedUrlPath = parse_url($GLOBALS['PMA_PHP_SELF'], PHP_URL_PATH);

        $parts = explode(
            '/',
            rtrim(str_replace('\\', '/', $parsedUrlPath), '/')
        );

        /* Remove filename */
        if (substr($parts[count($parts) - 1], -4) === '.php') {
            $parts = array_slice($parts, 0, count($parts) - 1);
        }

        /* Remove extra path from javascript calls */
        if (defined('PMA_PATH_TO_BASEDIR')) {
            $parts = array_slice($parts, 0, count($parts) - 1);
        }

        $parts[] = '';

        return implode('/', $parts);
    }

    /**
     * removes cookie
     *
     * @param string $cookieName name of cookie to remove
     */
    public function removeCookie(string $cookieName): bool
    {
        $httpCookieName = $this->getCookieName($cookieName);

        if ($this->issetCookie($cookieName)) {
            unset($_COOKIE[$httpCookieName]);
        }

        if (defined('TESTSUITE')) {
            return true;
        }

        return setcookie(
            $httpCookieName,
            '',
            time() - 3600,
            $this->getRootPath(),
            '',
            $this->isHttps()
        );
    }

    /**
     * sets cookie if value is different from current cookie value,
     * or removes if value is equal to default
     *
     * @param string $cookie   name of cookie to remove
     * @param string $value    new cookie value
     * @param string $default  default value
     * @param int    $validity validity of cookie in seconds (default is one month)
     * @param bool   $httponly whether cookie is only for HTTP (and not for scripts)
     */
    public function setCookie(
        string $cookie,
        string $value,
        ?string $default = null,
        ?int $validity = null,
        bool $httponly = true
    ): bool {
        if (strlen($value) > 0 && $default !== null && $value === $default) {
            // default value is used
            if ($this->issetCookie($cookie)) {
                // remove cookie
                return $this->removeCookie($cookie);
            }

            return false;
        }

        if (strlen($value) === 0 && $this->issetCookie($cookie)) {
            // remove cookie, value is empty
            return $this->removeCookie($cookie);
        }

        $httpCookieName = $this->getCookieName($cookie);

        if (! $this->issetCookie($cookie) || $this->getCookie($cookie) !== $value) {
            // set cookie with new value
            /* Calculate cookie validity */
            if ($validity === null) {
                /* Valid for one month */
                $validity = time() + 2592000;
            } elseif ($validity == 0) {
                /* Valid for session */
                $validity = 0;
            } else {
                $validity = time() + $validity;
            }

            if (defined('TESTSUITE')) {
                $_COOKIE[$httpCookieName] = $value;

                return true;
            }

            /** @psalm-var 'Lax'|'Strict'|'None' $cookieSameSite */
            $cookieSameSite = $this->get('CookieSameSite');

            if (PHP_VERSION_ID < 70300) {
                return setcookie(
                    $httpCookieName,
                    $value,
                    $validity,
                    $this->getRootPath() . '; SameSite=' . $cookieSameSite,
                    '',
                    $this->isHttps(),
                    $httponly
                );
            }

            $optionalParams = [
                'expires' => $validity,
                'path' => $this->getRootPath(),
                'domain' => '',
                'secure' => $this->isHttps(),
                'httponly' => $httponly,
                'samesite' => $cookieSameSite,
            ];

            return setcookie($httpCookieName, $value, $optionalParams);
        }

        // cookie has already $value as value
        return true;
    }

    /**
     * get cookie
     *
     * @param string $cookieName The name of the cookie to get
     *
     * @return mixed|null result of getCookie()
     */
    public function getCookie(string $cookieName)
    {
        if (isset($_COOKIE[$this->getCookieName($cookieName)])) {
            return $_COOKIE[$this->getCookieName($cookieName)];
        }

        return null;
    }

    /**
     * Get the real cookie name
     *
     * @param string $cookieName The name of the cookie
     */
    public function getCookieName(string $cookieName): string
    {
        return ($this->isHttps() ? '__Secure-' : '') . $cookieName . ($this->isHttps() ? '_https' : '');
    }

    /**
     * isset cookie
     *
     * @param string $cookieName The name of the cookie to check
     */
    public function issetCookie(string $cookieName): bool
    {
        return isset($_COOKIE[$this->getCookieName($cookieName)]);
    }

    /**
     * Error handler to catch fatal errors when loading configuration
     * file
     */
    public static function fatalErrorHandler(): void
    {
        global $isConfigLoading;

        if (! isset($isConfigLoading) || ! $isConfigLoading) {
            return;
        }

        $error = error_get_last();
        if ($error === null) {
            return;
        }

        Core::fatalError(
            sprintf(
                'Failed to load phpMyAdmin configuration (%s:%s): %s',
                Error::relPath($error['file']),
                $error['line'],
                $error['message']
            )
        );
    }

    /**
     * Wrapper for footer/header rendering
     *
     * @param string $filename File to check and render
     * @param string $id       Div ID
     */
    private static function renderCustom(string $filename, string $id): string
    {
        $retval = '';
        if (@file_exists($filename)) {
            $retval .= '<div id="' . $id . '" class="d-print-none">';
            ob_start();
            include $filename;
            $retval .= ob_get_clean();
            $retval .= '</div>';
        }

        return $retval;
    }

    /**
     * Renders user configured footer
     */
    public static function renderFooter(): string
    {
        return self::renderCustom(CUSTOM_FOOTER_FILE, 'pma_footer');
    }

    /**
     * Renders user configured footer
     */
    public static function renderHeader(): string
    {
        return self::renderCustom(CUSTOM_HEADER_FILE, 'pma_header');
    }

    /**
     * Returns temporary dir path
     *
     * @param string $name Directory name
     *
     * @staticvar array<string,string|null> $temp_dir
     */
    public function getTempDir(string $name): ?string
    {
        static $temp_dir = [];

        if (isset($temp_dir[$name]) && ! defined('TESTSUITE')) {
            return $temp_dir[$name];
        }

        $path = $this->get('TempDir');
        if (empty($path)) {
            $path = null;
        } else {
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
            if (! @is_dir($path)) {
                @mkdir($path, 0770, true);
            }

            if (! @is_dir($path) || ! @is_writable($path)) {
                $path = null;
            }
        }

        $temp_dir[$name] = $path;

        return $path;
    }

    /**
     * Returns temporary directory
     */
    public function getUploadTempDir(): ?string
    {
        // First try configured temp dir
        // Fallback to PHP upload_tmp_dir
        $dirs = [
            $this->getTempDir('upload'),
            ini_get('upload_tmp_dir'),
            sys_get_temp_dir(),
        ];

        foreach ($dirs as $dir) {
            if (! empty($dir) && @is_writable($dir)) {
                return realpath($dir);
            }
        }

        return null;
    }

    /**
     * Selects server based on request parameters.
     */
    public function selectServer(): int
    {
        $request = empty($_REQUEST['server']) ? 0 : $_REQUEST['server'];

        /**
         * Lookup server by name
         * (see FAQ 4.8)
         */
        if (! is_numeric($request)) {
            foreach ($this->settings['Servers'] as $i => $server) {
                $verboseToLower = mb_strtolower($server['verbose']);
                $serverToLower = mb_strtolower($request);
                if (
                    $server['host'] == $request
                    || $server['verbose'] == $request
                    || $verboseToLower == $serverToLower
                    || md5($verboseToLower) === $serverToLower
                ) {
                    $request = $i;
                    break;
                }
            }

            if (is_string($request)) {
                $request = 0;
            }
        }

        /**
         * If no server is selected, make sure that $this->settings['Server'] is empty (so
         * that nothing will work), and skip server authentication.
         * We do NOT exit here, but continue on without logging into any server.
         * This way, the welcome page will still come up (with no server info) and
         * present a choice of servers in the case that there are multiple servers
         * and '$this->settings['ServerDefault'] = 0' is set.
         */

        if (is_numeric($request) && ! empty($request) && ! empty($this->settings['Servers'][$request])) {
            $server = $request;
            $this->settings['Server'] = $this->settings['Servers'][$server];
        } else {
            if (! empty($this->settings['Servers'][$this->settings['ServerDefault']])) {
                $server = $this->settings['ServerDefault'];
                $this->settings['Server'] = $this->settings['Servers'][$server];
            } else {
                $server = 0;
                $this->settings['Server'] = [];
            }
        }

        return (int) $server;
    }

    /**
     * Checks whether Servers configuration is valid and possibly apply fixups.
     */
    public function checkServers(): void
    {
        // Do we have some server?
        if (! isset($this->settings['Servers']) || count($this->settings['Servers']) === 0) {
            // No server => create one with defaults
            $this->settings['Servers'] = [1 => $this->defaultServer];

            return;
        }

        // We have server(s) => apply default configuration
        $new_servers = [];

        foreach ($this->settings['Servers'] as $server_index => $each_server) {
            // Detect wrong configuration
            if (! is_int($server_index) || $server_index < 1) {
                trigger_error(
                    sprintf(__('Invalid server index: %s'), $server_index),
                    E_USER_ERROR
                );
            }

            $each_server = array_merge($this->defaultServer, $each_server);

            // Final solution to bug #582890
            // If we are using a socket connection
            // and there is nothing in the verbose server name
            // or the host field, then generate a name for the server
            // in the form of "Server 2", localized of course!
            if (empty($each_server['host']) && empty($each_server['verbose'])) {
                $each_server['verbose'] = sprintf(__('Server %d'), $server_index);
            }

            $new_servers[$server_index] = $each_server;
        }

        $this->settings['Servers'] = $new_servers;
    }

    /**
     * Return connection parameters for the database server
     *
     * @param int        $mode   Connection mode on of CONNECT_USER, CONNECT_CONTROL
     *                           or CONNECT_AUXILIARY.
     * @param array|null $server Server information like host/port/socket/persistent
     *
     * @return array user, host and server settings array
     */
    public static function getConnectionParams(int $mode, ?array $server = null): array
    {
        global $cfg;

        $user = null;
        $password = null;

        if ($mode == DatabaseInterface::CONNECT_USER) {
            $user = $cfg['Server']['user'];
            $password = $cfg['Server']['password'];
            $server = $cfg['Server'];
        } elseif ($mode == DatabaseInterface::CONNECT_CONTROL) {
            $user = $cfg['Server']['controluser'];
            $password = $cfg['Server']['controlpass'];

            $server = [];

            $server['hide_connection_errors'] = $cfg['Server']['hide_connection_errors'];

            if (! empty($cfg['Server']['controlhost'])) {
                $server['host'] = $cfg['Server']['controlhost'];
            } else {
                $server['host'] = $cfg['Server']['host'];
            }

            // Share the settings if the host is same
            if ($server['host'] == $cfg['Server']['host']) {
                $shared = [
                    'port',
                    'socket',
                    'compress',
                    'ssl',
                    'ssl_key',
                    'ssl_cert',
                    'ssl_ca',
                    'ssl_ca_path',
                    'ssl_ciphers',
                    'ssl_verify',
                ];
                foreach ($shared as $item) {
                    if (! isset($cfg['Server'][$item])) {
                        continue;
                    }

                    $server[$item] = $cfg['Server'][$item];
                }
            }

            // Set configured port
            if (! empty($cfg['Server']['controlport'])) {
                $server['port'] = $cfg['Server']['controlport'];
            }

            // Set any configuration with control_ prefix
            foreach ($cfg['Server'] as $key => $val) {
                if (substr($key, 0, 8) !== 'control_') {
                    continue;
                }

                $server[substr($key, 8)] = $val;
            }
        } else {
            if ($server === null) {
                return [
                    null,
                    null,
                    null,
                ];
            }

            if (isset($server['user'])) {
                $user = $server['user'];
            }

            if (isset($server['password'])) {
                $password = $server['password'];
            }
        }

        // Perform sanity checks on some variables
        $server['port'] = empty($server['port']) ? 0 : (int) $server['port'];

        if (empty($server['socket'])) {
            $server['socket'] = null;
        }

        if (empty($server['host'])) {
            $server['host'] = 'localhost';
        }

        if (! isset($server['ssl'])) {
            $server['ssl'] = false;
        }

        if (! isset($server['compress'])) {
            $server['compress'] = false;
        }

        if (! isset($server['hide_connection_errors'])) {
            $server['hide_connection_errors'] = false;
        }

        return [
            $user,
            $password,
            $server,
        ];
    }

    /**
     * Get LoginCookieValidity from preferences cache.
     *
     * No generic solution for loading preferences from cache as some settings
     * need to be kept for processing in loadUserPreferences().
     *
     * @see loadUserPreferences()
     */
    public function getLoginCookieValidityFromCache(int $server): void
    {
        global $cfg;

        $cacheKey = 'server_' . $server;

        if (! isset($_SESSION['cache'][$cacheKey]['userprefs']['LoginCookieValidity'])) {
            return;
        }

        $value = $_SESSION['cache'][$cacheKey]['userprefs']['LoginCookieValidity'];
        $this->set('LoginCookieValidity', $value);
        $cfg['LoginCookieValidity'] = $value;
    }
}
