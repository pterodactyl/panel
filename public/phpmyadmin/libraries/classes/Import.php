<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\ReplaceStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\Utils\Query;

use function __;
use function abs;
use function count;
use function explode;
use function function_exists;
use function htmlspecialchars;
use function implode;
use function is_array;
use function is_numeric;
use function max;
use function mb_chr;
use function mb_ord;
use function mb_stripos;
use function mb_strlen;
use function mb_strpos;
use function mb_strtoupper;
use function mb_substr;
use function mb_substr_count;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strcmp;
use function strlen;
use function strpos;
use function substr;
use function time;
use function trim;

/**
 * Library that provides common import functions that are used by import plugins
 */
class Import
{
    /* MySQL type defs */
    public const NONE = 0;
    public const VARCHAR = 1;
    public const INT = 2;
    public const DECIMAL = 3;
    public const BIGINT = 4;
    public const GEOMETRY = 5;
    /* Decimal size defs */
    public const M = 0;
    public const D = 1;
    public const FULL = 2;
    /* Table array defs */
    public const TBL_NAME = 0;
    public const COL_NAMES = 1;
    public const ROWS = 2;
    /* Analysis array defs */
    public const TYPES = 0;
    public const SIZES = 1;
    public const FORMATTEDSQL = 2;

    public function __construct()
    {
        global $dbi;

        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $checkUserPrivileges = new CheckUserPrivileges($dbi);
        $checkUserPrivileges->getPrivileges();
    }

    /**
     * Checks whether timeout is getting close
     */
    public function checkTimeout(): bool
    {
        global $timestamp, $maximum_time, $timeout_passed;
        if ($maximum_time == 0) {
            return false;
        }

        if ($timeout_passed) {
            return true;

            /* 5 in next row might be too much */
        }

        if (time() - $timestamp > $maximum_time - 5) {
            $timeout_passed = true;

            return true;
        }

        return false;
    }

    /**
     * Runs query inside import buffer. This is needed to allow displaying
     * of last SELECT, SHOW or HANDLER results and similar nice stuff.
     *
     * @param string $sql     query to run
     * @param string $full    query to display, this might be commented
     * @param array  $sqlData SQL parse data storage
     */
    public function executeQuery(string $sql, string $full, array &$sqlData): void
    {
        global $sql_query, $my_die, $error, $reload, $result, $msg, $cfg, $sql_query_disabled, $db, $dbi;

        $result = $dbi->tryQuery($sql);

        // USE query changes the database, son need to track
        // while running multiple queries
        $isUseQuery = mb_stripos($sql, 'use ') !== false;

        $msg = '# ';
        if ($result === false) { // execution failed
            if (! isset($my_die)) {
                $my_die = [];
            }

            $my_die[] = [
                'sql' => $full,
                'error' => $dbi->getError(),
            ];

            $msg .= __('Error');

            if (! $cfg['IgnoreMultiSubmitErrors']) {
                $error = true;

                return;
            }
        } else {
            $aNumRows = (int) $result->numRows();
            $aAffectedRows = (int) @$dbi->affectedRows();
            if ($aNumRows > 0) {
                $msg .= __('Rows') . ': ' . $aNumRows;
            } elseif ($aAffectedRows > 0) {
                $message = Message::getMessageForAffectedRows($aAffectedRows);
                $msg .= $message->getMessage();
            } else {
                $msg .= __('MySQL returned an empty result set (i.e. zero rows).');
            }

            if (($aNumRows > 0) || $isUseQuery) {
                $sqlData['valid_sql'][] = $sql;
                if (! isset($sqlData['valid_queries'])) {
                    $sqlData['valid_queries'] = 0;
                }

                $sqlData['valid_queries']++;
            }
        }

        if (! $sql_query_disabled) {
            $sql_query .= $msg . "\n";
        }

        // If a 'USE <db>' SQL-clause was found and the query
        // succeeded, set our current $db to the new one
        if ($result != false) {
            [$db, $reload] = $this->lookForUse($sql, $db, $reload);
        }

        $pattern = '@^[\s]*(DROP|CREATE)[\s]+(IF EXISTS[[:space:]]+)?(TABLE|DATABASE)[[:space:]]+(.+)@im';
        if ($result == false || ! preg_match($pattern, $sql)) {
            return;
        }

        $reload = true;
    }

    /**
     * Runs query inside import buffer. This is needed to allow displaying
     * of last SELECT, SHOW or HANDLER results and similar nice stuff.
     *
     * @param string $sql     query to run
     * @param string $full    query to display, this might be commented
     * @param array  $sqlData SQL parse data storage
     */
    public function runQuery(
        string $sql = '',
        string $full = '',
        array &$sqlData = []
    ): void {
        global $import_run_buffer, $go_sql, $complete_query, $display_query, $sql_query, $msg,
            $skip_queries, $executed_queries, $max_sql_len, $read_multiply, $sql_query_disabled, $run_query;
        $read_multiply = 1;
        if (! isset($import_run_buffer)) {
            // Do we have something to push into buffer?
            $import_run_buffer = $this->runQueryPost($import_run_buffer, $sql, $full);

            return;
        }

        // Should we skip something?
        if ($skip_queries > 0) {
            $skip_queries--;
            // Do we have something to push into buffer?
            $import_run_buffer = $this->runQueryPost($import_run_buffer, $sql, $full);

            return;
        }

        if (! empty($import_run_buffer['sql']) && trim($import_run_buffer['sql']) != '') {
            $max_sql_len = max(
                $max_sql_len,
                mb_strlen($import_run_buffer['sql'])
            );
            if (! $sql_query_disabled) {
                $sql_query .= $import_run_buffer['full'];
            }

            $executed_queries++;

            if ($run_query && $executed_queries < 50) {
                $go_sql = true;

                if (! $sql_query_disabled) {
                    $complete_query = $sql_query;
                    $display_query = $sql_query;
                } else {
                    $complete_query = '';
                    $display_query = '';
                }

                $sql_query = $import_run_buffer['sql'];
                $sqlData['valid_sql'][] = $import_run_buffer['sql'];
                $sqlData['valid_full'][] = $import_run_buffer['full'];
                if (! isset($sqlData['valid_queries'])) {
                    $sqlData['valid_queries'] = 0;
                }

                $sqlData['valid_queries']++;
            } elseif ($run_query) {
                /* Handle rollback from go_sql */
                if ($go_sql && isset($sqlData['valid_full'])) {
                    $queries = $sqlData['valid_sql'];
                    $fulls = $sqlData['valid_full'];
                    $count = $sqlData['valid_queries'];
                    $go_sql = false;

                    $sqlData['valid_sql'] = [];
                    $sqlData['valid_queries'] = 0;
                    unset($sqlData['valid_full']);
                    for ($i = 0; $i < $count; $i++) {
                        $this->executeQuery($queries[$i], $fulls[$i], $sqlData);
                        if ($GLOBALS['error']) {
                            break;
                        }
                    }
                }

                if (! $GLOBALS['error']) {
                    $this->executeQuery($import_run_buffer['sql'], $import_run_buffer['full'], $sqlData);
                }
            }
        } elseif (! empty($import_run_buffer['full'])) {
            if ($go_sql) {
                $complete_query .= $import_run_buffer['full'];
                $display_query .= $import_run_buffer['full'];
            } elseif (! $sql_query_disabled) {
                $sql_query .= $import_run_buffer['full'];
            }
        }

        // check length of query unless we decided to pass it to /sql
        // (if $run_query is false, we are just displaying so show
        // the complete query in the textarea)
        if (! $go_sql && $run_query && ! empty($sql_query)) {
            if (mb_strlen($sql_query) > 50000 || $executed_queries > 50 || $max_sql_len > 1000) {
                $sql_query = '';
                $sql_query_disabled = true;
            }
        }

        // Do we have something to push into buffer?
        $import_run_buffer = $this->runQueryPost($import_run_buffer, $sql, $full);

        // In case of ROLLBACK, notify the user.
        if (! isset($_POST['rollback_query'])) {
            return;
        }

        $msg .= __('[ROLLBACK occurred.]');
    }

    /**
     * Return import run buffer
     *
     * @param array  $importRunBuffer Buffer of queries for import
     * @param string $sql             SQL query
     * @param string $full            Query to display
     *
     * @return array Buffer of queries for import
     */
    public function runQueryPost(
        ?array $importRunBuffer,
        string $sql,
        string $full
    ): ?array {
        if (! empty($sql) || ! empty($full)) {
            return [
                'sql' => $sql . ';',
                'full' => $full . ';',
            ];
        }

        unset($GLOBALS['import_run_buffer']);

        return $importRunBuffer;
    }

    /**
     * Looks for the presence of USE to possibly change current db
     *
     * @param string $buffer buffer to examine
     * @param string $db     current db
     * @param bool   $reload reload
     *
     * @return array (current or new db, whether to reload)
     */
    public function lookForUse(?string $buffer, ?string $db, ?bool $reload): array
    {
        if (preg_match('@^[\s]*USE[[:space:]]+([\S]+)@i', (string) $buffer, $match)) {
            $db = trim($match[1]);
            $db = trim($db, ';'); // for example, USE abc;

            // $db must not contain the escape characters generated by backquote()
            // ( used in buildSql() as: backquote($db_name), and then called
            // in runQuery() which in turn calls lookForUse() )
            $db = Util::unQuote($db);

            $reload = true;
        }

        return [
            $db,
            $reload,
        ];
    }

    /**
     * Returns next part of imported file/buffer
     *
     * @param int $size size of buffer to read (this is maximal size function will return)
     *
     * @return string|bool part of file/buffer
     */
    public function getNextChunk(?File $importHandle = null, int $size = 32768)
    {
        global $charset_conversion, $charset_of_file, $read_multiply;

        // Add some progression while reading large amount of data
        if ($read_multiply <= 8) {
            $size *= $read_multiply;
        } else {
            $size *= 8;
        }

        $read_multiply++;

        // We can not read too much
        if ($size > $GLOBALS['read_limit']) {
            $size = $GLOBALS['read_limit'];
        }

        if ($this->checkTimeout()) {
            return false;
        }

        if ($GLOBALS['finished']) {
            return true;
        }

        if ($GLOBALS['import_file'] === 'none') {
            // Well this is not yet supported and tested,
            // but should return content of textarea
            if (mb_strlen($GLOBALS['import_text']) < $size) {
                $GLOBALS['finished'] = true;

                return $GLOBALS['import_text'];
            }

            $r = mb_substr($GLOBALS['import_text'], 0, $size);
            $GLOBALS['offset'] += $size;
            $GLOBALS['import_text'] = mb_substr($GLOBALS['import_text'], $size);

            return $r;
        }

        if ($importHandle === null) {
            return false;
        }

        $result = $importHandle->read($size);
        $GLOBALS['finished'] = $importHandle->eof();
        $GLOBALS['offset'] += $size;

        if ($charset_conversion) {
            return Encoding::convertString($charset_of_file, 'utf-8', $result);
        }

        /**
         * Skip possible byte order marks (I do not think we need more
         * charsets, but feel free to add more, you can use wikipedia for
         * reference: <https://en.wikipedia.org/wiki/Byte_Order_Mark>)
         *
         * @todo BOM could be used for charset autodetection
         */
        if ($GLOBALS['offset'] == $size) {
            $result = $this->skipByteOrderMarksFromContents($result);
        }

        return $result;
    }

    /**
     * Skip possible byte order marks (I do not think we need more
     * charsets, but feel free to add more, you can use wikipedia for
     * reference: <https://en.wikipedia.org/wiki/Byte_Order_Mark>)
     *
     * @param string $contents The contents to strip BOM
     *
     * @todo BOM could be used for charset autodetection
     */
    public function skipByteOrderMarksFromContents(string $contents): string
    {
        // Do not use mb_ functions they are sensible to mb_internal_encoding()

        // UTF-8
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            return substr($contents, 3);

            // UTF-16 BE, LE
        }

        if (str_starts_with($contents, "\xFE\xFF") || str_starts_with($contents, "\xFF\xFE")) {
            return substr($contents, 2);
        }

        return $contents;
    }

    /**
     * Returns the "Excel" column name (i.e. 1 = "A", 26 = "Z", 27 = "AA", etc.)
     *
     * This functions uses recursion to build the Excel column name.
     *
     * The column number (1-26) is converted to the responding
     * ASCII character (A-Z) and returned.
     *
     * If the column number is bigger than 26 (= num of letters in alphabet),
     * an extra character needs to be added. To find this extra character,
     * the number is divided by 26 and this value is passed to another instance
     * of the same function (hence recursion). In that new instance the number is
     * evaluated again, and if it is still bigger than 26, it is divided again
     * and passed to another instance of the same function. This continues until
     * the number is smaller than 26. Then the last called function returns
     * the corresponding ASCII character to the function that called it.
     * Each time a called function ends an extra character is added to the column name.
     * When the first function is reached, the last character is added and the complete
     * column name is returned.
     *
     * @param int $num the column number
     *
     * @return string The column's "Excel" name
     */
    public function getColumnAlphaName(int $num): string
    {
        $capitalA = 65; // ASCII value for capital "A"
        $colName = '';

        if ($num > 26) {
            $div = (int) ($num / 26);
            $remain = $num % 26;

            // subtract 1 of divided value in case the modulus is 0,
            // this is necessary because A-Z has no 'zero'
            if ($remain == 0) {
                $div--;
            }

            // recursive function call
            $colName = $this->getColumnAlphaName($div);
            // use modulus as new column number
            $num = $remain;
        }

        if ($num == 0) {
            // use 'Z' if column number is 0,
            // this is necessary because A-Z has no 'zero'
            $colName .= mb_chr($capitalA + 26 - 1);
        } else {
            // convert column number to ASCII character
            $colName .= mb_chr($capitalA + $num - 1);
        }

        return $colName;
    }

    /**
     * Returns the column number based on the Excel name.
     * So "A" = 1, "Z" = 26, "AA" = 27, etc.
     *
     * Basically this is a base26 (A-Z) to base10 (0-9) conversion.
     * It iterates through all characters in the column name and
     * calculates the corresponding value, based on character value
     * (A = 1, ..., Z = 26) and position in the string.
     *
     * @param string $name column name(i.e. "A", or "BC", etc.)
     *
     * @return int The column number
     */
    public function getColumnNumberFromName(string $name): int
    {
        if (empty($name)) {
            return 0;
        }

        $name = mb_strtoupper($name);
        $numChars = mb_strlen($name);
        $columnNumber = 0;
        for ($i = 0; $i < $numChars; ++$i) {
            // read string from back to front
            $charPos = $numChars - 1 - $i;

            // convert capital character to ASCII value
            // and subtract 64 to get corresponding decimal value
            // ASCII value of "A" is 65, "B" is 66, etc.
            // Decimal equivalent of "A" is 1, "B" is 2, etc.
            $number = (int) (mb_ord($name[$charPos]) - 64);

            // base26 to base10 conversion : multiply each number
            // with corresponding value of the position, in this case
            // $i=0 : 1; $i=1 : 26; $i=2 : 676; ...
            $columnNumber += $number * 26 ** $i;
        }

        return (int) $columnNumber;
    }

    /**
     * Obtains the precision (total # of digits) from a size of type decimal
     *
     * @param string $lastCumulativeSize Size of type decimal
     *
     * @return int Precision of the given decimal size notation
     */
    public function getDecimalPrecision(string $lastCumulativeSize): int
    {
        return (int) substr(
            $lastCumulativeSize,
            0,
            (int) strpos($lastCumulativeSize, ',')
        );
    }

    /**
     * Obtains the scale (# of digits to the right of the decimal point)
     * from a size of type decimal
     *
     * @param string $lastCumulativeSize Size of type decimal
     *
     * @return int Scale of the given decimal size notation
     */
    public function getDecimalScale(string $lastCumulativeSize): int
    {
        return (int) substr(
            $lastCumulativeSize,
            strpos($lastCumulativeSize, ',') + 1,
            strlen($lastCumulativeSize) - strpos($lastCumulativeSize, ',')
        );
    }

    /**
     * Obtains the decimal size of a given cell
     *
     * @param string $cell cell content
     *
     * @return array Contains the precision, scale, and full size
     *                representation of the given decimal cell
     */
    public function getDecimalSize(string $cell): array
    {
        $currSize = mb_strlen($cell);
        $decPos = mb_strpos($cell, '.');
        $decPrecision = $currSize - 1 - $decPos;

        $m = $currSize - 1;
        $d = $decPrecision;

        return [
            $m,
            $d,
            $m . ',' . $d,
        ];
    }

    /**
     * Obtains the size of the given cell
     *
     * @param string|int $lastCumulativeSize Last cumulative column size
     * @param int|null   $lastCumulativeType Last cumulative column type (NONE or VARCHAR or DECIMAL or INT or BIGINT)
     * @param int        $currentCellType    Type of the current cell (NONE or VARCHAR or DECIMAL or INT or BIGINT)
     * @param string     $cell               The current cell
     *
     * @return string|int Size of the given cell in the type-appropriate format
     *
     * @todo    Handle the error cases more elegantly
     */
    public function detectSize(
        $lastCumulativeSize,
        ?int $lastCumulativeType,
        int $currentCellType,
        string $cell
    ) {
        $currSize = mb_strlen($cell);

        /**
         * If the cell is NULL, don't treat it as a varchar
         */
        if (! strcmp('NULL', $cell)) {
            return $lastCumulativeSize;
        }

        if ($currentCellType == self::VARCHAR) {
            /**
             * What to do if the current cell is of type VARCHAR
             */
            /**
             * The last cumulative type was VARCHAR
             */
            if ($lastCumulativeType == self::VARCHAR) {
                if ($currSize >= $lastCumulativeSize) {
                    return $currSize;
                }

                return $lastCumulativeSize;
            }

            if ($lastCumulativeType == self::DECIMAL) {
                /**
                 * The last cumulative type was DECIMAL
                 */
                $oldM = $this->getDecimalPrecision($lastCumulativeSize);

                if ($currSize >= $oldM) {
                    return $currSize;
                }

                return $oldM;
            }

            if ($lastCumulativeType == self::BIGINT || $lastCumulativeType == self::INT) {
                /**
                 * The last cumulative type was BIGINT or INT
                 */
                if ($currSize >= $lastCumulativeSize) {
                    return $currSize;
                }

                return $lastCumulativeSize;
            }

            if (! isset($lastCumulativeType) || $lastCumulativeType == self::NONE) {
                /**
                 * This is the first row to be analyzed
                 */
                return $currSize;
            }

            /**
             * An error has DEFINITELY occurred
             */
            /**
             * TODO: Handle this MUCH more elegantly
             */

            return -1;
        }

        if ($currentCellType == self::DECIMAL) {
            /**
             * What to do if the current cell is of type DECIMAL
             */
            /**
             * The last cumulative type was VARCHAR
             */
            if ($lastCumulativeType == self::VARCHAR) {
                /* Convert $last_cumulative_size from varchar to decimal format */
                $size = $this->getDecimalSize($cell);

                if ($size[self::M] >= $lastCumulativeSize) {
                    return $size[self::M];
                }

                return $lastCumulativeSize;
            }

            if ($lastCumulativeType == self::DECIMAL) {
                /**
                 * The last cumulative type was DECIMAL
                 */
                $size = $this->getDecimalSize($cell);

                $oldM = $this->getDecimalPrecision($lastCumulativeSize);
                $oldD = $this->getDecimalScale($lastCumulativeSize);

                /* New val if M or D is greater than current largest */
                if ($size[self::M] > $oldM || $size[self::D] > $oldD) {
                    /* Take the largest of both types */
                    return (string) (($size[self::M] > $oldM ? $size[self::M] : $oldM)
                        . ',' . ($size[self::D] > $oldD ? $size[self::D] : $oldD));
                }

                return $lastCumulativeSize;
            }

            if ($lastCumulativeType == self::BIGINT || $lastCumulativeType == self::INT) {
                /**
                 * The last cumulative type was BIGINT or INT
                 */
                /* Convert $last_cumulative_size from int to decimal format */
                $size = $this->getDecimalSize($cell);

                if ($size[self::M] >= $lastCumulativeSize) {
                    return $size[self::FULL];
                }

                return $lastCumulativeSize . ',' . $size[self::D];
            }

            if (! isset($lastCumulativeType) || $lastCumulativeType == self::NONE) {
                /**
                 * This is the first row to be analyzed
                 */
                /* First row of the column */
                $size = $this->getDecimalSize($cell);

                return $size[self::FULL];
            }

            /**
             * An error has DEFINITELY occurred
             */
            /**
             * TODO: Handle this MUCH more elegantly
             */

            return -1;
        }

        if ($currentCellType == self::BIGINT || $currentCellType == self::INT) {
            /**
             * What to do if the current cell is of type BIGINT or INT
             */
            /**
             * The last cumulative type was VARCHAR
             */
            if ($lastCumulativeType == self::VARCHAR) {
                if ($currSize >= $lastCumulativeSize) {
                    return $currSize;
                }

                return $lastCumulativeSize;
            }

            if ($lastCumulativeType == self::DECIMAL) {
                /**
                 * The last cumulative type was DECIMAL
                 */
                $oldM = $this->getDecimalPrecision($lastCumulativeSize);
                $oldD = $this->getDecimalScale($lastCumulativeSize);
                $oldInt = $oldM - $oldD;
                $newInt = mb_strlen((string) $cell);

                /* See which has the larger integer length */
                if ($oldInt >= $newInt) {
                    /* Use old decimal size */
                    return $lastCumulativeSize;
                }

                /* Use $newInt + $oldD as new M */
                return ($newInt + $oldD) . ',' . $oldD;
            }

            if ($lastCumulativeType == self::BIGINT || $lastCumulativeType == self::INT) {
                /**
                 * The last cumulative type was BIGINT or INT
                 */
                if ($currSize >= $lastCumulativeSize) {
                    return $currSize;
                }

                return $lastCumulativeSize;
            }

            if (! isset($lastCumulativeType) || $lastCumulativeType == self::NONE) {
                /**
                 * This is the first row to be analyzed
                 */
                return $currSize;
            }

            /**
             * An error has DEFINITELY occurred
             */
            /**
             * TODO: Handle this MUCH more elegantly
             */

            return -1;
        }

        /**
         * An error has DEFINITELY occurred
         */
        /**
         * TODO: Handle this MUCH more elegantly
         */

        return -1;
    }

    /**
     * Determines what MySQL type a cell is
     *
     * @param int         $lastCumulativeType Last cumulative column type
     *                                        (VARCHAR or INT or BIGINT or DECIMAL or NONE)
     * @param string|null $cell               String representation of the cell for which
     *                                        a best-fit type is to be determined
     *
     * @return int  The MySQL type representation
     *               (VARCHAR or INT or BIGINT or DECIMAL or NONE)
     */
    public function detectType(?int $lastCumulativeType, ?string $cell): int
    {
        /**
         * If numeric, determine if decimal, int or bigint
         * Else, we call it varchar for simplicity
         */

        if (! strcmp('NULL', (string) $cell)) {
            if ($lastCumulativeType === null || $lastCumulativeType == self::NONE) {
                return self::NONE;
            }

            return $lastCumulativeType;
        }

        if (! is_numeric($cell)) {
            return self::VARCHAR;
        }

        if (
            $cell == (string) (float) $cell
            && str_contains((string) $cell, '.')
            && mb_substr_count((string) $cell, '.') === 1
        ) {
            return self::DECIMAL;
        }

        if (abs((int) $cell) > 2147483647) {
            return self::BIGINT;
        }

        if ($cell !== (string) (int) $cell) {
            return self::VARCHAR;
        }

        return self::INT;
    }

    /**
     * Determines if the column types are int, decimal, or string
     *
     * @link https://wiki.phpmyadmin.net/pma/Import
     *
     * @param array $table array(string $table_name, array $col_names, array $rows)
     *
     * @return array|bool array(array $types, array $sizes)
     *
     * @todo    Handle the error case more elegantly
     */
    public function analyzeTable(array &$table)
    {
        /* Get number of rows in table */
        $numRows = count($table[self::ROWS]);
        /* Get number of columns */
        $numCols = count($table[self::COL_NAMES]);
        /* Current type for each column */
        $types = [];
        $sizes = [];

        /* Initialize $sizes to all 0's */
        for ($i = 0; $i < $numCols; ++$i) {
            $sizes[$i] = 0;
        }

        /* Initialize $types to NONE */
        for ($i = 0; $i < $numCols; ++$i) {
            $types[$i] = self::NONE;
        }

        /* If the passed array is not of the correct form, do not process it */
        if (
            is_array($table[self::TBL_NAME])
            || ! is_array($table[self::COL_NAMES])
            || ! is_array($table[self::ROWS])
        ) {
            /**
             * TODO: Handle this better
             */

            return false;
        }

        /* Analyze each column */
        for ($i = 0; $i < $numCols; ++$i) {
            /* Analyze the column in each row */
            for ($j = 0; $j < $numRows; ++$j) {
                $cellValue = $table[self::ROWS][$j][$i];
                /* Determine type of the current cell */
                $currType = $this->detectType($types[$i], $cellValue === null ? null : (string) $cellValue);
                /* Determine size of the current cell */
                $sizes[$i] = $this->detectSize($sizes[$i], $types[$i], $currType, (string) $cellValue);

                /**
                 * If a type for this column has already been declared,
                 * only alter it if it was a number and a varchar was found
                 */
                if ($currType == self::NONE) {
                    continue;
                }

                if ($currType == self::VARCHAR) {
                    $types[$i] = self::VARCHAR;
                } elseif ($currType == self::DECIMAL) {
                    if ($types[$i] != self::VARCHAR) {
                        $types[$i] = self::DECIMAL;
                    }
                } elseif ($currType == self::BIGINT) {
                    if ($types[$i] != self::VARCHAR && $types[$i] != self::DECIMAL) {
                        $types[$i] = self::BIGINT;
                    }
                } elseif ($currType == self::INT) {
                    if ($types[$i] != self::VARCHAR && $types[$i] != self::DECIMAL && $types[$i] != self::BIGINT) {
                        $types[$i] = self::INT;
                    }
                }
            }
        }

        /* Check to ensure that all types are valid */
        $len = count($types);
        for ($n = 0; $n < $len; ++$n) {
            if (strcmp((string) self::NONE, (string) $types[$n])) {
                continue;
            }

            $types[$n] = self::VARCHAR;
            $sizes[$n] = '10';
        }

        return [
            $types,
            $sizes,
        ];
    }

    /**
     * Builds and executes SQL statements to create the database and tables
     * as necessary, as well as insert all the data.
     *
     * @link https://wiki.phpmyadmin.net/pma/Import
     *
     * @param string     $dbName        Name of the database
     * @param array      $tables        Array of tables for the specified database
     * @param array|null $analyses      Analyses of the tables
     * @param array|null $additionalSql Additional SQL statements to be executed
     * @param array|null $options       Associative array of options
     * @param array      $sqlData       2-element array with sql data
     */
    public function buildSql(
        string $dbName,
        array &$tables,
        ?array &$analyses = null,
        ?array &$additionalSql = null,
        ?array $options = null,
        array &$sqlData = []
    ): void {
        global $import_notice, $dbi;

        /* Needed to quell the beast that is Message */
        $import_notice = null;

        /* Take care of the options */
        $collation = 'utf8_general_ci';
        $charset = 'utf8';
        $createDb = $options['create_db'] ?? true;

        /**
         * Create SQL code to handle the database
         *
         * @var array<int,string> $sql
         */
        $sql = [];

        if ($createDb) {
            $sql[] = 'CREATE DATABASE IF NOT EXISTS ' . Util::backquote($dbName)
                . ' DEFAULT CHARACTER SET ' . $charset . ' COLLATE ' . $collation
                . ';';
        }

        /**
         * The calling plug-in should include this statement,
         * if necessary, in the $additional_sql parameter
         *
         * $sql[] = "USE " . backquote($db_name);
         */

        /* Execute the SQL statements create above */
        $sqlLength = count($sql);
        for ($i = 0; $i < $sqlLength; ++$i) {
            $this->runQuery($sql[$i], $sql[$i], $sqlData);
        }

        /* No longer needed */
        unset($sql);

        /* Run the $additional_sql statements supplied by the caller plug-in */
        if ($additionalSql != null) {
            /* Clean the SQL first */
            $additionalSqlLength = count($additionalSql);

            /**
             * Only match tables for now, because CREATE IF NOT EXISTS
             * syntax is lacking or nonexisting for views, triggers,
             * functions, and procedures.
             *
             * See: https://bugs.mysql.com/bug.php?id=15287
             *
             * To the best of my knowledge this is still an issue.
             *
             * $pattern = 'CREATE (TABLE|VIEW|TRIGGER|FUNCTION|PROCEDURE)';
             */
            $pattern = '/CREATE [^`]*(TABLE)/';
            $replacement = 'CREATE \\1 IF NOT EXISTS';

            /* Change CREATE statements to CREATE IF NOT EXISTS to support
             * inserting into existing structures
             */
            for ($i = 0; $i < $additionalSqlLength; ++$i) {
                $additionalSql[$i] = preg_replace($pattern, $replacement, $additionalSql[$i]);
                /* Execute the resulting statements */
                $this->runQuery($additionalSql[$i], $additionalSql[$i], $sqlData);
            }
        }

        if ($analyses != null) {
            $typeArray = [
                self::NONE => 'NULL',
                self::VARCHAR => 'varchar',
                self::INT => 'int',
                self::DECIMAL => 'decimal',
                self::BIGINT => 'bigint',
                self::GEOMETRY => 'geometry',
            ];

            /* TODO: Do more checking here to make sure they really are matched */
            if (count($tables) != count($analyses)) {
                exit;
            }

            /* Create SQL code to create the tables */
            $numTables = count($tables);
            for ($i = 0; $i < $numTables; ++$i) {
                $numCols = count($tables[$i][self::COL_NAMES]);
                $tempSQLStr = 'CREATE TABLE IF NOT EXISTS '
                . Util::backquote($dbName)
                . '.' . Util::backquote($tables[$i][self::TBL_NAME]) . ' (';
                for ($j = 0; $j < $numCols; ++$j) {
                    $size = $analyses[$i][self::SIZES][$j];
                    if ((int) $size == 0) {
                        $size = 10;
                    }

                    $tempSQLStr .= Util::backquote($tables[$i][self::COL_NAMES][$j]) . ' '
                    . $typeArray[$analyses[$i][self::TYPES][$j]];
                    if ($analyses[$i][self::TYPES][$j] != self::GEOMETRY) {
                        $tempSQLStr .= '(' . $size . ')';
                    }

                    if ($j == count($tables[$i][self::COL_NAMES]) - 1) {
                        continue;
                    }

                    $tempSQLStr .= ', ';
                }

                $tempSQLStr .= ');';

                /**
                 * Each SQL statement is executed immediately
                 * after it is formed so that we don't have
                 * to store them in a (possibly large) buffer
                 */
                $this->runQuery($tempSQLStr, $tempSQLStr, $sqlData);
            }
        }

        /**
         * Create the SQL statements to insert all the data
         *
         * Only one insert query is formed for each table
         */
        $tempSQLStr = '';
        $colCount = 0;
        $numTables = count($tables);
        for ($i = 0; $i < $numTables; ++$i) {
            $numCols = count($tables[$i][self::COL_NAMES]);
            $numRows = count($tables[$i][self::ROWS]);

            if ($numRows === 0) {
                break;
            }

            $tempSQLStr = 'INSERT INTO ' . Util::backquote($dbName) . '.'
                . Util::backquote($tables[$i][self::TBL_NAME]) . ' (';

            for ($m = 0; $m < $numCols; ++$m) {
                $tempSQLStr .= Util::backquote($tables[$i][self::COL_NAMES][$m]);

                if ($m == $numCols - 1) {
                    continue;
                }

                $tempSQLStr .= ', ';
            }

            $tempSQLStr .= ') VALUES ';

            for ($j = 0; $j < $numRows; ++$j) {
                $tempSQLStr .= '(';

                for ($k = 0; $k < $numCols; ++$k) {
                    // If fully formatted SQL, no need to enclose
                    // with apostrophes, add slashes etc.
                    if (
                        $analyses != null
                        && isset($analyses[$i][self::FORMATTEDSQL][$colCount])
                        && $analyses[$i][self::FORMATTEDSQL][$colCount] == true
                    ) {
                        $tempSQLStr .= (string) $tables[$i][self::ROWS][$j][$k];
                    } else {
                        if ($analyses != null) {
                            $isVarchar = ($analyses[$i][self::TYPES][$colCount] === self::VARCHAR);
                        } else {
                            $isVarchar = ! is_numeric($tables[$i][self::ROWS][$j][$k]);
                        }

                        /* Don't put quotes around NULL fields */
                        if (! strcmp((string) $tables[$i][self::ROWS][$j][$k], 'NULL')) {
                            $isVarchar = false;
                        }

                        $tempSQLStr .= $isVarchar ? "'" : '';
                        $tempSQLStr .= $dbi->escapeString((string) $tables[$i][self::ROWS][$j][$k]);
                        $tempSQLStr .= $isVarchar ? "'" : '';
                    }

                    if ($k != $numCols - 1) {
                        $tempSQLStr .= ', ';
                    }

                    if ($colCount == $numCols - 1) {
                        $colCount = 0;
                    } else {
                        $colCount++;
                    }

                    /* Delete the cell after we are done with it */
                    unset($tables[$i][self::ROWS][$j][$k]);
                }

                $tempSQLStr .= ')';

                if ($j != $numRows - 1) {
                    $tempSQLStr .= ",\n ";
                }

                $colCount = 0;
                /* Delete the row after we are done with it */
                unset($tables[$i][self::ROWS][$j]);
            }

            $tempSQLStr .= ';';

            /**
             * Each SQL statement is executed immediately
             * after it is formed so that we don't have
             * to store them in a (possibly large) buffer
             */
            $this->runQuery($tempSQLStr, $tempSQLStr, $sqlData);
        }

        /* No longer needed */
        unset($tempSQLStr);

        /**
         * A work in progress
         */

        /**
         * Add the viewable structures from $additional_sql
         * to $tables so they are also displayed
         */
        $viewPattern = '@VIEW `[^`]+`\.`([^`]+)@';
        $tablePattern = '@CREATE TABLE IF NOT EXISTS `([^`]+)`@';
        /* Check a third pattern to make sure its not a "USE `db_name`;" statement */

        $regs = [];

        $inTables = false;

        $additionalSqlLength = $additionalSql === null ? 0 : count($additionalSql);
        for ($i = 0; $i < $additionalSqlLength; ++$i) {
            preg_match($viewPattern, $additionalSql[$i], $regs);

            if (count($regs) === 0) {
                preg_match($tablePattern, $additionalSql[$i], $regs);
            }

            if (count($regs)) {
                for ($n = 0; $n < $numTables; ++$n) {
                    if (! strcmp($regs[1], $tables[$n][self::TBL_NAME])) {
                        $inTables = true;
                        break;
                    }
                }

                if (! $inTables) {
                    $tables[] = [self::TBL_NAME => $regs[1]];
                }
            }

            /* Reset the array */
            $regs = [];
            $inTables = false;
        }

        $params = ['db' => $dbName];
        $dbUrl = Url::getFromRoute('/database/structure', $params);
        $dbOperationsUrl = Url::getFromRoute('/database/operations', $params);

        $message = '<br><br>';
        $message .= '<strong>' . __(
            'The following structures have either been created or altered. Here you can:'
        ) . '</strong><br>';
        $message .= '<ul><li>' . __("View a structure's contents by clicking on its name.") . '</li>';
        $message .= '<li>' . __('Change any of its settings by clicking the corresponding "Options" link.') . '</li>';
        $message .= '<li>' . __('Edit structure by following the "Structure" link.')
            . '</li>';
        $message .= sprintf(
            '<br><li><a href="%s" title="%s">%s</a> (<a href="%s" title="%s">'
            . __('Options') . '</a>)</li>',
            $dbUrl,
            sprintf(
                __('Go to database: %s'),
                htmlspecialchars(Util::backquote($dbName))
            ),
            htmlspecialchars($dbName),
            $dbOperationsUrl,
            sprintf(
                __('Edit settings for %s'),
                htmlspecialchars(Util::backquote($dbName))
            )
        );

        $message .= '<ul>';

        unset($params);

        foreach ($tables as $table) {
            $params = [
                'db' => $dbName,
                'table' => (string) $table[self::TBL_NAME],
            ];
            $tblUrl = Url::getFromRoute('/sql', $params);
            $tblStructUrl = Url::getFromRoute('/table/structure', $params);
            $tblOpsUrl = Url::getFromRoute('/table/operations', $params);

            unset($params);

            $tableObj = new Table($table[self::TBL_NAME], $dbName);
            if (! $tableObj->isView()) {
                $message .= sprintf(
                    '<li><a href="%s" title="%s">%s</a> (<a href="%s" title="%s">' . __(
                        'Structure'
                    ) . '</a>) (<a href="%s" title="%s">' . __('Options') . '</a>)</li>',
                    $tblUrl,
                    sprintf(
                        __('Go to table: %s'),
                        htmlspecialchars(
                            Util::backquote($table[self::TBL_NAME])
                        )
                    ),
                    htmlspecialchars($table[self::TBL_NAME]),
                    $tblStructUrl,
                    sprintf(
                        __('Structure of %s'),
                        htmlspecialchars(
                            Util::backquote($table[self::TBL_NAME])
                        )
                    ),
                    $tblOpsUrl,
                    sprintf(
                        __('Edit settings for %s'),
                        htmlspecialchars(
                            Util::backquote($table[self::TBL_NAME])
                        )
                    )
                );
            } else {
                $message .= sprintf(
                    '<li><a href="%s" title="%s">%s</a></li>',
                    $tblUrl,
                    sprintf(
                        __('Go to view: %s'),
                        htmlspecialchars(
                            Util::backquote($table[self::TBL_NAME])
                        )
                    ),
                    htmlspecialchars($table[self::TBL_NAME])
                );
            }
        }

        $message .= '</ul></ul>';

        $import_notice = $message;
    }

    /**
     * Handles request for ROLLBACK.
     *
     * @param string $sqlQuery SQL query(s)
     */
    public function handleRollbackRequest(string $sqlQuery): void
    {
        global $dbi;

        $sqlDelimiter = $_POST['sql_delimiter'];
        $queries = explode($sqlDelimiter, $sqlQuery);
        $error = false;
        $errorMsg = __(
            'Only INSERT, UPDATE, DELETE and REPLACE '
            . 'SQL queries containing transactional engine tables can be rolled back.'
        );
        foreach ($queries as $sqlQuery) {
            if (empty($sqlQuery)) {
                continue;
            }

            // Check each query for ROLLBACK support.
            if ($this->checkIfRollbackPossible($sqlQuery)) {
                continue;
            }

            $globalError = $dbi->getError();
            if ($globalError) {
                $error = $globalError;
            } else {
                $error = $errorMsg;
            }

            break;
        }

        if ($error) {
            unset($_POST['rollback_query']);
            $response = ResponseRenderer::getInstance();
            $message = Message::rawError($error);
            $response->addJSON('message', $message);
            exit;
        }

        // If everything fine, START a transaction.
        $dbi->query('START TRANSACTION');
    }

    /**
     * Checks if ROLLBACK is possible for a SQL query or not.
     *
     * @param string $sqlQuery SQL query
     */
    public function checkIfRollbackPossible(string $sqlQuery): bool
    {
        $parser = new Parser($sqlQuery);

        if (empty($parser->statements[0])) {
            return true;
        }

        $statement = $parser->statements[0];

        // Check if query is supported.
        if (
            ! (($statement instanceof InsertStatement)
            || ($statement instanceof UpdateStatement)
            || ($statement instanceof DeleteStatement)
            || ($statement instanceof ReplaceStatement))
        ) {
            return false;
        }

        // Get table_references from the query.
        $tables = Query::getTables($statement);

        // Check if each table is 'InnoDB'.
        foreach ($tables as $table) {
            if (! $this->isTableTransactional($table)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a table is 'InnoDB' or not.
     *
     * @param string $table Table details
     */
    public function isTableTransactional(string $table): bool
    {
        global $dbi;

        $table = explode('.', $table);
        if (count($table) === 2) {
            $db = Util::unQuote($table[0]);
            $table = Util::unQuote($table[1]);
        } else {
            $db = $GLOBALS['db'];
            $table = Util::unQuote($table[0]);
        }

        // Query to check if table exists.
        $checkTableQuery = 'SELECT * FROM ' . Util::backquote($db)
            . '.' . Util::backquote($table) . ' '
            . 'LIMIT 1';

        $result = $dbi->tryQuery($checkTableQuery);

        if (! $result) {
            return false;
        }

        // List of Transactional Engines.
        $transactionalEngines = [
            'INNODB',
            'FALCON',
            'NDB',
            'INFINIDB',
            'TOKUDB',
            'XTRADB',
            'SEQUENCE',
            'BDB',
            'ROCKSDB',
        ];

        // Query to check if table is 'Transactional'.
        $checkQuery = 'SELECT `ENGINE` FROM `information_schema`.`tables` '
            . 'WHERE `table_name` = "' . $dbi->escapeString($table) . '" '
            . 'AND `table_schema` = "' . $dbi->escapeString($db) . '" '
            . 'AND UPPER(`engine`) IN ("'
            . implode('", "', $transactionalEngines)
            . '")';

        $result = $dbi->tryQuery($checkQuery);

        return $result && $result->numRows() == 1;
    }

    /** @return string[] */
    public static function getCompressions(): array
    {
        global $cfg;

        $compressions = [];

        if ($cfg['GZipDump'] && function_exists('gzopen')) {
            $compressions[] = 'gzip';
        }

        if ($cfg['BZipDump'] && function_exists('bzopen')) {
            $compressions[] = 'bzip2';
        }

        if ($cfg['ZipDump'] && function_exists('zip_open')) {
            $compressions[] = 'zip';
        }

        return $compressions;
    }

    /**
     * @param array $importList List of plugin instances.
     *
     * @return false|string
     */
    public static function getLocalFiles(array $importList)
    {
        $fileListing = new FileListing();

        $extensions = '';
        foreach ($importList as $importPlugin) {
            if (! empty($extensions)) {
                $extensions .= '|';
            }

            $extensions .= $importPlugin->getProperties()->getExtension();
        }

        $matcher = '@\.(' . $extensions . ')(\.(' . $fileListing->supportedDecompressions() . '))?$@';

        $active = isset($GLOBALS['timeout_passed'], $GLOBALS['local_import_file']) && $GLOBALS['timeout_passed']
            ? $GLOBALS['local_import_file']
            : '';

        return $fileListing->getFileSelectOptions(
            Util::userDir((string) ($GLOBALS['cfg']['UploadDir'] ?? '')),
            $matcher,
            $active
        );
    }
}
