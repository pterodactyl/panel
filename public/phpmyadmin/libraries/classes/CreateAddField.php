<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Html\Generator;

use function count;
use function implode;
use function in_array;
use function intval;
use function json_decode;
use function min;
use function preg_replace;
use function rtrim;
use function strlen;
use function trim;

/**
 * Set of functions for /table/create and /table/add-field
 */
class CreateAddField
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param DatabaseInterface $dbi DatabaseInterface interface
     */
    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
    }

    /**
     * Transforms the radio button field_key into 4 arrays
     *
     * @return array An array of arrays which represents column keys for each index type
     * @psalm-return array{int, array, array, array, array, array}
     */
    private function getIndexedColumns(): array
    {
        $fieldCount = count($_POST['field_name']);
        $fieldPrimary = json_decode($_POST['primary_indexes'], true);
        $fieldIndex = json_decode($_POST['indexes'], true);
        $fieldUnique = json_decode($_POST['unique_indexes'], true);
        $fieldFullText = json_decode($_POST['fulltext_indexes'], true);
        $fieldSpatial = json_decode($_POST['spatial_indexes'], true);

        return [
            $fieldCount,
            $fieldPrimary,
            $fieldIndex,
            $fieldUnique,
            $fieldFullText,
            $fieldSpatial,
        ];
    }

    /**
     * Initiate the column creation statement according to the table creation or
     * add columns to a existing table
     *
     * @param int  $fieldCount    number of columns
     * @param bool $isCreateTable true if requirement is to get the statement
     *                            for table creation
     *
     * @return array An array of initial sql statements
     *                             according to the request
     */
    private function buildColumnCreationStatement(
        int $fieldCount,
        bool $isCreateTable = true
    ): array {
        $definitions = [];
        $previousField = -1;
        for ($i = 0; $i < $fieldCount; ++$i) {
            // '0' is also empty for php :-(
            if (strlen($_POST['field_name'][$i]) === 0) {
                continue;
            }

            $definition = $this->getStatementPrefix($isCreateTable) . Table::generateFieldSpec(
                rtrim($_POST['field_name'][$i]),
                $_POST['field_type'][$i],
                $_POST['field_length'][$i],
                $_POST['field_attribute'][$i],
                $_POST['field_collation'][$i] ?? '',
                $_POST['field_null'][$i] ?? 'NO',
                $_POST['field_default_type'][$i],
                $_POST['field_default_value'][$i],
                $_POST['field_extra'][$i] ?? false,
                $_POST['field_comments'][$i] ?? '',
                $_POST['field_virtuality'][$i] ?? '',
                $_POST['field_expression'][$i] ?? ''
            );

            $definition .= $this->setColumnCreationStatementSuffix($previousField, $isCreateTable);
            $previousField = $i;
            $definitions[] = $definition;
        }

        return $definitions;
    }

    /**
     * Set column creation suffix according to requested position of the new column
     *
     * @param int  $previousField previous field for ALTER statement
     * @param bool $isCreateTable true if requirement is to get the statement
     *                            for table creation
     *
     * @return string suffix
     */
    private function setColumnCreationStatementSuffix(
        int $previousField,
        bool $isCreateTable = true
    ): string {
        // no suffix is needed if request is a table creation
        if ($isCreateTable) {
            return ' ';
        }

        if ((string) $_POST['field_where'] === 'last') {
            return ' ';
        }

        // Only the first field can be added somewhere other than at the end
        if ($previousField === -1) {
            if ((string) $_POST['field_where'] === 'first') {
                return ' FIRST';
            }

            if (! empty($_POST['after_field'])) {
                return ' AFTER '
                        . Util::backquote($_POST['after_field']);
            }

            return ' ';
        }

        return ' AFTER ' . Util::backquote($_POST['field_name'][$previousField]);
    }

    /**
     * Create relevant index statements
     *
     * @param array  $index         an array of index columns
     * @param string $indexChoice   index choice that which represents
     *                              the index type of $indexed_fields
     * @param bool   $isCreateTable true if requirement is to get the statement
     *                              for table creation
     *
     * @return string sql statement for indexes
     */
    private function buildIndexStatement(
        array $index,
        string $indexChoice,
        bool $isCreateTable = true
    ): string {
        if ($index === []) {
            return '';
        }

        $sqlQuery = $this->getStatementPrefix($isCreateTable) . $indexChoice;

        if (! empty($index['Key_name']) && $index['Key_name'] !== 'PRIMARY') {
            $sqlQuery .= ' ' . Util::backquote($index['Key_name']);
        }

        $indexFields = [];
        foreach ($index['columns'] as $key => $column) {
            $indexFields[$key] = Util::backquote(rtrim($_POST['field_name'][$column['col_index']]));
            if (! $column['size']) {
                continue;
            }

            $indexFields[$key] .= '(' . $column['size'] . ')';
        }

        $sqlQuery .= ' (' . implode(', ', $indexFields) . ')';

        if ($index['Key_block_size']) {
            $sqlQuery .= ' KEY_BLOCK_SIZE = '
                 . $this->dbi->escapeString($index['Key_block_size']);
        }

        // specifying index type is allowed only for primary, unique and index only
        if (
            $index['Index_choice'] !== 'SPATIAL'
            && $index['Index_choice'] !== 'FULLTEXT'
            && in_array($index['Index_type'], Index::getIndexTypes())
        ) {
            $sqlQuery .= ' USING ' . $index['Index_type'];
        }

        if ($index['Index_choice'] === 'FULLTEXT' && $index['Parser']) {
            $sqlQuery .= ' WITH PARSER ' . $this->dbi->escapeString($index['Parser']);
        }

        if ($index['Index_comment']) {
            $sqlQuery .= " COMMENT '" . $this->dbi->escapeString($index['Index_comment']) . "'";
        }

        return $sqlQuery;
    }

    /**
     * Statement prefix for the buildColumnCreationStatement()
     *
     * @param bool $isCreateTable true if requirement is to get the statement
     *                            for table creation
     *
     * @return string prefix
     */
    private function getStatementPrefix(bool $isCreateTable = true): string
    {
        return $isCreateTable ? '' : 'ADD ';
    }

    /**
     * Returns sql statement according to the column and index specifications as
     * requested
     *
     * @param bool $isCreateTable true if requirement is to get the statement
     *                            for table creation
     *
     * @return string sql statement
     */
    private function getColumnCreationStatements(bool $isCreateTable = true): string
    {
        $sqlStatement = '';
        [
            $fieldCount,
            $fieldPrimary,
            $fieldIndex,
            $fieldUnique,
            $fieldFullText,
            $fieldSpatial,
        ] = $this->getIndexedColumns();
        $definitions = $this->buildColumnCreationStatement($fieldCount, $isCreateTable);

        // Builds the PRIMARY KEY statements
        if (isset($fieldPrimary[0])) {
            $definitions[] = $this->buildIndexStatement($fieldPrimary[0], 'PRIMARY KEY', $isCreateTable);
        }

        // Builds the INDEX statements
        foreach ($fieldIndex as $index) {
            $definitions[] = $this->buildIndexStatement($index, 'INDEX', $isCreateTable);
        }

        // Builds the UNIQUE statements
        foreach ($fieldUnique as $index) {
            $definitions[] = $this->buildIndexStatement($index, 'UNIQUE', $isCreateTable);
        }

        // Builds the FULLTEXT statements
        foreach ($fieldFullText as $index) {
            $definitions[] = $this->buildIndexStatement($index, 'FULLTEXT', $isCreateTable);
        }

        // Builds the SPATIAL statements
        foreach ($fieldSpatial as $index) {
            $definitions[] = $this->buildIndexStatement($index, 'SPATIAL', $isCreateTable);
        }

        if ($definitions !== []) {
            $sqlStatement = implode(', ', $definitions);
        }

        return preg_replace('@, $@', '', $sqlStatement) ?? '';
    }

    /**
     * Returns the partitioning clause
     *
     * @return string partitioning clause
     */
    public function getPartitionsDefinition(): string
    {
        $sqlQuery = '';
        if (
            ! empty($_POST['partition_by'])
            && ! empty($_POST['partition_expr'])
            && ! empty($_POST['partition_count'])
            && $_POST['partition_count'] > 1
        ) {
            $sqlQuery .= ' PARTITION BY ' . $_POST['partition_by']
                . ' (' . $_POST['partition_expr'] . ')'
                . ' PARTITIONS ' . $_POST['partition_count'];
        }

        if (
            ! empty($_POST['subpartition_by'])
            && ! empty($_POST['subpartition_expr'])
            && ! empty($_POST['subpartition_count'])
            && $_POST['subpartition_count'] > 1
        ) {
            $sqlQuery .= ' SUBPARTITION BY ' . $_POST['subpartition_by']
               . ' (' . $_POST['subpartition_expr'] . ')'
               . ' SUBPARTITIONS ' . $_POST['subpartition_count'];
        }

        if (! empty($_POST['partitions'])) {
            $partitions = [];
            foreach ($_POST['partitions'] as $partition) {
                $partitions[] = $this->getPartitionDefinition($partition);
            }

            $sqlQuery .= ' (' . implode(', ', $partitions) . ')';
        }

        return $sqlQuery;
    }

    /**
     * Returns the definition of a partition/subpartition
     *
     * @param array $partition      array of partition/subpartition details
     * @param bool  $isSubPartition whether a subpartition
     *
     * @return string partition/subpartition definition
     */
    private function getPartitionDefinition(
        array $partition,
        bool $isSubPartition = false
    ): string {
        $sqlQuery = ' ' . ($isSubPartition ? 'SUB' : '') . 'PARTITION ';
        $sqlQuery .= $partition['name'];

        if (! empty($partition['value_type'])) {
            $sqlQuery .= ' VALUES ' . $partition['value_type'];

            if ($partition['value_type'] !== 'LESS THAN MAXVALUE') {
                $sqlQuery .= ' (' . $partition['value'] . ')';
            }
        }

        if (! empty($partition['engine'])) {
            $sqlQuery .= ' ENGINE = ' . $partition['engine'];
        }

        if (! empty($partition['comment'])) {
            $sqlQuery .= " COMMENT = '" . $partition['comment'] . "'";
        }

        if (! empty($partition['data_directory'])) {
            $sqlQuery .= " DATA DIRECTORY = '" . $partition['data_directory'] . "'";
        }

        if (! empty($partition['index_directory'])) {
            $sqlQuery .= " INDEX_DIRECTORY = '" . $partition['index_directory'] . "'";
        }

        if (! empty($partition['max_rows'])) {
            $sqlQuery .= ' MAX_ROWS = ' . $partition['max_rows'];
        }

        if (! empty($partition['min_rows'])) {
            $sqlQuery .= ' MIN_ROWS = ' . $partition['min_rows'];
        }

        if (! empty($partition['tablespace'])) {
            $sqlQuery .= ' TABLESPACE = ' . $partition['tablespace'];
        }

        if (! empty($partition['node_group'])) {
            $sqlQuery .= ' NODEGROUP = ' . $partition['node_group'];
        }

        if (! empty($partition['subpartitions'])) {
            $subpartitions = [];
            foreach ($partition['subpartitions'] as $subpartition) {
                $subpartitions[] = $this->getPartitionDefinition($subpartition, true);
            }

            $sqlQuery .= ' (' . implode(', ', $subpartitions) . ')';
        }

        return $sqlQuery;
    }

    /**
     * Function to get table creation sql query
     *
     * @param string $db    database name
     * @param string $table table name
     */
    public function getTableCreationQuery(string $db, string $table): string
    {
        // get column addition statements
        $sqlStatement = $this->getColumnCreationStatements(true);

        // Builds the 'create table' statement
        $sqlQuery = 'CREATE TABLE ' . Util::backquote($db) . '.'
            . Util::backquote(trim($table)) . ' (' . $sqlStatement . ')';

        // Adds table type, character set, comments and partition definition
        if (
            ! empty($_POST['tbl_storage_engine'])
            && ($_POST['tbl_storage_engine'] !== 'Default')
            && StorageEngine::isValid($_POST['tbl_storage_engine'])
        ) {
            $sqlQuery .= ' ENGINE = ' . $_POST['tbl_storage_engine'];
        }

        if (! empty($_POST['tbl_collation'])) {
            $sqlQuery .= Util::getCharsetQueryPart($_POST['tbl_collation'] ?? '');
        }

        if (
            ! empty($_POST['connection'])
            && ! empty($_POST['tbl_storage_engine'])
            && $_POST['tbl_storage_engine'] === 'FEDERATED'
        ) {
            $sqlQuery .= " CONNECTION = '"
                . $this->dbi->escapeString($_POST['connection']) . "'";
        }

        if (! empty($_POST['comment'])) {
            $sqlQuery .= ' COMMENT = \''
                . $this->dbi->escapeString($_POST['comment']) . '\'';
        }

        $sqlQuery .= $this->getPartitionsDefinition();
        $sqlQuery .= ';';

        return $sqlQuery;
    }

    /**
     * Function to get the number of fields for the table creation form
     */
    public function getNumberOfFieldsFromRequest(): int
    {
        // Limit to 4096 fields (MySQL maximal value)
        $mysqlLimit = 4096;

        if (isset($_POST['submit_num_fields'])) { // adding new fields
            $numberOfFields = intval($_POST['orig_num_fields']) + intval($_POST['added_fields']);
        } elseif (isset($_POST['orig_num_fields'])) { // retaining existing fields
            $numberOfFields = intval($_POST['orig_num_fields']);
        } elseif (
            isset($_POST['num_fields'])
            && intval($_POST['num_fields']) > 0
        ) { // new table with specified number of fields
            $numberOfFields = intval($_POST['num_fields']);
        } else { // new table with unspecified number of fields
            $numberOfFields = 4;
        }

        return min($numberOfFields, $mysqlLimit);
    }

    /**
     * Function to get the column creation statement
     *
     * @param string $table current table
     */
    public function getColumnCreationQuery(
        string $table
    ): string {
        // get column addition statements
        $sqlStatement = $this->getColumnCreationStatements(false);

        $sqlQuery = 'ALTER TABLE ' .
            Util::backquote($table) . ' ' . $sqlStatement;
        if (isset($_POST['online_transaction'])) {
            $sqlQuery .= ', ALGORITHM=INPLACE, LOCK=NONE';
        }

        return $sqlQuery . ';';
    }

    /**
     * Function to execute the column creation statement
     *
     * @param string $db       current database
     * @param string $sqlQuery the query to run
     * @param string $errorUrl error page url
     */
    public function tryColumnCreationQuery(
        string $db,
        string $sqlQuery,
        string $errorUrl
    ): bool {
        // To allow replication, we first select the db to use and then run queries
        // on this db.
        if (! $this->dbi->selectDb($db)) {
            Generator::mysqlDie(
                $this->dbi->getError(),
                'USE ' . Util::backquote($db),
                false,
                $errorUrl
            );
        }

        return (bool) $this->dbi->tryQuery($sqlQuery);
    }
}
