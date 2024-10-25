
$(function () {
    Functions.checkNumberOfFields();
});

/**
 * Holds common parameters such as server, db, table, etc
 *
 * The content for this is normally loaded from Header.php or
 * Response.php and executed by ajax.js
 *
 * @test-module CommonParams
 */
var CommonParams = (function () {
    /**
     * @var {Object} params An associative array of key value pairs
     * @access private
     */
    var params = {};
    // The returned object is the public part of the module
    return {
        /**
         * Saves all the key value pair that
         * are provided in the input array
         *
         * @param obj hash The input array
         *
         * @return {void}
         */
        setAll: function (obj) {
            var updateNavigation = false;
            for (var i in obj) {
                if (params[i] !== undefined && params[i] !== obj[i]) {
                    if (i === 'db' || i === 'table') {
                        updateNavigation = true;
                    }
                }
                params[i] = obj[i];
            }
            if (updateNavigation &&
                    $('#pma_navigation_tree').hasClass('synced')
            ) {
                Navigation.showCurrent();
            }
        },
        /**
         * Retrieves a value given its key
         * Returns empty string for undefined values
         *
         * @param {string} name The key
         *
         * @return {string}
         */
        get: function (name) {
            return params[name];
        },
        /**
         * Saves a single key value pair
         *
         * @param {string} name  The key
         * @param {string} value The value
         *
         * @return {CommonParams} For chainability
         */
        set: function (name, value) {
            var updateNavigation = false;
            if (name === 'db' || name === 'table' &&
                params[name] !== value
            ) {
                updateNavigation = true;
            }
            params[name] = value;
            if (updateNavigation &&
                    $('#pma_navigation_tree').hasClass('synced')
            ) {
                Navigation.showCurrent();
            }
            return this;
        },
        /**
         * Returns the url query string using the saved parameters
         *
         * @param {string} separator New separator
         *
         * @return {string}
         */
        getUrlQuery: function (separator) {
            var sep = (typeof separator !== 'undefined') ? separator : '?';
            var common = this.get('common_query');
            var argsep = CommonParams.get('arg_separator');
            if (typeof common === 'string' && common.length > 0) {
                // If the last char is the separator, do not add it
                // Else add it
                common = common.substr(common.length - 1, common.length) === argsep ? common : common + argsep;
            }

            return Functions.sprintf(
                '%s%sserver=%s' + argsep + 'db=%s' + argsep + 'table=%s',
                sep,
                common,
                encodeURIComponent(this.get('server')),
                encodeURIComponent(this.get('db')),
                encodeURIComponent(this.get('table'))
            );
        }
    };
}());

/**
 * Holds common parameters such as server, db, table, etc
 *
 * The content for this is normally loaded from Header.php or
 * Response.php and executed by ajax.js
 */
// eslint-disable-next-line no-unused-vars
var CommonActions = {
    /**
     * Saves the database name when it's changed
     * and reloads the query window, if necessary
     *
     * @param {string} newDb new_db The name of the new database
     *
     * @return {void}
     */
    setDb: function (newDb) {
        if (newDb !== CommonParams.get('db')) {
            CommonParams.setAll({ 'db': newDb, 'table': '' });
        }
    },
    /**
     * Opens a database in the main part of the page
     *
     * @param {string} newDb The name of the new database
     *
     * @return {void}
     */
    openDb: function (newDb) {
        CommonParams
            .set('db', newDb)
            .set('table', '');
        this.refreshMain(
            CommonParams.get('opendb_url')
        );
    },
    /**
     * Refreshes the main frame
     *
     * @param {any} url Undefined to refresh to the same page
     *                  String to go to a different page, e.g: 'index.php'
     * @param {function | undefined} callback
     *
     * @return {void}
     */
    refreshMain: function (url, callback = undefined) {
        var newUrl = url;
        if (! newUrl) {
            newUrl = $('#selflink').find('a').attr('href') || window.location.pathname;
            newUrl = newUrl.substring(0, newUrl.indexOf('?'));
        }
        if (newUrl.indexOf('?') !== -1) {
            newUrl += CommonParams.getUrlQuery(CommonParams.get('arg_separator'));
        } else {
            newUrl += CommonParams.getUrlQuery('?');
        }
        $('<a></a>', { href: newUrl })
            .appendTo('body')
            .trigger('click')
            .remove();
        if (typeof callback !== 'undefined') {
            AJAX.callback = callback;
        }
    }
};
