<?php
/**
 * Links configuration for MySQL system tables
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

class SpecialSchemaLinks
{
    /**
     * This array represent the details for generating links inside
     * special schemas like mysql, information_schema etc.
     * Major element represent a schema.
     * All the strings in this array represented in lower case
     *
     * Array structure ex:
     * array(
     *     // Database name is the major element
     *     'mysql' => array(
     *         // Table name
     *         'db' => array(
     *             // Column name
     *             'user' => array(
     *                 // Main url param (can be an array where represent sql)
     *                 'link_param' => 'username',
     *                 // Other url params
     *                 'link_dependancy_params' => array(
     *                     0 => array(
     *                         // URL parameter name
     *                         // (can be array where url param has static value)
     *                         'param_info' => 'hostname',
     *                         // Column name related to url param
     *                         'column_name' => 'host'
     *                     )
     *                 ),
     *                 // Page to link
     *                 'default_page' => './' . Url::getFromRoute('/server/privileges')
     *             )
     *         )
     *     )
     * );
     *
     * @return array<string,array<string,array<string,array<string,array<int,array<string,string>>|string>>>>
     * @phpstan-return array<
     *              string, array<
     *                  string, array<
     *                      string,
     *                      array{
     *                          'link_param': string,
     *                          'link_dependancy_params'?: array<
     *                                                      int,
     *                                                      array{'param_info': string, 'column_name': string}
     *                                                     >,
     *                          'default_page': string
     *                      }>
     *                  >
     *             >
     * }
     */
    public static function get(): array
    {
        global $cfg;

        $defaultPageDatabase = './' . Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $defaultPageTable = './' . Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');

        return [
            'mysql' => [
                'columns_priv' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'hostname',
                                'column_name' => 'host',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'Db',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                    'column_name' => [
                        'link_param' => 'field',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'Db',
                            ],
                            1 => [
                                'param_info' => 'table',
                                'column_name' => 'Table_name',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/table/structure/change', ['change_column' => 1]),
                    ],
                ],
                'db' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'hostname',
                                'column_name' => 'host',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                ],
                'event' => [
                    'name' => [
                        'link_param' => 'item_name',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'db',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/database/events', ['edit_item' => 1]),
                    ],

                ],
                'innodb_index_stats' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'database_name',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                    'index_name' => [
                        'link_param' => 'index',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'database_name',
                            ],
                            1 => [
                                'param_info' => 'table',
                                'column_name' => 'table_name',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/table/structure'),
                    ],
                ],
                'innodb_table_stats' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'database_name',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                ],
                'proc' => [
                    'name' => [
                        'link_param' => 'item_name',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'db',
                            ],
                            1 => [
                                'param_info' => 'item_type',
                                'column_name' => 'type',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/database/routines', ['edit_item' => 1]),
                    ],
                    'specific_name' => [
                        'link_param' => 'item_name',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'db',
                            ],
                            1 => [
                                'param_info' => 'item_type',
                                'column_name' => 'type',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/database/routines', ['edit_item' => 1]),
                    ],
                ],
                'proc_priv' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'hostname',
                                'column_name' => 'Host',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                    'routine_name' => [
                        'link_param' => 'item_name',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'Db',
                            ],
                            1 => [
                                'param_info' => 'item_type',
                                'column_name' => 'Routine_type',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/database/routines', ['edit_item' => 1]),
                    ],
                ],
                'proxies_priv' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'hostname',
                                'column_name' => 'Host',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                ],
                'tables_priv' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'hostname',
                                'column_name' => 'Host',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'Db',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                ],
                'user' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'hostname',
                                'column_name' => 'host',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                ],
            ],
            'information_schema' => [
                'columns' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'table_schema',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                    'column_name' => [
                        'link_param' => 'field',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'table_schema',
                            ],
                            1 => [
                                'param_info' => 'table',
                                'column_name' => 'table_name',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/table/structure/change', ['change_column' => 1]),
                    ],
                ],
                'key_column_usage' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'constraint_schema',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                    'column_name' => [
                        'link_param' => 'field',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'table_schema',
                            ],
                            1 => [
                                'param_info' => 'table',
                                'column_name' => 'table_name',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/table/structure/change', ['change_column' => 1]),
                    ],
                    'referenced_table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'referenced_table_schema',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                    'referenced_column_name' => [
                        'link_param' => 'field',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'referenced_table_schema',
                            ],
                            1 => [
                                'param_info' => 'table',
                                'column_name' => 'referenced_table_name',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/table/structure/change', ['change_column' => 1]),
                    ],
                ],
                'partitions' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'table_schema',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                ],
                'processlist' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'hostname',
                                'column_name' => 'host',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                ],
                'referential_constraints' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'constraint_schema',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                    'referenced_table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'constraint_schema',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                ],
                'routines' => [
                    'routine_name' => [
                        'link_param' => 'item_name',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'routine_schema',
                            ],
                            1 => [
                                'param_info' => 'item_type',
                                'column_name' => 'routine_type',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/database/routines'),
                    ],
                ],
                'schemata' => [
                    'schema_name' => [
                        'link_param' => 'db',
                        'default_page' => $defaultPageDatabase,
                    ],
                ],
                'statistics' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'table_schema',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                    'column_name' => [
                        'link_param' => 'field',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'table_schema',
                            ],
                            1 => [
                                'param_info' => 'table',
                                'column_name' => 'table_name',
                            ],
                        ],
                        'default_page' => './' . Url::getFromRoute('/table/structure/change', ['change_column' => 1]),
                    ],
                ],
                'tables' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'table_schema',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                ],
                'table_constraints' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'table_schema',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                ],
                'views' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'table_schema',
                            ],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                ],
            ],
        ];
    }
}
