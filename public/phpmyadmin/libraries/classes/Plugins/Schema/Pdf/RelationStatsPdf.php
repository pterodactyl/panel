<?php
/**
 * Contains PhpMyAdmin\Plugins\Schema\Pdf\RelationStatsPdf class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Pdf;

use PhpMyAdmin\Plugins\Schema\RelationStats;

use function sqrt;

/**
 * Relation preferences/statistics
 *
 * This class fetches the table master and foreign fields positions
 * and helps in generating the Table references and then connects
 * master table's master field to foreign table's foreign key
 * in PDF document.
 *
 * @see Schema\Pdf::setDrawColor
 * @see Schema\Pdf::setLineWidthScale
 * @see Pdf::lineScale
 */
class RelationStatsPdf extends RelationStats
{
    /**
     * @param Pdf    $diagram       The PDF diagram
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
        $this->wTick = 5;
        parent::__construct($diagram, $master_table, $master_field, $foreign_table, $foreign_field);
    }

    /**
     * draws relation links and arrows shows foreign key relations
     *
     * @see    Pdf
     *
     * @param bool $showColor Whether to use one color per relation or not
     * @param int  $i         The id of the link to draw
     */
    public function relationDraw($showColor, $i): void
    {
        if ($showColor) {
            $d = $i % 6;
            $j = ($i - $d) / 6;
            $j %= 4;
            $j++;
            $case = [
                [
                    1,
                    0,
                    0,
                ],
                [
                    0,
                    1,
                    0,
                ],
                [
                    0,
                    0,
                    1,
                ],
                [
                    1,
                    1,
                    0,
                ],
                [
                    1,
                    0,
                    1,
                ],
                [
                    0,
                    1,
                    1,
                ],
            ];
            [$a, $b, $c] = $case[$d];
            $e = 1 - ($j - 1) / 6;
            $this->diagram->setDrawColor($a * 255 * $e, $b * 255 * $e, $c * 255 * $e);
        } else {
            $this->diagram->setDrawColor(0);
        }

        $this->diagram->setLineWidthScale(0.2);
        $this->diagram->lineScale($this->xSrc, $this->ySrc, $this->xSrc + $this->srcDir * $this->wTick, $this->ySrc);
        $this->diagram->lineScale(
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest,
            $this->xDest,
            $this->yDest
        );
        $this->diagram->setLineWidthScale(0.1);
        $this->diagram->lineScale(
            $this->xSrc + $this->srcDir * $this->wTick,
            $this->ySrc,
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest
        );
        /*
         * Draws arrows ->
        */
        $root2 = 2 * sqrt(2);
        $this->diagram->lineScale(
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc + $this->wTick / $root2
        );
        $this->diagram->lineScale(
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc - $this->wTick / $root2
        );

        $this->diagram->lineScale(
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest + $this->wTick / $root2
        );
        $this->diagram->lineScale(
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest - $this->wTick / $root2
        );
        $this->diagram->setDrawColor(0);
    }
}
