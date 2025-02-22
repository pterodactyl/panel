<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_slice;
use function in_array;
use function substr;
use function usort;

/**
 * Represents a database node in the navigation tree
 */
class NodeDatabase extends Node
{
    /**
     * The number of hidden items in this database
     *
     * @var int
     */
    protected $hiddenCount = 0;

    /**
     * Initialises the class
     *
     * @param string $name    An identifier for the new node
     * @param int    $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $isGroup Whether this object has been created
     *                        while grouping nodes
     */
    public function __construct($name, $type = Node::OBJECT, $isGroup = false)
    {
        parent::__construct($name, $type, $isGroup);
        $this->icon = ['image' => 's_db', 'title' => __('Database operations')];

        $this->links = [
            'text' => [
                'route' => Util::getUrlForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database'),
                'params' => ['db' => null],
            ],
            'icon' => ['route' => '/database/operations', 'params' => ['db' => null]],
            'title' => __('Structure'),
        ];

        $this->classes = 'database';
        $this->urlParamName = 'db';
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param string $searchClause A string used to filter the results of
     *                             the query
     * @param bool   $singleItem   Whether to get presence of a single known
     *                             item or false in none
     *
     * @return int
     */
    public function getPresence($type = '', $searchClause = '', $singleItem = false)
    {
        $retval = 0;
        switch ($type) {
            case 'tables':
                $retval = $this->getTableCount($searchClause, $singleItem);
                break;
            case 'views':
                $retval = $this->getViewCount($searchClause, $singleItem);
                break;
            case 'procedures':
                $retval = $this->getProcedureCount($searchClause, $singleItem);
                break;
            case 'functions':
                $retval = $this->getFunctionCount($searchClause, $singleItem);
                break;
            case 'events':
                $retval = $this->getEventCount($searchClause, $singleItem);
                break;
            default:
                break;
        }

        return $retval;
    }

    /**
     * Returns the number of tables or views present inside this database
     *
     * @param string $which        tables|views
     * @param string $searchClause A string used to filter the results of
     *                             the query
     * @param bool   $singleItem   Whether to get presence of a single known
     *                             item or false in none
     *
     * @return int
     */
    private function getTableOrViewCount($which, $searchClause, $singleItem)
    {
        global $dbi;

        $db = $this->realName;
        if ($which === 'tables') {
            $condition = 'IN';
        } else {
            $condition = 'NOT IN';
        }

        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $db = $dbi->escapeString($db);
            $query = 'SELECT COUNT(*) ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`TABLES` ';
            $query .= "WHERE `TABLE_SCHEMA`='" . $db . "' ";
            $query .= 'AND `TABLE_TYPE` ' . $condition . "('BASE TABLE', 'SYSTEM VERSIONED') ";
            if (! empty($searchClause)) {
                $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, $singleItem, 'TABLE_NAME');
            }

            $retval = (int) $dbi->fetchValue($query);
        } else {
            $query = 'SHOW FULL TABLES FROM ';
            $query .= Util::backquote($db);
            $query .= ' WHERE `Table_type` ' . $condition . "('BASE TABLE', 'SYSTEM VERSIONED') ";
            if (! empty($searchClause)) {
                $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, $singleItem, 'Tables_in_' . $db);
            }

            $retval = $dbi->queryAndGetNumRows($query);
        }

        return $retval;
    }

    /**
     * Returns the number of tables present inside this database
     *
     * @param string $searchClause A string used to filter the results of
     *                             the query
     * @param bool   $singleItem   Whether to get presence of a single known
     *                             item or false in none
     *
     * @return int
     */
    private function getTableCount($searchClause, $singleItem)
    {
        return $this->getTableOrViewCount('tables', $searchClause, $singleItem);
    }

    /**
     * Returns the number of views present inside this database
     *
     * @param string $searchClause A string used to filter the results of
     *                             the query
     * @param bool   $singleItem   Whether to get presence of a single known
     *                             item or false in none
     *
     * @return int
     */
    private function getViewCount($searchClause, $singleItem)
    {
        return $this->getTableOrViewCount('views', $searchClause, $singleItem);
    }

    /**
     * Returns the number of procedures present inside this database
     *
     * @param string $searchClause A string used to filter the results of
     *                             the query
     * @param bool   $singleItem   Whether to get presence of a single known
     *                             item or false in none
     *
     * @return int
     */
    private function getProcedureCount($searchClause, $singleItem)
    {
        global $dbi;

        $db = $this->realName;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $db = $dbi->escapeString($db);
            $query = 'SELECT COUNT(*) ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`ROUTINES` ';
            $query .= 'WHERE `ROUTINE_SCHEMA` '
                . Util::getCollateForIS() . "='" . $db . "'";
            $query .= "AND `ROUTINE_TYPE`='PROCEDURE' ";
            if (! empty($searchClause)) {
                $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, $singleItem, 'ROUTINE_NAME');
            }

            $retval = (int) $dbi->fetchValue($query);
        } else {
            $db = $dbi->escapeString($db);
            $query = "SHOW PROCEDURE STATUS WHERE `Db`='" . $db . "' ";
            if (! empty($searchClause)) {
                $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, $singleItem, 'Name');
            }

            $retval = $dbi->queryAndGetNumRows($query);
        }

        return $retval;
    }

    /**
     * Returns the number of functions present inside this database
     *
     * @param string $searchClause A string used to filter the results of
     *                             the query
     * @param bool   $singleItem   Whether to get presence of a single known
     *                             item or false in none
     *
     * @return int
     */
    private function getFunctionCount($searchClause, $singleItem)
    {
        global $dbi;

        $db = $this->realName;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $db = $dbi->escapeString($db);
            $query = 'SELECT COUNT(*) ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`ROUTINES` ';
            $query .= 'WHERE `ROUTINE_SCHEMA` '
                . Util::getCollateForIS() . "='" . $db . "' ";
            $query .= "AND `ROUTINE_TYPE`='FUNCTION' ";
            if (! empty($searchClause)) {
                $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, $singleItem, 'ROUTINE_NAME');
            }

            $retval = (int) $dbi->fetchValue($query);
        } else {
            $db = $dbi->escapeString($db);
            $query = "SHOW FUNCTION STATUS WHERE `Db`='" . $db . "' ";
            if (! empty($searchClause)) {
                $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, $singleItem, 'Name');
            }

            $retval = $dbi->queryAndGetNumRows($query);
        }

        return $retval;
    }

    /**
     * Returns the number of events present inside this database
     *
     * @param string $searchClause A string used to filter the results of
     *                             the query
     * @param bool   $singleItem   Whether to get presence of a single known
     *                             item or false in none
     *
     * @return int
     */
    private function getEventCount($searchClause, $singleItem)
    {
        global $dbi;

        $db = $this->realName;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $db = $dbi->escapeString($db);
            $query = 'SELECT COUNT(*) ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`EVENTS` ';
            $query .= 'WHERE `EVENT_SCHEMA` '
                . Util::getCollateForIS() . "='" . $db . "' ";
            if (! empty($searchClause)) {
                $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, $singleItem, 'EVENT_NAME');
            }

            $retval = (int) $dbi->fetchValue($query);
        } else {
            $db = Util::backquote($db);
            $query = 'SHOW EVENTS FROM ' . $db . ' ';
            if (! empty($searchClause)) {
                $query .= 'WHERE ' . $this->getWhereClauseForSearch($searchClause, $singleItem, 'Name');
            }

            $retval = $dbi->queryAndGetNumRows($query);
        }

        return $retval;
    }

    /**
     * Returns the WHERE clause for searching inside a database
     *
     * @param string $searchClause A string used to filter the results of the query
     * @param bool   $singleItem   Whether to get presence of a single known item
     * @param string $columnName   Name of the column in the result set to match
     *
     * @return string WHERE clause for searching
     */
    private function getWhereClauseForSearch(
        $searchClause,
        $singleItem,
        $columnName
    ) {
        global $dbi;

        $query = '';
        if ($singleItem) {
            $query .= Util::backquote($columnName) . ' = ';
            $query .= "'" . $dbi->escapeString($searchClause) . "'";
        } else {
            $query .= Util::backquote($columnName) . ' LIKE ';
            $query .= "'%" . $dbi->escapeString($searchClause)
                . "%'";
        }

        return $query;
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
        $pos = (int) $pos;
        $retval = [];
        switch ($type) {
            case 'tables':
                $retval = $this->getTables($pos, $searchClause);
                break;
            case 'views':
                $retval = $this->getViews($pos, $searchClause);
                break;
            case 'procedures':
                $retval = $this->getProcedures($pos, $searchClause);
                break;
            case 'functions':
                $retval = $this->getFunctions($pos, $searchClause);
                break;
            case 'events':
                $retval = $this->getEvents($pos, $searchClause);
                break;
            default:
                break;
        }

        // Remove hidden items so that they are not displayed in navigation tree
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->navigationItemsHidingFeature !== null) {
            $hiddenItems = $this->getHiddenItems(substr($type, 0, -1));
            foreach ($retval as $key => $item) {
                if (! in_array($item, $hiddenItems)) {
                    continue;
                }

                unset($retval[$key]);
            }
        }

        return $retval;
    }

    /**
     * Return list of hidden items of given type
     *
     * @param string $type The type of items we are looking for
     *                     ('table', 'function', 'group', etc.)
     *
     * @return array Array containing hidden items of given type
     */
    public function getHiddenItems($type)
    {
        global $dbi;

        $db = $this->realName;
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->navigationItemsHidingFeature === null || $relationParameters->user === null) {
            return [];
        }

        $navTable = Util::backquote($relationParameters->navigationItemsHidingFeature->database)
            . '.' . Util::backquote($relationParameters->navigationItemsHidingFeature->navigationHiding);
        $sqlQuery = 'SELECT `item_name` FROM ' . $navTable
            . " WHERE `username`='" . $relationParameters->user . "'"
            . " AND `item_type`='" . $type
            . "' AND `db_name`='" . $dbi->escapeString($db)
            . "'";
        $result = $dbi->tryQueryAsControlUser($sqlQuery);
        if ($result) {
            return $result->fetchAllColumn();
        }

        return [];
    }

    /**
     * Returns the list of tables or views inside this database
     *
     * @param string $which        tables|views
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function getTablesOrViews($which, int $pos, $searchClause)
    {
        global $dbi;

        if ($which === 'tables') {
            $condition = 'IN';
        } else {
            $condition = 'NOT IN';
        }

        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval = [];
        $db = $this->realName;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $escdDb = $dbi->escapeString($db);
            $query = 'SELECT `TABLE_NAME` AS `name` ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`TABLES` ';
            $query .= "WHERE `TABLE_SCHEMA`='" . $escdDb . "' ";
            $query .= 'AND `TABLE_TYPE` ' . $condition . "('BASE TABLE', 'SYSTEM VERSIONED') ";
            if (! empty($searchClause)) {
                $query .= "AND `TABLE_NAME` LIKE '%";
                $query .= $dbi->escapeString($searchClause);
                $query .= "%'";
            }

            $query .= 'ORDER BY `TABLE_NAME` ASC ';
            $retval = $dbi->fetchResult($query);
        } else {
            $query = ' SHOW FULL TABLES FROM ';
            $query .= Util::backquote($db);
            $query .= ' WHERE `Table_type` ' . $condition . "('BASE TABLE', 'SYSTEM VERSIONED') ";
            if (! empty($searchClause)) {
                $query .= 'AND ' . Util::backquote('Tables_in_' . $db);
                $query .= " LIKE '%" . $dbi->escapeString($searchClause);
                $query .= "%'";
            }

            $handle = $dbi->tryQuery($query);
            if ($handle !== false) {
                $retval = $handle->fetchAllColumn();
            }
        }

        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($retval, 'strnatcasecmp');
        }

        return array_slice($retval, $pos, $maxItems);
    }

    /**
     * Returns the list of tables inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function getTables(int $pos, $searchClause)
    {
        return $this->getTablesOrViews('tables', $pos, $searchClause);
    }

    /**
     * Returns the list of views inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function getViews(int $pos, $searchClause)
    {
        return $this->getTablesOrViews('views', $pos, $searchClause);
    }

    /**
     * Returns the list of procedures or functions inside this database
     *
     * @param string $routineType  PROCEDURE|FUNCTION
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function getRoutines($routineType, $pos, $searchClause)
    {
        global $dbi;

        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval = [];
        $db = $this->realName;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $escdDb = $dbi->escapeString($db);
            $query = 'SELECT `ROUTINE_NAME` AS `name` ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`ROUTINES` ';
            $query .= 'WHERE `ROUTINE_SCHEMA` '
                . Util::getCollateForIS() . "='" . $escdDb . "'";
            $query .= "AND `ROUTINE_TYPE`='" . $routineType . "' ";
            if (! empty($searchClause)) {
                $query .= "AND `ROUTINE_NAME` LIKE '%";
                $query .= $dbi->escapeString($searchClause);
                $query .= "%'";
            }

            $query .= 'ORDER BY `ROUTINE_NAME` ASC ';
            $retval = $dbi->fetchResult($query);
        } else {
            $escdDb = $dbi->escapeString($db);
            $query = 'SHOW ' . $routineType . " STATUS WHERE `Db`='" . $escdDb . "' ";
            if (! empty($searchClause)) {
                $query .= "AND `Name` LIKE '%";
                $query .= $dbi->escapeString($searchClause);
                $query .= "%'";
            }

            $handle = $dbi->tryQuery($query);
            if ($handle !== false) {
                while ($arr = $handle->fetchAssoc()) {
                    $retval[] = $arr['Name'];
                }
            }
        }

        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($retval, 'strnatcasecmp');
        }

        return array_slice($retval, $pos, $maxItems);
    }

    /**
     * Returns the list of procedures inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function getProcedures($pos, $searchClause)
    {
        return $this->getRoutines('PROCEDURE', $pos, $searchClause);
    }

    /**
     * Returns the list of functions inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function getFunctions($pos, $searchClause)
    {
        return $this->getRoutines('FUNCTION', $pos, $searchClause);
    }

    /**
     * Returns the list of events inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function getEvents($pos, $searchClause)
    {
        global $dbi;

        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval = [];
        $db = $this->realName;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $escdDb = $dbi->escapeString($db);
            $query = 'SELECT `EVENT_NAME` AS `name` ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`EVENTS` ';
            $query .= 'WHERE `EVENT_SCHEMA` '
                . Util::getCollateForIS() . "='" . $escdDb . "' ";
            if (! empty($searchClause)) {
                $query .= "AND `EVENT_NAME` LIKE '%";
                $query .= $dbi->escapeString($searchClause);
                $query .= "%'";
            }

            $query .= 'ORDER BY `EVENT_NAME` ASC ';
            $retval = $dbi->fetchResult($query);
        } else {
            $escdDb = Util::backquote($db);
            $query = 'SHOW EVENTS FROM ' . $escdDb . ' ';
            if (! empty($searchClause)) {
                $query .= "WHERE `Name` LIKE '%";
                $query .= $dbi->escapeString($searchClause);
                $query .= "%'";
            }

            $handle = $dbi->tryQuery($query);
            if ($handle !== false) {
                while ($arr = $handle->fetchAssoc()) {
                    $retval[] = $arr['Name'];
                }
            }
        }

        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($retval, 'strnatcasecmp');
        }

        return array_slice($retval, $pos, $maxItems);
    }

    /**
     * Returns HTML for control buttons displayed infront of a node
     *
     * @return string HTML for control buttons
     */
    public function getHtmlForControlButtons(): string
    {
        $ret = '';
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->navigationItemsHidingFeature !== null) {
            if ($this->hiddenCount > 0) {
                $params = [
                    'showUnhideDialog' => true,
                    'dbName' => $this->realName,
                ];
                $ret = '<span class="dbItemControls">'
                    . '<a href="' . Url::getFromRoute('/navigation') . '" data-post="'
                    . Url::getCommon($params, '', false) . '"'
                    . ' class="showUnhide ajax">'
                    . Generator::getImage(
                        'show',
                        __('Show hidden items')
                    )
                    . '</a></span>';
            }
        }

        return $ret;
    }

    /**
     * Sets the number of hidden items in this database
     *
     * @param int $count hidden item count
     */
    public function setHiddenCount($count): void
    {
        $this->hiddenCount = $count;
    }

    /**
     * Returns the number of hidden items in this database
     *
     * @return int hidden item count
     */
    public function getHiddenCount()
    {
        return $this->hiddenCount;
    }
}
