<?php
/**
 * function for the main export logic
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Controllers\Database\ExportController as DatabaseExportController;
use PhpMyAdmin\Controllers\Server\ExportController as ServerExportController;
use PhpMyAdmin\Controllers\Table\ExportController as TableExportController;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\SchemaPlugin;

use function __;
use function array_merge_recursive;
use function error_get_last;
use function fclose;
use function file_exists;
use function fopen;
use function function_exists;
use function fwrite;
use function gzencode;
use function header;
use function htmlentities;
use function htmlspecialchars;
use function implode;
use function in_array;
use function ini_get;
use function is_array;
use function is_file;
use function is_numeric;
use function is_string;
use function is_writable;
use function mb_strlen;
use function mb_strpos;
use function mb_strtolower;
use function mb_substr;
use function ob_list_handlers;
use function preg_match;
use function preg_replace;
use function strlen;
use function strtolower;
use function substr;
use function time;
use function trim;
use function urlencode;

use const ENT_COMPAT;

/**
 * PhpMyAdmin\Export class
 */
class Export
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var mixed */
    public $dumpBuffer = '';

    /** @var int */
    public $dumpBufferLength = 0;

    /** @var array */
    public $dumpBufferObjects = [];

    /**
     * @param DatabaseInterface $dbi DatabaseInterface instance
     */
    public function __construct($dbi)
    {
        $this->dbi = $dbi;
    }

    /**
     * Sets a session variable upon a possible fatal error during export
     */
    public function shutdown(): void
    {
        $error = error_get_last();
        if ($error == null || ! mb_strpos($error['message'], 'execution time')) {
            return;
        }

        //set session variable to check if there was error while exporting
        $_SESSION['pma_export_error'] = $error['message'];
    }

    /**
     * Detect ob_gzhandler
     */
    public function isGzHandlerEnabled(): bool
    {
        /** @var string[] $handlers */
        $handlers = ob_list_handlers();

        return in_array('ob_gzhandler', $handlers);
    }

    /**
     * Detect whether gzencode is needed; it might not be needed if
     * the server is already compressing by itself
     */
    public function gzencodeNeeded(): bool
    {
        /*
         * We should gzencode only if the function exists
         * but we don't want to compress twice, therefore
         * gzencode only if transparent compression is not enabled
         * and gz compression was not asked via $cfg['OBGzip']
         * but transparent compression does not apply when saving to server
         */
        return function_exists('gzencode')
            && ((! ini_get('zlib.output_compression')
                    && ! $this->isGzHandlerEnabled())
                || $GLOBALS['save_on_server']
                || $GLOBALS['config']->get('PMA_USR_BROWSER_AGENT') === 'CHROME');
    }

    /**
     * Output handler for all exports, if needed buffering, it stores data into
     * $this->dumpBuffer, otherwise it prints them out.
     *
     * @param string $line the insert statement
     */
    public function outputHandler(?string $line): bool
    {
        global $time_start, $save_filename;

        // Kanji encoding convert feature
        if ($GLOBALS['output_kanji_conversion']) {
            $line = Encoding::kanjiStrConv($line, $GLOBALS['knjenc'], $GLOBALS['xkana'] ?? '');
        }

        // If we have to buffer data, we will perform everything at once at the end
        if ($GLOBALS['buffer_needed']) {
            $this->dumpBuffer .= $line;
            if ($GLOBALS['onfly_compression']) {
                $this->dumpBufferLength += strlen((string) $line);

                if ($this->dumpBufferLength > $GLOBALS['memory_limit']) {
                    if ($GLOBALS['output_charset_conversion']) {
                        $this->dumpBuffer = Encoding::convertString('utf-8', $GLOBALS['charset'], $this->dumpBuffer);
                    }

                    if ($GLOBALS['compression'] === 'gzip' && $this->gzencodeNeeded()) {
                        // as a gzipped file
                        // without the optional parameter level because it bugs
                        $this->dumpBuffer = gzencode($this->dumpBuffer);
                    }

                    if ($GLOBALS['save_on_server']) {
                        $writeResult = @fwrite($GLOBALS['file_handle'], (string) $this->dumpBuffer);
                        // Here, use strlen rather than mb_strlen to get the length
                        // in bytes to compare against the number of bytes written.
                        if ($writeResult != strlen((string) $this->dumpBuffer)) {
                            $GLOBALS['message'] = Message::error(
                                __('Insufficient space to save the file %s.')
                            );
                            $GLOBALS['message']->addParam($save_filename);

                            return false;
                        }
                    } else {
                        echo $this->dumpBuffer;
                    }

                    $this->dumpBuffer = '';
                    $this->dumpBufferLength = 0;
                }
            } else {
                $timeNow = time();
                if ($time_start >= $timeNow + 30) {
                    $time_start = $timeNow;
                    header('X-pmaPing: Pong');
                }
            }
        } elseif ($GLOBALS['asfile']) {
            if ($GLOBALS['output_charset_conversion']) {
                $line = Encoding::convertString('utf-8', $GLOBALS['charset'], $line);
            }

            if ($GLOBALS['save_on_server'] && mb_strlen((string) $line) > 0) {
                if ($GLOBALS['file_handle'] !== null) {
                    $writeResult = @fwrite($GLOBALS['file_handle'], (string) $line);
                } else {
                    $writeResult = false;
                }

                // Here, use strlen rather than mb_strlen to get the length
                // in bytes to compare against the number of bytes written.
                if (! $writeResult || $writeResult != strlen((string) $line)) {
                    $GLOBALS['message'] = Message::error(
                        __('Insufficient space to save the file %s.')
                    );
                    $GLOBALS['message']->addParam($save_filename);

                    return false;
                }

                $timeNow = time();
                if ($time_start >= $timeNow + 30) {
                    $time_start = $timeNow;
                    header('X-pmaPing: Pong');
                }
            } else {
                // We export as file - output normally
                echo $line;
            }
        } else {
            // We export as html - replace special chars
            echo htmlspecialchars((string) $line, ENT_COMPAT);
        }

        return true;
    }

    /**
     * Returns HTML containing the footer for a displayed export
     *
     * @param string $backButton    the link for going Back
     * @param string $refreshButton the link for refreshing page
     *
     * @return string the HTML output
     */
    public function getHtmlForDisplayedExportFooter(
        string $backButton,
        string $refreshButton
    ): string {
        /**
         * Close the html tags and add the footers for on-screen export
         */
        return '</textarea>'
            . '    </form>'
            . '<br>'
            // bottom back button
            . $backButton
            . $refreshButton
            . '</div>'
            . '<script type="text/javascript">' . "\n"
            . '//<![CDATA[' . "\n"
            . 'var $body = $("body");' . "\n"
            . '$("#textSQLDUMP")' . "\n"
            . '.width($body.width() - 50)' . "\n"
            . '.height($body.height() - 100);' . "\n"
            . '//]]>' . "\n"
            . '</script>' . "\n";
    }

    /**
     * Computes the memory limit for export
     *
     * @return int the memory limit
     */
    public function getMemoryLimit(): int
    {
        $memoryLimit = trim((string) ini_get('memory_limit'));
        $memoryLimitNumber = (int) substr($memoryLimit, 0, -1);
        $lowerLastChar = strtolower(substr($memoryLimit, -1));
        // 2 MB as default
        if (empty($memoryLimit) || $memoryLimit == '-1') {
            $memoryLimit = 2 * 1024 * 1024;
        } elseif ($lowerLastChar === 'm') {
            $memoryLimit = $memoryLimitNumber * 1024 * 1024;
        } elseif ($lowerLastChar === 'k') {
            $memoryLimit = $memoryLimitNumber * 1024;
        } elseif ($lowerLastChar === 'g') {
            $memoryLimit = $memoryLimitNumber * 1024 * 1024 * 1024;
        } else {
            $memoryLimit = (int) $memoryLimit;
        }

        // Some of memory is needed for other things and as threshold.
        // During export I had allocated (see memory_get_usage function)
        // approx 1.2MB so this comes from that.
        if ($memoryLimit > 1500000) {
            $memoryLimit -= 1500000;
        }

        // Some memory is needed for compression, assume 1/3
        $memoryLimit /= 8;

        return $memoryLimit;
    }

    /**
     * Returns the filename and MIME type for a compression and an export plugin
     *
     * @param ExportPlugin $exportPlugin the export plugin
     * @param string       $compression  compression asked
     * @param string       $filename     the filename
     *
     * @return string[]    the filename and mime type
     */
    public function getFinalFilenameAndMimetypeForFilename(
        ExportPlugin $exportPlugin,
        string $compression,
        string $filename
    ): array {
        // Grab basic dump extension and mime type
        // Check if the user already added extension;
        // get the substring where the extension would be if it was included
        $requiredExtension = '.' . $exportPlugin->getProperties()->getExtension();
        $extensionLength = mb_strlen($requiredExtension);
        $userExtension = mb_substr($filename, -$extensionLength);
        if (mb_strtolower($userExtension) != $requiredExtension) {
            $filename .= $requiredExtension;
        }

        $mediaType = $exportPlugin->getProperties()->getMimeType();

        // If dump is going to be compressed, set correct mime_type and add
        // compression to extension
        if ($compression === 'gzip') {
            $filename .= '.gz';
            $mediaType = 'application/x-gzip';
        } elseif ($compression === 'zip') {
            $filename .= '.zip';
            $mediaType = 'application/zip';
        }

        return [
            $filename,
            $mediaType,
        ];
    }

    /**
     * Return the filename and MIME type for export file
     *
     * @param string       $exportType       type of export
     * @param string       $rememberTemplate whether to remember template
     * @param ExportPlugin $exportPlugin     the export plugin
     * @param string       $compression      compression asked
     * @param string       $filenameTemplate the filename template
     *
     * @return string[] the filename template and mime type
     */
    public function getFilenameAndMimetype(
        string $exportType,
        string $rememberTemplate,
        ExportPlugin $exportPlugin,
        string $compression,
        string $filenameTemplate
    ): array {
        if ($exportType === 'server') {
            if (! empty($rememberTemplate)) {
                $GLOBALS['config']->setUserValue(
                    'pma_server_filename_template',
                    'Export/file_template_server',
                    $filenameTemplate
                );
            }
        } elseif ($exportType === 'database') {
            if (! empty($rememberTemplate)) {
                $GLOBALS['config']->setUserValue(
                    'pma_db_filename_template',
                    'Export/file_template_database',
                    $filenameTemplate
                );
            }
        } elseif ($exportType === 'raw') {
            if (! empty($rememberTemplate)) {
                $GLOBALS['config']->setUserValue(
                    'pma_raw_filename_template',
                    'Export/file_template_raw',
                    $filenameTemplate
                );
            }
        } else {
            if (! empty($rememberTemplate)) {
                $GLOBALS['config']->setUserValue(
                    'pma_table_filename_template',
                    'Export/file_template_table',
                    $filenameTemplate
                );
            }
        }

        $filename = Util::expandUserString($filenameTemplate);
        // remove dots in filename (coming from either the template or already
        // part of the filename) to avoid a remote code execution vulnerability
        $filename = Sanitize::sanitizeFilename($filename, true);

        return $this->getFinalFilenameAndMimetypeForFilename($exportPlugin, $compression, $filename);
    }

    /**
     * Open the export file
     *
     * @param string $filename    the export filename
     * @param bool   $quickExport whether it's a quick export or not
     *
     * @return array the full save filename, possible message and the file handle
     */
    public function openFile(string $filename, bool $quickExport): array
    {
        $fileHandle = null;
        $message = '';
        $doNotSaveItOver = true;

        if (isset($_POST['quick_export_onserver_overwrite'])) {
            $doNotSaveItOver = $_POST['quick_export_onserver_overwrite'] !== 'saveitover';
        }

        $saveFilename = Util::userDir((string) ($GLOBALS['cfg']['SaveDir'] ?? ''))
            . preg_replace('@[/\\\\]@', '_', $filename);

        if (
            @file_exists($saveFilename)
            && ((! $quickExport && empty($_POST['onserver_overwrite']))
            || ($quickExport
            && $doNotSaveItOver))
        ) {
            $message = Message::error(
                __(
                    'File %s already exists on server, change filename or check overwrite option.'
                )
            );
            $message->addParam($saveFilename);
        } elseif (@is_file($saveFilename) && ! @is_writable($saveFilename)) {
            $message = Message::error(
                __(
                    'The web server does not have permission to save the file %s.'
                )
            );
            $message->addParam($saveFilename);
        } else {
            $fileHandle = @fopen($saveFilename, 'w');

            if ($fileHandle === false) {
                $message = Message::error(
                    __(
                        'The web server does not have permission to save the file %s.'
                    )
                );
                $message->addParam($saveFilename);
            }
        }

        return [
            $saveFilename,
            $message,
            $fileHandle,
        ];
    }

    /**
     * Close the export file
     *
     * @param resource $fileHandle   the export file handle
     * @param string   $dumpBuffer   the current dump buffer
     * @param string   $saveFilename the export filename
     *
     * @return Message a message object (or empty string)
     */
    public function closeFile(
        $fileHandle,
        string $dumpBuffer,
        string $saveFilename
    ): Message {
        $writeResult = @fwrite($fileHandle, $dumpBuffer);
        fclose($fileHandle);
        // Here, use strlen rather than mb_strlen to get the length
        // in bytes to compare against the number of bytes written.
        if (strlen($dumpBuffer) > 0 && (! $writeResult || $writeResult != strlen($dumpBuffer))) {
            $message = new Message(
                __('Insufficient space to save the file %s.'),
                Message::ERROR,
                [$saveFilename]
            );
        } else {
            $message = new Message(
                __('Dump has been saved to file %s.'),
                Message::SUCCESS,
                [$saveFilename]
            );
        }

        return $message;
    }

    /**
     * Compress the export buffer
     *
     * @param array|string $dumpBuffer  the current dump buffer
     * @param string       $compression the compression mode
     * @param string       $filename    the filename
     *
     * @return array|string|bool
     */
    public function compress($dumpBuffer, string $compression, string $filename)
    {
        if ($compression === 'zip' && function_exists('gzcompress')) {
            $zipExtension = new ZipExtension();
            $filename = substr($filename, 0, -4); // remove extension (.zip)
            $dumpBuffer = $zipExtension->createFile($dumpBuffer, $filename);
        } elseif ($compression === 'gzip' && $this->gzencodeNeeded() && is_string($dumpBuffer)) {
            // without the optional parameter level because it bugs
            $dumpBuffer = gzencode($dumpBuffer);
        }

        return $dumpBuffer;
    }

    /**
     * Saves the dump buffer for a particular table in an array
     * Used in separate files export
     *
     * @param string $objectName the name of current object to be stored
     * @param bool   $append     optional boolean to append to an existing index or not
     */
    public function saveObjectInBuffer(string $objectName, bool $append = false): void
    {
        if (! empty($this->dumpBuffer)) {
            if ($append && isset($this->dumpBufferObjects[$objectName])) {
                $this->dumpBufferObjects[$objectName] .= $this->dumpBuffer;
            } else {
                $this->dumpBufferObjects[$objectName] = $this->dumpBuffer;
            }
        }

        // Re - initialize
        $this->dumpBuffer = '';
        $this->dumpBufferLength = 0;
    }

    /**
     * Returns HTML containing the header for a displayed export
     *
     * @param string $exportType the export type
     * @param string $db         the database name
     * @param string $table      the table name
     *
     * @return string[] the generated HTML and back button
     */
    public function getHtmlForDisplayedExportHeader(
        string $exportType,
        string $db,
        string $table
    ): array {
        $html = '<div>';

        /**
         * Displays a back button with all the $_POST data in the URL
         * (store in a variable to also display after the textarea)
         */
        $backButton = '<p>[ <a href="';
        if ($exportType === 'server') {
            $backButton .= Url::getFromRoute('/server/export') . '" data-post="' . Url::getCommon([], '', false);
        } elseif ($exportType === 'database') {
            $backButton .= Url::getFromRoute('/database/export') . '" data-post="' . Url::getCommon(
                ['db' => $db],
                '',
                false
            );
        } else {
            $backButton .= Url::getFromRoute('/table/export') . '" data-post="' . Url::getCommon(
                ['db' => $db, 'table' => $table],
                '',
                false
            );
        }

        $postParams = $_POST;

        // Convert the multiple select elements from an array to a string
        if ($exportType === 'database') {
            $structOrDataForced = empty($postParams['structure_or_data_forced']);
            if ($structOrDataForced && ! isset($postParams['table_structure'])) {
                $postParams['table_structure'] = [];
            }

            if ($structOrDataForced && ! isset($postParams['table_data'])) {
                $postParams['table_data'] = [];
            }
        }

        foreach ($postParams as $name => $value) {
            if (is_array($value)) {
                continue;
            }

            $backButton .= '&amp;' . urlencode((string) $name) . '=' . urlencode((string) $value);
        }

        $backButton .= '&amp;repopulate=1">' . __('Back') . '</a> ]</p>';
        $html .= '<br>';
        $html .= $backButton;
        $refreshButton = '<form id="export_refresh_form" method="POST" action="'
            . Url::getFromRoute('/export') . '" class="disableAjax">';
        $refreshButton .= '[ <a class="disableAjax export_refresh_btn">' . __('Refresh') . '</a> ]';
        foreach ($postParams as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $refreshButton .= '<input type="hidden" name="' . htmlentities((string) $name)
                        . '[]" value="' . htmlentities((string) $val) . '">';
                }
            } else {
                $refreshButton .= '<input type="hidden" name="' . htmlentities((string) $name)
                    . '" value="' . htmlentities((string) $value) . '">';
            }
        }

        $refreshButton .= '</form>';
        $html .= $refreshButton
            . '<br>'
            . '<form name="nofunction">'
            . '<textarea name="sqldump" cols="50" rows="30" '
            . 'id="textSQLDUMP" wrap="OFF">';

        return [
            $html,
            $backButton,
            $refreshButton,
        ];
    }

    /**
     * Export at the server level
     *
     * @param string|array $dbSelect        the selected databases to export
     * @param string       $whatStrucOrData structure or data or both
     * @param ExportPlugin $exportPlugin    the selected export plugin
     * @param string       $crlf            end of line character(s)
     * @param string       $errorUrl        the URL in case of error
     * @param string       $exportType      the export type
     * @param bool         $doRelation      whether to export relation info
     * @param bool         $doComments      whether to add comments
     * @param bool         $doMime          whether to add MIME info
     * @param bool         $doDates         whether to add dates
     * @param array        $aliases         alias information for db/table/column
     * @param string       $separateFiles   whether it is a separate-files export
     */
    public function exportServer(
        $dbSelect,
        string $whatStrucOrData,
        ExportPlugin $exportPlugin,
        string $crlf,
        string $errorUrl,
        string $exportType,
        bool $doRelation,
        bool $doComments,
        bool $doMime,
        bool $doDates,
        array $aliases,
        string $separateFiles
    ): void {
        if (! empty($dbSelect) && is_array($dbSelect)) {
            $tmpSelect = implode('|', $dbSelect);
            $tmpSelect = '|' . $tmpSelect . '|';
        }

        // Walk over databases
        foreach ($GLOBALS['dblist']->databases as $currentDb) {
            if (! isset($tmpSelect) || ! mb_strpos(' ' . $tmpSelect, '|' . $currentDb . '|')) {
                continue;
            }

            $tables = $this->dbi->getTables($currentDb);
            $this->exportDatabase(
                $currentDb,
                $tables,
                $whatStrucOrData,
                $tables,
                $tables,
                $exportPlugin,
                $crlf,
                $errorUrl,
                $exportType,
                $doRelation,
                $doComments,
                $doMime,
                $doDates,
                $aliases,
                $separateFiles === 'database' ? $separateFiles : ''
            );
            if ($separateFiles !== 'server') {
                continue;
            }

            $this->saveObjectInBuffer($currentDb);
        }
    }

    /**
     * Export at the database level
     *
     * @param string       $db              the database to export
     * @param array        $tables          the tables to export
     * @param string       $whatStrucOrData structure or data or both
     * @param array        $tableStructure  whether to export structure for each table
     * @param array        $tableData       whether to export data for each table
     * @param ExportPlugin $exportPlugin    the selected export plugin
     * @param string       $crlf            end of line character(s)
     * @param string       $errorUrl        the URL in case of error
     * @param string       $exportType      the export type
     * @param bool         $doRelation      whether to export relation info
     * @param bool         $doComments      whether to add comments
     * @param bool         $doMime          whether to add MIME info
     * @param bool         $doDates         whether to add dates
     * @param array        $aliases         Alias information for db/table/column
     * @param string       $separateFiles   whether it is a separate-files export
     */
    public function exportDatabase(
        string $db,
        array $tables,
        string $whatStrucOrData,
        array $tableStructure,
        array $tableData,
        ExportPlugin $exportPlugin,
        string $crlf,
        string $errorUrl,
        string $exportType,
        bool $doRelation,
        bool $doComments,
        bool $doMime,
        bool $doDates,
        array $aliases,
        string $separateFiles
    ): void {
        $dbAlias = ! empty($aliases[$db]['alias'])
            ? $aliases[$db]['alias'] : '';

        if (! $exportPlugin->exportDBHeader($db, $dbAlias)) {
            return;
        }

        if (! $exportPlugin->exportDBCreate($db, $exportType, $dbAlias)) {
            return;
        }

        if ($separateFiles === 'database') {
            $this->saveObjectInBuffer('database', true);
        }

        if (
            ($GLOBALS['sql_structure_or_data'] === 'structure'
            || $GLOBALS['sql_structure_or_data'] === 'structure_and_data')
            && isset($GLOBALS['sql_procedure_function'])
        ) {
            $exportPlugin->exportRoutines($db, $aliases);

            if ($separateFiles === 'database') {
                $this->saveObjectInBuffer('routines');
            }
        }

        $views = [];

        foreach ($tables as $table) {
            $tableObject = new Table($table, $db);
            // if this is a view, collect it for later;
            // views must be exported after the tables
            $isView = $tableObject->isView();
            if ($isView) {
                $views[] = $table;
            }

            if (
                ($whatStrucOrData === 'structure'
                || $whatStrucOrData === 'structure_and_data')
                && in_array($table, $tableStructure)
            ) {
                // for a view, export a stand-in definition of the table
                // to resolve view dependencies (only when it's a single-file export)
                if ($isView) {
                    if (
                        $separateFiles == ''
                        && isset($GLOBALS['sql_create_view'])
                        && ! $exportPlugin->exportStructure(
                            $db,
                            $table,
                            $crlf,
                            $errorUrl,
                            'stand_in',
                            $exportType,
                            $doRelation,
                            $doComments,
                            $doMime,
                            $doDates,
                            $aliases
                        )
                    ) {
                        break;
                    }
                } elseif (isset($GLOBALS['sql_create_table'])) {
                    $tableSize = $GLOBALS['maxsize'];
                    // Checking if the maximum table size constrain has been set
                    // And if that constrain is a valid number or not
                    if ($tableSize !== '' && is_numeric($tableSize)) {
                        // This obtains the current table's size
                        $query = 'SELECT data_length + index_length
                              from information_schema.TABLES
                              WHERE table_schema = "' . $this->dbi->escapeString($db) . '"
                              AND table_name = "' . $this->dbi->escapeString($table) . '"';

                        $size = (int) $this->dbi->fetchValue($query);
                        //Converting the size to MB
                        $size /= 1024 * 1024;
                        if ($size > $tableSize) {
                            continue;
                        }
                    }

                    if (
                        ! $exportPlugin->exportStructure(
                            $db,
                            $table,
                            $crlf,
                            $errorUrl,
                            'create_table',
                            $exportType,
                            $doRelation,
                            $doComments,
                            $doMime,
                            $doDates,
                            $aliases
                        )
                    ) {
                        break;
                    }
                }
            }

            // if this is a view or a merge table, don't export data
            if (
                ($whatStrucOrData === 'data' || $whatStrucOrData === 'structure_and_data')
                && in_array($table, $tableData)
                && ! $isView
            ) {
                $tableObj = new Table($table, $db);
                $nonGeneratedCols = $tableObj->getNonGeneratedColumns(true);

                $localQuery = 'SELECT ' . implode(', ', $nonGeneratedCols)
                    . ' FROM ' . Util::backquote($db)
                    . '.' . Util::backquote($table);

                if (! $exportPlugin->exportData($db, $table, $crlf, $errorUrl, $localQuery, $aliases)) {
                    break;
                }
            }

            // this buffer was filled, we save it and go to the next one
            if ($separateFiles === 'database') {
                $this->saveObjectInBuffer('table_' . $table);
            }

            // now export the triggers (needs to be done after the data because
            // triggers can modify already imported tables)
            if (
                ! isset($GLOBALS['sql_create_trigger']) || ($whatStrucOrData !== 'structure'
                && $whatStrucOrData !== 'structure_and_data')
                || ! in_array($table, $tableStructure)
            ) {
                continue;
            }

            if (
                ! $exportPlugin->exportStructure(
                    $db,
                    $table,
                    $crlf,
                    $errorUrl,
                    'triggers',
                    $exportType,
                    $doRelation,
                    $doComments,
                    $doMime,
                    $doDates,
                    $aliases
                )
            ) {
                break;
            }

            if ($separateFiles !== 'database') {
                continue;
            }

            $this->saveObjectInBuffer('table_' . $table, true);
        }

        if (isset($GLOBALS['sql_create_view'])) {
            foreach ($views as $view) {
                // no data export for a view
                if ($whatStrucOrData !== 'structure' && $whatStrucOrData !== 'structure_and_data') {
                    continue;
                }

                if (
                    ! $exportPlugin->exportStructure(
                        $db,
                        $view,
                        $crlf,
                        $errorUrl,
                        'create_view',
                        $exportType,
                        $doRelation,
                        $doComments,
                        $doMime,
                        $doDates,
                        $aliases
                    )
                ) {
                    break;
                }

                if ($separateFiles !== 'database') {
                    continue;
                }

                $this->saveObjectInBuffer('view_' . $view);
            }
        }

        if (! $exportPlugin->exportDBFooter($db)) {
            return;
        }

        // export metadata related to this db
        if (isset($GLOBALS['sql_metadata'])) {
            // Types of metadata to export.
            // In the future these can be allowed to be selected by the user
            $metadataTypes = $this->getMetadataTypes();
            $exportPlugin->exportMetadata($db, $tables, $metadataTypes);

            if ($separateFiles === 'database') {
                $this->saveObjectInBuffer('metadata');
            }
        }

        if ($separateFiles === 'database') {
            $this->saveObjectInBuffer('extra');
        }

        if (
            ($GLOBALS['sql_structure_or_data'] !== 'structure'
            && $GLOBALS['sql_structure_or_data'] !== 'structure_and_data')
            || ! isset($GLOBALS['sql_procedure_function'])
        ) {
            return;
        }

        $exportPlugin->exportEvents($db);

        if ($separateFiles !== 'database') {
            return;
        }

        $this->saveObjectInBuffer('events');
    }

    /**
     * Export a raw query
     *
     * @param string       $whatStrucOrData whether to export structure for each table or raw
     * @param ExportPlugin $exportPlugin    the selected export plugin
     * @param string       $crlf            end of line character(s)
     * @param string       $errorUrl        the URL in case of error
     * @param string|null  $db              the database where the query is executed
     * @param string       $sqlQuery        the query to be executed
     * @param string       $exportType      the export type
     */
    public static function exportRaw(
        string $whatStrucOrData,
        ExportPlugin $exportPlugin,
        string $crlf,
        string $errorUrl,
        ?string $db,
        string $sqlQuery,
        string $exportType
    ): void {
        // In case the we need to dump just the raw query
        if ($whatStrucOrData !== 'raw') {
            return;
        }

        if (! $exportPlugin->exportRawQuery($errorUrl, $db, $sqlQuery, $crlf)) {
            $GLOBALS['message'] = Message::error(
                // phpcs:disable Generic.Files.LineLength.TooLong
                /* l10n: A query written by the user is a "raw query" that could be using no tables or databases in particular */
                __('Exporting a raw query is not supported for this export method.')
            );

            return;
        }
    }

    /**
     * Export at the table level
     *
     * @param string       $db              the database to export
     * @param string       $table           the table to export
     * @param string       $whatStrucOrData structure or data or both
     * @param ExportPlugin $exportPlugin    the selected export plugin
     * @param string       $crlf            end of line character(s)
     * @param string       $errorUrl        the URL in case of error
     * @param string       $exportType      the export type
     * @param bool         $doRelation      whether to export relation info
     * @param bool         $doComments      whether to add comments
     * @param bool         $doMime          whether to add MIME info
     * @param bool         $doDates         whether to add dates
     * @param string|null  $allrows         whether "dump all rows" was ticked
     * @param string       $limitTo         upper limit
     * @param string       $limitFrom       starting limit
     * @param string       $sqlQuery        query for which exporting is requested
     * @param array        $aliases         Alias information for db/table/column
     */
    public function exportTable(
        string $db,
        string $table,
        string $whatStrucOrData,
        ExportPlugin $exportPlugin,
        string $crlf,
        string $errorUrl,
        string $exportType,
        bool $doRelation,
        bool $doComments,
        bool $doMime,
        bool $doDates,
        ?string $allrows,
        string $limitTo,
        string $limitFrom,
        string $sqlQuery,
        array $aliases
    ): void {
        $dbAlias = ! empty($aliases[$db]['alias'])
            ? $aliases[$db]['alias'] : '';
        if (! $exportPlugin->exportDBHeader($db, $dbAlias)) {
            return;
        }

        if (isset($allrows) && $allrows == '0' && $limitTo > 0 && $limitFrom >= 0) {
            $addQuery = ' LIMIT '
                        . ($limitFrom > 0 ? $limitFrom . ', ' : '')
                        . $limitTo;
        } else {
            $addQuery = '';
        }

        $tableObject = new Table($table, $db);
        $isView = $tableObject->isView();
        if ($whatStrucOrData === 'structure' || $whatStrucOrData === 'structure_and_data') {
            if ($isView) {
                if (isset($GLOBALS['sql_create_view'])) {
                    if (
                        ! $exportPlugin->exportStructure(
                            $db,
                            $table,
                            $crlf,
                            $errorUrl,
                            'create_view',
                            $exportType,
                            $doRelation,
                            $doComments,
                            $doMime,
                            $doDates,
                            $aliases
                        )
                    ) {
                        return;
                    }
                }
            } elseif (isset($GLOBALS['sql_create_table'])) {
                if (
                    ! $exportPlugin->exportStructure(
                        $db,
                        $table,
                        $crlf,
                        $errorUrl,
                        'create_table',
                        $exportType,
                        $doRelation,
                        $doComments,
                        $doMime,
                        $doDates,
                        $aliases
                    )
                ) {
                    return;
                }
            }
        }

        // If this is an export of a single view, we have to export data;
        // for example, a PDF report
        // if it is a merge table, no data is exported
        if ($whatStrucOrData === 'data' || $whatStrucOrData === 'structure_and_data') {
            if (! empty($sqlQuery)) {
                // only preg_replace if needed
                if (! empty($addQuery)) {
                    // remove trailing semicolon before adding a LIMIT
                    $sqlQuery = preg_replace('%;\s*$%', '', $sqlQuery);
                }

                $localQuery = $sqlQuery . $addQuery;
                $this->dbi->selectDb($db);
            } else {
                // Data is exported only for Non-generated columns
                $tableObj = new Table($table, $db);
                $nonGeneratedCols = $tableObj->getNonGeneratedColumns(true);

                $localQuery = 'SELECT ' . implode(', ', $nonGeneratedCols)
                    . ' FROM ' . Util::backquote($db)
                    . '.' . Util::backquote($table) . $addQuery;
            }

            if (! $exportPlugin->exportData($db, $table, $crlf, $errorUrl, $localQuery, $aliases)) {
                return;
            }
        }

        // now export the triggers (needs to be done after the data because
        // triggers can modify already imported tables)
        if (
            isset($GLOBALS['sql_create_trigger']) && ($whatStrucOrData === 'structure'
            || $whatStrucOrData === 'structure_and_data')
        ) {
            if (
                ! $exportPlugin->exportStructure(
                    $db,
                    $table,
                    $crlf,
                    $errorUrl,
                    'triggers',
                    $exportType,
                    $doRelation,
                    $doComments,
                    $doMime,
                    $doDates,
                    $aliases
                )
            ) {
                return;
            }
        }

        if (! $exportPlugin->exportDBFooter($db)) {
            return;
        }

        if (! isset($GLOBALS['sql_metadata'])) {
            return;
        }

        // Types of metadata to export.
        // In the future these can be allowed to be selected by the user
        $metadataTypes = $this->getMetadataTypes();
        $exportPlugin->exportMetadata($db, $table, $metadataTypes);
    }

    /**
     * Loads correct page after doing export
     */
    public function showPage(string $exportType): void
    {
        global $active_page, $containerBuilder;

        if ($exportType === 'server') {
            $active_page = Url::getFromRoute('/server/export');
            /** @var ServerExportController $controller */
            $controller = $containerBuilder->get(ServerExportController::class);
            $controller();

            return;
        }

        if ($exportType === 'database') {
            $active_page = Url::getFromRoute('/database/export');
            /** @var DatabaseExportController $controller */
            $controller = $containerBuilder->get(DatabaseExportController::class);
            $controller();

            return;
        }

        $active_page = Url::getFromRoute('/table/export');
        /** @var TableExportController $controller */
        $controller = $containerBuilder->get(TableExportController::class);
        $controller();
    }

    /**
     * Merge two alias arrays, if array1 and array2 have
     * conflicting alias then array2 value is used if it
     * is non empty otherwise array1 value.
     *
     * @param array $aliases1 first array of aliases
     * @param array $aliases2 second array of aliases
     *
     * @return array resultant merged aliases info
     */
    public function mergeAliases(array $aliases1, array $aliases2): array
    {
        // First do a recursive array merge
        // on aliases arrays.
        $aliases = array_merge_recursive($aliases1, $aliases2);
        // Now, resolve conflicts in aliases, if any
        foreach ($aliases as $dbName => $db) {
            // If alias key is an array then
            // it is a merge conflict.
            if (isset($db['alias']) && is_array($db['alias'])) {
                $val1 = $db['alias'][0];
                $val2 = $db['alias'][1];
                // Use aliases2 alias if non empty
                $aliases[$dbName]['alias'] = empty($val2) ? $val1 : $val2;
            }

            if (! isset($db['tables'])) {
                continue;
            }

            foreach ($db['tables'] as $tableName => $tbl) {
                if (isset($tbl['alias']) && is_array($tbl['alias'])) {
                    $val1 = $tbl['alias'][0];
                    $val2 = $tbl['alias'][1];
                    // Use aliases2 alias if non empty
                    $aliases[$dbName]['tables'][$tableName]['alias'] = empty($val2) ? $val1 : $val2;
                }

                if (! isset($tbl['columns'])) {
                    continue;
                }

                foreach ($tbl['columns'] as $col => $colAs) {
                    if (! isset($colAs) || ! is_array($colAs)) {
                        continue;
                    }

                    $val1 = $colAs[0];
                    $val2 = $colAs[1];
                    // Use aliases2 alias if non empty
                    $aliases[$dbName]['tables'][$tableName]['columns'][$col] = empty($val2) ? $val1 : $val2;
                }
            }
        }

        return $aliases;
    }

    /**
     * Locks tables
     *
     * @param string $db       database name
     * @param array  $tables   list of table names
     * @param string $lockType lock type; "[LOW_PRIORITY] WRITE" or "READ [LOCAL]"
     *
     * @return mixed result of the query
     */
    public function lockTables(string $db, array $tables, string $lockType = 'WRITE')
    {
        $locks = [];
        foreach ($tables as $table) {
            $locks[] = Util::backquote($db) . '.'
                . Util::backquote($table) . ' ' . $lockType;
        }

        $sql = 'LOCK TABLES ' . implode(', ', $locks);

        return $this->dbi->tryQuery($sql);
    }

    /**
     * Releases table locks
     *
     * @return mixed result of the query
     */
    public function unlockTables()
    {
        return $this->dbi->tryQuery('UNLOCK TABLES');
    }

    /**
     * Returns all the metadata types that can be exported with a database or a table
     *
     * @return string[] metadata types.
     */
    public function getMetadataTypes(): array
    {
        return [
            'column_info',
            'table_uiprefs',
            'tracking',
            'bookmark',
            'relation',
            'table_coords',
            'pdf_pages',
            'savedsearches',
            'central_columns',
            'export_templates',
        ];
    }

    /**
     * Returns the checked clause, depending on the presence of key in array
     *
     * @param string $key   the key to look for
     * @param array  $array array to verify
     *
     * @return string the checked clause
     */
    public function getCheckedClause(string $key, array $array): string
    {
        if (in_array($key, $array)) {
            return ' checked="checked"';
        }

        return '';
    }

    /**
     * get all the export options and verify
     * call and include the appropriate Schema Class depending on $export_type
     *
     * @param string|null $exportType format of the export
     */
    public function processExportSchema(?string $exportType): void
    {
        /**
         * default is PDF, otherwise validate it's only letters a-z
         */
        if (! isset($exportType) || ! preg_match('/^[a-zA-Z]+$/', $exportType)) {
            $exportType = 'pdf';
        }

        // sanitize this parameter which will be used below in a file inclusion
        $exportType = Core::securePath($exportType);

        // get the specific plugin
        /** @var SchemaPlugin $exportPlugin */
        $exportPlugin = Plugins::getPlugin('schema', $exportType);

        // Check schema export type
        if ($exportPlugin === null) {
            Core::fatalError(__('Bad type!'));
        }

        $this->dbi->selectDb($_POST['db']);
        $exportPlugin->exportSchema($_POST['db']);
    }
}
