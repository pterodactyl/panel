<?php
/**
 * Set of functions used to build YAML dumps of tables
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;

use function __;
use function array_key_exists;
use function is_numeric;
use function str_replace;
use function stripslashes;

/**
 * Handles the export for the YAML format
 */
class ExportYaml extends ExportPlugin
{
    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'yaml';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('YAML');
        $exportPluginProperties->setExtension('yml');
        $exportPluginProperties->setMimeType('text/yaml');
        $exportPluginProperties->setForceFile(true);
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
        $leaf = new HiddenPropertyItem('structure_or_data');
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);

        return $exportPluginProperties;
    }

    /**
     * Outputs export header
     */
    public function exportHeader(): bool
    {
        $this->export->outputHandler('%YAML 1.1' . $GLOBALS['crlf'] . '---' . $GLOBALS['crlf']);

        return true;
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        $this->export->outputHandler('...' . $GLOBALS['crlf']);

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
        $result = $dbi->query($sqlQuery, DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED);

        $columns_cnt = $result->numFields();
        $fieldsMeta = $dbi->getFieldsMeta($result);

        $columns = [];
        foreach ($fieldsMeta as $i => $field) {
            $col_as = $field->name;
            if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
            }

            $columns[$i] = stripslashes($col_as);
        }

        $record_cnt = 0;
        while ($record = $result->fetchRow()) {
            $record_cnt++;

            // Output table name as comment if this is the first record of the table
            if ($record_cnt == 1) {
                $buffer = '# ' . $db_alias . '.' . $table_alias . $crlf;
                $buffer .= '-' . $crlf;
            } else {
                $buffer = '-' . $crlf;
            }

            for ($i = 0; $i < $columns_cnt; $i++) {
                if (! array_key_exists($i, $record)) {
                    continue;
                }

                if ($record[$i] === null) {
                    $buffer .= '  ' . $columns[$i] . ': null' . $crlf;
                    continue;
                }

                $isNotString = isset($fieldsMeta[$i]) && $fieldsMeta[$i]->isNotType(FieldMetadata::TYPE_STRING);
                if (is_numeric($record[$i]) && $isNotString) {
                    $buffer .= '  ' . $columns[$i] . ': ' . $record[$i] . $crlf;
                    continue;
                }

                $record[$i] = str_replace(
                    [
                        '\\',
                        '"',
                        "\n",
                        "\r",
                    ],
                    [
                        '\\\\',
                        '\"',
                        '\n',
                        '\r',
                    ],
                    $record[$i]
                );
                $buffer .= '  ' . $columns[$i] . ': "' . $record[$i] . '"' . $crlf;
            }

            if (! $this->export->outputHandler($buffer)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Outputs result raw query in YAML format
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
}
