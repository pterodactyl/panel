<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function in_array;
use function intval;

/**
 * Represents a columns node in the navigation tree
 */
class NodeTable extends NodeDatabaseChild
{
    /**
     * For the second IMG tag, used when rendering the node.
     *
     * @var array<string, string>|null
     * @psalm-var array{image: string, title: string}|null
     */
    public $secondIcon;

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
        $icon = $this->addIcon(
            Util::getScriptNameForOption($GLOBALS['cfg']['NavigationTreeDefaultTabTable'], 'table')
        );
        if ($icon !== null) {
            $this->icon = $icon;
        }

        $this->secondIcon = $this->addIcon(
            Util::getScriptNameForOption($GLOBALS['cfg']['NavigationTreeDefaultTabTable2'], 'table')
        );
        $title = (string) Util::getTitleForTarget($GLOBALS['cfg']['DefaultTabTable']);
        $this->title = $title;

        $this->links = [
            'text' => [
                'route' => Util::getUrlForOption($GLOBALS['cfg']['DefaultTabTable'], 'table'),
                'params' => ['pos' => 0, 'db' => null, 'table' => null],
            ],
            'icon' => [
                'route' => Util::getUrlForOption($GLOBALS['cfg']['NavigationTreeDefaultTabTable'], 'table'),
                'params' => ['db' => null, 'table' => null],
            ],
            'second_icon' => [
                'route' => Util::getUrlForOption($GLOBALS['cfg']['NavigationTreeDefaultTabTable2'], 'table'),
                'params' => ['db' => null, 'table' => null],
            ],
            'title' => $this->title,
        ];
        $this->classes = 'nav_node_table';
        $this->urlParamName = 'table';
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('columns' or 'indexes')
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return int
     */
    public function getPresence($type = '', $searchClause = '')
    {
        global $dbi;

        $retval = 0;
        $db = $this->realParent()->realName;
        $table = $this->realName;
        switch ($type) {
            case 'columns':
                if (! $GLOBALS['cfg']['Server']['DisableIS']) {
                    $db = $dbi->escapeString($db);
                    $table = $dbi->escapeString($table);
                    $query = 'SELECT COUNT(*) ';
                    $query .= 'FROM `INFORMATION_SCHEMA`.`COLUMNS` ';
                    $query .= "WHERE `TABLE_NAME`='" . $table . "' ";
                    $query .= "AND `TABLE_SCHEMA`='" . $db . "'";
                    $retval = (int) $dbi->fetchValue($query);
                } else {
                    $db = Util::backquote($db);
                    $table = Util::backquote($table);
                    $query = 'SHOW COLUMNS FROM ' . $table . ' FROM ' . $db . '';
                    $retval = (int) $dbi->queryAndGetNumRows($query);
                }

                break;
            case 'indexes':
                $db = Util::backquote($db);
                $table = Util::backquote($table);
                $query = 'SHOW INDEXES FROM ' . $table . ' FROM ' . $db;
                $retval = (int) $dbi->queryAndGetNumRows($query);
                break;
            case 'triggers':
                if (! $GLOBALS['cfg']['Server']['DisableIS']) {
                    $db = $dbi->escapeString($db);
                    $table = $dbi->escapeString($table);
                    $query = 'SELECT COUNT(*) ';
                    $query .= 'FROM `INFORMATION_SCHEMA`.`TRIGGERS` ';
                    $query .= 'WHERE `EVENT_OBJECT_SCHEMA` '
                    . Util::getCollateForIS() . "='" . $db . "' ";
                    $query .= 'AND `EVENT_OBJECT_TABLE` '
                    . Util::getCollateForIS() . "='" . $table . "'";
                    $retval = (int) $dbi->fetchValue($query);
                } else {
                    $db = Util::backquote($db);
                    $table = $dbi->escapeString($table);
                    $query = 'SHOW TRIGGERS FROM ' . $db . " WHERE `Table` = '" . $table . "'";
                    $retval = (int) $dbi->queryAndGetNumRows($query);
                }

                break;
            default:
                break;
        }

        return $retval;
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
        global $dbi;

        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval = [];
        $db = $this->realParent()->realName;
        $table = $this->realName;
        switch ($type) {
            case 'columns':
                if (! $GLOBALS['cfg']['Server']['DisableIS']) {
                    $db = $dbi->escapeString($db);
                    $table = $dbi->escapeString($table);
                    $query = 'SELECT `COLUMN_NAME` AS `name` ';
                    $query .= ',`COLUMN_KEY` AS `key` ';
                    $query .= ',`DATA_TYPE` AS `type` ';
                    $query .= ',`COLUMN_DEFAULT` AS `default` ';
                    $query .= ",IF (`IS_NULLABLE` = 'NO', '', 'nullable') AS `nullable` ";
                    $query .= 'FROM `INFORMATION_SCHEMA`.`COLUMNS` ';
                    $query .= "WHERE `TABLE_NAME`='" . $table . "' ";
                    $query .= "AND `TABLE_SCHEMA`='" . $db . "' ";
                    $query .= 'ORDER BY `COLUMN_NAME` ASC ';
                    $query .= 'LIMIT ' . intval($pos) . ', ' . $maxItems;
                    $retval = $dbi->fetchResult($query);
                    break;
                }

                $db = Util::backquote($db);
                $table = Util::backquote($table);
                $query = 'SHOW COLUMNS FROM ' . $table . ' FROM ' . $db;
                $handle = $dbi->tryQuery($query);
                if ($handle === false) {
                    break;
                }

                $count = 0;
                if ($handle->seek($pos)) {
                    while ($arr = $handle->fetchAssoc()) {
                        if ($count >= $maxItems) {
                            break;
                        }

                        $retval[] = [
                            'name' => $arr['Field'],
                            'key' => $arr['Key'],
                            'type' => Util::extractColumnSpec($arr['Type'])['type'],
                            'default' => $arr['Default'],
                            'nullable' => ($arr['Null'] === 'NO' ? '' : 'nullable'),
                        ];
                        $count++;
                    }
                }

                break;
            case 'indexes':
                $db = Util::backquote($db);
                $table = Util::backquote($table);
                $query = 'SHOW INDEXES FROM ' . $table . ' FROM ' . $db;
                $handle = $dbi->tryQuery($query);
                if ($handle === false) {
                    break;
                }

                $count = 0;
                foreach ($handle as $arr) {
                    if (in_array($arr['Key_name'], $retval)) {
                        continue;
                    }

                    if ($pos <= 0 && $count < $maxItems) {
                        $retval[] = $arr['Key_name'];
                        $count++;
                    }

                    $pos--;
                }

                break;
            case 'triggers':
                if (! $GLOBALS['cfg']['Server']['DisableIS']) {
                    $db = $dbi->escapeString($db);
                    $table = $dbi->escapeString($table);
                    $query = 'SELECT `TRIGGER_NAME` AS `name` ';
                    $query .= 'FROM `INFORMATION_SCHEMA`.`TRIGGERS` ';
                    $query .= 'WHERE `EVENT_OBJECT_SCHEMA` '
                    . Util::getCollateForIS() . "='" . $db . "' ";
                    $query .= 'AND `EVENT_OBJECT_TABLE` '
                    . Util::getCollateForIS() . "='" . $table . "' ";
                    $query .= 'ORDER BY `TRIGGER_NAME` ASC ';
                    $query .= 'LIMIT ' . intval($pos) . ', ' . $maxItems;
                    $retval = $dbi->fetchResult($query);
                    break;
                }

                $db = Util::backquote($db);
                $table = $dbi->escapeString($table);
                $query = 'SHOW TRIGGERS FROM ' . $db . " WHERE `Table` = '" . $table . "'";
                $handle = $dbi->tryQuery($query);
                if ($handle === false) {
                    break;
                }

                $count = 0;
                if ($handle->seek($pos)) {
                    while ($arr = $handle->fetchAssoc()) {
                        if ($count >= $maxItems) {
                            break;
                        }

                        $retval[] = $arr['Trigger'];
                        $count++;
                    }
                }

                break;
            default:
                break;
        }

        return $retval;
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType()
    {
        return 'table';
    }

    /**
     * Add an icon to navigation tree
     *
     * @param string $page Page name to redirect
     *
     * @return array<string, string>|null
     * @psalm-return array{image: string, title: string}|null
     */
    private function addIcon(string $page): ?array
    {
        if (empty($page)) {
            return null;
        }

        switch ($page) {
            case Url::getFromRoute('/table/structure'):
                return ['image' => 'b_props', 'title' => __('Structure')];

            case Url::getFromRoute('/table/search'):
                return ['image' => 'b_search', 'title' => __('Search')];

            case Url::getFromRoute('/table/change'):
                return ['image' => 'b_insrow', 'title' => __('Insert')];

            case Url::getFromRoute('/table/sql'):
                return ['image' => 'b_sql', 'title' => __('SQL')];

            case Url::getFromRoute('/sql'):
                return ['image' => 'b_browse', 'title' => __('Browse')];
        }

        return null;
    }
}
