<?php
/**
 * Interface to the MySQL Improved extension (MySQLi)
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use mysqli;
use mysqli_stmt;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Query\Utilities;

use function __;
use function defined;
use function mysqli_connect_errno;
use function mysqli_connect_error;
use function mysqli_get_client_info;
use function mysqli_init;
use function mysqli_report;
use function sprintf;
use function stripos;
use function trigger_error;

use const E_USER_ERROR;
use const E_USER_WARNING;
use const MYSQLI_CLIENT_COMPRESS;
use const MYSQLI_CLIENT_SSL;
use const MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
use const MYSQLI_OPT_LOCAL_INFILE;
use const MYSQLI_OPT_SSL_VERIFY_SERVER_CERT;
use const MYSQLI_REPORT_OFF;
use const MYSQLI_STORE_RESULT;
use const MYSQLI_USE_RESULT;

/**
 * Interface to the MySQL Improved extension (MySQLi)
 */
class DbiMysqli implements DbiExtension
{
    /**
     * connects to the database server
     *
     * @param string $user     mysql user name
     * @param string $password mysql user password
     * @param array  $server   host/port/socket/persistent
     *
     * @return mysqli|bool false on error or a mysqli object on success
     */
    public function connect($user, $password, array $server)
    {
        if ($server) {
            $server['host'] = empty($server['host'])
                ? 'localhost'
                : $server['host'];
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        $mysqli = mysqli_init();

        if ($mysqli === false) {
            return false;
        }

        $client_flags = 0;

        /* Optionally compress connection */
        if ($server['compress'] && defined('MYSQLI_CLIENT_COMPRESS')) {
            $client_flags |= MYSQLI_CLIENT_COMPRESS;
        }

        /* Optionally enable SSL */
        if ($server['ssl']) {
            $client_flags |= MYSQLI_CLIENT_SSL;
            if (
                ! empty($server['ssl_key']) ||
                ! empty($server['ssl_cert']) ||
                ! empty($server['ssl_ca']) ||
                ! empty($server['ssl_ca_path']) ||
                ! empty($server['ssl_ciphers'])
            ) {
                $mysqli->ssl_set(
                    $server['ssl_key'] ?? '',
                    $server['ssl_cert'] ?? '',
                    $server['ssl_ca'] ?? '',
                    $server['ssl_ca_path'] ?? '',
                    $server['ssl_ciphers'] ?? ''
                );
            }

            /*
             * disables SSL certificate validation on mysqlnd for MySQL 5.6 or later
             * @link https://bugs.php.net/bug.php?id=68344
             * @link https://github.com/phpmyadmin/phpmyadmin/pull/11838
             */
            if (! $server['ssl_verify']) {
                $mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, (int) $server['ssl_verify']);
                $client_flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
            }
        }

        if ($GLOBALS['cfg']['PersistentConnections']) {
            $host = 'p:' . $server['host'];
        } else {
            $host = $server['host'];
        }

        if ($server['hide_connection_errors']) {
            $return_value = @$mysqli->real_connect(
                $host,
                $user,
                $password,
                '',
                $server['port'],
                (string) $server['socket'],
                $client_flags
            );
        } else {
            $return_value = $mysqli->real_connect(
                $host,
                $user,
                $password,
                '',
                $server['port'],
                (string) $server['socket'],
                $client_flags
            );
        }

        if ($return_value === false) {
            /*
             * Switch to SSL if server asked us to do so, unfortunately
             * there are more ways MySQL server can tell this:
             *
             * - MySQL 8.0 and newer should return error 3159
             * - #2001 - SSL Connection is required. Please specify SSL options and retry.
             * - #9002 - SSL connection is required. Please specify SSL options and retry.
             */
            // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            $error_number = $mysqli->connect_errno;
            $error_message = $mysqli->connect_error;
            // phpcs:enable
            if (
                ! $server['ssl']
                && ($error_number == 3159
                    || (($error_number == 2001 || $error_number == 9002)
                        && stripos($error_message, 'SSL Connection is required') !== false))
            ) {
                trigger_error(
                    __('SSL connection enforced by server, automatically enabling it.'),
                    E_USER_WARNING
                );
                $server['ssl'] = true;

                return self::connect($user, $password, $server);
            }

            if ($error_number === 1045 && $server['hide_connection_errors']) {
                trigger_error(
                    sprintf(
                        __(
                            'Error 1045: Access denied for user. Additional error information'
                            . ' may be available, but is being hidden by the %s configuration directive.'
                        ),
                        '[code][doc@cfg_Servers_hide_connection_errors]'
                        . '$cfg[\'Servers\'][$i][\'hide_connection_errors\'][/doc][/code]'
                    ),
                    E_USER_ERROR
                );
            }

            return false;
        }

        $mysqli->options(MYSQLI_OPT_LOCAL_INFILE, (int) defined('PMA_ENABLE_LDI'));

        return $mysqli;
    }

    /**
     * selects given database
     *
     * @param string|DatabaseName $databaseName database name to select
     * @param mysqli              $link         the mysqli object
     */
    public function selectDb($databaseName, $link): bool
    {
        return $link->select_db((string) $databaseName);
    }

    /**
     * runs a query and returns the result
     *
     * @param string $query   query to execute
     * @param mysqli $link    mysqli object
     * @param int    $options query options
     *
     * @return MysqliResult|false
     */
    public function realQuery(string $query, $link, int $options)
    {
        $method = MYSQLI_STORE_RESULT;
        if ($options == ($options | DatabaseInterface::QUERY_UNBUFFERED)) {
            $method = MYSQLI_USE_RESULT;
        }

        $result = $link->query($query, $method);
        if ($result === false) {
            return false;
        }

        return new MysqliResult($result);
    }

    /**
     * Run the multi query and output the results
     *
     * @param mysqli $link  mysqli object
     * @param string $query multi query statement to execute
     */
    public function realMultiQuery($link, $query): bool
    {
        return $link->multi_query($query);
    }

    /**
     * Check if there are any more query results from a multi query
     *
     * @param mysqli $link the mysqli object
     */
    public function moreResults($link): bool
    {
        return $link->more_results();
    }

    /**
     * Prepare next result from multi_query
     *
     * @param mysqli $link the mysqli object
     */
    public function nextResult($link): bool
    {
        return $link->next_result();
    }

    /**
     * Store the result returned from multi query
     *
     * @param mysqli $link the mysqli object
     *
     * @return MysqliResult|false false when empty results / result set when not empty
     */
    public function storeResult($link)
    {
        $result = $link->store_result();

        return $result === false ? false : new MysqliResult($result);
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @param mysqli $link mysql link
     *
     * @return string type of connection used
     */
    public function getHostInfo($link)
    {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return $link->host_info;
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param mysqli $link mysql link
     *
     * @return string version of the MySQL protocol used
     */
    public function getProtoInfo($link)
    {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return $link->protocol_version;
    }

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo()
    {
        return mysqli_get_client_info();
    }

    /**
     * Returns last error message or an empty string if no errors occurred.
     *
     * @param mysqli|false|null $link mysql link
     */
    public function getError($link): string
    {
        $GLOBALS['errno'] = 0;

        if ($link !== null && $link !== false) {
            $error_number = $link->errno;
            $error_message = $link->error;
        } else {
            $error_number = mysqli_connect_errno();
            $error_message = (string) mysqli_connect_error();
        }

        if ($error_number === 0 || $error_message === '') {
            return '';
        }

        // keep the error number for further check after
        // the call to getError()
        $GLOBALS['errno'] = $error_number;

        return Utilities::formatError($error_number, $error_message);
    }

    /**
     * returns the number of rows affected by last query
     *
     * @param mysqli $link the mysqli object
     *
     * @return int|string
     * @psalm-return int|numeric-string
     */
    public function affectedRows($link)
    {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return $link->affected_rows;
    }

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param mysqli $link   database link
     * @param string $string string to be escaped
     *
     * @return string a MySQL escaped string
     */
    public function escapeString($link, $string)
    {
        return $link->real_escape_string($string);
    }

    /**
     * Prepare an SQL statement for execution.
     *
     * @param mysqli $link  database link
     * @param string $query The query, as a string.
     *
     * @return mysqli_stmt|false A statement object or false.
     */
    public function prepare($link, string $query)
    {
        return $link->prepare($query);
    }
}
