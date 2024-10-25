<?php

declare(strict_types=1);

return [
    // Query cache
    [
        'id' => 'Query cache disabled',
        'name' => __('Query cache disabled'),
        'formula' => 'query_cache_size',
        'test' => 'value == 0 || query_cache_type == \'OFF\' || query_cache_type == \'0\'',
        'issue' => __('The query cache is not enabled.'),
        'recommendation' => __(
            'The query cache is known to greatly improve performance if configured correctly. Enable it by'
            . ' setting {query_cache_size} to a 2 digit MiB value and setting {query_cache_type} to \'ON\'.'
            . ' <b>Note:</b> If you are using memcached, ignore this recommendation.'
        ),
        'justification' => __('query_cache_size is set to 0 or query_cache_type is set to \'OFF\''),
    ],
    [
        'id' => 'Query cache efficiency (%)',
        /* xgettext:no-php-format */
        'name' => __('Query cache efficiency (%)'),
        'precondition' => 'Com_select + Qcache_hits > 0 && !fired(\'Query cache disabled\')',
        'formula' => 'Qcache_hits / (Com_select + Qcache_hits) * 100',
        'test' => 'value  < 20',
        'issue' => __('Query cache not running efficiently, it has a low hit rate.'),
        'recommendation' => __('Consider increasing {query_cache_limit}.'),
        'justification' => __('The current query cache hit rate of %s%% is below 20%%'),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Query Cache usage',
        'name' => __('Query Cache usage'),
        'precondition' => '!fired(\'Query cache disabled\')',
        'formula' => '100 - Qcache_free_memory / query_cache_size * 100',
        'test' => 'value < 80',
        /* xgettext:no-php-format */
        'issue' => __('Less than 80% of the query cache is being utilized.'),
        'recommendation' => __(
            'This might be caused by {query_cache_limit} being too low.'
            . ' Flushing the query cache might help as well.'
        ),
        'justification' => __(
            'The current ratio of free query cache memory to total query'
            . ' cache size is %s%%. It should be above 80%%'
        ),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Query cache fragmentation',
        'name' => __('Query cache fragmentation'),
        'precondition' => '!fired(\'Query cache disabled\')',
        'formula' => 'Qcache_free_blocks / (Qcache_total_blocks / 2) * 100',
        'test' => 'value > 20',
        'issue' => __('The query cache is considerably fragmented.'),
        'recommendation' => __(
            'Severe fragmentation is likely to (further) increase Qcache_lowmem_prunes. This might be'
            . ' caused by many Query cache low memory prunes due to {query_cache_size} being too small. For a'
            . ' immediate but short lived fix you can flush the query cache (might lock the query cache for a'
            . ' long time). Carefully adjusting {query_cache_min_res_unit} to a lower value might help too,'
            . ' e.g. you can set it to the average size of your queries in the cache using this formula:'
            . ' (query_cache_size - qcache_free_memory) / qcache_queries_in_cache'
        ),
        'justification' => __(
            'The cache is currently fragmented by %s%% , with 100%% fragmentation meaning that the query'
            . ' cache is an alternating pattern of free and used blocks. This value should be below 20%%.'
        ),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Query cache low memory prunes',
        'name' => __('Query cache low memory prunes'),
        'precondition' => 'Qcache_inserts > 0 && !fired(\'Query cache disabled\')',
        'formula' => 'Qcache_lowmem_prunes / Qcache_inserts * 100',
        'test' => 'value > 0.1',
        'issue' => __('Cached queries are removed due to low query cache memory from the query cache.'),
        'recommendation' => __(
            'You might want to increase {query_cache_size}, however keep in mind that the overhead of'
            . ' maintaining the cache is likely to increase with its size, so do this in small increments'
            . ' and monitor the results.'
        ),
        'justification' => __(
            'The ratio of removed queries to inserted queries is %s%%. The lower this value is,'
            . ' the better (This rules firing limit: 0.1%%)'
        ),
        'justification_formula' => 'round(value,1)',
    ],
    [
        'id' => 'Query cache max size',
        'name' => __('Query cache max size'),
        'precondition' => '!fired(\'Query cache disabled\')',
        'formula' => 'query_cache_size',
        'test' => 'value > 1024 * 1024 * 128',
        'issue' => __(
            'The query cache size is above 128 MiB. Big query caches may cause significant'
            . ' overhead that is required to maintain the cache.'
        ),
        'recommendation' => __(
            'Depending on your environment, it might be performance increasing to reduce this value.'
        ),
        'justification' => __('Current query cache size: %s'),
        'justification_formula' => 'ADVISOR_formatByteDown(value, 2, 2)',
    ],
    [
        'id' => 'Query cache min result size',
        'name' => __('Query cache min result size'),
        'precondition' => '!fired(\'Query cache disabled\')',
        'formula' => 'query_cache_limit',
        'test' => 'value == 1024*1024',
        'issue' => __('The max size of the result set in the query cache is the default of 1 MiB.'),
        'recommendation' => __(
            'Changing {query_cache_limit} (usually by increasing) may increase efficiency. This variable'
            . ' determines the maximum size a query result may have to be inserted into the query cache.'
            . ' If there are many query results above 1 MiB that are well cacheable (many reads, little writes)'
            . ' then increasing {query_cache_limit} will increase efficiency. Whereas in the case of many query'
            . ' results being above 1 MiB that are not very well cacheable (often invalidated due to table'
            . ' updates) increasing {query_cache_limit} might reduce efficiency.'
        ),
        'justification' => __('query_cache_limit is set to 1 MiB'),
    ],
];
