<?php

declare(strict_types=1);

use FastRoute\RouteCollector;
use PhpMyAdmin\Controllers\BrowseForeignersController;
use PhpMyAdmin\Controllers\ChangeLogController;
use PhpMyAdmin\Controllers\CheckRelationsController;
use PhpMyAdmin\Controllers\CollationConnectionController;
use PhpMyAdmin\Controllers\ColumnController;
use PhpMyAdmin\Controllers\Config;
use PhpMyAdmin\Controllers\Database;
use PhpMyAdmin\Controllers\DatabaseController;
use PhpMyAdmin\Controllers\ErrorReportController;
use PhpMyAdmin\Controllers\Export;
use PhpMyAdmin\Controllers\GisDataEditorController;
use PhpMyAdmin\Controllers\GitInfoController;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Controllers\Import;
use PhpMyAdmin\Controllers\LicenseController;
use PhpMyAdmin\Controllers\LintController;
use PhpMyAdmin\Controllers\LogoutController;
use PhpMyAdmin\Controllers\NavigationController;
use PhpMyAdmin\Controllers\NormalizationController;
use PhpMyAdmin\Controllers\PhpInfoController;
use PhpMyAdmin\Controllers\Preferences;
use PhpMyAdmin\Controllers\RecentTablesListController;
use PhpMyAdmin\Controllers\SchemaExportController;
use PhpMyAdmin\Controllers\Server;
use PhpMyAdmin\Controllers\Sql;
use PhpMyAdmin\Controllers\Table;
use PhpMyAdmin\Controllers\TableController;
use PhpMyAdmin\Controllers\ThemesController;
use PhpMyAdmin\Controllers\ThemeSetController;
use PhpMyAdmin\Controllers\Transformation;
use PhpMyAdmin\Controllers\UserPasswordController;
use PhpMyAdmin\Controllers\VersionCheckController;
use PhpMyAdmin\Controllers\View;

if (! defined('PHPMYADMIN')) {
    exit;
}

return static function (RouteCollector $routes): void {
    $routes->addRoute(['GET', 'POST'], '[/]', HomeController::class);
    $routes->addRoute(['GET', 'POST'], '/browse-foreigners', BrowseForeignersController::class);
    $routes->get('/changelog', ChangeLogController::class);
    $routes->addRoute(['GET', 'POST'], '/check-relations', CheckRelationsController::class);
    $routes->post('/collation-connection', CollationConnectionController::class);
    $routes->post('/columns', ColumnController::class);
    $routes->addGroup('/config', static function (RouteCollector $routes): void {
        $routes->post('/get', Config\GetConfigController::class);
        $routes->post('/set', Config\SetConfigController::class);
    });
    $routes->addGroup('/database', static function (RouteCollector $routes): void {
        $routes->addGroup('/central-columns', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Database\CentralColumnsController::class);
            $routes->post('/populate', Database\CentralColumns\PopulateColumnsController::class);
        });
        $routes->get('/data-dictionary', Database\DataDictionaryController::class);
        $routes->addRoute(['GET', 'POST'], '/designer', Database\DesignerController::class);
        $routes->addRoute(['GET', 'POST'], '/events', Database\EventsController::class);
        $routes->addRoute(['GET', 'POST'], '/export', Database\ExportController::class);
        $routes->addRoute(['GET', 'POST'], '/import', Database\ImportController::class);
        $routes->addGroup('/multi-table-query', static function (RouteCollector $routes): void {
            $routes->get('', Database\MultiTableQueryController::class);
            $routes->get('/tables', Database\MultiTableQuery\TablesController::class);
            $routes->post('/query', Database\MultiTableQuery\QueryController::class);
        });
        $routes->addGroup('/operations', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Database\OperationsController::class);
            $routes->post('/collation', Database\Operations\CollationController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/qbe', Database\QueryByExampleController::class);
        $routes->addRoute(['GET', 'POST'], '/routines', Database\RoutinesController::class);
        $routes->addRoute(['GET', 'POST'], '/search', Database\SearchController::class);
        $routes->addGroup('/sql', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Database\SqlController::class);
            $routes->post('/autocomplete', Database\SqlAutoCompleteController::class);
            $routes->post('/format', Database\SqlFormatController::class);
        });
        $routes->addGroup('/structure', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Database\StructureController::class);
            $routes->post('/add-prefix', Database\Structure\AddPrefixController::class);
            $routes->post('/add-prefix-table', Database\Structure\AddPrefixTableController::class);
            $routes->addGroup('/central-columns', static function (RouteCollector $routes): void {
                $routes->post('/add', Database\Structure\CentralColumns\AddController::class);
                $routes->post('/make-consistent', Database\Structure\CentralColumns\MakeConsistentController::class);
                $routes->post('/remove', Database\Structure\CentralColumns\RemoveController::class);
            });
            $routes->post('/change-prefix-form', Database\Structure\ChangePrefixFormController::class);
            $routes->post('/copy-form', Database\Structure\CopyFormController::class);
            $routes->post('/copy-table', Database\Structure\CopyTableController::class);
            $routes->post('/copy-table-with-prefix', Database\Structure\CopyTableWithPrefixController::class);
            $routes->post('/drop-form', Database\Structure\DropFormController::class);
            $routes->post('/drop-table', Database\Structure\DropTableController::class);
            $routes->post('/empty-form', Database\Structure\EmptyFormController::class);
            $routes->post('/empty-table', Database\Structure\EmptyTableController::class);
            $routes->post('/favorite-table', Database\Structure\FavoriteTableController::class);
            $routes->addRoute(['GET', 'POST'], '/real-row-count', Database\Structure\RealRowCountController::class);
            $routes->post('/replace-prefix', Database\Structure\ReplacePrefixController::class);
            $routes->post('/show-create', Database\Structure\ShowCreateController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/tracking', Database\TrackingController::class);
        $routes->addRoute(['GET', 'POST'], '/triggers', Database\TriggersController::class);
    });
    $routes->post('/databases', DatabaseController::class);
    $routes->addRoute(['GET', 'POST'], '/error-report', ErrorReportController::class);
    $routes->addGroup('/export', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '', Export\ExportController::class);
        $routes->get('/check-time-out', Export\CheckTimeOutController::class);
        $routes->post('/tables', Export\TablesController::class);
        $routes->addGroup('/template', static function (RouteCollector $routes): void {
            $routes->post('/create', Export\Template\CreateController::class);
            $routes->post('/delete', Export\Template\DeleteController::class);
            $routes->post('/load', Export\Template\LoadController::class);
            $routes->post('/update', Export\Template\UpdateController::class);
        });
    });
    $routes->addRoute(['GET', 'POST'], '/gis-data-editor', GisDataEditorController::class);
    $routes->addRoute(['GET', 'POST'], '/git-revision', GitInfoController::class);
    $routes->addGroup('/import', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '', Import\ImportController::class);
        $routes->post('/simulate-dml', Import\SimulateDmlController::class);
    });
    $routes->addRoute(['GET', 'POST'], '/import-status', Import\StatusController::class);
    $routes->get('/license', LicenseController::class);
    $routes->addRoute(['GET', 'POST'], '/lint', LintController::class);
    $routes->addRoute(['GET', 'POST'], '/logout', LogoutController::class);
    $routes->addRoute(['GET', 'POST'], '/navigation', NavigationController::class);
    $routes->addRoute(['GET', 'POST'], '/normalization', NormalizationController::class);
    $routes->get('/phpinfo', PhpInfoController::class);
    $routes->addGroup('/preferences', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/export', Preferences\ExportController::class);
        $routes->addRoute(['GET', 'POST'], '/features', Preferences\FeaturesController::class);
        $routes->addRoute(['GET', 'POST'], '/import', Preferences\ImportController::class);
        $routes->addRoute(['GET', 'POST'], '/main-panel', Preferences\MainPanelController::class);
        $routes->addRoute(['GET', 'POST'], '/manage', Preferences\ManageController::class);
        $routes->addRoute(['GET', 'POST'], '/navigation', Preferences\NavigationController::class);
        $routes->addRoute(['GET', 'POST'], '/sql', Preferences\SqlController::class);
        $routes->addRoute(['GET', 'POST'], '/two-factor', Preferences\TwoFactorController::class);
    });
    $routes->addRoute(['GET', 'POST'], '/recent-table', RecentTablesListController::class);
    $routes->addRoute(['GET', 'POST'], '/schema-export', SchemaExportController::class);
    $routes->addGroup('/server', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/binlog', Server\BinlogController::class);
        $routes->get('/collations', Server\CollationsController::class);
        $routes->addGroup('/databases', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Server\DatabasesController::class);
            $routes->post('/create', Server\Databases\CreateController::class);
            $routes->post('/destroy', Server\Databases\DestroyController::class);
        });
        $routes->addGroup('/engines', static function (RouteCollector $routes): void {
            $routes->get('', Server\EnginesController::class);
            $routes->get('/{engine}[/{page}]', Server\ShowEngineController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/export', Server\ExportController::class);
        $routes->addRoute(['GET', 'POST'], '/import', Server\ImportController::class);
        $routes->get('/plugins', Server\PluginsController::class);
        $routes->addGroup('/privileges', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Server\PrivilegesController::class);
            $routes->post('/account-lock', Server\Privileges\AccountLockController::class);
            $routes->post('/account-unlock', Server\Privileges\AccountUnlockController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/replication', Server\ReplicationController::class);
        $routes->addRoute(['GET', 'POST'], '/sql', Server\SqlController::class);
        $routes->addGroup('/status', static function (RouteCollector $routes): void {
            $routes->get('', Server\Status\StatusController::class);
            $routes->get('/advisor', Server\Status\AdvisorController::class);
            $routes->addGroup('/monitor', static function (RouteCollector $routes): void {
                $routes->get('', Server\Status\MonitorController::class);
                $routes->post('/chart', Server\Status\Monitor\ChartingDataController::class);
                $routes->post('/slow-log', Server\Status\Monitor\SlowLogController::class);
                $routes->post('/general-log', Server\Status\Monitor\GeneralLogController::class);
                $routes->post('/log-vars', Server\Status\Monitor\LogVarsController::class);
                $routes->post('/query', Server\Status\Monitor\QueryAnalyzerController::class);
            });
            $routes->addGroup('/processes', static function (RouteCollector $routes): void {
                $routes->addRoute(['GET', 'POST'], '', Server\Status\ProcessesController::class);
                $routes->post('/refresh', Server\Status\Processes\RefreshController::class);
                $routes->post('/kill/{id:\d+}', Server\Status\Processes\KillController::class);
            });
            $routes->get('/queries', Server\Status\QueriesController::class);
            $routes->addRoute(['GET', 'POST'], '/variables', Server\Status\VariablesController::class);
        });
        $routes->addGroup('/user-groups', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Server\UserGroupsController::class);
            $routes->get('/edit-form', Server\UserGroupsFormController::class);
        });
        $routes->addGroup('/variables', static function (RouteCollector $routes): void {
            $routes->get('', Server\VariablesController::class);
            $routes->get('/get/{name}', Server\Variables\GetVariableController::class);
            $routes->post('/set/{name}', Server\Variables\SetVariableController::class);
        });
    });
    $routes->addGroup('/sql', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '', Sql\SqlController::class);
        $routes->post('/get-relational-values', Sql\RelationalValuesController::class);
        $routes->post('/get-enum-values', Sql\EnumValuesController::class);
        $routes->post('/get-set-values', Sql\SetValuesController::class);
        $routes->get('/get-default-fk-check-value', Sql\DefaultForeignKeyCheckValueController::class);
        $routes->post('/set-column-preferences', Sql\ColumnPreferencesController::class);
    });
    $routes->addGroup('/table', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/add-field', Table\AddFieldController::class);
        $routes->addGroup('/change', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Table\ChangeController::class);
            $routes->post('/rows', Table\ChangeRowsController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/chart', Table\ChartController::class);
        $routes->addRoute(['GET', 'POST'], '/create', Table\CreateController::class);
        $routes->addGroup('/delete', static function (RouteCollector $routes): void {
            $routes->post('/confirm', Table\DeleteConfirmController::class);
            $routes->post('/rows', Table\DeleteRowsController::class);
        });
        $routes->addGroup('/export', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Table\ExportController::class);
            $routes->post('/rows', Table\ExportRowsController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/find-replace', Table\FindReplaceController::class);
        $routes->addRoute(['GET', 'POST'], '/get-field', Table\GetFieldController::class);
        $routes->addRoute(['GET', 'POST'], '/gis-visualization', Table\GisVisualizationController::class);
        $routes->addRoute(['GET', 'POST'], '/import', Table\ImportController::class);
        $routes->addRoute(['GET', 'POST'], '/indexes', Table\IndexesController::class);
        $routes->addRoute(['GET', 'POST'], '/indexes/rename', Table\IndexRenameController::class);
        $routes->addGroup('/maintenance', static function (RouteCollector $routes): void {
            $routes->post('/analyze', Table\Maintenance\AnalyzeController::class);
            $routes->post('/check', Table\Maintenance\CheckController::class);
            $routes->post('/checksum', Table\Maintenance\ChecksumController::class);
            $routes->post('/optimize', Table\Maintenance\OptimizeController::class);
            $routes->post('/repair', Table\Maintenance\RepairController::class);
        });
        $routes->addGroup('/partition', static function (RouteCollector $routes): void {
            $routes->post('/analyze', Table\Partition\AnalyzeController::class);
            $routes->post('/check', Table\Partition\CheckController::class);
            $routes->post('/drop', Table\Partition\DropController::class);
            $routes->post('/optimize', Table\Partition\OptimizeController::class);
            $routes->post('/rebuild', Table\Partition\RebuildController::class);
            $routes->post('/repair', Table\Partition\RepairController::class);
            $routes->post('/truncate', Table\Partition\TruncateController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/operations', Table\OperationsController::class);
        $routes->addRoute(['GET', 'POST'], '/recent-favorite', Table\RecentFavoriteController::class);
        $routes->addRoute(['GET', 'POST'], '/relation', Table\RelationController::class);
        $routes->addRoute(['GET', 'POST'], '/replace', Table\ReplaceController::class);
        $routes->addRoute(['GET', 'POST'], '/search', Table\SearchController::class);
        $routes->addRoute(['GET', 'POST'], '/sql', Table\SqlController::class);
        $routes->addGroup('/structure', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Table\StructureController::class);
            $routes->post('/add-key', Table\Structure\AddKeyController::class);
            $routes->post('/browse', Table\Structure\BrowseController::class);
            $routes->post('/central-columns-add', Table\Structure\CentralColumnsAddController::class);
            $routes->post('/central-columns-remove', Table\Structure\CentralColumnsRemoveController::class);
            $routes->addRoute(['GET', 'POST'], '/change', Table\Structure\ChangeController::class);
            $routes->post('/drop', Table\DropColumnController::class);
            $routes->post('/drop-confirm', Table\DropColumnConfirmationController::class);
            $routes->post('/fulltext', Table\Structure\FulltextController::class);
            $routes->post('/index', Table\Structure\AddIndexController::class);
            $routes->post('/move-columns', Table\Structure\MoveColumnsController::class);
            $routes->post('/partitioning', Table\Structure\PartitioningController::class);
            $routes->post('/primary', Table\Structure\PrimaryController::class);
            $routes->post('/reserved-word-check', Table\Structure\ReservedWordCheckController::class);
            $routes->post('/save', Table\Structure\SaveController::class);
            $routes->post('/spatial', Table\Structure\SpatialController::class);
            $routes->post('/unique', Table\Structure\UniqueController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/tracking', Table\TrackingController::class);
        $routes->addRoute(['GET', 'POST'], '/triggers', Table\TriggersController::class);
        $routes->addRoute(['GET', 'POST'], '/zoom-search', Table\ZoomSearchController::class);
    });
    $routes->post('/tables', TableController::class);
    $routes->addGroup('/themes', static function (RouteCollector $routes): void {
        $routes->get('', ThemesController::class);
        $routes->post('/set', ThemeSetController::class);
    });
    $routes->addGroup('/transformation', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/overview', Transformation\OverviewController::class);
        $routes->addRoute(['GET', 'POST'], '/wrapper', Transformation\WrapperController::class);
    });
    $routes->addRoute(['GET', 'POST'], '/user-password', UserPasswordController::class);
    $routes->addRoute(['GET', 'POST'], '/version-check', VersionCheckController::class);
    $routes->addGroup('/view', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/create', View\CreateController::class);
        $routes->addRoute(['GET', 'POST'], '/operations', View\OperationsController::class);
    });
};
