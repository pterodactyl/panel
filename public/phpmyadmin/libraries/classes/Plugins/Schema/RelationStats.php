<?php
/**
 * Contains abstract class to hold relation preferences/statistics
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema;

use function abs;
use function array_search;
use function min;

/**
 * Relations preferences/statistics
 *
 * This class fetches the table master and foreign fields positions
 * and helps in generating the Table references and then connects
 * master table's master field to foreign table's foreign key.
 *
 * @abstract
 */
abstract class RelationStats
{
    /** @var object */
    protected $diagram;

    /** @var mixed */
    public $xSrc;

    /** @var mixed */
    public $ySrc;

    /** @var int */
    public $srcDir;

    /** @var int */
    public $destDir;

    /** @var mixed */
    public $xDest;

    /** @var mixed */
    public $yDest;

    /** @var int */
    public $wTick = 0;

    /**
     * @param object $diagram       The diagram
     * @param string $master_table  The master table name
     * @param string $master_field  The relation field in the master table
     * @param string $foreign_table The foreign table name
     * @param string $foreign_field The relation field in the foreign table
     */
    public function __construct(
        $diagram,
        $master_table,
        $master_field,
        $foreign_table,
        $foreign_field
    ) {
        $this->diagram = $diagram;

        $src_pos = $this->getXy($master_table, $master_field);
        $dest_pos = $this->getXy($foreign_table, $foreign_field);
        /*
         * [0] is x-left
        * [1] is x-right
        * [2] is y
        */
        $src_left = $src_pos[0] - $this->wTick;
        $src_right = $src_pos[1] + $this->wTick;
        $dest_left = $dest_pos[0] - $this->wTick;
        $dest_right = $dest_pos[1] + $this->wTick;

        $d1 = abs($src_left - $dest_left);
        $d2 = abs($src_right - $dest_left);
        $d3 = abs($src_left - $dest_right);
        $d4 = abs($src_right - $dest_right);
        $d = min($d1, $d2, $d3, $d4);

        if ($d == $d1) {
            $this->xSrc = $src_pos[0];
            $this->srcDir = -1;
            $this->xDest = $dest_pos[0];
            $this->destDir = -1;
        } elseif ($d == $d2) {
            $this->xSrc = $src_pos[1];
            $this->srcDir = 1;
            $this->xDest = $dest_pos[0];
            $this->destDir = -1;
        } elseif ($d == $d3) {
            $this->xSrc = $src_pos[0];
            $this->srcDir = -1;
            $this->xDest = $dest_pos[1];
            $this->destDir = 1;
        } else {
            $this->xSrc = $src_pos[1];
            $this->srcDir = 1;
            $this->xDest = $dest_pos[1];
            $this->destDir = 1;
        }

        $this->ySrc = $src_pos[2];
        $this->yDest = $dest_pos[2];
    }

    /**
     * Gets arrows coordinates
     *
     * @param TableStats $table  The table
     * @param string     $column The relation column name
     *
     * @return array Arrows coordinates
     */
    private function getXy($table, $column)
    {
        $pos = array_search($column, $table->fields);

        // x_left, x_right, y
        return [
            $table->x,
            $table->x + $table->width,
            $table->y + ($pos + 1.5) * $table->heightCell,
        ];
    }
}
