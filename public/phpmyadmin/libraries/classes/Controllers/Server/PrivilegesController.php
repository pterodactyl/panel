<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\PrivilegesController as DatabaseController;
use PhpMyAdmin\Controllers\Table\PrivilegesController as TableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function header;
use function implode;
use function is_array;
use function is_string;
use function ob_get_clean;
use function ob_start;
use function str_replace;
use function urlencode;

/**
 * Server privileges and users manipulations.
 */
class PrivilegesController extends AbstractController
{
    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $db, $table, $errorUrl, $message, $text_dir, $post_patterns;
        global $username, $hostname, $dbname, $tablename, $routinename, $db_and_table, $dbname_is_wildcard;
        global $queries, $password, $ret_message, $ret_queries, $queries_for_display, $sql_query, $_add_user_error;
        global $itemType, $tables, $num_tables, $total_num_tables, $sub_part;
        global $tooltip_truename, $tooltip_aliasname, $pos, $title, $export, $grants, $one_grant, $url_dbname;

        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        $relationParameters = $this->relation->getRelationParameters();

        $this->addScriptFiles(['server/privileges.js', 'vendor/zxcvbn-ts.js']);

        $relationCleanup = new RelationCleanup($this->dbi, $this->relation);
        $serverPrivileges = new Privileges(
            $this->template,
            $this->dbi,
            $this->relation,
            $relationCleanup,
            new Plugins($this->dbi)
        );

        $databaseController = new DatabaseController(
            $this->response,
            $this->template,
            $db,
            $serverPrivileges,
            $this->dbi
        );

        $tableController = new TableController(
            $this->response,
            $this->template,
            $db,
            $table,
            $serverPrivileges,
            $this->dbi
        );

        if (
            (isset($_GET['viewing_mode'])
                && $_GET['viewing_mode'] === 'server')
            && $relationParameters->configurableMenusFeature !== null
        ) {
            $this->response->addHTML('<div class="container-fluid">');
            $this->render('server/privileges/subnav', [
                'active' => 'privileges',
                'is_super_user' => $this->dbi->isSuperUser(),
            ]);
        }

        /**
         * Sets globals from $_POST patterns, for privileges and max_* vars
         */
        $post_patterns = [
            '/_priv$/i',
            '/^max_/i',
        ];

        Core::setPostAsGlobal($post_patterns);

        $errorUrl = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $_add_user_error = false;
        /**
         * Get DB information: username, hostname, dbname,
         * tablename, db_and_table, dbname_is_wildcard
         */
        [
            $username,
            $hostname,
            $dbname,
            $tablename,
            $routinename,
            $db_and_table,
            $dbname_is_wildcard,
        ] = $serverPrivileges->getDataForDBInfo();

        /**
         * Checks if the user is allowed to do what they try to...
         */
        $isGrantUser = $this->dbi->isGrantUser();
        $isCreateUser = $this->dbi->isCreateUser();

        if (! $this->dbi->isSuperUser() && ! $isGrantUser && ! $isCreateUser) {
            $this->render('server/sub_page_header', [
                'type' => 'privileges',
                'is_image' => false,
            ]);
            $this->response->addHTML(
                Message::error(__('No Privileges'))
                    ->getDisplay()
            );

            return;
        }

        if (! $isGrantUser && ! $isCreateUser) {
            $this->response->addHTML(Message::notice(
                __('You do not have the privileges to administrate the users!')
            )->getDisplay());
        }

        /**
         * Checks if the user is using "Change Login Information / Copy User" dialog
         * only to update the password
         */
        if (
            isset($_POST['change_copy']) && $username == $_POST['old_username']
            && $hostname == $_POST['old_hostname']
        ) {
            $this->response->addHTML(
                Message::error(
                    __(
                        "Username and hostname didn't change. "
                        . 'If you only want to change the password, '
                        . "'Change password' tab should be used."
                    )
                )->getDisplay()
            );
            $this->response->setRequestStatus(false);

            return;
        }

        /**
         * Changes / copies a user, part I
         */
        [$queries, $password] = $serverPrivileges->getDataForChangeOrCopyUser();

        /**
         * Adds a user
         *   (Changes / copies a user, part II)
         */
        [
            $ret_message,
            $ret_queries,
            $queries_for_display,
            $sql_query,
            $_add_user_error,
        ] = $serverPrivileges->addUser(
            $dbname ?? null,
            $username ?? '',
            $hostname ?? '',
            $password ?? null,
            $relationParameters->configurableMenusFeature !== null
        );
        //update the old variables
        if (isset($ret_queries)) {
            $queries = $ret_queries;
            unset($ret_queries);
        }

        if (isset($ret_message)) {
            $message = $ret_message;
            unset($ret_message);
        }

        /**
         * Changes / copies a user, part III
         */
        if (isset($_POST['change_copy']) && $username !== null && $hostname !== null) {
            $queries = $serverPrivileges->getDbSpecificPrivsQueriesForChangeOrCopyUser($queries, $username, $hostname);
        }

        $itemType = '';
        if (! empty($routinename) && is_string($dbname)) {
            $itemType = $serverPrivileges->getRoutineType($dbname, $routinename);
        }

        /**
         * Updates privileges
         */
        if (! empty($_POST['update_privs'])) {
            if (is_array($dbname)) {
                foreach ($dbname as $key => $db_name) {
                    [$sql_query[$key], $message] = $serverPrivileges->updatePrivileges(
                        ($username ?? ''),
                        ($hostname ?? ''),
                        ($tablename ?? ($routinename ?? '')),
                        ($db_name ?? ''),
                        $itemType
                    );
                }

                $sql_query = implode("\n", $sql_query);
            } else {
                [$sql_query, $message] = $serverPrivileges->updatePrivileges(
                    ($username ?? ''),
                    ($hostname ?? ''),
                    ($tablename ?? ($routinename ?? '')),
                    ($dbname ?? ''),
                    $itemType
                );
            }
        }

        /**
         * Assign users to user groups
         */
        if (
            ! empty($_POST['changeUserGroup']) && $relationParameters->configurableMenusFeature !== null
            && $this->dbi->isSuperUser() && $this->dbi->isCreateUser()
        ) {
            $serverPrivileges->setUserGroup($username ?? '', $_POST['userGroup']);
            $message = Message::success();
        }

        /**
         * Revokes Privileges
         */
        if (isset($_POST['revokeall'])) {
            [$message, $sql_query] = $serverPrivileges->getMessageAndSqlQueryForPrivilegesRevoke(
                (is_string($dbname) ? $dbname : ''),
                ($tablename ?? ($routinename ?? '')),
                $username ?? '',
                $hostname ?? '',
                $itemType
            );
        }

        /**
         * Updates the password
         */
        if (isset($_POST['change_pw'])) {
            $message = $serverPrivileges->updatePassword($errorUrl, $username ?? '', $hostname ?? '');
        }

        /**
         * Deletes users
         *   (Changes / copies a user, part IV)
         */
        if (isset($_POST['delete']) || (isset($_POST['change_copy']) && $_POST['mode'] < 4)) {
            $queries = $serverPrivileges->getDataForDeleteUsers($queries);
            if (empty($_POST['change_copy'])) {
                [$sql_query, $message] = $serverPrivileges->deleteUser($queries);
            }
        }

        /**
         * Changes / copies a user, part V
         */
        if (isset($_POST['change_copy'])) {
            $queries = $serverPrivileges->getDataForQueries($queries, $queries_for_display);
            $message = Message::success();
            $sql_query = implode("\n", $queries);
        }

        /**
         * Reloads the privilege tables into memory
         */
        $message_ret = $serverPrivileges->updateMessageForReload();
        if ($message_ret !== null) {
            $message = $message_ret;
            unset($message_ret);
        }

        /**
         * If we are in an Ajax request for Create User/Edit User/Revoke User/
         * Flush Privileges, show $message and return.
         */
        if (
            $this->response->isAjax()
            && empty($_REQUEST['ajax_page_request'])
            && ! isset($_GET['export'])
            && (! isset($_POST['submit_mult']) || $_POST['submit_mult'] !== 'export')
            && ((! isset($_GET['initial']) || $_GET['initial'] === '')
                || (isset($_POST['delete']) && $_POST['delete'] === __('Go')))
            && ! isset($_GET['showall'])
        ) {
            $extra_data = $serverPrivileges->getExtraDataForAjaxBehavior(
                ($password ?? ''),
                ($sql_query ?? ''),
                ($hostname ?? ''),
                ($username ?? '')
            );

            if (! empty($message) && $message instanceof Message) {
                $this->response->setRequestStatus($message->isSuccess());
                $this->response->addJSON('message', $message);
                $this->response->addJSON($extra_data);

                return;
            }
        }

        /**
         * Displays the links
         */
        if (isset($_GET['viewing_mode']) && $_GET['viewing_mode'] === 'db') {
            $db = $_REQUEST['db'] = $_GET['checkprivsdb'];

            // Gets the database structure
            $sub_part = '_structure';
            ob_start();

            [
                $tables,
                $num_tables,
                $total_num_tables,
                $sub_part,,,
                $tooltip_truename,
                $tooltip_aliasname,
                $pos,
            ] = Util::getDbInfo($db, $sub_part);

            $content = ob_get_clean();
            $this->response->addHTML($content . "\n");
        } elseif (! empty($GLOBALS['message'])) {
            $this->response->addHTML(Generator::getMessage($GLOBALS['message']));
            unset($GLOBALS['message']);
        }

        // export user definition
        if (isset($_GET['export']) || (isset($_POST['submit_mult']) && $_POST['submit_mult'] === 'export')) {
            [$title, $export] = $serverPrivileges->getListForExportUserDefinition($username ?? '', $hostname ?? '');

            unset($username, $hostname, $grants, $one_grant);

            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $export);
                $this->response->addJSON('title', $title);

                return;
            }

            $this->response->addHTML('<h2>' . $title . '</h2>' . $export);
        }

        // Show back the form if an error occurred
        if (isset($_GET['adduser']) || $_add_user_error === true) {
            // Add user
            $this->response->addHTML(
                $serverPrivileges->getHtmlForAddUser(Util::escapeMysqlWildcards(is_string($dbname) ? $dbname : ''))
            );
        } elseif (isset($_GET['checkprivsdb']) && is_string($_GET['checkprivsdb'])) {
            if (isset($_GET['checkprivstable']) && is_string($_GET['checkprivstable'])) {
                $this->response->addHTML($tableController([
                    'checkprivsdb' => $_GET['checkprivsdb'],
                    'checkprivstable' => $_GET['checkprivstable'],
                ]));
                $this->render('export_modal');
            } elseif ($this->response->isAjax() === true && empty($_REQUEST['ajax_page_request'])) {
                $message = Message::success(__('User has been added.'));
                $this->response->addJSON('message', $message);

                return;
            } else {
                $this->response->addHTML($databaseController(['checkprivsdb' => $_GET['checkprivsdb']]));
                $this->render('export_modal');
            }
        } else {
            if (isset($dbname) && ! is_array($dbname)) {
                $url_dbname = urlencode(
                    str_replace(
                        [
                            '\_',
                            '\%',
                        ],
                        [
                            '_',
                            '%',
                        ],
                        $dbname
                    )
                );
            }

            if (! isset($username)) {
                // No username is given --> display the overview
                $this->response->addHTML(
                    $serverPrivileges->getHtmlForUserOverview($text_dir)
                );
            } elseif (! empty($routinename)) {
                $this->response->addHTML(
                    $serverPrivileges->getHtmlForRoutineSpecificPrivileges(
                        $username,
                        $hostname ?? '',
                        is_string($dbname) ? $dbname : '',
                        $routinename,
                        Util::escapeMysqlWildcards($url_dbname ?? '')
                    )
                );
            } else {
                // A user was selected -> display the user's properties
                // In an Ajax request, prevent cached values from showing
                if ($this->response->isAjax()) {
                    header('Cache-Control: no-cache');
                }

                $this->response->addHTML(
                    $serverPrivileges->getHtmlForUserProperties(
                        $dbname_is_wildcard,
                        Util::escapeMysqlWildcards($url_dbname ?? ''),
                        $username,
                        $hostname ?? '',
                        $dbname ?? '',
                        $tablename ?? ''
                    )
                );
            }
        }

        if (
            ! isset($_GET['viewing_mode'])
            || $_GET['viewing_mode'] !== 'server'
            || $relationParameters->configurableMenusFeature === null
        ) {
            return;
        }

        $this->response->addHTML('</div>');
    }
}
