<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Features\DisplayFeature;
use PhpMyAdmin\ConfigStorage\Features\RelationFeature;
use PhpMyAdmin\ConfigStorage\Features\UiPreferencesFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Utils\Table as TableUtils;
use Stringable;

use function __;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function end;
use function explode;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function mb_stripos;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_replace;
use function stripos;
use function strlen;
use function strtolower;
use function strtoupper;
use function substr;
use function substr_compare;
use function trigger_error;
use function trim;

use const E_USER_WARNING;

/**
 * Handles everything related to tables
 *
 * @todo make use of Message and Error
 */
class Table implements Stringable
{
    /**
     * UI preferences properties
     */
    public const PROP_SORTED_COLUMN = 'sorted_col';
    public const PROP_COLUMN_ORDER = 'col_order';
    public const PROP_COLUMN_VISIB = 'col_visib';

    /** @var string  engine (innodb, myisam, bdb, ...) */
    public $engine = '';

    /** @var string  type (view, base table, system view) */
    public $type = '';

    /** @var array UI preferences */
    public $uiprefs = [];

    /** @var array errors occurred */
    public $errors = [];

    /** @var array messages */
    public $messages = [];

    /** @var string  table name */
    protected $name = '';

    /** @var string  database name */
    protected $dbName = '';

    /** @var DatabaseInterface */
    protected $dbi;

    /** @var Relation */
    private $relation;

    /**
     * @param string                 $tableName table name
     * @param string                 $dbName    database name
     * @param DatabaseInterface|null $dbi       database interface for the table
     */
    public function __construct($tableName, $dbName, ?DatabaseInterface $dbi = null)
    {
        if (empty($dbi)) {
            $dbi = $GLOBALS['dbi'];
        }

        $this->dbi = $dbi;
        $this->name = $tableName;
        $this->dbName = $dbName;
        $this->relation = new Relation($this->dbi);
    }

    /**
     * returns table name
     *
     * @see Table::getName()
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * Table getter
     *
     * @param string                 $tableName table name
     * @param string                 $dbName    database name
     * @param DatabaseInterface|null $dbi       database interface for the table
     *
     * @return Table
     */
    public static function get($tableName, $dbName, ?DatabaseInterface $dbi = null)
    {
        return new Table($tableName, $dbName, $dbi);
    }

    /**
     * return the last error
     *
     * @return string the last error
     */
    public function getLastError()
    {
        return end($this->errors);
    }

    /**
     * return the last message
     *
     * @return string the last message
     */
    public function getLastMessage()
    {
        return end($this->messages);
    }

    /**
     * returns table name
     *
     * @param bool $backquoted whether to quote name with backticks ``
     *
     * @return string  table name
     */
    public function getName($backquoted = false)
    {
        if ($backquoted) {
            return Util::backquote($this->name);
        }

        return $this->name;
    }

    /**
     * returns database name for this table
     *
     * @param bool $backquoted whether to quote name with backticks ``
     *
     * @return string  database name for this table
     */
    public function getDbName($backquoted = false)
    {
        if ($backquoted) {
            return Util::backquote($this->dbName);
        }

        return $this->dbName;
    }

    /**
     * returns full name for table, including database name
     *
     * @param bool $backquoted whether to quote name with backticks ``
     *
     * @return string
     */
    public function getFullName($backquoted = false)
    {
        return $this->getDbName($backquoted) . '.'
        . $this->getName($backquoted);
    }

    /**
     * Checks the storage engine used to create table
     *
     * @param array|string $engine Checks the table engine against an
     *                             array of engine strings or a single string, should be uppercase
     */
    public function isEngine($engine): bool
    {
        $tableStorageEngine = $this->getStorageEngine();

        if (is_array($engine)) {
            foreach ($engine as $e) {
                if ($e == $tableStorageEngine) {
                    return true;
                }
            }

            return false;
        }

        return $tableStorageEngine == $engine;
    }

    /**
     * returns whether the table is actually a view
     */
    public function isView(): bool
    {
        $db = $this->dbName;
        $table = $this->name;
        if (empty($db) || empty($table)) {
            return false;
        }

        // use cached data or load information with SHOW command
        if (
            $this->dbi->getCache()->getCachedTableContent([$db, $table]) != null
            || $GLOBALS['cfg']['Server']['DisableIS']
        ) {
            $type = $this->getStatusInfo('TABLE_TYPE');

            return $type === 'VIEW' || $type === 'SYSTEM VIEW';
        }

        // information_schema tables are 'SYSTEM VIEW's
        if ($db === 'information_schema') {
            return true;
        }

        // query information_schema
        $result = $this->dbi->fetchResult(
            'SELECT TABLE_NAME'
            . ' FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'' . $this->dbi->escapeString((string) $db) . '\''
            . ' AND TABLE_NAME = \'' . $this->dbi->escapeString((string) $table) . '\''
        );

        return (bool) $result;
    }

    /**
     * Returns whether the table is actually an updatable view
     */
    public function isUpdatableView(): bool
    {
        if (empty($this->dbName) || empty($this->name)) {
            return false;
        }

        $result = $this->dbi->fetchResult(
            'SELECT TABLE_NAME'
            . ' FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'' . $this->dbi->escapeString($this->dbName) . '\''
            . ' AND TABLE_NAME = \'' . $this->dbi->escapeString($this->name) . '\''
            . ' AND IS_UPDATABLE = \'YES\''
        );

        return (bool) $result;
    }

    /**
     * Checks if this is a merge table
     *
     * If the ENGINE of the table is MERGE or MRG_MYISAM (alias),
     * this is a merge table.
     */
    public function isMerge(): bool
    {
        return $this->isEngine(['MERGE', 'MRG_MYISAM']);
    }

    /**
     * Returns full table status info, or specific if $info provided
     * this info is collected from information_schema
     *
     * @param string $info         specific information to be fetched
     * @param bool   $forceRead    read new rather than serving from cache
     * @param bool   $disableError if true, disables error message
     *
     * @return mixed
     *
     * @todo DatabaseInterface::getTablesFull needs to be merged
     * somehow into this class or at least better documented
     */
    public function getStatusInfo(
        $info = null,
        $forceRead = false,
        $disableError = false
    ) {
        $db = $this->dbName;
        $table = $this->name;

        if (! empty($_SESSION['is_multi_query'])) {
            $disableError = true;
        }

        $cachedResult = $this->dbi->getCache()->getCachedTableContent([$db, $table]);

        // sometimes there is only one entry (ExactRows) so
        // we have to get the table's details
        if ($cachedResult === null || $forceRead || count($cachedResult) === 1) {
            $this->dbi->getTablesFull($db, $table);
            $cachedResult = $this->dbi->getCache()->getCachedTableContent([$db, $table]);
        }

        if ($cachedResult === null) {
            // happens when we enter the table creation dialog
            // or when we really did not get any status info, for example
            // when $table === 'TABLE_NAMES' after the user tried SHOW TABLES
            return '';
        }

        if ($info === null) {
            return $cachedResult;
        }

        // array_key_exists allows for null values
        if (! array_key_exists($info, $cachedResult)) {
            if (! $disableError) {
                trigger_error(
                    __('Unknown table status:') . ' ' . $info,
                    E_USER_WARNING
                );
            }

            return false;
        }

        return $this->dbi->getCache()->getCachedTableContent([$db, $table, $info]);
    }

    /**
     * Returns the Table storage Engine for current table.
     *
     * @return string                 Return storage engine info if it is set for
     *                                the selected table else return blank.
     */
    public function getStorageEngine(): string
    {
        $tableStorageEngine = $this->getStatusInfo('ENGINE', false, true);
        if ($tableStorageEngine === false) {
            return '';
        }

        return strtoupper((string) $tableStorageEngine);
    }

    /**
     * Returns the comments for current table.
     *
     * @return string Return comment info if it is set for the selected table or return blank.
     */
    public function getComment()
    {
        $tableComment = $this->getStatusInfo('TABLE_COMMENT', false, true);
        if ($tableComment === false) {
            return '';
        }

        return $tableComment;
    }

    /**
     * Returns the collation for current table.
     *
     * @return string Return blank if collation is empty else return the collation info from table info.
     */
    public function getCollation()
    {
        $tableCollation = $this->getStatusInfo('TABLE_COLLATION', false, true);
        if ($tableCollation === false) {
            return '';
        }

        return $tableCollation;
    }

    /**
     * Returns the info about no of rows for current table.
     *
     * @return int Return no of rows info if it is not null for the selected table or return 0.
     */
    public function getNumRows()
    {
        $tableNumRowInfo = $this->getStatusInfo('TABLE_ROWS', false, true);
        if ($tableNumRowInfo === false) {
            $tableNumRowInfo = $this->dbi->getTable($this->dbName, $GLOBALS['showtable']['Name'])
            ->countRecords(true);
        }

        return $tableNumRowInfo ?: 0;
    }

    /**
     * Returns the Row format for current table.
     *
     * @return string Return table row format info if it is set for the selected table or return blank.
     */
    public function getRowFormat()
    {
        $tableRowFormat = $this->getStatusInfo('ROW_FORMAT', false, true);
        if ($tableRowFormat === false) {
            return '';
        }

        return $tableRowFormat;
    }

    /**
     * Returns the auto increment option for current table.
     *
     * @return int Return auto increment info if it is set for the selected table or return blank.
     */
    public function getAutoIncrement()
    {
        $tableAutoIncrement = $this->getStatusInfo('AUTO_INCREMENT', false, true);

        return $tableAutoIncrement ?? '';
    }

    /**
     * Returns the array for CREATE statement for current table.
     *
     * @return array Return options array info if it is set for the selected table or return blank.
     */
    public function getCreateOptions()
    {
        $tableOptions = $this->getStatusInfo('CREATE_OPTIONS', false, true);
        $createOptionsTmp = empty($tableOptions) ? [] : explode(' ', $tableOptions);
        $createOptions = [];
        // export create options by its name as variables into global namespace
        // f.e. pack_keys=1 becomes available as $pack_keys with value of '1'
        // unset($pack_keys);
        foreach ($createOptionsTmp as $eachCreateOption) {
            $eachCreateOption = explode('=', $eachCreateOption);
            if (! isset($eachCreateOption[1])) {
                continue;
            }

            // ensure there is no ambiguity for PHP 5 and 7
            $createOptions[$eachCreateOption[0]] = $eachCreateOption[1];
        }

        // we need explicit DEFAULT value here (different from '0')
        $hasPackKeys = isset($createOptions['pack_keys']) && strlen($createOptions['pack_keys']) > 0;
        $createOptions['pack_keys'] = $hasPackKeys ? $createOptions['pack_keys'] : 'DEFAULT';

        return $createOptions;
    }

    /**
     * generates column specification for ALTER or CREATE TABLE syntax
     *
     * @param string      $name             name
     * @param string      $type             type ('INT', 'VARCHAR', 'BIT', ...)
     * @param string      $length           length ('2', '5,2', '', ...)
     * @param string      $attribute        attribute
     * @param string      $collation        collation
     * @param bool|string $null             with 'NULL' or 'NOT NULL'
     * @param string      $defaultType      whether default is CURRENT_TIMESTAMP,
     *                                       NULL, NONE, USER_DEFINED, UUID
     * @param string      $defaultValue     default value for USER_DEFINED
     *                                       default type
     * @param string      $extra            'AUTO_INCREMENT'
     * @param string      $comment          field comment
     * @param string      $virtuality       virtuality of the column
     * @param string      $expression       expression for the virtual column
     * @param string      $moveTo           new position for column
     * @param array       $columnsWithIndex Fields having PRIMARY or UNIQUE KEY indexes
     * @param string      $oldColumnName    Old column name
     *
     * @return string  field specification
     *
     * @todo    move into class PMA_Column
     * @todo on the interface, some js to clear the default value when the
     * default current_timestamp is checked
     */
    public static function generateFieldSpec(
        $name,
        string $type,
        string $length = '',
        $attribute = '',
        $collation = '',
        $null = false,
        $defaultType = 'USER_DEFINED',
        $defaultValue = '',
        $extra = '',
        $comment = '',
        $virtuality = '',
        $expression = '',
        $moveTo = '',
        $columnsWithIndex = null,
        $oldColumnName = null
    ) {
        global $dbi;

        $strLength = strlen($length);
        $isTimestamp = mb_stripos($type, 'TIMESTAMP') !== false;

        $query = Util::backquote($name) . ' ' . $type;

        // allow the possibility of a length for TIME, DATETIME and TIMESTAMP
        // (will work on MySQL >= 5.6.4)
        //
        // MySQL permits a non-standard syntax for FLOAT and DOUBLE,
        // see https://dev.mysql.com/doc/refman/5.5/en/floating-point-types.html
        $pattern = '@^(DATE|TINYBLOB|TINYTEXT|BLOB|TEXT|'
            . 'MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|SERIAL|BOOLEAN|UUID|JSON)$@i';
        if (
            $strLength !== 0
            && ! preg_match($pattern, $type)
            && Compatibility::isIntegersSupportLength($type, $length, $dbi)
        ) {
            // Note: The variable $length here can contain several other things
            // besides length - ENUM/SET value or length of DECIMAL (eg. 12,3)
            // so we can't just convert it to integer
            $query .= '(' . $length . ')';
        }

        if ($attribute != '') {
            $query .= ' ' . $attribute;

            if ($isTimestamp && stripos($attribute, 'TIMESTAMP') !== false && $strLength !== 0) {
                $query .= '(' . $length . ')';
            }
        }

        // if column is virtual, check if server type is Mysql as only Mysql server
        // supports extra column properties
        $isVirtualColMysql = $virtuality && Compatibility::isMySqlOrPerconaDb();
        // if column is virtual, check if server type is MariaDB as MariaDB server
        // supports no extra virtual column properties except CHARACTER SET for text column types
        $isVirtualColMariaDB = $virtuality && Compatibility::isMariaDb();

        $matches = preg_match('@^(TINYTEXT|TEXT|MEDIUMTEXT|LONGTEXT|VARCHAR|CHAR|ENUM|SET)$@i', $type);
        if (! empty($collation) && $collation !== 'NULL' && $matches) {
            $query .= Util::getCharsetQueryPart(
                $isVirtualColMariaDB ? (string) preg_replace('~_.+~s', '', $collation) : $collation,
                true
            );
        }

        if ($virtuality) {
            $query .= ' AS (' . $expression . ') ' . $virtuality;
        }

        if (! $virtuality || $isVirtualColMysql) {
            if ($null !== false) {
                if ($null === 'YES') {
                    $query .= ' NULL';
                } else {
                    $query .= ' NOT NULL';
                }
            }

            if (! $virtuality) {
                switch ($defaultType) {
                    case 'USER_DEFINED':
                        if ($isTimestamp && $defaultValue === '0') {
                            // a TIMESTAMP does not accept DEFAULT '0'
                            // but DEFAULT 0 works
                            $query .= ' DEFAULT 0';
                        } elseif (
                            $isTimestamp
                            && preg_match(
                                '/^\'\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d(\.\d{1,6})?\'$/',
                                (string) $defaultValue
                            )
                        ) {
                            $query .= ' DEFAULT ' . (string) $defaultValue;
                        } elseif ($type === 'BIT') {
                            $query .= ' DEFAULT b\''
                            . preg_replace('/[^01]/', '0', (string) $defaultValue)
                            . '\'';
                        } elseif ($type === 'BOOLEAN') {
                            if (preg_match('/^1|T|TRUE|YES$/i', (string) $defaultValue)) {
                                $query .= ' DEFAULT TRUE';
                            } elseif (preg_match('/^0|F|FALSE|NO$/i', $defaultValue)) {
                                $query .= ' DEFAULT FALSE';
                            } else {
                                // Invalid BOOLEAN value
                                $query .= ' DEFAULT \''
                                . $dbi->escapeString($defaultValue) . '\'';
                            }
                        } elseif ($type === 'BINARY' || $type === 'VARBINARY') {
                            $query .= ' DEFAULT 0x' . $defaultValue;
                        } else {
                            $query .= ' DEFAULT \''
                            . $dbi->escapeString((string) $defaultValue) . '\'';
                        }

                        break;
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case 'NULL':
                        // If user uncheck null checkbox and not change default value null,
                        // default value will be ignored.
                        if ($null !== false && $null !== 'YES') {
                            break;
                        }
                        // else fall-through intended, no break here
                    case 'CURRENT_TIMESTAMP':
                    case 'current_timestamp()':
                        $query .= ' DEFAULT ' . $defaultType;

                        if (
                            $strLength !== 0
                            && $isTimestamp
                            && $defaultType !== 'NULL' // Not to be added in case of NULL
                        ) {
                            $query .= '(' . $length . ')';
                        }

                        break;
                    case 'UUID':
                    case 'uuid()':
                        $query .= ' DEFAULT uuid()';

                        break;
                    case 'NONE':
                    default:
                        break;
                }
            }

            if (! empty($extra)) {
                if ($virtuality) {
                    $extra = trim((string) preg_replace('~^\s*AUTO_INCREMENT\s*~is', ' ', $extra));
                }

                $query .= ' ' . $extra;
            }
        }

        if (! empty($comment)) {
            $query .= " COMMENT '" . $dbi->escapeString($comment) . "'";
        }

        // move column
        if ($moveTo === '-first') { // dash can't appear as part of column name
            $query .= ' FIRST';
        } elseif ($moveTo != '') {
            $query .= ' AFTER ' . Util::backquote($moveTo);
        }

        if (! $virtuality && ! empty($extra)) {
            if ($oldColumnName === null) {
                if (is_array($columnsWithIndex) && ! in_array($name, $columnsWithIndex)) {
                    $query .= ', add PRIMARY KEY (' . Util::backquote($name) . ')';
                }
            } else {
                if (is_array($columnsWithIndex) && ! in_array($oldColumnName, $columnsWithIndex)) {
                    $query .= ', add PRIMARY KEY (' . Util::backquote($name) . ')';
                }
            }
        }

        return $query;
    }

    /**
     * Checks if the number of records in a table is at least equal to
     * $min_records
     *
     * @param int $minRecords Number of records to check for in a table
     */
    public function checkIfMinRecordsExist($minRecords = 0): bool
    {
        $checkQuery = 'SELECT ';

        $uniqueFields = $this->getUniqueColumns(true, false);
        if (count($uniqueFields) > 0) {
            $fieldsToSelect = implode(', ', $uniqueFields);
        } else {
            $indexedCols = $this->getIndexedColumns(true, false);
            if (count($indexedCols) > 0) {
                $fieldsToSelect = implode(', ', $indexedCols);
            } else {
                $fieldsToSelect = '*';
            }
        }

        $checkQuery .= $fieldsToSelect
            . ' FROM ' . $this->getFullName(true)
            . ' LIMIT ' . $minRecords;

        $res = $this->dbi->tryQuery($checkQuery);

        if ($res !== false) {
            $numRecords = $res->numRows();
            if ($numRecords >= $minRecords) {
                return true;
            }
        }

        return false;
    }

    /**
     * Counts and returns (or displays) the number of records in a table
     *
     * @param bool $forceExact whether to force an exact count
     *
     * @return mixed the number of records if "retain" param is true,
     *               otherwise true
     */
    public function countRecords($forceExact = false)
    {
        $isView = $this->isView();
        $db = $this->dbName;
        $table = $this->name;

        if ($this->dbi->getCache()->getCachedTableContent([$db, $table, 'ExactRows']) != null) {
            return $this->dbi->getCache()->getCachedTableContent(
                [
                    $db,
                    $table,
                    'ExactRows',
                ]
            );
        }

        $rowCount = false;

        if (! $forceExact) {
            if (($this->dbi->getCache()->getCachedTableContent([$db, $table, 'Rows']) == null) && ! $isView) {
                $tmpTables = $this->dbi->getTablesFull($db, $table);
                if (isset($tmpTables[$table])) {
                    $this->dbi->getCache()->cacheTableContent(
                        [
                            $db,
                            $table,
                        ],
                        $tmpTables[$table]
                    );
                }
            }

            if ($this->dbi->getCache()->getCachedTableContent([$db, $table, 'Rows']) != null) {
                $rowCount = $this->dbi->getCache()->getCachedTableContent(
                    [
                        $db,
                        $table,
                        'Rows',
                    ]
                );
            } else {
                $rowCount = false;
            }
        }

        // for a VIEW, $row_count is always false at this point
        if ($rowCount !== false && $rowCount >= $GLOBALS['cfg']['MaxExactCount']) {
            return $rowCount;
        }

        if (! $isView) {
            $rowCount = $this->dbi->fetchValue(
                'SELECT COUNT(*) FROM ' . Util::backquote($db) . '.'
                . Util::backquote($table)
            );
        } else {
            // For complex views, even trying to get a partial record
            // count could bring down a server, so we offer an
            // alternative: setting MaxExactCountViews to 0 will bypass
            // completely the record counting for views

            if ($GLOBALS['cfg']['MaxExactCountViews'] == 0) {
                $rowCount = false;
            } else {
                // Counting all rows of a VIEW could be too long,
                // so use a LIMIT clause.
                // Use try_query because it can fail (when a VIEW is
                // based on a table that no longer exists)
                $result = $this->dbi->tryQuery(
                    'SELECT 1 FROM ' . Util::backquote($db) . '.'
                    . Util::backquote($table) . ' LIMIT '
                    . $GLOBALS['cfg']['MaxExactCountViews']
                );
                if ($result) {
                    $rowCount = $result->numRows();
                }
            }
        }

        if ($rowCount) {
            $this->dbi->getCache()->cacheTableContent([$db, $table, 'ExactRows'], $rowCount);
        }

        return $rowCount;
    }

    /**
     * Generates column specification for ALTER syntax
     *
     * @see Table::generateFieldSpec()
     *
     * @param string      $oldcol           old column name
     * @param string      $newcol           new column name
     * @param string      $type             type ('INT', 'VARCHAR', 'BIT', ...)
     * @param string      $length           length ('2', '5,2', '', ...)
     * @param string      $attribute        attribute
     * @param string      $collation        collation
     * @param bool|string $null             with 'NULL' or 'NOT NULL'
     * @param string      $defaultType      whether default is CURRENT_TIMESTAMP,
     *                                       NULL, NONE, USER_DEFINED
     * @param string      $defaultValue     default value for USER_DEFINED default
     *                                       type
     * @param string      $extra            'AUTO_INCREMENT'
     * @param string      $comment          field comment
     * @param string      $virtuality       virtuality of the column
     * @param string      $expression       expression for the virtual column
     * @param string      $moveTo           new position for column
     * @param array       $columnsWithIndex Fields having PRIMARY or UNIQUE KEY indexes
     *
     * @return string  field specification
     */
    public static function generateAlter(
        $oldcol,
        $newcol,
        $type,
        $length,
        $attribute,
        $collation,
        $null,
        $defaultType,
        $defaultValue,
        $extra,
        $comment,
        $virtuality,
        $expression,
        $moveTo,
        $columnsWithIndex = null
    ) {
        return Util::backquote($oldcol) . ' '
        . self::generateFieldSpec(
            $newcol,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
            $columnsWithIndex,
            $oldcol
        );
    }

    /**
     * Inserts existing entries in a PMA_* table by reading a value from an old
     * entry
     *
     * @param string $work        The array index, which Relation feature to check ('relwork', 'commwork', ...)
     * @param string $table       The array index, which PMA-table to update ('bookmark', 'relation', ...)
     * @param array  $getFields   Which fields will be SELECT'ed from the old entry
     * @param array  $whereFields Which fields will be used for the WHERE query (array('FIELDNAME' => 'FIELDVALUE'))
     * @param array  $newFields   Which fields will be used as new VALUES. These are the important keys which differ
     *                            from the old entry (array('FIELDNAME' => 'NEW FIELDVALUE'))
     *
     * @return int|bool
     */
    public static function duplicateInfo(
        $work,
        $table,
        array $getFields,
        array $whereFields,
        array $newFields
    ) {
        global $dbi;

        $relation = new Relation($dbi);
        $relationParameters = $relation->getRelationParameters();
        $relationParams = $relationParameters->toArray();
        $lastId = -1;

        if (! isset($relationParams[$work], $relationParams[$table]) || ! $relationParams[$work]) {
            return true;
        }

        $selectParts = [];
        $rowFields = [];
        foreach ($getFields as $getField) {
            $selectParts[] = Util::backquote($getField);
            $rowFields[$getField] = 'cc';
        }

        $whereParts = [];
        foreach ($whereFields as $where => $value) {
            $whereParts[] = Util::backquote($where) . ' = \''
                . $dbi->escapeString((string) $value) . '\'';
        }

        $newParts = [];
        $newValueParts = [];
        foreach ($newFields as $where => $value) {
            $newParts[] = Util::backquote($where);
            $newValueParts[] = $dbi->escapeString((string) $value);
        }

        $tableCopyQuery = '
            SELECT ' . implode(', ', $selectParts) . '
              FROM ' . Util::backquote($relationParameters->db) . '.'
              . Util::backquote((string) $relationParams[$table]) . '
             WHERE ' . implode(' AND ', $whereParts);

        // must use DatabaseInterface::QUERY_BUFFERED here, since we execute
        // another query inside the loop
        $tableCopyRs = $dbi->queryAsControlUser($tableCopyQuery);

        foreach ($tableCopyRs as $tableCopyRow) {
            $valueParts = [];
            foreach ($tableCopyRow as $key => $val) {
                if (! isset($rowFields[$key]) || $rowFields[$key] != 'cc') {
                    continue;
                }

                $valueParts[] = $dbi->escapeString($val);
            }

            $newTableQuery = 'INSERT IGNORE INTO '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote((string) $relationParams[$table])
                . ' (' . implode(', ', $selectParts) . ', '
                . implode(', ', $newParts) . ') VALUES (\''
                . implode('\', \'', $valueParts) . '\', \''
                . implode('\', \'', $newValueParts) . '\')';

            $dbi->queryAsControlUser($newTableQuery);
            $lastId = $dbi->insertId();
        }

        return $lastId;
    }

    /**
     * Copies or renames table
     *
     * @param string      $sourceDb    source database
     * @param string      $sourceTable source table
     * @param string|null $targetDb    target database
     * @param string      $targetTable target table
     * @param string      $what        what to be moved or copied (data, dataonly)
     * @param bool        $move        whether to move
     * @param string      $mode        mode
     */
    public static function moveCopy(
        $sourceDb,
        $sourceTable,
        ?string $targetDb,
        $targetTable,
        $what,
        $move,
        $mode,
        bool $addDropIfExists
    ): bool {
        global $errorUrl, $dbi;

        $relation = new Relation($dbi);

        // Try moving the tables directly, using native `RENAME` statement.
        if ($move && $what === 'data') {
            $tbl = new Table($sourceTable, $sourceDb);
            if ($tbl->rename($targetTable, $targetDb)) {
                $GLOBALS['message'] = $tbl->getLastMessage();

                return true;
            }
        }

        // Setting required export settings.
        $GLOBALS['sql_backquotes'] = 1;
        $GLOBALS['asfile'] = 1;

        // Ensuring the target database is valid.
        if (! $GLOBALS['dblist']->databases->exists($sourceDb, $targetDb)) {
            if (! $GLOBALS['dblist']->databases->exists($sourceDb)) {
                $GLOBALS['message'] = Message::rawError(
                    sprintf(
                        __('Source database `%s` was not found!'),
                        htmlspecialchars($sourceDb)
                    )
                );
            }

            if (! $GLOBALS['dblist']->databases->exists($targetDb)) {
                $GLOBALS['message'] = Message::rawError(
                    sprintf(
                        __('Target database `%s` was not found!'),
                        htmlspecialchars((string) $targetDb)
                    )
                );
            }

            return false;
        }

        /**
         * The full name of source table, quoted.
         *
         * @var string $source
         */
        $source = Util::backquote($sourceDb)
            . '.' . Util::backquote($sourceTable);

        // If the target database is not specified, the operation is taking
        // place in the same database.
        if (! isset($targetDb) || strlen($targetDb) === 0) {
            $targetDb = $sourceDb;
        }

        // Selecting the database could avoid some problems with replicated
        // databases, when moving table from replicated one to not replicated one.
        $dbi->selectDb($targetDb);

        /**
         * The full name of target table, quoted.
         *
         * @var string $target
         */
        $target = Util::backquote($targetDb)
            . '.' . Util::backquote($targetTable);

        // No table is created when this is a data-only operation.
        if ($what !== 'dataonly') {
            /**
             * Instance used for exporting the current structure of the table.
             *
             * @var ExportSql $exportSqlPlugin
             */
            $exportSqlPlugin = Plugins::getPlugin('export', 'sql', [
                'export_type' => 'table',
                'single_table' => false,
            ]);

            $noConstraintsComments = true;
            $GLOBALS['sql_constraints_query'] = '';
            // set the value of global sql_auto_increment variable
            if (isset($_POST['sql_auto_increment'])) {
                $GLOBALS['sql_auto_increment'] = $_POST['sql_auto_increment'];
            }

            /**
             * The old structure of the table..
             */
            $sqlStructure = $exportSqlPlugin->getTableDef($sourceDb, $sourceTable, "\n", $errorUrl, false, false);

            unset($noConstraintsComments);

            // -----------------------------------------------------------------
            // Phase 0: Preparing structures used.

            /**
             * The destination where the table is moved or copied to.
             */
            $destination = new Expression($targetDb, $targetTable, '');

            // Find server's SQL mode so the builder can generate correct
            // queries.
            // One of the options that alters the behaviour is `ANSI_QUOTES`.
            Context::setMode((string) $dbi->fetchValue('SELECT @@sql_mode'));

            // -----------------------------------------------------------------
            // Phase 1: Dropping existent element of the same name (if exists
            // and required).

            if ($addDropIfExists) {
                /**
                 * Drop statement used for building the query.
                 */
                $statement = new DropStatement();

                $tbl = new Table($targetDb, $targetTable);

                $statement->options = new OptionsArray(
                    [
                        $tbl->isView() ? 'VIEW' : 'TABLE',
                        'IF EXISTS',
                    ]
                );

                $statement->fields = [$destination];

                // Building the query.
                $dropQuery = $statement->build() . ';';

                // Executing it.
                $dbi->query($dropQuery);
                $GLOBALS['sql_query'] .= "\n" . $dropQuery;

                // If an existing table gets deleted, maintain any entries for
                // the PMA_* tables.
                $maintainRelations = true;
            }

            // -----------------------------------------------------------------
            // Phase 2: Generating the new query of this structure.

            /**
             * The parser responsible for parsing the old queries.
             */
            $parser = new Parser($sqlStructure);

            if (! empty($parser->statements[0])) {

                /**
                 * The CREATE statement of this structure.
                 *
                 * @var CreateStatement $statement
                 */
                $statement = $parser->statements[0];

                // Changing the destination.
                $statement->name = $destination;

                // Building back the query.
                $sqlStructure = $statement->build() . ';';

                // This is to avoid some issues when renaming databases with views
                // See: https://github.com/phpmyadmin/phpmyadmin/issues/16422
                if ($move) {
                    $dbi->selectDb($targetDb);
                }

                // Executing it
                $dbi->query($sqlStructure);
                $GLOBALS['sql_query'] .= "\n" . $sqlStructure;
            }

            // -----------------------------------------------------------------
            // Phase 3: Adding constraints.
            // All constraint names are removed because they must be unique.

            if (($move || isset($GLOBALS['add_constraints'])) && ! empty($GLOBALS['sql_constraints_query'])) {
                $parser = new Parser($GLOBALS['sql_constraints_query']);

                /**
                 * The ALTER statement that generates the constraints.
                 *
                 * @var AlterStatement $statement
                 */
                $statement = $parser->statements[0];

                // Changing the altered table to the destination.
                $statement->table = $destination;

                // Removing the name of the constraints.
                foreach ($statement->altered as $altered) {
                    // All constraint names are removed because they must be unique.
                    if (! $altered->options->has('CONSTRAINT')) {
                        continue;
                    }

                    $altered->field = null;
                }

                // Building back the query.
                $GLOBALS['sql_constraints_query'] = $statement->build() . ';';

                // Executing it.
                if ($mode === 'one_table') {
                    $dbi->query($GLOBALS['sql_constraints_query']);
                }

                $GLOBALS['sql_query'] .= "\n" . $GLOBALS['sql_constraints_query'];
                if ($mode === 'one_table') {
                    unset($GLOBALS['sql_constraints_query']);
                }
            }

            // -----------------------------------------------------------------
            // Phase 4: Adding indexes.
            // View phase 3.

            if (! empty($GLOBALS['sql_indexes'])) {
                $parser = new Parser($GLOBALS['sql_indexes']);

                $GLOBALS['sql_indexes'] = '';
                /**
                 * The ALTER statement that generates the indexes.
                 *
                 * @var AlterStatement $statement
                 */
                foreach ($parser->statements as $statement) {
                    // Changing the altered table to the destination.
                    $statement->table = $destination;

                    // Removing the name of the constraints.
                    foreach ($statement->altered as $altered) {
                        // All constraint names are removed because they must be unique.
                        if (! $altered->options->has('CONSTRAINT')) {
                            continue;
                        }

                        $altered->field = null;
                    }

                    // Building back the query.
                    $sqlIndex = $statement->build() . ';';

                    // Executing it.
                    if ($mode === 'one_table' || $mode === 'db_copy') {
                        $dbi->query($sqlIndex);
                    }

                    $GLOBALS['sql_indexes'] .= $sqlIndex;
                }

                $GLOBALS['sql_query'] .= "\n" . $GLOBALS['sql_indexes'];
                if ($mode === 'one_table' || $mode === 'db_copy') {
                    unset($GLOBALS['sql_indexes']);
                }
            }

            // -----------------------------------------------------------------
            // Phase 5: Adding AUTO_INCREMENT.

            if (! empty($GLOBALS['sql_auto_increments']) && ($mode === 'one_table' || $mode === 'db_copy')) {
                $parser = new Parser($GLOBALS['sql_auto_increments']);

                /**
                 * The ALTER statement that alters the AUTO_INCREMENT value.
                 */
                $statement = $parser->statements[0];
                if ($statement instanceof AlterStatement) {
                    // Changing the altered table to the destination.
                    $statement->table = $destination;

                    // Building back the query.
                    $GLOBALS['sql_auto_increments'] = $statement->build() . ';';

                    // Executing it.
                    $dbi->query($GLOBALS['sql_auto_increments']);
                    $GLOBALS['sql_query'] .= "\n" . $GLOBALS['sql_auto_increments'];
                }

                unset($GLOBALS['sql_auto_increments']);
            }
        } else {
            $GLOBALS['sql_query'] = '';
        }

        $table = new Table($targetTable, $targetDb);
        // Copy the data unless this is a VIEW
        if (($what === 'data' || $what === 'dataonly') && ! $table->isView()) {
            $sqlSetMode = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'";
            $dbi->query($sqlSetMode);
            $GLOBALS['sql_query'] .= "\n\n" . $sqlSetMode . ';';

            $oldTable = new Table($sourceTable, $sourceDb);
            $nonGeneratedCols = $oldTable->getNonGeneratedColumns(true);
            if (count($nonGeneratedCols) > 0) {
                $sqlInsertData = 'INSERT INTO ' . $target . '('
                    . implode(', ', $nonGeneratedCols)
                    . ') SELECT ' . implode(', ', $nonGeneratedCols)
                    . ' FROM ' . $source;

                $dbi->query($sqlInsertData);
                $GLOBALS['sql_query'] .= "\n\n" . $sqlInsertData . ';';
            }
        }

        $relationParameters = $relation->getRelationParameters();

        // Drops old table if the user has requested to move it
        if ($move) {
            // This could avoid some problems with replicated databases, when
            // moving table from replicated one to not replicated one
            $dbi->selectDb($sourceDb);

            $sourceTableObj = new Table($sourceTable, $sourceDb);
            if ($sourceTableObj->isView()) {
                $sqlDropQuery = 'DROP VIEW';
            } else {
                $sqlDropQuery = 'DROP TABLE';
            }

            $sqlDropQuery .= ' ' . $source;
            $dbi->query($sqlDropQuery);

            // Rename table in configuration storage
            $relation->renameTable($sourceDb, $targetDb, $sourceTable, $targetTable);

            $GLOBALS['sql_query'] .= "\n\n" . $sqlDropQuery . ';';

            return true;
        }

        // we are copying
        // Create new entries as duplicates from old PMA DBs
        if ($what === 'dataonly' || isset($maintainRelations)) {
            return true;
        }

        if ($relationParameters->columnCommentsFeature !== null) {
            // Get all comments and MIME-Types for current table
            $commentsCopyRs = $dbi->queryAsControlUser(
                'SELECT column_name, comment'
                . ($relationParameters->browserTransformationFeature !== null
                ? ', mimetype, transformation, transformation_options'
                : '')
                . ' FROM '
                . Util::backquote($relationParameters->columnCommentsFeature->database)
                . '.'
                . Util::backquote($relationParameters->columnCommentsFeature->columnInfo)
                . ' WHERE '
                . ' db_name = \''
                . $dbi->escapeString($sourceDb) . '\''
                . ' AND '
                . ' table_name = \''
                . $dbi->escapeString((string) $sourceTable) . '\''
            );

            // Write every comment as new copied entry. [MIME]
            foreach ($commentsCopyRs as $commentsCopyRow) {
                $newCommentQuery = 'REPLACE INTO '
                    . Util::backquote($relationParameters->columnCommentsFeature->database)
                    . '.' . Util::backquote($relationParameters->columnCommentsFeature->columnInfo)
                    . ' (db_name, table_name, column_name, comment'
                    . ($relationParameters->browserTransformationFeature !== null
                        ? ', mimetype, transformation, transformation_options'
                        : '')
                    . ') VALUES(\'' . $dbi->escapeString($targetDb)
                    . '\',\'' . $dbi->escapeString($targetTable) . '\',\''
                    . $dbi->escapeString($commentsCopyRow['column_name'])
                    . '\',\''
                    . $dbi->escapeString($commentsCopyRow['comment'])
                    . '\''
                    . ($relationParameters->browserTransformationFeature !== null
                        ? ',\'' . $dbi->escapeString($commentsCopyRow['mimetype'])
                        . '\',\'' . $dbi->escapeString($commentsCopyRow['transformation'])
                        . '\',\'' . $dbi->escapeString($commentsCopyRow['transformation_options'])
                        . '\''
                        : '')
                    . ')';
                $dbi->queryAsControlUser($newCommentQuery);
            }

            unset($commentsCopyRs);
        }

        // duplicating the bookmarks must not be done here, but
        // just once per db

        $getFields = ['display_field'];
        $whereFields = [
            'db_name' => $sourceDb,
            'table_name' => $sourceTable,
        ];
        $newFields = [
            'db_name' => $targetDb,
            'table_name' => $targetTable,
        ];
        self::duplicateInfo('displaywork', 'table_info', $getFields, $whereFields, $newFields);

        /**
         * @todo revise this code when we support cross-db relations
         */
        $getFields = [
            'master_field',
            'foreign_table',
            'foreign_field',
        ];
        $whereFields = [
            'master_db' => $sourceDb,
            'master_table' => $sourceTable,
        ];
        $newFields = [
            'master_db' => $targetDb,
            'foreign_db' => $targetDb,
            'master_table' => $targetTable,
        ];
        self::duplicateInfo('relwork', 'relation', $getFields, $whereFields, $newFields);

        $getFields = [
            'foreign_field',
            'master_table',
            'master_field',
        ];
        $whereFields = [
            'foreign_db' => $sourceDb,
            'foreign_table' => $sourceTable,
        ];
        $newFields = [
            'master_db' => $targetDb,
            'foreign_db' => $targetDb,
            'foreign_table' => $targetTable,
        ];
        self::duplicateInfo('relwork', 'relation', $getFields, $whereFields, $newFields);

        return true;
    }

    /**
     * checks if given name is a valid table name,
     * currently if not empty, trailing spaces, '.', '/' and '\'
     *
     * @see  https://dev.mysql.com/doc/refman/5.0/en/legal-names.html
     *
     * @param string $tableName    name to check
     * @param bool   $isBackquoted whether this name is used inside backquotes or not
     *
     * @todo add check for valid chars in filename on current system/os
     */
    public static function isValidName($tableName, $isBackquoted = false): bool
    {
        if ($tableName !== rtrim((string) $tableName)) {
            // trailing spaces not allowed even in backquotes
            return false;
        }

        if (strlen($tableName) === 0) {
            // zero length
            return false;
        }

        if (! $isBackquoted && $tableName !== trim($tableName)) {
            // spaces at the start or in between only allowed inside backquotes
            return false;
        }

        if (! $isBackquoted && preg_match('/^[a-zA-Z0-9_$]+$/', $tableName)) {
            // only allow the above regex in unquoted identifiers
            // see : https://dev.mysql.com/doc/refman/5.7/en/identifiers.html
            return true;
        }

        // If backquoted, all characters should be allowed (except w/ trailing spaces).
        return $isBackquoted;
    }

    /**
     * renames table
     *
     * @param string $newName new table name
     * @param string $newDb   new database name
     */
    public function rename($newName, $newDb = null): bool
    {
        if ($this->dbi->getLowerCaseNames() === '1') {
            $newName = strtolower($newName);
        }

        if ($newDb !== null && $newDb !== $this->getDbName()) {
            // Ensure the target is valid
            if (! $GLOBALS['dblist']->databases->exists($newDb)) {
                $this->errors[] = __('Invalid database:') . ' ' . $newDb;

                return false;
            }
        } else {
            $newDb = $this->getDbName();
        }

        $newTable = new Table($newName, $newDb);

        if ($this->getFullName() === $newTable->getFullName()) {
            return true;
        }

        // Allow whitespaces (not trailing) in $new_name,
        // since we are using $backquoted in getting the fullName of table
        // below to be used in the query
        if (! self::isValidName($newName, true)) {
            $this->errors[] = __('Invalid table name:') . ' '
                . $newTable->getFullName();

            return false;
        }

        // If the table is moved to a different database drop its triggers first
        $triggers = $this->dbi->getTriggers(
            $this->getDbName(),
            $this->getName(),
            ''
        );
        $handleTriggers = $this->getDbName() != $newDb && $triggers;
        if ($handleTriggers) {
            foreach ($triggers as $trigger) {
                $sql = 'DROP TRIGGER IF EXISTS '
                    . Util::backquote($this->getDbName())
                    . '.' . Util::backquote($trigger['name']) . ';';
                $this->dbi->query($sql);
            }
        }

        /*
         * tested also for a view, in MySQL 5.0.92, 5.1.55 and 5.5.13
         */
        $GLOBALS['sql_query'] = '
            RENAME TABLE ' . $this->getFullName(true) . '
                  TO ' . $newTable->getFullName(true) . ';';
        // I don't think a specific error message for views is necessary
        if (! $this->dbi->query($GLOBALS['sql_query'])) {
            // TODO: this is dead code, should it be removed?
            // Restore triggers in the old database
            if ($handleTriggers) {
                $this->dbi->selectDb($this->getDbName());
                foreach ($triggers as $trigger) {
                    $this->dbi->query($trigger['create']);
                }
            }

            $this->errors[] = sprintf(
                __('Failed to rename table %1$s to %2$s!'),
                $this->getFullName(),
                $newTable->getFullName()
            );

            return false;
        }

        $oldName = $this->getName();
        $oldDb = $this->getDbName();
        $this->name = $newName;
        $this->dbName = $newDb;

        // Rename table in configuration storage
        $this->relation->renameTable($oldDb, $newDb, $oldName, $newName);

        $this->messages[] = sprintf(
            __('Table %1$s has been renamed to %2$s.'),
            htmlspecialchars($oldName),
            htmlspecialchars($newName)
        );

        return true;
    }

    /**
     * Get all unique columns
     *
     * returns an array with all columns with unique content, in fact these are
     * all columns being single indexed in PRIMARY or UNIQUE
     *
     * e.g.
     *  - PRIMARY(id) // id
     *  - UNIQUE(name) // name
     *  - PRIMARY(fk_id1, fk_id2) // NONE
     *  - UNIQUE(x,y) // NONE
     *
     * @param bool $backquoted whether to quote name with backticks ``
     * @param bool $fullName   whether to include full name of the table as a prefix
     *
     * @return array
     */
    public function getUniqueColumns($backquoted = true, $fullName = true)
    {
        $sql = QueryGenerator::getTableIndexesSql(
            $this->getDbName(),
            $this->getName(),
            'Non_unique = 0'
        );
        $uniques = $this->dbi->fetchResult(
            $sql,
            [
                'Key_name',
                null,
            ],
            'Column_name'
        );

        $return = [];
        foreach ($uniques as $index) {
            if (count($index) > 1) {
                continue;
            }

            if ($fullName) {
                $possibleColumn = $this->getFullName($backquoted) . '.';
            } else {
                $possibleColumn = '';
            }

            if ($backquoted) {
                $possibleColumn .= Util::backquote($index[0]);
            } else {
                $possibleColumn .= $index[0];
            }

            // a column might have a primary and an unique index on it
            if (in_array($possibleColumn, $return)) {
                continue;
            }

            $return[] = $possibleColumn;
        }

        return $return;
    }

    /**
     * Formats lists of columns
     *
     * returns an array with all columns that make use of an index
     *
     * e.g. index(col1, col2) would return col1, col2
     *
     * @param array $indexed    column data
     * @param bool  $backquoted whether to quote name with backticks ``
     * @param bool  $fullName   whether to include full name of the table as a prefix
     *
     * @return array
     */
    private function formatColumns(array $indexed, $backquoted, $fullName)
    {
        $return = [];
        foreach ($indexed as $column) {
            $return[] = ($fullName ? $this->getFullName($backquoted) . '.' : '')
                . ($backquoted ? Util::backquote($column) : $column);
        }

        return $return;
    }

    /**
     * Get all indexed columns
     *
     * returns an array with all columns that make use of an index
     *
     * e.g. index(col1, col2) would return col1, col2
     *
     * @param bool $backquoted whether to quote name with backticks ``
     * @param bool $fullName   whether to include full name of the table as a prefix
     *
     * @return array
     */
    public function getIndexedColumns($backquoted = true, $fullName = true)
    {
        $sql = QueryGenerator::getTableIndexesSql(
            $this->getDbName(),
            $this->getName(),
            ''
        );
        $indexed = $this->dbi->fetchResult($sql, 'Column_name', 'Column_name');

        return $this->formatColumns($indexed, $backquoted, $fullName);
    }

    /**
     * Get all columns
     *
     * returns an array with all columns
     *
     * @param bool $backquoted whether to quote name with backticks ``
     * @param bool $fullName   whether to include full name of the table as a prefix
     *
     * @return array
     */
    public function getColumns($backquoted = true, $fullName = true)
    {
        $sql = 'SHOW COLUMNS FROM ' . $this->getFullName(true);
        $indexed = $this->dbi->fetchResult($sql, 'Field', 'Field');

        return $this->formatColumns($indexed, $backquoted, $fullName);
    }

    /**
     * Get meta info for fields in table
     *
     * @return FieldMetadata[]
     */
    public function getColumnsMeta(): array
    {
        $moveColumnsSqlQuery = sprintf(
            'SELECT * FROM %s.%s LIMIT 1',
            Util::backquote($this->dbName),
            Util::backquote($this->name)
        );
        $moveColumnsSqlResult = $this->dbi->tryQuery($moveColumnsSqlQuery);
        if ($moveColumnsSqlResult !== false) {
            return $this->dbi->getFieldsMeta($moveColumnsSqlResult);
        }

        // unsure how to reproduce but it was seen on the reporting server
        return [];
    }

    /**
     * Get non-generated columns in table
     *
     * @param bool $backquoted whether to quote name with backticks ``
     *
     * @return array
     */
    public function getNonGeneratedColumns($backquoted = true)
    {
        $columnsMetaQuery = 'SHOW COLUMNS FROM ' . $this->getFullName(true);
        $ret = [];

        $columnsMetaQueryResult = $this->dbi->fetchResult($columnsMetaQuery);

        foreach ($columnsMetaQueryResult as $column) {
            $value = $column['Field'];
            if ($backquoted === true) {
                $value = Util::backquote($value);
            }

            // If contains GENERATED or VIRTUAL and does not contain DEFAULT_GENERATED
            if (
                (
                str_contains($column['Extra'], 'GENERATED')
                || str_contains($column['Extra'], 'VIRTUAL')
                ) && ! str_contains($column['Extra'], 'DEFAULT_GENERATED')
            ) {
                continue;
            }

            $ret[] = $value;
        }

        return $ret;
    }

    /**
     * Return UI preferences for this table from phpMyAdmin database.
     *
     * @return array
     */
    protected function getUiPrefsFromDb(?UiPreferencesFeature $uiPreferencesFeature)
    {
        if ($uiPreferencesFeature === null) {
            return [];
        }

        // Read from phpMyAdmin database
        $sqlQuery = sprintf(
            'SELECT `prefs` FROM %s.%s WHERE `username` = \'%s\' AND `db_name` = \'%s\' AND `table_name` = \'%s\'',
            Util::backquote($uiPreferencesFeature->database),
            Util::backquote($uiPreferencesFeature->tableUiPrefs),
            $this->dbi->escapeString($GLOBALS['cfg']['Server']['user']),
            $this->dbi->escapeString($this->dbName),
            $this->dbi->escapeString($this->name)
        );

        $value = $this->dbi->queryAsControlUser($sqlQuery)->fetchValue();
        if (is_string($value)) {
            return json_decode($value, true);
        }

        return [];
    }

    /**
     * Save this table's UI preferences into phpMyAdmin database.
     *
     * @return true|Message
     */
    protected function saveUiPrefsToDb(UiPreferencesFeature $uiPreferencesFeature)
    {
        $table = Util::backquote($uiPreferencesFeature->database) . '.'
            . Util::backquote($uiPreferencesFeature->tableUiPrefs);

        $secureDbName = $this->dbi->escapeString($this->dbName);

        $username = $GLOBALS['cfg']['Server']['user'];
        $sqlQuery = ' REPLACE INTO ' . $table
            . " (username, db_name, table_name, prefs) VALUES ('"
            . $this->dbi->escapeString($username) . "', '" . $secureDbName
            . "', '" . $this->dbi->escapeString($this->name) . "', '"
            . $this->dbi->escapeString((string) json_encode($this->uiprefs)) . "')";

        $success = $this->dbi->tryQuery($sqlQuery, DatabaseInterface::CONNECT_CONTROL);

        if (! $success) {
            $message = Message::error(
                __('Could not save table UI preferences!')
            );
            $message->addMessage(
                Message::rawError($this->dbi->getError(DatabaseInterface::CONNECT_CONTROL)),
                '<br><br>'
            );

            return $message;
        }

        // Remove some old rows in table_uiprefs if it exceeds the configured
        // maximum rows
        $sqlQuery = 'SELECT COUNT(*) FROM ' . $table;
        $rowsCount = (int) $this->dbi->fetchValue($sqlQuery);
        $maxRows = (int) $GLOBALS['cfg']['Server']['MaxTableUiprefs'];
        if ($rowsCount > $maxRows) {
            $numRowsToDelete = $rowsCount - $maxRows;
            $sqlQuery = ' DELETE FROM ' . $table .
                ' ORDER BY last_update ASC' .
                ' LIMIT ' . $numRowsToDelete;
            $success = $this->dbi->tryQuery($sqlQuery, DatabaseInterface::CONNECT_CONTROL);

            if (! $success) {
                $message = Message::error(
                    sprintf(
                        __(
                            'Failed to cleanup table UI preferences (see $cfg[\'Servers\'][$i][\'MaxTableUiprefs\'] %s)'
                        ),
                        MySQLDocumentation::showDocumentation('config', 'cfg_Servers_MaxTableUiprefs')
                    )
                );
                $message->addMessage(
                    Message::rawError($this->dbi->getError(DatabaseInterface::CONNECT_CONTROL)),
                    '<br><br>'
                );

                return $message;
            }
        }

        return true;
    }

    /**
     * Loads the UI preferences for this table.
     * If pmadb and table_uiprefs is set, it will load the UI preferences from
     * phpMyAdmin database.
     */
    protected function loadUiPrefs(): void
    {
        $uiPreferencesFeature = $this->relation->getRelationParameters()->uiPreferencesFeature;
        $serverId = $GLOBALS['server'];

        // set session variable if it's still undefined
        if (! isset($_SESSION['tmpval']['table_uiprefs'][$serverId][$this->dbName][$this->name])) {
            // check whether we can get from pmadb
            $uiPrefs = $this->getUiPrefsFromDb($uiPreferencesFeature);
            $_SESSION['tmpval']['table_uiprefs'][$serverId][$this->dbName][$this->name] = $uiPrefs;
        }

        $this->uiprefs =& $_SESSION['tmpval']['table_uiprefs'][$serverId][$this->dbName][$this->name];
    }

    /**
     * Get a property from UI preferences.
     * Return false if the property is not found.
     * Available property:
     * - PROP_SORTED_COLUMN
     * - PROP_COLUMN_ORDER
     * - PROP_COLUMN_VISIB
     *
     * @param string $property property
     *
     * @return mixed
     */
    public function getUiProp($property)
    {
        if (empty($this->uiprefs)) {
            $this->loadUiPrefs();
        }

        // do checking based on property
        if ($property == self::PROP_SORTED_COLUMN) {
            if (! isset($this->uiprefs[$property])) {
                return false;
            }

            if (! isset($_POST['discard_remembered_sort'])) {
                // check if the column name exists in this table
                $tmp = explode(' ', $this->uiprefs[$property]);
                $colname = $tmp[0];
                //remove backquoting from colname
                $colname = str_replace('`', '', $colname);
                //get the available column name without backquoting
                $availColumns = $this->getColumns(false);

                foreach ($availColumns as $eachCol) {
                    // check if $each_col ends with $colname
                    if (substr_compare($eachCol, $colname, mb_strlen($eachCol) - mb_strlen($colname)) === 0) {
                        return $this->uiprefs[$property];
                    }
                }
            }

            // remove the property, since it no longer exists in database
            $this->removeUiProp($property);

            return false;
        }

        if ($property == self::PROP_COLUMN_ORDER || $property == self::PROP_COLUMN_VISIB) {
            if ($this->isView() || ! isset($this->uiprefs[$property])) {
                return false;
            }

            // check if the table has not been modified
            if ($this->getStatusInfo('Create_time') == $this->uiprefs['CREATE_TIME']) {
                return array_map('intval', $this->uiprefs[$property]);
            }

            // remove the property, since the table has been modified
            $this->removeUiProp($property);

            return false;
        }

        // default behaviour for other property:
        return $this->uiprefs[$property] ?? false;
    }

    /**
     * Set a property from UI preferences.
     * If pmadb and table_uiprefs is set, it will save the UI preferences to
     * phpMyAdmin database.
     * Available property:
     * - PROP_SORTED_COLUMN
     * - PROP_COLUMN_ORDER
     * - PROP_COLUMN_VISIB
     *
     * @param string $property        Property
     * @param mixed  $value           Value for the property
     * @param string $tableCreateTime Needed for PROP_COLUMN_ORDER and PROP_COLUMN_VISIB
     *
     * @return bool|Message
     */
    public function setUiProp($property, $value, $tableCreateTime = null)
    {
        if (empty($this->uiprefs)) {
            $this->loadUiPrefs();
        }

        // we want to save the create time if the property is PROP_COLUMN_ORDER
        if (! $this->isView() && ($property == self::PROP_COLUMN_ORDER || $property == self::PROP_COLUMN_VISIB)) {
            $currCreateTime = $this->getStatusInfo('CREATE_TIME');
            if (! isset($tableCreateTime) || $tableCreateTime != $currCreateTime) {
                // there is no $table_create_time, or
                // supplied $table_create_time is older than current create time,
                // so don't save
                return Message::error(
                    sprintf(
                        __(
                            'Cannot save UI property "%s". The changes made will ' .
                            'not be persistent after you refresh this page. ' .
                            'Please check if the table structure has been changed.'
                        ),
                        $property
                    )
                );
            }

            $this->uiprefs['CREATE_TIME'] = $currCreateTime;
        }

        // save the value
        $this->uiprefs[$property] = $value;

        // check if pmadb is set
        $uiPreferencesFeature = $this->relation->getRelationParameters()->uiPreferencesFeature;
        if ($uiPreferencesFeature !== null) {
            return $this->saveUiPrefsToDb($uiPreferencesFeature);
        }

        return true;
    }

    /**
     * Remove a property from UI preferences.
     *
     * @param string $property the property
     *
     * @return true|Message
     */
    public function removeUiProp($property)
    {
        if (empty($this->uiprefs)) {
            $this->loadUiPrefs();
        }

        if (isset($this->uiprefs[$property])) {
            unset($this->uiprefs[$property]);

            // check if pmadb is set
            $uiPreferencesFeature = $this->relation->getRelationParameters()->uiPreferencesFeature;
            if ($uiPreferencesFeature !== null) {
                return $this->saveUiPrefsToDb($uiPreferencesFeature);
            }
        }

        return true;
    }

    /**
     * Get all column names which are MySQL reserved words
     *
     * @return array
     */
    public function getReservedColumnNames()
    {
        $columns = $this->getColumns(false);
        $return = [];
        foreach ($columns as $column) {
            $temp = explode('.', $column);
            $columnName = $temp[2];
            if (! Context::isKeyword($columnName, true)) {
                continue;
            }

            $return[] = $columnName;
        }

        return $return;
    }

    /**
     * Function to get the name and type of the columns of a table
     *
     * @return array
     */
    public function getNameAndTypeOfTheColumns()
    {
        $columns = [];
        foreach (
            $this->dbi->getColumnsFull($this->dbName, $this->name) as $row
        ) {
            if (preg_match('@^(set|enum)\((.+)\)$@i', $row['Type'], $tmp)) {
                $tmp[2] = mb_substr(
                    (string) preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]),
                    1
                );
                $columns[$row['Field']] = $tmp[1] . '('
                    . str_replace(',', ', ', $tmp[2]) . ')';
            } else {
                $columns[$row['Field']] = $row['Type'];
            }
        }

        return $columns;
    }

    /**
     * Get index with index name
     *
     * @param string $index Index name
     *
     * @return Index
     */
    public function getIndex($index)
    {
        return Index::singleton($this->dbName, $this->name, $index);
    }

    /**
     * Function to get the sql query for index creation or edit
     *
     * @param Index $index current index
     * @param bool  $error whether error occurred or not
     *
     * @return string
     */
    public function getSqlQueryForIndexCreateOrEdit($index, &$error)
    {
        // $sql_query is the one displayed in the query box
        $sqlQuery = sprintf(
            'ALTER TABLE %s.%s',
            Util::backquote($this->dbName),
            Util::backquote($this->name)
        );

        // Drops the old index
        if (! empty($_POST['old_index'])) {
            $oldIndex = is_array($_POST['old_index']) ? $_POST['old_index']['Key_name'] : $_POST['old_index'];
            if ($oldIndex === 'PRIMARY') {
                $sqlQuery .= ' DROP PRIMARY KEY,';
            } else {
                $sqlQuery .= sprintf(
                    ' DROP INDEX %s,',
                    Util::backquote($oldIndex)
                );
            }
        }

        // Builds the new one
        switch ($index->getChoice()) {
            case 'PRIMARY':
                if ($index->getName() == '') {
                    $index->setName('PRIMARY');
                } elseif ($index->getName() !== 'PRIMARY') {
                    $error = Message::error(
                        __('The name of the primary key must be "PRIMARY"!')
                    );
                }

                $sqlQuery .= ' ADD PRIMARY KEY';
                break;
            case 'FULLTEXT':
            case 'UNIQUE':
            case 'INDEX':
            case 'SPATIAL':
                if ($index->getName() === 'PRIMARY') {
                    $error = Message::error(
                        __('Can\'t rename index to PRIMARY!')
                    );
                }

                $sqlQuery .= sprintf(
                    ' ADD %s ',
                    $index->getChoice()
                );
                if ($index->getName()) {
                    $sqlQuery .= Util::backquote($index->getName());
                }

                break;
        }

        $indexFields = [];
        foreach ($index->getColumns() as $key => $column) {
            $indexFields[$key] = Util::backquote($column->getName());
            if (! $column->getSubPart()) {
                continue;
            }

            $indexFields[$key] .= '(' . $column->getSubPart() . ')';
        }

        if (empty($indexFields)) {
            $error = Message::error(__('No index parts defined!'));
        } else {
            $sqlQuery .= ' (' . implode(', ', $indexFields) . ')';
        }

        $keyBlockSizes = $index->getKeyBlockSize();
        if (! empty($keyBlockSizes)) {
            $sqlQuery .= sprintf(
                ' KEY_BLOCK_SIZE = %s',
                $this->dbi->escapeString((string) $keyBlockSizes)
            );
        }

        // specifying index type is allowed only for primary, unique and index only
        // TokuDB is using Fractal Tree, Using Type is not useless
        // Ref: https://mariadb.com/kb/en/mariadb/storage-engine-index-types/
        $type = $index->getType();
        if (
            $index->getChoice() !== 'SPATIAL'
            && $index->getChoice() !== 'FULLTEXT'
            && in_array($type, Index::getIndexTypes())
            && ! $this->isEngine(['TOKUDB'])
        ) {
            $sqlQuery .= ' USING ' . $type;
        }

        $parser = $index->getParser();
        if ($index->getChoice() === 'FULLTEXT' && ! empty($parser)) {
            $sqlQuery .= ' WITH PARSER ' . $this->dbi->escapeString($parser);
        }

        $comment = $index->getComment();
        if (! empty($comment)) {
            $sqlQuery .= sprintf(
                " COMMENT '%s'",
                $this->dbi->escapeString($comment)
            );
        }

        $sqlQuery .= ';';

        return $sqlQuery;
    }

    /**
     * Function to handle update for display field
     *
     * @param string $displayField display field
     */
    public function updateDisplayField($displayField, DisplayFeature $displayFeature): void
    {
        if ($displayField == '') {
            $updQuery = 'DELETE FROM '
                . Util::backquote($displayFeature->database)
                . '.' . Util::backquote($displayFeature->tableInfo)
                . ' WHERE db_name  = \''
                . $this->dbi->escapeString($this->dbName) . '\''
                . ' AND table_name = \''
                . $this->dbi->escapeString($this->name) . '\'';
        } else {
            $updQuery = 'REPLACE INTO '
                . Util::backquote($displayFeature->database)
                . '.' . Util::backquote($displayFeature->tableInfo)
                . '(db_name, table_name, display_field) VALUES('
                . '\'' . $this->dbi->escapeString($this->dbName) . '\','
                . '\'' . $this->dbi->escapeString($this->name) . '\','
                . '\'' . $this->dbi->escapeString($displayField) . '\')';
        }

        $this->dbi->queryAsControlUser($updQuery);
    }

    /**
     * Function to get update query for updating internal relations
     *
     * @param array      $multiEditColumnsName multi edit column names
     * @param array      $destinationDb        destination tables
     * @param array      $destinationTable     destination tables
     * @param array      $destinationColumn    destination columns
     * @param array|null $existrel             db, table, column
     */
    public function updateInternalRelations(
        array $multiEditColumnsName,
        array $destinationDb,
        array $destinationTable,
        array $destinationColumn,
        RelationFeature $relationFeature,
        $existrel
    ): bool {
        $updated = false;
        foreach ($destinationDb as $masterFieldMd5 => $foreignDb) {
            $updQuery = null;
            // Map the fieldname's md5 back to its real name
            $masterField = $multiEditColumnsName[$masterFieldMd5];
            $foreignTable = $destinationTable[$masterFieldMd5];
            $foreignField = $destinationColumn[$masterFieldMd5];
            if (! empty($foreignDb) && ! empty($foreignTable) && ! empty($foreignField)) {
                if (! isset($existrel[$masterField])) {
                    $updQuery = 'INSERT INTO '
                        . Util::backquote($relationFeature->database)
                        . '.' . Util::backquote($relationFeature->relation)
                        . '(master_db, master_table, master_field, foreign_db,'
                        . ' foreign_table, foreign_field)'
                        . ' values('
                        . '\'' . $this->dbi->escapeString($this->dbName) . '\', '
                        . '\'' . $this->dbi->escapeString($this->name) . '\', '
                        . '\'' . $this->dbi->escapeString($masterField) . '\', '
                        . '\'' . $this->dbi->escapeString($foreignDb) . '\', '
                        . '\'' . $this->dbi->escapeString($foreignTable) . '\','
                        . '\'' . $this->dbi->escapeString($foreignField) . '\')';
                } elseif (
                    $existrel[$masterField]['foreign_db'] != $foreignDb
                    || $existrel[$masterField]['foreign_table'] != $foreignTable
                    || $existrel[$masterField]['foreign_field'] != $foreignField
                ) {
                    $updQuery = 'UPDATE '
                        . Util::backquote($relationFeature->database)
                        . '.' . Util::backquote($relationFeature->relation)
                        . ' SET foreign_db       = \''
                        . $this->dbi->escapeString($foreignDb) . '\', '
                        . ' foreign_table    = \''
                        . $this->dbi->escapeString($foreignTable) . '\', '
                        . ' foreign_field    = \''
                        . $this->dbi->escapeString($foreignField) . '\' '
                        . ' WHERE master_db  = \''
                        . $this->dbi->escapeString($this->dbName) . '\''
                        . ' AND master_table = \''
                        . $this->dbi->escapeString($this->name) . '\''
                        . ' AND master_field = \''
                        . $this->dbi->escapeString($masterField) . '\'';
                }
            } elseif (isset($existrel[$masterField])) {
                $updQuery = 'DELETE FROM '
                    . Util::backquote($relationFeature->database)
                    . '.' . Util::backquote($relationFeature->relation)
                    . ' WHERE master_db  = \''
                    . $this->dbi->escapeString($this->dbName) . '\''
                    . ' AND master_table = \''
                    . $this->dbi->escapeString($this->name) . '\''
                    . ' AND master_field = \''
                    . $this->dbi->escapeString($masterField) . '\'';
            }

            if (! isset($updQuery)) {
                continue;
            }

            $this->dbi->queryAsControlUser($updQuery);
            $updated = true;
        }

        return $updated;
    }

    /**
     * Function to handle foreign key updates
     *
     * @param array  $destinationForeignDb     destination foreign database
     * @param array  $multiEditColumnsName     multi edit column names
     * @param array  $destinationForeignTable  destination foreign table
     * @param array  $destinationForeignColumn destination foreign column
     * @param array  $optionsArray             options array
     * @param string $table                    current table
     * @param array  $existrelForeign          db, table, column
     *
     * @return array
     */
    public function updateForeignKeys(
        array $destinationForeignDb,
        array $multiEditColumnsName,
        array $destinationForeignTable,
        array $destinationForeignColumn,
        array $optionsArray,
        $table,
        array $existrelForeign
    ) {
        $htmlOutput = '';
        $previewSqlData = '';
        $displayQuery = '';
        $seenError = false;

        foreach ($destinationForeignDb as $masterFieldMd5 => $foreignDb) {
            $create = false;
            $drop = false;

            // Map the fieldname's md5 back to its real name
            $masterField = $multiEditColumnsName[$masterFieldMd5];

            $foreignTable = $destinationForeignTable[$masterFieldMd5];
            $foreignField = $destinationForeignColumn[$masterFieldMd5];

            if (isset($existrelForeign[$masterFieldMd5]['ref_db_name'])) {
                $refDbName = $existrelForeign[$masterFieldMd5]['ref_db_name'];
            } else {
                $refDbName = $GLOBALS['db'];
            }

            $emptyFields = false;
            foreach ($masterField as $key => $oneField) {
                if (
                    (! empty($oneField) && empty($foreignField[$key]))
                    || (empty($oneField) && ! empty($foreignField[$key]))
                ) {
                    $emptyFields = true;
                }

                if (! empty($oneField) || ! empty($foreignField[$key])) {
                    continue;
                }

                unset($masterField[$key], $foreignField[$key]);
            }

            if (! empty($foreignDb) && ! empty($foreignTable) && ! $emptyFields) {
                if (isset($existrelForeign[$masterFieldMd5])) {
                    $constraintName = $existrelForeign[$masterFieldMd5]['constraint'];
                    $onDelete = ! empty(
                        $existrelForeign[$masterFieldMd5]['on_delete']
                    )
                        ? $existrelForeign[$masterFieldMd5]['on_delete']
                        : 'RESTRICT';
                    $onUpdate = ! empty(
                        $existrelForeign[$masterFieldMd5]['on_update']
                    )
                        ? $existrelForeign[$masterFieldMd5]['on_update']
                        : 'RESTRICT';

                    if (
                        $refDbName != $foreignDb
                        || $existrelForeign[$masterFieldMd5]['ref_table_name'] != $foreignTable
                        || $existrelForeign[$masterFieldMd5]['ref_index_list'] != $foreignField
                        || $existrelForeign[$masterFieldMd5]['index_list'] != $masterField
                        || $_POST['constraint_name'][$masterFieldMd5] != $constraintName
                        || ($_POST['on_delete'][$masterFieldMd5] != $onDelete)
                        || ($_POST['on_update'][$masterFieldMd5] != $onUpdate)
                    ) {
                        // another foreign key is already defined for this field
                        // or an option has been changed for ON DELETE or ON UPDATE
                        $drop = true;
                        $create = true;
                    }
                } else {
                    // no key defined for this field(s)
                    $create = true;
                }
            } elseif (isset($existrelForeign[$masterFieldMd5])) {
                $drop = true;
            }

            if ($drop) {
                $dropQuery = 'ALTER TABLE ' . Util::backquote($table)
                    . ' DROP FOREIGN KEY '
                    . Util::backquote($existrelForeign[$masterFieldMd5]['constraint'])
                    . ';';

                if (! isset($_POST['preview_sql'])) {
                    $displayQuery .= $dropQuery . "\n";
                    $this->dbi->tryQuery($dropQuery);
                    $tmpErrorDrop = $this->dbi->getError();

                    if ($tmpErrorDrop !== '') {
                        $seenError = true;
                        $htmlOutput .= Generator::mysqlDie($tmpErrorDrop, $dropQuery, false, '', false);
                        continue;
                    }
                } else {
                    $previewSqlData .= $dropQuery . "\n";
                }
            }

            $tmpErrorCreate = false;
            if (! $create) {
                continue;
            }

            $createQuery = $this->getSQLToCreateForeignKey(
                $table,
                $masterField,
                $foreignDb,
                $foreignTable,
                $foreignField,
                $_POST['constraint_name'][$masterFieldMd5],
                $optionsArray[$_POST['on_delete'][$masterFieldMd5]],
                $optionsArray[$_POST['on_update'][$masterFieldMd5]]
            );

            if (! isset($_POST['preview_sql'])) {
                $displayQuery .= $createQuery . "\n";
                $this->dbi->tryQuery($createQuery);
                $tmpErrorCreate = $this->dbi->getError();
                if (! empty($tmpErrorCreate)) {
                    $seenError = true;

                    if (substr($tmpErrorCreate, 1, 4) == '1005') {
                        $message = Message::error(
                            __(
                                'Error creating foreign key on %1$s (check data types)'
                            )
                        );
                        $message->addParam(implode(', ', $masterField));
                        $htmlOutput .= $message->getDisplay();
                    } else {
                        $htmlOutput .= Generator::mysqlDie($tmpErrorCreate, $createQuery, false, '', false);
                    }

                    $htmlOutput .= MySQLDocumentation::show('create-table-foreign-keys') . "\n";
                }
            } else {
                $previewSqlData .= $createQuery . "\n";
            }

            // this is an alteration and the old constraint has been dropped
            // without creation of a new one
            if (! $drop || empty($tmpErrorCreate)) {
                continue;
            }

            // a rollback may be better here
            $sqlQueryRecreate = '# Restoring the dropped constraint...' . "\n";
            $sqlQueryRecreate .= $this->getSQLToCreateForeignKey(
                $table,
                $masterField,
                $existrelForeign[$masterFieldMd5]['ref_db_name'],
                $existrelForeign[$masterFieldMd5]['ref_table_name'],
                $existrelForeign[$masterFieldMd5]['ref_index_list'],
                $existrelForeign[$masterFieldMd5]['constraint'],
                $optionsArray[$existrelForeign[$masterFieldMd5]['on_delete'] ?? ''] ?? null,
                $optionsArray[$existrelForeign[$masterFieldMd5]['on_update'] ?? ''] ?? null
            );
            if (! isset($_POST['preview_sql'])) {
                $displayQuery .= $sqlQueryRecreate . "\n";
                $this->dbi->tryQuery($sqlQueryRecreate);
            } else {
                $previewSqlData .= $sqlQueryRecreate;
            }
        }

        return [
            $htmlOutput,
            $previewSqlData,
            $displayQuery,
            $seenError,
        ];
    }

    /**
     * Returns the SQL query for foreign key constraint creation
     *
     * @param string $table        table name
     * @param array  $field        field names
     * @param string $foreignDb    foreign database name
     * @param string $foreignTable foreign table name
     * @param array  $foreignField foreign field names
     * @param string $name         name of the constraint
     * @param string $onDelete     on delete action
     * @param string $onUpdate     on update action
     *
     * @return string SQL query for foreign key constraint creation
     */
    private function getSQLToCreateForeignKey(
        $table,
        array $field,
        $foreignDb,
        $foreignTable,
        array $foreignField,
        $name = null,
        $onDelete = null,
        $onUpdate = null
    ) {
        $sqlQuery = 'ALTER TABLE ' . Util::backquote($table) . ' ADD ';
        // if user entered a constraint name
        if (! empty($name)) {
            $sqlQuery .= ' CONSTRAINT ' . Util::backquote($name);
        }

        foreach ($field as $key => $oneField) {
            $field[$key] = Util::backquote($oneField);
        }

        foreach ($foreignField as $key => $oneField) {
            $foreignField[$key] = Util::backquote($oneField);
        }

        $sqlQuery .= ' FOREIGN KEY (' . implode(', ', $field) . ') REFERENCES '
            . ($this->dbName != $foreignDb
                ? Util::backquote($foreignDb) . '.' : '')
            . Util::backquote($foreignTable)
            . '(' . implode(', ', $foreignField) . ')';

        if (! empty($onDelete)) {
            $sqlQuery .= ' ON DELETE ' . $onDelete;
        }

        if (! empty($onUpdate)) {
            $sqlQuery .= ' ON UPDATE ' . $onUpdate;
        }

        $sqlQuery .= ';';

        return $sqlQuery;
    }

    /**
     * Returns the generation expression for virtual columns
     *
     * @param string $column name of the column
     *
     * @return array|bool associative array of column name and their expressions
     * or false on failure
     */
    public function getColumnGenerationExpression($column = null)
    {
        if (
            Compatibility::isMySqlOrPerconaDb()
            && $this->dbi->getVersion() > 50705
            && ! $GLOBALS['cfg']['Server']['DisableIS']
        ) {
            $sql = "SELECT
                `COLUMN_NAME` AS `Field`,
                `GENERATION_EXPRESSION` AS `Expression`
                FROM
                `information_schema`.`COLUMNS`
                WHERE
                `TABLE_SCHEMA` = '" . $this->dbi->escapeString($this->dbName) . "'
                AND `TABLE_NAME` = '" . $this->dbi->escapeString($this->name) . "'";
            if ($column != null) {
                $sql .= " AND  `COLUMN_NAME` = '" . $this->dbi->escapeString($column)
                    . "'";
            }

            return $this->dbi->fetchResult($sql, 'Field', 'Expression');
        }

        $createTable = $this->showCreate();
        if (! $createTable) {
            return false;
        }

        $parser = new Parser($createTable);
        $stmt = $parser->statements[0];
        $fields = [];
        if ($stmt instanceof CreateStatement) {
            $fields = TableUtils::getFields($stmt);
        }

        if ($column != null) {
            $expression = isset($fields[$column]['expr']) ?
                substr($fields[$column]['expr'], 1, -1) : '';

            return [$column => $expression];
        }

        $ret = [];
        foreach ($fields as $field => $options) {
            if (! isset($options['expr'])) {
                continue;
            }

            $ret[$field] = substr($options['expr'], 1, -1);
        }

        return $ret;
    }

    /**
     * Returns the CREATE statement for this table
     *
     * @return mixed
     */
    public function showCreate()
    {
        return $this->dbi->fetchValue(
            'SHOW CREATE TABLE ' . Util::backquote($this->dbName) . '.'
            . Util::backquote($this->name),
            1
        );
    }

    /**
     * Returns the real row count for a table
     */
    public function getRealRowCountTable(): ?int
    {
        // SQL query to get row count for a table.
        $result = $this->dbi->fetchSingleRow(
            sprintf(
                'SELECT COUNT(*) AS %s FROM %s.%s',
                Util::backquote('row_count'),
                Util::backquote($this->dbName),
                Util::backquote($this->name)
            )
        );

        if (! is_array($result)) {
            return null;
        }

        return (int) $result['row_count'];
    }

    /**
     * Get columns with indexes
     *
     * @param int $types types bitmask
     *
     * @return array an array of columns
     */
    public function getColumnsWithIndex($types)
    {
        $columnsWithIndex = [];
        foreach (
            Index::getFromTableByChoice($this->name, $this->dbName, $types) as $index
        ) {
            $columns = $index->getColumns();
            $columnsWithIndex = array_merge($columnsWithIndex, array_keys($columns));
        }

        return $columnsWithIndex;
    }
}
