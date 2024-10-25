<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseForm;

use function __;

class ExportForm extends BaseForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        // phpcs:disable Squiz.Arrays.ArrayDeclaration.KeySpecified,Squiz.Arrays.ArrayDeclaration.NoKeySpecified
        return [
            'Export_defaults' => [
                'Export/method',
                ':group:' . __('Quick'),
                'Export/quick_export_onserver',
                'Export/quick_export_onserver_overwrite',
                ':group:end',
                ':group:' . __('Custom'),
                'Export/format',
                'Export/compression',
                'Export/charset',
                'Export/lock_tables',
                'Export/as_separate_files',
                'Export/asfile' => ':group',
                'Export/onserver',
                'Export/onserver_overwrite',
                ':group:end',
                'Export/file_template_table',
                'Export/file_template_database',
                'Export/file_template_server',
            ],
            'Sql' => [
                'Export/sql_include_comments' => ':group',
                'Export/sql_dates',
                'Export/sql_relation',
                'Export/sql_mime',
                ':group:end',
                'Export/sql_use_transaction',
                'Export/sql_disable_fk',
                'Export/sql_views_as_tables',
                'Export/sql_metadata',
                'Export/sql_compatibility',
                'Export/sql_structure_or_data',
                ':group:' . __('Structure'),
                'Export/sql_drop_database',
                'Export/sql_create_database',
                'Export/sql_drop_table',
                'Export/sql_create_table' => ':group',
                'Export/sql_if_not_exists',
                'Export/sql_auto_increment',
                ':group:end',
                'Export/sql_create_view' => ':group',
                'Export/sql_view_current_user',
                'Export/sql_or_replace_view',
                ':group:end',
                'Export/sql_procedure_function',
                'Export/sql_create_trigger',
                'Export/sql_backquotes',
                ':group:end',
                ':group:' . __('Data'),
                'Export/sql_delayed',
                'Export/sql_ignore',
                'Export/sql_type',
                'Export/sql_insert_syntax',
                'Export/sql_max_query_size',
                'Export/sql_hex_for_binary',
                'Export/sql_utc_time',
            ],
            'CodeGen' => ['Export/codegen_format'],
            'Csv' => [
                ':group:' . __('CSV'),
                'Export/csv_separator',
                'Export/csv_enclosed',
                'Export/csv_escaped',
                'Export/csv_terminated',
                'Export/csv_null',
                'Export/csv_removeCRLF',
                'Export/csv_columns',
                ':group:end',
                ':group:' . __('CSV for MS Excel'),
                'Export/excel_null',
                'Export/excel_removeCRLF',
                'Export/excel_columns',
                'Export/excel_edition',
            ],
            'Latex' => [
                'Export/latex_caption',
                'Export/latex_structure_or_data',
                ':group:' . __('Structure'),
                'Export/latex_structure_caption',
                'Export/latex_structure_continued_caption',
                'Export/latex_structure_label',
                'Export/latex_relation',
                'Export/latex_comments',
                'Export/latex_mime',
                ':group:end',
                ':group:' . __('Data'),
                'Export/latex_columns',
                'Export/latex_data_caption',
                'Export/latex_data_continued_caption',
                'Export/latex_data_label',
                'Export/latex_null',
            ],
            'Microsoft_Office' => [
                ':group:' . __('Microsoft Word 2000'),
                'Export/htmlword_structure_or_data',
                'Export/htmlword_null',
                'Export/htmlword_columns',
            ],
            'Open_Document' => [
                ':group:' . __('OpenDocument Spreadsheet'),
                'Export/ods_columns',
                'Export/ods_null',
                ':group:end',
                ':group:' . __('OpenDocument Text'),
                'Export/odt_structure_or_data',
                ':group:' . __('Structure'),
                'Export/odt_relation',
                'Export/odt_comments',
                'Export/odt_mime',
                ':group:end',
                ':group:' . __('Data'),
                'Export/odt_columns',
                'Export/odt_null',
            ],
            'Texy' => [
                'Export/texytext_structure_or_data',
                ':group:' . __('Data'),
                'Export/texytext_null',
                'Export/texytext_columns',
            ],
        ];

        // phpcs:enable
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return __('Export');
    }
}
