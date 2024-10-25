<?php
/**
 * Set of functions used to build OpenDocument Spreadsheet dumps of tables
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
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;

use function __;
use function bin2hex;
use function date;
use function htmlspecialchars;
use function stripslashes;
use function strtotime;

/**
 * Handles the export for the ODS class
 */
class ExportOds extends ExportPlugin
{
    protected function init(): void
    {
        $GLOBALS['ods_buffer'] = '';
    }

    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'ods';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('OpenDocument Spreadsheet');
        $exportPluginProperties->setExtension('ods');
        $exportPluginProperties->setMimeType('application/vnd.oasis.opendocument.spreadsheet');
        $exportPluginProperties->setForceFile(true);
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
        $leaf = new TextPropertyItem(
            'null',
            __('Replace NULL with:')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'columns',
            __('Put columns names in the first row')
        );
        $generalOptions->addProperty($leaf);
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
        $GLOBALS['ods_buffer'] .= '<?xml version="1.0" encoding="utf-8"?' . '>'
            . '<office:document-content '
            . OpenDocument::NS . ' office:version="1.0">'
            . '<office:automatic-styles>'
            . '<number:date-style style:name="N37"'
            . ' number:automatic-order="true">'
            . '<number:month number:style="long"/>'
            . '<number:text>/</number:text>'
            . '<number:day number:style="long"/>'
            . '<number:text>/</number:text>'
            . '<number:year/>'
            . '</number:date-style>'
            . '<number:time-style style:name="N43">'
            . '<number:hours number:style="long"/>'
            . '<number:text>:</number:text>'
            . '<number:minutes number:style="long"/>'
            . '<number:text>:</number:text>'
            . '<number:seconds number:style="long"/>'
            . '<number:text> </number:text>'
            . '<number:am-pm/>'
            . '</number:time-style>'
            . '<number:date-style style:name="N50"'
            . ' number:automatic-order="true"'
            . ' number:format-source="language">'
            . '<number:month/>'
            . '<number:text>/</number:text>'
            . '<number:day/>'
            . '<number:text>/</number:text>'
            . '<number:year/>'
            . '<number:text> </number:text>'
            . '<number:hours number:style="long"/>'
            . '<number:text>:</number:text>'
            . '<number:minutes number:style="long"/>'
            . '<number:text> </number:text>'
            . '<number:am-pm/>'
            . '</number:date-style>'
            . '<style:style style:name="DateCell" style:family="table-cell"'
            . ' style:parent-style-name="Default" style:data-style-name="N37"/>'
            . '<style:style style:name="TimeCell" style:family="table-cell"'
            . ' style:parent-style-name="Default" style:data-style-name="N43"/>'
            . '<style:style style:name="DateTimeCell" style:family="table-cell"'
            . ' style:parent-style-name="Default" style:data-style-name="N50"/>'
            . '</office:automatic-styles>'
            . '<office:body>'
            . '<office:spreadsheet>';

        return true;
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        $GLOBALS['ods_buffer'] .= '</office:spreadsheet></office:body></office:document-content>';

        return $this->export->outputHandler(
            OpenDocument::create(
                'application/vnd.oasis.opendocument.spreadsheet',
                $GLOBALS['ods_buffer']
            )
        );
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

        $GLOBALS['ods_buffer'] .= '<table:table table:name="' . htmlspecialchars($table_alias) . '">';

        // If required, get fields name at the first line
        if (isset($GLOBALS[$what . '_columns'])) {
            $GLOBALS['ods_buffer'] .= '<table:table-row>';
            foreach ($fieldsMeta as $field) {
                $col_as = $field->name;
                if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                    $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
                }

                $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="string">'
                    . '<text:p>'
                    . htmlspecialchars(
                        stripslashes($col_as)
                    )
                    . '</text:p>'
                    . '</table:table-cell>';
            }

            $GLOBALS['ods_buffer'] .= '</table:table-row>';
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $GLOBALS['ods_buffer'] .= '<table:table-row>';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if ($fieldsMeta[$j]->isMappedTypeGeometry) {
                    // export GIS types as hex
                    $row[$j] = '0x' . bin2hex($row[$j]);
                }

                if (! isset($row[$j])) {
                    $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($GLOBALS[$what . '_null'])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fieldsMeta[$j]->isBinary && $fieldsMeta[$j]->isBlob) {
                    // ignore BLOB
                    $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                } elseif ($fieldsMeta[$j]->isType(FieldMetadata::TYPE_DATE)) {
                    $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="date"'
                        . ' office:date-value="'
                        . date('Y-m-d', strtotime($row[$j]))
                        . '" table:style-name="DateCell">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fieldsMeta[$j]->isType(FieldMetadata::TYPE_TIME)) {
                    $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="time"'
                        . ' office:time-value="'
                        . date('\P\TH\Hi\Ms\S', strtotime($row[$j]))
                        . '" table:style-name="TimeCell">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fieldsMeta[$j]->isType(FieldMetadata::TYPE_DATETIME)) {
                    $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="date"'
                        . ' office:date-value="'
                        . date('Y-m-d\TH:i:s', strtotime($row[$j]))
                        . '" table:style-name="DateTimeCell">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif (
                    $fieldsMeta[$j]->isNumeric
                ) {
                    $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="float"'
                        . ' office:value="' . $row[$j] . '" >'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                }
            }

            $GLOBALS['ods_buffer'] .= '</table:table-row>';
        }

        $GLOBALS['ods_buffer'] .= '</table:table>';

        return true;
    }

    /**
     * Outputs result raw query in ODS format
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
