<?php
/**
 * Internal relations for information schema and mysql databases.
 */

declare(strict_types=1);

namespace PhpMyAdmin;

/**
 * Internal relations for information schema and mysql databases.
 */
class InternalRelations
{
    /** @var array */
    private static $informationSchema = [
        'CHARACTER_SETS' => [
            'DEFAULT_COLLATE_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
        ],
        'COLLATIONS' => [
            'CHARACTER_SET_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'CHARACTER_SETS',
                'foreign_field' => 'CHARACTER_SET_NAME',
            ],
        ],
        'COLLATION_CHARACTER_SET_APPLICABILITY' => [
            'CHARACTER_SET_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'CHARACTER_SETS',
                'foreign_field' => 'CHARACTER_SET_NAME',
            ],
            'COLLATION_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
        ],
        'COLUMNS' => [
            'TABLE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'CHARACTER_SET_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'CHARACTER_SETS',
                'foreign_field' => 'CHARACTER_SET_NAME',
            ],
            'COLLATION_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
        ],
        'COLUMN_PRIVILEGES' => [
            'TABLE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'EVENTS' => [
            'EVENT_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'CHARACTER_SET_CLIENT' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'CHARACTER_SETS',
                'foreign_field' => 'CHARACTER_SET_NAME',
            ],
            'COLLATION_CONNECTION' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
            'DATABASE_COLLATION' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
        ],
        'FILES' => [
            'TABLESPACE_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'TABLESPACES',
                'foreign_field' => 'TABLESPACE_NAME',
            ],
            'TABLE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'COLLATION_CONNECTION' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
            'ENGINE' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'ENGINES',
                'foreign_field' => 'ENGINE',
            ],
        ],
        'KEY_COLUMN_USAGE' => [
            'CONSTRAINT_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'TABLE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'REFERENCED_TABLE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'PARAMETERS' => [
            'SPECIFIC_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'CHARACTER_SET_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'CHARACTER_SETS',
                'foreign_field' => 'CHARACTER_SET_NAME',
            ],
            'COLLATION_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
        ],
        'PARTITIONS' => [
            'TABLE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'TABLESPACE_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'TABLESPACES',
                'foreign_field' => 'TABLESPACE_NAME',
            ],
        ],
        'PROCESSLIST' => [
            'DB' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'REFERENTIAL_CONSTRAINTS' => [
            'CONSTRAINT_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'UNIQUE_CONSTRAINT_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'ROUTINES' => [
            'ROUTINE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'CHARACTER_SET_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'CHARACTER_SETS',
                'foreign_field' => 'CHARACTER_SET_NAME',
            ],
            'COLLATION_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
            'CHARACTER_SET_CLIENT' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'CHARACTER_SETS',
                'foreign_field' => 'CHARACTER_SET_NAME',
            ],
            'COLLATION_CONNECTION' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
            'DATABASE_COLLATION' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
        ],
        'SCHEMATA' => [
            'DEFAULT_CHARACTER_SET_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'CHARACTER_SETS',
                'foreign_field' => 'CHARACTER_SET_NAME',
            ],
            'DEFAULT_COLLATION_NAME' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
        ],
        'SCHEMA_PRIVILEGES' => [
            'TABLE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'STATISTICS' => [
            'TABLE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'INDEX_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'TABLES' => [
            'TABLE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'TABLE_COLLATION' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
            'ENGINE' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'ENGINES',
                'foreign_field' => 'ENGINE',
            ],
        ],
        'TABLESAPCES' => [
            'ENGINE' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'ENGINES',
                'foreign_field' => 'ENGINE',
            ],
        ],
        'TABLE_CONSTRAINTS' => [
            'CONSTRAINT_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'TABLE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'TABLE_PRIVILEGES' => [
            'TABLE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'TRIGGERS' => [
            'TRIGGER_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'EVENT_OBJECT_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'CHARACTER_SET_CLIENT' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'CHARACTER_SETS',
                'foreign_field' => 'CHARACTER_SET_NAME',
            ],
            'COLLATION_CONNECTION' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
            'DATABASE_COLLATION' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
        ],
        'VIEWS' => [
            'TABLE_SCHEMA' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'CHARACTER_SET_CLIENT' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'CHARACTER_SETS',
                'foreign_field' => 'CHARACTER_SET_NAME',
            ],
            'COLLATION_CONNECTION' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
        ],
    ];

    /** @var array */
    private static $mysql = [
        'columns_priv' => [
            'Db' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'db' => [
            'Db' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'event' => [
            'db' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'character_set_client' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'CHARACTER_SETS',
                'foreign_field' => 'CHARACTER_SET_NAME',
            ],
            'collation_connection' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
            'db_collation' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
        ],
        'help_category' => [
            'parent_category_id' => [
                'foreign_db' => 'mysql',
                'foreign_table' => 'help_category',
                'foreign_field' => 'help_category_id',
            ],
        ],
        'help_relation' => [
            'help_topic_id' => [
                'foreign_db' => 'mysql',
                'foreign_table' => 'help_topic',
                'foreign_field' => 'help_topic_id',
            ],
            'help_keyword_id' => [
                'foreign_db' => 'mysql',
                'foreign_table' => 'help_keyword',
                'foreign_field' => 'help_keyword_id',
            ],
        ],
        'help_topic' => [
            'help_category_id' => [
                'foreign_db' => 'mysql',
                'foreign_table' => 'help_category',
                'foreign_field' => 'help_category_id',
            ],
        ],
        'innodb_index_stats' => [
            'database_name' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'innodb_table_stats' => [
            'database_name' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'proc' => [
            'db' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
            'character_set_client' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'CHARACTER_SETS',
                'foreign_field' => 'CHARACTER_SET_NAME',
            ],
            'collation_connection' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
            'db_collation' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'COLLATIONS',
                'foreign_field' => 'COLLATION_NAME',
            ],
        ],
        'proc_priv' => [
            'Db' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'servers' => [
            'Db' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'slow_log' => [
            'db' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'tables_priv' => [
            'Db' => [
                'foreign_db' => 'information_schema',
                'foreign_table' => 'SCHEMATA',
                'foreign_field' => 'SCHEMA_NAME',
            ],
        ],
        'time_zone_name' => [
            'Time_zone_id' => [
                'foreign_db' => 'mysql',
                'foreign_table' => 'time_zone',
                'foreign_field' => 'Time_zone_id',
            ],
        ],
        'time_zone_transition' => [
            'Time_zone_id' => [
                'foreign_db' => 'mysql',
                'foreign_table' => 'time_zone',
                'foreign_field' => 'Time_zone_id',
            ],
            'Transition_time' => [
                'foreign_db' => 'mysql',
                'foreign_table' => 'time_zone_leap_second',
                'foreign_field' => 'Transition_time',
            ],
        ],
        'time_zone_transition_type' => [
            'Time_zone_id' => [
                'foreign_db' => 'mysql',
                'foreign_table' => 'time_zone',
                'foreign_field' => 'Time_zone_id',
            ],
        ],
    ];

    /**
     * @return array
     */
    public static function getInformationSchema(): array
    {
        return self::$informationSchema;
    }

    /**
     * @return array
     */
    public static function getMySql(): array
    {
        return self::$mysql;
    }
}
