<?php
/**
 * Recent and Favorite table list handling
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;

use function __;
use function array_key_exists;
use function array_merge;
use function array_pop;
use function array_unique;
use function array_unshift;
use function count;
use function is_string;
use function json_decode;
use function json_encode;
use function max;
use function md5;
use function ucfirst;

use const SORT_REGULAR;

/**
 * Handles the recently used and favorite tables.
 *
 * @TODO Change the release version in table pma_recent
 * (#recent in documentation)
 */
class RecentFavoriteTable
{
    /** @var Template */
    public $template;

    /**
     * Reference to session variable containing recently used or favorite tables.
     *
     * @var array
     */
    private $tables;

    /**
     * Defines type of action, Favorite or Recent table.
     *
     * @var string
     */
    private $tableType;

    /**
     * RecentFavoriteTable instances.
     *
     * @var array<string,RecentFavoriteTable>
     */
    private static $instances = [];

    /** @var Relation */
    private $relation;

    /**
     * Creates a new instance of RecentFavoriteTable
     *
     * @param Template $template Template object
     * @param string   $type     the table type
     */
    private function __construct(Template $template, string $type)
    {
        $this->template = $template;

        global $dbi;

        $this->relation = new Relation($dbi);
        $this->tableType = $type;
        $server_id = $GLOBALS['server'];
        if (! isset($_SESSION['tmpval'][$this->tableType . 'Tables'][$server_id])) {
            $_SESSION['tmpval'][$this->tableType . 'Tables'][$server_id] = $this->getPmaTable()
                ? $this->getFromDb()
                : [];
        }

        $this->tables =& $_SESSION['tmpval'][$this->tableType . 'Tables'][$server_id];
    }

    /**
     * Returns class instance.
     *
     * @param string $type the table type
     * @psalm-param 'favorite'|'recent' $type
     */
    public static function getInstance(string $type): RecentFavoriteTable
    {
        if (! array_key_exists($type, self::$instances)) {
            $template = new Template();
            self::$instances[$type] = new RecentFavoriteTable($template, $type);
        }

        return self::$instances[$type];
    }

    /**
     * Returns the recent/favorite tables array
     *
     * @return array
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * Returns recently used tables or favorite from phpMyAdmin database.
     *
     * @return array
     */
    public function getFromDb(): array
    {
        global $dbi;

        // Read from phpMyAdmin database, if recent tables is not in session
        $sql_query = ' SELECT `tables` FROM ' . $this->getPmaTable() .
            " WHERE `username` = '" . $dbi->escapeString($GLOBALS['cfg']['Server']['user']) . "'";

        $result = $dbi->tryQueryAsControlUser($sql_query);
        if ($result) {
            $value = $result->fetchValue();
            if (is_string($value)) {
                return json_decode($value, true);
            }
        }

        return [];
    }

    /**
     * Save recent/favorite tables into phpMyAdmin database.
     *
     * @return true|Message
     */
    public function saveToDb()
    {
        global $dbi;

        $username = $GLOBALS['cfg']['Server']['user'];
        $sql_query = ' REPLACE INTO ' . $this->getPmaTable() . ' (`username`, `tables`)' .
                " VALUES ('" . $dbi->escapeString($username) . "', '"
                . $dbi->escapeString(
                    json_encode($this->tables)
                ) . "')";

        $success = $dbi->tryQuery($sql_query, DatabaseInterface::CONNECT_CONTROL);

        if (! $success) {
            $error_msg = '';
            switch ($this->tableType) {
                case 'recent':
                    $error_msg = __('Could not save recent table!');
                    break;

                case 'favorite':
                    $error_msg = __('Could not save favorite table!');
                    break;
            }

            $message = Message::error($error_msg);
            $message->addMessage(
                Message::rawError($dbi->getError(DatabaseInterface::CONNECT_CONTROL)),
                '<br><br>'
            );

            return $message;
        }

        return true;
    }

    /**
     * Trim recent.favorite table according to the
     * NumRecentTables/NumFavoriteTables configuration.
     */
    public function trim(): bool
    {
        $max = max(
            $GLOBALS['cfg']['Num' . ucfirst($this->tableType) . 'Tables'],
            0
        );
        $trimmingOccurred = count($this->tables) > $max;
        while (count($this->tables) > $max) {
            array_pop($this->tables);
        }

        return $trimmingOccurred;
    }

    /**
     * Return HTML ul.
     */
    public function getHtmlList(): string
    {
        if (count($this->tables)) {
            if ($this->tableType === 'recent') {
                $tables = [];
                foreach ($this->tables as $table) {
                    $tables[] = [
                        'db' => $table['db'],
                        'table' => $table['table'],
                    ];
                }

                return $this->template->render('recent_favorite_table_recent', ['tables' => $tables]);
            }

            $tables = [];
            foreach ($this->tables as $table) {
                $removeParameters = [
                    'db' => $table['db'],
                    'ajax_request' => true,
                    'favorite_table' => $table['table'],
                    'remove_favorite' => true,
                ];
                $tableParameters = [
                    'db' => $table['db'],
                    'table' => $table['table'],
                    'md5' => md5($table['db'] . '.' . $table['table']),
                ];

                $tables[] = [
                    'remove_parameters' => $removeParameters,
                    'table_parameters' => $tableParameters,
                ];
            }

            return $this->template->render('recent_favorite_table_favorite', ['tables' => $tables]);
        }

        return $this->template->render('recent_favorite_table_no_tables', [
            'is_recent' => $this->tableType === 'recent',
        ]);
    }

    public function getHtml(): string
    {
        $html = '<div class="drop_list">';
        if ($this->tableType === 'recent') {
            $html .= '<button title="' . __('Recent tables')
                . '" class="drop_button btn">'
                . __('Recent') . '</button><ul id="pma_recent_list">';
        } else {
            $html .= '<button title="' . __('Favorite tables')
                . '" class="drop_button btn">'
                . __('Favorites') . '</button><ul id="pma_favorite_list">';
        }

        $html .= $this->getHtmlList();
        $html .= '</ul></div>';

        return $html;
    }

    /**
     * Add recently used or favorite tables.
     *
     * @param string $db    database name where the table is located
     * @param string $table table name
     *
     * @return true|Message True if success, Message if not
     */
    public function add($db, $table)
    {
        global $dbi;

        // If table does not exist, do not add._getPmaTable()
        if (! $dbi->getColumns($db, $table)) {
            return true;
        }

        $table_arr = [];
        $table_arr['db'] = $db;
        $table_arr['table'] = $table;

        // add only if this is new table
        if (! isset($this->tables[0]) || $this->tables[0] != $table_arr) {
            array_unshift($this->tables, $table_arr);
            $this->tables = array_merge(array_unique($this->tables, SORT_REGULAR));
            $this->trim();
            if ($this->getPmaTable()) {
                return $this->saveToDb();
            }
        }

        return true;
    }

    /**
     * Removes recent/favorite tables that don't exist.
     *
     * @param string $db    database
     * @param string $table table
     *
     * @return bool|Message True if invalid and removed, False if not invalid,
     * Message if error while removing
     */
    public function removeIfInvalid($db, $table)
    {
        global $dbi;

        foreach ($this->tables as $tbl) {
            if ($tbl['db'] != $db || $tbl['table'] != $table) {
                continue;
            }

            // TODO Figure out a better way to find the existence of a table
            if (! $dbi->getColumns($tbl['db'], $tbl['table'])) {
                return $this->remove($tbl['db'], $tbl['table']);
            }
        }

        return false;
    }

    /**
     * Remove favorite tables.
     *
     * @param string $db    database name where the table is located
     * @param string $table table name
     *
     * @return true|Message True if success, Message if not
     */
    public function remove($db, $table)
    {
        foreach ($this->tables as $key => $value) {
            if ($value['db'] != $db || $value['table'] != $table) {
                continue;
            }

            unset($this->tables[$key]);
        }

        if ($this->getPmaTable()) {
            return $this->saveToDb();
        }

        return true;
    }

    /**
     * Generate Html for sync Favorite tables anchor. (from localStorage to pmadb)
     */
    public function getHtmlSyncFavoriteTables(): string
    {
        $retval = '';
        $server_id = $GLOBALS['server'];
        if ($server_id == 0) {
            return '';
        }

        $relationParameters = $this->relation->getRelationParameters();
        // Not to show this once list is synchronized.
        if (
            $relationParameters->favoriteTablesFeature !== null
            && ! isset($_SESSION['tmpval']['favorites_synced'][$server_id])
        ) {
            $url = Url::getFromRoute('/database/structure/favorite-table', [
                'ajax_request' => true,
                'favorite_table' => true,
                'sync_favorite_tables' => true,
            ]);
            $retval = '<a class="hide" id="sync_favorite_tables"';
            $retval .= ' href="' . $url . '"></a>';
        }

        return $retval;
    }

    /**
     * Generate Html to update recent tables.
     */
    public static function getHtmlUpdateRecentTables(): string
    {
        return '<a class="hide" id="update_recent_tables" href="'
                    . Url::getFromRoute('/recent-table', [
                        'ajax_request' => true,
                        'recent_table' => true,
                    ])
                . '"></a>';
    }

    /**
     * Return the name of the configuration storage table
     *
     * @return string|null pma table name
     */
    private function getPmaTable(): ?string
    {
        $relationParameters = $this->relation->getRelationParameters();
        if ($this->tableType === 'recent' && $relationParameters->recentlyUsedTablesFeature !== null) {
            return Util::backquote($relationParameters->recentlyUsedTablesFeature->database)
                . '.' . Util::backquote($relationParameters->recentlyUsedTablesFeature->recent);
        }

        if ($this->tableType === 'favorite' && $relationParameters->favoriteTablesFeature !== null) {
            return Util::backquote($relationParameters->favoriteTablesFeature->database)
                . '.' . Util::backquote($relationParameters->favoriteTablesFeature->favorite);
        }

        return null;
    }
}
