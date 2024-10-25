<?php
/**
 * Contains functions used by browse foreigners
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use function __;
use function array_keys;
use function asort;
use function ceil;
use function floor;
use function htmlspecialchars;
use function is_array;
use function mb_strlen;
use function mb_substr;

/**
 * PhpMyAdmin\BrowseForeigners class
 */
class BrowseForeigners
{
    /** @var int */
    private $limitChars;
    /** @var int */
    private $maxRows;
    /** @var int */
    private $repeatCells;
    /** @var bool */
    private $showAll;

    /** @var Template */
    public $template;

    /**
     * @param Template $template Template object
     */
    public function __construct(Template $template)
    {
        global $cfg;

        $this->template = $template;

        $this->limitChars = (int) $cfg['LimitChars'];
        $this->maxRows = (int) $cfg['MaxRows'];
        $this->repeatCells = (int) $cfg['RepeatCells'];
        $this->showAll = (bool) $cfg['ShowAll'];
    }

    /**
     * Function to get html for one relational key
     *
     * @param int    $horizontalCount    the current horizontal count
     * @param string $header             table header
     * @param array  $keys               all the keys
     * @param int    $indexByKeyname     index by keyname
     * @param array  $descriptions       descriptions
     * @param int    $indexByDescription index by description
     * @param string $currentValue       current value on the edit form
     *
     * @return array the generated html
     */
    private function getHtmlForOneKey(
        int $horizontalCount,
        string $header,
        array $keys,
        int $indexByKeyname,
        array $descriptions,
        int $indexByDescription,
        string $currentValue
    ): array {
        global $theme;

        $horizontalCount++;
        $output = '';

        // whether the key name corresponds to the selected value in the form
        $rightKeynameIsSelected = false;
        $leftKeynameIsSelected = false;

        if ($this->repeatCells > 0 && $horizontalCount > $this->repeatCells) {
            $output .= $header;
            $horizontalCount = 0;
        }

        // key names and descriptions for the left section,
        // sorted by key names
        $leftKeyname = $keys[$indexByKeyname];
        [
            $leftDescription,
            $leftDescriptionTitle,
        ] = $this->getDescriptionAndTitle($descriptions[$indexByKeyname]);

        // key names and descriptions for the right section,
        // sorted by descriptions
        $rightKeyname = $keys[$indexByDescription];
        [
            $rightDescription,
            $rightDescriptionTitle,
        ] = $this->getDescriptionAndTitle($descriptions[$indexByDescription]);

        $indexByDescription++;

        if (! empty($currentValue)) {
            $rightKeynameIsSelected = $rightKeyname == $currentValue;
            $leftKeynameIsSelected = $leftKeyname == $currentValue;
        }

        $output .= '<tr class="noclick">';

        $output .= $this->template->render('table/browse_foreigners/column_element', [
            'keyname' => $leftKeyname,
            'description' => $leftDescription,
            'title' => $leftDescriptionTitle,
            'is_selected' => $leftKeynameIsSelected,
            'nowrap' => true,
        ]);
        $output .= $this->template->render('table/browse_foreigners/column_element', [
            'keyname' => $leftKeyname,
            'description' => $leftDescription,
            'title' => $leftDescriptionTitle,
            'is_selected' => $leftKeynameIsSelected,
            'nowrap' => false,
        ]);

        $output .= '<td width="20%"><img src="'
            . ($theme instanceof Theme ? $theme->getImgPath('spacer.png') : '')
            . '" alt="" width="1" height="1"></td>';

        $output .= $this->template->render('table/browse_foreigners/column_element', [
            'keyname' => $rightKeyname,
            'description' => $rightDescription,
            'title' => $rightDescriptionTitle,
            'is_selected' => $rightKeynameIsSelected,
            'nowrap' => false,
        ]);
        $output .= $this->template->render('table/browse_foreigners/column_element', [
            'keyname' => $rightKeyname,
            'description' => $rightDescription,
            'title' => $rightDescriptionTitle,
            'is_selected' => $rightKeynameIsSelected,
            'nowrap' => true,
        ]);

        $output .= '</tr>';

        return [
            $output,
            $horizontalCount,
            $indexByDescription,
        ];
    }

    /**
     * Function to get html for relational field selection
     *
     * @param string      $db           current database
     * @param string      $table        current table
     * @param string      $field        field
     * @param array       $foreignData  foreign column data
     * @param string|null $fieldKey     field key
     * @param string      $currentValue current columns's value
     */
    public function getHtmlForRelationalFieldSelection(
        string $db,
        string $table,
        string $field,
        array $foreignData,
        ?string $fieldKey,
        string $currentValue
    ): string {
        $gotoPage = $this->getHtmlForGotoPage($foreignData);
        $foreignShowAll = $this->template->render('table/browse_foreigners/show_all', [
            'foreign_data' => $foreignData,
            'show_all' => $this->showAll,
            'max_rows' => $this->maxRows,
        ]);

        $output = '<form class="ajax" '
            . 'id="browse_foreign_form" name="browse_foreign_from" action="'
            . Url::getFromRoute('/browse-foreigners')
            . '" method="post"><fieldset class="row g-3 align-items-center mb-3">'
            . Url::getHiddenInputs($db, $table)
            . '<input type="hidden" name="field" value="' . htmlspecialchars($field)
            . '">'
            . '<input type="hidden" name="fieldkey" value="'
            . (isset($fieldKey) ? htmlspecialchars($fieldKey) : '') . '">';

        if (isset($_POST['rownumber'])) {
            $output .= '<input type="hidden" name="rownumber" value="'
                . htmlspecialchars((string) $_POST['rownumber']) . '">';
        }

        $filterValue = (isset($_POST['foreign_filter'])
            ? htmlspecialchars($_POST['foreign_filter'])
            : '');
        $output .= '<div class="col-auto">'
            . '<label class="form-label" for="input_foreign_filter">' . __('Search:') . '</label></div>'
            . '<div class="col-auto"><input class="form-control" type="text" name="foreign_filter" '
            . 'id="input_foreign_filter" '
            . 'value="' . $filterValue . '" data-old="' . $filterValue . '">'
            . '</div><div class="col-auto">'
            . '<input class="btn btn-primary" type="submit" name="submit_foreign_filter" value="'
            . __('Go') . '">'
            . '</div>'
            . '<div class="col-auto">' . $gotoPage . '</div>'
            . '<div class="col-auto">' . $foreignShowAll . '</div>'
            . '</fieldset>'
            . '</form>';

        $output .= '<table class="table table-striped table-hover" id="browse_foreign_table">';

        if (! is_array($foreignData['disp_row'])) {
            return $output . '</tbody>'
                . '</table>';
        }

        $header = '<tr>
            <th>' . __('Keyname') . '</th>
            <th>' . __('Description') . '</th>
            <td width="20%"></td>
            <th>' . __('Description') . '</th>
            <th>' . __('Keyname') . '</th>
        </tr>';

        $output .= '<thead>' . $header . '</thead>' . "\n"
            . '<tfoot>' . $header . '</tfoot>' . "\n"
            . '<tbody>' . "\n";

        $descriptions = [];
        $keys = [];
        foreach ($foreignData['disp_row'] as $relrow) {
            if ($foreignData['foreign_display'] != false) {
                $descriptions[] = $relrow[$foreignData['foreign_display']] ?? '';
            } else {
                $descriptions[] = '';
            }

            $keys[] = $relrow[$foreignData['foreign_field']];
        }

        asort($keys);

        $horizontalCount = 0;
        $indexByDescription = 0;

        foreach (array_keys($keys) as $indexByKeyname) {
            [
                $html,
                $horizontalCount,
                $indexByDescription,
            ] = $this->getHtmlForOneKey(
                $horizontalCount,
                $header,
                $keys,
                $indexByKeyname,
                $descriptions,
                $indexByDescription,
                $currentValue
            );
            $output .= $html;
        }

        $output .= '</tbody></table>';

        return $output;
    }

    /**
     * Get the description (possibly truncated) and the title
     *
     * @param string $description the key name's description
     *
     * @return array the new description and title
     */
    private function getDescriptionAndTitle(string $description): array
    {
        if (mb_strlen($description) <= $this->limitChars) {
            $descriptionTitle = '';
        } else {
            $descriptionTitle = $description;
            $description = mb_substr($description, 0, $this->limitChars)
            . '...';
        }

        return [
            $description,
            $descriptionTitle,
        ];
    }

    /**
     * Function to get html for the goto page option
     *
     * @param array|null $foreignData foreign data
     */
    private function getHtmlForGotoPage(?array $foreignData): string
    {
        $gotoPage = '';
        isset($_POST['pos']) ? $pos = $_POST['pos'] : $pos = 0;
        if ($foreignData === null || ! is_array($foreignData['disp_row'])) {
            return $gotoPage;
        }

        $pageNow = (int) floor($pos / $this->maxRows) + 1;
        $nbTotalPage = (int) ceil($foreignData['the_total'] / $this->maxRows);

        if ($foreignData['the_total'] > $this->maxRows) {
            $gotoPage = Util::pageselector(
                'pos',
                $this->maxRows,
                $pageNow,
                $nbTotalPage,
                200,
                5,
                5,
                20,
                10,
                __('Page number:')
            );
        }

        return $gotoPage;
    }

    /**
     * Function to get foreign limit
     *
     * @param string|null $foreignShowAll foreign navigation
     */
    public function getForeignLimit(?string $foreignShowAll): ?string
    {
        if (isset($foreignShowAll) && $foreignShowAll == __('Show all')) {
            return null;
        }

        isset($_POST['pos']) ? $pos = $_POST['pos'] : $pos = 0;

        return 'LIMIT ' . $pos . ', ' . $this->maxRows . ' ';
    }
}
