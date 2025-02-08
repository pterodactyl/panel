<?php
/**
 * SQL import plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\SqlParser\Utils\BufferedQuery;

use function __;
use function count;
use function implode;
use function mb_strlen;
use function preg_replace;

/**
 * Handles the import for the SQL format
 */
class ImportSql extends ImportPlugin
{
    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'sql';
    }

    protected function setProperties(): ImportPluginProperties
    {
        global $dbi;

        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText('SQL');
        $importPluginProperties->setExtension('sql');
        $importPluginProperties->setOptionsText(__('Options'));

        $compats = $dbi->getCompatibilities();
        if (count($compats) > 0) {
            $values = [];
            foreach ($compats as $val) {
                $values[$val] = $val;
            }

            // create the root group that will be the options field for
            // $importPluginProperties
            // this will be shown as "Format specific options"
            $importSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

            // general options main group
            $generalOptions = new OptionsPropertyMainGroup('general_opts');
            // create primary items and add them to the group
            $leaf = new SelectPropertyItem(
                'compatibility',
                __('SQL compatibility mode:')
            );
            $leaf->setValues($values);
            $leaf->setDoc(
                [
                    'manual_MySQL_Database_Administration',
                    'Server_SQL_mode',
                ]
            );
            $generalOptions->addProperty($leaf);
            $leaf = new BoolPropertyItem(
                'no_auto_value_on_zero',
                __('Do not use <code>AUTO_INCREMENT</code> for zero values')
            );
            $leaf->setDoc(
                [
                    'manual_MySQL_Database_Administration',
                    'Server_SQL_mode',
                    'sqlmode_no_auto_value_on_zero',
                ]
            );
            $generalOptions->addProperty($leaf);

            // add the main group to the root group
            $importSpecificOptions->addProperty($generalOptions);
            // set the options for the import plugin property item
            $importPluginProperties->setOptions($importSpecificOptions);
        }

        return $importPluginProperties;
    }

    /**
     * Handles the whole import logic
     *
     * @param array $sql_data 2-element array with sql data
     */
    public function doImport(?File $importHandle = null, array &$sql_data = []): void
    {
        global $error, $timeout_passed, $dbi;

        // Handle compatibility options.
        $this->setSQLMode($dbi, $_REQUEST);

        $bq = new BufferedQuery();
        if (isset($_POST['sql_delimiter'])) {
            $bq->setDelimiter($_POST['sql_delimiter']);
        }

        /**
         * Will be set in Import::getNextChunk().
         *
         * @global bool $GLOBALS ['finished']
         */
        $GLOBALS['finished'] = false;

        while (! $error && (! $timeout_passed)) {
            // Getting the first statement, the remaining data and the last
            // delimiter.
            $statement = $bq->extract();

            // If there is no full statement, we are looking for more data.
            if (empty($statement)) {
                // Importing new data.
                $newData = $this->import->getNextChunk($importHandle);

                // Subtract data we didn't handle yet and stop processing.
                if ($newData === false) {
                    $GLOBALS['offset'] -= mb_strlen($bq->query);
                    break;
                }

                // Checking if the input buffer has finished.
                if ($newData === true) {
                    $GLOBALS['finished'] = true;
                    break;
                }

                // Convert CR (but not CRLF) to LF otherwise all queries may
                // not get executed on some platforms.
                $bq->query .= preg_replace("/\r($|[^\n])/", "\n$1", $newData);

                continue;
            }

            // Executing the query.
            $this->import->runQuery($statement, $statement, $sql_data);
        }

        // Extracting remaining statements.
        while (! $error && ! $timeout_passed && ! empty($bq->query)) {
            $statement = $bq->extract(true);
            if (empty($statement)) {
                continue;
            }

            $this->import->runQuery($statement, $statement, $sql_data);
        }

        if ($GLOBALS['error']) {
            return;
        }

        // Finishing.
        $this->import->runQuery('', '', $sql_data);
    }

    /**
     * Handle compatibility options
     *
     * @param DatabaseInterface $dbi     Database interface
     * @param array             $request Request array
     */
    private function setSQLMode($dbi, array $request): void
    {
        $sql_modes = [];
        if (isset($request['sql_compatibility']) && $request['sql_compatibility'] !== 'NONE') {
            $sql_modes[] = $request['sql_compatibility'];
        }

        if (isset($request['sql_no_auto_value_on_zero'])) {
            $sql_modes[] = 'NO_AUTO_VALUE_ON_ZERO';
        }

        if (count($sql_modes) <= 0) {
            return;
        }

        $dbi->tryQuery(
            'SET SQL_MODE="' . implode(',', $sql_modes) . '"'
        );
    }
}
