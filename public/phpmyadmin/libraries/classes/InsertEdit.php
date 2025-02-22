<?php
/**
 * set of functions with the insert/edit features in pma
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Utils\Gis;

use function __;
use function array_fill;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_values;
use function bin2hex;
use function class_exists;
use function count;
use function current;
use function date;
use function explode;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function is_file;
use function is_string;
use function max;
use function mb_stripos;
use function mb_strlen;
use function mb_strstr;
use function md5;
use function method_exists;
use function min;
use function password_hash;
use function preg_match;
use function preg_replace;
use function str_contains;
use function str_replace;
use function stripcslashes;
use function stripslashes;
use function strlen;
use function substr;
use function time;
use function trim;

use const ENT_COMPAT;
use const PASSWORD_DEFAULT;

/**
 * PhpMyAdmin\InsertEdit class
 */
class InsertEdit
{
    /**
     * DatabaseInterface instance
     *
     * @var DatabaseInterface
     */
    private $dbi;

    /** @var Relation */
    private $relation;

    /** @var Transformations */
    private $transformations;

    /** @var FileListing */
    private $fileListing;

    /** @var Template */
    public $template;

    /**
     * @param DatabaseInterface $dbi DatabaseInterface instance
     */
    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
        $this->relation = new Relation($this->dbi);
        $this->transformations = new Transformations();
        $this->fileListing = new FileListing();
        $this->template = new Template();
    }

    /**
     * Retrieve form parameters for insert/edit form
     *
     * @param string     $db               name of the database
     * @param string     $table            name of the table
     * @param array|null $whereClauses     where clauses
     * @param array      $whereClauseArray array of where clauses
     * @param string     $errorUrl         error url
     *
     * @return array array of insert/edit form parameters
     */
    public function getFormParametersForInsertForm(
        $db,
        $table,
        ?array $whereClauses,
        array $whereClauseArray,
        $errorUrl
    ): array {
        $formParams = [
            'db' => $db,
            'table' => $table,
            'goto' => $GLOBALS['goto'],
            'err_url' => $errorUrl,
            'sql_query' => $_POST['sql_query'] ?? '',
        ];

        if ($formParams['sql_query'] === '' && isset($_GET['sql_query'], $_GET['sql_signature'])) {
            if (Core::checkSqlQuerySignature($_GET['sql_query'], $_GET['sql_signature'])) {
                $formParams['sql_query'] = $_GET['sql_query'];
            }
        }

        if (isset($whereClauses)) {
            foreach ($whereClauseArray as $keyId => $whereClause) {
                $formParams['where_clause[' . $keyId . ']'] = trim($whereClause);
            }
        }

        if (isset($_POST['clause_is_unique'])) {
            $formParams['clause_is_unique'] = $_POST['clause_is_unique'];
        } elseif (isset($_GET['clause_is_unique'])) {
            $formParams['clause_is_unique'] = $_GET['clause_is_unique'];
        }

        return $formParams;
    }

    /**
     * Creates array of where clauses
     *
     * @param array|string|null $whereClause where clause
     *
     * @return array whereClauseArray array of where clauses
     */
    private function getWhereClauseArray($whereClause): array
    {
        if ($whereClause === null) {
            return [];
        }

        if (is_array($whereClause)) {
            return $whereClause;
        }

        return [0 => $whereClause];
    }

    /**
     * Analysing where clauses array
     *
     * @param array  $whereClauseArray array of where clauses
     * @param string $table            name of the table
     * @param string $db               name of the database
     *
     * @return array $where_clauses, $result, $rows, $found_unique_key
     */
    private function analyzeWhereClauses(
        array $whereClauseArray,
        $table,
        $db
    ): array {
        $rows = [];
        $result = [];
        $whereClauses = [];
        $foundUniqueKey = false;
        foreach ($whereClauseArray as $keyId => $whereClause) {
            $localQuery = 'SELECT * FROM '
                . Util::backquote($db) . '.'
                . Util::backquote($table)
                . ' WHERE ' . $whereClause . ';';
            $result[$keyId] = $this->dbi->query($localQuery);
            $rows[$keyId] = $result[$keyId]->fetchAssoc();

            $whereClauses[$keyId] = str_replace('\\', '\\\\', $whereClause);
            $hasUniqueCondition = $this->showEmptyResultMessageOrSetUniqueCondition(
                $rows,
                $keyId,
                $whereClauseArray,
                $localQuery,
                $result
            );
            if (! $hasUniqueCondition) {
                continue;
            }

            $foundUniqueKey = true;
        }

        return [
            $whereClauses,
            $result,
            $rows,
            $foundUniqueKey,
        ];
    }

    /**
     * Show message for empty result or set the unique_condition
     *
     * @param array             $rows             MySQL returned rows
     * @param string            $keyId            ID in current key
     * @param array             $whereClauseArray array of where clauses
     * @param string            $localQuery       query performed
     * @param ResultInterface[] $result           MySQL result handle
     */
    private function showEmptyResultMessageOrSetUniqueCondition(
        array $rows,
        $keyId,
        array $whereClauseArray,
        $localQuery,
        array $result
    ): bool {
        // No row returned
        if (! $rows[$keyId]) {
            unset($rows[$keyId], $whereClauseArray[$keyId]);
            ResponseRenderer::getInstance()->addHTML(
                Generator::getMessage(
                    __('MySQL returned an empty result set (i.e. zero rows).'),
                    $localQuery
                )
            );
            /**
             * @todo not sure what should be done at this point, but we must not
             * exit if we want the message to be displayed
             */

            return false;
        }

        $meta = $this->dbi->getFieldsMeta($result[$keyId]);

        [$uniqueCondition] = Util::getUniqueCondition(
            count($meta),
            $meta,
            $rows[$keyId],
            true
        );

        return (bool) $uniqueCondition;
    }

    /**
     * No primary key given, just load first row
     *
     * @param string $table name of the table
     * @param string $db    name of the database
     *
     * @return array containing $result and $rows arrays
     */
    private function loadFirstRow($table, $db)
    {
        $result = $this->dbi->query(
            'SELECT * FROM ' . Util::backquote($db)
            . '.' . Util::backquote($table) . ' LIMIT 1;'
        );
        // Can be a string on some old configuration storage settings
        $rows = array_fill(0, (int) $GLOBALS['cfg']['InsertRows'], false);

        return [
            $result,
            $rows,
        ];
    }

    /**
     * Add some url parameters
     *
     * @param array $urlParams        containing $db and $table as url parameters
     * @param array $whereClauseArray where clauses array
     *
     * @return array Add some url parameters to $url_params array and return it
     */
    public function urlParamsInEditMode(
        array $urlParams,
        array $whereClauseArray
    ): array {
        foreach ($whereClauseArray as $whereClause) {
            $urlParams['where_clause'] = trim($whereClause);
        }

        if (! empty($_POST['sql_query'])) {
            $urlParams['sql_query'] = $_POST['sql_query'];
        }

        return $urlParams;
    }

    /**
     * Show type information or function selectors in Insert/Edit
     *
     * @param string $which     function|type
     * @param array  $urlParams containing url parameters
     * @param bool   $isShow    whether to show the element in $which
     *
     * @return string an HTML snippet
     */
    public function showTypeOrFunction($which, array $urlParams, $isShow): string
    {
        $params = [];

        switch ($which) {
            case 'function':
                $params['ShowFunctionFields'] = ($isShow ? 0 : 1);
                $params['ShowFieldTypesInDataEditView'] = $GLOBALS['cfg']['ShowFieldTypesInDataEditView'];
                break;
            case 'type':
                $params['ShowFieldTypesInDataEditView'] = ($isShow ? 0 : 1);
                $params['ShowFunctionFields'] = $GLOBALS['cfg']['ShowFunctionFields'];
                break;
        }

        $params['goto'] = Url::getFromRoute('/sql');
        $thisUrlParams = array_merge($urlParams, $params);

        if (! $isShow) {
            return ' : <a href="' . Url::getFromRoute('/table/change') . '" data-post="'
                . Url::getCommon($thisUrlParams, '', false) . '">'
                . $this->showTypeOrFunctionLabel($which)
                . '</a>';
        }

        return '<th><a href="' . Url::getFromRoute('/table/change') . '" data-post="'
            . Url::getCommon($thisUrlParams, '', false)
            . '" title="' . __('Hide') . '">'
            . $this->showTypeOrFunctionLabel($which)
            . '</a></th>';
    }

    /**
     * Show type information or function selectors labels in Insert/Edit
     *
     * @param string $which function|type
     *
     * @return string an HTML snippet
     */
    private function showTypeOrFunctionLabel($which): string
    {
        switch ($which) {
            case 'function':
                return __('Function');

            case 'type':
                return __('Type');
        }

        return '';
    }

    /**
     * Analyze the table column array
     *
     * @param array $column        description of column in given table
     * @param array $commentsMap   comments for every column that has a comment
     * @param bool  $timestampSeen whether a timestamp has been seen
     *
     * @return array                   description of column in given table
     */
    private function analyzeTableColumnsArray(
        array $column,
        array $commentsMap,
        $timestampSeen
    ) {
        $column['Field_md5'] = md5($column['Field']);
        // True_Type contains only the type (stops at first bracket)
        $column['True_Type'] = preg_replace('@(\(.*)|(\s/.*)@s', '', $column['Type']);
        $column['len'] = preg_match('@float|double@', $column['Type']) ? 100 : -1;
        $column['Field_title'] = $this->getColumnTitle($column, $commentsMap);
        $column['is_binary'] = $this->isColumn(
            $column,
            [
                'binary',
                'varbinary',
            ]
        );
        $column['is_blob'] = $this->isColumn(
            $column,
            [
                'blob',
                'tinyblob',
                'mediumblob',
                'longblob',
            ]
        );
        $column['is_char'] = $this->isColumn(
            $column,
            [
                'char',
                'varchar',
            ]
        );

        [
            $column['pma_type'],
            $column['wrap'],
            $column['first_timestamp'],
        ] = $this->getEnumSetAndTimestampColumns($column, $timestampSeen);

        return $column;
    }

    /**
     * Retrieve the column title
     *
     * @param array $column      description of column in given table
     * @param array $commentsMap comments for every column that has a comment
     *
     * @return string              column title
     */
    private function getColumnTitle(array $column, array $commentsMap): string
    {
        if (isset($commentsMap[$column['Field']])) {
            return '<span style="border-bottom: 1px dashed black;" title="'
                . htmlspecialchars($commentsMap[$column['Field']]) . '">'
                . htmlspecialchars($column['Field']) . '</span>';
        }

        return htmlspecialchars($column['Field']);
    }

    /**
     * check whether the column is of a certain type
     * the goal is to ensure that types such as "enum('one','two','binary',..)"
     * or "enum('one','two','varbinary',..)" are not categorized as binary
     *
     * @param array    $column description of column in given table
     * @param string[] $types  the types to verify
     */
    public function isColumn(array $column, array $types): bool
    {
        foreach ($types as $oneType) {
            if (mb_stripos($column['Type'], $oneType) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve set, enum, timestamp table columns
     *
     * @param array $column        description of column in given table
     * @param bool  $timestampSeen whether a timestamp has been seen
     *
     * @return array $column['pma_type'], $column['wrap'], $column['first_timestamp']
     * @psalm-return array{0: mixed, 1: string, 2: bool}
     */
    private function getEnumSetAndTimestampColumns(array $column, $timestampSeen)
    {
        switch ($column['True_Type']) {
            case 'set':
                return [
                    'set',
                    '',
                    false,
                ];

            case 'enum':
                return [
                    'enum',
                    '',
                    false,
                ];

            case 'timestamp':
                return [
                    $column['Type'],
                    ' text-nowrap',
                    ! $timestampSeen, // can only occur once per table
                ];

            default:
                return [
                    $column['Type'],
                    ' text-nowrap',
                    false,
                ];
        }
    }

    /**
     * Retrieve the nullify code for the null column
     *
     * @param array $column      description of column in given table
     * @param array $foreigners  keys into foreign fields
     * @param array $foreignData data about the foreign keys
     */
    private function getNullifyCodeForNullColumn(
        array $column,
        array $foreigners,
        array $foreignData
    ): string {
        $foreigner = $this->relation->searchColumnInForeigners($foreigners, $column['Field']);
        if (mb_strstr($column['True_Type'], 'enum')) {
            if (mb_strlen((string) $column['Type']) > 20) {
                $nullifyCode = '1';
            } else {
                $nullifyCode = '2';
            }
        } elseif (mb_strstr($column['True_Type'], 'set')) {
            $nullifyCode = '3';
        } elseif ($foreigner && $foreignData['foreign_link'] == false) {
            // foreign key in a drop-down
            $nullifyCode = '4';
        } elseif ($foreigner && $foreignData['foreign_link'] == true) {
            // foreign key with a browsing icon
            $nullifyCode = '6';
        } else {
            $nullifyCode = '5';
        }

        return $nullifyCode;
    }

    /**
     * Get HTML textarea for insert form
     *
     * @param array  $column              column information
     * @param string $backupField         hidden input field
     * @param string $columnNameAppendix  the name attribute
     * @param string $onChangeClause      onchange clause for fields
     * @param int    $tabindex            tab index
     * @param int    $tabindexForValue    offset for the values tabindex
     * @param int    $idindex             id index
     * @param string $textDir             text direction
     * @param string $specialCharsEncoded replaced char if the string starts
     *                                      with a \r\n pair (0x0d0a) add an extra \n
     * @param string $dataType            the html5 data-* attribute type
     * @param bool   $readOnly            is column read only or not
     *
     * @return string                       an html snippet
     */
    private function getTextarea(
        array $column,
        $backupField,
        $columnNameAppendix,
        $onChangeClause,
        $tabindex,
        $tabindexForValue,
        $idindex,
        $textDir,
        $specialCharsEncoded,
        $dataType,
        $readOnly
    ): string {
        $theClass = '';
        $textAreaRows = $GLOBALS['cfg']['TextareaRows'];
        $textareaCols = $GLOBALS['cfg']['TextareaCols'];

        if ($column['is_char']) {
            /**
             * @todo clarify the meaning of the "textfield" class and explain
             *       why character columns have the "char" class instead
             */
            $theClass = 'charField';
            $textAreaRows = $GLOBALS['cfg']['CharTextareaRows'];
            $textareaCols = $GLOBALS['cfg']['CharTextareaCols'];
            $extractedColumnspec = Util::extractColumnSpec($column['Type']);
            $maxlength = $extractedColumnspec['spec_in_brackets'];
        } elseif ($GLOBALS['cfg']['LongtextDoubleTextarea'] && mb_strstr($column['pma_type'], 'longtext')) {
            $textAreaRows = $GLOBALS['cfg']['TextareaRows'] * 2;
            $textareaCols = $GLOBALS['cfg']['TextareaCols'] * 2;
        }

        return $backupField . "\n"
            . '<textarea name="fields' . $columnNameAppendix . '"'
            . ' class="' . $theClass . '"'
            . ($readOnly ? ' readonly="readonly"' : '')
            . (isset($maxlength) ? ' data-maxlength="' . $maxlength . '"' : '')
            . ' rows="' . $textAreaRows . '"'
            . ' cols="' . $textareaCols . '"'
            . ' dir="' . $textDir . '"'
            . ' id="field_' . $idindex . '_3"'
            . ($onChangeClause ? ' ' . $onChangeClause : '')
            . ' tabindex="' . ($tabindex + $tabindexForValue) . '"'
            . ' data-type="' . $dataType . '">'
            . $specialCharsEncoded
            . '</textarea>';
    }

    /**
     * Get column values
     *
     * @param string[] $enum_set_values
     *
     * @return array column values as an associative array
     * @psalm-return list<array{html: string, plain: string}>
     */
    private function getColumnEnumValues(array $enum_set_values): array
    {
        $values = [];
        foreach ($enum_set_values as $val) {
            $values[] = [
                'plain' => $val,
                'html' => htmlspecialchars($val),
            ];
        }

        return $values;
    }

    /**
     * Retrieve column 'set' value and select size
     *
     * @param array    $column          description of column in given table
     * @param string[] $enum_set_values
     *
     * @return array $column['values'], $column['select_size']
     */
    private function getColumnSetValueAndSelectSize(
        array $column,
        array $enum_set_values
    ): array {
        if (! isset($column['values'])) {
            $column['values'] = [];
            foreach ($enum_set_values as $val) {
                $column['values'][] = [
                    'plain' => $val,
                    'html' => htmlspecialchars($val),
                ];
            }

            $column['select_size'] = min(4, count($column['values']));
        }

        return [
            $column['values'],
            $column['select_size'],
        ];
    }

    /**
     * Get HTML input type
     *
     * @param array  $column             description of column in given table
     * @param string $columnNameAppendix the name attribute
     * @param string $specialChars       special characters
     * @param int    $fieldsize          html field size
     * @param string $onChangeClause     onchange clause for fields
     * @param int    $tabindex           tab index
     * @param int    $tabindexForValue   offset for the values tabindex
     * @param int    $idindex            id index
     * @param string $dataType           the html5 data-* attribute type
     * @param bool   $readOnly           is column read only or not
     *
     * @return string                       an html snippet
     */
    private function getHtmlInput(
        array $column,
        $columnNameAppendix,
        $specialChars,
        $fieldsize,
        $onChangeClause,
        $tabindex,
        $tabindexForValue,
        $idindex,
        $dataType,
        $readOnly
    ): string {
        $theClass = 'textfield';
        // verify True_Type which does not contain the parentheses and length
        if (! $readOnly) {
            if ($column['True_Type'] === 'date') {
                $theClass .= ' datefield';
            } elseif ($column['True_Type'] === 'time') {
                $theClass .= ' timefield';
            } elseif ($column['True_Type'] === 'datetime' || $column['True_Type'] === 'timestamp') {
                $theClass .= ' datetimefield';
            }
        }

        $inputMinMax = '';
        if (in_array($column['True_Type'], $this->dbi->types->getIntegerTypes())) {
            $extractedColumnspec = Util::extractColumnSpec($column['Type']);
            $isUnsigned = $extractedColumnspec['unsigned'];
            $minMaxValues = $this->dbi->types->getIntegerRange($column['True_Type'], ! $isUnsigned);
            $inputMinMax = 'min="' . $minMaxValues[0] . '" '
                . 'max="' . $minMaxValues[1] . '"';
            $dataType = 'INT';
        }

        // do not use the 'date' or 'time' types here; they have no effect on some
        // browsers and create side effects (see bug #4218)
        return '<input type="text"'
            . ' name="fields' . $columnNameAppendix . '"'
            . ' value="' . $specialChars . '" size="' . $fieldsize . '"'
            . (isset($column['is_char']) && $column['is_char']
                ? ' data-maxlength="' . $fieldsize . '"'
                : '')
            . ($readOnly ? ' readonly="readonly"' : '')
            . ($inputMinMax ? ' ' . $inputMinMax : '')
            . ' data-type="' . $dataType . '"'
            . ' class="' . $theClass . '" ' . $onChangeClause
            . ' tabindex="' . ($tabindex + $tabindexForValue) . '"'
            . ' id="field_' . $idindex . '_3">';
    }

    /**
     * Get HTML select option for upload
     *
     * @param string $vkey         [multi_edit]['row_id']
     * @param string $fieldHashMd5 array index as an MD5 to avoid having special characters
     *
     * @return string an HTML snippet
     */
    private function getSelectOptionForUpload(string $vkey, string $fieldHashMd5): string
    {
        $files = $this->fileListing->getFileSelectOptions(
            Util::userDir((string) ($GLOBALS['cfg']['UploadDir'] ?? ''))
        );

        if ($files === false) {
            return '<span style="color:red">' . __('Error') . '</span><br>' . "\n"
                . __('The directory you set for upload work cannot be reached.') . "\n";
        }

        if ($files === '') {
            return '';
        }

        return "<br>\n"
            . '<i>' . __('Or') . '</i> '
            . __('web server upload directory:') . '<br>' . "\n"
            . '<select size="1" name="fields_uploadlocal'
            . $vkey . '[' . $fieldHashMd5 . ']">' . "\n"
            . '<option value="" selected="selected"></option>' . "\n"
            . $files
            . '</select>' . "\n";
    }

    /**
     * Retrieve the maximum upload file size
     *
     * @param string $pma_type           column type
     * @param int    $biggestMaxFileSize biggest max file size for uploading
     *
     * @return array an html snippet and $biggest_max_file_size
     * @psalm-return array{non-empty-string, int}
     */
    private function getMaxUploadSize(string $pma_type, $biggestMaxFileSize): array
    {
        // find maximum upload size, based on field type
        /**
         * @todo with functions this is not so easy, as you can basically
         * process any data with function like MD5
         */
        $maxFieldSizes = [
            'tinyblob' => 256,
            'blob' => 65536,
            'mediumblob' => 16777216,
            'longblob' => 4294967296,// yeah, really
        ];

        $thisFieldMaxSize = (int) $GLOBALS['config']->get('max_upload_size'); // from PHP max
        if ($thisFieldMaxSize > $maxFieldSizes[$pma_type]) {
            $thisFieldMaxSize = $maxFieldSizes[$pma_type];
        }

        $htmlOutput = Util::getFormattedMaximumUploadSize($thisFieldMaxSize) . "\n";
        // do not generate here the MAX_FILE_SIZE, because we should
        // put only one in the form to accommodate the biggest field
        if ($thisFieldMaxSize > $biggestMaxFileSize) {
            $biggestMaxFileSize = $thisFieldMaxSize;
        }

        return [
            $htmlOutput,
            $biggestMaxFileSize,
        ];
    }

    /**
     * Get HTML for the Value column of other datatypes
     * (here, "column" is used in the sense of HTML column in HTML table)
     *
     * @param array  $column              description of column in given table
     * @param string $defaultCharEditing  default char editing mode which is stored
     *                                       in the config.inc.php script
     * @param string $backupField         hidden input field
     * @param string $columnNameAppendix  the name attribute
     * @param string $onChangeClause      onchange clause for fields
     * @param int    $tabindex            tab index
     * @param string $specialChars        special characters
     * @param int    $tabindexForValue    offset for the values tabindex
     * @param int    $idindex             id index
     * @param string $textDir             text direction
     * @param string $specialCharsEncoded replaced char if the string starts
     *                                      with a \r\n pair (0x0d0a) add an extra \n
     * @param string $data                data to edit
     * @param array  $extractedColumnspec associative array containing type,
     *                                      spec_in_brackets and possibly
     *                                      enum_set_values (another array)
     * @param bool   $readOnly            is column read only or not
     *
     * @return string an html snippet
     */
    private function getValueColumnForOtherDatatypes(
        array $column,
        $defaultCharEditing,
        $backupField,
        $columnNameAppendix,
        $onChangeClause,
        $tabindex,
        $specialChars,
        $tabindexForValue,
        $idindex,
        $textDir,
        $specialCharsEncoded,
        $data,
        array $extractedColumnspec,
        $readOnly
    ): string {
        // HTML5 data-* attribute data-type
        $dataType = $this->dbi->types->getTypeClass($column['True_Type']);
        $fieldsize = $this->getColumnSize($column, $extractedColumnspec['spec_in_brackets']);
        $htmlOutput = $backupField . "\n";
        if ($column['is_char'] && ($GLOBALS['cfg']['CharEditing'] === 'textarea' || str_contains($data, "\n"))) {
            $htmlOutput .= "\n";
            $GLOBALS['cfg']['CharEditing'] = $defaultCharEditing;
            $htmlOutput .= $this->getTextarea(
                $column,
                $backupField,
                $columnNameAppendix,
                $onChangeClause,
                $tabindex,
                $tabindexForValue,
                $idindex,
                $textDir,
                $specialCharsEncoded,
                $dataType,
                $readOnly
            );
        } else {
            $htmlOutput .= $this->getHtmlInput(
                $column,
                $columnNameAppendix,
                $specialChars,
                $fieldsize,
                $onChangeClause,
                $tabindex,
                $tabindexForValue,
                $idindex,
                $dataType,
                $readOnly
            );

            if (
                preg_match('/(VIRTUAL|PERSISTENT|GENERATED)/', $column['Extra'])
                && ! str_contains($column['Extra'], 'DEFAULT_GENERATED')
            ) {
                $htmlOutput .= '<input type="hidden" name="virtual'
                    . $columnNameAppendix . '" value="1">';
            }

            if ($column['Extra'] === 'auto_increment') {
                $htmlOutput .= '<input type="hidden" name="auto_increment'
                    . $columnNameAppendix . '" value="1">';
            }

            if (substr($column['pma_type'], 0, 9) === 'timestamp') {
                $htmlOutput .= '<input type="hidden" name="fields_type'
                    . $columnNameAppendix . '" value="timestamp">';
            }

            if (substr($column['pma_type'], 0, 4) === 'date') {
                $type = substr($column['pma_type'], 0, 8) === 'datetime' ? 'datetime' : 'date';
                $htmlOutput .= '<input type="hidden" name="fields_type'
                    . $columnNameAppendix . '" value="' . $type . '">';
            }

            if (in_array($column['True_Type'], ['bit', 'uuid'], true)) {
                $htmlOutput .= '<input type="hidden" name="fields_type'
                    . $columnNameAppendix . '" value="' . $column['True_Type'] . '">';
            }
        }

        return $htmlOutput;
    }

    /**
     * Get the field size
     *
     * @param array  $column         description of column in given table
     * @param string $specInBrackets text in brackets inside column definition
     *
     * @return int field size
     */
    private function getColumnSize(array $column, string $specInBrackets): int
    {
        if ($column['is_char']) {
            $fieldsize = (int) $specInBrackets;
            if ($fieldsize > $GLOBALS['cfg']['MaxSizeForInputField']) {
                /**
                 * This case happens for CHAR or VARCHAR columns which have
                 * a size larger than the maximum size for input field.
                 */
                $GLOBALS['cfg']['CharEditing'] = 'textarea';
            }
        } else {
            /**
             * This case happens for example for INT or DATE columns;
             * in these situations, the value returned in $column['len']
             * seems appropriate.
             */
            $fieldsize = $column['len'];
        }

        return min(
            max($fieldsize, $GLOBALS['cfg']['MinSizeForInputField']),
            $GLOBALS['cfg']['MaxSizeForInputField']
        );
    }

    /**
     * get html for continue insertion form
     *
     * @param string $table            name of the table
     * @param string $db               name of the database
     * @param array  $whereClauseArray array of where clauses
     * @param string $errorUrl         error url
     *
     * @return string                   an html snippet
     */
    public function getContinueInsertionForm(
        $table,
        $db,
        array $whereClauseArray,
        $errorUrl
    ): string {
        return $this->template->render('table/insert/continue_insertion_form', [
            'db' => $db,
            'table' => $table,
            'where_clause_array' => $whereClauseArray,
            'err_url' => $errorUrl,
            'goto' => $GLOBALS['goto'],
            'sql_query' => $_POST['sql_query'] ?? null,
            'has_where_clause' => isset($_POST['where_clause']),
            'insert_rows_default' => $GLOBALS['cfg']['InsertRows'],
        ]);
    }

    /**
     * @param string[]|string|null $whereClause
     *
     * @psalm-pure
     */
    public static function isWhereClauseNumeric($whereClause): bool
    {
        if ($whereClause === null) {
            return false;
        }

        if (! is_array($whereClause)) {
            $whereClause = [$whereClause];
        }

        // If we have just numeric primary key, we can also edit next
        // we are looking for `table_name`.`field_name` = numeric_value
        foreach ($whereClause as $clause) {
            // preg_match() returns 1 if there is a match
            $isNumeric = preg_match('@^[\s]*`[^`]*`[\.]`[^`]*` = [0-9]+@', $clause) === 1;
            if ($isNumeric) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get table head and table foot for insert row table
     *
     * @param array $urlParams url parameters
     *
     * @return string           an html snippet
     */
    private function getHeadAndFootOfInsertRowTable(array $urlParams): string
    {
        $type = '';
        $function = '';

        if ($GLOBALS['cfg']['ShowFieldTypesInDataEditView']) {
            $type = $this->showTypeOrFunction('type', $urlParams, true);
        }

        if ($GLOBALS['cfg']['ShowFunctionFields']) {
            $function = $this->showTypeOrFunction('function', $urlParams, true);
        }

        $template = new Template();

        return $template->render('table/insert/get_head_and_foot_of_insert_row_table', [
            'type' => $type,
            'function' => $function,
        ]);
    }

    /**
     * Prepares the field value and retrieve special chars, backup field and data array
     *
     * @param array  $currentRow          a row of the table
     * @param array  $column              description of column in given table
     * @param array  $extractedColumnspec associative array containing type,
     *                                      spec_in_brackets and possibly
     *                                      enum_set_values (another array)
     * @param array  $gisDataTypes        list of GIS data types
     * @param string $columnNameAppendix  string to append to column name in input
     * @param bool   $asIs                use the data as is, used in repopulating
     *
     * @return array $real_null_value, $data, $special_chars, $backup_field,
     *               $special_chars_encoded
     */
    private function getSpecialCharsAndBackupFieldForExistingRow(
        array $currentRow,
        array $column,
        array $extractedColumnspec,
        array $gisDataTypes,
        $columnNameAppendix,
        $asIs
    ) {
        $specialCharsEncoded = '';
        $data = null;
        $realNullValue = false;
        // (we are editing)
        if (! isset($currentRow[$column['Field']])) {
            $realNullValue = true;
            $currentRow[$column['Field']] = '';
            $specialChars = '';
            $data = $currentRow[$column['Field']];
        } elseif ($column['True_Type'] === 'bit') {
            $specialChars = $asIs
                ? $currentRow[$column['Field']]
                : Util::printableBitValue(
                    (int) $currentRow[$column['Field']],
                    (int) $extractedColumnspec['spec_in_brackets']
                );
        } elseif (
            (substr($column['True_Type'], 0, 9) === 'timestamp'
                || $column['True_Type'] === 'datetime'
                || $column['True_Type'] === 'time')
            && (str_contains($currentRow[$column['Field']], '.'))
        ) {
            $currentRow[$column['Field']] = $asIs
                ? $currentRow[$column['Field']]
                : Util::addMicroseconds($currentRow[$column['Field']]);
            $specialChars = htmlspecialchars($currentRow[$column['Field']], ENT_COMPAT);
        } elseif (in_array($column['True_Type'], $gisDataTypes)) {
            // Convert gis data to Well Know Text format
            $currentRow[$column['Field']] = $asIs
                ? $currentRow[$column['Field']]
                : Gis::convertToWellKnownText($currentRow[$column['Field']], true);
            $specialChars = htmlspecialchars($currentRow[$column['Field']], ENT_COMPAT);
        } else {
            // special binary "characters"
            if ($column['is_binary'] || ($column['is_blob'] && $GLOBALS['cfg']['ProtectBinary'] !== 'all')) {
                $currentRow[$column['Field']] = $asIs
                    ? $currentRow[$column['Field']]
                    : bin2hex($currentRow[$column['Field']]);
            }

            $specialChars = htmlspecialchars($currentRow[$column['Field']], ENT_COMPAT);

            //We need to duplicate the first \n or otherwise we will lose
            //the first newline entered in a VARCHAR or TEXT column
            $specialCharsEncoded = Util::duplicateFirstNewline($specialChars);

            $data = $currentRow[$column['Field']];
        }

        /** @var string $defaultAction */
        $defaultAction = $_POST['default_action'] ?? $_GET['default_action'] ?? '';
        if (
            $defaultAction === 'insert'
            && $column['Key'] === 'PRI'
            && str_contains($column['Extra'], 'auto_increment')
        ) {
            // When copying row, it is useful to empty auto-increment column to prevent duplicate key error.
            $data = $specialCharsEncoded = $specialChars = null;
        }

        // If a timestamp field value is not included in an update
        // statement MySQL auto-update it to the current timestamp;
        // however, things have changed since MySQL 4.1, so
        // it's better to set a fields_prev in this situation
        $backupField = '<input type="hidden" name="fields_prev'
            . $columnNameAppendix . '" value="'
            . htmlspecialchars($currentRow[$column['Field']], ENT_COMPAT) . '">';

        return [
            $realNullValue,
            $specialCharsEncoded,
            $specialChars,
            $data,
            $backupField,
        ];
    }

    /**
     * display default values
     *
     * @param array $column description of column in given table
     *
     * @return array $real_null_value, $data, $special_chars,
     *               $backup_field, $special_chars_encoded
     * @psalm-return array{bool, mixed, string, string, string}
     */
    private function getSpecialCharsAndBackupFieldForInsertingMode(
        array $column
    ) {
        if (! isset($column['Default'])) {
            $column['Default'] = '';
            $realNullValue = true;
            $data = '';
        } else {
            $realNullValue = false;
            $data = $column['Default'];
        }

        $trueType = $column['True_Type'];

        if ($trueType === 'bit') {
            $specialChars = Util::convertBitDefaultValue($column['Default']);
        } elseif (substr($trueType, 0, 9) === 'timestamp' || $trueType === 'datetime' || $trueType === 'time') {
            $specialChars = Util::addMicroseconds($column['Default']);
        } elseif ($trueType === 'binary' || $trueType === 'varbinary') {
            $specialChars = bin2hex($column['Default']);
        } elseif (substr($trueType, -4) === 'text') {
            $textDefault = (string) substr($column['Default'], 1, -1);
            $specialChars = htmlspecialchars(stripcslashes($textDefault !== '' ? $textDefault : $column['Default']));
        } else {
            $specialChars = htmlspecialchars($column['Default']);
        }

        $specialCharsEncoded = Util::duplicateFirstNewline($specialChars);

        return [
            $realNullValue,
            $data,
            $specialChars,
            '',
            $specialCharsEncoded,
        ];
    }

    /**
     * Prepares the update/insert of a row
     *
     * @return array $loop_array, $using_key, $is_insert, $is_insertignore
     * @psalm-return array{array, bool, bool, bool}
     */
    public function getParamsForUpdateOrInsert()
    {
        if (isset($_POST['where_clause'])) {
            // we were editing something => use the WHERE clause
            $loopArray = is_array($_POST['where_clause'])
                ? $_POST['where_clause']
                : [$_POST['where_clause']];
            $usingKey = true;
            $isInsert = isset($_POST['submit_type'])
                && ($_POST['submit_type'] === 'insert'
                    || $_POST['submit_type'] === 'showinsert'
                    || $_POST['submit_type'] === 'insertignore');
        } else {
            // new row => use indexes
            $loopArray = [];
            if (! empty($_POST['fields'])) {
                $loopArray = array_keys($_POST['fields']['multi_edit']);
            }

            $usingKey = false;
            $isInsert = true;
        }

        $isInsertIgnore = isset($_POST['submit_type'])
            && $_POST['submit_type'] === 'insertignore';

        return [
            $loopArray,
            $usingKey,
            $isInsert,
            $isInsertIgnore,
        ];
    }

    /**
     * set $_SESSION for edit_next
     *
     * @param string $oneWhereClause one where clause from where clauses array
     */
    public function setSessionForEditNext($oneWhereClause): void
    {
        $localQuery = 'SELECT * FROM ' . Util::backquote($GLOBALS['db'])
            . '.' . Util::backquote($GLOBALS['table']) . ' WHERE '
            . str_replace('` =', '` >', $oneWhereClause) . ' LIMIT 1;';

        $res = $this->dbi->query($localQuery);
        $row = $res->fetchRow();
        $meta = $this->dbi->getFieldsMeta($res);
        // must find a unique condition based on unique key,
        // not a combination of all fields
        [$uniqueCondition] = Util::getUniqueCondition(
            count($meta),
            $meta,
            $row,
            true
        );
        if (! $uniqueCondition) {
            return;
        }

        $_SESSION['edit_next'] = $uniqueCondition;
    }

    /**
     * set $goto_include variable for different cases and retrieve like,
     * if $GLOBALS['goto'] empty, if $goto_include previously not defined
     * and new_insert, same_insert, edit_next
     *
     * @param string|false $gotoInclude store some script for include, otherwise it is
     *                                   boolean false
     */
    public function getGotoInclude($gotoInclude): string
    {
        $validOptions = [
            'new_insert',
            'same_insert',
            'edit_next',
        ];
        if (isset($_POST['after_insert']) && in_array($_POST['after_insert'], $validOptions)) {
            return '/table/change';
        }

        if (! empty($GLOBALS['goto'])) {
            if (! preg_match('@^[a-z_]+\.php$@', $GLOBALS['goto'])) {
                // this should NOT happen
                //$GLOBALS['goto'] = false;
                if (str_contains($GLOBALS['goto'], 'index.php?route=/sql')) {
                    $gotoInclude = '/sql';
                } else {
                    $gotoInclude = false;
                }
            } else {
                $gotoInclude = $GLOBALS['goto'];
            }

            if ($GLOBALS['goto'] === 'index.php?route=/database/sql' && strlen($GLOBALS['table']) > 0) {
                $GLOBALS['table'] = '';
            }
        }

        if (! $gotoInclude) {
            if (strlen($GLOBALS['table']) === 0) {
                $gotoInclude = '/database/sql';
            } else {
                $gotoInclude = '/table/sql';
            }
        }

        return $gotoInclude;
    }

    /**
     * Defines the url to return in case of failure of the query
     *
     * @param array $urlParams url parameters
     *
     * @return string           error url for query failure
     */
    public function getErrorUrl(array $urlParams)
    {
        if (isset($_POST['err_url'])) {
            return $_POST['err_url'];
        }

        return Url::getFromRoute('/table/change', $urlParams);
    }

    /**
     * Builds the sql query
     *
     * @param bool  $isInsertIgnore $_POST['submit_type'] === 'insertignore'
     * @param array $queryFields    column names array
     * @param array $valueSets      array of query values
     *
     * @return array of query
     * @psalm-return array{string}
     */
    public function buildSqlQuery(bool $isInsertIgnore, array $queryFields, array $valueSets)
    {
        if ($isInsertIgnore) {
            $insertCommand = 'INSERT IGNORE ';
        } else {
            $insertCommand = 'INSERT ';
        }

        return [
            $insertCommand . 'INTO '
            . Util::backquote($GLOBALS['table'])
            . ' (' . implode(', ', $queryFields) . ') VALUES ('
            . implode('), (', $valueSets) . ')',
        ];
    }

    /**
     * Executes the sql query and get the result, then move back to the calling page
     *
     * @param array $urlParams url parameters array
     * @param array $query     built query from buildSqlQuery()
     *
     * @return array $url_params, $total_affected_rows, $last_messages
     *               $warning_messages, $error_messages, $return_to_sql_query
     */
    public function executeSqlQuery(array $urlParams, array $query)
    {
        $returnToSqlQuery = '';
        if (! empty($GLOBALS['sql_query'])) {
            $urlParams['sql_query'] = $GLOBALS['sql_query'];
            $returnToSqlQuery = $GLOBALS['sql_query'];
        }

        $GLOBALS['sql_query'] = implode('; ', $query) . ';';
        // to ensure that the query is displayed in case of
        // "insert as new row" and then "insert another new row"
        $GLOBALS['display_query'] = $GLOBALS['sql_query'];

        $totalAffectedRows = 0;
        $lastMessages = [];
        $warningMessages = [];
        $errorMessages = [];

        foreach ($query as $singleQuery) {
            if (isset($_POST['submit_type']) && $_POST['submit_type'] === 'showinsert') {
                $lastMessages[] = Message::notice(__('Showing SQL query'));
                continue;
            }

            if ($GLOBALS['cfg']['IgnoreMultiSubmitErrors']) {
                $result = $this->dbi->tryQuery($singleQuery);
            } else {
                $result = $this->dbi->query($singleQuery);
            }

            if (! $result) {
                $errorMessages[] = $this->dbi->getError();
            } else {
                $totalAffectedRows += $this->dbi->affectedRows();

                $insertId = $this->dbi->insertId();
                if ($insertId) {
                    // insert_id is id of FIRST record inserted in one insert, so if we
                    // inserted multiple rows, we had to increment this

                    if ($totalAffectedRows > 0) {
                        $insertId += $totalAffectedRows - 1;
                    }

                    $lastMessage = Message::notice(__('Inserted row id: %1$d'));
                    $lastMessage->addParam($insertId);
                    $lastMessages[] = $lastMessage;
                }
            }

            $warningMessages = $this->getWarningMessages();
        }

        return [
            $urlParams,
            $totalAffectedRows,
            $lastMessages,
            $warningMessages,
            $errorMessages,
            $returnToSqlQuery,
        ];
    }

    /**
     * get the warning messages array
     *
     * @return string[]
     */
    private function getWarningMessages(): array
    {
        $warningMessages = [];
        foreach ($this->dbi->getWarnings() as $warning) {
            $warningMessages[] = htmlspecialchars((string) $warning);
        }

        return $warningMessages;
    }

    /**
     * Column to display from the foreign table?
     *
     * @param string $whereComparison string that contain relation field value
     * @param array  $map             all Relations to foreign tables for a given
     *                                             table or optionally a given column in a table
     * @param string $relationField   relation field
     *
     * @return string display value from the foreign table
     */
    public function getDisplayValueForForeignTableColumn(
        $whereComparison,
        array $map,
        $relationField
    ) {
        $foreigner = $this->relation->searchColumnInForeigners($map, $relationField);

        if (! is_array($foreigner)) {
            return '';
        }

        $displayField = $this->relation->getDisplayField($foreigner['foreign_db'], $foreigner['foreign_table']);
        // Field to display from the foreign table?
        if (is_string($displayField) && strlen($displayField) > 0) {
            $dispsql = 'SELECT ' . Util::backquote($displayField)
                . ' FROM ' . Util::backquote($foreigner['foreign_db'])
                . '.' . Util::backquote($foreigner['foreign_table'])
                . ' WHERE ' . Util::backquote($foreigner['foreign_field'])
                . $whereComparison;
            $dispresult = $this->dbi->tryQuery($dispsql);
            if ($dispresult && $dispresult->numRows() > 0) {
                return (string) $dispresult->fetchValue();
            }
        }

        return '';
    }

    /**
     * Display option in the cell according to user choices
     *
     * @param array  $map                all Relations to foreign tables for a given
     *                                                   table or optionally a given column in a table
     * @param string $relationField      relation field
     * @param string $whereComparison    string that contain relation field value
     * @param string $dispval            display value from the foreign table
     * @param string $relationFieldValue relation field value
     *
     * @return string HTML <a> tag
     */
    public function getLinkForRelationalDisplayField(
        array $map,
        $relationField,
        $whereComparison,
        $dispval,
        $relationFieldValue
    ): string {
        $foreigner = $this->relation->searchColumnInForeigners($map, $relationField);

        if (! is_array($foreigner)) {
            return '';
        }

        if ($_SESSION['tmpval']['relational_display'] === 'K') {
            // user chose "relational key" in the display options, so
            // the title contains the display field
            $title = $dispval
                ? ' title="' . htmlspecialchars($dispval) . '"'
                : '';
        } else {
            $title = ' title="' . htmlspecialchars($relationFieldValue) . '"';
        }

        $sqlQuery = 'SELECT * FROM '
            . Util::backquote($foreigner['foreign_db'])
            . '.' . Util::backquote($foreigner['foreign_table'])
            . ' WHERE ' . Util::backquote($foreigner['foreign_field'])
            . $whereComparison;
        $urlParams = [
            'db' => $foreigner['foreign_db'],
            'table' => $foreigner['foreign_table'],
            'pos' => '0',
            'sql_signature' => Core::signSqlQuery($sqlQuery),
            'sql_query' => $sqlQuery,
        ];
        $output = '<a href="' . Url::getFromRoute('/sql', $urlParams) . '"' . $title . '>';

        if ($_SESSION['tmpval']['relational_display'] === 'D') {
            // user chose "relational display field" in the
            // display options, so show display field in the cell
            $output .= htmlspecialchars($dispval);
        } else {
            // otherwise display data in the cell
            $output .= htmlspecialchars($relationFieldValue);
        }

        $output .= '</a>';

        return $output;
    }

    /**
     * Transform edited values
     *
     * @param string $db             db name
     * @param string $table          table name
     * @param array  $transformation mimetypes for all columns of a table
     *                                [field_name][field_key]
     * @param array  $editedValues   transform columns list and new values
     * @param string $file           file containing the transformation plugin
     * @param string $columnName     column name
     * @param array  $extraData      extra data array
     * @param string $type           the type of transformation
     *
     * @return array
     */
    public function transformEditedValues(
        $db,
        $table,
        array $transformation,
        array &$editedValues,
        $file,
        $columnName,
        array $extraData,
        $type
    ) {
        $includeFile = 'libraries/classes/Plugins/Transformations/' . $file;
        if (is_file(ROOT_PATH . $includeFile)) {
            // $cfg['SaveCellsAtOnce'] = true; JS code sends an array
            $whereClause = is_array($_POST['where_clause']) ? $_POST['where_clause'][0] : $_POST['where_clause'];
            $urlParams = [
                'db' => $db,
                'table' => $table,
                'where_clause_sign' => Core::signSqlQuery($whereClause),
                'where_clause' => $whereClause,
                'transform_key' => $columnName,
            ];
            $transformOptions = $this->transformations->getOptions($transformation[$type . '_options'] ?? '');
            $transformOptions['wrapper_link'] = Url::getCommon($urlParams);
            $transformOptions['wrapper_params'] = $urlParams;
            $className = $this->transformations->getClassName($includeFile);
            if (class_exists($className)) {
                /** @var TransformationsPlugin $transformationPlugin */
                $transformationPlugin = new $className();

                foreach ($editedValues as $cellIndex => $currCellEditedValues) {
                    if (! isset($currCellEditedValues[$columnName])) {
                        continue;
                    }

                    $extraData['transformations'][$cellIndex] = $transformationPlugin->applyTransformation(
                        $currCellEditedValues[$columnName],
                        $transformOptions
                    );
                    $editedValues[$cellIndex][$columnName] = $extraData['transformations'][$cellIndex];
                }
            }
        }

        return $extraData;
    }

    /**
     * Get current value in multi edit mode
     *
     * @param array  $multiEditFuncs       multiple edit functions array
     * @param array  $multiEditSalt        multiple edit array with encryption salt
     * @param array  $gisFromTextFunctions array that contains gis from text functions
     * @param string $currentValue         current value in the column
     * @param array  $gisFromWkbFunctions  initially $val is $multi_edit_columns[$key]
     * @param array  $funcOptionalParam    array('RAND','UNIX_TIMESTAMP')
     * @param array  $funcNoParam          array of set of string
     * @param string $key                  an md5 of the column name
     */
    public function getCurrentValueAsAnArrayForMultipleEdit(
        $multiEditFuncs,
        $multiEditSalt,
        $gisFromTextFunctions,
        $currentValue,
        $gisFromWkbFunctions,
        $funcOptionalParam,
        $funcNoParam,
        $key
    ): string {
        if ($multiEditFuncs[$key] === 'PHP_PASSWORD_HASH') {
            /**
             * @see https://github.com/vimeo/psalm/issues/3350
             *
             * @psalm-suppress InvalidArgument
             */
            $hash = password_hash($currentValue, PASSWORD_DEFAULT);

            return "'" . $this->dbi->escapeString($hash) . "'";
        }

        if ($multiEditFuncs[$key] === 'UUID') {
            /* This way user will know what UUID new row has */
            $uuid = (string) $this->dbi->fetchValue('SELECT UUID()');

            return "'" . $this->dbi->escapeString($uuid) . "'";
        }

        if (
            in_array($multiEditFuncs[$key], $gisFromTextFunctions)
            || in_array($multiEditFuncs[$key], $gisFromWkbFunctions)
        ) {
            preg_match('/^(\'?)(.*?)\1(?:,(\d+))?$/', $currentValue, $matches);
            $escapedParams = "'" . $this->dbi->escapeString($matches[2])
                . (isset($matches[3]) ? "'," . $matches[3] : "'");

            return $multiEditFuncs[$key] . '(' . $escapedParams . ')';
        }

        if (
            ! in_array($multiEditFuncs[$key], $funcNoParam)
            || ($currentValue !== ''
                && in_array($multiEditFuncs[$key], $funcOptionalParam))
        ) {
            if (
                (isset($multiEditSalt[$key])
                    && ($multiEditFuncs[$key] === 'AES_ENCRYPT'
                        || $multiEditFuncs[$key] === 'AES_DECRYPT'))
                || (! empty($multiEditSalt[$key])
                    && ($multiEditFuncs[$key] === 'DES_ENCRYPT'
                        || $multiEditFuncs[$key] === 'DES_DECRYPT'
                        || $multiEditFuncs[$key] === 'ENCRYPT'))
            ) {
                return $multiEditFuncs[$key] . "('" . $this->dbi->escapeString($currentValue) . "','"
                    . $this->dbi->escapeString($multiEditSalt[$key]) . "')";
            }

            return $multiEditFuncs[$key] . "('" . $this->dbi->escapeString($currentValue) . "')";
        }

        return $multiEditFuncs[$key] . '()';
    }

    /**
     * Get query values array and query fields array for insert and update in multi edit
     *
     * @param array  $multiEditColumnsName     multiple edit columns name array
     * @param array  $multiEditColumnsNull     multiple edit columns null array
     * @param string $currentValue             current value in the column in loop
     * @param array  $multiEditColumnsPrev     multiple edit previous columns array
     * @param array  $multiEditFuncs           multiple edit functions array
     * @param bool   $isInsert                 boolean value whether insert or not
     * @param array  $queryValues              SET part of the sql query
     * @param array  $queryFields              array of query fields
     * @param string $currentValueAsAnArray    current value in the column
     *                                                as an array
     * @param array  $valueSets                array of valu sets
     * @param string $key                      an md5 of the column name
     * @param array  $multiEditColumnsNullPrev array of multiple edit columns
     *                                              null previous
     *
     * @return array[] ($query_values, $query_fields)
     */
    public function getQueryValuesForInsertAndUpdateInMultipleEdit(
        $multiEditColumnsName,
        $multiEditColumnsNull,
        $currentValue,
        $multiEditColumnsPrev,
        $multiEditFuncs,
        $isInsert,
        $queryValues,
        $queryFields,
        $currentValueAsAnArray,
        $valueSets,
        $key,
        $multiEditColumnsNullPrev
    ) {
        //  i n s e r t
        if ($isInsert) {
            // no need to add column into the valuelist
            if (strlen($currentValueAsAnArray) > 0) {
                $queryValues[] = $currentValueAsAnArray;
                // first inserted row so prepare the list of fields
                if (empty($valueSets)) {
                    $queryFields[] = Util::backquote($multiEditColumnsName[$key]);
                }
            }
        } elseif (! empty($multiEditColumnsNullPrev[$key]) && ! isset($multiEditColumnsNull[$key])) {
            //  u p d a t e

            // field had the null checkbox before the update
            // field no longer has the null checkbox
            $queryValues[] = Util::backquote($multiEditColumnsName[$key])
                . ' = ' . $currentValueAsAnArray;
        } elseif (
            ! (empty($multiEditFuncs[$key])
                && empty($multiEditColumnsNull[$key])
                && isset($multiEditColumnsPrev[$key])
                && $currentValue === $multiEditColumnsPrev[$key])
            && $currentValueAsAnArray !== ''
        ) {
            // avoid setting a field to NULL when it's already NULL
            // (field had the null checkbox before the update
            //  field still has the null checkbox)
            if (empty($multiEditColumnsNullPrev[$key]) || empty($multiEditColumnsNull[$key])) {
                $queryValues[] = Util::backquote($multiEditColumnsName[$key])
                    . ' = ' . $currentValueAsAnArray;
            }
        }

        return [
            $queryValues,
            $queryFields,
        ];
    }

    /**
     * Get the current column value in the form for different data types
     *
     * @param string|false $possiblyUploadedVal      uploaded file content
     * @param string       $key                      an md5 of the column name
     * @param array|null   $multiEditColumnsType     array of multi edit column types
     * @param string       $currentValue             current column value in the form
     * @param array|null   $multiEditAutoIncrement   multi edit auto increment
     * @param int          $rownumber                index of where clause array
     * @param array        $multiEditColumnsName     multi edit column names array
     * @param array        $multiEditColumnsNull     multi edit columns null array
     * @param array        $multiEditColumnsNullPrev multi edit columns previous null
     * @param bool         $isInsert                 whether insert or not
     * @param bool         $usingKey                 whether editing or new row
     * @param string       $whereClause              where clause
     * @param string       $table                    table name
     * @param array        $multiEditFuncs           multiple edit functions array
     *
     * @return string  current column value in the form
     */
    public function getCurrentValueForDifferentTypes(
        $possiblyUploadedVal,
        $key,
        ?array $multiEditColumnsType,
        $currentValue,
        ?array $multiEditAutoIncrement,
        $rownumber,
        $multiEditColumnsName,
        $multiEditColumnsNull,
        $multiEditColumnsNullPrev,
        $isInsert,
        $usingKey,
        $whereClause,
        $table,
        $multiEditFuncs
    ): string {
        if ($possiblyUploadedVal !== false) {
            return $possiblyUploadedVal;
        }

        // c o l u m n    v a l u e    i n    t h e    f o r m
        $type = $multiEditColumnsType[$key] ?? '';

        if ($type !== 'protected' && $type !== 'set' && strlen($currentValue) === 0) {
            // best way to avoid problems in strict mode
            // (works also in non-strict mode)
            $currentValue = "''";
            if (isset($multiEditAutoIncrement, $multiEditAutoIncrement[$key])) {
                $currentValue = 'NULL';
            }
        } elseif ($type === 'set') {
            $currentValue = "''";
            if (! empty($_POST['fields']['multi_edit'][$rownumber][$key])) {
                $currentValue = implode(',', $_POST['fields']['multi_edit'][$rownumber][$key]);
                $currentValue = "'"
                    . $this->dbi->escapeString($currentValue) . "'";
            }
        } elseif ($type === 'protected') {
            // Fetch the current values of a row to use in case we have a protected field
            if (
                $isInsert
                && $usingKey
                && is_array($multiEditColumnsType) && $whereClause
            ) {
                $protectedRow = $this->dbi->fetchSingleRow(
                    'SELECT * FROM ' . Util::backquote($table)
                    . ' WHERE ' . $whereClause . ';'
                );
            }

            // here we are in protected mode (asked in the config)
            // so tbl_change has put this special value in the
            // columns array, so we do not change the column value
            // but we can still handle column upload

            // when in UPDATE mode, do not alter field's contents. When in INSERT
            // mode, insert empty field because no values were submitted.
            // If protected blobs where set, insert original fields content.
            $currentValue = '';
            if (! empty($protectedRow[$multiEditColumnsName[$key]])) {
                $currentValue = '0x'
                    . bin2hex($protectedRow[$multiEditColumnsName[$key]]);
            }
        } elseif ($type === 'hex') {
            if (substr($currentValue, 0, 2) != '0x') {
                $currentValue = '0x' . $currentValue;
            }
        } elseif ($type === 'bit') {
            $currentValue = (string) preg_replace('/[^01]/', '0', $currentValue);
            $currentValue = "b'" . $this->dbi->escapeString($currentValue) . "'";
        } elseif (
            ! ($type === 'datetime' || $type === 'timestamp' || $type === 'date')
            || ! preg_match('/^current_timestamp(\([0-6]?\))?$/i', $currentValue)
        ) {
            $currentValue = "'" . $this->dbi->escapeString($currentValue)
                . "'";
        }

        // Was the Null checkbox checked for this field?
        // (if there is a value, we ignore the Null checkbox: this could
        // be possible if Javascript is disabled in the browser)
        if (! empty($multiEditColumnsNull[$key]) && ($currentValue == "''" || $currentValue == '')) {
            $currentValue = 'NULL';
        }

        // The Null checkbox was unchecked for this field
        if (
            empty($currentValue)
            && ! empty($multiEditColumnsNullPrev[$key])
            && ! isset($multiEditColumnsNull[$key])
        ) {
            $currentValue = "''";
        }

        // For uuid type, generate uuid value
        // if empty value but not set null or value is uuid() function
        if (
            $type === 'uuid'
                && ! isset($multiEditColumnsNull[$key])
                && ($currentValue == "''"
                    || $currentValue == ''
                    || $currentValue === "'uuid()'")
        ) {
            $currentValue = 'uuid()';
        }

        return $currentValue;
    }

    /**
     * Check whether inline edited value can be truncated or not,
     * and add additional parameters for extra_data array  if needed
     *
     * @param string $db         Database name
     * @param string $table      Table name
     * @param string $columnName Column name
     * @param array  $extraData  Extra data for ajax response
     */
    public function verifyWhetherValueCanBeTruncatedAndAppendExtraData(
        $db,
        $table,
        $columnName,
        array &$extraData
    ): void {
        $extraData['isNeedToRecheck'] = false;

        $sqlForRealValue = 'SELECT ' . Util::backquote($table) . '.'
            . Util::backquote($columnName)
            . ' FROM ' . Util::backquote($db) . '.'
            . Util::backquote($table)
            . ' WHERE ' . $_POST['where_clause'][0];

        $result = $this->dbi->tryQuery($sqlForRealValue);

        if (! $result) {
            return;
        }

        $fieldsMeta = $this->dbi->getFieldsMeta($result);
        $meta = $fieldsMeta[0];
        $newValue = $result->fetchValue();

        if ($newValue === false) {
            return;
        }

        if ($meta->isTimeType()) {
            $newValue = Util::addMicroseconds($newValue);
        } elseif ($meta->isBinary()) {
            $newValue = '0x' . bin2hex($newValue);
        }

        $extraData['isNeedToRecheck'] = true;
        $extraData['truncatableFieldValue'] = $newValue;
    }

    /**
     * Function to get the columns of a table
     *
     * @param string $db    current db
     * @param string $table current table
     *
     * @return array[]
     */
    public function getTableColumns($db, $table)
    {
        $this->dbi->selectDb($db);

        return array_values($this->dbi->getColumns($db, $table, true));
    }

    /**
     * Function to determine Insert/Edit rows
     *
     * @param string|null $whereClause where clause
     * @param string      $db          current database
     * @param string      $table       current table
     *
     * @return array
     */
    public function determineInsertOrEdit($whereClause, $db, $table): array
    {
        if (isset($_POST['where_clause'])) {
            $whereClause = $_POST['where_clause'];
        }

        if (isset($_SESSION['edit_next'])) {
            $whereClause = $_SESSION['edit_next'];
            unset($_SESSION['edit_next']);
            $afterInsert = 'edit_next';
        }

        if (isset($_POST['ShowFunctionFields'])) {
            $GLOBALS['cfg']['ShowFunctionFields'] = $_POST['ShowFunctionFields'];
        }

        if (isset($_POST['ShowFieldTypesInDataEditView'])) {
            $GLOBALS['cfg']['ShowFieldTypesInDataEditView'] = $_POST['ShowFieldTypesInDataEditView'];
        }

        if (isset($_POST['after_insert'])) {
            $afterInsert = $_POST['after_insert'];
        }

        if (isset($whereClause)) {
            // we are editing
            $insertMode = false;
            $whereClauseArray = $this->getWhereClauseArray($whereClause);
            [$whereClauses, $result, $rows, $foundUniqueKey] = $this->analyzeWhereClauses(
                $whereClauseArray,
                $table,
                $db
            );
        } else {
            // we are inserting
            $insertMode = true;
            $whereClause = null;
            [$result, $rows] = $this->loadFirstRow($table, $db);
            $whereClauses = null;
            $whereClauseArray = [];
            $foundUniqueKey = false;
        }

        /** @var string $defaultAction */
        $defaultAction = $_POST['default_action'] ?? $_GET['default_action'] ?? '';
        if ($defaultAction === 'insert') {
            // Copying a row - fetched data will be inserted as a new row, therefore the where clause is needless.
            $whereClause = $whereClauses = null;
        }

        return [
            $insertMode,
            $whereClause,
            $whereClauseArray,
            $whereClauses,
            $result,
            $rows,
            $foundUniqueKey,
            $afterInsert ?? null,
        ];
    }

    /**
     * Function to get comments for the table columns
     *
     * @param string $db    current database
     * @param string $table current table
     *
     * @return array comments for columns
     */
    public function getCommentsMap($db, $table): array
    {
        if ($GLOBALS['cfg']['ShowPropertyComments']) {
            return $this->relation->getComments($db, $table);
        }

        return [];
    }

    /**
     * Function to get html for the gis editor div
     */
    public function getHtmlForGisEditor(): string
    {
        return '<div id="gis_editor"></div><div id="popup_background"></div><br>';
    }

    /**
     * Function to get html for the ignore option in insert mode
     *
     * @param int  $rowId   row id
     * @param bool $checked ignore option is checked or not
     */
    public function getHtmlForIgnoreOption($rowId, $checked = true): string
    {
        return '<input type="checkbox"'
            . ($checked ? ' checked="checked"' : '')
            . ' name="insert_ignore_' . $rowId . '"'
            . ' id="insert_ignore_' . $rowId . '">'
            . '<label for="insert_ignore_' . $rowId . '">'
            . __('Ignore')
            . '</label><br>' . "\n";
    }

    /**
     * Function to get html for the insert edit form header
     *
     * @param bool $hasBlobField whether has blob field
     * @param bool $isUpload     whether is upload
     */
    public function getHtmlForInsertEditFormHeader($hasBlobField, $isUpload): string
    {
        $template = new Template();

        return $template->render('table/insert/get_html_for_insert_edit_form_header', [
            'has_blob_field' => $hasBlobField,
            'is_upload' => $isUpload,
        ]);
    }

    /**
     * Function to get html for each insert/edit column
     *
     * @param array           $column             column
     * @param int             $columnNumber       column index in table_columns
     * @param array           $commentsMap        comments map
     * @param bool            $timestampSeen      whether timestamp seen
     * @param ResultInterface $currentResult      current result
     * @param string          $chgEvtHandler      javascript change event handler
     * @param string          $jsvkey             javascript validation key
     * @param string          $vkey               validation key
     * @param bool            $insertMode         whether insert mode
     * @param array           $currentRow         current row
     * @param int             $oRows              row offset
     * @param int             $tabindex           tab index
     * @param int             $columnsCnt         columns count
     * @param bool            $isUpload           whether upload
     * @param array           $foreigners         foreigners
     * @param int             $tabindexForValue   tab index offset for value
     * @param string          $table              table
     * @param string          $db                 database
     * @param int             $rowId              row id
     * @param int             $biggestMaxFileSize biggest max file size
     * @param string          $defaultCharEditing default char editing mode which is stored in the config.inc.php script
     * @param string          $textDir            text direction
     * @param array           $repopulate         the data to be repopulated
     * @param array           $columnMime         the mime information of column
     * @param string          $whereClause        the where clause
     *
     * @return string
     */
    private function getHtmlForInsertEditFormColumn(
        array $column,
        int $columnNumber,
        array $commentsMap,
        $timestampSeen,
        ResultInterface $currentResult,
        $chgEvtHandler,
        $jsvkey,
        $vkey,
        $insertMode,
        array $currentRow,
        $oRows,
        &$tabindex,
        $columnsCnt,
        $isUpload,
        array $foreigners,
        $tabindexForValue,
        $table,
        $db,
        $rowId,
        $biggestMaxFileSize,
        $defaultCharEditing,
        $textDir,
        array $repopulate,
        array $columnMime,
        $whereClause
    ) {
        $readOnly = false;

        if (! isset($column['processed'])) {
            $column = $this->analyzeTableColumnsArray($column, $commentsMap, $timestampSeen);
        }

        $asIs = false;
        /** @var string $fieldHashMd5 */
        $fieldHashMd5 = $column['Field_md5'];
        if ($repopulate && array_key_exists($fieldHashMd5, $currentRow)) {
            $currentRow[$column['Field']] = $repopulate[$fieldHashMd5];
            $asIs = true;
        }

        $extractedColumnspec = Util::extractColumnSpec($column['Type']);

        if ($column['len'] === -1) {
            $column['len'] = $this->dbi->getFieldsMeta($currentResult)[$columnNumber]->length;
            // length is unknown for geometry fields,
            // make enough space to edit very simple WKTs
            if ($column['len'] === -1) {
                $column['len'] = 30;
            }
        }

        //Call validation when the form submitted...
        $onChangeClause = $chgEvtHandler
            . "=\"return verificationsAfterFieldChange('"
            . Sanitize::escapeJsString($fieldHashMd5) . "', '"
            . Sanitize::escapeJsString($jsvkey) . "','" . $column['pma_type'] . "')\"";

        // Use an MD5 as an array index to avoid having special characters
        // in the name attribute (see bug #1746964 )
        $columnNameAppendix = $vkey . '[' . $fieldHashMd5 . ']';

        if ($column['Type'] === 'datetime' && $column['Null'] !== 'YES' && ! isset($column['Default']) && $insertMode) {
            $column['Default'] = date('Y-m-d H:i:s', time());
        }

        // Get a list of GIS data types.
        $gisDataTypes = Gis::getDataTypes();

        // Prepares the field value
        if ($currentRow) {
            // (we are editing)
            [
                $realNullValue,
                $specialCharsEncoded,
                $specialChars,
                $data,
                $backupField,
            ] = $this->getSpecialCharsAndBackupFieldForExistingRow(
                $currentRow,
                $column,
                $extractedColumnspec,
                $gisDataTypes,
                $columnNameAppendix,
                $asIs
            );
        } else {
            // (we are inserting)
            // display default values
            $tmp = $column;
            if (isset($repopulate[$fieldHashMd5])) {
                $tmp['Default'] = $repopulate[$fieldHashMd5];
            }

            [
                $realNullValue,
                $data,
                $specialChars,
                $backupField,
                $specialCharsEncoded,
            ] = $this->getSpecialCharsAndBackupFieldForInsertingMode($tmp);
            unset($tmp);
        }

        $idindex = ($oRows * $columnsCnt) + $columnNumber + 1;
        $tabindex = $idindex;

        // The function column
        // -------------------
        $foreignData = $this->relation->getForeignData($foreigners, $column['Field'], false, '', '');
        $isColumnBinary = $this->isColumnBinary($column, $isUpload);
        $functionOptions = '';

        if ($GLOBALS['cfg']['ShowFunctionFields']) {
            $functionOptions = Generator::getFunctionsForField($column, $insertMode, $foreignData);
        }

        // nullify code is needed by the js nullify() function to be able to generate calls to nullify() in jQuery
        $nullifyCode = $this->getNullifyCodeForNullColumn($column, $foreigners, $foreignData);

        // The value column (depends on type)
        // ----------------
        // See bug #1667887 for the reason why we don't use the maxlength
        // HTML attribute

        //add data attributes "no of decimals" and "data type"
        $noDecimals = 0;
        $type = current(explode('(', $column['pma_type']));
        if (preg_match('/\(([^()]+)\)/', $column['pma_type'], $match)) {
            $match[0] = trim($match[0], '()');
            $noDecimals = $match[0];
        }

        // Check input transformation of column
        $transformedHtml = '';
        if (! empty($columnMime['input_transformation'])) {
            $file = $columnMime['input_transformation'];
            $includeFile = 'libraries/classes/Plugins/Transformations/' . $file;
            if (is_file(ROOT_PATH . $includeFile)) {
                $className = $this->transformations->getClassName($includeFile);
                if (class_exists($className)) {
                    $transformationPlugin = new $className();
                    $transformationOptions = $this->transformations->getOptions(
                        $columnMime['input_transformation_options']
                    );
                    $urlParams = [
                        'db' => $db,
                        'table' => $table,
                        'transform_key' => $column['Field'],
                        'where_clause_sign' => Core::signSqlQuery($whereClause),
                        'where_clause' => $whereClause,
                    ];
                    $transformationOptions['wrapper_link'] = Url::getCommon($urlParams);
                    $transformationOptions['wrapper_params'] = $urlParams;
                    $currentValue = '';
                    if (isset($currentRow[$column['Field']])) {
                        $currentValue = $currentRow[$column['Field']];
                    }

                    if (method_exists($transformationPlugin, 'getInputHtml')) {
                        $transformedHtml = $transformationPlugin->getInputHtml(
                            $column,
                            $rowId,
                            $columnNameAppendix,
                            $transformationOptions,
                            $currentValue,
                            $textDir,
                            $tabindex,
                            $tabindexForValue,
                            $idindex
                        );
                    }

                    if (method_exists($transformationPlugin, 'getScripts')) {
                        $GLOBALS['plugin_scripts'] = array_merge(
                            $GLOBALS['plugin_scripts'],
                            $transformationPlugin->getScripts()
                        );
                    }
                }
            }
        }

        $columnValue = '';
        $foreignDropdown = '';
        $dataType = '';
        $textAreaRows = $GLOBALS['cfg']['TextareaRows'];
        $textareaCols = $GLOBALS['cfg']['TextareaCols'];
        $maxlength = '';
        $enumSelectedValue = '';
        $columnSetValues = [];
        $setSelectSize = 0;
        $isColumnProtectedBlob = false;
        $blobValue = '';
        $blobValueUnit = '';
        $maxUploadSize = 0;
        $selectOptionForUpload = '';
        $inputFieldHtml = '';
        if (empty($transformedHtml)) {
            if (is_array($foreignData['disp_row'])) {
                $foreignDropdown = $this->relation->foreignDropdown(
                    $foreignData['disp_row'],
                    $foreignData['foreign_field'],
                    $foreignData['foreign_display'],
                    $data,
                    $GLOBALS['cfg']['ForeignKeyMaxLimit']
                );
            }

            $dataType = $this->dbi->types->getTypeClass($column['True_Type']);

            if ($column['is_char']) {
                $textAreaRows = max($GLOBALS['cfg']['CharTextareaRows'], 7);
                $textareaCols = $GLOBALS['cfg']['CharTextareaCols'];
                $maxlength = $extractedColumnspec['spec_in_brackets'];
            } elseif ($GLOBALS['cfg']['LongtextDoubleTextarea'] && mb_strstr($column['pma_type'], 'longtext')) {
                $textAreaRows = $GLOBALS['cfg']['TextareaRows'] * 2;
                $textareaCols = $GLOBALS['cfg']['TextareaCols'] * 2;
            }

            if ($column['pma_type'] === 'enum') {
                if (! isset($column['values'])) {
                    $column['values'] = $this->getColumnEnumValues($extractedColumnspec['enum_set_values']);
                }

                foreach ($column['values'] as $enumValue) {
                    if (
                        $data == $enumValue['plain'] || ($data == ''
                            && (! isset($_POST['where_clause']) || $column['Null'] !== 'YES')
                            && isset($column['Default']) && $enumValue['plain'] == $column['Default'])
                    ) {
                        $enumSelectedValue = $enumValue['plain'];
                        break;
                    }
                }
            } elseif ($column['pma_type'] === 'set') {
                [$columnSetValues, $setSelectSize] = $this->getColumnSetValueAndSelectSize(
                    $column,
                    $extractedColumnspec['enum_set_values']
                );
            } elseif ($column['is_binary'] || $column['is_blob']) {
                $isColumnProtectedBlob = ($GLOBALS['cfg']['ProtectBinary'] === 'blob' && $column['is_blob'])
                    || ($GLOBALS['cfg']['ProtectBinary'] === 'all')
                    || ($GLOBALS['cfg']['ProtectBinary'] === 'noblob' && ! $column['is_blob']);
                if ($isColumnProtectedBlob && isset($data)) {
                    $blobSize = Util::formatByteDown(mb_strlen(stripslashes($data)), 3, 1);
                    if ($blobSize !== null) {
                        [$blobValue, $blobValueUnit] = $blobSize;
                    }
                }

                if ($isUpload && $column['is_blob']) {
                    [$maxUploadSize] = $this->getMaxUploadSize($column['True_Type'], $biggestMaxFileSize);
                }

                if (! empty($GLOBALS['cfg']['UploadDir'])) {
                    $selectOptionForUpload = $this->getSelectOptionForUpload($vkey, $fieldHashMd5);
                }

                if (
                    ! $isColumnProtectedBlob
                    && ! ($column['is_blob'] || ($column['len'] > $GLOBALS['cfg']['LimitChars']))
                ) {
                    $inputFieldHtml = $this->getHtmlInput(
                        $column,
                        $columnNameAppendix,
                        $specialChars,
                        min(max($column['len'] * 2, 4), $GLOBALS['cfg']['LimitChars']),
                        $onChangeClause,
                        $tabindex,
                        $tabindexForValue,
                        $idindex,
                        'HEX',
                        $readOnly
                    );
                }
            } else {
                $columnValue = $this->getValueColumnForOtherDatatypes(
                    $column,
                    $defaultCharEditing,
                    $backupField,
                    $columnNameAppendix,
                    $onChangeClause,
                    $tabindex,
                    $specialChars,
                    $tabindexForValue,
                    $idindex,
                    $textDir,
                    $specialCharsEncoded,
                    $data,
                    $extractedColumnspec,
                    $readOnly
                );
            }
        }

        return $this->template->render('table/insert/column_row', [
            'db' => $db,
            'table' => $table,
            'column' => $column,
            'row_id' => $rowId,
            'show_field_types_in_data_edit_view' => $GLOBALS['cfg']['ShowFieldTypesInDataEditView'],
            'show_function_fields' => $GLOBALS['cfg']['ShowFunctionFields'],
            'is_column_binary' => $isColumnBinary,
            'function_options' => $functionOptions,
            'read_only' => $readOnly,
            'nullify_code' => $nullifyCode,
            'real_null_value' => $realNullValue,
            'id_index' => $idindex,
            'type' => $type,
            'decimals' => $noDecimals,
            'special_chars' => $specialChars,
            'transformed_value' => $transformedHtml,
            'value' => $columnValue,
            'is_value_foreign_link' => $foreignData['foreign_link'] === true,
            'backup_field' => $backupField,
            'data' => $data,
            'gis_data_types' => $gisDataTypes,
            'foreign_dropdown' => $foreignDropdown,
            'data_type' => $dataType,
            'textarea_cols' => $textareaCols,
            'textarea_rows' => $textAreaRows,
            'text_dir' => $textDir,
            'max_length' => $maxlength,
            'longtext_double_textarea' => $GLOBALS['cfg']['LongtextDoubleTextarea'],
            'enum_selected_value' => $enumSelectedValue,
            'set_values' => $columnSetValues,
            'set_select_size' => $setSelectSize,
            'is_column_protected_blob' => $isColumnProtectedBlob,
            'blob_value' => $blobValue,
            'blob_value_unit' => $blobValueUnit,
            'is_upload' => $isUpload,
            'max_upload_size' => $maxUploadSize,
            'select_option_for_upload' => $selectOptionForUpload,
            'limit_chars' => $GLOBALS['cfg']['LimitChars'],
            'input_field_html' => $inputFieldHtml,
            'tab_index' => $tabindex,
            'tab_index_for_value' => $tabindexForValue,
        ]);
    }

    private function isColumnBinary(array $column, bool $isUpload): bool
    {
        global $cfg;

        if (! $cfg['ShowFunctionFields']) {
            return false;
        }

        return ($cfg['ProtectBinary'] === 'blob' && $column['is_blob'] && ! $isUpload)
            || ($cfg['ProtectBinary'] === 'all' && $column['is_binary'])
            || ($cfg['ProtectBinary'] === 'noblob' && $column['is_binary']);
    }

    /**
     * Function to get html for each insert/edit row
     *
     * @param array           $urlParams          url parameters
     * @param array[]         $tableColumns       table columns
     * @param array           $commentsMap        comments map
     * @param bool            $timestampSeen      whether timestamp seen
     * @param ResultInterface $currentResult      current result
     * @param string          $chgEvtHandler      javascript change event handler
     * @param string          $jsvkey             javascript validation key
     * @param string          $vkey               validation key
     * @param bool            $insertMode         whether insert mode
     * @param array           $currentRow         current row
     * @param int             $oRows              row offset
     * @param int             $tabindex           tab index
     * @param int             $columnsCnt         columns count
     * @param bool            $isUpload           whether upload
     * @param array           $foreigners         foreigners
     * @param int             $tabindexForValue   tab index offset for value
     * @param string          $table              table
     * @param string          $db                 database
     * @param int             $rowId              row id
     * @param int             $biggestMaxFileSize biggest max file size
     * @param string          $textDir            text direction
     * @param array           $repopulate         the data to be repopulated
     * @param array           $whereClauseArray   the array of where clauses
     *
     * @return string
     */
    public function getHtmlForInsertEditRow(
        array $urlParams,
        array $tableColumns,
        array $commentsMap,
        $timestampSeen,
        ResultInterface $currentResult,
        $chgEvtHandler,
        $jsvkey,
        $vkey,
        $insertMode,
        array $currentRow,
        &$oRows,
        &$tabindex,
        $columnsCnt,
        $isUpload,
        array $foreigners,
        $tabindexForValue,
        $table,
        $db,
        $rowId,
        $biggestMaxFileSize,
        $textDir,
        array $repopulate,
        array $whereClauseArray
    ) {
        $htmlOutput = $this->getHeadAndFootOfInsertRowTable($urlParams)
            . '<tbody>';

        //store the default value for CharEditing
        $defaultCharEditing = $GLOBALS['cfg']['CharEditing'];
        $mimeMap = $this->transformations->getMime($db, $table);
        $whereClause = '';
        if (isset($whereClauseArray[$rowId])) {
            $whereClause = $whereClauseArray[$rowId];
        }

        for ($columnNumber = 0; $columnNumber < $columnsCnt; $columnNumber++) {
            $tableColumn = $tableColumns[$columnNumber];
            $columnMime = [];
            if (isset($mimeMap[$tableColumn['Field']])) {
                $columnMime = $mimeMap[$tableColumn['Field']];
            }

            $virtual = [
                'VIRTUAL',
                'PERSISTENT',
                'VIRTUAL GENERATED',
                'STORED GENERATED',
            ];
            if (in_array($tableColumn['Extra'], $virtual)) {
                continue;
            }

            $htmlOutput .= $this->getHtmlForInsertEditFormColumn(
                $tableColumn,
                $columnNumber,
                $commentsMap,
                $timestampSeen,
                $currentResult,
                $chgEvtHandler,
                $jsvkey,
                $vkey,
                $insertMode,
                $currentRow,
                $oRows,
                $tabindex,
                $columnsCnt,
                $isUpload,
                $foreigners,
                $tabindexForValue,
                $table,
                $db,
                $rowId,
                $biggestMaxFileSize,
                $defaultCharEditing,
                $textDir,
                $repopulate,
                $columnMime,
                $whereClause
            );
        }

        $oRows++;

        return $htmlOutput . '  </tbody>'
            . '</table></div><br>'
            . '<div class="clearfloat"></div>';
    }
}
