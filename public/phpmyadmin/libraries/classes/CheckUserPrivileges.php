<?php
/**
 * Get user's global privileges and some db-specific privileges
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Utils\SessionCache;

use function mb_strpos;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function str_contains;

/**
 * PhpMyAdmin\CheckUserPrivileges class
 */
class CheckUserPrivileges
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param DatabaseInterface $dbi DatabaseInterface object
     */
    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
    }

    /**
     * Extracts details from a result row of a SHOW GRANT query
     *
     * @param string $row grant row
     *
     * @return array
     */
    public function getItemsFromShowGrantsRow(string $row): array
    {
        $dbNameOffset = mb_strpos($row, ' ON ') + 4;

        $tableNameEndOffset = mb_strpos($row, ' TO ');
        $tableNameStartOffset = false;
        $tableNameStartOffset2 = mb_strpos($row, '`.', $dbNameOffset);

        if ($tableNameStartOffset2 && $tableNameStartOffset2 < $tableNameEndOffset) {
            $tableNameStartOffset = $tableNameStartOffset2 + 1;
        }

        if ($tableNameStartOffset === false) {
            $tableNameStartOffset = mb_strpos($row, '.', $dbNameOffset);
        }

        $showGrantsDbName = mb_substr($row, $dbNameOffset, $tableNameStartOffset - $dbNameOffset);

        $showGrantsDbName = Util::unQuote($showGrantsDbName, '`');

        $showGrantsString = mb_substr(
            $row,
            6,
            mb_strpos($row, ' ON ') - 6
        );

        $showGrantsTableName = mb_substr(
            $row,
            $tableNameStartOffset + 1,
            $tableNameEndOffset - $tableNameStartOffset - 1
        );
        $showGrantsTableName = Util::unQuote($showGrantsTableName, '`');

        return [
            $showGrantsString,
            $showGrantsDbName,
            $showGrantsTableName,
        ];
    }

    /**
     * Check if user has required privileges for
     * performing 'Adjust privileges' operations
     *
     * @param string $showGrantsString    string containing grants for user
     * @param string $showGrantsDbName    name of db extracted from grant string
     * @param string $showGrantsTableName name of table extracted from grant string
     */
    public function checkRequiredPrivilegesForAdjust(
        string $showGrantsString,
        string $showGrantsDbName,
        string $showGrantsTableName
    ): void {
        // '... ALL PRIVILEGES ON *.* ...' OR '... ALL PRIVILEGES ON `mysql`.* ..'
        // OR
        // SELECT, INSERT, UPDATE, DELETE .... ON *.* OR `mysql`.*
        if (
            $showGrantsString !== 'ALL'
            && $showGrantsString !== 'ALL PRIVILEGES'
            && (mb_strpos($showGrantsString, 'SELECT, INSERT, UPDATE, DELETE') === false)
        ) {
            return;
        }

        if ($showGrantsDbName === '*' && $showGrantsTableName === '*') {
            $GLOBALS['col_priv'] = true;
            $GLOBALS['db_priv'] = true;
            $GLOBALS['proc_priv'] = true;
            $GLOBALS['table_priv'] = true;

            if ($showGrantsString === 'ALL PRIVILEGES' || $showGrantsString === 'ALL') {
                $GLOBALS['is_reload_priv'] = true;
            }
        }

        // check for specific tables in `mysql` db
        // Ex. '... ALL PRIVILEGES on `mysql`.`columns_priv` .. '
        if ($showGrantsDbName !== 'mysql') {
            return;
        }

        switch ($showGrantsTableName) {
            case 'columns_priv':
                $GLOBALS['col_priv'] = true;
                break;
            case 'db':
                $GLOBALS['db_priv'] = true;
                break;
            case 'procs_priv':
                $GLOBALS['proc_priv'] = true;
                break;
            case 'tables_priv':
                $GLOBALS['table_priv'] = true;
                break;
            case '*':
                $GLOBALS['col_priv'] = true;
                $GLOBALS['db_priv'] = true;
                $GLOBALS['proc_priv'] = true;
                $GLOBALS['table_priv'] = true;
                break;
            default:
        }
    }

    /**
     * sets privilege information extracted from SHOW GRANTS result
     *
     * Detection for some CREATE privilege.
     *
     * Since MySQL 4.1.2, we can easily detect current user's grants using $userlink
     * (no control user needed) and we don't have to try any other method for
     * detection
     *
     * @todo fix to get really all privileges, not only explicitly defined for this user
     * from MySQL manual: (https://dev.mysql.com/doc/refman/5.0/en/show-grants.html)
     * SHOW GRANTS displays only the privileges granted explicitly to the named
     * account. Other privileges might be available to the account, but they are not
     * displayed. For example, if an anonymous account exists, the named account
     * might be able to use its privileges, but SHOW GRANTS will not display them.
     */
    private function analyseShowGrant(): void
    {
        if (SessionCache::has('is_create_db_priv')) {
            $GLOBALS['is_create_db_priv'] = SessionCache::get('is_create_db_priv');
            $GLOBALS['is_reload_priv'] = SessionCache::get('is_reload_priv');
            $GLOBALS['db_to_create'] = SessionCache::get('db_to_create');
            $GLOBALS['dbs_where_create_table_allowed'] = SessionCache::get('dbs_where_create_table_allowed');
            $GLOBALS['dbs_to_test'] = SessionCache::get('dbs_to_test');

            $GLOBALS['db_priv'] = SessionCache::get('db_priv');
            $GLOBALS['col_priv'] = SessionCache::get('col_priv');
            $GLOBALS['table_priv'] = SessionCache::get('table_priv');
            $GLOBALS['proc_priv'] = SessionCache::get('proc_priv');

            return;
        }

        // defaults
        $GLOBALS['is_create_db_priv'] = false;
        $GLOBALS['is_reload_priv'] = false;
        $GLOBALS['db_to_create'] = '';
        $GLOBALS['dbs_where_create_table_allowed'] = [];
        $GLOBALS['dbs_to_test'] = Utilities::getSystemSchemas();
        $GLOBALS['proc_priv'] = false;
        $GLOBALS['db_priv'] = false;
        $GLOBALS['col_priv'] = false;
        $GLOBALS['table_priv'] = false;

        $showGrantsResult = $this->dbi->tryQuery('SHOW GRANTS');

        if (! $showGrantsResult) {
            return;
        }

        $re0 = '(^|(\\\\\\\\)+|[^\\\\])'; // non-escaped wildcards
        $re1 = '(^|[^\\\\])(\\\)+'; // escaped wildcards

        while ($row = $showGrantsResult->fetchRow()) {
            [
                $showGrantsString,
                $showGrantsDbName,
                $showGrantsTableName,
            ] = $this->getItemsFromShowGrantsRow($row[0]);

            if ($showGrantsDbName === '*') {
                if ($showGrantsString !== 'USAGE') {
                    $GLOBALS['dbs_to_test'] = false;
                }
            } elseif ($GLOBALS['dbs_to_test'] !== false) {
                $GLOBALS['dbs_to_test'][] = $showGrantsDbName;
            }

            if (str_contains($showGrantsString, 'RELOAD')) {
                $GLOBALS['is_reload_priv'] = true;
            }

            // check for the required privileges for adjust
            $this->checkRequiredPrivilegesForAdjust($showGrantsString, $showGrantsDbName, $showGrantsTableName);

            /**
             * @todo if we find CREATE VIEW but not CREATE, do not offer
             * the create database dialog box
             */
            if (
                $showGrantsString !== 'ALL'
                && $showGrantsString !== 'ALL PRIVILEGES'
                && $showGrantsString !== 'CREATE'
                && ! str_contains($showGrantsString, 'CREATE,')
            ) {
                continue;
            }

            if ($showGrantsDbName === '*') {
                // a global CREATE privilege
                $GLOBALS['is_create_db_priv'] = true;
                $GLOBALS['is_reload_priv'] = true;
                $GLOBALS['db_to_create'] = '';
                $GLOBALS['dbs_where_create_table_allowed'][] = '*';
                // @todo we should not break here, cause GRANT ALL *.*
                // could be revoked by a later rule like GRANT SELECT ON db.*
                break;
            }

            // this array may contain wildcards
            $GLOBALS['dbs_where_create_table_allowed'][] = $showGrantsDbName;

            $dbNameToTest = Util::backquote($showGrantsDbName);

            if ($GLOBALS['is_create_db_priv']) {
                // no need for any more tests if we already know this
                continue;
            }

            // does this db exist?
            if (
                (! preg_match('/' . $re0 . '%|_/', $showGrantsDbName)
                || preg_match('/\\\\%|\\\\_/', $showGrantsDbName))
                && ($this->dbi->tryQuery(
                    'USE ' . preg_replace(
                        '/' . $re1 . '(%|_)/',
                        '\\1\\3',
                        $dbNameToTest
                    )
                )
                || mb_substr($this->dbi->getError(), 1, 4) == 1044)
            ) {
                continue;
            }

            /**
             * Do not handle the underscore wildcard
             * (this case must be rare anyway)
             */
            $GLOBALS['db_to_create'] = preg_replace('/' . $re0 . '%/', '\\1', $showGrantsDbName);
            $GLOBALS['db_to_create'] = preg_replace('/' . $re1 . '(%|_)/', '\\1\\3', $GLOBALS['db_to_create']);
            $GLOBALS['is_create_db_priv'] = true;

            /**
             * @todo collect $GLOBALS['db_to_create'] into an array,
             * to display a drop-down in the "Create database" dialog
             */
            // we don't break, we want all possible databases
            //break;
        }

        // must also cacheUnset() them in
        // PhpMyAdmin\Plugins\Auth\AuthenticationCookie
        SessionCache::set('is_create_db_priv', $GLOBALS['is_create_db_priv']);
        SessionCache::set('is_reload_priv', $GLOBALS['is_reload_priv']);
        SessionCache::set('db_to_create', $GLOBALS['db_to_create']);
        SessionCache::set('dbs_where_create_table_allowed', $GLOBALS['dbs_where_create_table_allowed']);
        SessionCache::set('dbs_to_test', $GLOBALS['dbs_to_test']);

        SessionCache::set('proc_priv', $GLOBALS['proc_priv']);
        SessionCache::set('table_priv', $GLOBALS['table_priv']);
        SessionCache::set('col_priv', $GLOBALS['col_priv']);
        SessionCache::set('db_priv', $GLOBALS['db_priv']);
    }

    /**
     * Get user's global privileges and some db-specific privileges
     */
    public function getPrivileges(): void
    {
        $username = '';

        $current = $this->dbi->getCurrentUserAndHost();
        if (! empty($current)) {
            [$username] = $current;
        }

        // If MySQL is started with --skip-grant-tables
        if ($username === '') {
            $GLOBALS['is_create_db_priv'] = true;
            $GLOBALS['is_reload_priv'] = true;
            $GLOBALS['db_to_create'] = '';
            $GLOBALS['dbs_where_create_table_allowed'] = ['*'];
            $GLOBALS['dbs_to_test'] = false;
            $GLOBALS['db_priv'] = true;
            $GLOBALS['col_priv'] = true;
            $GLOBALS['table_priv'] = true;
            $GLOBALS['proc_priv'] = true;

            return;
        }

        $this->analyseShowGrant();
    }
}
