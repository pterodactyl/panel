<?php
/**
 * Set of methods used to build dumps of tables as Latex
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
use PhpMyAdmin\Version;

use function __;
use function count;
use function in_array;
use function mb_strpos;
use function mb_substr;
use function str_replace;
use function stripslashes;

use const PHP_VERSION;

/**
 * Handles the export for the Latex format
 */
class ExportLatex extends ExportPlugin
{
    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'latex';
    }

    /**
     * Initialize the local variables that are used for export Latex.
     */
    protected function init(): void
    {
        /* Messages used in default captions */
        $GLOBALS['strLatexContent'] = __('Content of table @TABLE@');
        $GLOBALS['strLatexContinued'] = __('(continued)');
        $GLOBALS['strLatexStructure'] = __('Structure of table @TABLE@');
    }

    protected function setProperties(): ExportPluginProperties
    {
        global $plugin_param;
        $hide_structure = false;
        if ($plugin_param['export_type'] === 'table' && ! $plugin_param['single_table']) {
            $hide_structure = true;
        }

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('LaTeX');
        $exportPluginProperties->setExtension('tex');
        $exportPluginProperties->setMimeType('application/x-tex');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem(
            'caption',
            __('Include table caption')
        );
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // what to dump (structure/data/both) main group
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

        // structure options main group
        if (! $hide_structure) {
            $structureOptions = new OptionsPropertyMainGroup(
                'structure',
                __('Object creation options')
            );
            $structureOptions->setForce('data');
            // create primary items and add them to the group
            $leaf = new TextPropertyItem(
                'structure_caption',
                __('Table caption:')
            );
            $leaf->setDoc('faq6-27');
            $structureOptions->addProperty($leaf);
            $leaf = new TextPropertyItem(
                'structure_continued_caption',
                __('Table caption (continued):')
            );
            $leaf->setDoc('faq6-27');
            $structureOptions->addProperty($leaf);
            $leaf = new TextPropertyItem(
                'structure_label',
                __('Label key:')
            );
            $leaf->setDoc('faq6-27');
            $structureOptions->addProperty($leaf);
            $relationParameters = $this->relation->getRelationParameters();
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
            __('Put columns names in the first row:')
        );
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'data_caption',
            __('Table caption:')
        );
        $leaf->setDoc('faq6-27');
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'data_continued_caption',
            __('Table caption (continued):')
        );
        $leaf->setDoc('faq6-27');
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'data_label',
            __('Label key:')
        );
        $leaf->setDoc('faq6-27');
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
        global $crlf, $cfg, $dbi;

        $head = '% phpMyAdmin LaTeX Dump' . $crlf
            . '% version ' . Version::VERSION . $crlf
            . '% https://www.phpmyadmin.net/' . $crlf
            . '%' . $crlf
            . '% ' . __('Host:') . ' ' . $cfg['Server']['host'];
        if (! empty($cfg['Server']['port'])) {
            $head .= ':' . $cfg['Server']['port'];
        }

        $head .= $crlf
            . '% ' . __('Generation Time:') . ' '
            . Util::localisedDate() . $crlf
            . '% ' . __('Server version:') . ' ' . $dbi->getVersionString() . $crlf
            . '% ' . __('PHP Version:') . ' ' . PHP_VERSION . $crlf;

        return $this->export->outputHandler($head);
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
     * @param string $dbAlias Aliases of db
     */
    public function exportDBHeader($db, $dbAlias = ''): bool
    {
        if (empty($dbAlias)) {
            $dbAlias = $db;
        }

        global $crlf;
        $head = '% ' . $crlf
            . '% ' . __('Database:') . ' \'' . $dbAlias . '\'' . $crlf
            . '% ' . $crlf;

        return $this->export->outputHandler($head);
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
     * Outputs the content of a table in JSON format
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
        global $dbi;

        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);

        $result = $dbi->tryQuery($sqlQuery, DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED);

        $columns_cnt = $result->numFields();
        $columns = [];
        $columns_alias = [];
        foreach ($result->getFieldNames() as $i => $col_as) {
            $columns[$i] = $col_as;
            if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
            }

            $columns_alias[$i] = $col_as;
        }

        $buffer = $crlf . '%' . $crlf . '% ' . __('Data:') . ' ' . $table_alias
            . $crlf . '%' . $crlf . ' \\begin{longtable}{|';

        for ($index = 0; $index < $columns_cnt; $index++) {
            $buffer .= 'l|';
        }

        $buffer .= '} ' . $crlf;

        $buffer .= ' \\hline \\endhead \\hline \\endfoot \\hline ' . $crlf;
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . Util::expandUserString(
                    $GLOBALS['latex_data_caption'],
                    [
                        'texEscape',
                        static::class,
                    ],
                    [
                        'table' => $table_alias,
                        'database' => $db_alias,
                    ]
                )
                . '} \\label{'
                . Util::expandUserString(
                    $GLOBALS['latex_data_label'],
                    null,
                    [
                        'table' => $table_alias,
                        'database' => $db_alias,
                    ]
                )
                . '} \\\\';
        }

        if (! $this->export->outputHandler($buffer)) {
            return false;
        }

        // show column names
        if (isset($GLOBALS['latex_columns'])) {
            $buffer = '\\hline ';
            for ($i = 0; $i < $columns_cnt; $i++) {
                $buffer .= '\\multicolumn{1}{|c|}{\\textbf{'
                    . self::texEscape(stripslashes($columns_alias[$i])) . '}} & ';
            }

            $buffer = mb_substr($buffer, 0, -2) . '\\\\ \\hline \hline ';
            if (! $this->export->outputHandler($buffer . ' \\endfirsthead ' . $crlf)) {
                return false;
            }

            if (isset($GLOBALS['latex_caption'])) {
                if (
                    ! $this->export->outputHandler(
                        '\\caption{'
                        . Util::expandUserString(
                            $GLOBALS['latex_data_continued_caption'],
                            [
                                'texEscape',
                                static::class,
                            ],
                            [
                                'table' => $table_alias,
                                'database' => $db_alias,
                            ]
                        )
                        . '} \\\\ '
                    )
                ) {
                    return false;
                }
            }

            if (! $this->export->outputHandler($buffer . '\\endhead \\endfoot' . $crlf)) {
                return false;
            }
        } else {
            if (! $this->export->outputHandler('\\\\ \hline')) {
                return false;
            }
        }

        // print the whole table
        while ($record = $result->fetchAssoc()) {
            $buffer = '';
            // print each row
            for ($i = 0; $i < $columns_cnt; $i++) {
                if ($record[$columns[$i]] !== null && isset($record[$columns[$i]])) {
                    $column_value = self::texEscape(
                        stripslashes($record[$columns[$i]])
                    );
                } else {
                    $column_value = $GLOBALS['latex_null'];
                }

                // last column ... no need for & character
                if ($i == $columns_cnt - 1) {
                    $buffer .= $column_value;
                } else {
                    $buffer .= $column_value . ' & ';
                }
            }

            $buffer .= ' \\\\ \\hline ' . $crlf;
            if (! $this->export->outputHandler($buffer)) {
                return false;
            }
        }

        $buffer = ' \\end{longtable}' . $crlf;

        return $this->export->outputHandler($buffer);
    }

    /**
     * Outputs result raw query
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
     *                            exportStructure() also for other
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

        $relationParameters = $this->relation->getRelationParameters();

        /* We do not export triggers */
        if ($exportMode === 'triggers') {
            return true;
        }

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
        $buffer = $crlf . '%' . $crlf . '% ' . __('Structure:') . ' '
            . $table_alias . $crlf . '%' . $crlf . ' \\begin{longtable}{';
        if (! $this->export->outputHandler($buffer)) {
            return false;
        }

        $alignment = '|l|c|c|c|';
        if ($do_relation && $have_rel) {
            $alignment .= 'l|';
        }

        if ($do_comments) {
            $alignment .= 'l|';
        }

        if ($do_mime && $relationParameters->browserTransformationFeature !== null) {
            $alignment .= 'l|';
        }

        $buffer = $alignment . '} ' . $crlf;

        $header = ' \\hline ';
        $header .= '\\multicolumn{1}{|c|}{\\textbf{' . __('Column')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Type')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Null')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Default') . '}}';
        if ($do_relation && $have_rel) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Links to') . '}}';
        }

        if ($do_comments) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Comments') . '}}';
            $comments = $this->relation->getComments($db, $table);
        }

        if ($do_mime && $relationParameters->browserTransformationFeature !== null) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{MIME}}';
            $mime_map = $this->transformations->getMime($db, $table, true);
        }

        // Table caption for first page and label
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . Util::expandUserString(
                    $GLOBALS['latex_structure_caption'],
                    [
                        'texEscape',
                        static::class,
                    ],
                    [
                        'table' => $table_alias,
                        'database' => $db_alias,
                    ]
                )
                . '} \\label{'
                . Util::expandUserString(
                    $GLOBALS['latex_structure_label'],
                    null,
                    [
                        'table' => $table_alias,
                        'database' => $db_alias,
                    ]
                )
                . '} \\\\' . $crlf;
        }

        $buffer .= $header . ' \\\\ \\hline \\hline' . $crlf
            . '\\endfirsthead' . $crlf;
        // Table caption on next pages
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . Util::expandUserString(
                    $GLOBALS['latex_structure_continued_caption'],
                    [
                        'texEscape',
                        static::class,
                    ],
                    [
                        'table' => $table_alias,
                        'database' => $db_alias,
                    ]
                )
                . '} \\\\ ' . $crlf;
        }

        $buffer .= $header . ' \\\\ \\hline \\hline \\endhead \\endfoot ' . $crlf;

        if (! $this->export->outputHandler($buffer)) {
            return false;
        }

        $fields = $dbi->getColumns($db, $table);
        foreach ($fields as $row) {
            $extracted_columnspec = Util::extractColumnSpec($row['Type']);
            $type = $extracted_columnspec['print_type'];
            if (empty($type)) {
                $type = ' ';
            }

            if (! isset($row['Default'])) {
                if ($row['Null'] !== 'NO') {
                    $row['Default'] = 'NULL';
                }
            }

            $field_name = $col_as = $row['Field'];
            if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
            }

            $local_buffer = $col_as . "\000" . $type . "\000"
                . ($row['Null'] == '' || $row['Null'] === 'NO'
                    ? __('No') : __('Yes'))
                . "\000" . ($row['Default'] ?? '');

            if ($do_relation && $have_rel) {
                $local_buffer .= "\000";
                $local_buffer .= $this->getRelationString($res_rel, $field_name, $db, $aliases);
            }

            if ($do_comments && $relationParameters->columnCommentsFeature !== null) {
                $local_buffer .= "\000";
                if (isset($comments[$field_name])) {
                    $local_buffer .= $comments[$field_name];
                }
            }

            if ($do_mime && $relationParameters->browserTransformationFeature !== null) {
                $local_buffer .= "\000";
                if (isset($mime_map[$field_name])) {
                    $local_buffer .= str_replace('_', '/', $mime_map[$field_name]['mimetype']);
                }
            }

            $local_buffer = self::texEscape($local_buffer);
            if ($row['Key'] === 'PRI') {
                $pos = (int) mb_strpos($local_buffer, "\000");
                $local_buffer = '\\textit{'
                    .
                    mb_substr($local_buffer, 0, $pos)
                    . '}' .
                    mb_substr($local_buffer, $pos);
            }

            if (in_array($field_name, $unique_keys)) {
                $pos = (int) mb_strpos($local_buffer, "\000");
                $local_buffer = '\\textbf{'
                    .
                    mb_substr($local_buffer, 0, $pos)
                    . '}' .
                    mb_substr($local_buffer, $pos);
            }

            $buffer = str_replace("\000", ' & ', $local_buffer);
            $buffer .= ' \\\\ \\hline ' . $crlf;

            if (! $this->export->outputHandler($buffer)) {
                return false;
            }
        }

        $buffer = ' \\end{longtable}' . $crlf;

        return $this->export->outputHandler($buffer);
    }

    /**
     * Escapes some special characters for use in TeX/LaTeX
     *
     * @param string $string the string to convert
     *
     * @return string the converted string with escape codes
     */
    public static function texEscape($string)
    {
        $escape = [
            '$',
            '%',
            '{',
            '}',
            '&',
            '#',
            '_',
            '^',
        ];
        $cnt_escape = count($escape);
        for ($k = 0; $k < $cnt_escape; $k++) {
            $string = str_replace($escape[$k], '\\' . $escape[$k], $string);
        }

        return $string;
    }
}
