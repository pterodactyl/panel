<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function array_merge;
use function is_array;
use function is_string;
use function preg_match;
use function sort;
use function strlen;
use function usort;

/**
 * handles database lists
 *
 * <code>
 * $ListDatabase = new ListDatabase();
 * </code>
 *
 * @todo this object should be attached to the PMA_Server object
 */
class ListDatabase extends ListAbstract
{
    public function __construct()
    {
        global $dbi;

        parent::__construct();

        $checkUserPrivileges = new CheckUserPrivileges($dbi);
        $checkUserPrivileges->getPrivileges();

        $this->build();
    }

    /**
     * checks if the configuration wants to hide some databases
     */
    protected function checkHideDatabase(): void
    {
        if (empty($GLOBALS['cfg']['Server']['hide_db'])) {
            return;
        }

        foreach ($this->getArrayCopy() as $key => $db) {
            if (! preg_match('/' . $GLOBALS['cfg']['Server']['hide_db'] . '/', $db)) {
                continue;
            }

            $this->offsetUnset($key);
        }
    }

    /**
     * retrieves database list from server
     *
     * @param string $like_db_name usually a db_name containing wildcards
     *
     * @return array
     */
    protected function retrieve($like_db_name = null)
    {
        global $dbi;

        $database_list = [];
        $command = '';
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $command .= 'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`';
            if ($like_db_name !== null) {
                $command .= " WHERE `SCHEMA_NAME` LIKE '" . $like_db_name . "'";
            }
        } else {
            if ($GLOBALS['dbs_to_test'] === false || $like_db_name !== null) {
                $command .= 'SHOW DATABASES';
                if ($like_db_name !== null) {
                    $command .= " LIKE '" . $like_db_name . "'";
                }
            } else {
                foreach ($GLOBALS['dbs_to_test'] as $db) {
                    $database_list = array_merge(
                        $database_list,
                        $this->retrieve($db)
                    );
                }
            }
        }

        if ($command) {
            $database_list = $dbi->fetchResult($command, null, null);
        }

        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($database_list, 'strnatcasecmp');
        } else {
            // need to sort anyway, otherwise information_schema
            // goes at the top
            sort($database_list);
        }

        return $database_list;
    }

    /**
     * builds up the list
     */
    public function build(): void
    {
        if (! $this->checkOnlyDatabase()) {
            $items = $this->retrieve();
            $this->exchangeArray($items);
        }

        $this->checkHideDatabase();
    }

    /**
     * checks the only_db configuration
     */
    protected function checkOnlyDatabase(): bool
    {
        if (is_string($GLOBALS['cfg']['Server']['only_db']) && strlen($GLOBALS['cfg']['Server']['only_db']) > 0) {
            $GLOBALS['cfg']['Server']['only_db'] = [
                $GLOBALS['cfg']['Server']['only_db'],
            ];
        }

        if (! is_array($GLOBALS['cfg']['Server']['only_db'])) {
            return false;
        }

        $items = [];

        foreach ($GLOBALS['cfg']['Server']['only_db'] as $each_only_db) {
            // check if the db name contains wildcard,
            // thus containing not escaped _ or %
            if (! preg_match('/(^|[^\\\\])(_|%)/', $each_only_db)) {
                // ... not contains wildcard
                $items[] = Util::unescapeMysqlWildcards($each_only_db);
                continue;
            }

            $items = array_merge($items, $this->retrieve($each_only_db));
        }

        $this->exchangeArray($items);

        return true;
    }

    /**
     * returns default item
     *
     * @return string default item
     */
    public function getDefault()
    {
        if (strlen($GLOBALS['db']) > 0) {
            return $GLOBALS['db'];
        }

        return $this->getEmpty();
    }
}
