<?php
/**
 * HTML Generator
 */

declare(strict_types=1);

namespace PhpMyAdmin\Html;

use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Profiling;
use PhpMyAdmin\Providers\ServerVariables\ServerVariablesProvider;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Utils\Error as ParserError;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use function __;
use function _pgettext;
use function addslashes;
use function array_key_exists;
use function ceil;
use function count;
use function explode;
use function htmlentities;
use function htmlspecialchars;
use function implode;
use function in_array;
use function ini_get;
use function intval;
use function is_array;
use function mb_strlen;
use function mb_strstr;
use function mb_strtolower;
use function mb_substr;
use function nl2br;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtoupper;
use function substr;
use function trim;

use const ENT_COMPAT;

/**
 * HTML Generator
 */
class Generator
{
    /**
     * Displays a button to copy content to clipboard
     *
     * @param string $text Text to copy to clipboard
     *
     * @return string  the html link
     */
    public static function showCopyToClipboard(string $text): string
    {
        return '  <a href="#" class="copyQueryBtn" data-text="'
            . htmlspecialchars($text) . '">' . __('Copy') . '</a>';
    }

    /**
     * Get a link to variable documentation
     *
     * @param string $name       The variable name
     * @param bool   $useMariaDB Use only MariaDB documentation
     * @param string $text       (optional) The text for the link
     *
     * @return string link or empty string
     */
    public static function linkToVarDocumentation(
        string $name,
        bool $useMariaDB = false,
        ?string $text = null
    ): string {
        $kbs = ServerVariablesProvider::getImplementation();
        $link = $useMariaDB ? $kbs->getDocLinkByNameMariaDb($name) :
                            $kbs->getDocLinkByNameMysql($name);
        $link = $link !== null ? Core::linkURL($link) : $link;

        return MySQLDocumentation::show($name, false, $link, $text);
    }

    /**
     * Returns HTML code for a tooltip
     *
     * @param string $message the message for the tooltip
     */
    public static function showHint(string $message): string
    {
        if ($GLOBALS['cfg']['ShowHint']) {
            $classClause = ' class="pma_hint"';
        } else {
            $classClause = '';
        }

        return '<span' . $classClause . '>'
            . self::getImage('b_help')
            . '<span class="hide">' . $message . '</span>'
            . '</span>';
    }

    /**
     * returns html code for db link to default db page
     *
     * @param string $database database
     *
     * @return string  html link to default db page
     */
    public static function getDbLink($database = ''): string
    {
        if ((string) $database === '') {
            if ((string) $GLOBALS['db'] === '') {
                return '';
            }

            $database = $GLOBALS['db'];
        } else {
            $database = Util::unescapeMysqlWildcards($database);
        }

        $scriptName = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');

        return '<a href="'
            . $scriptName
            . Url::getCommon(['db' => $database], ! str_contains($scriptName, '?') ? '?' : '&')
            . '" title="'
            . htmlspecialchars(
                sprintf(
                    __('Jump to database “%s”.'),
                    $database
                )
            )
            . '">' . htmlspecialchars($database) . '</a>';
    }

    /**
     * Prepare a lightbulb hint explaining a known external bug
     * that affects a functionality
     *
     * @param string $functionality  localized message explaining the func.
     * @param string $component      'mysql' (eventually, 'php')
     * @param string $minimumVersion of this component
     * @param string $bugReference   bug reference for this component
     */
    public static function getExternalBug(
        $functionality,
        $component,
        $minimumVersion,
        $bugReference
    ): string {
        global $dbi;

        $return = '';
        if (($component === 'mysql') && ($dbi->getVersion() < $minimumVersion)) {
            $return .= self::showHint(
                sprintf(
                    __('The %s functionality is affected by a known bug, see %s'),
                    $functionality,
                    Core::linkURL('https://bugs.mysql.com/') . $bugReference
                )
            );
        }

        return $return;
    }

    /**
     * Returns an HTML IMG tag for a particular icon from a theme,
     * which may be an actual file or an icon from a sprite.
     * This function takes into account the ActionLinksMode
     * configuration setting and wraps the image tag in a span tag.
     *
     * @param string $icon         name of icon file
     * @param string $alternate    alternate text
     * @param bool   $forceText    whether to force alternate text to be displayed
     * @param bool   $menuIcon     whether this icon is for the menu bar or not
     * @param string $controlParam which directive controls the display
     *
     * @return string an html snippet
     */
    public static function getIcon(
        $icon,
        $alternate = '',
        $forceText = false,
        $menuIcon = false,
        $controlParam = 'ActionLinksMode'
    ): string {
        $includeIcon = $includeText = false;
        if (Util::showIcons($controlParam)) {
            $includeIcon = true;
        }

        if ($forceText || Util::showText($controlParam)) {
            $includeText = true;
        }

        // Sometimes use a span (we rely on this in js/sql.js). But for menu bar
        // we don't need a span
        $button = $menuIcon ? '' : '<span class="text-nowrap">';
        if ($includeIcon) {
            $button .= self::getImage($icon, $alternate);
        }

        if ($includeIcon && $includeText) {
            $button .= '&nbsp;';
        }

        if ($includeText) {
            $button .= $alternate;
        }

        $button .= $menuIcon ? '' : '</span>';

        return $button;
    }

    /**
     * Returns information about SSL status for current connection
     */
    public static function getServerSSL(): string
    {
        $server = $GLOBALS['cfg']['Server'];
        $class = 'text-danger';
        if (! $server['ssl']) {
            $message = __('SSL is not being used');
            if (! empty($server['socket']) || in_array($server['host'], $GLOBALS['cfg']['MysqlSslWarningSafeHosts'])) {
                $class = '';
            }
        } elseif (! $server['ssl_verify']) {
            $message = __('SSL is used with disabled verification');
        } elseif (empty($server['ssl_ca'])) {
            $message = __('SSL is used without certification authority');
        } else {
            $class = '';
            $message = __('SSL is used');
        }

        return '<span class="' . $class . '">' . $message . '</span> ' . MySQLDocumentation::showDocumentation(
            'setup',
            'ssl'
        );
    }

    /**
     * Returns default function for a particular column.
     *
     * @param array $field      Data about the column for which
     *                          to generate the dropdown
     * @param bool  $insertMode Whether the operation is 'insert'
     *
     * @return string An HTML snippet of a dropdown list with function
     *                names appropriate for the requested column.
     *
     * @global mixed $data data of currently edited row
     *                     (used to detect whether to choose defaults)
     * @global array $cfg  PMA configuration
     */
    public static function getDefaultFunctionForField(array $field, $insertMode): string
    {
        global $cfg, $data, $dbi;

        $defaultFunction = '';

        // Can we get field class based values?
        $currentClass = $dbi->types->getTypeClass($field['True_Type']);
        if (! empty($currentClass) && isset($cfg['DefaultFunctions']['FUNC_' . $currentClass])) {
            $defaultFunction = $cfg['DefaultFunctions']['FUNC_' . $currentClass];
            // Change the configured default function to include the ST_ prefix with MySQL 5.6 and later.
            // It needs to match the function listed in the select html element.
            if (
                $currentClass === 'SPATIAL' &&
                $dbi->getVersion() >= 50600 &&
                strtoupper(substr($defaultFunction, 0, 3)) !== 'ST_'
            ) {
                $defaultFunction = 'ST_' . $defaultFunction;
            }
        }

        // what function defined as default?
        // for the first timestamp we don't set the default function
        // if there is a default value for the timestamp
        // (not including CURRENT_TIMESTAMP)
        // and the column does not have the
        // ON UPDATE DEFAULT TIMESTAMP attribute.
        if (
            ($field['True_Type'] === 'timestamp')
            && $field['first_timestamp']
            && empty($field['Default'])
            && empty($data)
            && $field['Extra'] !== 'on update CURRENT_TIMESTAMP'
            && $field['Null'] === 'NO'
        ) {
            $defaultFunction = $cfg['DefaultFunctions']['first_timestamp'];
        }

        // For uuid field, no default function
        if ($field['True_Type'] === 'uuid') {
            return '';
        }

        // For primary keys of type char(36) or varchar(36) UUID if the default
        // function
        // Only applies to insert mode, as it would silently trash data on updates.
        if (
            $insertMode
            && $field['Key'] === 'PRI'
            && ($field['Type'] === 'char(36)' || $field['Type'] === 'varchar(36)')
        ) {
            $defaultFunction = $cfg['DefaultFunctions']['FUNC_UUID'];
        }

        return $defaultFunction;
    }

    /**
     * Creates a dropdown box with MySQL functions for a particular column.
     *
     * @param array $field       Data about the column for which to generate the dropdown
     * @param bool  $insertMode  Whether the operation is 'insert'
     * @param array $foreignData Foreign data
     *
     * @return string An HTML snippet of a dropdown list with function names appropriate for the requested column.
     */
    public static function getFunctionsForField(array $field, $insertMode, array $foreignData): string
    {
        global $dbi;

        $defaultFunction = self::getDefaultFunctionForField($field, $insertMode);

        // Create the output
        $retval = '<option></option>' . "\n";
        // loop on the dropdown array and print all available options for that
        // field.
        $functions = $dbi->types->getAllFunctions();
        foreach ($functions as $function) {
            $retval .= '<option';
            if ($function === $defaultFunction && ! isset($foreignData['foreign_field'])) {
                $retval .= ' selected="selected"';
            }

            $retval .= '>' . $function . '</option>' . "\n";
        }

        $retval .= '<option value="PHP_PASSWORD_HASH" title="';
        $retval .= htmlentities(__('The PHP function password_hash() with default options.'), ENT_COMPAT);
        $retval .= '">' . __('password_hash() PHP function') . '</option>' . "\n";

        return $retval;
    }

    /**
     * Renders a single link for the top of the navigation panel
     *
     * @param string $link        The url for the link
     * @param bool   $showText    Whether to show the text or to
     *                            only use it for title attributes
     * @param string $text        The text to display and use for title attributes
     * @param bool   $showIcon    Whether to show the icon
     * @param string $icon        The filename of the icon to show
     * @param string $linkId      Value to use for the ID attribute
     * @param bool   $disableAjax Whether to disable ajax page loading for this link
     * @param string $linkTarget  The name of the target frame for the link
     * @param array  $classes     HTML classes to apply
     *
     * @return string HTML code for one link
     */
    public static function getNavigationLink(
        $link,
        $showText,
        $text,
        $showIcon,
        $icon,
        $linkId = '',
        $disableAjax = false,
        $linkTarget = '',
        array $classes = []
    ): string {
        $retval = '<a href="' . $link . '"';
        if (! empty($linkId)) {
            $retval .= ' id="' . $linkId . '"';
        }

        if (! empty($linkTarget)) {
            $retval .= ' target="' . $linkTarget . '"';
        }

        if ($disableAjax) {
            $classes[] = 'disableAjax';
        }

        if (! empty($classes)) {
            $retval .= ' class="' . implode(' ', $classes) . '"';
        }

        $retval .= ' title="' . $text . '">';
        if ($showIcon) {
            $retval .= self::getImage($icon, $text);
        }

        if ($showText) {
            $retval .= $text;
        }

        $retval .= '</a>';
        if ($showText) {
            $retval .= '<br>';
        }

        return $retval;
    }

    /**
     * @return array<string, int|string>
     * @psalm-return array{pos: int, unlim_num_rows: int, rows: int, sql_query: string}
     */
    public static function getStartAndNumberOfRowsFieldsetData(string $sqlQuery): array
    {
        if (isset($_REQUEST['session_max_rows'])) {
            $rows = (int) $_REQUEST['session_max_rows'];
        } elseif (isset($_SESSION['tmpval']['max_rows']) && $_SESSION['tmpval']['max_rows'] !== 'all') {
            $rows = (int) $_SESSION['tmpval']['max_rows'];
        } else {
            $rows = (int) $GLOBALS['cfg']['MaxRows'];
            $_SESSION['tmpval']['max_rows'] = $rows;
        }

        $numberOfLine = (int) $_REQUEST['unlim_num_rows'];
        if (isset($_REQUEST['pos'])) {
            $pos = (int) $_REQUEST['pos'];
        } elseif (isset($_SESSION['tmpval']['pos'])) {
            $pos = (int) $_SESSION['tmpval']['pos'];
        } else {
            $pos = ((int) ceil($numberOfLine / $rows) - 1) * $rows;
            $_SESSION['tmpval']['pos'] = $pos;
        }

        return ['pos' => $pos, 'unlim_num_rows' => $numberOfLine, 'rows' => $rows, 'sql_query' => $sqlQuery];
    }

    /**
     * Execute an EXPLAIN query and formats results similar to MySQL command line
     * utility.
     *
     * @param string $sqlQuery EXPLAIN query
     *
     * @return string query results
     */
    private static function generateRowQueryOutput($sqlQuery): string
    {
        global $dbi;

        $ret = '';
        $result = $dbi->query($sqlQuery);
        $devider = '+';
        $columnNames = '|';
        $fieldsMeta = $dbi->getFieldsMeta($result);
        foreach ($fieldsMeta as $meta) {
            $devider .= '---+';
            $columnNames .= ' ' . $meta->name . ' |';
        }

        $devider .= "\n";

        $ret .= $devider . $columnNames . "\n" . $devider;
        while ($row = $result->fetchRow()) {
            $values = '|';
            foreach ($row as $value) {
                if ($value === null) {
                    $value = 'NULL';
                }

                $values .= ' ' . $value . ' |';
            }

            $ret .= $values . "\n";
        }

        $ret .= $devider;

        return $ret;
    }

    /**
     * Prepare the message and the query
     * usually the message is the result of the query executed
     *
     * @param Message|string $message  the message to display
     * @param string         $sqlQuery the query to display
     * @param string         $type     the type (level) of the message
     *
     * @throws Throwable
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public static function getMessage(
        $message,
        $sqlQuery = null,
        $type = 'notice'
    ): string {
        global $cfg, $dbi;

        $retval = '';

        if ($sqlQuery === null) {
            if (! empty($GLOBALS['display_query'])) {
                $sqlQuery = $GLOBALS['display_query'];
            } elseif (! empty($GLOBALS['unparsed_sql'])) {
                $sqlQuery = $GLOBALS['unparsed_sql'];
            } elseif (! empty($GLOBALS['sql_query'])) {
                $sqlQuery = $GLOBALS['sql_query'];
            } else {
                $sqlQuery = '';
            }
        }

        $renderSql = $cfg['ShowSQL'] == true && ! empty($sqlQuery) && $sqlQuery !== ';';

        if (isset($GLOBALS['using_bookmark_message'])) {
            $retval .= $GLOBALS['using_bookmark_message']->getDisplay();
            unset($GLOBALS['using_bookmark_message']);
        }

        if ($renderSql) {
            $retval .= '<div class="result_query">' . "\n";
        }

        if ($message instanceof Message) {
            if (isset($GLOBALS['special_message'])) {
                $message->addText($GLOBALS['special_message']);
                unset($GLOBALS['special_message']);
            }

            $retval .= $message->getDisplay();
        } else {
            $context = 'primary';
            if ($type === 'error') {
                $context = 'danger';
            } elseif ($type === 'success') {
                $context = 'success';
            }

            $retval .= '<div class="alert alert-' . $context . '" role="alert">';
            $retval .= Sanitize::sanitizeMessage($message);
            if (isset($GLOBALS['special_message'])) {
                $retval .= Sanitize::sanitizeMessage($GLOBALS['special_message']);
                unset($GLOBALS['special_message']);
            }

            $retval .= '</div>';
        }

        if ($renderSql) {
            $queryTooBig = false;

            $queryLength = mb_strlen($sqlQuery);
            if ($queryLength > $cfg['MaxCharactersInDisplayedSQL']) {
                // when the query is large (for example an INSERT of binary
                // data), the parser chokes; so avoid parsing the query
                $queryTooBig = true;
                $queryBase = mb_substr($sqlQuery, 0, $cfg['MaxCharactersInDisplayedSQL']) . '[...]';
            } else {
                $queryBase = $sqlQuery;
            }

            // Html format the query to be displayed
            // If we want to show some sql code it is easiest to create it here
            /* SQL-Parser-Analyzer */

            if (! empty($GLOBALS['show_as_php'])) {
                $newLine = '\\n"<br>' . "\n" . '&nbsp;&nbsp;&nbsp;&nbsp;. "';
                $queryBase = htmlspecialchars(addslashes($queryBase));
                $queryBase = preg_replace('/((\015\012)|(\015)|(\012))/', $newLine, $queryBase);
                $queryBase = '<code class="php"><pre>' . "\n"
                    . '$sql = "' . $queryBase . '";' . "\n"
                    . '</pre></code>';
            } elseif ($queryTooBig) {
                $queryBase = '<code class="sql"><pre>' . "\n" .
                    htmlspecialchars($queryBase, ENT_COMPAT) .
                    '</pre></code>';
            } else {
                $queryBase = self::formatSql($queryBase);
            }

            // Prepares links that may be displayed to edit/explain the query
            // (don't go to default pages, we must go to the page
            // where the query box is available)

            // Basic url query part
            $urlParams = [];
            if (! isset($GLOBALS['db'])) {
                $GLOBALS['db'] = '';
            }

            if (strlen($GLOBALS['db']) > 0) {
                $urlParams['db'] = $GLOBALS['db'];
                if (strlen($GLOBALS['table']) > 0) {
                    $urlParams['table'] = $GLOBALS['table'];
                    $editLink = Url::getFromRoute('/table/sql');
                } else {
                    $editLink = Url::getFromRoute('/database/sql');
                }
            } else {
                $editLink = Url::getFromRoute('/server/sql');
            }

            // Want to have the query explained
            // but only explain a SELECT (that has not been explained)
            /* SQL-Parser-Analyzer */
            $explainLink = '';
            $isSelect = preg_match('@^SELECT[[:space:]]+@i', $sqlQuery);
            if (! empty($cfg['SQLQuery']['Explain']) && ! $queryTooBig) {
                $explainParams = $urlParams;
                if ($isSelect) {
                    $explainParams['sql_query'] = 'EXPLAIN ' . $sqlQuery;
                    $explainLink = ' [&nbsp;'
                        . self::linkOrButton(
                            Url::getFromRoute('/import'),
                            $explainParams,
                            __('Explain SQL')
                        ) . '&nbsp;]';
                } elseif (preg_match('@^EXPLAIN[[:space:]]+SELECT[[:space:]]+@i', $sqlQuery)) {
                    $explainParams['sql_query'] = mb_substr($sqlQuery, 8);
                    $explainLink = ' [&nbsp;'
                        . self::linkOrButton(
                            Url::getFromRoute('/import'),
                            $explainParams,
                            __('Skip Explain SQL')
                        ) . ']';
                }
            }

            $urlParams['sql_query'] = $sqlQuery;
            $urlParams['show_query'] = 1;

            // even if the query is big and was truncated, offer the chance
            // to edit it (unless it's enormous, see linkOrButton() )
            if (! empty($cfg['SQLQuery']['Edit']) && empty($GLOBALS['show_as_php'])) {
                $editLink = ' [&nbsp;'
                    . self::linkOrButton($editLink, $urlParams, __('Edit'))
                    . '&nbsp;]';
            } else {
                $editLink = '';
            }

            // Also we would like to get the SQL formed in some nice
            // php-code
            if (! empty($cfg['SQLQuery']['ShowAsPHP']) && ! $queryTooBig) {
                if (! empty($GLOBALS['show_as_php'])) {
                    $phpLink = ' [&nbsp;'
                        . self::linkOrButton(
                            Url::getFromRoute('/import'),
                            $urlParams,
                            __('Without PHP code')
                        )
                        . '&nbsp;]';

                    $phpLink .= ' [&nbsp;'
                        . self::linkOrButton(
                            Url::getFromRoute('/import'),
                            $urlParams,
                            __('Submit query')
                        )
                        . '&nbsp;]';
                } else {
                    $phpParams = $urlParams;
                    $phpParams['show_as_php'] = 1;
                    $phpLink = ' [&nbsp;'
                        . self::linkOrButton(
                            Url::getFromRoute('/import'),
                            $phpParams,
                            __('Create PHP code')
                        )
                        . '&nbsp;]';
                }
            } else {
                $phpLink = '';
            }

            // Refresh query
            if (
                ! empty($cfg['SQLQuery']['Refresh'])
                && ! isset($GLOBALS['show_as_php']) // 'Submit query' does the same
                && preg_match('@^(SELECT|SHOW)[[:space:]]+@i', $sqlQuery)
            ) {
                $refreshLink = Url::getFromRoute('/sql', $urlParams);
                $refreshLink = ' [&nbsp;'
                    . self::linkOrButton($refreshLink, $urlParams, __('Refresh')) . '&nbsp;]';
            } else {
                $refreshLink = '';
            }

            $retval .= '<div class="sqlOuter">';
            $retval .= $queryBase;
            $retval .= '</div>';

            $retval .= '<div class="tools d-print-none">';
            $retval .= '<form action="' . Url::getFromRoute('/sql') . '" method="post">';
            $retval .= Url::getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);
            $retval .= '<input type="hidden" name="sql_query" value="'
                . htmlspecialchars($sqlQuery) . '">';

            // avoid displaying a Profiling checkbox that could
            // be checked, which would re-execute an INSERT, for example
            if (! empty($refreshLink) && Profiling::isSupported($dbi)) {
                $retval .= '<input type="hidden" name="profiling_form" value="1">';
                $retval .= '<input type="checkbox" name="profiling" id="profilingCheckbox" class="autosubmit"';
                $retval .= isset($_SESSION['profiling']) ? ' checked' : '';
                $retval .= '> <label for="profilingCheckbox">' . __('Profiling') . '</label>';
            }

            $retval .= '</form>';

            /**
             * TODO: Should we have $cfg['SQLQuery']['InlineEdit']?
             */
            if (! empty($cfg['SQLQuery']['Edit']) && ! $queryTooBig && empty($GLOBALS['show_as_php'])) {
                $inlineEditLink = ' [&nbsp;'
                    . self::linkOrButton(
                        '#',
                        null,
                        _pgettext('Inline edit query', 'Edit inline'),
                        ['class' => 'inline_edit_sql']
                    )
                    . '&nbsp;]';
            } else {
                $inlineEditLink = '';
            }

            $retval .= $inlineEditLink . $editLink . $explainLink . $phpLink
                . $refreshLink;
            $retval .= '</div>';

            $retval .= '</div>';
        }

        return $retval;
    }

    /**
     * Displays a link to the PHP documentation
     *
     * @param string $target anchor in documentation
     *
     * @return string  the html link
     */
    public static function showPHPDocumentation($target): string
    {
        return self::showDocumentationLink(Core::getPHPDocLink($target));
    }

    /**
     * Displays a link to the documentation as an icon
     *
     * @param string $link   documentation link
     * @param string $target optional link target
     * @param bool   $bbcode optional flag indicating whether to output bbcode
     *
     * @return string the html link
     */
    public static function showDocumentationLink($link, $target = 'documentation', $bbcode = false): string
    {
        if ($bbcode) {
            return '[a@' . $link . '@' . $target . '][dochelpicon][/a]';
        }

        return '<a href="' . $link . '" target="' . $target . '">'
            . self::getImage('b_help', __('Documentation'))
            . '</a>';
    }

    /**
     * Displays a MySQL error message in the main panel when $exit is true.
     * Returns the error message otherwise.
     *
     * @param string $serverMessage Server's error message.
     * @param string $sqlQuery      The SQL query that failed.
     * @param bool   $isModifyLink  Whether to show a "modify" link or not.
     * @param string $backUrl       URL for the "back" link (full path is not required).
     * @param bool   $exit          Whether execution should be stopped or the error message should be returned.
     *
     * @global string $table The current table.
     * @global string $db    The current database.
     */
    public static function mysqlDie(
        $serverMessage = '',
        $sqlQuery = '',
        $isModifyLink = true,
        $backUrl = '',
        $exit = true
    ): ?string {
        global $table, $db, $dbi;

        /**
         * Error message to be built.
         */
        $errorMessage = '';

        // Checking for any server errors.
        if (empty($serverMessage)) {
            $serverMessage = $dbi->getError();
        }

        // Finding the query that failed, if not specified.
        if (empty($sqlQuery) && ! empty($GLOBALS['sql_query'])) {
            $sqlQuery = $GLOBALS['sql_query'];
        }

        $sqlQuery = trim($sqlQuery);

        /**
         * The lexer used for analysis.
         */
        $lexer = new Lexer($sqlQuery);

        /**
         * The parser used for analysis.
         */
        $parser = new Parser($lexer->list);

        /**
         * The errors found by the lexer and the parser.
         */
        $errors = ParserError::get(
            [
                $lexer,
                $parser,
            ]
        );

        if (empty($sqlQuery)) {
            $formattedSql = '';
        } elseif (count($errors)) {
            $formattedSql = htmlspecialchars($sqlQuery);
        } else {
            $formattedSql = self::formatSql($sqlQuery, true);
        }

        $errorMessage .= '<div class="alert alert-danger" role="alert"><h1>' . __('Error') . '</h1>';

        // For security reasons, if the MySQL refuses the connection, the query
        // is hidden so no details are revealed.
        if (! empty($sqlQuery) && ! mb_strstr($sqlQuery, 'connect')) {
            // Static analysis errors.
            if (! empty($errors)) {
                $errorMessage .= '<p><strong>' . __('Static analysis:')
                    . '</strong></p>';
                $errorMessage .= '<p>' . sprintf(
                    __('%d errors were found during analysis.'),
                    count($errors)
                ) . '</p>';
                $errorMessage .= '<p><ol>';
                $errorMessage .= implode(
                    ParserError::format(
                        $errors,
                        '<li>%2$s (near "%4$s" at position %5$d)</li>'
                    )
                );
                $errorMessage .= '</ol></p>';
            }

            // Display the SQL query and link to MySQL documentation.
            $errorMessage .= '<p><strong>' . __('SQL query:') . '</strong>' . self::showCopyToClipboard(
                $sqlQuery
            ) . "\n";
            $formattedSqlToLower = mb_strtolower($formattedSql);

            // TODO: Show documentation for all statement types.
            if (mb_strstr($formattedSqlToLower, 'select')) {
                // please show me help to the error on select
                $errorMessage .= MySQLDocumentation::show('SELECT');
            }

            if ($isModifyLink) {
                $urlParams = [
                    'sql_query' => $sqlQuery,
                    'show_query' => 1,
                ];
                if (strlen($table) > 0) {
                    $urlParams['db'] = $db;
                    $urlParams['table'] = $table;
                    $doEditGoto = '<a href="' . Url::getFromRoute('/table/sql', $urlParams) . '">';
                } elseif (strlen($db) > 0) {
                    $urlParams['db'] = $db;
                    $doEditGoto = '<a href="' . Url::getFromRoute('/database/sql', $urlParams) . '">';
                } else {
                    $doEditGoto = '<a href="' . Url::getFromRoute('/server/sql', $urlParams) . '">';
                }

                $errorMessage .= $doEditGoto
                    . self::getIcon('b_edit', __('Edit'))
                    . '</a>';
            }

            $errorMessage .= '    </p>' . "\n"
                . '<p>' . "\n"
                . $formattedSql . "\n"
                . '</p>' . "\n";
        }

        // Display server's error.
        if ($serverMessage !== '') {
            $serverMessage = (string) preg_replace("@((\015\012)|(\015)|(\012)){3,}@", "\n\n", $serverMessage);

            // Adds a link to MySQL documentation.
            $errorMessage .= '<p>' . "\n"
                . '    <strong>' . __('MySQL said: ') . '</strong>'
                . MySQLDocumentation::show('server-error-reference')
                . "\n"
                . '</p>' . "\n";

            // The error message will be displayed within a CODE segment.
            // To preserve original formatting, but allow word-wrapping,
            // a couple of replacements are done.
            // All non-single blanks and  TAB-characters are replaced with their
            // HTML-counterpart
            $serverMessage = str_replace(
                [
                    '  ',
                    "\t",
                ],
                [
                    '&nbsp;&nbsp;',
                    '&nbsp;&nbsp;&nbsp;&nbsp;',
                ],
                $serverMessage
            );

            // Replace line breaks
            $serverMessage = nl2br($serverMessage);

            $errorMessage .= '<code>' . $serverMessage . '</code><br>';
        }

        $errorMessage .= '</div>';
        $_SESSION['Import_message']['message'] = $errorMessage;

        if (! $exit) {
            return $errorMessage;
        }

        /**
         * If this is an AJAX request, there is no "Back" link and
         * `Response()` is used to send the response.
         */
        $response = ResponseRenderer::getInstance();
        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            $response->addJSON('message', $errorMessage);
            exit;
        }

        if (! empty($backUrl)) {
            if (mb_strstr($backUrl, '?')) {
                $backUrl .= '&amp;no_history=true';
            } else {
                $backUrl .= '?no_history=true';
            }

            $_SESSION['Import_message']['go_back_url'] = $backUrl;

            $errorMessage .= '<fieldset class="pma-fieldset tblFooters">'
                . '[ <a href="' . $backUrl . '">' . __('Back') . '</a> ]'
                . '</fieldset>' . "\n\n";
        }

        exit($errorMessage);
    }

    /**
     * Returns an HTML IMG tag for a particular image from a theme
     *
     * The image name should match CSS class defined in icons.css.php
     *
     * @param string $image      The name of the file to get
     * @param string $alternate  Used to set 'alt' and 'title' attributes
     *                           of the image
     * @param array  $attributes An associative array of other attributes
     *
     * @return string an html IMG tag
     */
    public static function getImage($image, $alternate = '', array $attributes = []): string
    {
        $alternate = htmlspecialchars($alternate);

        if (isset($attributes['class'])) {
            $attributes['class'] = 'icon ic_' . $image . ' ' . $attributes['class'];
        } else {
            $attributes['class'] = 'icon ic_' . $image;
        }

        // set all other attributes
        $attributeString = '';
        foreach ($attributes as $key => $value) {
            if (in_array($key, ['alt', 'title'])) {
                continue;
            }

            $attributeString .= ' ' . $key . '="' . $value . '"';
        }

        // override the alt attribute
        $alt = $attributes['alt'] ?? $alternate;

        // override the title attribute
        $title = $attributes['title'] ?? $alternate;

        // generate the IMG tag
        $template = '<img src="themes/dot.gif" title="%s" alt="%s"%s>';

        return sprintf($template, $title, $alt, $attributeString);
    }

    /**
     * Displays a link, or a link with code to trigger POST request.
     *
     * POST is used in following cases:
     *
     * - URL is too long
     * - URL components are over Suhosin limits
     * - There is SQL query in the parameters
     *
     * @param string                        $urlPath   the URL
     * @param array<int|string, mixed>|null $urlParams URL parameters
     * @param string                        $message   the link message
     * @param string|array<string, string>  $tagParams string: js confirmation;
     *                                                 array: additional tag params (f.e. style="")
     * @param string                        $target    target
     *
     * @return string  the results to be echoed or saved in an array
     */
    public static function linkOrButton(
        $urlPath,
        $urlParams,
        $message,
        $tagParams = [],
        $target = '',
        bool $respectUrlLengthLimit = true
    ): string {
        $url = $urlPath;
        if (is_array($urlParams)) {
            $url = $urlPath . Url::getCommon($urlParams, str_contains($urlPath, '?') ? '&' : '?', false);
        }

        $urlLength = strlen($url);

        if (! is_array($tagParams)) {
            $tmp = $tagParams;
            $tagParams = [];
            if (! empty($tmp)) {
                $tagParams['onclick'] = 'return Functions.confirmLink(this, \''
                    . Sanitize::escapeJsString($tmp) . '\')';
            }

            unset($tmp);
        }

        if (! empty($target)) {
            $tagParams['target'] = $target;
            if ($target === '_blank' && str_starts_with($url, 'url.php?')) {
                $tagParams['rel'] = 'noopener noreferrer';
            }
        }

        // Suhosin: Check that each query parameter is not above maximum
        $inSuhosinLimits = true;
        if ($urlLength <= $GLOBALS['cfg']['LinkLengthLimit']) {
            $suhosinGetMaxValueLength = ini_get('suhosin.get.max_value_length');
            if ($suhosinGetMaxValueLength) {
                $queryParts = Util::splitURLQuery($url);
                foreach ($queryParts as $queryPair) {
                    if (! str_contains($queryPair, '=')) {
                        continue;
                    }

                    [, $eachValue] = explode('=', $queryPair);
                    if (strlen($eachValue) > $suhosinGetMaxValueLength) {
                        $inSuhosinLimits = false;
                        break;
                    }
                }
            }
        }

        $tagParamsStrings = [];
        $isDataPostFormatSupported = ($urlLength > $GLOBALS['cfg']['LinkLengthLimit'])
                                || ! $inSuhosinLimits
                                // Has as sql_query without a signature, to be accepted it needs
                                // to be sent using POST
                                || (
                                    str_contains($url, 'sql_query=')
                                    && ! str_contains($url, 'sql_signature=')
                                )
                                || str_contains($url, 'view[as]=');
        if ($respectUrlLengthLimit && $isDataPostFormatSupported) {
            $parts = explode('?', $url, 2);
            /*
             * The data-post indicates that client should do POST
             * this is handled in js/ajax.js
             */
            $tagParamsStrings[] = 'data-post="' . ($parts[1] ?? '') . '"';
            $url = $parts[0];
            if (array_key_exists('class', $tagParams) && str_contains($tagParams['class'], 'create_view')) {
                $url .= '?' . explode('&', $parts[1], 2)[0];
            }
        } else {
            $url = $urlPath;
            if (is_array($urlParams)) {
                $url = $urlPath . Url::getCommon($urlParams, str_contains($urlPath, '?') ? '&' : '?');
            }
        }

        foreach ($tagParams as $paramName => $paramValue) {
            $tagParamsStrings[] = $paramName . '="' . htmlspecialchars($paramValue) . '"';
        }

        // no whitespace within an <a> else Safari will make it part of the link
        return '<a href="' . $url . '" '
            . implode(' ', $tagParamsStrings) . '>'
            . $message . '</a>';
    }

    /**
     * Prepare navigation for a list
     *
     * @param int      $count     number of elements in the list
     * @param int      $pos       current position in the list
     * @param array    $urlParams url parameters
     * @param string   $script    script name for form target
     * @param string   $frame     target frame
     * @param int      $maxCount  maximum number of elements to display from
     *                             the list
     * @param string   $name      the name for the request parameter
     * @param string[] $classes   additional classes for the container
     *
     * @return string the  html content
     *
     * @todo    use $pos from $_url_params
     */
    public static function getListNavigator(
        $count,
        $pos,
        array $urlParams,
        $script,
        $frame,
        $maxCount,
        $name = 'pos',
        $classes = []
    ): string {
        // This is often coming from $cfg['MaxTableList'] and
        // people sometimes set it to empty string
        $maxCount = intval($maxCount);
        if ($maxCount <= 0) {
            $maxCount = 250;
        }

        $pageSelector = '';
        if ($maxCount < $count) {
            $classes[] = 'pageselector';

            $pageSelector = Util::pageselector(
                $name,
                $maxCount,
                Util::getPageFromPosition($pos, $maxCount),
                (int) ceil($count / $maxCount)
            );
        }

        return (new Template())->render('list_navigator', [
            'count' => $count,
            'max_count' => $maxCount,
            'classes' => $classes,
            'frame' => $frame,
            'position' => $pos,
            'script' => $script,
            'url_params' => $urlParams,
            'param_name' => $name,
            'page_selector' => $pageSelector,
        ]);
    }

    /**
     * format sql strings
     *
     * @param string $sqlQuery raw SQL string
     * @param bool   $truncate truncate the query if it is too long
     *
     * @return string the formatted sql
     *
     * @global array  $cfg the configuration array
     */
    public static function formatSql($sqlQuery, $truncate = false): string
    {
        global $cfg;

        if ($truncate && mb_strlen($sqlQuery) > $cfg['MaxCharactersInDisplayedSQL']) {
            $sqlQuery = mb_substr($sqlQuery, 0, $cfg['MaxCharactersInDisplayedSQL']) . '[...]';
        }

        return '<code class="sql"><pre>' . "\n"
            . htmlspecialchars($sqlQuery, ENT_COMPAT) . "\n"
            . '</pre></code>';
    }

    /**
     * This function processes the datatypes supported by the DB,
     * as specified in Types->getColumns() and returns an HTML snippet that
     * creates a drop-down list.
     *
     * @param string $selected The value to mark as selected in HTML mode
     */
    public static function getSupportedDatatypes($selected): string
    {
        global $dbi;

        // NOTE: the SELECT tag is not included in this snippet.
        $retval = '';

        foreach ($dbi->types->getColumns() as $key => $value) {
            if (is_array($value)) {
                $retval .= '<optgroup label="' . htmlspecialchars($key) . '">';
                foreach ($value as $subvalue) {
                    if ($subvalue === '-') {
                        $retval .= '<option disabled="disabled">';
                        $retval .= $subvalue;
                        $retval .= '</option>';
                        continue;
                    }

                    $isLengthRestricted = Compatibility::isIntegersSupportLength($subvalue, '2', $dbi);
                    $retval .= sprintf(
                        '<option data-length-restricted="%b" %s title="%s">%s</option>',
                        $isLengthRestricted ? 0 : 1,
                        $selected === $subvalue ? 'selected="selected"' : '',
                        $dbi->types->getTypeDescription($subvalue),
                        $subvalue
                    );
                }

                $retval .= '</optgroup>';
                continue;
            }

            $isLengthRestricted = Compatibility::isIntegersSupportLength($value, '2', $dbi);
            $retval .= sprintf(
                '<option data-length-restricted="%b" %s title="%s">%s</option>',
                $isLengthRestricted ? 0 : 1,
                $selected === $value ? 'selected="selected"' : '',
                $dbi->types->getTypeDescription($value),
                $value
            );
        }

        return $retval;
    }
}
