<?php
/**
 * PhpMyAdmin\Server\Status\Data class
 * Used by server_status_*.php pages
 */

declare(strict_types=1);

namespace PhpMyAdmin\Server\Status;

use PhpMyAdmin\ReplicationInfo;
use PhpMyAdmin\Url;

use function __;
use function basename;
use function mb_strtolower;
use function str_contains;

/**
 * This class provides data about the server status
 *
 * All properties of the class are read-only
 *
 * TODO: Use lazy initialisation for some of the properties
 *       since not all of the server_status_*.php pages need
 *       all the data that this class provides.
 */
class Data
{
    /** @var array */
    public $status;

    /** @var array */
    public $sections;

    /** @var array */
    public $variables;

    /** @var array */
    public $usedQueries;

    /** @var array */
    public $allocationMap;

    /** @var array */
    public $links;

    /** @var bool */
    public $dbIsLocal;

    /** @var mixed */
    public $section;

    /** @var array */
    public $sectionUsed;

    /** @var string */
    public $selfUrl;

    /** @var bool */
    public $dataLoaded;

    /** @var ReplicationInfo */
    private $replicationInfo;

    public function getReplicationInfo(): ReplicationInfo
    {
        return $this->replicationInfo;
    }

    /**
     * An empty setter makes the above properties read-only
     *
     * @param string $a key
     * @param mixed  $b value
     */
    public function __set($a, $b): void
    {
        // Discard everything
    }

    /**
     * Gets the allocations for constructor
     *
     * @return array
     */
    private function getAllocations()
    {
        return [
            // variable name => section
            // variable names match when they begin with the given string

            'Com_' => 'com',
            'Innodb_' => 'innodb',
            'Ndb_' => 'ndb',
            'Handler_' => 'handler',
            'Qcache_' => 'qcache',
            'Threads_' => 'threads',
            'Slow_launch_threads' => 'threads',

            'Binlog_cache_' => 'binlog_cache',
            'Created_tmp_' => 'created_tmp',
            'Key_' => 'key',

            'Delayed_' => 'delayed',
            'Not_flushed_delayed_rows' => 'delayed',

            'Flush_commands' => 'query',
            'Last_query_cost' => 'query',
            'Slow_queries' => 'query',
            'Queries' => 'query',
            'Prepared_stmt_count' => 'query',

            'Select_' => 'select',
            'Sort_' => 'sort',

            'Open_tables' => 'table',
            'Opened_tables' => 'table',
            'Open_table_definitions' => 'table',
            'Opened_table_definitions' => 'table',
            'Table_locks_' => 'table',

            'Rpl_status' => 'repl',
            'Slave_' => 'repl',

            'Tc_' => 'tc',

            'Ssl_' => 'ssl',

            'Open_files' => 'files',
            'Open_streams' => 'files',
            'Opened_files' => 'files',
        ];
    }

    /**
     * Gets the sections for constructor
     *
     * @return array
     */
    private function getSections()
    {
        return [
            // section => section name (description)
            'com' => 'Com',
            'query' => __('SQL query'),
            'innodb' => 'InnoDB',
            'ndb' => 'NDB',
            'handler' => __('Handler'),
            'qcache' => __('Query cache'),
            'threads' => __('Threads'),
            'binlog_cache' => __('Binary log'),
            'created_tmp' => __('Temporary data'),
            'delayed' => __('Delayed inserts'),
            'key' => __('Key cache'),
            'select' => __('Joins'),
            'repl' => __('Replication'),
            'sort' => __('Sorting'),
            'table' => __('Tables'),
            'tc' => __('Transaction coordinator'),
            'files' => __('Files'),
            'ssl' => 'SSL',
            'other' => __('Other'),
        ];
    }

    /**
     * Gets the links for constructor
     *
     * @return array
     */
    private function getLinks()
    {
        $primaryInfo = $this->replicationInfo->getPrimaryInfo();
        $replicaInfo = $this->replicationInfo->getReplicaInfo();

        $links = [];
        // variable or section name => (name => url)

        $links['table'][__('Flush (close) all tables')] = [
            'url' => $this->selfUrl,
            'params' => Url::getCommon(['flush' => 'TABLES'], ''),
        ];
        $links['table'][__('Show open tables')] = [
            'url' => Url::getFromRoute('/sql'),
            'params' => Url::getCommon([
                'sql_query' => 'SHOW OPEN TABLES',
                'goto' => $this->selfUrl,
            ], ''),
        ];

        if ($primaryInfo['status']) {
            $links['repl'][__('Show replica hosts')] = [
                'url' => Url::getFromRoute('/sql'),
                'params' => Url::getCommon([
                    'sql_query' => 'SHOW SLAVE HOSTS',
                    'goto' => $this->selfUrl,
                ], ''),
            ];
            $links['repl'][__('Show primary status')] = [
                'url' => '#replication_primary',
                'params' => '',
            ];
        }

        if ($replicaInfo['status']) {
            $links['repl'][__('Show replica status')] = [
                'url' => '#replication_replica',
                'params' => '',
            ];
        }

        $links['repl']['doc'] = 'replication';

        $links['qcache'][__('Flush query cache')] = [
            'url' => $this->selfUrl,
            'params' => Url::getCommon(['flush' => 'QUERY CACHE'], ''),
        ];
        $links['qcache']['doc'] = 'query_cache';

        $links['threads']['doc'] = 'mysql_threads';

        $links['key']['doc'] = 'myisam_key_cache';

        $links['binlog_cache']['doc'] = 'binary_log';

        $links['Slow_queries']['doc'] = 'slow_query_log';

        $links['innodb'][__('Variables')] = [
            'url' => Url::getFromRoute('/server/engines/InnoDB'),
            'params' => '',
        ];
        $links['innodb'][__('InnoDB Status')] = [
            'url' => Url::getFromRoute('/server/engines/InnoDB/Status'),
            'params' => '',
        ];
        $links['innodb']['doc'] = 'innodb';

        return $links;
    }

    /**
     * Calculate some values
     *
     * @param array $server_status    contains results of SHOW GLOBAL STATUS
     * @param array $server_variables contains results of SHOW GLOBAL VARIABLES
     *
     * @return array
     */
    private function calculateValues(array $server_status, array $server_variables)
    {
        // Key_buffer_fraction
        if (
            isset($server_status['Key_blocks_unused'], $server_variables['key_cache_block_size'])
            && isset($server_variables['key_buffer_size'])
            && $server_variables['key_buffer_size'] != 0
        ) {
            $server_status['Key_buffer_fraction_%'] = 100
                - $server_status['Key_blocks_unused']
                * $server_variables['key_cache_block_size']
                / $server_variables['key_buffer_size']
                * 100;
        } elseif (
            isset($server_status['Key_blocks_used'], $server_variables['key_buffer_size'])
            && $server_variables['key_buffer_size'] != 0
        ) {
            $server_status['Key_buffer_fraction_%'] = $server_status['Key_blocks_used']
                * 1024
                / $server_variables['key_buffer_size'];
        }

        // Ratio for key read/write
        if (
            isset($server_status['Key_writes'], $server_status['Key_write_requests'])
            && $server_status['Key_write_requests'] > 0
        ) {
            $key_writes = $server_status['Key_writes'];
            $key_write_requests = $server_status['Key_write_requests'];
            $server_status['Key_write_ratio_%'] = 100 * $key_writes / $key_write_requests;
        }

        if (
            isset($server_status['Key_reads'], $server_status['Key_read_requests'])
            && $server_status['Key_read_requests'] > 0
        ) {
            $key_reads = $server_status['Key_reads'];
            $key_read_requests = $server_status['Key_read_requests'];
            $server_status['Key_read_ratio_%'] = 100 * $key_reads / $key_read_requests;
        }

        // Threads_cache_hitrate
        if (
            isset($server_status['Threads_created'], $server_status['Connections'])
            && $server_status['Connections'] > 0
        ) {
            $server_status['Threads_cache_hitrate_%'] = 100 - $server_status['Threads_created']
                / $server_status['Connections'] * 100;
        }

        return $server_status;
    }

    /**
     * Sort variables into arrays
     *
     * @param array $server_status contains results of SHOW GLOBAL STATUS
     * @param array $allocations   allocations for sections
     * @param array $allocationMap map variables to their section
     * @param array $sectionUsed   is a section used?
     * @param array $used_queries  used queries
     *
     * @return array ($allocationMap, $sectionUsed, $used_queries)
     */
    private function sortVariables(
        array $server_status,
        array $allocations,
        array $allocationMap,
        array $sectionUsed,
        array $used_queries
    ) {
        foreach ($server_status as $name => $value) {
            $section_found = false;
            foreach ($allocations as $filter => $section) {
                if (! str_contains($name, $filter)) {
                    continue;
                }

                $allocationMap[$name] = $section;
                $sectionUsed[$section] = true;
                $section_found = true;
                if ($section === 'com' && $value > 0) {
                    $used_queries[$name] = $value;
                }

                break; // Only exits inner loop
            }

            if ($section_found) {
                continue;
            }

            $allocationMap[$name] = 'other';
            $sectionUsed['other'] = true;
        }

        return [
            $allocationMap,
            $sectionUsed,
            $used_queries,
        ];
    }

    public function __construct()
    {
        global $dbi;

        $this->replicationInfo = new ReplicationInfo($dbi);
        $this->replicationInfo->load($_POST['primary_connection'] ?? null);

        $this->selfUrl = basename($GLOBALS['PMA_PHP_SELF']);

        // get status from server
        $server_status_result = $dbi->tryQuery('SHOW GLOBAL STATUS');
        if ($server_status_result === false) {
            $server_status = [];
            $this->dataLoaded = false;
        } else {
            $this->dataLoaded = true;
            $server_status = $server_status_result->fetchAllKeyPair();
            unset($server_status_result);
        }

        // for some calculations we require also some server settings
        $server_variables = $dbi->fetchResult('SHOW GLOBAL VARIABLES', 0, 1);

        // cleanup of some deprecated values
        $server_status = self::cleanDeprecated($server_status);

        // calculate some values
        $server_status = $this->calculateValues($server_status, $server_variables);

        // split variables in sections
        $allocations = $this->getAllocations();

        $sections = $this->getSections();

        // define some needful links/commands
        $links = $this->getLinks();

        // Variable to contain all com_ variables (query statistics)
        $used_queries = [];

        // Variable to map variable names to their respective section name
        // (used for js category filtering)
        $allocationMap = [];

        // Variable to mark used sections
        $sectionUsed = [];

        // sort vars into arrays
        [
            $allocationMap,
            $sectionUsed,
            $used_queries,
        ] = $this->sortVariables($server_status, $allocations, $allocationMap, $sectionUsed, $used_queries);

        // admin commands are not queries (e.g. they include COM_PING,
        // which is excluded from $server_status['Questions'])
        unset($used_queries['Com_admin_commands']);

        // Set all class properties
        $this->dbIsLocal = false;
        // can be null if $cfg['ServerDefault'] = 0;
        $serverHostToLower = mb_strtolower((string) $GLOBALS['cfg']['Server']['host']);
        if (
            $serverHostToLower === 'localhost'
            || $GLOBALS['cfg']['Server']['host'] === '127.0.0.1'
            || $GLOBALS['cfg']['Server']['host'] === '::1'
        ) {
            $this->dbIsLocal = true;
        }

        $this->status = $server_status;
        $this->sections = $sections;
        $this->variables = $server_variables;
        $this->usedQueries = $used_queries;
        $this->allocationMap = $allocationMap;
        $this->links = $links;
        $this->sectionUsed = $sectionUsed;
    }

    /**
     * cleanup of some deprecated values
     *
     * @param array $server_status status array to process
     *
     * @return array
     */
    public static function cleanDeprecated(array $server_status)
    {
        $deprecated = [
            'Com_prepare_sql' => 'Com_stmt_prepare',
            'Com_execute_sql' => 'Com_stmt_execute',
            'Com_dealloc_sql' => 'Com_stmt_close',
        ];
        foreach ($deprecated as $old => $new) {
            if (! isset($server_status[$old], $server_status[$new])) {
                continue;
            }

            unset($server_status[$old]);
        }

        return $server_status;
    }
}
