<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Database\MultiTableQuery;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

/**
 * Handles database multi-table querying
 */
class MultiTableQueryController extends AbstractController
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
        $this->addScriptFiles([
            'database/multi_table_query.js',
            'database/query_generator.js',
        ]);

        $queryInstance = new MultiTableQuery($this->dbi, $this->template, $this->db);

        $this->response->addHTML($queryInstance->getFormHtml());
    }
}
