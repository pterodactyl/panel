<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\Database\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Handles request for real row count on database level view page.
 */
final class RealRowCountController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, string $db, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template, $db);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $cfg, $db, $errorUrl;

        $parameters = [
            'real_row_count_all' => $_REQUEST['real_row_count_all'] ?? null,
            'table' => $_REQUEST['table'] ?? null,
        ];

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase() || ! $this->response->isAjax()) {
            return;
        }

        [$tables] = Util::getDbInfo($this->db, '_structure');

        // If there is a request to update all table's row count.
        if (! isset($parameters['real_row_count_all'])) {
            // Get the real row count for the table.
            $realRowCount = (int) $this->dbi
                ->getTable($this->db, (string) $parameters['table'])
                ->getRealRowCountTable();
            // Format the number.
            $realRowCount = Util::formatNumber($realRowCount, 0);

            $this->response->addJSON(['real_row_count' => $realRowCount]);

            return;
        }

        // Array to store the results.
        $realRowCountAll = [];
        // Iterate over each table and fetch real row count.
        foreach ($tables as $table) {
            $rowCount = $this->dbi
                ->getTable($this->db, $table['TABLE_NAME'])
                ->getRealRowCountTable();
            $realRowCountAll[] = [
                'table' => $table['TABLE_NAME'],
                'row_count' => Util::formatNumber($rowCount, 0),
            ];
        }

        $this->response->addJSON(['real_row_count_all' => $realRowCountAll]);
    }
}
