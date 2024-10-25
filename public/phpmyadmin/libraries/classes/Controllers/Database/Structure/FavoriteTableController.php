<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Database\AbstractController;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;
use function json_decode;
use function json_encode;
use function md5;
use function sha1;

final class FavoriteTableController extends AbstractController
{
    /** @var Relation */
    private $relation;

    public function __construct(ResponseRenderer $response, Template $template, string $db, Relation $relation)
    {
        parent::__construct($response, $template, $db);
        $this->relation = $relation;
    }

    public function __invoke(): void
    {
        global $cfg, $db, $errorUrl;

        $parameters = [
            'favorite_table' => $_REQUEST['favorite_table'] ?? null,
            'favoriteTables' => $_REQUEST['favoriteTables'] ?? null,
            'sync_favorite_tables' => $_REQUEST['sync_favorite_tables'] ?? null,
        ];

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase() || ! $this->response->isAjax()) {
            return;
        }

        $favoriteInstance = RecentFavoriteTable::getInstance('favorite');
        if (isset($parameters['favoriteTables'])) {
            $favoriteTables = json_decode($parameters['favoriteTables'], true);
        } else {
            $favoriteTables = [];
        }

        // Required to keep each user's preferences separate.
        $user = sha1($cfg['Server']['user']);

        // Request for Synchronization of favorite tables.
        if (isset($parameters['sync_favorite_tables'])) {
            $relationParameters = $this->relation->getRelationParameters();
            if ($relationParameters->favoriteTablesFeature !== null) {
                $this->response->addJSON($this->synchronizeFavoriteTables(
                    $favoriteInstance,
                    $user,
                    $favoriteTables
                ));
            }

            return;
        }

        $changes = true;
        $favoriteTable = $parameters['favorite_table'] ?? '';
        $alreadyFavorite = $this->checkFavoriteTable($favoriteTable);

        if (isset($_REQUEST['remove_favorite'])) {
            if ($alreadyFavorite) {
                // If already in favorite list, remove it.
                $favoriteInstance->remove($this->db, $favoriteTable);
                $alreadyFavorite = false; // for favorite_anchor template
            }
        } elseif (isset($_REQUEST['add_favorite'])) {
            if (! $alreadyFavorite) {
                $numTables = count($favoriteInstance->getTables());
                if ($numTables == $cfg['NumFavoriteTables']) {
                    $changes = false;
                } else {
                    // Otherwise add to favorite list.
                    $favoriteInstance->add($this->db, $favoriteTable);
                    $alreadyFavorite = true; // for favorite_anchor template
                }
            }
        }

        $favoriteTables[$user] = $favoriteInstance->getTables();

        $json = [];
        $json['changes'] = $changes;
        if (! $changes) {
            $json['message'] = $this->template->render('components/error_message', [
                'msg' => __('Favorite List is full!'),
            ]);
            $this->response->addJSON($json);

            return;
        }

        // Check if current table is already in favorite list.
        $favoriteParams = [
            'db' => $this->db,
            'ajax_request' => true,
            'favorite_table' => $favoriteTable,
            ($alreadyFavorite ? 'remove' : 'add') . '_favorite' => true,
        ];

        $json['user'] = $user;
        $json['favoriteTables'] = json_encode($favoriteTables);
        $json['list'] = $favoriteInstance->getHtmlList();
        $json['anchor'] = $this->template->render('database/structure/favorite_anchor', [
            'table_name_hash' => md5($favoriteTable),
            'db_table_name_hash' => md5($this->db . '.' . $favoriteTable),
            'fav_params' => $favoriteParams,
            'already_favorite' => $alreadyFavorite,
        ]);

        $this->response->addJSON($json);
    }

    /**
     * Synchronize favorite tables
     *
     * @param RecentFavoriteTable $favoriteInstance Instance of this class
     * @param string              $user             The user hash
     * @param array               $favoriteTables   Existing favorites
     *
     * @return array
     */
    private function synchronizeFavoriteTables(
        RecentFavoriteTable $favoriteInstance,
        string $user,
        array $favoriteTables
    ): array {
        $favoriteInstanceTables = $favoriteInstance->getTables();

        if (empty($favoriteInstanceTables) && isset($favoriteTables[$user])) {
            foreach ($favoriteTables[$user] as $value) {
                $favoriteInstance->add($value['db'], $value['table']);
            }
        }

        $favoriteTables[$user] = $favoriteInstance->getTables();

        // Set flag when localStorage and pmadb(if present) are in sync.
        $_SESSION['tmpval']['favorites_synced'][$GLOBALS['server']] = true;

        return [
            'favoriteTables' => json_encode($favoriteTables),
            'list' => $favoriteInstance->getHtmlList(),
        ];
    }

    /**
     * Function to check if a table is already in favorite list.
     *
     * @param string $currentTable current table
     */
    private function checkFavoriteTable(string $currentTable): bool
    {
        // ensure $_SESSION['tmpval']['favoriteTables'] is initialized
        RecentFavoriteTable::getInstance('favorite');
        $favoriteTables = $_SESSION['tmpval']['favoriteTables'][$GLOBALS['server']] ?? [];
        foreach ($favoriteTables as $value) {
            if ($value['db'] == $this->db && $value['table'] == $currentTable) {
                return true;
            }
        }

        return false;
    }
}
