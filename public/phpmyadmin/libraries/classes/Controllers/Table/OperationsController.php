<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Partitioning\Partition;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;
use function implode;
use function mb_strstr;
use function mb_strtolower;
use function mb_strtoupper;
use function preg_replace;
use function strlen;
use function urldecode;

class OperationsController extends AbstractController
{
    /** @var Operations */
    private $operations;

    /** @var CheckUserPrivileges */
    private $checkUserPrivileges;

    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        Operations $operations,
        CheckUserPrivileges $checkUserPrivileges,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->operations = $operations;
        $this->checkUserPrivileges = $checkUserPrivileges;
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $urlParams, $reread_info, $tbl_is_view, $tbl_storage_engine;
        global $show_comment, $tbl_collation, $table_info_num_rows, $row_format, $auto_increment, $create_options;
        global $table_alters, $warning_messages, $lowerCaseNames, $db, $table, $reload, $result;
        global $new_tbl_storage_engine, $sql_query, $message_to_show, $columns, $hideOrderTable, $indexes;
        global $notNull, $comment, $errorUrl, $cfg;

        $this->checkUserPrivileges->getPrivileges();

        // lower_case_table_names=1 `DB` becomes `db`
        $lowerCaseNames = $this->dbi->getLowerCaseNames() === '1';

        if ($lowerCaseNames) {
            $table = mb_strtolower($table);
        }

        $pma_table = $this->dbi->getTable($db, $table);

        $this->addScriptFiles(['table/operations.js']);

        Util::checkParameters(['db', 'table']);

        $isSystemSchema = Utilities::isSystemSchema($db);
        $urlParams = ['db' => $db, 'table' => $table];
        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $errorUrl .= Url::getCommon($urlParams, '&');

        DbTableExists::check();

        $urlParams['goto'] = $urlParams['back'] = Url::getFromRoute('/table/operations');

        $relationParameters = $this->relation->getRelationParameters();

        /**
         * Reselect current db (needed in some cases probably due to the calling of {@link Relation})
         */
        $this->dbi->selectDb($db);

        $reread_info = $pma_table->getStatusInfo(null, false);
        $GLOBALS['showtable'] = $pma_table->getStatusInfo(null, (isset($reread_info) && $reread_info));
        if ($pma_table->isView()) {
            $tbl_is_view = true;
            $tbl_storage_engine = __('View');
            $show_comment = null;
        } else {
            $tbl_is_view = false;
            $tbl_storage_engine = $pma_table->getStorageEngine();
            $show_comment = $pma_table->getComment();
        }

        $tbl_collation = $pma_table->getCollation();
        $table_info_num_rows = $pma_table->getNumRows();
        $row_format = $pma_table->getRowFormat();
        $auto_increment = $pma_table->getAutoIncrement();
        $create_options = $pma_table->getCreateOptions();

        // set initial value of these variables, based on the current table engine
        if ($pma_table->isEngine('ARIA')) {
            // the value for transactional can be implicit
            // (no create option found, in this case it means 1)
            // or explicit (option found with a value of 0 or 1)
            // ($create_options['transactional'] may have been set by Table class,
            // from the $create_options)
            $create_options['transactional'] = ($create_options['transactional'] ?? '') == '0'
                ? '0'
                : '1';
            $create_options['page_checksum'] = $create_options['page_checksum'] ?? '';
        }

        $pma_table = $this->dbi->getTable($db, $table);
        $reread_info = false;
        $table_alters = [];

        /**
         * If the table has to be moved to some other database
         */
        if (isset($_POST['submit_move']) || isset($_POST['submit_copy'])) {
            $message = $this->operations->moveOrCopyTable($db, $table);

            if (! $this->response->isAjax()) {
                return;
            }

            $this->response->addJSON('message', $message);

            if ($message->isSuccess()) {
                if (isset($_POST['submit_move'], $_POST['target_db'])) {
                    $db = $_POST['target_db'];// Used in Header::getJsParams()
                }

                $this->response->addJSON('db', $db);

                return;
            }

            $this->response->setRequestStatus(false);

            return;
        }

        /**
         * Updates table comment, type and options if required
         */
        if (isset($_POST['submitoptions'])) {
            $_message = '';
            $warning_messages = [];

            if (isset($_POST['new_name'])) {
                // lower_case_table_names=1 `DB` becomes `db`
                if ($lowerCaseNames) {
                    $_POST['new_name'] = mb_strtolower($_POST['new_name']);
                }

                // Get original names before rename operation
                $oldTable = $pma_table->getName();
                $oldDb = $pma_table->getDbName();

                if ($pma_table->rename($_POST['new_name'])) {
                    if (isset($_POST['adjust_privileges']) && ! empty($_POST['adjust_privileges'])) {
                        $this->operations->adjustPrivilegesRenameOrMoveTable(
                            $oldDb,
                            $oldTable,
                            $_POST['db'],
                            $_POST['new_name']
                        );
                    }

                    // Reselect the original DB
                    $db = $oldDb;
                    $this->dbi->selectDb($oldDb);
                    $_message .= $pma_table->getLastMessage();
                    $result = true;
                    $table = $pma_table->getName();
                    $reread_info = true;
                    $reload = true;
                } else {
                    $_message .= $pma_table->getLastError();
                    $result = false;
                }
            }

            if (
                ! empty($_POST['new_tbl_storage_engine'])
                && mb_strtoupper($_POST['new_tbl_storage_engine']) !== $tbl_storage_engine
            ) {
                $new_tbl_storage_engine = mb_strtoupper($_POST['new_tbl_storage_engine']);

                if ($pma_table->isEngine('ARIA')) {
                    $create_options['transactional'] = ($create_options['transactional'] ?? '') == '0'
                        ? '0'
                        : '1';
                    $create_options['page_checksum'] = $create_options['page_checksum'] ?? '';
                }
            } else {
                $new_tbl_storage_engine = '';
            }

            $row_format = $create_options['row_format'] ?? $pma_table->getRowFormat();

            $table_alters = $this->operations->getTableAltersArray(
                $pma_table,
                $create_options['pack_keys'],
                (empty($create_options['checksum']) ? '0' : '1'),
                ($create_options['page_checksum'] ?? ''),
                (empty($create_options['delay_key_write']) ? '0' : '1'),
                $row_format,
                $new_tbl_storage_engine,
                (isset($create_options['transactional']) && $create_options['transactional'] == '0' ? '0' : '1'),
                $tbl_collation
            );

            if (count($table_alters) > 0) {
                $sql_query = 'ALTER TABLE '
                    . Util::backquote($table);
                $sql_query .= "\r\n" . implode("\r\n", $table_alters);
                $sql_query .= ';';
                $result = (bool) $this->dbi->query($sql_query);
                $reread_info = true;
                unset($table_alters);
                $warning_messages = $this->operations->getWarningMessagesArray();
            }

            if (! empty($_POST['tbl_collation']) && ! empty($_POST['change_all_collations'])) {
                $this->operations->changeAllColumnsCollation($db, $table, $_POST['tbl_collation']);
            }

            if (isset($_POST['tbl_collation']) && empty($_POST['tbl_collation'])) {
                if ($this->response->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON(
                        'message',
                        Message::error(__('No collation provided.'))
                    );

                    return;
                }
            }
        }

        /**
         * Reordering the table has been requested by the user
         */
        if (isset($_POST['submitorderby']) && ! empty($_POST['order_field'])) {
            $sql_query = QueryGenerator::getQueryForReorderingTable(
                $table,
                urldecode($_POST['order_field']),
                $_POST['order_order'] ?? null
            );
            $result = $this->dbi->query($sql_query);
        }

        /**
         * A partition operation has been requested by the user
         */
        if (isset($_POST['submit_partition']) && ! empty($_POST['partition_operation'])) {
            $sql_query = QueryGenerator::getQueryForPartitioningTable(
                $table,
                $_POST['partition_operation'],
                $_POST['partition_name']
            );
            $result = $this->dbi->query($sql_query);
        }

        if ($reread_info) {
            // to avoid showing the old value (for example the AUTO_INCREMENT) after
            // a change, clear the cache
            $this->dbi->getCache()->clearTableCache();
            $this->dbi->selectDb($db);
            $GLOBALS['showtable'] = $pma_table->getStatusInfo(null, true);
            if ($pma_table->isView()) {
                $tbl_is_view = true;
                $tbl_storage_engine = __('View');
                $show_comment = null;
            } else {
                $tbl_is_view = false;
                $tbl_storage_engine = $pma_table->getStorageEngine();
                $show_comment = $pma_table->getComment();
            }

            $tbl_collation = $pma_table->getCollation();
            $table_info_num_rows = $pma_table->getNumRows();
            $row_format = $pma_table->getRowFormat();
            $auto_increment = $pma_table->getAutoIncrement();
            $create_options = $pma_table->getCreateOptions();
        }

        unset($reread_info);

        if (isset($result) && empty($message_to_show)) {
            if (empty($_message)) {
                if (empty($sql_query)) {
                    $_message = Message::success(__('No change'));
                } else {
                    $_message = $result
                        ? Message::success()
                        : Message::error();
                }

                if ($this->response->isAjax()) {
                    $this->response->setRequestStatus($_message->isSuccess());
                    $this->response->addJSON('message', $_message);
                    if (! empty($sql_query)) {
                        $this->response->addJSON(
                            'sql_query',
                            Generator::getMessage('', $sql_query)
                        );
                    }

                    return;
                }
            } else {
                $_message = $result
                    ? Message::success($_message)
                    : Message::error($_message);
            }

            if (! empty($warning_messages)) {
                $_message = new Message();
                $_message->addMessagesString($warning_messages);
                $_message->isError(true);
                if ($this->response->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $_message);
                    if (! empty($sql_query)) {
                        $this->response->addJSON(
                            'sql_query',
                            Generator::getMessage('', $sql_query)
                        );
                    }

                    return;
                }

                unset($warning_messages);
            }

            if (empty($sql_query)) {
                $this->response->addHTML(
                    $_message->getDisplay()
                );
            } else {
                $this->response->addHTML(
                    Generator::getMessage($_message, $sql_query)
                );
            }

            unset($_message);
        }

        $urlParams['goto'] = $urlParams['back'] = Url::getFromRoute('/table/operations');

        $columns = $this->dbi->getColumns($db, $table);

        $hideOrderTable = false;
        // `ALTER TABLE ORDER BY` does not make sense for InnoDB tables that contain
        // a user-defined clustered index (PRIMARY KEY or NOT NULL UNIQUE index).
        // InnoDB always orders table rows according to such an index if one is present.
        if ($tbl_storage_engine === 'INNODB') {
            $indexes = Index::getFromTable($table, $db);
            foreach ($indexes as $name => $idx) {
                if ($name === 'PRIMARY') {
                    $hideOrderTable = true;
                    break;
                }

                if ($idx->getNonUnique()) {
                    continue;
                }

                $notNull = true;
                foreach ($idx->getColumns() as $column) {
                    if ($column->getNull()) {
                        $notNull = false;
                        break;
                    }
                }

                if ($notNull) {
                    $hideOrderTable = true;
                    break;
                }
            }
        }

        $comment = '';
        if (mb_strstr((string) $show_comment, '; InnoDB free') === false) {
            if (mb_strstr((string) $show_comment, 'InnoDB free') === false) {
                // only user entered comment
                $comment = (string) $show_comment;
            } else {
                // here we have just InnoDB generated part
                $comment = '';
            }
        } else {
            // remove InnoDB comment from end, just the minimal part (*? is non greedy)
            $comment = preg_replace('@; InnoDB free:.*?$@', '', (string) $show_comment);
        }

        $storageEngines = StorageEngine::getArray();

        $charsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);

        $hasPackKeys = isset($create_options['pack_keys'])
            && $pma_table->isEngine(['MYISAM', 'ARIA', 'ISAM']);
        $hasChecksumAndDelayKeyWrite = $pma_table->isEngine(['MYISAM', 'ARIA']);
        $hasTransactionalAndPageChecksum = $pma_table->isEngine('ARIA');
        $hasAutoIncrement = strlen((string) $auto_increment) > 0
            && $pma_table->isEngine(['MYISAM', 'ARIA', 'INNODB', 'PBXT', 'ROCKSDB']);

        $possibleRowFormats = $this->operations->getPossibleRowFormat();

        $databaseList = [];
        if (count($GLOBALS['dblist']->databases) <= $GLOBALS['cfg']['MaxDbList']) {
            $databaseList = $GLOBALS['dblist']->databases->getList();
        }

        $hasForeignKeys = ! empty($this->relation->getForeigners($db, $table, '', 'foreign'));
        $hasPrivileges = $GLOBALS['table_priv'] && $GLOBALS['col_priv'] && $GLOBALS['is_reload_priv'];
        $switchToNew = isset($_SESSION['pma_switch_to_new']) && $_SESSION['pma_switch_to_new'];

        $partitions = [];
        $partitionsChoices = [];

        if (Partition::havePartitioning()) {
            $partitionNames = Partition::getPartitionNames($db, $table);
            if (isset($partitionNames[0])) {
                $partitions = $partitionNames;
                $partitionsChoices = $this->operations->getPartitionMaintenanceChoices();
            }
        }

        $foreigners = $this->operations->getForeignersForReferentialIntegrityCheck(
            $urlParams,
            $relationParameters->relationFeature !== null
        );

        $this->render('table/operations/index', [
            'db' => $db,
            'table' => $table,
            'url_params' => $urlParams,
            'columns' => $columns,
            'hide_order_table' => $hideOrderTable,
            'table_comment' => $comment,
            'storage_engine' => $tbl_storage_engine,
            'storage_engines' => $storageEngines,
            'charsets' => $charsets,
            'collations' => $collations,
            'tbl_collation' => $tbl_collation,
            'row_formats' => $possibleRowFormats[$tbl_storage_engine] ?? [],
            'row_format_current' => $GLOBALS['showtable']['Row_format'],
            'has_auto_increment' => $hasAutoIncrement,
            'auto_increment' => $auto_increment,
            'has_pack_keys' => $hasPackKeys,
            'pack_keys' => $create_options['pack_keys'] ?? '',
            'has_transactional_and_page_checksum' => $hasTransactionalAndPageChecksum,
            'has_checksum_and_delay_key_write' => $hasChecksumAndDelayKeyWrite,
            'delay_key_write' => empty($create_options['delay_key_write']) ? '0' : '1',
            'transactional' => ($create_options['transactional'] ?? '') == '0' ? '0' : '1',
            'page_checksum' => $create_options['page_checksum'] ?? '',
            'checksum' => empty($create_options['checksum']) ? '0' : '1',
            'database_list' => $databaseList,
            'has_foreign_keys' => $hasForeignKeys,
            'has_privileges' => $hasPrivileges,
            'switch_to_new' => $switchToNew,
            'is_system_schema' => $isSystemSchema,
            'is_view' => $tbl_is_view,
            'partitions' => $partitions,
            'partitions_choices' => $partitionsChoices,
            'foreigners' => $foreigners,
        ]);
    }
}
