<?php
/**
 * Contains abstract class to hold table preferences/statistics
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Font;
use PhpMyAdmin\Index;
use PhpMyAdmin\Util;

use function array_flip;
use function array_keys;
use function array_merge;
use function is_array;
use function rawurldecode;
use function sprintf;

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the tables.
 *
 * @abstract
 */
abstract class TableStats
{
    /** @var Dia\Dia|Eps\Eps|Pdf\Pdf|Svg\Svg */
    protected $diagram;

    /** @var string */
    protected $db;

    /** @var int */
    protected $pageNumber;

    /** @var string */
    protected $tableName;

    /** @var bool */
    protected $showKeys;

    /** @var bool */
    protected $tableDimension;

    /** @var mixed */
    public $displayfield;

    /** @var array */
    public $fields = [];

    /** @var array */
    public $primary = [];

    /** @var int|float */
    public $x = 0;

    /** @var int|float */
    public $y = 0;

    /** @var int */
    public $width = 0;

    /** @var int */
    public $heightCell = 0;

    /** @var bool */
    protected $offline;

    /** @var Relation */
    protected $relation;

    /** @var Font */
    protected $font;

    /**
     * @param Pdf\Pdf|Svg\Svg|Eps\Eps|Dia\Dia $diagram        schema diagram
     * @param string                          $db             current db name
     * @param int                             $pageNumber     current page number (from the
     *                                                        $cfg['Servers'][$i]['table_coords'] table)
     * @param string                          $tableName      table name
     * @param bool                            $showKeys       whether to display keys or not
     * @param bool                            $tableDimension whether to display table position or not
     * @param bool                            $offline        whether the coordinates are sent from the browser
     */
    public function __construct(
        $diagram,
        $db,
        $pageNumber,
        $tableName,
        $showKeys,
        $tableDimension,
        $offline
    ) {
        global $dbi;

        $this->diagram = $diagram;
        $this->db = $db;
        $this->pageNumber = $pageNumber;
        $this->tableName = $tableName;

        $this->showKeys = $showKeys;
        $this->tableDimension = $tableDimension;

        $this->offline = $offline;

        $this->relation = new Relation($dbi);
        $this->font = new Font();

        // checks whether the table exists
        // and loads fields
        $this->validateTableAndLoadFields();
        // load table coordinates
        $this->loadCoordinates();
        // loads display field
        $this->loadDisplayField();
        // loads primary keys
        $this->loadPrimaryKey();
    }

    /**
     * Validate whether the table exists.
     */
    protected function validateTableAndLoadFields(): void
    {
        global $dbi;

        $sql = 'DESCRIBE ' . Util::backquote($this->tableName);
        $result = $dbi->tryQuery($sql);
        if (! $result || ! $result->numRows()) {
            $this->showMissingTableError();
            exit;
        }

        if ($this->showKeys) {
            $indexes = Index::getFromTable($this->tableName, $this->db);
            $all_columns = [];
            foreach ($indexes as $index) {
                $all_columns = array_merge(
                    $all_columns,
                    array_flip(array_keys($index->getColumns()))
                );
            }

            $this->fields = array_keys($all_columns);
        } else {
            $this->fields = $result->fetchAllColumn();
        }
    }

    /**
     * Displays an error when the table cannot be found.
     *
     * @abstract
     */
    abstract protected function showMissingTableError(): void;

    /**
     * Loads coordinates of a table
     */
    protected function loadCoordinates(): void
    {
        if (! isset($_POST['t_h']) || ! is_array($_POST['t_h'])) {
            return;
        }

        foreach (array_keys($_POST['t_h']) as $key) {
            $db = rawurldecode($_POST['t_db'][$key]);
            $tbl = rawurldecode($_POST['t_tbl'][$key]);
            if ($this->db . '.' . $this->tableName === $db . '.' . $tbl) {
                $this->x = (float) $_POST['t_x'][$key];
                $this->y = (float) $_POST['t_y'][$key];
                break;
            }
        }
    }

    /**
     * Loads the table's display field
     */
    protected function loadDisplayField(): void
    {
        $this->displayfield = $this->relation->getDisplayField($this->db, $this->tableName);
    }

    /**
     * Loads the PRIMARY key.
     */
    protected function loadPrimaryKey(): void
    {
        global $dbi;

        $result = $dbi->query('SHOW INDEX FROM ' . Util::backquote($this->tableName) . ';');
        if ($result->numRows() <= 0) {
            return;
        }

        while ($row = $result->fetchAssoc()) {
            if ($row['Key_name'] !== 'PRIMARY') {
                continue;
            }

            $this->primary[] = $row['Column_name'];
        }
    }

    /**
     * Returns title of the current table,
     * title can have the dimensions/co-ordinates of the table
     *
     * @return string title of the current table
     */
    protected function getTitle()
    {
        return ($this->tableDimension
            ? sprintf('%.0fx%0.f', $this->width, $this->heightCell)
            : ''
        )
        . ' ' . $this->tableName;
    }
}
