<?php

declare(strict_types=1);

namespace PhpMyAdmin\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Core;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Table;
use PhpMyAdmin\Util;

use function explode;
use function function_exists;
use function in_array;
use function is_array;
use function is_string;
use function mb_strpos;
use function strlen;
use function urldecode;

final class Options
{
    /** @var Relation */
    private $relation;

    /** @var TemplateModel */
    private $templateModel;

    public function __construct(Relation $relation, TemplateModel $templateModel)
    {
        $this->relation = $relation;
        $this->templateModel = $templateModel;
    }

    /**
     * Outputs appropriate checked statement for checkbox.
     *
     * @param string $str option name
     */
    private function checkboxCheck($str): bool
    {
        return isset($GLOBALS['cfg']['Export'][$str])
            && $GLOBALS['cfg']['Export'][$str];
    }

    /**
     * Prints Html For Export Selection Options
     *
     * @param string $tmpSelect Tmp selected method of export
     *
     * @return array
     */
    public function getDatabasesForSelectOptions($tmpSelect = '')
    {
        /** @var array|string|null $dbSelect */
        $dbSelect = $_POST['db_select'] ?? null;

        // Check if the selected databases are defined in $_POST
        // (from clicking Back button on /export page)
        if (isset($dbSelect) && is_string($dbSelect)) {
            $dbSelect = urldecode($dbSelect);
            $dbSelect = explode(',', $dbSelect);
        }

        $databases = [];
        foreach ($GLOBALS['dblist']->databases as $currentDb) {
            if (Utilities::isSystemSchema($currentDb, true)) {
                continue;
            }

            $isSelected = false;
            if (isset($dbSelect) && is_array($dbSelect)) {
                if (in_array($currentDb, $dbSelect)) {
                    $isSelected = true;
                }
            } elseif (! empty($tmpSelect)) {
                if (mb_strpos(' ' . $tmpSelect, '|' . $currentDb . '|')) {
                    $isSelected = true;
                }
            } else {
                $isSelected = true;
            }

            $databases[] = [
                'name' => $currentDb,
                'is_selected' => $isSelected,
            ];
        }

        return $databases;
    }

    /**
     * @param string         $exportType   export type: server|database|table
     * @param string         $db           selected DB
     * @param string         $table        selected table
     * @param string         $sqlQuery     SQL query
     * @param int|string     $numTables    number of tables
     * @param int|string     $unlimNumRows unlimited number of rows
     * @param ExportPlugin[] $exportList
     *
     * @return array<string, mixed>
     */
    public function getOptions(
        $exportType,
        $db,
        $table,
        $sqlQuery,
        $numTables,
        $unlimNumRows,
        array $exportList
    ) {
        global $cfg;

        $exportTemplatesFeature = $this->relation->getRelationParameters()->exportTemplatesFeature;

        $templates = [];

        if ($exportTemplatesFeature !== null) {
            $templates = $this->templateModel->getAll(
                $exportTemplatesFeature->database,
                $exportTemplatesFeature->exportTemplates,
                $GLOBALS['cfg']['Server']['user'],
                $exportType
            );

            $templates = is_array($templates) ? $templates : [];
        }

        $default = isset($_GET['what']) ? (string) $_GET['what'] : Plugins::getDefault('Export', 'format');
        $dropdown = Plugins::getChoice($exportList, $default);
        $tableObject = new Table($table, $db);
        $rows = [];

        if (strlen($table) > 0 && empty($numTables) && ! $tableObject->isMerge() && $exportType !== 'raw') {
            $rows = [
                'allrows' => $_POST['allrows'] ?? null,
                'limit_to' => $_POST['limit_to'] ?? null,
                'limit_from' => $_POST['limit_from'] ?? null,
                'unlim_num_rows' => $unlimNumRows,
                'number_of_rows' => $tableObject->countRecords(),
            ];
        }

        $hasAliases = isset($_SESSION['tmpval']['aliases']) && ! Core::emptyRecursive($_SESSION['tmpval']['aliases']);
        $aliases = $_SESSION['tmpval']['aliases'] ?? [];
        unset($_SESSION['tmpval']['aliases']);
        $filenameTemplate = $this->getFileNameTemplate($exportType, $_POST['filename_template'] ?? null);
        $isEncodingSupported = Encoding::isSupported();
        $selectedCompression = $_POST['compression'] ?? $cfg['Export']['compression'] ?? 'none';

        if (isset($cfg['Export']['as_separate_files']) && $cfg['Export']['as_separate_files']) {
            $selectedCompression = 'zip';
        }

        $hiddenInputs = [
            'db' => $db,
            'table' => $table,
            'export_type' => $exportType,
            'export_method' => $_POST['export_method'] ?? $cfg['Export']['method'] ?? 'quick',
            'template_id' => $_POST['template_id'] ?? '',
        ];

        if (! empty($GLOBALS['single_table'])) {
            $hiddenInputs['single_table'] = true;
        }

        if (! empty($sqlQuery)) {
            $hiddenInputs['sql_query'] = $sqlQuery;
        }

        return [
            'export_type' => $exportType,
            'db' => $db,
            'table' => $table,
            'templates' => [
                'is_enabled' => $exportTemplatesFeature !== null,
                'templates' => $templates,
                'selected' => $_POST['template_id'] ?? null,
            ],
            'sql_query' => $sqlQuery,
            'hidden_inputs' => $hiddenInputs,
            'export_method' => $_POST['quick_or_custom'] ?? $cfg['Export']['method'] ?? '',
            'plugins_choice' => $dropdown,
            'options' => Plugins::getOptions('Export', $exportList),
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'exec_time_limit' => $cfg['ExecTimeLimit'],
            'rows' => $rows,
            'has_save_dir' => isset($cfg['SaveDir']) && ! empty($cfg['SaveDir']),
            'save_dir' => Util::userDir((string) ($cfg['SaveDir'] ?? '')),
            'export_is_checked' => $this->checkboxCheck('quick_export_onserver'),
            'export_overwrite_is_checked' => $this->checkboxCheck('quick_export_onserver_overwrite'),
            'has_aliases' => $hasAliases,
            'aliases' => $aliases,
            'is_checked_lock_tables' => $this->checkboxCheck('lock_tables'),
            'is_checked_asfile' => $this->checkboxCheck('asfile'),
            'is_checked_as_separate_files' => $this->checkboxCheck('as_separate_files'),
            'is_checked_export' => $this->checkboxCheck('onserver'),
            'is_checked_export_overwrite' => $this->checkboxCheck('onserver_overwrite'),
            'is_checked_remember_file_template' => $this->checkboxCheck('remember_file_template'),
            'repopulate' => isset($_POST['repopulate']),
            'lock_tables' => isset($_POST['lock_tables']),
            'is_encoding_supported' => $isEncodingSupported,
            'encodings' => $isEncodingSupported ? Encoding::listEncodings() : [],
            'export_charset' => $cfg['Export']['charset'],
            'export_asfile' => $cfg['Export']['asfile'],
            'has_zip' => $cfg['ZipDump'] && function_exists('gzcompress'),
            'has_gzip' => $cfg['GZipDump'] && function_exists('gzencode'),
            'selected_compression' => $selectedCompression,
            'filename_template' => $filenameTemplate,
        ];
    }

    private function getFileNameTemplate(string $exportType, ?string $filename = null): string
    {
        global $cfg, $config;

        if ($filename !== null) {
            return $filename;
        }

        if ($exportType === 'database') {
            return (string) $config->getUserValue('pma_db_filename_template', $cfg['Export']['file_template_database']);
        }

        if ($exportType === 'table') {
            return (string) $config->getUserValue('pma_table_filename_template', $cfg['Export']['file_template_table']);
        }

        return (string) $config->getUserValue('pma_server_filename_template', $cfg['Export']['file_template_server']);
    }
}
