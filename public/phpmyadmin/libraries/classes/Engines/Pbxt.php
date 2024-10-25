<?php
/**
 * The PBXT storage engine
 */

declare(strict_types=1);

namespace PhpMyAdmin\Engines;

use PhpMyAdmin\Core;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Util;

use function __;
use function is_string;
use function preg_match;
use function sprintf;

/**
 * The PBXT storage engine
 */
class Pbxt extends StorageEngine
{
    /**
     * Returns array with variable names dedicated to PBXT storage engine
     *
     * @return array   variable names
     */
    public function getVariables()
    {
        return [
            'pbxt_index_cache_size' => [
                'title' => __('Index cache size'),
                'desc' => __(
                    'This is the amount of memory allocated to the'
                    . ' index cache. Default value is 32MB. The memory'
                    . ' allocated here is used only for caching index pages.'
                ),
                'type' => StorageEngine::DETAILS_TYPE_SIZE,
            ],
            'pbxt_record_cache_size' => [
                'title' => __('Record cache size'),
                'desc' => __(
                    'This is the amount of memory allocated to the'
                    . ' record cache used to cache table data. The default'
                    . ' value is 32MB. This memory is used to cache changes to'
                    . ' the handle data (.xtd) and row pointer (.xtr) files.'
                ),
                'type' => StorageEngine::DETAILS_TYPE_SIZE,
            ],
            'pbxt_log_cache_size' => [
                'title' => __('Log cache size'),
                'desc' => __(
                    'The amount of memory allocated to the'
                    . ' transaction log cache used to cache on transaction log'
                    . ' data. The default is 16MB.'
                ),
                'type' => StorageEngine::DETAILS_TYPE_SIZE,
            ],
            'pbxt_log_file_threshold' => [
                'title' => __('Log file threshold'),
                'desc' => __(
                    'The size of a transaction log before rollover,'
                    . ' and a new log is created. The default value is 16MB.'
                ),
                'type' => StorageEngine::DETAILS_TYPE_SIZE,
            ],
            'pbxt_transaction_buffer_size' => [
                'title' => __('Transaction buffer size'),
                'desc' => __(
                    'The size of the global transaction log buffer'
                    . ' (the engine allocates 2 buffers of this size).'
                    . ' The default is 1MB.'
                ),
                'type' => StorageEngine::DETAILS_TYPE_SIZE,
            ],
            'pbxt_checkpoint_frequency' => [
                'title' => __('Checkpoint frequency'),
                'desc' => __(
                    'The amount of data written to the transaction'
                    . ' log before a checkpoint is performed.'
                    . ' The default value is 24MB.'
                ),
                'type' => StorageEngine::DETAILS_TYPE_SIZE,
            ],
            'pbxt_data_log_threshold' => [
                'title' => __('Data log threshold'),
                'desc' => __(
                    'The maximum size of a data log file. The default'
                    . ' value is 64MB. PBXT can create a maximum of 32000 data'
                    . ' logs, which are used by all tables. So the value of'
                    . ' this variable can be increased to increase the total'
                    . ' amount of data that can be stored in the database.'
                ),
                'type' => StorageEngine::DETAILS_TYPE_SIZE,
            ],
            'pbxt_garbage_threshold' => [
                'title' => __('Garbage threshold'),
                'desc' => __(
                    'The percentage of garbage in a data log file'
                    . ' before it is compacted. This is a value between 1 and'
                    . ' 99. The default is 50.'
                ),
                'type' => StorageEngine::DETAILS_TYPE_NUMERIC,
            ],
            'pbxt_log_buffer_size' => [
                'title' => __('Log buffer size'),
                'desc' => __(
                    'The size of the buffer used when writing a data'
                    . ' log. The default is 256MB. The engine allocates one'
                    . ' buffer per thread, but only if the thread is required'
                    . ' to write a data log.'
                ),
                'type' => StorageEngine::DETAILS_TYPE_SIZE,
            ],
            'pbxt_data_file_grow_size' => [
                'title' => __('Data file grow size'),
                'desc' => __('The grow size of the handle data (.xtd) files.'),
                'type' => StorageEngine::DETAILS_TYPE_SIZE,
            ],
            'pbxt_row_file_grow_size' => [
                'title' => __('Row file grow size'),
                'desc' => __('The grow size of the row pointer (.xtr) files.'),
                'type' => StorageEngine::DETAILS_TYPE_SIZE,
            ],
            'pbxt_log_file_count' => [
                'title' => __('Log file count'),
                'desc' => __(
                    'This is the number of transaction log files'
                    . ' (pbxt/system/xlog*.xt) the system will maintain. If the'
                    . ' number of logs exceeds this value then old logs will be'
                    . ' deleted, otherwise they are renamed and given the next'
                    . ' highest number.'
                ),
                'type' => StorageEngine::DETAILS_TYPE_NUMERIC,
            ],
        ];
    }

    /**
     * returns the pbxt engine specific handling for
     * DETAILS_TYPE_SIZE variables.
     *
     * @param int|string $formatted_size the size expression (for example 8MB)
     *
     * @return array|null the formatted value and its unit
     */
    public function resolveTypeSize($formatted_size): ?array
    {
        if (is_string($formatted_size) && preg_match('/^[0-9]+[a-zA-Z]+$/', $formatted_size)) {
            $value = Util::extractValueFromFormattedSize($formatted_size);
        } else {
            $value = $formatted_size;
        }

        return Util::formatByteDown($value);
    }

    //--------------------

    /**
     * Get information about pages
     *
     * @return array Information about pages
     */
    public function getInfoPages()
    {
        $pages = [];
        $pages['Documentation'] = __('Documentation');

        return $pages;
    }

    //--------------------

    /**
     * Get content of documentation page
     *
     * @return string
     */
    public function getPageDocumentation()
    {
        return '<p>' . sprintf(
            __(
                'Documentation and further information about PBXT can be found on the %sPrimeBase XT Home Page%s.'
            ),
            '<a href="' . Core::linkURL('https://mariadb.com/kb/en/mariadb/about-pbxt/')
            . '" rel="noopener noreferrer" target="_blank">',
            '</a>'
        )
        . '</p>' . "\n";
    }
}
