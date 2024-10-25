<?php
/**
 * Replication helpers
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Dbal\ResultInterface;

use function explode;
use function mb_strtoupper;

/**
 * PhpMyAdmin\Replication class
 */
class Replication
{
    /**
     * Extracts database or table name from string
     *
     * @param string $string contains "dbname.tablename"
     * @param string $what   what to extract (db|table)
     *
     * @return string the extracted part
     */
    public function extractDbOrTable($string, $what = 'db')
    {
        $list = explode('.', $string);
        if ($what === 'db') {
            return $list[0];
        }

        return $list[1];
    }

    /**
     * Configures replication replica
     *
     * @param string      $action  possible values: START or STOP
     * @param string|null $control default: null,
     *                             possible values: SQL_THREAD or IO_THREAD or null.
     *                             If it is set to null, it controls both
     *                             SQL_THREAD and IO_THREAD
     * @param int         $link    mysql link
     *
     * @return ResultInterface|false|int output of DatabaseInterface::tryQuery
     */
    public function replicaControl(string $action, ?string $control, int $link)
    {
        global $dbi;

        $action = mb_strtoupper($action);
        $control = $control !== null ? mb_strtoupper($control) : '';

        if ($action !== 'START' && $action !== 'STOP') {
            return -1;
        }

        if ($control !== 'SQL_THREAD' && $control !== 'IO_THREAD' && $control != null) {
            return -1;
        }

        return $dbi->tryQuery($action . ' SLAVE ' . $control . ';', $link);
    }

    /**
     * Changes primary for replication replica
     *
     * @param string $user     replication user on primary
     * @param string $password password for the user
     * @param string $host     primary's hostname or IP
     * @param int    $port     port, where mysql is running
     * @param array  $pos      position of mysql replication, array should contain fields File and Position
     * @param bool   $stop     shall we stop replica?
     * @param bool   $start    shall we start replica?
     * @param int    $link     mysql link
     *
     * @return ResultInterface|false output of CHANGE MASTER mysql command
     */
    public function replicaChangePrimary(
        $user,
        $password,
        $host,
        $port,
        array $pos,
        bool $stop,
        bool $start,
        int $link
    ) {
        global $dbi;

        if ($stop) {
            $this->replicaControl('STOP', null, $link);
        }

        $out = $dbi->tryQuery(
            'CHANGE MASTER TO ' .
            'MASTER_HOST=\'' . $host . '\',' .
            'MASTER_PORT=' . ($port * 1) . ',' .
            'MASTER_USER=\'' . $user . '\',' .
            'MASTER_PASSWORD=\'' . $password . '\',' .
            'MASTER_LOG_FILE=\'' . $pos['File'] . '\',' .
            'MASTER_LOG_POS=' . $pos['Position'] . ';',
            $link
        );

        if ($start) {
            $this->replicaControl('START', null, $link);
        }

        return $out;
    }

    /**
     * This function provides connection to remote mysql server
     *
     * @param string $user     mysql username
     * @param string $password password for the user
     * @param string $host     mysql server's hostname or IP
     * @param int    $port     mysql remote port
     * @param string $socket   path to unix socket
     *
     * @return mixed mysql link on success
     */
    public function connectToPrimary(
        $user,
        $password,
        $host = null,
        $port = null,
        $socket = null
    ) {
        global $dbi;

        $server = [];
        $server['user'] = $user;
        $server['password'] = $password;
        $server['host'] = Core::sanitizeMySQLHost($host);
        $server['port'] = $port;
        $server['socket'] = $socket;

        // 5th parameter set to true means that it's an auxiliary connection
        // and we must not go back to login page if it fails
        return $dbi->connect(DatabaseInterface::CONNECT_AUXILIARY, $server);
    }

    /**
     * Fetches position and file of current binary log on primary
     *
     * @param int $link mysql link
     *
     * @return array an array containing File and Position in MySQL replication
     * on primary server, useful for {@see Replication::replicaChangePrimary()}.
     * @phpstan-return array{'File'?: string, 'Position'?: string}
     */
    public function replicaBinLogPrimary(int $link): array
    {
        global $dbi;

        $data = $dbi->fetchResult('SHOW MASTER STATUS', null, null, $link);
        $output = [];

        if (! empty($data)) {
            $output['File'] = $data[0]['File'];
            $output['Position'] = $data[0]['Position'];
        }

        return $output;
    }
}
