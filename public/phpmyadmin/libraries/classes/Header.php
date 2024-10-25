<?php
/**
 * Used to render the header of PMA's pages
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Navigation\Navigation;

use function array_merge;
use function defined;
use function gmdate;
use function header;
use function htmlspecialchars;
use function implode;
use function ini_get;
use function is_bool;
use function sprintf;
use function strlen;
use function strtolower;
use function urlencode;

/**
 * Class used to output the HTTP and HTML headers
 */
class Header
{
    /**
     * Scripts instance
     *
     * @var Scripts
     */
    private $scripts;
    /**
     * PhpMyAdmin\Console instance
     *
     * @var Console
     */
    private $console;
    /**
     * Menu instance
     *
     * @var Menu
     */
    private $menu;
    /**
     * The page title
     *
     * @var string
     */
    private $title;
    /**
     * The value for the id attribute for the body tag
     *
     * @var string
     */
    private $bodyId;
    /**
     * Whether to show the top menu
     *
     * @var bool
     */
    private $menuEnabled;
    /**
     * Whether to show the warnings
     *
     * @var bool
     */
    private $warningsEnabled;
    /**
     * Whether we are servicing an ajax request.
     *
     * @var bool
     */
    private $isAjax;
    /**
     * Whether to display anything
     *
     * @var bool
     */
    private $isEnabled;
    /**
     * Whether the HTTP headers (and possibly some HTML)
     * have already been sent to the browser
     *
     * @var bool
     */
    private $headerIsSent;

    /** @var UserPreferences */
    private $userPreferences;

    /** @var Template */
    private $template;

    /**
     * Creates a new class instance
     */
    public function __construct()
    {
        global $db, $table, $dbi;

        $this->template = new Template();

        $this->isEnabled = true;
        $this->isAjax = false;
        $this->bodyId = '';
        $this->title = '';
        $this->console = new Console();
        $this->menu = new Menu($dbi, $db ?? '', $table ?? '');
        $this->menuEnabled = true;
        $this->warningsEnabled = true;
        $this->scripts = new Scripts();
        $this->addDefaultScripts();
        $this->headerIsSent = false;

        $this->userPreferences = new UserPreferences();
    }

    /**
     * Loads common scripts
     */
    private function addDefaultScripts(): void
    {
        // Localised strings
        $this->scripts->addFile('vendor/jquery/jquery.min.js');
        $this->scripts->addFile('vendor/jquery/jquery-migrate.min.js');
        $this->scripts->addFile('vendor/sprintf.js');
        $this->scripts->addFile('ajax.js');
        $this->scripts->addFile('keyhandler.js');
        $this->scripts->addFile('vendor/jquery/jquery-ui.min.js');
        $this->scripts->addFile('name-conflict-fixes.js');
        $this->scripts->addFile('vendor/bootstrap/bootstrap.bundle.min.js');
        $this->scripts->addFile('vendor/js.cookie.js');
        $this->scripts->addFile('vendor/jquery/jquery.validate.min.js');
        $this->scripts->addFile('vendor/jquery/jquery-ui-timepicker-addon.js');
        $this->scripts->addFile('vendor/jquery/jquery.debounce-1.0.6.js');
        $this->scripts->addFile('menu_resizer.js');

        // Cross-framing protection
        // At this point browser settings are not merged
        // this is good that we only use file configuration for this protection
        if ($GLOBALS['cfg']['AllowThirdPartyFraming'] === false) {
            $this->scripts->addFile('cross_framing_protection.js');
        }

        // Here would not be a good place to add CodeMirror because
        // the user preferences have not been merged at this point

        $this->scripts->addFile('messages.php', ['l' => $GLOBALS['lang']]);
        $this->scripts->addFile('config.js');
        $this->scripts->addFile('doclinks.js');
        $this->scripts->addFile('functions.js');
        $this->scripts->addFile('navigation.js');
        $this->scripts->addFile('indexes.js');
        $this->scripts->addFile('common.js');
        $this->scripts->addFile('page_settings.js');

        $this->scripts->addCode($this->getJsParamsCode());
    }

    /**
     * Returns, as an array, a list of parameters
     * used on the client side
     *
     * @return array
     */
    public function getJsParams(): array
    {
        global $db, $table, $dbi;

        $pftext = $_SESSION['tmpval']['pftext'] ?? '';

        $params = [
            // Do not add any separator, JS code will decide
            'common_query' => Url::getCommonRaw([], ''),
            'opendb_url' => Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database'),
            'lang' => $GLOBALS['lang'],
            'server' => $GLOBALS['server'],
            'table' => $table ?? '',
            'db' => $db ?? '',
            'token' => $_SESSION[' PMA_token '],
            'text_dir' => $GLOBALS['text_dir'],
            'LimitChars' => $GLOBALS['cfg']['LimitChars'],
            'pftext' => $pftext,
            'confirm' => $GLOBALS['cfg']['Confirm'],
            'LoginCookieValidity' => $GLOBALS['cfg']['LoginCookieValidity'],
            'session_gc_maxlifetime' => (int) ini_get('session.gc_maxlifetime'),
            'logged_in' => isset($dbi) ? $dbi->isConnected() : false,
            'is_https' => $GLOBALS['config']->isHttps(),
            'rootPath' => $GLOBALS['config']->getRootPath(),
            'arg_separator' => Url::getArgSeparator(),
            'version' => Version::VERSION,
        ];
        if (isset($GLOBALS['cfg']['Server'], $GLOBALS['cfg']['Server']['auth_type'])) {
            $params['auth_type'] = $GLOBALS['cfg']['Server']['auth_type'];
            if (isset($GLOBALS['cfg']['Server']['user'])) {
                $params['user'] = $GLOBALS['cfg']['Server']['user'];
            }
        }

        return $params;
    }

    /**
     * Returns, as a string, a list of parameters
     * used on the client side
     */
    public function getJsParamsCode(): string
    {
        $params = $this->getJsParams();
        foreach ($params as $key => $value) {
            if (is_bool($value)) {
                $params[$key] = $key . ':' . ($value ? 'true' : 'false') . '';
            } else {
                $params[$key] = $key . ':"' . Sanitize::escapeJsString($value) . '"';
            }
        }

        return 'CommonParams.setAll({' . implode(',', $params) . '});';
    }

    /**
     * Disables the rendering of the header
     */
    public function disable(): void
    {
        $this->isEnabled = false;
    }

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     */
    public function setAjax(bool $isAjax): void
    {
        $this->isAjax = $isAjax;
        $this->console->setAjax($isAjax);
    }

    /**
     * Returns the Scripts object
     *
     * @return Scripts object
     */
    public function getScripts(): Scripts
    {
        return $this->scripts;
    }

    /**
     * Returns the Menu object
     *
     * @return Menu object
     */
    public function getMenu(): Menu
    {
        return $this->menu;
    }

    /**
     * Setter for the ID attribute in the BODY tag
     *
     * @param string $id Value for the ID attribute
     */
    public function setBodyId(string $id): void
    {
        $this->bodyId = htmlspecialchars($id);
    }

    /**
     * Setter for the title of the page
     *
     * @param string $title New title
     */
    public function setTitle(string $title): void
    {
        $this->title = htmlspecialchars($title);
    }

    /**
     * Disables the display of the top menu
     */
    public function disableMenuAndConsole(): void
    {
        $this->menuEnabled = false;
        $this->console->disable();
    }

    /**
     * Disables the display of the top menu
     */
    public function disableWarnings(): void
    {
        $this->warningsEnabled = false;
    }

    /**
     * Generates the header
     *
     * @return string The header
     */
    public function getDisplay(): string
    {
        global $db, $table, $theme, $dbi;

        if ($this->headerIsSent || ! $this->isEnabled) {
            return '';
        }

        $recentTable = '';
        if (empty($_REQUEST['recent_table'])) {
            $recentTable = $this->addRecentTable($db, $table);
        }

        if ($this->isAjax) {
            return $recentTable;
        }

        $this->sendHttpHeaders();

        $baseDir = defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : '';
        $themePath = $theme instanceof Theme ? $theme->getPath() : '';
        $version = self::getVersionParameter();

        // The user preferences have been merged at this point
        // so we can conditionally add CodeMirror, other scripts and settings
        if ($GLOBALS['cfg']['CodemirrorEnable']) {
            $this->scripts->addFile('vendor/codemirror/lib/codemirror.js');
            $this->scripts->addFile('vendor/codemirror/mode/sql/sql.js');
            $this->scripts->addFile('vendor/codemirror/addon/runmode/runmode.js');
            $this->scripts->addFile('vendor/codemirror/addon/hint/show-hint.js');
            $this->scripts->addFile('vendor/codemirror/addon/hint/sql-hint.js');
            if ($GLOBALS['cfg']['LintEnable']) {
                $this->scripts->addFile('vendor/codemirror/addon/lint/lint.js');
                $this->scripts->addFile('codemirror/addon/lint/sql-lint.js');
            }
        }

        if ($GLOBALS['cfg']['SendErrorReports'] !== 'never') {
            $this->scripts->addFile('vendor/tracekit.js');
            $this->scripts->addFile('error_report.js');
        }

        if ($GLOBALS['cfg']['enable_drag_drop_import'] === true) {
            $this->scripts->addFile('drag_drop_import.js');
        }

        if (! $GLOBALS['config']->get('DisableShortcutKeys')) {
            $this->scripts->addFile('shortcuts_handler.js');
        }

        $this->scripts->addCode($this->getVariablesForJavaScript());

        $this->scripts->addCode('ConsoleEnterExecutes=' . ($GLOBALS['cfg']['ConsoleEnterExecutes'] ? 'true' : 'false'));
        $this->scripts->addFiles($this->console->getScripts());

        // if database storage for user preferences is transient,
        // offer to load exported settings from localStorage
        // (detection will be done in JavaScript)
        $userprefsOfferImport = false;
        if (
            $GLOBALS['config']->get('user_preferences') === 'session'
            && ! isset($_SESSION['userprefs_autoload'])
        ) {
            $userprefsOfferImport = true;
        }

        if ($userprefsOfferImport) {
            $this->scripts->addFile('config.js');
        }

        if ($this->menuEnabled && $GLOBALS['server'] > 0) {
            $nav = new Navigation(
                $this->template,
                new Relation($dbi),
                $dbi
            );
            $navigation = $nav->getDisplay();
        }

        $customHeader = Config::renderHeader();

        // offer to load user preferences from localStorage
        if ($userprefsOfferImport) {
            $loadUserPreferences = $this->userPreferences->autoloadGetHeader();
        }

        if ($this->menuEnabled && $GLOBALS['server'] > 0) {
            $menu = $this->menu->getDisplay();
        }

        $console = $this->console->getDisplay();
        $messages = $this->getMessage();
        $isLoggedIn = isset($GLOBALS['dbi']) && $GLOBALS['dbi']->isConnected();

        return $this->template->render('header', [
            'lang' => $GLOBALS['lang'],
            'allow_third_party_framing' => $GLOBALS['cfg']['AllowThirdPartyFraming'],
            'base_dir' => $baseDir,
            'theme_path' => $themePath,
            'version' => $version,
            'text_dir' => $GLOBALS['text_dir'],
            'server' => $GLOBALS['server'] ?? null,
            'title' => $this->getPageTitle(),
            'scripts' => $this->scripts->getDisplay(),
            'body_id' => $this->bodyId,
            'navigation' => $navigation ?? '',
            'custom_header' => $customHeader,
            'load_user_preferences' => $loadUserPreferences ?? '',
            'show_hint' => $GLOBALS['cfg']['ShowHint'],
            'is_warnings_enabled' => $this->warningsEnabled,
            'is_menu_enabled' => $this->menuEnabled,
            'is_logged_in' => $isLoggedIn,
            'menu' => $menu ?? '',
            'console' => $console,
            'messages' => $messages,
            'recent_table' => $recentTable,
        ]);
    }

    /**
     * Returns the message to be displayed at the top of
     * the page, including the executed SQL query, if any.
     */
    public function getMessage(): string
    {
        $retval = '';
        $message = '';
        if (! empty($GLOBALS['message'])) {
            $message = $GLOBALS['message'];
            unset($GLOBALS['message']);
        } elseif (! empty($_REQUEST['message'])) {
            $message = $_REQUEST['message'];
        }

        if (! empty($message)) {
            if (isset($GLOBALS['buffer_message'])) {
                $bufferMessage = $GLOBALS['buffer_message'];
            }

            $retval .= Generator::getMessage($message);
            if (isset($bufferMessage)) {
                $GLOBALS['buffer_message'] = $bufferMessage;
            }
        }

        return $retval;
    }

    /**
     * Sends out the HTTP headers
     */
    public function sendHttpHeaders(): void
    {
        if (defined('TESTSUITE')) {
            return;
        }

        /**
         * Sends http headers
         */
        $GLOBALS['now'] = gmdate('D, d M Y H:i:s') . ' GMT';

        $headers = $this->getHttpHeaders();

        foreach ($headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        $this->headerIsSent = true;
    }

    /**
     * @return array<string, string>
     */
    private function getHttpHeaders(): array
    {
        $headers = [];

        /* Prevent against ClickJacking by disabling framing */
        if (strtolower((string) $GLOBALS['cfg']['AllowThirdPartyFraming']) === 'sameorigin') {
            $headers['X-Frame-Options'] = 'SAMEORIGIN';
        } elseif ($GLOBALS['cfg']['AllowThirdPartyFraming'] !== true) {
            $headers['X-Frame-Options'] = 'DENY';
        }

        $headers['Referrer-Policy'] = 'no-referrer';

        $headers = array_merge($headers, $this->getCspHeaders());

        /**
         * Re-enable possible disabled XSS filters.
         *
         * @see https://www.owasp.org/index.php/List_of_useful_HTTP_headers
         */
        $headers['X-XSS-Protection'] = '1; mode=block';

        /**
         * "nosniff", prevents Internet Explorer and Google Chrome from MIME-sniffing
         * a response away from the declared content-type.
         *
         * @see https://www.owasp.org/index.php/List_of_useful_HTTP_headers
         */
        $headers['X-Content-Type-Options'] = 'nosniff';

        /**
         * Adobe cross-domain-policies.
         *
         * @see https://www.adobe.com/devnet/articles/crossdomain_policy_file_spec.html
         */
        $headers['X-Permitted-Cross-Domain-Policies'] = 'none';

        /**
         * Robots meta tag.
         *
         * @see https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag
         */
        $headers['X-Robots-Tag'] = 'noindex, nofollow';

        $headers = array_merge($headers, Core::getNoCacheHeaders());

        if (! defined('IS_TRANSFORMATION_WRAPPER')) {
            // Define the charset to be used
            $headers['Content-Type'] = 'text/html; charset=utf-8';
        }

        return $headers;
    }

    /**
     * If the page is missing the title, this function
     * will set it to something reasonable
     */
    public function getPageTitle(): string
    {
        if (strlen($this->title) == 0) {
            if ($GLOBALS['server'] > 0) {
                if (strlen($GLOBALS['table'])) {
                    $tempTitle = $GLOBALS['cfg']['TitleTable'];
                } elseif (strlen($GLOBALS['db'])) {
                    $tempTitle = $GLOBALS['cfg']['TitleDatabase'];
                } elseif (strlen($GLOBALS['cfg']['Server']['host'])) {
                    $tempTitle = $GLOBALS['cfg']['TitleServer'];
                } else {
                    $tempTitle = $GLOBALS['cfg']['TitleDefault'];
                }

                $this->title = htmlspecialchars(
                    Util::expandUserString($tempTitle)
                );
            } else {
                $this->title = 'phpMyAdmin';
            }
        }

        return $this->title;
    }

    /**
     * Get all the CSP allow policy headers
     *
     * @return array<string, string>
     */
    private function getCspHeaders(): array
    {
        global $cfg;

        $mapTileUrls = ' *.tile.openstreetmap.org';
        $captchaUrl = '';
        $cspAllow = $cfg['CSPAllow'];

        if (
            ! empty($cfg['CaptchaLoginPrivateKey'])
            && ! empty($cfg['CaptchaLoginPublicKey'])
            && ! empty($cfg['CaptchaApi'])
            && ! empty($cfg['CaptchaRequestParam'])
            && ! empty($cfg['CaptchaResponseParam'])
        ) {
            $captchaUrl = ' ' . $cfg['CaptchaCsp'] . ' ';
        }

        $headers = [];

        $headers['Content-Security-Policy'] = sprintf(
            'default-src \'self\' %s%s;script-src \'self\' \'unsafe-inline\' \'unsafe-eval\' %s%s;'
                . 'style-src \'self\' \'unsafe-inline\' %s%s;img-src \'self\' data: %s%s%s;object-src \'none\';',
            $captchaUrl,
            $cspAllow,
            $captchaUrl,
            $cspAllow,
            $captchaUrl,
            $cspAllow,
            $cspAllow,
            $mapTileUrls,
            $captchaUrl
        );

        $headers['X-Content-Security-Policy'] = sprintf(
            'default-src \'self\' %s%s;options inline-script eval-script;'
                . 'referrer no-referrer;img-src \'self\' data: %s%s%s;object-src \'none\';',
            $captchaUrl,
            $cspAllow,
            $cspAllow,
            $mapTileUrls,
            $captchaUrl
        );

        $headers['X-WebKit-CSP'] = sprintf(
            'default-src \'self\' %s%s;script-src \'self\' %s%s \'unsafe-inline\' \'unsafe-eval\';'
                . 'referrer no-referrer;style-src \'self\' \'unsafe-inline\' %s;'
                . 'img-src \'self\' data: %s%s%s;object-src \'none\';',
            $captchaUrl,
            $cspAllow,
            $captchaUrl,
            $cspAllow,
            $captchaUrl,
            $cspAllow,
            $mapTileUrls,
            $captchaUrl
        );

        return $headers;
    }

    /**
     * Add recently used table and reload the navigation.
     *
     * @param string $db    Database name where the table is located.
     * @param string $table The table name
     */
    private function addRecentTable(string $db, string $table): string
    {
        $retval = '';
        if ($this->menuEnabled && strlen($table) > 0 && $GLOBALS['cfg']['NumRecentTables'] > 0) {
            $tmpResult = RecentFavoriteTable::getInstance('recent')->add($db, $table);
            if ($tmpResult === true) {
                $retval = RecentFavoriteTable::getHtmlUpdateRecentTables();
            } else {
                $error = $tmpResult;
                $retval = $error->getDisplay();
            }
        }

        return $retval;
    }

    /**
     * Returns the phpMyAdmin version to be appended to the url to avoid caching
     * between versions
     *
     * @return string urlencoded pma version as a parameter
     */
    public static function getVersionParameter(): string
    {
        return 'v=' . urlencode(Version::VERSION);
    }

    private function getVariablesForJavaScript(): string
    {
        global $cfg;

        $maxInputVars = ini_get('max_input_vars');
        $maxInputVarsValue = $maxInputVars === false || $maxInputVars === '' ? 'false' : (int) $maxInputVars;

        return $this->template->render('javascript/variables', [
            'first_day_of_calendar' => $cfg['FirstDayOfCalendar'] ?? 0,
            'max_input_vars' => $maxInputVarsValue,
        ]);
    }
}
