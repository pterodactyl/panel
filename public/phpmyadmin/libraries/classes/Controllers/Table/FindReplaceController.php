<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_key_exists;
use function count;
use function is_array;
use function mb_strtolower;
use function preg_match;
use function preg_replace;
use function str_contains;
use function str_ireplace;
use function str_replace;
use function strncasecmp;

/**
 * Handles find and replace tab.
 *
 * Displays find and replace form, allows previewing and do the replacing.
 */
class FindReplaceController extends AbstractController
{
    /** @var array */
    private $columnNames;

    /** @var array */
    private $columnTypes;

    /** @var string */
    private $connectionCharSet;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->dbi = $dbi;

        $this->columnNames = [];
        $this->columnTypes = [];
        $this->loadTableInfo();
        $this->connectionCharSet = (string) $this->dbi->fetchValue('SELECT @@character_set_connection');
    }

    public function __invoke(): void
    {
        global $db, $table, $urlParams, $cfg, $errorUrl;

        Util::checkParameters(['db', 'table']);

        $urlParams = ['db' => $db, 'table' => $table];
        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $errorUrl .= Url::getCommon($urlParams, '&');

        DbTableExists::check();

        if (isset($_POST['find'])) {
            $this->findAction();

            return;
        }

        $this->addScriptFiles(['table/find_replace.js']);

        if (isset($_POST['replace'])) {
            $this->replaceAction();
        }

        // Displays the find and replace form
        $this->displaySelectionFormAction();
    }

    /**
     * Gets all the columns of a table along with their types.
     */
    private function loadTableInfo(): void
    {
        // Gets the list and number of columns
        $columns = $this->dbi->getColumns($this->db, $this->table, true);

        foreach ($columns as $row) {
            // set column name
            $this->columnNames[] = $row['Field'];

            $type = (string) $row['Type'];
            // reformat mysql query output
            if (strncasecmp($type, 'set', 3) == 0 || strncasecmp($type, 'enum', 4) == 0) {
                $type = str_replace(',', ', ', $type);
            } else {
                // strip the "BINARY" attribute, except if we find "BINARY(" because
                // this would be a BINARY or VARBINARY column type
                if (! preg_match('@BINARY[\(]@i', $type)) {
                    $type = str_ireplace('BINARY', '', $type);
                }

                $type = str_ireplace('ZEROFILL', '', $type);
                $type = str_ireplace('UNSIGNED', '', $type);
                $type = mb_strtolower($type);
            }

            if (empty($type)) {
                $type = '&nbsp;';
            }

            $this->columnTypes[] = $type;
        }
    }

    /**
     * Display selection form action
     */
    public function displaySelectionFormAction(): void
    {
        global $goto;

        if (! isset($goto)) {
            $goto = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
        }

        $column_names = $this->columnNames;
        $column_types = $this->columnTypes;
        $types = [];
        $num_cols = count($column_names);
        for ($i = 0; $i < $num_cols; $i++) {
            $types[$column_names[$i]] = preg_replace('@\\(.*@s', '', $column_types[$i]);
        }

        $this->render('table/find_replace/index', [
            'db' => $this->db,
            'table' => $this->table,
            'goto' => $goto,
            'column_names' => $column_names,
            'types' => $types,
            'sql_types' => $this->dbi->types,
        ]);
    }

    public function findAction(): void
    {
        $useRegex = array_key_exists('useRegex', $_POST)
            && $_POST['useRegex'] === 'on';

        $preview = $this->getReplacePreview(
            $_POST['columnIndex'],
            $_POST['find'],
            $_POST['replaceWith'],
            $useRegex,
            $this->connectionCharSet
        );
        $this->response->addJSON('preview', $preview);
    }

    public function replaceAction(): void
    {
        $this->replace(
            $_POST['columnIndex'],
            $_POST['findString'],
            $_POST['replaceWith'],
            $_POST['useRegex'],
            $this->connectionCharSet
        );
        $this->response->addHTML(
            Generator::getMessage(
                __('Your SQL query has been executed successfully.'),
                null,
                'success'
            )
        );
    }

    /**
     * Returns HTML for previewing strings found and their replacements
     *
     * @param int    $columnIndex index of the column
     * @param string $find        string to find in the column
     * @param string $replaceWith string to replace with
     * @param bool   $useRegex    to use Regex replace or not
     * @param string $charSet     character set of the connection
     *
     * @return string HTML for previewing strings found and their replacements
     */
    public function getReplacePreview(
        $columnIndex,
        $find,
        $replaceWith,
        $useRegex,
        $charSet
    ) {
        $column = $this->columnNames[$columnIndex];
        if ($useRegex) {
            $result = $this->getRegexReplaceRows($columnIndex, $find, $replaceWith, $charSet);
        } else {
            $sql_query = 'SELECT '
                . Util::backquote($column) . ','
                . ' REPLACE('
                . Util::backquote($column) . ", '" . $find . "', '"
                . $replaceWith
                . "'),"
                . ' COUNT(*)'
                . ' FROM ' . Util::backquote($this->db)
                . '.' . Util::backquote($this->table)
                . ' WHERE ' . Util::backquote($column)
                . " LIKE '%" . $find . "%' COLLATE " . $charSet . '_bin'; // here we
            // change the collation of the 2nd operand to a case sensitive
            // binary collation to make sure that the comparison
            // is case sensitive
            $sql_query .= ' GROUP BY ' . Util::backquote($column)
                . ' ORDER BY ' . Util::backquote($column) . ' ASC';

            $result = $this->dbi->fetchResult($sql_query, 0);
        }

        return $this->template->render('table/find_replace/replace_preview', [
            'db' => $this->db,
            'table' => $this->table,
            'column_index' => $columnIndex,
            'find' => $find,
            'replace_with' => $replaceWith,
            'use_regex' => $useRegex,
            'result' => $result,
        ]);
    }

    /**
     * Finds and returns Regex pattern and their replacements
     *
     * @param int    $columnIndex index of the column
     * @param string $find        string to find in the column
     * @param string $replaceWith string to replace with
     * @param string $charSet     character set of the connection
     *
     * @return array|bool Array containing original values, replaced values and count
     */
    private function getRegexReplaceRows(
        $columnIndex,
        $find,
        $replaceWith,
        $charSet
    ) {
        $column = $this->columnNames[$columnIndex];
        $sql_query = 'SELECT '
            . Util::backquote($column) . ','
            . ' 1,' // to add an extra column that will have replaced value
            . ' COUNT(*)'
            . ' FROM ' . Util::backquote($this->db)
            . '.' . Util::backquote($this->table)
            . ' WHERE ' . Util::backquote($column)
            . " RLIKE '" . $this->dbi->escapeString($find) . "' COLLATE "
            . $charSet . '_bin'; // here we
        // change the collation of the 2nd operand to a case sensitive
        // binary collation to make sure that the comparison is case sensitive
        $sql_query .= ' GROUP BY ' . Util::backquote($column)
            . ' ORDER BY ' . Util::backquote($column) . ' ASC';

        $result = $this->dbi->fetchResult($sql_query, 0);

        /* Iterate over possible delimiters to get one */
        $delimiters = [
            '/',
            '@',
            '#',
            '~',
            '!',
            '$',
            '%',
            '^',
            '&',
            '_',
        ];
        $found = false;
        for ($i = 0, $l = count($delimiters); $i < $l; $i++) {
            if (! str_contains($find, $delimiters[$i])) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            return false;
        }

        $find = $delimiters[$i] . $find . $delimiters[$i];
        foreach ($result as $index => $row) {
            $result[$index][1] = preg_replace($find, $replaceWith, $row[0]);
        }

        return $result;
    }

    /**
     * Replaces a given string in a column with a give replacement
     *
     * @param int    $columnIndex index of the column
     * @param string $find        string to find in the column
     * @param string $replaceWith string to replace with
     * @param bool   $useRegex    to use Regex replace or not
     * @param string $charSet     character set of the connection
     */
    public function replace(
        $columnIndex,
        $find,
        $replaceWith,
        $useRegex,
        $charSet
    ): void {
        $column = $this->columnNames[$columnIndex];
        if ($useRegex) {
            $toReplace = $this->getRegexReplaceRows($columnIndex, $find, $replaceWith, $charSet);
            $sql_query = 'UPDATE ' . Util::backquote($this->table)
                . ' SET ' . Util::backquote($column);

            if (is_array($toReplace)) {
                if (count($toReplace) > 0) {
                    $sql_query .= ' = CASE';
                    foreach ($toReplace as $row) {
                        $sql_query .= "\n WHEN " . Util::backquote($column)
                            . " = '" . $this->dbi->escapeString($row[0])
                            . "' THEN '" . $this->dbi->escapeString($row[1]) . "'";
                    }

                    $sql_query .= ' END';
                } else {
                    $sql_query .= ' = ' . Util::backquote($column);
                }
            }

            $sql_query .= ' WHERE ' . Util::backquote($column)
                . " RLIKE '" . $this->dbi->escapeString($find) . "' COLLATE "
                . $charSet . '_bin'; // here we
            // change the collation of the 2nd operand to a case sensitive
            // binary collation to make sure that the comparison
            // is case sensitive
        } else {
            $sql_query = 'UPDATE ' . Util::backquote($this->table)
                . ' SET ' . Util::backquote($column) . ' ='
                . ' REPLACE('
                . Util::backquote($column) . ", '" . $find . "', '"
                . $replaceWith
                . "')"
                . ' WHERE ' . Util::backquote($column)
                . " LIKE '%" . $find . "%' COLLATE " . $charSet . '_bin'; // here we
            // change the collation of the 2nd operand to a case sensitive
            // binary collation to make sure that the comparison
            // is case sensitive
        }

        $this->dbi->query($sql_query);
        $GLOBALS['sql_query'] = $sql_query;
    }
}
