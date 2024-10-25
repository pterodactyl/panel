<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function count;
use function explode;
use function htmlspecialchars;
use function in_array;
use function intval;
use function mb_strtoupper;
use function sprintf;
use function str_contains;
use function strtoupper;
use function trim;

/**
 * Functions for event management.
 */
class Events
{
    /** @var array<string, array<int, string>> */
    private $status = [
        'query' => ['ENABLE', 'DISABLE', 'DISABLE ON SLAVE'],
        'display' => ['ENABLED', 'DISABLED', 'SLAVESIDE_DISABLED'],
    ];

    /** @var array<int, string> */
    private $type = ['RECURRING', 'ONE TIME'];

    /** @var array<int, string> */
    private $interval = [
        'YEAR',
        'QUARTER',
        'MONTH',
        'DAY',
        'HOUR',
        'MINUTE',
        'WEEK',
        'SECOND',
        'YEAR_MONTH',
        'DAY_HOUR',
        'DAY_MINUTE',
        'DAY_SECOND',
        'HOUR_MINUTE',
        'HOUR_SECOND',
        'MINUTE_SECOND',
    ];

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
        global $db, $table, $errors, $message;

        if (! empty($_POST['editor_process_add']) || ! empty($_POST['editor_process_edit'])) {
            $sql_query = '';

            $item_query = $this->getQueryFromRequest();

            // set by getQueryFromRequest()
            if (! count($errors)) {
                // Execute the created query
                if (! empty($_POST['editor_process_edit'])) {
                    // Backup the old trigger, in case something goes wrong
                    $create_item = $this->dbi->getDefinition($db, 'EVENT', $_POST['item_original_name']);
                    $drop_item = 'DROP EVENT IF EXISTS '
                        . Util::backquote($_POST['item_original_name'])
                        . ";\n";
                    $result = $this->dbi->tryQuery($drop_item);
                    if (! $result) {
                        $errors[] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($drop_item)
                        )
                        . '<br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                    } else {
                        $result = $this->dbi->tryQuery($item_query);
                        if (! $result) {
                            $errors[] = sprintf(
                                __('The following query has failed: "%s"'),
                                htmlspecialchars($item_query)
                            )
                            . '<br>'
                            . __('MySQL said: ') . $this->dbi->getError();
                            // We dropped the old item, but were unable to create
                            // the new one. Try to restore the backup query
                            $result = $this->dbi->tryQuery($create_item);
                            if (! $result) {
                                $errors = $this->checkResult($create_item, $errors);
                            }
                        } else {
                            $message = Message::success(
                                __('Event %1$s has been modified.')
                            );
                            $message->addParam(
                                Util::backquote($_POST['item_name'])
                            );
                            $sql_query = $drop_item . $item_query;
                        }
                    }
                } else {
                    // 'Add a new item' mode
                    $result = $this->dbi->tryQuery($item_query);
                    if (! $result) {
                        $errors[] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($item_query)
                        )
                        . '<br><br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                    } else {
                        $message = Message::success(
                            __('Event %1$s has been created.')
                        );
                        $message->addParam(
                            Util::backquote($_POST['item_name'])
                        );
                        $sql_query = $item_query;
                    }
                }
            }

            if (count($errors)) {
                $message = Message::error(
                    '<b>'
                    . __(
                        'One or more errors have occurred while processing your request:'
                    )
                    . '</b>'
                );
                $message->addHtml('<ul>');
                foreach ($errors as $string) {
                    $message->addHtml('<li>' . $string . '</li>');
                }

                $message->addHtml('</ul>');
            }

            $output = Generator::getMessage($message, $sql_query);

            if ($this->response->isAjax()) {
                if ($message->isSuccess()) {
                    $events = $this->dbi->getEvents($db, $_POST['item_name']);
                    $event = $events[0];
                    $this->response->addJSON(
                        'name',
                        htmlspecialchars(
                            mb_strtoupper($_POST['item_name'])
                        )
                    );
                    if (! empty($event)) {
                        $sqlDrop = sprintf(
                            'DROP EVENT IF EXISTS %s',
                            Util::backquote($event['name'])
                        );
                        $this->response->addJSON(
                            'new_row',
                            $this->template->render('database/events/row', [
                                'db' => $db,
                                'table' => $table,
                                'event' => $event,
                                'has_privilege' => Util::currentUserHasPrivilege('EVENT', $db),
                                'sql_drop' => $sqlDrop,
                                'row_class' => '',
                            ])
                        );
                    }

                    $this->response->addJSON('insert', ! empty($event));
                    $this->response->addJSON('message', $output);
                } else {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $message);
                }

                $this->response->addJSON('tableType', 'events');
                exit;
            }
        }

        /**
         * Display a form used to add/edit a trigger, if necessary
         */
        if (
            ! count($errors)
            && (! empty($_POST['editor_process_add'])
            || ! empty($_POST['editor_process_edit'])
            || (empty($_REQUEST['add_item'])
            && empty($_REQUEST['edit_item'])
            && empty($_POST['item_changetype'])))
        ) {
            return;
        }

        // FIXME: this must be simpler than that
        $operation = '';
        $title = '';
        $item = null;
        $mode = '';
        if (! empty($_POST['item_changetype'])) {
            $operation = 'change';
        }

        // Get the data for the form (if any)
        if (! empty($_REQUEST['add_item'])) {
            $title = __('Add event');
            $item = $this->getDataFromRequest();
            $mode = 'add';
        } elseif (! empty($_REQUEST['edit_item'])) {
            $title = __('Edit event');
            if (
                ! empty($_REQUEST['item_name'])
                && empty($_POST['editor_process_edit'])
                && empty($_POST['item_changetype'])
            ) {
                $item = $this->getDataFromName($_REQUEST['item_name']);
                if ($item !== null) {
                    $item['item_original_name'] = $item['item_name'];
                }
            } else {
                $item = $this->getDataFromRequest();
            }

            $mode = 'edit';
        }

        $this->sendEditor($mode, $item, $title, $db, $operation);
    }

    /**
     * This function will generate the values that are required to for the editor
     *
     * @return array    Data necessary to create the editor.
     */
    public function getDataFromRequest()
    {
        $retval = [];
        $indices = [
            'item_name',
            'item_original_name',
            'item_status',
            'item_execute_at',
            'item_interval_value',
            'item_interval_field',
            'item_starts',
            'item_ends',
            'item_definition',
            'item_preserve',
            'item_comment',
            'item_definer',
        ];
        foreach ($indices as $index) {
            $retval[$index] = $_POST[$index] ?? '';
        }

        $retval['item_type'] = 'ONE TIME';
        $retval['item_type_toggle'] = 'RECURRING';
        if (isset($_POST['item_type']) && $_POST['item_type'] === 'RECURRING') {
            $retval['item_type'] = 'RECURRING';
            $retval['item_type_toggle'] = 'ONE TIME';
        }

        return $retval;
    }

    /**
     * This function will generate the values that are required to complete
     * the "Edit event" form given the name of a event.
     *
     * @param string $name The name of the event.
     *
     * @return array|null Data necessary to create the editor.
     */
    public function getDataFromName($name): ?array
    {
        global $db;

        $retval = [];
        $columns = '`EVENT_NAME`, `STATUS`, `EVENT_TYPE`, `EXECUTE_AT`, '
                 . '`INTERVAL_VALUE`, `INTERVAL_FIELD`, `STARTS`, `ENDS`, '
                 . '`EVENT_DEFINITION`, `ON_COMPLETION`, `DEFINER`, `EVENT_COMMENT`';
        $where = 'EVENT_SCHEMA ' . Util::getCollateForIS() . '='
                 . "'" . $this->dbi->escapeString($db) . "' "
                 . "AND EVENT_NAME='" . $this->dbi->escapeString($name) . "'";
        $query = 'SELECT ' . $columns . ' FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE ' . $where . ';';
        $item = $this->dbi->fetchSingleRow($query);
        if (! $item) {
            return null;
        }

        $retval['item_name'] = $item['EVENT_NAME'];
        $retval['item_status'] = $item['STATUS'];
        $retval['item_type'] = $item['EVENT_TYPE'];
        if ($retval['item_type'] === 'RECURRING') {
            $retval['item_type_toggle'] = 'ONE TIME';
        } else {
            $retval['item_type_toggle'] = 'RECURRING';
        }

        $retval['item_execute_at'] = $item['EXECUTE_AT'];
        $retval['item_interval_value'] = $item['INTERVAL_VALUE'];
        $retval['item_interval_field'] = $item['INTERVAL_FIELD'];
        $retval['item_starts'] = $item['STARTS'];
        $retval['item_ends'] = $item['ENDS'];
        $retval['item_preserve'] = '';
        if ($item['ON_COMPLETION'] === 'PRESERVE') {
            $retval['item_preserve'] = " checked='checked'";
        }

        $retval['item_definition'] = $item['EVENT_DEFINITION'];
        $retval['item_definer'] = $item['DEFINER'];
        $retval['item_comment'] = $item['EVENT_COMMENT'];

        return $retval;
    }

    /**
     * Displays a form used to add/edit an event
     *
     * @param string $mode      If the editor will be used to edit an event
     *                          or add a new one: 'edit' or 'add'.
     * @param string $operation If the editor was previously invoked with
     *                          JS turned off, this will hold the name of
     *                          the current operation
     * @param array  $item      Data for the event returned by
     *                          getDataFromRequest() or getDataFromName()
     *
     * @return string   HTML code for the editor.
     */
    public function getEditorForm($mode, $operation, array $item)
    {
        global $db;

        if ($operation === 'change') {
            if ($item['item_type'] === 'RECURRING') {
                $item['item_type'] = 'ONE TIME';
                $item['item_type_toggle'] = 'RECURRING';
            } else {
                $item['item_type'] = 'RECURRING';
                $item['item_type_toggle'] = 'ONE TIME';
            }
        }

        return $this->template->render('database/events/editor_form', [
            'db' => $db,
            'event' => $item,
            'mode' => $mode,
            'is_ajax' => $this->response->isAjax(),
            'status_display' => $this->status['display'],
            'event_type' => $this->type,
            'event_interval' => $this->interval,
        ]);
    }

    /**
     * Composes the query necessary to create an event from an HTTP request.
     *
     * @return string  The CREATE EVENT query.
     */
    public function getQueryFromRequest()
    {
        global $errors;

        $query = 'CREATE ';
        if (! empty($_POST['item_definer'])) {
            if (str_contains($_POST['item_definer'], '@')) {
                $arr = explode('@', $_POST['item_definer']);
                $query .= 'DEFINER=' . Util::backquote($arr[0]);
                $query .= '@' . Util::backquote($arr[1]) . ' ';
            } else {
                $errors[] = __('The definer must be in the "username@hostname" format!');
            }
        }

        $query .= 'EVENT ';
        if (! empty($_POST['item_name'])) {
            $query .= Util::backquote($_POST['item_name']) . ' ';
        } else {
            $errors[] = __('You must provide an event name!');
        }

        $query .= 'ON SCHEDULE ';
        if (! empty($_POST['item_type']) && in_array($_POST['item_type'], $this->type)) {
            if ($_POST['item_type'] === 'RECURRING') {
                if (
                    ! empty($_POST['item_interval_value'])
                    && ! empty($_POST['item_interval_field'])
                    && in_array($_POST['item_interval_field'], $this->interval)
                ) {
                    $query .= 'EVERY ' . intval($_POST['item_interval_value']) . ' ';
                    $query .= $_POST['item_interval_field'] . ' ';
                } else {
                    $errors[] = __('You must provide a valid interval value for the event.');
                }

                if (! empty($_POST['item_starts'])) {
                    $query .= "STARTS '"
                        . $this->dbi->escapeString($_POST['item_starts'])
                        . "' ";
                }

                if (! empty($_POST['item_ends'])) {
                    $query .= "ENDS '"
                        . $this->dbi->escapeString($_POST['item_ends'])
                        . "' ";
                }
            } else {
                if (! empty($_POST['item_execute_at'])) {
                    $query .= "AT '"
                        . $this->dbi->escapeString($_POST['item_execute_at'])
                        . "' ";
                } else {
                    $errors[] = __('You must provide a valid execution time for the event.');
                }
            }
        } else {
            $errors[] = __('You must provide a valid type for the event.');
        }

        $query .= 'ON COMPLETION ';
        if (empty($_POST['item_preserve'])) {
            $query .= 'NOT ';
        }

        $query .= 'PRESERVE ';
        if (! empty($_POST['item_status'])) {
            foreach ($this->status['display'] as $key => $value) {
                if ($value == $_POST['item_status']) {
                    $query .= $this->status['query'][$key] . ' ';
                    break;
                }
            }
        }

        if (! empty($_POST['item_comment'])) {
            $query .= "COMMENT '" . $this->dbi->escapeString($_POST['item_comment']) . "' ";
        }

        $query .= 'DO ';
        if (! empty($_POST['item_definition'])) {
            $query .= $_POST['item_definition'];
        } else {
            $errors[] = __('You must provide an event definition.');
        }

        return $query;
    }

    public function getEventSchedulerStatus(): bool
    {
        $state = (string) $this->dbi->fetchValue('SHOW GLOBAL VARIABLES LIKE \'event_scheduler\'', 1);

        return strtoupper($state) === 'ON' || $state === '1';
    }

    /**
     * @param string|null $createStatement Query
     * @param array       $errors          Errors
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
        $errors[] = __('Sorry, we failed to restore the dropped event.') . '<br>'
            . __('The backed up query was:')
            . '"' . htmlspecialchars((string) $createStatement) . '"<br>'
            . __('MySQL said: ') . $this->dbi->getError();

        return $errors;
    }

    /**
     * Send editor via ajax or by echoing.
     *
     * @param string     $mode      Editor mode 'add' or 'edit'
     * @param array|null $item      Data necessary to create the editor
     * @param string     $title     Title of the editor
     * @param string     $db        Database
     * @param string     $operation Operation 'change' or ''
     */
    private function sendEditor($mode, ?array $item, $title, $db, $operation): void
    {
        if ($item !== null) {
            $editor = $this->getEditorForm($mode, $operation, $item);
            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $editor);
                $this->response->addJSON('title', $title);
            } else {
                echo "\n\n<h2>" . $title . "</h2>\n\n" . $editor;
                unset($_POST);
            }

            exit;
        }

        $message = __('Error in processing request:') . ' ';
        $message .= sprintf(
            __('No event with name %1$s found in database %2$s.'),
            htmlspecialchars(Util::backquote($_REQUEST['item_name'])),
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

    public function export(): void
    {
        global $db;

        if (empty($_GET['export_item']) || empty($_GET['item_name'])) {
            return;
        }

        $itemName = $_GET['item_name'];
        $exportData = $this->dbi->getDefinition($db, 'EVENT', $itemName);

        if (! $exportData) {
            $exportData = false;
        }

        $itemName = htmlspecialchars(Util::backquote($itemName));
        if ($exportData !== false) {
            $exportData = htmlspecialchars(trim($exportData));
            $title = sprintf(__('Export of event %s'), $itemName);

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
            __('Error in processing request: No event with name %1$s found in database %2$s.'),
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
