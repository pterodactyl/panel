<?php

declare(strict_types=1);

return [
    // Queries
    [
        'id' => 'Uptime below one day',
        'name' => __('Uptime below one day'),
        'formula' => 'Uptime',
        'test' => 'value < 86400',
        'issue' => __('Uptime is less than 1 day, performance tuning may not be accurate.'),
        'recommendation' => __(
            'To have more accurate averages it is recommended to let the server run for'
            . ' longer than a day before running this analyzer'
        ),
        'justification' => __('The uptime is only %s'),
        'justification_formula' => 'ADVISOR_timespanFormat(Uptime)',
    ],
    [
        'id' => 'Questions below 1,000',
        'name' => __('Questions below 1,000'),
        'formula' => 'Questions',
        'test' => 'value < 1000',
        'issue' => __(
            'Fewer than 1,000 questions have been run against this server.'
            . ' The recommendations may not be accurate.'
        ),
        'recommendation' => __(
            'Let the server run for a longer time until it has executed a greater amount of queries.'
        ),
        'justification' => __('Current amount of Questions: %s'),
        'justification_formula' => 'Questions',
    ],
    [
        'id' => 'Percentage of slow queries',
        'name' => __('Percentage of slow queries'),
        'precondition' => 'Questions > 0',
        'formula' => 'Slow_queries / Questions * 100',
        'test' => 'value >= 5',
        'issue' => __('There is a lot of slow queries compared to the overall amount of Queries.'),
        'recommendation' => __(
            'You might want to increase {long_query_time} or optimize the queries listed in the slow query log'
        ),
        'justification' => __('The slow query rate should be below 5%%, your value is %s%%.'),
        'justification_formula' => 'round(value,2)',
    ],
    [
        'id' => 'Slow query rate',
        'name' => __('Slow query rate'),
        'precondition' => 'Questions > 0',
        'formula' => '(Slow_queries / Questions * 100) / Uptime',
        'test' => 'value * 60 * 60 > 1',
        'issue' => __('There is a high percentage of slow queries compared to the server uptime.'),
        'recommendation' => __(
            'You might want to increase {long_query_time} or optimize the queries listed in the slow query log'
        ),
        'justification' => __('You have a slow query rate of %s per hour, you should have less than 1%% per hour.'),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    [
        'id' => 'Long query time',
        'name' => __('Long query time'),
        'formula' => 'long_query_time',
        'test' => 'value >= 10',
        'issue' => __(
            '{long_query_time} is set to 10 seconds or more,'
            . ' thus only slow queries that take above 10 seconds are logged.'
        ),
        'recommendation' => __(
            'It is suggested to set {long_query_time} to a lower value, depending on your environment.'
            . ' Usually a value of 1-5 seconds is suggested.'
        ),
        'justification' => __('long_query_time is currently set to %ds.'),
        'justification_formula' => 'value',
    ],
    [
        'id' => 'Slow query logging',
        'name' => __('Slow query logging'),
        'precondition' => 'PMA_MYSQL_INT_VERSION < 50600',
        'formula' => 'log_slow_queries',
        'test' => 'value == \'OFF\'',
        'issue' => __('The slow query log is disabled.'),
        'recommendation' => __(
            'Enable slow query logging by setting {log_slow_queries} to \'ON\'.'
            . ' This will help troubleshooting badly performing queries.'
        ),
        'justification' => __('log_slow_queries is set to \'OFF\''),
    ],
    [
        'id' => 'Slow query logging',
        'name' => __('Slow query logging'),
        'precondition' => 'PMA_MYSQL_INT_VERSION >= 50600',
        'formula' => 'slow_query_log',
        'test' => 'value == \'OFF\'',
        'issue' => __('The slow query log is disabled.'),
        'recommendation' => __(
            'Enable slow query logging by setting {slow_query_log} to \'ON\'.'
            . ' This will help troubleshooting badly performing queries.'
        ),
        'justification' => __('slow_query_log is set to \'OFF\''),
    ],
    // Versions
    [
        'id' => 'Release Series',
        'name' => __('Release Series'),
        'formula' => 'version',
        'test' => 'substr(value,0,2) <= \'5.\' && substr(value,2,1) < 1',
        'issue' => __('The MySQL server version less than 5.1.'),
        'recommendation' => __(
            'You should upgrade, as MySQL 5.1 has improved performance, and MySQL 5.5 even more so.'
        ),
        'justification' => __('Current version: %s'),
        'justification_formula' => 'value',
    ],
    [
        'id' => 'Minor Version',
        'name' => __('Minor Version'),
        'precondition' => '! fired(\'Release Series\')',
        'formula' => 'version',
        'test' => 'substr(value,0,2) <= \'5.\' && substr(value,2,1) <= 1 && substr(value,4,2) < 30',
        'issue' => __('Version less than 5.1.30 (the first GA release of 5.1).'),
        'recommendation' => __(
            'You should upgrade, as recent versions of MySQL 5.1 have improved performance'
            . ' and MySQL 5.5 even more so.'
        ),
        'justification' => __('Current version: %s'),
        'justification_formula' => 'value',
    ],
    [
        'id' => 'Minor Version',
        'name' => __('Minor Version'),
        'precondition' => '! fired(\'Release Series\')',
        'formula' => 'version',
        'test' => 'substr(value,0,1) == 5 && substr(value,2,1) == 5 && substr(value,4,2) < 8',
        'issue' => __('Version less than 5.5.8 (the first GA release of 5.5).'),
        'recommendation' => __('You should upgrade, to a stable version of MySQL 5.5.'),
        'justification' => __('Current version: %s'),
        'justification_formula' => 'value',
    ],
    [
        'id' => 'Distribution',
        'name' => __('Distribution'),
        'formula' => 'version_comment',
        'test' => 'preg_match(\'/source/i\',value)',
        'issue' => __('Version is compiled from source, not a MySQL official binary.'),
        'recommendation' => __(
            'If you did not compile from source, you may be using a package modified by a distribution.'
            . ' The MySQL manual only is accurate for official MySQL binaries,'
            . ' not any package distributions (such as RedHat, Debian/Ubuntu etc).'
        ),
        'justification' => __('\'source\' found in version_comment'),
    ],
    [
        'id' => 'Distribution',
        'name' => __('Distribution'),
        'formula' => 'version_comment',
        'test' => 'preg_match(\'/percona/i\',value)',
        'issue' => __('The MySQL manual only is accurate for official MySQL binaries.'),
        'recommendation' => __(
            'Percona documentation is at <a href="https://www.percona.com/software/documentation/">'
            . 'https://www.percona.com/software/documentation/</a>'
        ),
        'justification' => __('\'percona\' found in version_comment'),
    ],
    [
        'id' => 'MySQL Architecture',
        'name' => __('MySQL Architecture'),
        'formula' => 'system_memory',
        'test' => 'value > 3072*1024 && !preg_match(\'/64/\',version_compile_machine)'
            . ' && !preg_match(\'/64/\',version_compile_os)',
        'issue' => __('MySQL is not compiled as a 64-bit package.'),
        'recommendation' => __(
            'Your memory capacity is above 3 GiB (assuming the Server is on localhost),'
            . ' so MySQL might not be able to access all of your memory.'
            . ' You might want to consider installing the 64-bit version of MySQL.'
        ),
        'justification' => __('Available memory on this host: %s'),
        'justification_formula' => 'ADVISOR_formatByteDown(value*1024, 2, 2)',
    ],
    // Query cache
    [
        'id' => 'Query caching method',
        'name' => __('Query caching method'),
        'precondition' => '!fired(\'Query cache disabled\')',
        'formula' => 'Questions / Uptime',
        'test' => 'value > 100',
        'issue' => __('Suboptimal caching method.'),
        'recommendation' => __(
            'You are using the MySQL Query cache with a fairly high traffic database.'
            . ' It might be worth considering to use '
            . '<a href="https://dev.mysql.com/doc/refman/5.6/en/ha-memcached.html">memcached</a>'
            . ' instead of the MySQL Query cache, especially if you have multiple replicas.'
        ),
        'justification' => __(
            'The query cache is enabled and the server receives %d queries per second.'
            . ' This rule fires if there is more than 100 queries per second.'
        ),
        'justification_formula' => 'round(value,1)',
    ],
    // Sorts
    [
        'id' => 'Percentage of sorts that cause temporary tables',
        'name' => __('Percentage of sorts that cause temporary tables'),
        'precondition' => 'Sort_scan + Sort_range > 0',
        'formula' => 'Sort_merge_passes / (Sort_scan + Sort_range) * 100',
        'test' => 'value > 10',
        'issue' => __('Too many sorts are causing temporary tables.'),
        'recommendation' => __(
            'Consider increasing {sort_buffer_size} and/or {read_rnd_buffer_size},'
            . ' depending on your system memory limits.'
        ),
        'justification' => __('%s%% of all sorts cause temporary tables, this value should be lower than 10%%.'),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Rate of sorts that cause temporary tables',
        'name' => __('Rate of sorts that cause temporary tables'),
        'formula' => 'Sort_merge_passes / Uptime',
        'test' => 'value * 60 * 60 > 1',
        'issue' => __('Too many sorts are causing temporary tables.'),
        'recommendation' => __(
            'Consider increasing {sort_buffer_size} and/or {read_rnd_buffer_size},'
            . ' depending on your system memory limits.'
        ),
        'justification' => __('Temporary tables average: %s, this value should be less than 1 per hour.'),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    [
        'id' => 'Sort rows',
        'name' => __('Sort rows'),
        'formula' => 'Sort_rows / Uptime',
        'test' => 'value * 60 >= 1',
        'issue' => __('There are lots of rows being sorted.'),
        'recommendation' => __(
            'While there is nothing wrong with a high amount of row sorting, you might want to'
            . ' make sure that the queries which require a lot of sorting use indexed columns in'
            . ' the ORDER BY clause, as this will result in much faster sorting.'
        ),
        'justification' => __('Sorted rows average: %s'),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    // Joins, scans
    [
        'id' => 'Rate of joins without indexes',
        'name' => __('Rate of joins without indexes'),
        'formula' => '(Select_range_check + Select_scan + Select_full_join) / Uptime',
        'test' => 'value * 60 * 60 > 1',
        'issue' => __('There are too many joins without indexes.'),
        'recommendation' => __(
            'This means that joins are doing full table scans. Adding indexes for the columns being'
            . ' used in the join conditions will greatly speed up table joins.'
        ),
        'justification' => __('Table joins average: %s, this value should be less than 1 per hour'),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    [
        'id' => 'Rate of reading first index entry',
        'name' => __('Rate of reading first index entry'),
        'formula' => 'Handler_read_first / Uptime',
        'test' => 'value * 60 * 60 > 1',
        'issue' => __('The rate of reading the first index entry is high.'),
        'recommendation' => __(
            'This usually indicates frequent full index scans. Full index scans are faster than'
            . ' table scans but require lots of CPU cycles in big tables, if those tables that have or'
            . ' had high volumes of UPDATEs and DELETEs, running \'OPTIMIZE TABLE\' might reduce the'
            . ' amount of and/or speed up full index scans. Other than that full index scans can'
            . ' only be reduced by rewriting queries.'
        ),
        'justification' => __('Index scans average: %s, this value should be less than 1 per hour'),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    [
        'id' => 'Rate of reading fixed position',
        'name' => __('Rate of reading fixed position'),
        'formula' => 'Handler_read_rnd / Uptime',
        'test' => 'value * 60 * 60 > 1',
        'issue' => __('The rate of reading data from a fixed position is high.'),
        'recommendation' => __(
            'This indicates that many queries need to sort results and/or do a full table scan,'
            . ' including join queries that do not use indexes. Add indexes where applicable.'
        ),
        'justification' => __('Rate of reading fixed position average: %s, this value should be less than 1 per hour'),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    [
        'id' => 'Rate of reading next table row',
        'name' => __('Rate of reading next table row'),
        'formula' => 'Handler_read_rnd_next / Uptime',
        'test' => 'value * 60 * 60 > 1',
        'issue' => __('The rate of reading the next table row is high.'),
        'recommendation' => __(
            'This indicates that many queries are doing full table scans. Add indexes where applicable.'
        ),
        'justification' => __('Rate of reading next table row: %s, this value should be less than 1 per hour'),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    // Temp tables
    [
        'id' => 'Different tmp_table_size and max_heap_table_size',
        'name' => __('Different tmp_table_size and max_heap_table_size'),
        'formula' => 'tmp_table_size - max_heap_table_size',
        'test' => 'value !=0',
        'issue' => __('{tmp_table_size} and {max_heap_table_size} are not the same.'),
        'recommendation' => __(
            'If you have deliberately changed one of either: The server uses the lower value of either'
            . ' to determine the maximum size of in-memory tables. So if you wish to increase the'
            . ' in-memory table limit you will have to increase the other value as well.'
        ),
        'justification' => __('Current values are tmp_table_size: %s, max_heap_table_size: %s'),
        'justification_formula' => 'ADVISOR_formatByteDown(tmp_table_size, 2, 2),'
            . ' ADVISOR_formatByteDown(max_heap_table_size, 2, 2)',
    ],
    [
        'id' => 'Percentage of temp tables on disk',
        'name' => __('Percentage of temp tables on disk'),
        'precondition' => 'Created_tmp_tables + Created_tmp_disk_tables > 0',
        'formula' => 'Created_tmp_disk_tables / (Created_tmp_tables + Created_tmp_disk_tables) * 100',
        'test' => 'value > 25',
        'issue' => __('Many temporary tables are being written to disk instead of being kept in memory.'),
        'recommendation' => __(
            'Increasing {max_heap_table_size} and {tmp_table_size} might help. However some'
            . ' temporary tables are always being written to disk, independent of the value of these variables.'
            . ' To eliminate these you will have to rewrite your queries to avoid those conditions'
            . ' (Within a temporary table: Presence of a BLOB or TEXT column or presence of a column'
            . ' bigger than 512 bytes) as mentioned in the beginning of an <a href="'
            . 'https://www.facebook.com/note.php?note_id=10150111255065841&comments'
            . '">Article by the Pythian Group</a>'
        ),
        'justification' => __(
            '%s%% of all temporary tables are being written to disk, this value should be below 25%%'
        ),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Temp disk rate',
        'name' => __('Temp disk rate'),
        'precondition' => '!fired(\'Percentage of temp tables on disk\')',
        'formula' => 'Created_tmp_disk_tables / Uptime',
        'test' => 'value * 60 * 60 > 1',
        'issue' => __('Many temporary tables are being written to disk instead of being kept in memory.'),
        'recommendation' => __(
            'Increasing {max_heap_table_size} and {tmp_table_size} might help. However some'
            . ' temporary tables are always being written to disk, independent of the value of these variables.'
            . ' To eliminate these you will have to rewrite your queries to avoid those conditions'
            . ' (Within a temporary table: Presence of a BLOB or TEXT column or presence of a column'
            . ' bigger than 512 bytes) as mentioned in the <a href="'
            . 'https://dev.mysql.com/doc/refman/8.0/en/internal-temporary-tables.html'
            . '">MySQL Documentation</a>'
        ),
        'justification' => __(
            'Rate of temporary tables being written to disk: %s, this value should be less than 1 per hour'
        ),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    // MyISAM index cache
    [
        'id' => 'MyISAM key buffer size',
        'name' => __('MyISAM key buffer size'),
        'formula' => 'key_buffer_size',
        'test' => 'value == 0',
        'issue' => __('Key buffer is not initialized. No MyISAM indexes will be cached.'),
        'recommendation' => __(
            'Set {key_buffer_size} depending on the size of your MyISAM indexes. 64M is a good start.'
        ),
        'justification' => __('key_buffer_size is 0'),
    ],
    [
        'id' => 'Max % MyISAM key buffer ever used',
        /* xgettext:no-php-format */
        'name' => __('Max % MyISAM key buffer ever used'),
        'precondition' => 'key_buffer_size > 0',
        'formula' => 'Key_blocks_used * key_cache_block_size / key_buffer_size * 100',
        'test' => 'value < 95',
        /* xgettext:no-php-format */
        'issue' => __('MyISAM key buffer (index cache) % used is low.'),
        'recommendation' => __(
            'You may need to decrease the size of {key_buffer_size}, re-examine your tables to see'
            . ' if indexes have been removed, or examine queries and expectations'
            . ' about what indexes are being used.'
        ),
        'justification' => __('max %% MyISAM key buffer ever used: %s%%, this value should be above 95%%'),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Percentage of MyISAM key buffer used',
        'name' => __('Percentage of MyISAM key buffer used'),
        // Don't fire if above rule fired - we don't need the same advice twice
        'precondition' => 'key_buffer_size > 0 && !fired(\'Max % MyISAM key buffer ever used\')',
        'formula' => '( 1 - Key_blocks_unused * key_cache_block_size / key_buffer_size) * 100',
        'test' => 'value < 95',
        /* xgettext:no-php-format */
        'issue' => __('MyISAM key buffer (index cache) % used is low.'),
        'recommendation' => __(
            'You may need to decrease the size of {key_buffer_size}, re-examine your tables to see'
            . ' if indexes have been removed, or examine queries and expectations'
            . ' about what indexes are being used.'
        ),
        'justification' => __('%% MyISAM key buffer used: %s%%, this value should be above 95%%'),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Percentage of index reads from memory',
        'name' => __('Percentage of index reads from memory'),
        'precondition' => 'Key_read_requests > 0',
        'formula' => '100 - (Key_reads / Key_read_requests * 100)',
        'test' => 'value < 95',
        /* xgettext:no-php-format */
        'issue' => __('The % of indexes that use the MyISAM key buffer is low.'),
        'recommendation' => __('You may need to increase {key_buffer_size}.'),
        'justification' => __('Index reads from memory: %s%%, this value should be above 95%%'),
        'justification_formula' => 'round(value,1)',
    ],
    // Other caches
    [
        'id' => 'Rate of table open',
        'name' => __('Rate of table open'),
        'formula' => 'Opened_tables / Uptime',
        'test' => 'value*60*60 > 10',
        'issue' => __('The rate of opening tables is high.'),
        'recommendation' => __(
            'Opening tables requires disk I/O which is costly. Increasing {table_open_cache} might avoid this.'
        ),
        'justification' => __('Opened table rate: %s, this value should be less than 10 per hour'),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    [
        'id' => 'Percentage of used open files limit',
        'name' => __('Percentage of used open files limit'),
        'formula' => 'Open_files / open_files_limit * 100',
        'test' => 'value > 85',
        'issue' => __(
            'The number of open files is approaching the max number of open files.'
            . ' You may get a "Too many open files" error.'
        ),
        'recommendation' => __(
            'Consider increasing {open_files_limit}, and check the error log when'
            . ' restarting after changing {open_files_limit}.'
        ),
        'justification' => __('The number of opened files is at %s%% of the limit. It should be below 85%%'),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Rate of open files',
        'name' => __('Rate of open files'),
        'formula' => 'Open_files / Uptime',
        'test' => 'value * 60 * 60 > 5',
        'issue' => __('The rate of opening files is high.'),
        'recommendation' => __(
            'Consider increasing {open_files_limit}, and check the error log when'
            . ' restarting after changing {open_files_limit}.'
        ),
        'justification' => __('Opened files rate: %s, this value should be less than 5 per hour'),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    [
        'id' => 'Immediate table locks %',
        /* xgettext:no-php-format */
        'name' => __('Immediate table locks %'),
        'precondition' => 'Table_locks_waited + Table_locks_immediate > 0',
        'formula' => 'Table_locks_immediate / (Table_locks_waited + Table_locks_immediate) * 100',
        'test' => 'value < 95',
        'issue' => __('Too many table locks were not granted immediately.'),
        'recommendation' => __('Optimize queries and/or use InnoDB to reduce lock wait.'),
        'justification' => __('Immediate table locks: %s%%, this value should be above 95%%'),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Table lock wait rate',
        'name' => __('Table lock wait rate'),
        'formula' => 'Table_locks_waited / Uptime',
        'test' => 'value * 60 * 60 > 1',
        'issue' => __('Too many table locks were not granted immediately.'),
        'recommendation' => __('Optimize queries and/or use InnoDB to reduce lock wait.'),
        'justification' => __('Table lock wait rate: %s, this value should be less than 1 per hour'),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    [
        'id' => 'Thread cache',
        'name' => __('Thread cache'),
        'formula' => 'thread_cache_size',
        'test' => 'value < 1',
        'issue' => __('Thread cache is disabled, resulting in more overhead from new connections to MySQL.'),
        'recommendation' => __('Enable the thread cache by setting {thread_cache_size} > 0.'),
        'justification' => __('The thread cache is set to 0'),
    ],
    [
        'id' => 'Thread cache hit rate %',
        /* xgettext:no-php-format */
        'name' => __('Thread cache hit rate %'),
        'precondition' => 'thread_cache_size > 0',
        'formula' => '100 - Threads_created / Connections',
        'test' => 'value < 80',
        'issue' => __('Thread cache is not efficient.'),
        'recommendation' => __('Increase {thread_cache_size}.'),
        'justification' => __('Thread cache hitrate: %s%%, this value should be above 80%%'),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Threads that are slow to launch',
        'name' => __('Threads that are slow to launch'),
        'precondition' => 'slow_launch_time > 0',
        'formula' => 'Slow_launch_threads',
        'test' => 'value > 0',
        'issue' => __('There are too many threads that are slow to launch.'),
        'recommendation' => __(
            'This generally happens in case of general system overload as it is pretty simple'
            . ' operations. You might want to monitor your system load carefully.'
        ),
        'justification' => __('%s thread(s) took longer than %s seconds to start, it should be 0'),
        'justification_formula' => 'value, slow_launch_time',
    ],
    [
        'id' => 'Slow launch time',
        'name' => __('Slow launch time'),
        'formula' => 'slow_launch_time',
        'test' => 'value > 2',
        'issue' => __('Slow_launch_time is above 2s.'),
        'recommendation' => __(
            'Set {slow_launch_time} to 1s or 2s to correctly count threads that are slow to launch.'
        ),
        'justification' => __('slow_launch_time is set to %s'),
        'justification_formula' => 'value',
    ],
    // Connections
    [
        'id' => 'Percentage of used connections',
        'name' => __('Percentage of used connections'),
        'formula' => 'Max_used_connections / max_connections * 100',
        'test' => 'value > 80',
        'issue' => __('The maximum amount of used connections is getting close to the value of {max_connections}.'),
        'recommendation' => __(
            'Increase {max_connections}, or decrease {wait_timeout} so that connections that do not'
            . ' close database handlers properly get killed sooner.'
            . ' Make sure the code closes database handlers properly.'
        ),
        'justification' => __('Max_used_connections is at %s%% of max_connections, it should be below 80%%'),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Percentage of aborted connections',
        'name' => __('Percentage of aborted connections'),
        'formula' => 'Aborted_connects / Connections * 100',
        'test' => 'value > 1',
        'issue' => __('Too many connections are aborted.'),
        'recommendation' => __(
            'Connections are usually aborted when they cannot be authorized. <a href="'
            . 'https://www.percona.com/blog/2008/08/23/how-to-track-down-the-source-of-aborted_connects/'
            . '">This article</a> might help you track down the source.'
        ),
        'justification' => __('%s%% of all connections are aborted. This value should be below 1%%'),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Rate of aborted connections',
        'name' => __('Rate of aborted connections'),
        'formula' => 'Aborted_connects / Uptime',
        'test' => 'value * 60 * 60 > 1',
        'issue' => __('Too many connections are aborted.'),
        'recommendation' => __(
            'Connections are usually aborted when they cannot be authorized. <a href="'
            . 'https://www.percona.com/blog/2008/08/23/how-to-track-down-the-source-of-aborted_connects/'
            . '">This article</a> might help you track down the source.'
        ),
        'justification' => __('Aborted connections rate is at %s, this value should be less than 1 per hour'),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    [
        'id' => 'Percentage of aborted clients',
        'name' => __('Percentage of aborted clients'),
        'formula' => 'Aborted_clients / Connections * 100',
        'test' => 'value > 2',
        'issue' => __('Too many clients are aborted.'),
        'recommendation' => __(
            'Clients are usually aborted when they did not close their connection to MySQL properly.'
            . ' This can be due to network issues or code not closing a database handler properly.'
            . ' Check your network and code.'
        ),
        'justification' => __('%s%% of all clients are aborted. This value should be below 2%%'),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Rate of aborted clients',
        'name' => __('Rate of aborted clients'),
        'formula' => 'Aborted_clients / Uptime',
        'test' => 'value * 60 * 60 > 1',
        'issue' => __('Too many clients are aborted.'),
        'recommendation' => __(
            'Clients are usually aborted when they did not close their connection to MySQL properly.'
            . ' This can be due to network issues or code not closing a database handler properly.'
            . ' Check your network and code.'
        ),
        'justification' => __('Aborted client rate is at %s, this value should be less than 1 per hour'),
        'justification_formula' => 'ADVISOR_bytime(value,2)',
    ],
    // InnoDB
    [
        'id' => 'Is InnoDB disabled?',
        'name' => __('Is InnoDB disabled?'),
        'precondition' => 'PMA_MYSQL_INT_VERSION < 50600',
        'formula' => 'have_innodb',
        'test' => 'value != "YES"',
        'issue' => __('You do not have InnoDB enabled.'),
        'recommendation' => __('InnoDB is usually the better choice for table engines.'),
        'justification' => __('have_innodb is set to \'value\''),
    ],
    [
        'id' => 'InnoDB log size',
        'name' => __('InnoDB log size'),
        'precondition' => 'innodb_buffer_pool_size > 0 && ! (IS_MARIADB && PMA_MYSQL_INT_VERSION > 100500)',
        'formula' => '(innodb_log_file_size * innodb_log_files_in_group)/ innodb_buffer_pool_size * 100',
        'test' => 'value < 20 && innodb_log_file_size / (1024 * 1024) < 256',
        'issue' => __('The InnoDB log file size is not an appropriate size, in relation to the InnoDB buffer pool.'),
        'recommendation' => __(/* xgettext:no-php-format */
            'Especially on a system with a lot of writes to InnoDB tables you should set'
            . ' {innodb_log_file_size} to 25% of {innodb_buffer_pool_size}. However the bigger this value,'
            . ' the longer the recovery time will be when database crashes, so this value should not be set'
            . ' much higher than 256 MiB. Please note however that you cannot simply change the value of'
            . ' this variable. You need to shutdown the server, remove the InnoDB log files, set the new'
            . ' value in my.cnf, start the server, then check the error logs if everything went fine.'
            . ' See also <a href="'
            . 'https://mysqldatabaseadministration.blogspot.com/2007/01/increase-innodblogfilesize-proper-way.html'
            . '">this blog entry</a>'
        ),
        'justification' => __(
            'Your InnoDB log size is at %s%% in relation to the InnoDB buffer pool size,'
            . ' it should not be below 20%%'
        ),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'InnoDB log size',
        'name' => __('InnoDB log size'),
        'precondition' => 'innodb_buffer_pool_size > 0 && IS_MARIADB && PMA_MYSQL_INT_VERSION > 100500',
        // From MariaDB 10.5, there is 1 redo log.
        // For MariaDB 10.4 and before, the number of redo log files is configured
        // by the innodb_log_files_in_group system variable.
        'formula' => 'innodb_log_file_size / innodb_buffer_pool_size * 100',
        'test' => 'value < 20 && innodb_log_file_size / (1024 * 1024) < 256',
        'issue' => __('The InnoDB log file size is not an appropriate size, in relation to the InnoDB buffer pool.'),
        'recommendation' => __(/* xgettext:no-php-format */
            'Especially on a system with a lot of writes to InnoDB tables you should set'
            . ' {innodb_log_file_size} to 25% of {innodb_buffer_pool_size}. However the bigger this value,'
            . ' the longer the recovery time will be when database crashes, so this value should not be set'
            . ' much higher than 256 MiB. Please note however that you cannot simply change the value of'
            . ' this variable. You need to shutdown the server, remove the InnoDB log files, set the new'
            . ' value in my.cnf, start the server, then check the error logs if everything went fine.'
            . ' See also <a href="'
            . 'https://mysqldatabaseadministration.blogspot.com/2007/01/increase-innodblogfilesize-proper-way.html'
            . '">this blog entry</a>'
        ),
        'justification' => __(
            'Your InnoDB log size is at %s%% in relation to the InnoDB buffer pool size,'
            . ' it should not be below 20%%'
        ),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Max InnoDB log size',
        'name' => __('Max InnoDB log size'),
        'precondition' => 'innodb_buffer_pool_size > 0 && innodb_log_file_size / innodb_buffer_pool_size * 100 < 30',
        'formula' => 'innodb_log_file_size / (1024 * 1024)',
        'test' => 'value > 256',
        'issue' => __('The InnoDB log file size is inadequately large.'),
        'recommendation' => __(/* xgettext:no-php-format */
            'It is usually sufficient to set {innodb_log_file_size} to 25% of the size of'
            . ' {innodb_buffer_pool_size}. A very big {innodb_log_file_size} slows down the recovery'
            . ' time after a database crash considerably. See also '
            . '<a href="https://www.percona.com/blog/2006/07/03/choosing-proper-innodb_log_file_size/">'
            . 'this Article</a>. You need to shutdown the server, remove the InnoDB log files, set the'
            . ' new value in my.cnf, start the server, then check the error logs'
            . ' if everything went fine. See also <a href="'
            . 'https://mysqldatabaseadministration.blogspot.com/2007/01/increase-innodblogfilesize-proper-way.html'
            . '">this blog entry</a>'
        ),
        'justification' => __('Your absolute InnoDB log size is %s MiB'),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'InnoDB buffer pool size',
        'name' => __('InnoDB buffer pool size'),
        'precondition' => 'system_memory > 0',
        'formula' => 'innodb_buffer_pool_size / system_memory * 100',
        'test' => 'value < 60',
        'issue' => __('Your InnoDB buffer pool is fairly small.'),
        'recommendation' => __(/* xgettext:no-php-format */
            'The InnoDB buffer pool has a profound impact on performance for InnoDB tables.'
            . ' Assign all your remaining memory to this buffer. For database servers that use solely InnoDB'
            . ' as storage engine and have no other services (e.g. a web server) running, you may set this'
            . ' as high as 80% of your available memory. If that is not the case, you need to carefully'
            . ' assess the memory consumption of your other services and non-InnoDB-Tables and set this'
            . ' variable accordingly. If it is set too high, your system will start swapping,'
            . ' which decreases performance significantly. See also '
            . '<a href="https://www.percona.com/blog/2007/11/03/choosing-innodb_buffer_pool_size/">this article</a>'
        ),
        'justification' => __(
            'You are currently using %s%% of your memory for the InnoDB buffer pool.'
            . ' This rule fires if you are assigning less than 60%%, however this might be perfectly'
            . ' adequate for your system if you don\'t have much InnoDB tables'
            . ' or other services running on the same machine.'
        ),
        'justification_formula' => 'value',
    ],
    // Other
    [
        'id' => 'MyISAM concurrent inserts',
        'name' => __('MyISAM concurrent inserts'),
        'formula' => 'concurrent_insert',
        'test' => 'value === 0 || value === \'NEVER\'',
        'issue' => __('Enable {concurrent_insert} by setting it to 1'),
        'recommendation' => __(
            'Setting {concurrent_insert} to 1 reduces contention between'
            . ' readers and writers for a given table. See also '
            . '<a href="https://dev.mysql.com/doc/refman/5.5/en/concurrent-inserts.html">MySQL Documentation</a>'
        ),
        'justification' => __('concurrent_insert is set to 0'),
    ],
];
