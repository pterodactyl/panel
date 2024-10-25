<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function json_encode;

/**
 * Table/Column autocomplete in SQL editors.
 */
class SqlAutoCompleteController extends AbstractController
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
        global $cfg, $db, $sql_autocomplete;

        $sql_autocomplete = true;
        if ($cfg['EnableAutocompleteForTablesAndColumns']) {
            $db = $_POST['db'] ?? $db;
            $sql_autocomplete = [];
            if ($db) {
                $tableNames = $this->dbi->getTables($db);
                foreach ($tableNames as $tableName) {
                    $sql_autocomplete[$tableName] = $this->dbi->getColumns($db, $tableName);
                }
            }
        }

        $this->response->addJSON(['tables' => json_encode($sql_autocomplete)]);
    }
}
