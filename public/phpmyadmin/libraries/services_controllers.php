<?php

declare(strict_types=1);

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
use PhpMyAdmin\Controllers\JavaScriptMessagesController;
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

return [
    'services' => [
        BrowseForeignersController::class => [
            'class' => BrowseForeignersController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$browseForeigners' => '@browse_foreigners',
                '$relation' => '@relation',
            ],
        ],
        ChangeLogController::class => [
            'class' => ChangeLogController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        CheckRelationsController::class => [
            'class' => CheckRelationsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
            ],
        ],
        CollationConnectionController::class => [
            'class' => CollationConnectionController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$config' => '@config',
            ],
        ],
        ColumnController::class => [
            'class' => ColumnController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        Config\GetConfigController::class => [
            'class' => Config\GetConfigController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$config' => '@config',
            ],
        ],
        Config\SetConfigController::class => [
            'class' => Config\SetConfigController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$config' => '@config',
            ],
        ],
        Database\CentralColumns\PopulateColumnsController::class => [
            'class' => Database\CentralColumns\PopulateColumnsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$centralColumns' => '@central_columns',
            ],
        ],
        Database\CentralColumnsController::class => [
            'class' => Database\CentralColumnsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$centralColumns' => '@central_columns',
            ],
        ],
        Database\DataDictionaryController::class => [
            'class' => Database\DataDictionaryController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$relation' => '@relation',
                '$transformations' => '@transformations',
                '$dbi' => '@dbi',
            ],
        ],
        Database\DesignerController::class => [
            'class' => Database\DesignerController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$databaseDesigner' => '@designer',
                '$designerCommon' => '@designer_common',
            ],
        ],
        Database\EventsController::class => [
            'class' => Database\EventsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$events' => '@events',
                '$dbi' => '@dbi',
            ],
        ],
        Database\ExportController::class => [
            'class' => Database\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$export' => '@export',
                '$exportOptions' => '@export_options',
            ],
        ],
        Database\ImportController::class => [
            'class' => Database\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        Database\MultiTableQuery\QueryController::class => [
            'class' => Database\MultiTableQuery\QueryController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        Database\MultiTableQuery\TablesController::class => [
            'class' => Database\MultiTableQuery\TablesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        Database\MultiTableQueryController::class => [
            'class' => Database\MultiTableQueryController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        Database\Operations\CollationController::class => [
            'class' => Database\Operations\CollationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$operations' => '@operations',
                '$dbi' => '@dbi',
            ],
        ],
        Database\OperationsController::class => [
            'class' => Database\OperationsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$operations' => '@operations',
                '$checkUserPrivileges' => '@check_user_privileges',
                '$relation' => '@relation',
                '$relationCleanup' => '@relation_cleanup',
                '$dbi' => '@dbi',
            ],
        ],
        Database\PrivilegesController::class => [
            'class' => Database\PrivilegesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$privileges' => '@server_privileges',
                '$dbi' => '@dbi',
            ],
        ],
        Database\QueryByExampleController::class => [
            'class' => Database\QueryByExampleController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Database\RoutinesController::class => [
            'class' => Database\RoutinesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$checkUserPrivileges' => '@check_user_privileges',
                '$dbi' => '@dbi',
            ],
        ],
        Database\SearchController::class => [
            'class' => Database\SearchController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        Database\SqlAutoCompleteController::class => [
            'class' => Database\SqlAutoCompleteController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        Database\SqlController::class => [
            'class' => Database\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$sqlQueryForm' => '@sql_query_form',
            ],
        ],
        Database\SqlFormatController::class => [
            'class' => Database\SqlFormatController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
            ],
        ],
        Database\Structure\AddPrefixController::class => [
            'class' => Database\Structure\AddPrefixController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
            ],
        ],
        Database\Structure\AddPrefixTableController::class => [
            'class' => Database\Structure\AddPrefixTableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\CentralColumns\AddController::class => [
            'class' => Database\Structure\CentralColumns\AddController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\CentralColumns\MakeConsistentController::class => [
            'class' => Database\Structure\CentralColumns\MakeConsistentController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\CentralColumns\RemoveController::class => [
            'class' => Database\Structure\CentralColumns\RemoveController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\ChangePrefixFormController::class => [
            'class' => Database\Structure\ChangePrefixFormController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
            ],
        ],
        Database\Structure\CopyFormController::class => [
            'class' => Database\Structure\CopyFormController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
            ],
        ],
        Database\Structure\CopyTableController::class => [
            'class' => Database\Structure\CopyTableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$operations' => '@operations',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\CopyTableWithPrefixController::class => [
            'class' => Database\Structure\CopyTableWithPrefixController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\DropFormController::class => [
            'class' => Database\Structure\DropFormController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        Database\Structure\DropTableController::class => [
            'class' => Database\Structure\DropTableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
                '$relationCleanup' => '@relation_cleanup',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\EmptyFormController::class => [
            'class' => Database\Structure\EmptyFormController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
            ],
        ],
        Database\Structure\EmptyTableController::class => [
            'class' => Database\Structure\EmptyTableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
                '$relation' => '@relation',
                '$relationCleanup' => '@relation_cleanup',
                '$operations' => '@operations',
                '$flash' => '@flash',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\FavoriteTableController::class => [
            'class' => Database\Structure\FavoriteTableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$relation' => '@relation',
            ],
        ],
        Database\Structure\RealRowCountController::class => [
            'class' => Database\Structure\RealRowCountController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        Database\Structure\ReplacePrefixController::class => [
            'class' => Database\Structure\ReplacePrefixController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\ShowCreateController::class => [
            'class' => Database\Structure\ShowCreateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        Database\StructureController::class => [
            'class' => Database\StructureController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$relation' => '@relation',
                '$replication' => '@replication',
                '$relationCleanup' => '@relation_cleanup',
                '$operations' => '@operations',
                '$dbi' => '@dbi',
                '$flash' => '@flash',
            ],
        ],
        Database\TrackingController::class => [
            'class' => Database\TrackingController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$tracking' => '@tracking',
                '$dbi' => '@dbi',
            ],
        ],
        Database\TriggersController::class => [
            'class' => Database\TriggersController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        DatabaseController::class => [
            'class' => DatabaseController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        ErrorReportController::class => [
            'class' => ErrorReportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$errorReport' => '@error_report',
                '$errorHandler' => '@error_handler',
            ],
        ],
        Export\CheckTimeOutController::class => [
            'class' => Export\CheckTimeOutController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        Export\ExportController::class => [
            'class' => Export\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$export' => '@export',
            ],
        ],
        Export\TablesController::class => [
            'class' => Export\TablesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$exportController' => '@' . Database\ExportController::class,
            ],
        ],
        Export\Template\CreateController::class => [
            'class' => Export\Template\CreateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@export_template_model',
                '$relation' => '@relation',
            ],
        ],
        Export\Template\DeleteController::class => [
            'class' => Export\Template\DeleteController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@export_template_model',
                '$relation' => '@relation',
            ],
        ],
        Export\Template\LoadController::class => [
            'class' => Export\Template\LoadController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@export_template_model',
                '$relation' => '@relation',
            ],
        ],
        Export\Template\UpdateController::class => [
            'class' => Export\Template\UpdateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@export_template_model',
                '$relation' => '@relation',
            ],
        ],
        GisDataEditorController::class => [
            'class' => GisDataEditorController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        GitInfoController::class => [
            'class' => GitInfoController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$config' => '@config',
            ],
        ],
        HomeController::class => [
            'class' => HomeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$config' => '@config',
                '$themeManager' => '@theme_manager',
                '$dbi' => '@dbi',
            ],
        ],
        Import\ImportController::class => [
            'class' => Import\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$import' => '@import',
                '$sql' => '@sql',
                '$dbi' => '@dbi',
            ],
        ],
        Import\SimulateDmlController::class => [
            'class' => Import\SimulateDmlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$simulateDml' => '@import_simulate_dml',
            ],
        ],
        Import\StatusController::class => [
            'class' => Import\StatusController::class,
            'arguments' => ['$template' => '@template'],
        ],
        JavaScriptMessagesController::class => [
            'class' => JavaScriptMessagesController::class,
        ],
        LicenseController::class => [
            'class' => LicenseController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        LintController::class => [
            'class' => LintController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        LogoutController::class => [
            'class' => LogoutController::class,
        ],
        NavigationController::class => [
            'class' => NavigationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$navigation' => '@navigation',
                '$relation' => '@relation',
            ],
        ],
        NormalizationController::class => [
            'class' => NormalizationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        PhpInfoController::class => [
            'class' => PhpInfoController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        RecentTablesListController::class => [
            'class' => RecentTablesListController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        Preferences\ExportController::class => [
            'class' => Preferences\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
            ],
        ],
        Preferences\FeaturesController::class => [
            'class' => Preferences\FeaturesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
            ],
        ],
        Preferences\ImportController::class => [
            'class' => Preferences\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
            ],
        ],
        Preferences\MainPanelController::class => [
            'class' => Preferences\MainPanelController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
            ],
        ],
        Preferences\ManageController::class => [
            'class' => Preferences\ManageController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
            ],
        ],
        Preferences\NavigationController::class => [
            'class' => Preferences\NavigationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
            ],
        ],
        Preferences\SqlController::class => [
            'class' => Preferences\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
            ],
        ],
        Preferences\TwoFactorController::class => [
            'class' => Preferences\TwoFactorController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
            ],
        ],
        SchemaExportController::class => [
            'class' => SchemaExportController::class,
            'arguments' => ['$export' => '@export'],
        ],
        Server\BinlogController::class => [
            'class' => Server\BinlogController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        Server\CollationsController::class => [
            'class' => Server\CollationsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Databases\CreateController::class => [
            'class' => Server\Databases\CreateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Databases\DestroyController::class => [
            'class' => Server\Databases\DestroyController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$transformations' => '@transformations',
                '$relationCleanup' => '@relation_cleanup',
            ],
        ],
        Server\DatabasesController::class => [
            'class' => Server\DatabasesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$transformations' => '@transformations',
                '$relationCleanup' => '@relation_cleanup',
                '$dbi' => '@dbi',
            ],
        ],
        Server\EnginesController::class => [
            'class' => Server\EnginesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        Server\ExportController::class => [
            'class' => Server\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$export' => '@export_options',
                '$dbi' => '@dbi',
            ],
        ],
        Server\ImportController::class => [
            'class' => Server\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        Server\PluginsController::class => [
            'class' => Server\PluginsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$plugins' => '@server_plugins',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Privileges\AccountLockController::class => [
            'class' => Server\Privileges\AccountLockController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$accountLocking' => '@server_privileges_account_locking',
            ],
        ],
        Server\Privileges\AccountUnlockController::class => [
            'class' => Server\Privileges\AccountUnlockController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$accountLocking' => '@server_privileges_account_locking',
            ],
        ],
        Server\PrivilegesController::class => [
            'class' => Server\PrivilegesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Server\ReplicationController::class => [
            'class' => Server\ReplicationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$replicationGui' => '@replication_gui',
                '$dbi' => '@dbi',
            ],
        ],
        Server\ShowEngineController::class => [
            'class' => Server\ShowEngineController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        Server\SqlController::class => [
            'class' => Server\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sqlQueryForm' => '@sql_query_form',
                '$dbi' => '@dbi',
            ],
        ],
        Server\UserGroupsController::class => [
            'class' => Server\UserGroupsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Server\UserGroupsFormController::class => [
            'class' => Server\UserGroupsFormController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\AdvisorController::class => [
            'class' => Server\Status\AdvisorController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$advisor' => '@advisor',
            ],
        ],
        Server\Status\Monitor\ChartingDataController::class => [
            'class' => Server\Status\Monitor\ChartingDataController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$monitor' => '@status_monitor',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\Monitor\GeneralLogController::class => [
            'class' => Server\Status\Monitor\GeneralLogController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$monitor' => '@status_monitor',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\Monitor\LogVarsController::class => [
            'class' => Server\Status\Monitor\LogVarsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$monitor' => '@status_monitor',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\Monitor\QueryAnalyzerController::class => [
            'class' => Server\Status\Monitor\QueryAnalyzerController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$monitor' => '@status_monitor',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\Monitor\SlowLogController::class => [
            'class' => Server\Status\Monitor\SlowLogController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$monitor' => '@status_monitor',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\MonitorController::class => [
            'class' => Server\Status\MonitorController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\Processes\KillController::class => [
            'class' => Server\Status\Processes\KillController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\Processes\RefreshController::class => [
            'class' => Server\Status\Processes\RefreshController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$processes' => '@status_processes',
            ],
        ],
        Server\Status\ProcessesController::class => [
            'class' => Server\Status\ProcessesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
                '$processes' => '@status_processes',
            ],
        ],
        Server\Status\QueriesController::class => [
            'class' => Server\Status\QueriesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\StatusController::class => [
            'class' => Server\Status\StatusController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$replicationGui' => '@replication_gui',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\VariablesController::class => [
            'class' => Server\Status\VariablesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Variables\GetVariableController::class => [
            'class' => Server\Variables\GetVariableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Variables\SetVariableController::class => [
            'class' => Server\Variables\SetVariableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        Server\VariablesController::class => [
            'class' => Server\VariablesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        Sql\ColumnPreferencesController::class => [
            'class' => Sql\ColumnPreferencesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sql' => '@sql',
                '$checkUserPrivileges' => '@check_user_privileges',
                '$dbi' => '@dbi',
            ],
        ],
        Sql\DefaultForeignKeyCheckValueController::class => [
            'class' => Sql\DefaultForeignKeyCheckValueController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$checkUserPrivileges' => '@check_user_privileges',
            ],
        ],
        Sql\EnumValuesController::class => [
            'class' => Sql\EnumValuesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sql' => '@sql',
                '$checkUserPrivileges' => '@check_user_privileges',
            ],
        ],
        Sql\RelationalValuesController::class => [
            'class' => Sql\RelationalValuesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sql' => '@sql',
                '$checkUserPrivileges' => '@check_user_privileges',
            ],
        ],
        Sql\SetValuesController::class => [
            'class' => Sql\SetValuesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sql' => '@sql',
                '$checkUserPrivileges' => '@check_user_privileges',
            ],
        ],
        Sql\SqlController::class => [
            'class' => Sql\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sql' => '@sql',
                '$checkUserPrivileges' => '@check_user_privileges',
                '$dbi' => '@dbi',
            ],
        ],
        Table\AddFieldController::class => [
            'class' => Table\AddFieldController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$transformations' => '@transformations',
                '$config' => '@config',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Table\ChangeController::class => [
            'class' => Table\ChangeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$insertEdit' => '@insert_edit',
                '$relation' => '@relation',
            ],
        ],
        Table\ChangeRowsController::class => [
            'class' => Table\ChangeRowsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$changeController' => '@' . Table\ChangeController::class,
            ],
        ],
        Table\ChartController::class => [
            'class' => Table\ChartController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        Table\CreateController::class => [
            'class' => Table\CreateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$transformations' => '@transformations',
                '$config' => '@config',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Table\DeleteConfirmController::class => [
            'class' => Table\DeleteConfirmController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
            ],
        ],
        Table\DeleteRowsController::class => [
            'class' => Table\DeleteRowsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        Table\DropColumnConfirmationController::class => [
            'class' => Table\DropColumnConfirmationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
            ],
        ],
        Table\DropColumnController::class => [
            'class' => Table\DropColumnController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
                '$flash' => '@flash',
                '$relationCleanup' => '@relation_cleanup',
            ],
        ],
        Table\ExportController::class => [
            'class' => Table\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$export' => '@export_options',
            ],
        ],
        Table\ExportRowsController::class => [
            'class' => Table\ExportRowsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$exportController' => '@' . Table\ExportController::class,
            ],
        ],
        Table\FindReplaceController::class => [
            'class' => Table\FindReplaceController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        Table\GetFieldController::class => [
            'class' => Table\GetFieldController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        Table\GisVisualizationController::class => [
            'class' => Table\GisVisualizationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        Table\ImportController::class => [
            'class' => Table\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        Table\IndexesController::class => [
            'class' => Table\IndexesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
                '$indexes' => '@table_indexes',
            ],
        ],
        Table\IndexRenameController::class => [
            'class' => Table\IndexRenameController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
                '$indexes' => '@table_indexes',
            ],
        ],
        Table\Maintenance\AnalyzeController::class => [
            'class' => Table\Maintenance\AnalyzeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$model' => '@table_maintenance',
                '$config' => '@config',
            ],
        ],
        Table\Maintenance\CheckController::class => [
            'class' => Table\Maintenance\CheckController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$model' => '@table_maintenance',
                '$config' => '@config',
            ],
        ],
        Table\Maintenance\ChecksumController::class => [
            'class' => Table\Maintenance\ChecksumController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$model' => '@table_maintenance',
                '$config' => '@config',
            ],
        ],
        Table\Maintenance\OptimizeController::class => [
            'class' => Table\Maintenance\OptimizeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$model' => '@table_maintenance',
                '$config' => '@config',
            ],
        ],
        Table\Maintenance\RepairController::class => [
            'class' => Table\Maintenance\RepairController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$model' => '@table_maintenance',
                '$config' => '@config',
            ],
        ],
        Table\Partition\AnalyzeController::class => [
            'class' => Table\Partition\AnalyzeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$maintenance' => '@partitioning_maintenance',
            ],
        ],
        Table\Partition\CheckController::class => [
            'class' => Table\Partition\CheckController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$maintenance' => '@partitioning_maintenance',
            ],
        ],
        Table\Partition\DropController::class => [
            'class' => Table\Partition\DropController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$maintenance' => '@partitioning_maintenance',
            ],
        ],
        Table\Partition\OptimizeController::class => [
            'class' => Table\Partition\OptimizeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$maintenance' => '@partitioning_maintenance',
            ],
        ],
        Table\Partition\RebuildController::class => [
            'class' => Table\Partition\RebuildController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$maintenance' => '@partitioning_maintenance',
            ],
        ],
        Table\Partition\RepairController::class => [
            'class' => Table\Partition\RepairController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$maintenance' => '@partitioning_maintenance',
            ],
        ],
        Table\Partition\TruncateController::class => [
            'class' => Table\Partition\TruncateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$maintenance' => '@partitioning_maintenance',
            ],
        ],
        Table\OperationsController::class => [
            'class' => Table\OperationsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$operations' => '@operations',
                '$checkUserPrivileges' => '@check_user_privileges',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Table\PrivilegesController::class => [
            'class' => Table\PrivilegesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$privileges' => '@server_privileges',
                '$dbi' => '@dbi',
            ],
        ],
        Table\RecentFavoriteController::class => [
            'class' => Table\RecentFavoriteController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
            ],
        ],
        Table\RelationController::class => [
            'class' => Table\RelationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Table\ReplaceController::class => [
            'class' => Table\ReplaceController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$insertEdit' => '@insert_edit',
                '$transformations' => '@transformations',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Table\SearchController::class => [
            'class' => Table\SearchController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$search' => '@table_search',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Table\SqlController::class => [
            'class' => Table\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$sqlQueryForm' => '@sql_query_form',
            ],
        ],
        Table\Structure\AddIndexController::class => [
            'class' => Table\Structure\AddIndexController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\AddKeyController::class => [
            'class' => Table\Structure\AddKeyController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$sqlController' => '@' . Sql\SqlController::class,
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\BrowseController::class => [
            'class' => Table\Structure\BrowseController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$sql' => '@sql',
            ],
        ],
        Table\Structure\CentralColumnsAddController::class => [
            'class' => Table\Structure\CentralColumnsAddController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$centralColumns' => '@central_columns',
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\CentralColumnsRemoveController::class => [
            'class' => Table\Structure\CentralColumnsRemoveController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$centralColumns' => '@central_columns',
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\ChangeController::class => [
            'class' => Table\Structure\ChangeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$relation' => '@relation',
                '$transformations' => '@transformations',
                '$dbi' => '@dbi',
            ],
        ],
        Table\Structure\FulltextController::class => [
            'class' => Table\Structure\FulltextController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\MoveColumnsController::class => [
            'class' => Table\Structure\MoveColumnsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        Table\Structure\PartitioningController::class => [
            'class' => Table\Structure\PartitioningController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
                '$createAddField' => '@create_add_field',
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\PrimaryController::class => [
            'class' => Table\Structure\PrimaryController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\ReservedWordCheckController::class => [
            'class' => Table\Structure\ReservedWordCheckController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
            ],
        ],
        Table\Structure\SaveController::class => [
            'class' => Table\Structure\SaveController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$relation' => '@relation',
                '$transformations' => '@transformations',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\SpatialController::class => [
            'class' => Table\Structure\SpatialController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\UniqueController::class => [
            'class' => Table\Structure\UniqueController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\StructureController::class => [
            'class' => Table\StructureController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$relation' => '@relation',
                '$transformations' => '@transformations',
                '$createAddField' => '@create_add_field',
                '$relationCleanup' => '@relation_cleanup',
                '$dbi' => '@dbi',
                '$flash' => '@flash',
            ],
        ],
        Table\TrackingController::class => [
            'class' => Table\TrackingController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$tracking' => '@tracking',
            ],
        ],
        Table\TriggersController::class => [
            'class' => Table\TriggersController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        Table\ZoomSearchController::class => [
            'class' => Table\ZoomSearchController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$search' => '@table_search',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        TableController::class => [
            'class' => TableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        ThemesController::class => [
            'class' => ThemesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$themeManager' => '@theme_manager',
            ],
        ],
        ThemeSetController::class => [
            'class' => ThemeSetController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$themeManager' => '@theme_manager',
            ],
        ],
        Transformation\OverviewController::class => [
            'class' => Transformation\OverviewController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$transformations' => '@transformations',
            ],
        ],
        Transformation\WrapperController::class => [
            'class' => Transformation\WrapperController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$transformations' => '@transformations',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        UserPasswordController::class => [
            'class' => UserPasswordController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPassword' => '@user_password',
                '$dbi' => '@dbi',
            ],
        ],
        VersionCheckController::class => [
            'class' => VersionCheckController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        View\CreateController::class => [
            'class' => View\CreateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        View\OperationsController::class => [
            'class' => View\OperationsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$operations' => '@operations',
                '$dbi' => '@dbi',
            ],
        ],
    ],
];
