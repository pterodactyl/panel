<?php
/**
 * OpenDocument Spreadsheet import plugin for phpMyAdmin
 *
 * @todo       Pretty much everything
 * @todo       Importing of accented characters seems to fail
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Import;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use SimpleXMLElement;

use function __;
use function count;
use function implode;
use function libxml_disable_entity_loader;
use function rtrim;
use function simplexml_load_string;
use function strcmp;
use function strlen;

use const LIBXML_COMPACT;
use const PHP_VERSION_ID;

/**
 * Handles the import for the ODS format
 */
class ImportOds extends ImportPlugin
{
    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'ods';
    }

    protected function setProperties(): ImportPluginProperties
    {
        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText('OpenDocument Spreadsheet');
        $importPluginProperties->setExtension('ods');
        $importPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $importPluginProperties
        // this will be shown as "Format specific options"
        $importSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem(
            'col_names',
            __(
                'The first line of the file contains the table column names'
                . ' <i>(if this is unchecked, the first line will become part'
                . ' of the data)</i>'
            )
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'empty_rows',
            __('Do not import empty rows')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'recognize_percentages',
            __(
                'Import percentages as proper decimals <i>(ex. 12.00% to .12)</i>'
            )
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'recognize_currency',
            __('Import currencies <i>(ex. $5.00 to 5.00)</i>')
        );
        $generalOptions->addProperty($leaf);

        // add the main group to the root group
        $importSpecificOptions->addProperty($generalOptions);

        // set the options for the import plugin property item
        $importPluginProperties->setOptions($importSpecificOptions);

        return $importPluginProperties;
    }

    /**
     * Handles the whole import logic
     *
     * @param array $sql_data 2-element array with sql data
     */
    public function doImport(?File $importHandle = null, array &$sql_data = []): void
    {
        global $db, $error, $timeout_passed, $finished;

        $buffer = '';

        /**
         * Read in the file via Import::getNextChunk so that
         * it can process compressed files
         */
        while (! $finished && ! $error && ! $timeout_passed) {
            $data = $this->import->getNextChunk($importHandle);
            if ($data === false) {
                /* subtract data we didn't handle yet and stop processing */
                $GLOBALS['offset'] -= strlen($buffer);
                break;
            }

            if ($data === true) {
                continue;
            }

            /* Append new data to buffer */
            $buffer .= $data;
        }

        /**
         * Disable loading of external XML entities for PHP versions below 8.0.
         */
        if (PHP_VERSION_ID < 80000) {
            // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated
            libxml_disable_entity_loader();
        }

        /**
         * Load the XML string
         *
         * The option LIBXML_COMPACT is specified because it can
         * result in increased performance without the need to
         * alter the code in any way. It's basically a freebee.
         */
        $xml = @simplexml_load_string($buffer, SimpleXMLElement::class, LIBXML_COMPACT);

        unset($buffer);

        if ($xml === false) {
            $sheets = [];
            $GLOBALS['message'] = Message::error(
                __(
                    'The XML file specified was either malformed or incomplete. Please correct the issue and try again.'
                )
            );
            $GLOBALS['error'] = true;
        } else {
            /** @var SimpleXMLElement $root */
            $root = $xml->children('office', true)->{'body'}->{'spreadsheet'};
            if (empty($root)) {
                $sheets = [];
                $GLOBALS['message'] = Message::error(
                    __('Could not parse OpenDocument Spreadsheet!')
                );
                $GLOBALS['error'] = true;
            } else {
                $sheets = $root->children('table', true);
            }
        }

        [$tables, $rows] = $this->iterateOverTables($sheets);

        /**
         * Bring accumulated rows into the corresponding table
         */
        $num_tables = count($tables);
        for ($i = 0; $i < $num_tables; ++$i) {
            $num_rows = count($rows);
            for ($j = 0; $j < $num_rows; ++$j) {
                if (strcmp($tables[$i][Import::TBL_NAME], $rows[$j][Import::TBL_NAME])) {
                    continue;
                }

                if (! isset($tables[$i][Import::COL_NAMES])) {
                    $tables[$i][] = $rows[$j][Import::COL_NAMES];
                }

                $tables[$i][Import::ROWS] = $rows[$j][Import::ROWS];
            }
        }

        /* No longer needed */
        unset($rows);

        /* Obtain the best-fit MySQL types for each column */
        $analyses = [];

        $len = count($tables);
        for ($i = 0; $i < $len; ++$i) {
            $analyses[] = $this->import->analyzeTable($tables[$i]);
        }

        /**
         * string $db_name (no backquotes)
         *
         * array $table = array(table_name, array() column_names, array()() rows)
         * array $tables = array of "$table"s
         *
         * array $analysis = array(array() column_types, array() column_sizes)
         * array $analyses = array of "$analysis"s
         *
         * array $create = array of SQL strings
         *
         * array $options = an associative array of options
         */

        /* Set database name to the currently selected one, if applicable */
        [$db_name, $options] = $this->getDbnameAndOptions($db, 'ODS_DB');

        /* Non-applicable parameters */
        $create = null;

        /* Created and execute necessary SQL statements from data */
        $this->import->buildSql($db_name, $tables, $analyses, $create, $options, $sql_data);

        unset($tables, $analyses);

        /* Commit any possible data in buffers */
        $this->import->runQuery('', '', $sql_data);
    }

    /**
     * Get value
     *
     * @param SimpleXMLElement $cell_attrs Cell attributes
     * @param SimpleXMLElement $text       Texts
     *
     * @return float|string
     */
    protected function getValue($cell_attrs, $text)
    {
        if (
            isset($_REQUEST['ods_recognize_percentages'])
            && $_REQUEST['ods_recognize_percentages']
            && ! strcmp('percentage', (string) $cell_attrs['value-type'])
        ) {
            return (float) $cell_attrs['value'];
        }

        if (
            isset($_REQUEST['ods_recognize_currency'])
            && $_REQUEST['ods_recognize_currency']
            && ! strcmp('currency', (string) $cell_attrs['value-type'])
        ) {
            return (float) $cell_attrs['value'];
        }

        /* We need to concatenate all paragraphs */
        $values = [];
        foreach ($text as $paragraph) {
            // Maybe a text node has the content ? (email, url, ...)
            // Example: <text:a ... xlink:href="mailto:contact@example.org">test@example.fr</text:a>
            $paragraphValue = $paragraph->__toString();
            if ($paragraphValue === '' && isset($paragraph->{'a'})) {
                $values[] = $paragraph->{'a'}->__toString();
                continue;
            }

            $values[] = $paragraphValue;
        }

        return implode("\n", $values);
    }

    private function iterateOverColumns(
        SimpleXMLElement $row,
        bool $col_names_in_first_row,
        array $tempRow,
        array $col_names,
        int $col_count
    ): array {
        $cellCount = $row->count();
        $a = 0;
        foreach ($row as $cell) {
            $a++;
            $text = $cell->children('text', true);
            $cell_attrs = $cell->attributes('office', true);

            if ($text->count() != 0) {
                $attr = $cell->attributes('table', true);
                $num_repeat = (int) $attr['number-columns-repeated'];
                $num_iterations = $num_repeat ?: 1;

                for ($k = 0; $k < $num_iterations; $k++) {
                    $value = $this->getValue($cell_attrs, $text);
                    if (! $col_names_in_first_row) {
                        $tempRow[] = $value;
                    } else {
                        // MySQL column names can't end with a space
                        // character.
                        $col_names[] = rtrim((string) $value);
                    }

                    ++$col_count;
                }

                continue;
            }

            // skip empty repeats in the last row
            if ($a == $cellCount) {
                continue;
            }

            $attr = $cell->attributes('table', true);
            $num_null = (int) $attr['number-columns-repeated'];

            if ($num_null) {
                if (! $col_names_in_first_row) {
                    for ($i = 0; $i < $num_null; ++$i) {
                        $tempRow[] = 'NULL';
                        ++$col_count;
                    }
                } else {
                    for ($i = 0; $i < $num_null; ++$i) {
                        $col_names[] = $this->import->getColumnAlphaName($col_count + 1);
                        ++$col_count;
                    }
                }
            } else {
                if (! $col_names_in_first_row) {
                    $tempRow[] = 'NULL';
                } else {
                    $col_names[] = $this->import->getColumnAlphaName($col_count + 1);
                }

                ++$col_count;
            }
        }

        return [$tempRow, $col_names, $col_count];
    }

    private function iterateOverRows(
        SimpleXMLElement $sheet,
        bool $col_names_in_first_row,
        array $tempRow,
        array $col_names,
        int $col_count,
        int $max_cols,
        array $tempRows
    ): array {
        foreach ($sheet as $row) {
            $type = $row->getName();
            if (strcmp('table-row', $type)) {
                continue;
            }

            [$tempRow, $col_names, $col_count] = $this->iterateOverColumns(
                $row,
                $col_names_in_first_row,
                $tempRow,
                $col_names,
                $col_count
            );

            /* Find the widest row */
            if ($col_count > $max_cols) {
                $max_cols = $col_count;
            }

            /* Don't include a row that is full of NULL values */
            if (! $col_names_in_first_row) {
                if ($_REQUEST['ods_empty_rows'] ?? false) {
                    foreach ($tempRow as $cell) {
                        if (strcmp('NULL', (string) $cell)) {
                            $tempRows[] = $tempRow;
                            break;
                        }
                    }
                } else {
                    $tempRows[] = $tempRow;
                }
            }

            $col_count = 0;
            $col_names_in_first_row = false;
            $tempRow = [];
        }

        return [$tempRow, $col_names, $max_cols, $tempRows];
    }

    /**
     * @param array|SimpleXMLElement $sheets Sheets of the spreadsheet.
     *
     * @return array|array[]
     */
    private function iterateOverTables($sheets): array
    {
        $tables = [];
        $max_cols = 0;
        $col_count = 0;
        $col_names = [];
        $tempRow = [];
        $tempRows = [];
        $rows = [];

        /** @var SimpleXMLElement $sheet */
        foreach ($sheets as $sheet) {
            $col_names_in_first_row = isset($_REQUEST['ods_col_names']);

            [$tempRow, $col_names, $max_cols, $tempRows] = $this->iterateOverRows(
                $sheet,
                $col_names_in_first_row,
                $tempRow,
                $col_names,
                $col_count,
                $max_cols,
                $tempRows
            );

            /* Skip over empty sheets */
            if (count($tempRows) == 0 || count($tempRows[0]) === 0) {
                $col_names = [];
                $tempRow = [];
                $tempRows = [];
                continue;
            }

            /**
             * Fill out each row as necessary to make
             * every one exactly as wide as the widest
             * row. This included column names.
             */

            /* Fill out column names */
            for ($i = count($col_names); $i < $max_cols; ++$i) {
                $col_names[] = $this->import->getColumnAlphaName($i + 1);
            }

            /* Fill out all rows */
            $num_rows = count($tempRows);
            for ($i = 0; $i < $num_rows; ++$i) {
                for ($j = count($tempRows[$i]); $j < $max_cols; ++$j) {
                    $tempRows[$i][] = 'NULL';
                }
            }

            /* Store the table name so we know where to place the row set */
            $tbl_attr = $sheet->attributes('table', true);
            $tables[] = [(string) $tbl_attr['name']];

            /* Store the current sheet in the accumulator */
            $rows[] = [
                (string) $tbl_attr['name'],
                $col_names,
                $tempRows,
            ];
            $tempRows = [];
            $col_names = [];
            $max_cols = 0;
        }

        return [$tables, $rows];
    }
}
