<?php
/**
 * Contains PhpMyAdmin\Plugins\Schema\ExportRelationSchema class which is
 * inherited by all schema classes.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function rawurldecode;

/**
 * This class is inherited by all schema classes
 * It contains those methods which are common in them
 * it works like factory pattern
 */
class ExportRelationSchema
{
    /** @var string */
    protected $db;

    /** @var Dia\Dia|Eps\Eps|Pdf\Pdf|Svg\Svg|null */
    protected $diagram;

    /** @var bool */
    protected $showColor = false;

    /** @var bool */
    protected $tableDimension = false;

    /** @var bool */
    protected $sameWide = false;

    /** @var bool */
    protected $showKeys = false;

    /** @var string */
    protected $orientation = 'L';

    /** @var string */
    protected $paper = 'A4';

    /** @var int */
    protected $pageNumber = 0;

    /** @var bool */
    protected $offline = false;

    /** @var Relation */
    protected $relation;

    /**
     * @param string                               $db      database name
     * @param Pdf\Pdf|Svg\Svg|Eps\Eps|Dia\Dia|null $diagram schema diagram
     */
    public function __construct($db, $diagram)
    {
        global $dbi;

        $this->db = $db;
        $this->diagram = $diagram;
        $this->setPageNumber((int) $_REQUEST['page_number']);
        $this->setOffline(isset($_REQUEST['offline_export']));
        $this->relation = new Relation($dbi);
    }

    /**
     * Set Page Number
     *
     * @param int $value Page Number of the document to be created
     */
    public function setPageNumber(int $value): void
    {
        $this->pageNumber = $value;
    }

    /**
     * Returns the schema page number
     *
     * @return int schema page number
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * Sets showColor
     *
     * @param bool $value whether to show colors
     */
    public function setShowColor(bool $value): void
    {
        $this->showColor = $value;
    }

    /**
     * Returns whether to show colors
     */
    public function isShowColor(): bool
    {
        return $this->showColor;
    }

    /**
     * Set Table Dimension
     *
     * @param bool $value show table co-ordinates or not
     */
    public function setTableDimension(bool $value): void
    {
        $this->tableDimension = $value;
    }

    /**
     * Returns whether to show table dimensions
     */
    public function isTableDimension(): bool
    {
        return $this->tableDimension;
    }

    /**
     * Set same width of All Tables
     *
     * @param bool $value set same width of all tables or not
     */
    public function setAllTablesSameWidth(bool $value): void
    {
        $this->sameWide = $value;
    }

    /**
     * Returns whether to use same width for all tables or not
     */
    public function isAllTableSameWidth(): bool
    {
        return $this->sameWide;
    }

    /**
     * Set Show only keys
     *
     * @param bool $value show only keys or not
     */
    public function setShowKeys(bool $value): void
    {
        $this->showKeys = $value;
    }

    /**
     * Returns whether to show keys
     */
    public function isShowKeys(): bool
    {
        return $this->showKeys;
    }

    /**
     * Set Orientation
     *
     * @param string $value Orientation will be portrait or landscape
     */
    public function setOrientation(string $value): void
    {
        $this->orientation = $value === 'P' ? 'P' : 'L';
    }

    /**
     * Returns orientation
     *
     * @return string orientation
     */
    public function getOrientation()
    {
        return $this->orientation;
    }

    /**
     * Set type of paper
     *
     * @param string $value paper type can be A4 etc
     */
    public function setPaper(string $value): void
    {
        $this->paper = $value;
    }

    /**
     * Returns the paper size
     *
     * @return string paper size
     */
    public function getPaper()
    {
        return $this->paper;
    }

    /**
     * Set whether the document is generated from client side DB
     *
     * @param bool $value offline or not
     */
    public function setOffline(bool $value): void
    {
        $this->offline = $value;
    }

    /**
     * Returns whether the client side database is used
     */
    public function isOffline(): bool
    {
        return $this->offline;
    }

    /**
     * Get the table names from the request
     *
     * @return string[] an array of table names
     */
    protected function getTablesFromRequest(): array
    {
        $tables = [];
        if (isset($_POST['t_tbl'])) {
            foreach ($_POST['t_tbl'] as $table) {
                $tables[] = rawurldecode($table);
            }
        }

        return $tables;
    }

    /**
     * Returns the file name
     *
     * @param string $extension file extension
     *
     * @return string file name
     */
    protected function getFileName($extension): string
    {
        global $dbi;

        $pdfFeature = $this->relation->getRelationParameters()->pdfFeature;

        $filename = $this->db . $extension;
        // Get the name of this page to use as filename
        if ($this->pageNumber != -1 && ! $this->offline && $pdfFeature !== null) {
            $_name_sql = 'SELECT page_descr FROM '
                . Util::backquote($pdfFeature->database) . '.'
                . Util::backquote($pdfFeature->pdfPages)
                . ' WHERE page_nr = ' . $this->pageNumber;
            $_name_rs = $dbi->queryAsControlUser($_name_sql);
            $_name_row = $_name_rs->fetchRow();
            $filename = $_name_row[0] . $extension;
        }

        return $filename;
    }

    /**
     * Displays an error message
     *
     * @param int    $pageNumber    ID of the chosen page
     * @param string $type          Schema Type
     * @param string $error_message The error message
     */
    public static function dieSchema($pageNumber, $type = '', $error_message = ''): void
    {
        echo '<p><strong>' , __('SCHEMA ERROR: ') , $type , '</strong></p>' , "\n";
        if (! empty($error_message)) {
            $error_message = htmlspecialchars($error_message);
        }

        echo '<p>' , "\n";
        echo '    ' , $error_message , "\n";
        echo '</p>' , "\n";
        echo '<a href="';
        echo Url::getFromRoute('/database/designer', [
            'db' => $GLOBALS['db'],
            'server' => $GLOBALS['server'],
            'page' => $pageNumber,
        ]);
        echo '">' . __('Back') . '</a>';
        echo "\n";
        exit;
    }
}
