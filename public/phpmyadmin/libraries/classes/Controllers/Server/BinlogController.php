<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function array_key_exists;

/**
 * Handles viewing binary logs
 */
class BinlogController extends AbstractController
{
    /**
     * binary log files
     *
     * @var array
     */
    protected $binaryLogs;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
        $this->dbi = $dbi;

        $this->binaryLogs = $this->dbi->fetchResult(
            'SHOW MASTER LOGS',
            'Log_name'
        );
    }

    public function __invoke(): void
    {
        global $cfg, $errorUrl;

        $params = [
            'log' => $_POST['log'] ?? null,
            'pos' => $_POST['pos'] ?? null,
            'is_full_query' => $_POST['is_full_query'] ?? null,
        ];
        $errorUrl = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $position = ! empty($params['pos']) ? (int) $params['pos'] : 0;

        $urlParams = [];
        if (isset($params['log']) && array_key_exists($params['log'], $this->binaryLogs)) {
            $urlParams['log'] = $params['log'];
        }

        $isFullQuery = false;
        if (! empty($params['is_full_query'])) {
            $isFullQuery = true;
            $urlParams['is_full_query'] = 1;
        }

        $sqlQuery = $this->getSqlQuery($params['log'] ?? '', $position, (int) $cfg['MaxRows']);
        $result = $this->dbi->query($sqlQuery);

        $numRows = $result->numRows();

        $previousParams = $urlParams;
        $fullQueriesParams = $urlParams;
        $nextParams = $urlParams;
        if ($position > 0) {
            $fullQueriesParams['pos'] = $position;
            if ($position > $cfg['MaxRows']) {
                $previousParams['pos'] = $position - $cfg['MaxRows'];
            }
        }

        $fullQueriesParams['is_full_query'] = 1;
        if ($isFullQuery) {
            unset($fullQueriesParams['is_full_query']);
        }

        if ($numRows >= $cfg['MaxRows']) {
            $nextParams['pos'] = $position + $cfg['MaxRows'];
        }

        $values = $result->fetchAllAssoc();

        $this->render('server/binlog/index', [
            'url_params' => $urlParams,
            'binary_logs' => $this->binaryLogs,
            'log' => $params['log'],
            'sql_message' => Generator::getMessage(Message::success(), $sqlQuery),
            'values' => $values,
            'has_previous' => $position > 0,
            'has_next' => $numRows >= $cfg['MaxRows'],
            'previous_params' => $previousParams,
            'full_queries_params' => $fullQueriesParams,
            'next_params' => $nextParams,
            'has_icons' => Util::showIcons('TableNavigationLinksMode'),
            'is_full_query' => $isFullQuery,
        ]);
    }

    /**
     * @param string $log      Binary log file name
     * @param int    $position Position to display
     * @param int    $maxRows  Maximum number of rows
     */
    private function getSqlQuery(
        string $log,
        int $position,
        int $maxRows
    ): string {
        $sqlQuery = 'SHOW BINLOG EVENTS';
        if (! empty($log)) {
            $sqlQuery .= ' IN \'' . $log . '\'';
        }

        $sqlQuery .= ' LIMIT ' . $position . ', ' . $maxRows;

        return $sqlQuery;
    }
}
