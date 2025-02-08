<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\Table\AbstractController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Partitioning\TablePartitionDefinition;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function count;
use function strpos;
use function strrpos;
use function substr;
use function trim;

final class PartitioningController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var CreateAddField */
    private $createAddField;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        DatabaseInterface $dbi,
        CreateAddField $createAddField,
        StructureController $structureController
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->dbi = $dbi;
        $this->createAddField = $createAddField;
        $this->structureController = $structureController;
    }

    public function __invoke(): void
    {
        if (isset($_POST['save_partitioning'])) {
            $this->dbi->selectDb($this->db);
            $this->updatePartitioning();
            ($this->structureController)();

            return;
        }

        $pageSettings = new PageSettings('TableStructure');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        $this->addScriptFiles(['table/structure.js', 'indexes.js']);

        $partitionDetails = null;
        if (! isset($_POST['partition_by'])) {
            $partitionDetails = $this->extractPartitionDetails();
        }

        $storageEngines = StorageEngine::getArray();

        $partitionDetails = TablePartitionDefinition::getDetails($partitionDetails);
        $this->render('table/structure/partition_definition_form', [
            'db' => $this->db,
            'table' => $this->table,
            'partition_details' => $partitionDetails,
            'storage_engines' => $storageEngines,
        ]);
    }

    /**
     * Extracts partition details from CREATE TABLE statement
     *
     * @return array<string, array<int, array<string, mixed>>|bool|int|string>|null array of partition details
     */
    private function extractPartitionDetails(): ?array
    {
        $createTable = (new Table($this->table, $this->db))->showCreate();
        if ($createTable === '') {
            return null;
        }

        $parser = new Parser($createTable);
        /**
         * @var CreateStatement $stmt
         */
        $stmt = $parser->statements[0];

        $partitionDetails = [];

        $partitionDetails['partition_by'] = '';
        $partitionDetails['partition_expr'] = '';
        $partitionDetails['partition_count'] = 0;

        if (! empty($stmt->partitionBy)) {
            $openPos = strpos($stmt->partitionBy, '(');
            $closePos = strrpos($stmt->partitionBy, ')');

            if ($openPos !== false && $closePos !== false) {
                $partitionDetails['partition_by'] = trim(substr($stmt->partitionBy, 0, $openPos));
                $partitionDetails['partition_expr'] = trim(substr(
                    $stmt->partitionBy,
                    $openPos + 1,
                    $closePos - ($openPos + 1)
                ));

                $count = $stmt->partitionsNum ?? count($stmt->partitions);

                $partitionDetails['partition_count'] = $count;
            }
        }

        $partitionDetails['subpartition_by'] = '';
        $partitionDetails['subpartition_expr'] = '';
        $partitionDetails['subpartition_count'] = 0;

        if (! empty($stmt->subpartitionBy)) {
            $openPos = strpos($stmt->subpartitionBy, '(');
            $closePos = strrpos($stmt->subpartitionBy, ')');

            if ($openPos !== false && $closePos !== false) {
                $partitionDetails['subpartition_by'] = trim(substr($stmt->subpartitionBy, 0, $openPos));
                $partitionDetails['subpartition_expr'] = trim(substr(
                    $stmt->subpartitionBy,
                    $openPos + 1,
                    $closePos - ($openPos + 1)
                ));

                $count = $stmt->subpartitionsNum ?? count($stmt->partitions[0]->subpartitions);

                $partitionDetails['subpartition_count'] = $count;
            }
        }

        // Only LIST and RANGE type parameters allow subpartitioning
        $partitionDetails['can_have_subpartitions'] = $partitionDetails['partition_count'] > 1
            && ($partitionDetails['partition_by'] === 'RANGE'
                || $partitionDetails['partition_by'] === 'RANGE COLUMNS'
                || $partitionDetails['partition_by'] === 'LIST'
                || $partitionDetails['partition_by'] === 'LIST COLUMNS');

        // Values are specified only for LIST and RANGE type partitions
        $partitionDetails['value_enabled'] = isset($partitionDetails['partition_by'])
            && ($partitionDetails['partition_by'] === 'RANGE'
                || $partitionDetails['partition_by'] === 'RANGE COLUMNS'
                || $partitionDetails['partition_by'] === 'LIST'
                || $partitionDetails['partition_by'] === 'LIST COLUMNS');

        $partitionDetails['partitions'] = [];

        for ($i = 0, $iMax = $partitionDetails['partition_count']; $i < $iMax; $i++) {
            if (! isset($stmt->partitions[$i])) {
                $partitionDetails['partitions'][$i] = [
                    'name' => 'p' . $i,
                    'value_type' => '',
                    'value' => '',
                    'engine' => '',
                    'comment' => '',
                    'data_directory' => '',
                    'index_directory' => '',
                    'max_rows' => '',
                    'min_rows' => '',
                    'tablespace' => '',
                    'node_group' => '',
                ];
            } else {
                $p = $stmt->partitions[$i];
                $type = $p->type;
                $expr = trim((string) $p->expr, '()');
                if ($expr === 'MAXVALUE') {
                    $type .= ' MAXVALUE';
                    $expr = '';
                }

                $partitionDetails['partitions'][$i] = [
                    'name' => $p->name,
                    'value_type' => $type,
                    'value' => $expr,
                    'engine' => $p->options->has('ENGINE', true),
                    'comment' => trim((string) $p->options->has('COMMENT', true), "'"),
                    'data_directory' => trim((string) $p->options->has('DATA DIRECTORY', true), "'"),
                    'index_directory' => trim((string) $p->options->has('INDEX_DIRECTORY', true), "'"),
                    'max_rows' => $p->options->has('MAX_ROWS', true),
                    'min_rows' => $p->options->has('MIN_ROWS', true),
                    'tablespace' => $p->options->has('TABLESPACE', true),
                    'node_group' => $p->options->has('NODEGROUP', true),
                ];
            }

            $partition =& $partitionDetails['partitions'][$i];
            $partition['prefix'] = 'partitions[' . $i . ']';

            if ($partitionDetails['subpartition_count'] <= 1) {
                continue;
            }

            $partition['subpartition_count'] = $partitionDetails['subpartition_count'];
            $partition['subpartitions'] = [];

            for ($j = 0, $jMax = $partitionDetails['subpartition_count']; $j < $jMax; $j++) {
                if (! isset($stmt->partitions[$i]->subpartitions[$j])) {
                    $partition['subpartitions'][$j] = [
                        'name' => $partition['name'] . '_s' . $j,
                        'engine' => '',
                        'comment' => '',
                        'data_directory' => '',
                        'index_directory' => '',
                        'max_rows' => '',
                        'min_rows' => '',
                        'tablespace' => '',
                        'node_group' => '',
                    ];
                } else {
                    $sp = $stmt->partitions[$i]->subpartitions[$j];
                    $partition['subpartitions'][$j] = [
                        'name' => $sp->name,
                        'engine' => $sp->options->has('ENGINE', true),
                        'comment' => trim((string) $sp->options->has('COMMENT', true), "'"),
                        'data_directory' => trim((string) $sp->options->has('DATA DIRECTORY', true), "'"),
                        'index_directory' => trim((string) $sp->options->has('INDEX_DIRECTORY', true), "'"),
                        'max_rows' => $sp->options->has('MAX_ROWS', true),
                        'min_rows' => $sp->options->has('MIN_ROWS', true),
                        'tablespace' => $sp->options->has('TABLESPACE', true),
                        'node_group' => $sp->options->has('NODEGROUP', true),
                    ];
                }

                $subpartition =& $partition['subpartitions'][$j];
                $subpartition['prefix'] = 'partitions[' . $i . ']'
                    . '[subpartitions][' . $j . ']';
            }
        }

        return $partitionDetails;
    }

    private function updatePartitioning(): void
    {
        $sql_query = 'ALTER TABLE ' . Util::backquote($this->table) . ' '
            . $this->createAddField->getPartitionsDefinition();

        // Execute alter query
        $result = $this->dbi->tryQuery($sql_query);

        if ($result === false) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(
                'message',
                Message::rawError(
                    __('Query error') . ':<br>' . $this->dbi->getError()
                )
            );

            return;
        }

        $message = Message::success(
            __('Table %1$s has been altered successfully.')
        );
        $message->addParam($this->table);
        $this->response->addHTML(
            Generator::getMessage($message, $sql_query, 'success')
        );
    }
}
