<?php
/**
 * set of functions with the Privileges section in pma
 */

declare(strict_types=1);

namespace PhpMyAdmin\Server;

use mysqli_stmt;
use PhpMyAdmin\ConfigStorage\Features\ConfigurableMenusFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\MysqliResult;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function explode;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function ksort;
use function max;
use function mb_chr;
use function mb_strpos;
use function mb_strrpos;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_replace;
use function strlen;
use function trim;
use function uksort;

/**
 * Privileges class
 */
class Privileges
{
    /** @var Template */
    public $template;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var DatabaseInterface */
    public $dbi;

    /** @var Relation */
    public $relation;

    /** @var Plugins */
    private $plugins;

    /**
     * @param Template          $template        Template object
     * @param DatabaseInterface $dbi             DatabaseInterface object
     * @param Relation          $relation        Relation object
     * @param RelationCleanup   $relationCleanup RelationCleanup object
     */
    public function __construct(
        Template $template,
        $dbi,
        Relation $relation,
        RelationCleanup $relationCleanup,
        Plugins $plugins
    ) {
        $this->template = $template;
        $this->dbi = $dbi;
        $this->relation = $relation;
        $this->relationCleanup = $relationCleanup;
        $this->plugins = $plugins;
    }

    /**
     * Escapes wildcard in a database+table specification
     * before using it in a GRANT statement.
     *
     * Escaping a wildcard character in a GRANT is only accepted at the global
     * or database level, not at table level; this is why I remove
     * the escaping character. Internally, in mysql.tables_priv.Db there are
     * no escaping (for example test_db) but in mysql.db you'll see test\_db
     * for a db-specific privilege.
     *
     * @param string $dbname    Database name
     * @param string $tablename Table name
     *
     * @return string the escaped (if necessary) database.table
     */
    public function wildcardEscapeForGrant(string $dbname, string $tablename): string
    {
        if (strlen($dbname) === 0) {
            return '*.*';
        }

        if (strlen($tablename) > 0) {
            return Util::backquote(
                Util::unescapeMysqlWildcards($dbname)
            )
            . '.' . Util::backquote($tablename);
        }

        return Util::backquote($dbname) . '.*';
    }

    /**
     * Generates a condition on the user name
     *
     * @param string|null $initial the user's initial
     *
     * @return string   the generated condition
     */
    public function rangeOfUsers($initial = '')
    {
        // strtolower() is used because the User field
        // might be BINARY, so LIKE would be case sensitive
        if ($initial === null || $initial === '') {
            return '';
        }

        return " WHERE `User` LIKE '"
            . $this->dbi->escapeString($initial) . "%'"
            . " OR `User` LIKE '"
            . $this->dbi->escapeString(mb_strtolower($initial))
            . "%'";
    }

    /**
     * Parses privileges into an array, it modifies the array
     *
     * @param array $row Results row from
     */
    public function fillInTablePrivileges(array &$row): void
    {
        $row1 = $this->dbi->fetchSingleRow('SHOW COLUMNS FROM `mysql`.`tables_priv` LIKE \'Table_priv\';');
        // note: in MySQL 5.0.3 we get "Create View', 'Show view';
        // the View for Create is spelled with uppercase V
        // the view for Show is spelled with lowercase v
        // and there is a space between the words

        $avGrants = explode(
            '\',\'',
            mb_substr(
                $row1['Type'],
                mb_strpos($row1['Type'], '(') + 2,
                mb_strpos($row1['Type'], ')')
                - mb_strpos($row1['Type'], '(') - 3
            )
        );

        $usersGrants = explode(',', $row['Table_priv']);

        foreach ($avGrants as $currentGrant) {
            $row[$currentGrant . '_priv'] = in_array($currentGrant, $usersGrants) ? 'Y' : 'N';
        }

        unset($row['Table_priv']);
    }

    /**
     * Extracts the privilege information of a priv table row
     *
     * @param array|null $row        the row
     * @param bool       $enableHTML add <dfn> tag with tooltips
     * @param bool       $tablePrivs whether row contains table privileges
     *
     * @return array
     *
     * @global resource $user_link the database connection
     */
    public function extractPrivInfo($row = null, $enableHTML = false, $tablePrivs = false)
    {
        if ($tablePrivs) {
            $grants = $this->getTableGrantsArray();
        } else {
            $grants = $this->getGrantsArray();
        }

        if ($row !== null && isset($row['Table_priv'])) {
            $this->fillInTablePrivileges($row);
        }

        $privs = [];
        $allPrivileges = true;
        foreach ($grants as $currentGrant) {
            if (
                ($row === null || ! isset($row[$currentGrant[0]]))
                && ($row !== null || ! isset($GLOBALS[$currentGrant[0]]))
            ) {
                continue;
            }

            if (
                ($row !== null && $row[$currentGrant[0]] === 'Y')
                || ($row === null
                && ($GLOBALS[$currentGrant[0]] === 'Y'
                || (is_array($GLOBALS[$currentGrant[0]])
                && count($GLOBALS[$currentGrant[0]]) == $_REQUEST['column_count']
                && empty($GLOBALS[$currentGrant[0] . '_none']))))
            ) {
                if ($enableHTML) {
                    $privs[] = '<dfn title="' . $currentGrant[2] . '">'
                    . $currentGrant[1] . '</dfn>';
                } else {
                    $privs[] = $currentGrant[1];
                }
            } elseif (
                ! empty($GLOBALS[$currentGrant[0]])
                && is_array($GLOBALS[$currentGrant[0]])
                && empty($GLOBALS[$currentGrant[0] . '_none'])
            ) {
                // Required for proper escaping of ` (backtick) in a column name
                $grantCols = array_map(
                    /**
                     * @param string $val
                     *
                     * @return string
                     */
                    static function ($val) {
                        return Util::backquote($val);
                    },
                    $GLOBALS[$currentGrant[0]]
                );

                if ($enableHTML) {
                    $privs[] = '<dfn title="' . $currentGrant[2] . '">'
                        . $currentGrant[1] . '</dfn>'
                        . ' (' . implode(', ', $grantCols) . ')';
                } else {
                    $privs[] = $currentGrant[1]
                        . ' (' . implode(', ', $grantCols) . ')';
                }
            } else {
                $allPrivileges = false;
            }
        }

        if (empty($privs)) {
            if ($enableHTML) {
                $privs[] = '<dfn title="' . __('No privileges.') . '">USAGE</dfn>';
            } else {
                $privs[] = 'USAGE';
            }
        } elseif ($allPrivileges && (! isset($_POST['grant_count']) || count($privs) == $_POST['grant_count'])) {
            if ($enableHTML) {
                $privs = [
                    '<dfn title="'
                    . __('Includes all privileges except GRANT.')
                    . '">ALL PRIVILEGES</dfn>',
                ];
            } else {
                $privs = ['ALL PRIVILEGES'];
            }
        }

        return $privs;
    }

    /**
     * Returns an array of table grants and their descriptions
     *
     * @return array array of table grants
     */
    public function getTableGrantsArray()
    {
        return [
            [
                'Delete',
                'DELETE',
                __('Allows deleting data.'),
            ],
            [
                'Create',
                'CREATE',
                __('Allows creating new tables.'),
            ],
            [
                'Drop',
                'DROP',
                __('Allows dropping tables.'),
            ],
            [
                'Index',
                'INDEX',
                __('Allows creating and dropping indexes.'),
            ],
            [
                'Alter',
                'ALTER',
                __('Allows altering the structure of existing tables.'),
            ],
            [
                'Create View',
                'CREATE_VIEW',
                __('Allows creating new views.'),
            ],
            [
                'Show view',
                'SHOW_VIEW',
                __('Allows performing SHOW CREATE VIEW queries.'),
            ],
            [
                'Trigger',
                'TRIGGER',
                __('Allows creating and dropping triggers.'),
            ],
        ];
    }

    /**
     * Get the grants array which contains all the privilege types
     * and relevant grant messages
     *
     * @return array
     */
    public function getGrantsArray()
    {
        return [
            [
                'Select_priv',
                'SELECT',
                __('Allows reading data.'),
            ],
            [
                'Insert_priv',
                'INSERT',
                __('Allows inserting and replacing data.'),
            ],
            [
                'Update_priv',
                'UPDATE',
                __('Allows changing data.'),
            ],
            [
                'Delete_priv',
                'DELETE',
                __('Allows deleting data.'),
            ],
            [
                'Create_priv',
                'CREATE',
                __('Allows creating new databases and tables.'),
            ],
            [
                'Drop_priv',
                'DROP',
                __('Allows dropping databases and tables.'),
            ],
            [
                'Reload_priv',
                'RELOAD',
                __('Allows reloading server settings and flushing the server\'s caches.'),
            ],
            [
                'Shutdown_priv',
                'SHUTDOWN',
                __('Allows shutting down the server.'),
            ],
            [
                'Process_priv',
                'PROCESS',
                __('Allows viewing processes of all users.'),
            ],
            [
                'File_priv',
                'FILE',
                __('Allows importing data from and exporting data into files.'),
            ],
            [
                'References_priv',
                'REFERENCES',
                __('Has no effect in this MySQL version.'),
            ],
            [
                'Index_priv',
                'INDEX',
                __('Allows creating and dropping indexes.'),
            ],
            [
                'Alter_priv',
                'ALTER',
                __('Allows altering the structure of existing tables.'),
            ],
            [
                'Show_db_priv',
                'SHOW DATABASES',
                __('Gives access to the complete list of databases.'),
            ],
            [
                'Super_priv',
                'SUPER',
                __(
                    'Allows connecting, even if maximum number of connections '
                    . 'is reached; required for most administrative operations '
                    . 'like setting global variables or killing threads of other users.'
                ),
            ],
            [
                'Create_tmp_table_priv',
                'CREATE TEMPORARY TABLES',
                __('Allows creating temporary tables.'),
            ],
            [
                'Lock_tables_priv',
                'LOCK TABLES',
                __('Allows locking tables for the current thread.'),
            ],
            [
                'Repl_slave_priv',
                'REPLICATION SLAVE',
                __('Needed for the replication replicas.'),
            ],
            [
                'Repl_client_priv',
                'REPLICATION CLIENT',
                __('Allows the user to ask where the replicas / primaries are.'),
            ],
            [
                'Create_view_priv',
                'CREATE VIEW',
                __('Allows creating new views.'),
            ],
            [
                'Event_priv',
                'EVENT',
                __('Allows to set up events for the event scheduler.'),
            ],
            [
                'Trigger_priv',
                'TRIGGER',
                __('Allows creating and dropping triggers.'),
            ],
            // for table privs:
            [
                'Create View_priv',
                'CREATE VIEW',
                __('Allows creating new views.'),
            ],
            [
                'Show_view_priv',
                'SHOW VIEW',
                __('Allows performing SHOW CREATE VIEW queries.'),
            ],
            // for table privs:
            [
                'Show view_priv',
                'SHOW VIEW',
                __('Allows performing SHOW CREATE VIEW queries.'),
            ],
            [
                'Delete_history_priv',
                'DELETE HISTORY',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                /* l10n: https://mariadb.com/kb/en/library/grant/#table-privileges "Remove historical rows from a table using the DELETE HISTORY statement" */
                __('Allows deleting historical rows.'),
            ],
            [
                // This was finally removed in the following MariaDB versions
                // @see https://jira.mariadb.org/browse/MDEV-20382
                'Delete versioning rows_priv',
                'DELETE HISTORY',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                /* l10n: https://mariadb.com/kb/en/library/grant/#table-privileges "Remove historical rows from a table using the DELETE HISTORY statement" */
                __('Allows deleting historical rows.'),
            ],
            [
                'Create_routine_priv',
                'CREATE ROUTINE',
                __('Allows creating stored routines.'),
            ],
            [
                'Alter_routine_priv',
                'ALTER ROUTINE',
                __('Allows altering and dropping stored routines.'),
            ],
            [
                'Create_user_priv',
                'CREATE USER',
                __('Allows creating, dropping and renaming user accounts.'),
            ],
            [
                'Execute_priv',
                'EXECUTE',
                __('Allows executing stored routines.'),
            ],
        ];
    }

    /**
     * Get sql query for display privileges table
     *
     * @param string $db       the database
     * @param string $table    the table
     * @param string $username username for database connection
     * @param string $hostname hostname for database connection
     *
     * @return string sql query
     */
    public function getSqlQueryForDisplayPrivTable(string $db, string $table, string $username, string $hostname)
    {
        if ($db === '*') {
            return 'SELECT * FROM `mysql`.`user`'
                . " WHERE `User` = '" . $this->dbi->escapeString($username) . "'"
                . " AND `Host` = '" . $this->dbi->escapeString($hostname) . "';";
        }

        if ($table === '*') {
            return 'SELECT * FROM `mysql`.`db`'
                . " WHERE `User` = '" . $this->dbi->escapeString($username) . "'"
                . " AND `Host` = '" . $this->dbi->escapeString($hostname) . "'"
                . " AND `Db` = '" . $this->dbi->escapeString($db) . "'";
        }

        return 'SELECT `Table_priv`'
            . ' FROM `mysql`.`tables_priv`'
            . " WHERE `User` = '" . $this->dbi->escapeString($username) . "'"
            . " AND `Host` = '" . $this->dbi->escapeString($hostname) . "'"
            . " AND `Db` = '" . $this->dbi->escapeString(Util::unescapeMysqlWildcards($db)) . "'"
            . " AND `Table_name` = '" . $this->dbi->escapeString($table) . "';";
    }

    /**
     * Sets the user group from request values
     *
     * @param string $username  username
     * @param string $userGroup user group to set
     */
    public function setUserGroup($username, $userGroup): void
    {
        $userGroup = $userGroup ?? '';
        $configurableMenusFeature = $this->relation->getRelationParameters()->configurableMenusFeature;
        if ($configurableMenusFeature === null) {
            return;
        }

        $userTable = Util::backquote($configurableMenusFeature->database)
            . '.' . Util::backquote($configurableMenusFeature->users);

        $sqlQuery = 'SELECT `usergroup` FROM ' . $userTable
            . " WHERE `username` = '" . $this->dbi->escapeString($username) . "'";
        $oldUserGroup = $this->dbi->fetchValue($sqlQuery, 0, DatabaseInterface::CONNECT_CONTROL);

        if ($oldUserGroup === false) {
            $updQuery = 'INSERT INTO ' . $userTable . '(`username`, `usergroup`)'
                . " VALUES ('" . $this->dbi->escapeString($username) . "', "
                . "'" . $this->dbi->escapeString($userGroup) . "')";
        } else {
            if (empty($userGroup)) {
                $updQuery = 'DELETE FROM ' . $userTable
                    . " WHERE `username`='" . $this->dbi->escapeString($username) . "'";
            } elseif ($oldUserGroup != $userGroup) {
                $updQuery = 'UPDATE ' . $userTable
                    . " SET `usergroup`='" . $this->dbi->escapeString($userGroup) . "'"
                    . " WHERE `username`='" . $this->dbi->escapeString($username) . "'";
            }
        }

        if (! isset($updQuery)) {
            return;
        }

        $this->dbi->queryAsControlUser($updQuery);
    }

    /**
     * Displays the privileges form table
     *
     * @param string $db     the database
     * @param string $table  the table
     * @param bool   $submit whether to display the submit button or not
     *
     * @return string html snippet
     *
     * @global array     $cfg         the phpMyAdmin configuration
     * @global resource  $user_link   the database connection
     */
    public function getHtmlToDisplayPrivilegesTable(
        $db = '*',
        $table = '*',
        $submit = true
    ) {
        if ($db === '*') {
            $table = '*';
        }

        $username = '';
        $hostname = '';
        $row = [];
        if (isset($GLOBALS['username'])) {
            $username = $GLOBALS['username'];
            $hostname = $GLOBALS['hostname'];
            $sqlQuery = $this->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
            $row = $this->dbi->fetchSingleRow($sqlQuery);
        }

        if (empty($row)) {
            if ($table === '*' && $this->dbi->isSuperUser()) {
                $row = [];
                $sqlQuery = 'SHOW COLUMNS FROM `mysql`.' . ($db === '*' ? '`user`' : '`db`') . ';';

                $res = $this->dbi->query($sqlQuery);
                while ($row1 = $res->fetchRow()) {
                    if (mb_substr($row1[0], 0, 4) === 'max_') {
                        $row[$row1[0]] = 0;
                    } elseif (mb_substr($row1[0], 0, 5) === 'x509_' || mb_substr($row1[0], 0, 4) === 'ssl_') {
                        $row[$row1[0]] = '';
                    } else {
                        $row[$row1[0]] = 'N';
                    }
                }
            } elseif ($table === '*') {
                $row = [];
            } else {
                $row = ['Table_priv' => ''];
            }
        }

        if (isset($row['Table_priv'])) {
            $this->fillInTablePrivileges($row);

            // get columns
            $res = $this->dbi->tryQuery(
                'SHOW COLUMNS FROM '
                . Util::backquote(
                    Util::unescapeMysqlWildcards($db)
                )
                . '.' . Util::backquote($table) . ';'
            );
            $columns = [];
            if ($res) {
                while ($row1 = $res->fetchRow()) {
                    $columns[$row1[0]] = [
                        'Select' => false,
                        'Insert' => false,
                        'Update' => false,
                        'References' => false,
                    ];
                }
            }
        }

        if (! empty($columns)) {
            $res = $this->dbi->query(
                'SELECT `Column_name`, `Column_priv`'
                . ' FROM `mysql`.`columns_priv`'
                . ' WHERE `User`'
                . ' = \'' . $this->dbi->escapeString($username) . "'"
                . ' AND `Host`'
                . ' = \'' . $this->dbi->escapeString($hostname) . "'"
                . ' AND `Db`'
                . ' = \'' . $this->dbi->escapeString(
                    Util::unescapeMysqlWildcards($db)
                ) . "'"
                . ' AND `Table_name`'
                . ' = \'' . $this->dbi->escapeString($table) . '\';'
            );

            while ($row1 = $res->fetchRow()) {
                $row1[1] = explode(',', $row1[1]);
                foreach ($row1[1] as $current) {
                    $columns[$row1[0]][$current] = true;
                }
            }
        }

        return $this->template->render('server/privileges/privileges_table', [
            'is_global' => $db === '*',
            'is_database' => $table === '*',
            'row' => $row,
            'columns' => $columns ?? [],
            'has_submit' => $submit,
            'supports_references_privilege' => Compatibility::supportsReferencesPrivilege($this->dbi),
            'is_mariadb' => $this->dbi->isMariaDB(),
        ]);
    }

    /**
     * Get the HTML snippet for routine specific privileges
     *
     * @param string $username  username for database connection
     * @param string $hostname  hostname for database connection
     * @param string $db        the database
     * @param string $routine   the routine
     * @param string $urlDbname url encoded db name
     *
     * @return string
     */
    public function getHtmlForRoutineSpecificPrivileges(
        string $username,
        string $hostname,
        string $db,
        string $routine,
        $urlDbname
    ) {
        $privileges = $this->getRoutinePrivileges($username, $hostname, $db, $routine);

        return $this->template->render('server/privileges/edit_routine_privileges', [
            'username' => $username,
            'hostname' => $hostname,
            'database' => $db,
            'routine' => $routine,
            'privileges' => $privileges,
            'dbname' => $urlDbname,
            'current_user' => $this->dbi->getCurrentUser(),
        ]);
    }

    /**
     * Displays the fields used by the "new user" form as well as the
     * "change login information / copy user" form.
     *
     * @param string $mode are we creating a new user or are we just
     *                     changing  one? (allowed values: 'new', 'change')
     * @param string $user User name
     * @param string $host Host name
     *
     * @return string  a HTML snippet
     */
    public function getHtmlForLoginInformationFields(
        $mode = 'new',
        $user = null,
        $host = null
    ) {
        global $pred_username, $pred_hostname, $username, $hostname, $new_username;

        [$usernameLength, $hostnameLength] = $this->getUsernameAndHostnameLength();

        if (isset($username) && strlen($username) === 0) {
            $pred_username = 'any';
        }

        $currentUser = $this->dbi->fetchValue('SELECT USER();');
        $thisHost = null;
        if (! empty($currentUser)) {
            $thisHost = str_replace(
                '\'',
                '',
                mb_substr(
                    $currentUser,
                    mb_strrpos($currentUser, '@') + 1
                )
            );
        }

        if (! isset($pred_hostname) && isset($hostname)) {
            switch (mb_strtolower($hostname)) {
                case 'localhost':
                case '127.0.0.1':
                    $pred_hostname = 'localhost';
                    break;
                case '%':
                    $pred_hostname = 'any';
                    break;
                default:
                    $pred_hostname = 'userdefined';
                    break;
            }
        }

        $serverVersion = $this->dbi->getVersion();
        $authPlugin = $this->getCurrentAuthenticationPlugin($mode, $user, $host);

        $isNew = (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50507)
            || (Compatibility::isMariaDb() && $serverVersion >= 50200);

        $activeAuthPlugins = ['mysql_native_password' => __('Native MySQL authentication')];
        if ($isNew) {
            $activeAuthPlugins = $this->plugins->getAuthentication();
            if (isset($activeAuthPlugins['mysql_old_password'])) {
                unset($activeAuthPlugins['mysql_old_password']);
            }
        }

        return $this->template->render('server/privileges/login_information_fields', [
            'pred_username' => $pred_username ?? null,
            'pred_hostname' => $pred_hostname ?? null,
            'username_length' => $usernameLength,
            'hostname_length' => $hostnameLength,
            'username' => $username ?? null,
            'new_username' => $new_username ?? null,
            'hostname' => $hostname ?? null,
            'this_host' => $thisHost,
            'is_change' => $mode === 'change',
            'auth_plugin' => $authPlugin,
            'active_auth_plugins' => $activeAuthPlugins,
            'is_new' => $isNew,
        ]);
    }

    /**
     * Get username and hostname length
     *
     * @return array username length and hostname length
     */
    public function getUsernameAndHostnameLength()
    {
        /* Fallback values */
        $usernameLength = 16;
        $hostnameLength = 41;

        /* Try to get real lengths from the database */
        $fieldsInfo = $this->dbi->fetchResult(
            'SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH '
            . 'FROM information_schema.columns '
            . "WHERE table_schema = 'mysql' AND table_name = 'user' "
            . "AND COLUMN_NAME IN ('User', 'Host')"
        );
        foreach ($fieldsInfo as $val) {
            if ($val['COLUMN_NAME'] === 'User') {
                $usernameLength = $val['CHARACTER_MAXIMUM_LENGTH'];
            } elseif ($val['COLUMN_NAME'] === 'Host') {
                $hostnameLength = $val['CHARACTER_MAXIMUM_LENGTH'];
            }
        }

        return [
            $usernameLength,
            $hostnameLength,
        ];
    }

    /**
     * Get current authentication plugin in use - for a user or globally
     *
     * @param string $mode     are we creating a new user or are we just
     *                         changing  one? (allowed values: 'new', 'change')
     * @param string $username User name
     * @param string $hostname Host name
     *
     * @return string authentication plugin in use
     */
    public function getCurrentAuthenticationPlugin(
        $mode = 'new',
        $username = null,
        $hostname = null
    ) {
        global $dbi;

        /* Fallback (standard) value */
        $authenticationPlugin = 'mysql_native_password';
        $serverVersion = $this->dbi->getVersion();

        if (isset($username, $hostname) && $mode === 'change') {
            $row = $this->dbi->fetchSingleRow(
                'SELECT `plugin` FROM `mysql`.`user` WHERE `User` = "'
                . $dbi->escapeString($username)
                . '" AND `Host` = "'
                . $dbi->escapeString($hostname)
                . '" LIMIT 1'
            );
            // Table 'mysql'.'user' may not exist for some previous
            // versions of MySQL - in that case consider fallback value
            if (is_array($row) && isset($row['plugin'])) {
                $authenticationPlugin = $row['plugin'];
            }
        } elseif ($mode === 'change') {
            [$username, $hostname] = $this->dbi->getCurrentUserAndHost();

            $row = $this->dbi->fetchSingleRow(
                'SELECT `plugin` FROM `mysql`.`user` WHERE `User` = "'
                . $dbi->escapeString($username)
                . '" AND `Host` = "'
                . $dbi->escapeString($hostname)
                . '"'
            );
            if (is_array($row) && isset($row['plugin'])) {
                $authenticationPlugin = $row['plugin'];
            }
        } elseif ($serverVersion >= 50702) {
            $row = $this->dbi->fetchSingleRow('SELECT @@default_authentication_plugin');
            $authenticationPlugin = is_array($row) ? $row['@@default_authentication_plugin'] : null;
        }

        return $authenticationPlugin;
    }

    /**
     * Returns all the grants for a certain user on a certain host
     * Used in the export privileges for all users section
     *
     * @param string $user User name
     * @param string $host Host name
     *
     * @return string containing all the grants text
     */
    public function getGrants($user, $host)
    {
        $grants = $this->dbi->fetchResult(
            "SHOW GRANTS FOR '"
            . $this->dbi->escapeString($user) . "'@'"
            . $this->dbi->escapeString($host) . "'"
        );
        $response = '';
        foreach ($grants as $oneGrant) {
            $response .= $oneGrant . ";\n\n";
        }

        return $response;
    }

    /**
     * Update password and get message for password updating
     *
     * @param string $errorUrl error url
     * @param string $username username
     * @param string $hostname hostname
     *
     * @return Message success or error message after updating password
     */
    public function updatePassword($errorUrl, $username, $hostname)
    {
        global $dbi;

        // similar logic in /user-password
        $message = null;

        if (isset($_POST['pma_pw'], $_POST['pma_pw2']) && empty($_POST['nopass'])) {
            if ($_POST['pma_pw'] != $_POST['pma_pw2']) {
                $message = Message::error(__('The passwords aren\'t the same!'));
            } elseif (empty($_POST['pma_pw']) || empty($_POST['pma_pw2'])) {
                $message = Message::error(__('The password is empty!'));
            }
        }

        // here $nopass could be == 1
        if ($message === null) {
            $hashingFunction = 'PASSWORD';
            $serverVersion = $this->dbi->getVersion();
            $authenticationPlugin = ($_POST['authentication_plugin'] ?? $this->getCurrentAuthenticationPlugin(
                'change',
                $username,
                $hostname
            ));

            // Use 'ALTER USER ...' syntax for MySQL 5.7.6+
            if (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50706) {
                if ($authenticationPlugin !== 'mysql_old_password') {
                    $queryPrefix = "ALTER USER '"
                        . $this->dbi->escapeString($username)
                        . "'@'" . $this->dbi->escapeString($hostname) . "'"
                        . ' IDENTIFIED WITH '
                        . $authenticationPlugin
                        . " BY '";
                } else {
                    $queryPrefix = "ALTER USER '"
                        . $this->dbi->escapeString($username)
                        . "'@'" . $this->dbi->escapeString($hostname) . "'"
                        . " IDENTIFIED BY '";
                }

                // in $sql_query which will be displayed, hide the password
                $sqlQuery = $queryPrefix . "*'";

                $localQuery = $queryPrefix
                    . $this->dbi->escapeString($_POST['pma_pw']) . "'";
            } elseif (Compatibility::isMariaDb() && $serverVersion >= 10000) {
                // MariaDB uses "SET PASSWORD" syntax to change user password.
                // On Galera cluster only DDL queries are replicated, since
                // users are stored in MyISAM storage engine.
                $queryPrefix = "SET PASSWORD FOR  '"
                    . $this->dbi->escapeString($username)
                    . "'@'" . $this->dbi->escapeString($hostname) . "'"
                    . " = PASSWORD ('";
                $sqlQuery = $localQuery = $queryPrefix
                    . $this->dbi->escapeString($_POST['pma_pw']) . "')";
            } elseif (Compatibility::isMariaDb() && $serverVersion >= 50200 && $this->dbi->isSuperUser()) {
                // Use 'UPDATE `mysql`.`user` ...' Syntax for MariaDB 5.2+
                if ($authenticationPlugin === 'mysql_native_password') {
                    // Set the hashing method used by PASSWORD()
                    // to be 'mysql_native_password' type
                    $this->dbi->tryQuery('SET old_passwords = 0;');
                } elseif ($authenticationPlugin === 'sha256_password') {
                    // Set the hashing method used by PASSWORD()
                    // to be 'sha256_password' type
                    $this->dbi->tryQuery('SET `old_passwords` = 2;');
                }

                $hashedPassword = $this->getHashedPassword($_POST['pma_pw']);

                $sqlQuery = 'SET PASSWORD FOR \''
                    . $this->dbi->escapeString($username)
                    . '\'@\'' . $this->dbi->escapeString($hostname) . '\' = '
                    . ($_POST['pma_pw'] == ''
                        ? '\'\''
                        : $hashingFunction . '(\''
                        . preg_replace('@.@s', '*', $_POST['pma_pw']) . '\')');

                $localQuery = 'UPDATE `mysql`.`user` SET '
                    . " `authentication_string` = '" . $hashedPassword
                    . "', `Password` = '', "
                    . " `plugin` = '" . $authenticationPlugin . "'"
                    . " WHERE `User` = '" . $dbi->escapeString($username)
                    . "' AND Host = '" . $dbi->escapeString($hostname) . "';";
            } else {
                // USE 'SET PASSWORD ...' syntax for rest of the versions
                // Backup the old value, to be reset later
                $row = $this->dbi->fetchSingleRow('SELECT @@old_passwords;');
                $origValue = $row['@@old_passwords'];
                $updatePluginQuery = 'UPDATE `mysql`.`user` SET'
                    . " `plugin` = '" . $authenticationPlugin . "'"
                    . " WHERE `User` = '" . $dbi->escapeString($username)
                    . "' AND Host = '" . $dbi->escapeString($hostname) . "';";

                // Update the plugin for the user
                if (! $this->dbi->tryQuery($updatePluginQuery)) {
                    Generator::mysqlDie(
                        $this->dbi->getError(),
                        $updatePluginQuery,
                        false,
                        $errorUrl
                    );
                }

                $this->dbi->tryQuery('FLUSH PRIVILEGES;');

                if ($authenticationPlugin === 'mysql_native_password') {
                    // Set the hashing method used by PASSWORD()
                    // to be 'mysql_native_password' type
                    $this->dbi->tryQuery('SET old_passwords = 0;');
                } elseif ($authenticationPlugin === 'sha256_password') {
                    // Set the hashing method used by PASSWORD()
                    // to be 'sha256_password' type
                    $this->dbi->tryQuery('SET `old_passwords` = 2;');
                }

                $sqlQuery = 'SET PASSWORD FOR \''
                    . $this->dbi->escapeString($username)
                    . '\'@\'' . $this->dbi->escapeString($hostname) . '\' = '
                    . ($_POST['pma_pw'] == ''
                        ? '\'\''
                        : $hashingFunction . '(\''
                        . preg_replace('@.@s', '*', $_POST['pma_pw']) . '\')');

                $localQuery = 'SET PASSWORD FOR \''
                    . $this->dbi->escapeString($username)
                    . '\'@\'' . $this->dbi->escapeString($hostname) . '\' = '
                    . ($_POST['pma_pw'] == '' ? '\'\'' : $hashingFunction
                    . '(\'' . $this->dbi->escapeString($_POST['pma_pw']) . '\')');
            }

            if (! $this->dbi->tryQuery($localQuery)) {
                Generator::mysqlDie(
                    $this->dbi->getError(),
                    $sqlQuery,
                    false,
                    $errorUrl
                );
            }

            // Flush privileges after successful password change
            $this->dbi->tryQuery('FLUSH PRIVILEGES;');

            $message = Message::success(
                __('The password for %s was changed successfully.')
            );
            $message->addParam('\'' . $username . '\'@\'' . $hostname . '\'');
            if (isset($origValue)) {
                $this->dbi->tryQuery('SET `old_passwords` = ' . $origValue . ';');
            }
        }

        return $message;
    }

    /**
     * Revokes privileges and get message and SQL query for privileges revokes
     *
     * @param string $dbname    database name
     * @param string $tablename table name
     * @param string $username  username
     * @param string $hostname  host name
     * @param string $itemType  item type
     *
     * @return array ($message, $sql_query)
     */
    public function getMessageAndSqlQueryForPrivilegesRevoke(
        string $dbname,
        string $tablename,
        string $username,
        string $hostname,
        $itemType
    ) {
        $dbAndTable = $this->wildcardEscapeForGrant($dbname, $tablename);

        $sqlQuery0 = 'REVOKE ALL PRIVILEGES ON ' . $itemType . ' ' . $dbAndTable
            . ' FROM \''
            . $this->dbi->escapeString($username) . '\'@\''
            . $this->dbi->escapeString($hostname) . '\';';

        $sqlQuery1 = 'REVOKE GRANT OPTION ON ' . $itemType . ' ' . $dbAndTable
            . ' FROM \'' . $this->dbi->escapeString($username) . '\'@\''
            . $this->dbi->escapeString($hostname) . '\';';

        $this->dbi->query($sqlQuery0);
        if (! $this->dbi->tryQuery($sqlQuery1)) {
            // this one may fail, too...
            $sqlQuery1 = '';
        }

        $sqlQuery = $sqlQuery0 . ' ' . $sqlQuery1;
        $message = Message::success(
            __('You have revoked the privileges for %s.')
        );
        $message->addParam('\'' . $username . '\'@\'' . $hostname . '\'');

        return [
            $message,
            $sqlQuery,
        ];
    }

    /**
     * Get REQUIRE clause
     *
     * @return string REQUIRE clause
     */
    public function getRequireClause()
    {
        $arr = isset($_POST['ssl_type']) ? $_POST : $GLOBALS;
        if (isset($arr['ssl_type']) && $arr['ssl_type'] === 'SPECIFIED') {
            $require = [];
            if (! empty($arr['ssl_cipher'])) {
                $require[] = "CIPHER '"
                        . $this->dbi->escapeString($arr['ssl_cipher']) . "'";
            }

            if (! empty($arr['x509_issuer'])) {
                $require[] = "ISSUER '"
                        . $this->dbi->escapeString($arr['x509_issuer']) . "'";
            }

            if (! empty($arr['x509_subject'])) {
                $require[] = "SUBJECT '"
                        . $this->dbi->escapeString($arr['x509_subject']) . "'";
            }

            if (count($require)) {
                $requireClause = ' REQUIRE ' . implode(' AND ', $require);
            } else {
                $requireClause = ' REQUIRE NONE';
            }
        } elseif (isset($arr['ssl_type']) && $arr['ssl_type'] === 'X509') {
            $requireClause = ' REQUIRE X509';
        } elseif (isset($arr['ssl_type']) && $arr['ssl_type'] === 'ANY') {
            $requireClause = ' REQUIRE SSL';
        } else {
            $requireClause = ' REQUIRE NONE';
        }

        return $requireClause;
    }

    /**
     * Get a WITH clause for 'update privileges' and 'add user'
     *
     * @return string
     */
    public function getWithClauseForAddUserAndUpdatePrivs()
    {
        $sqlQuery = '';
        if (
            ((isset($_POST['Grant_priv']) && $_POST['Grant_priv'] === 'Y')
            || (isset($GLOBALS['Grant_priv']) && $GLOBALS['Grant_priv'] === 'Y'))
            && ! (Compatibility::isMySqlOrPerconaDb() && $this->dbi->getVersion() >= 80011)
        ) {
            $sqlQuery .= ' GRANT OPTION';
        }

        if (isset($_POST['max_questions']) || isset($GLOBALS['max_questions'])) {
            $maxQuestions = isset($_POST['max_questions'])
                ? (int) $_POST['max_questions'] : (int) $GLOBALS['max_questions'];
            $maxQuestions = max(0, $maxQuestions);
            $sqlQuery .= ' MAX_QUERIES_PER_HOUR ' . $maxQuestions;
        }

        if (isset($_POST['max_connections']) || isset($GLOBALS['max_connections'])) {
            $maxConnections = isset($_POST['max_connections'])
                ? (int) $_POST['max_connections'] : (int) $GLOBALS['max_connections'];
            $maxConnections = max(0, $maxConnections);
            $sqlQuery .= ' MAX_CONNECTIONS_PER_HOUR ' . $maxConnections;
        }

        if (isset($_POST['max_updates']) || isset($GLOBALS['max_updates'])) {
            $maxUpdates = isset($_POST['max_updates'])
                ? (int) $_POST['max_updates'] : (int) $GLOBALS['max_updates'];
            $maxUpdates = max(0, $maxUpdates);
            $sqlQuery .= ' MAX_UPDATES_PER_HOUR ' . $maxUpdates;
        }

        if (isset($_POST['max_user_connections']) || isset($GLOBALS['max_user_connections'])) {
            $maxUserConnections = isset($_POST['max_user_connections'])
                ? (int) $_POST['max_user_connections']
                : (int) $GLOBALS['max_user_connections'];
            $maxUserConnections = max(0, $maxUserConnections);
            $sqlQuery .= ' MAX_USER_CONNECTIONS ' . $maxUserConnections;
        }

        return ! empty($sqlQuery) ? ' WITH' . $sqlQuery : '';
    }

    /**
     * Get HTML for addUsersForm, This function call if isset($_GET['adduser'])
     *
     * @param string $dbname database name
     *
     * @return string HTML for addUserForm
     */
    public function getHtmlForAddUser($dbname)
    {
        $isGrantUser = $this->dbi->isGrantUser();
        $loginInformationFieldsNew = $this->getHtmlForLoginInformationFields('new');
        $privilegesTable = '';
        if ($isGrantUser) {
            $privilegesTable = $this->getHtmlToDisplayPrivilegesTable('*', '*', false);
        }

        return $this->template->render('server/privileges/add_user', [
            'database' => $dbname,
            'login_information_fields_new' => $loginInformationFieldsNew,
            'is_grant_user' => $isGrantUser,
            'privileges_table' => $privilegesTable,
        ]);
    }

    /**
     * @param string $db    database name
     * @param string $table table name
     *
     * @return array
     */
    public function getAllPrivileges(string $db, string $table = ''): array
    {
        $databasePrivileges = $this->getGlobalAndDatabasePrivileges($db);
        $tablePrivileges = [];
        if ($table !== '') {
            $tablePrivileges = $this->getTablePrivileges($db, $table);
        }

        $routinePrivileges = $this->getRoutinesPrivileges($db);
        $allPrivileges = array_merge($databasePrivileges, $tablePrivileges, $routinePrivileges);

        $privileges = [];
        foreach ($allPrivileges as $privilege) {
            $userHost = $privilege['User'] . '@' . $privilege['Host'];
            $privileges[$userHost] = $privileges[$userHost] ?? [];
            $privileges[$userHost]['user'] = (string) $privilege['User'];
            $privileges[$userHost]['host'] = (string) $privilege['Host'];
            $privileges[$userHost]['privileges'] = $privileges[$userHost]['privileges'] ?? [];
            $privileges[$userHost]['privileges'][] = $this->getSpecificPrivilege($privilege);
        }

        return $privileges;
    }

    /**
     * @param array $row Array with user privileges
     *
     * @return array
     */
    private function getSpecificPrivilege(array $row): array
    {
        $privilege = [
            'type' => $row['Type'],
            'database' => $row['Db'],
        ];
        if ($row['Type'] === 'r') {
            $privilege['routine'] = $row['Routine_name'];
            $privilege['has_grant'] = str_contains($row['Proc_priv'], 'Grant');
            $privilege['privileges'] = explode(',', $row['Proc_priv']);
        } elseif ($row['Type'] === 't') {
            $privilege['table'] = $row['Table_name'];
            $privilege['has_grant'] = str_contains($row['Table_priv'], 'Grant');
            $tablePrivs = explode(',', $row['Table_priv']);
            $specificPrivileges = [];
            $grantsArr = $this->getTableGrantsArray();
            foreach ($grantsArr as $grant) {
                $specificPrivileges[$grant[0]] = 'N';
                foreach ($tablePrivs as $tablePriv) {
                    if ($grant[0] != $tablePriv) {
                        continue;
                    }

                    $specificPrivileges[$grant[0]] = 'Y';
                }
            }

            $privilege['privileges'] = $this->extractPrivInfo($specificPrivileges, true, true);
        } else {
            $privilege['has_grant'] = $row['Grant_priv'] === 'Y';
            $privilege['privileges'] = $this->extractPrivInfo($row, true);
        }

        return $privilege;
    }

    /**
     * @param string $db database name
     *
     * @return array
     */
    private function getGlobalAndDatabasePrivileges(string $db): array
    {
        $listOfPrivileges = '`Select_priv`,
            `Insert_priv`,
            `Update_priv`,
            `Delete_priv`,
            `Create_priv`,
            `Drop_priv`,
            `Grant_priv`,
            `Index_priv`,
            `Alter_priv`,
            `References_priv`,
            `Create_tmp_table_priv`,
            `Lock_tables_priv`,
            `Create_view_priv`,
            `Show_view_priv`,
            `Create_routine_priv`,
            `Alter_routine_priv`,
            `Execute_priv`,
            `Event_priv`,
            `Trigger_priv`,';

        $listOfComparedPrivileges = 'BINARY `Select_priv` = \'N\' AND
            BINARY `Insert_priv` = \'N\' AND
            BINARY `Update_priv` = \'N\' AND
            BINARY `Delete_priv` = \'N\' AND
            BINARY `Create_priv` = \'N\' AND
            BINARY `Drop_priv` = \'N\' AND
            BINARY `Grant_priv` = \'N\' AND
            BINARY `References_priv` = \'N\' AND
            BINARY `Create_tmp_table_priv` = \'N\' AND
            BINARY `Lock_tables_priv` = \'N\' AND
            BINARY `Create_view_priv` = \'N\' AND
            BINARY `Show_view_priv` = \'N\' AND
            BINARY `Create_routine_priv` = \'N\' AND
            BINARY `Alter_routine_priv` = \'N\' AND
            BINARY `Execute_priv` = \'N\' AND
            BINARY `Event_priv` = \'N\' AND
            BINARY `Trigger_priv` = \'N\'';

        $query = '
            (
                SELECT `User`, `Host`, ' . $listOfPrivileges . ' \'*\' AS `Db`, \'g\' AS `Type`
                FROM `mysql`.`user`
                WHERE NOT (' . $listOfComparedPrivileges . ')
            )
            UNION
            (
                SELECT `User`, `Host`, ' . $listOfPrivileges . ' `Db`, \'d\' AS `Type`
                FROM `mysql`.`db`
                WHERE \'' . $this->dbi->escapeString($db) . '\' LIKE `Db` AND NOT (' . $listOfComparedPrivileges . ')
            )
            ORDER BY `User` ASC, `Host` ASC, `Db` ASC;
        ';
        $result = $this->dbi->query($query);

        return $result->fetchAllAssoc();
    }

    /**
     * @param string $db    database name
     * @param string $table table name
     *
     * @return array
     */
    private function getTablePrivileges(string $db, string $table): array
    {
        $query = '
            SELECT `User`, `Host`, `Db`, \'t\' AS `Type`, `Table_name`, `Table_priv`
            FROM `mysql`.`tables_priv`
            WHERE
                ? LIKE `Db` AND
                ? LIKE `Table_name` AND
                NOT (`Table_priv` = \'\' AND Column_priv = \'\')
            ORDER BY `User` ASC, `Host` ASC, `Db` ASC, `Table_priv` ASC;
        ';
        /** @var mysqli_stmt|false $statement */
        $statement = $this->dbi->prepare($query);
        if ($statement === false || ! $statement->bind_param('ss', $db, $table) || ! $statement->execute()) {
            return [];
        }

        $result = new MysqliResult($statement->get_result());

        return $result->fetchAllAssoc();
    }

    /**
     * @param string $db database name
     *
     * @return array
     */
    private function getRoutinesPrivileges(string $db): array
    {
        $query = '
            SELECT *, \'r\' AS `Type`
            FROM `mysql`.`procs_priv`
            WHERE Db = \'' . $this->dbi->escapeString($db) . '\';
        ';
        $result = $this->dbi->query($query);

        return $result->fetchAllAssoc();
    }

    /**
     * Get HTML error for View Users form
     * For non superusers such as grant/create users
     *
     * @return string
     */
    public function getHtmlForViewUsersError()
    {
        return Message::error(
            __('Not enough privilege to view users.')
        )->getDisplay();
    }

    /**
     * Returns edit, revoke or export link for a user.
     *
     * @param string $linktype    The link type (edit | revoke | export)
     * @param string $username    User name
     * @param string $hostname    Host name
     * @param string $dbname      Database name
     * @param string $tablename   Table name
     * @param string $routinename Routine name
     * @param string $initial     Initial value
     *
     * @return string HTML code with link
     */
    public function getUserLink(
        $linktype,
        $username,
        $hostname,
        $dbname = '',
        $tablename = '',
        $routinename = '',
        $initial = ''
    ) {
        $linkClass = '';
        switch ($linktype) {
            case 'edit':
                $linkClass = 'edit_user_anchor';
                break;
            case 'export':
                $linkClass = 'export_user_anchor ajax';
                break;
        }

        $params = [
            'username' => $username,
            'hostname' => $hostname,
        ];
        switch ($linktype) {
            case 'edit':
                $params['dbname'] = $dbname;
                $params['tablename'] = $tablename;
                $params['routinename'] = $routinename;
                break;
            case 'revoke':
                $params['dbname'] = $dbname;
                $params['tablename'] = $tablename;
                $params['routinename'] = $routinename;
                $params['revokeall'] = 1;
                break;
            case 'export':
                $params['initial'] = $initial;
                $params['export'] = 1;
                break;
        }

        $action = [];
        switch ($linktype) {
            case 'edit':
                $action['icon'] = 'b_usredit';
                $action['text'] = __('Edit privileges');
                break;
            case 'revoke':
                $action['icon'] = 'b_usrdrop';
                $action['text'] = __('Revoke');
                break;
            case 'export':
                $action['icon'] = 'b_tblexport';
                $action['text'] = __('Export');
                break;
        }

        return $this->template->render('server/privileges/get_user_link', [
            'link_class' => $linkClass,
            'is_revoke' => $linktype === 'revoke',
            'url_params' => $params,
            'action' => $action,
        ]);
    }

    /**
     * Returns number of defined user groups
     */
    public function getUserGroupCount(ConfigurableMenusFeature $configurableMenusFeature): int
    {
        $userGroupTable = Util::backquote($configurableMenusFeature->database)
            . '.' . Util::backquote($configurableMenusFeature->userGroups);
        $sqlQuery = 'SELECT COUNT(*) FROM ' . $userGroupTable;

        return (int) $this->dbi->fetchValue($sqlQuery, 0, DatabaseInterface::CONNECT_CONTROL);
    }

    /**
     * Returns name of user group that user is part of
     *
     * @param string $username User name
     *
     * @return mixed|null usergroup if found or null if not found
     */
    public function getUserGroupForUser($username)
    {
        $configurableMenusFeature = $this->relation->getRelationParameters()->configurableMenusFeature;
        if ($configurableMenusFeature === null) {
            return null;
        }

        $userTable = Util::backquote($configurableMenusFeature->database)
            . '.' . Util::backquote($configurableMenusFeature->users);
        $sqlQuery = 'SELECT `usergroup` FROM ' . $userTable
            . ' WHERE `username` = \'' . $username . '\''
            . ' LIMIT 1';

        $usergroup = $this->dbi->fetchValue($sqlQuery, 0, DatabaseInterface::CONNECT_CONTROL);

        if ($usergroup === false) {
            return null;
        }

        return $usergroup;
    }

    /**
     * This function return the extra data array for the ajax behavior
     *
     * @param string $password password
     * @param string $sqlQuery sql query
     * @param string $hostname hostname
     * @param string $username username
     *
     * @return array
     */
    public function getExtraDataForAjaxBehavior(
        $password,
        $sqlQuery,
        $hostname,
        $username
    ) {
        if (isset($GLOBALS['dbname'])) {
            //if (preg_match('/\\\\(?:_|%)/i', $dbname)) {
            if (preg_match('/(?<!\\\\)(?:_|%)/', $GLOBALS['dbname'])) {
                $dbnameIsWildcard = true;
            } else {
                $dbnameIsWildcard = false;
            }
        }

        $configurableMenusFeature = $this->relation->getRelationParameters()->configurableMenusFeature;

        $userGroupCount = 0;
        if ($configurableMenusFeature !== null) {
            $userGroupCount = $this->getUserGroupCount($configurableMenusFeature);
        }

        $extraData = [];
        if (strlen($sqlQuery) > 0) {
            $extraData['sql_query'] = Generator::getMessage('', $sqlQuery);
        }

        if (isset($_POST['change_copy'])) {
            $user = [
                'name' => $username,
                'host' => $hostname,
                'has_password' => ! empty($password) || isset($_POST['pma_pw']),
                'privileges' => implode(', ', $this->extractPrivInfo(null, true)),
                'has_group' => $configurableMenusFeature !== null,
                'has_group_edit' => $configurableMenusFeature !== null && $userGroupCount > 0,
                'has_grant' => isset($_POST['Grant_priv']) && $_POST['Grant_priv'] === 'Y',
            ];
            $extraData['new_user_string'] = $this->template->render('server/privileges/new_user_ajax', [
                'user' => $user,
                'is_grantuser' => $this->dbi->isGrantUser(),
                'initial' => $_GET['initial'] ?? '',
            ]);

            /**
             * Generate the string for this alphabet's initial, to update the user
             * pagination
             */
            $newUserInitial = mb_strtoupper(
                mb_substr($username, 0, 1)
            );
            $newUserInitialString = '<a href="';
            $newUserInitialString .= Url::getFromRoute('/server/privileges', ['initial' => $newUserInitial]);
            $newUserInitialString .= '">' . $newUserInitial . '</a>';
            $extraData['new_user_initial'] = $newUserInitial;
            $extraData['new_user_initial_string'] = $newUserInitialString;
        }

        if (isset($_POST['update_privs'])) {
            $extraData['db_specific_privs'] = false;
            $extraData['db_wildcard_privs'] = false;
            if (isset($dbnameIsWildcard)) {
                $extraData['db_specific_privs'] = ! $dbnameIsWildcard;
                $extraData['db_wildcard_privs'] = $dbnameIsWildcard;
            }

            $newPrivileges = implode(', ', $this->extractPrivInfo(null, true));

            $extraData['new_privileges'] = $newPrivileges;
        }

        if (isset($_GET['validate_username'])) {
            $sqlQuery = "SELECT * FROM `mysql`.`user` WHERE `User` = '"
                . $this->dbi->escapeString($_GET['username']) . "';";
            $res = $this->dbi->query($sqlQuery);
            $extraData['user_exists'] = $res->fetchRow() !== [];
        }

        return $extraData;
    }

    /**
     * no db name given, so we want all privs for the given user
     * db name was given, so we want all user specific rights for this db
     * So this function returns user rights as an array
     *
     * @param string $username username
     * @param string $hostname host name
     * @param string $type     database or table
     * @param string $dbname   database name
     *
     * @return array database rights
     */
    public function getUserSpecificRights($username, $hostname, $type, $dbname = '')
    {
        $userHostCondition = ' WHERE `User`'
            . " = '" . $this->dbi->escapeString($username) . "'"
            . ' AND `Host`'
            . " = '" . $this->dbi->escapeString($hostname) . "'";

        if ($type === 'database') {
            $tablesToSearchForUsers = [
                'tables_priv',
                'columns_priv',
                'procs_priv',
            ];
            $dbOrTableName = 'Db';
        } elseif ($type === 'table') {
            $userHostCondition .= " AND `Db` LIKE '"
                . $this->dbi->escapeString($dbname) . "'";
            $tablesToSearchForUsers = ['columns_priv'];
            $dbOrTableName = 'Table_name';
        } else { // routine
            $userHostCondition .= " AND `Db` LIKE '"
                . $this->dbi->escapeString($dbname) . "'";
            $tablesToSearchForUsers = ['procs_priv'];
            $dbOrTableName = 'Routine_name';
        }

        // we also want privileges for this user not in table `db` but in other table
        $tables = $this->dbi->fetchResult('SHOW TABLES FROM `mysql`;');

        $dbRightsSqls = [];
        foreach ($tablesToSearchForUsers as $tableSearchIn) {
            if (! in_array($tableSearchIn, $tables)) {
                continue;
            }

            $dbRightsSqls[] = '
                SELECT DISTINCT `' . $dbOrTableName . '`
                FROM `mysql`.' . Util::backquote($tableSearchIn)
               . $userHostCondition;
        }

        $userDefaults = [
            $dbOrTableName => '',
            'Grant_priv' => 'N',
            'privs' => ['USAGE'],
            'Column_priv' => true,
        ];

        // for the rights
        $dbRights = [];

        $dbRightsSql = '(' . implode(') UNION (', $dbRightsSqls) . ')'
            . ' ORDER BY `' . $dbOrTableName . '` ASC';

        $dbRightsResult = $this->dbi->query($dbRightsSql);

        while ($dbRightsRow = $dbRightsResult->fetchAssoc()) {
            $dbRightsRow = array_merge($userDefaults, $dbRightsRow);
            if ($type === 'database') {
                // only Db names in the table `mysql`.`db` uses wildcards
                // as we are in the db specific rights display we want
                // all db names escaped, also from other sources
                $dbRightsRow['Db'] = Util::escapeMysqlWildcards($dbRightsRow['Db']);
            }

            $dbRights[$dbRightsRow[$dbOrTableName]] = $dbRightsRow;
        }

        if ($type === 'database') {
            $sqlQuery = 'SELECT * FROM `mysql`.`db`'
                . $userHostCondition . ' ORDER BY `Db` ASC';
        } elseif ($type === 'table') {
            $sqlQuery = 'SELECT `Table_name`,'
                . ' `Table_priv`,'
                . ' IF(`Column_priv` = _latin1 \'\', 0, 1)'
                . ' AS \'Column_priv\''
                . ' FROM `mysql`.`tables_priv`'
                . $userHostCondition
                . ' ORDER BY `Table_name` ASC;';
        } else {
            $sqlQuery = 'SELECT `Routine_name`, `Proc_priv`'
                . ' FROM `mysql`.`procs_priv`'
                . $userHostCondition
                . ' ORDER BY `Routine_name`';
        }

        $result = $this->dbi->query($sqlQuery);

        while ($row = $result->fetchAssoc()) {
            if (isset($dbRights[$row[$dbOrTableName]])) {
                $dbRights[$row[$dbOrTableName]] = array_merge($dbRights[$row[$dbOrTableName]], $row);
            } else {
                $dbRights[$row[$dbOrTableName]] = $row;
            }

            if ($type !== 'database') {
                continue;
            }

            // there are db specific rights for this user
            // so we can drop this db rights
            $dbRights[$row['Db']]['can_delete'] = true;
        }

        return $dbRights;
    }

    /**
     * Parses Proc_priv data
     *
     * @param string $privs Proc_priv
     *
     * @return array
     */
    public function parseProcPriv($privs)
    {
        $result = [
            'Alter_routine_priv' => 'N',
            'Execute_priv' => 'N',
            'Grant_priv' => 'N',
        ];
        foreach (explode(',', (string) $privs) as $priv) {
            if ($priv === 'Alter Routine') {
                $result['Alter_routine_priv'] = 'Y';
            } else {
                $result[$priv . '_priv'] = 'Y';
            }
        }

        return $result;
    }

    /**
     * Get a HTML table for display user's table specific or database specific rights
     *
     * @param string $username username
     * @param string $hostname host name
     * @param string $type     database, table or routine
     * @param string $dbname   database name
     *
     * @return string
     */
    public function getHtmlForAllTableSpecificRights(
        $username,
        $hostname,
        $type,
        $dbname = ''
    ) {
        $uiData = [
            'database' => [
                'form_id' => 'database_specific_priv',
                'sub_menu_label' => __('Database'),
                'legend' => __('Database-specific privileges'),
                'type_label' => __('Database'),
            ],
            'table' => [
                'form_id' => 'table_specific_priv',
                'sub_menu_label' => __('Table'),
                'legend' => __('Table-specific privileges'),
                'type_label' => __('Table'),
            ],
            'routine' => [
                'form_id' => 'routine_specific_priv',
                'sub_menu_label' => __('Routine'),
                'legend' => __('Routine-specific privileges'),
                'type_label' => __('Routine'),
            ],
        ];

        /**
         * no db name given, so we want all privs for the given user
         * db name was given, so we want all user specific rights for this db
         */
        $dbRights = $this->getUserSpecificRights($username, $hostname, $type, $dbname);
        ksort($dbRights);

        $foundRows = [];
        $privileges = [];
        foreach ($dbRights as $row) {
            $onePrivilege = [];

            $paramTableName = '';
            $paramRoutineName = '';

            if ($type === 'database') {
                $name = $row['Db'];
                $onePrivilege['grant'] = $row['Grant_priv'] === 'Y';
                $onePrivilege['table_privs'] = ! empty($row['Table_priv'])
                    || ! empty($row['Column_priv']);
                $onePrivilege['privileges'] = implode(',', $this->extractPrivInfo($row, true));

                $paramDbName = $row['Db'];
            } elseif ($type === 'table') {
                $name = $row['Table_name'];
                $onePrivilege['grant'] = in_array(
                    'Grant',
                    explode(',', $row['Table_priv'])
                );
                $onePrivilege['column_privs'] = ! empty($row['Column_priv']);
                $onePrivilege['privileges'] = implode(',', $this->extractPrivInfo($row, true));

                $paramDbName = Util::escapeMysqlWildcards($dbname);
                $paramTableName = $row['Table_name'];
            } else { // routine
                $name = $row['Routine_name'];
                $onePrivilege['grant'] = in_array(
                    'Grant',
                    explode(',', $row['Proc_priv'])
                );

                $privs = $this->parseProcPriv($row['Proc_priv']);
                $onePrivilege['privileges'] = implode(
                    ',',
                    $this->extractPrivInfo($privs, true)
                );

                $paramDbName = Util::escapeMysqlWildcards($dbname);
                $paramRoutineName = $row['Routine_name'];
            }

            $foundRows[] = $name;
            $onePrivilege['name'] = $name;

            $onePrivilege['edit_link'] = '';
            if ($this->dbi->isGrantUser()) {
                $onePrivilege['edit_link'] = $this->getUserLink(
                    'edit',
                    $username,
                    $hostname,
                    $paramDbName,
                    $paramTableName,
                    $paramRoutineName
                );
            }

            $onePrivilege['revoke_link'] = '';
            if ($type !== 'database' || ! empty($row['can_delete'])) {
                $onePrivilege['revoke_link'] = $this->getUserLink(
                    'revoke',
                    $username,
                    $hostname,
                    $paramDbName,
                    $paramTableName,
                    $paramRoutineName
                );
            }

            $privileges[] = $onePrivilege;
        }

        $data = $uiData[$type];
        $data['privileges'] = $privileges;
        $data['username'] = $username;
        $data['hostname'] = $hostname;
        $data['database'] = $dbname;
        $data['type'] = $type;

        if ($type === 'database') {
            $predDbArray = $GLOBALS['dblist']->databases;
            $databasesToSkip = [
                'information_schema',
                'performance_schema',
            ];

            $databases = [];
            $escapedDatabases = [];
            if (! empty($predDbArray)) {
                foreach ($predDbArray as $currentDb) {
                    if (in_array($currentDb, $databasesToSkip)) {
                        continue;
                    }

                    $currentDbEscaped = Util::escapeMysqlWildcards($currentDb);
                    // cannot use array_diff() once, outside of the loop,
                    // because the list of databases has special characters
                    // already escaped in $foundRows,
                    // contrary to the output of SHOW DATABASES
                    if (in_array($currentDbEscaped, $foundRows)) {
                        continue;
                    }

                    $databases[] = $currentDb;
                    $escapedDatabases[] = $currentDbEscaped;
                }
            }

            $data['databases'] = $databases;
            $data['escaped_databases'] = $escapedDatabases;
        } elseif ($type === 'table') {
            $result = $this->dbi->tryQuery('SHOW TABLES FROM ' . Util::backquote($dbname));

            $tables = [];
            if ($result) {
                while ($row = $result->fetchRow()) {
                    if (in_array($row[0], $foundRows)) {
                        continue;
                    }

                    $tables[] = $row[0];
                }
            }

            $data['tables'] = $tables;
        } else { // routine
            $routineData = $this->dbi->getRoutines($dbname);

            $routines = [];
            foreach ($routineData as $routine) {
                if (in_array($routine['name'], $foundRows)) {
                    continue;
                }

                $routines[] = $routine['name'];
            }

            $data['routines'] = $routines;
        }

        return $this->template->render('server/privileges/privileges_summary', $data);
    }

    /**
     * Get HTML for display the users overview
     * (if less than 50 users, display them immediately)
     *
     * @param ResultInterface $result   ran sql query
     * @param array           $dbRights user's database rights array
     * @param string          $textDir  text directory
     *
     * @return string HTML snippet
     */
    public function getUsersOverview(ResultInterface $result, array $dbRights, $textDir)
    {
        $configurableMenusFeature = $this->relation->getRelationParameters()->configurableMenusFeature;

        while ($row = $result->fetchAssoc()) {
            $row['privs'] = $this->extractPrivInfo($row, true);
            $dbRights[$row['User']][$row['Host']] = $row;
        }

        unset($result);

        $userGroupCount = 0;
        if ($configurableMenusFeature !== null) {
            $sqlQuery = 'SELECT * FROM ' . Util::backquote($configurableMenusFeature->database)
                . '.' . Util::backquote($configurableMenusFeature->users);
            $result = $this->dbi->tryQueryAsControlUser($sqlQuery);
            $groupAssignment = [];
            if ($result) {
                while ($row = $result->fetchAssoc()) {
                    $groupAssignment[$row['username']] = $row['usergroup'];
                }
            }

            unset($result);

            $userGroupCount = $this->getUserGroupCount($configurableMenusFeature);
        }

        $hosts = [];
        $hasAccountLocking = Compatibility::hasAccountLocking($this->dbi->isMariaDB(), $this->dbi->getVersion());
        foreach ($dbRights as $user) {
            ksort($user);
            foreach ($user as $host) {
                $res = $this->getUserPrivileges((string) $host['User'], (string) $host['Host'], $hasAccountLocking);

                $hasPassword = false;
                if (
                    (isset($res['authentication_string'])
                    && ! empty($res['authentication_string']))
                    || (isset($res['Password'])
                    && ! empty($res['Password']))
                ) {
                    $hasPassword = true;
                }

                $hosts[] = [
                    'user' => $host['User'],
                    'host' => $host['Host'],
                    'has_password' => $hasPassword,
                    'has_select_priv' => isset($host['Select_priv']),
                    'privileges' => $host['privs'],
                    'group' => $groupAssignment[$host['User']] ?? '',
                    'has_grant' => $host['Grant_priv'] === 'Y',
                    'is_account_locked' => isset($res['account_locked']) && $res['account_locked'] === 'Y',
                ];
            }
        }

        return $this->template->render('server/privileges/users_overview', [
            'menus_work' => $configurableMenusFeature !== null,
            'user_group_count' => $userGroupCount,
            'text_dir' => $textDir,
            'initial' => $_GET['initial'] ?? '',
            'hosts' => $hosts,
            'is_grantuser' => $this->dbi->isGrantUser(),
            'is_createuser' => $this->dbi->isCreateUser(),
            'has_account_locking' => $hasAccountLocking,
        ]);
    }

    /**
     * Get HTML for Displays the initials
     *
     * @param array $arrayInitials array for all initials, even non A-Z
     *
     * @return string HTML snippet
     */
    public function getHtmlForInitials(array $arrayInitials)
    {
        // initialize to false the letters A-Z
        for ($letterCounter = 1; $letterCounter < 27; $letterCounter++) {
            if (isset($arrayInitials[mb_chr($letterCounter + 64)])) {
                continue;
            }

            $arrayInitials[mb_chr($letterCounter + 64)] = false;
        }

        $initials = $this->dbi->tryQuery(
            'SELECT DISTINCT UPPER(LEFT(`User`,1)) FROM `user` ORDER BY UPPER(LEFT(`User`,1)) ASC'
        );
        if ($initials) {
            while ($tmpInitial = $initials->fetchRow()) {
                $arrayInitials[$tmpInitial[0]] = true;
            }
        }

        // Display the initials, which can be any characters, not
        // just letters. For letters A-Z, we add the non-used letters
        // as greyed out.

        uksort($arrayInitials, 'strnatcasecmp');

        return $this->template->render('server/privileges/initials_row', [
            'array_initials' => $arrayInitials,
            'initial' => $_GET['initial'] ?? null,
            'viewing_mode' => $_GET['viewing_mode'] ?? null,
        ]);
    }

    /**
     * Get the database rights array for Display user overview
     *
     * @return array    database rights array
     */
    public function getDbRightsForUserOverview()
    {
        // we also want users not in table `user` but in other table
        $tables = $this->dbi->fetchResult('SHOW TABLES FROM `mysql`;');

        $tablesSearchForUsers = [
            'user',
            'db',
            'tables_priv',
            'columns_priv',
            'procs_priv',
        ];

        $dbRightsSqls = [];
        foreach ($tablesSearchForUsers as $tableSearchIn) {
            if (! in_array($tableSearchIn, $tables)) {
                continue;
            }

            $dbRightsSqls[] = 'SELECT DISTINCT `User`, `Host` FROM `mysql`.`'
                . $tableSearchIn . '` '
                . (isset($_GET['initial'])
                ? $this->rangeOfUsers($_GET['initial'])
                : '');
        }

        $userDefaults = [
            'User' => '',
            'Host' => '%',
            'Password' => '?',
            'Grant_priv' => 'N',
            'privs' => ['USAGE'],
        ];

        // for the rights
        $dbRights = [];

        $dbRightsSql = '(' . implode(') UNION (', $dbRightsSqls) . ')'
            . ' ORDER BY `User` ASC, `Host` ASC';

        $dbRightsResult = $this->dbi->query($dbRightsSql);

        while ($dbRightsRow = $dbRightsResult->fetchAssoc()) {
            $dbRightsRow = array_merge($userDefaults, $dbRightsRow);
            $dbRights[$dbRightsRow['User']][$dbRightsRow['Host']] = $dbRightsRow;
        }

        ksort($dbRights);

        return $dbRights;
    }

    /**
     * Delete user and get message and sql query for delete user in privileges
     *
     * @param array $queries queries
     *
     * @return array Message
     */
    public function deleteUser(array $queries)
    {
        $sqlQuery = '';
        if (empty($queries)) {
            $message = Message::error(__('No users selected for deleting!'));
        } else {
            if ($_POST['mode'] == 3) {
                $queries[] = '# ' . __('Reloading the privileges') . ' …';
                $queries[] = 'FLUSH PRIVILEGES;';
            }

            $dropUserError = '';
            foreach ($queries as $sqlQuery) {
                if ($sqlQuery[0] === '#') {
                    continue;
                }

                if ($this->dbi->tryQuery($sqlQuery)) {
                    continue;
                }

                $dropUserError .= $this->dbi->getError() . "\n";
            }

            // tracking sets this, causing the deleted db to be shown in navi
            unset($GLOBALS['db']);

            $sqlQuery = implode("\n", $queries);
            if (! empty($dropUserError)) {
                $message = Message::rawError($dropUserError);
            } else {
                $message = Message::success(
                    __('The selected users have been deleted successfully.')
                );
            }
        }

        return [
            $sqlQuery,
            $message,
        ];
    }

    /**
     * Update the privileges and return the success or error message
     *
     * @return array success message or error message for update
     */
    public function updatePrivileges(
        string $username,
        string $hostname,
        string $tablename,
        string $dbname,
        string $itemType
    ): array {
        $dbAndTable = $this->wildcardEscapeForGrant($dbname, $tablename);

        $sqlQuery0 = 'REVOKE ALL PRIVILEGES ON ' . $itemType . ' ' . $dbAndTable
            . ' FROM \'' . $this->dbi->escapeString($username)
            . '\'@\'' . $this->dbi->escapeString($hostname) . '\';';

        if (! isset($_POST['Grant_priv']) || $_POST['Grant_priv'] !== 'Y') {
            $sqlQuery1 = 'REVOKE GRANT OPTION ON ' . $itemType . ' ' . $dbAndTable
                . ' FROM \'' . $this->dbi->escapeString($username) . '\'@\''
                . $this->dbi->escapeString($hostname) . '\';';
        } else {
            $sqlQuery1 = '';
        }

        $grantBackQuery = null;
        $alterUserQuery = null;

        // Should not do a GRANT USAGE for a table-specific privilege, it
        // causes problems later (cannot revoke it)
        if (! (strlen($tablename) > 0 && implode('', $this->extractPrivInfo()) === 'USAGE')) {
            [$grantBackQuery, $alterUserQuery] = $this->generateQueriesForUpdatePrivileges(
                $itemType,
                $dbAndTable,
                $username,
                $hostname,
                $dbname
            );
        }

        if (! $this->dbi->tryQuery($sqlQuery0)) {
            // This might fail when the executing user does not have
            // ALL PRIVILEGES themselves.
            // See https://github.com/phpmyadmin/phpmyadmin/issues/9673
            $sqlQuery0 = '';
        }

        if (! empty($sqlQuery1) && ! $this->dbi->tryQuery($sqlQuery1)) {
            // this one may fail, too...
            $sqlQuery1 = '';
        }

        if ($grantBackQuery !== null) {
            $this->dbi->query($grantBackQuery);
        } else {
            $grantBackQuery = '';
        }

        if ($alterUserQuery !== null) {
            $this->dbi->query($alterUserQuery);
        } else {
            $alterUserQuery = '';
        }

        $sqlQuery = $sqlQuery0 . ' ' . $sqlQuery1 . ' ' . $grantBackQuery . ' ' . $alterUserQuery;
        $message = Message::success(__('You have updated the privileges for %s.'));
        $message->addParam('\'' . $username . '\'@\'' . $hostname . '\'');

        return [
            $sqlQuery,
            $message,
        ];
    }

    /**
     * Generate the query for the GRANTS and requirements + limits
     *
     * @return array<int,string|null>
     */
    private function generateQueriesForUpdatePrivileges(
        string $itemType,
        string $dbAndTable,
        string $username,
        string $hostname,
        string $dbname
    ): array {
        $alterUserQuery = null;

        $grantBackQuery = 'GRANT ' . implode(', ', $this->extractPrivInfo())
            . ' ON ' . $itemType . ' ' . $dbAndTable
            . ' TO \'' . $this->dbi->escapeString($username) . '\'@\''
            . $this->dbi->escapeString($hostname) . '\'';

        $isMySqlOrPercona = Compatibility::isMySqlOrPerconaDb();
        $needsToUseAlter = $isMySqlOrPercona && $this->dbi->getVersion() >= 80011;

        if ($needsToUseAlter) {
            $alterUserQuery = 'ALTER USER \'' . $this->dbi->escapeString($username) . '\'@\''
            . $this->dbi->escapeString($hostname) . '\' ';
        }

        if (strlen($dbname) === 0) {
            // add REQUIRE clause
            if ($needsToUseAlter) {
                $alterUserQuery .= $this->getRequireClause();
            } else {
                $grantBackQuery .= $this->getRequireClause();
            }
        }

        if (
            (isset($_POST['Grant_priv']) && $_POST['Grant_priv'] === 'Y')
            || (strlen($dbname) === 0
            && (isset($_POST['max_questions']) || isset($_POST['max_connections'])
            || isset($_POST['max_updates'])
            || isset($_POST['max_user_connections'])))
        ) {
            if ($needsToUseAlter) {
                $alterUserQuery .= $this->getWithClauseForAddUserAndUpdatePrivs();
            } else {
                $grantBackQuery .= $this->getWithClauseForAddUserAndUpdatePrivs();
            }
        }

        $grantBackQuery .= ';';

        if ($needsToUseAlter) {
            $alterUserQuery .= ';';
        }

        return [$grantBackQuery, $alterUserQuery];
    }

    /**
     * Get List of information: Changes / copies a user
     *
     * @return array
     */
    public function getDataForChangeOrCopyUser()
    {
        $queries = null;
        $password = null;

        if (isset($_POST['change_copy'])) {
            $userHostCondition = ' WHERE `User` = '
                . "'" . $this->dbi->escapeString($_POST['old_username']) . "'"
                . ' AND `Host` = '
                . "'" . $this->dbi->escapeString($_POST['old_hostname']) . "';";
            $row = $this->dbi->fetchSingleRow('SELECT * FROM `mysql`.`user` ' . $userHostCondition);
            if (! $row) {
                $response = ResponseRenderer::getInstance();
                $response->addHTML(
                    Message::notice(__('No user found.'))->getDisplay()
                );
                unset($_POST['change_copy']);
            } else {
                foreach ($row as $key => $value) {
                    $GLOBALS[$key] = $value;
                }

                $serverVersion = $this->dbi->getVersion();
                // Recent MySQL versions have the field "Password" in mysql.user,
                // so the previous extract creates $row['Password'] but this script
                // uses $password
                if (! isset($row['password']) && isset($row['Password'])) {
                    $row['password'] = $row['Password'];
                }

                if (
                    Compatibility::isMySqlOrPerconaDb()
                    && $serverVersion >= 50606
                    && $serverVersion < 50706
                    && ((isset($row['authentication_string'])
                    && empty($row['password']))
                    || (isset($row['plugin'])
                    && $row['plugin'] === 'sha256_password'))
                ) {
                    $row['password'] = $row['authentication_string'];
                }

                if (
                    Compatibility::isMariaDb()
                    && $serverVersion >= 50500
                    && isset($row['authentication_string'])
                    && empty($row['password'])
                ) {
                    $row['password'] = $row['authentication_string'];
                }

                // Always use 'authentication_string' column
                // for MySQL 5.7.6+ since it does not have
                // the 'password' column at all
                if (
                    Compatibility::isMySqlOrPerconaDb()
                    && $serverVersion >= 50706
                    && isset($row['authentication_string'])
                ) {
                    $row['password'] = $row['authentication_string'];
                }

                $password = $row['password'];
                $queries = [];
            }
        }

        return [
            $queries,
            $password,
        ];
    }

    /**
     * Update Data for information: Deletes users
     *
     * @param array $queries queries array
     *
     * @return array
     */
    public function getDataForDeleteUsers($queries)
    {
        if (isset($_POST['change_copy'])) {
            $selectedUsr = [
                $_POST['old_username'] . '&amp;#27;' . $_POST['old_hostname'],
            ];
        } else {
            // null happens when no user was selected
            $selectedUsr = $_POST['selected_usr'] ?? null;
            $queries = [];
        }

        // this happens, was seen in https://reports.phpmyadmin.net/reports/view/17146
        if (! is_array($selectedUsr)) {
            return [];
        }

        foreach ($selectedUsr as $eachUser) {
            [$thisUser, $thisHost] = explode('&amp;#27;', $eachUser);
            $queries[] = '# '
                . sprintf(
                    __('Deleting %s'),
                    '\'' . $thisUser . '\'@\'' . $thisHost . '\''
                )
                . ' ...';
            $queries[] = 'DROP USER \''
                . $this->dbi->escapeString($thisUser)
                . '\'@\'' . $this->dbi->escapeString($thisHost) . '\';';
            $this->relationCleanup->user($thisUser);

            if (! isset($_POST['drop_users_db'])) {
                continue;
            }

            $queries[] = 'DROP DATABASE IF EXISTS '
                . Util::backquote($thisUser) . ';';
            $GLOBALS['reload'] = true;
        }

        return $queries;
    }

    /**
     * update Message For Reload
     */
    public function updateMessageForReload(): ?Message
    {
        $message = null;
        if (isset($_GET['flush_privileges'])) {
            $sqlQuery = 'FLUSH PRIVILEGES;';
            $this->dbi->query($sqlQuery);
            $message = Message::success(
                __('The privileges were reloaded successfully.')
            );
        }

        if (isset($_GET['validate_username'])) {
            $message = Message::success();
        }

        return $message;
    }

    /**
     * update Data For Queries from queries_for_display
     *
     * @param array      $queries           queries array
     * @param array|null $queriesForDisplay queries array for display
     *
     * @return array
     */
    public function getDataForQueries(array $queries, $queriesForDisplay)
    {
        $tmpCount = 0;
        foreach ($queries as $sqlQuery) {
            if ($sqlQuery[0] !== '#') {
                $this->dbi->query($sqlQuery);
            }

            // when there is a query containing a hidden password, take it
            // instead of the real query sent
            if (isset($queriesForDisplay[$tmpCount])) {
                $queries[$tmpCount] = $queriesForDisplay[$tmpCount];
            }

            $tmpCount++;
        }

        return $queries;
    }

    /**
     * update Data for information: Adds a user
     *
     * @param string|array|null $dbname     db name
     * @param string            $username   user name
     * @param string            $hostname   host name
     * @param string|null       $password   password
     * @param bool              $isMenuwork is_menuwork set?
     *
     * @return array
     */
    public function addUser(
        $dbname,
        string $username,
        string $hostname,
        ?string $password,
        $isMenuwork
    ) {
        $message = null;
        $queries = null;
        $queriesForDisplay = null;
        $sqlQuery = null;

        if (! isset($_POST['adduser_submit']) && ! isset($_POST['change_copy'])) {
            return [
                $message,
                $queries,
                $queriesForDisplay,
                $sqlQuery,
                false, // Add user error
            ];
        }

        $sqlQuery = '';
        // Some reports where sent to the error reporting server with phpMyAdmin 5.1.0
        // pred_username was reported to be not defined
        $predUsername = $_POST['pred_username'] ?? '';
        if ($predUsername === 'any') {
            $username = '';
        }

        switch ($_POST['pred_hostname']) {
            case 'any':
                $hostname = '%';
                break;
            case 'localhost':
                $hostname = 'localhost';
                break;
            case 'hosttable':
                $hostname = '';
                break;
            case 'thishost':
                $currentUserName = $this->dbi->fetchValue('SELECT USER()');
                if (is_string($currentUserName)) {
                    $hostname = mb_substr($currentUserName, mb_strrpos($currentUserName, '@') + 1);
                    unset($currentUserName);
                }

                break;
        }

        $sql = "SELECT '1' FROM `mysql`.`user`"
            . " WHERE `User` = '" . $this->dbi->escapeString($username) . "'"
            . " AND `Host` = '" . $this->dbi->escapeString($hostname) . "';";
        if ($this->dbi->fetchValue($sql) == 1) {
            $message = Message::error(__('The user %s already exists!'));
            $message->addParam('[em]\'' . $username . '\'@\'' . $hostname . '\'[/em]');
            $_GET['adduser'] = true;

            return [
                $message,
                $queries,
                $queriesForDisplay,
                $sqlQuery,
                true, // Add user error
            ];
        }

        [
            $createUserReal,
            $createUserShow,
            $realSqlQuery,
            $sqlQuery,
            $passwordSetReal,
            $passwordSetShow,
            $alterRealSqlQuery,
            $alterSqlQuery,
        ] = $this->getSqlQueriesForDisplayAndAddUser($username, $hostname, ($password ?? ''));

        if (empty($_POST['change_copy'])) {
            $error = false;

            if ($createUserReal !== null) {
                if (! $this->dbi->tryQuery($createUserReal)) {
                    $error = true;
                }

                if (isset($passwordSetReal, $_POST['authentication_plugin']) && ! empty($passwordSetReal)) {
                    $this->setProperPasswordHashing($_POST['authentication_plugin']);
                    if ($this->dbi->tryQuery($passwordSetReal)) {
                        $sqlQuery .= $passwordSetShow;
                    }
                }

                $sqlQuery = $createUserShow . $sqlQuery;
            }

            [$sqlQuery, $message] = $this->addUserAndCreateDatabase(
                $error,
                $realSqlQuery,
                $sqlQuery,
                $username,
                $hostname,
                $dbname,
                $alterRealSqlQuery,
                $alterSqlQuery,
                isset($_POST['createdb-1']),
                isset($_POST['createdb-2']),
                isset($_POST['createdb-3'])
            );
            if (! empty($_POST['userGroup']) && $isMenuwork) {
                $this->setUserGroup($GLOBALS['username'], $_POST['userGroup']);
            }

            return [
                $message,
                $queries,
                $queriesForDisplay,
                $sqlQuery,
                $error, // Add user error if the query fails
            ];
        }

        // Copy the user group while copying a user
        $oldUserGroup = $_POST['old_usergroup'] ?? null;
        $this->setUserGroup($_POST['username'], $oldUserGroup);

        if ($createUserReal !== null) {
            $queries[] = $createUserReal;
        }

        $queries[] = $realSqlQuery;

        if (isset($passwordSetReal, $_POST['authentication_plugin']) && ! empty($passwordSetReal)) {
            $this->setProperPasswordHashing($_POST['authentication_plugin']);

            $queries[] = $passwordSetReal;
        }

        // we put the query containing the hidden password in
        // $queries_for_display, at the same position occupied
        // by the real query in $queries
        $tmpCount = count($queries);
        if (isset($createUserReal)) {
            $queriesForDisplay[$tmpCount - 2] = $createUserShow;
        }

        if (isset($passwordSetReal) && ! empty($passwordSetReal)) {
            $queriesForDisplay[$tmpCount - 3] = $createUserShow;
            $queriesForDisplay[$tmpCount - 2] = $sqlQuery;
            $queriesForDisplay[$tmpCount - 1] = $passwordSetShow;
        } else {
            $queriesForDisplay[$tmpCount - 1] = $sqlQuery;
        }

        return [
            $message,
            $queries,
            $queriesForDisplay,
            $sqlQuery,
            false, // Add user error
        ];
    }

    /**
     * Sets proper value of `old_passwords` according to
     * the authentication plugin selected
     *
     * @param string $authPlugin authentication plugin selected
     */
    public function setProperPasswordHashing($authPlugin): void
    {
        // Set the hashing method used by PASSWORD()
        // to be of type depending upon $authentication_plugin
        if ($authPlugin === 'sha256_password') {
            $this->dbi->tryQuery('SET `old_passwords` = 2;');
        } elseif ($authPlugin === 'mysql_old_password') {
            $this->dbi->tryQuery('SET `old_passwords` = 1;');
        } else {
            $this->dbi->tryQuery('SET `old_passwords` = 0;');
        }
    }

    /**
     * Update DB information: DB, Table, isWildcard
     *
     * @return array
     * @psalm-return array{?string, ?string, array|string|null, ?string, ?string, array|string, bool}
     */
    public function getDataForDBInfo()
    {
        $username = null;
        $hostname = null;
        $dbname = null;
        $tablename = null;
        $routinename = null;

        if (isset($_REQUEST['username'])) {
            $username = (string) $_REQUEST['username'];
        }

        if (isset($_REQUEST['hostname'])) {
            $hostname = (string) $_REQUEST['hostname'];
        }

        /**
         * Checks if a dropdown box has been used for selecting a database / table
         */
        if (
            isset($_POST['pred_tablename'])
            && is_string($_POST['pred_tablename'])
            && $_POST['pred_tablename'] !== ''
        ) {
            $tablename = $_POST['pred_tablename'];
        } elseif (
            isset($_REQUEST['tablename'])
            && is_string($_REQUEST['tablename'])
            && $_REQUEST['tablename'] !== ''
        ) {
            $tablename = $_REQUEST['tablename'];
        }

        if (
            isset($_POST['pred_routinename'])
            && is_string($_POST['pred_routinename'])
            && $_POST['pred_routinename'] !== ''
        ) {
            $routinename = $_POST['pred_routinename'];
        } elseif (
            isset($_REQUEST['routinename'])
            && is_string($_REQUEST['routinename'])
            && $_REQUEST['routinename'] !== ''
        ) {
            $routinename = $_REQUEST['routinename'];
        }

        if (isset($_POST['pred_dbname']) && is_array($_POST['pred_dbname'])) {
            // Accept only array of non-empty strings
            if ($_POST['pred_dbname'] === array_filter($_POST['pred_dbname'])) {
                $dbname = $_POST['pred_dbname'];
                // If dbname contains only one database.
                if (count($dbname) === 1) {
                    $dbname = (string) $dbname[0];
                }
            }
        }

        if ($dbname === null && isset($_REQUEST['dbname'])) {
            if (is_array($_REQUEST['dbname'])) {
                // Accept only array of non-empty strings
                if ($_REQUEST['dbname'] === array_filter($_REQUEST['dbname'])) {
                    $dbname = $_REQUEST['dbname'];
                }
            } elseif (
                is_string($_REQUEST['dbname'])
                && $_REQUEST['dbname'] !== ''
            ) {
                $dbname = $_REQUEST['dbname'];
            }
        }

        $dbAndTable = '*.*';
        if ($dbname === null) {
            $tablename = null;
        } else {
            if (is_array($dbname)) {
                $dbAndTable = $dbname;
                foreach (array_keys($dbAndTable) as $key) {
                    $dbAndTable[$key] .= '.*';
                }
            } else {
                $unescapedDb = Util::unescapeMysqlWildcards($dbname);
                $dbAndTable = Util::backquote($unescapedDb) . '.';

                if ($tablename !== null) {
                    $dbAndTable .= Util::backquote($tablename);
                } else {
                    $dbAndTable .= '*';
                }
            }
        }

        // check if given $dbname is a wildcard or not
        $databaseNameIsWildcard = is_string($dbname) && preg_match('/(?<!\\\\)(?:_|%)/', $dbname);

        return [
            $username,
            $hostname,
            $dbname,
            $tablename,
            $routinename,
            $dbAndTable,
            $databaseNameIsWildcard,
        ];
    }

    /**
     * Get title and textarea for export user definition in Privileges
     *
     * @param string $username username
     * @param string $hostname host name
     *
     * @return array ($title, $export)
     */
    public function getListForExportUserDefinition(string $username, string $hostname)
    {
        $export = '<textarea class="export" cols="60" rows="15">';

        /** @var array|null $selectedUsers */
        $selectedUsers = $_POST['selected_usr'] ?? null;

        if (isset($selectedUsers)) {
            // export privileges for selected users
            $title = __('Privileges');

            //For removing duplicate entries of users
            $selectedUsers = array_unique($selectedUsers);

            foreach ($selectedUsers as $exportUser) {
                $exportUsername = mb_substr(
                    $exportUser,
                    0,
                    (int) mb_strpos($exportUser, '&')
                );
                $exportHostname = mb_substr(
                    $exportUser,
                    mb_strrpos($exportUser, ';') + 1
                );
                $export .= '# '
                    . sprintf(
                        __('Privileges for %s'),
                        '`' . htmlspecialchars($exportUsername)
                        . '`@`' . htmlspecialchars($exportHostname) . '`'
                    )
                    . "\n\n";
                $export .= $this->getGrants($exportUsername, $exportHostname) . "\n";
            }
        } else {
            // export privileges for a single user
            $title = __('User') . ' `' . htmlspecialchars($username)
                . '`@`' . htmlspecialchars($hostname) . '`';
            $export .= $this->getGrants($username, $hostname);
        }

        // remove trailing whitespace
        $export = trim($export);

        $export .= '</textarea>';

        return [
            $title,
            $export,
        ];
    }

    /**
     * Get HTML for display Add userfieldset
     *
     * @param string $db    the database
     * @param string $table the table name
     *
     * @return string html output
     */
    public function getAddUserHtmlFieldset($db = '', $table = '')
    {
        if (! $this->dbi->isCreateUser()) {
            return '';
        }

        $relParams = [];
        $urlParams = ['adduser' => 1];
        if (! empty($db)) {
            $urlParams['dbname'] = $relParams['checkprivsdb'] = $db;
        }

        if (! empty($table)) {
            $urlParams['tablename'] = $relParams['checkprivstable'] = $table;
        }

        return $this->template->render('server/privileges/add_user_fieldset', [
            'url_params' => $urlParams,
            'rel_params' => $relParams,
        ]);
    }

    /**
     * Get HTML snippet for display user overview page
     *
     * @param string $textDir text directory
     *
     * @return string
     */
    public function getHtmlForUserOverview($textDir)
    {
        $passwordColumn = 'Password';
        $serverVersion = $this->dbi->getVersion();
        if (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50706) {
            $passwordColumn = 'authentication_string';
        }

        // $sql_query is for the initial-filtered,
        // $sql_query_all is for counting the total no. of users

        $sqlQuery = $sqlQueryAll = 'SELECT *,' .
            ' IF(`' . $passwordColumn . "` = _latin1 '', 'N', 'Y') AS 'Password'" .
            ' FROM `mysql`.`user`';

        $sqlQuery .= (isset($_GET['initial'])
            ? $this->rangeOfUsers($_GET['initial'])
            : '');

        $sqlQuery .= ' ORDER BY `User` ASC, `Host` ASC;';
        $sqlQueryAll .= ' ;';

        $res = $this->dbi->tryQuery($sqlQuery);
        $resAll = $this->dbi->tryQuery($sqlQueryAll);

        $errorMessages = '';
        if (! $res) {
            // the query failed! This may have two reasons:
            // - the user does not have enough privileges
            // - the privilege tables use a structure of an earlier version.
            // so let's try a more simple query

            unset($resAll);
            $sqlQuery = 'SELECT * FROM `mysql`.`user`';
            $res = $this->dbi->tryQuery($sqlQuery);

            if (! $res) {
                $errorMessages .= $this->getHtmlForViewUsersError();
                $errorMessages .= $this->getAddUserHtmlFieldset();
            } else {
                // This message is hardcoded because I will replace it by
                // a automatic repair feature soon.
                $raw = 'Your privilege table structure seems to be older than'
                    . ' this MySQL version!<br>'
                    . 'Please run the <code>mysql_upgrade</code> command'
                    . ' that should be included in your MySQL server distribution'
                    . ' to solve this problem!';
                $errorMessages .= Message::rawError($raw)->getDisplay();
            }

            unset($res);
        } else {
            $dbRights = $this->getDbRightsForUserOverview();
            // for all initials, even non A-Z
            $arrayInitials = [];

            foreach ($dbRights as $right) {
                foreach ($right as $account) {
                    if (empty($account['User']) && $account['Host'] === 'localhost') {
                        $emptyUserNotice = Message::notice(
                            __(
                                'A user account allowing any user from localhost to '
                                . 'connect is present. This will prevent other users '
                                . 'from connecting if the host part of their account '
                                . 'allows a connection from any (%) host.'
                            )
                            . MySQLDocumentation::show('problems-connecting')
                        )->getDisplay();
                        break 2;
                    }
                }
            }

            /**
             * Displays the initials
             * Also not necessary if there is less than 20 privileges
             */
            if ($resAll && $resAll->numRows() > 20) {
                $initials = $this->getHtmlForInitials($arrayInitials);
            }

            /**
            * Display the user overview
            * (if less than 50 users, display them immediately)
            */
            if (isset($_GET['initial']) || isset($_GET['showall']) || $res->numRows() < 50) {
                $usersOverview = $this->getUsersOverview($res, $dbRights, $textDir);
                $usersOverview .= $this->template->render('export_modal');
            }

            $response = ResponseRenderer::getInstance();
            if (! $response->isAjax() || ! empty($_REQUEST['ajax_page_request'])) {
                if ($GLOBALS['is_reload_priv']) {
                    $flushnote = new Message(
                        __(
                            'Note: phpMyAdmin gets the users’ privileges directly '
                            . 'from MySQL’s privilege tables. The content of these '
                            . 'tables may differ from the privileges the server uses, '
                            . 'if they have been changed manually. In this case, '
                            . 'you should %sreload the privileges%s before you continue.'
                        ),
                        Message::NOTICE
                    );
                    $flushnote->addParamHtml(
                        '<a href="' . Url::getFromRoute('/server/privileges', ['flush_privileges' => 1])
                        . '" id="reload_privileges_anchor">'
                    );
                    $flushnote->addParamHtml('</a>');
                } else {
                    $flushnote = new Message(
                        __(
                            'Note: phpMyAdmin gets the users’ privileges directly '
                            . 'from MySQL’s privilege tables. The content of these '
                            . 'tables may differ from the privileges the server uses, '
                            . 'if they have been changed manually. In this case, '
                            . 'the privileges have to be reloaded but currently, you '
                            . 'don\'t have the RELOAD privilege.'
                        )
                        . MySQLDocumentation::show(
                            'privileges-provided',
                            false,
                            null,
                            null,
                            'priv_reload'
                        ),
                        Message::NOTICE
                    );
                }

                $flushNotice = $flushnote->getDisplay();
            }
        }

        return $this->template->render('server/privileges/user_overview', [
            'error_messages' => $errorMessages,
            'empty_user_notice' => $emptyUserNotice ?? '',
            'initials' => $initials ?? '',
            'users_overview' => $usersOverview ?? '',
            'is_createuser' => $this->dbi->isCreateUser(),
            'flush_notice' => $flushNotice ?? '',
        ]);
    }

    /**
     * Get HTML snippet for display user properties
     *
     * @param bool         $dbnameIsWildcard whether database name is wildcard or not
     * @param string       $urlDbname        url database name that urlencode() string
     * @param string       $username         username
     * @param string       $hostname         host name
     * @param string|array $dbname           database name
     * @param string       $tablename        table name
     *
     * @return string
     */
    public function getHtmlForUserProperties(
        $dbnameIsWildcard,
        $urlDbname,
        $username,
        $hostname,
        $dbname,
        $tablename
    ) {
        global $cfg;

        $sql = "SELECT '1' FROM `mysql`.`user`"
            . " WHERE `User` = '" . $this->dbi->escapeString($username) . "'"
            . " AND `Host` = '" . $this->dbi->escapeString($hostname) . "';";

        $userDoesNotExists = ! $this->dbi->fetchValue($sql);

        $loginInformationFields = '';
        if ($userDoesNotExists) {
            $loginInformationFields = $this->getHtmlForLoginInformationFields();
        }

        $params = [
            'username' => $username,
            'hostname' => $hostname,
        ];
        if (! is_array($dbname) && strlen($dbname) > 0) {
            $params['dbname'] = $dbname;
            if (strlen($tablename) > 0) {
                $params['tablename'] = $tablename;
            }
        } else {
            $params['dbname'] = $dbname;
        }

        $privilegesTable = $this->getHtmlToDisplayPrivilegesTable(
            // If $dbname is an array, pass any one db as all have same privs.
            is_string($dbname) && strlen($dbname) > 0
                ? $dbname
                : (is_array($dbname) ? (string) $dbname[0] : '*'),
            strlen($tablename) > 0
                ? $tablename
                : '*'
        );

        $tableSpecificRights = '';
        if (! is_array($dbname) && strlen($tablename) === 0 && empty($dbnameIsWildcard)) {
            // no table name was given, display all table specific rights
            // but only if $dbname contains no wildcards
            if (strlen($dbname) === 0) {
                $tableSpecificRights .= $this->getHtmlForAllTableSpecificRights($username, $hostname, 'database');
            } else {
                // unescape wildcards in dbname at table level
                $unescapedDb = Util::unescapeMysqlWildcards($dbname);

                $tableSpecificRights .= $this->getHtmlForAllTableSpecificRights(
                    $username,
                    $hostname,
                    'table',
                    $unescapedDb
                );
                $tableSpecificRights .= $this->getHtmlForAllTableSpecificRights(
                    $username,
                    $hostname,
                    'routine',
                    $unescapedDb
                );
            }
        }

        $databaseUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $databaseUrlTitle = Util::getTitleForTarget($cfg['DefaultTabDatabase']);
        $tableUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $tableUrlTitle = Util::getTitleForTarget($cfg['DefaultTabTable']);

        $changePassword = '';
        $userGroup = '';
        $changeLoginInfoFields = '';
        if (! is_array($dbname) && strlen($dbname) === 0 && ! $userDoesNotExists) {
            //change login information
            $changePassword = $this->getFormForChangePassword($username, $hostname, true);
            $userGroup = $this->getUserGroupForUser($username);
            $changeLoginInfoFields = $this->getHtmlForLoginInformationFields('change', $username, $hostname);
        }

        return $this->template->render('server/privileges/user_properties', [
            'user_does_not_exists' => $userDoesNotExists,
            'login_information_fields' => $loginInformationFields,
            'params' => $params,
            'privileges_table' => $privilegesTable,
            'table_specific_rights' => $tableSpecificRights,
            'change_password' => $changePassword,
            'database' => $dbname,
            'dbname' => $urlDbname,
            'username' => $username,
            'hostname' => $hostname,
            'is_databases' => $dbnameIsWildcard || is_array($dbname) && count($dbname) > 1,
            'is_wildcard' => $dbnameIsWildcard,
            'table' => $tablename,
            'current_user' => $this->dbi->getCurrentUser(),
            'user_group' => $userGroup,
            'change_login_info_fields' => $changeLoginInfoFields,
            'database_url' => $databaseUrl,
            'database_url_title' => $databaseUrlTitle,
            'table_url' => $tableUrl,
            'table_url_title' => $tableUrlTitle,
        ]);
    }

    /**
     * Get queries for Table privileges to change or copy user
     *
     * @param string $userHostCondition user host condition to
     *                                    select relevant table privileges
     * @param array  $queries           queries array
     * @param string $username          username
     * @param string $hostname          host name
     *
     * @return array
     */
    public function getTablePrivsQueriesForChangeOrCopyUser(
        $userHostCondition,
        array $queries,
        $username,
        $hostname
    ) {
        $res = $this->dbi->query(
            'SELECT `Db`, `Table_name`, `Table_priv` FROM `mysql`.`tables_priv`'
            . $userHostCondition
        );
        while ($row = $res->fetchAssoc()) {
            $res2 = $this->dbi->query(
                'SELECT `Column_name`, `Column_priv`'
                . ' FROM `mysql`.`columns_priv`'
                . ' WHERE `User`'
                . ' = \'' . $this->dbi->escapeString($_POST['old_username']) . "'"
                . ' AND `Host`'
                . ' = \'' . $this->dbi->escapeString($_POST['old_username']) . '\''
                . ' AND `Db`'
                . ' = \'' . $this->dbi->escapeString($row['Db']) . "'"
                . ' AND `Table_name`'
                . ' = \'' . $this->dbi->escapeString($row['Table_name']) . "'"
                . ';'
            );

            $tmpPrivs1 = $this->extractPrivInfo($row);
            $tmpPrivs2 = [
                'Select' => [],
                'Insert' => [],
                'Update' => [],
                'References' => [],
            ];

            while ($row2 = $res2->fetchAssoc()) {
                $tmpArray = explode(',', $row2['Column_priv']);
                if (in_array('Select', $tmpArray)) {
                    $tmpPrivs2['Select'][] = $row2['Column_name'];
                }

                if (in_array('Insert', $tmpArray)) {
                    $tmpPrivs2['Insert'][] = $row2['Column_name'];
                }

                if (in_array('Update', $tmpArray)) {
                    $tmpPrivs2['Update'][] = $row2['Column_name'];
                }

                if (! in_array('References', $tmpArray)) {
                    continue;
                }

                $tmpPrivs2['References'][] = $row2['Column_name'];
            }

            if (count($tmpPrivs2['Select']) > 0 && ! in_array('SELECT', $tmpPrivs1)) {
                $tmpPrivs1[] = 'SELECT (`' . implode('`, `', $tmpPrivs2['Select']) . '`)';
            }

            if (count($tmpPrivs2['Insert']) > 0 && ! in_array('INSERT', $tmpPrivs1)) {
                $tmpPrivs1[] = 'INSERT (`' . implode('`, `', $tmpPrivs2['Insert']) . '`)';
            }

            if (count($tmpPrivs2['Update']) > 0 && ! in_array('UPDATE', $tmpPrivs1)) {
                $tmpPrivs1[] = 'UPDATE (`' . implode('`, `', $tmpPrivs2['Update']) . '`)';
            }

            if (count($tmpPrivs2['References']) > 0 && ! in_array('REFERENCES', $tmpPrivs1)) {
                $tmpPrivs1[] = 'REFERENCES (`' . implode('`, `', $tmpPrivs2['References']) . '`)';
            }

            $queries[] = 'GRANT ' . implode(', ', $tmpPrivs1)
                . ' ON ' . Util::backquote($row['Db']) . '.'
                . Util::backquote($row['Table_name'])
                . ' TO \'' . $this->dbi->escapeString($username)
                . '\'@\'' . $this->dbi->escapeString($hostname) . '\''
                . (in_array('Grant', explode(',', $row['Table_priv']))
                ? ' WITH GRANT OPTION;'
                : ';');
        }

        return $queries;
    }

    /**
     * Get queries for database specific privileges for change or copy user
     *
     * @param array  $queries  queries array with string
     * @param string $username username
     * @param string $hostname host name
     *
     * @return array
     */
    public function getDbSpecificPrivsQueriesForChangeOrCopyUser(
        array $queries,
        string $username,
        string $hostname
    ) {
        $userHostCondition = ' WHERE `User`'
            . ' = \'' . $this->dbi->escapeString($_POST['old_username']) . "'"
            . ' AND `Host`'
            . ' = \'' . $this->dbi->escapeString($_POST['old_hostname']) . '\';';

        $res = $this->dbi->query('SELECT * FROM `mysql`.`db`' . $userHostCondition);

        while ($row = $res->fetchAssoc()) {
            $queries[] = 'GRANT ' . implode(', ', $this->extractPrivInfo($row))
                . ' ON ' . Util::backquote($row['Db']) . '.*'
                . ' TO \'' . $this->dbi->escapeString($username)
                . '\'@\'' . $this->dbi->escapeString($hostname) . '\''
                . ($row['Grant_priv'] === 'Y' ? ' WITH GRANT OPTION;' : ';');
        }

        return $this->getTablePrivsQueriesForChangeOrCopyUser($userHostCondition, $queries, $username, $hostname);
    }

    /**
     * Prepares queries for adding users and
     * also create database and return query and message
     *
     * @param bool   $error             whether user create or not
     * @param string $realSqlQuery      SQL query for add a user
     * @param string $sqlQuery          SQL query to be displayed
     * @param string $username          username
     * @param string $hostname          host name
     * @param string $dbname            database name
     * @param string $alterRealSqlQuery SQL query for ALTER USER
     * @param string $alterSqlQuery     SQL query for ALTER USER to be displayed
     *
     * @return array<int,string|Message>
     */
    public function addUserAndCreateDatabase(
        $error,
        $realSqlQuery,
        $sqlQuery,
        $username,
        $hostname,
        $dbname,
        $alterRealSqlQuery,
        $alterSqlQuery,
        bool $createDb1,
        bool $createDb2,
        bool $createDb3
    ): array {
        if ($error || (! empty($realSqlQuery) && ! $this->dbi->tryQuery($realSqlQuery))) {
            $createDb1 = $createDb2 = $createDb3 = false;
            $message = Message::rawError($this->dbi->getError());
        } elseif ($alterRealSqlQuery !== '' && ! $this->dbi->tryQuery($alterRealSqlQuery)) {
            $createDb1 = $createDb2 = $createDb3 = false;
            $message = Message::rawError($this->dbi->getError());
        } else {
            $sqlQuery .= $alterSqlQuery;
            $message = Message::success(__('You have added a new user.'));
        }

        if ($createDb1) {
            // Create database with same name and grant all privileges
            $query = 'CREATE DATABASE IF NOT EXISTS '
                . Util::backquote($username) . ';';
            $sqlQuery .= $query;
            if (! $this->dbi->tryQuery($query)) {
                $message = Message::rawError($this->dbi->getError());
            }

            /**
             * Reload the navigation
             */
            $GLOBALS['reload'] = true;
            $GLOBALS['db'] = $username;

            $query = 'GRANT ALL PRIVILEGES ON '
                . Util::backquote(
                    Util::escapeMysqlWildcards($username)
                ) . '.* TO \''
                . $this->dbi->escapeString($username)
                . '\'@\'' . $this->dbi->escapeString($hostname) . '\';';
            $sqlQuery .= $query;
            if (! $this->dbi->tryQuery($query)) {
                $message = Message::rawError($this->dbi->getError());
            }
        }

        if ($createDb2) {
            // Grant all privileges on wildcard name (username\_%)
            $query = 'GRANT ALL PRIVILEGES ON '
                . Util::backquote(
                    Util::escapeMysqlWildcards($username) . '\_%'
                ) . '.* TO \''
                . $this->dbi->escapeString($username)
                . '\'@\'' . $this->dbi->escapeString($hostname) . '\';';
            $sqlQuery .= $query;
            if (! $this->dbi->tryQuery($query)) {
                $message = Message::rawError($this->dbi->getError());
            }
        }

        if ($createDb3) {
            // Grant all privileges on the specified database to the new user
            $query = 'GRANT ALL PRIVILEGES ON '
            . Util::backquote($dbname) . '.* TO \''
            . $this->dbi->escapeString($username)
            . '\'@\'' . $this->dbi->escapeString($hostname) . '\';';
            $sqlQuery .= $query;
            if (! $this->dbi->tryQuery($query)) {
                $message = Message::rawError($this->dbi->getError());
            }
        }

        return [
            $sqlQuery,
            $message,
        ];
    }

    /**
     * Get the hashed string for password
     *
     * @param string $password password
     *
     * @return string
     */
    public function getHashedPassword($password)
    {
        $password = $this->dbi->escapeString($password);
        $result = $this->dbi->fetchSingleRow("SELECT PASSWORD('" . $password . "') AS `password`;");

        return $result['password'];
    }

    /**
     * Check if MariaDB's 'simple_password_check'
     * OR 'cracklib_password_check' is ACTIVE
     */
    public function checkIfMariaDBPwdCheckPluginActive(): bool
    {
        $serverVersion = $this->dbi->getVersion();
        if (! (Compatibility::isMariaDb() && $serverVersion >= 100002)) {
            return false;
        }

        $result = $this->dbi->tryQuery('SHOW PLUGINS SONAME LIKE \'%_password_check%\'');

        /* Plugins are not working, for example directory does not exists */
        if ($result === false) {
            return false;
        }

        while ($row = $result->fetchAssoc()) {
            if ($row['Status'] === 'ACTIVE') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get SQL queries for Display and Add user
     *
     * @param string $username username
     * @param string $hostname host name
     * @param string $password password
     *
     * @return array ($create_user_real, $create_user_show, $real_sql_query, $sql_query
     *                $password_set_real, $password_set_show, $alter_real_sql_query, $alter_sql_query)
     */
    public function getSqlQueriesForDisplayAndAddUser($username, $hostname, $password)
    {
        $slashedUsername = $this->dbi->escapeString($username);
        $slashedHostname = $this->dbi->escapeString($hostname);
        $slashedPassword = $this->dbi->escapeString($password);
        $serverVersion = $this->dbi->getVersion();

        $createUserStmt = sprintf('CREATE USER \'%s\'@\'%s\'', $slashedUsername, $slashedHostname);
        $isMariaDBPwdPluginActive = $this->checkIfMariaDBPwdCheckPluginActive();

        // See https://github.com/phpmyadmin/phpmyadmin/pull/11560#issuecomment-147158219
        // for details regarding details of syntax usage for various versions

        // 'IDENTIFIED WITH auth_plugin'
        // is supported by MySQL 5.5.7+
        if (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50507 && isset($_POST['authentication_plugin'])) {
            $createUserStmt .= ' IDENTIFIED WITH '
                . $_POST['authentication_plugin'];
        }

        // 'IDENTIFIED VIA auth_plugin'
        // is supported by MariaDB 5.2+
        if (
            Compatibility::isMariaDb()
            && $serverVersion >= 50200
            && isset($_POST['authentication_plugin'])
            && ! $isMariaDBPwdPluginActive
        ) {
            $createUserStmt .= ' IDENTIFIED VIA '
                . $_POST['authentication_plugin'];
        }

        $createUserReal = $createUserStmt;
        $createUserShow = $createUserStmt;

        $passwordSetStmt = 'SET PASSWORD FOR \'%s\'@\'%s\' = \'%s\'';
        $passwordSetShow = sprintf($passwordSetStmt, $slashedUsername, $slashedHostname, '***');

        $sqlQueryStmt = sprintf(
            'GRANT %s ON *.* TO \'%s\'@\'%s\'',
            implode(', ', $this->extractPrivInfo()),
            $slashedUsername,
            $slashedHostname
        );
        $realSqlQuery = $sqlQuery = $sqlQueryStmt;

        // Set the proper hashing method
        if (isset($_POST['authentication_plugin'])) {
            $this->setProperPasswordHashing($_POST['authentication_plugin']);
        }

        // Use 'CREATE USER ... WITH ... AS ..' syntax for
        // newer MySQL versions
        // and 'CREATE USER ... VIA .. USING ..' syntax for
        // newer MariaDB versions
        if (
            (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50706)
            || (Compatibility::isMariaDb() && $serverVersion >= 50200)
        ) {
            $passwordSetReal = null;

            // Required for binding '%' with '%s'
            $createUserStmt = str_replace('%', '%%', $createUserStmt);

            // MariaDB uses 'USING' whereas MySQL uses 'AS'
            // but MariaDB with validation plugin needs cleartext password
            if (Compatibility::isMariaDb() && ! $isMariaDBPwdPluginActive) {
                $createUserStmt .= ' USING \'%s\'';
            } elseif (Compatibility::isMariaDb()) {
                $createUserStmt .= ' IDENTIFIED BY \'%s\'';
            } elseif (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 80011) {
                if (! str_contains($createUserStmt, 'IDENTIFIED')) {
                    // Maybe the authentication_plugin was not posted and then a part is missing
                    $createUserStmt .= ' IDENTIFIED BY \'%s\'';
                } else {
                    $createUserStmt .= ' BY \'%s\'';
                }
            } else {
                $createUserStmt .= ' AS \'%s\'';
            }

            if ($_POST['pred_password'] === 'keep') {
                $createUserReal = sprintf($createUserStmt, $slashedPassword);
                $createUserShow = sprintf($createUserStmt, '***');
            } elseif ($_POST['pred_password'] === 'none') {
                $createUserReal = sprintf($createUserStmt, null);
                $createUserShow = sprintf($createUserStmt, '***');
            } else {
                if (
                    ! ((Compatibility::isMariaDb() && $isMariaDBPwdPluginActive)
                    || Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 80011)
                ) {
                    $hashedPassword = $this->getHashedPassword($_POST['pma_pw']);
                } else {
                    // MariaDB with validation plugin needs cleartext password
                    $hashedPassword = $_POST['pma_pw'];
                }

                $createUserReal = sprintf($createUserStmt, $hashedPassword);
                $createUserShow = sprintf($createUserStmt, '***');
            }
        } else {
            // Use 'SET PASSWORD' syntax for pre-5.7.6 MySQL versions
            // and pre-5.2.0 MariaDB versions
            if ($_POST['pred_password'] === 'keep') {
                $passwordSetReal = sprintf($passwordSetStmt, $slashedUsername, $slashedHostname, $slashedPassword);
            } elseif ($_POST['pred_password'] === 'none') {
                $passwordSetReal = sprintf($passwordSetStmt, $slashedUsername, $slashedHostname, null);
            } else {
                $hashedPassword = $this->getHashedPassword($_POST['pma_pw']);
                $passwordSetReal = sprintf($passwordSetStmt, $slashedUsername, $slashedHostname, $hashedPassword);
            }
        }

        $alterRealSqlQuery = '';
        $alterSqlQuery = '';
        if (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 80011) {
            $sqlQueryStmt = '';
            if (
                (isset($_POST['Grant_priv']) && $_POST['Grant_priv'] === 'Y')
                || (isset($GLOBALS['Grant_priv']) && $GLOBALS['Grant_priv'] === 'Y')
            ) {
                $sqlQueryStmt = ' WITH GRANT OPTION';
            }

            $realSqlQuery .= $sqlQueryStmt;
            $sqlQuery .= $sqlQueryStmt;

            $alterSqlQueryStmt = sprintf('ALTER USER \'%s\'@\'%s\'', $slashedUsername, $slashedHostname);
            $alterRealSqlQuery = $alterSqlQueryStmt;
            $alterSqlQuery = $alterSqlQueryStmt;
        }

        // add REQUIRE clause
        $requireClause = $this->getRequireClause();
        $withClause = $this->getWithClauseForAddUserAndUpdatePrivs();

        if (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 80011) {
            $alterRealSqlQuery .= $requireClause;
            $alterSqlQuery .= $requireClause;
            $alterRealSqlQuery .= $withClause;
            $alterSqlQuery .= $withClause;
        } else {
            $realSqlQuery .= $requireClause;
            $sqlQuery .= $requireClause;
            $realSqlQuery .= $withClause;
            $sqlQuery .= $withClause;
        }

        if ($alterRealSqlQuery !== '') {
            $alterRealSqlQuery .= ';';
            $alterSqlQuery .= ';';
        }

        $createUserReal .= ';';
        $createUserShow .= ';';
        $realSqlQuery .= ';';
        $sqlQuery .= ';';
        // No Global GRANT_OPTION privilege
        if (! $this->dbi->isGrantUser()) {
            $realSqlQuery = '';
            $sqlQuery = '';
        }

        // Use 'SET PASSWORD' for pre-5.7.6 MySQL versions
        // and pre-5.2.0 MariaDB
        if (
            (Compatibility::isMySqlOrPerconaDb()
            && $serverVersion >= 50706)
            || (Compatibility::isMariaDb()
            && $serverVersion >= 50200)
        ) {
            $passwordSetReal = null;
            $passwordSetShow = null;
        } else {
            if ($passwordSetReal !== null) {
                $passwordSetReal .= ';';
            }

            $passwordSetShow .= ';';
        }

        return [
            $createUserReal,
            $createUserShow,
            $realSqlQuery,
            $sqlQuery,
            $passwordSetReal,
            $passwordSetShow,
            $alterRealSqlQuery,
            $alterSqlQuery,
        ];
    }

    /**
     * Returns the type ('PROCEDURE' or 'FUNCTION') of the routine
     *
     * @param string $dbname      database
     * @param string $routineName routine
     *
     * @return string type
     */
    public function getRoutineType(string $dbname, string $routineName)
    {
        $routineData = $this->dbi->getRoutines($dbname);
        $routineName = mb_strtolower($routineName);

        foreach ($routineData as $routine) {
            if (mb_strtolower($routine['name']) === $routineName) {
                return $routine['type'];
            }
        }

        return '';
    }

    /**
     * @param string $username User name
     * @param string $hostname Host name
     * @param string $database Database name
     * @param string $routine  Routine name
     *
     * @return array
     */
    private function getRoutinePrivileges(
        string $username,
        string $hostname,
        string $database,
        string $routine
    ): array {
        $sql = 'SELECT `Proc_priv`'
            . ' FROM `mysql`.`procs_priv`'
            . " WHERE `User` = '" . $this->dbi->escapeString($username) . "'"
            . " AND `Host` = '" . $this->dbi->escapeString($hostname) . "'"
            . " AND `Db` = '"
            . $this->dbi->escapeString(Util::unescapeMysqlWildcards($database)) . "'"
            . " AND `Routine_name` LIKE '" . $this->dbi->escapeString($routine) . "';";
        $privileges = $this->dbi->fetchValue($sql);
        if ($privileges === false) {
            $privileges = '';
        }

        return $this->parseProcPriv($privileges);
    }

    public function getFormForChangePassword(string $username, string $hostname, bool $editOthers): string
    {
        global $route;

        $isPrivileges = $route === '/server/privileges';

        $serverVersion = $this->dbi->getVersion();
        $origAuthPlugin = $this->getCurrentAuthenticationPlugin('change', $username, $hostname);

        $isNew = (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50507)
            || (Compatibility::isMariaDb() && $serverVersion >= 50200);
        $hasMoreAuthPlugins = (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50706)
            || ($this->dbi->isSuperUser() && $editOthers);

        $activeAuthPlugins = ['mysql_native_password' => __('Native MySQL authentication')];

        if ($isNew && $hasMoreAuthPlugins) {
            $activeAuthPlugins = $this->plugins->getAuthentication();
            if (isset($activeAuthPlugins['mysql_old_password'])) {
                unset($activeAuthPlugins['mysql_old_password']);
            }
        }

        return $this->template->render('server/privileges/change_password', [
            'username' => $username,
            'hostname' => $hostname,
            'is_privileges' => $isPrivileges,
            'is_new' => $isNew,
            'has_more_auth_plugins' => $hasMoreAuthPlugins,
            'active_auth_plugins' => $activeAuthPlugins,
            'orig_auth_plugin' => $origAuthPlugin,
        ]);
    }

    /**
     * @see https://dev.mysql.com/doc/refman/en/account-locking.html
     * @see https://mariadb.com/kb/en/account-locking/
     *
     * @return array<string, string|null>|null
     */
    private function getUserPrivileges(string $user, string $host, bool $hasAccountLocking): ?array
    {
        $query = 'SELECT * FROM `mysql`.`user` WHERE `User` = ? AND `Host` = ?;';
        /** @var mysqli_stmt|false $statement */
        $statement = $this->dbi->prepare($query);
        if ($statement === false || ! $statement->bind_param('ss', $user, $host) || ! $statement->execute()) {
            return null;
        }

        $result = new MysqliResult($statement->get_result());
        /** @var array<string, string|null>|null $userPrivileges */
        $userPrivileges = $result->fetchAssoc();
        if ($userPrivileges === []) {
            return null;
        }

        if (! $hasAccountLocking || ! $this->dbi->isMariaDB()) {
            return $userPrivileges;
        }

        $userPrivileges['account_locked'] = 'N';

        $query = 'SELECT * FROM `mysql`.`global_priv` WHERE `User` = ? AND `Host` = ?;';
        /** @var mysqli_stmt|false $statement */
        $statement = $this->dbi->prepare($query);
        if ($statement === false || ! $statement->bind_param('ss', $user, $host) || ! $statement->execute()) {
            return $userPrivileges;
        }

        $result = new MysqliResult($statement->get_result());
        /** @var array<string, string|null>|null $globalPrivileges */
        $globalPrivileges = $result->fetchAssoc();
        if ($globalPrivileges === []) {
            return $userPrivileges;
        }

        $privileges = json_decode($globalPrivileges['Priv'] ?? '[]', true);
        if (! is_array($privileges)) {
            return $userPrivileges;
        }

        if (isset($privileges['account_locked']) && $privileges['account_locked']) {
            $userPrivileges['account_locked'] = 'Y';
        }

        return $userPrivileges;
    }
}
