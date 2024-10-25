<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\Utils\Routine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function _ngettext;
use function array_merge;
use function count;
use function explode;
use function htmlentities;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function max;
use function mb_strtolower;
use function mb_strtoupper;
use function preg_match;
use function sprintf;
use function str_contains;
use function stripos;
use function substr;
use function trim;

use const ENT_QUOTES;

/**
 * Functions for routine management.
 */
class Routines
{
    /** @var array<int, string> */
    private $directions = ['IN', 'OUT', 'INOUT'];

    /** @var array<int, string> */
    private $sqlDataAccess = ['CONTAINS SQL', 'NO SQL', 'READS SQL DATA', 'MODIFIES SQL DATA'];

    /** @var array<int, string> */
    private $numericOptions = ['UNSIGNED', 'ZEROFILL', 'UNSIGNED ZEROFILL'];

    /** @var DatabaseInterface */
    private $dbi;

    /** @var Template */
    private $template;

    /** @var ResponseRenderer */
    private $response;

    /**
     * @param DatabaseInterface $dbi      DatabaseInterface instance.
     * @param Template          $template Template instance.
     * @param ResponseRenderer  $response Response instance.
     */
    public function __construct(DatabaseInterface $dbi, Template $template, $response)
    {
        $this->dbi = $dbi;
        $this->template = $template;
        $this->response = $response;
    }

    /**
     * Handles editor requests for adding or editing an item
     */
    public function handleEditor(): void
    {
        global $db, $errors;

        $errors = $this->handleRequestCreateOrEdit($errors, $db);

        /**
         * Display a form used to add/edit a routine, if necessary
         */
        // FIXME: this must be simpler than that
        if (
            ! count($errors)
            && ( ! empty($_POST['editor_process_add'])
            || ! empty($_POST['editor_process_edit'])
            || (empty($_REQUEST['add_item']) && empty($_REQUEST['edit_item'])
            && empty($_POST['routine_addparameter'])
            && empty($_POST['routine_removeparameter'])
            && empty($_POST['routine_changetype'])))
        ) {
            return;
        }

        // Handle requests to add/remove parameters and changing routine type
        // This is necessary when JS is disabled
        $operation = '';
        if (! empty($_POST['routine_addparameter'])) {
            $operation = 'add';
        } elseif (! empty($_POST['routine_removeparameter'])) {
            $operation = 'remove';
        } elseif (! empty($_POST['routine_changetype'])) {
            $operation = 'change';
        }

        // Get the data for the form (if any)
        $routine = null;
        $mode = null;
        $title = null;
        if (! empty($_REQUEST['add_item'])) {
            $title = __('Add routine');
            $routine = $this->getDataFromRequest();
            $mode = 'add';
        } elseif (! empty($_REQUEST['edit_item'])) {
            $title = __('Edit routine');
            if (! $operation && ! empty($_GET['item_name']) && empty($_POST['editor_process_edit'])) {
                $routine = $this->getDataFromName($_GET['item_name'], $_GET['item_type']);
                if ($routine !== null) {
                    $routine['item_original_name'] = $routine['item_name'];
                    $routine['item_original_type'] = $routine['item_type'];
                }
            } else {
                $routine = $this->getDataFromRequest();
            }

            $mode = 'edit';
        }

        if ($routine !== null) {
            // Show form
            $editor = $this->getEditorForm($mode, $operation, $routine);
            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $editor);
                $this->response->addJSON('title', $title);
                $this->response->addJSON('paramTemplate', $this->getParameterRow());
                $this->response->addJSON('type', $routine['item_type']);
            } else {
                echo "\n\n<h2>" . $title . "</h2>\n\n" . $editor;
            }

            exit;
        }

        $message = __('Error in processing request:') . ' ';
        $message .= sprintf(
            __(
                'No routine with name %1$s found in database %2$s. '
                . 'You might be lacking the necessary privileges to edit this routine.'
            ),
            htmlspecialchars(
                Util::backquote($_REQUEST['item_name'])
            ),
            htmlspecialchars(Util::backquote($db))
        );

        $message = Message::error($message);
        if ($this->response->isAjax()) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $message);
            exit;
        }

        echo $message->getDisplay();
    }

    /**
     * Handle request to create or edit a routine
     *
     * @param array  $errors Errors
     * @param string $db     DB name
     *
     * @return array
     */
    public function handleRequestCreateOrEdit(array $errors, $db)
    {
        global $message;

        if (empty($_POST['editor_process_add']) && empty($_POST['editor_process_edit'])) {
            return $errors;
        }

        $sql_query = '';
        $routine_query = $this->getQueryFromRequest();

        // set by getQueryFromRequest()
        if (! count($errors)) {
            // Execute the created query
            if (! empty($_POST['editor_process_edit'])) {
                $isProcOrFunc = in_array(
                    $_POST['item_original_type'],
                    [
                        'PROCEDURE',
                        'FUNCTION',
                    ]
                );

                if (! $isProcOrFunc) {
                    $errors[] = sprintf(
                        __('Invalid routine type: "%s"'),
                        htmlspecialchars($_POST['item_original_type'])
                    );
                } else {
                    // Backup the old routine, in case something goes wrong
                    $create_routine = $this->dbi->getDefinition(
                        $db,
                        $_POST['item_original_type'],
                        $_POST['item_original_name']
                    );

                    $privilegesBackup = $this->backupPrivileges();

                    $drop_routine = 'DROP ' . $_POST['item_original_type'] . ' '
                        . Util::backquote($_POST['item_original_name'])
                        . ";\n";
                    $result = $this->dbi->tryQuery($drop_routine);
                    if (! $result) {
                        $errors[] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($drop_routine)
                        )
                        . '<br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                    } else {
                        [$newErrors, $message] = $this->create($routine_query, $create_routine, $privilegesBackup);
                        if (empty($newErrors)) {
                            $sql_query = $drop_routine . $routine_query;
                        } else {
                            $errors = array_merge($errors, $newErrors);
                        }

                        unset($newErrors);
                    }
                }
            } else {
                // 'Add a new routine' mode
                $result = $this->dbi->tryQuery($routine_query);
                if (! $result) {
                    $errors[] = sprintf(
                        __('The following query has failed: "%s"'),
                        htmlspecialchars($routine_query)
                    )
                    . '<br><br>'
                    . __('MySQL said: ') . $this->dbi->getError();
                } else {
                    $message = Message::success(
                        __('Routine %1$s has been created.')
                    );
                    $message->addParam(
                        Util::backquote($_POST['item_name'])
                    );
                    $sql_query = $routine_query;
                }
            }
        }

        if (count($errors)) {
            $message = Message::error(
                __(
                    'One or more errors have occurred while processing your request:'
                )
            );
            $message->addHtml('<ul>');
            foreach ($errors as $string) {
                $message->addHtml('<li>' . $string . '</li>');
            }

            $message->addHtml('</ul>');
        }

        $output = Generator::getMessage($message, $sql_query);

        if (! $this->response->isAjax()) {
            return $errors;
        }

        if (! $message->isSuccess()) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $output);
            exit;
        }

        $routines = $this->dbi->getRoutines($db, $_POST['item_type'], $_POST['item_name']);
        $routine = $routines[0];
        $this->response->addJSON(
            'name',
            htmlspecialchars(
                mb_strtoupper($_POST['item_name'])
            )
        );
        $this->response->addJSON('new_row', $this->getRow($routine));
        $this->response->addJSON('insert', ! empty($routine));
        $this->response->addJSON('message', $output);
        $this->response->addJSON('tableType', 'routines');
        exit;
    }

    /**
     * Backup the privileges
     *
     * @return array
     */
    public function backupPrivileges()
    {
        if (! $GLOBALS['proc_priv'] || ! $GLOBALS['is_reload_priv']) {
            return [];
        }

        // Backup the Old Privileges before dropping
        // if $_POST['item_adjust_privileges'] set
        if (! isset($_POST['item_adjust_privileges']) || empty($_POST['item_adjust_privileges'])) {
            return [];
        }

        $privilegesBackupQuery = 'SELECT * FROM ' . Util::backquote('mysql')
        . '.' . Util::backquote('procs_priv')
        . ' where Routine_name = "' . $_POST['item_original_name']
        . '" AND Routine_type = "' . $_POST['item_original_type']
        . '";';

        return $this->dbi->fetchResult($privilegesBackupQuery, 0);
    }

    /**
     * Create the routine
     *
     * @param string $routine_query    Query to create routine
     * @param string $create_routine   Query to restore routine
     * @param array  $privilegesBackup Privileges backup
     *
     * @return array
     */
    public function create(
        $routine_query,
        $create_routine,
        array $privilegesBackup
    ) {
        $result = $this->dbi->tryQuery($routine_query);
        if (! $result) {
            $errors = [];
            $errors[] = sprintf(
                __('The following query has failed: "%s"'),
                htmlspecialchars($routine_query)
            )
            . '<br>'
            . __('MySQL said: ') . $this->dbi->getError();
            // We dropped the old routine,
            // but were unable to create the new one
            // Try to restore the backup query
            $result = $this->dbi->tryQuery($create_routine);
            if (! $result) {
                $errors = $this->checkResult($create_routine, $errors);
            }

            return [
                $errors,
                null,
            ];
        }

        // Default value
        $resultAdjust = false;

        if ($GLOBALS['proc_priv'] && $GLOBALS['is_reload_priv']) {
            // Insert all the previous privileges
            // but with the new name and the new type
            foreach ($privilegesBackup as $priv) {
                $adjustProcPrivilege = 'INSERT INTO '
                    . Util::backquote('mysql') . '.'
                    . Util::backquote('procs_priv')
                    . ' VALUES("' . $priv[0] . '", "'
                    . $priv[1] . '", "' . $priv[2] . '", "'
                    . $_POST['item_name'] . '", "'
                    . $_POST['item_type'] . '", "'
                    . $priv[5] . '", "'
                    . $priv[6] . '", "'
                    . $priv[7] . '");';
                $this->dbi->query($adjustProcPrivilege);
                $resultAdjust = true;
            }
        }

        $message = $this->flushPrivileges($resultAdjust);

        return [
            [],
            $message,
        ];
    }

    /**
     * Flush privileges and get message
     *
     * @param bool $flushPrivileges Flush privileges
     *
     * @return Message
     */
    public function flushPrivileges($flushPrivileges)
    {
        if ($flushPrivileges) {
            // Flush the Privileges
            $flushPrivQuery = 'FLUSH PRIVILEGES;';
            $this->dbi->query($flushPrivQuery);

            $message = Message::success(
                __(
                    'Routine %1$s has been modified. Privileges have been adjusted.'
                )
            );
        } else {
            $message = Message::success(
                __('Routine %1$s has been modified.')
            );
        }

        $message->addParam(
            Util::backquote($_POST['item_name'])
        );

        return $message;
    }

    /**
     * This function will generate the values that are required to
     * complete the editor form. It is especially necessary to handle
     * the 'Add another parameter', 'Remove last parameter' and
     * 'Change routine type' functionalities when JS is disabled.
     *
     * @return array    Data necessary to create the routine editor.
     */
    public function getDataFromRequest()
    {
        $retval = [];
        $indices = [
            'item_name',
            'item_original_name',
            'item_returnlength',
            'item_returnopts_num',
            'item_returnopts_text',
            'item_definition',
            'item_comment',
            'item_definer',
        ];
        foreach ($indices as $index) {
            $retval[$index] = $_POST[$index] ?? '';
        }

        $retval['item_type'] = 'PROCEDURE';
        $retval['item_type_toggle'] = 'FUNCTION';
        if (isset($_POST['item_type']) && $_POST['item_type'] === 'FUNCTION') {
            $retval['item_type'] = 'FUNCTION';
            $retval['item_type_toggle'] = 'PROCEDURE';
        }

        $retval['item_original_type'] = 'PROCEDURE';
        if (isset($_POST['item_original_type']) && $_POST['item_original_type'] === 'FUNCTION') {
            $retval['item_original_type'] = 'FUNCTION';
        }

        $retval['item_num_params'] = 0;
        $retval['item_param_dir'] = [];
        $retval['item_param_name'] = [];
        $retval['item_param_type'] = [];
        $retval['item_param_length'] = [];
        $retval['item_param_opts_num'] = [];
        $retval['item_param_opts_text'] = [];
        if (
            isset($_POST['item_param_name'], $_POST['item_param_type'])
            && isset($_POST['item_param_length'])
            && isset($_POST['item_param_opts_num'])
            && isset($_POST['item_param_opts_text'])
            && is_array($_POST['item_param_name'])
            && is_array($_POST['item_param_type'])
            && is_array($_POST['item_param_length'])
            && is_array($_POST['item_param_opts_num'])
            && is_array($_POST['item_param_opts_text'])
        ) {
            if ($_POST['item_type'] === 'PROCEDURE') {
                $retval['item_param_dir'] = $_POST['item_param_dir'];
                foreach ($retval['item_param_dir'] as $key => $value) {
                    if (in_array($value, $this->directions, true)) {
                        continue;
                    }

                    $retval['item_param_dir'][$key] = '';
                }
            }

            $retval['item_param_name'] = $_POST['item_param_name'];
            $retval['item_param_type'] = $_POST['item_param_type'];
            foreach ($retval['item_param_type'] as $key => $value) {
                if (in_array($value, Util::getSupportedDatatypes(), true)) {
                    continue;
                }

                $retval['item_param_type'][$key] = '';
            }

            $retval['item_param_length'] = $_POST['item_param_length'];
            $retval['item_param_opts_num'] = $_POST['item_param_opts_num'];
            $retval['item_param_opts_text'] = $_POST['item_param_opts_text'];
            $retval['item_num_params'] = max(
                count($retval['item_param_name']),
                count($retval['item_param_type']),
                count($retval['item_param_length']),
                count($retval['item_param_opts_num']),
                count($retval['item_param_opts_text'])
            );
        }

        $retval['item_returntype'] = '';
        if (isset($_POST['item_returntype']) && in_array($_POST['item_returntype'], Util::getSupportedDatatypes())) {
            $retval['item_returntype'] = $_POST['item_returntype'];
        }

        $retval['item_isdeterministic'] = '';
        if (isset($_POST['item_isdeterministic']) && mb_strtolower($_POST['item_isdeterministic']) === 'on') {
            $retval['item_isdeterministic'] = " checked='checked'";
        }

        $retval['item_securitytype_definer'] = '';
        $retval['item_securitytype_invoker'] = '';
        if (isset($_POST['item_securitytype'])) {
            if ($_POST['item_securitytype'] === 'DEFINER') {
                $retval['item_securitytype_definer'] = " selected='selected'";
            } elseif ($_POST['item_securitytype'] === 'INVOKER') {
                $retval['item_securitytype_invoker'] = " selected='selected'";
            }
        }

        $retval['item_sqldataaccess'] = '';
        if (isset($_POST['item_sqldataaccess']) && in_array($_POST['item_sqldataaccess'], $this->sqlDataAccess, true)) {
            $retval['item_sqldataaccess'] = $_POST['item_sqldataaccess'];
        }

        return $retval;
    }

    /**
     * This function will generate the values that are required to complete
     * the "Edit routine" form given the name of a routine.
     *
     * @param string $name The name of the routine.
     * @param string $type Type of routine (ROUTINE|PROCEDURE)
     * @param bool   $all  Whether to return all data or just the info about parameters.
     *
     * @return array|null    Data necessary to create the routine editor.
     */
    public function getDataFromName($name, $type, $all = true): ?array
    {
        global $db;

        $retval = [];

        // Build and execute the query
        $fields = 'SPECIFIC_NAME, ROUTINE_TYPE, DTD_IDENTIFIER, '
                 . 'ROUTINE_DEFINITION, IS_DETERMINISTIC, SQL_DATA_ACCESS, '
                 . 'ROUTINE_COMMENT, SECURITY_TYPE';
        $where = 'ROUTINE_SCHEMA ' . Util::getCollateForIS() . '='
                 . "'" . $this->dbi->escapeString($db) . "' "
                 . "AND SPECIFIC_NAME='" . $this->dbi->escapeString($name) . "'"
                 . "AND ROUTINE_TYPE='" . $this->dbi->escapeString($type) . "'";
        $query = 'SELECT ' . $fields . ' FROM INFORMATION_SCHEMA.ROUTINES WHERE ' . $where . ';';

        $routine = $this->dbi->fetchSingleRow($query);

        if (! $routine) {
            return null;
        }

        // Get required data
        $retval['item_name'] = $routine['SPECIFIC_NAME'];
        $retval['item_type'] = $routine['ROUTINE_TYPE'];

        $definition = $this->dbi->getDefinition($db, $routine['ROUTINE_TYPE'], $routine['SPECIFIC_NAME']);

        if ($definition === null) {
            return null;
        }

        $parser = new Parser($definition);

        /**
         * @var CreateStatement $stmt
         */
        $stmt = $parser->statements[0];

        // Do not use $routine['ROUTINE_DEFINITION'] because of a MySQL escaping issue: #15370
        $body = TokensList::build($stmt->body);
        if (empty($body)) {
            // Fallback just in case the parser fails
            $body = (string) $routine['ROUTINE_DEFINITION'];
        }

        $params = Routine::getParameters($stmt);
        $retval['item_num_params'] = $params['num'];
        $retval['item_param_dir'] = $params['dir'];
        $retval['item_param_name'] = $params['name'];
        $retval['item_param_type'] = $params['type'];
        $retval['item_param_length'] = $params['length'];
        $retval['item_param_length_arr'] = $params['length_arr'];
        $retval['item_param_opts_num'] = $params['opts'];
        $retval['item_param_opts_text'] = $params['opts'];

        // Get extra data
        if (! $all) {
            return $retval;
        }

        if ($retval['item_type'] === 'FUNCTION') {
            $retval['item_type_toggle'] = 'PROCEDURE';
        } else {
            $retval['item_type_toggle'] = 'FUNCTION';
        }

        $retval['item_returntype'] = '';
        $retval['item_returnlength'] = '';
        $retval['item_returnopts_num'] = '';
        $retval['item_returnopts_text'] = '';

        if (! empty($routine['DTD_IDENTIFIER'])) {
            $options = [];
            foreach ($stmt->return->options->options as $opt) {
                $options[] = is_string($opt) ? $opt : $opt['value'];
            }

            $retval['item_returntype'] = $stmt->return->name;
            $retval['item_returnlength'] = implode(',', $stmt->return->parameters);
            $retval['item_returnopts_num'] = implode(' ', $options);
            $retval['item_returnopts_text'] = implode(' ', $options);
        }

        $retval['item_definer'] = $stmt->options->has('DEFINER');
        $retval['item_definition'] = $body;
        $retval['item_isdeterministic'] = '';
        if ($routine['IS_DETERMINISTIC'] === 'YES') {
            $retval['item_isdeterministic'] = " checked='checked'";
        }

        $retval['item_securitytype_definer'] = '';
        $retval['item_securitytype_invoker'] = '';
        if ($routine['SECURITY_TYPE'] === 'DEFINER') {
            $retval['item_securitytype_definer'] = " selected='selected'";
        } elseif ($routine['SECURITY_TYPE'] === 'INVOKER') {
            $retval['item_securitytype_invoker'] = " selected='selected'";
        }

        $retval['item_sqldataaccess'] = $routine['SQL_DATA_ACCESS'];
        $retval['item_comment'] = $routine['ROUTINE_COMMENT'];

        return $retval;
    }

    /**
     * Creates one row for the parameter table used in the routine editor.
     *
     * @param array  $routine Data for the routine returned by
     *                        getDataFromRequest() or getDataFromName()
     * @param mixed  $index   Either a numeric index of the row being processed
     *                        or NULL to create a template row for AJAX request
     * @param string $class   Class used to hide the direction column, if the
     *                        row is for a stored function.
     *
     * @return string    HTML code of one row of parameter table for the editor.
     */
    public function getParameterRow(array $routine = [], $index = null, $class = '')
    {
        if ($index === null) {
            // template row for AJAX request
            $i = 0;
            $index = '%s';
            $drop_class = '';
            $routine = [
                'item_param_dir' => [0 => ''],
                'item_param_name' => [0 => ''],
                'item_param_type' => [0 => ''],
                'item_param_length' => [0 => ''],
                'item_param_opts_num' => [0 => ''],
                'item_param_opts_text' => [0 => ''],
            ];
        } elseif (! empty($routine)) {
            // regular row for routine editor
            $drop_class = ' hide';
            $i = $index;
        } else {
            // No input data. This shouldn't happen,
            // but better be safe than sorry.
            return '';
        }

        $allCharsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
        $charsets = [];
        foreach ($allCharsets as $charset) {
            $charsets[] = [
                'name' => $charset->getName(),
                'description' => $charset->getDescription(),
                'is_selected' => $charset->getName() === $routine['item_param_opts_text'][$i],
            ];
        }

        return $this->template->render('database/routines/parameter_row', [
            'class' => $class,
            'index' => $index,
            'param_directions' => $this->directions,
            'param_opts_num' => $this->numericOptions,
            'item_param_dir' => $routine['item_param_dir'][$i] ?? '',
            'item_param_name' => $routine['item_param_name'][$i] ?? '',
            'item_param_length' => $routine['item_param_length'][$i] ?? '',
            'item_param_opts_num' => $routine['item_param_opts_num'][$i] ?? '',
            'supported_datatypes' => Util::getSupportedDatatypes(
                true,
                $routine['item_param_type'][$i]
            ),
            'charsets' => $charsets,
            'drop_class' => $drop_class,
        ]);
    }

    /**
     * Displays a form used to add/edit a routine
     *
     * @param string $mode      If the editor will be used to edit a routine
     *                          or add a new one: 'edit' or 'add'.
     * @param string $operation If the editor was previously invoked with
     *                          JS turned off, this will hold the name of
     *                          the current operation
     * @param array  $routine   Data for the routine returned by
     *                          getDataFromRequest() or getDataFromName()
     *
     * @return string   HTML code for the editor.
     */
    public function getEditorForm($mode, $operation, array $routine)
    {
        global $db, $errors;

        for ($i = 0; $i < $routine['item_num_params']; $i++) {
            $routine['item_param_name'][$i] = htmlentities($routine['item_param_name'][$i], ENT_QUOTES);
            $routine['item_param_length'][$i] = htmlentities($routine['item_param_length'][$i], ENT_QUOTES);
        }

        // Handle some logic first
        if ($operation === 'change') {
            if ($routine['item_type'] === 'PROCEDURE') {
                $routine['item_type'] = 'FUNCTION';
                $routine['item_type_toggle'] = 'PROCEDURE';
            } else {
                $routine['item_type'] = 'PROCEDURE';
                $routine['item_type_toggle'] = 'FUNCTION';
            }
        } elseif ($operation === 'add' || ($routine['item_num_params'] == 0 && $mode === 'add' && ! $errors)) {
            $routine['item_param_dir'][] = '';
            $routine['item_param_name'][] = '';
            $routine['item_param_type'][] = '';
            $routine['item_param_length'][] = '';
            $routine['item_param_opts_num'][] = '';
            $routine['item_param_opts_text'][] = '';
            $routine['item_num_params']++;
        } elseif ($operation === 'remove') {
            unset(
                $routine['item_param_dir'][$routine['item_num_params'] - 1],
                $routine['item_param_name'][$routine['item_num_params'] - 1],
                $routine['item_param_type'][$routine['item_num_params'] - 1],
                $routine['item_param_length'][$routine['item_num_params'] - 1],
                $routine['item_param_opts_num'][$routine['item_num_params'] - 1],
                $routine['item_param_opts_text'][$routine['item_num_params'] - 1]
            );
            $routine['item_num_params']--;
        }

        $parameterRows = '';
        for ($i = 0; $i < $routine['item_num_params']; $i++) {
            $parameterRows .= $this->getParameterRow($routine, $i, $routine['item_type'] === 'FUNCTION' ? ' hide' : '');
        }

        $charsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);

        return $this->template->render('database/routines/editor_form', [
            'db' => $db,
            'routine' => $routine,
            'is_edit_mode' => $mode === 'edit',
            'is_ajax' => $this->response->isAjax(),
            'parameter_rows' => $parameterRows,
            'charsets' => $charsets,
            'numeric_options' => $this->numericOptions,
            'has_privileges' => $GLOBALS['proc_priv'] && $GLOBALS['is_reload_priv'],
            'sql_data_access' => $this->sqlDataAccess,
        ]);
    }

    /**
     * Set the found errors and build the params
     *
     * @param string[] $itemParamName     The parameter names
     * @param string[] $itemParamDir      The direction parameter (see $this->directions)
     * @param array    $itemParamType     The parameter type
     * @param array    $itemParamLength   A length or not for the parameter
     * @param array    $itemParamOpsText  An optional charset for the parameter
     * @param array    $itemParamOpsNum   An optional parameter for a $itemParamType NUMBER
     * @param string   $itemType          The item type (PROCEDURE/FUNCTION)
     * @param bool     $warnedAboutLength A boolean that will be switched if a the length warning is given
     */
    private function processParamsAndBuild(
        array $itemParamName,
        array $itemParamDir,
        array $itemParamType,
        array $itemParamLength,
        array $itemParamOpsText,
        array $itemParamOpsNum,
        string $itemType,
        bool &$warnedAboutLength
    ): string {
        global $errors, $dbi;

        $params = '';
        $warnedAboutDir = false;

        for ($i = 0, $nb = count($itemParamName); $i < $nb; $i++) {
            if (empty($itemParamName[$i]) || empty($itemParamType[$i])) {
                $errors[] = __('You must provide a name and a type for each routine parameter.');
                break;
            }

            if (
                $itemType === 'PROCEDURE'
                && ! empty($itemParamDir[$i])
                && in_array($itemParamDir[$i], $this->directions)
            ) {
                $params .= $itemParamDir[$i] . ' '
                    . Util::backquote($itemParamName[$i])
                    . ' ' . $itemParamType[$i];
            } elseif ($itemType === 'FUNCTION') {
                $params .= Util::backquote($itemParamName[$i])
                    . ' ' . $itemParamType[$i];
            } elseif (! $warnedAboutDir) {
                $warnedAboutDir = true;
                $errors[] = sprintf(
                    __('Invalid direction "%s" given for parameter.'),
                    htmlspecialchars($itemParamDir[$i])
                );
            }

            if (
                $itemParamLength[$i] != ''
                && ! preg_match(
                    '@^(DATE|TINYBLOB|TINYTEXT|BLOB|TEXT|MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|SERIAL|BOOLEAN)$@i',
                    $itemParamType[$i]
                )
            ) {
                $params .= '(' . $itemParamLength[$i] . ')';
            } elseif (
                $itemParamLength[$i] == ''
                && preg_match('@^(ENUM|SET|VARCHAR|VARBINARY)$@i', $itemParamType[$i])
            ) {
                if (! $warnedAboutLength) {
                    $warnedAboutLength = true;
                    $errors[] = __(
                        'You must provide length/values for routine parameters'
                        . ' of type ENUM, SET, VARCHAR and VARBINARY.'
                    );
                }
            }

            if (! empty($itemParamOpsText[$i])) {
                if ($dbi->types->getTypeClass($itemParamType[$i]) === 'CHAR') {
                    if (! in_array($itemParamType[$i], ['VARBINARY', 'BINARY'])) {
                        $params .= ' CHARSET '
                            . mb_strtolower($itemParamOpsText[$i]);
                    }
                }
            }

            if (! empty($itemParamOpsNum[$i])) {
                if ($dbi->types->getTypeClass($itemParamType[$i]) === 'NUMBER') {
                    $params .= ' '
                        . mb_strtoupper($itemParamOpsNum[$i]);
                }
            }

            if ($i == count($itemParamName) - 1) {
                continue;
            }

            $params .= ', ';
        }

        return $params;
    }

    /**
     * Set the found errors and build the query
     *
     * @param string $query             The existing query
     * @param bool   $warnedAboutLength If the length warning was given
     */
    private function processFunctionSpecificParameters(
        string $query,
        bool $warnedAboutLength
    ): string {
        global $errors, $dbi;

        $itemReturnType = $_POST['item_returntype'] ?? null;

        if (! empty($itemReturnType) && in_array($itemReturnType, Util::getSupportedDatatypes())) {
            $query .= 'RETURNS ' . $itemReturnType;
        } else {
            $errors[] = __('You must provide a valid return type for the routine.');
        }

        if (
            ! empty($_POST['item_returnlength'])
            && ! preg_match(
                '@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|'
                . 'MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|SERIAL|BOOLEAN)$@i',
                $itemReturnType
            )
        ) {
            $query .= '(' . $_POST['item_returnlength'] . ')';
        } elseif (
            empty($_POST['item_returnlength'])
            && preg_match('@^(ENUM|SET|VARCHAR|VARBINARY)$@i', $itemReturnType)
        ) {
            if (! $warnedAboutLength) {
                $errors[] = __(
                    'You must provide length/values for routine parameters of type ENUM, SET, VARCHAR and VARBINARY.'
                );
            }
        }

        if (! empty($_POST['item_returnopts_text'])) {
            if ($dbi->types->getTypeClass($itemReturnType) === 'CHAR') {
                $query .= ' CHARSET '
                    . mb_strtolower($_POST['item_returnopts_text']);
            }
        }

        if (! empty($_POST['item_returnopts_num'])) {
            if ($dbi->types->getTypeClass($itemReturnType) === 'NUMBER') {
                $query .= ' '
                    . mb_strtoupper($_POST['item_returnopts_num']);
            }
        }

        return $query . ' ';
    }

    /**
     * Composes the query necessary to create a routine from an HTTP request.
     *
     * @return string  The CREATE [ROUTINE | PROCEDURE] query.
     */
    public function getQueryFromRequest(): string
    {
        global $errors;

        $itemType = $_POST['item_type'] ?? '';
        $itemDefiner = $_POST['item_definer'] ?? '';
        $itemName = $_POST['item_name'] ?? '';

        $query = 'CREATE ';
        if (! empty($itemDefiner)) {
            if (str_contains($itemDefiner, '@')) {
                $arr = explode('@', $itemDefiner);

                $do_backquote = true;
                if (substr($arr[0], 0, 1) === '`' && substr($arr[0], -1) === '`') {
                    $do_backquote = false;
                }

                $query .= 'DEFINER=' . Util::backquoteCompat($arr[0], 'NONE', $do_backquote);

                $do_backquote = true;
                if (substr($arr[1], 0, 1) === '`' && substr($arr[1], -1) === '`') {
                    $do_backquote = false;
                }

                $query .= '@' . Util::backquoteCompat($arr[1], 'NONE', $do_backquote) . ' ';
            } else {
                $errors[] = __('The definer must be in the "username@hostname" format!');
            }
        }

        if ($itemType === 'FUNCTION' || $itemType === 'PROCEDURE') {
            $query .= $itemType . ' ';
        } else {
            $errors[] = sprintf(
                __('Invalid routine type: "%s"'),
                htmlspecialchars($itemType)
            );
        }

        if (! empty($itemName)) {
            $query .= Util::backquote($itemName);
        } else {
            $errors[] = __('You must provide a routine name!');
        }

        $warnedAboutLength = false;

        $itemParamName = $_POST['item_param_name'] ?? '';
        $itemParamType = $_POST['item_param_type'] ?? '';
        $itemParamLength = $_POST['item_param_length'] ?? '';
        $itemParamDir = (array) ($_POST['item_param_dir'] ?? []);
        $itemParamOpsText = (array) ($_POST['item_param_opts_text'] ?? []);
        $itemParamOpsNum = (array) ($_POST['item_param_opts_num'] ?? []);

        $params = '';
        if (
            ! empty($itemParamName)
            && ! empty($itemParamType)
            && ! empty($itemParamLength)
            && is_array($itemParamName)
            && is_array($itemParamType)
            && is_array($itemParamLength)
        ) {
            $params = $this->processParamsAndBuild(
                $itemParamName,
                $itemParamDir,
                $itemParamType,
                $itemParamLength,
                $itemParamOpsText,
                $itemParamOpsNum,
                $itemType,
                $warnedAboutLength// Will possibly be modified by the function
            );
        }

        $query .= '(' . $params . ') ';
        if ($itemType === 'FUNCTION') {
            $query = $this->processFunctionSpecificParameters($query, $warnedAboutLength);
        }

        if (! empty($_POST['item_comment'])) {
            $query .= "COMMENT '" . $this->dbi->escapeString($_POST['item_comment'])
                . "' ";
        }

        if (isset($_POST['item_isdeterministic'])) {
            $query .= 'DETERMINISTIC ';
        } else {
            $query .= 'NOT DETERMINISTIC ';
        }

        $itemSqlDataAccess = $_POST['item_sqldataaccess'] ?? '';
        if (! empty($itemSqlDataAccess) && in_array($itemSqlDataAccess, $this->sqlDataAccess)) {
            $query .= $itemSqlDataAccess . ' ';
        }

        $itemSecurityType = $_POST['item_securitytype'] ?? '';
        if (! empty($itemSecurityType)) {
            if ($itemSecurityType === 'DEFINER' || $itemSecurityType === 'INVOKER') {
                $query .= 'SQL SECURITY ' . $itemSecurityType . ' ';
            }
        }

        $itemDefinition = $_POST['item_definition'] ?? '';
        if (! empty($itemDefinition)) {
            $query .= $itemDefinition;
        } else {
            $errors[] = __('You must provide a routine definition.');
        }

        return $query;
    }

    /**
     * @see handleExecuteRoutine
     *
     * @param array $routine The routine params
     *
     * @return string[] The SQL queries / SQL query parts
     */
    private function getQueriesFromRoutineForm(array $routine): array
    {
        $queries = [];
        $end_query = [];
        $args = [];
        $all_functions = $this->dbi->types->getAllFunctions();
        for ($i = 0; $i < $routine['item_num_params']; $i++) {
            if (isset($_POST['params'][$routine['item_param_name'][$i]])) {
                $value = $_POST['params'][$routine['item_param_name'][$i]];
                if (is_array($value)) { // is SET type
                    $value = implode(',', $value);
                }

                $value = $this->dbi->escapeString($value);
                if (
                    ! empty($_POST['funcs'][$routine['item_param_name'][$i]])
                    && in_array($_POST['funcs'][$routine['item_param_name'][$i]], $all_functions)
                ) {
                    $queries[] = 'SET @p' . $i . '='
                        . $_POST['funcs'][$routine['item_param_name'][$i]]
                        . "('" . $value . "');\n";
                } else {
                    $queries[] = 'SET @p' . $i . "='" . $value . "';\n";
                }

                $args[] = '@p' . $i;
            } else {
                $args[] = '@p' . $i;
            }

            if ($routine['item_type'] !== 'PROCEDURE') {
                continue;
            }

            if ($routine['item_param_dir'][$i] !== 'OUT' && $routine['item_param_dir'][$i] !== 'INOUT') {
                continue;
            }

            $end_query[] = '@p' . $i . ' AS '
                . Util::backquote($routine['item_param_name'][$i]);
        }

        if ($routine['item_type'] === 'PROCEDURE') {
            $queries[] = 'CALL ' . Util::backquote($routine['item_name'])
                        . '(' . implode(', ', $args) . ");\n";
            if (count($end_query)) {
                $queries[] = 'SELECT ' . implode(', ', $end_query) . ";\n";
            }
        } else {
            $queries[] = 'SELECT ' . Util::backquote($routine['item_name'])
                        . '(' . implode(', ', $args) . ') '
                        . 'AS ' . Util::backquote($routine['item_name'])
                        . ";\n";
        }

        return $queries;
    }

    private function handleExecuteRoutine(): void
    {
        global $db;

        // Build the queries
        $routine = $this->getDataFromName($_POST['item_name'], $_POST['item_type'], false);
        if ($routine === null) {
            $message = __('Error in processing request:') . ' ';
            $message .= sprintf(
                __('No routine with name %1$s found in database %2$s.'),
                htmlspecialchars(Util::backquote($_POST['item_name'])),
                htmlspecialchars(Util::backquote($db))
            );
            $message = Message::error($message);
            if ($this->response->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);
                exit;
            }

            echo $message->getDisplay();
            unset($_POST);
            //NOTE: Missing exit ?
        }

        $queries = is_array($routine) ? $this->getQueriesFromRoutineForm($routine) : [];

        // Get all the queries as one SQL statement
        $multiple_query = implode('', $queries);

        $outcome = true;
        $affected = 0;

        // Execute query
        if (! $this->dbi->tryMultiQuery($multiple_query)) {
            $outcome = false;
        }

        // Generate output
        $output = '';
        $nbResultsetToDisplay = 0;
        if ($outcome) {
            // Pass the SQL queries through the "pretty printer"
            $output = Generator::formatSql(implode("\n", $queries));

            // Display results
            $output .= '<div class="card my-3"><div class="card-header">';
            $output .= sprintf(
                __('Execution results of routine %s'),
                Util::backquote(htmlspecialchars($routine['item_name']))
            );
            $output .= '</div><div class="card-body">';

            do {
                $result = $this->dbi->storeResult();

                if ($result !== false && $result->numRows() > 0) {
                    $output .= '<table class="table table-striped w-auto"><tr>';
                    foreach ($result->getFieldNames() as $field) {
                        $output .= '<th>';
                        $output .= htmlspecialchars($field);
                        $output .= '</th>';
                    }

                    $output .= '</tr>';

                    foreach ($result as $row) {
                        $output .= '<tr>' . $this->browseRow($row) . '</tr>';
                    }

                    $output .= '</table>';
                    $nbResultsetToDisplay++;
                    $affected = $result->numRows();
                }

                if (! $this->dbi->moreResults()) {
                    break;
                }

                unset($result);

                $outcome = $this->dbi->nextResult();
            } while ($outcome);
        }

        if ($outcome) {
            $output .= '</div></div>';

            $message = __('Your SQL query has been executed successfully.');
            if ($routine['item_type'] === 'PROCEDURE') {
                $message .= '<br>';

                // TODO : message need to be modified according to the
                // output from the routine
                $message .= sprintf(
                    _ngettext(
                        '%d row affected by the last statement inside the procedure.',
                        '%d rows affected by the last statement inside the procedure.',
                        (int) $affected
                    ),
                    $affected
                );
            }

            $message = Message::success($message);

            if ($nbResultsetToDisplay == 0) {
                $notice = __('MySQL returned an empty result set (i.e. zero rows).');
                $output .= Message::notice($notice)->getDisplay();
            }
        } else {
            $output = '';
            $message = Message::error(
                sprintf(
                    __('The following query has failed: "%s"'),
                    htmlspecialchars($multiple_query)
                )
                . '<br><br>'
                . __('MySQL said: ') . $this->dbi->getError()
            );
        }

        // Print/send output
        if ($this->response->isAjax()) {
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON('message', $message->getDisplay() . $output);
            $this->response->addJSON('dialog', false);
            exit;
        }

        echo $message->getDisplay() , $output;
        if ($message->isError()) {
            // At least one query has failed, so shouldn't
            // execute any more queries, so we quit.
            exit;
        }

        unset($_POST);
        // Now deliberately fall through to displaying the routines list
    }

    /**
     * Handles requests for executing a routine
     */
    public function handleExecute(): void
    {
        global $db;

        /**
         * Handle all user requests other than the default of listing routines
         */
        if (! empty($_POST['execute_routine']) && ! empty($_POST['item_name'])) {
            $this->handleExecuteRoutine();
        } elseif (! empty($_GET['execute_dialog']) && ! empty($_GET['item_name'])) {
            /**
             * Display the execute form for a routine.
             */
            $routine = $this->getDataFromName($_GET['item_name'], $_GET['item_type'], true);
            if ($routine !== null) {
                $form = $this->getExecuteForm($routine);
                if ($this->response->isAjax()) {
                    $title = __('Execute routine') . ' ' . Util::backquote(
                        htmlentities($_GET['item_name'], ENT_QUOTES)
                    );
                    $this->response->addJSON('message', $form);
                    $this->response->addJSON('title', $title);
                    $this->response->addJSON('dialog', true);
                } else {
                    echo "\n\n<h2>" . __('Execute routine') . "</h2>\n\n";
                    echo $form;
                }

                exit;
            }

            if ($this->response->isAjax()) {
                $message = __('Error in processing request:') . ' ';
                $message .= sprintf(
                    __('No routine with name %1$s found in database %2$s.'),
                    htmlspecialchars(Util::backquote($_GET['item_name'])),
                    htmlspecialchars(Util::backquote($db))
                );
                $message = Message::error($message);

                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);
                exit;
            }
        }
    }

    /**
     * Browse row array
     *
     * @param array $row Columns
     */
    private function browseRow(array $row): ?string
    {
        $output = null;
        foreach ($row as $value) {
            if ($value === null) {
                $value = '<i>NULL</i>';
            } else {
                $value = htmlspecialchars($value);
            }

            $output .= '<td>' . $value . '</td>';
        }

        return $output;
    }

    /**
     * Creates the HTML code that shows the routine execution dialog.
     *
     * @param array $routine Data for the routine returned by
     *                       getDataFromName()
     *
     * @return string HTML code for the routine execution dialog.
     */
    public function getExecuteForm(array $routine): string
    {
        global $db, $cfg;

        // Escape special characters
        $routine['item_name'] = htmlentities($routine['item_name'], ENT_QUOTES);
        for ($i = 0; $i < $routine['item_num_params']; $i++) {
            $routine['item_param_name'][$i] = htmlentities($routine['item_param_name'][$i], ENT_QUOTES);
        }

        $no_support_types = Util::unsupportedDatatypes();

        $params = [];
        $params['no_support_types'] = $no_support_types;

        for ($i = 0; $i < $routine['item_num_params']; $i++) {
            if ($routine['item_type'] === 'PROCEDURE' && $routine['item_param_dir'][$i] === 'OUT') {
                continue;
            }

            if ($cfg['ShowFunctionFields']) {
                if (
                    stripos($routine['item_param_type'][$i], 'enum') !== false
                    || stripos($routine['item_param_type'][$i], 'set') !== false
                    || in_array(
                        mb_strtolower($routine['item_param_type'][$i]),
                        $no_support_types
                    )
                ) {
                    $params[$i]['generator'] = null;
                } else {
                    $field = [
                        'True_Type' => mb_strtolower($routine['item_param_type'][$i]),
                        'Type' => '',
                        'Key' => '',
                        'Field' => '',
                        'Default' => '',
                        'first_timestamp' => false,
                    ];

                    $generator = Generator::getFunctionsForField($field, false, []);
                    $params[$i]['generator'] = $generator;
                }
            }

            if ($routine['item_param_type'][$i] === 'DATETIME' || $routine['item_param_type'][$i] === 'TIMESTAMP') {
                $params[$i]['class'] = 'datetimefield';
            } elseif ($routine['item_param_type'][$i] === 'DATE') {
                $params[$i]['class'] = 'datefield';
            }

            if (in_array($routine['item_param_type'][$i], ['ENUM', 'SET'])) {
                if ($routine['item_param_type'][$i] === 'ENUM') {
                    $params[$i]['input_type'] = 'radio';
                } else {
                    $params[$i]['input_type'] = 'checkbox';
                }

                foreach ($routine['item_param_length_arr'][$i] as $value) {
                    $value = htmlentities(Util::unQuote($value), ENT_QUOTES);
                    $params[$i]['htmlentities'][] = $value;
                }
            } elseif (in_array(mb_strtolower($routine['item_param_type'][$i]), $no_support_types)) {
                $params[$i]['input_type'] = null;
            } else {
                $params[$i]['input_type'] = 'text';
            }
        }

        return $this->template->render('database/routines/execute_form', [
            'db' => $db,
            'routine' => $routine,
            'ajax' => $this->response->isAjax(),
            'show_function_fields' => $cfg['ShowFunctionFields'],
            'params' => $params,
        ]);
    }

    /**
     * Creates the contents for a row in the list of routines
     *
     * @param array  $routine  An array of routine data
     * @param string $rowClass Additional class
     *
     * @return string HTML code of a row for the list of routines
     */
    public function getRow(array $routine, $rowClass = '')
    {
        global $db, $table;

        $sqlDrop = sprintf(
            'DROP %s IF EXISTS %s',
            $routine['type'],
            Util::backquote($routine['name'])
        );

        // this is for our purpose to decide whether to
        // show the edit link or not, so we need the DEFINER for the routine
        $where = 'ROUTINE_SCHEMA ' . Util::getCollateForIS() . '='
            . "'" . $this->dbi->escapeString($db) . "' "
            . "AND SPECIFIC_NAME='" . $this->dbi->escapeString($routine['name']) . "'"
            . "AND ROUTINE_TYPE='" . $this->dbi->escapeString($routine['type']) . "'";
        $query = 'SELECT `DEFINER` FROM INFORMATION_SCHEMA.ROUTINES WHERE ' . $where . ';';
        $routineDefiner = $this->dbi->fetchValue($query);

        $currentUser = $this->dbi->getCurrentUser();
        $currentUserIsRoutineDefiner = $currentUser === $routineDefiner;

        // Since editing a procedure involved dropping and recreating, check also for
        // CREATE ROUTINE privilege to avoid lost procedures.
        $hasCreateRoutine = Util::currentUserHasPrivilege('CREATE ROUTINE', $db);
        $hasEditPrivilege = ($hasCreateRoutine && $currentUserIsRoutineDefiner)
                            || $this->dbi->isSuperUser();
        $hasExportPrivilege = ($hasCreateRoutine && $currentUserIsRoutineDefiner)
                            || $this->dbi->isSuperUser();
        $hasExecutePrivilege = Util::currentUserHasPrivilege('EXECUTE', $db)
                            || $currentUserIsRoutineDefiner;

        // There is a problem with Util::currentUserHasPrivilege():
        // it does not detect all kinds of privileges, for example
        // a direct privilege on a specific routine. So, at this point,
        // we show the Execute link, hoping that the user has the correct rights.
        // Also, information_schema might be hiding the ROUTINE_DEFINITION
        // but a routine with no input parameters can be nonetheless executed.

        // Check if the routine has any input parameters. If it does,
        // we will show a dialog to get values for these parameters,
        // otherwise we can execute it directly.

        $definition = $this->dbi->getDefinition($db, $routine['type'], $routine['name']);
        $executeAction = '';

        if ($definition !== null) {
            $parser = new Parser($definition);

            /**
             * @var CreateStatement $stmt
             */
            $stmt = $parser->statements[0];

            $params = Routine::getParameters($stmt);

            if ($hasExecutePrivilege) {
                $executeAction = 'execute_routine';
                for ($i = 0; $i < $params['num']; $i++) {
                    if ($routine['type'] === 'PROCEDURE' && $params['dir'][$i] === 'OUT') {
                        continue;
                    }

                    $executeAction = 'execute_dialog';
                    break;
                }
            }
        }

        return $this->template->render('database/routines/row', [
            'db' => $db,
            'table' => $table,
            'sql_drop' => $sqlDrop,
            'routine' => $routine,
            'row_class' => $rowClass,
            'has_edit_privilege' => $hasEditPrivilege,
            'has_export_privilege' => $hasExportPrivilege,
            'has_execute_privilege' => $hasExecutePrivilege,
            'execute_action' => $executeAction,
        ]);
    }

    /**
     * @param string $createStatement Query
     * @param array  $errors          Errors
     *
     * @return array
     */
    private function checkResult($createStatement, array $errors)
    {
        // OMG, this is really bad! We dropped the query,
        // failed to create a new one
        // and now even the backup query does not execute!
        // This should not happen, but we better handle
        // this just in case.
        $errors[] = __('Sorry, we failed to restore the dropped routine.') . '<br>'
            . __('The backed up query was:')
            . '"' . htmlspecialchars($createStatement) . '"<br>'
            . __('MySQL said: ') . $this->dbi->getError();

        return $errors;
    }

    public function export(): void
    {
        global $db;

        if (empty($_GET['export_item']) || empty($_GET['item_name']) || empty($_GET['item_type'])) {
            return;
        }

        if ($_GET['item_type'] !== 'FUNCTION' && $_GET['item_type'] !== 'PROCEDURE') {
            return;
        }

        $routineDefinition = $this->dbi->getDefinition($db, $_GET['item_type'], $_GET['item_name']);
        $exportData = false;

        if ($routineDefinition !== null) {
            $exportData = "DELIMITER $$\n" . $routineDefinition . "$$\nDELIMITER ;\n";
        }

        $itemName = htmlspecialchars(Util::backquote($_GET['item_name']));
        if ($exportData !== false) {
            $exportData = htmlspecialchars(trim($exportData));
            $title = sprintf(__('Export of routine %s'), $itemName);

            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $exportData);
                $this->response->addJSON('title', $title);

                exit;
            }

            $output = '<div class="container">';
            $output .= '<h2>' . $title . '</h2>';
            $output .= '<div class="card"><div class="card-body">';
            $output .= '<textarea rows="15" class="form-control">' . $exportData . '</textarea>';
            $output .= '</div></div></div>';

            $this->response->addHTML($output);

            return;
        }

        $message = sprintf(
            __(
                'Error in processing request: No routine with name %1$s found in database %2$s.'
                . ' You might be lacking the necessary privileges to view/export this routine.'
            ),
            $itemName,
            htmlspecialchars(Util::backquote($db))
        );
        $message = Message::error($message);

        if ($this->response->isAjax()) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $message);

            exit;
        }

        $this->response->addHTML($message->getDisplay());
    }
}
