<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Import;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Console;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\File;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Import;
use PhpMyAdmin\Message;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;
use Throwable;

use function __;
use function _ngettext;
use function in_array;
use function ini_get;
use function ini_set;
use function intval;
use function is_array;
use function is_link;
use function is_numeric;
use function is_string;
use function is_uploaded_file;
use function mb_strlen;
use function mb_strtolower;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function strlen;
use function substr;
use function time;
use function trim;

final class ImportController extends AbstractController
{
    /** @var Import */
    private $import;

    /** @var Sql */
    private $sql;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Import $import,
        Sql $sql,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->import = $import;
        $this->sql = $sql;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $cfg, $collation_connection, $db, $import_type, $table, $goto, $display_query;
        global $format, $local_import_file, $ajax_reload, $import_text, $sql_query, $message, $errorUrl, $urlParams;
        global $memory_limit, $read_limit, $finished, $offset, $charset_conversion, $charset_of_file;
        global $timestamp, $maximum_time, $timeout_passed, $import_file, $go_sql, $sql_file, $error, $max_sql_len, $msg;
        global $sql_query_disabled, $executed_queries, $run_query, $reset_charset;
        global $result, $import_file_name, $sql_data, $import_notice, $read_multiply, $my_die, $active_page;
        global $show_as_php, $reload, $charset_connection, $is_js_confirmed, $MAX_FILE_SIZE, $message_to_show;
        global $noplugin, $skip_queries;

        $charset_of_file = $_POST['charset_of_file'] ?? null;
        $format = $_POST['format'] ?? '';
        $import_type = $_POST['import_type'] ?? null;
        $is_js_confirmed = $_POST['is_js_confirmed'] ?? null;
        $MAX_FILE_SIZE = $_POST['MAX_FILE_SIZE'] ?? null;
        $message_to_show = $_POST['message_to_show'] ?? null;
        $noplugin = $_POST['noplugin'] ?? null;
        $skip_queries = $_POST['skip_queries'] ?? null;
        $local_import_file = $_POST['local_import_file'] ?? null;
        $show_as_php = $_POST['show_as_php'] ?? null;

        // If it's a refresh console bookmarks request
        if (isset($_GET['console_bookmark_refresh'])) {
            $this->response->addJSON(
                'console_message_bookmark',
                Console::getBookmarkContent()
            );

            return;
        }

        // If it's a console bookmark add request
        if (isset($_POST['console_bookmark_add'])) {
            if (! isset($_POST['label'], $_POST['db'], $_POST['bookmark_query'], $_POST['shared'])) {
                $this->response->addJSON('message', __('Incomplete params'));

                return;
            }

            $bookmarkFields = [
                'bkm_database' => $_POST['db'],
                'bkm_user' => $cfg['Server']['user'],
                'bkm_sql_query' => $_POST['bookmark_query'],
                'bkm_label' => $_POST['label'],
            ];
            $isShared = ($_POST['shared'] === 'true');
            $bookmark = Bookmark::createBookmark($this->dbi, $bookmarkFields, $isShared);
            if ($bookmark !== false && $bookmark->save()) {
                $this->response->addJSON('message', __('Succeeded'));
                $this->response->addJSON('data', $bookmarkFields);
                $this->response->addJSON('isShared', $isShared);
            } else {
                $this->response->addJSON('message', __('Failed'));
            }

            return;
        }

        // reset import messages for ajax request
        $_SESSION['Import_message']['message'] = null;
        $_SESSION['Import_message']['go_back_url'] = null;
        // default values
        $reload = false;

        // Use to identify current cycle is executing
        // a multiquery statement or stored routine
        if (! isset($_SESSION['is_multi_query'])) {
            $_SESSION['is_multi_query'] = false;
        }

        $ajax_reload = [];
        $import_text = '';
        // Are we just executing plain query or sql file?
        // (eg. non import, but query box/window run)
        if (! empty($sql_query)) {
            // apply values for parameters
            if (! empty($_POST['parameterized']) && ! empty($_POST['parameters']) && is_array($_POST['parameters'])) {
                $parameters = $_POST['parameters'];
                foreach ($parameters as $parameter => $replacementValue) {
                    if (! is_numeric($replacementValue)) {
                        $replacementValue = '\'' . $this->dbi->escapeString($replacementValue) . '\'';
                    }

                    $quoted = preg_quote($parameter, '/');
                    // making sure that :param does not apply values to :param1
                    $sql_query = preg_replace(
                        '/' . $quoted . '([^a-zA-Z0-9_])/',
                        $replacementValue . '${1}',
                        $sql_query
                    );
                    // for parameters the appear at the end of the string
                    $sql_query = preg_replace('/' . $quoted . '$/', $replacementValue, $sql_query);
                }
            }

            // run SQL query
            $import_text = $sql_query;
            $import_type = 'query';
            $format = 'sql';
            $_SESSION['sql_from_query_box'] = true;

            // If there is a request to ROLLBACK when finished.
            if (isset($_POST['rollback_query'])) {
                $this->import->handleRollbackRequest($import_text);
            }

            // refresh navigation and main panels
            if (preg_match('/^(DROP)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', $sql_query)) {
                $reload = true;
                $ajax_reload['reload'] = true;
            }

            // refresh navigation panel only
            if (preg_match('/^(CREATE|ALTER)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', $sql_query)) {
                $ajax_reload['reload'] = true;
            }

            // do a dynamic reload if table is RENAMED
            // (by sending the instruction to the AJAX response handler)
            if (preg_match('/^RENAME\s+TABLE\s+(.*?)\s+TO\s+(.*?)($|;|\s)/i', $sql_query, $rename_table_names)) {
                $ajax_reload['reload'] = true;
                $ajax_reload['table_name'] = Util::unQuote($rename_table_names[2]);
            }

            $sql_query = '';
        } elseif (! empty($sql_file)) {
            // run uploaded SQL file
            $import_file = $sql_file;
            $import_type = 'queryfile';
            $format = 'sql';
            unset($sql_file);
        } elseif (! empty($_POST['id_bookmark'])) {
            // run bookmark
            $import_type = 'query';
            $format = 'sql';
        }

        // If we didn't get any parameters, either user called this directly, or
        // upload limit has been reached, let's assume the second possibility.
        if ($_POST == [] && $_GET == []) {
            $message = Message::error(
                __(
                    'You probably tried to upload a file that is too large. Please refer ' .
                    'to %sdocumentation%s for a workaround for this limit.'
                )
            );
            $message->addParam('[doc@faq1-16]');
            $message->addParam('[/doc]');

            // so we can obtain the message
            $_SESSION['Import_message']['message'] = $message->getDisplay();
            $_SESSION['Import_message']['go_back_url'] = $goto;

            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $message);

            return; // the footer is displayed automatically
        }

        // Add console message id to response output
        if (isset($_POST['console_message_id'])) {
            $this->response->addJSON('console_message_id', $_POST['console_message_id']);
        }

        /**
         * Sets globals from $_POST patterns, for import plugins
         * We only need to load the selected plugin
         */

        if (! in_array($format, ['csv', 'ldi', 'mediawiki', 'ods', 'shp', 'sql', 'xml'])) {
            // this should not happen for a normal user
            // but only during an attack
            Core::fatalError('Incorrect format parameter');
        }

        $post_patterns = [
            '/^force_file_/',
            '/^' . $format . '_/',
        ];

        Core::setPostAsGlobal($post_patterns);

        // Check needed parameters
        Util::checkParameters(['import_type', 'format']);

        // We don't want anything special in format
        $format = Core::securePath($format);

        if (strlen($table) > 0 && strlen($db) > 0) {
            $urlParams = [
                'db' => $db,
                'table' => $table,
            ];
        } elseif (strlen($db) > 0) {
            $urlParams = ['db' => $db];
        } else {
            $urlParams = [];
        }

        // Create error and goto url
        if ($import_type === 'table') {
            $goto = Url::getFromRoute('/table/import');
        } elseif ($import_type === 'database') {
            $goto = Url::getFromRoute('/database/import');
        } elseif ($import_type === 'server') {
            $goto = Url::getFromRoute('/server/import');
        } elseif (empty($goto) || ! preg_match('@^index\.php$@i', $goto)) {
            if (strlen($table) > 0 && strlen($db) > 0) {
                $goto = Url::getFromRoute('/table/structure');
            } elseif (strlen($db) > 0) {
                $goto = Url::getFromRoute('/database/structure');
            } else {
                $goto = Url::getFromRoute('/server/sql');
            }
        }

        $errorUrl = $goto . Url::getCommon($urlParams, '&');
        $_SESSION['Import_message']['go_back_url'] = $errorUrl;

        if (strlen($db) > 0) {
            $this->dbi->selectDb($db);
        }

        Util::setTimeLimit();
        if (! empty($cfg['MemoryLimit'])) {
            ini_set('memory_limit', $cfg['MemoryLimit']);
        }

        $timestamp = time();
        $maximum_time = 0;
        $maxExecutionTime = (int) ini_get('max_execution_time');
        if (isset($_POST['allow_interrupt']) && $maxExecutionTime >= 1) {
            $maximum_time = $maxExecutionTime - 1; // Give 1 second for phpMyAdmin to exit nicely
        }

        // set default values
        $timeout_passed = false;
        $error = false;
        $read_multiply = 1;
        $finished = false;
        $offset = 0;
        $max_sql_len = 0;
        $sql_query = '';
        $sql_query_disabled = false;
        $go_sql = false;
        $executed_queries = 0;
        $run_query = true;
        $charset_conversion = false;
        $reset_charset = false;
        $msg = 'Sorry an unexpected error happened!';

        /** @var bool|mixed $result */
        $result = false;

        // Bookmark Support: get a query back from bookmark if required
        if (! empty($_POST['id_bookmark'])) {
            $id_bookmark = (int) $_POST['id_bookmark'];
            switch ($_POST['action_bookmark']) {
                case 0: // bookmarked query that have to be run
                    $bookmark = Bookmark::get(
                        $this->dbi,
                        $cfg['Server']['user'],
                        $db,
                        $id_bookmark,
                        'id',
                        isset($_POST['action_bookmark_all'])
                    );
                    if (! $bookmark instanceof Bookmark) {
                        break;
                    }

                    if (! empty($_POST['bookmark_variable'])) {
                        $import_text = $bookmark->applyVariables($_POST['bookmark_variable']);
                    } else {
                        $import_text = $bookmark->getQuery();
                    }

                    // refresh navigation and main panels
                    if (preg_match('/^(DROP)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', $import_text)) {
                        $reload = true;
                        $ajax_reload['reload'] = true;
                    }

                    // refresh navigation panel only
                    if (preg_match('/^(CREATE|ALTER)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', $import_text)) {
                        $ajax_reload['reload'] = true;
                    }

                    break;
                case 1: // bookmarked query that have to be displayed
                    $bookmark = Bookmark::get($this->dbi, $cfg['Server']['user'], $db, $id_bookmark);
                    if (! $bookmark instanceof Bookmark) {
                        break;
                    }

                    $import_text = $bookmark->getQuery();
                    if ($this->response->isAjax()) {
                        $message = Message::success(__('Showing bookmark'));
                        $this->response->setRequestStatus($message->isSuccess());
                        $this->response->addJSON('message', $message);
                        $this->response->addJSON('sql_query', $import_text);
                        $this->response->addJSON('action_bookmark', $_POST['action_bookmark']);

                        return;
                    } else {
                        $run_query = false;
                    }

                    break;
                case 2: // bookmarked query that have to be deleted
                    $bookmark = Bookmark::get($this->dbi, $cfg['Server']['user'], $db, $id_bookmark);
                    if (! $bookmark instanceof Bookmark) {
                        break;
                    }

                    $bookmark->delete();
                    if ($this->response->isAjax()) {
                        $message = Message::success(
                            __('The bookmark has been deleted.')
                        );
                        $this->response->setRequestStatus($message->isSuccess());
                        $this->response->addJSON('message', $message);
                        $this->response->addJSON('action_bookmark', $_POST['action_bookmark']);
                        $this->response->addJSON('id_bookmark', $id_bookmark);

                        return;
                    } else {
                        $run_query = false;
                        $error = true; // this is kind of hack to skip processing the query
                    }

                    break;
            }
        }

        // Do no run query if we show PHP code
        if (isset($show_as_php)) {
            $run_query = false;
            $go_sql = true;
        }

        // We can not read all at once, otherwise we can run out of memory
        $memory_limit = trim((string) ini_get('memory_limit'));
        // 2 MB as default
        if (empty($memory_limit)) {
            $memory_limit = 2 * 1024 * 1024;
        }

        // In case no memory limit we work on 10MB chunks
        if ($memory_limit === '-1') {
            $memory_limit = 10 * 1024 * 1024;
        }

        // Calculate value of the limit
        $memoryUnit = mb_strtolower(substr((string) $memory_limit, -1));
        if ($memoryUnit === 'm') {
            $memory_limit = (int) substr((string) $memory_limit, 0, -1) * 1024 * 1024;
        } elseif ($memoryUnit === 'k') {
            $memory_limit = (int) substr((string) $memory_limit, 0, -1) * 1024;
        } elseif ($memoryUnit === 'g') {
            $memory_limit = (int) substr((string) $memory_limit, 0, -1) * 1024 * 1024 * 1024;
        } else {
            $memory_limit = (int) $memory_limit;
        }

        // Just to be sure, there might be lot of memory needed for uncompression
        $read_limit = $memory_limit / 8;

        // handle filenames
        if (
            isset($_FILES['import_file'])
            && is_array($_FILES['import_file'])
            && isset($_FILES['import_file']['name'], $_FILES['import_file']['tmp_name'])
            && is_string($_FILES['import_file']['name'])
            && is_string($_FILES['import_file']['tmp_name'])
        ) {
            $import_file = $_FILES['import_file']['tmp_name'];
            $import_file_name = $_FILES['import_file']['name'];
        }

        if (! empty($local_import_file) && ! empty($cfg['UploadDir'])) {
            // sanitize $local_import_file as it comes from a POST
            $local_import_file = Core::securePath($local_import_file);

            $import_file = Util::userDir((string) $cfg['UploadDir'])
                . $local_import_file;

            /*
             * Do not allow symlinks to avoid security issues
             * (user can create symlink to file they can not access,
             * but phpMyAdmin can).
             */
            if (@is_link($import_file)) {
                $import_file = 'none';
            }
        } elseif (empty($import_file) || ! is_uploaded_file($import_file)) {
            $import_file = 'none';
        }

        // Do we have file to import?

        if ($import_file !== 'none' && ! $error) {
            /**
             *  Handle file compression
             */
            $importHandle = new File($import_file);
            $importHandle->checkUploadedFile();
            if ($importHandle->isError()) {
                /** @var Message $errorMessage */
                $errorMessage = $importHandle->getError();

                $importHandle->close();

                $_SESSION['Import_message']['message'] = $errorMessage->getDisplay();

                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $errorMessage->getDisplay());
                $this->response->addHTML($errorMessage->getDisplay());

                return;
            }

            $importHandle->setDecompressContent(true);
            $importHandle->open();
            if ($importHandle->isError()) {
                /** @var Message $errorMessage */
                $errorMessage = $importHandle->getError();

                $importHandle->close();

                $_SESSION['Import_message']['message'] = $errorMessage->getDisplay();

                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $errorMessage->getDisplay());
                $this->response->addHTML($errorMessage->getDisplay());

                return;
            }
        } elseif (! $error && (! isset($import_text) || empty($import_text))) {
            $message = Message::error(
                __(
                    'No data was received to import. Either no file name was ' .
                    'submitted, or the file size exceeded the maximum size permitted ' .
                    'by your PHP configuration. See [doc@faq1-16]FAQ 1.16[/doc].'
                )
            );

            $_SESSION['Import_message']['message'] = $message->getDisplay();

            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $message->getDisplay());
            $this->response->addHTML($message->getDisplay());

            return;
        }

        // Convert the file's charset if necessary
        if (Encoding::isSupported() && isset($charset_of_file)) {
            if ($charset_of_file !== 'utf-8') {
                $charset_conversion = true;
            }
        } elseif (isset($charset_of_file) && $charset_of_file !== 'utf-8') {
            $this->dbi->query('SET NAMES \'' . $charset_of_file . '\'');
            // We can not show query in this case, it is in different charset
            $sql_query_disabled = true;
            $reset_charset = true;
        }

        // Something to skip? (because timeout has passed)
        if (! $error && isset($_POST['skip'])) {
            $original_skip = $skip = intval($_POST['skip']);
            while ($skip > 0 && ! $finished) {
                $this->import->getNextChunk($importHandle ?? null, $skip < $read_limit ? $skip : $read_limit);
                // Disable read progressivity, otherwise we eat all memory!
                $read_multiply = 1;
                $skip -= $read_limit;
            }

            unset($skip);
        }

        // This array contain the data like number of valid sql queries in the statement
        // and complete valid sql statement (which affected for rows)
        $sql_data = [
            'valid_sql' => [],
            'valid_queries' => 0,
        ];

        if (! $error) {
            /**
             * @var ImportPlugin $import_plugin
             */
            $import_plugin = Plugins::getPlugin('import', $format, $import_type);
            if ($import_plugin == null) {
                $message = Message::error(
                    __('Could not load import plugins, please check your installation!')
                );

                $_SESSION['Import_message']['message'] = $message->getDisplay();

                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message->getDisplay());
                $this->response->addHTML($message->getDisplay());

                return;
            }

            // Do the real import
            $default_fk_check = ForeignKey::handleDisableCheckInit();
            try {
                $import_plugin->doImport($importHandle ?? null, $sql_data);
                ForeignKey::handleDisableCheckCleanup($default_fk_check);
            } catch (Throwable $e) {
                ForeignKey::handleDisableCheckCleanup($default_fk_check);

                throw $e;
            }
        }

        if (isset($importHandle)) {
            $importHandle->close();
        }

        // Reset charset back, if we did some changes
        if ($reset_charset) {
            $this->dbi->query('SET CHARACTER SET ' . $charset_connection);
            $this->dbi->setCollation($collation_connection);
        }

        // Show correct message
        if (! empty($id_bookmark) && $_POST['action_bookmark'] == 2) {
            $message = Message::success(__('The bookmark has been deleted.'));
            $display_query = $import_text;
            $error = false; // unset error marker, it was used just to skip processing
        } elseif (! empty($id_bookmark) && $_POST['action_bookmark'] == 1) {
            $message = Message::notice(__('Showing bookmark'));
        } elseif ($finished && ! $error) {
            // Do not display the query with message, we do it separately
            $display_query = ';';
            if ($import_type !== 'query') {
                $message = Message::success(
                    '<em>'
                    . _ngettext(
                        'Import has been successfully finished, %d query executed.',
                        'Import has been successfully finished, %d queries executed.',
                        $executed_queries
                    )
                    . '</em>'
                );
                $message->addParam($executed_queries);

                if (! empty($import_notice)) {
                    $message->addHtml($import_notice);
                }

                if (! empty($local_import_file)) {
                    $message->addText('(' . $local_import_file . ')');
                } elseif (
                    isset($_FILES['import_file'])
                    && is_array($_FILES['import_file'])
                    && isset($_FILES['import_file']['name'])
                    && is_string($_FILES['import_file']['name'])
                ) {
                    $message->addText('(' . $_FILES['import_file']['name'] . ')');
                }
            }
        }

        // Did we hit timeout? Tell it user.
        if ($timeout_passed) {
            $urlParams['timeout_passed'] = '1';
            $urlParams['offset'] = $offset;
            if (isset($local_import_file)) {
                $urlParams['local_import_file'] = $local_import_file;
            }

            $importUrl = $errorUrl = $goto . Url::getCommon($urlParams, '&');

            $message = Message::error(
                __(
                    'Script timeout passed, if you want to finish import,'
                    . ' please %sresubmit the same file%s and import will resume.'
                )
            );
            $message->addParamHtml('<a href="' . $importUrl . '">');
            $message->addParamHtml('</a>');

            if ($offset == 0 || (isset($original_skip) && $original_skip == $offset)) {
                $message->addText(
                    __(
                        'However on last run no data has been parsed,'
                        . ' this usually means phpMyAdmin won\'t be able to'
                        . ' finish this import unless you increase php time limits.'
                    )
                );
            }
        }

        // if there is any message, copy it into $_SESSION as well,
        // so we can obtain it by AJAX call
        if (isset($message)) {
            $_SESSION['Import_message']['message'] = $message->getDisplay();
        }

        // Parse and analyze the query, for correct db and table name
        // in case of a query typed in the query window
        // (but if the query is too large, in case of an imported file, the parser
        //  can choke on it so avoid parsing)
        $sqlLength = mb_strlen($sql_query);
        if ($sqlLength <= $cfg['MaxCharactersInDisplayedSQL']) {
            [
                $analyzed_sql_results,
                $db,
                $table_from_sql,
            ] = ParseAnalyze::sqlQuery($sql_query, $db);

            $reload = $analyzed_sql_results['reload'];
            $offset = $analyzed_sql_results['offset'];

            if ($table != $table_from_sql && ! empty($table_from_sql)) {
                $table = $table_from_sql;
            }
        }

        // There was an error?
        if (isset($my_die)) {
            foreach ($my_die as $die) {
                Generator::mysqlDie($die['error'], $die['sql'], false, $errorUrl, $error);
            }
        }

        if ($go_sql) {
            if (! empty($sql_data) && ($sql_data['valid_queries'] > 1)) {
                $_SESSION['is_multi_query'] = true;
                $sql_queries = $sql_data['valid_sql'];
            } else {
                $sql_queries = [$sql_query];
            }

            $html_output = '';

            foreach ($sql_queries as $sql_query) {
                // parse sql query
                [
                    $analyzed_sql_results,
                    $db,
                    $table_from_sql,
                ] = ParseAnalyze::sqlQuery($sql_query, $db);

                $offset = $analyzed_sql_results['offset'];
                $reload = $analyzed_sql_results['reload'];

                // Check if User is allowed to issue a 'DROP DATABASE' Statement
                if (
                    $this->sql->hasNoRightsToDropDatabase(
                        $analyzed_sql_results,
                        $cfg['AllowUserDropDatabase'],
                        $this->dbi->isSuperUser()
                    )
                ) {
                    Generator::mysqlDie(
                        __('"DROP DATABASE" statements are disabled.'),
                        '',
                        false,
                        $_SESSION['Import_message']['go_back_url']
                    );

                    return;
                }

                if ($table != $table_from_sql && ! empty($table_from_sql)) {
                    $table = $table_from_sql;
                }

                $html_output .= $this->sql->executeQueryAndGetQueryResponse(
                    $analyzed_sql_results, // analyzed_sql_results
                    false, // is_gotofile
                    $db, // db
                    $table, // table
                    null, // find_real_end
                    null, // sql_query_for_bookmark - see below
                    null, // extra_data
                    null, // message_to_show
                    null, // sql_data
                    $goto, // goto
                    null, // disp_query
                    null, // disp_message
                    $sql_query, // sql_query
                    null // complete_query
                );
            }

            // sql_query_for_bookmark is not included in Sql::executeQueryAndGetQueryResponse
            // since only one bookmark has to be added for all the queries submitted through
            // the SQL tab
            if (! empty($_POST['bkm_label']) && ! empty($import_text)) {
                $relation = new Relation($this->dbi);

                $this->sql->storeTheQueryAsBookmark(
                    $relation->getRelationParameters()->bookmarkFeature,
                    $db,
                    $cfg['Server']['user'],
                    $_POST['sql_query'],
                    $_POST['bkm_label'],
                    isset($_POST['bkm_replace'])
                );
            }

            $this->response->addJSON('ajax_reload', $ajax_reload);
            $this->response->addHTML($html_output);

            return;
        }

        if ($result) {
            // Save a Bookmark with more than one queries (if Bookmark label given).
            if (! empty($_POST['bkm_label']) && ! empty($import_text)) {
                $relation = new Relation($this->dbi);

                $this->sql->storeTheQueryAsBookmark(
                    $relation->getRelationParameters()->bookmarkFeature,
                    $db,
                    $cfg['Server']['user'],
                    $_POST['sql_query'],
                    $_POST['bkm_label'],
                    isset($_POST['bkm_replace'])
                );
            }

            $this->response->setRequestStatus(true);
            $this->response->addJSON('message', Message::success($msg));
            $this->response->addJSON(
                'sql_query',
                Generator::getMessage($msg, $sql_query, 'success')
            );
        } elseif ($result === false) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error($msg));
        } else {
            $active_page = $goto;
            /** @psalm-suppress UnresolvableInclude */
            include ROOT_PATH . $goto;
        }

        // If there is request for ROLLBACK in the end.
        if (! isset($_POST['rollback_query'])) {
            return;
        }

        $this->dbi->query('ROLLBACK');
    }
}
