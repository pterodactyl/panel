<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use const SQL_DIR;

/**
 * Displays status of phpMyAdmin configuration storage
 */
class CheckRelationsController extends AbstractController
{
    /** @var Relation */
    private $relation;

    public function __construct(ResponseRenderer $response, Template $template, Relation $relation)
    {
        parent::__construct($response, $template);
        $this->relation = $relation;
    }

    public function __invoke(ServerRequest $request): void
    {
        global $db, $cfg;

        /** @var string|null $createPmaDb */
        $createPmaDb = $request->getParsedBodyParam('create_pmadb');
        /** @var string|null $fixAllPmaDb */
        $fixAllPmaDb = $request->getParsedBodyParam('fixall_pmadb');
        /** @var string|null $fixPmaDb */
        $fixPmaDb = $request->getParsedBodyParam('fix_pmadb');

        $cfgStorageDbName = $this->relation->getConfigurationStorageDbName();

        // If request for creating the pmadb
        if (isset($createPmaDb) && $this->relation->createPmaDatabase($cfgStorageDbName)) {
            $this->relation->fixPmaTables($cfgStorageDbName);
        }

        // If request for creating all PMA tables.
        if (isset($fixAllPmaDb)) {
            $this->relation->fixPmaTables($db);
        }

        // If request for creating missing PMA tables.
        if (isset($fixPmaDb)) {
            $relationParameters = $this->relation->getRelationParameters();
            $this->relation->fixPmaTables((string) $relationParameters->db);
        }

        // Do not use any previous $relationParameters value as it could have changed after a successful fixPmaTables()
        $relationParameters = $this->relation->getRelationParameters();

        $this->render('relation/check_relations', [
            'db' => $db,
            'zero_conf' => $cfg['ZeroConf'],
            'relation_parameters' => $relationParameters->toArray(),
            'sql_dir' => SQL_DIR,
            'config_storage_database_name' => $cfgStorageDbName,
            'are_config_storage_tables_defined' => $this->relation->arePmadbTablesDefined(),
        ]);
    }
}
