<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage;

use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\ConfigStorage\Features\BrowserTransformationFeature;
use PhpMyAdmin\ConfigStorage\Features\CentralColumnsFeature;
use PhpMyAdmin\ConfigStorage\Features\ColumnCommentsFeature;
use PhpMyAdmin\ConfigStorage\Features\ConfigurableMenusFeature;
use PhpMyAdmin\ConfigStorage\Features\DatabaseDesignerSettingsFeature;
use PhpMyAdmin\ConfigStorage\Features\DisplayFeature;
use PhpMyAdmin\ConfigStorage\Features\ExportTemplatesFeature;
use PhpMyAdmin\ConfigStorage\Features\FavoriteTablesFeature;
use PhpMyAdmin\ConfigStorage\Features\NavigationItemsHidingFeature;
use PhpMyAdmin\ConfigStorage\Features\PdfFeature;
use PhpMyAdmin\ConfigStorage\Features\RecentlyUsedTablesFeature;
use PhpMyAdmin\ConfigStorage\Features\RelationFeature;
use PhpMyAdmin\ConfigStorage\Features\SavedQueryByExampleSearchesFeature;
use PhpMyAdmin\ConfigStorage\Features\SqlHistoryFeature;
use PhpMyAdmin\ConfigStorage\Features\TrackingFeature;
use PhpMyAdmin\ConfigStorage\Features\UiPreferencesFeature;
use PhpMyAdmin\ConfigStorage\Features\UserPreferencesFeature;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Version;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function is_string;

/**
 * @psalm-immutable
 */
final class RelationParameters
{
    /**
     * @var string|null
     * @psalm-var non-empty-string|null
     */
    public $user;
    /** @var DatabaseName|null */
    public $db;

    /** @var BookmarkFeature|null */
    public $bookmarkFeature;
    /** @var BrowserTransformationFeature|null */
    public $browserTransformationFeature;
    /** @var CentralColumnsFeature|null */
    public $centralColumnsFeature;
    /** @var ColumnCommentsFeature|null */
    public $columnCommentsFeature;
    /** @var ConfigurableMenusFeature|null */
    public $configurableMenusFeature;
    /** @var DatabaseDesignerSettingsFeature|null */
    public $databaseDesignerSettingsFeature;
    /** @var DisplayFeature|null */
    public $displayFeature;
    /** @var ExportTemplatesFeature|null */
    public $exportTemplatesFeature;
    /** @var FavoriteTablesFeature|null */
    public $favoriteTablesFeature;
    /** @var NavigationItemsHidingFeature|null */
    public $navigationItemsHidingFeature;
    /** @var PdfFeature|null */
    public $pdfFeature;
    /** @var RecentlyUsedTablesFeature|null */
    public $recentlyUsedTablesFeature;
    /** @var RelationFeature|null */
    public $relationFeature;
    /** @var SavedQueryByExampleSearchesFeature|null */
    public $savedQueryByExampleSearchesFeature;
    /** @var SqlHistoryFeature|null */
    public $sqlHistoryFeature;
    /** @var TrackingFeature|null */
    public $trackingFeature;
    /** @var UiPreferencesFeature|null */
    public $uiPreferencesFeature;
    /** @var UserPreferencesFeature|null */
    public $userPreferencesFeature;

    /**
     * @psalm-param non-empty-string|null $user
     */
    public function __construct(
        ?string $user,
        ?DatabaseName $db,
        ?BookmarkFeature $bookmarkFeature = null,
        ?BrowserTransformationFeature $browserTransformationFeature = null,
        ?CentralColumnsFeature $centralColumnsFeature = null,
        ?ColumnCommentsFeature $columnCommentsFeature = null,
        ?ConfigurableMenusFeature $configurableMenusFeature = null,
        ?DatabaseDesignerSettingsFeature $databaseDesignerSettingsFeature = null,
        ?DisplayFeature $displayFeature = null,
        ?ExportTemplatesFeature $exportTemplatesFeature = null,
        ?FavoriteTablesFeature $favoriteTablesFeature = null,
        ?NavigationItemsHidingFeature $navigationItemsHidingFeature = null,
        ?PdfFeature $pdfFeature = null,
        ?RecentlyUsedTablesFeature $recentlyUsedTablesFeature = null,
        ?RelationFeature $relationFeature = null,
        ?SavedQueryByExampleSearchesFeature $savedQueryByExampleSearchesFeature = null,
        ?SqlHistoryFeature $sqlHistoryFeature = null,
        ?TrackingFeature $trackingFeature = null,
        ?UiPreferencesFeature $uiPreferencesFeature = null,
        ?UserPreferencesFeature $userPreferencesFeature = null
    ) {
        $this->user = $user;
        $this->db = $db;
        $this->bookmarkFeature = $bookmarkFeature;
        $this->browserTransformationFeature = $browserTransformationFeature;
        $this->centralColumnsFeature = $centralColumnsFeature;
        $this->columnCommentsFeature = $columnCommentsFeature;
        $this->configurableMenusFeature = $configurableMenusFeature;
        $this->databaseDesignerSettingsFeature = $databaseDesignerSettingsFeature;
        $this->displayFeature = $displayFeature;
        $this->exportTemplatesFeature = $exportTemplatesFeature;
        $this->favoriteTablesFeature = $favoriteTablesFeature;
        $this->navigationItemsHidingFeature = $navigationItemsHidingFeature;
        $this->pdfFeature = $pdfFeature;
        $this->recentlyUsedTablesFeature = $recentlyUsedTablesFeature;
        $this->relationFeature = $relationFeature;
        $this->savedQueryByExampleSearchesFeature = $savedQueryByExampleSearchesFeature;
        $this->sqlHistoryFeature = $sqlHistoryFeature;
        $this->trackingFeature = $trackingFeature;
        $this->uiPreferencesFeature = $uiPreferencesFeature;
        $this->userPreferencesFeature = $userPreferencesFeature;
    }

    /**
     * @param mixed[] $params
     */
    public static function fromArray(array $params): self
    {
        $user = null;
        if (isset($params['user']) && is_string($params['user']) && $params['user'] !== '') {
            $user = $params['user'];
        }

        try {
            Assert::keyExists($params, 'db');
            $db = DatabaseName::fromValue($params['db']);
        } catch (InvalidArgumentException $exception) {
            return new self($user, null);
        }

        $bookmarkFeature = null;
        if (isset($params['bookmarkwork'], $params['bookmark']) && $params['bookmarkwork']) {
            $bookmark = self::getTableName($params['bookmark']);
            if ($bookmark !== null) {
                $bookmarkFeature = new BookmarkFeature($db, $bookmark);
            }
        }

        $columnInfo = self::getTableName($params['column_info'] ?? null);
        $browserTransformationFeature = null;
        if (isset($params['mimework']) && $params['mimework'] && $columnInfo !== null) {
            $browserTransformationFeature = new BrowserTransformationFeature($db, $columnInfo);
        }

        $columnCommentsFeature = null;
        if (isset($params['commwork']) && $params['commwork'] && $columnInfo !== null) {
            $columnCommentsFeature = new ColumnCommentsFeature($db, $columnInfo);
        }

        $centralColumnsFeature = null;
        if (isset($params['centralcolumnswork'], $params['central_columns']) && $params['centralcolumnswork']) {
            $centralColumns = self::getTableName($params['central_columns']);
            if ($centralColumns !== null) {
                $centralColumnsFeature = new CentralColumnsFeature($db, $centralColumns);
            }
        }

        $configurableMenusFeature = null;
        if (isset($params['menuswork'], $params['usergroups'], $params['users']) && $params['menuswork']) {
            $userGroups = self::getTableName($params['usergroups']);
            $users = self::getTableName($params['users']);
            if ($userGroups !== null && $users !== null) {
                $configurableMenusFeature = new ConfigurableMenusFeature($db, $userGroups, $users);
            }
        }

        $databaseDesignerSettingsFeature = null;
        if (isset($params['designersettingswork'], $params['designer_settings']) && $params['designersettingswork']) {
            $designerSettings = self::getTableName($params['designer_settings']);
            if ($designerSettings !== null) {
                $databaseDesignerSettingsFeature = new DatabaseDesignerSettingsFeature($db, $designerSettings);
            }
        }

        $relation = self::getTableName($params['relation'] ?? null);
        $displayFeature = null;
        if (isset($params['displaywork'], $params['table_info']) && $params['displaywork'] && $relation !== null) {
            $tableInfo = self::getTableName($params['table_info']);
            if ($tableInfo !== null) {
                $displayFeature = new DisplayFeature($db, $relation, $tableInfo);
            }
        }

        $exportTemplatesFeature = null;
        if (isset($params['exporttemplateswork'], $params['export_templates']) && $params['exporttemplateswork']) {
            $exportTemplates = self::getTableName($params['export_templates']);
            if ($exportTemplates !== null) {
                $exportTemplatesFeature = new ExportTemplatesFeature($db, $exportTemplates);
            }
        }

        $favoriteTablesFeature = null;
        if (isset($params['favoritework'], $params['favorite']) && $params['favoritework']) {
            $favorite = self::getTableName($params['favorite']);
            if ($favorite !== null) {
                $favoriteTablesFeature = new FavoriteTablesFeature($db, $favorite);
            }
        }

        $navigationItemsHidingFeature = null;
        if (isset($params['navwork'], $params['navigationhiding']) && $params['navwork']) {
            $navigationHiding = self::getTableName($params['navigationhiding']);
            if ($navigationHiding !== null) {
                $navigationItemsHidingFeature = new NavigationItemsHidingFeature($db, $navigationHiding);
            }
        }

        $pdfFeature = null;
        if (isset($params['pdfwork'], $params['pdf_pages'], $params['table_coords']) && $params['pdfwork']) {
            $pdfPages = self::getTableName($params['pdf_pages']);
            $tableCoords = self::getTableName($params['table_coords']);
            if ($pdfPages !== null && $tableCoords !== null) {
                $pdfFeature = new PdfFeature($db, $pdfPages, $tableCoords);
            }
        }

        $recentlyUsedTablesFeature = null;
        if (isset($params['recentwork'], $params['recent']) && $params['recentwork']) {
            $recent = self::getTableName($params['recent']);
            if ($recent !== null) {
                $recentlyUsedTablesFeature = new RecentlyUsedTablesFeature($db, $recent);
            }
        }

        $relationFeature = null;
        if (isset($params['relwork']) && $params['relwork'] && $relation !== null) {
            $relationFeature = new RelationFeature($db, $relation);
        }

        $savedQueryByExampleSearchesFeature = null;
        if (isset($params['savedsearcheswork'], $params['savedsearches']) && $params['savedsearcheswork']) {
            $savedSearches = self::getTableName($params['savedsearches']);
            if ($savedSearches !== null) {
                $savedQueryByExampleSearchesFeature = new SavedQueryByExampleSearchesFeature($db, $savedSearches);
            }
        }

        $sqlHistoryFeature = null;
        if (isset($params['historywork'], $params['history']) && $params['historywork']) {
            $history = self::getTableName($params['history']);
            if ($history !== null) {
                $sqlHistoryFeature = new SqlHistoryFeature($db, $history);
            }
        }

        $trackingFeature = null;
        if (isset($params['trackingwork'], $params['tracking']) && $params['trackingwork']) {
            $tracking = self::getTableName($params['tracking']);
            if ($tracking !== null) {
                $trackingFeature = new TrackingFeature($db, $tracking);
            }
        }

        $uiPreferencesFeature = null;
        if (isset($params['uiprefswork'], $params['table_uiprefs']) && $params['uiprefswork']) {
            $tableUiPrefs = self::getTableName($params['table_uiprefs']);
            if ($tableUiPrefs !== null) {
                $uiPreferencesFeature = new UiPreferencesFeature($db, $tableUiPrefs);
            }
        }

        $userPreferencesFeature = null;
        if (isset($params['userconfigwork'], $params['userconfig']) && $params['userconfigwork']) {
            $userConfig = self::getTableName($params['userconfig']);
            if ($userConfig !== null) {
                $userPreferencesFeature = new UserPreferencesFeature($db, $userConfig);
            }
        }

        return new self(
            $user,
            $db,
            $bookmarkFeature,
            $browserTransformationFeature,
            $centralColumnsFeature,
            $columnCommentsFeature,
            $configurableMenusFeature,
            $databaseDesignerSettingsFeature,
            $displayFeature,
            $exportTemplatesFeature,
            $favoriteTablesFeature,
            $navigationItemsHidingFeature,
            $pdfFeature,
            $recentlyUsedTablesFeature,
            $relationFeature,
            $savedQueryByExampleSearchesFeature,
            $sqlHistoryFeature,
            $trackingFeature,
            $uiPreferencesFeature,
            $userPreferencesFeature
        );
    }

    /**
     * @return array<string, bool|string|null>
     * @psalm-return array{
     *   version: string,
     *   user: (string|null),
     *   db: (string|null),
     *   bookmark: (string|null),
     *   central_columns: (string|null),
     *   column_info: (string|null),
     *   designer_settings: (string|null),
     *   export_templates: (string|null),
     *   favorite: (string|null),
     *   history: (string|null),
     *   navigationhiding: (string|null),
     *   pdf_pages: (string|null),
     *   recent: (string|null),
     *   relation: (string|null),
     *   savedsearches: (string|null),
     *   table_coords: (string|null),
     *   table_info: (string|null),
     *   table_uiprefs: (string|null),
     *   tracking: (string|null),
     *   userconfig: (string|null),
     *   usergroups: (string|null),
     *   users: (string|null),
     *   bookmarkwork: bool,
     *   mimework: bool,
     *   centralcolumnswork: bool,
     *   commwork: bool,
     *   menuswork: bool,
     *   designersettingswork: bool,
     *   displaywork: bool,
     *   exporttemplateswork: bool,
     *   favoritework: bool,
     *   navwork: bool,
     *   pdfwork: bool,
     *   recentwork: bool,
     *   relwork: bool,
     *   savedsearcheswork: bool,
     *   historywork: bool,
     *   trackingwork: bool,
     *   uiprefswork: bool,
     *   userconfigwork: bool,
     *   allworks: bool
     * }
     */
    public function toArray(): array
    {
        $columnInfo = null;
        if ($this->columnCommentsFeature !== null) {
            $columnInfo = $this->columnCommentsFeature->columnInfo->getName();
        } elseif ($this->browserTransformationFeature !== null) {
            $columnInfo = $this->browserTransformationFeature->columnInfo->getName();
        }

        $relation = null;
        if ($this->relationFeature !== null) {
            $relation = $this->relationFeature->relation->getName();
        } elseif ($this->displayFeature !== null) {
            $relation = $this->displayFeature->relation->getName();
        }

        return [
            'version' => Version::VERSION,
            'user' => $this->user,
            'db' => $this->db !== null ? $this->db->getName() : null,
            'bookmark' => $this->bookmarkFeature !== null ? $this->bookmarkFeature->bookmark->getName() : null,
            'central_columns' => $this->centralColumnsFeature !== null
                ? $this->centralColumnsFeature->centralColumns->getName()
                : null,
            'column_info' => $columnInfo,
            'designer_settings' => $this->databaseDesignerSettingsFeature !== null
                ? $this->databaseDesignerSettingsFeature->designerSettings->getName()
                : null,
            'export_templates' => $this->exportTemplatesFeature !== null
                ? $this->exportTemplatesFeature->exportTemplates->getName()
                : null,
            'favorite' => $this->favoriteTablesFeature !== null
                ? $this->favoriteTablesFeature->favorite->getName()
                : null,
            'history' => $this->sqlHistoryFeature !== null ? $this->sqlHistoryFeature->history->getName() : null,
            'navigationhiding' => $this->navigationItemsHidingFeature !== null
                ? $this->navigationItemsHidingFeature->navigationHiding->getName()
                : null,
            'pdf_pages' => $this->pdfFeature !== null ? $this->pdfFeature->pdfPages->getName() : null,
            'recent' => $this->recentlyUsedTablesFeature !== null
                ? $this->recentlyUsedTablesFeature->recent->getName()
                : null,
            'relation' => $relation,
            'savedsearches' => $this->savedQueryByExampleSearchesFeature !== null
                ? $this->savedQueryByExampleSearchesFeature->savedSearches->getName()
                : null,
            'table_coords' => $this->pdfFeature !== null ? $this->pdfFeature->tableCoords->getName() : null,
            'table_info' => $this->displayFeature !== null ? $this->displayFeature->tableInfo->getName() : null,
            'table_uiprefs' => $this->uiPreferencesFeature !== null
                ? $this->uiPreferencesFeature->tableUiPrefs->getName()
                : null,
            'tracking' => $this->trackingFeature !== null ? $this->trackingFeature->tracking->getName() : null,
            'userconfig' => $this->userPreferencesFeature !== null
                ? $this->userPreferencesFeature->userConfig->getName()
                : null,
            'usergroups' => $this->configurableMenusFeature !== null
                ? $this->configurableMenusFeature->userGroups->getName()
                : null,
            'users' => $this->configurableMenusFeature !== null
                ? $this->configurableMenusFeature->users->getName()
                : null,
            'bookmarkwork' => $this->bookmarkFeature !== null,
            'mimework' => $this->browserTransformationFeature !== null,
            'centralcolumnswork' => $this->centralColumnsFeature !== null,
            'commwork' => $this->columnCommentsFeature !== null,
            'menuswork' => $this->configurableMenusFeature !== null,
            'designersettingswork' => $this->databaseDesignerSettingsFeature !== null,
            'displaywork' => $this->displayFeature !== null,
            'exporttemplateswork' => $this->exportTemplatesFeature !== null,
            'favoritework' => $this->favoriteTablesFeature !== null,
            'navwork' => $this->navigationItemsHidingFeature !== null,
            'pdfwork' => $this->pdfFeature !== null,
            'recentwork' => $this->recentlyUsedTablesFeature !== null,
            'relwork' => $this->relationFeature !== null,
            'savedsearcheswork' => $this->savedQueryByExampleSearchesFeature !== null,
            'historywork' => $this->sqlHistoryFeature !== null,
            'trackingwork' => $this->trackingFeature !== null,
            'uiprefswork' => $this->uiPreferencesFeature !== null,
            'userconfigwork' => $this->userPreferencesFeature !== null,
            'allworks' => $this->hasAllFeatures(),
        ];
    }

    public function hasAllFeatures(): bool
    {
        return $this->bookmarkFeature !== null
            && $this->browserTransformationFeature !== null
            && $this->centralColumnsFeature !== null
            && $this->columnCommentsFeature !== null
            && $this->configurableMenusFeature !== null
            && $this->databaseDesignerSettingsFeature !== null
            && $this->displayFeature !== null
            && $this->exportTemplatesFeature !== null
            && $this->favoriteTablesFeature !== null
            && $this->navigationItemsHidingFeature !== null
            && $this->pdfFeature !== null
            && $this->recentlyUsedTablesFeature !== null
            && $this->relationFeature !== null
            && $this->savedQueryByExampleSearchesFeature !== null
            && $this->sqlHistoryFeature !== null
            && $this->trackingFeature !== null
            && $this->uiPreferencesFeature !== null
            && $this->userPreferencesFeature !== null;
    }

    /**
     * @param mixed $tableName
     */
    private static function getTableName($tableName): ?TableName
    {
        try {
            return TableName::fromValue($tableName);
        } catch (InvalidArgumentException $exception) {
            return null;
        }
    }
}
