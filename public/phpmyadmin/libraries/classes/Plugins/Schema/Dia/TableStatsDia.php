<?php
/**
 * Contains PhpMyAdmin\Plugins\Schema\Dia\TableStatsDia class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Dia;

use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Plugins\Schema\TableStats;

use function __;
use function in_array;
use function shuffle;
use function sprintf;

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the Tables in dia XML document.
 *
 * @property Dia $diagram
 */
class TableStatsDia extends TableStats
{
    /** @var int */
    public $tableId;

    /** @var string */
    public $tableColor = '#000000';

    /**
     * @param Dia    $diagram    The current dia document
     * @param string $db         The database name
     * @param string $tableName  The table name
     * @param int    $pageNumber The current page number (from the
     *                           $cfg['Servers'][$i]['table_coords'] table)
     * @param bool   $showKeys   Whether to display ONLY keys or not
     * @param bool   $offline    Whether the coordinates are sent from the browser
     */
    public function __construct(
        $diagram,
        $db,
        $tableName,
        $pageNumber,
        $showKeys = false,
        $offline = false
    ) {
        parent::__construct($diagram, $db, $pageNumber, $tableName, $showKeys, false, $offline);

        /**
         * Every object in Dia document needs an ID to identify
         * so, we used a static variable to keep the things unique
        */
        $this->tableId = ++DiaRelationSchema::$objectId;
    }

    /**
     * Displays an error when the table cannot be found.
     */
    protected function showMissingTableError(): void
    {
        ExportRelationSchema::dieSchema(
            $this->pageNumber,
            'DIA',
            sprintf(__('The %s table doesn\'t exist!'), $this->tableName)
        );
    }

    /**
     * Do draw the table
     *
     * Tables are generated using object type Database - Table
     * primary fields are underlined in tables. Dia object
     * is used to generate the XML of Dia Document. Database Table
     * Object and their attributes are involved in the combination
     * of displaying Database - Table on Dia Document.
     *
     * @see    Dia
     *
     * @param bool $showColor Whether to show color for tables text or not
     *                        if showColor is true then an array of $listOfColors
     *                        will be used to choose the random colors for tables
     *                        text we can change/add more colors to this array
     */
    public function tableDraw($showColor): void
    {
        if ($showColor) {
            $listOfColors = [
                'FF0000',
                '000099',
                '00FF00',
            ];
            shuffle($listOfColors);
            $this->tableColor = '#' . $listOfColors[0] . '';
        } else {
            $this->tableColor = '#000000';
        }

        $factor = 0.1;

        $this->diagram->startElement('dia:object');
        $this->diagram->writeAttribute('type', 'Database - Table');
        $this->diagram->writeAttribute('version', '0');
        $this->diagram->writeAttribute('id', '' . $this->tableId . '');
        $this->diagram->writeRaw(
            '<dia:attribute name="obj_pos">
                <dia:point val="'
            . ($this->x * $factor) . ',' . ($this->y * $factor) . '"/>
            </dia:attribute>
            <dia:attribute name="obj_bb">
                <dia:rectangle val="'
            . ($this->x * $factor) . ',' . ($this->y * $factor) . ';9.97,9.2"/>
            </dia:attribute>
            <dia:attribute name="meta">
                <dia:composite type="dict"/>
            </dia:attribute>
            <dia:attribute name="elem_corner">
                <dia:point val="'
            . ($this->x * $factor) . ',' . ($this->y * $factor) . '"/>
            </dia:attribute>
            <dia:attribute name="elem_width">
                <dia:real val="5.9199999999999999"/>
            </dia:attribute>
            <dia:attribute name="elem_height">
                <dia:real val="3.5"/>
            </dia:attribute>
            <dia:attribute name="text_colour">
                <dia:color val="' . $this->tableColor . '"/>
            </dia:attribute>
            <dia:attribute name="line_colour">
                <dia:color val="#000000"/>
            </dia:attribute>
            <dia:attribute name="fill_colour">
                <dia:color val="#ffffff"/>
            </dia:attribute>
            <dia:attribute name="line_width">
                <dia:real val="0.10000000000000001"/>
            </dia:attribute>
            <dia:attribute name="name">
                <dia:string>#' . $this->tableName . '#</dia:string>
            </dia:attribute>
            <dia:attribute name="comment">
                <dia:string>##</dia:string>
            </dia:attribute>
            <dia:attribute name="visible_comment">
                <dia:boolean val="false"/>
            </dia:attribute>
            <dia:attribute name="tagging_comment">
                <dia:boolean val="false"/>
            </dia:attribute>
            <dia:attribute name="underline_primary_key">
                <dia:boolean val="true"/>
            </dia:attribute>
            <dia:attribute name="bold_primary_keys">
                <dia:boolean val="true"/>
            </dia:attribute>
            <dia:attribute name="normal_font">
                <dia:font family="monospace" style="0" name="Courier"/>
            </dia:attribute>
            <dia:attribute name="name_font">
                <dia:font family="sans" style="80" name="Helvetica-Bold"/>
            </dia:attribute>
            <dia:attribute name="comment_font">
                <dia:font family="sans" style="0" name="Helvetica"/>
            </dia:attribute>
            <dia:attribute name="normal_font_height">
                <dia:real val="0.80000000000000004"/>
            </dia:attribute>
            <dia:attribute name="name_font_height">
                <dia:real val="0.69999999999999996"/>
            </dia:attribute>
            <dia:attribute name="comment_font_height">
                <dia:real val="0.69999999999999996"/>
            </dia:attribute>'
        );

        $this->diagram->startElement('dia:attribute');
        $this->diagram->writeAttribute('name', 'attributes');

        foreach ($this->fields as $field) {
            $this->diagram->writeRaw(
                '<dia:composite type="table_attribute">
                    <dia:attribute name="name">
                <dia:string>#' . $field . '#</dia:string>
                </dia:attribute>
                <dia:attribute name="type">
                    <dia:string>##</dia:string>
                </dia:attribute>
                    <dia:attribute name="comment">
                <dia:string>##</dia:string>
                </dia:attribute>'
            );
            unset($pm);
            $pm = 'false';
            if (in_array($field, $this->primary)) {
                $pm = 'true';
            }

            if ($field == $this->displayfield) {
                $pm = 'false';
            }

            $this->diagram->writeRaw(
                '<dia:attribute name="primary_key">
                    <dia:boolean val="' . $pm . '"/>
                </dia:attribute>
                <dia:attribute name="nullable">
                    <dia:boolean val="false"/>
                </dia:attribute>
                <dia:attribute name="unique">
                    <dia:boolean val="' . $pm . '"/>
                </dia:attribute>
                </dia:composite>'
            );
        }

        $this->diagram->endElement();
        $this->diagram->endElement();
    }
}
