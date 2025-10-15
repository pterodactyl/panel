<?php
/**
 * Set of methods used to build dumps of tables as JSON
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Version;

use function __;
use function bin2hex;
use function explode;
use function json_encode;
use function stripslashes;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_UNICODE;

/**
 * Handles the export for the JSON format
 */
class ExportJson extends ExportPlugin
{
    /** @var bool */
    private $first = true;

    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'json';
    }

    /**
     * Encodes the data into JSON
     *
     * @param mixed $data Data to encode
     *
     * @return string|false
     */
    public function encode($data)
    {
        $options = 0;
        if (isset($GLOBALS['json_pretty_print']) && $GLOBALS['json_pretty_print']) {
            $options |= JSON_PRETTY_PRINT;
        }

        if (isset($GLOBALS['json_unicode']) && $GLOBALS['json_unicode']) {
            $options |= JSON_UNESCAPED_UNICODE;
        }

        return json_encode($data, $options);
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('JSON');
        $exportPluginProperties->setExtension('json');
        $exportPluginProperties->setMimeType('text/plain');
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

        $leaf = new BoolPropertyItem(
            'pretty_print',
            __('Output pretty-printed JSON (Use human-readable formatting)')
        );
        $generalOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem(
            'unicode',
            __('Output unicode characters unescaped')
        );
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
        global $crlf;

        $data = $this->encode([
            'type' => 'header',
            'version' => Version::VERSION,
            'comment' => 'Export to JSON plugin for phpMyAdmin',
        ]);
        if ($data === false) {
            return false;
        }

        return $this->export->outputHandler('[' . $crlf . $data . ',' . $crlf);
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        global $crlf;

        return $this->export->outputHandler(']' . $crlf);
    }

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    public function exportDBHeader($db, $dbAlias = ''): bool
    {
        global $crlf;

        if (empty($dbAlias)) {
            $dbAlias = $db;
        }

        $data = $this->encode(['type' => 'database', 'name' => $dbAlias]);
        if ($data === false) {
            return false;
        }

        return $this->export->outputHandler($data . ',' . $crlf);
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

        if (! $this->first) {
            if (! $this->export->outputHandler(',')) {
                return false;
            }
        } else {
            $this->first = false;
        }

        $buffer = $this->encode([
            'type' => 'table',
            'name' => $table_alias,
            'database' => $db_alias,
            'data' => '@@DATA@@',
        ]);
        if ($buffer === false) {
            return false;
        }

        return $this->doExportForQuery($dbi, $sqlQuery, $buffer, $crlf, $aliases, $db, $table);
    }

    /**
     * Export to JSON
     *
     * @phpstan-param array<
     *   string,
     *   array{
     *     tables: array<
     *       string,
     *       array{columns: array<string, string>}
     *     >
     *   }
     * >|null $aliases
     */
    protected function doExportForQuery(
        DatabaseInterface $dbi,
        string $sqlQuery,
        string $buffer,
        string $crlf,
        ?array $aliases,
        ?string $db,
        ?string $table
    ): bool {
        [$header, $footer] = explode('"@@DATA@@"', $buffer);

        if (! $this->export->outputHandler($header . $crlf . '[' . $crlf)) {
            return false;
        }

        $result = $dbi->query($sqlQuery, DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED);
        $columns_cnt = $result->numFields();
        $fieldsMeta = $dbi->getFieldsMeta($result);

        $columns = [];
        foreach ($fieldsMeta as $i => $field) {
            $col_as = $field->name;
            if (
                $db !== null && $table !== null && $aliases !== null
                && ! empty($aliases[$db]['tables'][$table]['columns'][$col_as])
            ) {
                $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
            }

            $columns[$i] = stripslashes($col_as);
        }

        $record_cnt = 0;
        while ($record = $result->fetchRow()) {
            $record_cnt++;

            // Output table name as comment if this is the first record of the table
            if ($record_cnt > 1) {
                if (! $this->export->outputHandler(',' . $crlf)) {
                    return false;
                }
            }

            $data = [];

            for ($i = 0; $i < $columns_cnt; $i++) {
                // 63 is the binary charset, see: https://dev.mysql.com/doc/internals/en/charsets.html
                $isBlobAndIsBinaryCharset = isset($fieldsMeta[$i])
                                                && $fieldsMeta[$i]->isType(FieldMetadata::TYPE_BLOB)
                                                && $fieldsMeta[$i]->charsetnr === 63;
                // This can occur for binary fields
                $isBinaryString = isset($fieldsMeta[$i])
                                    && $fieldsMeta[$i]->isType(FieldMetadata::TYPE_STRING)
                                    && $fieldsMeta[$i]->charsetnr === 63;
                if (
                    isset($fieldsMeta[$i]) &&
                    (
                        $fieldsMeta[$i]->isMappedTypeGeometry ||
                        $isBlobAndIsBinaryCharset ||
                        $isBinaryString
                    ) &&
                    $record[$i] !== null
                ) {
                    // export GIS and blob types as hex
                    $record[$i] = '0x' . bin2hex($record[$i]);
                }

                $data[$columns[$i]] = $record[$i];
            }

            $encodedData = $this->encode($data);
            if (! $encodedData) {
                return false;
            }

            if (! $this->export->outputHandler($encodedData)) {
                return false;
            }
        }

        return $this->export->outputHandler($crlf . ']' . $crlf . $footer . $crlf);
    }

    /**
     * Outputs result raw query in JSON format
     *
     * @param string      $errorUrl the url to go back in case of error
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     * @param string      $crlf     the end of line sequence
     */
    public function exportRawQuery(string $errorUrl, ?string $db, string $sqlQuery, string $crlf): bool
    {
        global $dbi;

        $buffer = $this->encode([
            'type' => 'raw',
            'data' => '@@DATA@@',
        ]);
        if ($buffer === false) {
            return false;
        }

        if ($db !== null) {
            $dbi->selectDb($db);
        }

        return $this->doExportForQuery($dbi, $sqlQuery, $buffer, $crlf, null, $db, null);
    }
}
