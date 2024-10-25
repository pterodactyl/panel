<?php
/**
 * XML import plugin for phpMyAdmin
 *
 * @todo       Improve efficiency
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Import;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Util;
use SimpleXMLElement;

use function __;
use function count;
use function in_array;
use function libxml_disable_entity_loader;
use function simplexml_load_string;
use function str_replace;
use function strcmp;
use function strlen;

use const LIBXML_COMPACT;
use const PHP_VERSION_ID;

/**
 * Handles the import for the XML format
 */
class ImportXml extends ImportPlugin
{
    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'xml';
    }

    protected function setProperties(): ImportPluginProperties
    {
        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText(__('XML'));
        $importPluginProperties->setExtension('xml');
        $importPluginProperties->setMimeType('text/xml');
        $importPluginProperties->setOptionsText(__('Options'));

        return $importPluginProperties;
    }

    /**
     * Handles the whole import logic
     *
     * @param array $sql_data 2-element array with sql data
     */
    public function doImport(?File $importHandle = null, array &$sql_data = []): void
    {
        global $error, $timeout_passed, $finished, $db;

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

        /**
         * The XML was malformed
         */
        if ($xml === false) {
            echo Message::error(
                __(
                    'The XML file specified was either malformed or incomplete. Please correct the issue and try again.'
                )
            )->getDisplay();
            unset($xml);
            $GLOBALS['finished'] = false;

            return;
        }

        /**
         * Table accumulator
         */
        $tables = [];
        /**
         * Row accumulator
         */
        $rows = [];

        /**
         * Temp arrays
         */
        $tempRow = [];
        $tempCells = [];

        /**
         * CREATE code included (by default: no)
         */
        $struct_present = false;

        /**
         * Analyze the data in each table
         */
        $namespaces = $xml->getNamespaces(true);

        /**
         * Get the database name, collation and charset
         */
        $db_attr = $xml->children($namespaces['pma'] ?? null)
            ->{'structure_schemas'}->{'database'};

        if ($db_attr instanceof SimpleXMLElement) {
            $db_attr = $db_attr->attributes();
            $db_name = (string) $db_attr['name'];
            $collation = (string) $db_attr['collation'];
            $charset = (string) $db_attr['charset'];
        } else {
            /**
             * If the structure section is not present
             * get the database name from the data section
             */
            $db_attr = $xml->children()
                ->attributes();
            $db_name = (string) $db_attr['name'];
            $collation = null;
            $charset = null;
        }

        /**
         * The XML was malformed
         */
        if ($db_name === '') {
            echo Message::error(
                __(
                    'The XML file specified was either malformed or incomplete. Please correct the issue and try again.'
                )
            )->getDisplay();
            unset($xml);
            $GLOBALS['finished'] = false;

            return;
        }

        /**
         * Retrieve the structure information
         */
        if (isset($namespaces['pma'])) {
            /**
             * Get structures for all tables
             *
             * @var SimpleXMLElement $struct
             */
            $struct = $xml->children($namespaces['pma']);

            $create = [];

            foreach ($struct as $val1) {
                foreach ($val1 as $val2) {
                    // Need to select the correct database for the creation of
                    // tables, views, triggers, etc.
                    /**
                     * @todo    Generating a USE here blocks importing of a table
                     *          into another database.
                     */
                    $attrs = $val2->attributes();
                    $create[] = 'USE ' . Util::backquote((string) $attrs['name']);

                    foreach ($val2 as $val3) {
                        /**
                         * Remove the extra cosmetic spacing
                         */
                        $val3 = str_replace('                ', '', (string) $val3);
                        $create[] = $val3;
                    }
                }
            }

            $struct_present = true;
        }

        /**
         * Move down the XML tree to the actual data
         */
        $xml = $xml->children()
            ->children();

        $data_present = false;

        /**
         * Only attempt to analyze/collect data if there is data present
         */
        if ($xml && $xml->children()->count()) {
            $data_present = true;

            /**
             * Process all database content
             */
            foreach ($xml as $v1) {
                /** @psalm-suppress PossiblyNullReference */
                $tbl_attr = $v1->attributes();

                $isInTables = false;
                $num_tables = count($tables);
                for ($i = 0; $i < $num_tables; ++$i) {
                    if (! strcmp($tables[$i][Import::TBL_NAME], (string) $tbl_attr['name'])) {
                        $isInTables = true;
                        break;
                    }
                }

                if (! $isInTables) {
                    $tables[] = [(string) $tbl_attr['name']];
                }

                foreach ($v1 as $v2) {
                    /** @psalm-suppress PossiblyNullReference */
                    $row_attr = $v2->attributes();
                    if (! in_array((string) $row_attr['name'], $tempRow)) {
                        $tempRow[] = (string) $row_attr['name'];
                    }

                    $tempCells[] = (string) $v2;
                }

                $rows[] = [
                    (string) $tbl_attr['name'],
                    $tempRow,
                    $tempCells,
                ];

                $tempRow = [];
                $tempCells = [];
            }

            unset($tempRow, $tempCells, $xml);

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

                    $tables[$i][Import::ROWS][] = $rows[$j][Import::ROWS];
                }
            }

            unset($rows);

            if (! $struct_present) {
                $analyses = [];

                $len = count($tables);
                for ($i = 0; $i < $len; ++$i) {
                    $analyses[] = $this->import->analyzeTable($tables[$i]);
                }
            }
        }

        unset($xml, $tempCells, $rows);

        /**
         * Only build SQL from data if there is data present
         */
        if ($data_present) {
            /**
             * Set values to NULL if they were not present
             * to maintain Import::buildSql() call integrity
             */
            if (! isset($analyses)) {
                $analyses = null;
                if (! $struct_present) {
                    $create = null;
                }
            }
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
        if (strlen((string) $db)) {
            /* Override the database name in the XML file, if one is selected */
            $db_name = $db;
            $options = ['create_db' => false];
        } else {
            /* Set database collation/charset */
            $options = [
                'db_collation' => $collation,
                'db_charset' => $charset,
            ];
        }

        /* Created and execute necessary SQL statements from data */
        $this->import->buildSql($db_name, $tables, $analyses, $create, $options, $sql_data);

        unset($analyses, $tables, $create);

        /* Commit any possible data in buffers */
        $this->import->runQuery('', '', $sql_data);
    }
}
