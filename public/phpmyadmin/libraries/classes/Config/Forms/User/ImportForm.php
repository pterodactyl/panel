<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseForm;

use function __;

class ImportForm extends BaseForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        return [
            'Import_defaults' => [
                'Import/format',
                'Import/charset',
                'Import/allow_interrupt',
                'Import/skip_queries',
                'enable_drag_drop_import',
            ],
            'Sql' => [
                'Import/sql_compatibility',
                'Import/sql_no_auto_value_on_zero',
                'Import/sql_read_as_multibytes',
            ],
            'Csv' => [
                ':group:' . __('CSV'),
                'Import/csv_replace',
                'Import/csv_ignore',
                'Import/csv_terminated',
                'Import/csv_enclosed',
                'Import/csv_escaped',
                'Import/csv_col_names',
                ':group:end',
                ':group:' . __('CSV using LOAD DATA'),
                'Import/ldi_replace',
                'Import/ldi_ignore',
                'Import/ldi_terminated',
                'Import/ldi_enclosed',
                'Import/ldi_escaped',
                'Import/ldi_local_option',
            ],
            'Open_Document' => [
                ':group:' . __('OpenDocument Spreadsheet'),
                'Import/ods_col_names',
                'Import/ods_empty_rows',
                'Import/ods_recognize_percentages',
                'Import/ods_recognize_currency',
            ],

        ];
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return __('Import');
    }
}
