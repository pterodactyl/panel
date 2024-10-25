<?php
/**
 * Handles DB QBE search
 */

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\SavedSearches;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function array_diff;
use function array_fill;
use function array_keys;
use function array_map;
use function array_multisort;
use function count;
use function explode;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function key;
use function max;
use function mb_strlen;
use function mb_strtoupper;
use function mb_substr;
use function min;
use function reset;
use function str_replace;
use function stripos;
use function strlen;

/**
 * Class to handle database QBE search
 */
class Qbe
{
    /**
     * Database name
     *
     * @var string
     */
    private $db;
    /**
     * Table Names (selected/non-selected)
     *
     * @var array
     */
    private $criteriaTables;
    /**
     * Column Names
     *
     * @var array
     */
    private $columnNames;
    /**
     * Number of columns
     *
     * @var int
     */
    private $criteriaColumnCount;
    /**
     * Number of Rows
     *
     * @var int
     */
    private $criteriaRowCount;
    /**
     * Whether to insert a new column
     *
     * @var array|null
     */
    private $criteriaColumnInsert;
    /**
     * Whether to delete a column
     *
     * @var array|null
     */
    private $criteriaColumnDelete;
    /**
     * Whether to insert a new row
     *
     * @var array
     */
    private $criteriaRowInsert;
    /**
     * Whether to delete a row
     *
     * @var array
     */
    private $criteriaRowDelete;
    /**
     * Already set criteria values
     *
     * @var array
     */
    private $criteria;
    /**
     * Previously set criteria values
     *
     * @var array
     */
    private $prevCriteria;
    /**
     * AND/OR relation b/w criteria columns
     *
     * @var array
     */
    private $criteriaAndOrColumn;
    /**
     * AND/OR relation b/w criteria rows
     *
     * @var array
     */
    private $criteriaAndOrRow;
    /**
     * Large width of a column
     *
     * @var string
     */
    private $realwidth;
    /**
     * Minimum width of a column
     *
     * @var int
     */
    private $formColumnWidth;
    /**
     * Selected columns in the form
     *
     * @var array
     */
    private $formColumns;
    /**
     * Entered aliases in the form
     *
     * @var array
     */
    private $formAliases;
    /**
     * Chosen sort options in the form
     *
     * @var array
     */
    private $formSorts;
    /**
     * Chosen sort orders in the form
     *
     * @var array
     */
    private $formSortOrders;
    /**
     * Show checkboxes in the form
     *
     * @var array
     */
    private $formShows;
    /**
     * Entered criteria values in the form
     *
     * @var array
     */
    private $formCriterions;
    /**
     * AND/OR column radio buttons in the form
     *
     * @var array
     */
    private $formAndOrCols;
    /**
     * AND/OR row radio buttons in the form
     *
     * @var array
     */
    private $formAndOrRows;
    /**
     * New column count in case of add/delete
     *
     * @var int
     */
    private $newColumnCount;
    /**
     * New row count in case of add/delete
     *
     * @var int
     */
    private $newRowCount;
    /**
     * List of saved searches
     *
     * @var array
     */
    private $savedSearchList = null;
    /**
     * Current search
     *
     * @var SavedSearches|null
     */
    private $currentSearch = null;

    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    public $dbi;

    /** @var Template */
    public $template;

    /**
     * @param Relation           $relation        Relation object
     * @param Template           $template        Template object
     * @param DatabaseInterface  $dbi             DatabaseInterface object
     * @param string             $dbname          Database name
     * @param array              $savedSearchList List of saved searches
     * @param SavedSearches|null $currentSearch   Current search id
     */
    public function __construct(
        Relation $relation,
        Template $template,
        $dbi,
        $dbname,
        array $savedSearchList = [],
        $currentSearch = null
    ) {
        $this->db = $dbname;
        $this->savedSearchList = $savedSearchList;
        $this->currentSearch = $currentSearch;
        $this->dbi = $dbi;
        $this->relation = $relation;
        $this->template = $template;

        $this->loadCriterias();
        // Sets criteria parameters
        $this->setSearchParams();
        $this->setCriteriaTablesAndColumns();
    }

    /**
     * Initialize criterias
     *
     * @return static
     */
    private function loadCriterias()
    {
        if ($this->currentSearch === null || $this->currentSearch->getCriterias() === null) {
            return $this;
        }

        $criterias = $this->currentSearch->getCriterias();
        $_POST = $criterias + $_POST;

        return $this;
    }

    /**
     * Getter for current search
     *
     * @return SavedSearches|null
     */
    private function getCurrentSearch()
    {
        return $this->currentSearch;
    }

    /**
     * Sets search parameters
     */
    private function setSearchParams(): void
    {
        $criteriaColumnCount = $this->initializeCriteriasCount();

        $this->criteriaColumnInsert = isset($_POST['criteriaColumnInsert']) && is_array($_POST['criteriaColumnInsert'])
            ? $_POST['criteriaColumnInsert']
            : null;
        $this->criteriaColumnDelete = isset($_POST['criteriaColumnDelete']) && is_array($_POST['criteriaColumnDelete'])
            ? $_POST['criteriaColumnDelete']
            : null;

        $this->prevCriteria = $_POST['prev_criteria'] ?? [];
        $this->criteria = $_POST['criteria'] ?? array_fill(0, $criteriaColumnCount, '');

        $this->criteriaRowInsert = $_POST['criteriaRowInsert'] ?? array_fill(0, $criteriaColumnCount, '');
        $this->criteriaRowDelete = $_POST['criteriaRowDelete'] ?? array_fill(0, $criteriaColumnCount, '');
        $this->criteriaAndOrRow = $_POST['criteriaAndOrRow'] ?? array_fill(0, $criteriaColumnCount, '');
        $this->criteriaAndOrColumn = $_POST['criteriaAndOrColumn'] ?? array_fill(0, $criteriaColumnCount, '');
        // sets minimum width
        $this->formColumnWidth = 12;
        $this->formColumns = [];
        $this->formSorts = [];
        $this->formShows = [];
        $this->formCriterions = [];
        $this->formAndOrRows = [];
        $this->formAndOrCols = [];
    }

    /**
     * Sets criteria tables and columns
     */
    private function setCriteriaTablesAndColumns(): void
    {
        // The tables list sent by a previously submitted form
        if (isset($_POST['TableList']) && is_array($_POST['TableList'])) {
            foreach ($_POST['TableList'] as $eachTable) {
                $this->criteriaTables[$eachTable] = ' selected="selected"';
            }
        }

        $allTables = $this->dbi->query('SHOW TABLES FROM ' . Util::backquote($this->db) . ';');
        $allTablesCount = $allTables->numRows();
        if ($allTablesCount == 0) {
            echo Message::error(__('No tables found in database.'))->getDisplay();
            exit;
        }

        // The tables list gets from MySQL
        foreach ($allTables->fetchAllColumn() as $table) {
            $columns = $this->dbi->getColumns($this->db, $table);

            if (empty($this->criteriaTables[$table]) && ! empty($_POST['TableList'])) {
                $this->criteriaTables[$table] = '';
            } else {
                $this->criteriaTables[$table] = ' selected="selected"';
            }

            // The fields list per selected tables
            if ($this->criteriaTables[$table] !== ' selected="selected"') {
                continue;
            }

            $eachTable = Util::backquote($table);
            $this->columnNames[] = $eachTable . '.*';
            foreach ($columns as $eachColumn) {
                $eachColumn = $eachTable . '.'
                    . Util::backquote($eachColumn['Field']);
                $this->columnNames[] = $eachColumn;
                // increase the width if necessary
                $this->formColumnWidth = max(
                    mb_strlen($eachColumn),
                    $this->formColumnWidth
                );
            }
        }

        // sets the largest width found
        $this->realwidth = $this->formColumnWidth . 'ex';
    }

    /**
     * Provides select options list containing column names
     *
     * @param int    $columnNumber Column Number (0,1,2) or more
     * @param string $selected     Selected criteria column name
     *
     * @return string HTML for select options
     */
    private function showColumnSelectCell($columnNumber, $selected = '')
    {
        return $this->template->render('database/qbe/column_select_cell', [
            'column_number' => $columnNumber,
            'column_names' => $this->columnNames,
            'selected' => $selected,
        ]);
    }

    /**
     * Provides select options list containing sort options (ASC/DESC)
     *
     * @param int    $columnNumber Column Number (0,1,2) or more
     * @param string $selected     Selected criteria 'ASC' or 'DESC'
     *
     * @return string HTML for select options
     */
    private function getSortSelectCell(
        $columnNumber,
        $selected = ''
    ) {
        return $this->template->render('database/qbe/sort_select_cell', [
            'real_width' => $this->realwidth,
            'column_number' => $columnNumber,
            'selected' => $selected,
        ]);
    }

    /**
     * Provides select options list containing sort order
     *
     * @param int $columnNumber Column Number (0,1,2) or more
     * @param int $sortOrder    Sort order
     *
     * @return string HTML for select options
     */
    private function getSortOrderSelectCell($columnNumber, $sortOrder)
    {
        $totalColumnCount = $this->getNewColumnCount();

        return $this->template->render('database/qbe/sort_order_select_cell', [
            'total_column_count' => $totalColumnCount,
            'column_number' => $columnNumber,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Returns the new column count after adding and removing columns as instructed
     *
     * @return int new column count
     */
    private function getNewColumnCount()
    {
        $totalColumnCount = $this->criteriaColumnCount;
        if (! empty($this->criteriaColumnInsert)) {
            $totalColumnCount += count($this->criteriaColumnInsert);
        }

        if (! empty($this->criteriaColumnDelete)) {
            $totalColumnCount -= count($this->criteriaColumnDelete);
        }

        return $totalColumnCount;
    }

    /**
     * Provides search form's row containing column select options
     *
     * @return string HTML for search table's row
     */
    private function getColumnNamesRow()
    {
        $htmlOutput = '';

        $newColumnCount = 0;
        for ($columnIndex = 0; $columnIndex < $this->criteriaColumnCount; $columnIndex++) {
            if (
                isset($this->criteriaColumnInsert[$columnIndex])
                && $this->criteriaColumnInsert[$columnIndex] === 'on'
            ) {
                $htmlOutput .= $this->showColumnSelectCell($newColumnCount);
                $newColumnCount++;
            }

            if (
                ! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$columnIndex])
                && $this->criteriaColumnDelete[$columnIndex] === 'on'
            ) {
                continue;
            }

            $selected = '';
            if (isset($_POST['criteriaColumn'][$columnIndex])) {
                $selected = $_POST['criteriaColumn'][$columnIndex];
                $this->formColumns[$newColumnCount] = $_POST['criteriaColumn'][$columnIndex];
            }

            $htmlOutput .= $this->showColumnSelectCell($newColumnCount, $selected);
            $newColumnCount++;
        }

        $this->newColumnCount = $newColumnCount;

        return $htmlOutput;
    }

    /**
     * Provides search form's row containing column aliases
     *
     * @return string HTML for search table's row
     */
    private function getColumnAliasRow()
    {
        $htmlOutput = '';

        $newColumnCount = 0;

        for ($colInd = 0; $colInd < $this->criteriaColumnCount; $colInd++) {
            if (
                ! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$colInd])
                && $this->criteriaColumnInsert[$colInd] === 'on'
            ) {
                $htmlOutput .= '<td class="text-center">';
                $htmlOutput .= '<input type="text"'
                    . ' name="criteriaAlias[' . $newColumnCount . ']">';
                $htmlOutput .= '</td>';
                $newColumnCount++;
            }

            if (
                ! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$colInd])
                && $this->criteriaColumnDelete[$colInd] === 'on'
            ) {
                continue;
            }

            $tmpAlias = '';
            if (! empty($_POST['criteriaAlias'][$colInd])) {
                $tmpAlias = $this->formAliases[$newColumnCount] = $_POST['criteriaAlias'][$colInd];
            }

            $htmlOutput .= '<td class="text-center">';
            $htmlOutput .= '<input type="text"'
                . ' name="criteriaAlias[' . $newColumnCount . ']"'
                . ' value="' . htmlspecialchars($tmpAlias) . '">';
            $htmlOutput .= '</td>';
            $newColumnCount++;
        }

        return $htmlOutput;
    }

    /**
     * Provides search form's row containing sort(ASC/DESC) select options
     *
     * @return string HTML for search table's row
     */
    private function getSortRow()
    {
        $htmlOutput = '';

        $newColumnCount = 0;

        for ($colInd = 0; $colInd < $this->criteriaColumnCount; $colInd++) {
            if (
                ! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$colInd])
                && $this->criteriaColumnInsert[$colInd] === 'on'
            ) {
                $htmlOutput .= $this->getSortSelectCell($newColumnCount);
                $newColumnCount++;
            }

            if (
                ! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$colInd])
                && $this->criteriaColumnDelete[$colInd] === 'on'
            ) {
                continue;
            }

            // If they have chosen all fields using the * selector,
            // then sorting is not available, Fix for Bug #570698
            if (
                isset($_POST['criteriaSort'][$colInd], $_POST['criteriaColumn'][$colInd])
                && mb_substr($_POST['criteriaColumn'][$colInd], -2) === '.*'
            ) {
                $_POST['criteriaSort'][$colInd] = '';
            }

            $selected = '';
            if (isset($_POST['criteriaSort'][$colInd])) {
                $this->formSorts[$newColumnCount] = $_POST['criteriaSort'][$colInd];

                if ($_POST['criteriaSort'][$colInd] === 'ASC') {
                    $selected = 'ASC';
                } elseif ($_POST['criteriaSort'][$colInd] === 'DESC') {
                    $selected = 'DESC';
                }
            } else {
                $this->formSorts[$newColumnCount] = '';
            }

            $htmlOutput .= $this->getSortSelectCell($newColumnCount, $selected);
            $newColumnCount++;
        }

        return $htmlOutput;
    }

    /**
     * Provides search form's row containing sort order
     *
     * @return string HTML for search table's row
     */
    private function getSortOrder()
    {
        $htmlOutput = '';

        $newColumnCount = 0;

        for ($colInd = 0; $colInd < $this->criteriaColumnCount; $colInd++) {
            if (
                ! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$colInd])
                && $this->criteriaColumnInsert[$colInd] === 'on'
            ) {
                $htmlOutput .= $this->getSortOrderSelectCell($newColumnCount, null);
                $newColumnCount++;
            }

            if (
                ! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$colInd])
                && $this->criteriaColumnDelete[$colInd] === 'on'
            ) {
                continue;
            }

            $sortOrder = null;
            if (! empty($_POST['criteriaSortOrder'][$colInd])) {
                $sortOrder = $this->formSortOrders[$newColumnCount] = $_POST['criteriaSortOrder'][$colInd];
            }

            $htmlOutput .= $this->getSortOrderSelectCell($newColumnCount, $sortOrder);
            $newColumnCount++;
        }

        return $htmlOutput;
    }

    /**
     * Provides search form's row containing SHOW checkboxes
     *
     * @return string HTML for search table's row
     */
    private function getShowRow()
    {
        $htmlOutput = '';

        $newColumnCount = 0;
        for ($columnIndex = 0; $columnIndex < $this->criteriaColumnCount; $columnIndex++) {
            if (
                ! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$columnIndex])
                && $this->criteriaColumnInsert[$columnIndex] === 'on'
            ) {
                $htmlOutput .= '<td class="text-center">';
                $htmlOutput .= '<input type="checkbox"'
                    . ' name="criteriaShow[' . $newColumnCount . ']">';
                $htmlOutput .= '</td>';
                $newColumnCount++;
            }

            if (
                ! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$columnIndex])
                && $this->criteriaColumnDelete[$columnIndex] === 'on'
            ) {
                continue;
            }

            if (isset($_POST['criteriaShow'][$columnIndex])) {
                $checkedOptions = ' checked="checked"';
                $this->formShows[$newColumnCount] = $_POST['criteriaShow'][$columnIndex];
            } else {
                $checkedOptions = '';
            }

            $htmlOutput .= '<td class="text-center">';
            $htmlOutput .= '<input type="checkbox"'
                . ' name="criteriaShow[' . $newColumnCount . ']"'
                . $checkedOptions . '>';
            $htmlOutput .= '</td>';
            $newColumnCount++;
        }

        return $htmlOutput;
    }

    /**
     * Provides search form's row containing criteria Inputboxes
     *
     * @return string HTML for search table's row
     */
    private function getCriteriaInputboxRow()
    {
        $htmlOutput = '';

        $newColumnCount = 0;
        for ($columnIndex = 0; $columnIndex < $this->criteriaColumnCount; $columnIndex++) {
            if (
                ! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$columnIndex])
                && $this->criteriaColumnInsert[$columnIndex] === 'on'
            ) {
                $htmlOutput .= '<td class="text-center">';
                $htmlOutput .= '<input type="text"'
                    . ' name="criteria[' . $newColumnCount . ']"'
                    . ' class="textfield"'
                    . ' style="width: ' . $this->realwidth . '"'
                    . ' size="20">';
                $htmlOutput .= '</td>';
                $newColumnCount++;
            }

            if (
                ! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$columnIndex])
                && $this->criteriaColumnDelete[$columnIndex] === 'on'
            ) {
                continue;
            }

            $tmpCriteria = '';
            if (isset($this->criteria[$columnIndex])) {
                $tmpCriteria = $this->criteria[$columnIndex];
            }

            if (
                (empty($this->prevCriteria)
                || ! isset($this->prevCriteria[$columnIndex]))
                || $this->prevCriteria[$columnIndex] != $tmpCriteria
            ) {
                $this->formCriterions[$newColumnCount] = $tmpCriteria;
            } else {
                $this->formCriterions[$newColumnCount] = $this->prevCriteria[$columnIndex];
            }

            $htmlOutput .= '<td class="text-center">';
            $htmlOutput .= '<input type="hidden"'
                . ' name="prev_criteria[' . $newColumnCount . ']"'
                . ' value="'
                . htmlspecialchars($this->formCriterions[$newColumnCount])
                . '">';
            $htmlOutput .= '<input type="text"'
                . ' name="criteria[' . $newColumnCount . ']"'
                . ' value="' . htmlspecialchars($tmpCriteria) . '"'
                . ' class="textfield"'
                . ' style="width: ' . $this->realwidth . '"'
                . ' size="20">';
            $htmlOutput .= '</td>';
            $newColumnCount++;
        }

        return $htmlOutput;
    }

    /**
     * Provides And/Or modification cell along with Insert/Delete options
     * (For modifying search form's table columns)
     *
     * @param int        $columnNumber Column Number (0,1,2) or more
     * @param array|null $selected     Selected criteria column name
     * @param bool       $lastColumn   Whether this is the last column
     *
     * @return string HTML for modification cell
     */
    private function getAndOrColCell(
        $columnNumber,
        $selected = null,
        $lastColumn = false
    ) {
        $htmlOutput = '<td class="text-center">';
        if (! $lastColumn) {
            $htmlOutput .= '<strong>' . __('Or:') . '</strong>';
            $htmlOutput .= '<input type="radio"'
                . ' name="criteriaAndOrColumn[' . $columnNumber . ']"'
                . ' value="or"' . ($selected['or'] ?? '') . '>';
            $htmlOutput .= '&nbsp;&nbsp;<strong>' . __('And:') . '</strong>';
            $htmlOutput .= '<input type="radio"'
                . ' name="criteriaAndOrColumn[' . $columnNumber . ']"'
                . ' value="and"' . ($selected['and'] ?? '') . '>';
        }

        $htmlOutput .= '<br>' . __('Ins');
        $htmlOutput .= '<input type="checkbox"'
            . ' name="criteriaColumnInsert[' . $columnNumber . ']">';
        $htmlOutput .= '&nbsp;&nbsp;' . __('Del');
        $htmlOutput .= '<input type="checkbox"'
            . ' name="criteriaColumnDelete[' . $columnNumber . ']">';
        $htmlOutput .= '</td>';

        return $htmlOutput;
    }

    /**
     * Provides search form's row containing column modifications options
     * (For modifying search form's table columns)
     *
     * @return string HTML for search table's row
     */
    private function getModifyColumnsRow()
    {
        $htmlOutput = '';

        $newColumnCount = 0;
        for ($columnIndex = 0; $columnIndex < $this->criteriaColumnCount; $columnIndex++) {
            if (
                ! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$columnIndex])
                && $this->criteriaColumnInsert[$columnIndex] === 'on'
            ) {
                $htmlOutput .= $this->getAndOrColCell($newColumnCount);
                $newColumnCount++;
            }

            if (
                ! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$columnIndex])
                && $this->criteriaColumnDelete[$columnIndex] === 'on'
            ) {
                continue;
            }

            if (isset($this->criteriaAndOrColumn[$columnIndex])) {
                $this->formAndOrCols[$newColumnCount] = $this->criteriaAndOrColumn[$columnIndex];
            }

            $checkedOptions = [];
            if (isset($this->criteriaAndOrColumn[$columnIndex]) && $this->criteriaAndOrColumn[$columnIndex] === 'or') {
                $checkedOptions['or'] = ' checked="checked"';
                $checkedOptions['and'] = '';
            } else {
                $checkedOptions['and'] = ' checked="checked"';
                $checkedOptions['or'] = '';
            }

            $htmlOutput .= $this->getAndOrColCell(
                $newColumnCount,
                $checkedOptions,
                $columnIndex + 1 == $this->criteriaColumnCount
            );
            $newColumnCount++;
        }

        return $htmlOutput;
    }

    /**
     * Provides rows for criteria inputbox Insert/Delete options
     * with AND/OR relationship modification options
     *
     * @param int $newRowIndex New row index if rows are added/deleted
     *
     * @return string HTML table rows
     */
    private function getInputboxRow($newRowIndex)
    {
        $htmlOutput = '';
        $newColumnCount = 0;
        for ($columnIndex = 0; $columnIndex < $this->criteriaColumnCount; $columnIndex++) {
            if (
                ! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$columnIndex])
                && $this->criteriaColumnInsert[$columnIndex] === 'on'
            ) {
                $orFieldName = 'Or' . $newRowIndex . '[' . $newColumnCount . ']';
                $htmlOutput .= '<td class="text-center">';
                $htmlOutput .= '<input type="text"'
                    . ' name="Or' . $orFieldName . '" class="textfield"'
                    . ' style="width: ' . $this->realwidth . '" size="20">';
                $htmlOutput .= '</td>';
                $newColumnCount++;
            }

            if (
                ! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$columnIndex])
                && $this->criteriaColumnDelete[$columnIndex] === 'on'
            ) {
                continue;
            }

            $or = 'Or' . $newRowIndex;
            if (! empty($_POST[$or]) && isset($_POST[$or][$columnIndex])) {
                $tmpOr = $_POST[$or][$columnIndex];
            } else {
                $tmpOr = '';
            }

            $htmlOutput .= '<td class="text-center">';
            $htmlOutput .= '<input type="text"'
                . ' name="Or' . $newRowIndex . '[' . $newColumnCount . ']"'
                . ' value="' . htmlspecialchars($tmpOr) . '" class="textfield"'
                . ' style="width: ' . $this->realwidth . '" size="20">';
            $htmlOutput .= '</td>';
            if (! empty(${$or}) && isset(${$or}[$columnIndex])) {
                $GLOBALS[${'cur' . $or}][$newColumnCount] = ${$or}[$columnIndex];
            }

            $newColumnCount++;
        }

        return $htmlOutput;
    }

    /**
     * Provides rows for criteria inputbox Insert/Delete options
     * with AND/OR relationship modification options
     *
     * @return string HTML table rows
     */
    private function getInsDelAndOrCriteriaRows()
    {
        $htmlOutput = '';
        $newRowCount = 0;
        $checkedOptions = [];
        for ($rowIndex = 0; $rowIndex <= $this->criteriaRowCount; $rowIndex++) {
            if (isset($this->criteriaRowInsert[$rowIndex]) && $this->criteriaRowInsert[$rowIndex] === 'on') {
                $checkedOptions['or'] = true;
                $checkedOptions['and'] = false;
                $htmlOutput .= '<tr class="noclick">';
                $htmlOutput .= $this->template->render('database/qbe/ins_del_and_or_cell', [
                    'row_index' => $newRowCount,
                    'checked_options' => $checkedOptions,
                ]);
                $htmlOutput .= $this->getInputboxRow($newRowCount);
                $newRowCount++;
                $htmlOutput .= '</tr>';
            }

            if (isset($this->criteriaRowDelete[$rowIndex]) && $this->criteriaRowDelete[$rowIndex] === 'on') {
                continue;
            }

            if (isset($this->criteriaAndOrRow[$rowIndex])) {
                $this->formAndOrRows[$newRowCount] = $this->criteriaAndOrRow[$rowIndex];
            }

            if (isset($this->criteriaAndOrRow[$rowIndex]) && $this->criteriaAndOrRow[$rowIndex] === 'and') {
                $checkedOptions['and'] = true;
                $checkedOptions['or'] = false;
            } else {
                $checkedOptions['or'] = true;
                $checkedOptions['and'] = false;
            }

            $htmlOutput .= '<tr class="noclick">';
            $htmlOutput .= $this->template->render('database/qbe/ins_del_and_or_cell', [
                'row_index' => $newRowCount,
                'checked_options' => $checkedOptions,
            ]);
            $htmlOutput .= $this->getInputboxRow($newRowCount);
            $newRowCount++;
            $htmlOutput .= '</tr>';
        }

        $this->newRowCount = $newRowCount;

        return $htmlOutput;
    }

    /**
     * Provides SELECT clause for building SQL query
     *
     * @return string Select clause
     */
    private function getSelectClause()
    {
        $selectClause = '';
        $selectClauses = [];
        for ($columnIndex = 0; $columnIndex < $this->criteriaColumnCount; $columnIndex++) {
            if (
                empty($this->formColumns[$columnIndex])
                || ! isset($this->formShows[$columnIndex])
                || $this->formShows[$columnIndex] !== 'on'
            ) {
                continue;
            }

            $select = $this->formColumns[$columnIndex];
            if (! empty($this->formAliases[$columnIndex])) {
                $select .= ' AS '
                    . Util::backquote($this->formAliases[$columnIndex]);
            }

            $selectClauses[] = $select;
        }

        if (! empty($selectClauses)) {
            $selectClause = 'SELECT ' . implode(', ', $selectClauses) . "\n";
        }

        return $selectClause;
    }

    /**
     * Provides WHERE clause for building SQL query
     *
     * @return string Where clause
     */
    private function getWhereClause()
    {
        $whereClause = '';
        $criteriaCount = 0;
        for ($columnIndex = 0; $columnIndex < $this->criteriaColumnCount; $columnIndex++) {
            if (
                isset($lastWhere, $this->formAndOrCols)
                && ! empty($this->formColumns[$columnIndex])
                && ! empty($this->formCriterions[$columnIndex])
                && $columnIndex
            ) {
                $whereClause .= ' '
                    . mb_strtoupper($this->formAndOrCols[$lastWhere])
                    . ' ';
            }

            if (empty($this->formColumns[$columnIndex]) || empty($this->formCriterions[$columnIndex])) {
                continue;
            }

            $whereClause .= '(' . $this->formColumns[$columnIndex] . ' '
                . $this->formCriterions[$columnIndex] . ')';
            $lastWhere = $columnIndex;
            $criteriaCount++;
        }

        if ($criteriaCount > 1) {
            $whereClause = '(' . $whereClause . ')';
        }

        // OR rows ${'cur' . $or}[$column_index]
        if (! isset($this->formAndOrRows)) {
            $this->formAndOrRows = [];
        }

        for ($rowIndex = 0; $rowIndex <= $this->criteriaRowCount; $rowIndex++) {
            $criteriaCount = 0;
            $queryOrWhere = '';
            $lastOrWhere = '';
            for ($columnIndex = 0; $columnIndex < $this->criteriaColumnCount; $columnIndex++) {
                if (
                    ! empty($this->formColumns[$columnIndex])
                    && ! empty($_POST['Or' . $rowIndex][$columnIndex])
                    && $columnIndex
                ) {
                    $queryOrWhere .= ' '
                        . mb_strtoupper($this->formAndOrCols[$lastOrWhere])
                        . ' ';
                }

                if (empty($this->formColumns[$columnIndex]) || empty($_POST['Or' . $rowIndex][$columnIndex])) {
                    continue;
                }

                $queryOrWhere .= '(' . $this->formColumns[$columnIndex]
                    . ' '
                    . $_POST['Or' . $rowIndex][$columnIndex]
                    . ')';
                $lastOrWhere = $columnIndex;
                $criteriaCount++;
            }

            if ($criteriaCount > 1) {
                $queryOrWhere = '(' . $queryOrWhere . ')';
            }

            if (empty($queryOrWhere)) {
                continue;
            }

            $whereClause .= "\n"
                . mb_strtoupper(isset($this->formAndOrRows[$rowIndex]) ? $this->formAndOrRows[$rowIndex] . ' ' : '')
                . $queryOrWhere;
        }

        if (! empty($whereClause) && $whereClause !== '()') {
            $whereClause = 'WHERE ' . $whereClause . "\n";
        }

        return $whereClause;
    }

    /**
     * Provides ORDER BY clause for building SQL query
     *
     * @return string Order By clause
     */
    private function getOrderByClause()
    {
        $orderByClause = '';
        $orderByClauses = [];

        // Create copy of instance variables
        $columns = $this->formColumns;
        $sort = $this->formSorts;
        $sortOrder = $this->formSortOrders;
        if (! empty($sortOrder) && count($sortOrder) == count($sort) && count($sortOrder) == count($columns)) {
            // Sort all three arrays based on sort order
            array_multisort($sortOrder, $sort, $columns);
        }

        for ($columnIndex = 0; $columnIndex < $this->criteriaColumnCount; $columnIndex++) {
            // if all columns are chosen with * selector,
            // then sorting isn't available
            // Fix for Bug #570698
            if (empty($columns[$columnIndex]) && empty($sort[$columnIndex])) {
                continue;
            }

            if (mb_substr($columns[$columnIndex], -2) === '.*') {
                continue;
            }

            if (empty($sort[$columnIndex])) {
                continue;
            }

            $orderByClauses[] = $columns[$columnIndex] . ' '
                . $sort[$columnIndex];
        }

        if (! empty($orderByClauses)) {
            $orderByClause = 'ORDER BY ' . implode(', ', $orderByClauses) . "\n";
        }

        return $orderByClause;
    }

    /**
     * Provides UNIQUE columns and INDEX columns present in criteria tables
     *
     * @param array $searchTables       Tables involved in the search
     * @param array $searchColumns      Columns involved in the search
     * @param array $whereClauseColumns Columns having criteria where clause
     *
     * @return array having UNIQUE and INDEX columns
     */
    private function getIndexes(
        array $searchTables,
        array $searchColumns,
        array $whereClauseColumns
    ) {
        $uniqueColumns = [];
        $indexColumns = [];

        foreach ($searchTables as $table) {
            $indexes = $this->dbi->getTableIndexes($this->db, $table);
            foreach ($indexes as $index) {
                $column = $table . '.' . $index['Column_name'];
                if (! isset($searchColumns[$column])) {
                    continue;
                }

                if ($index['Non_unique'] == 0) {
                    if (isset($whereClauseColumns[$column])) {
                        $uniqueColumns[$column] = 'Y';
                    } else {
                        $uniqueColumns[$column] = 'N';
                    }
                } else {
                    if (isset($whereClauseColumns[$column])) {
                        $indexColumns[$column] = 'Y';
                    } else {
                        $indexColumns[$column] = 'N';
                    }
                }
            }
        }

        return [
            'unique' => $uniqueColumns,
            'index' => $indexColumns,
        ];
    }

    /**
     * Provides UNIQUE columns and INDEX columns present in criteria tables
     *
     * @param array $searchTables       Tables involved in the search
     * @param array $searchColumns      Columns involved in the search
     * @param array $whereClauseColumns Columns having criteria where clause
     *
     * @return array having UNIQUE and INDEX columns
     */
    private function getLeftJoinColumnCandidates(
        array $searchTables,
        array $searchColumns,
        array $whereClauseColumns
    ) {
        $this->dbi->selectDb($this->db);

        // Get unique columns and index columns
        $indexes = $this->getIndexes($searchTables, $searchColumns, $whereClauseColumns);
        $uniqueColumns = $indexes['unique'];
        $indexColumns = $indexes['index'];

        [$candidateColumns, $needSort] = $this->getLeftJoinColumnCandidatesBest(
            $searchTables,
            $whereClauseColumns,
            $uniqueColumns,
            $indexColumns
        );

        // If we came up with $unique_columns (very good) or $index_columns (still
        // good) as $candidate_columns we want to check if we have any 'Y' there
        // (that would mean that they were also found in the whereclauses
        // which would be great). if yes, we take only those
        if ($needSort != 1) {
            return $candidateColumns;
        }

        $veryGood = [];
        $stillGood = [];
        foreach ($candidateColumns as $column => $isWhere) {
            $table = explode('.', $column);
            $table = $table[0];
            if ($isWhere === 'Y') {
                $veryGood[$column] = $table;
            } else {
                $stillGood[$column] = $table;
            }
        }

        if (count($veryGood) > 0) {
            $candidateColumns = $veryGood;
            // Candidates restricted in index+where
        } else {
            $candidateColumns = $stillGood;
            // None of the candidates where in a where-clause
        }

        return $candidateColumns;
    }

    /**
     * Provides the main table to form the LEFT JOIN clause
     *
     * @param array $searchTables       Tables involved in the search
     * @param array $searchColumns      Columns involved in the search
     * @param array $whereClauseColumns Columns having criteria where clause
     * @param array $whereClauseTables  Tables having criteria where clause
     *
     * @return string table name
     */
    private function getMasterTable(
        array $searchTables,
        array $searchColumns,
        array $whereClauseColumns,
        array $whereClauseTables
    ) {
        if (count($whereClauseTables) === 1) {
            // If there is exactly one column that has a decent where-clause
            // we will just use this
            return key($whereClauseTables);
        }

        // Now let's find out which of the tables has an index
        // (When the control user is the same as the normal user
        // because they are using one of their databases as pmadb,
        // the last db selected is not always the one where we need to work)
        $candidateColumns = $this->getLeftJoinColumnCandidates($searchTables, $searchColumns, $whereClauseColumns);

        // Generally, we need to display all the rows of foreign (referenced)
        // table, whether they have any matching row in child table or not.
        // So we select candidate tables which are foreign tables.
        $foreignTables = [];
        foreach ($candidateColumns as $oneTable) {
            $foreigners = $this->relation->getForeigners($this->db, $oneTable);
            foreach ($foreigners as $key => $foreigner) {
                if ($key !== 'foreign_keys_data') {
                    if (in_array($foreigner['foreign_table'], $candidateColumns)) {
                        $foreignTables[$foreigner['foreign_table']] = $foreigner['foreign_table'];
                    }

                    continue;
                }

                foreach ($foreigner as $oneKey) {
                    if (! in_array($oneKey['ref_table_name'], $candidateColumns)) {
                        continue;
                    }

                    $foreignTables[$oneKey['ref_table_name']] = $oneKey['ref_table_name'];
                }
            }
        }

        if (count($foreignTables)) {
            $candidateColumns = $foreignTables;
        }

        // If our array of candidates has more than one member we'll just
        // find the smallest table.
        // Of course the actual query would be faster if we check for
        // the Criteria which gives the smallest result set in its table,
        // but it would take too much time to check this
        if (! (count($candidateColumns) > 1)) {
            // Only one single candidate
            return reset($candidateColumns);
        }

        // Of course we only want to check each table once
        $checkedTables = $candidateColumns;
        $tsize = [];
        $maxsize = -1;
        $result = '';
        foreach ($candidateColumns as $table) {
            if ($checkedTables[$table] != 1) {
                $tableObj = new Table($table, $this->db);
                $tsize[$table] = $tableObj->countRecords();
                $checkedTables[$table] = 1;
            }

            if ($tsize[$table] <= $maxsize) {
                continue;
            }

            $maxsize = $tsize[$table];
            $result = $table;
        }

        // Return largest table
        return $result;
    }

    /**
     * Provides columns and tables that have valid where clause criteria
     *
     * @return array
     */
    private function getWhereClauseTablesAndColumns()
    {
        $whereClauseColumns = [];
        $whereClauseTables = [];

        // Now we need all tables that we have in the where clause
        for ($columnIndex = 0, $nb = count($this->criteria); $columnIndex < $nb; $columnIndex++) {
            $currentTable = explode('.', $_POST['criteriaColumn'][$columnIndex]);
            if (empty($currentTable[0]) || empty($currentTable[1])) {
                continue;
            }

            $table = str_replace('`', '', $currentTable[0]);
            $column = str_replace('`', '', $currentTable[1]);
            $column = $table . '.' . $column;
            // Now we know that our array has the same numbers as $criteria
            // we can check which of our columns has a where clause
            if (empty($this->criteria[$columnIndex])) {
                continue;
            }

            if (
                mb_substr($this->criteria[$columnIndex], 0, 1) !== '='
                && stripos($this->criteria[$columnIndex], 'is') === false
            ) {
                continue;
            }

            $whereClauseColumns[$column] = $column;
            $whereClauseTables[$table] = $table;
        }

        return [
            'where_clause_tables' => $whereClauseTables,
            'where_clause_columns' => $whereClauseColumns,
        ];
    }

    /**
     * Provides FROM clause for building SQL query
     *
     * @param array $formColumns List of selected columns in the form
     *
     * @return string FROM clause
     */
    private function getFromClause(array $formColumns)
    {
        $fromClause = '';
        if (empty($formColumns)) {
            return $fromClause;
        }

        // Initialize some variables
        $searchTables = $searchColumns = [];

        // We only start this if we have fields, otherwise it would be dumb
        foreach ($formColumns as $value) {
            $parts = explode('.', $value);
            if (empty($parts[0]) || empty($parts[1])) {
                continue;
            }

            $table = str_replace('`', '', $parts[0]);
            $searchTables[$table] = $table;
            $searchColumns[] = $table . '.' . str_replace('`', '', $parts[1]);
        }

        // Create LEFT JOINS out of Relations
        $fromClause = $this->getJoinForFromClause($searchTables, $searchColumns);

        // In case relations are not defined, just generate the FROM clause
        // from the list of tables, however we don't generate any JOIN
        if (empty($fromClause)) {
            // Create cartesian product
            $fromClause = implode(
                ', ',
                array_map([Util::class, 'backquote'], $searchTables)
            );
        }

        return $fromClause;
    }

    /**
     * Formulates the WHERE clause by JOINing tables
     *
     * @param array $searchTables  Tables involved in the search
     * @param array $searchColumns Columns involved in the search
     *
     * @return string table name
     */
    private function getJoinForFromClause(array $searchTables, array $searchColumns)
    {
        // $relations[master_table][foreign_table] => clause
        $relations = [];

        // Fill $relations with inter table relationship data
        foreach ($searchTables as $oneTable) {
            $this->loadRelationsForTable($relations, $oneTable);
        }

        // Get tables and columns with valid where clauses
        $validWhereClauses = $this->getWhereClauseTablesAndColumns();
        $whereClauseTables = $validWhereClauses['where_clause_tables'];
        $whereClauseColumns = $validWhereClauses['where_clause_columns'];

        // Get master table
        $master = $this->getMasterTable($searchTables, $searchColumns, $whereClauseColumns, $whereClauseTables);

        // Will include master tables and all tables that can be combined into
        // a cluster by their relation
        $finalized = [];
        if (strlen((string) $master) > 0) {
            // Add master tables
            $finalized[$master] = '';
        }

        // Fill the $finalized array with JOIN clauses for each table
        $this->fillJoinClauses($finalized, $relations, $searchTables);

        // JOIN clause
        $join = '';

        // Tables that can not be combined with the table cluster
        // which includes master table
        $unfinalized = array_diff($searchTables, array_keys($finalized));
        if (count($unfinalized) > 0) {
            // We need to look for intermediary tables to JOIN unfinalized tables
            // Heuristic to chose intermediary tables is to look for tables
            // having relationships with unfinalized tables
            foreach ($unfinalized as $oneTable) {
                $references = $this->relation->getChildReferences($this->db, $oneTable);
                foreach ($references as $columnReferences) {
                    foreach ($columnReferences as $reference) {
                        // Only from this schema
                        if ($reference['table_schema'] != $this->db) {
                            continue;
                        }

                        $table = $reference['table_name'];

                        $this->loadRelationsForTable($relations, $table);

                        // Make copies
                        $tempFinalized = $finalized;
                        $tempSearchTables = $searchTables;
                        $tempSearchTables[] = $table;

                        // Try joining with the added table
                        $this->fillJoinClauses($tempFinalized, $relations, $tempSearchTables);

                        $tempUnfinalized = array_diff(
                            $tempSearchTables,
                            array_keys($tempFinalized)
                        );
                        // Take greedy approach.
                        // If the unfinalized count drops we keep the new table
                        // and switch temporary varibles with the original ones
                        if (count($tempUnfinalized) < count($unfinalized)) {
                            $finalized = $tempFinalized;
                            $searchTables = $tempSearchTables;
                        }

                        // We are done if no unfinalized tables anymore
                        if (count($tempUnfinalized) === 0) {
                            break 3;
                        }
                    }
                }
            }

            $unfinalized = array_diff($searchTables, array_keys($finalized));
            // If there are still unfinalized tables
            if (count($unfinalized) > 0) {
                // Add these tables as cartesian product before joined tables
                $join .= implode(
                    ', ',
                    array_map([Util::class, 'backquote'], $unfinalized)
                );
            }
        }

        $first = true;
        // Add joined tables
        foreach ($finalized as $table => $clause) {
            if ($first) {
                if (! empty($join)) {
                    $join .= ', ';
                }

                $join .= Util::backquote($table);
                $first = false;
            } else {
                $join .= "\n    LEFT JOIN " . Util::backquote($table) . ' ON ' . $clause;
            }
        }

        return $join;
    }

    /**
     * Loads relations for a given table into the $relations array
     *
     * @param array  $relations array of relations
     * @param string $oneTable  the table
     */
    private function loadRelationsForTable(array &$relations, $oneTable): void
    {
        $relations[$oneTable] = [];

        $foreigners = $this->relation->getForeigners($GLOBALS['db'], $oneTable);
        foreach ($foreigners as $field => $foreigner) {
            // Foreign keys data
            if ($field === 'foreign_keys_data') {
                foreach ($foreigner as $oneKey) {
                    $clauses = [];
                    // There may be multiple column relations
                    foreach ($oneKey['index_list'] as $index => $oneField) {
                        $clauses[] = Util::backquote($oneTable) . '.'
                            . Util::backquote($oneField) . ' = '
                            . Util::backquote($oneKey['ref_table_name']) . '.'
                            . Util::backquote($oneKey['ref_index_list'][$index]);
                    }

                    // Combine multiple column relations with AND
                    $relations[$oneTable][$oneKey['ref_table_name']] = implode(' AND ', $clauses);
                }
            } else { // Internal relations
                $relations[$oneTable][$foreigner['foreign_table']] = Util::backquote($oneTable) . '.'
                    . Util::backquote((string) $field) . ' = '
                    . Util::backquote($foreigner['foreign_table']) . '.'
                    . Util::backquote($foreigner['foreign_field']);
            }
        }
    }

    /**
     * Fills the $finalized arrays with JOIN clauses for each of the tables
     *
     * @param array $finalized    JOIN clauses for each table
     * @param array $relations    Relations among tables
     * @param array $searchTables Tables involved in the search
     */
    private function fillJoinClauses(array &$finalized, array $relations, array $searchTables): void
    {
        while (true) {
            $added = false;
            foreach ($searchTables as $masterTable) {
                $foreignData = $relations[$masterTable];
                foreach ($foreignData as $foreignTable => $clause) {
                    if (! isset($finalized[$masterTable]) && isset($finalized[$foreignTable])) {
                        $finalized[$masterTable] = $clause;
                        $added = true;
                    } elseif (
                        ! isset($finalized[$foreignTable])
                        && isset($finalized[$masterTable])
                        && in_array($foreignTable, $searchTables)
                    ) {
                        $finalized[$foreignTable] = $clause;
                        $added = true;
                    }

                    if (! $added) {
                        continue;
                    }

                    // We are done if all tables are in $finalized
                    if (count($finalized) == count($searchTables)) {
                        return;
                    }
                }
            }

            // If no new tables were added during this iteration, break;
            if (! $added) {
                return;
            }
        }
    }

    /**
     * Provides the generated SQL query
     *
     * @param array $formColumns List of selected columns in the form
     *
     * @return string SQL query
     */
    private function getSQLQuery(array $formColumns)
    {
        $sqlQuery = '';
        // get SELECT clause
        $sqlQuery .= $this->getSelectClause();
        // get FROM clause
        $fromClause = $this->getFromClause($formColumns);
        if ($fromClause !== '') {
            $sqlQuery .= 'FROM ' . $fromClause . "\n";
        }

        // get WHERE clause
        $sqlQuery .= $this->getWhereClause();
        // get ORDER BY clause
        $sqlQuery .= $this->getOrderByClause();

        return $sqlQuery;
    }

    public function getSelectionForm(): string
    {
        $relationParameters = $this->relation->getRelationParameters();
        $savedSearchesField = $relationParameters->savedQueryByExampleSearchesFeature !== null
            ? $this->getSavedSearchesField()
            : '';

        $columnNamesRow = $this->getColumnNamesRow();
        $columnAliasRow = $this->getColumnAliasRow();
        $showRow = $this->getShowRow();
        $sortRow = $this->getSortRow();
        $sortOrder = $this->getSortOrder();
        $criteriaInputBoxRow = $this->getCriteriaInputboxRow();
        $insDelAndOrCriteriaRows = $this->getInsDelAndOrCriteriaRows();
        $modifyColumnsRow = $this->getModifyColumnsRow();

        $this->newRowCount--;
        $urlParams = [];
        $urlParams['db'] = $this->db;
        $urlParams['criteriaColumnCount'] = $this->newColumnCount;
        $urlParams['rows'] = $this->newRowCount;

        if (empty($this->formColumns)) {
            $this->formColumns = [];
        }

        $sqlQuery = $this->getSQLQuery($this->formColumns);

        return $this->template->render('database/qbe/selection_form', [
            'db' => $this->db,
            'url_params' => $urlParams,
            'db_link' => Generator::getDbLink($this->db),
            'criteria_tables' => $this->criteriaTables,
            'saved_searches_field' => $savedSearchesField,
            'column_names_row' => $columnNamesRow,
            'column_alias_row' => $columnAliasRow,
            'show_row' => $showRow,
            'sort_row' => $sortRow,
            'sort_order' => $sortOrder,
            'criteria_input_box_row' => $criteriaInputBoxRow,
            'ins_del_and_or_criteria_rows' => $insDelAndOrCriteriaRows,
            'modify_columns_row' => $modifyColumnsRow,
            'sql_query' => $sqlQuery,
        ]);
    }

    /**
     * Get fields to display
     *
     * @return string
     */
    private function getSavedSearchesField()
    {
        $htmlOutput = __('Saved bookmarked search:');
        $htmlOutput .= ' <select name="searchId" id="searchId">';
        $htmlOutput .= '<option value="">' . __('New bookmark') . '</option>';

        $currentSearch = $this->getCurrentSearch();
        $currentSearchId = null;
        $currentSearchName = null;
        if ($currentSearch !== null) {
            $currentSearchId = $currentSearch->getId();
            $currentSearchName = $currentSearch->getSearchName();
        }

        foreach ($this->savedSearchList as $id => $name) {
            $htmlOutput .= '<option value="' . htmlspecialchars((string) $id)
                . '" ' . (
                $id == $currentSearchId
                    ? 'selected="selected" '
                    : ''
                )
                . '>'
                . htmlspecialchars($name)
                . '</option>';
        }

        $htmlOutput .= '</select>';
        $htmlOutput .= '<input type="text" name="searchName" id="searchName" '
            . 'value="' . htmlspecialchars((string) $currentSearchName) . '">';
        $htmlOutput .= '<input type="hidden" name="action" id="action" value="">';
        $htmlOutput .= '<input class="btn btn-secondary" type="submit" name="saveSearch" id="saveSearch" '
            . 'value="' . __('Create bookmark') . '">';
        if ($currentSearchId !== null) {
            $htmlOutput .= '<input class="btn btn-secondary" type="submit" name="updateSearch" '
                . 'id="updateSearch" value="' . __('Update bookmark') . '">';
            $htmlOutput .= '<input class="btn btn-secondary" type="submit" name="deleteSearch" '
                . 'id="deleteSearch" value="' . __('Delete bookmark') . '">';
        }

        return $htmlOutput;
    }

    /**
     * Initialize _criteria_column_count
     *
     * @return int Previous number of columns
     */
    private function initializeCriteriasCount(): int
    {
        // sets column count
        $criteriaColumnCount = isset($_POST['criteriaColumnCount']) && is_numeric($_POST['criteriaColumnCount'])
            ? (int) $_POST['criteriaColumnCount']
            : 3;
        $criteriaColumnAdd = isset($_POST['criteriaColumnAdd']) && is_numeric($_POST['criteriaColumnAdd'])
            ? (int) $_POST['criteriaColumnAdd']
            : 0;
        $this->criteriaColumnCount = max($criteriaColumnCount + $criteriaColumnAdd, 0);

        // sets row count
        $rows = isset($_POST['rows']) && is_numeric($_POST['rows']) ? (int) $_POST['rows'] : 0;
        $criteriaRowAdd = isset($_POST['criteriaRowAdd']) && is_numeric($_POST['criteriaRowAdd'])
            ? (int) $_POST['criteriaRowAdd']
            : 0;
        $this->criteriaRowCount = min(
            100,
            max($rows + $criteriaRowAdd, 0)
        );

        return $criteriaColumnCount;
    }

    /**
     * Get best
     *
     * @param array      $searchTables       Tables involved in the search
     * @param array|null $whereClauseColumns Columns with where clause
     * @param array|null $uniqueColumns      Unique columns
     * @param array|null $indexColumns       Indexed columns
     *
     * @return array
     */
    private function getLeftJoinColumnCandidatesBest(
        array $searchTables,
        ?array $whereClauseColumns,
        ?array $uniqueColumns,
        ?array $indexColumns
    ) {
        // now we want to find the best.
        if (isset($uniqueColumns) && count($uniqueColumns) > 0) {
            $candidateColumns = $uniqueColumns;
            $needSort = 1;

            return [
                $candidateColumns,
                $needSort,
            ];
        }

        if (isset($indexColumns) && count($indexColumns) > 0) {
            $candidateColumns = $indexColumns;
            $needSort = 1;

            return [
                $candidateColumns,
                $needSort,
            ];
        }

        if (isset($whereClauseColumns) && count($whereClauseColumns) > 0) {
            $candidateColumns = $whereClauseColumns;
            $needSort = 0;

            return [
                $candidateColumns,
                $needSort,
            ];
        }

        $candidateColumns = $searchTables;
        $needSort = 0;

        return [
            $candidateColumns,
            $needSort,
        ];
    }
}
