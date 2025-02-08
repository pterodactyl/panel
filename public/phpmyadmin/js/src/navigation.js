/**
 * function used in or for navigation panel
 *
 * @package phpMyAdmin-Navigation
 */

/* global isStorageSupported, setupRestoreField, setupValidation */ // js/config.js

var Navigation = {};

/**
 * updates the tree state in sessionStorage
 *
 * @return {void}
 */
Navigation.treeStateUpdate = function () {
    // update if session storage is supported
    if (isStorageSupported('sessionStorage')) {
        var storage = window.sessionStorage;
        // try catch necessary here to detect whether
        // content to be stored exceeds storage capacity
        try {
            storage.setItem('navTreePaths', JSON.stringify(Navigation.traverseForPaths()));
            storage.setItem('server', CommonParams.get('server'));
            storage.setItem('token', CommonParams.get('token'));
        } catch (error) {
            // storage capacity exceeded & old navigation tree
            // state is no more valid, so remove it
            storage.removeItem('navTreePaths');
            storage.removeItem('server');
            storage.removeItem('token');
        }
    }
};

/**
 * updates the filter state in sessionStorage
 *
 * @param {string} filterName
 * @param {string} filterValue
 *
 * @return {void}
 */
Navigation.filterStateUpdate = function (filterName, filterValue) {
    if (isStorageSupported('sessionStorage')) {
        var storage = window.sessionStorage;
        try {
            var currentFilter = $.extend({}, JSON.parse(storage.getItem('navTreeSearchFilters')));
            var filter = {};
            filter[filterName] = filterValue;
            currentFilter = $.extend(currentFilter, filter);
            storage.setItem('navTreeSearchFilters', JSON.stringify(currentFilter));
        } catch (error) {
            storage.removeItem('navTreeSearchFilters');
        }
    }
};

/**
 * restores the filter state on navigation reload
 *
 * @return {void}
 */
Navigation.filterStateRestore = function () {
    if (isStorageSupported('sessionStorage')
        && typeof window.sessionStorage.navTreeSearchFilters !== 'undefined'
    ) {
        var searchClauses = JSON.parse(window.sessionStorage.navTreeSearchFilters);
        if (Object.keys(searchClauses).length < 1) {
            return;
        }
        // restore database filter if present and not empty
        if (searchClauses.hasOwnProperty('dbFilter')
            && searchClauses.dbFilter.length
        ) {
            var $obj = $('#pma_navigation_tree');
            if (! $obj.data('fastFilter')) {
                $obj.data(
                    'fastFilter',
                    new Navigation.FastFilter.Filter($obj, '')
                );
            }
            $obj.find('li.fast_filter.db_fast_filter input.searchClause')
                .val(searchClauses.dbFilter)
                .trigger('keyup');
        }
        // find all table filters present in the tree
        var $tableFilters = $('#pma_navigation_tree li.database')
            .children('div.list_container')
            .find('li.fast_filter input.searchClause');
        // restore table filters
        $tableFilters.each(function () {
            $obj = $(this).closest('div.list_container');
            // aPath associated with this filter
            var filterName = $(this).siblings('input[name=aPath]').val();
            // if this table's filter has a state stored in storage
            if (searchClauses.hasOwnProperty(filterName)
                && searchClauses[filterName].length
            ) {
                // clear state if item is not visible,
                // happens when table filter becomes invisible
                // as db filter has already been applied
                if (! $obj.is(':visible')) {
                    Navigation.filterStateUpdate(filterName, '');
                    return true;
                }
                if (! $obj.data('fastFilter')) {
                    $obj.data(
                        'fastFilter',
                        new Navigation.FastFilter.Filter($obj, '')
                    );
                }
                $(this).val(searchClauses[filterName])
                    .trigger('keyup');
            }
        });
    }
};

/**
 * Loads child items of a node and executes a given callback
 *
 * @param isNode
 * @param $expandElem expander
 * @param callback    callback function
 *
 * @return {void}
 */
Navigation.loadChildNodes = function (isNode, $expandElem, callback) {
    var $destination = null;
    var params = null;

    if (isNode) {
        if (!$expandElem.hasClass('expander')) {
            return;
        }
        $destination = $expandElem.closest('li');
        var pos2Name = $expandElem.find('span.pos2_nav');
        var pathsNav = $expandElem.find('span.paths_nav');
        params = {
            'server': CommonParams.get('server'),
            'aPath': pathsNav.attr('data-apath'),
            'vPath': pathsNav.attr('data-vpath'),
            'pos': pathsNav.attr('data-pos'),
            'pos2_name': pos2Name.attr('data-name'),
            'pos2_value': pos2Name.attr('data-value'),
            'searchClause': '',
            'searchClause2': ''
        };
        if ($expandElem.closest('ul').hasClass('search_results')) {
            params.searchClause = Navigation.FastFilter.getSearchClause();
            params.searchClause2 = Navigation.FastFilter.getSearchClause2($expandElem);
        }
    } else {
        $destination = $('#pma_navigation_tree_content');
        params = {
            'server': CommonParams.get('server'),
            'aPath': $expandElem.attr('data-apath'),
            'vPath': $expandElem.attr('data-vpath'),
            'pos': $expandElem.attr('data-pos'),
            'pos2_name': '',
            'pos2_value': '',
            'searchClause': '',
            'searchClause2': ''
        };
    }

    $.post('index.php?route=/navigation&ajax_request=1', params, function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
            $destination.find('div.list_container').remove(); // FIXME: Hack, there shouldn't be a list container there
            if (isNode) {
                $destination.append(data.message);
                $expandElem.addClass('loaded');
            } else {
                $destination.html(data.message);
                $destination.children()
                    .first()
                    .css({
                        border: '0px',
                        margin: '0em',
                        padding : '0em'
                    })
                    .slideDown('slow');
            }
            if (data.errors) {
                var $errors = $(data.errors);
                if ($errors.children().length > 0) {
                    $('#pma_errors').append(data.errors);
                }
            }
            if (callback && typeof callback === 'function') {
                callback(data);
            }
        } else if (typeof data !== 'undefined' && data.redirect_flag === '1') {
            if (window.location.href.indexOf('?') === -1) {
                window.location.href += '?session_expired=1';
            } else {
                window.location.href += CommonParams.get('arg_separator') + 'session_expired=1';
            }
            window.location.reload();
        } else {
            var $throbber = $expandElem.find('img.throbber');
            $throbber.hide();
            var $icon = $expandElem.find('img.ic_b_plus');
            $icon.show();
            Functions.ajaxShowMessage(data.error, false);
        }
    });
};

/**
 * Collapses a node in navigation tree.
 *
 * @param $expandElem expander
 *
 * @return {void}
 */
Navigation.collapseTreeNode = function ($expandElem) {
    var $children = $expandElem.closest('li').children('div.list_container');
    var $icon = $expandElem.find('img');
    if ($expandElem.hasClass('loaded')) {
        if ($icon.is('.ic_b_minus')) {
            $icon.removeClass('ic_b_minus').addClass('ic_b_plus');
            $children.slideUp('fast');
        }
    }
    $expandElem.trigger('blur');
    $children.promise().done(Navigation.treeStateUpdate);
};

/**
 * Traverse the navigation tree backwards to generate all the actual
 * and virtual paths, as well as the positions in the pagination at
 * various levels, if necessary.
 *
 * @return {object}
 */
Navigation.traverseForPaths = function () {
    var params = {
        pos: $('#pma_navigation_tree').find('div.dbselector select').val()
    };
    if ($('#navi_db_select').length) {
        return params;
    }
    var count = 0;
    $('#pma_navigation_tree').find('a.expander:visible').each(function () {
        if ($(this).find('img').is('.ic_b_minus') &&
            $(this).closest('li').find('div.list_container .ic_b_minus').length === 0
        ) {
            var pathsNav = $(this).find('span.paths_nav');
            params['n' + count + '_aPath'] = pathsNav.attr('data-apath');
            params['n' + count + '_vPath'] = pathsNav.attr('data-vpath');

            var pos2Nav = $(this).find('span.pos2_nav');

            if (pos2Nav.length === 0) {
                pos2Nav = $(this)
                    .parent()
                    .parent()
                    .find('span.pos2_nav').last();
            }

            params['n' + count + '_pos2_name'] = pos2Nav.attr('data-name');
            params['n' + count + '_pos2_value'] = pos2Nav.attr('data-value');

            var pos3Nav = $(this).find('span.pos3_nav');

            params['n' + count + '_pos3_name'] = pos3Nav.attr('data-name');
            params['n' + count + '_pos3_value'] = pos3Nav.attr('data-value');
            count++;
        }
    });
    return params;
};

/**
 * Executed on page load
 */
$(function () {
    if (! $('#pma_navigation').length) {
        // Don't bother running any code if the navigation is not even on the page
        return;
    }

    // Do not let the page reload on submitting the fast filter
    $(document).on('submit', '.fast_filter', function (event) {
        event.preventDefault();
    });

    // Fire up the resize handlers
    new Navigation.ResizeHandler();

    /**
     * opens/closes (hides/shows) tree elements
     * loads data via ajax
     */
    $(document).on('click', '#pma_navigation_tree a.expander', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        var $icon = $(this).find('img');
        if ($icon.is('.ic_b_plus')) {
            Navigation.expandTreeNode($(this));
        } else {
            Navigation.collapseTreeNode($(this));
        }
    });

    /**
     * Register event handler for click on the reload
     * navigation icon at the top of the panel
     */
    $(document).on('click', '#pma_navigation_reload', function (event) {
        event.preventDefault();

        // Find the loading symbol and show it
        var $iconThrobberSrc = $('#pma_navigation').find('.throbber');
        $iconThrobberSrc.show();
        // TODO Why is a loading symbol both hidden, and invisible?
        $iconThrobberSrc.css('visibility', '');

        // Callback to be used to hide the loading symbol when done reloading
        function hideNav () {
            $iconThrobberSrc.hide();
        }

        // Reload the navigation
        Navigation.reload(hideNav);
    });

    $(document).on('change', '#navi_db_select',  function () {
        if (! $(this).val()) {
            CommonParams.set('db', '');
            Navigation.reload();
        }
        $(this).closest('form').trigger('submit');
    });

    /**
     * Register event handler for click on the collapse all
     * navigation icon at the top of the navigation tree
     */
    $(document).on('click', '#pma_navigation_collapse', function (event) {
        event.preventDefault();
        $('#pma_navigation_tree').find('a.expander').each(function () {
            var $icon = $(this).find('img');
            if ($icon.is('.ic_b_minus')) {
                $(this).trigger('click');
            }
        });
    });

    /**
     * Register event handler to toggle
     * the 'link with main panel' icon on mouseenter.
     */
    $(document).on('mouseenter', '#pma_navigation_sync', function (event) {
        event.preventDefault();
        var synced = $('#pma_navigation_tree').hasClass('synced');
        var $img = $('#pma_navigation_sync').children('img');
        if (synced) {
            $img.removeClass('ic_s_link').addClass('ic_s_unlink');
        } else {
            $img.removeClass('ic_s_unlink').addClass('ic_s_link');
        }
    });

    /**
     * Register event handler to toggle
     * the 'link with main panel' icon on mouseout.
     */
    $(document).on('mouseout', '#pma_navigation_sync', function (event) {
        event.preventDefault();
        var synced = $('#pma_navigation_tree').hasClass('synced');
        var $img = $('#pma_navigation_sync').children('img');
        if (synced) {
            $img.removeClass('ic_s_unlink').addClass('ic_s_link');
        } else {
            $img.removeClass('ic_s_link').addClass('ic_s_unlink');
        }
    });

    /**
     * Register event handler to toggle
     * the linking with main panel behavior
     */
    $(document).on('click', '#pma_navigation_sync', function (event) {
        event.preventDefault();
        var synced = $('#pma_navigation_tree').hasClass('synced');
        var $img = $('#pma_navigation_sync').children('img');
        if (synced) {
            $img
                .removeClass('ic_s_unlink')
                .addClass('ic_s_link')
                .attr('alt', Messages.linkWithMain)
                .attr('title', Messages.linkWithMain);
            $('#pma_navigation_tree')
                .removeClass('synced')
                .find('li.selected')
                .removeClass('selected');
        } else {
            $img
                .removeClass('ic_s_link')
                .addClass('ic_s_unlink')
                .attr('alt', Messages.unlinkWithMain)
                .attr('title', Messages.unlinkWithMain);
            $('#pma_navigation_tree').addClass('synced');
            Navigation.showCurrent();
        }
    });

    /**
     * Bind all "fast filter" events
     */
    $('#pma_navigation_tree').on('click', 'li.fast_filter button.searchClauseClear', Navigation.FastFilter.events.clear);
    $('#pma_navigation_tree').on('focus', 'li.fast_filter input.searchClause', Navigation.FastFilter.events.focus);
    $('#pma_navigation_tree').on('blur', 'li.fast_filter input.searchClause', Navigation.FastFilter.events.blur);
    $('#pma_navigation_tree').on('keyup', 'li.fast_filter input.searchClause', Navigation.FastFilter.events.keyup);

    /**
     * Ajax handler for pagination
     */
    $('#pma_navigation_tree').on('click', 'div.pageselector a.ajax', function (event) {
        event.preventDefault();
        Navigation.treePagination($(this));
    });

    /**
     * Node highlighting
     */
    $('#pma_navigation_tree.highlight').on(
        'mouseover',
        'li:not(.fast_filter)',
        function () {
            if ($('li:visible', this).length === 0) {
                $(this).addClass('activePointer');
            }
        }
    );
    $('#pma_navigation_tree.highlight').on(
        'mouseout',
        'li:not(.fast_filter)',
        function () {
            $(this).removeClass('activePointer');
        }
    );

    /** New view */
    $(document).on('click', 'li.new_view a.ajax', function (event) {
        event.preventDefault();
        Functions.createViewModal($(this));
    });

    /** Hide navigation tree item */
    $(document).on('click', 'a.hideNavItem.ajax', function (event) {
        event.preventDefault();
        var argSep = CommonParams.get('arg_separator');
        var params = $(this).getPostData();
        params += argSep + 'ajax_request=true' + argSep + 'server=' + CommonParams.get('server');
        $.ajax({
            type: 'POST',
            data: params,
            url: $(this).attr('href'),
            success: function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    Navigation.reload();
                } else {
                    Functions.ajaxShowMessage(data.error);
                }
            }
        });
    });

    /** Display a dialog to choose hidden navigation items to show */
    $(document).on('click', 'a.showUnhide.ajax', function (event) {
        event.preventDefault();
        var $msg = Functions.ajaxShowMessage();
        var argSep = CommonParams.get('arg_separator');
        var params = $(this).getPostData();
        params += argSep + 'ajax_request=true';
        $.post($(this).attr('href'), params, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                Functions.ajaxRemoveMessage($msg);
                $('#unhideNavItemModal').modal('show');
                $('#unhideNavItemModal').find('.modal-body').first().html(data.message);
            } else {
                Functions.ajaxShowMessage(data.error);
            }
        });
    });

    /** Show a hidden navigation tree item */
    $(document).on('click', 'a.unhideNavItem.ajax', function (event) {
        event.preventDefault();
        var $tr = $(this).parents('tr');
        var $hiddenTableCount = $tr.parents('tbody').children().length;
        var $hideDialogBox = $tr.closest('div.ui-dialog');
        var $msg = Functions.ajaxShowMessage();
        var argSep = CommonParams.get('arg_separator');
        var params = $(this).getPostData();
        params += argSep + 'ajax_request=true' + argSep + 'server=' + CommonParams.get('server');
        $.ajax({
            type: 'POST',
            data: params,
            url: $(this).attr('href'),
            success: function (data) {
                Functions.ajaxRemoveMessage($msg);
                if (typeof data !== 'undefined' && data.success === true) {
                    $tr.remove();
                    if ($hiddenTableCount === 1) {
                        $hideDialogBox.remove();
                    }
                    Navigation.reload();
                } else {
                    Functions.ajaxShowMessage(data.error);
                }
            }
        });
    });

    // Add/Remove favorite table using Ajax.
    $(document).on('click', '.favorite_table_anchor', function (event) {
        event.preventDefault();
        var $self = $(this);
        var anchorId = $self.attr('id');
        if ($self.data('favtargetn') !== null) {
            var $dataFavTargets = $('a[data-favtargets="' + $self.data('favtargetn') + '"]');
            if ($dataFavTargets.length > 0) {
                $dataFavTargets.trigger('click');
                return;
            }
        }

        var hasLocalStorage = isStorageSupported('localStorage') &&
            typeof window.localStorage.favoriteTables !== 'undefined';
        $.ajax({
            url: $self.attr('href'),
            cache: false,
            type: 'POST',
            data: {
                'favoriteTables': hasLocalStorage ? window.localStorage.favoriteTables : '',
                'server': CommonParams.get('server'),
            },
            success: function (data) {
                if (data.changes) {
                    $('#pma_favorite_list').html(data.list);
                    $('#' + anchorId).parent().html(data.anchor);
                    Functions.tooltip(
                        $('#' + anchorId),
                        'a',
                        $('#' + anchorId).attr('title')
                    );
                    // Update localStorage.
                    if (isStorageSupported('localStorage')) {
                        window.localStorage.favoriteTables = data.favoriteTables;
                    }
                } else {
                    Functions.ajaxShowMessage(data.message);
                }
            }
        });
    });
    // Check if session storage is supported
    if (isStorageSupported('sessionStorage')) {
        var storage = window.sessionStorage;
        // remove tree from storage if Navi_panel config form is submitted
        $(document).on('submit', 'form.config-form', function () {
            storage.removeItem('navTreePaths');
        });
        // Initialize if no previous state is defined
        if ($('#pma_navigation_tree_content').length &&
            typeof storage.navTreePaths === 'undefined'
        ) {
            Navigation.reload();
        } else if (CommonParams.get('server') === storage.server &&
            CommonParams.get('token') === storage.token
        ) {
            // Reload the tree to the state before page refresh
            Navigation.reload(Navigation.filterStateRestore, JSON.parse(storage.navTreePaths));
        } else {
            // If the user is different
            Navigation.treeStateUpdate();
            Navigation.reload();
        }
    }
});

/**
 * Expands a node in navigation tree.
 *
 * @param $expandElem expander
 * @param callback    callback function
 *
 * @return {void}
 */
Navigation.expandTreeNode = function ($expandElem, callback) {
    var $children = $expandElem.closest('li').children('div.list_container');
    var $icon = $expandElem.find('img');
    if ($expandElem.hasClass('loaded')) {
        if ($icon.is('.ic_b_plus')) {
            $icon.removeClass('ic_b_plus').addClass('ic_b_minus');
            $children.slideDown('fast');
        }
        if (callback && typeof callback === 'function') {
            callback.call();
        }
        $children.promise().done(Navigation.treeStateUpdate);
    } else {
        var $throbber = $('#pma_navigation').find('.throbber')
            .first()
            .clone()
            .css({ visibility: 'visible', display: 'block' })
            .on('click', false);
        $icon.hide();
        $throbber.insertBefore($icon);

        Navigation.loadChildNodes(true, $expandElem, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                var $destination = $expandElem.closest('li');
                $icon.removeClass('ic_b_plus').addClass('ic_b_minus');
                $children = $destination.children('div.list_container');
                $children.slideDown('fast');
                if ($destination.find('ul > li').length === 1) {
                    $destination.find('ul > li')
                        .find('a.expander.container')
                        .trigger('click');
                }
                if (callback && typeof callback === 'function') {
                    callback.call();
                }
                Navigation.showFullName($destination);
            } else {
                Functions.ajaxShowMessage(data.error, false);
            }
            $icon.show();
            $throbber.remove();
            $children.promise().done(Navigation.treeStateUpdate);
        });
    }
    $expandElem.trigger('blur');
};

/**
 * Auto-scrolls the newly chosen database
 *
 * @param {object} $element    The element to set to view
 * @param {bool}   $forceToTop Whether to force scroll to top
 *
 */
Navigation.scrollToView = function ($element, $forceToTop) {
    Navigation.filterStateRestore();
    var $container = $('#pma_navigation_tree_content');
    var elemTop = $element.offset().top - $container.offset().top;
    var textHeight = 20;
    var scrollPadding = 20; // extra padding from top of bottom when scrolling to view
    if (elemTop < 0 || $forceToTop) {
        $container.stop().animate({
            scrollTop: elemTop + $container.scrollTop() - scrollPadding
        });
    } else if (elemTop + textHeight > $container.height()) {
        $container.stop().animate({
            scrollTop: elemTop + textHeight - $container.height() + $container.scrollTop() + scrollPadding
        });
    }
};

/**
 * Expand the navigation and highlight the current database or table/view
 *
 * @return {void}
 */
Navigation.showCurrent = function () {
    var db = CommonParams.get('db');
    var table = CommonParams.get('table');

    var autoexpand = $('#pma_navigation_tree').hasClass('autoexpand');

    $('#pma_navigation_tree')
        .find('li.selected')
        .removeClass('selected');
    var $dbItem;
    if (db) {
        $dbItem = findLoadedItem(
            $('#pma_navigation_tree').find('> div'), db, 'database', !table
        );
        if ($('#navi_db_select').length &&
            $('option:selected', $('#navi_db_select')).length
        ) {
            if (! Navigation.selectCurrentDatabase()) {
                return;
            }
            // If loaded database in navigation is not same as current one
            if ($('#pma_navigation_tree_content').find('span.loaded_db').first().text()
                !== $('#navi_db_select').val()
            ) {
                Navigation.loadChildNodes(false, $('option:selected', $('#navi_db_select')), function () {
                    handleTableOrDb(table, $('#pma_navigation_tree_content'));
                    var $children = $('#pma_navigation_tree_content').children('div.list_container');
                    $children.promise().done(Navigation.treeStateUpdate);
                });
            } else {
                handleTableOrDb(table, $('#pma_navigation_tree_content'));
            }
        } else if ($dbItem) {
            fullExpand(table, $dbItem);
        }
    } else if ($('#navi_db_select').length && $('#navi_db_select').val()) {
        $('#navi_db_select').val('').hide().trigger('change');
    } else if (autoexpand && $('#pma_navigation_tree_content > ul > li.database').length === 1) {
        // automatically expand the list if there is only single database

        // find the name of the database
        var dbItemName = '';

        $('#pma_navigation_tree_content > ul > li.database').children('a').each(function () {
            var name = $(this).text();
            if (!dbItemName && name.trim()) { // if the name is not empty, it is the desired element
                dbItemName = name;
            }
        });

        $dbItem = findLoadedItem(
            $('#pma_navigation_tree').find('> div'), dbItemName, 'database', !table
        );

        fullExpand(table, $dbItem);
    }
    Navigation.showFullName($('#pma_navigation_tree'));

    function fullExpand (table, $dbItem) {
        var $expander = $dbItem.children('div').first().children('a.expander');
        // if not loaded or loaded but collapsed
        if (! $expander.hasClass('loaded') ||
            $expander.find('img').is('.ic_b_plus')
        ) {
            Navigation.expandTreeNode($expander, function () {
                handleTableOrDb(table, $dbItem);
            });
        } else {
            handleTableOrDb(table, $dbItem);
        }
    }

    function handleTableOrDb (table, $dbItem) {
        if (table) {
            loadAndHighlightTableOrView($dbItem, table);
        } else {
            var $container = $dbItem.children('div.list_container');
            var $tableContainer = $container.children('ul').children('li.tableContainer');
            if ($tableContainer.length > 0) {
                var $expander = $tableContainer.children('div').first().children('a.expander');
                $tableContainer.addClass('selected');
                Navigation.expandTreeNode($expander, function () {
                    Navigation.scrollToView($dbItem, true);
                });
            } else {
                Navigation.scrollToView($dbItem, true);
            }
        }
    }

    function findLoadedItem ($container, name, clazz, doSelect) {
        var ret = false;
        $container.children('ul').children('li').each(function () {
            var $li = $(this);
            // this is a navigation group, recurse
            if ($li.is('.navGroup')) {
                var $container = $li.children('div.list_container');
                var $childRet = findLoadedItem(
                    $container, name, clazz, doSelect
                );
                if ($childRet) {
                    ret = $childRet;
                    return false;
                }
            } else { // this is a real navigation item
                // name and class matches
                if (((clazz && $li.is('.' + clazz)) || ! clazz) &&
                        $li.children('a').text() === name) {
                    if (doSelect) {
                        $li.addClass('selected');
                    }
                    // traverse up and expand and parent navigation groups
                    $li.parents('.navGroup').each(function () {
                        var $cont = $(this).children('div.list_container');
                        if (! $cont.is(':visible')) {
                            $(this)
                                .children('div').first()
                                .children('a.expander')
                                .trigger('click');
                        }
                    });
                    ret = $li;
                    return false;
                }
            }
        });
        return ret;
    }

    function loadAndHighlightTableOrView ($dbItem, itemName) {
        var $container = $dbItem.children('div.list_container');
        var $expander;
        var $whichItem = isItemInContainer($container, itemName, 'li.nav_node_table, li.view');
        // If item already there in some container
        if ($whichItem) {
            // get the relevant container while may also be a subcontainer
            var $relatedContainer = $whichItem.closest('li.subContainer').length
                ? $whichItem.closest('li.subContainer')
                : $dbItem;
            $whichItem = findLoadedItem(
                $relatedContainer.children('div.list_container'),
                itemName, null, true
            );
            // Show directly
            showTableOrView($whichItem, $relatedContainer.children('div').first().children('a.expander'));
        // else if item not there, try loading once
        } else {
            var $subContainers = $dbItem.find('.subContainer');
            // If there are subContainers i.e. tableContainer or viewContainer
            if ($subContainers.length > 0) {
                var $containers = [];
                $subContainers.each(function (index) {
                    $containers[index] = $(this);
                    $expander = $containers[index]
                        .children('div').first()
                        .children('a.expander');
                    if (! $expander.hasClass('loaded')) {
                        loadAndShowTableOrView($expander, $containers[index], itemName);
                    }
                });
            // else if no subContainers
            } else {
                $expander = $dbItem
                    .children('div').first()
                    .children('a.expander');
                if (! $expander.hasClass('loaded')) {
                    loadAndShowTableOrView($expander, $dbItem, itemName);
                }
            }
        }
    }

    function loadAndShowTableOrView ($expander, $relatedContainer, itemName) {
        Navigation.loadChildNodes(true, $expander, function () {
            var $whichItem = findLoadedItem(
                $relatedContainer.children('div.list_container'),
                itemName, null, true
            );
            if ($whichItem) {
                showTableOrView($whichItem, $expander);
            }
        });
    }

    function showTableOrView ($whichItem, $expander) {
        Navigation.expandTreeNode($expander, function () {
            if ($whichItem) {
                Navigation.scrollToView($whichItem, false);
            }
        });
    }

    function isItemInContainer ($container, name, clazz) {
        var $whichItem = null;
        var $items = $container.find(clazz);
        $items.each(function () {
            if ($(this).children('a').text() === name) {
                $whichItem = $(this);
                return false;
            }
        });
        return $whichItem;
    }
};

/**
 * Disable navigation panel settings
 *
 * @return {void}
 */
Navigation.disableSettings = function () {
    $('#pma_navigation_settings_icon').addClass('hide');
    $('#pma_navigation_settings').remove();
};

/**
 * Ensure that navigation panel settings is properly setup.
 * If not, set it up
 *
 * @param {string} selflink
 *
 * @return {void}
 */
Navigation.ensureSettings = function (selflink) {
    $('#pma_navigation_settings_icon').removeClass('hide');

    if (!$('#pma_navigation_settings').length) {
        var params = {
            getNaviSettings: true,
            server: CommonParams.get('server'),
        };
        $.post('index.php?route=/navigation&ajax_request=1', params, function (data) {
            if (typeof data !== 'undefined' && data.success) {
                $('#pma_navi_settings_container').html(data.message);
                setupRestoreField();
                setupValidation();
                $('#pma_navigation_settings').find('form').attr('action', selflink);
            } else {
                Functions.ajaxShowMessage(data.error);
            }
        });
    } else {
        $('#pma_navigation_settings').find('form').attr('action', selflink);
    }
};

/**
 * Reloads the whole navigation tree while preserving its state
 *
 * @param {Function} callback the callback function
 * @param {object} paths stored navigation paths
 *
 * @return {void}
 */
Navigation.reload = function (callback, paths) {
    var params = {
        'reload': true,
        'no_debug': true,
        'server': CommonParams.get('server'),
    };
    var pathsLocal = paths || Navigation.traverseForPaths();
    $.extend(params, pathsLocal);
    if ($('#navi_db_select').length) {
        params.db = CommonParams.get('db');
        requestNaviReload(params);
        return;
    }
    requestNaviReload(params);

    function requestNaviReload (params) {
        $.post('index.php?route=/navigation&ajax_request=1', params, function (data) {
            if (typeof data !== 'undefined' && data.success) {
                $('#pma_navigation_tree').html(data.message).children('div').show();
                if ($('#pma_navigation_tree').hasClass('synced')) {
                    Navigation.selectCurrentDatabase();
                    Navigation.showCurrent();
                }
                // Fire the callback, if any
                if (typeof callback === 'function') {
                    callback.call();
                }
                Navigation.treeStateUpdate();
            } else {
                Functions.ajaxShowMessage(data.error);
            }
        });
    }
};

Navigation.selectCurrentDatabase = function () {
    var $naviDbSelect = $('#navi_db_select');

    if (!$naviDbSelect.length) {
        return false;
    }

    if (CommonParams.get('db')) { // db selected
        $naviDbSelect.show();
    }

    $naviDbSelect.val(CommonParams.get('db'));
    return $naviDbSelect.val() === CommonParams.get('db');
};

/**
 * Handles any requests to change the page in a branch of a tree
 *
 * This can be called from link click or select change event handlers
 *
 * @param {object} $this A jQuery object that points to the element that
 * initiated the action of changing the page
 *
 * @return {void}
 */
Navigation.treePagination = function ($this) {
    var $msgbox = Functions.ajaxShowMessage();
    var isDbSelector = $this.closest('div.pageselector').is('.dbselector');
    var url = 'index.php?route=/navigation';
    var params = 'ajax_request=true';
    if ($this[0].tagName === 'A') {
        params += CommonParams.get('arg_separator') + $this.getPostData();
    } else { // tagName === 'SELECT'
        params += CommonParams.get('arg_separator') + $this.closest('form').serialize();
    }
    var searchClause = Navigation.FastFilter.getSearchClause();
    if (searchClause) {
        params += CommonParams.get('arg_separator') + 'searchClause=' + encodeURIComponent(searchClause);
    }
    if (isDbSelector) {
        params += CommonParams.get('arg_separator') + 'full=true';
    } else {
        var searchClause2 = Navigation.FastFilter.getSearchClause2($this);
        if (searchClause2) {
            params += CommonParams.get('arg_separator') + 'searchClause2=' + encodeURIComponent(searchClause2);
        }
    }
    $.post(url, params, function (data) {
        if (typeof data !== 'undefined' && data.success) {
            Functions.ajaxRemoveMessage($msgbox);
            var val;
            if (isDbSelector) {
                val = Navigation.FastFilter.getSearchClause();
                $('#pma_navigation_tree')
                    .html(data.message)
                    .children('div')
                    .show();
                if (val) {
                    $('#pma_navigation_tree')
                        .find('li.fast_filter input.searchClause')
                        .val(val);
                }
            } else {
                var $parent = $this.closest('div.list_container').parent();
                val = Navigation.FastFilter.getSearchClause2($this);
                $this.closest('div.list_container').html(
                    $(data.message).children().show()
                );
                if (val) {
                    $parent.find('li.fast_filter input.searchClause').val(val);
                }
                $parent.find('span.pos2_value').first().text(
                    $parent.find('span.pos2_value').last().text()
                );
                $parent.find('span.pos3_value').first().text(
                    $parent.find('span.pos3_value').last().text()
                );
            }
        } else {
            Functions.ajaxShowMessage(data.error);
            Functions.handleRedirectAndReload(data);
        }
        Navigation.treeStateUpdate();
    });
};

/**
 * ResizeHandler Custom object that manages the resizing of the navigation
 *
 * XXX: Must only be ever instanciated once
 * XXX: Inside event handlers the 'this' object is accessed as 'event.data.resize_handler'
 */
Navigation.ResizeHandler = function () {
    /**
     * @var {number} panelWidth Used by the collapser to know where to go
     *                      back to when uncollapsing the panel
     */
    this.panelWidth = 0;
    /**
     * @var {string} left Used to provide support for RTL languages
     */
    this.left = $('html').attr('dir') === 'ltr' ? 'left' : 'right';
    /**
     * Adjusts the width of the navigation panel to the specified value
     *
     * @param {number} position Navigation width in pixels
     *
     * @return {void}
     */
    this.setWidth = function (position) {
        var pos = position;
        if (typeof pos !== 'number') {
            pos = 240;
        }
        var $resizer = $('#pma_navigation_resizer');
        var resizerWidth = $resizer.width();
        var $collapser = $('#pma_navigation_collapser');
        var windowWidth = $(window).width();
        $('#pma_navigation').width(pos);
        $('body').css('margin-' + this.left, pos + 'px');
        // Issue #15127 : Adding fixed positioning to menubar
        // Issue #15570 : Panels on homescreen go underneath of floating menubar
        $('#floating_menubar')
            .css('margin-' + this.left, $('#pma_navigation').width() + $('#pma_navigation_resizer').width())
            .css(this.left, 0)
            .css({
                'position': 'fixed',
                'top': 0,
                'width': '100%',
                'z-index': 99
            })
            .append($('#server-breadcrumb'))
            .append($('#topmenucontainer'));
        // Allow the DOM to render, then adjust the padding on the body
        setTimeout(function () {
            $('body').css(
                'padding-top',
                $('#floating_menubar').outerHeight(true)
            );
        }, 2);
        $('#pma_console')
            .css('margin-' + this.left, (pos + resizerWidth) + 'px');
        $resizer.css(this.left, pos + 'px');
        if (pos === 0) {
            $collapser
                .css(this.left, pos + resizerWidth)
                .html(this.getSymbol(pos))
                .prop('title', Messages.strShowPanel);
        } else if (windowWidth > 768) {
            $collapser
                .css(this.left, pos)
                .html(this.getSymbol(pos))
                .prop('title', Messages.strHidePanel);
            $('#pma_navigation_resizer').css({ 'width': '3px' });
        } else {
            $collapser
                .css(this.left, windowWidth - 22)
                .html(this.getSymbol(100))
                .prop('title', Messages.strHidePanel);
            $('#pma_navigation').width(windowWidth);
            $('body').css('margin-' + this.left, '0px');
            $('#pma_navigation_resizer').css({ 'width': '0px' });
        }
        setTimeout(function () {
            $(window).trigger('resize');
        }, 4);
    };
    /**
     * Returns the horizontal position of the mouse,
     * relative to the outer side of the navigation panel
     *
     * @param {MouseEvent} event
     *
     * @return {number} Navigation width in pixels
     */
    this.getPos = function (event) {
        var pos = event.pageX;
        var windowWidth = $(window).width();
        var windowScroll = $(window).scrollLeft();
        pos = pos - windowScroll;
        if (this.left !== 'left') {
            pos = windowWidth - event.pageX;
        }
        if (pos < 0) {
            pos = 0;
        } else if (pos + 100 >= windowWidth) {
            pos = windowWidth - 100;
        } else {
            this.panelWidth = 0;
        }
        return pos;
    };
    /**
     * Returns the HTML code for the arrow symbol used in the collapser
     *
     * @param {number} width The width of the panel
     *
     * @return {string}
     */
    this.getSymbol = function (width) {
        if (this.left === 'left') {
            if (width === 0) {
                return '&rarr;';
            } else {
                return '&larr;';
            }
        } else {
            if (width === 0) {
                return '&larr;';
            } else {
                return '&rarr;';
            }
        }
    };
    /**
     * Event handler for initiating a resize of the panel
     *
     * @param {object} event Event data (contains a reference to Navigation.ResizeHandler)
     *
     * @return {void}
     */
    this.mousedown = function (event) {
        event.preventDefault();
        $(document)
            .on('mousemove', { 'resize_handler': event.data.resize_handler },
                $.throttle(event.data.resize_handler.mousemove, 4))
            .on('mouseup', { 'resize_handler': event.data.resize_handler },
                event.data.resize_handler.mouseup);
        $('body').css('cursor', 'col-resize');
    };
    /**
     * Event handler for terminating a resize of the panel
     *
     * @param {object} event Event data (contains a reference to Navigation.ResizeHandler)
     *
     * @return {void}
     */
    this.mouseup = function (event) {
        $('body').css('cursor', '');
        Functions.configSet('NavigationWidth', event.data.resize_handler.getPos(event));
        $('#topmenu').menuResizer('resize');
        $(document)
            .off('mousemove')
            .off('mouseup');
    };
    /**
     * Event handler for updating the panel during a resize operation
     *
     * @param {object} event Event data (contains a reference to Navigation.ResizeHandler)
     *
     * @return {void}
     */
    this.mousemove = function (event) {
        event.preventDefault();
        if (event.data && event.data.resize_handler) {
            var pos = event.data.resize_handler.getPos(event);
            event.data.resize_handler.setWidth(pos);
        }
    };
    /**
     * Event handler for collapsing the panel
     *
     * @param {object} event Event data (contains a reference to Navigation.ResizeHandler)
     *
     * @return {void}
     */
    this.collapse = function (event) {
        event.preventDefault();
        var panelWidth = event.data.resize_handler.panelWidth;
        var width = $('#pma_navigation').width();
        if (width === 0 && panelWidth === 0) {
            panelWidth = 240;
        }
        Functions.configSet('NavigationWidth', panelWidth);
        event.data.resize_handler.setWidth(panelWidth);
        event.data.resize_handler.panelWidth = width;
    };
    /**
     * Event handler for resizing the navigation tree height on window resize
     *
     * @return {void}
     */
    this.treeResize = function () {
        var $nav = $('#pma_navigation');
        var $navTree = $('#pma_navigation_tree');
        var $navHeader = $('#pma_navigation_header');
        var $navTreeContent = $('#pma_navigation_tree_content');
        var height = ($nav.height() - $navHeader.height());

        height = height > 50 ? height : 800; // keep min. height
        $navTree.height(height);
        if ($navTreeContent.length > 0) {
            $navTreeContent.height(height - $navTreeContent.position().top);
        } else {
            // TODO: in fast filter search response there is no #pma_navigation_tree_content, needs to be added in php
            $navTree.css({
                'overflow-y': 'auto'
            });
        }
        // Set content bottom space because of console
        $('body').css('margin-bottom', $('#pma_console').height() + 'px');
    };
    /**
     * Init handlers for the tree resizers
     *
     * @return {void}
     */
    this.treeInit = function () {
        const isLoadedOnMobile = $(window).width() < 768;
        // Hide the pma_navigation initially when loaded on mobile
        if (isLoadedOnMobile) {
            this.setWidth(0);
        }
        // Register the events for the resizer and the collapser
        $(document).on('mousedown', '#pma_navigation_resizer', { 'resize_handler': this }, this.mousedown);
        $(document).on('click', '#pma_navigation_collapser', { 'resize_handler': this }, this.collapse);

        // Add the correct arrow symbol to the collapser
        $('#pma_navigation_collapser').html(this.getSymbol($('#pma_navigation').width()));
        // Fix navigation tree height
        $(window).on('resize', this.treeResize);
        // need to call this now and then, browser might decide
        // to show/hide horizontal scrollbars depending on page content width
        setInterval(this.treeResize, 2000);
        this.treeResize();
        const callbackSuccessGetConfigValue = (data) => {
            this.setWidth(data);
            $('#topmenu').menuResizer('resize');
        };
        // Skip mobile
        if (isLoadedOnMobile === false) {
            // Make an init using the default found value
            const initialResizeValue = $('#pma_navigation').data('config-navigation-width');
            callbackSuccessGetConfigValue(initialResizeValue);
        }
        Functions.configGet('NavigationWidth', false, callbackSuccessGetConfigValue);
    };
    this.treeInit();
};

/**
 * @var {object} FastFilter Handles the functionality that allows filtering
 *                          of the items in a branch of the navigation tree
 */
Navigation.FastFilter = {
    /**
     * Construct for the asynchronous fast filter functionality
     *
     * @param {object} $this        A jQuery object pointing to the list container
     *                              which is the nearest parent of the fast filter
     * @param {string} searchClause The query string for the filter
     *
     * @return {void}
     */
    Filter: function ($this, searchClause) {
        /**
         * @var {object} $this A jQuery object pointing to the list container
         *                     which is the nearest parent of the fast filter
         */
        this.$this = $this;
        /**
         * @var {boolean} searchClause The query string for the filter
         */
        this.searchClause = searchClause;
        /**
         * @var {object} $clone A clone of the original contents
         *                      of the navigation branch before
         *                      the fast filter was applied
         */
        this.$clone = $this.clone();
        /**
         * @var {object} xhr A reference to the ajax request that is currently running
         * @type {JQuery.jqXHR<any> | null}
         */
        this.xhr = null;
        /**
         * @var {number} timeout Used to delay the request for asynchronous search
         */
        this.timeout = null;

        var $filterInput = $this.find('li.fast_filter input.searchClause');
        if ($filterInput.length !== 0 &&
            $filterInput.val() !== '' &&
            $filterInput.val() !== $filterInput[0].defaultValue
        ) {
            this.request();
        }
    },
    /**
     * Gets the query string from the database fast filter form
     *
     * @return {string}
     */
    getSearchClause: function () {
        var retval = '';
        var $input = $('#pma_navigation_tree')
            .find('li.fast_filter.db_fast_filter input.searchClause');
        if ($input.length && $input.val() !== $input[0].defaultValue) {
            retval = $input.val();
        }
        return retval;
    },
    /**
     * Gets the query string from a second level item's fast filter form
     * The retrieval is done by traversing the navigation tree backwards
     *
     * @param $this
     *
     * @return {string}
     */
    getSearchClause2: function ($this) {
        var $filterContainer = $this.closest('div.list_container');
        var $filterInput = $([]);
        if ($filterContainer
            .find('li.fast_filter:not(.db_fast_filter) input.searchClause')
            .length !== 0) {
            $filterInput = $filterContainer
                .find('li.fast_filter:not(.db_fast_filter) input.searchClause');
        }
        var searchClause2 = '';
        if ($filterInput.length !== 0 &&
            $filterInput.first().val() !== $filterInput[0].defaultValue
        ) {
            searchClause2 = $filterInput.val();
        }
        return searchClause2;
    },
    /**
     * @var hash events A list of functions that are bound to DOM events
     *                  at the top of this file
     */
    events: {
        focus: function () {
            var $obj = $(this).closest('div.list_container');
            if (! $obj.data('fastFilter')) {
                $obj.data(
                    'fastFilter',
                    new Navigation.FastFilter.Filter($obj, $(this).val())
                );
            }
            if ($(this).val() === this.defaultValue) {
                $(this).val('');
            } else {
                $(this).trigger('select');
            }
        },
        blur: function () {
            if ($(this).val() === '') {
                $(this).val(this.defaultValue);
            }
            var $obj = $(this).closest('div.list_container');
            if ($(this).val() === this.defaultValue && $obj.data('fastFilter')) {
                $obj.data('fastFilter').restore();
            }
        },
        keyup: function (event) {
            var $obj = $(this).closest('div.list_container');
            var str = '';
            if ($(this).val() !== this.defaultValue && $(this).val() !== '') {
                $obj.find('div.pageselector').hide();
                str = $(this).val();
            }

            /**
             * FIXME at the server level a value match is done while on
             * the client side it is a regex match. These two should be aligned
             */

            // regex used for filtering.
            var regex;
            try {
                regex = new RegExp(str, 'i');
            } catch (err) {
                return;
            }

            // this is the div that houses the items to be filtered by this filter.
            var outerContainer;
            if ($(this).closest('li.fast_filter').is('.db_fast_filter')) {
                outerContainer = $('#pma_navigation_tree_content');
            } else {
                outerContainer = $obj;
            }

            // filters items that are directly under the div as well as grouped in
            // groups. Does not filter child items (i.e. a database search does
            // not filter tables)
            var itemFilter = function ($curr) {
                $curr.children('ul').children('li.navGroup').each(function () {
                    $(this).children('div.list_container').each(function () {
                        itemFilter($(this)); // recursive
                    });
                });
                $curr.children('ul').children('li').children('a').not('.container').each(function () {
                    if (regex.test($(this).text())) {
                        $(this).parent().show().removeClass('hidden');
                    } else {
                        $(this).parent().hide().addClass('hidden');
                    }
                });
            };
            itemFilter(outerContainer);

            // hides containers that does not have any visible children
            var containerFilter = function ($curr) {
                $curr.children('ul').children('li.navGroup').each(function () {
                    var $group = $(this);
                    $group.children('div.list_container').each(function () {
                        containerFilter($(this)); // recursive
                    });
                    $group.show().removeClass('hidden');
                    if ($group.children('div.list_container').children('ul')
                        .children('li').not('.hidden').length === 0) {
                        $group.hide().addClass('hidden');
                    }
                });
            };
            containerFilter(outerContainer);

            if ($(this).val() !== this.defaultValue && $(this).val() !== '') {
                if (! $obj.data('fastFilter')) {
                    $obj.data(
                        'fastFilter',
                        new Navigation.FastFilter.Filter($obj, $(this).val())
                    );
                } else {
                    if (event.keyCode === 13) {
                        $obj.data('fastFilter').update($(this).val());
                    }
                }
            } else if ($obj.data('fastFilter')) {
                $obj.data('fastFilter').restore(true);
            }
            // update filter state
            var filterName;
            if ($(this).attr('name') === 'searchClause2') {
                filterName = $(this).siblings('input[name=aPath]').val();
            } else {
                filterName = 'dbFilter';
            }
            Navigation.filterStateUpdate(filterName, $(this).val());
        },
        clear: function (event) {
            event.stopPropagation();
            // Clear the input and apply the fast filter with empty input
            var filter = $(this).closest('div.list_container').data('fastFilter');
            if (filter) {
                filter.restore();
            }
            var value = $(this).prev()[0].defaultValue;
            $(this).prev().val(value).trigger('keyup');
        }
    }
};
/**
 * Handles a change in the search clause
 *
 * @param {string} searchClause The query string for the filter
 *
 * @return {void}
 */
Navigation.FastFilter.Filter.prototype.update = function (searchClause) {
    if (this.searchClause !== searchClause) {
        this.searchClause = searchClause;
        this.request();
    }
};
/**
 * After a delay of 250mS, initiates a request to retrieve search results
 * Multiple calls to this function will always abort the previous request
 *
 * @return {void}
 */
Navigation.FastFilter.Filter.prototype.request = function () {
    var self = this;
    if (self.$this.find('li.fast_filter').find('img.throbber').length === 0) {
        self.$this.find('li.fast_filter').append(
            $('<div class="throbber"></div>').append(
                $('#pma_navigation_content')
                    .find('img.throbber')
                    .clone()
                    .css({ visibility: 'visible', display: 'block' })
            )
        );
    }
    if (self.xhr) {
        self.xhr.abort();
    }
    var params = self.$this.find('> ul > li > form.fast_filter').first().serialize();

    if (self.$this.find('> ul > li > form.fast_filter').first().find('input[name=searchClause]').length === 0) {
        var $input = $('#pma_navigation_tree').find('li.fast_filter.db_fast_filter input.searchClause');
        if ($input.length && $input.val() !== $input[0].defaultValue) {
            params += CommonParams.get('arg_separator') + 'searchClause=' + encodeURIComponent($input.val());
        }
    }
    self.xhr = $.ajax({
        url: 'index.php?route=/navigation&ajax_request=1&server=' + CommonParams.get('server'),
        type: 'post',
        dataType: 'json',
        data: params,
        complete: function (jqXHR, status) {
            if (status !== 'abort') {
                var data = JSON.parse(jqXHR.responseText);
                self.$this.find('li.fast_filter').find('div.throbber').remove();
                if (data && data.results) {
                    self.swap.apply(self, [data.message]);
                }
            }
        }
    });
};
/**
 * Replaces the contents of the navigation branch with the search results
 *
 * @param {string} list The search results
 *
 * @return {void}
 */
Navigation.FastFilter.Filter.prototype.swap = function (list) {
    this.$this
        .html($(list).html())
        .children()
        .show()
        .end()
        .find('li.fast_filter input.searchClause')
        .val(this.searchClause);
    this.$this.data('fastFilter', this);
};
/**
 * Restores the navigation to the original state after the fast filter is cleared
 *
 * @param {boolean} focus Whether to also focus the input box of the fast filter
 *
 * @return {void}
 */
Navigation.FastFilter.Filter.prototype.restore = function (focus) {
    if (this.$this.children('ul').first().hasClass('search_results')) {
        this.$this.html(this.$clone.html()).children().show();
        this.$this.data('fastFilter', this);
        if (focus) {
            this.$this.find('li.fast_filter input.searchClause').trigger('focus');
        }
    }
    this.searchClause = '';
    this.$this.find('div.pageselector').show();
    this.$this.find('div.throbber').remove();
};

/**
 * Show full name when cursor hover and name not shown completely
 *
 * @param {object} $containerELem Container element
 *
 * @return {void}
 */
Navigation.showFullName = function ($containerELem) {
    $containerELem.find('.hover_show_full').on('mouseenter', function () {
        /** mouseenter */
        var $this = $(this);
        var thisOffset = $this.offset();
        if ($this.text() === '') {
            return;
        }
        var $parent = $this.parent();
        if (($parent.offset().left + $parent.outerWidth())
           < (thisOffset.left + $this.outerWidth())) {
            var $fullNameLayer = $('#full_name_layer');
            if ($fullNameLayer.length === 0) {
                $('body').append('<div id="full_name_layer" class="hide"></div>');
                $('#full_name_layer').on('mouseleave', function () {
                    /** mouseleave */
                    $(this).addClass('hide')
                        .removeClass('hovering');
                }).on('mouseenter', function () {
                    /** mouseenter */
                    $(this).addClass('hovering');
                });
                $fullNameLayer = $('#full_name_layer');
            }
            $fullNameLayer.removeClass('hide');
            $fullNameLayer.css({ left: thisOffset.left, top: thisOffset.top });
            $fullNameLayer.html($this.clone());
            setTimeout(function () {
                if (! $fullNameLayer.hasClass('hovering')) {
                    $fullNameLayer.trigger('mouseleave');
                }
            }, 200);
        }
    });
};
