<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Engines\Innodb;
use PhpMyAdmin\FlashMessages;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Index;
use PhpMyAdmin\Partitioning\Partition;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;
use stdClass;

use function __;
use function in_array;
use function is_string;
use function str_contains;

/**
 * Displays table structure infos like columns, indexes, size, rows
 * and allows manipulation of indexes and columns.
 */
class StructureController extends AbstractController
{
    /** @var Table  The table object */
    protected $tableObj;

    /** @var CreateAddField */
    private $createAddField;

    /** @var Relation */
    private $relation;

    /** @var Transformations */
    private $transformations;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var DatabaseInterface */
    private $dbi;

    /** @var FlashMessages */
    private $flash;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        Relation $relation,
        Transformations $transformations,
        CreateAddField $createAddField,
        RelationCleanup $relationCleanup,
        DatabaseInterface $dbi,
        FlashMessages $flash
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->createAddField = $createAddField;
        $this->relation = $relation;
        $this->transformations = $transformations;
        $this->relationCleanup = $relationCleanup;
        $this->dbi = $dbi;
        $this->flash = $flash;

        $this->tableObj = $this->dbi->getTable($this->db, $this->table);
    }

    public function __invoke(): void
    {
        global $reread_info, $showtable, $db, $table, $cfg, $errorUrl;
        global $tbl_is_view, $tbl_storage_engine, $tbl_collation, $table_info_num_rows;

        $this->dbi->selectDb($this->db);
        $reread_info = $this->tableObj->getStatusInfo(null, true);
        $showtable = $this->tableObj->getStatusInfo(null, (isset($reread_info) && $reread_info));

        if ($this->tableObj->isView()) {
            $tbl_is_view = true;
            $tbl_storage_engine = __('View');
        } else {
            $tbl_is_view = false;
            $tbl_storage_engine = $this->tableObj->getStorageEngine();
        }

        $tbl_collation = $this->tableObj->getCollation();
        $table_info_num_rows = $this->tableObj->getNumRows();

        $pageSettings = new PageSettings('TableStructure');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        $this->addScriptFiles(['table/structure.js', 'indexes.js']);

        $relationParameters = $this->relation->getRelationParameters();

        Util::checkParameters(['db', 'table']);

        $isSystemSchema = Utilities::isSystemSchema($db);
        $url_params = ['db' => $db, 'table' => $table];
        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $errorUrl .= Url::getCommon($url_params, '&');

        DbTableExists::check();

        $primary = Index::getPrimary($this->table, $this->db);
        $columns_with_index = $this->dbi
            ->getTable($this->db, $this->table)
            ->getColumnsWithIndex(Index::UNIQUE | Index::INDEX | Index::SPATIAL | Index::FULLTEXT);
        $columns_with_unique_index = $this->dbi
            ->getTable($this->db, $this->table)
            ->getColumnsWithIndex(Index::UNIQUE);

        $fields = $this->dbi->getColumns($this->db, $this->table, true);

        $this->response->addHTML($this->displayStructure(
            $relationParameters,
            $columns_with_unique_index,
            $primary,
            $fields,
            $columns_with_index,
            $isSystemSchema
        ));
    }

    /**
     * Displays the table structure ('show table' works correct since 3.23.03)
     *
     * @param array       $columns_with_unique_index Columns with unique index
     * @param Index|false $primary_index             primary index or false if no one exists
     * @param array       $fields                    Fields
     * @param array       $columns_with_index        Columns with index
     *
     * @return string
     */
    protected function displayStructure(
        RelationParameters $relationParameters,
        array $columns_with_unique_index,
        $primary_index,
        array $fields,
        array $columns_with_index,
        bool $isSystemSchema
    ) {
        global $route, $tbl_is_view, $tbl_storage_engine;

        // prepare comments
        $comments_map = [];
        $mime_map = [];

        if ($GLOBALS['cfg']['ShowPropertyComments']) {
            $comments_map = $this->relation->getComments($this->db, $this->table);
            if ($relationParameters->browserTransformationFeature !== null && $GLOBALS['cfg']['BrowseMIME']) {
                $mime_map = $this->transformations->getMime($this->db, $this->table, true);
            }
        }

        $centralColumns = new CentralColumns($this->dbi);
        $central_list = $centralColumns->getFromTable($this->db, $this->table);

        /**
         * Displays Space usage and row statistics
         */
        // BEGIN - Calc Table Space
        // Get valid statistics whatever is the table type
        if ($GLOBALS['cfg']['ShowStats']) {
            //get table stats in HTML format
            $tablestats = $this->getTableStats($isSystemSchema);
            //returning the response in JSON format to be used by Ajax
            $this->response->addJSON('tableStat', $tablestats);
        }

        // END - Calc Table Space

        // logic removed from Template
        $rownum = 0;
        $columns_list = [];
        $attributes = [];
        $displayed_fields = [];
        $row_comments = [];
        $extracted_columnspecs = [];
        $collations = [];
        foreach ($fields as &$field) {
            ++$rownum;
            $columns_list[] = $field['Field'];

            $extracted_columnspecs[$rownum] = Util::extractColumnSpec($field['Type']);
            $attributes[$rownum] = $extracted_columnspecs[$rownum]['attribute'];
            if (str_contains($field['Extra'], 'on update CURRENT_TIMESTAMP')) {
                $attributes[$rownum] = 'on update CURRENT_TIMESTAMP';
            }

            $displayed_fields[$rownum] = new stdClass();
            $displayed_fields[$rownum]->text = $field['Field'];
            $displayed_fields[$rownum]->icon = '';
            $row_comments[$rownum] = '';

            if (isset($comments_map[$field['Field']])) {
                $displayed_fields[$rownum]->comment = $comments_map[$field['Field']];
                $row_comments[$rownum] = $comments_map[$field['Field']];
            }

            if ($primary_index && $primary_index->hasColumn($field['Field'])) {
                $displayed_fields[$rownum]->icon .= Generator::getImage('b_primary', __('Primary'));
            }

            if (in_array($field['Field'], $columns_with_index)) {
                $displayed_fields[$rownum]->icon .= Generator::getImage('bd_primary', __('Index'));
            }

            $collation = Charsets::findCollationByName(
                $this->dbi,
                $GLOBALS['cfg']['Server']['DisableIS'],
                $field['Collation'] ?? ''
            );
            if ($collation === null) {
                continue;
            }

            $collations[$collation->getName()] = [
                'name' => $collation->getName(),
                'description' => $collation->getDescription(),
            ];
        }

        $engine = $this->tableObj->getStorageEngine();

        return $this->template->render('table/structure/display_structure', [
            'collations' => $collations,
            'is_foreign_key_supported' => ForeignKey::isSupported($engine),
            'indexes' => Index::getFromTable($this->table, $this->db),
            'indexes_duplicates' => Index::findDuplicates($this->table, $this->db),
            'relation_parameters' => $relationParameters,
            'hide_structure_actions' => $GLOBALS['cfg']['HideStructureActions'] === true,
            'db' => $this->db,
            'table' => $this->table,
            'db_is_system_schema' => $isSystemSchema,
            'tbl_is_view' => $tbl_is_view,
            'mime_map' => $mime_map,
            'tbl_storage_engine' => $tbl_storage_engine,
            'primary' => $primary_index,
            'columns_with_unique_index' => $columns_with_unique_index,
            'columns_list' => $columns_list,
            'table_stats' => $tablestats ?? null,
            'fields' => $fields,
            'extracted_columnspecs' => $extracted_columnspecs,
            'columns_with_index' => $columns_with_index,
            'central_list' => $central_list,
            'comments_map' => $comments_map,
            'browse_mime' => $GLOBALS['cfg']['BrowseMIME'],
            'show_column_comments' => $GLOBALS['cfg']['ShowColumnComments'],
            'show_stats' => $GLOBALS['cfg']['ShowStats'],
            'mysql_int_version' => $this->dbi->getVersion(),
            'is_mariadb' => $this->dbi->isMariaDB(),
            'text_dir' => $GLOBALS['text_dir'],
            'is_active' => Tracker::isActive(),
            'have_partitioning' => Partition::havePartitioning(),
            'partitions' => Partition::getPartitions($this->db, $this->table),
            'partition_names' => Partition::getPartitionNames($this->db, $this->table),
            'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
            'attributes' => $attributes,
            'displayed_fields' => $displayed_fields,
            'row_comments' => $row_comments,
            'route' => $route,
        ]);
    }

    /**
     * Get HTML snippet for display table statistics
     *
     * @return string
     */
    protected function getTableStats(bool $isSystemSchema)
    {
        global $showtable, $tbl_is_view;
        global $tbl_storage_engine, $table_info_num_rows, $tbl_collation;

        if (empty($showtable)) {
            $showtable = $this->dbi->getTable($this->db, $this->table)->getStatusInfo(null, true);
        }

        if (is_string($showtable)) {
            $showtable = [];
        }

        if (empty($showtable['Data_length'])) {
            $showtable['Data_length'] = 0;
        }

        if (empty($showtable['Index_length'])) {
            $showtable['Index_length'] = 0;
        }

        $is_innodb = (isset($showtable['Type'])
            && $showtable['Type'] === 'InnoDB');

        $mergetable = $this->tableObj->isMerge();

        // this is to display for example 261.2 MiB instead of 268k KiB
        $max_digits = 3;
        $decimals = 1;
        [$data_size, $data_unit] = Util::formatByteDown($showtable['Data_length'], $max_digits, $decimals);
        if ($mergetable === false) {
            [$index_size, $index_unit] = Util::formatByteDown($showtable['Index_length'], $max_digits, $decimals);
        }

        if (isset($showtable['Data_free'])) {
            [$free_size, $free_unit] = Util::formatByteDown($showtable['Data_free'], $max_digits, $decimals);
            [$effect_size, $effect_unit] = Util::formatByteDown(
                $showtable['Data_length']
                + $showtable['Index_length']
                - $showtable['Data_free'],
                $max_digits,
                $decimals
            );
        } else {
            [$effect_size, $effect_unit] = Util::formatByteDown(
                $showtable['Data_length']
                + $showtable['Index_length'],
                $max_digits,
                $decimals
            );
        }

        [$tot_size, $tot_unit] = Util::formatByteDown(
            $showtable['Data_length'] + $showtable['Index_length'],
            $max_digits,
            $decimals
        );

        $avg_size = '';
        $avg_unit = '';
        if ($table_info_num_rows > 0) {
            [$avg_size, $avg_unit] = Util::formatByteDown(
                ($showtable['Data_length']
                + $showtable['Index_length'])
                / $showtable['Rows'],
                6,
                1
            );
        }

        /** @var Innodb $innodbEnginePlugin */
        $innodbEnginePlugin = StorageEngine::getEngine('Innodb');
        $innodb_file_per_table = $innodbEnginePlugin->supportsFilePerTable();

        $tableCollation = [];
        $collation = Charsets::findCollationByName($this->dbi, $GLOBALS['cfg']['Server']['DisableIS'], $tbl_collation);
        if ($collation !== null) {
            $tableCollation = [
                'name' => $collation->getName(),
                'description' => $collation->getDescription(),
            ];
        }

        return $this->template->render('table/structure/display_table_stats', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'showtable' => $showtable,
            'table_info_num_rows' => $table_info_num_rows,
            'tbl_is_view' => $tbl_is_view,
            'db_is_system_schema' => $isSystemSchema,
            'tbl_storage_engine' => $tbl_storage_engine,
            'table_collation' => $tableCollation,
            'is_innodb' => $is_innodb,
            'mergetable' => $mergetable,
            'avg_size' => $avg_size ?? null,
            'avg_unit' => $avg_unit ?? null,
            'data_size' => $data_size,
            'data_unit' => $data_unit,
            'index_size' => $index_size ?? null,
            'index_unit' => $index_unit ?? null,
            'innodb_file_per_table' => $innodb_file_per_table,
            'free_size' => $free_size ?? null,
            'free_unit' => $free_unit ?? null,
            'effect_size' => $effect_size,
            'effect_unit' => $effect_unit,
            'tot_size' => $tot_size,
            'tot_unit' => $tot_unit,
        ]);
    }
}
