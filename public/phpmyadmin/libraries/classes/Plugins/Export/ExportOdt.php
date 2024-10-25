<?php
/**
 * Set of functions used to build OpenDocument Text dumps of tables
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\OpenDocument;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Util;

use function __;
use function bin2hex;
use function htmlspecialchars;
use function str_replace;
use function stripslashes;

/**
 * Handles the export for the ODT class
 */
class ExportOdt extends ExportPlugin
{
    protected function init(): void
    {
        $GLOBALS['odt_buffer'] = '';
    }

    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'odt';
    }

    protected function setProperties(): ExportPluginProperties
    {
        global $plugin_param;
        $hide_structure = false;
        if ($plugin_param['export_type'] === 'table' && ! $plugin_param['single_table']) {
            $hide_structure = true;
        }

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('OpenDocument Text');
        $exportPluginProperties->setExtension('odt');
        $exportPluginProperties->setMimeType('application/vnd.oasis.opendocument.text');
        $exportPluginProperties->setForceFile(true);
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

        // structure options main group
        if (! $hide_structure) {
            $structureOptions = new OptionsPropertyMainGroup(
                'structure',
                __('Object creation options')
            );
            $structureOptions->setForce('data');
            $relationParameters = $this->relation->getRelationParameters();
            // create primary items and add them to the group
            if ($relationParameters->relationFeature !== null) {
                $leaf = new BoolPropertyItem(
                    'relation',
                    __('Display foreign key relationships')
                );
                $structureOptions->addProperty($leaf);
            }

            $leaf = new BoolPropertyItem(
                'comments',
                __('Display comments')
            );
            $structureOptions->addProperty($leaf);
            if ($relationParameters->browserTransformationFeature !== null) {
                $leaf = new BoolPropertyItem(
                    'mime',
                    __('Display media types')
                );
                $structureOptions->addProperty($leaf);
            }

            // add the main group to the root group
            $exportSpecificOptions->addProperty($structureOptions);
        }

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
        $GLOBALS['odt_buffer'] .= '<?xml version="1.0" encoding="utf-8"?' . '>'
            . '<office:document-content '
            . OpenDocument::NS . ' office:version="1.0">'
            . '<office:body>'
            . '<office:text>';

        return true;
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        $GLOBALS['odt_buffer'] .= '</office:text></office:body></office:document-content>';

        return $this->export->outputHandler(OpenDocument::create(
            'application/vnd.oasis.opendocument.text',
            $GLOBALS['odt_buffer']
        ));
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

        $GLOBALS['odt_buffer'] .= '<text:h text:outline-level="1" text:style-name="Heading_1"'
            . ' text:is-list-header="true">'
            . __('Database') . ' ' . htmlspecialchars($dbAlias)
            . '</text:h>';

        return true;
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
        // Gets the data from the database
        $result = $dbi->query($sqlQuery, DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED);
        $fields_cnt = $result->numFields();
        /** @var FieldMetadata[] $fieldsMeta */
        $fieldsMeta = $dbi->getFieldsMeta($result);

        $GLOBALS['odt_buffer'] .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
            . ' text:is-list-header="true">';
        $table_alias != ''
            ? $GLOBALS['odt_buffer'] .= __('Dumping data for table') . ' ' . htmlspecialchars($table_alias)
            : $GLOBALS['odt_buffer'] .= __('Dumping data for query result');
        $GLOBALS['odt_buffer'] .= '</text:h>'
            . '<table:table'
            . ' table:name="' . htmlspecialchars($table_alias) . '_structure">'
            . '<table:table-column'
            . ' table:number-columns-repeated="' . $fields_cnt . '"/>';

        // If required, get fields name at the first line
        if (isset($GLOBALS[$what . '_columns'])) {
            $GLOBALS['odt_buffer'] .= '<table:table-row>';
            foreach ($fieldsMeta as $field) {
                $col_as = $field->name;
                if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                    $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
                }

                $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                    . '<text:p>'
                    . htmlspecialchars(
                        stripslashes($col_as)
                    )
                    . '</text:p>'
                    . '</table:table-cell>';
            }

            $GLOBALS['odt_buffer'] .= '</table:table-row>';
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $GLOBALS['odt_buffer'] .= '<table:table-row>';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if ($fieldsMeta[$j]->isMappedTypeGeometry) {
                    // export GIS types as hex
                    $row[$j] = '0x' . bin2hex($row[$j]);
                }

                if (! isset($row[$j])) {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($GLOBALS[$what . '_null'])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fieldsMeta[$j]->isBinary && $fieldsMeta[$j]->isBlob) {
                    // ignore BLOB
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                } elseif (
                    $fieldsMeta[$j]->isNumeric
                ) {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="float"'
                        . ' office:value="' . $row[$j] . '" >'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                }
            }

            $GLOBALS['odt_buffer'] .= '</table:table-row>';
        }

        $GLOBALS['odt_buffer'] .= '</table:table>';

        return true;
    }

    /**
     * Outputs result raw query in ODT format
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

        $db_alias = $db;
        $view_alias = $view;
        $this->initAlias($aliases, $db_alias, $view_alias);
        /**
         * Gets fields properties
         */
        $dbi->selectDb($db);

        /**
         * Displays the table structure
         */
        $GLOBALS['odt_buffer'] .= '<table:table table:name="'
            . htmlspecialchars($view_alias) . '_data">';
        $columns_cnt = 4;
        $GLOBALS['odt_buffer'] .= '<table:table-column'
            . ' table:number-columns-repeated="' . $columns_cnt . '"/>';
        /* Header */
        $GLOBALS['odt_buffer'] .= '<table:table-row>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Column') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Type') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Null') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Default') . '</text:p>'
            . '</table:table-cell>'
            . '</table:table-row>';

        $columns = $dbi->getColumns($db, $view);
        foreach ($columns as $column) {
            $col_as = $column['Field'] ?? null;
            if (! empty($aliases[$db]['tables'][$view]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$view]['columns'][$col_as];
            }

            $GLOBALS['odt_buffer'] .= $this->formatOneColumnDefinition($column, $col_as);
            $GLOBALS['odt_buffer'] .= '</table:table-row>';
        }

        $GLOBALS['odt_buffer'] .= '</table:table>';

        return '';
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
     *                              PMA_exportStructure() also for other
     * @param bool   $do_mime       whether to include mime comments
     * @param bool   $show_dates    whether to include creation/update/check dates
     * @param bool   $add_semicolon whether to add semicolon and end-of-line at
     *                              the end
     * @param bool   $view          whether we're handling a view
     * @param array  $aliases       Aliases of db/table/columns
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
    ): bool {
        global $dbi;

        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);

        $relationParameters = $this->relation->getRelationParameters();

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
        $GLOBALS['odt_buffer'] .= '<table:table table:name="'
            . htmlspecialchars($table_alias) . '_structure">';
        $columns_cnt = 4;
        if ($do_relation && $have_rel) {
            $columns_cnt++;
        }

        if ($do_comments) {
            $columns_cnt++;
        }

        if ($do_mime && $relationParameters->browserTransformationFeature !== null) {
            $columns_cnt++;
        }

        $GLOBALS['odt_buffer'] .= '<table:table-column'
            . ' table:number-columns-repeated="' . $columns_cnt . '"/>';
        /* Header */
        $GLOBALS['odt_buffer'] .= '<table:table-row>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Column') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Type') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Null') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Default') . '</text:p>'
            . '</table:table-cell>';
        if ($do_relation && $have_rel) {
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>' . __('Links to') . '</text:p>'
                . '</table:table-cell>';
        }

        if ($do_comments) {
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>' . __('Comments') . '</text:p>'
                . '</table:table-cell>';
            $comments = $this->relation->getComments($db, $table);
        }

        if ($do_mime && $relationParameters->browserTransformationFeature !== null) {
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>' . __('Media type') . '</text:p>'
                . '</table:table-cell>';
            $mime_map = $this->transformations->getMime($db, $table, true);
        }

        $GLOBALS['odt_buffer'] .= '</table:table-row>';

        $columns = $dbi->getColumns($db, $table);
        foreach ($columns as $column) {
            $col_as = $field_name = $column['Field'];
            if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
            }

            $GLOBALS['odt_buffer'] .= $this->formatOneColumnDefinition($column, $col_as);
            if ($do_relation && $have_rel) {
                $foreigner = $this->relation->searchColumnInForeigners($res_rel, $field_name);
                if ($foreigner) {
                    $rtable = $foreigner['foreign_table'];
                    $rfield = $foreigner['foreign_field'];
                    if (! empty($aliases[$db]['tables'][$rtable]['columns'][$rfield])) {
                        $rfield = $aliases[$db]['tables'][$rtable]['columns'][$rfield];
                    }

                    if (! empty($aliases[$db]['tables'][$rtable]['alias'])) {
                        $rtable = $aliases[$db]['tables'][$rtable]['alias'];
                    }

                    $relation = htmlspecialchars($rtable . ' (' . $rfield . ')');
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($relation)
                        . '</text:p>'
                        . '</table:table-cell>';
                }
            }

            if ($do_comments) {
                if (isset($comments[$field_name])) {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($comments[$field_name])
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                }
            }

            if ($do_mime && $relationParameters->browserTransformationFeature !== null) {
                if (isset($mime_map[$field_name])) {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars(
                            str_replace('_', '/', $mime_map[$field_name]['mimetype'])
                        )
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                }
            }

            $GLOBALS['odt_buffer'] .= '</table:table-row>';
        }

        $GLOBALS['odt_buffer'] .= '</table:table>';

        return true;
    }

    /**
     * Outputs triggers
     *
     * @param string $db      database name
     * @param string $table   table name
     * @param array  $aliases Aliases of db/table/columns
     *
     * @return string
     */
    protected function getTriggers($db, $table, array $aliases = [])
    {
        global $dbi;

        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);
        $GLOBALS['odt_buffer'] .= '<table:table'
            . ' table:name="' . htmlspecialchars($table_alias) . '_triggers">'
            . '<table:table-column'
            . ' table:number-columns-repeated="4"/>'
            . '<table:table-row>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Name') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Time') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Event') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Definition') . '</text:p>'
            . '</table:table-cell>'
            . '</table:table-row>';

        $triggers = $dbi->getTriggers($db, $table);

        foreach ($triggers as $trigger) {
            $GLOBALS['odt_buffer'] .= '<table:table-row>';
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>'
                . htmlspecialchars($trigger['name'])
                . '</text:p>'
                . '</table:table-cell>';
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>'
                . htmlspecialchars($trigger['action_timing'])
                . '</text:p>'
                . '</table:table-cell>';
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>'
                . htmlspecialchars($trigger['event_manipulation'])
                . '</text:p>'
                . '</table:table-cell>';
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>'
                . htmlspecialchars($trigger['definition'])
                . '</text:p>'
                . '</table:table-cell>';
            $GLOBALS['odt_buffer'] .= '</table:table-row>';
        }

        $GLOBALS['odt_buffer'] .= '</table:table>';

        return $GLOBALS['odt_buffer'];
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
        switch ($exportMode) {
            case 'create_table':
                $GLOBALS['odt_buffer'] .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
                . ' text:is-list-header="true">'
                . __('Table structure for table') . ' ' .
                htmlspecialchars($table_alias)
                . '</text:h>';
                $this->getTableDef(
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
                $triggers = $dbi->getTriggers($db, $table);
                if ($triggers) {
                    $GLOBALS['odt_buffer'] .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
                    . ' text:is-list-header="true">'
                    . __('Triggers') . ' '
                    . htmlspecialchars($table_alias)
                    . '</text:h>';
                    $this->getTriggers($db, $table);
                }

                break;
            case 'create_view':
                $GLOBALS['odt_buffer'] .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
                . ' text:is-list-header="true">'
                . __('Structure for view') . ' '
                . htmlspecialchars($table_alias)
                . '</text:h>';
                $this->getTableDef(
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
                $GLOBALS['odt_buffer'] .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
                . ' text:is-list-header="true">'
                . __('Stand-in structure for view') . ' '
                . htmlspecialchars($table_alias)
                . '</text:h>';
                // export a stand-in definition to resolve view dependencies
                $this->getTableDefStandIn($db, $table, $crlf, $aliases);
        }

        return true;
    }

    /**
     * Formats the definition for one column
     *
     * @param array  $column info about this column
     * @param string $col_as column alias
     *
     * @return string Formatted column definition
     */
    protected function formatOneColumnDefinition($column, $col_as = '')
    {
        if (empty($col_as)) {
            $col_as = $column['Field'];
        }

        $definition = '<table:table-row>';
        $definition .= '<table:table-cell office:value-type="string">'
            . '<text:p>' . htmlspecialchars($col_as) . '</text:p>'
            . '</table:table-cell>';

        $extracted_columnspec = Util::extractColumnSpec($column['Type']);
        $type = htmlspecialchars($extracted_columnspec['print_type']);
        if (empty($type)) {
            $type = '&nbsp;';
        }

        $definition .= '<table:table-cell office:value-type="string">'
            . '<text:p>' . htmlspecialchars($type) . '</text:p>'
            . '</table:table-cell>';
        if (! isset($column['Default'])) {
            if ($column['Null'] !== 'NO') {
                $column['Default'] = 'NULL';
            } else {
                $column['Default'] = '';
            }
        }

        $definition .= '<table:table-cell office:value-type="string">'
            . '<text:p>'
            . ($column['Null'] == '' || $column['Null'] === 'NO'
                ? __('No')
                : __('Yes'))
            . '</text:p>'
            . '</table:table-cell>';
        $definition .= '<table:table-cell office:value-type="string">'
            . '<text:p>' . htmlspecialchars($column['Default']) . '</text:p>'
            . '</table:table-cell>';

        return $definition;
    }
}
