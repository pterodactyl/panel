<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\MultiTableQuery;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function rtrim;

final class TablesController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $params = [
            'tables' => $_GET['tables'] ?? [],
            'db' => $_GET['db'] ?? '',
        ];

        $tablesListForQuery = '';
        foreach ($params['tables'] as $table) {
            $tablesListForQuery .= "'" . $this->dbi->escapeString($table) . "',";
        }

        $tablesListForQuery = rtrim($tablesListForQuery, ',');

        $constrains = $this->dbi->fetchResult(
            QueryGenerator::getInformationSchemaForeignKeyConstraintsRequest(
                $this->dbi->escapeString($params['db']),
                $tablesListForQuery
            )
        );
        $this->response->addJSON(['foreignKeyConstrains' => $constrains]);
    }
}
