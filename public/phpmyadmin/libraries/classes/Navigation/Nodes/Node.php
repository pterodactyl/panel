<?php
/**
 * Functionality for the navigation tree in the left frame
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Util;

use function __;
use function array_keys;
use function array_reverse;
use function array_slice;
use function base64_encode;
use function count;
use function implode;
use function in_array;
use function is_string;
use function preg_match;
use function sort;
use function sprintf;
use function strlen;
use function strpos;
use function strstr;

/**
 * The Node is the building block for the collapsible navigation tree
 */
class Node
{
    public const CONTAINER = 0;
    public const OBJECT = 1;
    /**
     * @var string A non-unique identifier for the node
     *             This may be trimmed when grouping nodes
     */
    public $name = '';
    /**
     * @var string A non-unique identifier for the node
     *             This will never change after being assigned
     */
    public $realName = '';
    /** @var int May be one of CONTAINER or OBJECT */
    public $type = self::OBJECT;
    /**
     * @var bool Whether this object has been created while grouping nodes
     *           Only relevant if the node is of type CONTAINER
     */
    public $isGroup = false;
    /**
     * @var bool Whether to add a "display: none;" CSS
     *           rule to the node when rendering it
     */
    public $visible = false;
    /**
     * @var Node A reference to the parent object of
     *           this node, NULL for the root node.
     */
    public $parent;
    /**
     * @var Node[] An array of Node objects that are
     *             direct children of this node
     */
    public $children = [];
    /**
     * @var Mixed A string used to group nodes, or an array of strings
     *            Only relevant if the node is of type CONTAINER
     */
    public $separator = '';
    /**
     * @var int How many time to recursively apply the grouping function
     *          Only relevant if the node is of type CONTAINER
     */
    public $separatorDepth = 1;

    /**
     * For the IMG tag, used when rendering the node.
     *
     * @var array<string, string>
     * @psalm-var array{image: string, title: string}
     */
    public $icon = ['image' => '', 'title' => ''];

    /**
     * An array of A tags, used when rendering the node.
     *
     * @var array<string, mixed>
     * @psalm-var array{
     *   text: array{route: string, params: array<string, mixed>},
     *   icon: array{route: string, params: array<string, mixed>},
     *   second_icon?: array{route: string, params: array<string, mixed>},
     *   title?: string
     * }
     */
    public $links = [
        'text' => ['route' => '', 'params' => []],
        'icon' => ['route' => '', 'params' => []],
    ];

    /** @var string HTML title */
    public $title;
    /** @var string Extra CSS classes for the node */
    public $classes = '';
    /** @var bool Whether this node is a link for creating new objects */
    public $isNew = false;
    /**
     * @var int The position for the pagination of
     *          the branch at the second level of the tree
     */
    public $pos2 = 0;
    /**
     * @var int The position for the pagination of
     *          the branch at the third level of the tree
     */
    public $pos3 = 0;

    /** @var Relation */
    protected $relation;

    /** @var string $displayName  display name for the navigation tree */
    public $displayName;

    /** @var string|null */
    public $urlParamName = null;

    /**
     * Initialises the class by setting the mandatory variables
     *
     * @param string $name    An identifier for the new node
     * @param int    $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $isGroup Whether this object has been created
     *                        while grouping nodes
     */
    public function __construct($name, $type = self::OBJECT, $isGroup = false)
    {
        global $dbi;

        if (strlen((string) $name)) {
            $this->name = $name;
            $this->realName = $name;
        }

        if ($type === self::CONTAINER) {
            $this->type = self::CONTAINER;
        }

        $this->isGroup = (bool) $isGroup;
        $this->relation = new Relation($dbi);
    }

    /**
     * Adds a child node to this node
     *
     * @param Node $child A child node
     */
    public function addChild($child): void
    {
        $this->children[] = $child;
        $child->parent = $this;
    }

    /**
     * Returns a child node given it's name
     *
     * @param string $name     The name of requested child
     * @param bool   $realName Whether to use the "realName"
     *                         instead of "name" in comparisons
     *
     * @return Node|null The requested child node or null,
     *                   if the requested node cannot be found
     */
    public function getChild($name, $realName = false): ?Node
    {
        if ($realName) {
            foreach ($this->children as $child) {
                if ($child->realName == $name) {
                    return $child;
                }
            }
        } else {
            foreach ($this->children as $child) {
                if ($child->name == $name && $child->isNew === false) {
                    return $child;
                }
            }
        }

        return null;
    }

    /**
     * Removes a child node from this node
     *
     * @param string $name The name of child to be removed
     */
    public function removeChild($name): void
    {
        foreach ($this->children as $key => $child) {
            if ($child->name == $name) {
                unset($this->children[$key]);
                break;
            }
        }
    }

    /**
     * Retrieves the parents for a node
     *
     * @param bool $self       Whether to include the Node itself in the results
     * @param bool $containers Whether to include nodes of type CONTAINER
     * @param bool $groups     Whether to include nodes which have $group == true
     *
     * @return Node[] An array of parent Nodes
     */
    public function parents($self = false, $containers = false, $groups = false): array
    {
        $parents = [];
        if ($self && ($this->type != self::CONTAINER || $containers) && (! $this->isGroup || $groups)) {
            $parents[] = $this;
        }

        $parent = $this->parent;
        while ($parent !== null) {
            if (($parent->type != self::CONTAINER || $containers) && (! $parent->isGroup || $groups)) {
                $parents[] = $parent;
            }

            $parent = $parent->parent;
        }

        return $parents;
    }

    /**
     * Returns the actual parent of a node. If used twice on an index or columns
     * node, it will return the table and database nodes. The names of the returned
     * nodes can be used in SQL queries, etc...
     *
     * @return Node|false
     */
    public function realParent()
    {
        $retval = $this->parents();
        if (count($retval) <= 0) {
            return false;
        }

        return $retval[0];
    }

    /**
     * This function checks if the node has children nodes associated with it
     *
     * @param bool $countEmptyContainers Whether to count empty child
     *                                   containers as valid children
     */
    public function hasChildren($countEmptyContainers = true): bool
    {
        $retval = false;
        if ($countEmptyContainers) {
            if (count($this->children)) {
                $retval = true;
            }
        } else {
            foreach ($this->children as $child) {
                if ($child->type == self::OBJECT || $child->hasChildren(false)) {
                    $retval = true;
                    break;
                }
            }
        }

        return $retval;
    }

    /**
     * Returns true if the node has some siblings (other nodes on the same tree
     * level, in the same branch), false otherwise.
     * The only exception is for nodes on
     * the third level of the tree (columns and indexes), for which the function
     * always returns true. This is because we want to render the containers
     * for these nodes
     */
    public function hasSiblings(): bool
    {
        $retval = false;
        $paths = $this->getPaths();
        if (count($paths['aPath_clean']) > 3) {
            return true;
        }

        foreach ($this->parent->children as $child) {
            if ($child !== $this && ($child->type == self::OBJECT || $child->hasChildren(false))) {
                $retval = true;
                break;
            }
        }

        return $retval;
    }

    /**
     * Returns the number of child nodes that a node has associated with it
     *
     * @return int The number of children nodes
     */
    public function numChildren(): int
    {
        $retval = 0;
        foreach ($this->children as $child) {
            if ($child->type == self::OBJECT) {
                $retval++;
            } else {
                $retval += $child->numChildren();
            }
        }

        return $retval;
    }

    /**
     * Returns the actual path and the virtual paths for a node
     * both as clean arrays and base64 encoded strings
     *
     * @return array
     */
    public function getPaths(): array
    {
        $aPath = [];
        $aPathClean = [];
        foreach ($this->parents(true, true, false) as $parent) {
            $aPath[] = base64_encode($parent->realName);
            $aPathClean[] = $parent->realName;
        }

        $aPath = implode('.', array_reverse($aPath));
        $aPathClean = array_reverse($aPathClean);

        $vPath = [];
        $vPathClean = [];
        foreach ($this->parents(true, true, true) as $parent) {
            $vPath[] = base64_encode((string) $parent->name);
            $vPathClean[] = $parent->name;
        }

        $vPath = implode('.', array_reverse($vPath));
        $vPathClean = array_reverse($vPathClean);

        return [
            'aPath' => $aPath,
            'aPath_clean' => $aPathClean,
            'vPath' => $vPath,
            'vPath_clean' => $vPathClean,
        ];
    }

    /**
     * Returns the names of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    public function getData($type, $pos, $searchClause = '')
    {
        if (isset($GLOBALS['cfg']['Server']['DisableIS']) && ! $GLOBALS['cfg']['Server']['DisableIS']) {
            return $this->getDataFromInfoSchema($pos, $searchClause);
        }

        if ($GLOBALS['dbs_to_test'] === false) {
            return $this->getDataFromShowDatabases($pos, $searchClause);
        }

        return $this->getDataFromShowDatabasesLike($pos, $searchClause);
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return int
     */
    public function getPresence($type = '', $searchClause = '')
    {
        global $dbi;

        if (! $GLOBALS['cfg']['NavigationTreeEnableGrouping'] || ! $GLOBALS['cfg']['ShowDatabasesNavigationAsTree']) {
            if (isset($GLOBALS['cfg']['Server']['DisableIS']) && ! $GLOBALS['cfg']['Server']['DisableIS']) {
                $query = 'SELECT COUNT(*) ';
                $query .= 'FROM INFORMATION_SCHEMA.SCHEMATA ';
                $query .= $this->getWhereClause('SCHEMA_NAME', $searchClause);

                return (int) $dbi->fetchValue($query);
            }

            if ($GLOBALS['dbs_to_test'] === false) {
                $query = 'SHOW DATABASES ';
                $query .= $this->getWhereClause('Database', $searchClause);

                return (int) $dbi->queryAndGetNumRows($query);
            }

            $retval = 0;
            foreach ($this->getDatabasesToSearch($searchClause) as $db) {
                $query = "SHOW DATABASES LIKE '" . $db . "'";
                $retval += (int) $dbi->queryAndGetNumRows($query);
            }

            return $retval;
        }

        $dbSeparator = $GLOBALS['cfg']['NavigationTreeDbSeparator'];
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = 'SELECT COUNT(*) ';
            $query .= 'FROM ( ';
            $query .= 'SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, ';
            $query .= "'" . $dbSeparator . "', 1) ";
            $query .= 'DB_first_level ';
            $query .= 'FROM INFORMATION_SCHEMA.SCHEMATA ';
            $query .= $this->getWhereClause('SCHEMA_NAME', $searchClause);
            $query .= ') t ';

            return (int) $dbi->fetchValue($query);
        }

        if ($GLOBALS['dbs_to_test'] !== false) {
            $prefixMap = [];
            foreach ($this->getDatabasesToSearch($searchClause) as $db) {
                $query = "SHOW DATABASES LIKE '" . $db . "'";
                $handle = $dbi->tryQuery($query);
                if ($handle === false) {
                    continue;
                }

                while ($arr = $handle->fetchRow()) {
                    if ($this->isHideDb($arr[0])) {
                        continue;
                    }

                    $prefix = strstr($arr[0], $dbSeparator, true);
                    if ($prefix === false) {
                        $prefix = $arr[0];
                    }

                    $prefixMap[$prefix] = 1;
                }
            }

            return count($prefixMap);
        }

        $prefixMap = [];
        $query = 'SHOW DATABASES ';
        $query .= $this->getWhereClause('Database', $searchClause);
        $handle = $dbi->tryQuery($query);
        if ($handle !== false) {
            while ($arr = $handle->fetchRow()) {
                $prefix = strstr($arr[0], $dbSeparator, true);
                if ($prefix === false) {
                    $prefix = $arr[0];
                }

                $prefixMap[$prefix] = 1;
            }
        }

        return count($prefixMap);
    }

    /**
     * Detemines whether a given database should be hidden according to 'hide_db'
     *
     * @param string $db database name
     */
    private function isHideDb($db): bool
    {
        return ! empty($GLOBALS['cfg']['Server']['hide_db'])
            && preg_match('/' . $GLOBALS['cfg']['Server']['hide_db'] . '/', $db);
    }

    /**
     * Get the list of databases for 'SHOW DATABASES LIKE' queries.
     * If a search clause is set it gets the highest priority while only_db gets
     * the next priority. In case both are empty list of databases determined by
     * GRANTs are used
     *
     * @param string $searchClause search clause
     *
     * @return array array of databases
     */
    private function getDatabasesToSearch($searchClause)
    {
        global $dbi;

        $databases = [];
        if (! empty($searchClause)) {
            $databases = [
                '%' . $dbi->escapeString($searchClause) . '%',
            ];
        } elseif (! empty($GLOBALS['cfg']['Server']['only_db'])) {
            $databases = $GLOBALS['cfg']['Server']['only_db'];
        } elseif (! empty($GLOBALS['dbs_to_test'])) {
            $databases = $GLOBALS['dbs_to_test'];
        }

        sort($databases);

        return $databases;
    }

    /**
     * Returns the WHERE clause depending on the $searchClause parameter
     * and the hide_db directive
     *
     * @param string $columnName   Column name of the column having database names
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return string
     */
    private function getWhereClause($columnName, $searchClause = '')
    {
        global $dbi;

        $whereClause = 'WHERE TRUE ';
        if (! empty($searchClause)) {
            $whereClause .= 'AND ' . Util::backquote($columnName)
                . " LIKE '%";
            $whereClause .= $dbi->escapeString($searchClause);
            $whereClause .= "%' ";
        }

        if (! empty($GLOBALS['cfg']['Server']['hide_db'])) {
            $whereClause .= 'AND ' . Util::backquote($columnName)
                . " NOT REGEXP '"
                . $dbi->escapeString($GLOBALS['cfg']['Server']['hide_db'])
                . "' ";
        }

        if (! empty($GLOBALS['cfg']['Server']['only_db'])) {
            if (is_string($GLOBALS['cfg']['Server']['only_db'])) {
                $GLOBALS['cfg']['Server']['only_db'] = [
                    $GLOBALS['cfg']['Server']['only_db'],
                ];
            }

            $whereClause .= 'AND (';
            $subClauses = [];
            foreach ($GLOBALS['cfg']['Server']['only_db'] as $eachOnlyDb) {
                $subClauses[] = ' ' . Util::backquote($columnName)
                    . " LIKE '"
                    . $dbi->escapeString($eachOnlyDb) . "' ";
            }

            $whereClause .= implode('OR', $subClauses) . ') ';
        }

        return $whereClause;
    }

    /**
     * Returns HTML for control buttons displayed infront of a node
     *
     * @return string HTML for control buttons
     */
    public function getHtmlForControlButtons(): string
    {
        return '';
    }

    /**
     * Returns CSS classes for a node
     *
     * @param bool $match Whether the node matched loaded tree
     *
     * @return string with html classes.
     */
    public function getCssClasses($match): string
    {
        if (! $GLOBALS['cfg']['NavigationTreeEnableExpansion']) {
            return '';
        }

        $result = ['expander'];

        if ($this->isGroup || $match) {
            $result[] = 'loaded';
        }

        if ($this->type == self::CONTAINER) {
            $result[] = 'container';
        }

        return implode(' ', $result);
    }

    /**
     * Returns icon for the node
     *
     * @param bool $match Whether the node matched loaded tree
     *
     * @return string with image name
     */
    public function getIcon($match): string
    {
        if (! $GLOBALS['cfg']['NavigationTreeEnableExpansion']) {
            return '';
        }

        if ($match) {
            $this->visible = true;

            return Generator::getImage('b_minus');
        }

        return Generator::getImage('b_plus', __('Expand/Collapse'));
    }

    /**
     * Gets the count of hidden elements for each database
     *
     * @return array|null array containing the count of hidden elements for each database
     */
    public function getNavigationHidingData()
    {
        global $dbi;

        $navigationItemsHidingFeature = $this->relation->getRelationParameters()->navigationItemsHidingFeature;
        if ($navigationItemsHidingFeature !== null) {
            $navTable = Util::backquote($navigationItemsHidingFeature->database)
                . '.' . Util::backquote($navigationItemsHidingFeature->navigationHiding);
            $sqlQuery = 'SELECT `db_name`, COUNT(*) AS `count` FROM ' . $navTable
                . " WHERE `username`='"
                . $dbi->escapeString($GLOBALS['cfg']['Server']['user']) . "'"
                . ' GROUP BY `db_name`';

            return $dbi->fetchResult($sqlQuery, 'db_name', 'count', DatabaseInterface::CONNECT_CONTROL);
        }

        return null;
    }

    /**
     * @param int    $pos          The offset of the list within the results.
     * @param string $searchClause A string used to filter the results of the query.
     *
     * @return array
     */
    private function getDataFromInfoSchema($pos, $searchClause)
    {
        global $dbi, $cfg;

        $maxItems = $cfg['FirstLevelNavigationItems'];
        if (! $cfg['NavigationTreeEnableGrouping'] || ! $cfg['ShowDatabasesNavigationAsTree']) {
            $query = sprintf(
                'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA` %sORDER BY `SCHEMA_NAME` LIMIT %d, %d',
                $this->getWhereClause('SCHEMA_NAME', $searchClause),
                $pos,
                $maxItems
            );

            return $dbi->fetchResult($query);
        }

        $dbSeparator = $cfg['NavigationTreeDbSeparator'];
        $query = sprintf(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`, (SELECT DB_first_level'
                . ' FROM ( SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, \'%1$s\', 1) DB_first_level'
                . ' FROM INFORMATION_SCHEMA.SCHEMATA %2$s) t'
                . ' ORDER BY DB_first_level ASC LIMIT %3$d, %4$d) t2'
                . ' %2$sAND 1 = LOCATE(CONCAT(DB_first_level, \'%1$s\'),'
                . ' CONCAT(SCHEMA_NAME, \'%1$s\')) ORDER BY SCHEMA_NAME ASC',
            $dbi->escapeString($dbSeparator),
            $this->getWhereClause('SCHEMA_NAME', $searchClause),
            $pos,
            $maxItems
        );

        return $dbi->fetchResult($query);
    }

    /**
     * @param int    $pos          The offset of the list within the results.
     * @param string $searchClause A string used to filter the results of the query.
     *
     * @return array
     */
    private function getDataFromShowDatabases($pos, $searchClause)
    {
        global $dbi, $cfg;

        $maxItems = $cfg['FirstLevelNavigationItems'];
        if (! $cfg['NavigationTreeEnableGrouping'] || ! $cfg['ShowDatabasesNavigationAsTree']) {
            $handle = $dbi->tryQuery(sprintf(
                'SHOW DATABASES %s',
                $this->getWhereClause('Database', $searchClause)
            ));
            if ($handle === false) {
                return [];
            }

            $count = 0;
            if (! $handle->seek($pos)) {
                return [];
            }

            $retval = [];
            while ($arr = $handle->fetchRow()) {
                if ($count >= $maxItems) {
                    break;
                }

                $retval[] = $arr[0];
                $count++;
            }

            return $retval;
        }

        $dbSeparator = $cfg['NavigationTreeDbSeparator'];
        $handle = $dbi->tryQuery(sprintf(
            'SHOW DATABASES %s',
            $this->getWhereClause('Database', $searchClause)
        ));
        $prefixes = [];
        if ($handle !== false) {
            $prefixMap = [];
            $total = $pos + $maxItems;
            while ($arr = $handle->fetchRow()) {
                $prefix = strstr($arr[0], $dbSeparator, true);
                if ($prefix === false) {
                    $prefix = $arr[0];
                }

                $prefixMap[$prefix] = 1;
                if (count($prefixMap) == $total) {
                    break;
                }
            }

            $prefixes = array_slice(array_keys($prefixMap), (int) $pos);
        }

        $subClauses = [];
        foreach ($prefixes as $prefix) {
            $subClauses[] = sprintf(
                ' LOCATE(\'%1$s%2$s\', CONCAT(`Database`, \'%2$s\')) = 1 ',
                $dbi->escapeString((string) $prefix),
                $dbSeparator
            );
        }

        $query = sprintf(
            'SHOW DATABASES %sAND (%s)',
            $this->getWhereClause('Database', $searchClause),
            implode('OR', $subClauses)
        );

        return $dbi->fetchResult($query);
    }

    /**
     * @param int    $pos          The offset of the list within the results.
     * @param string $searchClause A string used to filter the results of the query.
     *
     * @return array
     */
    private function getDataFromShowDatabasesLike($pos, $searchClause)
    {
        global $dbi, $cfg;

        $maxItems = $cfg['FirstLevelNavigationItems'];
        if (! $cfg['NavigationTreeEnableGrouping'] || ! $cfg['ShowDatabasesNavigationAsTree']) {
            $retval = [];
            $count = 0;
            foreach ($this->getDatabasesToSearch($searchClause) as $db) {
                $handle = $dbi->tryQuery(sprintf('SHOW DATABASES LIKE \'%s\'', $db));
                if ($handle === false) {
                    continue;
                }

                while ($arr = $handle->fetchRow()) {
                    if ($this->isHideDb($arr[0])) {
                        continue;
                    }

                    if (in_array($arr[0], $retval)) {
                        continue;
                    }

                    if ($pos <= 0 && $count < $maxItems) {
                        $retval[] = $arr[0];
                        $count++;
                    }

                    $pos--;
                }
            }

            sort($retval);

            return $retval;
        }

        $dbSeparator = $cfg['NavigationTreeDbSeparator'];
        $retval = [];
        $prefixMap = [];
        $total = $pos + $maxItems;
        foreach ($this->getDatabasesToSearch($searchClause) as $db) {
            $handle = $dbi->tryQuery(sprintf('SHOW DATABASES LIKE \'%s\'', $db));
            if ($handle === false) {
                continue;
            }

            while ($arr = $handle->fetchRow()) {
                if ($this->isHideDb($arr[0])) {
                    continue;
                }

                $prefix = strstr($arr[0], $dbSeparator, true);
                if ($prefix === false) {
                    $prefix = $arr[0];
                }

                $prefixMap[$prefix] = 1;
                if (count($prefixMap) == $total) {
                    break 2;
                }
            }
        }

        $prefixes = array_slice(array_keys($prefixMap), $pos);

        foreach ($this->getDatabasesToSearch($searchClause) as $db) {
            $handle = $dbi->tryQuery(sprintf('SHOW DATABASES LIKE \'%s\'', $db));
            if ($handle === false) {
                continue;
            }

            while ($arr = $handle->fetchRow()) {
                if ($this->isHideDb($arr[0])) {
                    continue;
                }

                if (in_array($arr[0], $retval)) {
                    continue;
                }

                foreach ($prefixes as $prefix) {
                    $startsWith = strpos($arr[0] . $dbSeparator, $prefix . $dbSeparator) === 0;
                    if ($startsWith) {
                        $retval[] = $arr[0];
                        break;
                    }
                }
            }
        }

        sort($retval);

        return $retval;
    }
}
