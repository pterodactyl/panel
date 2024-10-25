<?php
/**
 * PDF schema handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Pdf;

use PhpMyAdmin\Pdf as PdfLib;
use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

use function __;
use function ceil;
use function getcwd;
use function in_array;
use function intval;
use function max;
use function min;
use function rsort;
use function sort;
use function sprintf;
use function str_replace;
use function strtotime;

// phpcs:disable PSR1.Files.SideEffects
/**
 * block attempts to directly run this script
 */
if (getcwd() == __DIR__) {
    die('Attack stopped');
}

// phpcs:enable

/**
 * Pdf Relation Schema Class
 *
 * Purpose of this class is to generate the PDF Document. PDF is widely
 * used format for documenting text,fonts,images and 3d vector graphics.
 *
 * This class inherits ExportRelationSchema class has common functionality added
 * to this class
 *
 * @property Pdf $diagram
 */
class PdfRelationSchema extends ExportRelationSchema
{
    /** @var bool */
    private $showGrid = false;

    /** @var bool */
    private $withDoc = false;

    /** @var string */
    private $tableOrder = '';

    /** @var TableStatsPdf[] */
    private $tables = [];

    /** @var string */
    private $ff = PdfLib::PMA_PDF_FONT;

    /** @var int|float */
    private $xMax = 0;

    /** @var int|float */
    private $yMax = 0;

    /** @var float|int */
    private $scale;

    /** @var int|float */
    private $xMin = 100000;

    /** @var int|float */
    private $yMin = 100000;

    /** @var int */
    private $topMargin = 10;

    /** @var int */
    private $bottomMargin = 10;

    /** @var int */
    private $leftMargin = 10;

    /** @var int */
    private $rightMargin = 10;

    /** @var int */
    private $tablewidth = 0;

    /** @var RelationStatsPdf[] */
    protected $relations = [];

    /** @var Transformations */
    private $transformations;

    /**
     * @see Schema\Pdf
     *
     * @param string $db database name
     */
    public function __construct($db)
    {
        $this->transformations = new Transformations();

        $this->setShowGrid(isset($_REQUEST['pdf_show_grid']));
        $this->setShowColor(isset($_REQUEST['pdf_show_color']));
        $this->setShowKeys(isset($_REQUEST['pdf_show_keys']));
        $this->setTableDimension(isset($_REQUEST['pdf_show_table_dimension']));
        $this->setAllTablesSameWidth(isset($_REQUEST['pdf_all_tables_same_width']));
        $this->setWithDataDictionary(isset($_REQUEST['pdf_with_doc']));
        $this->setTableOrder($_REQUEST['pdf_table_order']);
        $this->setOrientation((string) $_REQUEST['pdf_orientation']);
        $this->setPaper((string) $_REQUEST['pdf_paper']);

        // Initializes a new document
        parent::__construct(
            $db,
            new Pdf(
                $this->orientation,
                'mm',
                $this->paper,
                $this->pageNumber,
                $this->withDoc,
                $db
            )
        );
        $this->diagram->setTitle(
            sprintf(
                __('Schema of the %s database'),
                $this->db
            )
        );
        $this->diagram->setCMargin(0);
        $this->diagram->Open();
        $this->diagram->setAutoPageBreak(true);
        $this->diagram->setOffline($this->offline);

        $alltables = $this->getTablesFromRequest();
        if ($this->getTableOrder() === 'name_asc') {
            sort($alltables);
        } elseif ($this->getTableOrder() === 'name_desc') {
            rsort($alltables);
        }

        if ($this->withDoc) {
            $this->diagram->setAutoPageBreak(true, 15);
            $this->diagram->setCMargin(1);
            $this->dataDictionaryDoc($alltables);
            $this->diagram->setAutoPageBreak(true);
            $this->diagram->setCMargin(0);
        }

        $this->diagram->AddPage();

        if ($this->withDoc) {
            $this->diagram->setLink($this->diagram->customLinks['RT']['-'], -1);
            $this->diagram->Bookmark(__('Relational schema'));
            $this->diagram->setAlias('{00}', $this->diagram->PageNo());
            $this->topMargin = 28;
            $this->bottomMargin = 28;
        }

        /* snip */
        foreach ($alltables as $table) {
            if (! isset($this->tables[$table])) {
                $this->tables[$table] = new TableStatsPdf(
                    $this->diagram,
                    $this->db,
                    $table,
                    null,
                    $this->pageNumber,
                    $this->tablewidth,
                    $this->showKeys,
                    $this->tableDimension,
                    $this->offline
                );
            }

            if ($this->sameWide) {
                $this->tables[$table]->width = $this->tablewidth;
            }

            $this->setMinMax($this->tables[$table]);
        }

        // Defines the scale factor
        $innerWidth = $this->diagram->getPageWidth() - $this->rightMargin
            - $this->leftMargin;
        $innerHeight = $this->diagram->getPageHeight() - $this->topMargin
            - $this->bottomMargin;
        $this->scale = ceil(
            max(
                ($this->xMax - $this->xMin) / $innerWidth,
                ($this->yMax - $this->yMin) / $innerHeight
            ) * 100
        ) / 100;

        $this->diagram->setScale($this->scale, $this->xMin, $this->yMin, $this->leftMargin, $this->topMargin);
        // Builds and save the PDF document
        $this->diagram->setLineWidthScale(0.1);

        if ($this->showGrid) {
            $this->diagram->setFontSize(10);
            $this->strokeGrid();
        }

        $this->diagram->setFontSizeScale(14);
        // previous logic was checking master tables and foreign tables
        // but I think that looping on every table of the pdf page as a master
        // and finding its foreigns is OK (then we can support innodb)
        $seen_a_relation = false;
        foreach ($alltables as $one_table) {
            $exist_rel = $this->relation->getForeigners($this->db, $one_table, '', 'both');
            if (! $exist_rel) {
                continue;
            }

            $seen_a_relation = true;
            foreach ($exist_rel as $master_field => $rel) {
                // put the foreign table on the schema only if selected
                // by the user
                // (do not use array_search() because we would have to
                // to do a === false and this is not PHP3 compatible)
                if ($master_field !== 'foreign_keys_data') {
                    if (in_array($rel['foreign_table'], $alltables)) {
                        $this->addRelation($one_table, $master_field, $rel['foreign_table'], $rel['foreign_field']);
                    }

                    continue;
                }

                foreach ($rel as $one_key) {
                    if (! in_array($one_key['ref_table_name'], $alltables)) {
                        continue;
                    }

                    foreach ($one_key['index_list'] as $index => $one_field) {
                        $this->addRelation(
                            $one_table,
                            $one_field,
                            $one_key['ref_table_name'],
                            $one_key['ref_index_list'][$index]
                        );
                    }
                }
            }
        }

        if ($seen_a_relation) {
            $this->drawRelations();
        }

        $this->drawTables();
    }

    /**
     * Set Show Grid
     *
     * @param bool $value show grid of the document or not
     */
    public function setShowGrid($value): void
    {
        $this->showGrid = $value;
    }

    /**
     * Returns whether to show grid
     */
    public function isShowGrid(): bool
    {
        return $this->showGrid;
    }

    /**
     * Set Data Dictionary
     *
     * @param bool $value show selected database data dictionary or not
     */
    public function setWithDataDictionary($value): void
    {
        $this->withDoc = $value;
    }

    /**
     * Return whether to show selected database data dictionary or not
     */
    public function isWithDataDictionary(): bool
    {
        return $this->withDoc;
    }

    /**
     * Sets the order of the table in data dictionary
     *
     * @param string $value table order
     */
    public function setTableOrder($value): void
    {
        $this->tableOrder = $value;
    }

    /**
     * Returns the order of the table in data dictionary
     *
     * @return string table order
     */
    public function getTableOrder()
    {
        return $this->tableOrder;
    }

    /**
     * Output Pdf Document for download
     */
    public function showOutput(): void
    {
        $this->diagram->download($this->getFileName('.pdf'));
    }

    /**
     * Sets X and Y minimum and maximum for a table cell
     *
     * @param TableStatsPdf $table The table name of which sets XY co-ordinates
     */
    private function setMinMax($table): void
    {
        $this->xMax = max($this->xMax, $table->x + $table->width);
        $this->yMax = max($this->yMax, $table->y + $table->height);
        $this->xMin = min($this->xMin, $table->x);
        $this->yMin = min($this->yMin, $table->y);
    }

    /**
     * Defines relation objects
     *
     * @see setMinMax
     *
     * @param string $masterTable  The master table name
     * @param string $masterField  The relation field in the master table
     * @param string $foreignTable The foreign table name
     * @param string $foreignField The relation field in the foreign table
     */
    private function addRelation(
        $masterTable,
        $masterField,
        $foreignTable,
        $foreignField
    ): void {
        if (! isset($this->tables[$masterTable])) {
            $this->tables[$masterTable] = new TableStatsPdf(
                $this->diagram,
                $this->db,
                $masterTable,
                null,
                $this->pageNumber,
                $this->tablewidth,
                $this->showKeys,
                $this->tableDimension
            );
            $this->setMinMax($this->tables[$masterTable]);
        }

        if (! isset($this->tables[$foreignTable])) {
            $this->tables[$foreignTable] = new TableStatsPdf(
                $this->diagram,
                $this->db,
                $foreignTable,
                null,
                $this->pageNumber,
                $this->tablewidth,
                $this->showKeys,
                $this->tableDimension
            );
            $this->setMinMax($this->tables[$foreignTable]);
        }

        $this->relations[] = new RelationStatsPdf(
            $this->diagram,
            $this->tables[$masterTable],
            $masterField,
            $this->tables[$foreignTable],
            $foreignField
        );
    }

    /**
     * Draws the grid
     *
     * @see PMA_Schema_PDF
     */
    private function strokeGrid(): void
    {
        $gridSize = 10;
        $labelHeight = 4;
        $labelWidth = 5;
        if ($this->withDoc) {
            $topSpace = 6;
            $bottomSpace = 15;
        } else {
            $topSpace = 0;
            $bottomSpace = 0;
        }

        $this->diagram->setMargins(0, 0);
        $this->diagram->setDrawColor(200, 200, 200);
        // Draws horizontal lines
        $innerHeight = $this->diagram->getPageHeight() - $topSpace - $bottomSpace;
        for ($l = 0, $size = intval($innerHeight / $gridSize); $l <= $size; $l++) {
            $this->diagram->line(
                0,
                $l * $gridSize + $topSpace,
                $this->diagram->getPageWidth(),
                $l * $gridSize + $topSpace
            );
            // Avoid duplicates
            if ($l <= 0 || $l > intval(($innerHeight - $labelHeight) / $gridSize)) {
                continue;
            }

            $this->diagram->setXY(0, $l * $gridSize + $topSpace);
            $label = (string) sprintf(
                '%.0f',
                ($l * $gridSize + $topSpace - $this->topMargin)
                * $this->scale + $this->yMin
            );
            $this->diagram->Cell($labelWidth, $labelHeight, ' ' . $label);
        }

        // Draws vertical lines
        for ($j = 0, $size = intval($this->diagram->getPageWidth() / $gridSize); $j <= $size; $j++) {
            $this->diagram->line(
                $j * $gridSize,
                $topSpace,
                $j * $gridSize,
                $this->diagram->getPageHeight() - $bottomSpace
            );
            $this->diagram->setXY($j * $gridSize, $topSpace);
            $label = (string) sprintf('%.0f', ($j * $gridSize - $this->leftMargin) * $this->scale + $this->xMin);
            $this->diagram->Cell($labelWidth, $labelHeight, $label);
        }
    }

    /**
     * Draws relation arrows
     *
     * @see Relation_Stats_Pdf::relationdraw()
     */
    private function drawRelations(): void
    {
        $i = 0;
        foreach ($this->relations as $relation) {
            $relation->relationDraw($this->showColor, $i);
            $i++;
        }
    }

    /**
     * Draws tables
     *
     * @see TableStatsPdf::tableDraw()
     */
    private function drawTables(): void
    {
        foreach ($this->tables as $table) {
            $table->tableDraw(null, $this->withDoc, $this->showColor);
        }
    }

    /**
     * Generates data dictionary pages.
     *
     * @param array $alltables Tables to document.
     */
    public function dataDictionaryDoc(array $alltables): void
    {
        global $dbi;

        // TOC
        $this->diagram->AddPage($this->orientation);
        $this->diagram->Cell(0, 9, __('Table of contents'), 1, 0, 'C');
        $this->diagram->Ln(15);
        $i = 1;
        foreach ($alltables as $table) {
            $this->diagram->customLinks['doc'][$table]['-'] = $this->diagram->AddLink();
            $this->diagram->setX(10);
            // $this->diagram->Ln(1);
            $this->diagram->Cell(
                0,
                6,
                __('Page number:') . ' {' . sprintf('%02d', $i) . '}',
                0,
                0,
                'R',
                false,
                $this->diagram->customLinks['doc'][$table]['-']
            );
            $this->diagram->setX(10);
            $this->diagram->Cell(
                0,
                6,
                $i . ' ' . $table,
                0,
                1,
                'L',
                false,
                $this->diagram->customLinks['doc'][$table]['-']
            );
            // $this->diagram->Ln(1);
            $fields = $dbi->getColumns($this->db, $table);
            foreach ($fields as $row) {
                $this->diagram->setX(20);
                $field_name = $row['Field'];
                $this->diagram->customLinks['doc'][$table][$field_name] = $this->diagram->AddLink();
            }

            $i++;
        }

        $this->diagram->customLinks['RT']['-'] = $this->diagram->AddLink();
        $this->diagram->setX(10);
        $this->diagram->Cell(
            0,
            6,
            __('Page number:') . ' {00}',
            0,
            0,
            'R',
            false,
            $this->diagram->customLinks['RT']['-']
        );
        $this->diagram->setX(10);
        $this->diagram->Cell(
            0,
            6,
            $i . ' ' . __('Relational schema'),
            0,
            1,
            'L',
            false,
            $this->diagram->customLinks['RT']['-']
        );
        $z = 0;
        foreach ($alltables as $table) {
            $z++;
            $this->diagram->setAutoPageBreak(true, 15);
            $this->diagram->AddPage($this->orientation);
            $this->diagram->Bookmark($table);
            $this->diagram->setAlias(
                '{' . sprintf('%02d', $z) . '}',
                $this->diagram->PageNo()
            );
            $this->diagram->customLinks['RT'][$table]['-'] = $this->diagram->AddLink();
            $this->diagram->setLink($this->diagram->customLinks['doc'][$table]['-'], -1);
            $this->diagram->setFont($this->ff, 'B', 18);
            $this->diagram->Cell(
                0,
                8,
                $z . ' ' . $table,
                1,
                1,
                'C',
                false,
                $this->diagram->customLinks['RT'][$table]['-']
            );
            $this->diagram->setFont($this->ff, '', 8);
            $this->diagram->Ln();

            $relationParameters = $this->relation->getRelationParameters();
            $comments = $this->relation->getComments($this->db, $table);
            if ($relationParameters->browserTransformationFeature !== null) {
                $mime_map = $this->transformations->getMime($this->db, $table, true);
            }

            /**
             * Gets table information
             */
            $showtable = $dbi->getTable($this->db, $table)
                ->getStatusInfo();
            $show_comment = $showtable['Comment'] ?? '';
            $create_time = isset($showtable['Create_time'])
                ? Util::localisedDate(
                    strtotime($showtable['Create_time'])
                )
                : '';
            $update_time = isset($showtable['Update_time'])
                ? Util::localisedDate(
                    strtotime($showtable['Update_time'])
                )
                : '';
            $check_time = isset($showtable['Check_time'])
                ? Util::localisedDate(
                    strtotime($showtable['Check_time'])
                )
                : '';

            /**
             * Gets fields properties
             */
            $columns = $dbi->getColumns($this->db, $table);

            // Find which tables are related with the current one and write it in
            // an array
            $res_rel = $this->relation->getForeigners($this->db, $table);

            /**
             * Displays the comments of the table if MySQL >= 3.23
             */

            $break = false;
            if (! empty($show_comment)) {
                $this->diagram->Cell(
                    0,
                    3,
                    __('Table comments:') . ' ' . $show_comment,
                    0,
                    1
                );
                $break = true;
            }

            if (! empty($create_time)) {
                $this->diagram->Cell(
                    0,
                    3,
                    __('Creation:') . ' ' . $create_time,
                    0,
                    1
                );
                $break = true;
            }

            if (! empty($update_time)) {
                $this->diagram->Cell(
                    0,
                    3,
                    __('Last update:') . ' ' . $update_time,
                    0,
                    1
                );
                $break = true;
            }

            if (! empty($check_time)) {
                $this->diagram->Cell(
                    0,
                    3,
                    __('Last check:') . ' ' . $check_time,
                    0,
                    1
                );
                $break = true;
            }

            if ($break == true) {
                $this->diagram->Cell(0, 3, '', 0, 1);
                $this->diagram->Ln();
            }

            $this->diagram->setFont($this->ff, 'B');
            if (isset($this->orientation) && $this->orientation === 'L') {
                $this->diagram->Cell(25, 8, __('Column'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Type'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Attributes'), 1, 0, 'C');
                $this->diagram->Cell(10, 8, __('Null'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Default'), 1, 0, 'C');
                $this->diagram->Cell(25, 8, __('Extra'), 1, 0, 'C');
                $this->diagram->Cell(45, 8, __('Links to'), 1, 0, 'C');

                if ($this->paper === 'A4') {
                    $comments_width = 67;
                } else {
                    // this is really intended for 'letter'
                    /**
                     * @todo find optimal width for all formats
                     */
                    $comments_width = 50;
                }

                $this->diagram->Cell($comments_width, 8, __('Comments'), 1, 0, 'C');
                $this->diagram->Cell(45, 8, 'MIME', 1, 1, 'C');
                $this->diagram->setWidths(
                    [
                        25,
                        20,
                        20,
                        10,
                        20,
                        25,
                        45,
                        $comments_width,
                        45,
                    ]
                );
            } else {
                $this->diagram->Cell(20, 8, __('Column'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Type'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Attributes'), 1, 0, 'C');
                $this->diagram->Cell(10, 8, __('Null'), 1, 0, 'C');
                $this->diagram->Cell(15, 8, __('Default'), 1, 0, 'C');
                $this->diagram->Cell(15, 8, __('Extra'), 1, 0, 'C');
                $this->diagram->Cell(30, 8, __('Links to'), 1, 0, 'C');
                $this->diagram->Cell(30, 8, __('Comments'), 1, 0, 'C');
                $this->diagram->Cell(30, 8, 'MIME', 1, 1, 'C');
                $this->diagram->setWidths([20, 20, 20, 10, 15, 15, 30, 30, 30]);
            }

            $this->diagram->setFont($this->ff, '');

            foreach ($columns as $row) {
                $extracted_columnspec = Util::extractColumnSpec($row['Type']);
                $type = $extracted_columnspec['print_type'];
                $attribute = $extracted_columnspec['attribute'];
                if (! isset($row['Default'])) {
                    if ($row['Null'] != '' && $row['Null'] !== 'NO') {
                        $row['Default'] = 'NULL';
                    }
                }

                $field_name = $row['Field'];
                // $this->diagram->Ln();
                $this->diagram->customLinks['RT'][$table][$field_name] = $this->diagram->AddLink();
                $this->diagram->Bookmark($field_name, 1, -1);
                $this->diagram->setLink($this->diagram->customLinks['doc'][$table][$field_name], -1);
                $foreigner = $this->relation->searchColumnInForeigners($res_rel, $field_name);

                $linksTo = '';
                if ($foreigner) {
                    $linksTo = '-> ';
                    if ($foreigner['foreign_db'] != $this->db) {
                        $linksTo .= $foreigner['foreign_db'] . '.';
                    }

                    $linksTo .= $foreigner['foreign_table']
                        . '.' . $foreigner['foreign_field'];

                    if (isset($foreigner['on_update'])) { // not set for internal
                        $linksTo .= "\n" . 'ON UPDATE ' . $foreigner['on_update'];
                        $linksTo .= "\n" . 'ON DELETE ' . $foreigner['on_delete'];
                    }
                }

                $diagram_row = [
                    $field_name,
                    $type,
                    $attribute,
                    $row['Null'] == '' || $row['Null'] === 'NO'
                        ? __('No')
                        : __('Yes'),
                    $row['Default'] ?? '',
                    $row['Extra'],
                    $linksTo,
                    $comments[$field_name] ?? '',
                    isset($mime_map, $mime_map[$field_name])
                        ? str_replace('_', '/', $mime_map[$field_name]['mimetype'])
                        : '',
                ];
                $links = [];
                $links[0] = $this->diagram->customLinks['RT'][$table][$field_name];
                if (
                    $foreigner
                    && isset(
                        $this->diagram->customLinks['doc'][$foreigner['foreign_table']][$foreigner['foreign_field']]
                    )
                ) {
                    $foreignTable = $this->diagram->customLinks['doc'][$foreigner['foreign_table']];
                    $links[6] = $foreignTable[$foreigner['foreign_field']];
                }

                $this->diagram->row($diagram_row, $links);
            }

            $this->diagram->setFont($this->ff, '', 14);
        }
    }
}
