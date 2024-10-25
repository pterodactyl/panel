<?php
/**
 * Database with allowed values for configuration stored in the $cfg array,
 * used by setup script and user preferences to generate forms.
 */

declare(strict_types=1);

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Value meaning:
 * o array - select field, array contains allowed values
 * o string - type override
 *
 * Use normal array, paths won't be expanded
 */
return [
    'Servers' => [
        1 => [
            'port' => 'integer',
            'auth_type' => [
                'config',
                'http',
                'signon',
                'cookie',
            ],
            'AllowDeny' => [
                'order' => [
                    '',
                    'deny,allow',
                    'allow,deny',
                    'explicit',
                ],
            ],
            'only_db' => 'array',
        ],
    ],
    'RecodingEngine' => [
        'auto',
        'iconv',
        'recode',
        'mb',
        'none',
    ],
    'OBGzip' => [
        'auto',
        true,
        false,
    ],
    'MemoryLimit' => 'short_string',
    'NavigationLogoLinkWindow' => [
        'main',
        'new',
    ],
    'NavigationTreeDefaultTabTable' => [
        // fields list
        'structure' => __('Structure'),
        // SQL form
        'sql' => __('SQL'),
        // search page
        'search' => __('Search'),
        // insert row page
        'insert' => __('Insert'),
        // browse page
        'browse' => __('Browse'),
    ],
    'NavigationTreeDefaultTabTable2' => [
        //don't display
        '' => '',
        // fields list
        'structure' => __('Structure'),
        // SQL form
        'sql' => __('SQL'),
        // search page
        'search' => __('Search'),
        // insert row page
        'insert' => __('Insert'),
        // browse page
        'browse' => __('Browse'),
    ],
    'NavigationTreeDbSeparator' => 'short_string',
    'NavigationTreeTableSeparator' => 'short_string',
    'NavigationWidth' => 'integer',
    'TableNavigationLinksMode' => [
        'icons' => __('Icons'),
        'text' => __('Text'),
        'both' => __('Both'),
    ],
    'MaxRows' => [
        25,
        50,
        100,
        250,
        500,
    ],
    'Order' => [
        'ASC',
        'DESC',
        'SMART',
    ],
    'RowActionLinks' => [
        'none' => __('Nowhere'),
        'left' => __('Left'),
        'right' => __('Right'),
        'both' => __('Both'),
    ],
    'TablePrimaryKeyOrder' => [
        'NONE' => __('None'),
        'ASC' => __('Ascending'),
        'DESC' => __('Descending'),
    ],
    'ProtectBinary' => [
        false,
        'blob',
        'noblob',
        'all',
    ],
    'CharEditing' => [
        'input',
        'textarea',
    ],
    'TabsMode' => [
        'icons' => __('Icons'),
        'text' => __('Text'),
        'both' => __('Both'),
    ],
    'PDFDefaultPageSize' => [
        'A3' => 'A3',
        'A4' => 'A4',
        'A5' => 'A5',
        'letter' => 'letter',
        'legal' => 'legal',
    ],
    'ActionLinksMode' => [
        'icons' => __('Icons'),
        'text' => __('Text'),
        'both' => __('Both'),
    ],
    'GridEditing' => [
        'click' => __('Click'),
        'double-click' => __('Double click'),
        'disabled' => __('Disabled'),
    ],
    'RelationalDisplay' => [
        'K' => __('key'),
        'D' => __('display column'),
    ],
    'DefaultTabServer' => [
        // the welcome page (recommended for multiuser setups)
        'welcome' => __('Welcome'),
        // list of databases
        'databases' => __('Databases'),
        // runtime information
        'status' => __('Status'),
        // MySQL server variables
        'variables' => __('Variables'),
        // user management
        'privileges' => __('Privileges'),
    ],
    'DefaultTabDatabase' => [
        // tables list
        'structure' => __('Structure'),
        // SQL form
        'sql' => __('SQL'),
        // search query
        'search' => __('Search'),
        // operations on database
        'operations' => __('Operations'),
    ],
    'DefaultTabTable' => [
        // fields list
        'structure' => __('Structure'),
        // SQL form
        'sql' => __('SQL'),
        // search page
        'search' => __('Search'),
        // insert row page
        'insert' => __('Insert'),
        // browse page
        'browse' => __('Browse'),
    ],
    'InitialSlidersState' => [
        'open' => __('Open'),
        'closed' => __('Closed'),
        'disabled' => __('Disabled'),
    ],
    'FirstDayOfCalendar' => [
        '1' => __('Monday'),
        '2' => __('Tuesday'),
        '3' => __('Wednesday'),
        '4' => __('Thursday'),
        '5' => __('Friday'),
        '6' => __('Saturday'),
        '7' => __('Sunday'),
    ],
    'SendErrorReports' => [
        'ask' => __('Ask before sending error reports'),
        'always' => __('Always send error reports'),
        'never' => __('Never send error reports'),
    ],
    'DefaultForeignKeyChecks' => [
        'default' => __('Server default'),
        'enable' => __('Enable'),
        'disable' => __('Disable'),
    ],

    'Import' => [
        'format' => [
            // CSV
            'csv',
            // DocSQL
            'docsql',
            // CSV using LOAD DATA
            'ldi',
            // SQL
            'sql',
        ],
        'charset' => array_merge(
            [''],
            $GLOBALS['cfg']['AvailableCharsets']
        ),
        'sql_compatibility' => [
            'NONE',
            'ANSI',
            'DB2',
            'MAXDB',
            'MYSQL323',
            'MYSQL40',
            'MSSQL',
            'ORACLE',
            // removed; in MySQL 5.0.33, this produces exports that
            // can't be read by POSTGRESQL (see our bug #1596328)
            //'POSTGRESQL',
            'TRADITIONAL',
        ],
        'csv_terminated' => 'short_string',
        'csv_enclosed' => 'short_string',
        'csv_escaped' => 'short_string',
        'ldi_terminated' => 'short_string',
        'ldi_enclosed' => 'short_string',
        'ldi_escaped' => 'short_string',
        'ldi_local_option' => [
            'auto',
            true,
            false,
        ],
    ],

    'Export' => [
        '_sod_select' => [
            'structure' => __('structure'),
            'data' => __('data'),
            'structure_and_data' => __('structure and data'),
        ],
        'method' => [
            'quick' => __('Quick - display only the minimal options to configure'),
            'custom' => __('Custom - display all possible options to configure'),
            'custom-no-form' => __('Custom - like above, but without the quick/custom choice'),
        ],
        'format' => [
            'codegen',
            'csv',
            'excel',
            'htmlexcel',
            'htmlword',
            'latex',
            'ods',
            'odt',
            'pdf',
            'sql',
            'texytext',
            'xml',
            'yaml',
        ],
        'compression' => [
            'none',
            'zip',
            'gzip',
        ],
        'charset' => array_merge(
            [''],
            $GLOBALS['cfg']['AvailableCharsets']
        ),
        'sql_compatibility' => [
            'NONE',
            'ANSI',
            'DB2',
            'MAXDB',
            'MYSQL323',
            'MYSQL40',
            'MSSQL',
            'ORACLE',
            // removed; in MySQL 5.0.33, this produces exports that
            // can't be read by POSTGRESQL (see our bug #1596328)
            //'POSTGRESQL',
            'TRADITIONAL',
        ],
        'codegen_format' => [
            '#',
            'NHibernate C# DO',
            'NHibernate XML',
        ],
        'csv_separator' => 'short_string',
        'csv_terminated' => 'short_string',
        'csv_enclosed' => 'short_string',
        'csv_escaped' => 'short_string',
        'csv_null' => 'short_string',
        'excel_null' => 'short_string',
        'excel_edition' => [
            'win' => 'Windows',
            'mac_excel2003' => 'Excel 2003 / Macintosh',
            'mac_excel2008' => 'Excel 2008 / Macintosh',
        ],
        'sql_structure_or_data' => [
            'structure' => __('structure'),
            'data' => __('data'),
            'structure_and_data' => __('structure and data'),
        ],
        'sql_type' => [
            'INSERT',
            'UPDATE',
            'REPLACE',
        ],
        'sql_insert_syntax' => [
            'complete' => __('complete inserts'),
            'extended' => __('extended inserts'),
            'both' => __('both of the above'),
            'none' => __('neither of the above'),
        ],
        'htmlword_structure_or_data' => [
            'structure' => __('structure'),
            'data' => __('data'),
            'structure_and_data' => __('structure and data'),
        ],
        'htmlword_null' => 'short_string',
        'ods_null' => 'short_string',
        'odt_null' => 'short_string',
        'odt_structure_or_data' => [
            'structure' => __('structure'),
            'data' => __('data'),
            'structure_and_data' => __('structure and data'),
        ],
        'texytext_structure_or_data' => [
            'structure' => __('structure'),
            'data' => __('data'),
            'structure_and_data' => __('structure and data'),
        ],
        'texytext_null' => 'short_string',
    ],

    'Console' => [
        'Mode' => [
            'info',
            'show',
            'collapse',
        ],
        'OrderBy' => [
            'exec',
            'time',
            'count',
        ],
        'Order' => [
            'asc',
            'desc',
        ],
    ],

    /**
     * Default values overrides
     * Use only full paths
     */
    '_overrides' => [],

    /**
     * Basic validator assignments (functions from libraries/config/Validator.php
     * and 'validators' object in js/config.js)
     * Use only full paths and form ids
     */
    '_validators' => [
        'Console/Height' => 'validateNonNegativeNumber',
        'CharTextareaCols' => 'validatePositiveNumber',
        'CharTextareaRows' => 'validatePositiveNumber',
        'ExecTimeLimit' => 'validateNonNegativeNumber',
        'Export/sql_max_query_size' => 'validatePositiveNumber',
        'FirstLevelNavigationItems' => 'validatePositiveNumber',
        'ForeignKeyMaxLimit' => 'validatePositiveNumber',
        'Import/csv_enclosed' => [
            [
                'validateByRegex',
                '/^.?$/',
            ],
        ],
        'Import/csv_escaped' => [
            [
                'validateByRegex',
                '/^.$/',
            ],
        ],
        'Import/csv_terminated' => [
            [
                'validateByRegex',
                '/^.$/',
            ],
        ],
        'Import/ldi_enclosed' => [
            [
                'validateByRegex',
                '/^.?$/',
            ],
        ],
        'Import/ldi_escaped' => [
            [
                'validateByRegex',
                '/^.$/',
            ],
        ],
        'Import/ldi_terminated' => [
            [
                'validateByRegex',
                '/^.$/',
            ],
        ],
        'Import/skip_queries' => 'validateNonNegativeNumber',
        'InsertRows' => 'validatePositiveNumber',
        'NumRecentTables' => 'validateNonNegativeNumber',
        'NumFavoriteTables' => 'validateNonNegativeNumber',
        'LimitChars' => 'validatePositiveNumber',
        'LoginCookieValidity' => 'validatePositiveNumber',
        'LoginCookieStore' => 'validateNonNegativeNumber',
        'MaxDbList' => 'validatePositiveNumber',
        'MaxNavigationItems' => 'validatePositiveNumber',
        'MaxCharactersInDisplayedSQL' => 'validatePositiveNumber',
        'MaxRows' => 'validatePositiveNumber',
        'MaxSizeForInputField' => 'validatePositiveNumber',
        'MinSizeForInputField' => 'validateNonNegativeNumber',
        'MaxTableList' => 'validatePositiveNumber',
        'MemoryLimit' => [
            [
                'validateByRegex',
                '/^(-1|(\d+(?:[kmg])?))$/i',
            ],
        ],
        'NavigationTreeDisplayItemFilterMinimum' => 'validatePositiveNumber',
        'NavigationTreeTableLevel' => 'validatePositiveNumber',
        'NavigationWidth' => 'validateNonNegativeNumber',
        'QueryHistoryMax' => 'validatePositiveNumber',
        'RepeatCells' => 'validateNonNegativeNumber',
        'Server' => 'validateServer',
        'Server_pmadb' => 'validatePMAStorage',
        'Servers/1/port' => 'validatePortNumber',
        'Servers/1/hide_db' => 'validateRegex',
        'TextareaCols' => 'validatePositiveNumber',
        'TextareaRows' => 'validatePositiveNumber',
        'TrustedProxies' => 'validateTrustedProxies',
    ],

    /**
     * Additional validators used for user preferences
     */
    '_userValidators' => [
        'MaxDbList' => [
            [
                'validateUpperBound',
                'value:MaxDbList',
            ],
        ],
        'MaxTableList' => [
            [
                'validateUpperBound',
                'value:MaxTableList',
            ],
        ],
        'QueryHistoryMax' => [
            [
                'validateUpperBound',
                'value:QueryHistoryMax',
            ],
        ],
    ],
];
