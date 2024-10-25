<?php
/**
 * MediaWiki import plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;

use function __;
use function count;
use function explode;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function preg_match;
use function str_contains;
use function str_replace;
use function strcmp;
use function strlen;
use function trim;

/**
 * Handles the import for the MediaWiki format
 */
class ImportMediawiki extends ImportPlugin
{
    /**
     * Whether to analyze tables
     *
     * @var bool
     */
    private $analyze;

    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'mediawiki';
    }

    protected function setProperties(): ImportPluginProperties
    {
        $this->setAnalyze(false);
        if ($GLOBALS['plugin_param'] !== 'table') {
            $this->setAnalyze(true);
        }

        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText(__('MediaWiki Table'));
        $importPluginProperties->setExtension('txt');
        $importPluginProperties->setMimeType('text/plain');
        $importPluginProperties->setOptionsText(__('Options'));

        return $importPluginProperties;
    }

    /**
     * Handles the whole import logic
     *
     * @param array $sql_data 2-element array with sql data
     */
    public function doImport(?File $importHandle = null, array &$sql_data = []): void
    {
        global $error, $timeout_passed, $finished;

        // Defaults for parser

        // The buffer that will be used to store chunks read from the imported file
        $buffer = '';

        // Used as storage for the last part of the current chunk data
        // Will be appended to the first line of the next chunk, if there is one
        $last_chunk_line = '';

        // Remembers whether the current buffer line is part of a comment
        $inside_comment = false;
        // Remembers whether the current buffer line is part of a data comment
        $inside_data_comment = false;
        // Remembers whether the current buffer line is part of a structure comment
        $inside_structure_comment = false;

        // MediaWiki only accepts "\n" as row terminator
        $mediawiki_new_line = "\n";

        // Initialize the name of the current table
        $cur_table_name = '';

        $cur_temp_table_headers = [];
        $cur_temp_table = [];

        $in_table_header = false;

        while (! $finished && ! $error && ! $timeout_passed) {
            $data = $this->import->getNextChunk($importHandle);

            if ($data === false) {
                // Subtract data we didn't handle yet and stop processing
                $GLOBALS['offset'] -= mb_strlen($buffer);
                break;
            }

            if ($data !== true) {
                // Append new data to buffer
                $buffer = $data;
                unset($data);
                // Don't parse string if we're not at the end
                // and don't have a new line inside
                if (! str_contains($buffer, $mediawiki_new_line)) {
                    continue;
                }
            }

            // Because of reading chunk by chunk, the first line from the buffer
            // contains only a portion of an actual line from the imported file.
            // Therefore, we have to append it to the last line from the previous
            // chunk. If we are at the first chunk, $last_chunk_line should be empty.
            $buffer = $last_chunk_line . $buffer;

            // Process the buffer line by line
            $buffer_lines = explode($mediawiki_new_line, $buffer);

            $full_buffer_lines_count = count($buffer_lines);
            // If the reading is not finalized, the final line of the current chunk
            // will not be complete
            if (! $finished) {
                $last_chunk_line = $buffer_lines[--$full_buffer_lines_count];
            }

            for ($line_nr = 0; $line_nr < $full_buffer_lines_count; ++$line_nr) {
                $cur_buffer_line = trim($buffer_lines[$line_nr]);

                // If the line is empty, go to the next one
                if ($cur_buffer_line === '') {
                    continue;
                }

                $first_character = $cur_buffer_line[0];
                $matches = [];

                // Check beginning of comment
                if (! strcmp(mb_substr($cur_buffer_line, 0, 4), '<!--')) {
                    $inside_comment = true;
                    continue;
                }

                if ($inside_comment) {
                    // Check end of comment
                    if (! strcmp(mb_substr($cur_buffer_line, 0, 4), '-->')) {
                        // Only data comments are closed. The structure comments
                        // will be closed when a data comment begins (in order to
                        // skip structure tables)
                        if ($inside_data_comment) {
                            $inside_data_comment = false;
                        }

                        // End comments that are not related to table structure
                        if (! $inside_structure_comment) {
                            $inside_comment = false;
                        }
                    } else {
                        // Check table name
                        $match_table_name = [];
                        if (preg_match('/^Table data for `(.*)`$/', $cur_buffer_line, $match_table_name)) {
                            $cur_table_name = $match_table_name[1];
                            $inside_data_comment = true;

                            $inside_structure_comment = $this->mngInsideStructComm($inside_structure_comment);
                        } elseif (preg_match('/^Table structure for `(.*)`$/', $cur_buffer_line, $match_table_name)) {
                            // The structure comments will be ignored
                            $inside_structure_comment = true;
                        }
                    }

                    continue;
                }

                if (preg_match('/^\{\|(.*)$/', $cur_buffer_line, $matches)) {
                    // Check start of table

                    // This will store all the column info on all rows from
                    // the current table read from the buffer
                    $cur_temp_table = [];

                    // Will be used as storage for the current row in the buffer
                    // Once all its columns are read, it will be added to
                    // $cur_temp_table and then it will be emptied
                    $cur_temp_line = [];

                    // Helps us differentiate the header columns
                    // from the normal columns
                    $in_table_header = false;
                    // End processing because the current line does not
                    // contain any column information
                } elseif (
                    mb_substr($cur_buffer_line, 0, 2) === '|-'
                    || mb_substr($cur_buffer_line, 0, 2) === '|+'
                    || mb_substr($cur_buffer_line, 0, 2) === '|}'
                ) {
                    // Check begin row or end table

                    // Add current line to the values storage
                    if (! empty($cur_temp_line)) {
                        // If the current line contains header cells
                        // ( marked with '!' ),
                        // it will be marked as table header
                        if ($in_table_header) {
                            // Set the header columns
                            $cur_temp_table_headers = $cur_temp_line;
                        } else {
                            // Normal line, add it to the table
                            $cur_temp_table[] = $cur_temp_line;
                        }
                    }

                    // Empty the temporary buffer
                    $cur_temp_line = [];

                    // No more processing required at the end of the table
                    if (mb_substr($cur_buffer_line, 0, 2) === '|}') {
                        $current_table = [
                            $cur_table_name,
                            $cur_temp_table_headers,
                            $cur_temp_table,
                        ];

                        // Import the current table data into the database
                        $this->importDataOneTable($current_table, $sql_data);

                        // Reset table name
                        $cur_table_name = '';
                    }
                    // What's after the row tag is now only attributes
                } elseif (($first_character === '|') || ($first_character === '!')) {
                    // Check cell elements

                    // Header cells
                    if ($first_character === '!') {
                        // Mark as table header, but treat as normal row
                        $cur_buffer_line = str_replace('!!', '||', $cur_buffer_line);
                        // Will be used to set $cur_temp_line as table header
                        $in_table_header = true;
                    } else {
                        $in_table_header = false;
                    }

                    // Loop through each table cell
                    $cells = $this->explodeMarkup($cur_buffer_line);
                    foreach ($cells as $cell) {
                        $cell = $this->getCellData($cell);

                        // Delete the beginning of the column, if there is one
                        $cell = trim($cell);
                        $col_start_chars = [
                            '|',
                            '!',
                        ];
                        foreach ($col_start_chars as $col_start_char) {
                            $cell = $this->getCellContent($cell, $col_start_char);
                        }

                        // Add the cell to the row
                        $cur_temp_line[] = $cell;
                    }
                } else {
                    // If it's none of the above, then the current line has a bad
                    // format
                    $message = Message::error(
                        __('Invalid format of mediawiki input on line: <br>%s.')
                    );
                    $message->addParam($cur_buffer_line);
                    $error = true;
                }
            }
        }
    }

    /**
     * Imports data from a single table
     *
     * @param array $table    containing all table info:
     *                        <code> $table[0] - string
     *                        containing table name
     *                        $table[1] - array[]   of
     *                        table headers $table[2] -
     *                        array[][] of table content
     *                        rows </code>
     * @param array $sql_data 2-element array with sql data
     *
     * @global bool $analyze whether to scan for column types
     */
    private function importDataOneTable(array $table, array &$sql_data): void
    {
        $analyze = $this->getAnalyze();
        if ($analyze) {
            // Set the table name
            $this->setTableName($table[0]);

            // Set generic names for table headers if they don't exist
            $this->setTableHeaders($table[1], $table[2][0]);

            // Create the tables array to be used in Import::buildSql()
            $tables = [];
            $tables[] = [
                $table[0],
                $table[1],
                $table[2],
            ];

            // Obtain the best-fit MySQL types for each column
            $analyses = [];
            $analyses[] = $this->import->analyzeTable($tables[0]);

            $this->executeImportTables($tables, $analyses, $sql_data);
        }

        // Commit any possible data in buffers
        $this->import->runQuery('', '', $sql_data);
    }

    /**
     * Sets the table name
     *
     * @param string $table_name reference to the name of the table
     */
    private function setTableName(&$table_name): void
    {
        global $dbi;

        if (! empty($table_name)) {
            return;
        }

        $result = $dbi->fetchResult('SHOW TABLES');
        // todo check if the name below already exists
        $table_name = 'TABLE ' . (count($result) + 1);
    }

    /**
     * Set generic names for table headers, if they don't exist
     *
     * @param array $table_headers reference to the array containing the headers
     *                             of a table
     * @param array $table_row     array containing the first content row
     */
    private function setTableHeaders(array &$table_headers, array $table_row): void
    {
        if (! empty($table_headers)) {
            return;
        }

        // The first table row should contain the number of columns
        // If they are not set, generic names will be given (COL 1, COL 2, etc)
        $num_cols = count($table_row);
        for ($i = 0; $i < $num_cols; ++$i) {
            $table_headers[$i] = 'COL ' . ($i + 1);
        }
    }

    /**
     * Sets the database name and additional options and calls Import::buildSql()
     * Used in PMA_importDataAllTables() and $this->importDataOneTable()
     *
     * @param array $tables   structure:
     *                        array(
     *                        array(table_name, array() column_names, array()()
     *                        rows)
     *                        )
     * @param array $analyses structure:
     *                        $analyses = array(
     *                        array(array() column_types, array() column_sizes)
     *                        )
     * @param array $sql_data 2-element array with sql data
     *
     * @global string $db      name of the database to import in
     */
    private function executeImportTables(array &$tables, array &$analyses, array &$sql_data): void
    {
        global $db;

        // $db_name : The currently selected database name, if applicable
        //            No backquotes
        // $options : An associative array of options
        [$db_name, $options] = $this->getDbnameAndOptions($db, 'mediawiki_DB');

        // Array of SQL strings
        // Non-applicable parameters
        $create = null;

        // Create and execute necessary SQL statements from data
        $this->import->buildSql($db_name, $tables, $analyses, $create, $options, $sql_data);
    }

    /**
     * Replaces all instances of the '||' separator between delimiters
     * in a given string
     *
     * @param string $replace the string to be replaced with
     * @param string $subject the text to be replaced
     *
     * @return string with replacements
     */
    private function delimiterReplace($replace, $subject)
    {
        // String that will be returned
        $cleaned = '';
        // Possible states of current character
        $inside_tag = false;
        $inside_attribute = false;
        // Attributes can be declared with either " or '
        $start_attribute_character = false;

        // The full separator is "||";
        // This remembers if the previous character was '|'
        $partial_separator = false;

        // Parse text char by char
        for ($i = 0, $iMax = strlen($subject); $i < $iMax; $i++) {
            $cur_char = $subject[$i];
            // Check for separators
            if ($cur_char === '|') {
                // If we're not inside a tag, then this is part of a real separator,
                // so we append it to the current segment
                if (! $inside_attribute) {
                    $cleaned .= $cur_char;
                    if ($partial_separator) {
                        $inside_tag = false;
                        $inside_attribute = false;
                    }
                } elseif ($partial_separator) {
                    // If we are inside a tag, we replace the current char with
                    // the placeholder and append that to the current segment
                    $cleaned .= $replace;
                }

                // If the previous character was also '|', then this ends a
                // full separator. If not, this may be the beginning of one
                $partial_separator = ! $partial_separator;
            } else {
                // If we're inside a tag attribute and the current character is
                // not '|', but the previous one was, it means that the single '|'
                // was not appended, so we append it now
                if ($partial_separator && $inside_attribute) {
                    $cleaned .= '|';
                }

                // If the char is different from "|", no separator can be formed
                $partial_separator = false;

                // any other character should be appended to the current segment
                $cleaned .= $cur_char;

                if ($cur_char === '<' && ! $inside_attribute) {
                    // start of a tag
                    $inside_tag = true;
                } elseif ($cur_char === '>' && ! $inside_attribute) {
                    // end of a tag
                    $inside_tag = false;
                } elseif (($cur_char === '"' || $cur_char == "'") && $inside_tag) {
                    // start or end of an attribute
                    if (! $inside_attribute) {
                        $inside_attribute = true;
                        // remember the attribute`s declaration character (" or ')
                        $start_attribute_character = $cur_char;
                    } else {
                        if ($cur_char == $start_attribute_character) {
                            $inside_attribute = false;
                            // unset attribute declaration character
                            $start_attribute_character = false;
                        }
                    }
                }
            }
        }

        return $cleaned;
    }

    /**
     * Separates a string into items, similarly to explode
     * Uses the '||' separator (which is standard in the mediawiki format)
     * and ignores any instances of it inside markup tags
     * Used in parsing buffer lines containing data cells
     *
     * @param string $text text to be split
     *
     * @return array
     */
    private function explodeMarkup($text)
    {
        $separator = '||';
        $placeholder = "\x00";

        // Remove placeholder instances
        $text = str_replace($placeholder, '', $text);

        // Replace instances of the separator inside HTML-like
        // tags with the placeholder
        $cleaned = $this->delimiterReplace($placeholder, $text);
        // Explode, then put the replaced separators back in
        $items = explode($separator, $cleaned);
        foreach ($items as $i => $str) {
            $items[$i] = str_replace($placeholder, $separator, $str);
        }

        return $items;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Returns true if the table should be analyzed, false otherwise
     */
    private function getAnalyze(): bool
    {
        return $this->analyze;
    }

    /**
     * Sets to true if the table should be analyzed, false otherwise
     *
     * @param bool $analyze status
     */
    private function setAnalyze($analyze): void
    {
        $this->analyze = $analyze;
    }

    /**
     * Get cell
     *
     * @param string $cell Cell
     *
     * @return mixed
     */
    private function getCellData($cell)
    {
        // A cell could contain both parameters and data
        $cell_data = explode('|', $cell, 2);

        // A '|' inside an invalid link should not
        // be mistaken as delimiting cell parameters
        if (! str_contains($cell_data[0], '[[')) {
            return $cell;
        }

        if (count($cell_data) === 1) {
            return $cell_data[0];
        }

        return $cell_data[1];
    }

    /**
     * Manage $inside_structure_comment
     *
     * @param bool $inside_structure_comment Value to test
     */
    private function mngInsideStructComm($inside_structure_comment): bool
    {
        // End ignoring structure rows
        if ($inside_structure_comment) {
            $inside_structure_comment = false;
        }

        return $inside_structure_comment;
    }

    /**
     * Get cell content
     *
     * @param string $cell           Cell
     * @param string $col_start_char Start char
     *
     * @return string
     */
    private function getCellContent($cell, $col_start_char)
    {
        if (mb_strpos($cell, $col_start_char) === 0) {
            $cell = trim(mb_substr($cell, 1));
        }

        return $cell;
    }
}
