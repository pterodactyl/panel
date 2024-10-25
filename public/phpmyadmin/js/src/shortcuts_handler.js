/**
 * @fileoverview    Handle shortcuts in various pages
 * @name            Shortcuts handler
 *
 * @requires    jQuery
 * @requires    jQueryUI
 */

/* global Console */ // js/console.js

/**
 * Register key events on load
 */
$(function () {
    var databaseOp = false;
    var tableOp = false;
    var keyD = 68;
    var keyT = 84;
    var keyK = 75;
    var keyS = 83;
    var keyF = 70;
    var keyE = 69;
    var keyH = 72;
    var keyC = 67;
    var keyBackSpace = 8;
    $(document).on('keyup', function (e) {
        // is a string but is also a boolean according to https://api.jquery.com/prop/
        if ($(e.target).prop('contenteditable') === 'true' || $(e.target).prop('contenteditable') === true) {
            return;
        }

        if (e.target.nodeName === 'INPUT' || e.target.nodeName === 'TEXTAREA' || e.target.nodeName === 'SELECT') {
            return;
        }

        if (e.keyCode === keyD) {
            setTimeout(function () {
                databaseOp = false;
            }, 2000);
        } else if (e.keyCode === keyT) {
            setTimeout(function () {
                tableOp = false;
            }, 2000);
        }
    });
    $(document).on('keydown', function (e) {
        // is a string but is also a boolean according to https://api.jquery.com/prop/
        if ($(e.target).prop('contenteditable') === 'true' || $(e.target).prop('contenteditable') === true) {
            return;
        }

        // disable the shortcuts when session has timed out.
        if ($('#modalOverlay').length > 0) {
            return;
        }
        if (e.ctrlKey && e.altKey && e.keyCode === keyC) {
            Console.toggle();
        }

        if (e.ctrlKey && e.keyCode === keyK) {
            e.preventDefault();
            Console.toggle();
        }

        if (e.target.nodeName === 'INPUT' || e.target.nodeName === 'TEXTAREA' || e.target.nodeName === 'SELECT') {
            return;
        }

        var isTable;
        var isDb;
        if (e.keyCode === keyD) {
            databaseOp = true;
        } else if (e.keyCode === keyK) {
            e.preventDefault();
            Console.toggle();
        } else if (e.keyCode === keyS) {
            if (databaseOp === true) {
                isTable = CommonParams.get('table');
                isDb = CommonParams.get('db');
                if (isDb && ! isTable) {
                    $('.nav-link .ic_b_props').first().trigger('click');
                }
            } else if (tableOp === true) {
                isTable = CommonParams.get('table');
                isDb = CommonParams.get('db');
                if (isDb && isTable) {
                    $('.nav-link .ic_b_props').first().trigger('click');
                }
            } else {
                $('#pma_navigation_settings_icon').trigger('click');
            }
        } else if (e.keyCode === keyF) {
            if (databaseOp === true) {
                isTable = CommonParams.get('table');
                isDb = CommonParams.get('db');
                if (isDb && ! isTable) {
                    $('.nav-link .ic_b_search').first().trigger('click');
                }
            } else if (tableOp === true) {
                isTable = CommonParams.get('table');
                isDb = CommonParams.get('db');
                if (isDb && isTable) {
                    $('.nav-link .ic_b_search').first().trigger('click');
                }
            }
        } else if (e.keyCode === keyT) {
            tableOp = true;
        } else if (e.keyCode === keyE) {
            $('.ic_b_export').first().trigger('click');
        } else if (e.keyCode === keyBackSpace) {
            window.history.back();
        } else if (e.keyCode === keyH) {
            $('.ic_b_home').first().trigger('click');
        }
    });
});
