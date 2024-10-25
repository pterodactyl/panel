<?php
/**
 * functions for displaying the sql query form
 *
 * @usedby  /server/sql
 * @usedby  /database/sql
 * @usedby  /table/sql
 * @usedby  /table/structure
 * @usedby  /table/tracking
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function htmlspecialchars;
use function sprintf;
use function str_contains;
use function strlen;

/**
 * PhpMyAdmin\SqlQueryForm class
 */
class SqlQueryForm
{
    /** @var Template */
    private $template;

    /**
     * @param Template $template Template object
     */
    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    /**
     * return HTML for the sql query boxes
     *
     * @param bool|string $query       query to display in the textarea
     *                                 or true to display last executed
     * @param bool|string $display_tab sql|full|false
     *                                 what part to display
     *                                 false if not inside querywindow
     * @param string      $delimiter   delimiter
     *
     * @return string
     *
     * @usedby  /server/sql
     * @usedby  /database/sql
     * @usedby  /table/sql
     * @usedby  /table/structure
     * @usedby  /table/tracking
     */
    public function getHtml(
        string $db,
        string $table,
        $query = true,
        $display_tab = false,
        $delimiter = ';'
    ) {
        global $dbi;

        if (! $display_tab) {
            $display_tab = 'full';
        }

        // query to show
        if ($query === true) {
            $query = $GLOBALS['sql_query'];
            if (empty($query) && (isset($_GET['show_query']) || isset($_POST['show_query']))) {
                $query = $_GET['sql_query'] ?? $_POST['sql_query'] ?? '';
            }
        }

        if (strlen($db) === 0) {
            // prepare for server related
            $goto = empty($GLOBALS['goto']) ? Url::getFromRoute('/server/sql') : $GLOBALS['goto'];
        } elseif (strlen($table) === 0) {
            // prepare for db related
            $goto = empty($GLOBALS['goto']) ? Url::getFromRoute('/database/sql') : $GLOBALS['goto'];
        } else {
            $goto = empty($GLOBALS['goto']) ? Url::getFromRoute('/table/sql') : $GLOBALS['goto'];
        }

        if ($display_tab === 'full' || $display_tab === 'sql') {
            [$legend, $query, $columns_list] = $this->init($query);
        }

        $relation = new Relation($dbi);
        $bookmarkFeature = $relation->getRelationParameters()->bookmarkFeature;

        $bookmarks = [];
        if ($display_tab === 'full' && $bookmarkFeature !== null) {
            $bookmark_list = Bookmark::getList($bookmarkFeature, $dbi, $GLOBALS['cfg']['Server']['user'], $db);

            foreach ($bookmark_list as $bookmarkItem) {
                $bookmarks[] = [
                    'id' => $bookmarkItem->getId(),
                    'variable_count' => $bookmarkItem->getVariableCount(),
                    'label' => $bookmarkItem->getLabel(),
                    'is_shared' => empty($bookmarkItem->getUser()),
                ];
            }
        }

        return $this->template->render('sql/query', [
            'legend' => $legend ?? '',
            'textarea_cols' => $GLOBALS['cfg']['TextareaCols'],
            'textarea_rows' => $GLOBALS['cfg']['TextareaRows'],
            'textarea_auto_select' => $GLOBALS['cfg']['TextareaAutoSelect'],
            'columns_list' => $columns_list ?? [],
            'codemirror_enable' => $GLOBALS['cfg']['CodemirrorEnable'],
            'has_bookmark' => $bookmarkFeature !== null,
            'delimiter' => $delimiter,
            'retain_query_box' => $GLOBALS['cfg']['RetainQueryBox'] !== false,
            'is_upload' => $GLOBALS['config']->get('enable_upload'),
            'db' => $db,
            'table' => $table,
            'goto' => $goto,
            'query' => $query,
            'display_tab' => $display_tab,
            'bookmarks' => $bookmarks,
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
        ]);
    }

    /**
     * Get initial values for Sql Query Form Insert
     *
     * @param string $query query to display in the textarea
     *
     * @return array ($legend, $query, $columns_list)
     */
    public function init($query)
    {
        global $dbi;

        $columns_list = [];
        if (strlen($GLOBALS['db']) === 0) {
            // prepare for server related
            $legend = sprintf(
                __('Run SQL query/queries on server “%s”'),
                htmlspecialchars(
                    ! empty($GLOBALS['cfg']['Servers'][$GLOBALS['server']]['verbose'])
                    ? $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['verbose']
                    : $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['host']
                )
            );
        } elseif (strlen($GLOBALS['table']) === 0) {
            // prepare for db related
            $db = $GLOBALS['db'];
            // if you want navigation:
            $scriptName = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
            $tmp_db_link = '<a href="' . $scriptName
                . Url::getCommon(['db' => $db], ! str_contains($scriptName, '?') ? '?' : '&')
                . '">';
            $tmp_db_link .= htmlspecialchars($db) . '</a>';
            $legend = sprintf(__('Run SQL query/queries on database %s'), $tmp_db_link);
            if (empty($query)) {
                $query = Util::expandUserString($GLOBALS['cfg']['DefaultQueryDatabase'], 'backquote');
            }
        } else {
            $db = $GLOBALS['db'];
            $table = $GLOBALS['table'];
            // Get the list and number of fields
            // we do a try_query here, because we could be in the query window,
            // trying to synchronize and the table has not yet been created
            $columns_list = $dbi->getColumns($db, $GLOBALS['table'], true);

            $scriptName = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
            $tmp_tbl_link = '<a href="' . $scriptName . Url::getCommon(['db' => $db, 'table' => $table], '&') . '">';
            $tmp_tbl_link .= htmlspecialchars($db) . '.' . htmlspecialchars($table) . '</a>';
            $legend = sprintf(__('Run SQL query/queries on table %s'), $tmp_tbl_link);
            if (empty($query)) {
                $query = Util::expandUserString($GLOBALS['cfg']['DefaultQueryTable'], 'backquote');
            }
        }

        $legend .= ': ' . MySQLDocumentation::show('SELECT');

        return [
            $legend,
            $query,
            $columns_list,
        ];
    }
}
