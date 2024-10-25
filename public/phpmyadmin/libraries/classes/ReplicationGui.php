<?php
/**
 * Functions for the replication GUI
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Query\Utilities;

use function __;
use function htmlspecialchars;
use function in_array;
use function mb_strrpos;
use function mb_strtolower;
use function mb_substr;
use function sprintf;
use function str_replace;
use function strlen;
use function strtok;
use function time;

/**
 * Functions for the replication GUI
 */
class ReplicationGui
{
    /** @var Replication */
    private $replication;

    /** @var Template */
    private $template;

    /**
     * @param Replication $replication Replication instance
     * @param Template    $template    Template instance
     */
    public function __construct(Replication $replication, Template $template)
    {
        $this->replication = $replication;
        $this->template = $template;
    }

    /**
     * returns HTML for error message
     *
     * @return string HTML code
     */
    public function getHtmlForErrorMessage(): string
    {
        $html = '';
        if (isset($_SESSION['replication']['sr_action_status'], $_SESSION['replication']['sr_action_info'])) {
            if ($_SESSION['replication']['sr_action_status'] === 'error') {
                $errorMessage = $_SESSION['replication']['sr_action_info'];
                $html .= Message::error($errorMessage)->getDisplay();
                $_SESSION['replication']['sr_action_status'] = 'unknown';
            } elseif ($_SESSION['replication']['sr_action_status'] === 'success') {
                $successMessage = $_SESSION['replication']['sr_action_info'];
                $html .= Message::success($successMessage)->getDisplay();
                $_SESSION['replication']['sr_action_status'] = 'unknown';
            }
        }

        return $html;
    }

    /**
     * returns HTML for primary replication
     *
     * @return string HTML code
     */
    public function getHtmlForPrimaryReplication(): string
    {
        global $dbi;

        if (! isset($_POST['repl_clear_scr'])) {
            $primaryStatusTable = $this->getHtmlForReplicationStatusTable('primary', true, false);
            $replicas = $dbi->fetchResult('SHOW SLAVE HOSTS', null, null);

            $urlParams = $GLOBALS['urlParams'];
            $urlParams['primary_add_user'] = true;
            $urlParams['repl_clear_scr'] = true;
        }

        if (isset($_POST['primary_add_user'])) {
            $primaryAddReplicaUser = $this->getHtmlForReplicationPrimaryAddReplicaUser();
        }

        return $this->template->render('server/replication/primary_replication', [
            'clear_screen' => isset($_POST['repl_clear_scr']),
            'primary_status_table' => $primaryStatusTable ?? '',
            'replicas' => $replicas ?? [],
            'url_params' => $urlParams ?? [],
            'primary_add_user' => isset($_POST['primary_add_user']),
            'primary_add_replica_user' => $primaryAddReplicaUser ?? '',
        ]);
    }

    /**
     * returns HTML for primary replication configuration
     *
     * @return string HTML code
     */
    public function getHtmlForPrimaryConfiguration(): string
    {
        $databaseMultibox = $this->getHtmlForReplicationDbMultibox();

        return $this->template->render(
            'server/replication/primary_configuration',
            ['database_multibox' => $databaseMultibox]
        );
    }

    /**
     * returns HTML for replica replication configuration
     *
     * @param bool  $serverReplicaStatus      Whether it is Primary or Replica
     * @param array $serverReplicaReplication Replica replication
     *
     * @return string HTML code
     */
    public function getHtmlForReplicaConfiguration(
        $serverReplicaStatus,
        array $serverReplicaReplication
    ): string {
        global $dbi;

        $serverReplicaMultiReplication = $dbi->fetchResult('SHOW ALL SLAVES STATUS');
        if ($serverReplicaStatus) {
            $urlParams = $GLOBALS['urlParams'];
            $urlParams['sr_take_action'] = true;
            $urlParams['sr_replica_server_control'] = true;

            if ($serverReplicaReplication[0]['Slave_IO_Running'] === 'No') {
                $urlParams['sr_replica_action'] = 'start';
            } else {
                $urlParams['sr_replica_action'] = 'stop';
            }

            $urlParams['sr_replica_control_param'] = 'IO_THREAD';
            $replicaControlIoLink = Url::getCommon($urlParams, '', false);

            if ($serverReplicaReplication[0]['Slave_SQL_Running'] === 'No') {
                $urlParams['sr_replica_action'] = 'start';
            } else {
                $urlParams['sr_replica_action'] = 'stop';
            }

            $urlParams['sr_replica_control_param'] = 'SQL_THREAD';
            $replicaControlSqlLink = Url::getCommon($urlParams, '', false);

            if (
                $serverReplicaReplication[0]['Slave_IO_Running'] === 'No'
                || $serverReplicaReplication[0]['Slave_SQL_Running'] === 'No'
            ) {
                $urlParams['sr_replica_action'] = 'start';
            } else {
                $urlParams['sr_replica_action'] = 'stop';
            }

            $urlParams['sr_replica_control_param'] = null;
            $replicaControlFullLink = Url::getCommon($urlParams, '', false);

            $urlParams['sr_replica_action'] = 'reset';
            $replicaControlResetLink = Url::getCommon($urlParams, '', false);

            $urlParams = $GLOBALS['urlParams'];
            $urlParams['sr_take_action'] = true;
            $urlParams['sr_replica_skip_error'] = true;
            $replicaSkipErrorLink = Url::getCommon($urlParams, '', false);

            $urlParams = $GLOBALS['urlParams'];
            $urlParams['replica_configure'] = true;
            $urlParams['repl_clear_scr'] = true;

            $reconfigurePrimaryLink = Url::getCommon($urlParams, '', false);

            $replicaStatusTable = $this->getHtmlForReplicationStatusTable('replica', true, false);

            $replicaIoRunning = $serverReplicaReplication[0]['Slave_IO_Running'] !== 'No';
            $replicaSqlRunning = $serverReplicaReplication[0]['Slave_SQL_Running'] !== 'No';
        }

        return $this->template->render('server/replication/replica_configuration', [
            'server_replica_multi_replication' => $serverReplicaMultiReplication,
            'url_params' => $GLOBALS['urlParams'],
            'primary_connection' => $_POST['primary_connection'] ?? '',
            'server_replica_status' => $serverReplicaStatus,
            'replica_status_table' => $replicaStatusTable ?? '',
            'replica_sql_running' => $replicaSqlRunning ?? false,
            'replica_io_running' => $replicaIoRunning ?? false,
            'replica_control_full_link' => $replicaControlFullLink ?? '',
            'replica_control_reset_link' => $replicaControlResetLink ?? '',
            'replica_control_sql_link' => $replicaControlSqlLink ?? '',
            'replica_control_io_link' => $replicaControlIoLink ?? '',
            'replica_skip_error_link' => $replicaSkipErrorLink ?? '',
            'reconfigure_primary_link' => $reconfigurePrimaryLink ?? '',
            'has_replica_configure' => isset($_POST['replica_configure']),
        ]);
    }

    /**
     * returns HTML code for selecting databases
     *
     * @return string HTML code
     */
    public function getHtmlForReplicationDbMultibox(): string
    {
        $databases = [];
        foreach ($GLOBALS['dblist']->databases as $database) {
            if (Utilities::isSystemSchema($database)) {
                continue;
            }

            $databases[] = $database;
        }

        return $this->template->render('server/replication/database_multibox', ['databases' => $databases]);
    }

    /**
     * returns HTML for changing primary
     *
     * @param string $submitName submit button name
     *
     * @return string HTML code
     */
    public function getHtmlForReplicationChangePrimary($submitName): string
    {
        [
            $usernameLength,
            $hostnameLength,
        ] = $this->getUsernameHostnameLength();

        return $this->template->render('server/replication/change_primary', [
            'server_id' => time(),
            'username_length' => $usernameLength,
            'hostname_length' => $hostnameLength,
            'submit_name' => $submitName,
        ]);
    }

    /**
     * This function returns html code for table with replication status.
     *
     * @param string $type     either primary or replica
     * @param bool   $isHidden if true, then default style is set to hidden, default value false
     * @param bool   $hasTitle if true, then title is displayed, default true
     *
     * @return string HTML code
     */
    public function getHtmlForReplicationStatusTable(
        $type,
        $isHidden = false,
        $hasTitle = true
    ): string {
        global $dbi;

        $replicationInfo = new ReplicationInfo($dbi);
        $replicationInfo->load($_POST['primary_connection'] ?? null);

        $replicationVariables = $replicationInfo->primaryVariables;
        $variablesAlerts = null;
        $variablesOks = null;
        $serverReplication = $replicationInfo->getPrimaryStatus();
        if ($type === 'replica') {
            $replicationVariables = $replicationInfo->replicaVariables;
            $variablesAlerts = [
                'Slave_IO_Running' => 'No',
                'Slave_SQL_Running' => 'No',
            ];
            $variablesOks = [
                'Slave_IO_Running' => 'Yes',
                'Slave_SQL_Running' => 'Yes',
            ];
            $serverReplication = $replicationInfo->getReplicaStatus();
        }

        $variables = [];
        foreach ($replicationVariables as $variable) {
            $serverReplicationVariable = isset($serverReplication[0])
                ? $serverReplication[0][$variable]
                : '';

            $variables[$variable] = [
                'name' => $variable,
                'status' => '',
                'value' => $serverReplicationVariable,
            ];

            if (isset($variablesAlerts[$variable]) && $variablesAlerts[$variable] === $serverReplicationVariable) {
                $variables[$variable]['status'] = 'text-danger';
            } elseif (isset($variablesOks[$variable]) && $variablesOks[$variable] === $serverReplicationVariable) {
                $variables[$variable]['status'] = 'text-success';
            }

            $variablesWrap = [
                'Replicate_Do_DB',
                'Replicate_Ignore_DB',
                'Replicate_Do_Table',
                'Replicate_Ignore_Table',
                'Replicate_Wild_Do_Table',
                'Replicate_Wild_Ignore_Table',
            ];
            if (! in_array($variable, $variablesWrap)) {
                continue;
            }

            $variables[$variable]['value'] = str_replace(',', ', ', $serverReplicationVariable);
        }

        return $this->template->render('server/replication/status_table', [
            'type' => $type,
            'is_hidden' => $isHidden,
            'has_title' => $hasTitle,
            'variables' => $variables,
        ]);
    }

    /**
     * get the correct username and hostname lengths for this MySQL server
     *
     * @return array<int,int> username length, hostname length
     */
    public function getUsernameHostnameLength(): array
    {
        global $dbi;

        $fieldsInfo = $dbi->getColumns('mysql', 'user');
        $usernameLength = 16;
        $hostnameLength = 41;
        foreach ($fieldsInfo as $val) {
            if ($val['Field'] === 'User') {
                strtok($val['Type'], '()');
                $v = strtok('()');
                if (Util::isInteger($v)) {
                    $usernameLength = (int) $v;
                }
            } elseif ($val['Field'] === 'Host') {
                strtok($val['Type'], '()');
                $v = strtok('()');
                if (Util::isInteger($v)) {
                    $hostnameLength = (int) $v;
                }
            }
        }

        return [
            $usernameLength,
            $hostnameLength,
        ];
    }

    /**
     * returns html code to add a replication replica user to the primary
     *
     * @return string HTML code
     */
    public function getHtmlForReplicationPrimaryAddReplicaUser(): string
    {
        global $dbi;

        [
            $usernameLength,
            $hostnameLength,
        ] = $this->getUsernameHostnameLength();

        if (isset($_POST['username']) && strlen($_POST['username']) === 0) {
            $GLOBALS['pred_username'] = 'any';
        }

        $username = '';
        if (! empty($_POST['username'])) {
            $username = $GLOBALS['new_username'] ?? $_POST['username'];
        }

        $currentUser = $dbi->fetchValue('SELECT USER();');
        if (! empty($currentUser)) {
            $userHost = str_replace(
                "'",
                '',
                mb_substr(
                    $currentUser,
                    mb_strrpos($currentUser, '@') + 1
                )
            );
            if ($userHost !== 'localhost' && $userHost !== '127.0.0.1') {
                $thisHost = $userHost;
            }
        }

        // when we start editing a user, $GLOBALS['pred_hostname'] is not defined
        if (! isset($GLOBALS['pred_hostname']) && isset($_POST['hostname'])) {
            switch (mb_strtolower($_POST['hostname'])) {
                case 'localhost':
                case '127.0.0.1':
                    $GLOBALS['pred_hostname'] = 'localhost';
                    break;
                case '%':
                    $GLOBALS['pred_hostname'] = 'any';
                    break;
                default:
                    $GLOBALS['pred_hostname'] = 'userdefined';
                    break;
            }
        }

        return $this->template->render('server/replication/primary_add_replica_user', [
            'username_length' => $usernameLength,
            'hostname_length' => $hostnameLength,
            'has_username' => isset($_POST['username']),
            'username' => $username,
            'hostname' => $_POST['hostname'] ?? '',
            'predefined_username' => $GLOBALS['pred_username'] ?? '',
            'predefined_hostname' => $GLOBALS['pred_hostname'] ?? '',
            'this_host' => $thisHost ?? null,
        ]);
    }

    /**
     * handle control requests
     */
    public function handleControlRequest(): void
    {
        if (! isset($_POST['sr_take_action'])) {
            return;
        }

        $refresh = false;
        $result = false;
        $messageSuccess = '';
        $messageError = '';

        if (isset($_POST['replica_changeprimary']) && ! $GLOBALS['cfg']['AllowArbitraryServer']) {
            $_SESSION['replication']['sr_action_status'] = 'error';
            $_SESSION['replication']['sr_action_info'] = __(
                'Connection to server is disabled, please enable'
                . ' $cfg[\'AllowArbitraryServer\'] in phpMyAdmin configuration.'
            );
        } elseif (isset($_POST['replica_changeprimary'])) {
            $result = $this->handleRequestForReplicaChangePrimary();
        } elseif (isset($_POST['sr_replica_server_control'])) {
            $result = $this->handleRequestForReplicaServerControl();
            $refresh = true;

            switch ($_POST['sr_replica_action']) {
                case 'start':
                    $messageSuccess = __('Replication started successfully.');
                    $messageError = __('Error starting replication.');
                    break;
                case 'stop':
                    $messageSuccess = __('Replication stopped successfully.');
                    $messageError = __('Error stopping replication.');
                    break;
                case 'reset':
                    $messageSuccess = __('Replication resetting successfully.');
                    $messageError = __('Error resetting replication.');
                    break;
                default:
                    $messageSuccess = __('Success.');
                    $messageError = __('Error.');
                    break;
            }
        } elseif (isset($_POST['sr_replica_skip_error'])) {
            $result = $this->handleRequestForReplicaSkipError();
        }

        if ($refresh) {
            $response = ResponseRenderer::getInstance();
            if ($response->isAjax()) {
                $response->setRequestStatus($result);
                $response->addJSON(
                    'message',
                    $result
                    ? Message::success($messageSuccess)
                    : Message::error($messageError)
                );
            } else {
                Core::sendHeaderLocation(
                    './index.php?route=/server/replication'
                    . Url::getCommonRaw($GLOBALS['urlParams'], '&')
                );
            }
        }

        unset($refresh);
    }

    public function handleRequestForReplicaChangePrimary(): bool
    {
        global $dbi;

        $sr = [
            'username' => $dbi->escapeString($_POST['username']),
            'pma_pw' => $dbi->escapeString($_POST['pma_pw']),
            'hostname' => $dbi->escapeString($_POST['hostname']),
            'port' => (int) $dbi->escapeString($_POST['text_port']),
        ];

        $_SESSION['replication']['m_username'] = $sr['username'];
        $_SESSION['replication']['m_password'] = $sr['pma_pw'];
        $_SESSION['replication']['m_hostname'] = $sr['hostname'];
        $_SESSION['replication']['m_port'] = $sr['port'];
        $_SESSION['replication']['m_correct'] = '';
        $_SESSION['replication']['sr_action_status'] = 'error';
        $_SESSION['replication']['sr_action_info'] = __('Unknown error');

        // Attempt to connect to the new primary server
        $linkToPrimary = $this->replication->connectToPrimary(
            $sr['username'],
            $sr['pma_pw'],
            $sr['hostname'],
            $sr['port']
        );

        if (! $linkToPrimary) {
            $_SESSION['replication']['sr_action_status'] = 'error';
            $_SESSION['replication']['sr_action_info'] = sprintf(
                __('Unable to connect to primary %s.'),
                htmlspecialchars($sr['hostname'])
            );
        } else {
            // Read the current primary position
            $position = $this->replication->replicaBinLogPrimary(DatabaseInterface::CONNECT_AUXILIARY);

            if (empty($position)) {
                $_SESSION['replication']['sr_action_status'] = 'error';
                $_SESSION['replication']['sr_action_info'] = __(
                    'Unable to read primary log position. Possible privilege problem on primary.'
                );
            } else {
                $_SESSION['replication']['m_correct'] = true;

                if (
                    ! $this->replication->replicaChangePrimary(
                        $sr['username'],
                        $sr['pma_pw'],
                        $sr['hostname'],
                        $sr['port'],
                        $position,
                        true,
                        false,
                        DatabaseInterface::CONNECT_USER
                    )
                ) {
                    $_SESSION['replication']['sr_action_status'] = 'error';
                    $_SESSION['replication']['sr_action_info'] = __('Unable to change primary!');
                } else {
                    $_SESSION['replication']['sr_action_status'] = 'success';
                    $_SESSION['replication']['sr_action_info'] = sprintf(
                        __('Primary server changed successfully to %s.'),
                        htmlspecialchars($sr['hostname'])
                    );
                }
            }
        }

        return $_SESSION['replication']['sr_action_status'] === 'success';
    }

    public function handleRequestForReplicaServerControl(): bool
    {
        global $dbi;

        /** @var string|null $control */
        $control = $_POST['sr_replica_control_param'] ?? null;

        if ($_POST['sr_replica_action'] === 'reset') {
            $qStop = $this->replication->replicaControl('STOP', null, DatabaseInterface::CONNECT_USER);
            $qReset = $dbi->tryQuery('RESET SLAVE;');
            $qStart = $this->replication->replicaControl('START', null, DatabaseInterface::CONNECT_USER);

            $result = $qStop !== false && $qStop !== -1 &&
                $qReset !== false &&
                $qStart !== false && $qStart !== -1;
        } else {
            $qControl = $this->replication->replicaControl(
                $_POST['sr_replica_action'],
                $control,
                DatabaseInterface::CONNECT_USER
            );

            $result = $qControl !== false && $qControl !== -1;
        }

        return $result;
    }

    public function handleRequestForReplicaSkipError(): bool
    {
        global $dbi;

        $count = 1;
        if (isset($_POST['sr_skip_errors_count'])) {
            $count = $_POST['sr_skip_errors_count'] * 1;
        }

        $qStop = $this->replication->replicaControl('STOP', null, DatabaseInterface::CONNECT_USER);
        $qSkip = $dbi->tryQuery('SET GLOBAL SQL_SLAVE_SKIP_COUNTER = ' . $count . ';');
        $qStart = $this->replication->replicaControl('START', null, DatabaseInterface::CONNECT_USER);

        return $qStop !== false && $qStop !== -1 &&
            $qSkip !== false &&
            $qStart !== false && $qStart !== -1;
    }
}
