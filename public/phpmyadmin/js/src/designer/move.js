/**
 * @package PhpMyAdmin-Designer
 */

/* global DesignerObjects */ // js/designer/objects.js
/* global DesignerHistory, historyArray, selectField */ // js/designer/history.js
/* global contr, db, designerTablesEnabled, displayField, hTabs, jTabs, selectedPage:writable, server */ // js/designer/init.js
/* global DesignerPage */ // js/designer/page.js
/* global themeImagePath */ // templates/javascript/variables.twig

var DesignerMove = {};

var change = 0; // variable to track any change in designer layout.
var showRelationLines = true;
var alwaysShowText = false;

AJAX.registerTeardown('designer/move.js', function () {
    $(document).off('fullscreenchange');
    $('#selflink').show();
});

AJAX.registerOnload('designer/move.js', function () {
    var $content = $('#page_content');
    var $img = $('#toggleFullscreen').find('img');
    var $span = $img.siblings('span');

    $content.css({ 'margin-left': '3px' });
    $(document).on('fullscreenchange', function () {
        if (! document.fullscreenElement) {
            $content.removeClass('content_fullscreen')
                .css({ 'width': 'auto', 'height': 'auto' });
            $('#osn_tab').css({ 'width': 'auto', 'height': 'auto' });
            $img.attr('src', $img.data('enter'))
                .attr('title', $span.data('enter'));
            $span.text($span.data('enter'));

            // Saving the fullscreen state in config when
            // designer exists fullscreen mode via ESC key

            var valueSent = 'off';
            DesignerMove.saveValueInConfig('full_screen', valueSent);
        }
    });

    $('#selflink').hide();
});

DesignerMove.markSaved = function () {
    change = 0;
    $('#saved_state').text('');
};

DesignerMove.markUnsaved = function () {
    change = 1;
    $('#saved_state').text('*');
};

var mainDirection = $('html').attr('dir') === 'rtl' ? 'right' : 'left';
// Will be used to multiply the offsetLeft by -1 if the direction is rtl.
var directionEffect = mainDirection === 'right' ? -1 : 1;
var curClick = null;
var smS           = 0;
var smAdd         = 10;
var sLeft         = 0;
var sRight        = 0;
var onRelation    = 0;
var onGrid        = 0;
var onDisplayField = 0;
// relation_style: 0 - angular 1 - direct
var onAngularDirect = 1;
var clickField    = 0;
var linkRelation  = '';
var canvasWidth   = 0;
var canvasHeight  = 0;
var osnTabWidth  = 0;
var osnTabHeight = 0;
var heightField   = 7;
var globX;
var globY;
var timeoutId;
var layerMenuCurClick = 0;
var fromArray = [];
var menuMoved = false;
var gridSize = 10;

// ------------------------------------------------------------------------------

var isIe = document.all && !window.opera;

if (isIe) {
    window.onscroll = DesignerMove.generalScroll;
    document.onselectstart = function () {
        return false;
    };
}

DesignerMove.mouseDown = function (e) {
    // eslint-disable-next-line compat/compat
    globX = isIe ? e.clientX + document.body.scrollLeft : e.pageX;
    // eslint-disable-next-line compat/compat
    globY = isIe ? e.clientY + document.body.scrollTop : e.pageY;

    if (e.target.tagName === 'SPAN') {
        curClick = e.target.parentNode.parentNode.parentNode.parentNode;
    } else if (e.target.className === 'tab_zag_2') {
        curClick = e.target.parentNode.parentNode.parentNode;
    } else if (e.target.id === 'layer_menu_sizer_btn') {
        layerMenuCurClick = 1;
    } else if (e.target.className === 'M_butt') {
        return false;
    }

    if (curClick !== null) {
        document.getElementById('canvas').style.display = 'none';
        curClick.style.zIndex = 2;
    }
};

DesignerMove.mouseMove = function (e) {
    if (e.preventDefault) {
        e.preventDefault();
    }

    // eslint-disable-next-line compat/compat
    var newDx = isIe ? e.clientX + document.body.scrollLeft : e.pageX;
    // eslint-disable-next-line compat/compat
    var newDy = isIe ? e.clientY + document.body.scrollTop : e.pageY;

    var deltaX = globX - newDx;
    var deltaY = globY - newDy;

    globX = newDx;
    globY = newDy;

    if (curClick !== null) {
        DesignerMove.markUnsaved();

        var $curClick = $(curClick);

        var curX = parseFloat($curClick.attr('data-' + mainDirection) || $curClick.css(mainDirection));
        var curY = parseFloat($curClick.attr('data-top') || $curClick.css('top'));

        var newX = curX - directionEffect * deltaX;
        var newY = curY - deltaY;

        $curClick.attr('data-' + mainDirection, newX);
        $curClick.attr('data-top', newY);

        if (onGrid) {
            newX = parseInt(newX / gridSize) * gridSize;
            newY = parseInt(newY / gridSize) * gridSize;
        }

        if (newX < 0) {
            newX = 0;
        } else if (newY < 0) {
            newY = 0;
        }
        $curClick.css(mainDirection, newX + 'px');
        $curClick.css('top', newY + 'px');
    } else if (layerMenuCurClick) {
        if (menuMoved) {
            deltaX = -deltaX;
        }
        var $layerMenu = $('#layer_menu');
        var newWidth = $layerMenu.width() + directionEffect * deltaX;
        if (newWidth < 150) {
            newWidth = 150;
        }
        $layerMenu.width(newWidth);
    }

    if (onRelation || onDisplayField) {
        document.getElementById('designer_hint').style.left = (globX + 20) + 'px';
        document.getElementById('designer_hint').style.top  = (globY + 20) + 'px';
    }
};

DesignerMove.mouseUp = function () {
    if (curClick !== null) {
        document.getElementById('canvas').style.display = 'inline-block';
        DesignerMove.reload();
        curClick.style.zIndex = 1;
        curClick = null;
    }
    layerMenuCurClick = 0;
};

// ------------------------------------------------------------------------------

DesignerMove.canvasPos = function () {
    canvasWidth  = document.getElementById('canvas').width  = osnTabWidth  - 3;
    canvasHeight = document.getElementById('canvas').height = osnTabHeight - 3;

    if (isIe) {
        document.getElementById('canvas').style.width  = ((osnTabWidth  - 3) ? (osnTabWidth  - 3) : 0) + 'px';
        document.getElementById('canvas').style.height = ((osnTabHeight - 3) ? (osnTabHeight - 3) : 0) + 'px';
    }
};

DesignerMove.osnTabPos = function () {
    osnTabWidth  = parseInt(document.getElementById('osn_tab').style.width, 10);
    osnTabHeight = parseInt(document.getElementById('osn_tab').style.height, 10);
};

DesignerMove.setDefaultValuesFromSavedState = function () {
    if ($('#angular_direct_button').attr('class') === 'M_butt') {
        onAngularDirect = 0;
    } else {
        onAngularDirect = 1;
    }
    DesignerMove.angularDirect();

    if ($('#grid_button').attr('class') === 'M_butt') {
        onGrid = 1;
    } else {
        onGrid = 0;
    }
    DesignerMove.grid();

    var $relLineInvert = $('#relLineInvert');
    if ($relLineInvert.attr('class') === 'M_butt') {
        showRelationLines = false;
        $relLineInvert.attr('class', 'M_butt');
    } else {
        showRelationLines = true;
        $relLineInvert.attr('class', 'M_butt_Selected_down');
    }
    DesignerMove.relationLinesInvert();

    if ($('#pin_Text').attr('class') === 'M_butt_Selected_down') {
        alwaysShowText = true;
        DesignerMove.showText();
    } else {
        alwaysShowText = false;
    }

    var $keySbAll = $('#key_SB_all');
    if ($keySbAll.attr('class') === 'M_butt_Selected_down') {
        $keySbAll.trigger('click');
        $keySbAll.toggleClass('M_butt_Selected_down');
        $keySbAll.toggleClass('M_butt');
    }

    var $keyLeftRight = $('#key_Left_Right');
    if ($keyLeftRight.attr('class') === 'M_butt_Selected_down') {
        $keyLeftRight.trigger('click');
    }
};

DesignerMove.main = function () {
    // ---CROSS

    document.getElementById('layer_menu').style.top = -1000 + 'px'; // fast scroll
    DesignerMove.osnTabPos();
    DesignerMove.canvasPos();
    DesignerMove.smallTabRefresh();
    DesignerMove.reload();
    DesignerMove.setDefaultValuesFromSavedState();
    if (isIe) {
        DesignerMove.generalScroll();
    }
};

DesignerMove.resizeOsnTab = function () {
    var maxX = 0;
    var maxY = 0;
    for (var key in jTabs) {
        var kX = parseInt(document.getElementById(key).style[mainDirection], 10) + document.getElementById(key).offsetWidth;
        var kY = parseInt(document.getElementById(key).style.top, 10) + document.getElementById(key).offsetHeight;
        maxX = maxX < kX ? kX : maxX;
        maxY = maxY < kY ? kY : maxY;
    }

    osnTabWidth  = maxX + 50;
    osnTabHeight = maxY + 50;
    DesignerMove.canvasPos();
};

/**
 * Draw a colored line
 *
 * @param {number} x1
 * @param {number} x2
 * @param {number} y1
 * @param {number} y2
 * @param {HTMLElement} osnTab
 * @param {string} colorTarget
 * @return {void}
 */
DesignerMove.drawLine0 = function (x1, x2, y1, y2, osnTab, colorTarget) {
    DesignerMove.line0(
        x1 + directionEffect * osnTab.offsetLeft,
        y1 - osnTab.offsetTop,
        x2 + directionEffect * osnTab.offsetLeft,
        y2 - osnTab.offsetTop,
        DesignerMove.getColorByTarget(colorTarget)
    );
};

/**
 * refreshes display, must be called after state changes
 */
DesignerMove.reload = function () {
    DesignerMove.resizeOsnTab();
    var n;
    var x1;
    var x2;
    var a = [];
    var K;
    var key;
    var key2;
    var key3;
    DesignerMove.clear();
    var osnTab = document.getElementById('osn_tab');
    for (K in contr) {
        for (key in contr[K]) {
            // contr name
            for (key2 in contr[K][key]) {
                // table name
                for (key3 in contr[K][key][key2]) {
                    // field name
                    if (!document.getElementById('check_vis_' + key2).checked ||
                        !document.getElementById('check_vis_' + contr[K][key][key2][key3][0]).checked) {
                        // if hide
                        continue;
                    }
                    var x1Left  = document.getElementById(key2).offsetLeft + 1;
                    var x1Right = x1Left + document.getElementById(key2).offsetWidth;
                    var x2Left  = document.getElementById(contr[K][key][key2][key3][0]).offsetLeft;
                    var x2Right = x2Left + document.getElementById(contr[K][key][key2][key3][0]).offsetWidth;
                    a[0] = Math.abs(x1Left - x2Left);
                    a[1] = Math.abs(x1Left - x2Right);
                    a[2] = Math.abs(x1Right - x2Left);
                    a[3] = Math.abs(x1Right - x2Right);
                    n = sLeft = sRight = 0;
                    for (var i = 1; i < 4; i++) {
                        if (a[n] > a[i]) {
                            n = i;
                        }
                    }
                    if (n === 1) {
                        x1 = x1Left - smS;
                        x2 = x2Right + smS;
                        if (x1 < x2) {
                            n = 0;
                        }
                    }
                    if (n === 2) {
                        x1 = x1Right + smS;
                        x2 = x2Left - smS;
                        if (x1 > x2) {
                            n = 0;
                        }
                    }
                    if (n === 3) {
                        x1 = x1Right + smS;
                        x2 = x2Right + smS;
                        sRight = 1;
                    }
                    if (n === 0) {
                        x1 = x1Left - smS;
                        x2 = x2Left - smS;
                        sLeft = 1;
                    }

                    var rowOffsetTop = 0;
                    var tabHideButton = document.getElementById('id_hide_tbody_' + key2);

                    if (tabHideButton.innerHTML === 'v') {
                        var fromColumn = document.getElementById(key2 + '.' + key3);
                        if (fromColumn) {
                            rowOffsetTop = fromColumn.offsetTop;
                        } else {
                            continue;
                        }
                    }

                    var y1 = document.getElementById(key2).offsetTop +
                        rowOffsetTop +
                        heightField;


                    rowOffsetTop = 0;
                    tabHideButton = document.getElementById('id_hide_tbody_' + contr[K][key][key2][key3][0]);
                    if (tabHideButton.innerHTML === 'v') {
                        var toColumn = document.getElementById(contr[K][key][key2][key3][0] +
                            '.' + contr[K][key][key2][key3][1]);
                        if (toColumn) {
                            rowOffsetTop = toColumn.offsetTop;
                        } else {
                            continue;
                        }
                    }

                    var y2 =
                        document.getElementById(contr[K][key][key2][key3][0]).offsetTop +
                        rowOffsetTop +
                        heightField;

                    DesignerMove.drawLine0(
                        x1, x2, y1, y2, osnTab, contr[K][key][key2][key3][0] + '.' + contr[K][key][key2][key3][1]
                    );
                }
            }
        }
    }
};

/**
 * draws a line from x1:y1 to x2:y2 with color
 * @param x1
 * @param y1
 * @param x2
 * @param y2
 * @param colorLine
 */
DesignerMove.line = function (x1, y1, x2, y2, colorLine) {
    var canvas = document.getElementById('canvas');
    var ctx    = canvas.getContext('2d');
    ctx.strokeStyle = colorLine;
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(x1, y1);
    ctx.lineTo(x2, y2);
    ctx.stroke();
};

/**
 * draws a relation/constraint line, whether angular or not
 * @param x1
 * @param y1
 * @param x2
 * @param y2
 * @param colorLine
 */
DesignerMove.line0 = function (x1, y1, x2, y2, colorLine) {
    if (! showRelationLines) {
        return;
    }
    DesignerMove.circle(x1, y1, 3, 3, colorLine);
    DesignerMove.rect(x2 - 1, y2 - 2, 4, 4, colorLine);

    if (onAngularDirect) {
        DesignerMove.line2(x1, y1, x2, y2, colorLine);
    } else {
        DesignerMove.line3(x1, y1, x2, y2, colorLine);
    }
};

/**
 * draws a angular relation/constraint line
 * @param x1
 * @param y1
 * @param x2
 * @param y2
 * @param colorLine
 */
DesignerMove.line2 = function (x1, y1, x2, y2, colorLine) {
    var x1Local = x1;
    var x2Local = x2;

    if (sRight) {
        x1Local += smAdd;
        x2Local += smAdd;
    } else if (sLeft) {
        x1Local -= smAdd;
        x2Local -= smAdd;
    } else if (x1 < x2) {
        x1Local += smAdd;
        x2Local -= smAdd;
    } else {
        x1Local -= smAdd;
        x2Local += smAdd;
    }

    DesignerMove.line(x1, y1, x1Local, y1, colorLine);
    DesignerMove.line(x2, y2, x2Local, y2, colorLine);
    DesignerMove.line(x1Local, y1, x2Local, y2, colorLine);
};

/**
 * draws a relation/constraint line
 * @param x1
 * @param y1
 * @param x2
 * @param y2
 * @param colorLine
 */
DesignerMove.line3 = function (x1, y1, x2, y2, colorLine) {
    var x1Local = x1;
    var x2Local = x2;

    if (sRight) {
        if (x1 < x2) {
            x1Local += x2 - x1 + smAdd;
            x2Local += smAdd;
        } else {
            x2Local += x1 - x2 + smAdd;
            x1Local += smAdd;
        }

        DesignerMove.line(x1, y1, x1Local, y1, colorLine);
        DesignerMove.line(x2, y2, x2Local, y2, colorLine);
        DesignerMove.line(x1Local, y1, x2Local, y2, colorLine);
        return;
    }
    if (sLeft) {
        if (x1 < x2) {
            x2Local -= x2 - x1 + smAdd;
            x1Local -= smAdd;
        } else {
            x1Local -= x1 - x2 + smAdd;
            x2Local -= smAdd;
        }

        DesignerMove.line(x1, y1, x1Local, y1, colorLine);
        DesignerMove.line(x2, y2, x2Local, y2, colorLine);
        DesignerMove.line(x1Local, y1, x2Local, y2, colorLine);
        return;
    }

    var xS = (x1 + x2) / 2;
    DesignerMove.line(x1, y1, xS, y1, colorLine);
    DesignerMove.line(xS, y2, x2, y2, colorLine);
    DesignerMove.line(xS, y1, xS, y2, colorLine);
};

DesignerMove.circle = function (x, y, r, w, color) {
    var ctx = document.getElementById('canvas').getContext('2d');
    ctx.beginPath();
    ctx.moveTo(x, y);
    ctx.lineWidth = w;
    ctx.strokeStyle = color;
    ctx.arc(x, y, r, 0, 2 * Math.PI, true);
    ctx.stroke();
};

DesignerMove.clear = function () {
    var canvas = document.getElementById('canvas');
    var ctx    = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvasWidth, canvasHeight);
};

DesignerMove.rect = function (x1, y1, w, h, color) {
    var ctx = document.getElementById('canvas').getContext('2d');
    ctx.fillStyle = color;
    ctx.fillRect(x1, y1, w, h);
};

// --------------------------- FULLSCREEN -------------------------------------
DesignerMove.toggleFullscreen = function () {
    var valueSent = '';
    var $img = $('#toggleFullscreen').find('img');
    var $span = $img.siblings('span');
    var $content = $('#page_content');
    const pageContent = document.getElementById('page_content');

    if (! document.fullscreenElement) {
        $img.attr('src', $img.data('exit'))
            .attr('title', $span.data('exit'));
        $span.text($span.data('exit'));
        $content
            .addClass('content_fullscreen')
            .css({ 'width': screen.width - 5, 'height': screen.height - 5 });

        $('#osn_tab').css({ 'width': screen.width + 'px', 'height': screen.height });
        valueSent = 'on';
        pageContent.requestFullscreen();
    } else {
        $img.attr('src', $img.data('enter'))
            .attr('title', $span.data('enter'));
        $span.text($span.data('enter'));
        $content.removeClass('content_fullscreen')
            .css({ 'width': 'auto', 'height': 'auto' });
        $('#osn_tab').css({ 'width': 'auto', 'height': 'auto' });
        document.exitFullscreen();
        valueSent = 'off';
    }
    DesignerMove.saveValueInConfig('full_screen', valueSent);
};

DesignerMove.addTableToTablesList = function (index, tableDom) {
    var db = $(tableDom).find('.small_tab_pref').attr('db');
    var table = $(tableDom).find('.small_tab_pref').attr('table_name');
    var dbEncoded = $(tableDom).find('.small_tab_pref').attr('db_url');
    var tableEncoded = $(tableDom).find('.small_tab_pref').attr('table_name_url');
    var tableIsChecked = $(tableDom).css('display') === 'block' ? 'checked' : '';
    var checkboxStatus = (tableIsChecked === 'checked') ? Messages.strHide : Messages.strShow;
    var $newTableLine = $('<tr>' +
        '    <td title="' + Messages.strStructure + '"' +
        '        width="1px"' +
        '        class="L_butt2_1">' +
        '        <img alt=""' +
        '            db="' + dbEncoded + '"' +
        '            table_name="' + tableEncoded + '"' +
        '            class="scroll_tab_struct"' +
        '            src="' + themeImagePath + 'designer/exec.png"/>' +
        '    </td>' +
        '    <td width="1px">' +
        '        <input class="scroll_tab_checkbox"' +
        '            title="' + checkboxStatus + '"' +
        '            id="check_vis_' + dbEncoded + '.' + tableEncoded + '"' +
        '            style="margin:0;"' +
        '            type="checkbox"' +
        '            value="' + dbEncoded + '.' + tableEncoded + '"' + tableIsChecked +
        '            />' +
        '    </td>' +
        '    <td class="designer_Tabs"' +
        '        designer_url_table_name="' + dbEncoded + '.' + tableEncoded + '">' + $('<div/>').text(db + '.' + table).html() + '</td>' +
        '</tr>');
    $('#id_scroll_tab table').first().append($newTableLine);
    $($newTableLine).find('.scroll_tab_struct').on('click', function () {
        DesignerMove.startTabUpd(db, table);
    });
    $($newTableLine).on('click', '.designer_Tabs2,.designer_Tabs', function () {
        DesignerMove.selectTab($(this).attr('designer_url_table_name'));
    });
    $($newTableLine).find('.scroll_tab_checkbox').on('click', function () {
        $(this).attr('title', function (i, currentvalue) {
            return currentvalue === Messages.strHide ? Messages.strShow : Messages.strHide;
        });
        DesignerMove.visibleTab(this,$(this).val());
    });
    var $tablesCounter = $('#tables_counter');
    $tablesCounter.text(parseInt($tablesCounter.text(), 10) + 1);
};

/**
 * This function shows modal with Go buttons where required in designer
 * @param {object} form
 * @param {string} heading
 * @param {string} type
 *
 * @return {object} modal;
 */
DesignerMove.displayModal = function (form, heading, type) {
    var modal = $(type);
    modal.modal('show');
    modal.find('.modal-body').first().html(form);
    $(type + 'Label').first().html(heading);
    return modal;
};

DesignerMove.addOtherDbTables = function () {
    var $selectDb = $('<select id="add_table_from"></select>');
    $selectDb.append('<option value="">' + Messages.strNone + '</option>');

    var $selectTable = $('<select id="add_table"></select>');
    $selectTable.append('<option value="">' + Messages.strNone + '</option>');

    $.post('index.php?route=/sql', {
        'ajax_request' : true,
        'sql_query' : 'SHOW databases;',
        'server': CommonParams.get('server')
    }, function (data) {
        $(data.message).find('table.table_results.data.ajax').find('td.data').each(function () {
            var val = $(this)[0].innerText;
            $selectDb.append($('<option></option>').val(val).text(val));
        });
    });

    var $form = $('<form action="" class="ajax"></form>')
        .append($selectDb).append($selectTable);
    var modal = DesignerMove.displayModal($form, Messages.strAddTables, '#designerGoModal');
    $('#designerModalGoButton').on('click', function () {
        var db = $('#add_table_from').val();
        var table = $('#add_table').val();

        // Check if table already imported or not.
        var $table = $('[id="' + encodeURIComponent(db) + '.' + encodeURIComponent(table) + '"]');
        if ($table.length !== 0) {
            Functions.ajaxShowMessage(
                Functions.sprintf(Messages.strTableAlreadyExists, db + '.' + table),
                undefined,
                'error'
            );
            return;
        }

        $.post('index.php?route=/database/designer', {
            'ajax_request' : true,
            'dialog' : 'add_table',
            'db' : db,
            'table' : table,
            'server': CommonParams.get('server')
        }, function (data) {
            var $newTableDom = $(data.message);
            $newTableDom.find('a').first().remove();

            var dbEncoded = $($newTableDom).find('.small_tab_pref').attr('db_url');
            var tableEncoded = $($newTableDom).find('.small_tab_pref').attr('table_name_url');

            if (typeof dbEncoded === 'string' && typeof tableEncoded === 'string') { // Do not try to add if attr not found !
                $('#container-form').append($newTableDom);
                DesignerMove.enableTableEvents(null, $newTableDom);
                DesignerMove.addTableToTablesList(null, $newTableDom);
                jTabs[dbEncoded + '.' + tableEncoded] = 1;
                DesignerMove.markUnsaved();
            }
        });

        $('#designerModalGoButton').off('click');// Unregister the event for other modals to not call this one
        modal.modal('hide');
    });
    $('#add_table_from').on('change', function () {
        if ($(this).val()) {
            var dbName = $(this).val();
            var sqlQuery = 'SHOW tables;';
            $.post('index.php?route=/sql', {
                'ajax_request' : true,
                'sql_query': sqlQuery,
                'db' : dbName,
                'server': CommonParams.get('server')
            }, function (data) {
                $selectTable.html('');
                var rows = $(data.message).find('table.table_results.data.ajax').find('td.data');
                if (rows.length === 0) {
                    $selectTable.append('<option value="">' + Messages.strNone + '</option>');
                }
                rows.each(function () {
                    var val = $(this)[0].innerText;
                    $selectTable.append($('<option></option>').val(val).text(val));
                });
            });
        }
    });
};

// ------------------------------ NEW ------------------------------------------
DesignerMove.new = function () {
    DesignerMove.promptToSaveCurrentPage(function () {
        DesignerMove.loadPage(-1);
    });
};

// ------------------------------ SAVE ------------------------------------------
// (del?) no for pdf
DesignerMove.save = function (url) {
    for (var key in jTabs) {
        document.getElementById('t_x_' + key + '_').value = parseInt(document.getElementById(key).style.left, 10);
        document.getElementById('t_y_' + key + '_').value = parseInt(document.getElementById(key).style.top, 10);
        document.getElementById('t_v_' + key + '_').value = document.getElementById('id_tbody_' + key).style.display === 'none' ? 0 : 1;
        document.getElementById('t_h_' + key + '_').value = document.getElementById('check_vis_' + key).checked ? 1 : 0;
    }
    document.getElementById('container-form').action = url;
    $('#container-form').trigger('submit');
};

DesignerMove.getUrlPos = function (forceString) {
    var key;
    if (designerTablesEnabled || forceString) {
        var poststr = '';
        var argsep = CommonParams.get('arg_separator');
        var i = 1;
        for (key in jTabs) {
            poststr += argsep + 't_x[' + i + ']=' + parseInt(document.getElementById(key).style.left, 10);
            poststr += argsep + 't_y[' + i + ']=' + parseInt(document.getElementById(key).style.top, 10);
            poststr += argsep + 't_v[' + i + ']=' + (document.getElementById('id_tbody_' + key).style.display === 'none' ? 0 : 1);
            poststr += argsep + 't_h[' + i + ']=' + (document.getElementById('check_vis_' + key).checked ? 1 : 0);
            poststr += argsep + 't_db[' + i + ']=' + $(document.getElementById(key)).attr('db_url');
            poststr += argsep + 't_tbl[' + i + ']=' + $(document.getElementById(key)).attr('table_name_url');
            i++;
        }
        return poststr;
    } else {
        var coords = [];
        for (key in jTabs) {
            if (document.getElementById('check_vis_' + key).checked) {
                var x = parseInt(document.getElementById(key).style.left, 10);
                var y = parseInt(document.getElementById(key).style.top, 10);
                var tbCoords = new DesignerObjects.TableCoordinate(
                    $(document.getElementById(key)).attr('db_url'),
                    $(document.getElementById(key)).attr('table_name_url'),
                    -1, x, y);
                coords.push(tbCoords);
            }
        }
        return coords;
    }
};

DesignerMove.save2 = function (callback) {
    if (designerTablesEnabled) {
        var argsep = CommonParams.get('arg_separator');
        var poststr = 'operation=savePage' + argsep + 'save_page=same' + argsep + 'ajax_request=true';
        poststr += argsep + 'server=' + server + argsep + 'db=' + encodeURIComponent(db) + argsep + 'selected_page=' + selectedPage;
        poststr += DesignerMove.getUrlPos();

        var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
        $.post('index.php?route=/database/designer', poststr, function (data) {
            if (data.success === false) {
                Functions.ajaxShowMessage(data.error, false);
            } else {
                Functions.ajaxRemoveMessage($msgbox);
                Functions.ajaxShowMessage(Messages.strModificationSaved);
                DesignerMove.markSaved();
                if (typeof callback !== 'undefined') {
                    callback();
                }
            }
        });
    } else {
        var name = $('#page_name').html().trim();
        DesignerPage.saveToSelectedPage(db, selectedPage, name, DesignerMove.getUrlPos(), function () {
            DesignerMove.markSaved();
            if (typeof callback !== 'undefined') {
                callback();
            }
        });
    }
};

DesignerMove.submitSaveDialogAndClose = function (callback, modal) {
    var $form = $('#save_page');
    var name = $form.find('input[name="selected_value"]').val().trim();
    if (name === '') {
        Functions.ajaxShowMessage(Messages.strEnterValidPageName, false);
        return;
    }
    modal.modal('hide');

    if (designerTablesEnabled) {
        var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
        Functions.prepareForAjaxRequest($form);
        $.post($form.attr('action'), $form.serialize() + DesignerMove.getUrlPos(), function (data) {
            if (data.success === false) {
                Functions.ajaxShowMessage(data.error, false);
            } else {
                Functions.ajaxRemoveMessage($msgbox);
                DesignerMove.markSaved();
                if (data.id) {
                    selectedPage = data.id;
                }
                $('#page_name').text(name);
                if (typeof callback !== 'undefined') {
                    callback();
                }
            }
        });
    } else {
        DesignerPage.saveToNewPage(db, name, DesignerMove.getUrlPos(), function (page) {
            DesignerMove.markSaved();
            if (page.pgNr) {
                selectedPage = page.pgNr;
            }
            $('#page_name').text(page.pageDescr);
            if (typeof callback !== 'undefined') {
                callback();
            }
        });
    }
};

DesignerMove.save3 = function (callback) {
    if (selectedPage !== -1) {
        DesignerMove.save2(callback);
    } else {
        var $form = $('<form action="index.php?route=/database/designer" method="post" name="save_page" id="save_page" class="ajax"></form>')
            .append('<input type="hidden" name="server" value="' + server + '">')
            .append($('<input type="hidden" name="db" />').val(db))
            .append('<input type="hidden" name="operation" value="savePage">')
            .append('<input type="hidden" name="save_page" value="new">')
            .append('<label for="selected_value">' + Messages.strPageName +
                '</label>:<input type="text" name="selected_value">');
        var modal = DesignerMove.displayModal($form, Messages.strSavePage, '#designerGoModal');
        $form.on('submit', function (e) {
            e.preventDefault();
            DesignerMove.submitSaveDialogAndClose(callback, modal);
        });
        $('#designerModalGoButton').on('click', function () {
            var $form = $('#save_page');
            $form.trigger('submit');

            $('#designerModalGoButton').off('click');// Unregister the event for other modals to not call this one
            modal.modal('hide');
        });
    }
};

// ------------------------------ EDIT PAGES ------------------------------------------
DesignerMove.editPages = function () {
    DesignerMove.promptToSaveCurrentPage(function () {
        var $msgbox = Functions.ajaxShowMessage();
        $.post('index.php?route=/database/designer', {
            'ajax_request': true,
            'server': server,
            'db': db,
            'dialog': 'edit'
        }, function (data) {
            if (data.success === false) {
                Functions.ajaxShowMessage(data.error, false);
            } else {
                Functions.ajaxRemoveMessage($msgbox);

                if (! designerTablesEnabled) {
                    DesignerPage.createPageList(db, function (options) {
                        $('#selected_page').append(options);
                    });
                }
                var modal = DesignerMove.displayModal(data.message, Messages.strOpenPage, '#designerGoModal');
                $('#designerModalGoButton').on('click', function () {
                    var $form = $('#edit_delete_pages');
                    var selected = $form.find('select[name="selected_page"]').val();
                    if (selected === '0') {
                        Functions.ajaxShowMessage(Messages.strSelectPage, 2000);
                        return;
                    }

                    $('#designerModalGoButton').off('click');// Unregister the event for other modals to not call this one
                    modal.modal('hide');
                    DesignerMove.loadPage(selected);
                });
            }
        }); // end $.post()
    });
};

// -----------------------------  DELETE PAGES ---------------------------------------
DesignerMove.deletePages = function () {
    var $msgbox = Functions.ajaxShowMessage();
    $.post('index.php?route=/database/designer', {
        'ajax_request': true,
        'server': server,
        'db': db,
        'dialog': 'delete'
    }, function (data) {
        if (data.success === false) {
            Functions.ajaxShowMessage(data.error, false);
        } else {
            Functions.ajaxRemoveMessage($msgbox);

            if (! designerTablesEnabled) {
                DesignerPage.createPageList(db, function (options) {
                    $('#selected_page').append(options);
                });
            }

            var modal = DesignerMove.displayModal(data.message, Messages.strDeletePage, '#designerGoModal');
            $('#designerModalGoButton').on('click', function () {
                var $form = $('#edit_delete_pages');
                var selected = $form.find('select[name="selected_page"]').val();
                if (selected === '0') {
                    Functions.ajaxShowMessage(Messages.strSelectPage, 2000);
                    return;
                }

                var $messageBox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
                var deletingCurrentPage = parseInt(selected) === selectedPage;
                Functions.prepareForAjaxRequest($form);

                if (designerTablesEnabled) {
                    $.post($form.attr('action'), $form.serialize(), function (data) {
                        if (data.success === false) {
                            Functions.ajaxShowMessage(data.error, false);
                        } else {
                            Functions.ajaxRemoveMessage($messageBox);
                            if (deletingCurrentPage) {
                                DesignerMove.loadPage(null);
                            } else {
                                Functions.ajaxShowMessage(Messages.strSuccessfulPageDelete);
                            }
                        }
                    }); // end $.post()
                } else {
                    DesignerPage.deletePage(selected, function (success) {
                        if (! success) {
                            Functions.ajaxShowMessage('Error', false);
                        } else {
                            Functions.ajaxRemoveMessage($messageBox);
                            if (deletingCurrentPage) {
                                DesignerMove.loadPage(null);
                            } else {
                                Functions.ajaxShowMessage(Messages.strSuccessfulPageDelete);
                            }
                        }
                    });
                }

                $('#designerModalGoButton').off('click');// Unregister the event for other modals to not call this one
                modal.modal('hide');
            });
        }
    }); // end $.post()
};

// ------------------------------ SAVE AS PAGES ---------------------------------------
DesignerMove.saveAs = function () {
    var $msgbox = Functions.ajaxShowMessage();
    $.post('index.php?route=/database/designer', {
        'ajax_request': true,
        'server': server,
        'db': db,
        'dialog': 'save_as'
    }, function (data) {
        if (data.success === false) {
            Functions.ajaxShowMessage(data.error, false);
        } else {
            Functions.ajaxRemoveMessage($msgbox);

            if (! designerTablesEnabled) {
                DesignerPage.createPageList(db, function (options) {
                    $('#selected_page').append(options);
                });
            }

            var modal = DesignerMove.displayModal(data.message, Messages.strSavePageAs, '#designerGoModal');
            $('#designerModalGoButton').on('click', function () {
                var $form           = $('#save_as_pages');
                var selectedValue  = $form.find('input[name="selected_value"]').val().trim();
                var $selectedPage  = $form.find('select[name="selected_page"]');
                var choice          = $form.find('input[name="save_page"]:checked').val();
                var name            = '';

                if (choice === 'same') {
                    if ($selectedPage.val() === '0') {
                        Functions.ajaxShowMessage(Messages.strSelectPage, 2000);
                        return;
                    }
                    name = $selectedPage.find('option:selected').text();
                } else if (choice === 'new') {
                    if (selectedValue === '') {
                        Functions.ajaxShowMessage(Messages.strEnterValidPageName, 2000);
                        return;
                    }
                    name = selectedValue;
                }

                var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
                if (designerTablesEnabled) {
                    Functions.prepareForAjaxRequest($form);
                    $.post($form.attr('action'), $form.serialize() + DesignerMove.getUrlPos(), function (data) {
                        if (data.success === false) {
                            Functions.ajaxShowMessage(data.error, false);
                        } else {
                            Functions.ajaxRemoveMessage($msgbox);
                            DesignerMove.markSaved();
                            if (data.id) {
                                selectedPage = data.id;
                            }
                            DesignerMove.loadPage(selectedPage);
                        }
                    }); // end $.post()
                } else {
                    if (choice === 'same') {
                        var selectedPageId = $selectedPage.find('option:selected').val();
                        DesignerPage.saveToSelectedPage(db, selectedPageId, name, DesignerMove.getUrlPos(), function (page) {
                            Functions.ajaxRemoveMessage($msgbox);
                            DesignerMove.markSaved();
                            if (page.pgNr) {
                                selectedPage = page.pgNr;
                            }
                            DesignerMove.loadPage(selectedPage);
                        });
                    } else if (choice === 'new') {
                        DesignerPage.saveToNewPage(db, name, DesignerMove.getUrlPos(), function (page) {
                            Functions.ajaxRemoveMessage($msgbox);
                            DesignerMove.markSaved();
                            if (page.pgNr) {
                                selectedPage = page.pgNr;
                            }
                            DesignerMove.loadPage(selectedPage);
                        });
                    }
                }

                $('#designerModalGoButton').off('click');// Unregister the event for other modals to not call this one
                modal.modal('hide');
            });
            // select current page by default
            if (selectedPage !== -1) {
                $('select[name="selected_page"]').val(selectedPage);
            }
        }
    }); // end $.post()
};

DesignerMove.promptToSaveCurrentPage = function (callback) {
    if (change === 1 || selectedPage === -1) {
        var modal = DesignerMove.displayModal('<div>' + Messages.strLeavingPage + '</div>',
            Messages.strSavePage, '#designerPromptModal');
        $('#designerModalYesButton').on('click', function () {
            modal.modal('hide');
            DesignerMove.save3(callback);
        });
        $('#designerModalNoButton').on('click', function () {
            modal.modal('hide');
            callback();
        });
    } else {
        callback();
    }
};

// ------------------------------ EXPORT PAGES ---------------------------------------
DesignerMove.exportPages = function () {
    var $msgbox = Functions.ajaxShowMessage();
    var argsep = CommonParams.get('arg_separator');

    $.post('index.php?route=/database/designer', {
        'ajax_request': true,
        'server': server,
        'db': db,
        'dialog': 'export',
        'selected_page': selectedPage
    }, function (data) {
        if (data.success === false) {
            Functions.ajaxShowMessage(data.error, false);
        } else {
            Functions.ajaxRemoveMessage($msgbox);

            var $form = $(data.message);
            if (!designerTablesEnabled) {
                $form.append('<input type="hidden" name="offline_export" value="true">');
            }
            $.each(DesignerMove.getUrlPos(true).substring(1).split(argsep), function () {
                var pair = this.split('=');
                var input = $('<input type="hidden">');
                input.attr('name', pair[0]);
                input.attr('value', pair[1]);
                $form.append(input);
            });
            var $formatDropDown = $form.find('#plugins');
            $formatDropDown.on('change', function () {
                var format = $formatDropDown.val();
                $form.find('.format_specific_options').hide();
                $form.find('#' + format + '_options').show();
            }).trigger('change');

            var modal = DesignerMove.displayModal($form, Messages.strExportRelationalSchema, '#designerGoModal');
            $('#designerModalGoButton').on('click', function () {
                $('#id_export_pages').trigger('submit');
                $('#designerModalGoButton').off('click');// Unregister the event for other modals to not call this one
                modal.modal('hide');
            });
        }
    }); // end $.post()
};

DesignerMove.loadPage = function (page) {
    if (designerTablesEnabled) {
        var paramPage = '';
        var argsep = CommonParams.get('arg_separator');
        if (page !== null) {
            paramPage = argsep + 'page=' + page;
        }
        $('<a href="index.php?route=/database/designer&server=' + server + argsep + 'db=' + encodeURIComponent(db) + paramPage + '"></a>')
            .appendTo($('#page_content'))
            .trigger('click');
    } else {
        if (page === null) {
            DesignerPage.showTablesInLandingPage(db);
        } else if (page > -1) {
            DesignerPage.loadHtmlForPage(page);
        } else if (page === -1) {
            DesignerPage.showNewPageTables(true);
        }
    }
    DesignerMove.markSaved();
};

DesignerMove.grid = function () {
    var valueSent = '';
    if (!onGrid) {
        onGrid = 1;
        valueSent = 'on';
        document.getElementById('grid_button').className = 'M_butt_Selected_down';
    } else {
        document.getElementById('grid_button').className = 'M_butt';
        onGrid = 0;
        valueSent = 'off';
    }
    DesignerMove.saveValueInConfig('snap_to_grid', valueSent);
};

DesignerMove.angularDirect = function () {
    var valueSent = '';
    if (onAngularDirect) {
        onAngularDirect = 0;
        valueSent = 'angular';
        document.getElementById('angular_direct_button').className = 'M_butt_Selected_down';
    } else {
        onAngularDirect = 1;
        valueSent = 'direct';
        document.getElementById('angular_direct_button').className = 'M_butt';
    }
    DesignerMove.saveValueInConfig('angular_direct', valueSent);
    DesignerMove.reload();
};

DesignerMove.saveValueInConfig = function (indexSent, valueSent) {
    $.post('index.php?route=/database/designer',
        {
            'operation': 'save_setting_value',
            'index': indexSent,
            'ajax_request': true,
            'server': server,
            'value': valueSent
        },
        function (data) {
            if (data.success === false) {
                Functions.ajaxShowMessage(data.error, false);
            }
        });
};

// ++++++++++++++++++++++++++++++ RELATION ++++++++++++++++++++++++++++++++++++++
DesignerMove.startRelation = function () {
    if (onDisplayField) {
        return;
    }

    if (!onRelation) {
        document.getElementById('foreign_relation').style.display = '';
        onRelation = 1;
        document.getElementById('designer_hint').innerHTML = Messages.strSelectReferencedKey;
        document.getElementById('designer_hint').style.display = 'block';
        document.getElementById('rel_button').className = 'M_butt_Selected_down';
    } else {
        document.getElementById('designer_hint').innerHTML = '';
        document.getElementById('designer_hint').style.display = 'none';
        document.getElementById('rel_button').className = 'M_butt';
        clickField = 0;
        onRelation = 0;
    }
};

// table field
DesignerMove.clickField = function (db, T, f, pk) {
    var pkLocal = parseInt(pk);
    var argsep = CommonParams.get('arg_separator');
    if (onRelation) {
        if (!clickField) {
            // .style.display=='none'        .style.display = 'none'
            if (!pkLocal) {
                alert(Messages.strPleaseSelectPrimaryOrUniqueKey);
                return;// 0;
            }// PK
            if (jTabs[db + '.' + T] !== 1) {
                document.getElementById('foreign_relation').style.display = 'none';
            }
            clickField = 1;
            linkRelation = 'DB1=' + db + argsep + 'T1=' + T + argsep + 'F1=' + f;
            document.getElementById('designer_hint').innerHTML = Messages.strSelectForeignKey;
        } else {
            DesignerMove.startRelation(); // hidden hint...
            if (jTabs[db + '.' + T] !== 1 || !pkLocal) {
                document.getElementById('foreign_relation').style.display = 'none';
            }
            var left = globX - (document.getElementById('layer_new_relation').offsetWidth >> 1);
            document.getElementById('layer_new_relation').style.left = left + 'px';
            var top = globY - document.getElementById('layer_new_relation').offsetHeight;
            document.getElementById('layer_new_relation').style.top  = top + 'px';
            document.getElementById('layer_new_relation').style.display = 'block';
            linkRelation += argsep + 'DB2=' + db + argsep + 'T2=' + T + argsep + 'F2=' + f;
        }
    }

    if (onDisplayField) {
        var fieldNameToSend = decodeURIComponent(f);
        var newDisplayFieldClass = 'tab_field';
        var oldTabField = document.getElementById('id_tr_' + T + '.' + displayField[T]);
        // if is display field
        if (displayField[T] === f) {// The display field is already the one defined, user wants to remove it
            newDisplayFieldClass = 'tab_field';
            delete displayField[T];
            if (oldTabField) {// Clear the style
                // Set display field class on old item
                oldTabField.className = 'tab_field';
            }
            fieldNameToSend = '';
        } else {
            newDisplayFieldClass = 'tab_field_3';
            if (displayField[T]) { // Had a previous one, clear it
                if (oldTabField) {
                    // Set display field class on old item
                    oldTabField.className = 'tab_field';
                }
                delete displayField[T];
            }
            displayField[T] = f;

            var tabField = document.getElementById('id_tr_' + T + '.' + displayField[T]);
            if (tabField) {
                // Set new display field class
                tabField.className = newDisplayFieldClass;
            }
        }
        onDisplayField = 0;
        document.getElementById('designer_hint').innerHTML = '';
        document.getElementById('designer_hint').style.display = 'none';
        document.getElementById('display_field_button').className = 'M_butt';

        var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
        $.post('index.php?route=/database/designer',
            {
                'operation': 'setDisplayField',
                'ajax_request': true,
                'server': server,
                'db': db,
                'table': T,
                'field': fieldNameToSend
            },
            function (data) {
                if (data.success === false) {
                    Functions.ajaxShowMessage(data.error, false);
                } else {
                    Functions.ajaxRemoveMessage($msgbox);
                    Functions.ajaxShowMessage(Messages.strModificationSaved);
                }
            });
    }
};

DesignerMove.newRelation = function () {
    document.getElementById('layer_new_relation').style.display = 'none';
    var argsep = CommonParams.get('arg_separator');
    linkRelation += argsep + 'server=' + server + argsep + 'db=' + db + argsep + 'db2=p';
    linkRelation += argsep + 'on_delete=' + document.getElementById('on_delete').value + argsep + 'on_update=' + document.getElementById('on_update').value;
    linkRelation += argsep + 'operation=addNewRelation' + argsep + 'ajax_request=true';

    var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
    $.post('index.php?route=/database/designer', linkRelation, function (data) {
        if (data.success === false) {
            Functions.ajaxShowMessage(data.error, false);
        } else {
            Functions.ajaxRemoveMessage($msgbox);
            Functions.ajaxShowMessage(data.message);
            DesignerMove.loadPage(selectedPage);
        }
    }); // end $.post()
};

// -------------------------- create tables -------------------------------------
DesignerMove.startTableNew = function () {
    CommonParams.set('table', '');
    CommonActions.refreshMain('index.php?route=/table/create');
};

DesignerMove.startTabUpd = function (db, table) {
    CommonParams.set('db', db);
    CommonParams.set('table', table);
    CommonActions.refreshMain('index.php?route=/table/structure');
};

// --------------------------- hide tables --------------------------------------
// max/min all tables
DesignerMove.smallTabAll = function (idThis) {
    var icon = idThis.children[0];
    var valueSent = '';

    if (icon.alt === 'v') {
        $('.designer_tab .small_tab,.small_tab2').each(function (index, element) {
            if ($(element).text() === 'v') {
                DesignerMove.smallTab($(element).attr('table_name'), 0);
            }
        });
        icon.alt = '>';
        icon.src = icon.dataset.right;
        valueSent = 'v';
    } else {
        $('.designer_tab .small_tab,.small_tab2').each(function (index, element) {
            if ($(element).text() !== 'v') {
                DesignerMove.smallTab($(element).attr('table_name'), 0);
            }
        });
        icon.alt = 'v';
        icon.src = icon.dataset.down;
        valueSent = '>';
    }
    DesignerMove.saveValueInConfig('small_big_all', valueSent);
    $('#key_SB_all').toggleClass('M_butt_Selected_down');
    $('#key_SB_all').toggleClass('M_butt');
    DesignerMove.reload();
};

// invert max/min all tables
DesignerMove.smallTabInvert = function () {
    for (var key in jTabs) {
        DesignerMove.smallTab(key, 0);
    }
    DesignerMove.reload();
};

DesignerMove.relationLinesInvert = function () {
    showRelationLines = ! showRelationLines;
    DesignerMove.saveValueInConfig('relation_lines', showRelationLines);
    $('#relLineInvert').toggleClass('M_butt_Selected_down');
    $('#relLineInvert').toggleClass('M_butt');
    DesignerMove.reload();
};

DesignerMove.smallTabRefresh = function () {
    for (var key in jTabs) {
        if (document.getElementById('id_hide_tbody_' + key).innerHTML !== 'v') {
            DesignerMove.smallTab(key, 0);
        }
    }
};

DesignerMove.smallTab = function (t, reload) {
    var id      = document.getElementById('id_tbody_' + t);
    var idThis = document.getElementById('id_hide_tbody_' + t);
    if (idThis.innerHTML === 'v') {
        // ---CROSS
        id.style.display = 'none';
        idThis.innerHTML = '>';
    } else {
        id.style.display = '';
        idThis.innerHTML = 'v';
    }
    if (reload) {
        DesignerMove.reload();
    }
};

DesignerMove.selectTab = function (t) {
    var idZag = document.getElementById('id_zag_' + t);
    if (idZag.className !== 'tab_zag_3') {
        document.getElementById('id_zag_' + t).className = 'tab_zag_2';
    } else {
        document.getElementById('id_zag_' + t).className = 'tab_zag';
    }
    // ----------
    var idT = document.getElementById(t);
    window.scrollTo(parseInt(idT.style.left, 10) - 300, parseInt(idT.style.top, 10) - 300);
    setTimeout(
        function () {
            document.getElementById('id_zag_' + t).className = 'tab_zag';
        },
        800
    );
};

DesignerMove.canvasClick = function (id, event) {
    var n = 0;
    var selected = 0;
    var a = [];
    var Key0;
    var Key1;
    var Key2;
    var Key3;
    var Key;
    var x1;
    var x2;
    var K;
    var key;
    var key2;
    var key3;
    // eslint-disable-next-line compat/compat
    var localX = isIe ? event.clientX + document.body.scrollLeft : event.pageX;
    // eslint-disable-next-line compat/compat
    var localY = isIe ? event.clientY + document.body.scrollTop : event.pageY;
    localX -= $('#osn_tab').offset().left;
    localY -= $('#osn_tab').offset().top;
    DesignerMove.clear();
    var osnTab = document.getElementById('osn_tab');
    for (K in contr) {
        for (key in contr[K]) {
            for (key2 in contr[K][key]) {
                for (key3 in contr[K][key][key2]) {
                    if (! document.getElementById('check_vis_' + key2).checked ||
                        ! document.getElementById('check_vis_' + contr[K][key][key2][key3][0]).checked) {
                        continue; // if hide
                    }
                    var x1Left  = document.getElementById(key2).offsetLeft + 1;// document.getElementById(key2+"."+key3).offsetLeft;
                    var x1Right = x1Left + document.getElementById(key2).offsetWidth;
                    var x2Left  = document.getElementById(contr[K][key][key2][key3][0]).offsetLeft;// +document.getElementById(contr[K][key2][key3][0]+"."+contr[K][key2][key3][1]).offsetLeft
                    var x2Right = x2Left + document.getElementById(contr[K][key][key2][key3][0]).offsetWidth;
                    a[0] = Math.abs(x1Left - x2Left);
                    a[1] = Math.abs(x1Left - x2Right);
                    a[2] = Math.abs(x1Right - x2Left);
                    a[3] = Math.abs(x1Right - x2Right);
                    n = sLeft = sRight = 0;
                    for (var i = 1; i < 4; i++) {
                        if (a[n] > a[i]) {
                            n = i;
                        }
                    }
                    if (n === 1) {
                        x1 = x1Left - smS;
                        x2 = x2Right + smS;
                        if (x1 < x2) {
                            n = 0;
                        }
                    }
                    if (n === 2) {
                        x1 = x1Right + smS;
                        x2 = x2Left - smS;
                        if (x1 > x2) {
                            n = 0;
                        }
                    }
                    if (n === 3) {
                        x1 = x1Right + smS;
                        x2 = x2Right + smS;
                        sRight = 1;
                    }
                    if (n === 0) {
                        x1 = x1Left - smS;
                        x2 = x2Left - smS;
                        sLeft    = 1;
                    }

                    var y1 = document.getElementById(key2).offsetTop + document.getElementById(key2 + '.' + key3).offsetTop + heightField;
                    var y2 = document.getElementById(contr[K][key][key2][key3][0]).offsetTop +
                                     document.getElementById(contr[K][key][key2][key3][0] + '.' + contr[K][key][key2][key3][1]).offsetTop + heightField;

                    if (!selected && localX > x1 - 10 && localX < x1 + 10 && localY > y1 - 7 && localY < y1 + 7) {
                        DesignerMove.drawLine0(
                            x1, x2, y1, y2, osnTab, 'rgba(255,0,0,1)'
                        );

                        selected = 1;
                        Key0 = contr[K][key][key2][key3][0];
                        Key1 = contr[K][key][key2][key3][1];
                        Key2 = key2;
                        Key3 = key3;
                        Key = K;
                    } else {
                        DesignerMove.drawLine0(
                            x1, x2, y1, y2, osnTab,
                            contr[K][key][key2][key3][0] + '.' + contr[K][key][key2][key3][1]
                        );
                    }
                }
            }
        }
    }
    if (selected) {
        // select relations
        var left = globX - (document.getElementById('layer_upd_relation').offsetWidth >> 1);
        document.getElementById('layer_upd_relation').style.left = left + 'px';
        var top = globY - document.getElementById('layer_upd_relation').offsetHeight - 10;
        document.getElementById('layer_upd_relation').style.top = top + 'px';
        document.getElementById('layer_upd_relation').style.display = 'block';
        var argsep = CommonParams.get('arg_separator');
        linkRelation = 'T1=' + Key0 + argsep + 'F1=' + Key1 + argsep + 'T2=' + Key2 + argsep + 'F2=' + Key3 + argsep + 'K=' + Key;
    }
};

DesignerMove.updRelation = function () {
    document.getElementById('layer_upd_relation').style.display = 'none';
    var argsep = CommonParams.get('arg_separator');
    linkRelation += argsep + 'server=' + server + argsep + 'db=' + db;
    linkRelation += argsep + 'operation=removeRelation' + argsep + 'ajax_request=true';

    var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
    $.post('index.php?route=/database/designer', linkRelation, function (data) {
        if (data.success === false) {
            Functions.ajaxShowMessage(data.error, false);
        } else {
            Functions.ajaxRemoveMessage($msgbox);
            Functions.ajaxShowMessage(data.message);
            DesignerMove.loadPage(selectedPage);
        }
    }); // end $.post()
};

DesignerMove.visibleTab = function (id, tN) {
    if (id.checked) {
        document.getElementById(tN).style.display = 'block';
    } else {
        document.getElementById(tN).style.display = 'none';
    }
    DesignerMove.reload();
    DesignerMove.markUnsaved();
};

// max/min all tables
DesignerMove.hideTabAll = function (idThis) {
    if (idThis.alt === 'v') {
        idThis.alt = '>';
        idThis.src = idThis.dataset.right;
    } else {
        idThis.alt = 'v';
        idThis.src = idThis.dataset.down;
    }
    var E = document.getElementById('container-form');
    var EelementsLength = E.elements.length;
    for (var i = 0; i < EelementsLength; i++) {
        if (E.elements[i].type === 'checkbox' && E.elements[i].id.substring(0, 10) === 'check_vis_') {
            if (idThis.alt === 'v') {
                E.elements[i].checked = true;
                document.getElementById(E.elements[i].value).style.display = '';
            } else {
                E.elements[i].checked = false;
                document.getElementById(E.elements[i].value).style.display = 'none';
            }
        }
    }
    DesignerMove.reload();
};

DesignerMove.inArrayK = function (x, m) {
    var b = 0;
    for (var u in m) {
        if (x === u) {
            b = 1;
            break;
        }
    }
    return b;
};

DesignerMove.noHaveConstr = function (idThis) {
    var a = [];
    var K;
    var key;
    var key2;
    var key3;
    for (K in contr) {
        for (key in contr[K]) {
            // contr name
            for (key2 in contr[K][key]) {
                // table name
                for (key3 in contr[K][key][key2]) {
                    // field name
                    a[key2] = a[contr[K][key][key2][key3][0]] = 1; // exist constr
                }
            }
        }
    }

    if (idThis.alt === 'v') {
        idThis.alt = '>';
        idThis.src = idThis.dataset.right;
    } else {
        idThis.alt = 'v';
        idThis.src = idThis.dataset.down;
    }
    var E = document.getElementById('container-form');
    var EelementsLength = E.elements.length;
    for (var i = 0; i < EelementsLength; i++) {
        if (E.elements[i].type === 'checkbox' && E.elements[i].id.substring(0, 10) === 'check_vis_') {
            if (!DesignerMove.inArrayK(E.elements[i].value, a)) {
                if (idThis.alt === 'v') {
                    E.elements[i].checked = true;
                    document.getElementById(E.elements[i].value).style.display = '';
                } else {
                    E.elements[i].checked = false;
                    document.getElementById(E.elements[i].value).style.display = 'none';
                }
            }
        }
    }
};

DesignerMove.generalScroll = function () {
    // if (timeoutId)
    clearTimeout(timeoutId);
    timeoutId = setTimeout(
        function () {
            // eslint-disable-next-line compat/compat
            document.getElementById('top_menu').style.left = document.body.scrollLeft + 'px';
            // eslint-disable-next-line compat/compat
            document.getElementById('top_menu').style.top  = document.body.scrollTop + 'px';
        },
        200
    );
};

// max/min all tables
DesignerMove.showLeftMenu = function (idThis) {
    var icon = idThis.children[0];
    $('#key_Show_left_menu').toggleClass('M_butt_Selected_down');
    if (icon.alt === 'v') {
        document.getElementById('layer_menu').style.top = '0px';
        document.getElementById('layer_menu').style.display = 'block';
        icon.alt = '>';
        icon.src = icon.dataset.up;
        if (isIe) {
            DesignerMove.generalScroll();
        }
    } else {
        document.getElementById('layer_menu').style.top = -1000 + 'px'; // fast scroll
        document.getElementById('layer_menu').style.display = 'none';
        icon.alt = 'v';
        icon.src = icon.dataset.down;
    }
};

DesignerMove.sideMenuRight = function (idThis) {
    $('#side_menu').toggleClass('right');
    $('#layer_menu').toggleClass('float-start');
    var moveMenuIcon = $(idThis.getElementsByTagName('img')[0]);
    var resizeIcon = $('#layer_menu_sizer > img')
        .toggleClass('float-start')
        .toggleClass('float-end');

    var srcResizeIcon = resizeIcon.attr('src');
    resizeIcon.attr('src', resizeIcon.attr('data-right'));
    resizeIcon.attr('data-right', srcResizeIcon);

    var srcMoveIcon = moveMenuIcon.attr('src');
    moveMenuIcon.attr('src', moveMenuIcon.attr('data-right'));
    moveMenuIcon.attr('data-right', srcMoveIcon);

    menuMoved = !menuMoved;
    DesignerMove.saveValueInConfig('side_menu', $('#side_menu').hasClass('right'));
    $('#key_Left_Right').toggleClass('M_butt_Selected_down');
    $('#key_Left_Right').toggleClass('M_butt');
};

DesignerMove.showText = function () {
    $('#side_menu').find('.hidable').show();
};

DesignerMove.hideText = function () {
    if (!alwaysShowText) {
        $('#side_menu').find('.hidable').hide();
    }
};

DesignerMove.pinText = function () {
    alwaysShowText = !alwaysShowText;
    $('#pin_Text').toggleClass('M_butt_Selected_down');
    $('#pin_Text').toggleClass('M_butt');
    DesignerMove.saveValueInConfig('pin_text', alwaysShowText);
};

DesignerMove.startDisplayField = function () {
    if (onRelation) {
        return;
    }
    if (!onDisplayField) {
        onDisplayField = 1;
        document.getElementById('designer_hint').innerHTML = Messages.strChangeDisplay;
        document.getElementById('designer_hint').style.display = 'block';
        document.getElementById('display_field_button').className = 'M_butt_Selected_down';// '#FFEE99';gray #AAAAAA

        if (isIe) { // correct for IE
            document.getElementById('display_field_button').className = 'M_butt_Selected_down_IE';
        }
    } else {
        document.getElementById('designer_hint').innerHTML = '';
        document.getElementById('designer_hint').style.display = 'none';
        document.getElementById('display_field_button').className = 'M_butt';
        onDisplayField = 0;
    }
};

var TargetColors = [];

DesignerMove.getColorByTarget = function (target) {
    var color = '';  // "rgba(0,100,150,1)";

    for (var targetColor in TargetColors) {
        if (TargetColors[targetColor][0] === target) {
            color = TargetColors[targetColor][1];
            break;
        }
    }

    if (color.length === 0) {
        var i = TargetColors.length + 1;
        var d = i % 6;
        var j = (i - d) / 6;
        j = j % 4;
        j++;
        var colorCase = [
            [1, 0, 0],
            [0, 1, 0],
            [0, 0, 1],
            [1, 1, 0],
            [1, 0, 1],
            [0, 1, 1]
        ];
        var a = colorCase[d][0];
        var b = colorCase[d][1];
        var c = colorCase[d][2];
        var e = (1 - (j - 1) / 6);

        var r = Math.round(a * 200 * e);
        var g = Math.round(b * 200 * e);
        b = Math.round(c * 200 * e);
        color = 'rgba(' + r + ',' + g + ',' + b + ',1)';

        TargetColors.push([target, color]);
    }

    return color;
};

DesignerMove.clickOption = function (dbName, tableName, columnName, tableDbNameUrl, optionColNameString) {
    var designerOptions = document.getElementById('designer_optionse');
    var left = globX - (designerOptions.offsetWidth >> 1);
    designerOptions.style.left = left + 'px';
    // var top = Glob_Y - designerOptions.offsetHeight - 10;
    designerOptions.style.top  = (screen.height / 4) + 'px';
    designerOptions.style.display = 'block';
    document.getElementById('ok_add_object_db_and_table_name_url').value = tableDbNameUrl;
    document.getElementById('ok_add_object_db_name').value = dbName;
    document.getElementById('ok_add_object_table_name').value = tableName;
    document.getElementById('ok_add_object_col_name').value = columnName;
    document.getElementById('option_col_name').innerHTML = optionColNameString;
};

DesignerMove.closeOption = function () {
    document.getElementById('designer_optionse').style.display = 'none';
    document.getElementById('rel_opt').value = '--';
    document.getElementById('Query').value = '';
    document.getElementById('new_name').value = '';
    document.getElementById('operator').value = '---';
    document.getElementById('groupby').checked = false;
    document.getElementById('h_rel_opt').value = '--';
    document.getElementById('h_operator').value = '---';
    document.getElementById('having').value = '';
    document.getElementById('orderby').value = '---';
};

DesignerMove.selectAll = function (tableName, dbName, idSelectAll) {
    var parentIsChecked = $('#' + idSelectAll).is(':checked');
    var checkboxAll = $('#container-form input[id_check_all=\'' + idSelectAll + '\']:checkbox');

    checkboxAll.each(function () {
        // already checked and then check parent
        if (parentIsChecked === true && this.checked) {
            // was checked, removing column from selected fields
            // trigger unchecked event
            this.click();
        }
        this.checked = parentIsChecked;
        this.disabled = parentIsChecked;
    });
    if (parentIsChecked) {
        selectField.push('`' + tableName + '`.*');
        fromArray.push(tableName);
    } else {
        var i;
        for (i = 0; i < selectField.length; i++) {
            if (selectField[i] === ('`' + tableName + '`.*')) {
                selectField.splice(i, 1);
            }
        }
        var k;
        for (k = 0; k < fromArray.length; k++) {
            if (fromArray[k] === tableName) {
                fromArray.splice(k, 1);
                break;
            }
        }
    }
    DesignerMove.reload();
};

DesignerMove.tableOnOver = function (idThis, val, buil) {
    var builLocal = parseInt(buil);
    if (!val) {
        document.getElementById('id_zag_' + idThis).className = 'tab_zag_2';
        if (builLocal) {
            document.getElementById('id_zag_' + idThis + '_2').className = 'tab_zag_2';
        }
    } else {
        document.getElementById('id_zag_' + idThis).className = 'tab_zag';
        if (builLocal) {
            document.getElementById('id_zag_' + idThis + '_2').className = 'tab_zag';
        }
    }
};

/**
 * This function stores selected column information in selectField[]
 * In case column is checked it add else it deletes
 *
 * @param {string} tableName
 * @param {string} colName
 * @param {string} checkboxId
 */
DesignerMove.storeColumn = function (tableName, colName, checkboxId) {
    var i;
    var k;
    var selectKeyField = '`' + tableName + '`.`' + colName + '`';
    if (document.getElementById(checkboxId).checked === true) {
        selectField.push(selectKeyField);
        fromArray.push(tableName);
    } else {
        for (i = 0; i < selectField.length; i++) {
            if (selectField[i] === selectKeyField) {
                selectField.splice(i, 1);
                break;
            }
        }
        for (k = 0; k < fromArray.length; k++) {
            if (fromArray[k] === tableName) {
                fromArray.splice(k, 1);
                break;
            }
        }
    }
};

/**
 * This function builds object and adds them to historyArray
 * first it does a few checks on each object, then makes an object(where,rename,groupby,aggregate,orderby)
 * then a new history object is made and finally all these history objects are added to historyArray[]
 *
 * @param {string} dbName
 * @param {string} tableName
 * @param {string} colName
 * @param {string} dbTableNameUrl
 */
DesignerMove.addObject = function (dbName, tableName, colName, dbTableNameUrl) {
    var p;
    var whereObj;
    var rel = document.getElementById('rel_opt');
    var sum = 0;
    var init = historyArray.length;
    if (rel.value !== '--') {
        if (document.getElementById('Query').value === '') {
            Functions.ajaxShowMessage(Functions.sprintf(Messages.strQueryEmpty));
            return;
        }
        p = document.getElementById('Query');
        whereObj = new DesignerHistory.Where(rel.value, p.value);// make where object
        historyArray.push(new DesignerHistory.HistoryObj(colName, whereObj, tableName, hTabs[dbTableNameUrl], 'Where'));
        sum = sum + 1;
    }
    if (document.getElementById('new_name').value !== '') {
        var renameObj = new DesignerHistory.Rename(document.getElementById('new_name').value);// make Rename object
        historyArray.push(new DesignerHistory.HistoryObj(colName, renameObj, tableName, hTabs[dbTableNameUrl], 'Rename'));
        sum = sum + 1;
    }
    if (document.getElementById('operator').value !== '---') {
        var aggregateObj = new DesignerHistory.Aggregate(document.getElementById('operator').value);
        historyArray.push(new DesignerHistory.HistoryObj(colName, aggregateObj, tableName, hTabs[dbTableNameUrl], 'Aggregate'));
        sum = sum + 1;
        // make aggregate operator
    }
    if (document.getElementById('groupby').checked === true) {
        historyArray.push(new DesignerHistory.HistoryObj(colName, 'GroupBy', tableName, hTabs[dbTableNameUrl], 'GroupBy'));
        sum = sum + 1;
        // make groupby
    }
    if (document.getElementById('h_rel_opt').value !== '--') {
        if (document.getElementById('having').value === '') {
            return;
        }
        whereObj = new DesignerHistory.Having(
            document.getElementById('h_rel_opt').value,
            document.getElementById('having').value,
            document.getElementById('h_operator').value
        );// make where object
        historyArray.push(new DesignerHistory.HistoryObj(colName, whereObj, tableName, hTabs[dbTableNameUrl], 'Having'));
        sum = sum + 1;
        // make having
    }
    if (document.getElementById('orderby').value !== '---') {
        var orderByObj = new DesignerHistory.OrderBy(document.getElementById('orderby').value);
        historyArray.push(new DesignerHistory.HistoryObj(colName, orderByObj, tableName, hTabs[dbTableNameUrl], 'OrderBy'));
        sum = sum + 1;
        // make orderby
    }
    Functions.ajaxShowMessage(Functions.sprintf(Messages.strObjectsCreated, sum));
    // output sum new objects created
    var existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = DesignerHistory.display(init, historyArray.length);
    DesignerMove.closeOption();
    $('#ab').accordion('refresh');
};

DesignerMove.enablePageContentEvents = function () {
    $(document).off('mousedown');
    $(document).off('mouseup');
    $(document).off('mousemove');
    $(document).on('mousedown', function (e) {
        DesignerMove.mouseDown(e);
    });
    $(document).on('mouseup', function (e) {
        DesignerMove.mouseUp(e);
    });
    $(document).on('mousemove', function (e) {
        DesignerMove.mouseMove(e);
    });
};

/**
 * This function enables the events on table items.
 * It helps to enable them on page loading and when a table is added on the fly.
 * @param {number} index
 * @param {object} element
 */
DesignerMove.enableTableEvents = function (index, element) {
    $(element).on('click', '.select_all_1', function () {
        DesignerMove.selectAll($(this).attr('table_name'), $(this).attr('db_name'), $(this).attr('id'));
    });
    $(element).on('click', '.small_tab,.small_tab2', function () {
        DesignerMove.smallTab($(this).attr('table_name'), 1);
    });
    $(element).on('click', '.small_tab_pref_1', function () {
        DesignerMove.startTabUpd($(this).attr('db_url'), $(this).attr('table_name_url'));
    });
    $(element).on('click', '.select_all_store_col', function () {
        DesignerMove.storeColumn($(this).attr('table_name'), $(this).attr('col_name'), $(this).attr('id'));
    });
    $(element).on('click', '.small_tab_pref_click_opt', function () {
        DesignerMove.clickOption(
            $(this).attr('db_name'),
            $(this).attr('table_name'),
            $(this).attr('col_name'),
            $(this).attr('db_table_name_url'),
            $(this).attr('option_col_name_modal')
        );
    });
    $(element).on('click', '.tab_field_2,.tab_field_3,.tab_field', function () {
        var params = ($(this).attr('click_field_param')).split(',');
        DesignerMove.clickField(params[3], params[0], params[1], params[2]);
    });

    $(element).find('.tab_zag_noquery').on('mouseover', function () {
        DesignerMove.tableOnOver($(this).attr('table_name'),0, $(this).attr('query_set'));
    });
    $(element).find('.tab_zag_noquery').on('mouseout', function () {
        DesignerMove.tableOnOver($(this).attr('table_name'),1, $(this).attr('query_set'));
    });
    $(element).find('.tab_zag_query').on('mouseover', function () {
        DesignerMove.tableOnOver($(this).attr('table_name'),0, 1);
    });
    $(element).find('.tab_zag_query').on('mouseout', function () {
        DesignerMove.tableOnOver($(this).attr('table_name'),1, 1);
    });

    DesignerMove.enablePageContentEvents();
};

AJAX.registerTeardown('designer/move.js', function () {
    $('#side_menu').off('mouseenter mouseleave');
    $('#key_Show_left_menu').off('click');
    $('#toggleFullscreen').off('click');
    $('#newPage').off('click');
    $('#editPage').off('click');
    $('#savePos').off('click');
    $('#SaveAs').off('click');
    $('#delPages').off('click');
    $('#StartTableNew').off('click');
    $('#rel_button').off('click');
    $('#StartTableNew').off('click');
    $('#display_field_button').off('click');
    $('#reloadPage').off('click');
    $('#angular_direct_button').off('click');
    $('#grid_button').off('click');
    $('#key_SB_all').off('click');
    $('#SmallTabInvert').off('click');
    $('#relLineInvert').off('click');
    $('#exportPages').off('click');
    $('#query_builder').off('click');
    $('#key_Left_Right').off('click');
    $('#pin_Text').off('click');
    $('#canvas').off('click');
    $('#key_HS_all').off('click');
    $('#key_HS').off('click');
    $('.scroll_tab_struct').off('click');
    $('.scroll_tab_checkbox').off('click');
    $('#id_scroll_tab').find('tr').off('click', '.designer_Tabs2,.designer_Tabs');
    $('.designer_tab').off('click', '.select_all_1');
    $('.designer_tab').off('click', '.small_tab,.small_tab2');
    $('.designer_tab').off('click', '.small_tab_pref_1');
    $('.tab_zag_noquery').off('mouseover');
    $('.tab_zag_noquery').off('mouseout');
    $('.tab_zag_query').off('mouseover');
    $('.tab_zag_query').off('mouseout');
    $('.designer_tab').off('click','.tab_field_2,.tab_field_3,.tab_field');
    $('.designer_tab').off('click', '.select_all_store_col');
    $('.designer_tab').off('click', '.small_tab_pref_click_opt');
    $('#del_button').off('click');
    $('#cancel_button').off('click');
    $('#ok_add_object').off('click');
    $('#cancel_close_option').off('click');
    $('#ok_new_rel_panel').off('click');
    $('#cancel_new_rel_panel').off('click');
    $(document).off('mouseup');
    $(document).off('mousedown');
    $(document).off('mousemove');
});

AJAX.registerOnload('designer/move.js', function () {
    $('#key_Show_left_menu').on('click', function () {
        DesignerMove.showLeftMenu(this);
        return false;
    });
    $('#toggleFullscreen').on('click', function () {
        DesignerMove.toggleFullscreen();
        return false;
    });
    $('#addOtherDbTables').on('click', function () {
        DesignerMove.addOtherDbTables();
        return false;
    });
    $('#newPage').on('click', function () {
        DesignerMove.new();
        return false;
    });
    $('#editPage').on('click', function () {
        DesignerMove.editPages();
        return false;
    });
    $('#savePos').on('click', function () {
        DesignerMove.save3();
        return false;
    });
    $('#SaveAs').on('click', function () {
        DesignerMove.saveAs();
        $(document).on('ajaxStop', function () {
            $('#selected_value').on('click', function () {
                $('#savePageNewRadio').prop('checked', true);
            });
        });
        return false;
    });
    $('#delPages').on('click', function () {
        DesignerMove.deletePages();
        return false;
    });
    $('#StartTableNew').on('click', function () {
        DesignerMove.startTableNew();
        return false;
    });
    $('#rel_button').on('click', function () {
        DesignerMove.startRelation();
        return false;
    });
    $('#display_field_button').on('click', function () {
        DesignerMove.startDisplayField();
        return false;
    });
    $('#reloadPage').on('click', function () {
        DesignerMove.loadPage(selectedPage);
    });
    $('#angular_direct_button').on('click', function () {
        DesignerMove.angularDirect();
        return false;
    });
    $('#grid_button').on('click', function () {
        DesignerMove.grid();
        return false;
    });
    $('#key_SB_all').on('click', function () {
        DesignerMove.smallTabAll(this);
        return false;
    });
    $('#SmallTabInvert').on('click', function () {
        DesignerMove.smallTabInvert();
        return false;
    });
    $('#relLineInvert').on('click', function () {
        DesignerMove.relationLinesInvert();
        return false;
    });
    $('#exportPages').on('click', function () {
        DesignerMove.exportPages();
        return false;
    });
    $('#query_builder').on('click', function () {
        DesignerHistory.buildQuery('SQL Query on Database', 0);
    });
    $('#key_Left_Right').on('click', function () {
        DesignerMove.sideMenuRight(this);
        return false;
    });
    $('#side_menu').on('mouseenter', function () {
        DesignerMove.showText();
        return false;
    });
    $('#side_menu').on('mouseleave', function () {
        DesignerMove.hideText();
        return false;
    });
    $('#pin_Text').on('click', function () {
        DesignerMove.pinText(this);
        return false;
    });
    $('#canvas').on('click', function (event) {
        DesignerMove.canvasClick(this, event);
    });
    $('#key_HS_all').on('click', function () {
        DesignerMove.hideTabAll(this);
        return false;
    });
    $('#key_HS').on('click', function () {
        DesignerMove.noHaveConstr(this);
        return false;
    });

    $('.designer_tab').each(DesignerMove.enableTableEvents);
    $('.designer_tab').each(DesignerMove.addTableToTablesList);

    $('input#del_button').on('click', function () {
        DesignerMove.updRelation();
    });
    $('input#cancel_button').on('click', function () {
        document.getElementById('layer_upd_relation').style.display = 'none';
        DesignerMove.reload();
    });
    $('input#ok_add_object').on('click', function () {
        DesignerMove.addObject(
            $('#ok_add_object_db_name').val(),
            $('#ok_add_object_table_name').val(),
            $('#ok_add_object_col_name').val(),
            $('#ok_add_object_db_and_table_name_url').val()
        );
    });
    $('input#cancel_close_option').on('click', function () {
        DesignerMove.closeOption();
    });
    $('input#ok_new_rel_panel').on('click', function () {
        DesignerMove.newRelation();
    });
    $('input#cancel_new_rel_panel').on('click', function () {
        document.getElementById('layer_new_relation').style.display = 'none';
    });
    DesignerMove.enablePageContentEvents();
});
