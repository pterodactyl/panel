<?php
/**
 * Contains PhpMyAdmin\Plugins\Schema\Eps\TableStatsEps class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Eps;

use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Plugins\Schema\TableStats;

use function __;
use function count;
use function max;
use function sprintf;

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the Tables in EPS.
 *
 * @see     Eps
 *
 * @property Eps $diagram
 */
class TableStatsEps extends TableStats
{
    /** @var int */
    public $height;

    /** @var int */
    public $currentCell = 0;

    /**
     * @see Eps
     * @see TableStatsEps::setWidthTable
     * @see TableStatsEps::setHeightTable
     *
     * @param Eps    $diagram         The EPS diagram
     * @param string $db              The database name
     * @param string $tableName       The table name
     * @param string $font            The font  name
     * @param int    $fontSize        The font size
     * @param int    $pageNumber      Page number
     * @param int    $same_wide_width The max width among tables
     * @param bool   $showKeys        Whether to display keys or not
     * @param bool   $tableDimension  Whether to display table position or not
     * @param bool   $offline         Whether the coordinates are sent
     *                                from the browser
     */
    public function __construct(
        $diagram,
        $db,
        $tableName,
        $font,
        $fontSize,
        $pageNumber,
        &$same_wide_width,
        $showKeys = false,
        $tableDimension = false,
        $offline = false
    ) {
        parent::__construct($diagram, $db, $pageNumber, $tableName, $showKeys, $tableDimension, $offline);

        // height and width
        $this->setHeightTable($fontSize);
        // setWidth must me after setHeight, because title
        // can include table height which changes table width
        $this->setWidthTable($font, $fontSize);
        if ($same_wide_width >= $this->width) {
            return;
        }

        $same_wide_width = $this->width;
    }

    /**
     * Displays an error when the table cannot be found.
     */
    protected function showMissingTableError(): void
    {
        ExportRelationSchema::dieSchema(
            $this->pageNumber,
            'EPS',
            sprintf(__('The %s table doesn\'t exist!'), $this->tableName)
        );
    }

    /**
     * Sets the width of the table
     *
     * @see Eps
     *
     * @param string $font     The font name
     * @param int    $fontSize The font size
     */
    private function setWidthTable($font, $fontSize): void
    {
        foreach ($this->fields as $field) {
            $this->width = max(
                $this->width,
                $this->font->getStringWidth($field, $font, (int) $fontSize)
            );
        }

        $this->width += $this->font->getStringWidth('      ', $font, (int) $fontSize);
        /*
         * it is unknown what value must be added, because
        * table title is affected by the table width value
        */
        while ($this->width < $this->font->getStringWidth($this->getTitle(), $font, (int) $fontSize)) {
            $this->width += 7;
        }
    }

    /**
     * Sets the height of the table
     *
     * @param int $fontSize The font size
     */
    private function setHeightTable($fontSize): void
    {
        $this->heightCell = $fontSize + 4;
        $this->height = (count($this->fields) + 1) * $this->heightCell;
    }

    /**
     * Draw the table
     *
     * @see Eps
     * @see Eps::line
     * @see Eps::rect
     *
     * @param bool $showColor Whether to display color
     */
    public function tableDraw($showColor): void
    {
        $this->diagram->rect($this->x, $this->y + 12, $this->width, $this->heightCell, 1);
        $this->diagram->showXY($this->getTitle(), $this->x + 5, $this->y + 14);
        foreach ($this->fields as $field) {
            $this->currentCell += $this->heightCell;
            $this->diagram->rect($this->x, $this->y + 12 + $this->currentCell, $this->width, $this->heightCell, 1);
            $this->diagram->showXY($field, $this->x + 5, $this->y + 14 + $this->currentCell);
        }
    }
}
