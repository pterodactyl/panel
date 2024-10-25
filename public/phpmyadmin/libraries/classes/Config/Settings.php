<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Config\Settings\Console;
use PhpMyAdmin\Config\Settings\Debug;
use PhpMyAdmin\Config\Settings\Export;
use PhpMyAdmin\Config\Settings\Import;
use PhpMyAdmin\Config\Settings\Schema;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Config\Settings\SqlQueryBox;
use PhpMyAdmin\Config\Settings\Transformations;

use function array_keys;
use function count;
use function defined;
use function get_object_vars;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function min;
use function strlen;

use const DIRECTORY_SEPARATOR;
use const ROOT_PATH;
use const TEMP_DIR;
use const VERSION_CHECK_DEFAULT;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * @psalm-immutable
 */
final class Settings
{
    /**
     * Your phpMyAdmin URL.
     *
     * Complete the variable below with the full URL ie
     *    https://example.com/path_to_your_phpMyAdmin_directory/
     *
     * It must contain characters that are valid for a URL, and the path is
     * case sensitive on some Web servers, for example Unix-based servers.
     *
     * In most cases you can leave this variable empty, as the correct value
     * will be detected automatically. However, we recommend that you do
     * test to see that the auto-detection code works in your system. A good
     * test is to browse a table, then edit a row and save it.  There will be
     * an error message if phpMyAdmin cannot auto-detect the correct value.
     *
     * @var string
     */
    public $PmaAbsoluteUri;

    /**
     * Configure authentication logging destination
     *
     * @var string
     */
    public $AuthLog;

    /**
     * Whether to log successful authentication attempts
     *
     * @var bool
     */
    public $AuthLogSuccess;

    /**
     * Disable the default warning that is displayed on the DB Details Structure page if
     * any of the required Tables for the configuration storage could not be found
     *
     * @var bool
     */
    public $PmaNoRelation_DisableWarning;

    /**
     * Disable the default warning that is displayed if Suhosin is detected
     *
     * @var bool
     */
    public $SuhosinDisableWarning;

    /**
     * Disable the default warning that is displayed if session.gc_maxlifetime
     * is less than `LoginCookieValidity`
     *
     * @var bool
     */
    public $LoginCookieValidityDisableWarning;

    /**
     * Disable the default warning about MySQL reserved words in column names
     *
     * @var bool
     */
    public $ReservedWordDisableWarning;

    /**
     * Show warning about incomplete translations on certain threshold.
     *
     * @var int
     */
    public $TranslationWarningThreshold;

    /**
     * Allows phpMyAdmin to be included from a other document in a frame;
     * setting this to true is a potential security hole. Setting this to
     * 'sameorigin' prevents phpMyAdmin to be included from another document
     * in a frame, unless that document belongs to the same domain.
     *
     * @var bool|string
     * @psalm-var bool|'sameorigin'
     */
    public $AllowThirdPartyFraming;

    /**
     * The 'cookie' auth_type uses the Sodium extension to encrypt the cookies. If at least one server configuration
     * uses 'cookie' auth_type, enter here a generated string of random bytes to be used as an encryption key. The
     * encryption key must be 32 bytes long.
     *
     * @var string
     */
    public $blowfish_secret;

    /**
     * Server(s) configuration
     *
     * The $cfg['Servers'] array starts with $cfg['Servers'][1].  Do not use
     * $cfg['Servers'][0]. You can disable a server configuration entry by setting host
     * to ''. If you want more than one server, just copy following section
     * (including $i incrementation) several times. There is no need to define
     * full server array, just define values you need to change.
     *
     * @var array<int, Server>
     * @psalm-var array<int<1, max>, Server>
     */
    public $Servers;

    /**
     * Default server (0 = no default server)
     *
     * If you have more than one server configured, you can set $cfg['ServerDefault']
     * to any one of them to auto-connect to that server when phpMyAdmin is started,
     * or set it to 0 to be given a list of servers without logging in
     * If you have only one server configured, $cfg['ServerDefault'] *MUST* be
     * set to that server.
     *
     * @var int
     * @psalm-var 0|positive-int
     */
    public $ServerDefault;

    /**
     * whether version check is active
     *
     * @var bool
     */
    public $VersionCheck;

    /**
     * The url of the proxy to be used when retrieving the information about
     * the latest version of phpMyAdmin or error reporting. You need this if
     * the server where phpMyAdmin is installed does not have direct access to
     * the internet.
     * The format is: "hostname:portnumber"
     *
     * @var string
     */
    public $ProxyUrl;

    /**
     * The username for authenticating with the proxy. By default, no
     * authentication is performed. If a username is supplied, Basic
     * Authentication will be performed. No other types of authentication
     * are currently supported.
     *
     * @var string
     */
    public $ProxyUser;

    /**
     * The password for authenticating with the proxy.
     *
     * @var string
     */
    public $ProxyPass;

    /**
     * maximum number of db's displayed in database list
     *
     * @var int
     * @psalm-var positive-int
     */
    public $MaxDbList;

    /**
     * maximum number of tables displayed in table list
     *
     * @var int
     * @psalm-var positive-int
     */
    public $MaxTableList;

    /**
     * whether to show hint or not
     *
     * @var bool
     */
    public $ShowHint;

    /**
     * maximum number of characters when a SQL query is displayed
     *
     * @var int
     * @psalm-var positive-int
     */
    public $MaxCharactersInDisplayedSQL;

    /**
     * use GZIP output buffering if possible (true|false|'auto')
     *
     * @var string|bool
     * @psalm-var 'auto'|bool
     */
    public $OBGzip;

    /**
     * use persistent connections to MySQL database
     *
     * @var bool
     */
    public $PersistentConnections;

    /**
     * maximum execution time in seconds (0 for no limit)
     *
     * @var int
     * @psalm-var 0|positive-int
     */
    public $ExecTimeLimit;

    /**
     * Path for storing session data (session_save_path PHP parameter).
     *
     * @var string
     */
    public $SessionSavePath;

    /**
     * Hosts or IPs to consider safe when checking if SSL is used or not
     *
     * @var string[]
     */
    public $MysqlSslWarningSafeHosts;

    /**
     * maximum allocated bytes ('-1' for no limit, '0' for no change)
     * this is a string because '16M' is a valid value; we must put here
     * a string as the default value so that /setup accepts strings
     *
     * @var string
     */
    public $MemoryLimit;

    /**
     * mark used tables, make possible to show locked tables (since MySQL 3.23.30)
     *
     * @var bool
     */
    public $SkipLockedTables;

    /**
     * show SQL queries as run
     *
     * @var bool
     */
    public $ShowSQL;

    /**
     * retain SQL input on Ajax execute
     *
     * @var bool
     */
    public $RetainQueryBox;

    /**
     * use CodeMirror syntax highlighting for editing SQL
     *
     * @var bool
     */
    public $CodemirrorEnable;

    /**
     * use the parser to find any errors in the query before executing
     *
     * @var bool
     */
    public $LintEnable;

    /**
     * show a 'Drop database' link to normal users
     *
     * @var bool
     */
    public $AllowUserDropDatabase;

    /**
     * confirm some commands that can result in loss of data
     *
     * @var bool
     */
    public $Confirm;

    /**
     * sets SameSite attribute of the Set-Cookie HTTP response header
     *
     * @var string
     * @psalm-var 'Lax'|'Strict'|'None'
     */
    public $CookieSameSite;

    /**
     * recall previous login in cookie authentication mode or not
     *
     * @var bool
     */
    public $LoginCookieRecall;

    /**
     * validity of cookie login (in seconds; 1440 matches php.ini's
     * session.gc_maxlifetime)
     *
     * @var int
     * @psalm-var positive-int
     */
    public $LoginCookieValidity;

    /**
     * how long login cookie should be stored (in seconds)
     *
     * @var int
     * @psalm-var 0|positive-int
     */
    public $LoginCookieStore;

    /**
     * whether to delete all login cookies on logout
     *
     * @var bool
     */
    public $LoginCookieDeleteAll;

    /**
     * whether to enable the "database search" feature or not
     *
     * @var bool
     */
    public $UseDbSearch;

    /**
     * if set to true, PMA continues computing multiple-statement queries
     * even if one of the queries failed
     *
     * @var bool
     */
    public $IgnoreMultiSubmitErrors;

    /**
     * Define whether phpMyAdmin will encrypt sensitive data from the URL query string.
     *
     * @var bool
     */
    public $URLQueryEncryption;

    /**
     * A secret key used to encrypt/decrypt the URL query string. Should be 32 bytes long.
     *
     * @var string
     */
    public $URLQueryEncryptionSecretKey;

    /**
     * allow login to any user entered server in cookie based authentication
     *
     * @var bool
     */
    public $AllowArbitraryServer;

    /**
     * restrict by IP (with regular expression) the MySQL servers the user can enter
     * when $cfg['AllowArbitraryServer'] = true
     *
     * @var string
     */
    public $ArbitraryServerRegexp;

    /**
     * To enable reCaptcha v2 checkbox mode if necessary
     *
     * @var string
     * @psalm-var 'invisible'|'checkbox'
     */
    public $CaptchaMethod;

    /**
     * URL for the reCaptcha v2 compatible API to use
     *
     * @var string
     */
    public $CaptchaApi;

    /**
     * Content-Security-Policy snippet for the reCaptcha v2 compatible API
     *
     * @var string
     */
    public $CaptchaCsp;

    /**
     * reCaptcha API's request parameter name
     *
     * @var string
     */
    public $CaptchaRequestParam;

    /**
     * reCaptcha API's response parameter name
     *
     * @var string
     */
    public $CaptchaResponseParam;

    /**
     * if reCaptcha is enabled it needs public key to connect with the service
     *
     * @var string
     */
    public $CaptchaLoginPublicKey;

    /**
     * if reCaptcha is enabled it needs private key to connect with the service
     *
     * @var string
     */
    public $CaptchaLoginPrivateKey;

    /**
     * if reCaptcha is enabled may need an URL for site verify
     *
     * @var string
     */
    public $CaptchaSiteVerifyURL;

    /**
     * Enable drag and drop import
     *
     * @see https://github.com/phpmyadmin/phpmyadmin/issues/13155
     *
     * @var bool
     */
    public $enable_drag_drop_import;

    /**
     * In the navigation panel, replaces the database tree with a selector
     *
     * @var bool
     */
    public $ShowDatabasesNavigationAsTree;

    /**
     * maximum number of first level databases displayed in navigation panel
     *
     * @var int
     * @psalm-var positive-int
     */
    public $FirstLevelNavigationItems;

    /**
     * maximum number of items displayed in navigation panel
     *
     * @var int
     * @psalm-var positive-int
     */
    public $MaxNavigationItems;

    /**
     * turn the select-based light menu into a tree
     *
     * @var bool
     */
    public $NavigationTreeEnableGrouping;

    /**
     * the separator to sub-tree the select-based light menu tree
     *
     * @var string
     */
    public $NavigationTreeDbSeparator;

    /**
     * Which string will be used to generate table prefixes
     * to split/nest tables into multiple categories
     *
     * @var string|string[]|false
     */
    public $NavigationTreeTableSeparator;

    /**
     * How many sublevels should be displayed when splitting up tables
     * by the above Separator
     *
     * @var int
     * @psalm-var positive-int
     */
    public $NavigationTreeTableLevel;

    /**
     * link with main panel by highlighting the current db/table
     *
     * @var bool
     */
    public $NavigationLinkWithMainPanel;

    /**
     * display logo at top of navigation panel
     *
     * @var bool
     */
    public $NavigationDisplayLogo;

    /**
     * where should logo link point to (can also contain an external URL)
     *
     * @var string
     */
    public $NavigationLogoLink;

    /**
     * whether to open the linked page in the main window ('main') or
     * in a new window ('new')
     *
     * @var string
     * @psalm-var 'main'|'new'
     */
    public $NavigationLogoLinkWindow;

    /**
     * number of recently used tables displayed in the navigation panel
     *
     * @var int
     * @psalm-var 0|positive-int
     */
    public $NumRecentTables;

    /**
     * number of favorite tables displayed in the navigation panel
     *
     * @var int
     * @psalm-var 0|positive-int
     */
    public $NumFavoriteTables;

    /**
     * display a JavaScript table filter in the navigation panel
     * when more then x tables are present
     *
     * @var int
     * @psalm-var positive-int
     */
    public $NavigationTreeDisplayItemFilterMinimum;

    /**
     * display server choice at top of navigation panel
     *
     * @var bool
     */
    public $NavigationDisplayServers;

    /**
     * server choice as links
     *
     * @var bool
     */
    public $DisplayServersList;

    /**
     * display a JavaScript database filter in the navigation panel
     * when more then x databases are present
     *
     * @var int
     * @psalm-var positive-int
     */
    public $NavigationTreeDisplayDbFilterMinimum;

    /**
     * target of the navigation panel quick access icon
     *
     * Possible values:
     * 'structure' = fields list
     * 'sql' = SQL form
     * 'search' = search page
     * 'insert' = insert row page
     * 'browse' = browse page
     *
     * @var string
     * @psalm-var 'structure'|'sql'|'search'|'insert'|'browse'
     */
    public $NavigationTreeDefaultTabTable;

    /**
     * target of the navigation panel quick second access icon
     *
     * Possible values:
     * 'structure' = fields list
     * 'sql' = SQL form
     * 'search' = search page
     * 'insert' = insert row page
     * 'browse' = browse page
     * '' = no link
     *
     * @var string
     * @psalm-var 'structure'|'sql'|'search'|'insert'|'browse'|''
     */
    public $NavigationTreeDefaultTabTable2;

    /**
     * Enables the possibility of navigation tree expansion
     *
     * @var bool
     */
    public $NavigationTreeEnableExpansion;

    /**
     * Show tables in navigation panel
     *
     * @var bool
     */
    public $NavigationTreeShowTables;

    /**
     * Show views in navigation panel
     *
     * @var bool
     */
    public $NavigationTreeShowViews;

    /**
     * Show functions in navigation panel
     *
     * @var bool
     */
    public $NavigationTreeShowFunctions;

    /**
     * Show procedures in navigation panel
     *
     * @var bool
     */
    public $NavigationTreeShowProcedures;

    /**
     * Show events in navigation panel
     *
     * @var bool
     */
    public $NavigationTreeShowEvents;

    /**
     * Width of navigation panel
     *
     * @var int
     * @psalm-var 0|positive-int
     */
    public $NavigationWidth;

    /**
     * Automatically expands single database in navigation panel
     *
     * @var bool
     */
    public $NavigationTreeAutoexpandSingleDb;

    /**
     * allow to display statistics and space usage in the pages about database
     * details and table properties
     *
     * @var bool
     */
    public $ShowStats;

    /**
     * show PHP info link
     *
     * @var bool
     */
    public $ShowPhpInfo;

    /**
     * show MySQL server and web server information
     *
     * @var bool
     */
    public $ShowServerInfo;

    /**
     * show change password link
     *
     * @var bool
     */
    public $ShowChgPassword;

    /**
     * show create database form
     *
     * @var bool
     */
    public $ShowCreateDb;

    /**
     * show charset column in database structure (true|false)?
     *
     * @var bool
     */
    public $ShowDbStructureCharset;

    /**
     * show comment column in database structure (true|false)?
     *
     * @var bool
     */
    public $ShowDbStructureComment;

    /**
     * show creation timestamp column in database structure (true|false)?
     *
     * @var bool
     */
    public $ShowDbStructureCreation;

    /**
     * show last update timestamp column in database structure (true|false)?
     *
     * @var bool
     */
    public $ShowDbStructureLastUpdate;

    /**
     * show last check timestamp column in database structure (true|false)?
     *
     * @var bool
     */
    public $ShowDbStructureLastCheck;

    /**
     * allow hide action columns to drop down menu in database structure (true|false)?
     *
     * @var bool
     */
    public $HideStructureActions;

    /**
     * Show column comments in table structure view (true|false)?
     *
     * @var bool
     */
    public $ShowColumnComments;

    /**
     * Use icons instead of text for the navigation bar buttons (table browse)
     * ('text'|'icons'|'both')
     *
     * @var string
     * @psalm-var 'text'|'icons'|'both'
     */
    public $TableNavigationLinksMode;

    /**
     * Defines whether a user should be displayed a "show all (records)"
     * button in browse mode or not.
     *
     * @var bool
     */
    public $ShowAll;

    /**
     * Number of rows displayed when browsing a result set. If the result
     * set contains more rows, "Previous" and "Next".
     * Possible values: 25,50,100,250,500
     *
     * @var int
     * @psalm-var positive-int
     */
    public $MaxRows;

    /**
     * default for 'ORDER BY' clause (valid values are 'ASC', 'DESC' or 'SMART' -ie
     * descending order for fields of type TIME, DATE, DATETIME & TIMESTAMP,
     * ascending order else-)
     *
     * @var string
     * @psalm-var 'ASC'|'DESC'|'SMART'
     */
    public $Order;

    /**
     * grid editing: save edited cell(s) in browse-mode at once
     *
     * @var bool
     */
    public $SaveCellsAtOnce;

    /**
     * grid editing: which action triggers it, or completely disable the feature
     *
     * Possible values:
     * 'click'
     * 'double-click'
     * 'disabled'
     *
     * @var string
     * @psalm-var 'double-click'|'click'|'disabled'
     */
    public $GridEditing;

    /**
     * Options > Relational display
     *
     * Possible values:
     * 'K' for key value
     * 'D' for display column
     *
     * @var string
     * @psalm-var 'K'|'D'
     */
    public $RelationalDisplay;

    /**
     * disallow editing of binary fields
     * valid values are:
     *   false    allow editing
     *   'blob'   allow editing except for BLOB fields
     *   'noblob' disallow editing except for BLOB fields
     *   'all'    disallow editing
     *
     * @var string|false
     * @psalm-var 'blob'|'noblob'|'all'|false
     */
    public $ProtectBinary;

    /**
     * Display the function fields in edit/insert mode
     *
     * @var bool
     */
    public $ShowFunctionFields;

    /**
     * Display the type fields in edit/insert mode
     *
     * @var bool
     */
    public $ShowFieldTypesInDataEditView;

    /**
     * Which editor should be used for CHAR/VARCHAR fields:
     *  input - allows limiting of input length
     *  textarea - allows newlines in fields
     *
     * @var string
     * @psalm-var 'input'|'textarea'
     */
    public $CharEditing;

    /**
     * The minimum size for character input fields
     *
     * @var int
     * @psalm-var 0|positive-int
     */
    public $MinSizeForInputField;

    /**
     * The maximum size for character input fields
     *
     * @var int
     * @psalm-var positive-int
     */
    public $MaxSizeForInputField;

    /**
     * How many rows can be inserted at one time
     *
     * @var int
     * @psalm-var positive-int
     */
    public $InsertRows;

    /**
     * Sort order for items in a foreign-key drop-down list.
     * 'content' is the referenced data, 'id' is the key value.
     *
     * @var string[]
     * @psalm-var array{0: 'content-id'|'id-content', 1?: 'content-id'|'id-content'}
     */
    public $ForeignKeyDropdownOrder;

    /**
     * A drop-down list will be used if fewer items are present
     *
     * @var int
     * @psalm-var positive-int
     */
    public $ForeignKeyMaxLimit;

    /**
     * Whether to disable foreign key checks while importing
     *
     * @var string
     * @psalm-var 'default'|'enable'|'disable'
     */
    public $DefaultForeignKeyChecks;

    /**
     * Allow for the use of zip compression (requires zip support to be enabled)
     *
     * @var bool
     */
    public $ZipDump;

    /**
     * Allow for the use of gzip compression (requires zlib)
     *
     * @var bool
     */
    public $GZipDump;

    /**
     * Allow for the use of bzip2 decompression (requires bz2 extension)
     *
     * @var bool
     */
    public $BZipDump;

    /**
     * Will compress gzip exports on the fly without the need for much memory.
     * If you encounter problems with created gzip files disable this feature.
     *
     * @var bool
     */
    public $CompressOnFly;

    /**
     * How to display the menu tabs ('icons'|'text'|'both')
     *
     * @var string
     * @psalm-var 'icons'|'text'|'both'
     */
    public $TabsMode;

    /**
     * How to display various action links ('icons'|'text'|'both')
     *
     * @var string
     * @psalm-var 'icons'|'text'|'both'
     */
    public $ActionLinksMode;

    /**
     * How many columns should be used for table display of a database?
     * (a value larger than 1 results in some information being hidden)
     *
     * @var int
     * @psalm-var positive-int
     */
    public $PropertiesNumColumns;

    /**
     * Possible values:
     * 'welcome' = the welcome page (recommended for multiuser setups)
     * 'databases' = list of databases
     * 'status' = runtime information
     * 'variables' = MySQL server variables
     * 'privileges' = user management
     *
     * @var string
     * @psalm-var 'welcome'|'databases'|'status'|'variables'|'privileges'
     */
    public $DefaultTabServer;

    /**
     * Possible values:
     * 'structure' = tables list
     * 'sql' = SQL form
     * 'search' = search query
     * 'operations' = operations on database
     *
     * @var string
     * @psalm-var 'structure'|'sql'|'search'|'operations'
     */
    public $DefaultTabDatabase;

    /**
     * Possible values:
     * 'structure' = fields list
     * 'sql' = SQL form
     * 'search' = search page
     * 'insert' = insert row page
     * 'browse' = browse page
     *
     * @var string
     * @psalm-var 'structure'|'sql'|'search'|'insert'|'browse'
     */
    public $DefaultTabTable;

    /**
     * Whether to display image or text or both image and text in table row
     * action segment. Value can be either of ``image``, ``text`` or ``both``.
     *
     * @var string
     * @psalm-var 'icons'|'text'|'both'
     */
    public $RowActionType;

    /** @var Export */
    public $Export;

    /** @var Import */
    public $Import;

    /** @var Schema */
    public $Schema;

    /** @var string[] */
    public $PDFPageSizes;

    /** @var string */
    public $PDFDefaultPageSize;

    /**
     * Default language to use, if not browser-defined or user-defined
     *
     * @var string
     */
    public $DefaultLang;

    /**
     * Default connection collation
     *
     * @var string
     */
    public $DefaultConnectionCollation;

    /**
     * Force: always use this language, e.g. 'en'
     *
     * @var string
     */
    public $Lang;

    /**
     * Regular expression to limit listed languages, e.g. '^(cs|en)' for Czech and
     * English only
     *
     * @var string
     */
    public $FilterLanguages;

    /**
     * You can select here which functions will be used for character set conversion.
     * Possible values are:
     *      auto   - automatically use available one (first is tested iconv, then recode)
     *      iconv  - use iconv or libiconv functions
     *      recode - use recode_string function
     *      mb     - use mbstring extension
     *      none   - disable encoding conversion
     *
     * @var string
     * @psalm-var 'auto'|'iconv'|'recode'|'mb'|'none'
     */
    public $RecodingEngine;

    /**
     * Specify some parameters for iconv used in character set conversion. See iconv
     * documentation for details:
     * https://www.gnu.org/savannah-checkouts/gnu/libiconv/documentation/libiconv-1.15/iconv_open.3.html
     *
     * @var string
     */
    public $IconvExtraParams;

    /**
     * Available character sets for MySQL conversion. currently contains all which could
     * be found in lang/* files and few more.
     * Character sets will be shown in same order as here listed, so if you frequently
     * use some of these move them to the top.
     *
     * @var string[]
     */
    public $AvailableCharsets;

    /**
     * enable the left panel pointer
     *
     * @var bool
     */
    public $NavigationTreePointerEnable;

    /**
     * enable the browse pointer
     *
     * @var bool
     */
    public $BrowsePointerEnable;

    /**
     * enable the browse marker
     *
     * @var bool
     */
    public $BrowseMarkerEnable;

    /**
     * textarea size (columns) in edit mode
     * (this value will be emphasized (*2) for SQL
     * query textareas and (*1.25) for query window)
     *
     * @var int
     * @psalm-var positive-int
     */
    public $TextareaCols;

    /**
     * textarea size (rows) in edit mode
     *
     * @var int
     * @psalm-var positive-int
     */
    public $TextareaRows;

    /**
     * double size of textarea size for LONGTEXT columns
     *
     * @var bool
     */
    public $LongtextDoubleTextarea;

    /**
     * auto-select when clicking in the textarea of the query-box
     *
     * @var bool
     */
    public $TextareaAutoSelect;

    /**
     * textarea size (columns) for CHAR/VARCHAR
     *
     * @var int
     * @psalm-var positive-int
     */
    public $CharTextareaCols;

    /**
     * textarea size (rows) for CHAR/VARCHAR
     *
     * @var int
     * @psalm-var positive-int
     */
    public $CharTextareaRows;

    /**
     * Max field data length in browse mode for all non-numeric fields
     *
     * @var int
     * @psalm-var positive-int
     */
    public $LimitChars;

    /**
     * Where to show the edit/copy/delete links in browse mode
     * Possible values are 'left', 'right', 'both' and 'none'.
     *
     * @var string
     * @psalm-var 'left'|'right'|'both'|'none'
     */
    public $RowActionLinks;

    /**
     * Whether to show row links (Edit, Copy, Delete) and checkboxes for
     * multiple row operations even when the selection does not have a unique key.
     *
     * @var bool
     */
    public $RowActionLinksWithoutUnique;

    /**
     * Default sort order by primary key.
     *
     * @var string
     * @psalm-var 'NONE'|'ASC'|'DESC'
     */
    public $TablePrimaryKeyOrder;

    /**
     * remember the last way a table sorted
     *
     * @var bool
     */
    public $RememberSorting;

    /**
     * shows column comments in 'browse' mode.
     *
     * @var bool
     */
    public $ShowBrowseComments;

    /**
     * shows column comments in 'table property' mode.
     *
     * @var bool
     */
    public $ShowPropertyComments;

    /**
     * repeat header names every X cells? (0 = deactivate)
     *
     * @var int
     * @psalm-var 0|positive-int
     */
    public $RepeatCells;

    /**
     * Set to true if you want DB-based query history.If false, this utilizes
     * JS-routines to display query history (lost by window close)
     *
     * @var bool
     */
    public $QueryHistoryDB;

    /**
     * When using DB-based query history, how many entries should be kept?
     *
     * @var int
     * @psalm-var positive-int
     */
    public $QueryHistoryMax;

    /**
     * Use MIME-Types (stored in column comments table) for
     *
     * @var bool
     */
    public $BrowseMIME;

    /**
     * When approximate count < this, PMA will get exact count for table rows.
     *
     * @var int
     * @psalm-var positive-int
     */
    public $MaxExactCount;

    /**
     * Zero means that no row count is done for views; see the doc
     *
     * @var int
     * @psalm-var 0|positive-int
     */
    public $MaxExactCountViews;

    /**
     * Sort table and database in natural order
     *
     * @var bool
     */
    public $NaturalOrder;

    /**
     * Initial state for sliders
     * (open | closed | disabled)
     *
     * @var string
     * @psalm-var 'open'|'closed'|'disabled'
     */
    public $InitialSlidersState;

    /**
     * User preferences: disallow these settings
     * For possible setting names look in libraries/config/user_preferences.forms.php
     *
     * @var string[]
     */
    public $UserprefsDisallow;

    /**
     * User preferences: enable the Developer tab
     *
     * @var bool
     */
    public $UserprefsDeveloperTab;

    /**
     * title of browser window when a table is selected
     *
     * @var string
     */
    public $TitleTable;

    /**
     * title of browser window when a database is selected
     *
     * @var string
     */
    public $TitleDatabase;

    /**
     * title of browser window when a server is selected
     *
     * @var string
     */
    public $TitleServer;

    /**
     * title of browser window when nothing is selected
     *
     * @var string
     */
    public $TitleDefault;

    /**
     * if you want to use selectable themes and if ThemesPath not empty
     * set it to true, else set it to false (default is false);
     *
     * @var bool
     */
    public $ThemeManager;

    /**
     * set up default theme, you can set up here an valid
     * path to themes or 'original' for the original pma-theme
     *
     * @var string
     */
    public $ThemeDefault;

    /**
     * allow different theme for each configured server
     *
     * @var bool
     */
    public $ThemePerServer;

    /**
     * Default query for table
     *
     * @var string
     */
    public $DefaultQueryTable;

    /**
     * Default query for database
     *
     * @var string
     */
    public $DefaultQueryDatabase;

    /**
     * SQL Query box settings
     * These are the links display in all of the SQL Query boxes
     *
     * @var SqlQueryBox
     */
    public $SQLQuery;

    /**
     * Enables autoComplete for table & column names in SQL queries
     *
     * @var bool
     */
    public $EnableAutocompleteForTablesAndColumns;

    /**
     * Directory for uploaded files that can be executed by phpMyAdmin.
     * For example './upload'. Leave empty for no upload directory support.
     * Use %u for username inclusion.
     *
     * @var string
     */
    public $UploadDir;

    /**
     * Directory where phpMyAdmin can save exported data on server.
     * For example './save'. Leave empty for no save directory support.
     * Use %u for username inclusion.
     *
     * @var string
     */
    public $SaveDir;

    /**
     * Directory where phpMyAdmin can save temporary files.
     *
     * @var string
     */
    public $TempDir;

    /**
     * Is GD >= 2 available? Set to yes/no/auto. 'auto' does auto-detection,
     * which is the only safe way to determine GD version.
     *
     * @var string
     * @psalm-var 'auto'|'yes'|'no'
     */
    public $GD2Available;

    /**
     * Lists proxy IP and HTTP header combinations which are trusted for IP allow/deny
     *
     * @var array<string, string>
     */
    public $TrustedProxies;

    /**
     * We normally check the permissions on the configuration file to ensure
     * it's not world writable. However, phpMyAdmin could be installed on
     * a NTFS filesystem mounted on a non-Windows server, in which case the
     * permissions seems wrong but in fact cannot be detected. In this case
     * a sysadmin would set the following to false.
     *
     * @var bool
     */
    public $CheckConfigurationPermissions;

    /**
     * Limit for length of URL in links. When length would be above this limit, it
     * is replaced by form with button.
     * This is required as some web servers (IIS) have problems with long URLs.
     * The recommended limit is 2000
     * (see https://www.boutell.com/newfaq/misc/urllength.html) but we put
     * 1000 to accommodate Suhosin, see bug #3358750.
     *
     * @var int
     * @psalm-var positive-int
     */
    public $LinkLengthLimit;

    /**
     * Additional string to allow in CSP headers.
     *
     * @var string
     */
    public $CSPAllow;

    /**
     * Disable the table maintenance mass operations, like optimizing or
     * repairing the selected tables of a database. An accidental execution
     * of such a maintenance task can enormously slow down a bigger database.
     *
     * @var bool
     */
    public $DisableMultiTableMaintenance;

    /**
     * Whether or not to query the user before sending the error report to
     * the phpMyAdmin team when a JavaScript error occurs
     *
     * Available options
     * (ask | always | never)
     *
     * @var string
     * @psalm-var 'ask'|'always'|'never'
     */
    public $SendErrorReports;

    /**
     * Whether Enter or Ctrl+Enter executes queries in the console.
     *
     * @var bool
     */
    public $ConsoleEnterExecutes;

    /**
     * Zero Configuration mode.
     *
     * @var bool
     */
    public $ZeroConf;

    /**
     * Developers ONLY!
     *
     * @var Debug
     */
    public $DBG;

    /**
     * Sets the working environment
     *
     * This only needs to be changed when you are developing phpMyAdmin itself.
     * The development mode may display debug information in some places.
     *
     * Possible values are 'production' or 'development'
     *
     * @var string
     * @psalm-var 'production'|'development'
     */
    public $environment;

    /**
     * Default functions for above defined groups
     *
     * @var array<string, string>
     */
    public $DefaultFunctions;

    /**
     * Max rows retrieved for zoom search
     *
     * @var int
     * @psalm-var positive-int
     */
    public $maxRowPlotLimit;

    /**
     * Show Git revision if applicable
     *
     * @var bool
     */
    public $ShowGitRevision;

    /**
     * MySQL minimal version required
     *
     * @var array<string, int|string>
     * @psalm-var array{internal: int, human: string}
     */
    public $MysqlMinVersion;

    /**
     * Disable shortcuts
     *
     * @var bool
     */
    public $DisableShortcutKeys;

    /**
     * Console configuration
     *
     * This is mostly meant for user preferences.
     *
     * @var Console
     */
    public $Console;

    /**
     * Initialize default transformations array
     *
     * @var Transformations
     */
    public $DefaultTransformations;

    /**
     * Set default for FirstDayOfCalendar
     *
     * @var int
     * @psalm-var 0|positive-int
     */
    public $FirstDayOfCalendar;

    /**
     * @param array<int|string, mixed> $settings
     */
    public function __construct(array $settings)
    {
        $this->PmaAbsoluteUri = $this->setPmaAbsoluteUri($settings);
        $this->AuthLog = $this->setAuthLog($settings);
        $this->AuthLogSuccess = $this->setAuthLogSuccess($settings);
        $this->PmaNoRelation_DisableWarning = $this->setPmaNoRelationDisableWarning($settings);
        $this->SuhosinDisableWarning = $this->setSuhosinDisableWarning($settings);
        $this->LoginCookieValidityDisableWarning = $this->setLoginCookieValidityDisableWarning($settings);
        $this->ReservedWordDisableWarning = $this->setReservedWordDisableWarning($settings);
        $this->TranslationWarningThreshold = $this->setTranslationWarningThreshold($settings);
        $this->AllowThirdPartyFraming = $this->setAllowThirdPartyFraming($settings);
        $this->blowfish_secret = $this->setBlowfishSecret($settings);
        $this->Servers = $this->setServers($settings);
        $this->ServerDefault = $this->setServerDefault($settings);
        $this->VersionCheck = $this->setVersionCheck($settings);
        $this->ProxyUrl = $this->setProxyUrl($settings);
        $this->ProxyUser = $this->setProxyUser($settings);
        $this->ProxyPass = $this->setProxyPass($settings);
        $this->MaxDbList = $this->setMaxDbList($settings);
        $this->MaxTableList = $this->setMaxTableList($settings);
        $this->ShowHint = $this->setShowHint($settings);
        $this->MaxCharactersInDisplayedSQL = $this->setMaxCharactersInDisplayedSQL($settings);
        $this->OBGzip = $this->setOBGzip($settings);
        $this->PersistentConnections = $this->setPersistentConnections($settings);
        $this->ExecTimeLimit = $this->setExecTimeLimit($settings);
        $this->SessionSavePath = $this->setSessionSavePath($settings);
        $this->MysqlSslWarningSafeHosts = $this->setMysqlSslWarningSafeHosts($settings);
        $this->MemoryLimit = $this->setMemoryLimit($settings);
        $this->SkipLockedTables = $this->setSkipLockedTables($settings);
        $this->ShowSQL = $this->setShowSQL($settings);
        $this->RetainQueryBox = $this->setRetainQueryBox($settings);
        $this->CodemirrorEnable = $this->setCodemirrorEnable($settings);
        $this->LintEnable = $this->setLintEnable($settings);
        $this->AllowUserDropDatabase = $this->setAllowUserDropDatabase($settings);
        $this->Confirm = $this->setConfirm($settings);
        $this->CookieSameSite = $this->setCookieSameSite($settings);
        $this->LoginCookieRecall = $this->setLoginCookieRecall($settings);
        $this->LoginCookieValidity = $this->setLoginCookieValidity($settings);
        $this->LoginCookieStore = $this->setLoginCookieStore($settings);
        $this->LoginCookieDeleteAll = $this->setLoginCookieDeleteAll($settings);
        $this->UseDbSearch = $this->setUseDbSearch($settings);
        $this->IgnoreMultiSubmitErrors = $this->setIgnoreMultiSubmitErrors($settings);
        $this->URLQueryEncryption = $this->setURLQueryEncryption($settings);
        $this->URLQueryEncryptionSecretKey = $this->setURLQueryEncryptionSecretKey($settings);
        $this->AllowArbitraryServer = $this->setAllowArbitraryServer($settings);
        $this->ArbitraryServerRegexp = $this->setArbitraryServerRegexp($settings);
        $this->CaptchaMethod = $this->setCaptchaMethod($settings);
        $this->CaptchaApi = $this->setCaptchaApi($settings);
        $this->CaptchaCsp = $this->setCaptchaCsp($settings);
        $this->CaptchaRequestParam = $this->setCaptchaRequestParam($settings);
        $this->CaptchaResponseParam = $this->setCaptchaResponseParam($settings);
        $this->CaptchaLoginPublicKey = $this->setCaptchaLoginPublicKey($settings);
        $this->CaptchaLoginPrivateKey = $this->setCaptchaLoginPrivateKey($settings);
        $this->CaptchaSiteVerifyURL = $this->setCaptchaSiteVerifyURL($settings);
        $this->enable_drag_drop_import = $this->setEnableDragDropImport($settings);
        $this->ShowDatabasesNavigationAsTree = $this->setShowDatabasesNavigationAsTree($settings);
        $this->FirstLevelNavigationItems = $this->setFirstLevelNavigationItems($settings);
        $this->MaxNavigationItems = $this->setMaxNavigationItems($settings);
        $this->NavigationTreeEnableGrouping = $this->setNavigationTreeEnableGrouping($settings);
        $this->NavigationTreeDbSeparator = $this->setNavigationTreeDbSeparator($settings);
        $this->NavigationTreeTableSeparator = $this->setNavigationTreeTableSeparator($settings);
        $this->NavigationTreeTableLevel = $this->setNavigationTreeTableLevel($settings);
        $this->NavigationLinkWithMainPanel = $this->setNavigationLinkWithMainPanel($settings);
        $this->NavigationDisplayLogo = $this->setNavigationDisplayLogo($settings);
        $this->NavigationLogoLink = $this->setNavigationLogoLink($settings);
        $this->NavigationLogoLinkWindow = $this->setNavigationLogoLinkWindow($settings);
        $this->NumRecentTables = $this->setNumRecentTables($settings);
        $this->NumFavoriteTables = $this->setNumFavoriteTables($settings);
        $this->NavigationTreeDisplayItemFilterMinimum = $this->setNavigationTreeDisplayItemFilterMinimum($settings);
        $this->NavigationDisplayServers = $this->setNavigationDisplayServers($settings);
        $this->DisplayServersList = $this->setDisplayServersList($settings);
        $this->NavigationTreeDisplayDbFilterMinimum = $this->setNavigationTreeDisplayDbFilterMinimum($settings);
        $this->NavigationTreeDefaultTabTable = $this->setNavigationTreeDefaultTabTable($settings);
        $this->NavigationTreeDefaultTabTable2 = $this->setNavigationTreeDefaultTabTable2($settings);
        $this->NavigationTreeEnableExpansion = $this->setNavigationTreeEnableExpansion($settings);
        $this->NavigationTreeShowTables = $this->setNavigationTreeShowTables($settings);
        $this->NavigationTreeShowViews = $this->setNavigationTreeShowViews($settings);
        $this->NavigationTreeShowFunctions = $this->setNavigationTreeShowFunctions($settings);
        $this->NavigationTreeShowProcedures = $this->setNavigationTreeShowProcedures($settings);
        $this->NavigationTreeShowEvents = $this->setNavigationTreeShowEvents($settings);
        $this->NavigationWidth = $this->setNavigationWidth($settings);
        $this->NavigationTreeAutoexpandSingleDb = $this->setNavigationTreeAutoexpandSingleDb($settings);
        $this->ShowStats = $this->setShowStats($settings);
        $this->ShowPhpInfo = $this->setShowPhpInfo($settings);
        $this->ShowServerInfo = $this->setShowServerInfo($settings);
        $this->ShowChgPassword = $this->setShowChgPassword($settings);
        $this->ShowCreateDb = $this->setShowCreateDb($settings);
        $this->ShowDbStructureCharset = $this->setShowDbStructureCharset($settings);
        $this->ShowDbStructureComment = $this->setShowDbStructureComment($settings);
        $this->ShowDbStructureCreation = $this->setShowDbStructureCreation($settings);
        $this->ShowDbStructureLastUpdate = $this->setShowDbStructureLastUpdate($settings);
        $this->ShowDbStructureLastCheck = $this->setShowDbStructureLastCheck($settings);
        $this->HideStructureActions = $this->setHideStructureActions($settings);
        $this->ShowColumnComments = $this->setShowColumnComments($settings);
        $this->TableNavigationLinksMode = $this->setTableNavigationLinksMode($settings);
        $this->ShowAll = $this->setShowAll($settings);
        $this->MaxRows = $this->setMaxRows($settings);
        $this->Order = $this->setOrder($settings);
        $this->SaveCellsAtOnce = $this->setSaveCellsAtOnce($settings);
        $this->GridEditing = $this->setGridEditing($settings);
        $this->RelationalDisplay = $this->setRelationalDisplay($settings);
        $this->ProtectBinary = $this->setProtectBinary($settings);
        $this->ShowFunctionFields = $this->setShowFunctionFields($settings);
        $this->ShowFieldTypesInDataEditView = $this->setShowFieldTypesInDataEditView($settings);
        $this->CharEditing = $this->setCharEditing($settings);
        $this->MinSizeForInputField = $this->setMinSizeForInputField($settings);
        $this->MaxSizeForInputField = $this->setMaxSizeForInputField($settings);
        $this->InsertRows = $this->setInsertRows($settings);
        $this->ForeignKeyDropdownOrder = $this->setForeignKeyDropdownOrder($settings);
        $this->ForeignKeyMaxLimit = $this->setForeignKeyMaxLimit($settings);
        $this->DefaultForeignKeyChecks = $this->setDefaultForeignKeyChecks($settings);
        $this->ZipDump = $this->setZipDump($settings);
        $this->GZipDump = $this->setGZipDump($settings);
        $this->BZipDump = $this->setBZipDump($settings);
        $this->CompressOnFly = $this->setCompressOnFly($settings);
        $this->TabsMode = $this->setTabsMode($settings);
        $this->ActionLinksMode = $this->setActionLinksMode($settings);
        $this->PropertiesNumColumns = $this->setPropertiesNumColumns($settings);
        $this->DefaultTabServer = $this->setDefaultTabServer($settings);
        $this->DefaultTabDatabase = $this->setDefaultTabDatabase($settings);
        $this->DefaultTabTable = $this->setDefaultTabTable($settings);
        $this->RowActionType = $this->setRowActionType($settings);
        $this->Export = $this->setExport($settings);
        $this->Import = $this->setImport($settings);
        $this->Schema = $this->setSchema($settings);
        $this->PDFPageSizes = $this->setPDFPageSizes($settings);
        $this->PDFDefaultPageSize = $this->setPDFDefaultPageSize($settings);
        $this->DefaultLang = $this->setDefaultLang($settings);
        $this->DefaultConnectionCollation = $this->setDefaultConnectionCollation($settings);
        $this->Lang = $this->setLang($settings);
        $this->FilterLanguages = $this->setFilterLanguages($settings);
        $this->RecodingEngine = $this->setRecodingEngine($settings);
        $this->IconvExtraParams = $this->setIconvExtraParams($settings);
        $this->AvailableCharsets = $this->setAvailableCharsets($settings);
        $this->NavigationTreePointerEnable = $this->setNavigationTreePointerEnable($settings);
        $this->BrowsePointerEnable = $this->setBrowsePointerEnable($settings);
        $this->BrowseMarkerEnable = $this->setBrowseMarkerEnable($settings);
        $this->TextareaCols = $this->setTextareaCols($settings);
        $this->TextareaRows = $this->setTextareaRows($settings);
        $this->LongtextDoubleTextarea = $this->setLongtextDoubleTextarea($settings);
        $this->TextareaAutoSelect = $this->setTextareaAutoSelect($settings);
        $this->CharTextareaCols = $this->setCharTextareaCols($settings);
        $this->CharTextareaRows = $this->setCharTextareaRows($settings);
        $this->LimitChars = $this->setLimitChars($settings);
        $this->RowActionLinks = $this->setRowActionLinks($settings);
        $this->RowActionLinksWithoutUnique = $this->setRowActionLinksWithoutUnique($settings);
        $this->TablePrimaryKeyOrder = $this->setTablePrimaryKeyOrder($settings);
        $this->RememberSorting = $this->setRememberSorting($settings);
        $this->ShowBrowseComments = $this->setShowBrowseComments($settings);
        $this->ShowPropertyComments = $this->setShowPropertyComments($settings);
        $this->RepeatCells = $this->setRepeatCells($settings);
        $this->QueryHistoryDB = $this->setQueryHistoryDB($settings);
        $this->QueryHistoryMax = $this->setQueryHistoryMax($settings);
        $this->BrowseMIME = $this->setBrowseMIME($settings);
        $this->MaxExactCount = $this->setMaxExactCount($settings);
        $this->MaxExactCountViews = $this->setMaxExactCountViews($settings);
        $this->NaturalOrder = $this->setNaturalOrder($settings);
        $this->InitialSlidersState = $this->setInitialSlidersState($settings);
        $this->UserprefsDisallow = $this->setUserprefsDisallow($settings);
        $this->UserprefsDeveloperTab = $this->setUserprefsDeveloperTab($settings);
        $this->TitleTable = $this->setTitleTable($settings);
        $this->TitleDatabase = $this->setTitleDatabase($settings);
        $this->TitleServer = $this->setTitleServer($settings);
        $this->TitleDefault = $this->setTitleDefault($settings);
        $this->ThemeManager = $this->setThemeManager($settings);
        $this->ThemeDefault = $this->setThemeDefault($settings);
        $this->ThemePerServer = $this->setThemePerServer($settings);
        $this->DefaultQueryTable = $this->setDefaultQueryTable($settings);
        $this->DefaultQueryDatabase = $this->setDefaultQueryDatabase($settings);
        $this->SQLQuery = $this->setSQLQuery($settings);
        $this->EnableAutocompleteForTablesAndColumns = $this->setEnableAutocompleteForTablesAndColumns($settings);
        $this->UploadDir = $this->setUploadDir($settings);
        $this->SaveDir = $this->setSaveDir($settings);
        $this->TempDir = $this->setTempDir($settings);
        $this->GD2Available = $this->setGD2Available($settings);
        $this->TrustedProxies = $this->setTrustedProxies($settings);
        $this->CheckConfigurationPermissions = $this->setCheckConfigurationPermissions($settings);
        $this->LinkLengthLimit = $this->setLinkLengthLimit($settings);
        $this->CSPAllow = $this->setCSPAllow($settings);
        $this->DisableMultiTableMaintenance = $this->setDisableMultiTableMaintenance($settings);
        $this->SendErrorReports = $this->setSendErrorReports($settings);
        $this->ConsoleEnterExecutes = $this->setConsoleEnterExecutes($settings);
        $this->ZeroConf = $this->setZeroConf($settings);
        $this->DBG = $this->setDBG($settings);
        $this->environment = $this->setEnvironment($settings);
        $this->DefaultFunctions = $this->setDefaultFunctions($settings);
        $this->maxRowPlotLimit = $this->setMaxRowPlotLimit($settings);
        $this->ShowGitRevision = $this->setShowGitRevision($settings);
        $this->MysqlMinVersion = $this->setMysqlMinVersion($settings);
        $this->DisableShortcutKeys = $this->setDisableShortcutKeys($settings);
        $this->Console = $this->setConsole($settings);
        $this->DefaultTransformations = $this->setDefaultTransformations($settings);
        $this->FirstDayOfCalendar = $this->setFirstDayOfCalendar($settings);
    }

    /**
     * @return array<string, array|bool|int|string|null>
     */
    public function toArray(): array
    {
        $settings = get_object_vars($this);
        $settings['Console'] = get_object_vars($this->Console);
        $settings['DBG'] = get_object_vars($this->DBG);
        $settings['SQLQuery'] = get_object_vars($this->SQLQuery);
        $settings['Export'] = get_object_vars($this->Export);
        $settings['Import'] = get_object_vars($this->Import);
        $settings['Schema'] = get_object_vars($this->Schema);
        $settings['DefaultTransformations'] = get_object_vars($this->DefaultTransformations);

        foreach (array_keys($settings['Servers']) as $key) {
            $settings['Servers'][$key] = get_object_vars($this->Servers[$key]);
        }

        return $settings;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setPmaAbsoluteUri(array $settings): string
    {
        if (! isset($settings['PmaAbsoluteUri'])) {
            return '';
        }

        return (string) $settings['PmaAbsoluteUri'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setAuthLog(array $settings): string
    {
        if (! isset($settings['AuthLog'])) {
            return 'auto';
        }

        return (string) $settings['AuthLog'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setAuthLogSuccess(array $settings): bool
    {
        if (! isset($settings['AuthLogSuccess'])) {
            return false;
        }

        return (bool) $settings['AuthLogSuccess'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setPmaNoRelationDisableWarning(array $settings): bool
    {
        if (! isset($settings['PmaNoRelation_DisableWarning'])) {
            return false;
        }

        return (bool) $settings['PmaNoRelation_DisableWarning'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setSuhosinDisableWarning(array $settings): bool
    {
        if (! isset($settings['SuhosinDisableWarning'])) {
            return false;
        }

        return (bool) $settings['SuhosinDisableWarning'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setLoginCookieValidityDisableWarning(array $settings): bool
    {
        if (! isset($settings['LoginCookieValidityDisableWarning'])) {
            return false;
        }

        return (bool) $settings['LoginCookieValidityDisableWarning'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setReservedWordDisableWarning(array $settings): bool
    {
        if (! isset($settings['ReservedWordDisableWarning'])) {
            return false;
        }

        return (bool) $settings['ReservedWordDisableWarning'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setTranslationWarningThreshold(array $settings): int
    {
        if (! isset($settings['TranslationWarningThreshold'])) {
            return 80;
        }

        $threshold = (int) $settings['TranslationWarningThreshold'];
        if ($threshold < 0) {
            return 80;
        }

        return min($threshold, 100);
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return bool|string
     * @psalm-return bool|'sameorigin'
     */
    private function setAllowThirdPartyFraming(array $settings)
    {
        if (! isset($settings['AllowThirdPartyFraming'])) {
            return false;
        }

        if ($settings['AllowThirdPartyFraming'] === 'sameorigin') {
            return 'sameorigin';
        }

        return (bool) $settings['AllowThirdPartyFraming'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setBlowfishSecret(array $settings): string
    {
        if (! isset($settings['blowfish_secret'])) {
            return '';
        }

        return (string) $settings['blowfish_secret'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return array<int, Server>
     * @psalm-return array<int<1, max>, Server>
     */
    private function setServers(array $settings): array
    {
        if (! isset($settings['Servers']) || ! is_array($settings['Servers'])) {
            return [1 => new Server()];
        }

        $servers = [];
        /**
         * @var int|string $key
         * @var mixed $server
         */
        foreach ($settings['Servers'] as $key => $server) {
            if (! is_int($key) || $key < 1 || ! is_array($server)) {
                continue;
            }

            $servers[$key] = new Server($server);
        }

        if (count($servers) === 0) {
            return [1 => new Server()];
        }

        return $servers;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 0|positive-int
     */
    private function setServerDefault(array $settings): int
    {
        if (! isset($settings['ServerDefault'])) {
            return 1;
        }

        $serverDefault = (int) $settings['ServerDefault'];

        return $serverDefault >= 0 ? $serverDefault : 1;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setVersionCheck(array $settings): bool
    {
        $versionCheck = true;
        if (defined('VERSION_CHECK_DEFAULT')) {
            $versionCheck = VERSION_CHECK_DEFAULT;
        }

        if (! isset($settings['VersionCheck'])) {
            return $versionCheck;
        }

        return (bool) $settings['VersionCheck'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setProxyUrl(array $settings): string
    {
        if (! isset($settings['ProxyUrl'])) {
            return '';
        }

        return (string) $settings['ProxyUrl'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setProxyUser(array $settings): string
    {
        if (! isset($settings['ProxyUser'])) {
            return '';
        }

        return (string) $settings['ProxyUser'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setProxyPass(array $settings): string
    {
        if (! isset($settings['ProxyPass'])) {
            return '';
        }

        return (string) $settings['ProxyPass'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setMaxDbList(array $settings): int
    {
        if (! isset($settings['MaxDbList'])) {
            return 100;
        }

        $maxDbList = (int) $settings['MaxDbList'];

        return $maxDbList >= 1 ? $maxDbList : 100;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setMaxTableList(array $settings): int
    {
        if (! isset($settings['MaxTableList'])) {
            return 250;
        }

        $maxTableList = (int) $settings['MaxTableList'];

        return $maxTableList >= 1 ? $maxTableList : 250;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowHint(array $settings): bool
    {
        if (! isset($settings['ShowHint'])) {
            return true;
        }

        return (bool) $settings['ShowHint'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setMaxCharactersInDisplayedSQL(array $settings): int
    {
        if (! isset($settings['MaxCharactersInDisplayedSQL'])) {
            return 1000;
        }

        $maxCharactersInDisplayedSQL = (int) $settings['MaxCharactersInDisplayedSQL'];

        return $maxCharactersInDisplayedSQL >= 1 ? $maxCharactersInDisplayedSQL : 1000;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return bool|string
     * @psalm-return 'auto'|bool
     */
    private function setOBGzip(array $settings)
    {
        if (! isset($settings['OBGzip']) || $settings['OBGzip'] === 'auto') {
            return 'auto';
        }

        return (bool) $settings['OBGzip'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setPersistentConnections(array $settings): bool
    {
        if (! isset($settings['PersistentConnections'])) {
            return false;
        }

        return (bool) $settings['PersistentConnections'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 0|positive-int
     */
    private function setExecTimeLimit(array $settings): int
    {
        if (! isset($settings['ExecTimeLimit'])) {
            return 300;
        }

        $execTimeLimit = (int) $settings['ExecTimeLimit'];

        return $execTimeLimit >= 0 ? $execTimeLimit : 300;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setSessionSavePath(array $settings): string
    {
        if (! isset($settings['SessionSavePath'])) {
            return '';
        }

        return (string) $settings['SessionSavePath'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return string[]
     */
    private function setMysqlSslWarningSafeHosts(array $settings): array
    {
        if (! isset($settings['MysqlSslWarningSafeHosts']) || ! is_array($settings['MysqlSslWarningSafeHosts'])) {
            return ['127.0.0.1', 'localhost'];
        }

        $hosts = [];
        /** @var mixed $host */
        foreach ($settings['MysqlSslWarningSafeHosts'] as $host) {
            $safeHost = (string) $host;
            if (strlen($safeHost) === 0) {
                continue;
            }

            $hosts[] = $safeHost;
        }

        return $hosts;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setMemoryLimit(array $settings): string
    {
        if (! isset($settings['MemoryLimit'])) {
            return '-1';
        }

        return (string) $settings['MemoryLimit'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setSkipLockedTables(array $settings): bool
    {
        if (! isset($settings['SkipLockedTables'])) {
            return false;
        }

        return (bool) $settings['SkipLockedTables'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowSQL(array $settings): bool
    {
        if (! isset($settings['ShowSQL'])) {
            return true;
        }

        return (bool) $settings['ShowSQL'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setRetainQueryBox(array $settings): bool
    {
        if (! isset($settings['RetainQueryBox'])) {
            return false;
        }

        return (bool) $settings['RetainQueryBox'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setCodemirrorEnable(array $settings): bool
    {
        if (! isset($settings['CodemirrorEnable'])) {
            return true;
        }

        return (bool) $settings['CodemirrorEnable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setLintEnable(array $settings): bool
    {
        if (! isset($settings['LintEnable'])) {
            return true;
        }

        return (bool) $settings['LintEnable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setAllowUserDropDatabase(array $settings): bool
    {
        if (! isset($settings['AllowUserDropDatabase'])) {
            return false;
        }

        return (bool) $settings['AllowUserDropDatabase'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setConfirm(array $settings): bool
    {
        if (! isset($settings['Confirm'])) {
            return true;
        }

        return (bool) $settings['Confirm'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'Lax'|'Strict'|'None'
     */
    private function setCookieSameSite(array $settings): string
    {
        if (! isset($settings['CookieSameSite']) || ! in_array($settings['CookieSameSite'], ['Lax', 'None'], true)) {
            return 'Strict';
        }

        return $settings['CookieSameSite'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setLoginCookieRecall(array $settings): bool
    {
        if (! isset($settings['LoginCookieRecall'])) {
            return true;
        }

        return (bool) $settings['LoginCookieRecall'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setLoginCookieValidity(array $settings): int
    {
        if (! isset($settings['LoginCookieValidity'])) {
            return 1440;
        }

        $loginCookieValidity = (int) $settings['LoginCookieValidity'];

        return $loginCookieValidity >= 1 ? $loginCookieValidity : 1440;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 0|positive-int
     */
    private function setLoginCookieStore(array $settings): int
    {
        if (! isset($settings['LoginCookieStore'])) {
            return 0;
        }

        $loginCookieStore = (int) $settings['LoginCookieStore'];

        return $loginCookieStore >= 1 ? $loginCookieStore : 0;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setLoginCookieDeleteAll(array $settings): bool
    {
        if (! isset($settings['LoginCookieDeleteAll'])) {
            return true;
        }

        return (bool) $settings['LoginCookieDeleteAll'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setUseDbSearch(array $settings): bool
    {
        if (! isset($settings['UseDbSearch'])) {
            return true;
        }

        return (bool) $settings['UseDbSearch'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setIgnoreMultiSubmitErrors(array $settings): bool
    {
        if (! isset($settings['IgnoreMultiSubmitErrors'])) {
            return false;
        }

        return (bool) $settings['IgnoreMultiSubmitErrors'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setURLQueryEncryption(array $settings): bool
    {
        if (! isset($settings['URLQueryEncryption'])) {
            return false;
        }

        return (bool) $settings['URLQueryEncryption'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setURLQueryEncryptionSecretKey(array $settings): string
    {
        if (! isset($settings['URLQueryEncryptionSecretKey'])) {
            return '';
        }

        return (string) $settings['URLQueryEncryptionSecretKey'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setAllowArbitraryServer(array $settings): bool
    {
        if (! isset($settings['AllowArbitraryServer'])) {
            return false;
        }

        return (bool) $settings['AllowArbitraryServer'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setArbitraryServerRegexp(array $settings): string
    {
        if (! isset($settings['ArbitraryServerRegexp'])) {
            return '';
        }

        return (string) $settings['ArbitraryServerRegexp'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'invisible'|'checkbox'
     */
    private function setCaptchaMethod(array $settings): string
    {
        if (! isset($settings['CaptchaMethod']) || $settings['CaptchaMethod'] !== 'checkbox') {
            return 'invisible';
        }

        return 'checkbox';
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setCaptchaApi(array $settings): string
    {
        if (! isset($settings['CaptchaApi'])) {
            return 'https://www.google.com/recaptcha/api.js';
        }

        return (string) $settings['CaptchaApi'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setCaptchaCsp(array $settings): string
    {
        if (! isset($settings['CaptchaCsp'])) {
            return 'https://apis.google.com https://www.google.com/recaptcha/'
                . ' https://www.gstatic.com/recaptcha/ https://ssl.gstatic.com/';
        }

        return (string) $settings['CaptchaCsp'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setCaptchaRequestParam(array $settings): string
    {
        if (! isset($settings['CaptchaRequestParam'])) {
            return 'g-recaptcha';
        }

        return (string) $settings['CaptchaRequestParam'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setCaptchaResponseParam(array $settings): string
    {
        if (! isset($settings['CaptchaResponseParam'])) {
            return 'g-recaptcha-response';
        }

        return (string) $settings['CaptchaResponseParam'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setCaptchaLoginPublicKey(array $settings): string
    {
        if (! isset($settings['CaptchaLoginPublicKey'])) {
            return '';
        }

        return (string) $settings['CaptchaLoginPublicKey'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setCaptchaLoginPrivateKey(array $settings): string
    {
        if (! isset($settings['CaptchaLoginPrivateKey'])) {
            return '';
        }

        return (string) $settings['CaptchaLoginPrivateKey'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setCaptchaSiteVerifyURL(array $settings): string
    {
        if (! isset($settings['CaptchaSiteVerifyURL'])) {
            return '';
        }

        return (string) $settings['CaptchaSiteVerifyURL'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setEnableDragDropImport(array $settings): bool
    {
        if (! isset($settings['enable_drag_drop_import'])) {
            return true;
        }

        return (bool) $settings['enable_drag_drop_import'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowDatabasesNavigationAsTree(array $settings): bool
    {
        if (! isset($settings['ShowDatabasesNavigationAsTree'])) {
            return true;
        }

        return (bool) $settings['ShowDatabasesNavigationAsTree'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setFirstLevelNavigationItems(array $settings): int
    {
        if (! isset($settings['FirstLevelNavigationItems'])) {
            return 100;
        }

        $firstLevelNavigationItems = (int) $settings['FirstLevelNavigationItems'];

        return $firstLevelNavigationItems >= 1 ? $firstLevelNavigationItems : 100;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setMaxNavigationItems(array $settings): int
    {
        if (! isset($settings['MaxNavigationItems'])) {
            return 50;
        }

        $maxNavigationItems = (int) $settings['MaxNavigationItems'];

        return $maxNavigationItems >= 1 ? $maxNavigationItems : 50;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationTreeEnableGrouping(array $settings): bool
    {
        if (! isset($settings['NavigationTreeEnableGrouping'])) {
            return true;
        }

        return (bool) $settings['NavigationTreeEnableGrouping'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationTreeDbSeparator(array $settings): string
    {
        if (! isset($settings['NavigationTreeDbSeparator'])) {
            return '_';
        }

        return (string) $settings['NavigationTreeDbSeparator'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return false|string|string[]
     */
    private function setNavigationTreeTableSeparator(array $settings)
    {
        if (! isset($settings['NavigationTreeTableSeparator'])) {
            return '__';
        }

        if ($settings['NavigationTreeTableSeparator'] === false) {
            return false;
        }

        if (! is_array($settings['NavigationTreeTableSeparator'])) {
            return (string) $settings['NavigationTreeTableSeparator'];
        }

        if (count($settings['NavigationTreeTableSeparator']) > 0) {
            $navigationTreeTableSeparator = [];
            /** @var mixed $separator */
            foreach ($settings['NavigationTreeTableSeparator'] as $separator) {
                $navigationTreeTableSeparator[] = (string) $separator;
            }

            return $navigationTreeTableSeparator;
        }

        return '__';
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setNavigationTreeTableLevel(array $settings): int
    {
        if (! isset($settings['NavigationTreeTableLevel'])) {
            return 1;
        }

        $navigationTreeTableLevel = (int) $settings['NavigationTreeTableLevel'];

        return $navigationTreeTableLevel >= 2 ? $navigationTreeTableLevel : 1;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationLinkWithMainPanel(array $settings): bool
    {
        if (! isset($settings['NavigationLinkWithMainPanel'])) {
            return true;
        }

        return (bool) $settings['NavigationLinkWithMainPanel'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationDisplayLogo(array $settings): bool
    {
        if (! isset($settings['NavigationDisplayLogo'])) {
            return true;
        }

        return (bool) $settings['NavigationDisplayLogo'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationLogoLink(array $settings): string
    {
        if (! isset($settings['NavigationLogoLink'])) {
            return 'index.php';
        }

        return (string) $settings['NavigationLogoLink'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'main'|'new'
     */
    private function setNavigationLogoLinkWindow(array $settings): string
    {
        if (! isset($settings['NavigationLogoLinkWindow']) || $settings['NavigationLogoLinkWindow'] !== 'new') {
            return 'main';
        }

        return 'new';
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 0|positive-int
     */
    private function setNumRecentTables(array $settings): int
    {
        if (! isset($settings['NumRecentTables'])) {
            return 10;
        }

        $numRecentTables = (int) $settings['NumRecentTables'];

        return $numRecentTables >= 0 ? $numRecentTables : 10;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 0|positive-int
     */
    private function setNumFavoriteTables(array $settings): int
    {
        if (! isset($settings['NumFavoriteTables'])) {
            return 10;
        }

        $numFavoriteTables = (int) $settings['NumFavoriteTables'];

        return $numFavoriteTables >= 0 ? $numFavoriteTables : 10;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setNavigationTreeDisplayItemFilterMinimum(array $settings): int
    {
        if (! isset($settings['NavigationTreeDisplayItemFilterMinimum'])) {
            return 30;
        }

        $navigationTreeDisplayItemFilterMinimum = (int) $settings['NavigationTreeDisplayItemFilterMinimum'];

        return $navigationTreeDisplayItemFilterMinimum >= 1 ? $navigationTreeDisplayItemFilterMinimum : 30;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationDisplayServers(array $settings): bool
    {
        if (! isset($settings['NavigationDisplayServers'])) {
            return true;
        }

        return (bool) $settings['NavigationDisplayServers'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setDisplayServersList(array $settings): bool
    {
        if (! isset($settings['DisplayServersList'])) {
            return false;
        }

        return (bool) $settings['DisplayServersList'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setNavigationTreeDisplayDbFilterMinimum(array $settings): int
    {
        if (! isset($settings['NavigationTreeDisplayDbFilterMinimum'])) {
            return 30;
        }

        $navigationTreeDisplayDbFilterMinimum = (int) $settings['NavigationTreeDisplayDbFilterMinimum'];

        return $navigationTreeDisplayDbFilterMinimum >= 1 ? $navigationTreeDisplayDbFilterMinimum : 30;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'structure'|'sql'|'search'|'insert'|'browse'
     */
    private function setNavigationTreeDefaultTabTable(array $settings): string
    {
        if (! isset($settings['NavigationTreeDefaultTabTable'])) {
            return 'structure';
        }

        switch ($settings['NavigationTreeDefaultTabTable']) {
            case 'sql':
            case 'tbl_sql.php':
                return 'sql';

            case 'search':
            case 'tbl_select.php':
                return 'search';

            case 'insert':
            case 'tbl_change.php':
                return 'insert';

            case 'browse':
            case 'sql.php':
                return 'browse';

            default:
                return 'structure';
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'structure'|'sql'|'search'|'insert'|'browse'|''
     */
    private function setNavigationTreeDefaultTabTable2(array $settings): string
    {
        if (! isset($settings['NavigationTreeDefaultTabTable2'])) {
            return '';
        }

        switch ($settings['NavigationTreeDefaultTabTable2']) {
            case 'structure':
            case 'tbl_structure.php':
                return 'structure';

            case 'sql':
            case 'tbl_sql.php':
                return 'sql';

            case 'search':
            case 'tbl_select.php':
                return 'search';

            case 'insert':
            case 'tbl_change.php':
                return 'insert';

            case 'browse':
            case 'sql.php':
                return 'browse';

            default:
                return '';
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationTreeEnableExpansion(array $settings): bool
    {
        if (! isset($settings['NavigationTreeEnableExpansion'])) {
            return true;
        }

        return (bool) $settings['NavigationTreeEnableExpansion'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationTreeShowTables(array $settings): bool
    {
        if (! isset($settings['NavigationTreeShowTables'])) {
            return true;
        }

        return (bool) $settings['NavigationTreeShowTables'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationTreeShowViews(array $settings): bool
    {
        if (! isset($settings['NavigationTreeShowViews'])) {
            return true;
        }

        return (bool) $settings['NavigationTreeShowViews'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationTreeShowFunctions(array $settings): bool
    {
        if (! isset($settings['NavigationTreeShowFunctions'])) {
            return true;
        }

        return (bool) $settings['NavigationTreeShowFunctions'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationTreeShowProcedures(array $settings): bool
    {
        if (! isset($settings['NavigationTreeShowProcedures'])) {
            return true;
        }

        return (bool) $settings['NavigationTreeShowProcedures'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationTreeShowEvents(array $settings): bool
    {
        if (! isset($settings['NavigationTreeShowEvents'])) {
            return true;
        }

        return (bool) $settings['NavigationTreeShowEvents'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 0|positive-int
     */
    private function setNavigationWidth(array $settings): int
    {
        if (! isset($settings['NavigationWidth'])) {
            return 240;
        }

        $navigationWidth = (int) $settings['NavigationWidth'];

        return $navigationWidth >= 0 ? $navigationWidth : 240;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationTreeAutoexpandSingleDb(array $settings): bool
    {
        if (! isset($settings['NavigationTreeAutoexpandSingleDb'])) {
            return true;
        }

        return (bool) $settings['NavigationTreeAutoexpandSingleDb'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowStats(array $settings): bool
    {
        if (! isset($settings['ShowStats'])) {
            return true;
        }

        return (bool) $settings['ShowStats'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowPhpInfo(array $settings): bool
    {
        if (! isset($settings['ShowPhpInfo'])) {
            return false;
        }

        return (bool) $settings['ShowPhpInfo'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowServerInfo(array $settings): bool
    {
        if (! isset($settings['ShowServerInfo'])) {
            return true;
        }

        return (bool) $settings['ShowServerInfo'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowChgPassword(array $settings): bool
    {
        if (! isset($settings['ShowChgPassword'])) {
            return true;
        }

        return (bool) $settings['ShowChgPassword'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowCreateDb(array $settings): bool
    {
        if (! isset($settings['ShowCreateDb'])) {
            return true;
        }

        return (bool) $settings['ShowCreateDb'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowDbStructureCharset(array $settings): bool
    {
        if (! isset($settings['ShowDbStructureCharset'])) {
            return false;
        }

        return (bool) $settings['ShowDbStructureCharset'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowDbStructureComment(array $settings): bool
    {
        if (! isset($settings['ShowDbStructureComment'])) {
            return false;
        }

        return (bool) $settings['ShowDbStructureComment'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowDbStructureCreation(array $settings): bool
    {
        if (! isset($settings['ShowDbStructureCreation'])) {
            return false;
        }

        return (bool) $settings['ShowDbStructureCreation'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowDbStructureLastUpdate(array $settings): bool
    {
        if (! isset($settings['ShowDbStructureLastUpdate'])) {
            return false;
        }

        return (bool) $settings['ShowDbStructureLastUpdate'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowDbStructureLastCheck(array $settings): bool
    {
        if (! isset($settings['ShowDbStructureLastCheck'])) {
            return false;
        }

        return (bool) $settings['ShowDbStructureLastCheck'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setHideStructureActions(array $settings): bool
    {
        if (! isset($settings['HideStructureActions'])) {
            return true;
        }

        return (bool) $settings['HideStructureActions'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowColumnComments(array $settings): bool
    {
        if (! isset($settings['ShowColumnComments'])) {
            return true;
        }

        return (bool) $settings['ShowColumnComments'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'text'|'icons'|'both'
     */
    private function setTableNavigationLinksMode(array $settings): string
    {
        if (
            ! isset($settings['TableNavigationLinksMode'])
            || ! in_array($settings['TableNavigationLinksMode'], ['text', 'both'], true)
        ) {
            return 'icons';
        }

        return $settings['TableNavigationLinksMode'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowAll(array $settings): bool
    {
        if (! isset($settings['ShowAll'])) {
            return false;
        }

        return (bool) $settings['ShowAll'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setMaxRows(array $settings): int
    {
        if (! isset($settings['MaxRows'])) {
            return 25;
        }

        $maxRows = (int) $settings['MaxRows'];

        return $maxRows >= 1 ? $maxRows : 25;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'ASC'|'DESC'|'SMART'
     */
    private function setOrder(array $settings): string
    {
        if (! isset($settings['Order']) || ! in_array($settings['Order'], ['ASC', 'DESC'], true)) {
            return 'SMART';
        }

        return $settings['Order'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setSaveCellsAtOnce(array $settings): bool
    {
        if (! isset($settings['SaveCellsAtOnce'])) {
            return false;
        }

        return (bool) $settings['SaveCellsAtOnce'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'double-click'|'click'|'disabled'
     */
    private function setGridEditing(array $settings): string
    {
        if (! isset($settings['GridEditing']) || ! in_array($settings['GridEditing'], ['click', 'disabled'], true)) {
            return 'double-click';
        }

        return $settings['GridEditing'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'K'|'D'
     */
    private function setRelationalDisplay(array $settings): string
    {
        if (! isset($settings['RelationalDisplay']) || $settings['RelationalDisplay'] !== 'D') {
            return 'K';
        }

        return 'D';
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return false|string
     * @psalm-return 'blob'|'noblob'|'all'|false
     */
    private function setProtectBinary(array $settings)
    {
        if (
            ! isset($settings['ProtectBinary'])
            || ! in_array($settings['ProtectBinary'], ['noblob', 'all', false], true)
        ) {
            return 'blob';
        }

        return $settings['ProtectBinary'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowFunctionFields(array $settings): bool
    {
        if (! isset($settings['ShowFunctionFields'])) {
            return true;
        }

        return (bool) $settings['ShowFunctionFields'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowFieldTypesInDataEditView(array $settings): bool
    {
        if (! isset($settings['ShowFieldTypesInDataEditView'])) {
            return true;
        }

        return (bool) $settings['ShowFieldTypesInDataEditView'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'input'|'textarea'
     */
    private function setCharEditing(array $settings): string
    {
        if (! isset($settings['CharEditing']) || $settings['CharEditing'] !== 'textarea') {
            return 'input';
        }

        return 'textarea';
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 0|positive-int
     */
    private function setMinSizeForInputField(array $settings): int
    {
        if (! isset($settings['MinSizeForInputField'])) {
            return 4;
        }

        $minSizeForInputField = (int) $settings['MinSizeForInputField'];

        return $minSizeForInputField >= 0 ? $minSizeForInputField : 4;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setMaxSizeForInputField(array $settings): int
    {
        if (! isset($settings['MaxSizeForInputField'])) {
            return 60;
        }

        $maxSizeForInputField = (int) $settings['MaxSizeForInputField'];

        return $maxSizeForInputField >= 1 ? $maxSizeForInputField : 60;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setInsertRows(array $settings): int
    {
        if (! isset($settings['InsertRows'])) {
            return 2;
        }

        $insertRows = (int) $settings['InsertRows'];

        return $insertRows >= 1 ? $insertRows : 2;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return string[]
     * @psalm-return array{0: 'content-id'|'id-content', 1?: 'content-id'|'id-content'}
     */
    private function setForeignKeyDropdownOrder(array $settings): array
    {
        if (
            ! isset($settings['ForeignKeyDropdownOrder'])
            || ! is_array($settings['ForeignKeyDropdownOrder'])
            || ! isset($settings['ForeignKeyDropdownOrder'][0])
            || ! in_array($settings['ForeignKeyDropdownOrder'][0], ['content-id', 'id-content'], true)
        ) {
            return ['content-id', 'id-content'];
        }

        if (
            ! isset($settings['ForeignKeyDropdownOrder'][1])
            || ! in_array($settings['ForeignKeyDropdownOrder'][1], ['content-id', 'id-content'], true)
        ) {
            return [$settings['ForeignKeyDropdownOrder'][0]];
        }

        return [$settings['ForeignKeyDropdownOrder'][0], $settings['ForeignKeyDropdownOrder'][1]];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setForeignKeyMaxLimit(array $settings): int
    {
        if (! isset($settings['ForeignKeyMaxLimit'])) {
            return 100;
        }

        $foreignKeyMaxLimit = (int) $settings['ForeignKeyMaxLimit'];

        return $foreignKeyMaxLimit >= 1 ? $foreignKeyMaxLimit : 100;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'default'|'enable'|'disable'
     */
    private function setDefaultForeignKeyChecks(array $settings): string
    {
        if (
            ! isset($settings['DefaultForeignKeyChecks'])
            || ! in_array($settings['DefaultForeignKeyChecks'], ['enable', 'disable'], true)
        ) {
            return 'default';
        }

        return $settings['DefaultForeignKeyChecks'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setZipDump(array $settings): bool
    {
        if (! isset($settings['ZipDump'])) {
            return true;
        }

        return (bool) $settings['ZipDump'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setGZipDump(array $settings): bool
    {
        if (! isset($settings['GZipDump'])) {
            return true;
        }

        return (bool) $settings['GZipDump'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setBZipDump(array $settings): bool
    {
        if (! isset($settings['BZipDump'])) {
            return true;
        }

        return (bool) $settings['BZipDump'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setCompressOnFly(array $settings): bool
    {
        if (! isset($settings['CompressOnFly'])) {
            return true;
        }

        return (bool) $settings['CompressOnFly'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'icons'|'text'|'both'
     */
    private function setTabsMode(array $settings): string
    {
        if (! isset($settings['TabsMode']) || ! in_array($settings['TabsMode'], ['icons', 'text'], true)) {
            return 'both';
        }

        return $settings['TabsMode'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'icons'|'text'|'both'
     */
    private function setActionLinksMode(array $settings): string
    {
        if (
            ! isset($settings['ActionLinksMode'])
            || ! in_array($settings['ActionLinksMode'], ['icons', 'text'], true)
        ) {
            return 'both';
        }

        return $settings['ActionLinksMode'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setPropertiesNumColumns(array $settings): int
    {
        if (! isset($settings['PropertiesNumColumns'])) {
            return 1;
        }

        $propertiesNumColumns = (int) $settings['PropertiesNumColumns'];

        return $propertiesNumColumns >= 2 ? $propertiesNumColumns : 1;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'welcome'|'databases'|'status'|'variables'|'privileges'
     */
    private function setDefaultTabServer(array $settings): string
    {
        if (! isset($settings['DefaultTabServer'])) {
            return 'welcome';
        }

        switch ($settings['DefaultTabServer']) {
            case 'databases':
            case 'server_databases.php':
                return 'databases';

            case 'status':
            case 'server_status.php':
                return 'status';

            case 'variables':
            case 'server_variables.php':
                return 'variables';

            case 'privileges':
            case 'server_privileges.php':
                return 'privileges';

            default:
                return 'welcome';
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'structure'|'sql'|'search'|'operations'
     */
    private function setDefaultTabDatabase(array $settings): string
    {
        if (! isset($settings['DefaultTabDatabase'])) {
            return 'structure';
        }

        switch ($settings['DefaultTabDatabase']) {
            case 'sql':
            case 'db_sql.php':
                return 'sql';

            case 'search':
            case 'db_search.php':
                return 'search';

            case 'operations':
            case 'db_operations.php':
                return 'operations';

            default:
                return 'structure';
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'structure'|'sql'|'search'|'insert'|'browse'
     */
    private function setDefaultTabTable(array $settings): string
    {
        if (! isset($settings['DefaultTabTable'])) {
            return 'browse';
        }

        switch ($settings['DefaultTabTable']) {
            case 'structure':
            case 'tbl_structure.php':
                return 'structure';

            case 'sql':
            case 'tbl_sql.php':
                return 'sql';

            case 'search':
            case 'tbl_select.php':
                return 'search';

            case 'insert':
            case 'tbl_change.php':
                return 'insert';

            default:
                return 'browse';
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'icons'|'text'|'both'
     */
    private function setRowActionType(array $settings): string
    {
        if (! isset($settings['RowActionType']) || ! in_array($settings['RowActionType'], ['icons', 'text'], true)) {
            return 'both';
        }

        return $settings['RowActionType'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setExport(array $settings): Export
    {
        if (isset($settings['Export']) && is_array($settings['Export'])) {
            return new Export($settings['Export']);
        }

        return new Export();
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setImport(array $settings): Import
    {
        if (isset($settings['Import']) && is_array($settings['Import'])) {
            return new Import($settings['Import']);
        }

        return new Import();
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setSchema(array $settings): Schema
    {
        if (isset($settings['Schema']) && is_array($settings['Schema'])) {
            return new Schema($settings['Schema']);
        }

        return new Schema();
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return string[]
     */
    private function setPDFPageSizes(array $settings): array
    {
        if (
            ! isset($settings['PDFPageSizes'])
            || ! is_array($settings['PDFPageSizes'])
            || $settings['PDFPageSizes'] === []
        ) {
            return ['A3', 'A4', 'A5', 'letter', 'legal'];
        }

        $pdfPageSizes = [];
        /** @var mixed $pageSize */
        foreach ($settings['PDFPageSizes'] as $pageSize) {
            $pdfPageSizes[] = (string) $pageSize;
        }

        return $pdfPageSizes;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setPDFDefaultPageSize(array $settings): string
    {
        if (! isset($settings['PDFDefaultPageSize'])) {
            return 'A4';
        }

        return (string) $settings['PDFDefaultPageSize'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setDefaultLang(array $settings): string
    {
        if (! isset($settings['DefaultLang'])) {
            return 'en';
        }

        return (string) $settings['DefaultLang'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setDefaultConnectionCollation(array $settings): string
    {
        if (! isset($settings['DefaultConnectionCollation'])) {
            return 'utf8mb4_unicode_ci';
        }

        return (string) $settings['DefaultConnectionCollation'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setLang(array $settings): string
    {
        if (! isset($settings['Lang'])) {
            return '';
        }

        return (string) $settings['Lang'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setFilterLanguages(array $settings): string
    {
        if (! isset($settings['FilterLanguages'])) {
            return '';
        }

        return (string) $settings['FilterLanguages'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'auto'|'iconv'|'recode'|'mb'|'none'
     */
    private function setRecodingEngine(array $settings): string
    {
        if (
            ! isset($settings['RecodingEngine'])
            || ! in_array($settings['RecodingEngine'], ['iconv', 'recode', 'mb', 'none'], true)
        ) {
            return 'auto';
        }

        return $settings['RecodingEngine'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setIconvExtraParams(array $settings): string
    {
        if (! isset($settings['IconvExtraParams'])) {
            return '//TRANSLIT';
        }

        return (string) $settings['IconvExtraParams'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return string[]
     */
    private function setAvailableCharsets(array $settings): array
    {
        if (! isset($settings['AvailableCharsets']) || ! is_array($settings['AvailableCharsets'])) {
            return [
                'iso-8859-1',
                'iso-8859-2',
                'iso-8859-3',
                'iso-8859-4',
                'iso-8859-5',
                'iso-8859-6',
                'iso-8859-7',
                'iso-8859-8',
                'iso-8859-9',
                'iso-8859-10',
                'iso-8859-11',
                'iso-8859-12',
                'iso-8859-13',
                'iso-8859-14',
                'iso-8859-15',
                'windows-1250',
                'windows-1251',
                'windows-1252',
                'windows-1256',
                'windows-1257',
                'koi8-r',
                'big5',
                'gb2312',
                'utf-16',
                'utf-8',
                'utf-7',
                'x-user-defined',
                'euc-jp',
                'ks_c_5601-1987',
                'tis-620',
                'SHIFT_JIS',
                'SJIS',
                'SJIS-win',
            ];
        }

        $availableCharsets = [];
        /** @var mixed $availableCharset */
        foreach ($settings['AvailableCharsets'] as $availableCharset) {
            $availableCharsets[] = (string) $availableCharset;
        }

        return $availableCharsets;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNavigationTreePointerEnable(array $settings): bool
    {
        if (! isset($settings['NavigationTreePointerEnable'])) {
            return true;
        }

        return (bool) $settings['NavigationTreePointerEnable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setBrowsePointerEnable(array $settings): bool
    {
        if (! isset($settings['BrowsePointerEnable'])) {
            return true;
        }

        return (bool) $settings['BrowsePointerEnable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setBrowseMarkerEnable(array $settings): bool
    {
        if (! isset($settings['BrowseMarkerEnable'])) {
            return true;
        }

        return (bool) $settings['BrowseMarkerEnable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setTextareaCols(array $settings): int
    {
        if (! isset($settings['TextareaCols'])) {
            return 40;
        }

        $textareaCols = (int) $settings['TextareaCols'];

        return $textareaCols >= 1 ? $textareaCols : 40;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setTextareaRows(array $settings): int
    {
        if (! isset($settings['TextareaRows'])) {
            return 15;
        }

        $textareaRows = (int) $settings['TextareaRows'];

        return $textareaRows >= 1 ? $textareaRows : 15;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setLongtextDoubleTextarea(array $settings): bool
    {
        if (! isset($settings['LongtextDoubleTextarea'])) {
            return true;
        }

        return (bool) $settings['LongtextDoubleTextarea'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setTextareaAutoSelect(array $settings): bool
    {
        if (! isset($settings['TextareaAutoSelect'])) {
            return false;
        }

        return (bool) $settings['TextareaAutoSelect'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setCharTextareaCols(array $settings): int
    {
        if (! isset($settings['CharTextareaCols'])) {
            return 40;
        }

        $charTextareaCols = (int) $settings['CharTextareaCols'];

        return $charTextareaCols >= 1 ? $charTextareaCols : 40;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setCharTextareaRows(array $settings): int
    {
        if (! isset($settings['CharTextareaRows'])) {
            return 7;
        }

        $charTextareaRows = (int) $settings['CharTextareaRows'];

        return $charTextareaRows >= 1 ? $charTextareaRows : 7;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setLimitChars(array $settings): int
    {
        if (! isset($settings['LimitChars'])) {
            return 50;
        }

        $limitChars = (int) $settings['LimitChars'];

        return $limitChars >= 1 ? $limitChars : 50;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'left'|'right'|'both'|'none'
     */
    private function setRowActionLinks(array $settings): string
    {
        if (
            ! isset($settings['RowActionLinks'])
            || ! in_array($settings['RowActionLinks'], ['right', 'both', 'none'], true)
        ) {
            return 'left';
        }

        return $settings['RowActionLinks'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setRowActionLinksWithoutUnique(array $settings): bool
    {
        if (! isset($settings['RowActionLinksWithoutUnique'])) {
            return false;
        }

        return (bool) $settings['RowActionLinksWithoutUnique'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'NONE'|'ASC'|'DESC'
     */
    private function setTablePrimaryKeyOrder(array $settings): string
    {
        if (
            ! isset($settings['TablePrimaryKeyOrder'])
            || ! in_array($settings['TablePrimaryKeyOrder'], ['ASC', 'DESC'], true)
        ) {
            return 'NONE';
        }

        return $settings['TablePrimaryKeyOrder'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setRememberSorting(array $settings): bool
    {
        if (! isset($settings['RememberSorting'])) {
            return true;
        }

        return (bool) $settings['RememberSorting'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowBrowseComments(array $settings): bool
    {
        if (! isset($settings['ShowBrowseComments'])) {
            return true;
        }

        return (bool) $settings['ShowBrowseComments'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowPropertyComments(array $settings): bool
    {
        if (! isset($settings['ShowPropertyComments'])) {
            return true;
        }

        return (bool) $settings['ShowPropertyComments'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 0|positive-int
     */
    private function setRepeatCells(array $settings): int
    {
        if (! isset($settings['RepeatCells'])) {
            return 100;
        }

        $repeatCells = (int) $settings['RepeatCells'];

        return $repeatCells >= 0 ? $repeatCells : 100;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setQueryHistoryDB(array $settings): bool
    {
        if (! isset($settings['QueryHistoryDB'])) {
            return false;
        }

        return (bool) $settings['QueryHistoryDB'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setQueryHistoryMax(array $settings): int
    {
        if (! isset($settings['QueryHistoryMax'])) {
            return 25;
        }

        $queryHistoryMax = (int) $settings['QueryHistoryMax'];

        return $queryHistoryMax >= 1 ? $queryHistoryMax : 25;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setBrowseMIME(array $settings): bool
    {
        if (! isset($settings['BrowseMIME'])) {
            return true;
        }

        return (bool) $settings['BrowseMIME'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setMaxExactCount(array $settings): int
    {
        if (! isset($settings['MaxExactCount'])) {
            return 50000;
        }

        $maxExactCount = (int) $settings['MaxExactCount'];

        return $maxExactCount >= 1 ? $maxExactCount : 50000;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 0|positive-int
     */
    private function setMaxExactCountViews(array $settings): int
    {
        if (! isset($settings['MaxExactCountViews'])) {
            return 0;
        }

        $maxExactCountViews = (int) $settings['MaxExactCountViews'];

        return $maxExactCountViews >= 1 ? $maxExactCountViews : 0;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setNaturalOrder(array $settings): bool
    {
        if (! isset($settings['NaturalOrder'])) {
            return true;
        }

        return (bool) $settings['NaturalOrder'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'open'|'closed'|'disabled'
     */
    private function setInitialSlidersState(array $settings): string
    {
        if (
            ! isset($settings['InitialSlidersState'])
            || ! in_array($settings['InitialSlidersState'], ['open', 'disabled'], true)
        ) {
            return 'closed';
        }

        return $settings['InitialSlidersState'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return string[]
     */
    private function setUserprefsDisallow(array $settings): array
    {
        if (! isset($settings['UserprefsDisallow']) || ! is_array($settings['UserprefsDisallow'])) {
            return [];
        }

        $userprefsDisallow = [];
        /** @var mixed $userPreference */
        foreach ($settings['UserprefsDisallow'] as $userPreference) {
            $userprefsDisallow[] = (string) $userPreference;
        }

        return $userprefsDisallow;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setUserprefsDeveloperTab(array $settings): bool
    {
        if (! isset($settings['UserprefsDeveloperTab'])) {
            return false;
        }

        return (bool) $settings['UserprefsDeveloperTab'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setTitleTable(array $settings): string
    {
        if (! isset($settings['TitleTable'])) {
            return '@HTTP_HOST@ / @VSERVER@ / @DATABASE@ / @TABLE@ | @PHPMYADMIN@';
        }

        return (string) $settings['TitleTable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setTitleDatabase(array $settings): string
    {
        if (! isset($settings['TitleDatabase'])) {
            return '@HTTP_HOST@ / @VSERVER@ / @DATABASE@ | @PHPMYADMIN@';
        }

        return (string) $settings['TitleDatabase'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setTitleServer(array $settings): string
    {
        if (! isset($settings['TitleServer'])) {
            return '@HTTP_HOST@ / @VSERVER@ | @PHPMYADMIN@';
        }

        return (string) $settings['TitleServer'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setTitleDefault(array $settings): string
    {
        if (! isset($settings['TitleDefault'])) {
            return '@HTTP_HOST@ | @PHPMYADMIN@';
        }

        return (string) $settings['TitleDefault'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setThemeManager(array $settings): bool
    {
        if (! isset($settings['ThemeManager'])) {
            return true;
        }

        return (bool) $settings['ThemeManager'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setThemeDefault(array $settings): string
    {
        if (! isset($settings['ThemeDefault'])) {
            return 'pmahomme';
        }

        return (string) $settings['ThemeDefault'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setThemePerServer(array $settings): bool
    {
        if (! isset($settings['ThemePerServer'])) {
            return false;
        }

        return (bool) $settings['ThemePerServer'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setDefaultQueryTable(array $settings): string
    {
        if (! isset($settings['DefaultQueryTable'])) {
            return 'SELECT * FROM @TABLE@ WHERE 1';
        }

        return (string) $settings['DefaultQueryTable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setDefaultQueryDatabase(array $settings): string
    {
        if (! isset($settings['DefaultQueryDatabase'])) {
            return '';
        }

        return (string) $settings['DefaultQueryDatabase'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setSQLQuery(array $settings): SqlQueryBox
    {
        if (isset($settings['SQLQuery']) && is_array($settings['SQLQuery'])) {
            return new SqlQueryBox($settings['SQLQuery']);
        }

        return new SqlQueryBox();
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setEnableAutocompleteForTablesAndColumns(array $settings): bool
    {
        if (! isset($settings['EnableAutocompleteForTablesAndColumns'])) {
            return true;
        }

        return (bool) $settings['EnableAutocompleteForTablesAndColumns'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setUploadDir(array $settings): string
    {
        if (! isset($settings['UploadDir'])) {
            return '';
        }

        return (string) $settings['UploadDir'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setSaveDir(array $settings): string
    {
        if (! isset($settings['SaveDir'])) {
            return '';
        }

        return (string) $settings['SaveDir'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setTempDir(array $settings): string
    {
        $tempDir = ROOT_PATH . 'tmp' . DIRECTORY_SEPARATOR;
        if (defined('TEMP_DIR')) {
            $tempDir = TEMP_DIR;
        }

        if (! isset($settings['TempDir'])) {
            return $tempDir;
        }

        return (string) $settings['TempDir'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'auto'|'yes'|'no'
     */
    private function setGD2Available(array $settings): string
    {
        if (! isset($settings['GD2Available']) || ! in_array($settings['GD2Available'], ['yes', 'no'], true)) {
            return 'auto';
        }

        return $settings['GD2Available'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return array<string, string>
     */
    private function setTrustedProxies(array $settings): array
    {
        if (! isset($settings['TrustedProxies']) || ! is_array($settings['TrustedProxies'])) {
            return [];
        }

        $trustedProxies = [];
        /**
         * @var int|string $proxy
         * @var mixed $header
         */
        foreach ($settings['TrustedProxies'] as $proxy => $header) {
            if (! is_string($proxy)) {
                continue;
            }

            $trustedProxies[$proxy] = (string) $header;
        }

        return $trustedProxies;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setCheckConfigurationPermissions(array $settings): bool
    {
        if (! isset($settings['CheckConfigurationPermissions'])) {
            return true;
        }

        return (bool) $settings['CheckConfigurationPermissions'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setLinkLengthLimit(array $settings): int
    {
        if (! isset($settings['LinkLengthLimit'])) {
            return 1000;
        }

        $linkLengthLimit = (int) $settings['LinkLengthLimit'];

        return $linkLengthLimit >= 1 ? $linkLengthLimit : 1000;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setCSPAllow(array $settings): string
    {
        if (! isset($settings['CSPAllow'])) {
            return '';
        }

        return (string) $settings['CSPAllow'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setDisableMultiTableMaintenance(array $settings): bool
    {
        if (! isset($settings['DisableMultiTableMaintenance'])) {
            return false;
        }

        return (bool) $settings['DisableMultiTableMaintenance'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'ask'|'always'|'never'
     */
    private function setSendErrorReports(array $settings): string
    {
        if (
            ! isset($settings['SendErrorReports'])
            || ! in_array($settings['SendErrorReports'], ['always', 'never'], true)
        ) {
            return 'ask';
        }

        return $settings['SendErrorReports'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setConsoleEnterExecutes(array $settings): bool
    {
        if (! isset($settings['ConsoleEnterExecutes'])) {
            return false;
        }

        return (bool) $settings['ConsoleEnterExecutes'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setZeroConf(array $settings): bool
    {
        if (! isset($settings['ZeroConf'])) {
            return true;
        }

        return (bool) $settings['ZeroConf'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setDBG(array $settings): Debug
    {
        if (isset($settings['DBG']) && is_array($settings['DBG'])) {
            return new Debug($settings['DBG']);
        }

        return new Debug();
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 'production'|'development'
     */
    private function setEnvironment(array $settings): string
    {
        if (! isset($settings['environment']) || $settings['environment'] !== 'development') {
            return 'production';
        }

        return 'development';
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return array<string, string>
     */
    private function setDefaultFunctions(array $settings): array
    {
        if (! isset($settings['DefaultFunctions']) || ! is_array($settings['DefaultFunctions'])) {
            return [
                'FUNC_CHAR' => '',
                'FUNC_DATE' => '',
                'FUNC_NUMBER' => '',
                'FUNC_SPATIAL' => 'GeomFromText',
                'FUNC_UUID' => 'UUID',
                'first_timestamp' => 'NOW',
            ];
        }

        $defaultFunctions = [];
        /**
         * @var int|string $key
         * @var mixed $value
         */
        foreach ($settings['DefaultFunctions'] as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $defaultFunctions[$key] = (string) $value;
        }

        return $defaultFunctions;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return positive-int
     */
    private function setMaxRowPlotLimit(array $settings): int
    {
        if (! isset($settings['maxRowPlotLimit'])) {
            return 500;
        }

        $maxRowPlotLimit = (int) $settings['maxRowPlotLimit'];

        return $maxRowPlotLimit >= 1 ? $maxRowPlotLimit : 500;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setShowGitRevision(array $settings): bool
    {
        if (! isset($settings['ShowGitRevision'])) {
            return true;
        }

        return (bool) $settings['ShowGitRevision'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @return array<string, int|string>
     * @psalm-return array{internal: int, human: string}
     */
    private function setMysqlMinVersion(array $settings): array
    {
        $mysqlMinVersion = ['internal' => 50500, 'human' => '5.5.0'];
        if (! isset($settings['MysqlMinVersion']) || ! is_array($settings['MysqlMinVersion'])) {
            return $mysqlMinVersion;
        }

        if (isset($settings['MysqlMinVersion']['internal'])) {
            $mysqlMinVersion['internal'] = (int) $settings['MysqlMinVersion']['internal'];
        }

        if (! isset($settings['MysqlMinVersion']['human'])) {
            return $mysqlMinVersion;
        }

        $mysqlMinVersion['human'] = (string) $settings['MysqlMinVersion']['human'];

        return $mysqlMinVersion;
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setDisableShortcutKeys(array $settings): bool
    {
        if (! isset($settings['DisableShortcutKeys'])) {
            return false;
        }

        return (bool) $settings['DisableShortcutKeys'];
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setConsole(array $settings): Console
    {
        if (isset($settings['Console']) && is_array($settings['Console'])) {
            return new Console($settings['Console']);
        }

        return new Console();
    }

    /**
     * @param array<int|string, mixed> $settings
     */
    private function setDefaultTransformations(array $settings): Transformations
    {
        if (isset($settings['DefaultTransformations']) && is_array($settings['DefaultTransformations'])) {
            return new Transformations($settings['DefaultTransformations']);
        }

        return new Transformations();
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-return 0|positive-int
     */
    private function setFirstDayOfCalendar(array $settings): int
    {
        if (! isset($settings['FirstDayOfCalendar'])) {
            return 0;
        }

        $firstDayOfCalendar = (int) $settings['FirstDayOfCalendar'];

        return $firstDayOfCalendar >= 1 && $firstDayOfCalendar <= 7 ? $firstDayOfCalendar : 0;
    }
}
