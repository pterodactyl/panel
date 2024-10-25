<?php
/**
 * Abstract class for the export plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Export;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Properties\Plugins\PluginPropertyItem;
use PhpMyAdmin\Transformations;

use function stripos;

/**
 * Provides a common interface that will have to be implemented by all of the
 * export plugins. Some of the plugins will also implement other public
 * methods, but those are not declared here, because they are not implemented
 * by all export plugins.
 */
abstract class ExportPlugin implements Plugin
{
    /**
     * Object containing the specific export plugin type properties.
     *
     * @var ExportPluginProperties
     */
    protected $properties;

    /** @var Relation */
    public $relation;

    /** @var Export */
    protected $export;

    /** @var Transformations */
    protected $transformations;

    /**
     * @psalm-suppress InvalidArrayOffset, MixedAssignment, MixedMethodCall
     */
    final public function __construct()
    {
        $this->relation = $GLOBALS['containerBuilder']->get('relation');
        $this->export = $GLOBALS['containerBuilder']->get('export');
        $this->transformations = $GLOBALS['containerBuilder']->get('transformations');
        $this->init();
        $this->properties = $this->setProperties();
    }

    /**
     * Outputs export header
     */
    abstract public function exportHeader(): bool;

    /**
     * Outputs export footer
     */
    abstract public function exportFooter(): bool;

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    abstract public function exportDBHeader($db, $dbAlias = ''): bool;

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     */
    abstract public function exportDBFooter($db): bool;

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db         Database name
     * @param string $exportType 'server', 'database', 'table'
     * @param string $dbAlias    Aliases of db
     */
    abstract public function exportDBCreate($db, $exportType, $dbAlias = ''): bool;

    /**
     * Outputs the content of a table
     *
     * @param string $db       database name
     * @param string $table    table name
     * @param string $crlf     the end of line sequence
     * @param string $errorUrl the url to go back in case of error
     * @param string $sqlQuery SQL query for obtaining data
     * @param array  $aliases  Aliases of db/table/columns
     */
    abstract public function exportData(
        $db,
        $table,
        $crlf,
        $errorUrl,
        $sqlQuery,
        array $aliases = []
    ): bool;

    /**
     * The following methods are used in /export or in /database/operations,
     * but they are not implemented by all export plugins
     */

    /**
     * Exports routines (procedures and functions)
     *
     * @param string $db      Database
     * @param array  $aliases Aliases of db/table/columns
     */
    public function exportRoutines($db, array $aliases = []): bool
    {
        return true;
    }

    /**
     * Exports events
     *
     * @param string $db Database
     */
    public function exportEvents($db): bool
    {
        return true;
    }

    /**
     * Outputs for raw query
     *
     * @param string      $errorUrl the url to go back in case of error
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     * @param string      $crlf     the end of line sequence
     */
    public function exportRawQuery(
        string $errorUrl,
        ?string $db,
        string $sqlQuery,
        string $crlf
    ): bool {
        return false;
    }

    /**
     * Outputs table's structure
     *
     * @param string $db         database name
     * @param string $table      table name
     * @param string $crlf       the end of line sequence
     * @param string $errorUrl   the url to go back in case of error
     * @param string $exportMode 'create_table','triggers','create_view',
     *                            'stand_in'
     * @param string $exportType 'server', 'database', 'table'
     * @param bool   $relation   whether to include relation comments
     * @param bool   $comments   whether to include the pmadb-style column comments
     *                           as comments in the structure; this is deprecated
     *                           but the parameter is left here because /export
     *                           calls exportStructure() also for other export
     *                           types which use this parameter
     * @param bool   $mime       whether to include mime comments
     * @param bool   $dates      whether to include creation/update/check dates
     * @param array  $aliases    Aliases of db/table/columns
     */
    public function exportStructure(
        $db,
        $table,
        $crlf,
        $errorUrl,
        $exportMode,
        $exportType,
        $relation = false,
        $comments = false,
        $mime = false,
        $dates = false,
        array $aliases = []
    ): bool {
        return true;
    }

    /**
     * Exports metadata from Configuration Storage
     *
     * @param string       $db            database being exported
     * @param string|array $tables        table(s) being exported
     * @param array        $metadataTypes types of metadata to export
     */
    public function exportMetadata(
        $db,
        $tables,
        array $metadataTypes
    ): bool {
        return true;
    }

    /**
     * Returns a stand-in CREATE definition to resolve view dependencies
     *
     * @param string $db      the database name
     * @param string $view    the view name
     * @param string $crlf    the end of line sequence
     * @param array  $aliases Aliases of db/table/columns
     *
     * @return string resulting definition
     */
    public function getTableDefStandIn($db, $view, $crlf, $aliases = [])
    {
        return '';
    }

    /**
     * Outputs triggers
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return string Formatted triggers list
     */
    protected function getTriggers($db, $table)
    {
        return '';
    }

    /**
     * Plugin specific initializations.
     */
    protected function init(): void
    {
    }

    /**
     * Gets the export specific format plugin properties
     *
     * @return ExportPluginProperties
     */
    public function getProperties(): PluginPropertyItem
    {
        return $this->properties;
    }

    /**
     * Sets the export plugins properties and is implemented by each export plugin.
     */
    abstract protected function setProperties(): ExportPluginProperties;

    /**
     * The following methods are implemented here so that they
     * can be used by all export plugin without overriding it.
     * Note: If you are creating a export plugin then don't include
     * below methods unless you want to override them.
     */

    /**
     * Initialize aliases
     *
     * @param array  $aliases Alias information for db/table/column
     * @param string $db      the database
     * @param string $table   the table
     */
    public function initAlias($aliases, &$db, &$table = null): void
    {
        if (! empty($aliases[$db]['tables'][$table]['alias'])) {
            $table = $aliases[$db]['tables'][$table]['alias'];
        }

        if (empty($aliases[$db]['alias'])) {
            return;
        }

        $db = $aliases[$db]['alias'];
    }

    /**
     * Search for alias of a identifier.
     *
     * @param array  $aliases Alias information for db/table/column
     * @param string $id      the identifier to be searched
     * @param string $type    db/tbl/col or any combination of them
     *                        representing what to be searched
     * @param string $db      the database in which search is to be done
     * @param string $tbl     the table in which search is to be done
     *
     * @return string alias of the identifier if found or ''
     */
    public function getAlias(array $aliases, $id, $type = 'dbtblcol', $db = '', $tbl = '')
    {
        if (! empty($db) && isset($aliases[$db])) {
            $aliases = [
                $db => $aliases[$db],
            ];
        }

        // search each database
        foreach ($aliases as $db_key => $db) {
            // check if id is database and has alias
            if (stripos($type, 'db') !== false && $db_key === $id && ! empty($db['alias'])) {
                return $db['alias'];
            }

            if (empty($db['tables'])) {
                continue;
            }

            if (! empty($tbl) && isset($db['tables'][$tbl])) {
                $db['tables'] = [
                    $tbl => $db['tables'][$tbl],
                ];
            }

            // search each of its tables
            foreach ($db['tables'] as $table_key => $table) {
                // check if id is table and has alias
                if (stripos($type, 'tbl') !== false && $table_key === $id && ! empty($table['alias'])) {
                    return $table['alias'];
                }

                if (empty($table['columns'])) {
                    continue;
                }

                // search each of its columns
                foreach ($table['columns'] as $col_key => $col) {
                    // check if id is column
                    if (stripos($type, 'col') !== false && $col_key === $id && ! empty($col)) {
                        return $col;
                    }
                }
            }
        }

        return '';
    }

    /**
     * Gives the relation string and
     * also substitutes with alias if required
     * in this format:
     * [Foreign Table] ([Foreign Field])
     *
     * @param array  $foreigners the foreigners array
     * @param string $fieldName  the field name
     * @param string $db         the field name
     * @param array  $aliases    Alias information for db/table/column
     *
     * @return string the Relation string
     */
    public function getRelationString(
        array $foreigners,
        $fieldName,
        $db,
        array $aliases = []
    ) {
        $relation = '';
        $foreigner = $this->relation->searchColumnInForeigners($foreigners, $fieldName);
        if ($foreigner) {
            $ftable = $foreigner['foreign_table'];
            $ffield = $foreigner['foreign_field'];
            if (! empty($aliases[$db]['tables'][$ftable]['columns'][$ffield])) {
                $ffield = $aliases[$db]['tables'][$ftable]['columns'][$ffield];
            }

            if (! empty($aliases[$db]['tables'][$ftable]['alias'])) {
                $ftable = $aliases[$db]['tables'][$ftable]['alias'];
            }

            $relation = $ftable . ' (' . $ffield . ')';
        }

        return $relation;
    }

    public static function isAvailable(): bool
    {
        return true;
    }
}
