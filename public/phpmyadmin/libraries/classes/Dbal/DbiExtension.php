<?php
/**
 * Contract for every database extension supported by phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

/**
 * Contract for every database extension supported by phpMyAdmin
 */
interface DbiExtension
{
    /**
     * connects to the database server
     *
     * @param string $user     user name
     * @param string $password user password
     * @param array  $server   host/port/socket/persistent
     *
     * @return mixed false on error or a connection object on success
     */
    public function connect(
        $user,
        $password,
        array $server
    );

    /**
     * selects given database
     *
     * @param string|DatabaseName $databaseName database name to select
     * @param object              $link         connection object
     */
    public function selectDb($databaseName, $link): bool;

    /**
     * runs a query and returns the result
     *
     * @param string $query   query to execute
     * @param object $link    connection object
     * @param int    $options query options
     *
     * @return ResultInterface|false result
     */
    public function realQuery(string $query, $link, int $options);

    /**
     * Run the multi query and output the results
     *
     * @param object $link  connection object
     * @param string $query multi query statement to execute
     *
     * @return bool
     */
    public function realMultiQuery($link, $query);

    /**
     * Check if there are any more query results from a multi query
     *
     * @param object $link the connection object
     */
    public function moreResults($link): bool;

    /**
     * Prepare next result from multi_query
     *
     * @param object $link the connection object
     */
    public function nextResult($link): bool;

    /**
     * Store the result returned from multi query
     *
     * @param object $link mysql link
     *
     * @return ResultInterface|false false when empty results / result set when not empty
     */
    public function storeResult($link);

    /**
     * Returns a string representing the type of connection used
     *
     * @param object $link mysql link
     *
     * @return string type of connection used
     */
    public function getHostInfo($link);

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param object $link mysql link
     *
     * @return int|string version of the MySQL protocol used
     */
    public function getProtoInfo($link);

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo();

    /**
     * Returns last error message or an empty string if no errors occurred.
     *
     * @param object $link connection link
     */
    public function getError($link): string;

    /**
     * returns the number of rows affected by last query
     *
     * @param object $link the connection object
     *
     * @return int|string
     * @psalm-return int|numeric-string
     */
    public function affectedRows($link);

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param mixed  $link   database link
     * @param string $string string to be escaped
     *
     * @return string a MySQL escaped string
     */
    public function escapeString($link, $string);

    /**
     * Prepare an SQL statement for execution.
     *
     * @param mixed  $link  database link
     * @param string $query The query, as a string.
     *
     * @return object|false A statement object or false.
     */
    public function prepare($link, string $query);
}
