<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Util;

/**
 * Set of functions used for cleaning up phpMyAdmin tables
 */
class RelationCleanup
{
    /** @var Relation */
    public $relation;

    /** @var DatabaseInterface */
    public $dbi;

    /**
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Relation          $relation Relation object
     */
    public function __construct($dbi, Relation $relation)
    {
        $this->dbi = $dbi;
        $this->relation = $relation;
    }

    /**
     * Cleanup column related relation stuff
     *
     * @param string $db     database name
     * @param string $table  table name
     * @param string $column column name
     */
    public function column($db, $table, $column): void
    {
        $relationParameters = $this->relation->getRelationParameters();

        if ($relationParameters->columnCommentsFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->columnCommentsFeature->database)
                . '.' . Util::backquote($relationParameters->columnCommentsFeature->columnInfo)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\''
                . ' AND column_name = \'' . $this->dbi->escapeString($column)
                . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->displayFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->displayFeature->database)
                . '.' . Util::backquote($relationParameters->displayFeature->tableInfo)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\''
                . ' AND display_field = \'' . $this->dbi->escapeString($column)
                . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->relationFeature === null) {
            return;
        }

        $remove_query = 'DELETE FROM '
            . Util::backquote($relationParameters->relationFeature->database)
            . '.' . Util::backquote($relationParameters->relationFeature->relation)
            . ' WHERE master_db  = \'' . $this->dbi->escapeString($db)
            . '\''
            . ' AND master_table = \'' . $this->dbi->escapeString($table)
            . '\''
            . ' AND master_field = \'' . $this->dbi->escapeString($column)
            . '\'';
        $this->dbi->queryAsControlUser($remove_query);

        $remove_query = 'DELETE FROM '
            . Util::backquote($relationParameters->relationFeature->database)
            . '.' . Util::backquote($relationParameters->relationFeature->relation)
            . ' WHERE foreign_db  = \'' . $this->dbi->escapeString($db)
            . '\''
            . ' AND foreign_table = \'' . $this->dbi->escapeString($table)
            . '\''
            . ' AND foreign_field = \'' . $this->dbi->escapeString($column)
            . '\'';
        $this->dbi->queryAsControlUser($remove_query);
    }

    /**
     * Cleanup table related relation stuff
     *
     * @param string $db    database name
     * @param string $table table name
     */
    public function table($db, $table): void
    {
        $relationParameters = $this->relation->getRelationParameters();

        if ($relationParameters->columnCommentsFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->columnCommentsFeature->database)
                . '.' . Util::backquote($relationParameters->columnCommentsFeature->columnInfo)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->displayFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->displayFeature->database)
                . '.' . Util::backquote($relationParameters->displayFeature->tableInfo)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->pdfFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->pdfFeature->database)
                . '.' . Util::backquote($relationParameters->pdfFeature->tableCoords)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->relationFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->relationFeature->database)
                . '.' . Util::backquote($relationParameters->relationFeature->relation)
                . ' WHERE master_db  = \'' . $this->dbi->escapeString($db)
                . '\''
                . ' AND master_table = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->dbi->queryAsControlUser($remove_query);

            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->relationFeature->database)
                . '.' . Util::backquote($relationParameters->relationFeature->relation)
                . ' WHERE foreign_db  = \'' . $this->dbi->escapeString($db)
                . '\''
                . ' AND foreign_table = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->uiPreferencesFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->uiPreferencesFeature->database)
                . '.' . Util::backquote($relationParameters->uiPreferencesFeature->tableUiPrefs)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->navigationItemsHidingFeature === null) {
            return;
        }

        $remove_query = 'DELETE FROM '
            . Util::backquote($relationParameters->navigationItemsHidingFeature->database)
            . '.' . Util::backquote($relationParameters->navigationItemsHidingFeature->navigationHiding)
            . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
            . ' AND (table_name = \'' . $this->dbi->escapeString($table)
            . '\''
            . ' OR (item_name = \'' . $this->dbi->escapeString($table)
            . '\''
            . ' AND item_type = \'table\'))';
        $this->dbi->queryAsControlUser($remove_query);
    }

    /**
     * Cleanup database related relation stuff
     *
     * @param string $db database name
     */
    public function database($db): void
    {
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->db === null) {
            return;
        }

        if ($relationParameters->columnCommentsFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->columnCommentsFeature->database)
                . '.' . Util::backquote($relationParameters->columnCommentsFeature->columnInfo)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->bookmarkFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->bookmarkFeature->database)
                . '.' . Util::backquote($relationParameters->bookmarkFeature->bookmark)
                . ' WHERE dbase  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->displayFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->displayFeature->database)
                . '.' . Util::backquote($relationParameters->displayFeature->tableInfo)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->pdfFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->pdfFeature->database)
                . '.' . Util::backquote($relationParameters->pdfFeature->pdfPages)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->dbi->queryAsControlUser($remove_query);

            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->pdfFeature->database)
                . '.' . Util::backquote($relationParameters->pdfFeature->tableCoords)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->relationFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->relationFeature->database)
                . '.' . Util::backquote($relationParameters->relationFeature->relation)
                . ' WHERE master_db  = \''
                . $this->dbi->escapeString($db) . '\'';
            $this->dbi->queryAsControlUser($remove_query);

            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->relationFeature->database)
                . '.' . Util::backquote($relationParameters->relationFeature->relation)
                . ' WHERE foreign_db  = \'' . $this->dbi->escapeString($db)
                . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->uiPreferencesFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->uiPreferencesFeature->database)
                . '.' . Util::backquote($relationParameters->uiPreferencesFeature->tableUiPrefs)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->navigationItemsHidingFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->navigationItemsHidingFeature->database)
                . '.' . Util::backquote($relationParameters->navigationItemsHidingFeature->navigationHiding)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->savedQueryByExampleSearchesFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->savedQueryByExampleSearchesFeature->database)
                . '.' . Util::backquote($relationParameters->savedQueryByExampleSearchesFeature->savedSearches)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->centralColumnsFeature === null) {
            return;
        }

        $remove_query = 'DELETE FROM '
            . Util::backquote($relationParameters->centralColumnsFeature->database)
            . '.' . Util::backquote($relationParameters->centralColumnsFeature->centralColumns)
            . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
        $this->dbi->queryAsControlUser($remove_query);
    }

    /**
     * Cleanup user related relation stuff
     *
     * @param string $username username
     */
    public function user($username): void
    {
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->db === null) {
            return;
        }

        if ($relationParameters->bookmarkFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->bookmarkFeature->database)
                . '.' . Util::backquote($relationParameters->bookmarkFeature->bookmark)
                . " WHERE `user`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->sqlHistoryFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->sqlHistoryFeature->database)
                . '.' . Util::backquote($relationParameters->sqlHistoryFeature->history)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->recentlyUsedTablesFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->recentlyUsedTablesFeature->database)
                . '.' . Util::backquote($relationParameters->recentlyUsedTablesFeature->recent)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->favoriteTablesFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->favoriteTablesFeature->database)
                . '.' . Util::backquote($relationParameters->favoriteTablesFeature->favorite)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->uiPreferencesFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->uiPreferencesFeature->database)
                . '.' . Util::backquote($relationParameters->uiPreferencesFeature->tableUiPrefs)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->userPreferencesFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->userPreferencesFeature->database)
                . '.' . Util::backquote($relationParameters->userPreferencesFeature->userConfig)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->configurableMenusFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->configurableMenusFeature->database)
                . '.' . Util::backquote($relationParameters->configurableMenusFeature->users)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->navigationItemsHidingFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->navigationItemsHidingFeature->database)
                . '.' . Util::backquote($relationParameters->navigationItemsHidingFeature->navigationHiding)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->savedQueryByExampleSearchesFeature !== null) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->savedQueryByExampleSearchesFeature->database)
                . '.' . Util::backquote($relationParameters->savedQueryByExampleSearchesFeature->savedSearches)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->dbi->queryAsControlUser($remove_query);
        }

        if ($relationParameters->databaseDesignerSettingsFeature === null) {
            return;
        }

        $remove_query = 'DELETE FROM '
            . Util::backquote($relationParameters->databaseDesignerSettingsFeature->database)
            . '.' . Util::backquote($relationParameters->databaseDesignerSettingsFeature->designerSettings)
            . " WHERE `username`  = '" . $this->dbi->escapeString($username)
            . "'";
        $this->dbi->queryAsControlUser($remove_query);
    }
}
