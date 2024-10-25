<?php

declare(strict_types=1);

namespace PhpMyAdmin\Display;

use PhpMyAdmin\Config\SpecialSchemaLinks;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Octetstream_Sql;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_Json;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_Sql;
use PhpMyAdmin\Plugins\Transformations\Text_Plain_Link;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Sql;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\Gis;

use function __;
use function _pgettext;
use function array_filter;
use function array_keys;
use function array_merge;
use function array_shift;
use function bin2hex;
use function ceil;
use function class_exists;
use function count;
use function explode;
use function file_exists;
use function floor;
use function htmlspecialchars;
use function implode;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use function json_encode;
use function max;
use function mb_check_encoding;
use function mb_strlen;
use function mb_strpos;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function md5;
use function mt_getrandmax;
use function pack;
use function preg_match;
use function preg_replace;
use function random_int;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function strcasecmp;
use function strip_tags;
use function stripos;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;
use function trim;

/**
 * Handle all the functionalities related to displaying results
 * of sql queries, stored procedure, browsing sql processes or
 * displaying binary log.
 */
class Results
{
    // Define constants
    public const NO_EDIT_OR_DELETE = 'nn';
    public const UPDATE_ROW = 'ur';
    public const DELETE_ROW = 'dr';
    public const KILL_PROCESS = 'kp';

    public const POSITION_LEFT = 'left';
    public const POSITION_RIGHT = 'right';
    public const POSITION_BOTH = 'both';
    public const POSITION_NONE = 'none';

    public const DISPLAY_FULL_TEXT = 'F';
    public const DISPLAY_PARTIAL_TEXT = 'P';

    public const HEADER_FLIP_TYPE_AUTO = 'auto';
    public const HEADER_FLIP_TYPE_CSS = 'css';
    public const HEADER_FLIP_TYPE_FAKE = 'fake';

    public const RELATIONAL_KEY = 'K';
    public const RELATIONAL_DISPLAY_COLUMN = 'D';

    public const GEOMETRY_DISP_GEOM = 'GEOM';
    public const GEOMETRY_DISP_WKT = 'WKT';
    public const GEOMETRY_DISP_WKB = 'WKB';

    public const SMART_SORT_ORDER = 'SMART';
    public const ASCENDING_SORT_DIR = 'ASC';
    public const DESCENDING_SORT_DIR = 'DESC';

    public const TABLE_TYPE_INNO_DB = 'InnoDB';
    public const ALL_ROWS = 'all';
    public const QUERY_TYPE_SELECT = 'SELECT';

    public const ROUTINE_PROCEDURE = 'procedure';
    public const ROUTINE_FUNCTION = 'function';

    public const ACTION_LINK_CONTENT_ICONS = 'icons';
    public const ACTION_LINK_CONTENT_TEXT = 'text';

    /**
     * @psalm-var array{
     *   server: int,
     *   db: string,
     *   table: string,
     *   goto: string,
     *   sql_query: string,
     *   unlim_num_rows: int|numeric-string|false,
     *   fields_meta: FieldMetadata[],
     *   is_count: bool|null,
     *   is_export: bool|null,
     *   is_func: bool|null,
     *   is_analyse: bool|null,
     *   num_rows: int|numeric-string,
     *   fields_cnt: int,
     *   querytime: float|null,
     *   text_dir: string|null,
     *   is_maint: bool|null,
     *   is_explain: bool|null,
     *   is_show: bool|null,
     *   is_browse_distinct: bool|null,
     *   showtable: array<string, mixed>|null,
     *   printview: string|null,
     *   highlight_columns: array|null,
     *   display_params: array|null,
     *   mime_map: array|null,
     *   editable: bool|null,
     *   unique_id: int,
     *   whereClauseMap: array,
     * }
     */
    public $properties = [
        /* server id */
        'server' => 0,

        /* Database name */
        'db' => '',

        /* Table name */
        'table' => '',

        /* the URL to go back in case of errors */
        'goto' => '',

        /* the SQL query */
        'sql_query' => '',

        /* the total number of rows returned by the SQL query without any appended "LIMIT" clause programmatically */
        'unlim_num_rows' => 0,

        /* meta information about fields */
        'fields_meta' => [],

        'is_count' => null,

        'is_export' => null,

        'is_func' => null,

        'is_analyse' => null,

        /* the total number of rows returned by the SQL query */
        'num_rows' => 0,

        /* the total number of fields returned by the SQL query */
        'fields_cnt' => 0,

        /* time taken for execute the SQL query */
        'querytime' => null,

        'text_dir' => null,

        'is_maint' => null,

        'is_explain' => null,

        'is_show' => null,

        'is_browse_distinct' => null,

        /* table definitions */
        'showtable' => null,

        'printview' => null,

        /* column names to highlight */
        'highlight_columns' => null,

        /* display information */
        'display_params' => null,

        /* mime types information of fields */
        'mime_map' => null,

        'editable' => null,

        /* random unique ID to distinguish result set */
        'unique_id' => 0,

        /* where clauses for each row, each table in the row */
        'whereClauseMap' => [],
    ];

    /**
     * This variable contains the column transformation information
     * for some of the system databases.
     * One element of this array represent all relevant columns in all tables in
     * one specific database
     *
     * @var array<string, array<string, array<string, string[]>>>
     * @psalm-var array<string, array<string, array<string, array{string, class-string, string}>>> $transformationInfo
     */
    public $transformationInfo;

    /** @var DatabaseInterface */
    private $dbi;

    /** @var Relation */
    private $relation;

    /** @var Transformations */
    private $transformations;

    /** @var Template */
    public $template;

    /**
     * @param string $db       the database name
     * @param string $table    the table name
     * @param int    $server   the server id
     * @param string $goto     the URL to go back in case of errors
     * @param string $sqlQuery the SQL query
     */
    public function __construct(DatabaseInterface $dbi, $db, $table, $server, $goto, $sqlQuery)
    {
        $this->dbi = $dbi;

        $this->relation = new Relation($this->dbi);
        $this->transformations = new Transformations();
        $this->template = new Template();

        $this->setDefaultTransformations();

        $this->properties['db'] = $db;
        $this->properties['table'] = $table;
        $this->properties['server'] = $server;
        $this->properties['goto'] = $goto;
        $this->properties['sql_query'] = $sqlQuery;
        $this->properties['unique_id'] = random_int(0, mt_getrandmax());
    }

    /**
     * Sets default transformations for some columns
     */
    private function setDefaultTransformations(): void
    {
        $jsonHighlightingData = [
            'libraries/classes/Plugins/Transformations/Output/Text_Plain_Json.php',
            Text_Plain_Json::class,
            'Text_Plain',
        ];
        $sqlHighlightingData = [
            'libraries/classes/Plugins/Transformations/Output/Text_Plain_Sql.php',
            Text_Plain_Sql::class,
            'Text_Plain',
        ];
        $blobSqlHighlightingData = [
            'libraries/classes/Plugins/Transformations/Output/Text_Octetstream_Sql.php',
            Text_Octetstream_Sql::class,
            'Text_Octetstream',
        ];
        $linkData = [
            'libraries/classes/Plugins/Transformations/Text_Plain_Link.php',
            Text_Plain_Link::class,
            'Text_Plain',
        ];
        $this->transformationInfo = [
            'information_schema' => [
                'events' => ['event_definition' => $sqlHighlightingData],
                'processlist' => ['info' => $sqlHighlightingData],
                'routines' => ['routine_definition' => $sqlHighlightingData],
                'triggers' => ['action_statement' => $sqlHighlightingData],
                'views' => ['view_definition' => $sqlHighlightingData],
            ],
            'mysql' => [
                'event' => [
                    'body' => $blobSqlHighlightingData,
                    'body_utf8' => $blobSqlHighlightingData,
                ],
                'general_log' => ['argument' => $sqlHighlightingData],
                'help_category' => ['url' => $linkData],
                'help_topic' => [
                    'example' => $sqlHighlightingData,
                    'url' => $linkData,
                ],
                'proc' => [
                    'param_list' => $blobSqlHighlightingData,
                    'returns' => $blobSqlHighlightingData,
                    'body' => $blobSqlHighlightingData,
                    'body_utf8' => $blobSqlHighlightingData,
                ],
                'slow_log' => ['sql_text' => $sqlHighlightingData],
            ],
        ];

        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->db === null) {
            return;
        }

        $relDb = [];
        if ($relationParameters->sqlHistoryFeature !== null) {
            $relDb[$relationParameters->sqlHistoryFeature->history->getName()] = ['sqlquery' => $sqlHighlightingData];
        }

        if ($relationParameters->bookmarkFeature !== null) {
            $relDb[$relationParameters->bookmarkFeature->bookmark->getName()] = ['query' => $sqlHighlightingData];
        }

        if ($relationParameters->trackingFeature !== null) {
            $relDb[$relationParameters->trackingFeature->tracking->getName()] = [
                'schema_sql' => $sqlHighlightingData,
                'data_sql' => $sqlHighlightingData,
            ];
        }

        if ($relationParameters->favoriteTablesFeature !== null) {
            $table = $relationParameters->favoriteTablesFeature->favorite->getName();
            $relDb[$table] = ['tables' => $jsonHighlightingData];
        }

        if ($relationParameters->recentlyUsedTablesFeature !== null) {
            $table = $relationParameters->recentlyUsedTablesFeature->recent->getName();
            $relDb[$table] = ['tables' => $jsonHighlightingData];
        }

        if ($relationParameters->savedQueryByExampleSearchesFeature !== null) {
            $table = $relationParameters->savedQueryByExampleSearchesFeature->savedSearches->getName();
            $relDb[$table] = ['search_data' => $jsonHighlightingData];
        }

        if ($relationParameters->databaseDesignerSettingsFeature !== null) {
            $table = $relationParameters->databaseDesignerSettingsFeature->designerSettings->getName();
            $relDb[$table] = ['settings_data' => $jsonHighlightingData];
        }

        if ($relationParameters->uiPreferencesFeature !== null) {
            $table = $relationParameters->uiPreferencesFeature->tableUiPrefs->getName();
            $relDb[$table] = ['prefs' => $jsonHighlightingData];
        }

        if ($relationParameters->userPreferencesFeature !== null) {
            $table = $relationParameters->userPreferencesFeature->userConfig->getName();
            $relDb[$table] = ['config_data' => $jsonHighlightingData];
        }

        if ($relationParameters->exportTemplatesFeature !== null) {
            $table = $relationParameters->exportTemplatesFeature->exportTemplates->getName();
            $relDb[$table] = ['template_data' => $jsonHighlightingData];
        }

        $this->transformationInfo[$relationParameters->db->getName()] = $relDb;
    }

    /**
     * Set properties which were not initialized at the constructor
     *
     * @param int|string                $unlimNumRows     the total number of rows returned by the SQL query without
     *                                                    any appended "LIMIT" clause programmatically
     * @param FieldMetadata[]           $fieldsMeta       meta information about fields
     * @param bool                      $isCount          statement is SELECT COUNT
     * @param bool                      $isExport         statement contains INTO OUTFILE
     * @param bool                      $isFunction       statement contains a function like SUM()
     * @param bool                      $isAnalyse        statement contains PROCEDURE ANALYSE
     * @param int|string                $numRows          total no. of rows returned by SQL query
     * @param int                       $fieldsCount      total no.of fields returned by SQL query
     * @param double                    $queryTime        time taken for execute the SQL query
     * @param string                    $textDirection    text direction
     * @param bool                      $isMaintenance    statement contains a maintenance command
     * @param bool                      $isExplain        statement contains EXPLAIN
     * @param bool                      $isShow           statement contains SHOW
     * @param array<string, mixed>|null $showTable        table definitions
     * @param string|null               $printView        print view was requested
     * @param bool                      $editable         whether the results set is editable
     * @param bool                      $isBrowseDistinct whether browsing distinct values
     * @psalm-param int|numeric-string $unlimNumRows
     * @psalm-param int|numeric-string $numRows
     */
    public function setProperties(
        $unlimNumRows,
        array $fieldsMeta,
        $isCount,
        $isExport,
        $isFunction,
        $isAnalyse,
        $numRows,
        $fieldsCount,
        $queryTime,
        $textDirection,
        $isMaintenance,
        $isExplain,
        $isShow,
        ?array $showTable,
        $printView,
        $editable,
        $isBrowseDistinct
    ): void {
        $this->properties['unlim_num_rows'] = $unlimNumRows;
        $this->properties['fields_meta'] = $fieldsMeta;
        $this->properties['is_count'] = $isCount;
        $this->properties['is_export'] = $isExport;
        $this->properties['is_func'] = $isFunction;
        $this->properties['is_analyse'] = $isAnalyse;
        $this->properties['num_rows'] = $numRows;
        $this->properties['fields_cnt'] = $fieldsCount;
        $this->properties['querytime'] = $queryTime;
        $this->properties['text_dir'] = $textDirection;
        $this->properties['is_maint'] = $isMaintenance;
        $this->properties['is_explain'] = $isExplain;
        $this->properties['is_show'] = $isShow;
        $this->properties['showtable'] = $showTable;
        $this->properties['printview'] = $printView;
        $this->properties['editable'] = $editable;
        $this->properties['is_browse_distinct'] = $isBrowseDistinct;
    }

    /**
     * Defines the parts to display for a print view
     *
     * @param array $displayParts the parts to display
     *
     * @return array the modified display parts
     */
    private function setDisplayPartsForPrintView(array $displayParts)
    {
        // set all elements to false!
        $displayParts['edit_lnk'] = self::NO_EDIT_OR_DELETE; // no edit link
        $displayParts['del_lnk'] = self::NO_EDIT_OR_DELETE; // no delete link
        $displayParts['sort_lnk'] = '0';
        $displayParts['nav_bar'] = '0';
        $displayParts['bkm_form'] = '0';
        $displayParts['text_btn'] = '0';
        $displayParts['pview_lnk'] = '0';

        return $displayParts;
    }

    /**
     * Defines the parts to display for a SHOW statement
     *
     * @param array $displayParts the parts to display
     *
     * @return array the modified display parts
     */
    private function setDisplayPartsForShow(array $displayParts)
    {
        preg_match(
            '@^SHOW[[:space:]]+(VARIABLES|(FULL[[:space:]]+)?'
            . 'PROCESSLIST|STATUS|TABLE|GRANTS|CREATE|LOGS|DATABASES|FIELDS'
            . ')@i',
            $this->properties['sql_query'],
            $which
        );

        $bIsProcessList = isset($which[1]);
        if ($bIsProcessList) {
            $str = ' ' . strtoupper($which[1]);
            $bIsProcessList = strpos($str, 'PROCESSLIST') > 0;
        }

        // no edit link
        $displayParts['edit_lnk'] = self::NO_EDIT_OR_DELETE;
        if ($bIsProcessList) {
            // "kill process" type edit link
            $displayParts['del_lnk'] = self::KILL_PROCESS;
        } else {
            // Default case -> no links
            // no delete link
            $displayParts['del_lnk'] = self::NO_EDIT_OR_DELETE;
        }

        // Other settings
        $displayParts['sort_lnk'] = '0';
        $displayParts['nav_bar'] = '0';
        $displayParts['bkm_form'] = '1';
        $displayParts['text_btn'] = '1';
        $displayParts['pview_lnk'] = '1';

        return $displayParts;
    }

    /**
     * Defines the parts to display for statements not related to data
     *
     * @param array $displayParts the parts to display
     *
     * @return array the modified display parts
     */
    private function setDisplayPartsForNonData(array $displayParts)
    {
        // Statement is a "SELECT COUNT", a
        // "CHECK/ANALYZE/REPAIR/OPTIMIZE/CHECKSUM", an "EXPLAIN" one or
        // contains a "PROC ANALYSE" part
        $displayParts['edit_lnk'] = self::NO_EDIT_OR_DELETE; // no edit link
        $displayParts['del_lnk'] = self::NO_EDIT_OR_DELETE; // no delete link
        $displayParts['sort_lnk'] = '0';
        $displayParts['nav_bar'] = '0';
        $displayParts['bkm_form'] = '1';

        if ($this->properties['is_maint']) {
            $displayParts['text_btn'] = '1';
        } else {
            $displayParts['text_btn'] = '0';
        }

        $displayParts['pview_lnk'] = '1';

        return $displayParts;
    }

    /**
     * Defines the parts to display for other statements (probably SELECT)
     *
     * @param array $displayParts the parts to display
     *
     * @return array the modified display parts
     */
    private function setDisplayPartsForSelect(array $displayParts)
    {
        // Other statements (ie "SELECT" ones) -> updates
        // $displayParts['edit_lnk'], $displayParts['del_lnk'] and
        // $displayParts['text_btn'] (keeps other default values)

        $fieldsMeta = $this->properties['fields_meta'];
        $previousTable = '';
        $displayParts['text_btn'] = '1';
        $numberOfColumns = $this->properties['fields_cnt'];

        for ($i = 0; $i < $numberOfColumns; $i++) {
            $isLink = ($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                || ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE)
                || ($displayParts['sort_lnk'] != '0');

            // Displays edit/delete/sort/insert links?
            if (
                $isLink
                && $previousTable != ''
                && $fieldsMeta[$i]->table != ''
                && $fieldsMeta[$i]->table != $previousTable
            ) {
                // don't display links
                $displayParts['edit_lnk'] = self::NO_EDIT_OR_DELETE;
                $displayParts['del_lnk'] = self::NO_EDIT_OR_DELETE;
                /**
                 * @todo May be problematic with same field names
                 * in two joined table.
                 */
                if ($displayParts['text_btn'] == '1') {
                    break;
                }
            }

            // Always display print view link
            $displayParts['pview_lnk'] = '1';
            if ($fieldsMeta[$i]->table == '') {
                continue;
            }

            $previousTable = $fieldsMeta[$i]->table;
        }

        if ($previousTable == '') { // no table for any of the columns
            // don't display links
            $displayParts['edit_lnk'] = self::NO_EDIT_OR_DELETE;
            $displayParts['del_lnk'] = self::NO_EDIT_OR_DELETE;
        }

        return $displayParts;
    }

    /**
     * Defines the parts to display for the results of a SQL query
     * and the total number of rows
     *
     * @see     getTable()
     *
     * @param array $displayParts the parts to display (see a few
     *                            lines above for explanations)
     *
     * @return array the first element is an array with explicit indexes
     *               for all the display elements
     *               the second element is the total number of rows returned
     *               by the SQL query without any programmatically appended
     *               LIMIT clause (just a copy of $unlim_num_rows if it exists,
     *               else computed inside this function)
     */
    private function setDisplayPartsAndTotal(array $displayParts)
    {
        $theTotal = 0;

        // 1. Following variables are needed for use in isset/empty or
        //    use with array indexes or safe use in foreach
        $db = $this->properties['db'];
        $table = $this->properties['table'];
        $unlimNumRows = $this->properties['unlim_num_rows'];
        $numRows = $this->properties['num_rows'];
        $printView = $this->properties['printview'];

        // 2. Updates the display parts
        if ($printView == '1') {
            $displayParts = $this->setDisplayPartsForPrintView($displayParts);
        } elseif (
            $this->properties['is_count'] || $this->properties['is_analyse']
            || $this->properties['is_maint'] || $this->properties['is_explain']
        ) {
            $displayParts = $this->setDisplayPartsForNonData($displayParts);
        } elseif ($this->properties['is_show']) {
            $displayParts = $this->setDisplayPartsForShow($displayParts);
        } else {
            $displayParts = $this->setDisplayPartsForSelect($displayParts);
        }

        // 3. Gets the total number of rows if it is unknown
        if ($unlimNumRows > 0) {
            $theTotal = $unlimNumRows;
        } elseif (
            ($displayParts['nav_bar'] == '1')
            || ($displayParts['sort_lnk'] == '1')
            && $db !== '' && $table !== ''
        ) {
            $theTotal = $this->dbi->getTable($db, $table)->countRecords();
        }

        // if for COUNT query, number of rows returned more than 1
        // (may be being used GROUP BY)
        if ($this->properties['is_count'] && $numRows > 1) {
            $displayParts['nav_bar'] = '1';
            $displayParts['sort_lnk'] = '1';
        }

        // 4. If navigation bar or sorting fields names URLs should be
        //    displayed but there is only one row, change these settings to
        //    false
        if ($displayParts['nav_bar'] == '1' || $displayParts['sort_lnk'] == '1') {
            // - Do not display sort links if less than 2 rows.
            // - For a VIEW we (probably) did not count the number of rows
            //   so don't test this number here, it would remove the possibility
            //   of sorting VIEW results.
            $tableObject = new Table($table, $db);
            if ($unlimNumRows < 2 && ! $tableObject->isView()) {
                $displayParts['sort_lnk'] = '0';
            }
        }

        return [
            $displayParts,
            $theTotal,
        ];
    }

    /**
     * Return true if we are executing a query in the form of
     * "SELECT * FROM <a table> ..."
     *
     * @see getTableHeaders(), getColumnParams()
     *
     * @param array $analyzedSqlResults analyzed sql results
     */
    private function isSelect(array $analyzedSqlResults): bool
    {
        return ! ($this->properties['is_count']
                || $this->properties['is_export']
                || $this->properties['is_func']
                || $this->properties['is_analyse'])
            && ! empty($analyzedSqlResults['select_from'])
            && ! empty($analyzedSqlResults['statement']->from)
            && (count($analyzedSqlResults['statement']->from) === 1)
            && ! empty($analyzedSqlResults['statement']->from[0]->table);
    }

    /**
     * Get a navigation button
     *
     * @see     getMoveBackwardButtonsForTableNavigation(),
     *          getMoveForwardButtonsForTableNavigation()
     *
     * @param string $caption         iconic caption for button
     * @param string $title           text for button
     * @param int    $pos             position for next query
     * @param string $htmlSqlQuery    query ready for display
     * @param bool   $back            whether 'begin' or 'previous'
     * @param string $onsubmit        optional onsubmit clause
     * @param string $inputForRealEnd optional hidden field for special treatment
     *
     * @return string                     html content
     */
    private function getTableNavigationButton(
        $caption,
        $title,
        $pos,
        $htmlSqlQuery,
        $back,
        $onsubmit = '',
        $inputForRealEnd = ''
    ): string {
        $captionOutput = '';
        if ($back) {
            if (Util::showIcons('TableNavigationLinksMode')) {
                $captionOutput .= $caption;
            }

            if (Util::showText('TableNavigationLinksMode')) {
                $captionOutput .= '&nbsp;' . $title;
            }
        } else {
            if (Util::showText('TableNavigationLinksMode')) {
                $captionOutput .= $title;
            }

            if (Util::showIcons('TableNavigationLinksMode')) {
                $captionOutput .= '&nbsp;' . $caption;
            }
        }

        return $this->template->render('display/results/table_navigation_button', [
            'db' => $this->properties['db'],
            'table' => $this->properties['table'],
            'sql_query' => $htmlSqlQuery,
            'pos' => $pos,
            'is_browse_distinct' => $this->properties['is_browse_distinct'],
            'goto' => $this->properties['goto'],
            'input_for_real_end' => $inputForRealEnd,
            'caption_output' => $captionOutput,
            'title' => $title,
            'onsubmit' => $onsubmit,
        ]);
    }

    /**
     * Possibly return a page selector for table navigation
     *
     * @return array{string, int} ($output, $nbTotalPage)
     */
    private function getHtmlPageSelector(): array
    {
        $pageNow = (int) floor($_SESSION['tmpval']['pos'] / $_SESSION['tmpval']['max_rows']) + 1;

        $nbTotalPage = (int) ceil((int) $this->properties['unlim_num_rows'] / $_SESSION['tmpval']['max_rows']);

        $output = '';
        if ($nbTotalPage > 1) {
            $urlParams = [
                'db' => $this->properties['db'],
                'table' => $this->properties['table'],
                'sql_query' => $this->properties['sql_query'],
                'goto' => $this->properties['goto'],
                'is_browse_distinct' => $this->properties['is_browse_distinct'],
            ];

            $output = $this->template->render('display/results/page_selector', [
                'url_params' => $urlParams,
                'page_selector' => Util::pageselector(
                    'pos',
                    $_SESSION['tmpval']['max_rows'],
                    $pageNow,
                    $nbTotalPage
                ),
            ]);
        }

        return [
            $output,
            $nbTotalPage,
        ];
    }

    /**
     * Get a navigation bar to browse among the results of a SQL query
     *
     * @see getTable()
     *
     * @param int   $posNext       the offset for the "next" page
     * @param int   $posPrevious   the offset for the "previous" page
     * @param bool  $isInnodb      whether its InnoDB or not
     * @param array $sortByKeyData the sort by key dialog
     *
     * @return array
     */
    private function getTableNavigation(
        int $posNext,
        int $posPrevious,
        bool $isInnodb,
        array $sortByKeyData
    ): array {
        $isShowingAll = $_SESSION['tmpval']['max_rows'] === self::ALL_ROWS;

        // Move to the beginning or to the previous page
        $moveBackwardButtons = '';
        if ($_SESSION['tmpval']['pos'] && ! $isShowingAll) {
            $moveBackwardButtons = $this->getMoveBackwardButtonsForTableNavigation(
                htmlspecialchars($this->properties['sql_query']),
                $posPrevious
            );
        }

        $pageSelector = '';
        $numberTotalPage = 1;
        if (! $isShowingAll) {
            [
                $pageSelector,
                $numberTotalPage,
            ] = $this->getHtmlPageSelector();
        }

        // Move to the next page or to the last one
        $moveForwardButtons = '';
        if (
            // view with unknown number of rows
            ($this->properties['unlim_num_rows'] === -1 || $this->properties['unlim_num_rows'] === false)
            || (! $isShowingAll
            && intval($_SESSION['tmpval']['pos']) + intval($_SESSION['tmpval']['max_rows'])
                < $this->properties['unlim_num_rows']
            && $this->properties['num_rows'] >= $_SESSION['tmpval']['max_rows'])
        ) {
            $moveForwardButtons = $this->getMoveForwardButtonsForTableNavigation(
                htmlspecialchars($this->properties['sql_query']),
                $posNext,
                $isInnodb
            );
        }

        $hiddenFields = [
            'db' => $this->properties['db'],
            'table' => $this->properties['table'],
            'server' => $this->properties['server'],
            'sql_query' => $this->properties['sql_query'],
            'is_browse_distinct' => $this->properties['is_browse_distinct'],
            'goto' => $this->properties['goto'],
        ];

        return [
            'move_backward_buttons' => $moveBackwardButtons,
            'page_selector' => $pageSelector,
            'move_forward_buttons' => $moveForwardButtons,
            'number_total_page' => $numberTotalPage,
            'has_show_all' => $GLOBALS['cfg']['ShowAll'] || ($this->properties['unlim_num_rows'] <= 500),
            'hidden_fields' => $hiddenFields,
            'session_max_rows' => $isShowingAll ? $GLOBALS['cfg']['MaxRows'] : 'all',
            'is_showing_all' => $isShowingAll,
            'max_rows' => $_SESSION['tmpval']['max_rows'],
            'pos' => $_SESSION['tmpval']['pos'],
            'sort_by_key' => $sortByKeyData,
        ];
    }

    /**
     * Prepare move backward buttons - previous and first
     *
     * @see getTableNavigation()
     *
     * @param string $htmlSqlQuery the sql encoded by html special characters
     * @param int    $posPrev      the offset for the "previous" page
     *
     * @return string                 html content
     */
    private function getMoveBackwardButtonsForTableNavigation(
        string $htmlSqlQuery,
        int $posPrev
    ): string {
        return $this->getTableNavigationButton(
            '&lt;&lt;',
            _pgettext('First page', 'Begin'),
            0,
            $htmlSqlQuery,
            true
        )
        . $this->getTableNavigationButton(
            '&lt;',
            _pgettext('Previous page', 'Previous'),
            $posPrev,
            $htmlSqlQuery,
            true
        );
    }

    /**
     * Prepare move forward buttons - next and last
     *
     * @see getTableNavigation()
     *
     * @param string $htmlSqlQuery the sql encoded by htmlspecialchars()
     * @param int    $posNext      the offset for the "next" page
     * @param bool   $isInnodb     whether it's InnoDB or not
     *
     * @return string   html content
     */
    private function getMoveForwardButtonsForTableNavigation(
        string $htmlSqlQuery,
        int $posNext,
        bool $isInnodb
    ): string {
        // display the Next button
        $buttonsHtml = $this->getTableNavigationButton(
            '&gt;',
            _pgettext('Next page', 'Next'),
            $posNext,
            $htmlSqlQuery,
            false
        );

        // If the number of rows is unknown, stop here (don't add the End button)
        if ($this->properties['unlim_num_rows'] === false) {
            return $buttonsHtml;
        }

        $inputForRealEnd = '';
        // prepare some options for the End button
        if ($isInnodb && $this->properties['unlim_num_rows'] > $GLOBALS['cfg']['MaxExactCount']) {
            $inputForRealEnd = '<input id="real_end_input" type="hidden" name="find_real_end" value="1">';
            // no backquote around this message
        }

        $maxRows = (int) $_SESSION['tmpval']['max_rows'];
        $onsubmit = 'onsubmit="return '
            . (intval($_SESSION['tmpval']['pos'])
                + $maxRows
                < $this->properties['unlim_num_rows']
                && $this->properties['num_rows'] >= $maxRows
            ? 'true'
            : 'false') . '"';

        // display the End button
        return $buttonsHtml . $this->getTableNavigationButton(
            '&gt;&gt;',
            _pgettext('Last page', 'End'),
            @((int) ceil(
                (int) $this->properties['unlim_num_rows']
                / $_SESSION['tmpval']['max_rows']
            ) - 1) * $maxRows,
            $htmlSqlQuery,
            false,
            $onsubmit,
            $inputForRealEnd
        );
    }

    /**
     * Get the headers of the results table, for all of the columns
     *
     * @see getTableHeaders()
     *
     * @param array              $displayParts              which elements to display
     * @param array              $analyzedSqlResults        analyzed sql results
     * @param array              $sortExpression            sort expression
     * @param array<int, string> $sortExpressionNoDirection sort expression
     *                                                        without direction
     * @param array              $sortDirection             sort direction
     * @param bool               $isLimitedDisplay          with limited operations
     *                                                        or not
     * @param string             $unsortedSqlQuery          query without the sort part
     *
     * @return string html content
     */
    private function getTableHeadersForColumns(
        array $displayParts,
        array $analyzedSqlResults,
        array $sortExpression,
        array $sortExpressionNoDirection,
        array $sortDirection,
        $isLimitedDisplay,
        $unsortedSqlQuery
    ) {
        // required to generate sort links that will remember whether the
        // "Show all" button has been clicked
        $sqlMd5 = md5($this->properties['server'] . $this->properties['db'] . $this->properties['sql_query']);
        $sessionMaxRows = $isLimitedDisplay
            ? 0
            : $_SESSION['tmpval']['query'][$sqlMd5]['max_rows'];

        // Following variable are needed for use in isset/empty or
        // use with array indexes/safe use in the for loop
        $highlightColumns = $this->properties['highlight_columns'];
        $fieldsMeta = $this->properties['fields_meta'];

        // Prepare Display column comments if enabled
        // ($GLOBALS['cfg']['ShowBrowseComments']).
        $commentsMap = $this->getTableCommentsArray($analyzedSqlResults);

        [$colOrder, $colVisib] = $this->getColumnParams($analyzedSqlResults);

        // optimize: avoid calling a method on each iteration
        $numberOfColumns = $this->properties['fields_cnt'];

        $columns = [];

        for ($j = 0; $j < $numberOfColumns; $j++) {
            // PHP 7.4 fix for accessing array offset on bool
            $colVisibCurrent = $colVisib[$j] ?? null;

            // assign $i with the appropriate column order
            $i = $colOrder ? $colOrder[$j] : $j;

            //  See if this column should get highlight because it's used in the
            //  where-query.
            $name = $fieldsMeta[$i]->name;
            $conditionField = isset($highlightColumns[$name])
                || isset($highlightColumns[Util::backquote($name)]);

            // Prepare comment-HTML-wrappers for each row, if defined/enabled.
            $comments = $this->getCommentForRow($commentsMap, $fieldsMeta[$i]);
            $displayParams = $this->properties['display_params'] ?? [];

            if (($displayParts['sort_lnk'] == '1') && ! $isLimitedDisplay) {
                $sortedHeaderData = $this->getOrderLinkAndSortedHeaderHtml(
                    $fieldsMeta[$i],
                    $sortExpression,
                    $sortExpressionNoDirection,
                    $unsortedSqlQuery,
                    $sessionMaxRows,
                    $comments,
                    $sortDirection,
                    $colVisib,
                    $colVisibCurrent
                );

                $orderLink = $sortedHeaderData['order_link'];
                $columns[] = $sortedHeaderData;

                $displayParams['desc'][] = '    <th '
                    . 'class="draggable'
                    . ($conditionField ? ' condition' : '')
                    . '" data-column="' . htmlspecialchars($fieldsMeta[$i]->name)
                    . '">' . "\n" . $orderLink . $comments . '    </th>' . "\n";
            } else {
                // Results can't be sorted
                // Prepare columns to draggable effect for non sortable columns
                $columns[] = [
                    'column_name' => $fieldsMeta[$i]->name,
                    'comments' => $comments,
                    'is_column_hidden' => $colVisib && ! $colVisibCurrent,
                    'is_column_numeric' => $this->isColumnNumeric($fieldsMeta[$i]),
                    'has_condition' => $conditionField,
                ];

                $displayParams['desc'][] = '    <th '
                    . 'class="draggable'
                    . ($conditionField ? ' condition"' : '')
                    . '" data-column="' . htmlspecialchars((string) $fieldsMeta[$i]->name)
                    . '">        '
                    . htmlspecialchars((string) $fieldsMeta[$i]->name)
                    . $comments . '    </th>';
            }

            $this->properties['display_params'] = $displayParams;
        }

        return $this->template->render('display/results/table_headers_for_columns', [
            'is_sortable' => $displayParts['sort_lnk'] == '1' && ! $isLimitedDisplay,
            'columns' => $columns,
        ]);
    }

    /**
     * Get the headers of the results table
     *
     * @see getTable()
     *
     * @param array              $displayParts              which elements to display
     * @param array              $analyzedSqlResults        analyzed sql results
     * @param string             $unsortedSqlQuery          the unsorted sql query
     * @param array              $sortExpression            sort expression
     * @param array<int, string> $sortExpressionNoDirection sort expression without direction
     * @param array              $sortDirection             sort direction
     * @param bool               $isLimitedDisplay          with limited operations or not
     *
     * @psalm-return array{
     *   column_order: array,
     *   options: array,
     *   has_bulk_actions_form: bool,
     *   button: string,
     *   table_headers_for_columns: string,
     *   column_at_right_side: string,
     * }
     */
    private function getTableHeaders(
        array $displayParts,
        array $analyzedSqlResults,
        $unsortedSqlQuery,
        array $sortExpression = [],
        array $sortExpressionNoDirection = [],
        array $sortDirection = [],
        $isLimitedDisplay = false
    ): array {
        // Needed for use in isset/empty or
        // use with array indexes/safe use in foreach
        $printView = $this->properties['printview'];
        $displayParams = $this->properties['display_params'];

        // Output data needed for column reordering and show/hide column
        $columnOrder = $this->getDataForResettingColumnOrder($analyzedSqlResults);

        $displayParams['emptypre'] = 0;
        $displayParams['emptyafter'] = 0;
        $displayParams['textbtn'] = '';
        $fullOrPartialTextLink = '';

        $this->properties['display_params'] = $displayParams;

        // Display options (if we are not in print view)
        $optionsBlock = [];
        if (! (isset($printView) && ($printView == '1')) && ! $isLimitedDisplay) {
            $optionsBlock = $this->getOptionsBlock();

            // prepare full/partial text button or link
            $fullOrPartialTextLink = $this->getFullOrPartialTextButtonOrLink();
        }

        // 1. Set $colspan and generate html with full/partial
        // text button or link
        $colspan = $displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE
            && $displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE ? ' colspan="4"' : '';
        $buttonHtml = $this->getFieldVisibilityParams($displayParts, $fullOrPartialTextLink, $colspan);

        // 2. Displays the fields' name
        // 2.0 If sorting links should be used, checks if the query is a "JOIN"
        //     statement (see 2.1.3)

        // See if we have to highlight any header fields of a WHERE query.
        // Uses SQL-Parser results.
        $this->setHighlightedColumnGlobalField($analyzedSqlResults);

        // Get the headers for all of the columns
        $tableHeadersForColumns = $this->getTableHeadersForColumns(
            $displayParts,
            $analyzedSqlResults,
            $sortExpression,
            $sortExpressionNoDirection,
            $sortDirection,
            $isLimitedDisplay,
            $unsortedSqlQuery
        );

        // Display column at rightside - checkboxes or empty column
        $columnAtRightSide = '';
        if (! $printView) {
            $columnAtRightSide = $this->getColumnAtRightSide($displayParts, $fullOrPartialTextLink, $colspan);
        }

        return [
            'column_order' => $columnOrder,
            'options' => $optionsBlock,
            'has_bulk_actions_form' => $displayParts['del_lnk'] === self::DELETE_ROW
                || $displayParts['del_lnk'] === self::KILL_PROCESS,
            'button' => $buttonHtml,
            'table_headers_for_columns' => $tableHeadersForColumns,
            'column_at_right_side' => $columnAtRightSide,
        ];
    }

    /**
     * Prepare sort by key dropdown - html code segment
     *
     * @see getTableHeaders()
     *
     * @param array|null $sortExpression   the sort expression
     * @param string     $unsortedSqlQuery the unsorted sql query
     *
     * @return array[]
     * @psalm-return array{hidden_fields?:array, options?:array}
     */
    private function getSortByKeyDropDown(
        ?array $sortExpression,
        string $unsortedSqlQuery
    ): array {
        // grab indexes data:
        $indexes = Index::getFromTable($this->properties['table'], $this->properties['db']);

        // do we have any index?
        if ($indexes === []) {
            return [];
        }

        $hiddenFields = [
            'db' => $this->properties['db'],
            'table' => $this->properties['table'],
            'server' => $this->properties['server'],
            'sort_by_key' => '1',
        ];

        // Keep the number of rows (25, 50, 100, ...) when changing sort key value
        if (isset($_SESSION['tmpval']) && isset($_SESSION['tmpval']['max_rows'])) {
            $hiddenFields['session_max_rows'] = $_SESSION['tmpval']['max_rows'];
        }

        $isIndexUsed = false;
        $localOrder = is_array($sortExpression) ? implode(', ', $sortExpression) : '';

        $options = [];
        foreach ($indexes as $index) {
            $ascSort = '`'
                . implode('` ASC, `', array_keys($index->getColumns()))
                . '` ASC';

            $descSort = '`'
                . implode('` DESC, `', array_keys($index->getColumns()))
                . '` DESC';

            $isIndexUsed = $isIndexUsed
                || $localOrder === $ascSort
                || $localOrder === $descSort;

            $unsortedSqlQueryFirstPart = $unsortedSqlQuery;
            $unsortedSqlQuerySecondPart = '';
            if (
                preg_match(
                    '@(.*)([[:space:]](LIMIT (.*)|PROCEDURE (.*)|FOR UPDATE|LOCK IN SHARE MODE))@is',
                    $unsortedSqlQuery,
                    $myReg
                )
            ) {
                $unsortedSqlQueryFirstPart = $myReg[1];
                $unsortedSqlQuerySecondPart = $myReg[2];
            }

            $options[] = [
                'value' => $unsortedSqlQueryFirstPart . ' ORDER BY '
                    . $ascSort . $unsortedSqlQuerySecondPart,
                'content' => $index->getName() . ' (ASC)',
                'is_selected' => $localOrder === $ascSort,
            ];
            $options[] = [
                'value' => $unsortedSqlQueryFirstPart . ' ORDER BY '
                    . $descSort . $unsortedSqlQuerySecondPart,
                'content' => $index->getName() . ' (DESC)',
                'is_selected' => $localOrder === $descSort,
            ];
        }

        $options[] = [
            'value' => $unsortedSqlQuery,
            'content' => __('None'),
            'is_selected' => ! $isIndexUsed,
        ];

        return ['hidden_fields' => $hiddenFields, 'options' => $options];
    }

    /**
     * Set column span, row span and prepare html with full/partial
     * text button or link
     *
     * @see getTableHeaders()
     *
     * @param array  $displayParts          which elements to display
     * @param string $fullOrPartialTextLink full/partial link or text button
     * @param string $colspan               column span of table header
     *
     * @return string html with full/partial text button or link
     */
    private function getFieldVisibilityParams(
        array $displayParts,
        string $fullOrPartialTextLink,
        string $colspan
    ) {
        $displayParams = $this->properties['display_params'];

        // 1. Displays the full/partial text button (part 1)...
        $buttonHtml = '<thead><tr>' . "\n";

        $emptyPreCondition = $displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE
                           && $displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE;

        $leftOrBoth = $GLOBALS['cfg']['RowActionLinks'] === self::POSITION_LEFT
                   || $GLOBALS['cfg']['RowActionLinks'] === self::POSITION_BOTH;

        //     ... before the result table
        if (
            ($displayParts['edit_lnk'] === self::NO_EDIT_OR_DELETE)
            && ($displayParts['del_lnk'] === self::NO_EDIT_OR_DELETE)
            && ($displayParts['text_btn'] == '1')
        ) {
            $displayParams['emptypre'] = $emptyPreCondition ? 4 : 0;
        } elseif ($leftOrBoth && ($displayParts['text_btn'] == '1')) {
            //     ... at the left column of the result table header if possible
            //     and required

            $displayParams['emptypre'] = $emptyPreCondition ? 4 : 0;

            $buttonHtml .= '<th class="column_action position-sticky d-print-none"' . $colspan
                . '>' . $fullOrPartialTextLink . '</th>';
        } elseif (
            $leftOrBoth
            && (($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
            || ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE))
        ) {
            //     ... elseif no button, displays empty(ies) col(s) if required

            $displayParams['emptypre'] = $emptyPreCondition ? 4 : 0;

            $buttonHtml .= '<td' . $colspan . '></td>';
        } elseif ($GLOBALS['cfg']['RowActionLinks'] === self::POSITION_NONE) {
            // ... elseif display an empty column if the actions links are
            //  disabled to match the rest of the table
            $buttonHtml .= '<th class="column_action position-sticky"></th>';
        }

        $this->properties['display_params'] = $displayParams;

        return $buttonHtml;
    }

    /**
     * Get table comments as array
     *
     * @see getTableHeaders()
     *
     * @param array $analyzedSqlResults analyzed sql results
     *
     * @return array table comments
     */
    private function getTableCommentsArray(array $analyzedSqlResults)
    {
        if (! $GLOBALS['cfg']['ShowBrowseComments'] || empty($analyzedSqlResults['statement']->from)) {
            return [];
        }

        $ret = [];
        foreach ($analyzedSqlResults['statement']->from as $field) {
            if (empty($field->table)) {
                continue;
            }

            $ret[$field->table] = $this->relation->getComments(
                empty($field->database) ? $this->properties['db'] : $field->database,
                $field->table
            );
        }

        return $ret;
    }

    /**
     * Set global array for store highlighted header fields
     *
     * @see getTableHeaders()
     *
     * @param array $analyzedSqlResults analyzed sql results
     */
    private function setHighlightedColumnGlobalField(array $analyzedSqlResults): void
    {
        $highlightColumns = [];

        if (! empty($analyzedSqlResults['statement']->where)) {
            foreach ($analyzedSqlResults['statement']->where as $expr) {
                foreach ($expr->identifiers as $identifier) {
                    $highlightColumns[$identifier] = 'true';
                }
            }
        }

        $this->properties['highlight_columns'] = $highlightColumns;
    }

    /**
     * Prepare data for column restoring and show/hide
     *
     * @see getTableHeaders()
     *
     * @param array $analyzedSqlResults analyzed sql results
     *
     * @return array
     */
    private function getDataForResettingColumnOrder(array $analyzedSqlResults): array
    {
        if (! $this->isSelect($analyzedSqlResults)) {
            return [];
        }

        [$columnOrder, $columnVisibility] = $this->getColumnParams($analyzedSqlResults);

        $tableCreateTime = '';
        $table = new Table($this->properties['table'], $this->properties['db']);
        if (! $table->isView()) {
            $tableCreateTime = $this->dbi->getTable(
                $this->properties['db'],
                $this->properties['table']
            )->getStatusInfo('Create_time');
        }

        return [
            'order' => $columnOrder,
            'visibility' => $columnVisibility,
            'is_view' => $table->isView(),
            'table_create_time' => $tableCreateTime,
        ];
    }

    /**
     * Prepare option fields block
     *
     * @see getTableHeaders()
     *
     * @return array
     */
    private function getOptionsBlock(): array
    {
        if (
            isset($_SESSION['tmpval']['possible_as_geometry'])
            && $_SESSION['tmpval']['possible_as_geometry'] == false
            && $_SESSION['tmpval']['geoOption'] === self::GEOMETRY_DISP_GEOM
        ) {
            $_SESSION['tmpval']['geoOption'] = self::GEOMETRY_DISP_WKT;
        }

        return [
            'geo_option' => $_SESSION['tmpval']['geoOption'],
            'hide_transformation' => $_SESSION['tmpval']['hide_transformation'],
            'display_blob' => $_SESSION['tmpval']['display_blob'],
            'display_binary' => $_SESSION['tmpval']['display_binary'],
            'relational_display' => $_SESSION['tmpval']['relational_display'],
            'possible_as_geometry' => $_SESSION['tmpval']['possible_as_geometry'],
            'pftext' => $_SESSION['tmpval']['pftext'],
        ];
    }

    /**
     * Get full/partial text button or link
     *
     * @see getTableHeaders()
     *
     * @return string html content
     */
    private function getFullOrPartialTextButtonOrLink(): string
    {
        global $theme;

        $urlParamsFullText = [
            'db' => $this->properties['db'],
            'table' => $this->properties['table'],
            'sql_query' => $this->properties['sql_query'],
            'goto' => $this->properties['goto'],
            'full_text_button' => 1,
        ];

        if ($_SESSION['tmpval']['pftext'] === self::DISPLAY_FULL_TEXT) {
            // currently in fulltext mode so show the opposite link
            $tmpImageFile = 's_partialtext.png';
            $tmpTxt = __('Partial texts');
            $urlParamsFullText['pftext'] = self::DISPLAY_PARTIAL_TEXT;
        } else {
            $tmpImageFile = 's_fulltext.png';
            $tmpTxt = __('Full texts');
            $urlParamsFullText['pftext'] = self::DISPLAY_FULL_TEXT;
        }

        $tmpImage = '<img class="fulltext" src="'
            . ($theme instanceof Theme ? $theme->getImgPath($tmpImageFile) : '')
            . '" alt="' . $tmpTxt . '" title="' . $tmpTxt . '">';

        return Generator::linkOrButton(Url::getFromRoute('/sql'), $urlParamsFullText, $tmpImage);
    }

    /**
     * Get comment for row
     *
     * @see getTableHeaders()
     *
     * @param array         $commentsMap comments array
     * @param FieldMetadata $fieldsMeta  set of field properties
     *
     * @return string html content
     */
    private function getCommentForRow(array $commentsMap, FieldMetadata $fieldsMeta): string
    {
        return $this->template->render('display/results/comment_for_row', [
            'comments_map' => $commentsMap,
            'column_name' => $fieldsMeta->name,
            'table_name' => $fieldsMeta->table,
            'limit_chars' => $GLOBALS['cfg']['LimitChars'],
        ]);
    }

    /**
     * Prepare parameters and html for sorted table header fields
     *
     * @see getTableHeaders()
     *
     * @param FieldMetadata      $fieldsMeta                set of field properties
     * @param array              $sortExpression            sort expression
     * @param array<int, string> $sortExpressionNoDirection sort expression without direction
     * @param string             $unsortedSqlQuery          the unsorted sql query
     * @param int                $sessionMaxRows            maximum rows resulted by sql
     * @param string             $comments                  comment for row
     * @param array              $sortDirection             sort direction
     * @param bool               $colVisib                  column is visible(false)
     *                                                      or column isn't visible(string array)
     * @param string             $colVisibElement           element of $col_visib array
     *
     * @return array   2 element array - $orderLink, $sortedHeaderHtml
     * @psalm-return array{
     *   column_name: string,
     *   order_link: string,
     *   comments: string,
     *   is_browse_pointer_enabled: bool,
     *   is_browse_marker_enabled: bool,
     *   is_column_hidden: bool,
     *   is_column_numeric: bool,
     * }
     */
    private function getOrderLinkAndSortedHeaderHtml(
        FieldMetadata $fieldsMeta,
        array $sortExpression,
        array $sortExpressionNoDirection,
        $unsortedSqlQuery,
        $sessionMaxRows,
        string $comments,
        array $sortDirection,
        $colVisib,
        $colVisibElement
    ): array {
        // Checks if the table name is required; it's the case
        // for a query with a "JOIN" statement and if the column
        // isn't aliased, or in queries like
        // SELECT `1`.`master_field` , `2`.`master_field`
        // FROM `PMA_relation` AS `1` , `PMA_relation` AS `2`

        $sortTable = $fieldsMeta->table !== ''
            && $fieldsMeta->orgname === $fieldsMeta->name
            ? Util::backquote($fieldsMeta->table) . '.'
            : '';

        // Generates the orderby clause part of the query which is part
        // of URL
        [$singleSortOrder, $multiSortOrder, $orderImg] = $this->getSingleAndMultiSortUrls(
            $sortExpression,
            $sortExpressionNoDirection,
            $sortTable,
            $fieldsMeta->name,
            $sortDirection,
            $fieldsMeta
        );

        if (
            preg_match(
                '@(.*)([[:space:]](LIMIT (.*)|PROCEDURE (.*)|FOR UPDATE|LOCK IN SHARE MODE))@is',
                $unsortedSqlQuery,
                $regs3
            )
        ) {
            $singleSortedSqlQuery = $regs3[1] . $singleSortOrder . $regs3[2];
            $multiSortedSqlQuery = $regs3[1] . $multiSortOrder . $regs3[2];
        } else {
            $singleSortedSqlQuery = $unsortedSqlQuery . $singleSortOrder;
            $multiSortedSqlQuery = $unsortedSqlQuery . $multiSortOrder;
        }

        $singleUrlParams = [
            'db' => $this->properties['db'],
            'table' => $this->properties['table'],
            'sql_query' => $singleSortedSqlQuery,
            'sql_signature' => Core::signSqlQuery($singleSortedSqlQuery),
            'session_max_rows' => $sessionMaxRows,
            'is_browse_distinct' => $this->properties['is_browse_distinct'],
        ];

        $multiUrlParams = [
            'db' => $this->properties['db'],
            'table' => $this->properties['table'],
            'sql_query' => $multiSortedSqlQuery,
            'sql_signature' => Core::signSqlQuery($multiSortedSqlQuery),
            'session_max_rows' => $sessionMaxRows,
            'is_browse_distinct' => $this->properties['is_browse_distinct'],
        ];

        // Displays the sorting URL
        // enable sort order swapping for image
        $orderLink = $this->getSortOrderLink($orderImg, $fieldsMeta, $singleUrlParams, $multiUrlParams);

        $orderLink .= $this->getSortOrderHiddenInputs($multiUrlParams, $fieldsMeta->name);

        return [
            'column_name' => $fieldsMeta->name,
            'order_link' => $orderLink,
            'comments' => $comments,
            'is_browse_pointer_enabled' => $GLOBALS['cfg']['BrowsePointerEnable'] === true,
            'is_browse_marker_enabled' => $GLOBALS['cfg']['BrowseMarkerEnable'] === true,
            'is_column_hidden' => $colVisib && ! $colVisibElement,
            'is_column_numeric' => $this->isColumnNumeric($fieldsMeta),
        ];
    }

    /**
     * Prepare parameters and html for sorted table header fields
     *
     * @param array              $sortExpression            sort expression
     * @param array<int, string> $sortExpressionNoDirection sort expression without direction
     * @param string             $sortTable                 The name of the table to which
     *                                                      the current column belongs to
     * @param string             $nameToUseInSort           The current column under
     *                                                      consideration
     * @param string[]           $sortDirection             sort direction
     * @param FieldMetadata      $fieldsMeta                set of field properties
     *
     * @return string[]   3 element array - $single_sort_order, $sort_order, $order_img
     */
    private function getSingleAndMultiSortUrls(
        array $sortExpression,
        array $sortExpressionNoDirection,
        string $sortTable,
        string $nameToUseInSort,
        array $sortDirection,
        FieldMetadata $fieldsMeta
    ): array {
        // Check if the current column is in the order by clause
        $isInSort = $this->isInSorted($sortExpression, $sortExpressionNoDirection, $sortTable, $nameToUseInSort);
        $currentName = $nameToUseInSort;
        if ($sortExpressionNoDirection[0] == '' || ! $isInSort) {
            $specialIndex = $sortExpressionNoDirection[0] == ''
                ? 0
                : count($sortExpressionNoDirection);
            $sortExpressionNoDirection[$specialIndex] = Util::backquote($currentName);
            $isTimeOrDate = $fieldsMeta->isType(FieldMetadata::TYPE_TIME)
                || $fieldsMeta->isType(FieldMetadata::TYPE_DATE)
                || $fieldsMeta->isType(FieldMetadata::TYPE_DATETIME)
                || $fieldsMeta->isType(FieldMetadata::TYPE_TIMESTAMP);
            $sortDirection[$specialIndex] = $isTimeOrDate ? self::DESCENDING_SORT_DIR : self::ASCENDING_SORT_DIR;
        }

        $sortExpressionNoDirection = array_filter($sortExpressionNoDirection);
        $singleSortOrder = '';
        $sortOrderColumns = [];
        foreach ($sortExpressionNoDirection as $index => $expression) {
            $sortOrder = '';
            // check if this is the first clause,
            // if it is then we have to add "order by"
            $isFirstClause = ($index === 0);
            $nameToUseInSort = $expression;
            $sortTableNew = $sortTable;
            // Test to detect if the column name is a standard name
            // Standard name has the table name prefixed to the column name
            if (str_contains($nameToUseInSort, '.') && ! str_contains($nameToUseInSort, '(')) {
                $matches = explode('.', $nameToUseInSort);
                // Matches[0] has the table name
                // Matches[1] has the column name
                $nameToUseInSort = $matches[1];
                $sortTableNew = $matches[0];
            }

            // $name_to_use_in_sort might contain a space due to
            // formatting of function expressions like "COUNT(name )"
            // so we remove the space in this situation
            $nameToUseInSort = str_replace([' )', '``'], [')', '`'], $nameToUseInSort);
            $nameToUseInSort = trim($nameToUseInSort, '`');

            // If this the first column name in the order by clause add
            // order by clause to the  column name
            $sortOrder .= $isFirstClause ? "\nORDER BY " : '';

            // Again a check to see if the given column is a aggregate column
            if (str_contains($nameToUseInSort, '(')) {
                $sortOrder .= $nameToUseInSort;
            } else {
                if ($sortTableNew !== '' && ! str_ends_with($sortTableNew, '.')) {
                    $sortTableNew .= '.';
                }

                $sortOrder .= $sortTableNew . Util::backquote($nameToUseInSort);
            }

            // Incase this is the current column save $single_sort_order
            if ($currentName === $nameToUseInSort) {
                $singleSortOrder = "\n" . 'ORDER BY ';

                if (! str_contains($currentName, '(')) {
                    $singleSortOrder .= $sortTable;
                }

                $singleSortOrder .= Util::backquote($currentName) . ' ';

                if ($isInSort) {
                    [$singleSortOrder, $orderImg] = $this->getSortingUrlParams(
                        $sortDirection[$index],
                        $singleSortOrder
                    );
                } else {
                    $singleSortOrder .= strtoupper($sortDirection[$index]);
                }
            }

            $sortOrder .= ' ';
            if ($currentName === $nameToUseInSort && $isInSort) {
                // We need to generate the arrow button and related html
                [$sortOrder, $orderImg] = $this->getSortingUrlParams($sortDirection[$index], $sortOrder);
                $orderImg .= ' <small>' . ($index + 1) . '</small>';
            } else {
                $sortOrder .= strtoupper($sortDirection[$index]);
            }

            // Separate columns by a comma
            $sortOrderColumns[] = $sortOrder;
        }

        return [
            $singleSortOrder,
            implode(', ', $sortOrderColumns),
            $orderImg ?? '',
        ];
    }

    /**
     * Check whether the column is sorted
     *
     * @see getTableHeaders()
     *
     * @param array  $sortExpression            sort expression
     * @param array  $sortExpressionNoDirection sort expression without direction
     * @param string $sortTable                 the table name
     * @param string $nameToUseInSort           the sorting column name
     */
    private function isInSorted(
        array $sortExpression,
        array $sortExpressionNoDirection,
        string $sortTable,
        string $nameToUseInSort
    ): bool {
        $indexInExpression = 0;

        foreach ($sortExpressionNoDirection as $index => $clause) {
            if (str_contains($clause, '.')) {
                $fragments = explode('.', $clause);
                $clause2 = $fragments[0] . '.' . str_replace('`', '', $fragments[1]);
            } else {
                $clause2 = $sortTable . str_replace('`', '', $clause);
            }

            if ($clause2 === $sortTable . $nameToUseInSort) {
                $indexInExpression = $index;
                break;
            }
        }

        if (empty($sortExpression[$indexInExpression])) {
            return false;
        }

        // Field name may be preceded by a space, or any number
        // of characters followed by a dot (tablename.fieldname)
        // so do a direct comparison for the sort expression;
        // this avoids problems with queries like
        // "SELECT id, count(id)..." and clicking to sort
        // on id or on count(id).
        // Another query to test this:
        // SELECT p.*, FROM_UNIXTIME(p.temps) FROM mytable AS p
        // (and try clicking on each column's header twice)
        $noSortTable = $sortTable === '' || mb_strpos(
            $sortExpressionNoDirection[$indexInExpression],
            $sortTable
        ) === false;
        $noOpenParenthesis = mb_strpos($sortExpressionNoDirection[$indexInExpression], '(') === false;
        if ($sortTable !== '' && $noSortTable && $noOpenParenthesis) {
            $newSortExpressionNoDirection = $sortTable
                . $sortExpressionNoDirection[$indexInExpression];
        } else {
            $newSortExpressionNoDirection = $sortExpressionNoDirection[$indexInExpression];
        }

        //Back quotes are removed in next comparison, so remove them from value
        //to compare.
        $nameToUseInSort = str_replace('`', '', $nameToUseInSort);

        $sortName = str_replace('`', '', $sortTable) . $nameToUseInSort;

        return $sortName == str_replace('`', '', $newSortExpressionNoDirection)
            || $sortName == str_replace('`', '', $sortExpressionNoDirection[$indexInExpression]);
    }

    /**
     * Get sort url parameters - sort order and order image
     *
     * @see     getSingleAndMultiSortUrls()
     *
     * @param string $sortDirection the sort direction
     * @param string $sortOrder     the sorting order
     *
     * @return string[]             2 element array - $sort_order, $order_img
     */
    private function getSortingUrlParams(string $sortDirection, $sortOrder): array
    {
        if (strtoupper(trim($sortDirection)) === self::DESCENDING_SORT_DIR) {
            $sortOrder .= self::ASCENDING_SORT_DIR;
            $orderImg = ' ' . Generator::getImage(
                's_desc',
                __('Descending'),
                [
                    'class' => 'soimg',
                    'title' => '',
                ]
            );
            $orderImg .= ' ' . Generator::getImage(
                's_asc',
                __('Ascending'),
                [
                    'class' => 'soimg hide',
                    'title' => '',
                ]
            );
        } else {
            $sortOrder .= self::DESCENDING_SORT_DIR;
            $orderImg = ' ' . Generator::getImage(
                's_asc',
                __('Ascending'),
                [
                    'class' => 'soimg',
                    'title' => '',
                ]
            );
            $orderImg .= ' ' . Generator::getImage(
                's_desc',
                __('Descending'),
                [
                    'class' => 'soimg hide',
                    'title' => '',
                ]
            );
        }

        return [
            $sortOrder,
            $orderImg,
        ];
    }

    /**
     * Get sort order link
     *
     * @see getTableHeaders()
     *
     * @param string                   $orderImg            the sort order image
     * @param FieldMetadata            $fieldsMeta          set of field properties
     * @param array<int|string, mixed> $orderUrlParams      the url params for sort
     * @param array<int|string, mixed> $multiOrderUrlParams the url params for sort
     *
     * @return string the sort order link
     */
    private function getSortOrderLink(
        string $orderImg,
        FieldMetadata $fieldsMeta,
        array $orderUrlParams,
        array $multiOrderUrlParams
    ): string {
        $urlPath = Url::getFromRoute('/sql');
        $innerLinkContent = htmlspecialchars($fieldsMeta->name) . $orderImg
            . '<input type="hidden" value="'
            . $urlPath
            . Url::getCommon($multiOrderUrlParams, str_contains($urlPath, '?') ? '&' : '?', false)
            . '">';

        return Generator::linkOrButton(
            Url::getFromRoute('/sql'),
            $orderUrlParams,
            $innerLinkContent,
            ['class' => 'sortlink']
        );
    }

    private function getSortOrderHiddenInputs(
        array $multipleUrlParams,
        string $nameToUseInSort
    ): string {
        $sqlQuery = $multipleUrlParams['sql_query'];
        $sqlQueryAdd = $sqlQuery;
        $sqlQueryRemove = null;
        $parser = new Parser($sqlQuery);

        $firstStatement = $parser->statements[0] ?? null;
        $numberOfClausesFound = null;
        if ($firstStatement instanceof SelectStatement) {
            $orderClauses = $firstStatement->order ?? [];
            foreach ($orderClauses as $key => $order) {
                // If this is the column name, then remove it from the order clause
                if ($order->expr->column !== $nameToUseInSort) {
                    continue;
                }

                // remove the order clause for this column and from the counted array
                unset($firstStatement->order[$key], $orderClauses[$key]);
            }

            $numberOfClausesFound = count($orderClauses);
            $sqlQueryRemove = $firstStatement->build();
        }

        $multipleUrlParams['sql_query'] = $sqlQueryRemove ?? $sqlQuery;
        $multipleUrlParams['sql_signature'] = Core::signSqlQuery($multipleUrlParams['sql_query']);

        $urlRemoveOrder = Url::getFromRoute('/sql', $multipleUrlParams);
        if ($numberOfClausesFound === 0) {
            $urlRemoveOrder .= '&discard_remembered_sort=1';
        }

        $multipleUrlParams['sql_query'] = $sqlQueryAdd;
        $multipleUrlParams['sql_signature'] = Core::signSqlQuery($multipleUrlParams['sql_query']);

        $urlAddOrder = Url::getFromRoute('/sql', $multipleUrlParams);

        return '<input type="hidden" name="url-remove-order" value="' . $urlRemoveOrder . '">' . "\n"
             . '<input type="hidden" name="url-add-order" value="' . $urlAddOrder . '">';
    }

    /**
     * Check if the column contains numeric data
     *
     * @param FieldMetadata $fieldsMeta set of field properties
     */
    private function isColumnNumeric(FieldMetadata $fieldsMeta): bool
    {
        // This was defined in commit b661cd7c9b31f8bc564d2f9a1b8527e0eb966de8
        // For issue https://github.com/phpmyadmin/phpmyadmin/issues/4746
        return $fieldsMeta->isType(FieldMetadata::TYPE_REAL)
            || $fieldsMeta->isMappedTypeBit
            || $fieldsMeta->isType(FieldMetadata::TYPE_INT);
    }

    /**
     * Prepare column to show at right side - check boxes or empty column
     *
     * @see getTableHeaders()
     *
     * @param array  $displayParts          which elements to display
     * @param string $fullOrPartialTextLink full/partial link or text button
     * @param string $colspan               column span of table header
     *
     * @return string  html content
     */
    private function getColumnAtRightSide(
        array $displayParts,
        string $fullOrPartialTextLink,
        string $colspan
    ) {
        $rightColumnHtml = '';
        $displayParams = $this->properties['display_params'];

        // Displays the needed checkboxes at the right
        // column of the result table header if possible and required...
        if (
            ($GLOBALS['cfg']['RowActionLinks'] === self::POSITION_RIGHT)
            || ($GLOBALS['cfg']['RowActionLinks'] === self::POSITION_BOTH)
            && (($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
            || ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE))
            && ($displayParts['text_btn'] == '1')
        ) {
            $displayParams['emptyafter'] = ($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                && ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE) ? 4 : 1;

            $rightColumnHtml .= "\n"
                . '<th class="column_action d-print-none"' . $colspan . '>'
                . $fullOrPartialTextLink
                . '</th>';
        } elseif (
            ($GLOBALS['cfg']['RowActionLinks'] === self::POSITION_LEFT)
            || ($GLOBALS['cfg']['RowActionLinks'] === self::POSITION_BOTH)
            && (($displayParts['edit_lnk'] === self::NO_EDIT_OR_DELETE)
            && ($displayParts['del_lnk'] === self::NO_EDIT_OR_DELETE))
            && (! isset($GLOBALS['is_header_sent']) || ! $GLOBALS['is_header_sent'])
        ) {
            //     ... elseif no button, displays empty columns if required
            // (unless coming from Browse mode print view)

            $displayParams['emptyafter'] = ($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                && ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE) ? 4 : 1;

            $rightColumnHtml .= "\n" . '<td class="d-print-none"' . $colspan
                . '></td>';
        }

        $this->properties['display_params'] = $displayParams;

        return $rightColumnHtml;
    }

    /**
     * Prepares the display for a value
     *
     * @see     getDataCellForGeometryColumns(),
     *          getDataCellForNonNumericColumns()
     *
     * @param string $class          class of table cell
     * @param bool   $conditionField whether to add CSS class condition
     * @param string $value          value to display
     *
     * @return string  the td
     */
    private function buildValueDisplay($class, $conditionField, $value)
    {
        return $this->template->render('display/results/value_display', [
            'class' => $class,
            'condition_field' => $conditionField,
            'value' => $value,
        ]);
    }

    /**
     * Prepares the display for a null value
     *
     * @see     getDataCellForNumericColumns(),
     *          getDataCellForGeometryColumns(),
     *          getDataCellForNonNumericColumns()
     *
     * @param string        $class          class of table cell
     * @param bool          $conditionField whether to add CSS class condition
     * @param FieldMetadata $meta           the meta-information about this field
     *
     * @return string  the td
     */
    private function buildNullDisplay(string $class, bool $conditionField, FieldMetadata $meta): string
    {
        $classes = $this->addClass($class, $conditionField, $meta, '');

        return $this->template->render('display/results/null_display', [
            'data_decimals' => $meta->decimals,
            'data_type' => $meta->getMappedType(),
            'classes' => $classes,
        ]);
    }

    /**
     * Prepares the display for an empty value
     *
     * @see     getDataCellForNumericColumns(),
     *          getDataCellForGeometryColumns(),
     *          getDataCellForNonNumericColumns()
     *
     * @param string        $class          class of table cell
     * @param bool          $conditionField whether to add CSS class condition
     * @param FieldMetadata $meta           the meta-information about this field
     *
     * @return string  the td
     */
    private function buildEmptyDisplay(string $class, bool $conditionField, FieldMetadata $meta): string
    {
        $classes = $this->addClass($class, $conditionField, $meta, 'text-nowrap');

        return $this->template->render('display/results/empty_display', ['classes' => $classes]);
    }

    /**
     * Adds the relevant classes.
     *
     * @see buildNullDisplay(), getRowData()
     *
     * @param string        $class            class of table cell
     * @param bool          $conditionField   whether to add CSS class condition
     * @param FieldMetadata $meta             the meta-information about the field
     * @param string        $nowrap           avoid wrapping
     * @param bool          $isFieldTruncated is field truncated (display ...)
     *
     * @return string the list of classes
     */
    private function addClass(
        string $class,
        bool $conditionField,
        FieldMetadata $meta,
        string $nowrap,
        bool $isFieldTruncated = false,
        bool $hasTransformationPlugin = false
    ): string {
        $classes = array_filter([
            $class,
            $nowrap,
        ]);

        if (isset($meta->internalMediaType)) {
            $classes[] = preg_replace('/\//', '_', $meta->internalMediaType);
        }

        if ($conditionField) {
            $classes[] = 'condition';
        }

        if ($isFieldTruncated) {
            $classes[] = 'truncated';
        }

        $mediaTypeMap = $this->properties['mime_map'];
        $orgFullColName = $this->properties['db'] . '.' . $meta->orgtable
            . '.' . $meta->orgname;
        if ($hasTransformationPlugin || ! empty($mediaTypeMap[$orgFullColName]['input_transformation'])) {
            $classes[] = 'transformed';
        }

        // Define classes to be added to this data field based on the type of data

        if ($meta->isEnum()) {
            $classes[] = 'enum';
        }

        if ($meta->isSet()) {
            $classes[] = 'set';
        }

        if ($meta->isMappedTypeBit) {
            $classes[] = 'bit';
        }

        if ($meta->isBinary()) {
            $classes[] = 'hex';
        }

        return implode(' ', $classes);
    }

    /**
     * Prepare the body of the results table
     *
     * @see     getTable()
     *
     * @param ResultInterface         $dtResult           the link id associated to the query
     *                                                                      which results have to be displayed
     * @param array                   $displayParts       which elements to display
     * @param array<string, string[]> $map                the list of relations
     * @param array                   $analyzedSqlResults analyzed sql results
     * @param bool                    $isLimitedDisplay   with limited operations or not
     *
     * @return string  html content
     *
     * @global array  $row                  current row data
     */
    private function getTableBody(
        ResultInterface $dtResult,
        array $displayParts,
        array $map,
        array $analyzedSqlResults,
        $isLimitedDisplay = false
    ) {
        // Mostly because of browser transformations, to make the row-data accessible in a plugin.
        global $row;

        $tableBodyHtml = '';

        // query without conditions to shorten URLs when needed, 200 is just
        // guess, it should depend on remaining URL length
        $urlSqlQuery = $this->getUrlSqlQuery($analyzedSqlResults);

        $displayParams = $this->properties['display_params'];

        $rowNumber = 0;
        $displayParams['edit'] = [];
        $displayParams['copy'] = [];
        $displayParams['delete'] = [];
        $displayParams['data'] = [];
        $displayParams['row_delete'] = [];
        $this->properties['display_params'] = $displayParams;

        // name of the class added to all grid editable elements;
        // if we don't have all the columns of a unique key in the result set,
        //  do not permit grid editing
        if ($isLimitedDisplay || ! $this->properties['editable']) {
            $gridEditClass = '';
        } else {
            switch ($GLOBALS['cfg']['GridEditing']) {
                case 'double-click':
                    // trying to reduce generated HTML by using shorter
                    // classes like click1 and click2
                    $gridEditClass = 'grid_edit click2';
                    break;
                case 'click':
                    $gridEditClass = 'grid_edit click1';
                    break;
                default: // 'disabled'
                    $gridEditClass = '';
                    break;
            }
        }

        // prepare to get the column order, if available
        [$colOrder, $colVisib] = $this->getColumnParams($analyzedSqlResults);

        // Correction University of Virginia 19991216 in the while below
        // Previous code assumed that all tables have keys, specifically that
        // the phpMyAdmin GUI should support row delete/edit only for such
        // tables.
        // Although always using keys is arguably the prescribed way of
        // defining a relational table, it is not required. This will in
        // particular be violated by the novice.
        // We want to encourage phpMyAdmin usage by such novices. So the code
        // below has been changed to conditionally work as before when the
        // table being displayed has one or more keys; but to display
        // delete/edit options correctly for tables without keys.

        $whereClauseMap = $this->properties['whereClauseMap'];
        while ($row = $dtResult->fetchRow()) {
            // add repeating headers
            if (
                ($rowNumber !== 0) && ($_SESSION['tmpval']['repeat_cells'] > 0)
                && ($rowNumber % $_SESSION['tmpval']['repeat_cells']) === 0
            ) {
                $tableBodyHtml .= $this->getRepeatingHeaders(
                    $displayParams['emptypre'],
                    $displayParams['desc'],
                    $displayParams['emptyafter']
                );
            }

            $trClass = [];
            if ($GLOBALS['cfg']['BrowsePointerEnable'] != true) {
                $trClass[] = 'nopointer';
            }

            if ($GLOBALS['cfg']['BrowseMarkerEnable'] != true) {
                $trClass[] = 'nomarker';
            }

            // pointer code part
            $tableBodyHtml .= '<tr' . ($trClass === [] ? '' : ' class="' . implode(' ', $trClass) . '"') . '>';

            // 1. Prepares the row

            // In print view these variable needs to be initialized
            $deleteUrl = null;
            $deleteString = null;
            $editString = null;
            $jsConf = null;
            $copyUrl = null;
            $copyString = null;
            $editUrl = null;
            $editCopyUrlParams = [];
            $delUrlParams = null;

            // 1.2 Defines the URLs for the modify/delete link(s)

            if (
                ($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                || ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE)
            ) {
                $expressions = [];

                if (
                    isset($analyzedSqlResults['statement'])
                    && $analyzedSqlResults['statement'] instanceof SelectStatement
                ) {
                    $expressions = $analyzedSqlResults['statement']->expr;
                }

                // Results from a "SELECT" statement -> builds the
                // WHERE clause to use in links (a unique key if possible)
                /**
                 * @todo $where_clause could be empty, for example a table
                 *       with only one field and it's a BLOB; in this case,
                 *       avoid to display the delete and edit links
                 */
                [$whereClause, $clauseIsUnique, $conditionArray] = Util::getUniqueCondition(
                    $this->properties['fields_cnt'],
                    $this->properties['fields_meta'],
                    $row,
                    false,
                    $this->properties['table'],
                    $expressions
                );
                $whereClauseMap[$rowNumber][$this->properties['table']] = $whereClause;
                $this->properties['whereClauseMap'] = $whereClauseMap;

                // 1.2.1 Modify link(s) - update row case
                if ($displayParts['edit_lnk'] === self::UPDATE_ROW) {
                    [
                        $editUrl,
                        $copyUrl,
                        $editString,
                        $copyString,
                        $editCopyUrlParams,
                    ] = $this->getModifiedLinks($whereClause, $clauseIsUnique, $urlSqlQuery);
                }

                // 1.2.2 Delete/Kill link(s)
                [$deleteUrl, $deleteString, $jsConf, $delUrlParams] = $this->getDeleteAndKillLinks(
                    $whereClause,
                    $clauseIsUnique,
                    $urlSqlQuery,
                    $displayParts['del_lnk'],
                    (int) $row[0]
                );

                // 1.3 Displays the links at left if required
                if (
                    ($GLOBALS['cfg']['RowActionLinks'] === self::POSITION_LEFT)
                    || ($GLOBALS['cfg']['RowActionLinks'] === self::POSITION_BOTH)
                ) {
                    $tableBodyHtml .= $this->template->render('display/results/checkbox_and_links', [
                        'position' => self::POSITION_LEFT,
                        'has_checkbox' => $deleteUrl && $displayParts['del_lnk'] !== self::KILL_PROCESS,
                        'edit' => [
                            'url' => $editUrl,
                            'params' => $editCopyUrlParams + ['default_action' => 'update'],
                            'string' => $editString,
                            'clause_is_unique' => $clauseIsUnique,
                        ],
                        'copy' => [
                            'url' => $copyUrl,
                            'params' => $editCopyUrlParams + ['default_action' => 'insert'],
                            'string' => $copyString,
                        ],
                        'delete' => ['url' => $deleteUrl, 'params' => $delUrlParams, 'string' => $deleteString],
                        'row_number' => $rowNumber,
                        'where_clause' => $whereClause,
                        'condition' => json_encode($conditionArray),
                        'is_ajax' => ResponseRenderer::getInstance()->isAjax(),
                        'js_conf' => $jsConf ?? '',
                    ]);
                } elseif ($GLOBALS['cfg']['RowActionLinks'] === self::POSITION_NONE) {
                    $tableBodyHtml .= $this->template->render('display/results/checkbox_and_links', [
                        'position' => self::POSITION_NONE,
                        'has_checkbox' => $deleteUrl && $displayParts['del_lnk'] !== self::KILL_PROCESS,
                        'edit' => [
                            'url' => $editUrl,
                            'params' => $editCopyUrlParams + ['default_action' => 'update'],
                            'string' => $editString,
                            'clause_is_unique' => $clauseIsUnique,
                        ],
                        'copy' => [
                            'url' => $copyUrl,
                            'params' => $editCopyUrlParams + ['default_action' => 'insert'],
                            'string' => $copyString,
                        ],
                        'delete' => ['url' => $deleteUrl, 'params' => $delUrlParams, 'string' => $deleteString],
                        'row_number' => $rowNumber,
                        'where_clause' => $whereClause,
                        'condition' => json_encode($conditionArray),
                        'is_ajax' => ResponseRenderer::getInstance()->isAjax(),
                        'js_conf' => $jsConf ?? '',
                    ]);
                }
            }

            // 2. Displays the rows' values
            if ($this->properties['mime_map'] === null) {
                $this->setMimeMap();
            }

            $tableBodyHtml .= $this->getRowValues(
                $row,
                $rowNumber,
                $colOrder,
                $map,
                $gridEditClass,
                $colVisib,
                $urlSqlQuery,
                $analyzedSqlResults
            );

            // 3. Displays the modify/delete links on the right if required
            if (
                ($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE
                    || $displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE)
                && ($GLOBALS['cfg']['RowActionLinks'] === self::POSITION_RIGHT
                    || $GLOBALS['cfg']['RowActionLinks'] === self::POSITION_BOTH)
            ) {
                $tableBodyHtml .= $this->template->render('display/results/checkbox_and_links', [
                    'position' => self::POSITION_RIGHT,
                    'has_checkbox' => $deleteUrl && $displayParts['del_lnk'] !== self::KILL_PROCESS,
                    'edit' => [
                        'url' => $editUrl,
                        'params' => $editCopyUrlParams + ['default_action' => 'update'],
                        'string' => $editString,
                        'clause_is_unique' => $clauseIsUnique ?? true,
                    ],
                    'copy' => [
                        'url' => $copyUrl,
                        'params' => $editCopyUrlParams + ['default_action' => 'insert'],
                        'string' => $copyString,
                    ],
                    'delete' => ['url' => $deleteUrl, 'params' => $delUrlParams, 'string' => $deleteString],
                    'row_number' => $rowNumber,
                    'where_clause' => $whereClause ?? '',
                    'condition' => json_encode($conditionArray ?? []),
                    'is_ajax' => ResponseRenderer::getInstance()->isAjax(),
                    'js_conf' => $jsConf ?? '',
                ]);
            }

            $tableBodyHtml .= '</tr>';
            $tableBodyHtml .= "\n";
            $rowNumber++;
        }

        return $tableBodyHtml;
    }

    /**
     * Sets the MIME details of the columns in the results set
     */
    private function setMimeMap(): void
    {
        $fieldsMeta = $this->properties['fields_meta'];
        $mediaTypeMap = [];
        $added = [];
        $relationParameters = $this->relation->getRelationParameters();

        for ($currentColumn = 0; $currentColumn < $this->properties['fields_cnt']; ++$currentColumn) {
            $meta = $fieldsMeta[$currentColumn];
            $orgFullTableName = $this->properties['db'] . '.' . $meta->orgtable;

            if (
                $relationParameters->columnCommentsFeature === null
                || $relationParameters->browserTransformationFeature === null
                || ! $GLOBALS['cfg']['BrowseMIME']
                || $_SESSION['tmpval']['hide_transformation']
                || ! empty($added[$orgFullTableName])
            ) {
                continue;
            }

            $mediaTypeMap = array_merge(
                $mediaTypeMap,
                $this->transformations->getMime($this->properties['db'], $meta->orgtable, false, true) ?? []
            );
            $added[$orgFullTableName] = true;
        }

        // special browser transformation for some SHOW statements
        if ($this->properties['is_show'] && ! $_SESSION['tmpval']['hide_transformation']) {
            preg_match(
                '@^SHOW[[:space:]]+(VARIABLES|(FULL[[:space:]]+)?'
                . 'PROCESSLIST|STATUS|TABLE|GRANTS|CREATE|LOGS|DATABASES|FIELDS'
                . ')@i',
                $this->properties['sql_query'],
                $which
            );

            if (isset($which[1])) {
                $str = ' ' . strtoupper($which[1]);
                $isShowProcessList = strpos($str, 'PROCESSLIST') > 0;
                if ($isShowProcessList) {
                    $mediaTypeMap['..Info'] = [
                        'mimetype' => 'Text_Plain',
                        'transformation' => 'output/Text_Plain_Sql.php',
                    ];
                }

                $isShowCreateTable = preg_match('@CREATE[[:space:]]+TABLE@i', $this->properties['sql_query']);
                if ($isShowCreateTable) {
                    $mediaTypeMap['..Create Table'] = [
                        'mimetype' => 'Text_Plain',
                        'transformation' => 'output/Text_Plain_Sql.php',
                    ];
                }
            }
        }

        $this->properties['mime_map'] = $mediaTypeMap;
    }

    /**
     * Get the values for one data row
     *
     * @see     getTableBody()
     *
     * @param array                   $row                current row data
     * @param int                     $rowNumber          the index of current row
     * @param array|false             $colOrder           the column order false when
     *                                                     a property not found false
     *                                                     when a property not found
     * @param array<string, string[]> $map                the list of relations
     * @param string                  $gridEditClass      the class for all editable
     *                                                      columns
     * @param bool|array|string       $colVisib           column is visible(false);
     *                                                     column isn't visible(string
     *                                                     array)
     * @param string                  $urlSqlQuery        the analyzed sql query
     * @param array                   $analyzedSqlResults analyzed sql results
     *
     * @return string  html content
     */
    private function getRowValues(
        array $row,
        $rowNumber,
        $colOrder,
        array $map,
        $gridEditClass,
        $colVisib,
        $urlSqlQuery,
        array $analyzedSqlResults
    ) {
        $rowValuesHtml = '';

        // Following variable are needed for use in isset/empty or
        // use with array indexes/safe use in foreach
        $sqlQuery = $this->properties['sql_query'];
        $fieldsMeta = $this->properties['fields_meta'];
        $highlightColumns = $this->properties['highlight_columns'];
        $mediaTypeMap = $this->properties['mime_map'];

        $rowInfo = $this->getRowInfoForSpecialLinks($row, $colOrder);

        $whereClauseMap = $this->properties['whereClauseMap'];

        $columnCount = $this->properties['fields_cnt'];

        // Load SpecialSchemaLinks for all rows
        $specialSchemaLinks = SpecialSchemaLinks::get();
        $relationParameters = $this->relation->getRelationParameters();

        for ($currentColumn = 0; $currentColumn < $columnCount; ++$currentColumn) {
            // assign $i with appropriate column order
            $i = is_array($colOrder) ? $colOrder[$currentColumn] : $currentColumn;

            $meta = $fieldsMeta[$i];
            $orgFullColName = $this->properties['db'] . '.' . $meta->orgtable . '.' . $meta->orgname;

            $notNullClass = $meta->isNotNull() ? 'not_null' : '';
            $relationClass = isset($map[$meta->name]) ? 'relation' : '';
            $hideClass = is_array($colVisib) && isset($colVisib[$currentColumn]) && ! $colVisib[$currentColumn]
                ? 'hide'
                : '';
            $gridEdit = $meta->orgtable != '' ? $gridEditClass : '';

            // handle datetime-related class, for grid editing
            $fieldTypeClass = $this->getClassForDateTimeRelatedFields($meta);

            // combine all the classes applicable to this column's value
            $class = implode(' ', array_filter([
                'data',
                $gridEdit,
                $notNullClass,
                $relationClass,
                $hideClass,
                $fieldTypeClass,
            ]));

            //  See if this column should get highlight because it's used in the
            //  where-query.
            $conditionField = isset($highlightColumns)
                && (isset($highlightColumns[$meta->name])
                || isset($highlightColumns[Util::backquote($meta->name)]));

            // Wrap MIME-transformations. [MIME]
            $transformationPlugin = null;
            $transformOptions = [];

            if ($relationParameters->browserTransformationFeature !== null && $GLOBALS['cfg']['BrowseMIME']) {
                if (
                    isset($mediaTypeMap[$orgFullColName]['mimetype'])
                    && ! empty($mediaTypeMap[$orgFullColName]['transformation'])
                ) {
                    $file = $mediaTypeMap[$orgFullColName]['transformation'];
                    $includeFile = 'libraries/classes/Plugins/Transformations/' . $file;

                    if (@file_exists(ROOT_PATH . $includeFile)) {
                        $className = $this->transformations->getClassName($includeFile);
                        if (class_exists($className)) {
                            $plugin = new $className();
                            if ($plugin instanceof TransformationsPlugin) {
                                $transformationPlugin = $plugin;
                                $transformOptions = $this->transformations->getOptions(
                                    $mediaTypeMap[$orgFullColName]['transformation_options'] ?? ''
                                );

                                $meta->internalMediaType = str_replace(
                                    '_',
                                    '/',
                                    $mediaTypeMap[$orgFullColName]['mimetype']
                                );
                            }
                        }
                    }
                }
            }

            // Check whether the field needs to display with syntax highlighting

            $dbLower = mb_strtolower($this->properties['db']);
            $tblLower = mb_strtolower($meta->orgtable);
            $nameLower = mb_strtolower($meta->orgname);
            if (
                ! empty($this->transformationInfo[$dbLower][$tblLower][$nameLower])
                && isset($row[$i])
                && (trim($row[$i]) != '')
                && ! $_SESSION['tmpval']['hide_transformation']
            ) {
                /** @psalm-suppress UnresolvableInclude */
                include_once ROOT_PATH . $this->transformationInfo[$dbLower][$tblLower][$nameLower][0];
                $plugin = new $this->transformationInfo[$dbLower][$tblLower][$nameLower][1]();
                if ($plugin instanceof TransformationsPlugin) {
                    $transformationPlugin = $plugin;
                    $transformOptions = $this->transformations->getOptions(
                        $mediaTypeMap[$orgFullColName]['transformation_options'] ?? ''
                    );

                    $orgTable = mb_strtolower($meta->orgtable);
                    $orgName = mb_strtolower($meta->orgname);

                    $meta->internalMediaType = str_replace(
                        '_',
                        '/',
                        $this->transformationInfo[$dbLower][$orgTable][$orgName][2]
                    );
                }
            }

            // Check for the predefined fields need to show as link in schemas
            if (! empty($specialSchemaLinks[$dbLower][$tblLower][$nameLower])) {
                $linkingUrl = $this->getSpecialLinkUrl(
                    $specialSchemaLinks[$dbLower][$tblLower][$nameLower],
                    $row[$i],
                    $rowInfo
                );
                $transformationPlugin = new Text_Plain_Link();

                $transformOptions = [
                    0 => $linkingUrl,
                    2 => true,
                ];

                $meta->internalMediaType = str_replace('_', '/', 'Text/Plain');
            }

            $expressions = [];

            if (
                isset($analyzedSqlResults['statement'])
                && $analyzedSqlResults['statement'] instanceof SelectStatement
            ) {
                $expressions = $analyzedSqlResults['statement']->expr;
            }

            /**
             * The result set can have columns from more than one table,
             * this is why we have to check for the unique conditions
             * related to this table; however getUniqueCondition() is
             * costly and does not need to be called if we already know
             * the conditions for the current table.
             */
            if (! isset($whereClauseMap[$rowNumber][$meta->orgtable])) {
                $uniqueConditions = Util::getUniqueCondition(
                    $this->properties['fields_cnt'],
                    $this->properties['fields_meta'],
                    $row,
                    false,
                    $meta->orgtable,
                    $expressions
                );
                $whereClauseMap[$rowNumber][$meta->orgtable] = $uniqueConditions[0];
            }

            $urlParams = [
                'db' => $this->properties['db'],
                'table' => $meta->orgtable,
                'where_clause_sign' => Core::signSqlQuery($whereClauseMap[$rowNumber][$meta->orgtable]),
                'where_clause' => $whereClauseMap[$rowNumber][$meta->orgtable],
                'transform_key' => $meta->orgname,
            ];

            if ($sqlQuery !== '') {
                $urlParams['sql_query'] = $urlSqlQuery;
            }

            $transformOptions['wrapper_link'] = Url::getCommon($urlParams);
            $transformOptions['wrapper_params'] = $urlParams;

            $displayParams = $this->properties['display_params'] ?? [];

            if ($meta->isNumeric) {
                // n u m e r i c

                $displayParams['data'][$rowNumber][$i] = $this->getDataCellForNumericColumns(
                    $row[$i] === null ? null : (string) $row[$i],
                    'text-end ' . $class,
                    $conditionField,
                    $meta,
                    $map,
                    $analyzedSqlResults,
                    $transformationPlugin,
                    $transformOptions
                );
            } elseif ($meta->isMappedTypeGeometry) {
                // g e o m e t r y

                // Remove 'grid_edit' from $class as we do not allow to
                // inline-edit geometry data.
                $class = str_replace('grid_edit', '', $class);

                $displayParams['data'][$rowNumber][$i] = $this->getDataCellForGeometryColumns(
                    $row[$i] === null ? null : (string) $row[$i],
                    $class,
                    $meta,
                    $map,
                    $urlParams,
                    $conditionField,
                    $transformationPlugin,
                    $transformOptions,
                    $analyzedSqlResults
                );
            } else {
                // n o t   n u m e r i c

                $displayParams['data'][$rowNumber][$i] = $this->getDataCellForNonNumericColumns(
                    $row[$i] === null ? null : (string) $row[$i],
                    $class,
                    $meta,
                    $map,
                    $urlParams,
                    $conditionField,
                    $transformationPlugin,
                    $transformOptions,
                    $analyzedSqlResults
                );
            }

            // output stored cell
            $rowValuesHtml .= $displayParams['data'][$rowNumber][$i];

            if (isset($displayParams['rowdata'][$i][$rowNumber])) {
                $displayParams['rowdata'][$i][$rowNumber] .= $displayParams['data'][$rowNumber][$i];
            } else {
                $displayParams['rowdata'][$i][$rowNumber] = $displayParams['data'][$rowNumber][$i];
            }

            $this->properties['display_params'] = $displayParams;
        }

        return $rowValuesHtml;
    }

    /**
     * Get link for display special schema links
     *
     * @param array<string,array<int,array<string,string>>|string> $linkRelations
     * @param string                                               $columnValue   column value
     * @param array                                                $rowInfo       information about row
     * @phpstan-param array{
     *                         'link_param': string,
     *                         'link_dependancy_params'?: array<
     *                                                      int,
     *                                                      array{'param_info': string, 'column_name': string}
     *                                                     >,
     *                         'default_page': string
     *                     } $linkRelations
     *
     * @return string generated link
     */
    private function getSpecialLinkUrl(
        array $linkRelations,
        $columnValue,
        array $rowInfo
    ) {
        $linkingUrlParams = [];

        $linkingUrlParams[$linkRelations['link_param']] = $columnValue;

        $divider = strpos($linkRelations['default_page'], '?') ? '&' : '?';
        if (empty($linkRelations['link_dependancy_params'])) {
            return $linkRelations['default_page']
                . Url::getCommonRaw($linkingUrlParams, $divider);
        }

        foreach ($linkRelations['link_dependancy_params'] as $new_param) {
            $columnName = mb_strtolower($new_param['column_name']);

            // If there is a value for this column name in the rowInfo provided
            if (isset($rowInfo[$columnName])) {
                $linkingUrlParams[$new_param['param_info']] = $rowInfo[$columnName];
            }

            // Special case 1 - when executing routines, according
            // to the type of the routine, url param changes
            if (empty($rowInfo['routine_type'])) {
                continue;
            }
        }

        return $linkRelations['default_page']
            . Url::getCommonRaw($linkingUrlParams, $divider);
    }

    /**
     * Prepare row information for display special links
     *
     * @param array      $row      current row data
     * @param array|bool $colOrder the column order
     *
     * @return array<string, mixed> associative array with column nama -> value
     */
    private function getRowInfoForSpecialLinks(array $row, $colOrder): array
    {
        $rowInfo = [];
        $fieldsMeta = $this->properties['fields_meta'];

        for ($n = 0; $n < $this->properties['fields_cnt']; ++$n) {
            $m = is_array($colOrder) ? $colOrder[$n] : $n;
            $rowInfo[mb_strtolower($fieldsMeta[$m]->orgname)] = $row[$m];
        }

        return $rowInfo;
    }

    /**
     * Get url sql query without conditions to shorten URLs
     *
     * @see     getTableBody()
     *
     * @param array $analyzedSqlResults analyzed sql results
     *
     * @return string analyzed sql query
     */
    private function getUrlSqlQuery(array $analyzedSqlResults)
    {
        if (($analyzedSqlResults['querytype'] !== 'SELECT') || (mb_strlen($this->properties['sql_query']) < 200)) {
            return $this->properties['sql_query'];
        }

        $query = 'SELECT ' . Query::getClause(
            $analyzedSqlResults['statement'],
            $analyzedSqlResults['parser']->list,
            'SELECT'
        );

        $fromClause = Query::getClause($analyzedSqlResults['statement'], $analyzedSqlResults['parser']->list, 'FROM');

        if ($fromClause !== '') {
            $query .= ' FROM ' . $fromClause;
        }

        return $query;
    }

    /**
     * Get column order and column visibility
     *
     * @see    getTableBody()
     *
     * @param array $analyzedSqlResults analyzed sql results
     *
     * @return mixed[] 2 element array - $col_order, $col_visib
     */
    private function getColumnParams(array $analyzedSqlResults): array
    {
        if ($this->isSelect($analyzedSqlResults)) {
            $pmatable = new Table($this->properties['table'], $this->properties['db']);
            $colOrder = $pmatable->getUiProp(Table::PROP_COLUMN_ORDER);
            $fieldsCount = $this->properties['fields_cnt'];
            /* Validate the value */
            if (is_array($colOrder)) {
                foreach ($colOrder as $value) {
                    if ($value < $fieldsCount) {
                        continue;
                    }

                    $pmatable->removeUiProp(Table::PROP_COLUMN_ORDER);
                    break;
                }

                if ($fieldsCount !== count($colOrder)) {
                    $pmatable->removeUiProp(Table::PROP_COLUMN_ORDER);
                    $colOrder = false;
                }
            }

            $colVisib = $pmatable->getUiProp(Table::PROP_COLUMN_VISIB);
            if (is_array($colVisib) && $fieldsCount !== count($colVisib)) {
                $pmatable->removeUiProp(Table::PROP_COLUMN_VISIB);
                $colVisib = false;
            }
        } else {
            $colOrder = false;
            $colVisib = false;
        }

        return [
            $colOrder,
            $colVisib,
        ];
    }

    /**
     * Get HTML for repeating headers
     *
     * @see    getTableBody()
     *
     * @param int      $numEmptyColumnsBefore The number of blank columns before this one
     * @param string[] $descriptions          A list of descriptions
     * @param int      $numEmptyColumnsAfter  The number of blank columns after this one
     *
     * @return string html content
     */
    private function getRepeatingHeaders(
        int $numEmptyColumnsBefore,
        array $descriptions,
        int $numEmptyColumnsAfter
    ): string {
        $headerHtml = '<tr>' . "\n";

        if ($numEmptyColumnsBefore > 0) {
            $headerHtml .= '    <th colspan="'
                . $numEmptyColumnsBefore . '">'
                . "\n" . '        &nbsp;</th>' . "\n";
        } elseif ($GLOBALS['cfg']['RowActionLinks'] === self::POSITION_NONE) {
            $headerHtml .= '    <th></th>' . "\n";
        }

        $headerHtml .= implode($descriptions);

        if ($numEmptyColumnsAfter > 0) {
            $headerHtml .= '    <th colspan="' . $numEmptyColumnsAfter
                . '">'
                . "\n" . '        &nbsp;</th>' . "\n";
        }

        $headerHtml .= '</tr>' . "\n";

        return $headerHtml;
    }

    /**
     * Get modified links
     *
     * @see     getTableBody()
     *
     * @param string $whereClause    the where clause of the sql
     * @param bool   $clauseIsUnique the unique condition of clause
     * @param string $urlSqlQuery    the analyzed sql query
     *
     * @return array<int,string|array<string, bool|string>>
     */
    private function getModifiedLinks(
        $whereClause,
        $clauseIsUnique,
        $urlSqlQuery
    ) {
        $urlParams = [
            'db' => $this->properties['db'],
            'table' => $this->properties['table'],
            'where_clause' => $whereClause,
            'clause_is_unique' => $clauseIsUnique,
            'sql_query' => $urlSqlQuery,
            'goto' => Url::getFromRoute('/sql'),
        ];

        $editUrl = Url::getFromRoute('/table/change');

        $copyUrl = Url::getFromRoute('/table/change');

        $editStr = $this->getActionLinkContent(
            'b_edit',
            __('Edit')
        );
        $copyStr = $this->getActionLinkContent(
            'b_insrow',
            __('Copy')
        );

        return [
            $editUrl,
            $copyUrl,
            $editStr,
            $copyStr,
            $urlParams,
        ];
    }

    /**
     * Get delete and kill links
     *
     * @see     getTableBody()
     *
     * @param string $whereClause    the where clause of the sql
     * @param bool   $clauseIsUnique the unique condition of clause
     * @param string $urlSqlQuery    the analyzed sql query
     * @param string $deleteLink     the delete link of current row
     * @param int    $processId      Process ID
     *
     * @return array  $del_url, $del_str, $js_conf
     * @psalm-return array{?string, ?string, ?string}
     */
    private function getDeleteAndKillLinks(
        $whereClause,
        $clauseIsUnique,
        $urlSqlQuery,
        $deleteLink,
        int $processId
    ) {
        $goto = $this->properties['goto'];

        if ($deleteLink === self::DELETE_ROW) { // delete row case
            $urlParams = [
                'db' => $this->properties['db'],
                'table' => $this->properties['table'],
                'sql_query' => $urlSqlQuery,
                'message_to_show' => __('The row has been deleted.'),
                'goto' => $goto ?: Url::getFromRoute('/table/sql'),
            ];

            $linkGoto = Url::getFromRoute('/sql', $urlParams);

            $deleteQuery = 'DELETE FROM '
                . Util::backquote($this->properties['table'])
                . ' WHERE ' . $whereClause .
                ($clauseIsUnique ? '' : ' LIMIT 1');

            $urlParams = [
                'db' => $this->properties['db'],
                'table' => $this->properties['table'],
                'sql_query' => $deleteQuery,
                'message_to_show' => __('The row has been deleted.'),
                'goto' => $linkGoto,
            ];
            $deleteUrl = Url::getFromRoute('/sql');

            $jsConf = 'DELETE FROM ' . $this->properties['table']
                . ' WHERE ' . $whereClause
                . ($clauseIsUnique ? '' : ' LIMIT 1');

            $deleteString = $this->getActionLinkContent('b_drop', __('Delete'));
        } elseif ($deleteLink === self::KILL_PROCESS) { // kill process case
            $urlParams = [
                'db' => $this->properties['db'],
                'table' => $this->properties['table'],
                'sql_query' => $urlSqlQuery,
                'goto' => Url::getFromRoute('/'),
            ];

            $linkGoto = Url::getFromRoute('/sql', $urlParams);

            $kill = $this->dbi->getKillQuery($processId);

            $urlParams = [
                'db' => 'mysql',
                'sql_query' => $kill,
                'goto' => $linkGoto,
            ];

            $deleteUrl = Url::getFromRoute('/sql');
            $jsConf = $kill;
            $deleteString = Generator::getIcon(
                'b_drop',
                __('Kill')
            );
        } else {
            $deleteUrl = $deleteString = $jsConf = $urlParams = null;
        }

        return [
            $deleteUrl,
            $deleteString,
            $jsConf,
            $urlParams,
        ];
    }

    /**
     * Get content inside the table row action links (Edit/Copy/Delete)
     *
     * @see     getModifiedLinks(), getDeleteAndKillLinks()
     *
     * @param string $icon        The name of the file to get
     * @param string $displayText The text displaying after the image icon
     *
     * @return string
     */
    private function getActionLinkContent($icon, $displayText)
    {
        if (
            isset($GLOBALS['cfg']['RowActionType'])
            && $GLOBALS['cfg']['RowActionType'] === self::ACTION_LINK_CONTENT_ICONS
        ) {
            return '<span class="text-nowrap">'
                . Generator::getImage($icon, $displayText)
                . '</span>';
        }

        if (
            isset($GLOBALS['cfg']['RowActionType'])
            && $GLOBALS['cfg']['RowActionType'] === self::ACTION_LINK_CONTENT_TEXT
        ) {
            return '<span class="text-nowrap">' . $displayText . '</span>';
        }

        return Generator::getIcon($icon, $displayText);
    }

    /**
     * Get class for datetime related fields
     *
     * @see    getTableBody()
     *
     * @param FieldMetadata $meta the type of the column field
     *
     * @return string   the class for the column
     */
    private function getClassForDateTimeRelatedFields(FieldMetadata $meta): string
    {
        $fieldTypeClass = '';

        if ($meta->isMappedTypeTimestamp || $meta->isType(FieldMetadata::TYPE_DATETIME)) {
            $fieldTypeClass = 'datetimefield';
        } elseif ($meta->isType(FieldMetadata::TYPE_DATE)) {
            $fieldTypeClass = 'datefield';
        } elseif ($meta->isType(FieldMetadata::TYPE_TIME)) {
            $fieldTypeClass = 'timefield';
        } elseif ($meta->isType(FieldMetadata::TYPE_STRING)) {
            $fieldTypeClass = 'text';
        }

        return $fieldTypeClass;
    }

    /**
     * Prepare data cell for numeric type fields
     *
     * @see    getTableBody()
     *
     * @param string|null             $column             the column's value
     * @param string                  $class              the html class for column
     * @param bool                    $conditionField     the column should highlighted or not
     * @param FieldMetadata           $meta               the meta-information about this field
     * @param array<string, string[]> $map                the list of relations
     * @param array                   $analyzedSqlResults the analyzed query
     * @param array                   $transformOptions   the transformation parameters
     *
     * @return string the prepared cell, html content
     */
    private function getDataCellForNumericColumns(
        ?string $column,
        string $class,
        bool $conditionField,
        FieldMetadata $meta,
        array $map,
        array $analyzedSqlResults,
        ?TransformationsPlugin $transformationPlugin,
        array $transformOptions
    ) {
        if ($column === null) {
            return $this->buildNullDisplay($class, $conditionField, $meta);
        }

        if ($column === '') {
            return $this->buildEmptyDisplay($class, $conditionField, $meta);
        }

        $whereComparison = ' = ' . $column;

        return $this->getRowData(
            $class,
            $conditionField,
            $analyzedSqlResults,
            $meta,
            $map,
            $column,
            $column,
            $transformationPlugin,
            'text-nowrap',
            $whereComparison,
            $transformOptions
        );
    }

    /**
     * Get data cell for geometry type fields
     *
     * @see     getTableBody()
     *
     * @param string|null             $column             the relevant column in data row
     * @param string                  $class              the html class for column
     * @param FieldMetadata           $meta               the meta-information about this field
     * @param array<string, string[]> $map                the list of relations
     * @param array                   $urlParams          the parameters for generate url
     * @param bool                    $conditionField     the column should highlighted or not
     * @param array                   $transformOptions   the transformation parameters
     * @param array                   $analyzedSqlResults the analyzed query
     *
     * @return string the prepared data cell, html content
     */
    private function getDataCellForGeometryColumns(
        ?string $column,
        string $class,
        FieldMetadata $meta,
        array $map,
        array $urlParams,
        bool $conditionField,
        ?TransformationsPlugin $transformationPlugin,
        $transformOptions,
        array $analyzedSqlResults
    ) {
        if ($column === null) {
            return $this->buildNullDisplay($class, $conditionField, $meta);
        }

        if ($column === '') {
            return $this->buildEmptyDisplay($class, $conditionField, $meta);
        }

        // Display as [GEOMETRY - (size)]
        if ($_SESSION['tmpval']['geoOption'] === self::GEOMETRY_DISP_GEOM) {
            $geometryText = $this->handleNonPrintableContents(
                'GEOMETRY',
                $column,
                $transformationPlugin,
                $transformOptions,
                $meta,
                $urlParams
            );

            return $this->buildValueDisplay($class, $conditionField, $geometryText);
        }

        if ($_SESSION['tmpval']['geoOption'] === self::GEOMETRY_DISP_WKT) {
            // Prepare in Well Known Text(WKT) format.
            $whereComparison = ' = ' . $column;

            // Convert to WKT format
            $wktval = Gis::convertToWellKnownText($column);
            [
                $isFieldTruncated,
                $displayedColumn,
                // skip 3rd param
            ] = $this->getPartialText($wktval);

            return $this->getRowData(
                $class,
                $conditionField,
                $analyzedSqlResults,
                $meta,
                $map,
                $wktval,
                $displayedColumn,
                $transformationPlugin,
                '',
                $whereComparison,
                $transformOptions,
                $isFieldTruncated
            );
        }

        // Prepare in  Well Known Binary (WKB) format.

        if ($_SESSION['tmpval']['display_binary']) {
            $whereComparison = ' = ' . $column;

            $wkbval = substr(bin2hex($column), 8);
            [
                $isFieldTruncated,
                $displayedColumn,
                // skip 3rd param
            ] = $this->getPartialText($wkbval);

            return $this->getRowData(
                $class,
                $conditionField,
                $analyzedSqlResults,
                $meta,
                $map,
                $wkbval,
                $displayedColumn,
                $transformationPlugin,
                '',
                $whereComparison,
                $transformOptions,
                $isFieldTruncated
            );
        }

        $wkbval = $this->handleNonPrintableContents(
            'BINARY',
            $column,
            $transformationPlugin,
            $transformOptions,
            $meta,
            $urlParams
        );

        return $this->buildValueDisplay($class, $conditionField, $wkbval);
    }

    /**
     * Get data cell for non numeric type fields
     *
     * @see    getTableBody()
     *
     * @param string|null             $column             the relevant column in data row
     * @param string                  $class              the html class for column
     * @param FieldMetadata           $meta               the meta-information about the field
     * @param array<string, string[]> $map                the list of relations
     * @param array                   $urlParams          the parameters for generate url
     * @param bool                    $conditionField     the column should highlighted or not
     * @param array                   $transformOptions   the transformation parameters
     * @param array                   $analyzedSqlResults the analyzed query
     *
     * @return string the prepared data cell, html content
     */
    private function getDataCellForNonNumericColumns(
        ?string $column,
        string $class,
        FieldMetadata $meta,
        array $map,
        array $urlParams,
        bool $conditionField,
        ?TransformationsPlugin $transformationPlugin,
        $transformOptions,
        array $analyzedSqlResults
    ) {
        $originalLength = 0;

        $isAnalyse = $this->properties['is_analyse'];

        $bIsText = $transformationPlugin !== null && ! str_contains($transformationPlugin->getMIMEType(), 'Text');

        // disable inline grid editing
        // if binary fields are protected
        // or transformation plugin is of non text type
        // such as image
        $isTypeBlob = $meta->isType(FieldMetadata::TYPE_BLOB);
        $cfgProtectBinary = $GLOBALS['cfg']['ProtectBinary'];
        if (
            ($meta->isBinary()
            && (
                $cfgProtectBinary === 'all'
                || ($cfgProtectBinary === 'noblob' && ! $isTypeBlob)
                || ($cfgProtectBinary === 'blob' && $isTypeBlob)
                )
            ) || $bIsText
        ) {
            $class = str_replace('grid_edit', '', $class);
        }

        if ($column === null) {
            return $this->buildNullDisplay($class, $conditionField, $meta);
        }

        if ($column === '') {
            return $this->buildEmptyDisplay($class, $conditionField, $meta);
        }

        // Cut all fields to $GLOBALS['cfg']['LimitChars']
        // (unless it's a link-type transformation or binary)
        $originalDataForWhereClause = $column;
        $displayedColumn = $column;
        $isFieldTruncated = false;
        if (
            ! ($transformationPlugin !== null
            && str_contains($transformationPlugin->getName(), 'Link'))
            && ! $meta->isBinary()
        ) {
            [
                $isFieldTruncated,
                $column,
                $originalLength,
            ] = $this->getPartialText($column);
        }

        if ($meta->isMappedTypeBit) {
            $displayedColumn = Util::printableBitValue((int) $displayedColumn, (int) $meta->length);

            // some results of PROCEDURE ANALYSE() are reported as
            // being BINARY but they are quite readable,
            // so don't treat them as BINARY
        } elseif ($meta->isBinary() && $isAnalyse !== true) {
            // we show the BINARY or BLOB message and field's size
            // (or maybe use a transformation)
            $binaryOrBlob = 'BLOB';
            if ($meta->isType(FieldMetadata::TYPE_STRING)) {
                $binaryOrBlob = 'BINARY';
            }

            $displayedColumn = $this->handleNonPrintableContents(
                $binaryOrBlob,
                $displayedColumn,
                $transformationPlugin,
                $transformOptions,
                $meta,
                $urlParams,
                $isFieldTruncated
            );
            $class = $this->addClass(
                $class,
                $conditionField,
                $meta,
                '',
                $isFieldTruncated,
                $transformationPlugin !== null
            );
            $result = strip_tags($column);
            // disable inline grid editing
            // if binary or blob data is not shown
            if (stripos($result, $binaryOrBlob) !== false) {
                $class = str_replace('grid_edit', '', $class);
            }

            return $this->buildValueDisplay($class, $conditionField, $displayedColumn);
        }

        // transform functions may enable no-wrapping:
        $boolNoWrap = $transformationPlugin !== null
            && $transformationPlugin->applyTransformationNoWrap($transformOptions);

        // do not wrap if date field type or if no-wrapping enabled by transform functions
        // otherwise, preserve whitespaces and wrap
        $nowrap = $meta->isDateTimeType() || $boolNoWrap ? 'text-nowrap' : 'pre_wrap';

        $whereComparison = ' = \''
            . $this->dbi->escapeString($originalDataForWhereClause)
            . '\'';

        return $this->getRowData(
            $class,
            $conditionField,
            $analyzedSqlResults,
            $meta,
            $map,
            $column,
            $displayedColumn,
            $transformationPlugin,
            $nowrap,
            $whereComparison,
            $transformOptions,
            $isFieldTruncated,
            (string) $originalLength
        );
    }

    /**
     * Checks the posted options for viewing query results
     * and sets appropriate values in the session.
     *
     * @param array $analyzedSqlResults the analyzed query results
     *
     * @todo    make maximum remembered queries configurable
     * @todo    move/split into SQL class!?
     * @todo    currently this is called twice unnecessary
     * @todo    ignore LIMIT and ORDER in query!?
     */
    public function setConfigParamsForDisplayTable(array $analyzedSqlResults): void
    {
        $sqlMd5 = md5($this->properties['server'] . $this->properties['db'] . $this->properties['sql_query']);
        $query = [];
        if (isset($_SESSION['tmpval']['query'][$sqlMd5])) {
            $query = $_SESSION['tmpval']['query'][$sqlMd5];
        }

        $query['sql'] = $this->properties['sql_query'];

        if (empty($query['repeat_cells'])) {
            $query['repeat_cells'] = $GLOBALS['cfg']['RepeatCells'];
        }

        // The value can also be from _GET as described on issue #16146 when sorting results
        $sessionMaxRows = $_GET['session_max_rows'] ?? $_POST['session_max_rows'] ?? '';

        if (isset($sessionMaxRows) && is_numeric($sessionMaxRows)) {
            $query['max_rows'] = (int) $sessionMaxRows;
            unset($_GET['session_max_rows'], $_POST['session_max_rows']);
        } elseif ($sessionMaxRows === self::ALL_ROWS) {
            $query['max_rows'] = self::ALL_ROWS;
            unset($_GET['session_max_rows'], $_POST['session_max_rows']);
        } elseif (empty($query['max_rows'])) {
            $query['max_rows'] = intval($GLOBALS['cfg']['MaxRows']);
        }

        if (isset($_REQUEST['pos']) && is_numeric($_REQUEST['pos'])) {
            $query['pos'] = (int) $_REQUEST['pos'];
            unset($_REQUEST['pos']);
        } elseif (empty($query['pos'])) {
            $query['pos'] = 0;
        }

        // Full text is needed in case of explain statements, if not specified.
        $fullText = $analyzedSqlResults['is_explain'];

        if (
            isset($_REQUEST['pftext']) && in_array(
                $_REQUEST['pftext'],
                [self::DISPLAY_PARTIAL_TEXT, self::DISPLAY_FULL_TEXT]
            )
        ) {
            $query['pftext'] = $_REQUEST['pftext'];
            unset($_REQUEST['pftext']);
        } elseif ($fullText) {
            $query['pftext'] = self::DISPLAY_FULL_TEXT;
        } elseif (empty($query['pftext'])) {
            $query['pftext'] = self::DISPLAY_PARTIAL_TEXT;
        }

        if (
            isset($_REQUEST['relational_display']) && in_array(
                $_REQUEST['relational_display'],
                [self::RELATIONAL_KEY, self::RELATIONAL_DISPLAY_COLUMN]
            )
        ) {
            $query['relational_display'] = $_REQUEST['relational_display'];
            unset($_REQUEST['relational_display']);
        } elseif (empty($query['relational_display'])) {
            // The current session value has priority over a
            // change via Settings; this change will be apparent
            // starting from the next session
            $query['relational_display'] = $GLOBALS['cfg']['RelationalDisplay'];
        }

        if (
            isset($_REQUEST['geoOption']) && in_array(
                $_REQUEST['geoOption'],
                [self::GEOMETRY_DISP_WKT, self::GEOMETRY_DISP_WKB, self::GEOMETRY_DISP_GEOM]
            )
        ) {
            $query['geoOption'] = $_REQUEST['geoOption'];
            unset($_REQUEST['geoOption']);
        } elseif (empty($query['geoOption'])) {
            $query['geoOption'] = self::GEOMETRY_DISP_GEOM;
        }

        if (isset($_REQUEST['display_binary'])) {
            $query['display_binary'] = true;
            unset($_REQUEST['display_binary']);
        } elseif (isset($_REQUEST['display_options_form'])) {
            // we know that the checkbox was unchecked
            unset($query['display_binary']);
        } elseif (! isset($_REQUEST['full_text_button'])) {
            // selected by default because some operations like OPTIMIZE TABLE
            // and all queries involving functions return "binary" contents,
            // according to low-level field flags
            $query['display_binary'] = true;
        }

        if (isset($_REQUEST['display_blob'])) {
            $query['display_blob'] = true;
            unset($_REQUEST['display_blob']);
        } elseif (isset($_REQUEST['display_options_form'])) {
            // we know that the checkbox was unchecked
            unset($query['display_blob']);
        }

        if (isset($_REQUEST['hide_transformation'])) {
            $query['hide_transformation'] = true;
            unset($_REQUEST['hide_transformation']);
        } elseif (isset($_REQUEST['display_options_form'])) {
            // we know that the checkbox was unchecked
            unset($query['hide_transformation']);
        }

        // move current query to the last position, to be removed last
        // so only least executed query will be removed if maximum remembered
        // queries limit is reached
        unset($_SESSION['tmpval']['query'][$sqlMd5]);
        $_SESSION['tmpval']['query'][$sqlMd5] = $query;

        // do not exceed a maximum number of queries to remember
        if (count($_SESSION['tmpval']['query']) > 10) {
            array_shift($_SESSION['tmpval']['query']);
            //echo 'deleting one element ...';
        }

        // populate query configuration
        $_SESSION['tmpval']['pftext'] = $query['pftext'];
        $_SESSION['tmpval']['relational_display'] = $query['relational_display'];
        $_SESSION['tmpval']['geoOption'] = $query['geoOption'];
        $_SESSION['tmpval']['display_binary'] = isset($query['display_binary']);
        $_SESSION['tmpval']['display_blob'] = isset($query['display_blob']);
        $_SESSION['tmpval']['hide_transformation'] = isset($query['hide_transformation']);
        $_SESSION['tmpval']['pos'] = $query['pos'];
        $_SESSION['tmpval']['max_rows'] = $query['max_rows'];
        $_SESSION['tmpval']['repeat_cells'] = $query['repeat_cells'];
    }

    /**
     * Prepare a table of results returned by a SQL query.
     *
     * @param ResultInterface $dtResult           the link id associated to the query
     *                                   which results have to be displayed
     * @param array           $displayParts       the parts to display
     * @param array           $analyzedSqlResults analyzed sql results
     * @param bool            $isLimitedDisplay   With limited operations or not
     *
     * @return string   Generated HTML content for resulted table
     */
    public function getTable(
        ResultInterface $dtResult,
        array &$displayParts,
        array $analyzedSqlResults,
        $isLimitedDisplay = false
    ) {
        // The statement this table is built for.
        if (isset($analyzedSqlResults['statement'])) {
            /** @var SelectStatement $statement */
            $statement = $analyzedSqlResults['statement'];
        } else {
            $statement = null;
        }

        // Following variable are needed for use in isset/empty or
        // use with array indexes/safe use in foreach
        $fieldsMeta = $this->properties['fields_meta'];
        $showTable = $this->properties['showtable'];
        $printView = $this->properties['printview'];

        /**
         * @todo move this to a central place
         * @todo for other future table types
         */
        $isInnodb = (isset($showTable['Type'])
            && $showTable['Type'] === self::TABLE_TYPE_INNO_DB);

        if ($isInnodb && Sql::isJustBrowsing($analyzedSqlResults, true)) {
            $preCount = '~';
            $afterCount = Generator::showHint(
                Sanitize::sanitizeMessage(
                    __('May be approximate. See [doc@faq3-11]FAQ 3.11[/doc].')
                )
            );
        } else {
            $preCount = '';
            $afterCount = '';
        }

        // 1. ----- Prepares the work -----

        // 1.1 Gets the information about which functionalities should be
        //     displayed

        [
            $displayParts,
            $total,
        ] = $this->setDisplayPartsAndTotal($displayParts);

        // 1.2 Defines offsets for the next and previous pages
        $posNext = 0;
        $posPrev = 0;
        if ($displayParts['nav_bar'] == '1') {
            [$posNext, $posPrev] = $this->getOffsets();
        }

        // 1.3 Extract sorting expressions.
        //     we need $sort_expression and $sort_expression_nodirection
        //     even if there are many table references
        $sortExpression = [];
        $sortExpressionNoDirection = [];
        $sortDirection = [];

        if ($statement !== null && ! empty($statement->order)) {
            foreach ($statement->order as $o) {
                $sortExpression[] = $o->expr->expr . ' ' . $o->type;
                $sortExpressionNoDirection[] = $o->expr->expr;
                $sortDirection[] = $o->type;
            }
        } else {
            $sortExpression[] = '';
            $sortExpressionNoDirection[] = '';
            $sortDirection[] = '';
        }

        // 1.4 Prepares display of first and last value of the sorted column
        $sortedColumnMessage = '';
        foreach ($sortExpressionNoDirection as $expression) {
            $sortedColumnMessage .= $this->getSortedColumnMessage($dtResult, $expression);
        }

        // 2. ----- Prepare to display the top of the page -----

        // 2.1 Prepares a messages with position information
        $sqlQueryMessage = '';
        if ($displayParts['nav_bar'] == '1') {
            $message = $this->setMessageInformation(
                $sortedColumnMessage,
                $analyzedSqlResults,
                $total,
                $posNext,
                $preCount,
                $afterCount
            );

            $sqlQueryMessage = Generator::getMessage($message, $this->properties['sql_query'], 'success');
        } elseif (($printView === null || $printView != '1') && ! $isLimitedDisplay) {
            $sqlQueryMessage = Generator::getMessage(
                __('Your SQL query has been executed successfully.'),
                $this->properties['sql_query'],
                'success'
            );
        }

        // 2.3 Prepare the navigation bars
        if ($this->properties['table'] === '' && $analyzedSqlResults['querytype'] === 'SELECT') {
            // table does not always contain a real table name,
            // for example in MySQL 5.0.x, the query SHOW STATUS
            // returns STATUS as a table name
            $this->properties['table'] = $fieldsMeta[0]->table;
        }

        $unsortedSqlQuery = '';
        $sortByKeyData = [];
        // can the result be sorted?
        if ($displayParts['sort_lnk'] == '1' && isset($analyzedSqlResults['statement'])) {
            $unsortedSqlQuery = Query::replaceClause(
                $analyzedSqlResults['statement'],
                $analyzedSqlResults['parser']->list,
                'ORDER BY',
                ''
            );

            // Data is sorted by indexes only if there is only one table.
            if ($this->isSelect($analyzedSqlResults)) {
                $sortByKeyData = $this->getSortByKeyDropDown(
                    $sortExpression,
                    $unsortedSqlQuery
                );
            }
        }

        $navigation = [];
        if ($displayParts['nav_bar'] == '1' && $statement !== null && empty($statement->limit)) {
            $navigation = $this->getTableNavigation($posNext, $posPrev, $isInnodb, $sortByKeyData);
        }

        // 2b ----- Get field references from Database -----
        // (see the 'relation' configuration variable)

        // initialize map
        $map = [];

        if ($this->properties['table'] !== '') {
            // This method set the values for $map array
            $map = $this->setParamForLinkForeignKeyRelatedTables($map);

            // Coming from 'Distinct values' action of structure page
            // We manipulate relations mechanism to show a link to related rows.
            if ($this->properties['is_browse_distinct']) {
                $map[$fieldsMeta[1]->name] = [
                    $this->properties['table'],
                    $fieldsMeta[1]->name,
                    '',
                    $this->properties['db'],
                ];
            }
        }

        // end 2b

        // 3. ----- Prepare the results table -----
        $headers = $this->getTableHeaders(
            $displayParts,
            $analyzedSqlResults,
            $unsortedSqlQuery,
            $sortExpression,
            $sortExpressionNoDirection,
            $sortDirection,
            $isLimitedDisplay
        );

        $body = $this->getTableBody($dtResult, $displayParts, $map, $analyzedSqlResults, $isLimitedDisplay);

        $this->properties['display_params'] = null;

        // 4. ----- Prepares the link for multi-fields edit and delete
        $bulkLinks = $this->getBulkLinks($dtResult, $analyzedSqlResults, $displayParts['del_lnk']);

        // 5. ----- Prepare "Query results operations"
        $operations = [];
        if (($printView === null || $printView != '1') && ! $isLimitedDisplay) {
            $operations = $this->getResultsOperations($displayParts['pview_lnk'], $analyzedSqlResults);
        }

        $relationParameters = $this->relation->getRelationParameters();

        return $this->template->render('display/results/table', [
            'sql_query_message' => $sqlQueryMessage,
            'navigation' => $navigation,
            'headers' => $headers,
            'body' => $body,
            'bulk_links' => $bulkLinks,
            'operations' => $operations,
            'db' => $this->properties['db'],
            'table' => $this->properties['table'],
            'unique_id' => $this->properties['unique_id'],
            'sql_query' => $this->properties['sql_query'],
            'goto' => $this->properties['goto'],
            'unlim_num_rows' => $this->properties['unlim_num_rows'],
            'displaywork' => $relationParameters->displayFeature !== null,
            'relwork' => $relationParameters->relationFeature !== null,
            'save_cells_at_once' => $GLOBALS['cfg']['SaveCellsAtOnce'],
            'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
            'text_dir' => $this->properties['text_dir'],
        ]);
    }

    /**
     * Get offsets for next page and previous page
     *
     * @see    getTable()
     *
     * @return int[] array with two elements - $pos_next, $pos_prev
     */
    private function getOffsets()
    {
        if ($_SESSION['tmpval']['max_rows'] === self::ALL_ROWS) {
            return [0, 0];
        }

        return [
            $_SESSION['tmpval']['pos'] + $_SESSION['tmpval']['max_rows'],
            max(0, $_SESSION['tmpval']['pos'] - $_SESSION['tmpval']['max_rows']),
        ];
    }

    /**
     * Prepare sorted column message
     *
     * @see     getTable()
     *
     * @param ResultInterface $dtResult                  the link id associated to the query
     *                                                   which results have to be displayed
     * @param string|null     $sortExpressionNoDirection sort expression without direction
     *
     * @return string
     */
    private function getSortedColumnMessage(
        ResultInterface $dtResult,
        $sortExpressionNoDirection
    ) {
        $fieldsMeta = $this->properties['fields_meta']; // To use array indexes

        if (empty($sortExpressionNoDirection)) {
            return '';
        }

        if (! str_contains($sortExpressionNoDirection, '.')) {
            $sortTable = $this->properties['table'];
            $sortColumn = $sortExpressionNoDirection;
        } else {
            [$sortTable, $sortColumn] = explode('.', $sortExpressionNoDirection);
        }

        $sortTable = Util::unQuote($sortTable);
        $sortColumn = Util::unQuote($sortColumn);

        // find the sorted column index in row result
        // (this might be a multi-table query)
        $sortedColumnIndex = false;

        foreach ($fieldsMeta as $key => $meta) {
            if (($meta->table == $sortTable) && ($meta->name == $sortColumn)) {
                $sortedColumnIndex = $key;
                break;
            }
        }

        if ($sortedColumnIndex === false) {
            return '';
        }

        // fetch first row of the result set
        $row = $dtResult->fetchRow();

        // check for non printable sorted row data
        $meta = $fieldsMeta[$sortedColumnIndex];

        $isBlobOrGeometryOrBinary = $meta->isType(FieldMetadata::TYPE_BLOB)
                                    || $meta->isMappedTypeGeometry || $meta->isBinary;

        if ($isBlobOrGeometryOrBinary) {
            $columnForFirstRow = $this->handleNonPrintableContents(
                $meta->getMappedType(),
                $row ? $row[$sortedColumnIndex] : '',
                null,
                [],
                $meta
            );
        } else {
            $columnForFirstRow = $row !== [] ? $row[$sortedColumnIndex] : '';
        }

        $columnForFirstRow = mb_strtoupper(
            mb_substr(
                (string) $columnForFirstRow,
                0,
                (int) $GLOBALS['cfg']['LimitChars']
            ) . '...'
        );

        // fetch last row of the result set
        $dtResult->seek($this->properties['num_rows'] > 0 ? $this->properties['num_rows'] - 1 : 0);
        $row = $dtResult->fetchRow();

        // check for non printable sorted row data
        $meta = $fieldsMeta[$sortedColumnIndex];
        if ($isBlobOrGeometryOrBinary) {
            $columnForLastRow = $this->handleNonPrintableContents(
                $meta->getMappedType(),
                $row ? $row[$sortedColumnIndex] : '',
                null,
                [],
                $meta
            );
        } else {
            $columnForLastRow = $row !== [] ? $row[$sortedColumnIndex] : '';
        }

        $columnForLastRow = mb_strtoupper(
            mb_substr(
                (string) $columnForLastRow,
                0,
                (int) $GLOBALS['cfg']['LimitChars']
            ) . '...'
        );

        // reset to first row for the loop in getTableBody()
        $dtResult->seek(0);

        // we could also use here $sort_expression_nodirection
        return ' [' . htmlspecialchars($sortColumn)
            . ': <strong>' . htmlspecialchars($columnForFirstRow) . ' - '
            . htmlspecialchars($columnForLastRow) . '</strong>]';
    }

    /**
     * Set the content that needs to be shown in message
     *
     * @see     getTable()
     *
     * @param string $sortedColumnMessage the message for sorted column
     * @param array  $analyzedSqlResults  the analyzed query
     * @param int    $total               the total number of rows returned by
     *                                    the SQL query without any
     *                                    programmatically appended LIMIT clause
     * @param int    $posNext             the offset for next page
     * @param string $preCount            the string renders before row count
     * @param string $afterCount          the string renders after row count
     *
     * @return Message an object of Message
     */
    private function setMessageInformation(
        string $sortedColumnMessage,
        array $analyzedSqlResults,
        $total,
        $posNext,
        string $preCount,
        string $afterCount
    ) {
        $unlimNumRows = $this->properties['unlim_num_rows']; // To use in isset()

        if (! empty($analyzedSqlResults['statement']->limit)) {
            $firstShownRec = $analyzedSqlResults['statement']->limit->offset;
            $rowCount = $analyzedSqlResults['statement']->limit->rowCount;

            if ($rowCount < $total) {
                $lastShownRec = $firstShownRec + $rowCount - 1;
            } else {
                $lastShownRec = $firstShownRec + $total - 1;
            }
        } elseif (($_SESSION['tmpval']['max_rows'] === self::ALL_ROWS) || ($posNext > $total)) {
            $firstShownRec = $_SESSION['tmpval']['pos'];
            $lastShownRec = $total - 1;
        } else {
            $firstShownRec = $_SESSION['tmpval']['pos'];
            $lastShownRec = $posNext - 1;
        }

        $messageViewWarning = false;
        $table = new Table($this->properties['table'], $this->properties['db']);
        if ($table->isView() && ($total == $GLOBALS['cfg']['MaxExactCountViews'])) {
            $message = Message::notice(
                __(
                    'This view has at least this number of rows. Please refer to %sdocumentation%s.'
                )
            );

            $message->addParam('[doc@cfg_MaxExactCount]');
            $message->addParam('[/doc]');
            $messageViewWarning = Generator::showHint($message->getMessage());
        }

        $message = Message::success(__('Showing rows %1s - %2s'));
        $message->addParam($firstShownRec);

        if ($messageViewWarning !== false) {
            $message->addParamHtml('... ' . $messageViewWarning);
        } else {
            $message->addParam($lastShownRec);
        }

        $message->addText('(');

        if ($messageViewWarning === false) {
            if ($unlimNumRows != $total) {
                $messageTotal = Message::notice(
                    $preCount . __('%1$d total, %2$d in query')
                );
                $messageTotal->addParam($total);
                $messageTotal->addParam($unlimNumRows);
            } else {
                $messageTotal = Message::notice($preCount . __('%d total'));
                $messageTotal->addParam($total);
            }

            if ($afterCount !== '') {
                $messageTotal->addHtml($afterCount);
            }

            $message->addMessage($messageTotal, '');

            $message->addText(', ', '');
        }

        $messageQueryTime = Message::notice(__('Query took %01.4f seconds.') . ')');
        $messageQueryTime->addParam($this->properties['querytime']);

        $message->addMessage($messageQueryTime, '');
        $message->addHtml($sortedColumnMessage, '');

        return $message;
    }

    /**
     * Set the value of $map array for linking foreign key related tables
     *
     * @see      getTable()
     *
     * @param array<string, string[]> $map the list of relations
     *
     * @return array<string, string[]>
     */
    private function setParamForLinkForeignKeyRelatedTables(array $map): array
    {
        // To be able to later display a link to the related table,
        // we verify both types of relations: either those that are
        // native foreign keys or those defined in the phpMyAdmin
        // configuration storage. If no PMA storage, we won't be able
        // to use the "column to display" notion (for example show
        // the name related to a numeric id).
        $existRel = $this->relation->getForeigners(
            $this->properties['db'],
            $this->properties['table'],
            '',
            self::POSITION_BOTH
        );

        if ($existRel === []) {
            return $map;
        }

        foreach ($existRel as $masterField => $rel) {
            if ($masterField !== 'foreign_keys_data') {
                $displayField = $this->relation->getDisplayField($rel['foreign_db'], $rel['foreign_table']);
                $map[$masterField] = [
                    $rel['foreign_table'],
                    $rel['foreign_field'],
                    $displayField,
                    $rel['foreign_db'],
                ];
            } else {
                foreach ($rel as $oneKey) {
                    foreach ($oneKey['index_list'] as $index => $oneField) {
                        $displayField = $this->relation->getDisplayField(
                            $oneKey['ref_db_name'] ?? $GLOBALS['db'],
                            $oneKey['ref_table_name']
                        );

                        $map[$oneField] = [
                            $oneKey['ref_table_name'],
                            $oneKey['ref_index_list'][$index],
                            $displayField,
                            $oneKey['ref_db_name'] ?? $GLOBALS['db'],
                        ];
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Prepare multi field edit/delete links
     *
     * @see     getTable()
     *
     * @param ResultInterface $dtResult           the link id associated to the query which
     *                                    results have to be displayed
     * @param array           $analyzedSqlResults analyzed sql results
     * @param string          $deleteLink         the display element - 'del_link'
     *
     * @psalm-return array{has_export_button:bool, clause_is_unique:mixed}|array<empty, empty>
     */
    private function getBulkLinks(
        ResultInterface $dtResult,
        array $analyzedSqlResults,
        $deleteLink
    ): array {
        if ($deleteLink !== self::DELETE_ROW) {
            return [];
        }

        // fetch last row of the result set
        $dtResult->seek($this->properties['num_rows'] > 0 ? $this->properties['num_rows'] - 1 : 0);
        $row = $dtResult->fetchRow();

        $expressions = [];

        if (isset($analyzedSqlResults['statement']) && $analyzedSqlResults['statement'] instanceof SelectStatement) {
            $expressions = $analyzedSqlResults['statement']->expr;
        }

        /**
         * $clause_is_unique is needed by getTable() to generate the proper param
         * in the multi-edit and multi-delete form
         */
        [, $clauseIsUnique] = Util::getUniqueCondition(
            $this->properties['fields_cnt'],
            $this->properties['fields_meta'],
            $row,
            false,
            false,
            $expressions
        );

        // reset to first row for the loop in getTableBody()
        $dtResult->seek(0);

        return [
            'has_export_button' => $analyzedSqlResults['querytype'] === 'SELECT',
            'clause_is_unique' => $clauseIsUnique,
        ];
    }

    /**
     * Get operations that are available on results.
     *
     * @see     getTable()
     *
     * @param string $printLink          the parts to display
     * @param array  $analyzedSqlResults analyzed sql results
     *
     * @psalm-return array{
     *   has_export_link: bool,
     *   has_geometry: bool,
     *   has_print_link: bool,
     *   has_procedure: bool,
     *   url_params: array{
     *     db: string,
     *     table: string,
     *     printview: "1",
     *     sql_query: string,
     *     single_table?: "true",
     *     raw_query?: "true",
     *     unlim_num_rows?: int|numeric-string|false
     *   }
     * }
     */
    private function getResultsOperations(
        string $printLink,
        array $analyzedSqlResults
    ): array {
        $urlParams = [
            'db' => $this->properties['db'],
            'table' => $this->properties['table'],
            'printview' => '1',
            'sql_query' => $this->properties['sql_query'],
        ];

        $geometryFound = false;

        // Export link
        // (the single_table parameter is used in \PhpMyAdmin\Export->getDisplay()
        //  to hide the SQL and the structure export dialogs)
        // If the parser found a PROCEDURE clause
        // (most probably PROCEDURE ANALYSE()) it makes no sense to
        // display the Export link).
        if (
            ($analyzedSqlResults['querytype'] === self::QUERY_TYPE_SELECT)
            && empty($analyzedSqlResults['procedure'])
        ) {
            if (count($analyzedSqlResults['select_tables']) === 1) {
                $urlParams['single_table'] = 'true';
            }

            // In case this query doesn't involve any tables,
            // implies only raw query is to be exported
            if (! $analyzedSqlResults['select_tables']) {
                $urlParams['raw_query'] = 'true';
            }

            $urlParams['unlim_num_rows'] = $this->properties['unlim_num_rows'];

            /**
             * At this point we don't know the table name; this can happen
             * for example with a query like
             * SELECT bike_code FROM (SELECT bike_code FROM bikes) tmp
             * As a workaround we set in the table parameter the name of the
             * first table of this database, so that /table/export and
             * the script it calls do not fail
             */
            if ($urlParams['table'] === '' && $urlParams['db'] !== '') {
                $urlParams['table'] = (string) $this->dbi->fetchValue('SHOW TABLES');
            }

            $fieldsMeta = $this->properties['fields_meta'];
            foreach ($fieldsMeta as $meta) {
                if ($meta->isMappedTypeGeometry) {
                    $geometryFound = true;
                    break;
                }
            }
        }

        return [
            'has_procedure' => ! empty($analyzedSqlResults['procedure']),
            'has_geometry' => $geometryFound,
            'has_print_link' => $printLink == '1',
            'has_export_link' => $analyzedSqlResults['querytype'] === self::QUERY_TYPE_SELECT,
            'url_params' => $urlParams,
        ];
    }

    /**
     * Verifies what to do with non-printable contents (binary or BLOB)
     * in Browse mode.
     *
     * @see getDataCellForGeometryColumns(), getDataCellForNonNumericColumns(), getSortedColumnMessage()
     *
     * @param string        $category         BLOB|BINARY|GEOMETRY
     * @param string|null   $content          the binary content
     * @param array         $transformOptions transformation parameters
     * @param FieldMetadata $meta             the meta-information about the field
     * @param array         $urlParams        parameters that should go to the download link
     * @param bool          $isTruncated      the result is truncated or not
     */
    private function handleNonPrintableContents(
        $category,
        ?string $content,
        ?TransformationsPlugin $transformationPlugin,
        array $transformOptions,
        FieldMetadata $meta,
        array $urlParams = [],
        &$isTruncated = false
    ): string {
        $isTruncated = false;
        $result = '[' . $category;

        if ($content !== null) {
            $size = strlen($content);
            $displaySize = Util::formatByteDown($size, 3, 1);
            if ($displaySize !== null) {
                $result .= ' - ' . $displaySize[0] . ' ' . $displaySize[1];
            }
        } else {
            $result .= ' - NULL';
            $size = 0;
            $content = '';
        }

        $result .= ']';

        // if we want to use a text transformation on a BLOB column
        if ($transformationPlugin !== null) {
            $posMimeOctetstream = strpos(
                $transformationPlugin->getMIMESubtype(),
                'Octetstream'
            );
            $posMimeText = strpos($transformationPlugin->getMIMEType(), 'Text');
            if ($posMimeOctetstream || $posMimeText !== false) {
                // Applying Transformations on hex string of binary data
                // seems more appropriate
                $result = pack('H*', bin2hex($content));
            }
        }

        if ($size <= 0) {
            return $result;
        }

        if ($transformationPlugin !== null) {
            return $transformationPlugin->applyTransformation($result, $transformOptions, $meta);
        }

        $result = Core::mimeDefaultFunction($result);
        if (
            ($_SESSION['tmpval']['display_binary']
            && $meta->isType(FieldMetadata::TYPE_STRING))
            || ($_SESSION['tmpval']['display_blob']
            && $meta->isType(FieldMetadata::TYPE_BLOB))
        ) {
            // in this case, restart from the original $content
            if (
                mb_check_encoding($content, 'utf-8')
                && ! preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', $content)
            ) {
                // show as text if it's valid utf-8
                $result = htmlspecialchars($content);
            } else {
                $result = '0x' . bin2hex($content);
            }

            [
                $isTruncated,
                $result,
                // skip 3rd param
            ] = $this->getPartialText($result);
        }

        /* Create link to download */

        if ($urlParams !== [] && $this->properties['db'] !== '' && $meta->orgtable !== '') {
            $urlParams['where_clause_sign'] = Core::signSqlQuery($urlParams['where_clause']);
            $result = '<a href="'
                . Url::getFromRoute('/table/get-field', $urlParams)
                . '" class="disableAjax">'
                . $result . '</a>';
        }

        return $result;
    }

    /**
     * Retrieves the associated foreign key info for a data cell
     *
     * @param string[] $fieldInfo       the relation
     * @param string   $whereComparison data for the where clause
     *
     * @return string  formatted data
     */
    private function getFromForeign(array $fieldInfo, string $whereComparison): ?string
    {
        $dispsql = 'SELECT '
            . Util::backquote($fieldInfo[2])
            . ' FROM '
            . Util::backquote($fieldInfo[3])
            . '.'
            . Util::backquote($fieldInfo[0])
            . ' WHERE '
            . Util::backquote($fieldInfo[1])
            . $whereComparison;

        $dispval = $this->dbi->fetchValue($dispsql);
        if ($dispval === false) {
            return __('Link not found!');
        }

        if ($dispval === null) {
            return null;
        }

        // Truncate values that are too long, see: #17902
        [, $dispval] = $this->getPartialText($dispval);

        return $dispval;
    }

    /**
     * Prepares the displayable content of a data cell in Browse mode,
     * taking into account foreign key description field and transformations
     *
     * @see     getDataCellForNumericColumns(), getDataCellForGeometryColumns(),
     *          getDataCellForNonNumericColumns(),
     *
     * @param string                  $class              css classes for the td element
     * @param bool                    $conditionField     whether the column is a part of the where clause
     * @param array                   $analyzedSqlResults the analyzed query
     * @param FieldMetadata           $meta               the meta-information about the field
     * @param array<string, string[]> $map                the list of relations
     * @param string                  $data               data
     * @param string                  $displayedData      data that will be displayed (maybe be chunked)
     * @param string                  $nowrap             'nowrap' if the content should not be wrapped
     * @param string                  $whereComparison    data for the where clause
     * @param array                   $transformOptions   options for transformation
     * @param bool                    $isFieldTruncated   whether the field is truncated
     * @param string                  $originalLength     of a truncated column, or ''
     *
     * @return string  formatted data
     */
    private function getRowData(
        string $class,
        bool $conditionField,
        array $analyzedSqlResults,
        FieldMetadata $meta,
        array $map,
        $data,
        $displayedData,
        ?TransformationsPlugin $transformationPlugin,
        string $nowrap,
        string $whereComparison,
        array $transformOptions,
        bool $isFieldTruncated = false,
        string $originalLength = ''
    ) {
        $relationalDisplay = $_SESSION['tmpval']['relational_display'];
        $printView = $this->properties['printview'];
        $value = '';
        $tableDataCellClass = $this->addClass(
            $class,
            $conditionField,
            $meta,
            $nowrap,
            $isFieldTruncated,
            $transformationPlugin !== null
        );

        if (! empty($analyzedSqlResults['statement']->expr)) {
            foreach ($analyzedSqlResults['statement']->expr as $expr) {
                if (empty($expr->alias) || empty($expr->column)) {
                    continue;
                }

                if (strcasecmp($meta->name, $expr->alias) !== 0) {
                    continue;
                }

                $meta->name = $expr->column;
            }
        }

        if (isset($map[$meta->name])) {
            /** @var array{0: string, 1: string, 2: string|false, 3: string} $relation */
            $relation = $map[$meta->name];
            // Field to display from the foreign table?
            $dispval = '';

            // Check that we have a valid column name
            // Relation::getDisplayField() returns false by default
            if ($relation[2] !== '' && $relation[2] !== false) {
                $dispval = $this->getFromForeign($relation, $whereComparison);
            }

            if ($printView == '1') {
                if ($transformationPlugin !== null) {
                    $value .= $transformationPlugin->applyTransformation($data, $transformOptions, $meta);
                } else {
                    $value .= Core::mimeDefaultFunction($data);
                }

                $value .= ' <code>[-&gt;' . $dispval . ']</code>';
            } else {
                $sqlQuery = 'SELECT * FROM '
                    . Util::backquote($relation[3]) . '.'
                    . Util::backquote($relation[0])
                    . ' WHERE '
                    . Util::backquote($relation[1])
                    . $whereComparison;

                $urlParams = [
                    'db' => $relation[3],
                    'table' => $relation[0],
                    'pos' => '0',
                    'sql_signature' => Core::signSqlQuery($sqlQuery),
                    'sql_query' => $sqlQuery,
                ];

                if ($transformationPlugin !== null) {
                    // always apply a transformation on the real data,
                    // not on the display field
                    $displayedData = $transformationPlugin->applyTransformation($data, $transformOptions, $meta);
                } elseif ($relationalDisplay === self::RELATIONAL_DISPLAY_COLUMN && $relation[2]) {
                    // user chose "relational display field" in the
                    // display options, so show display field in the cell
                    $displayedData = $dispval === null ? '<em>NULL</em>' : Core::mimeDefaultFunction($dispval);
                } else {
                    // otherwise display data in the cell
                    $displayedData = Core::mimeDefaultFunction($displayedData);
                }

                if ($relationalDisplay === self::RELATIONAL_KEY) {
                    // user chose "relational key" in the display options, so
                    // the title contains the display field
                    $title = htmlspecialchars($dispval ?? '');
                } else {
                    $title = htmlspecialchars($data);
                }

                $tagParams = ['title' => $title];
                if (str_contains($class, 'grid_edit')) {
                    $tagParams['class'] = 'ajax';
                }

                $value .= Generator::linkOrButton(
                    Url::getFromRoute('/sql'),
                    $urlParams,
                    $displayedData,
                    $tagParams
                );
            }
        } elseif ($transformationPlugin !== null) {
            $value .= $transformationPlugin->applyTransformation($data, $transformOptions, $meta);
        } else {
            $value .= Core::mimeDefaultFunction($data);
        }

        return $this->template->render('display/results/row_data', [
            'value' => $value,
            'td_class' => $tableDataCellClass,
            'decimals' => $meta->decimals,
            'type' => $meta->getMappedType(),
            'original_length' => $originalLength,
        ]);
    }

    /**
     * Truncates given string based on LimitChars configuration
     * and Session pftext variable
     * (string is truncated only if necessary)
     *
     * @see handleNonPrintableContents(), getDataCellForGeometryColumns(), getDataCellForNonNumericColumns
     *
     * @param string $str string to be truncated
     *
     * @return array
     * @psalm-return array{bool, string, int}
     */
    private function getPartialText($str): array
    {
        $originalLength = mb_strlen($str);
        if (
            $originalLength > $GLOBALS['cfg']['LimitChars']
            && $_SESSION['tmpval']['pftext'] === self::DISPLAY_PARTIAL_TEXT
        ) {
            $str = mb_substr($str, 0, (int) $GLOBALS['cfg']['LimitChars']) . '...';
            $truncated = true;
        } else {
            $truncated = false;
        }

        return [
            $truncated,
            $str,
            $originalLength,
        ];
    }
}
