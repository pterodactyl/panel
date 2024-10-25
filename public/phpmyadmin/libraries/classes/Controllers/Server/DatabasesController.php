<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ReplicationInfo;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_keys;
use function array_search;
use function count;
use function in_array;
use function mb_strtolower;
use function str_contains;
use function strlen;

/**
 * Handles viewing and creating and deleting databases
 */
class DatabasesController extends AbstractController
{
    /** @var array array of database details */
    private $databases = [];

    /** @var int number of databases */
    private $databaseCount = 0;

    /** @var string sort by column */
    private $sortBy;

    /** @var string sort order of databases */
    private $sortOrder;

    /** @var bool whether to show database statistics */
    private $hasStatistics;

    /** @var int position in list navigation */
    private $position;

    /** @var Transformations */
    private $transformations;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Transformations $transformations,
        RelationCleanup $relationCleanup,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->transformations = $transformations;
        $this->relationCleanup = $relationCleanup;
        $this->dbi = $dbi;

        $checkUserPrivileges = new CheckUserPrivileges($dbi);
        $checkUserPrivileges->getPrivileges();
    }

    public function __invoke(): void
    {
        global $cfg, $server, $dblist, $is_create_db_priv;
        global $db_to_create, $text_dir, $errorUrl;

        $params = [
            'statistics' => $_REQUEST['statistics'] ?? null,
            'pos' => $_REQUEST['pos'] ?? null,
            'sort_by' => $_REQUEST['sort_by'] ?? null,
            'sort_order' => $_REQUEST['sort_order'] ?? null,
        ];

        $this->addScriptFiles(['server/databases.js']);
        $errorUrl = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $replicationInfo = new ReplicationInfo($this->dbi);
        $replicationInfo->load($_POST['primary_connection'] ?? null);

        $primaryInfo = $replicationInfo->getPrimaryInfo();
        $replicaInfo = $replicationInfo->getReplicaInfo();

        $this->setSortDetails($params['sort_by'], $params['sort_order']);
        $this->hasStatistics = ! empty($params['statistics']);
        $this->position = ! empty($params['pos']) ? (int) $params['pos'] : 0;

        /**
         * Gets the databases list
         */
        if ($server > 0) {
            $this->databases = $this->dbi->getDatabasesFull(
                null,
                $this->hasStatistics,
                DatabaseInterface::CONNECT_USER,
                $this->sortBy,
                $this->sortOrder,
                $this->position,
                true
            );
            $this->databaseCount = count($dblist->databases);
        }

        $urlParams = [
            'statistics' => $this->hasStatistics,
            'pos' => $this->position,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ];

        $databases = $this->getDatabases($primaryInfo, $replicaInfo);

        $charsetsList = [];
        if ($cfg['ShowCreateDb'] && $is_create_db_priv) {
            $charsets = Charsets::getCharsets($this->dbi, $cfg['Server']['DisableIS']);
            $collations = Charsets::getCollations($this->dbi, $cfg['Server']['DisableIS']);
            $serverCollation = $this->dbi->getServerCollation();
            foreach ($charsets as $charset) {
                $collationsList = [];
                foreach ($collations[$charset->getName()] as $collation) {
                    $collationsList[] = [
                        'name' => $collation->getName(),
                        'description' => $collation->getDescription(),
                        'is_selected' => $serverCollation === $collation->getName(),
                    ];
                }

                $charsetsList[] = [
                    'name' => $charset->getName(),
                    'description' => $charset->getDescription(),
                    'collations' => $collationsList,
                ];
            }
        }

        $headerStatistics = $this->getStatisticsColumns();

        $this->render('server/databases/index', [
            'is_create_database_shown' => $cfg['ShowCreateDb'],
            'has_create_database_privileges' => $is_create_db_priv,
            'has_statistics' => $this->hasStatistics,
            'database_to_create' => $db_to_create,
            'databases' => $databases['databases'],
            'total_statistics' => $databases['total_statistics'],
            'header_statistics' => $headerStatistics,
            'charsets' => $charsetsList,
            'database_count' => $this->databaseCount,
            'pos' => $this->position,
            'url_params' => $urlParams,
            'max_db_list' => $cfg['MaxDbList'],
            'has_primary_replication' => $primaryInfo['status'],
            'has_replica_replication' => $replicaInfo['status'],
            'is_drop_allowed' => $this->dbi->isSuperUser() || $cfg['AllowUserDropDatabase'],
            'text_dir' => $text_dir,
        ]);
    }

    /**
     * Extracts parameters sort order and sort by
     *
     * @param string|null $sortBy    sort by
     * @param string|null $sortOrder sort order
     */
    private function setSortDetails(?string $sortBy, ?string $sortOrder): void
    {
        if (empty($sortBy)) {
            $this->sortBy = 'SCHEMA_NAME';
        } else {
            $sortByAllowList = [
                'SCHEMA_NAME',
                'DEFAULT_COLLATION_NAME',
                'SCHEMA_TABLES',
                'SCHEMA_TABLE_ROWS',
                'SCHEMA_DATA_LENGTH',
                'SCHEMA_INDEX_LENGTH',
                'SCHEMA_LENGTH',
                'SCHEMA_DATA_FREE',
            ];
            $this->sortBy = 'SCHEMA_NAME';
            if (in_array($sortBy, $sortByAllowList)) {
                $this->sortBy = $sortBy;
            }
        }

        $this->sortOrder = 'asc';
        if (! isset($sortOrder) || mb_strtolower($sortOrder) !== 'desc') {
            return;
        }

        $this->sortOrder = 'desc';
    }

    /**
     * @param array $primaryInfo
     * @param array $replicaInfo
     *
     * @return array
     */
    private function getDatabases($primaryInfo, $replicaInfo): array
    {
        global $cfg;

        $databases = [];
        $totalStatistics = $this->getStatisticsColumns();
        foreach ($this->databases as $database) {
            $replication = [
                'primary' => ['status' => $primaryInfo['status']],
                'replica' => ['status' => $replicaInfo['status']],
            ];

            if ($primaryInfo['status']) {
                $key = array_search($database['SCHEMA_NAME'], $primaryInfo['Ignore_DB']);
                $replication['primary']['is_replicated'] = false;

                if (strlen((string) $key) === 0) {
                    $key = array_search($database['SCHEMA_NAME'], $primaryInfo['Do_DB']);

                    if (strlen((string) $key) > 0 || count($primaryInfo['Do_DB']) === 0) {
                        $replication['primary']['is_replicated'] = true;
                    }
                }
            }

            if ($replicaInfo['status']) {
                $key = array_search($database['SCHEMA_NAME'], $replicaInfo['Ignore_DB']);
                $replication['replica']['is_replicated'] = false;

                if (strlen((string) $key) === 0) {
                    $key = array_search($database['SCHEMA_NAME'], $replicaInfo['Do_DB']);

                    if (strlen((string) $key) > 0 || count($replicaInfo['Do_DB']) === 0) {
                        $replication['replica']['is_replicated'] = true;
                    }
                }
            }

            $statistics = $this->getStatisticsColumns();
            if ($this->hasStatistics) {
                foreach (array_keys($statistics) as $key) {
                    $statistics[$key]['raw'] = (int) ($database[$key] ?? 0);
                    $totalStatistics[$key]['raw'] += (int) ($database[$key] ?? 0);
                }
            }

            $url = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
            $url .= Url::getCommonRaw(
                ['db' => $database['SCHEMA_NAME']],
                ! str_contains($url, '?') ? '?' : '&'
            );
            $databases[$database['SCHEMA_NAME']] = [
                'name' => $database['SCHEMA_NAME'],
                'collation' => [],
                'statistics' => $statistics,
                'replication' => $replication,
                'is_system_schema' => Utilities::isSystemSchema($database['SCHEMA_NAME'], true),
                'is_pmadb' => $database['SCHEMA_NAME'] === ($cfg['Server']['pmadb'] ?? ''),
                'url' => $url,
            ];
            $collation = Charsets::findCollationByName(
                $this->dbi,
                $cfg['Server']['DisableIS'],
                $database['DEFAULT_COLLATION_NAME']
            );
            if ($collation === null) {
                continue;
            }

            $databases[$database['SCHEMA_NAME']]['collation'] = [
                'name' => $collation->getName(),
                'description' => $collation->getDescription(),
            ];
        }

        return [
            'databases' => $databases,
            'total_statistics' => $totalStatistics,
        ];
    }

    /**
     * Prepares the statistics columns
     *
     * @return array
     */
    private function getStatisticsColumns(): array
    {
        return [
            'SCHEMA_TABLES' => [
                'title' => __('Tables'),
                'format' => 'number',
                'raw' => 0,
            ],
            'SCHEMA_TABLE_ROWS' => [
                'title' => __('Rows'),
                'format' => 'number',
                'raw' => 0,
            ],
            'SCHEMA_DATA_LENGTH' => [
                'title' => __('Data'),
                'format' => 'byte',
                'raw' => 0,
            ],
            'SCHEMA_INDEX_LENGTH' => [
                'title' => __('Indexes'),
                'format' => 'byte',
                'raw' => 0,
            ],
            'SCHEMA_LENGTH' => [
                'title' => __('Total'),
                'format' => 'byte',
                'raw' => 0,
            ],
            'SCHEMA_DATA_FREE' => [
                'title' => __('Overhead'),
                'format' => 'byte',
                'raw' => 0,
            ],
        ];
    }
}
