<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

use function count;
use function in_array;
use function is_array;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * @psalm-immutable
 */
final class Server
{
    /**
     * MySQL hostname or IP address
     *
     * @var string
     */
    public $host;

    /**
     * MySQL port - leave blank for default port
     *
     * @var string
     */
    public $port;

    /**
     * Path to the socket - leave blank for default socket
     *
     * @var string
     */
    public $socket;

    /**
     * Use SSL for connecting to MySQL server?
     *
     * @var bool
     */
    public $ssl;

    /**
     * Path to the key file when using SSL for connecting to the MySQL server
     *
     * @var string|null
     */
    public $ssl_key;

    /**
     * Path to the cert file when using SSL for connecting to the MySQL server
     *
     * @var string|null
     */
    public $ssl_cert;

    /**
     * Path to the CA file when using SSL for connecting to the MySQL server
     *
     * @var string|null
     */
    public $ssl_ca;

    /**
     * Directory containing trusted SSL CA certificates in PEM format
     *
     * @var string|null
     */
    public $ssl_ca_path;

    /**
     * List of allowable ciphers for SSL connections to the MySQL server
     *
     * @var string|null
     */
    public $ssl_ciphers;

    /**
     * MySQL 5.6 or later triggers the mysqlnd driver in PHP to validate the
     * peer_name of the SSL certifcate
     * For most self-signed certificates this is a problem. Setting this to false
     * will disable the check and allow the connection (PHP 5.6.16 or later)
     *
     * @link https://bugs.php.net/68344
     *
     * @var bool
     */
    public $ssl_verify;

    /**
     * Use compressed protocol for the MySQL connection
     *
     * @var bool
     */
    public $compress;

    /**
     * MySQL control host. This permits to use a host different than the
     * main host, for the phpMyAdmin configuration storage. If left empty,
     * $cfg['Servers'][$i]['host'] is used instead.
     *
     * @var string
     */
    public $controlhost;

    /**
     * MySQL control port. This permits to use a port different than the
     * main port, for the phpMyAdmin configuration storage. If left empty,
     * $cfg['Servers'][$i]['port'] is used instead.
     *
     * @var string
     */
    public $controlport;

    /**
     * MySQL control user settings (this user must have read-only
     * access to the "mysql/user" and "mysql/db" tables). The controluser is also
     * used for all relational features (pmadb)
     *
     * @var string
     */
    public $controluser;

    /**
     * MySQL control user settings (this user must have read-only
     * access to the "mysql/user" and "mysql/db" tables). The controluser is also
     * used for all relational features (pmadb)
     *
     * @var string
     */
    public $controlpass;

    /**
     * Authentication method (valid choices: config, http, signon or cookie)
     *
     * @var string
     * @psalm-var 'config'|'http'|'signon'|'cookie'
     */
    public $auth_type;

    /**
     * HTTP Basic Auth Realm name to display (only used with 'HTTP' auth_type)
     *
     * @var string
     */
    public $auth_http_realm;

    /**
     * MySQL user
     *
     * @var string
     */
    public $user;

    /**
     * MySQL password (only needed with 'config' auth_type)
     *
     * @var string
     */
    public $password;

    /**
     * Session to use for 'signon' authentication method
     *
     * @var string
     */
    public $SignonSession;

    /**
     * Cookie params to match session to use for 'signon' authentication method
     * It should be an associative array matching result of session_get_cookie_params() in other system
     *
     * @var array<string, int|string|bool>
     * @psalm-var array{
     *   lifetime: 0|positive-int, path: string, domain: string, secure: bool, httponly: bool, samesite?: 'Lax'|'Strict'
     * }
     */
    public $SignonCookieParams;

    /**
     * PHP script to use for 'signon' authentication method
     *
     * @var string
     */
    public $SignonScript;

    /**
     * URL where to redirect user to login for 'signon' authentication method
     *
     * @var string
     */
    public $SignonURL;

    /**
     * URL where to redirect user after logout
     *
     * @var string
     */
    public $LogoutURL;

    /**
     * If set to a db-name, only this db is displayed in navigation panel
     * It may also be an array of db-names
     *
     * @var string|string[]
     */
    public $only_db;

    /**
     * Database name to be hidden from listings
     *
     * @var string
     */
    public $hide_db;

    /**
     * Verbose name for this host - leave blank to show the hostname
     * (for HTTP authentication, all non-US-ASCII characters will be stripped)
     *
     * @var string
     */
    public $verbose;

    /**
     * Database used for Relation, Bookmark and PDF Features
     * (see sql/create_tables.sql)
     *   - leave blank for no support
     *     SUGGESTED: 'phpmyadmin'
     *
     * @var string
     */
    public $pmadb;

    /**
     * Bookmark table
     *   - leave blank for no bookmark support
     *     SUGGESTED: 'pma__bookmark'
     *
     * @var string|false
     */
    public $bookmarktable;

    /**
     * table to describe the relation between links (see doc)
     *   - leave blank for no relation-links support
     *     SUGGESTED: 'pma__relation'
     *
     * @var string|false
     */
    public $relation;

    /**
     * table to describe the display fields
     *   - leave blank for no display fields support
     *     SUGGESTED: 'pma__table_info'
     *
     * @var string|false
     */
    public $table_info;

    /**
     * table to describe the tables position for the designer and PDF schema
     *   - leave blank for no PDF schema support
     *     SUGGESTED: 'pma__table_coords'
     *
     * @var string|false
     */
    public $table_coords;

    /**
     * table to describe pages of relationpdf
     *   - leave blank if you don't want to use this
     *     SUGGESTED: 'pma__pdf_pages'
     *
     * @var string|false
     */
    public $pdf_pages;

    /**
     * table to store column information
     *   - leave blank for no column comments/mime types
     *     SUGGESTED: 'pma__column_info'
     *
     * @var string|false
     */
    public $column_info;

    /**
     * table to store SQL history
     *   - leave blank for no SQL query history
     *     SUGGESTED: 'pma__history'
     *
     * @var string|false
     */
    public $history;

    /**
     * table to store recently used tables
     *   - leave blank for no "persistent" recently used tables
     *     SUGGESTED: 'pma__recent'
     *
     * @var string|false
     */
    public $recent;

    /**
     * table to store favorite tables
     *   - leave blank for no favorite tables
     *     SUGGESTED: 'pma__favorite'
     *
     * @var string|false
     */
    public $favorite;

    /**
     * table to store UI preferences for tables
     *   - leave blank for no "persistent" UI preferences
     *     SUGGESTED: 'pma__table_uiprefs'
     *
     * @var string|false
     */
    public $table_uiprefs;

    /**
     * table to store SQL tracking
     *   - leave blank for no SQL tracking
     *     SUGGESTED: 'pma__tracking'
     *
     * @var string|false
     */
    public $tracking;

    /**
     * table to store user preferences
     *   - leave blank to disable server storage
     *     SUGGESTED: 'pma__userconfig'
     *
     * @var string|false
     */
    public $userconfig;

    /**
     * table to store users and their assignment to user groups
     *   - leave blank to disable configurable menus feature
     *     SUGGESTED: 'pma__users'
     *
     * @var string|false
     */
    public $users;

    /**
     * table to store allowed menu items for each user group
     *   - leave blank to disable configurable menus feature
     *     SUGGESTED: 'pma__usergroups'
     *
     * @var string|false
     */
    public $usergroups;

    /**
     * table to store information about item hidden from navigation tree
     *   - leave blank to disable hide/show navigation items feature
     *     SUGGESTED: 'pma__navigationhiding'
     *
     * @var string|false
     */
    public $navigationhiding;

    /**
     * table to store information about saved searches from query-by-example on a db
     *   - leave blank to disable saved searches feature
     *     SUGGESTED: 'pma__savedsearches'
     *
     * @var string|false
     */
    public $savedsearches;

    /**
     * table to store central list of columns per database
     *   - leave blank to disable central list of columns feature
     *     SUGGESTED: 'pma__central_columns'
     *
     * @var string|false
     */
    public $central_columns;

    /**
     * table to store designer settings
     *   - leave blank to disable the storage of designer settings
     *     SUGGESTED: 'pma__designer_settings'
     *
     * @var string|false
     */
    public $designer_settings;

    /**
     * table to store export templates
     *   - leave blank to disable saved searches feature
     *     SUGGESTED: 'pma__export_templates'
     *
     * @var string|false
     */
    public $export_templates;

    /**
     * Maximum number of records saved in $cfg['Servers'][$i]['table_uiprefs'] table.
     *
     * In case where tables in databases is modified (e.g. dropped or renamed),
     * table_uiprefs may contains invalid data (referring to tables which are not
     * exist anymore).
     * This configuration make sure that we only keep N (N = MaxTableUiprefs)
     * newest record in table_uiprefs and automatically delete older records.
     *
     * @var int
     * @psalm-var positive-int
     */
    public $MaxTableUiprefs;

    /**
     * Sets the time zone used by phpMyAdmin. Possible values are explained at
     * https://dev.mysql.com/doc/refman/5.7/en/time-zone-support.html
     *
     * @var string
     */
    public $SessionTimeZone;

    /**
     * whether to allow root login
     *
     * @var bool
     */
    public $AllowRoot;

    /**
     * whether to allow login of any user without a password
     *
     * @var bool
     */
    public $AllowNoPassword;

    /**
     * Host authentication
     *
     * Host authentication order, leave blank to not use
     * Host authentication rules, leave blank for defaults
     *
     * @var array<string, string|string[]>
     * @psalm-var array{order: ''|'deny,allow'|'allow,deny'|'explicit', rules: string[]}
     */
    public $AllowDeny;

    /**
     * Disable use of INFORMATION_SCHEMA.
     *
     * @see https://github.com/phpmyadmin/phpmyadmin/issues/8970
     * @see https://bugs.mysql.com/19588
     *
     * @var bool
     */
    public $DisableIS;

    /**
     * Whether the tracking mechanism creates
     * versions for tables and views automatically.
     *
     * @var bool
     */
    public $tracking_version_auto_create;

    /**
     * Defines the list of statements
     * the auto-creation uses for new versions.
     *
     * @var string
     */
    public $tracking_default_statements;

    /**
     * Whether a DROP VIEW IF EXISTS statement will be added
     * as first line to the log when creating a view.
     *
     * @var bool
     */
    public $tracking_add_drop_view;

    /**
     * Whether a DROP TABLE IF EXISTS statement will be added
     * as first line to the log when creating a table.
     *
     * @var bool
     */
    public $tracking_add_drop_table;

    /**
     * Whether a DROP DATABASE IF EXISTS statement will be added
     * as first line to the log when creating a database.
     *
     * @var bool
     */
    public $tracking_add_drop_database;

    /**
     * Whether to show or hide detailed MySQL/MariaDB connection errors on the login page.
     *
     * @var bool
     */
    public $hide_connection_errors;

    /**
     * @param array<int|string, mixed> $server
     */
    public function __construct(array $server = [])
    {
        $this->host = $this->setHost($server);
        $this->port = $this->setPort($server);
        $this->socket = $this->setSocket($server);
        $this->ssl = $this->setSsl($server);
        $this->ssl_key = $this->setSslKey($server);
        $this->ssl_cert = $this->setSslCert($server);
        $this->ssl_ca = $this->setSslCa($server);
        $this->ssl_ca_path = $this->setSslCaPath($server);
        $this->ssl_ciphers = $this->setSslCiphers($server);
        $this->ssl_verify = $this->setSslVerify($server);
        $this->compress = $this->setCompress($server);
        $this->controlhost = $this->setControlhost($server);
        $this->controlport = $this->setControlport($server);
        $this->controluser = $this->setControluser($server);
        $this->controlpass = $this->setControlpass($server);
        $this->auth_type = $this->setAuthType($server);
        $this->auth_http_realm = $this->setAuthHttpRealm($server);
        $this->user = $this->setUser($server);
        $this->password = $this->setPassword($server);
        $this->SignonSession = $this->setSignonSession($server);
        $this->SignonCookieParams = $this->setSignonCookieParams($server);
        $this->SignonScript = $this->setSignonScript($server);
        $this->SignonURL = $this->setSignonUrl($server);
        $this->LogoutURL = $this->setLogoutUrl($server);
        $this->only_db = $this->setOnlyDb($server);
        $this->hide_db = $this->setHideDb($server);
        $this->verbose = $this->setVerbose($server);
        $this->pmadb = $this->setPmadb($server);
        $this->bookmarktable = $this->setBookmarktable($server);
        $this->relation = $this->setRelation($server);
        $this->table_info = $this->setTableInfo($server);
        $this->table_coords = $this->setTableCoords($server);
        $this->pdf_pages = $this->setPdfPages($server);
        $this->column_info = $this->setColumnInfo($server);
        $this->history = $this->setHistory($server);
        $this->recent = $this->setRecent($server);
        $this->favorite = $this->setFavorite($server);
        $this->table_uiprefs = $this->setTableUiprefs($server);
        $this->tracking = $this->setTracking($server);
        $this->userconfig = $this->setUserconfig($server);
        $this->users = $this->setUsers($server);
        $this->usergroups = $this->setUsergroups($server);
        $this->navigationhiding = $this->setNavigationhiding($server);
        $this->savedsearches = $this->setSavedsearches($server);
        $this->central_columns = $this->setCentralColumns($server);
        $this->designer_settings = $this->setDesignerSettings($server);
        $this->export_templates = $this->setExportTemplates($server);
        $this->MaxTableUiprefs = $this->setMaxTableUiprefs($server);
        $this->SessionTimeZone = $this->setSessionTimeZone($server);
        $this->AllowRoot = $this->setAllowRoot($server);
        $this->AllowNoPassword = $this->setAllowNoPassword($server);
        $this->AllowDeny = $this->setAllowDeny($server);
        $this->DisableIS = $this->setDisableIs($server);
        $this->tracking_version_auto_create = $this->setTrackingVersionAutoCreate($server);
        $this->tracking_default_statements = $this->setTrackingDefaultStatements($server);
        $this->tracking_add_drop_view = $this->setTrackingAddDropView($server);
        $this->tracking_add_drop_table = $this->setTrackingAddDropTable($server);
        $this->tracking_add_drop_database = $this->setTrackingAddDropDatabase($server);
        $this->hide_connection_errors = $this->setHideConnectionErrors($server);
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setHost(array $server): string
    {
        if (isset($server['host'])) {
            return (string) $server['host'];
        }

        return 'localhost';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setPort(array $server): string
    {
        if (isset($server['port'])) {
            return (string) $server['port'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setSocket(array $server): string
    {
        if (isset($server['socket'])) {
            return (string) $server['socket'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setSsl(array $server): bool
    {
        if (isset($server['ssl'])) {
            return (bool) $server['ssl'];
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setSslKey(array $server): ?string
    {
        if (isset($server['ssl_key'])) {
            return (string) $server['ssl_key'];
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setSslCert(array $server): ?string
    {
        if (isset($server['ssl_cert'])) {
            return (string) $server['ssl_cert'];
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setSslCa(array $server): ?string
    {
        if (isset($server['ssl_ca'])) {
            return (string) $server['ssl_ca'];
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setSslCaPath(array $server): ?string
    {
        if (isset($server['ssl_ca_path'])) {
            return (string) $server['ssl_ca_path'];
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setSslCiphers(array $server): ?string
    {
        if (isset($server['ssl_ciphers'])) {
            return (string) $server['ssl_ciphers'];
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setSslVerify(array $server): bool
    {
        if (isset($server['ssl_verify'])) {
            return (bool) $server['ssl_verify'];
        }

        return true;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setCompress(array $server): bool
    {
        if (isset($server['compress'])) {
            return (bool) $server['compress'];
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setControlhost(array $server): string
    {
        if (isset($server['controlhost'])) {
            return (string) $server['controlhost'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setControlport(array $server): string
    {
        if (isset($server['controlport'])) {
            return (string) $server['controlport'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setControluser(array $server): string
    {
        if (isset($server['controluser'])) {
            return (string) $server['controluser'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setControlpass(array $server): string
    {
        if (isset($server['controlpass'])) {
            return (string) $server['controlpass'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @psalm-return 'config'|'http'|'signon'|'cookie'
     */
    private function setAuthType(array $server): string
    {
        if (isset($server['auth_type']) && in_array($server['auth_type'], ['config', 'http', 'signon'], true)) {
            return $server['auth_type'];
        }

        return 'cookie';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setAuthHttpRealm(array $server): string
    {
        if (isset($server['auth_http_realm'])) {
            return (string) $server['auth_http_realm'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setUser(array $server): string
    {
        if (isset($server['user'])) {
            return (string) $server['user'];
        }

        return 'root';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setPassword(array $server): string
    {
        if (isset($server['password'])) {
            return (string) $server['password'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setSignonSession(array $server): string
    {
        if (isset($server['SignonSession'])) {
            return (string) $server['SignonSession'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return array<string, int|string|bool>
     * @psalm-return array{
     *   lifetime: 0|positive-int, path: string, domain: string, secure: bool, httponly: bool, samesite?: 'Lax'|'Strict'
     * }
     */
    private function setSignonCookieParams(array $server): array
    {
        $params = ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false];
        if (isset($server['SignonCookieParams']) && is_array($server['SignonCookieParams'])) {
            if (isset($server['SignonCookieParams']['lifetime'])) {
                $lifetime = (int) $server['SignonCookieParams']['lifetime'];
                if ($lifetime >= 1) {
                    $params['lifetime'] = $lifetime;
                }
            }

            if (isset($server['SignonCookieParams']['path'])) {
                $params['path'] = (string) $server['SignonCookieParams']['path'];
            }

            if (isset($server['SignonCookieParams']['domain'])) {
                $params['domain'] = (string) $server['SignonCookieParams']['domain'];
            }

            if (isset($server['SignonCookieParams']['secure'])) {
                $params['secure'] = (bool) $server['SignonCookieParams']['secure'];
            }

            if (isset($server['SignonCookieParams']['httponly'])) {
                $params['httponly'] = (bool) $server['SignonCookieParams']['httponly'];
            }

            if (
                isset($server['SignonCookieParams']['samesite'])
                && in_array($server['SignonCookieParams']['samesite'], ['Lax', 'Strict'], true)
            ) {
                $params['samesite'] = $server['SignonCookieParams']['samesite'];
            }
        }

        return $params;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setSignonScript(array $server): string
    {
        if (isset($server['SignonScript'])) {
            return (string) $server['SignonScript'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setSignonUrl(array $server): string
    {
        if (isset($server['SignonURL'])) {
            return (string) $server['SignonURL'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setLogoutUrl(array $server): string
    {
        if (isset($server['LogoutURL'])) {
            return (string) $server['LogoutURL'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return string|string[]
     */
    private function setOnlyDb(array $server)
    {
        $onlyDb = '';
        if (isset($server['only_db'])) {
            if (! is_array($server['only_db'])) {
                $onlyDb = (string) $server['only_db'];
            } elseif (count($server['only_db']) > 0) {
                $onlyDb = [];
                /** @var mixed $database */
                foreach ($server['only_db'] as $database) {
                    $onlyDb[] = (string) $database;
                }
            }
        }

        return $onlyDb;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setHideDb(array $server): string
    {
        if (isset($server['hide_db'])) {
            return (string) $server['hide_db'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setVerbose(array $server): string
    {
        if (isset($server['verbose'])) {
            return (string) $server['verbose'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setPmadb(array $server): string
    {
        if (isset($server['pmadb'])) {
            return (string) $server['pmadb'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setBookmarktable(array $server)
    {
        if (isset($server['bookmarktable'])) {
            return $server['bookmarktable'] === false ? false : (string) $server['bookmarktable'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setRelation(array $server)
    {
        if (isset($server['relation'])) {
            return $server['relation'] === false ? false : (string) $server['relation'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setTableInfo(array $server)
    {
        if (isset($server['table_info'])) {
            return $server['table_info'] === false ? false : (string) $server['table_info'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setTableCoords(array $server)
    {
        if (isset($server['table_coords'])) {
            return $server['table_coords'] === false ? false : (string) $server['table_coords'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setPdfPages(array $server)
    {
        if (isset($server['pdf_pages'])) {
            return $server['pdf_pages'] === false ? false : (string) $server['pdf_pages'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setColumnInfo(array $server)
    {
        if (isset($server['column_info'])) {
            return $server['column_info'] === false ? false : (string) $server['column_info'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setHistory(array $server)
    {
        if (isset($server['history'])) {
            return $server['history'] === false ? false : (string) $server['history'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setRecent(array $server)
    {
        if (isset($server['recent'])) {
            return $server['recent'] === false ? false : (string) $server['recent'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setFavorite(array $server)
    {
        if (isset($server['favorite'])) {
            return $server['favorite'] === false ? false : (string) $server['favorite'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setTableUiprefs(array $server)
    {
        if (isset($server['table_uiprefs'])) {
            return $server['table_uiprefs'] === false ? false : (string) $server['table_uiprefs'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setTracking(array $server)
    {
        if (isset($server['tracking'])) {
            return $server['tracking'] === false ? false : (string) $server['tracking'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setUserconfig(array $server)
    {
        if (isset($server['userconfig'])) {
            return $server['userconfig'] === false ? false : (string) $server['userconfig'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setUsers(array $server)
    {
        if (isset($server['users'])) {
            return $server['users'] === false ? false : (string) $server['users'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setUsergroups(array $server)
    {
        if (isset($server['usergroups'])) {
            return $server['usergroups'] === false ? false : (string) $server['usergroups'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setNavigationhiding(array $server)
    {
        if (isset($server['navigationhiding'])) {
            return $server['navigationhiding'] === false
                ? false
                : (string) $server['navigationhiding'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setSavedsearches(array $server)
    {
        if (isset($server['savedsearches'])) {
            return $server['savedsearches'] === false ? false : (string) $server['savedsearches'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setCentralColumns(array $server)
    {
        if (isset($server['central_columns'])) {
            return $server['central_columns'] === false ? false : (string) $server['central_columns'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setDesignerSettings(array $server)
    {
        if (isset($server['designer_settings'])) {
            return $server['designer_settings'] === false
                ? false
                : (string) $server['designer_settings'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return false|string
     */
    private function setExportTemplates(array $server)
    {
        if (isset($server['export_templates'])) {
            return $server['export_templates'] === false
                ? false
                : (string) $server['export_templates'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @psalm-return positive-int
     */
    private function setMaxTableUiprefs(array $server): int
    {
        if (isset($server['MaxTableUiprefs'])) {
            $maxTableUiprefs = (int) $server['MaxTableUiprefs'];
            if ($maxTableUiprefs >= 1) {
                return $maxTableUiprefs;
            }
        }

        return 100;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setSessionTimeZone(array $server): string
    {
        if (isset($server['SessionTimeZone'])) {
            return (string) $server['SessionTimeZone'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setAllowRoot(array $server): bool
    {
        if (isset($server['AllowRoot'])) {
            return (bool) $server['AllowRoot'];
        }

        return true;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setAllowNoPassword(array $server): bool
    {
        if (isset($server['AllowNoPassword'])) {
            return (bool) $server['AllowNoPassword'];
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return array<string, string|string[]>
     * @psalm-return array{order: ''|'deny,allow'|'allow,deny'|'explicit', rules: string[]}
     */
    private function setAllowDeny(array $server): array
    {
        $allowDeny = ['order' => '', 'rules' => []];
        if (isset($server['AllowDeny']) && is_array($server['AllowDeny'])) {
            if (
                isset($server['AllowDeny']['order'])
                && in_array($server['AllowDeny']['order'], ['deny,allow', 'allow,deny', 'explicit'], true)
            ) {
                $allowDeny['order'] = $server['AllowDeny']['order'];
            }

            if (isset($server['AllowDeny']['rules']) && is_array($server['AllowDeny']['rules'])) {
                /** @var mixed $rule */
                foreach ($server['AllowDeny']['rules'] as $rule) {
                    $allowDeny['rules'][] = (string) $rule;
                }
            }
        }

        return $allowDeny;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setDisableIs(array $server): bool
    {
        if (isset($server['DisableIS'])) {
            return (bool) $server['DisableIS'];
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setTrackingVersionAutoCreate(array $server): bool
    {
        if (isset($server['tracking_version_auto_create'])) {
            return (bool) $server['tracking_version_auto_create'];
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setTrackingDefaultStatements(array $server): string
    {
        if (isset($server['tracking_default_statements'])) {
            return (string) $server['tracking_default_statements'];
        }

        return 'CREATE TABLE,ALTER TABLE,DROP TABLE,RENAME TABLE,CREATE INDEX,DROP INDEX,INSERT,UPDATE,DELETE,'
            . 'TRUNCATE,REPLACE,CREATE VIEW,ALTER VIEW,DROP VIEW,CREATE DATABASE,ALTER DATABASE,DROP DATABASE';
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setTrackingAddDropView(array $server): bool
    {
        if (isset($server['tracking_add_drop_view'])) {
            return (bool) $server['tracking_add_drop_view'];
        }

        return true;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setTrackingAddDropTable(array $server): bool
    {
        if (isset($server['tracking_add_drop_table'])) {
            return (bool) $server['tracking_add_drop_table'];
        }

        return true;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setTrackingAddDropDatabase(array $server): bool
    {
        if (isset($server['tracking_add_drop_database'])) {
            return (bool) $server['tracking_add_drop_database'];
        }

        return true;
    }

    /**
     * @param array<int|string, mixed> $server
     */
    private function setHideConnectionErrors(array $server): bool
    {
        if (isset($server['hide_connection_errors'])) {
            return (bool) $server['hide_connection_errors'];
        }

        return false;
    }
}
