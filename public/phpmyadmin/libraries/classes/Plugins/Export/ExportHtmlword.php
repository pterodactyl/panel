<?php
/**
 * HTML-Word export code
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
 * Handles the export for the HTML-Word format
 */
class ExportHtmlword extends ExportPlugin
{
    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'htmlword';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('Microsoft Word 2000');
        $exportPluginProperties->setExtension('doc');
        $exportPluginProperties->setMimeType('application/vnd.ms-word');
        $exportPluginProperties->setForceFile(true);
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // what to dump (structure/data/both)
        $dumpWhat = new OptionsPropertyMainGroup(
            'dump_what',
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
            'dump_what',
            __('Data dump options')
        );
        $dataOptions->setForce('structure');
        // create primary items and add them to the group
        $leaf = new TextPropertyItem(
            'null',
            __('Replace NULL with:')
        );
        $dataOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'columns',
            __('Put columns names in the first row')
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
        global $charset;

        return $this->export->outputHandler(
            '<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:word"
            xmlns="http://www.w3.org/TR/REC-html40">

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
            <head>
                <meta http-equiv="Content-type" content="text/html;charset='
            . ($charset ?? 'utf-8') . '" />
            </head>
            <body>'
        );
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        return $this->export->outputHandler('</body></html>');
    }

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    public function exportDBHeader($db, $dbAlias = ''): bool
    {
        if (empty($dbAlias)) {
            $dbAlias = $db;
        }

        return $this->export->outputHandler(
            '<h1>' . __('Database') . ' ' . htmlspecialchars($dbAlias) . '</h1>'
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
     * Outputs the content of a table in HTML-Word format
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
                '<h2>'
                . __('Dumping data for table') . ' ' . htmlspecialchars($table_alias)
                . '</h2>'
            )
        ) {
            return false;
        }

        if (! $this->export->outputHandler('<table width="100%" cellspacing="1">')) {
            return false;
        }

        // Gets the data from the database
        $result = $dbi->query($sqlQuery, DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED);
        $fields_cnt = $result->numFields();

        // If required, get fields name at the first line
        if (isset($GLOBALS['htmlword_columns'])) {
            $schema_insert = '<tr class="print-category">';
            foreach ($result->getFieldNames() as $col_as) {
                if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                    $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
                }

                $col_as = stripslashes($col_as);
                $schema_insert .= '<td class="print"><strong>'
                    . htmlspecialchars($col_as)
                    . '</strong></td>';
            }

            $schema_insert .= '</tr>';
            if (! $this->export->outputHandler($schema_insert)) {
                return false;
            }
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $schema_insert = '<tr class="print-category">';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (! isset($row[$j])) {
                    $value = $GLOBALS[$what . '_null'];
                } elseif ($row[$j] == '0' || $row[$j] != '') {
                    $value = $row[$j];
                } else {
                    $value = '';
                }

                $schema_insert .= '<td class="print">'
                    . htmlspecialchars((string) $value)
                    . '</td>';
            }

            $schema_insert .= '</tr>';
            if (! $this->export->outputHandler($schema_insert)) {
                return false;
            }
        }

        return $this->export->outputHandler('</table>');
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

        $schema_insert = '<table width="100%" cellspacing="1">'
            . '<tr class="print-category">'
            . '<th class="print">'
            . __('Column')
            . '</th>'
            . '<td class="print"><strong>'
            . __('Type')
            . '</strong></td>'
            . '<td class="print"><strong>'
            . __('Null')
            . '</strong></td>'
            . '<td class="print"><strong>'
            . __('Default')
            . '</strong></td>'
            . '</tr>';

        /**
         * Get the unique keys in the view
         */
        $unique_keys = [];
        $keys = $dbi->getTableIndexes($db, $view);
        foreach ($keys as $key) {
            if ($key['Non_unique'] != 0) {
                continue;
            }

            $unique_keys[] = $key['Column_name'];
        }

        $columns = $dbi->getColumns($db, $view);
        foreach ($columns as $column) {
            $col_as = $column['Field'];
            if (! empty($aliases[$db]['tables'][$view]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$view]['columns'][$col_as];
            }

            $schema_insert .= $this->formatOneColumnDefinition($column, $unique_keys, $col_as);
            $schema_insert .= '</tr>';
        }

        $schema_insert .= '</table>';

        return $schema_insert;
    }

    /**
     * Returns $table's CREATE definition
     *
     * @param string $db          the database name
     * @param string $table       the table name
     * @param bool   $do_relation whether to include relation comments
     * @param bool   $do_comments whether to include the pmadb-style column
     *                            comments as comments in the structure;
     *                            this is deprecated but the parameter is
     *                            left here because /export calls
     *                            PMA_exportStructure() also for other
     *                            export types which use this parameter
     * @param bool   $do_mime     whether to include mime comments
     *                            at the end
     * @param bool   $view        whether we're handling a view
     * @param array  $aliases     Aliases of db/table/columns
     *
     * @return string resulting schema
     */
    public function getTableDef(
        $db,
        $table,
        $do_relation,
        $do_comments,
        $do_mime,
        $view = false,
        array $aliases = []
    ) {
        global $dbi;

        $relationParameters = $this->relation->getRelationParameters();

        $schema_insert = '';

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
        $schema_insert .= '<table width="100%" cellspacing="1">';

        $schema_insert .= '<tr class="print-category">';
        $schema_insert .= '<th class="print">'
            . __('Column')
            . '</th>';
        $schema_insert .= '<td class="print"><strong>'
            . __('Type')
            . '</strong></td>';
        $schema_insert .= '<td class="print"><strong>'
            . __('Null')
            . '</strong></td>';
        $schema_insert .= '<td class="print"><strong>'
            . __('Default')
            . '</strong></td>';
        if ($do_relation && $have_rel) {
            $schema_insert .= '<td class="print"><strong>'
                . __('Links to')
                . '</strong></td>';
        }

        if ($do_comments) {
            $schema_insert .= '<td class="print"><strong>'
                . __('Comments')
                . '</strong></td>';
            $comments = $this->relation->getComments($db, $table);
        }

        if ($do_mime && $relationParameters->browserTransformationFeature !== null) {
            $schema_insert .= '<td class="print"><strong>'
                . __('Media type')
                . '</strong></td>';
            $mime_map = $this->transformations->getMime($db, $table, true);
        }

        $schema_insert .= '</tr>';

        $columns = $dbi->getColumns($db, $table);
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

        foreach ($columns as $column) {
            $col_as = $column['Field'];
            if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
            }

            $schema_insert .= $this->formatOneColumnDefinition($column, $unique_keys, $col_as);
            $field_name = $column['Field'];
            if ($do_relation && $have_rel) {
                $schema_insert .= '<td class="print">'
                    . htmlspecialchars(
                        $this->getRelationString(
                            $res_rel,
                            $field_name,
                            $db,
                            $aliases
                        )
                    )
                    . '</td>';
            }

            if ($do_comments && $relationParameters->columnCommentsFeature !== null) {
                $schema_insert .= '<td class="print">'
                    . (isset($comments[$field_name])
                        ? htmlspecialchars($comments[$field_name])
                        : '') . '</td>';
            }

            if ($do_mime && $relationParameters->browserTransformationFeature !== null) {
                $schema_insert .= '<td class="print">'
                    . (isset($mime_map[$field_name]) ?
                        htmlspecialchars(
                            str_replace('_', '/', $mime_map[$field_name]['mimetype'])
                        )
                        : '') . '</td>';
            }

            $schema_insert .= '</tr>';
        }

        $schema_insert .= '</table>';

        return $schema_insert;
    }

    /**
     * Outputs triggers
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return string Formatted triggers list
     */
    protected function getTriggers($db, $table)
    {
        global $dbi;

        $dump = '<table width="100%" cellspacing="1">';
        $dump .= '<tr class="print-category">';
        $dump .= '<th class="print">' . __('Name') . '</th>';
        $dump .= '<td class="print"><strong>' . __('Time') . '</strong></td>';
        $dump .= '<td class="print"><strong>' . __('Event') . '</strong></td>';
        $dump .= '<td class="print"><strong>' . __('Definition') . '</strong></td>';
        $dump .= '</tr>';

        $triggers = $dbi->getTriggers($db, $table);

        foreach ($triggers as $trigger) {
            $dump .= '<tr class="print-category">';
            $dump .= '<td class="print">'
                . htmlspecialchars($trigger['name'])
                . '</td>'
                . '<td class="print">'
                . htmlspecialchars($trigger['action_timing'])
                . '</td>'
                . '<td class="print">'
                . htmlspecialchars($trigger['event_manipulation'])
                . '</td>'
                . '<td class="print">'
                . htmlspecialchars($trigger['definition'])
                . '</td>'
                . '</tr>';
        }

        $dump .= '</table>';

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
     *                            PMA_exportStructure() also for other
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
                $dump .= '<h2>'
                . __('Table structure for table') . ' '
                . htmlspecialchars($table_alias)
                . '</h2>';
                $dump .= $this->getTableDef($db, $table, $do_relation, $do_comments, $do_mime, false, $aliases);
                break;
            case 'triggers':
                $dump = '';
                $triggers = $dbi->getTriggers($db, $table);
                if ($triggers) {
                    $dump .= '<h2>'
                    . __('Triggers') . ' ' . htmlspecialchars($table_alias)
                    . '</h2>';
                    $dump .= $this->getTriggers($db, $table);
                }

                break;
            case 'create_view':
                $dump .= '<h2>'
                . __('Structure for view') . ' ' . htmlspecialchars($table_alias)
                . '</h2>';
                $dump .= $this->getTableDef($db, $table, $do_relation, $do_comments, $do_mime, true, $aliases);
                break;
            case 'stand_in':
                $dump .= '<h2>'
                . __('Stand-in structure for view') . ' '
                . htmlspecialchars($table_alias)
                . '</h2>';
                // export a stand-in definition to resolve view dependencies
                $dump .= $this->getTableDefStandIn($db, $table, $crlf, $aliases);
        }

        return $this->export->outputHandler($dump);
    }

    /**
     * Formats the definition for one column
     *
     * @param array  $column      info about this column
     * @param array  $unique_keys unique keys of the table
     * @param string $col_alias   Column Alias
     *
     * @return string Formatted column definition
     */
    protected function formatOneColumnDefinition(
        array $column,
        array $unique_keys,
        $col_alias = ''
    ) {
        if (empty($col_alias)) {
            $col_alias = $column['Field'];
        }

        $definition = '<tr class="print-category">';

        $extracted_columnspec = Util::extractColumnSpec($column['Type']);

        $type = htmlspecialchars($extracted_columnspec['print_type']);
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
            $fmt_pre = '<strong>' . $fmt_pre;
            $fmt_post .= '</strong>';
        }

        if ($column['Key'] === 'PRI') {
            $fmt_pre = '<em>' . $fmt_pre;
            $fmt_post .= '</em>';
        }

        $definition .= '<td class="print">' . $fmt_pre
            . htmlspecialchars($col_alias) . $fmt_post . '</td>';
        $definition .= '<td class="print">' . htmlspecialchars($type) . '</td>';
        $definition .= '<td class="print">'
            . ($column['Null'] == '' || $column['Null'] === 'NO'
                ? __('No')
                : __('Yes'))
            . '</td>';
        $definition .= '<td class="print">'
            . htmlspecialchars($column['Default'] ?? '')
            . '</td>';

        return $definition;
    }
}
