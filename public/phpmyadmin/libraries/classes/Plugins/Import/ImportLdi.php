<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Message;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Util;

use function __;
use function count;
use function is_array;
use function preg_split;
use function strlen;
use function trim;

use const PHP_EOL;

/**
 * CSV import plugin for phpMyAdmin using LOAD DATA
 */
class ImportLdi extends AbstractImportCsv
{
    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'ldi';
    }

    protected function setProperties(): ImportPluginProperties
    {
        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText('CSV using LOAD DATA');
        $importPluginProperties->setExtension('ldi');

        if (! self::isAvailable()) {
            return $importPluginProperties;
        }

        if ($GLOBALS['cfg']['Import']['ldi_local_option'] === 'auto') {
            $this->setLdiLocalOptionConfig();
        }

        $importPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $importPluginProperties
        // this will be shown as "Format specific options"
        $importSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        $generalOptions = $this->getGeneralOptions();

        $leaf = new TextPropertyItem(
            'columns',
            __('Column names: ')
        );
        $generalOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem(
            'ignore',
            __('Do not abort on INSERT error')
        );
        $generalOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem(
            'local_option',
            __('Use LOCAL keyword')
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
        global $finished, $import_file, $charset_conversion, $table, $dbi;
        global $ldi_local_option, $ldi_replace, $ldi_ignore, $ldi_terminated,
               $ldi_enclosed, $ldi_escaped, $ldi_new_line, $skip_queries, $ldi_columns;

        $compression = '';
        if ($importHandle !== null) {
            $compression = $importHandle->getCompression();
        }

        if ($import_file === 'none' || $compression !== 'none' || $charset_conversion) {
            // We handle only some kind of data!
            $GLOBALS['message'] = Message::error(
                __('This plugin does not support compressed imports!')
            );
            $GLOBALS['error'] = true;

            return;
        }

        $sql = 'LOAD DATA';
        if (isset($ldi_local_option)) {
            $sql .= ' LOCAL';
        }

        $sql .= ' INFILE \'' . $dbi->escapeString($import_file)
            . '\'';
        if (isset($ldi_replace)) {
            $sql .= ' REPLACE';
        } elseif (isset($ldi_ignore)) {
            $sql .= ' IGNORE';
        }

        $sql .= ' INTO TABLE ' . Util::backquote($table);

        if (strlen((string) $ldi_terminated) > 0) {
            $sql .= ' FIELDS TERMINATED BY \'' . $ldi_terminated . '\'';
        }

        if (strlen((string) $ldi_enclosed) > 0) {
            $sql .= ' ENCLOSED BY \''
                . $dbi->escapeString($ldi_enclosed) . '\'';
        }

        if (strlen((string) $ldi_escaped) > 0) {
            $sql .= ' ESCAPED BY \''
                . $dbi->escapeString($ldi_escaped) . '\'';
        }

        if (strlen((string) $ldi_new_line) > 0) {
            if ($ldi_new_line === 'auto') {
                $ldi_new_line = PHP_EOL == "\n"
                    ? '\n'
                    : '\r\n';
            }

            $sql .= ' LINES TERMINATED BY \'' . $ldi_new_line . '\'';
        }

        if ($skip_queries > 0) {
            $sql .= ' IGNORE ' . $skip_queries . ' LINES';
            $skip_queries = 0;
        }

        if (strlen((string) $ldi_columns) > 0) {
            $sql .= ' (';
            $tmp = preg_split('/,( ?)/', $ldi_columns);

            if (! is_array($tmp)) {
                $tmp = [];
            }

            $cnt_tmp = count($tmp);
            for ($i = 0; $i < $cnt_tmp; $i++) {
                if ($i > 0) {
                    $sql .= ', ';
                }

                /* Trim also `, if user already included backquoted fields */
                $sql .= Util::backquote(
                    trim($tmp[$i], " \t\r\n\0\x0B`")
                );
            }

            $sql .= ')';
        }

        $this->import->runQuery($sql, $sql, $sql_data);
        $this->import->runQuery('', '', $sql_data);
        $finished = true;
    }

    public static function isAvailable(): bool
    {
        global $plugin_param;

        // We need relations enabled and we work only on database.
        return isset($plugin_param) && $plugin_param === 'table';
    }

    private function setLdiLocalOptionConfig(): void
    {
        global $dbi;

        $GLOBALS['cfg']['Import']['ldi_local_option'] = false;
        $result = $dbi->tryQuery('SELECT @@local_infile;');

        if ($result === false || $result->numRows() <= 0) {
            return;
        }

        $tmp = $result->fetchValue();
        if ($tmp !== 'ON' && $tmp !== '1') {
            return;
        }

        $GLOBALS['cfg']['Import']['ldi_local_option'] = true;
    }
}
