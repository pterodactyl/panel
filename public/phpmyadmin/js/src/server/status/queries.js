/**
 * @fileoverview    Javascript functions used in server status query page
 * @name            Server Status Query
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 */

/* global initTableSorter */ // js/server/status/sorter.js

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server/status/queries.js', function () {
    if (document.getElementById('serverstatusquerieschart') !== null) {
        var queryPieChart = $('#serverstatusquerieschart').data('queryPieChart');
        if (queryPieChart) {
            queryPieChart.destroy();
        }
    }
});

AJAX.registerOnload('server/status/queries.js', function () {
    // Build query statistics chart
    var cdata = [];
    try {
        if (document.getElementById('serverstatusquerieschart') !== null) {
            $.each($('#serverstatusquerieschart').data('chart'), function (key, value) {
                cdata.push([key, parseInt(value, 10)]);
            });
            $('#serverstatusquerieschart').data(
                'queryPieChart',
                Functions.createProfilingChart(
                    'serverstatusquerieschart',
                    cdata
                )
            );
        }
    } catch (exception) {
        // Could not load chart, no big deal...
    }

    initTableSorter('statustabs_queries');
});
