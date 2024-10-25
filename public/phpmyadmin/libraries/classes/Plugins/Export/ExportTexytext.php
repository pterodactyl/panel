<?php
/**
 * Export to Texy! text.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function in_array;
use function str_replace;
use function stripslashes;

/**
 * Handles the export for the Texy! text class
 */
class ExportTexytext extends ExportPlugin
{
    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'texytext';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('Texy! text');
        $exportPluginProperties->setExtension('txt');
        $exportPluginProperties->setMimeType('text/plain');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // what to dump (structure/data/both) main group
        $dumpWhat = new OptionsPropertyMainGroup(
            'general_opts',
            __('Dump table')
        );
        // create primary items and add them to the group
        $leaf = new RadioPropertyItem('structure_or_data');
        $leaf->setValues(
            [
                'structure' => __('structure'),
                'data' => __('data'),
                'structure_and_data' => __('structure and data'),
            ]
        );
        $dumpWhat->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($dumpWhat);

        // data options main group
        $dataOptions = new OptionsPropertyMainGroup(
            'data',
            __('Data dump options')
        );
        $dataOptions->setForce('structure');
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem(
            'columns',
            __('Put columns names in the first row')
        );
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'null',
            __('Replace NULL with:')
        );
        $dataOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($dataOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);

        return $exportPluginProperties;
    }

    /**
     * Outputs export header
     */
    public function exportHeader(): bool
    {
        return true;
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        return true;
    }

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Alias of db
     */
    public function exportDBHeader($db, $dbAlias = ''): bool
    {
        if (empty($dbAlias)) {
            $dbAlias = $db;
        }

        return $this->export->outputHandler(
            '===' . __('Database') . ' ' . $dbAlias . "\n\n"
        );
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     */
    public function exportDBFooter($db): bool
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db         Database name
     * @param string $exportType 'server', 'database', 'table'
     * @param string $dbAlias    Aliases of db
     */
    public function exportDBCreate($db, $exportType, $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs the content of a table in NHibernate format
     *
     * @param string $db       database name
     * @param string $table    table name
     * @param string $crlf     the end of line sequence
     * @param string $errorUrl the url to go back in case of error
     * @param string $sqlQuery SQL query for obtaining data
     * @param array  $aliases  Aliases of db/table/columns
     */
    public function exportData(
        $db,
        $table,
        $crlf,
        $errorUrl,
        $sqlQuery,
        array $aliases = []
    ): bool {
        global $what, $dbi;

        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);

        if (
            ! $this->export->outputHandler(
                $table_alias != ''
                ? '== ' . __('Dumping data for table') . ' ' . $table_alias . "\n\n"
                : '==' . __('Dumping data for query result') . "\n\n"
            )
        ) {
            return false;
        }

        // Gets the data from the database
        $result = $dbi->query($sqlQuery, DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED);
        $fields_cnt = $result->numFields();

        // If required, get fields name at the first line
        if (isset($GLOBALS[$what . '_columns'])) {
            $text_output = "|------\n";
            foreach ($result->getFieldNames() as $col_as) {
                if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                    $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
                }

                $text_output .= '|'
                    . htmlspecialchars(stripslashes($col_as));
            }

            $text_output .= "\n|------\n";
            if (! $this->export->outputHandler($text_output)) {
                return false;
            }
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $text_output = '';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (! isset($row[$j])) {
                    $value = $GLOBALS[$what . '_null'];
                } elseif ($row[$j] == '0' || $row[$j] != '') {
                    $value = $row[$j];
                } else {
                    $value = ' ';
                }

                $text_output .= '|'
                    . str_replace(
                        '|',
                        '&#124;',
                        htmlspecialchars($value)
                    );
            }

            $text_output .= "\n";
            if (! $this->export->outputHandler($text_output)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Outputs result raw query in TexyText format
     *
     * @param string      $errorUrl the url to go back in case of error
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     * @param string      $crlf     the end of line sequence
     */
    public function exportRawQuery(string $errorUrl, ?string $db, string $sqlQuery, string $crlf): bool
    {
        global $dbi;

        if ($db !== null) {
            $dbi->selectDb($db);
        }

        return $this->exportData($db ?? '', '', $crlf, $errorUrl, $sqlQuery);
    }

    /**
     * Returns a stand-in CREATE definition to resolve view dependencies
     *
     * @param string $db      the database name
     * @param string $view    the view name
     * @param string $crlf    the end of line sequence
     * @param array  $aliases Aliases of db/table/columns
     *
     * @return string resulting definition
     */
    public function getTableDefStandIn($db, $view, $crlf, $aliases = [])
    {
        global $dbi;

        $text_output = '';

        /**
         * Get the unique keys in the table
         */
        $unique_keys = [];
        $keys = $dbi->getTableIndexes($db, $view);
        foreach ($keys as $key) {
            if ($key['Non_unique'] != 0) {
                continue;
            }

            $unique_keys[] = $key['Column_name'];
        }

        /**
         * Gets fields properties
         */
        $dbi->selectDb($db);

        /**
         * Displays the table structure
         */

        $text_output .= "|------\n"
            . '|' . __('Column')
            . '|' . __('Type')
            . '|' . __('Null')
            . '|' . __('Default')
            . "\n|------\n";

        $columns = $dbi->getColumns($db, $view);
        foreach ($columns as $column) {
            $col_as = $column['Field'] ?? null;
            if (! empty($aliases[$db]['tables'][$view]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$view]['columns'][$col_as];
            }

            $text_output .= $this->formatOneColumnDefinition($column, $unique_keys, $col_as);
            $text_output .= "\n";
        }

        return $text_output;
    }

    /**
     * Returns $table's CREATE definition
     *
     * @param string $db            the database name
     * @param string $table         the table name
     * @param string $crlf          the end of line sequence
     * @param string $error_url     the url to go back in case of error
     * @param bool   $do_relation   whether to include relation comments
     * @param bool   $do_comments   whether to include the pmadb-style column
     *                              comments as comments in the structure;
     *                              this is deprecated but the parameter is
     *                              left here because /export calls
     *                              $this->exportStructure() also for other
     *                              export types which use this parameter
     * @param bool   $do_mime       whether to include mime comments
     * @param bool   $show_dates    whether to include creation/update/check dates
     * @param bool   $add_semicolon whether to add semicolon and end-of-line
     *                              at the end
     * @param bool   $view          whether we're handling a view
     * @param array  $aliases       Aliases of db/table/columns
     *
     * @return string resulting schema
     */
    public function getTableDef(
        $db,
        $table,
        $crlf,
        $error_url,
        $do_relation,
        $do_comments,
        $do_mime,
        $show_dates = false,
        $add_semicolon = true,
        $view = false,
        array $aliases = []
    ) {
        global $dbi;

        $relationParameters = $this->relation->getRelationParameters();

        $text_output = '';

        /**
         * Get the unique keys in the table
         */
        $unique_keys = [];
        $keys = $dbi->getTableIndexes($db, $table);
        foreach ($keys as $key) {
            if ($key['Non_unique'] != 0) {
                continue;
            }

            $unique_keys[] = $key['Column_name'];
        }

        /**
         * Gets fields properties
         */
        $dbi->selectDb($db);

        // Check if we can use Relations
        [$res_rel, $have_rel] = $this->relation->getRelationsAndStatus(
            $do_relation && $relationParameters->relationFeature !== null,
            $db,
            $table
        );

        /**
         * Displays the table structure
         */

        $text_output .= "|------\n";
        $text_output .= '|' . __('Column');
        $text_output .= '|' . __('Type');
        $text_output .= '|' . __('Null');
        $text_output .= '|' . __('Default');
        if ($do_relation && $have_rel) {
            $text_output .= '|' . __('Links to');
        }

        if ($do_comments) {
            $text_output .= '|' . __('Comments');
            $comments = $this->relation->getComments($db, $table);
        }

        if ($do_mime && $relationParameters->browserTransformationFeature !== null) {
            $text_output .= '|' . __('Media type');
            $mime_map = $this->transformations->getMime($db, $table, true);
        }

        $text_output .= "\n|------\n";

        $columns = $dbi->getColumns($db, $table);
        foreach ($columns as $column) {
            $col_as = $column['Field'];
            if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
            }

            $text_output .= $this->formatOneColumnDefinition($column, $unique_keys, $col_as);
            $field_name = $column['Field'];
            if ($do_relation && $have_rel) {
                $text_output .= '|' . htmlspecialchars(
                    $this->getRelationString(
                        $res_rel,
                        $field_name,
                        $db,
                        $aliases
                    )
                );
            }

            if ($do_comments && $relationParameters->columnCommentsFeature !== null) {
                $text_output .= '|'
                    . (isset($comments[$field_name])
                        ? htmlspecialchars($comments[$field_name])
                        : '');
            }

            if ($do_mime && $relationParameters->browserTransformationFeature !== null) {
                $text_output .= '|'
                    . (isset($mime_map[$field_name])
                        ? htmlspecialchars(
                            str_replace('_', '/', $mime_map[$field_name]['mimetype'])
                        )
                        : '');
            }

            $text_output .= "\n";
        }

        return $text_output;
    }

    /**
     * Outputs triggers
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return string Formatted triggers list
     */
    public function getTriggers($db, $table)
    {
        global $dbi;

        $dump = "|------\n";
        $dump .= '|' . __('Name');
        $dump .= '|' . __('Time');
        $dump .= '|' . __('Event');
        $dump .= '|' . __('Definition');
        $dump .= "\n|------\n";

        $triggers = $dbi->getTriggers($db, $table);

        foreach ($triggers as $trigger) {
            $dump .= '|' . $trigger['name'];
            $dump .= '|' . $trigger['action_timing'];
            $dump .= '|' . $trigger['event_manipulation'];
            $dump .= '|' .
                str_replace(
                    '|',
                    '&#124;',
                    htmlspecialchars($trigger['definition'])
                );
            $dump .= "\n";
        }

        return $dump;
    }

    /**
     * Outputs table's structure
     *
     * @param string $db          database name
     * @param string $table       table name
     * @param string $crlf        the end of line sequence
     * @param string $errorUrl    the url to go back in case of error
     * @param string $exportMode  'create_table', 'triggers', 'create_view',
     *                             'stand_in'
     * @param string $exportType  'server', 'database', 'table'
     * @param bool   $do_relation whether to include relation comments
     * @param bool   $do_comments whether to include the pmadb-style column
     *                            comments as comments in the structure;
     *                            this is deprecated but the parameter is
     *                            left here because /export calls
     *                            $this->exportStructure() also for other
     *                            export types which use this parameter
     * @param bool   $do_mime     whether to include mime comments
     * @param bool   $dates       whether to include creation/update/check dates
     * @param array  $aliases     Aliases of db/table/columns
     */
    public function exportStructure(
        $db,
        $table,
        $crlf,
        $errorUrl,
        $exportMode,
        $exportType,
        $do_relation = false,
        $do_comments = false,
        $do_mime = false,
        $dates = false,
        array $aliases = []
    ): bool {
        global $dbi;

        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);
        $dump = '';

        switch ($exportMode) {
            case 'create_table':
                $dump .= '== ' . __('Table structure for table') . ' '
                . $table_alias . "\n\n";
                $dump .= $this->getTableDef(
                    $db,
                    $table,
                    $crlf,
                    $errorUrl,
                    $do_relation,
                    $do_comments,
                    $do_mime,
                    $dates,
                    true,
                    false,
                    $aliases
                );
                break;
            case 'triggers':
                $dump = '';
                $triggers = $dbi->getTriggers($db, $table);
                if ($triggers) {
                    $dump .= '== ' . __('Triggers') . ' ' . $table_alias . "\n\n";
                    $dump .= $this->getTriggers($db, $table);
                }

                break;
            case 'create_view':
                $dump .= '== ' . __('Structure for view') . ' ' . $table_alias . "\n\n";
                $dump .= $this->getTableDef(
                    $db,
                    $table,
                    $crlf,
                    $errorUrl,
                    $do_relation,
                    $do_comments,
                    $do_mime,
                    $dates,
                    true,
                    true,
                    $aliases
                );
                break;
            case 'stand_in':
                $dump .= '== ' . __('Stand-in structure for view')
                . ' ' . $table . "\n\n";
                // export a stand-in definition to resolve view dependencies
                $dump .= $this->getTableDefStandIn($db, $table, $crlf, $aliases);
        }

        return $this->export->outputHandler($dump);
    }

    /**
     * Formats the definition for one column
     *
     * @param array  $column      info about this column
     * @param array  $unique_keys unique keys for this table
     * @param string $col_alias   Column Alias
     *
     * @return string Formatted column definition
     */
    public function formatOneColumnDefinition(
        $column,
        $unique_keys,
        $col_alias = ''
    ) {
        if (empty($col_alias)) {
            $col_alias = $column['Field'];
        }

        $extracted_columnspec = Util::extractColumnSpec($column['Type']);
        $type = $extracted_columnspec['print_type'];
        if (empty($type)) {
            $type = '&nbsp;';
        }

        if (! isset($column['Default'])) {
            if ($column['Null'] !== 'NO') {
                $column['Default'] = 'NULL';
            }
        }

        $fmt_pre = '';
        $fmt_post = '';
        if (in_array($column['Field'], $unique_keys)) {
            $fmt_pre = '**' . $fmt_pre;
            $fmt_post .= '**';
        }

        if ($column['Key'] === 'PRI') {
            $fmt_pre = '//' . $fmt_pre;
            $fmt_post .= '//';
        }

        $definition = '|'
            . $fmt_pre . htmlspecialchars($col_alias) . $fmt_post;
        $definition .= '|' . htmlspecialchars($type);
        $definition .= '|'
            . ($column['Null'] == '' || $column['Null'] === 'NO'
                ? __('No') : __('Yes'));
        $definition .= '|'
            . htmlspecialchars($column['Default'] ?? '');

        return $definition;
    }
}
