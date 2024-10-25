<?php
/**
 * Contains PhpMyAdmin\Plugins\Schema\Svg\TableStatsSvg class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Svg;

use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Plugins\Schema\TableStats;

use function __;
use function count;
use function in_array;
use function max;
use function sprintf;

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the Tables in SVG XML document.
 *
 * @see     Svg
 *
 * @property Svg $diagram
 */
class TableStatsSvg extends TableStats
{
    /** @var int */
    public $height;

    /** @var int */
    public $currentCell = 0;

    /**
     * @see Svg
     * @see TableStatsSvg::setWidthTable
     * @see TableStatsSvg::setHeightTable
     *
     * @param Svg    $diagram         The current SVG image document
     * @param string $db              The database name
     * @param string $tableName       The table name
     * @param string $font            Font face
     * @param int    $fontSize        The font size
     * @param int    $pageNumber      Page number
     * @param int    $same_wide_width The max. width among tables
     * @param bool   $showKeys        Whether to display keys or not
     * @param bool   $tableDimension  Whether to display table position or not
     * @param bool   $offline         Whether the coordinates are sent
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
            'SVG',
            sprintf(__('The %s table doesn\'t exist!'), $this->tableName)
        );
    }

    /**
     * Sets the width of the table
     *
     * @see    PMA_SVG
     *
     * @param string $font     The font size
     * @param int    $fontSize The font size
     */
    private function setWidthTable($font, $fontSize): void
    {
        foreach ($this->fields as $field) {
            $this->width = max(
                $this->width,
                $this->font->getStringWidth($field, $font, $fontSize)
            );
        }

        $this->width += $this->font->getStringWidth('  ', $font, $fontSize);

        /*
         * it is unknown what value must be added, because
         * table title is affected by the table width value
         */
        while ($this->width < $this->font->getStringWidth($this->getTitle(), $font, $fontSize)) {
            $this->width += 7;
        }
    }

    /**
     * Sets the height of the table
     *
     * @param int $fontSize font size
     */
    private function setHeightTable($fontSize): void
    {
        $this->heightCell = $fontSize + 4;
        $this->height = (count($this->fields) + 1) * $this->heightCell;
    }

    /**
     * draw the table
     *
     * @see Svg::printElement
     *
     * @param bool $showColor Whether to display color
     */
    public function tableDraw($showColor): void
    {
        $this->diagram->printElement(
            'rect',
            $this->x,
            $this->y,
            $this->width,
            $this->heightCell,
            null,
            'fill:#007;stroke:black;'
        );
        $this->diagram->printElement(
            'text',
            $this->x + 5,
            $this->y + 14,
            $this->width,
            $this->heightCell,
            $this->getTitle(),
            'fill:#fff;'
        );
        foreach ($this->fields as $field) {
            $this->currentCell += $this->heightCell;
            $fillColor = 'none';
            if ($showColor) {
                if (in_array($field, $this->primary)) {
                    $fillColor = '#aea';
                }

                if ($field == $this->displayfield) {
                    $fillColor = 'none';
                }
            }

            $this->diagram->printElement(
                'rect',
                $this->x,
                $this->y + $this->currentCell,
                $this->width,
                $this->heightCell,
                null,
                'fill:' . $fillColor . ';stroke:black;'
            );
            $this->diagram->printElement(
                'text',
                $this->x + 5,
                $this->y + 14 + $this->currentCell,
                $this->width,
                $this->heightCell,
                $field,
                'fill:black;'
            );
        }
    }
}
