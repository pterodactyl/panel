/* global firstDayOfCalendar */ // templates/javascript/variables.twig

/**
 * Create advanced table (resize, reorder, and show/hide columns; and also grid editing).
 * This function is designed mainly for table DOM generated from browsing a table in the database.
 * For using this function in other table DOM, you may need to:
 * - add "draggable" class in the table header <th>, in order to make it resizable, sortable or hidable
 * - have at least one non-"draggable" header in the table DOM for placing column visibility drop-down arrow
 * - pass the value "false" for the parameter "enableGridEdit"
 * - adjust other parameter value, to select which features that will be enabled
 *
 * @param t the table DOM element
 * @param enableResize Optional, if false, column resizing feature will be disabled
 * @param enableReorder Optional, if false, column reordering feature will be disabled
 * @param enableVisib Optional, if false, show/hide column feature will be disabled
 * @param enableGridEdit Optional, if false, grid editing feature will be disabled
 */
// eslint-disable-next-line no-unused-vars
var makeGrid = function (t, enableResize, enableReorder, enableVisib, enableGridEdit) {
    var isResizeEnabled = enableResize === undefined ? true : enableResize;
    var isReorderEnabled = enableReorder === undefined ? true : enableReorder;
    var isVisibEnabled = enableVisib === undefined ? true : enableVisib;
    var isGridEditEnabled = enableGridEdit === undefined ? true : enableGridEdit;

    var g = {
        /** *********
         * Constant
         ***********/
        minColWidth: 15,


        /** *********
         * Variables, assigned with default value, changed later
         ***********/
        actionSpan: 5,              // number of colspan in Actions header in a table
        tableCreateTime: null,      // table creation time, used for saving column order and visibility to server, only available in "Browse tab"

        // Column reordering variables
        colOrder: [],      // array of column order

        // Column visibility variables
        colVisib: [],      // array of column visibility
        showAllColText: '',         // string, text for "show all" button under column visibility list
        visibleHeadersCount: 0,     // number of visible data headers

        // Table hint variables
        reorderHint: '',            // string, hint for column reordering
        sortHint: '',               // string, hint for column sorting
        markHint: '',               // string, hint for column marking
        copyHint: '',               // string, hint for copy column name
        showReorderHint: false,
        showSortHint: false,
        showMarkHint: false,

        // Grid editing
        isCellEditActive: false,    // true if current focus is in edit cell
        isEditCellTextEditable: false,  // true if current edit cell is editable in the text input box (not textarea)
        currentEditCell: null,      // reference to <td> that currently being edited
        cellEditHint: '',           // hint shown when doing grid edit
        gotoLinkText: '',           // "Go to link" text
        wasEditedCellNull: false,   // true if last value of the edited cell was NULL
        maxTruncatedLen: 0,         // number of characters that can be displayed in a cell
        saveCellsAtOnce: false,     // $cfg[saveCellsAtOnce]
        isCellEdited: false,        // true if at least one cell has been edited
        saveCellWarning: '',        // string, warning text when user want to leave a page with unsaved edited data
        lastXHR : null,             // last XHR object used in AJAX request
        isSaving: false,            // true when currently saving edited data, used to handle double posting caused by pressing ENTER in grid edit text box in Chrome browser
        alertNonUnique: '',         // string, alert shown when saving edited nonunique table

        // Common hidden inputs
        token: null,
        server: null,
        db: null,
        table: null,


        /** **********
         * Functions
         ************/

        /**
         * Start to resize column. Called when clicking on column separator.
         *
         * @param e event
         * @param obj dragged div object
         */
        dragStartRsz: function (e, obj) {
            var n = $(g.cRsz).find('div').index(obj);    // get the index of separator (i.e., column index)
            $(obj).addClass('colborder_active');
            g.colRsz = {
                x0: e.pageX,
                n: n,
                obj: obj,
                objLeft: $(obj).position().left,
                objWidth: $(g.t).find('th.draggable:visible').eq(n).find('span').outerWidth()
            };
            // eslint-disable-next-line compat/compat
            $(document.body).css('cursor', 'col-resize').noSelect();
            if (g.isCellEditActive) {
                g.hideEditCell();
            }
        },

        /**
         * Start to reorder column. Called when clicking on table header.
         *
         * @param e event
         * @param obj table header object
         */
        dragStartReorder: function (e, obj) {
            // prepare the cCpy (column copy) and cPointer (column pointer) from the dragged column
            $(g.cCpy).text($(obj).text());
            var objPos = $(obj).position();
            $(g.cCpy).css({
                top: objPos.top + 20,
                left: objPos.left,
                height: $(obj).height(),
                width: $(obj).width()
            });
            $(g.cPointer).css({
                top: objPos.top
            });

            // get the column index, zero-based
            var n = g.getHeaderIdx(obj);

            g.colReorder = {
                x0: e.pageX,
                y0: e.pageY,
                n: n,
                newn: n,
                obj: obj,
                objTop: objPos.top,
                objLeft: objPos.left
            };

            // eslint-disable-next-line compat/compat
            $(document.body).css('cursor', 'move').noSelect();
            if (g.isCellEditActive) {
                g.hideEditCell();
            }
        },

        /**
         * Handle mousemove event when dragging.
         *
         * @param e event
         */
        dragMove: function (e) {
            var dx;
            if (g.colRsz) {
                dx = e.pageX - g.colRsz.x0;
                if (g.colRsz.objWidth + dx > g.minColWidth) {
                    $(g.colRsz.obj).css('left', g.colRsz.objLeft + dx + 'px');
                }
            } else if (g.colReorder) {
                // dragged column animation
                dx = e.pageX - g.colReorder.x0;
                $(g.cCpy)
                    .css('left', g.colReorder.objLeft + dx)
                    .show();

                // pointer animation
                var hoveredCol = g.getHoveredCol(e);
                if (hoveredCol) {
                    var newn = g.getHeaderIdx(hoveredCol);
                    g.colReorder.newn = newn;
                    if (newn !== g.colReorder.n) {
                        // show the column pointer in the right place
                        var colPos = $(hoveredCol).position();
                        var newleft = newn < g.colReorder.n ?
                            colPos.left :
                            colPos.left + $(hoveredCol).outerWidth();
                        $(g.cPointer)
                            .css({
                                left: newleft,
                                visibility: 'visible'
                            });
                    } else {
                        // no movement to other column, hide the column pointer
                        $(g.cPointer).css('visibility', 'hidden');
                    }
                }
            }
        },

        /**
         * Stop the dragging action.
         *
         * @param e event
         */
        dragEnd: function (e) {
            if (g.colRsz) {
                var dx = e.pageX - g.colRsz.x0;
                var nw = g.colRsz.objWidth + dx;
                if (nw < g.minColWidth) {
                    nw = g.minColWidth;
                }
                var n = g.colRsz.n;
                // do the resizing
                g.resize(n, nw);

                g.reposRsz();
                g.reposDrop();
                g.colRsz = false;
                $(g.cRsz).find('div').removeClass('colborder_active');
            } else if (g.colReorder) {
                // shift columns
                if (g.colReorder.newn !== g.colReorder.n) {
                    g.shiftCol(g.colReorder.n, g.colReorder.newn);
                    // assign new position
                    var objPos = $(g.colReorder.obj).position();
                    g.colReorder.objTop = objPos.top;
                    g.colReorder.objLeft = objPos.left;
                    g.colReorder.n = g.colReorder.newn;
                    // send request to server to remember the column order
                    if (g.tableCreateTime) {
                        g.sendColPrefs();
                    }
                    g.refreshRestoreButton();
                }

                // animate new column position
                $(g.cCpy).stop(true, true)
                    .animate({
                        top: g.colReorder.objTop,
                        left: g.colReorder.objLeft
                    }, 'fast')
                    .fadeOut();
                $(g.cPointer).css('visibility', 'hidden');

                g.colReorder = false;
            }
            // eslint-disable-next-line compat/compat
            $(document.body).css('cursor', 'inherit').noSelect(false);
        },

        /**
         * Resize column n to new width "nw"
         *
         * @param n zero-based column index
         * @param nw new width of the column in pixel
         */
        resize: function (n, nw) {
            $(g.t).find('tr').each(function () {
                $(this).find('th.draggable:visible').eq(n).find('span')
                    .add($(this).find('td:visible').eq(g.actionSpan + n).find('span'))
                    .css('width', nw);
            });
        },

        /**
         * Reposition column resize bars.
         */
        reposRsz: function () {
            $(g.cRsz).find('div').hide();
            var $firstRowCols = $(g.t).find('tr').first().find('th.draggable:visible');
            var $resizeHandles = $(g.cRsz).find('div').removeClass('condition');
            $(g.t).find('table.pma_table').find('thead th').first().removeClass('before-condition');
            for (var n = 0, l = $firstRowCols.length; n < l; n++) {
                var $col = $($firstRowCols[n]);
                var colWidth;
                if (navigator.userAgent.toLowerCase().indexOf('safari') !== -1) {
                    colWidth = $col.outerWidth();
                } else {
                    colWidth = $col.outerWidth(true);
                }
                $($resizeHandles[n]).css('left', $col.position().left + colWidth)
                    .show();
                if ($col.hasClass('condition')) {
                    $($resizeHandles[n]).addClass('condition');
                    if (n > 0) {
                        $($resizeHandles[n - 1]).addClass('condition');
                    }
                }
            }
            if ($($resizeHandles[0]).hasClass('condition')) {
                $(g.t).find('thead th').first().addClass('before-condition');
            }
            $(g.cRsz).css('height', $(g.t).height());
        },

        /**
         * Shift column from index oldn to newn.
         *
         * @param oldn old zero-based column index
         * @param newn new zero-based column index
         */
        shiftCol: function (oldn, newn) {
            $(g.t).find('tr').each(function () {
                if (newn < oldn) {
                    $(this).find('th.draggable').eq(newn)
                        .add($(this).find('td').eq(g.actionSpan + newn))
                        .before($(this).find('th.draggable').eq(oldn)
                            .add($(this).find('td').eq(g.actionSpan + oldn)));
                } else {
                    $(this).find('th.draggable').eq(newn)
                        .add($(this).find('td').eq(g.actionSpan + newn))
                        .after($(this).find('th.draggable').eq(oldn)
                            .add($(this).find('td').eq(g.actionSpan + oldn)));
                }
            });
            // reposition the column resize bars
            g.reposRsz();

            // adjust the column visibility list
            if (newn < oldn) {
                $(g.cList).find('.lDiv div').eq(newn)
                    .before($(g.cList).find('.lDiv div').eq(oldn));
            } else {
                $(g.cList).find('.lDiv div').eq(newn)
                    .after($(g.cList).find('.lDiv div').eq(oldn));
            }
            // adjust the colOrder
            var tmp = g.colOrder[oldn];
            g.colOrder.splice(oldn, 1);
            g.colOrder.splice(newn, 0, tmp);
            // adjust the colVisib
            if (g.colVisib.length > 0) {
                tmp = g.colVisib[oldn];
                g.colVisib.splice(oldn, 1);
                g.colVisib.splice(newn, 0, tmp);
            }
        },

        /**
         * Find currently hovered table column's header (excluding actions column).
         *
         * @param e event
         * @return {object|undefined} the hovered column's th object or undefined if no hovered column found.
         */
        getHoveredCol: function (e) {
            var hoveredCol;
            var $headers = $(g.t).find('th.draggable:visible');
            $headers.each(function () {
                var left = $(this).offset().left;
                var right = left + $(this).outerWidth();
                if (left <= e.pageX && e.pageX <= right) {
                    hoveredCol = this;
                }
            });
            return hoveredCol;
        },

        /**
         * Get a zero-based index from a <th class="draggable"> tag in a table.
         *
         * @param obj table header <th> object
         * @return {number} zero-based index of the specified table header in the set of table headers (visible or not)
         */
        getHeaderIdx: function (obj) {
            return $(obj).parents('tr').find('th.draggable').index(obj);
        },

        /**
         * Reposition the columns back to normal order.
         */
        restoreColOrder: function () {
            // use insertion sort, since we already have shiftCol function
            for (var i = 1; i < g.colOrder.length; i++) {
                var x = g.colOrder[i];
                var j = i - 1;
                while (j >= 0 && x < g.colOrder[j]) {
                    j--;
                }
                if (j !== i - 1) {
                    g.shiftCol(i, j + 1);
                }
            }
            if (g.tableCreateTime) {
                // send request to server to remember the column order
                g.sendColPrefs();
            }
            g.refreshRestoreButton();
        },

        /**
         * Send column preferences (column order and visibility) to the server.
         */
        sendColPrefs: function () {
            if ($(g.t).is('.ajax')) {   // only send preferences if ajax class
                if (typeof g.db !== 'string' && typeof g.table !== 'string') {
                    // The server has nothing to do with it
                    // Issue: https://github.com/phpmyadmin/phpmyadmin/issues/15658
                    return;
                }
                var postParams = {
                    'ajax_request': true,
                    'db': g.db,
                    'table': g.table,
                    'token': g.token,
                    'server': g.server,
                    'table_create_time': g.tableCreateTime
                };
                if (g.colOrder.length > 0) {
                    $.extend(postParams, { 'col_order': g.colOrder.toString() });
                }
                if (g.colVisib.length > 0) {
                    $.extend(postParams, { 'col_visib': g.colVisib.toString() });
                }
                $.post('index.php?route=/sql/set-column-preferences', postParams, function (data) {
                    if (data.success !== true) {
                        var $tempDiv = $(document.createElement('div'));
                        $tempDiv.html(data.error);
                        $tempDiv.addClass('alert alert-danger');
                        Functions.ajaxShowMessage($tempDiv, false);
                    }
                });
            }
        },

        /**
         * Refresh restore button state.
         * Make restore button disabled if the table is similar with initial state.
         */
        refreshRestoreButton: function () {
            // check if table state is as initial state
            var isInitial = true;
            for (var i = 0; i < g.colOrder.length; i++) {
                if (g.colOrder[i] !== i) {
                    isInitial = false;
                    break;
                }
            }
            // check if only one visible column left
            var isOneColumn = g.visibleHeadersCount === 1;
            // enable or disable restore button
            if (isInitial || isOneColumn) {
                $(g.o).find('div.restore_column').hide();
            } else {
                $(g.o).find('div.restore_column').show();
            }
        },

        /**
         * Update current hint using the boolean values (showReorderHint, showSortHint, etc.).
         *
         * @return {string}
         *
         */
        updateHint: function () {
            var text = '';
            if (!g.colRsz && !g.colReorder) {     // if not resizing or dragging
                if (g.visibleHeadersCount > 1) {
                    g.showReorderHint = true;
                }
                if ($(t).find('th.marker').length > 0) {
                    g.showMarkHint = true;
                }
                if (g.showSortHint && g.sortHint) {
                    text += text.length > 0 ? '<br>' : '';
                    text += '- ' + g.sortHint;
                }
                if (g.showMultiSortHint && g.strMultiSortHint) {
                    text += text.length > 0 ? '<br>' : '';
                    text += '- ' + g.strMultiSortHint;
                }
                if (g.showMarkHint &&
                    g.markHint &&
                    ! g.showSortHint && // we do not show mark hint, when sort hint is shown
                    g.showReorderHint &&
                    g.reorderHint
                ) {
                    text += text.length > 0 ? '<br>' : '';
                    text += '- ' + g.reorderHint;
                    text += text.length > 0 ? '<br>' : '';
                    text += '- ' + g.markHint;
                    text += text.length > 0 ? '<br>' : '';
                    text += '- ' + g.copyHint;
                }
            }
            return text;
        },

        /**
         * Toggle column's visibility.
         * After calling this function and it returns true, afterToggleCol() must be called.
         *
         * @param {number} n
         *
         * @return {boolean} True if the column is toggled successfully.
         */
        toggleCol: function (n) {
            if (g.colVisib[n]) {
                // can hide if more than one column is visible
                if (g.visibleHeadersCount > 1) {
                    $(g.t).find('tr').each(function () {
                        $(this).find('th.draggable').eq(n)
                            .add($(this).find('td').eq(g.actionSpan + n))
                            .hide();
                    });
                    g.colVisib[n] = 0;
                    $(g.cList).find('.lDiv div').eq(n).find('input').prop('checked', false);
                } else {
                    // cannot hide, force the checkbox to stay checked
                    $(g.cList).find('.lDiv div').eq(n).find('input').prop('checked', true);
                    return false;
                }
            } else {    // column n is not visible
                $(g.t).find('tr').each(function () {
                    $(this).find('th.draggable').eq(n)
                        .add($(this).find('td').eq(g.actionSpan + n))
                        .show();
                });
                g.colVisib[n] = 1;
                $(g.cList).find('.lDiv div').eq(n).find('input').prop('checked', true);
            }
            return true;
        },

        /**
         * This must be called if toggleCol() returns is true.
         *
         * This function is separated from toggleCol because, sometimes, we want to toggle
         * some columns together at one time and do just one adjustment after it, e.g. in showAllColumns().
         */
        afterToggleCol: function () {
            // some adjustments after hiding column
            g.reposRsz();
            g.reposDrop();
            g.sendColPrefs();

            // check visible first row headers count
            g.visibleHeadersCount = $(g.t).find('tr').first().find('th.draggable:visible').length;
            g.refreshRestoreButton();

            // Display minimum of one column - disable checkbox for hiding last column
            if (g.visibleHeadersCount <= 1) {
                $(g.cList).find('.lDiv div').each(function () {
                    $(this).find('input:checkbox:checked').prop('disabled', true);
                });
            } else {
                // Remove disabled property if showing more than one column
                $(g.cList).find('.lDiv div').each(function () {
                    $(this).find('input:checkbox:disabled').prop('disabled', false);
                });
            }
        },

        /**
         * Show columns' visibility list.
         *
         * @param obj The drop down arrow of column visibility list
         */
        showColList: function (obj) {
            // only show when not resizing or reordering
            if (!g.colRsz && !g.colReorder) {
                var pos = $(obj).position();
                $(g.cList).css({
                    top: pos.top + $(obj).outerHeight(true)
                })
                    .show();
                $(obj).addClass('coldrop-hover');
            }
        },

        /**
         * Hide columns' visibility list.
         */
        hideColList: function () {
            $(g.cList).hide();
            $(g.cDrop).find('.coldrop-hover').removeClass('coldrop-hover');
        },

        /**
         * Reposition the column visibility drop-down arrow.
         */
        reposDrop: function () {
            var $th = $(t).find('th:not(.draggable)');
            for (var i = 0; i < $th.length; i++) {
                var $cd = $(g.cDrop).find('div').eq(i);   // column drop-down arrow
                var pos = $($th[i]).position();
                $cd.css({
                    left: pos.left + $($th[i]).width() - $cd.width(),
                    top: pos.top
                });
            }
        },

        /**
         * Show all hidden columns.
         */
        showAllColumns: function () {
            for (var i = 0; i < g.colVisib.length; i++) {
                if (!g.colVisib[i]) {
                    g.toggleCol(i);
                }
            }
            g.afterToggleCol();
        },

        /**
         * Show edit cell, if it can be shown
         *
         * @param cell <td> element to be edited
         */
        showEditCell: function (cell) {
            // destroy the date picker instance left if any, see: #17703
            var $datePickerInstance = $(g.cEdit).find('.hasDatepicker');
            if ($datePickerInstance.length > 0) {
                $datePickerInstance.datepicker('destroy');
            }

            if ($(cell).is('.grid_edit') &&
                !g.colRsz && !g.colReorder) {
                if (!g.isCellEditActive) {
                    var $cell = $(cell);

                    if ('string' === $cell.attr('data-type') ||
                        'blob' === $cell.attr('data-type') ||
                        'json' === $cell.attr('data-type')
                    ) {
                        g.cEdit = g.cEditTextarea;
                    } else {
                        g.cEdit = g.cEditStd;
                    }

                    // remove all edit area and hide it
                    $(g.cEdit).find('.edit_area').empty().hide();
                    // reposition the cEdit element
                    $(g.cEdit).css({
                        top: $cell.position().top,
                        left: $cell.position().left
                    })
                        .show()
                        .find('.edit_box')
                        .css({
                            width: $cell.outerWidth(),
                            height: $cell.outerHeight()
                        });
                    // fill the cell edit with text from <td>
                    var value = Functions.getCellValue(cell);
                    if ($cell.attr('data-type') === 'json' && $cell.is('.truncated') === false) {
                        value = Functions.stringifyJSON(value, null, 4);
                    }
                    $(g.cEdit).find('.edit_box').val(value);

                    g.currentEditCell = cell;
                    $(g.cEdit).find('.edit_box').trigger('focus');
                    moveCursorToEnd($(g.cEdit).find('.edit_box'));
                    $(g.cEdit).find('*').prop('disabled', false);
                }
            }

            function moveCursorToEnd (input) {
                var originalValue = input.val();
                var originallength = originalValue.length;
                input.val('');
                input.trigger('blur').trigger('focus').val(originalValue);
                input[0].setSelectionRange(originallength, originallength);
            }
        },

        /**
         * Remove edit cell and the edit area, if it is shown.
         *
         * @param force Optional, force to hide edit cell without saving edited field.
         * @param data  Optional, data from the POST AJAX request to save the edited field
         *              or just specify "true", if we want to replace the edited field with the new value.
         * @param field Optional, the edited <td>. If not specified, the function will
         *              use currently edited <td> from g.currentEditCell.
         * @param options Optional, this object contains a boolean named move (true, if called from move* functions)
         *                and a <td> to which the grid_edit should move
         */
        hideEditCell: function (force, data, field, options) {
            if (g.isCellEditActive && !force) {
                // cell is being edited, save or post the edited data
                if (options !== undefined) {
                    g.saveOrPostEditedCell(options);
                } else {
                    g.saveOrPostEditedCell();
                }
                return;
            }

            // cancel any previous request
            if (g.lastXHR !== null) {
                g.lastXHR.abort();
                g.lastXHR = null;
            }

            if (data) {
                if (g.currentEditCell) {    // save value of currently edited cell
                    // replace current edited field with the new value
                    var $thisField = $(g.currentEditCell);
                    var isNull = $thisField.data('value') === null;
                    if (isNull) {
                        $thisField.find('span').html('NULL');
                        $thisField.addClass('null');
                    } else {
                        $thisField.removeClass('null');
                        var value = data.isNeedToRecheck
                            ? data.truncatableFieldValue
                            : $thisField.data('value');

                        // Truncates the text.
                        $thisField.removeClass('truncated');
                        if (CommonParams.get('pftext') === 'P' && value.length > g.maxTruncatedLen) {
                            $thisField.addClass('truncated');
                            value = value.substring(0, g.maxTruncatedLen) + '...';
                        }

                        // Add <br> before carriage return.
                        var newHtml = Functions.escapeHtml(value);
                        newHtml = newHtml.replace(/\n/g, '<br>\n');

                        var decimals = parseInt($thisField.attr('data-decimals'));

                        // remove decimal places if column type not supported
                        if ((decimals === 0) && ($thisField.attr('data-type').indexOf('time') !== -1)) {
                            newHtml = newHtml.substring(0, newHtml.indexOf('.'));
                        }

                        // remove additional decimal places
                        if ((decimals > 0) && ($thisField.attr('data-type').indexOf('time') !== -1)) {
                            newHtml = newHtml.substring(0, newHtml.length - (6 - decimals));
                        }

                        var selector = 'span';
                        if ($thisField.hasClass('hex') && $thisField.find('a').length) {
                            selector = 'a';
                        }

                        // Updates the code keeping highlighting (if any).
                        var $target = $thisField.find(selector);
                        if (!Functions.updateCode($target, newHtml, value)) {
                            $target.html(newHtml);
                        }
                    }
                    if ($thisField.is('.bit')) {
                        $thisField.find('span').text($thisField.data('value'));
                    }
                }
                if (data.transformations !== undefined) {
                    $.each(data.transformations, function (cellIndex, value) {
                        var $thisField = $(g.t).find('.to_be_saved').eq(cellIndex);
                        $thisField.find('span').html(value);
                    });
                }
                if (data.relations !== undefined) {
                    $.each(data.relations, function (cellIndex, value) {
                        var $thisField = $(g.t).find('.to_be_saved').eq(cellIndex);
                        $thisField.find('span').html(value);
                    });
                }

                // refresh the grid
                g.reposRsz();
                g.reposDrop();
            }

            // hide the cell editing area
            $(g.cEdit).hide();
            $(g.cEdit).find('.edit_box').trigger('blur');
            g.isCellEditActive = false;
            g.currentEditCell = null;
            // destroy datepicker in edit area, if exist
            var $dp = $(g.cEdit).find('.hasDatepicker');
            if ($dp.length > 0) {
                // eslint-disable-next-line no-underscore-dangle
                $(document).on('mousedown', $.datepicker._checkExternalClick);
                $dp.datepicker('refresh');

                // change the cursor in edit box back to normal
                // (the cursor become a hand pointer when we add datepicker)
                $(g.cEdit).find('.edit_box').css('cursor', 'inherit');
            }
        },

        /**
         * Show drop-down edit area when edit cell is focused.
         */
        showEditArea: function () {
            if (!g.isCellEditActive) {   // make sure the edit area has not been shown
                g.isCellEditActive = true;
                g.isEditCellTextEditable = false;
                /**
                 * @var $td current edited cell
                 */
                var $td = $(g.currentEditCell);
                /**
                 * @var $editArea the editing area
                 */
                var $editArea = $(g.cEdit).find('.edit_area');
                /**
                 * @var whereClause WHERE clause for the edited cell
                 */
                var whereClause = $td.parent('tr').find('.where_clause').val();
                /**
                 * @var fieldName  String containing the name of this field.
                 * @see Sql.getFieldName()
                 */
                var fieldName = Sql.getFieldName($(t), $td);
                /**
                 * @var relationCurrValue String current value of the field (for fields that are foreign keyed).
                 */
                var relationCurrValue = $td.text();
                /**
                 * @var relationKeyOrDisplayColumn String relational key if in 'Relational display column' mode,
                 * relational display column if in 'Relational key' mode (for fields that are foreign keyed).
                 */
                var relationKeyOrDisplayColumn = $td.find('a').attr('title');
                /**
                 * @var currValue String current value of the field (for fields that are of type enum or set).
                 */
                var currValue = $td.find('span').text();

                // empty all edit area, then rebuild it based on $td classes
                $editArea.empty();

                // remember this instead of testing more than once
                var isNull = $td.is('.null');

                // add goto link, if this cell contains a link
                if ($td.find('a').length > 0) {
                    var gotoLink = document.createElement('div');
                    gotoLink.className = 'goto_link';
                    $(gotoLink).append(g.gotoLinkText + ' ').append($td.find('a').clone());
                    $editArea.append(gotoLink);
                }

                g.wasEditedCellNull = false;
                if ($td.is(':not(.not_null)')) {
                    // append a null checkbox
                    $editArea.append('<div class="null_div"><label>NULL:<input type="checkbox"></label></div>');

                    var $checkbox = $editArea.find('.null_div input');
                    // check if current <td> is NULL
                    if (isNull) {
                        $checkbox.prop('checked', true);
                        g.wasEditedCellNull = true;
                    }

                    // if the select/editor is changed un-check the 'checkbox_null_<field_name>_<row_index>'.
                    if ($td.is('.enum, .set')) {
                        $editArea.on('change', 'select', function () {
                            $checkbox.prop('checked', false);
                        });
                    } else if ($td.is('.relation')) {
                        $editArea.on('change', 'select', function () {
                            $checkbox.prop('checked', false);
                        });
                        $editArea.on('click', '.browse_foreign', function () {
                            $checkbox.prop('checked', false);
                        });
                    } else {
                        $(g.cEdit).on('keypress change paste', '.edit_box', function () {
                            $checkbox.prop('checked', false);
                        });
                        // Capture ctrl+v (on IE and Chrome)
                        $(g.cEdit).on('keydown', '.edit_box', function (e) {
                            if (e.ctrlKey && e.which === 86) {
                                $checkbox.prop('checked', false);
                            }
                        });
                        $editArea.on('keydown', 'textarea', function () {
                            $checkbox.prop('checked', false);
                        });
                    }
                    // if some text is written in textbox automatically unmark the null checkbox and if it is emptied again mark the checkbox.
                    $(g.cEdit).find('.edit_box').on('input', function () {
                        if ($(g.cEdit).find('.edit_box').val() !== '') {
                            $checkbox.prop('checked', false);
                        } else {
                            $checkbox.prop('checked', true);
                        }
                    });
                    // if null checkbox is clicked empty the corresponding select/editor.
                    $checkbox.on('click', function () {
                        if ($td.is('.enum')) {
                            $editArea.find('select').val('');
                        } else if ($td.is('.set')) {
                            $editArea.find('select').find('option').each(function () {
                                var $option = $(this);
                                $option.prop('selected', false);
                            });
                        } else if ($td.is('.relation')) {
                            // if the dropdown is there to select the foreign value
                            if ($editArea.find('select').length > 0) {
                                $editArea.find('select').val('');
                            }
                        } else {
                            $editArea.find('textarea').val('');
                        }
                        $(g.cEdit).find('.edit_box').val('');
                    });
                }

                // reset the position of the edit_area div after closing datetime picker
                $(g.cEdit).find('.edit_area').css({ 'top' :'0','position':'' });

                var postParams;
                if ($td.is('.relation')) {
                    // handle relations
                    $editArea.addClass('edit_area_loading');

                    // initialize the original data
                    $td.data('original_data', null);

                    /**
                     * @var postParams Object containing parameters for the POST request
                     */
                    postParams = {
                        'ajax_request' : true,
                        'server' : g.server,
                        'db' : g.db,
                        'table' : g.table,
                        'column' : fieldName,
                        'curr_value' : relationCurrValue,
                        'relation_key_or_display_column' : relationKeyOrDisplayColumn
                    };

                    g.lastXHR = $.post('index.php?route=/sql/get-relational-values', postParams, function (data) {
                        g.lastXHR = null;
                        $editArea.removeClass('edit_area_loading');
                        if ($(data.dropdown).is('select')) {
                            // save original_data
                            var value = $(data.dropdown).val();
                            $td.data('original_data', value);
                            // update the text input field, in case where the "Relational display column" is checked
                            $(g.cEdit).find('.edit_box').val(value);
                        }

                        $editArea.append(data.dropdown);
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');

                        // for 'Browse foreign values' options,
                        // hide the value next to 'Browse foreign values' link
                        $editArea.find('span.curr_value').hide();
                        // handle update for new values selected from new window
                        $editArea.find('span.curr_value').on('change', function () {
                            $(g.cEdit).find('.edit_box').val($(this).text());
                        });
                    }); // end $.post()

                    $editArea.show();
                    $editArea.on('change', 'select', function () {
                        $(g.cEdit).find('.edit_box').val($(this).val());
                    });
                    g.isEditCellTextEditable = true;
                } else if ($td.is('.enum')) {
                    // handle enum fields
                    $editArea.addClass('edit_area_loading');

                    /**
                     * @var postParams Object containing parameters for the POST request
                     */
                    postParams = {
                        'ajax_request' : true,
                        'server' : g.server,
                        'db' : g.db,
                        'table' : g.table,
                        'column' : fieldName,
                        'curr_value' : currValue
                    };
                    g.lastXHR = $.post('index.php?route=/sql/get-enum-values', postParams, function (data) {
                        g.lastXHR = null;
                        if (typeof data === 'object' && data.success === false) {
                            Functions.ajaxShowMessage(data.error, undefined, 'error');
                            return;
                        }
                        $editArea.removeClass('edit_area_loading');
                        $editArea.append(data.dropdown);
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                    }); // end $.post()

                    $editArea.show();
                    $editArea.on('change', 'select', function () {
                        $(g.cEdit).find('.edit_box').val($(this).val());
                    });
                } else if ($td.is('.set')) {
                    // handle set fields
                    $editArea.addClass('edit_area_loading');

                    // if the data is truncated, get the full data
                    if ($td.is('.truncated')) {
                        postParams = {
                            'ajax_request': true,
                            'server': g.server,
                            'db': g.db,
                            'table': g.table,
                            'column': fieldName,
                            'curr_value': currValue,
                            'get_full_values': true,
                            'where_clause': whereClause
                        };
                    } else {
                        postParams = {
                            'ajax_request': true,
                            'server': g.server,
                            'db': g.db,
                            'table': g.table,
                            'column': fieldName,
                            'curr_value': currValue
                        };
                    }

                    g.lastXHR = $.post('index.php?route=/sql/get-set-values', postParams, function (data) {
                        g.lastXHR = null;
                        if (typeof data === 'object' && data.success === false) {
                            Functions.ajaxShowMessage(data.error, undefined, 'error');
                            return;
                        }
                        $editArea.removeClass('edit_area_loading');
                        $editArea.append(data.select);
                        $td.data('original_data', $(data.select).val().join());
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                    }); // end $.post()

                    $editArea.show();
                    $editArea.on('change', 'select', function () {
                        $(g.cEdit).find('.edit_box').val($(this).val());
                    });
                } else if ($td.is('.truncated, .transformed')) {
                    if ($td.is('.to_be_saved')) {   // cell has been edited
                        var value = $td.data('value');
                        $(g.cEdit).find('.edit_box').val(value);
                        $editArea.append('<textarea></textarea>');
                        $editArea.find('textarea').val(value);
                        $editArea
                            .on('keyup', 'textarea', function () {
                                $(g.cEdit).find('.edit_box').val($(this).val());
                            });
                        $(g.cEdit).on('keyup', '.edit_box', function () {
                            $editArea.find('textarea').val($(this).val());
                        });
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                    } else {
                        // handle truncated/transformed values values
                        $editArea.addClass('edit_area_loading');

                        // initialize the original data
                        $td.data('original_data', null);

                        /**
                         * @var sqlQuery   String containing the SQL query used to retrieve value of truncated/transformed data
                         */
                        var sqlQuery = 'SELECT `' + fieldName + '` FROM `' + g.table + '` WHERE ' + whereClause;

                        // Make the Ajax call and get the data, wrap it and insert it
                        g.lastXHR = $.post('index.php?route=/sql', {
                            'server' : g.server,
                            'db' : g.db,
                            'ajax_request' : true,
                            'sql_query' : sqlQuery,
                            'grid_edit' : true
                        }, function (data) {
                            g.lastXHR = null;
                            $editArea.removeClass('edit_area_loading');
                            if (typeof data !== 'undefined' && data.success === true) {
                                if ($td.attr('data-type') === 'json') {
                                    data.value = Functions.stringifyJSON(data.value, null, 4);
                                }
                                $td.data('original_data', data.value);
                                $(g.cEdit).find('.edit_box').val(data.value);
                            } else {
                                Functions.ajaxShowMessage(data.error, false);
                            }
                        }); // end $.post()
                    }
                    g.isEditCellTextEditable = true;
                } else if ($td.is('.timefield, .datefield, .datetimefield, .timestampfield')) {
                    var $inputField = $(g.cEdit).find('.edit_box');

                    // remember current datetime value in $input_field, if it is not null
                    var datetimeValue = !isNull ? $inputField.val() : '';

                    var showMillisec = false;
                    var showMicrosec = false;
                    var timeFormat = 'HH:mm:ss';
                    // check for decimal places of seconds
                    if (($td.attr('data-decimals') > 0) && ($td.attr('data-type').indexOf('time') !== -1)) {
                        if (datetimeValue && datetimeValue.indexOf('.') === false) {
                            datetimeValue += '.';
                        }
                        if ($td.attr('data-decimals') > 3) {
                            showMillisec = true;
                            showMicrosec = true;
                            timeFormat = 'HH:mm:ss.lc';

                            if (datetimeValue) {
                                datetimeValue += '000000';
                                datetimeValue = datetimeValue.substring(0, datetimeValue.indexOf('.') + 7);
                                $inputField.val(datetimeValue);
                            }
                        } else {
                            showMillisec = true;
                            timeFormat = 'HH:mm:ss.l';

                            if (datetimeValue) {
                                datetimeValue += '000';
                                datetimeValue = datetimeValue.substring(0, datetimeValue.indexOf('.') + 4);
                                $inputField.val(datetimeValue);
                            }
                        }
                    }

                    // add datetime picker
                    Functions.addDatepicker($inputField, $td.attr('data-type'), {
                        showMillisec: showMillisec,
                        showMicrosec: showMicrosec,
                        timeFormat: timeFormat,
                        firstDay: firstDayOfCalendar
                    });

                    $inputField.on('keyup', function (e) {
                        if (e.which === 13) {
                            // post on pressing "Enter"
                            e.preventDefault();
                            e.stopPropagation();
                            g.saveOrPostEditedCell();
                        } else if (e.which !== 27) {
                            Functions.toggleDatepickerIfInvalid($td, $inputField);
                        }
                    });

                    $inputField.datepicker('show');
                    Functions.toggleDatepickerIfInvalid($td, $inputField);

                    // unbind the mousedown event to prevent the problem of
                    // datepicker getting closed, needs to be checked for any
                    // change in names when updating
                    // eslint-disable-next-line no-underscore-dangle
                    $(document).off('mousedown', $.datepicker._checkExternalClick);

                    // move ui-datepicker-div inside cEdit div
                    var datepickerDiv = $('#ui-datepicker-div');
                    datepickerDiv.css({ 'top': 0, 'left': 0, 'position': 'relative' });
                    $(g.cEdit).append(datepickerDiv);

                    // cancel any click on the datepicker element
                    $editArea.find('> *').on('click', function (e) {
                        e.stopPropagation();
                    });

                    g.isEditCellTextEditable = true;
                } else {
                    g.isEditCellTextEditable = true;
                    // only append edit area hint if there is a null checkbox
                    if ($editArea.children().length > 0) {
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                    }
                }
                if ($editArea.children().length > 0) {
                    $editArea.show();
                }
            }
        },

        /**
         * Post the content of edited cell.
         *
         * @param options Optional, this object contains a boolean named move (true, if called from move* functions)
         *                and a <td> to which the grid_edit should move
         */
        postEditedCell: function (options) {
            if (g.isSaving) {
                return;
            }
            g.isSaving = true;
            /**
             * @var relationFields Array containing the name/value pairs of relational fields
             */
            var relationFields = {};
            /**
             * @var relationalDisplay string 'K' if relational key, 'D' if relational display column
             */
            var relationalDisplay = $(g.o).find('input[name=relational_display]:checked').val();
            /**
             * @var transformFields    Array containing the name/value pairs for transformed fields
             */
            var transformFields = {};
            /**
             * @var transformationFields   Boolean, if there are any transformed fields in the edited cells
             */
            var transformationFields = false;
            /**
             * @var fullSqlQuery String containing the complete SQL query to update this table
             */
            var fullSqlQuery = '';
            /**
             * @var relFieldsList  String, url encoded representation of {@link relations_fields}
             */
            var relFieldsList = '';
            /**
             * @var transformFieldsList  String, url encoded representation of {@link transformFields}
             */
            var transformFieldsList = '';
            /**
             * @var fullWhereClause Array containing where clause for updated fields
             */
            var fullWhereClause = [];
            /**
             * @var isUnique   Boolean, whether the rows in this table is unique or not
             */
            var isUnique = $(g.t).find('td.edit_row_anchor').is('.nonunique') ? 0 : 1;
            /**
             * multi edit variables
             */
            var multiEditFieldsName = [];
            var multiEditFieldsType = [];
            var multiEditFields = [];
            var multiEditFieldsNull = [];

            // alert user if edited table is not unique
            if (!isUnique) {
                alert(g.alertNonUnique);
            }

            // loop each edited row
            $(g.t).find('td.to_be_saved').parents('tr').each(function () {
                var $tr = $(this);
                var whereClause = $tr.find('.where_clause').val();
                if (typeof whereClause === 'undefined') {
                    whereClause = '';
                }
                fullWhereClause.push(whereClause);
                var conditionArray = JSON.parse($tr.find('.condition_array').val());

                /**
                 * multi edit variables, for current row
                 * @TODO array indices are still not correct, they should be md5 of field's name
                 */
                var fieldsName = [];
                var fieldsType = [];
                var fields = [];
                var fieldsNull = [];

                // loop each edited cell in a row
                $tr.find('.to_be_saved').each(function () {
                    /**
                     * @var $thisField    Object referring to the td that is being edited
                     */
                    var $thisField = $(this);

                    /**
                     * @var fieldName  String containing the name of this field.
                     * @see Sql.getFieldName()
                     */
                    var fieldName = Sql.getFieldName($(g.t), $thisField);

                    /**
                     * @var thisFieldParams   Array temporary storage for the name/value of current field
                     */
                    var thisFieldParams = {};

                    if ($thisField.is('.transformed')) {
                        transformationFields =  true;
                    }
                    thisFieldParams[fieldName] = $thisField.data('value');

                    /**
                     * @var isNull String capturing whether 'checkbox_null_<field_name>_<row_index>' is checked.
                     */
                    var isNull = thisFieldParams[fieldName] === null;

                    fieldsName.push(fieldName);

                    if (isNull) {
                        fieldsNull.push('on');
                        fields.push('');
                    } else {
                        if ($thisField.is('.bit')) {
                            fieldsType.push('bit');
                        } else if ($thisField.hasClass('hex')) {
                            fieldsType.push('hex');
                        }
                        fieldsNull.push('');

                        if ($thisField.attr('data-type') !== 'json') {
                            fields.push($thisField.data('value'));
                        } else {
                            const JSONString = Functions.stringifyJSON($thisField.data('value'));
                            fields.push(JSONString);
                        }

                        var cellIndex = $thisField.index('.to_be_saved');
                        if ($thisField.is(':not(.relation, .enum, .set, .bit)')) {
                            if ($thisField.is('.transformed')) {
                                transformFields[cellIndex] = {};
                                $.extend(transformFields[cellIndex], thisFieldParams);
                            }
                        } else if ($thisField.is('.relation')) {
                            relationFields[cellIndex] = {};
                            $.extend(relationFields[cellIndex], thisFieldParams);
                        }
                    }
                    // check if edited field appears in WHERE clause
                    if (whereClause.indexOf(Sql.urlEncode(fieldName)) > -1) {
                        var fieldStr = '`' + g.table + '`.' + '`' + fieldName + '`';
                        for (var field in conditionArray) {
                            if (field.indexOf(fieldStr) > -1) {
                                conditionArray[field] = isNull ? 'IS NULL' : '= \'' + thisFieldParams[fieldName].replace(/'/g, '\'\'') + '\'';
                                break;
                            }
                        }
                    }
                }); // end of loop for every edited cells in a row

                // save new_clause
                var newClause = '';
                for (var field in conditionArray) {
                    newClause += field + ' ' + conditionArray[field] + ' AND ';
                }
                newClause = newClause.substring(0, newClause.length - 5); // remove the last AND
                $tr.data('new_clause', newClause);
                // save condition_array
                $tr.find('.condition_array').val(JSON.stringify(conditionArray));

                multiEditFieldsName.push(fieldsName);
                multiEditFieldsType.push(fieldsType);
                multiEditFields.push(fields);
                multiEditFieldsNull.push(fieldsNull);
            }); // end of loop for every edited rows

            relFieldsList = $.param(relationFields);
            transformFieldsList = $.param(transformFields);

            // Make the Ajax post after setting all parameters
            /**
             * @var postParams Object containing parameters for the POST request
             */
            var postParams = { 'ajax_request' : true,
                'sql_query' : fullSqlQuery,
                'server' : g.server,
                'db' : g.db,
                'table' : g.table,
                'clause_is_unique' : isUnique,
                'where_clause' : fullWhereClause,
                'fields[multi_edit]' : multiEditFields,
                'fields_name[multi_edit]' : multiEditFieldsName,
                'fields_type[multi_edit]' : multiEditFieldsType,
                'fields_null[multi_edit]' : multiEditFieldsNull,
                'rel_fields_list' : relFieldsList,
                'do_transformations' : transformationFields,
                'transform_fields_list' : transformFieldsList,
                'relational_display' : relationalDisplay,
                'goto' : encodeURIComponent('index.php?route=/sql'),
                'submit_type' : 'save'
            };

            if (!g.saveCellsAtOnce) {
                $(g.cEdit).find('*').prop('disabled', true);
                $(g.cEdit).find('.edit_box').addClass('edit_box_posting');
            } else {
                $(g.o).find('div.save_edited').addClass('saving_edited_data')
                    .find('input').prop('disabled', true);    // disable the save button
            }

            $.ajax({
                type: 'POST',
                url: 'index.php?route=/table/replace',
                data: postParams,
                success:
                    function (data) {
                        g.isSaving = false;
                        if (!g.saveCellsAtOnce) {
                            $(g.cEdit).find('*').prop('disabled', false);
                            $(g.cEdit).find('.edit_box').removeClass('edit_box_posting');
                        } else {
                            $(g.o).find('div.save_edited').removeClass('saving_edited_data')
                                .find('input').prop('disabled', false);  // enable the save button back
                        }
                        if (typeof data !== 'undefined' && data.success === true) {
                            if (typeof options === 'undefined' || ! options.move) {
                                Functions.ajaxShowMessage(data.message);
                            }

                            // update where_clause related data in each edited row
                            $(g.t).find('td.to_be_saved').parents('tr').each(function () {
                                var newClause = $(this).data('new_clause');
                                var $whereClause = $(this).find('.where_clause');
                                var oldClause = $whereClause.val();
                                var decodedOldClause = oldClause;
                                var decodedNewClause = newClause;

                                $whereClause.val(newClause);
                                // update Edit, Copy, and Delete links also
                                $(this).find('a').each(function () {
                                    $(this).attr('href', $(this).attr('href').replace(oldClause, newClause));
                                    // update delete confirmation in Delete link
                                    if ($(this).attr('href').indexOf('DELETE') > -1) {
                                        $(this).removeAttr('onclick')
                                            .off('click')
                                            .on('click', function () {
                                                return Functions.confirmLink(this, 'DELETE FROM `' + g.db + '`.`' + g.table + '` WHERE ' +
                                                       decodedNewClause + (isUnique ? '' : ' LIMIT 1'));
                                            });
                                    }
                                });
                                // update the multi edit checkboxes
                                $(this).find('input[type=checkbox]').each(function () {
                                    var $checkbox = $(this);
                                    var checkboxName = $checkbox.attr('name');
                                    var checkboxValue = $checkbox.val();

                                    $checkbox.attr('name', checkboxName.replace(oldClause, newClause));
                                    $checkbox.val(checkboxValue.replace(decodedOldClause, decodedNewClause));
                                });
                            });
                            // update the display of executed SQL query command
                            if (typeof data.sql_query !== 'undefined') {
                                // extract query box
                                var $resultQuery = $($.parseHTML(data.sql_query));
                                var sqlOuter = $resultQuery.find('.sqlOuter').wrap('<p>').parent().html();
                                var tools = $resultQuery.find('.tools').wrap('<p>').parent().html();
                                // sqlOuter and tools will not be present if 'Show SQL queries' configuration is off
                                if (typeof sqlOuter !== 'undefined' && typeof tools !== 'undefined') {
                                    $(g.o).find('.result_query').not($(g.o).find('.result_query').last()).remove();
                                    var $existingQuery = $(g.o).find('.result_query');
                                    // If two query box exists update query in second else add a second box
                                    if ($existingQuery.find('div.sqlOuter').length > 1) {
                                        $existingQuery.children().eq(3).remove();
                                        $existingQuery.children().eq(3).remove();
                                        $existingQuery.append(sqlOuter + tools);
                                    } else {
                                        $existingQuery.append(sqlOuter + tools);
                                    }
                                    Functions.highlightSql($existingQuery);
                                }
                            }
                            // hide and/or update the successfully saved cells
                            g.hideEditCell(true, data);

                            // remove the "Save edited cells" button
                            $(g.o).find('div.save_edited').hide();
                            // update saved fields
                            $(g.t).find('.to_be_saved')
                                .removeClass('to_be_saved')
                                .data('value', null)
                                .data('original_data', null);

                            g.isCellEdited = false;
                        } else {
                            Functions.ajaxShowMessage(data.error, false);
                            if (!g.saveCellsAtOnce) {
                                $(g.t).find('.to_be_saved')
                                    .removeClass('to_be_saved');
                            }
                        }
                    }
            }).done(function () {
                if (options !== undefined && options.move) {
                    g.showEditCell(options.cell);
                }
            }); // end $.ajax()
        },

        /**
         * Save edited cell, so it can be posted later.
         *
         * @return {bool}
         */
        saveEditedCell: function () {
            /**
             * @var $thisField    Object referring to the td that is being edited
             */
            var $thisField = $(g.currentEditCell);
            var $testElement = ''; // to test the presence of a element

            var needToPost = false;

            /**
             * @var fieldName  String containing the name of this field.
             * @see Sql.getFieldName()
             */
            var fieldName = Sql.getFieldName($(g.t), $thisField);

            /**
             * @var thisFieldParams   Array temporary storage for the name/value of current field
             */
            var thisFieldParams = {};

            /**
             * @var isNull String capturing whether 'checkbox_null_<field_name>_<row_index>' is checked.
             */
            var isNull = $(g.cEdit).find('input:checkbox').is(':checked');

            if ($(g.cEdit).find('.edit_area').is('.edit_area_loading')) {
                // the edit area is still loading (retrieving cell data), no need to post
                needToPost = false;
            } else if (isNull) {
                if (!g.wasEditedCellNull) {
                    thisFieldParams[fieldName] = null;
                    needToPost = true;
                }
            } else {
                if ($thisField.is('.bit')) {
                    thisFieldParams[fieldName] = $(g.cEdit).find('.edit_box').val();
                } else if ($thisField.is('.set')) {
                    $testElement = $(g.cEdit).find('select');
                    thisFieldParams[fieldName] = $testElement.map(function () {
                        return $(this).val();
                    }).get().join(',');
                } else if ($thisField.is('.relation, .enum')) {
                    // for relation and enumeration, take the results from edit box value,
                    // because selected value from drop-down, new window or multiple
                    // selection list will always be updated to the edit box
                    thisFieldParams[fieldName] = $(g.cEdit).find('.edit_box').val();
                } else if ($thisField.hasClass('hex')) {
                    if ($(g.cEdit).find('.edit_box').val().match(/^(0x)?[a-f0-9]*$/i) !== null) {
                        thisFieldParams[fieldName] = $(g.cEdit).find('.edit_box').val();
                    } else {
                        var hexError = '<div class="alert alert-danger" role="alert">' + Messages.strEnterValidHex + '</div>';
                        Functions.ajaxShowMessage(hexError, false);
                        thisFieldParams[fieldName] = Functions.getCellValue(g.currentEditCell);
                    }
                } else {
                    thisFieldParams[fieldName] = $(g.cEdit).find('.edit_box').val();
                }

                let isValueUpdated;
                if ($thisField.attr('data-type') !== 'json') {
                    isValueUpdated = thisFieldParams[fieldName] !== Functions.getCellValue(g.currentEditCell);
                } else {
                    const JSONString = Functions.stringifyJSON(thisFieldParams[fieldName]);
                    isValueUpdated = JSONString !== JSON.stringify(JSON.parse(Functions.getCellValue(g.currentEditCell)));
                }

                if (g.wasEditedCellNull || isValueUpdated) {
                    needToPost = true;
                }
            }

            if (needToPost) {
                $(g.currentEditCell).addClass('to_be_saved')
                    .data('value', thisFieldParams[fieldName]);
                if (g.saveCellsAtOnce) {
                    $(g.o).find('div.save_edited').show();
                }
                g.isCellEdited = true;
            }

            return needToPost;
        },

        /**
         * Save or post currently edited cell, depending on the "saveCellsAtOnce" configuration.
         *
         * @param options Optional, this object contains a boolean named move (true, if called from move* functions)
         *                and a <td> to which the grid_edit should move
         */
        saveOrPostEditedCell: function (options) {
            var saved = g.saveEditedCell();
            // Check if $cfg['SaveCellsAtOnce'] is false
            if (!g.saveCellsAtOnce) {
                // Check if need_to_post is true
                if (saved) {
                    // Check if this function called from 'move' functions
                    if (options !== undefined && options.move) {
                        g.postEditedCell(options);
                    } else {
                        g.postEditedCell();
                    }
                // need_to_post is false
                } else {
                    // Check if this function called from 'move' functions
                    if (options !== undefined && options.move) {
                        g.hideEditCell(true);
                        g.showEditCell(options.cell);
                    // NOT called from 'move' functions
                    } else {
                        g.hideEditCell(true);
                    }
                }
            // $cfg['SaveCellsAtOnce'] is true
            } else {
                // If need_to_post
                if (saved) {
                    // If this function called from 'move' functions
                    if (options !== undefined && options.move) {
                        g.hideEditCell(true, true, false, options);
                        g.showEditCell(options.cell);
                    // NOT called from 'move' functions
                    } else {
                        g.hideEditCell(true, true);
                    }
                } else {
                    // If this function called from 'move' functions
                    if (options !== undefined && options.move) {
                        g.hideEditCell(true, false, false, options);
                        g.showEditCell(options.cell);
                    // NOT called from 'move' functions
                    } else {
                        g.hideEditCell(true);
                    }
                }
            }
        },

        /**
         * Initialize column resize feature.
         */
        initColResize: function () {
            // create column resizer div
            g.cRsz = document.createElement('div');
            g.cRsz.className = 'cRsz';

            // get data columns in the first row of the table
            var $firstRowCols = $(g.t).find('tr').first().find('th.draggable');

            // create column borders
            $firstRowCols.each(function () {
                var cb = document.createElement('div'); // column border
                $(cb).addClass('colborder')
                    .on('mousedown', function (e) {
                        g.dragStartRsz(e, this);
                    });
                $(g.cRsz).append(cb);
            });
            g.reposRsz();

            // attach to global div
            $(g.gDiv).prepend(g.cRsz);
        },

        /**
         * Initialize column reordering feature.
         */
        initColReorder: function () {
            g.cCpy = document.createElement('div');     // column copy, to store copy of dragged column header
            g.cPointer = document.createElement('div'); // column pointer, used when reordering column

            // adjust g.cCpy
            g.cCpy.className = 'cCpy';
            $(g.cCpy).hide();

            // adjust g.cPointer
            g.cPointer.className = 'cPointer';
            $(g.cPointer).css('visibility', 'hidden');  // set visibility to hidden instead of calling hide() to force browsers to cache the image in cPointer class

            // assign column reordering hint
            g.reorderHint = Messages.strColOrderHint;

            // get data columns in the first row of the table
            var $firstRowCols = $(g.t).find('tr').first().find('th.draggable');

            // initialize column order
            var $colOrder = $(g.o).find('.col_order');   // check if column order is passed from PHP
            var i;
            if ($colOrder.length > 0) {
                g.colOrder = $colOrder.val().split(',');
                for (i = 0; i < g.colOrder.length; i++) {
                    g.colOrder[i] = parseInt(g.colOrder[i], 10);
                }
            } else {
                g.colOrder = [];
                for (i = 0; i < $firstRowCols.length; i++) {
                    g.colOrder.push(i);
                }
            }

            // register events
            $(g.t).find('th.draggable')
                .on('mousedown', function (e) {
                    $(g.o).addClass('turnOffSelect');
                    if (g.visibleHeadersCount > 1) {
                        g.dragStartReorder(e, this);
                    }
                })
                .on('mouseenter', function () {
                    if (g.visibleHeadersCount > 1) {
                        $(this).css('cursor', 'move');
                    } else {
                        $(this).css('cursor', 'inherit');
                    }
                })
                .on('mouseleave', function () {
                    g.showReorderHint = false;
                    $(this).uiTooltip('option', {
                        content: g.updateHint()
                    });
                })
                .on('dblclick', function (e) {
                    e.preventDefault();
                    var res = Functions.copyToClipboard($(this).data('column'));
                    if (res) {
                        Functions.ajaxShowMessage(Messages.strCopyColumnSuccess, false, 'success');
                    } else {
                        Functions.ajaxShowMessage(Messages.strCopyColumnFailure, false, 'error');
                    }
                });
            $(g.t).find('th.draggable a')
                .on('dblclick', function (e) {
                    e.stopPropagation();
                });
            // restore column order when the restore button is clicked
            $(g.o).find('div.restore_column').on('click', function () {
                g.restoreColOrder();
            });

            // attach to global div
            $(g.gDiv).append(g.cPointer);
            $(g.gDiv).append(g.cCpy);

            // prevent default "dragstart" event when dragging a link
            $(g.t).find('th a').on('dragstart', function () {
                return false;
            });

            // refresh the restore column button state
            g.refreshRestoreButton();
        },

        /**
         * Initialize column visibility feature.
         */
        initColVisib: function () {
            g.cDrop = document.createElement('div');    // column drop-down arrows
            g.cList = document.createElement('div');    // column visibility list

            // adjust g.cDrop
            g.cDrop.className = 'cDrop';

            // adjust g.cList
            g.cList.className = 'cList';
            $(g.cList).hide();

            // assign column visibility related hints
            g.showAllColText = Messages.strShowAllCol;

            // get data columns in the first row of the table
            var $firstRowCols = $(g.t).find('tr').first().find('th.draggable');

            var i;
            // initialize column visibility
            var $colVisib = $(g.o).find('.col_visib');   // check if column visibility is passed from PHP
            if ($colVisib.length > 0) {
                g.colVisib = $colVisib.val().split(',');
                for (i = 0; i < g.colVisib.length; i++) {
                    g.colVisib[i] = parseInt(g.colVisib[i], 10);
                }
            } else {
                g.colVisib = [];
                for (i = 0; i < $firstRowCols.length; i++) {
                    g.colVisib.push(1);
                }
            }

            // make sure we have more than one column
            if ($firstRowCols.length > 1) {
                var $colVisibTh = $(g.t).find('th:not(.draggable)').slice(0, 1);
                Functions.tooltip(
                    $colVisibTh,
                    'th',
                    Messages.strColVisibHint
                );

                // create column visibility drop-down arrow(s)
                $colVisibTh.each(function () {
                    var cd = document.createElement('div'); // column drop-down arrow
                    $(cd).addClass('coldrop')
                        .on('click', function () {
                            if (g.cList.style.display === 'none') {
                                g.showColList(this);
                            } else {
                                g.hideColList();
                            }
                        });
                    $(g.cDrop).append(cd);
                });

                // add column visibility control
                g.cList.innerHTML = '<div class="lDiv"></div>';
                var $listDiv = $(g.cList).find('div');

                var tempClick = function () {
                    if (g.toggleCol($(this).index())) {
                        g.afterToggleCol();
                    }
                };

                for (i = 0; i < $firstRowCols.length; i++) {
                    var currHeader = $firstRowCols[i];
                    var listElmt = document.createElement('div');
                    $(listElmt).text($(currHeader).text())
                        .prepend('<input type="checkbox" ' + (g.colVisib[i] ? 'checked="checked" ' : '') + '>');
                    $listDiv.append(listElmt);
                    // add event on click
                    $(listElmt).on('click', tempClick);
                }
                // add "show all column" button
                var showAll = document.createElement('div');
                $(showAll).addClass('showAllColBtn')
                    .text(g.showAllColText);
                $(g.cList).append(showAll);
                $(showAll).on('click', function () {
                    g.showAllColumns();
                });
                // prepend "show all column" button at top if the list is too long
                if ($firstRowCols.length > 10) {
                    var clone = showAll.cloneNode(true);
                    $(g.cList).prepend(clone);
                    $(clone).on('click', function () {
                        g.showAllColumns();
                    });
                }
            }

            // hide column visibility list if we move outside the list
            $(g.t).find('td, th.draggable').on('mouseenter', function () {
                g.hideColList();
            });

            // attach to first row first col of the grid
            var thFirst = $(g.t).find('th.d-print-none');
            $(thFirst).append(g.cDrop);
            $(thFirst).append(g.cList);

            // some adjustment
            g.reposDrop();
        },

        /**
         * Move currently Editing Cell to Up
         *
         * @param e
         *
         */
        moveUp: function (e) {
            e.preventDefault();
            var $thisField = $(g.currentEditCell);
            var fieldName = Sql.getFieldName($(g.t), $thisField);

            var whereClause = $thisField.parents('tr').first().find('.where_clause').val();
            if (typeof whereClause === 'undefined') {
                whereClause = '';
            }
            var found = false;
            var $prevRow;

            $thisField.parents('tr').first().parents('tbody').children().each(function () {
                if ($(this).find('.where_clause').val() === whereClause) {
                    found = true;
                }
                if (!found) {
                    $prevRow = $(this);
                }
            });

            var newCell;

            if (found && $prevRow) {
                $prevRow.children('td').each(function () {
                    if (Sql.getFieldName($(g.t), $(this)) === fieldName) {
                        newCell = this;
                    }
                });
            }

            if (newCell) {
                g.hideEditCell(false, false, false, { move : true, cell : newCell });
            }
        },

        /**
         * Move currently Editing Cell to Down
         *
         * @param e
         *
         */
        moveDown: function (e) {
            e.preventDefault();

            var $thisField = $(g.currentEditCell);
            var fieldName = Sql.getFieldName($(g.t), $thisField);

            var whereClause = $thisField.parents('tr').first().find('.where_clause').val();
            if (typeof whereClause === 'undefined') {
                whereClause = '';
            }
            var found = false;
            var $nextRow;
            var j = 0;
            var nextRowFound = false;
            $thisField.parents('tr').first().parents('tbody').children().each(function () {
                if ($(this).find('.where_clause').val() === whereClause) {
                    found = true;
                }
                if (found) {
                    if (j >= 1 && ! nextRowFound) {
                        $nextRow = $(this);
                        nextRowFound = true;
                    } else {
                        j++;
                    }
                }
            });

            var newCell;
            if (found && $nextRow) {
                $nextRow.children('td').each(function () {
                    if (Sql.getFieldName($(g.t), $(this)) === fieldName) {
                        newCell = this;
                    }
                });
            }

            if (newCell) {
                g.hideEditCell(false, false, false, { move : true, cell : newCell });
            }
        },

        /**
         * Move currently Editing Cell to Left
         *
         * @param e
         *
         */
        moveLeft: function (e) {
            e.preventDefault();

            var $thisField = $(g.currentEditCell);
            var fieldName = Sql.getFieldName($(g.t), $thisField);

            var whereClause = $thisField.parents('tr').first().find('.where_clause').val();
            if (typeof whereClause === 'undefined') {
                whereClause = '';
            }
            var found = false;
            var $foundRow;
            $thisField.parents('tr').first().parents('tbody').children().each(function () {
                if ($(this).find('.where_clause').val() === whereClause) {
                    found = true;
                    $foundRow = $(this);
                }
            });

            var leftCell;
            var cellFound = false;
            if (found) {
                $foundRow.children('td.grid_edit').each(function () {
                    if (Sql.getFieldName($(g.t), $(this)) === fieldName) {
                        cellFound = true;
                    }
                    if (!cellFound) {
                        leftCell = this;
                    }
                });
            }

            if (leftCell) {
                g.hideEditCell(false, false, false, { move : true, cell : leftCell });
            }
        },

        /**
         * Move currently Editing Cell to Right
         *
         * @param e
         *
         */
        moveRight: function (e) {
            e.preventDefault();

            var $thisField = $(g.currentEditCell);
            var fieldName = Sql.getFieldName($(g.t), $thisField);

            var whereClause = $thisField.parents('tr').first().find('.where_clause').val();
            if (typeof whereClause === 'undefined') {
                whereClause = '';
            }
            var found = false;
            var $foundRow;
            var j = 0;
            $thisField.parents('tr').first().parents('tbody').children().each(function () {
                if ($(this).find('.where_clause').val() === whereClause) {
                    found = true;
                    $foundRow = $(this);
                }
            });

            var rightCell;
            var cellFound = false;
            var nextCellFound = false;
            if (found) {
                $foundRow.children('td.grid_edit').each(function () {
                    if (Sql.getFieldName($(g.t), $(this)) === fieldName) {
                        cellFound = true;
                    }
                    if (cellFound) {
                        if (j >= 1 && ! nextCellFound) {
                            rightCell = this;
                            nextCellFound = true;
                        } else {
                            j++;
                        }
                    }
                });
            }

            if (rightCell) {
                g.hideEditCell(false, false, false, { move : true, cell : rightCell });
            }
        },

        /**
         * Initialize grid editing feature.
         */
        initGridEdit: function () {
            function startGridEditing (e, cell) {
                if (g.isCellEditActive) {
                    g.saveOrPostEditedCell();
                } else {
                    g.showEditCell(cell);
                }
                e.stopPropagation();
            }

            function handleCtrlNavigation (e) {
                if ((e.ctrlKey && e.which === 38) || (e.altKey && e.which === 38)) {
                    g.moveUp(e);
                } else if ((e.ctrlKey && e.which === 40)  || (e.altKey && e.which === 40)) {
                    g.moveDown(e);
                } else if ((e.ctrlKey && e.which === 37) || (e.altKey && e.which === 37)) {
                    g.moveLeft(e);
                } else if ((e.ctrlKey && e.which === 39)  || (e.altKey && e.which === 39)) {
                    g.moveRight(e);
                }
            }

            // create cell edit wrapper element
            g.cEditStd = document.createElement('div');
            g.cEdit = g.cEditStd;
            g.cEditTextarea = document.createElement('div');

            // adjust g.cEditStd
            g.cEditStd.className = 'cEdit';
            $(g.cEditStd).html('<input class="edit_box" rows="1"><div class="edit_area"></div>');
            $(g.cEditStd).hide();

            // adjust g.cEdit
            g.cEditTextarea.className = 'cEdit';
            $(g.cEditTextarea).html('<textarea class="edit_box" rows="1"></textarea><div class="edit_area"></div>');
            $(g.cEditTextarea).hide();

            // assign cell editing hint
            g.cellEditHint = Messages.strCellEditHint;
            g.saveCellWarning = Messages.strSaveCellWarning;
            g.alertNonUnique = Messages.strAlertNonUnique;
            g.gotoLinkText = Messages.strGoToLink;

            // initialize cell editing configuration
            g.saveCellsAtOnce = $(g.o).find('.save_cells_at_once').val();
            g.maxTruncatedLen = CommonParams.get('LimitChars');

            // register events
            $(g.t).find('td.data.click1')
                .on('click', function (e) {
                    startGridEditing(e, this);
                    // prevent default action when clicking on "link" in a table
                    if ($(e.target).is('.grid_edit a')) {
                        e.preventDefault();
                    }
                });

            $(g.t).find('td.data.click2')
                .on('click', function (e) {
                    var $cell = $(this);
                    // In the case of relational link, We want single click on the link
                    // to goto the link and double click to start grid-editing.
                    var $link = $(e.target);
                    if ($link.is('.grid_edit.relation a')) {
                        e.preventDefault();
                        // get the click count and increase
                        var clicks = $cell.data('clicks');
                        clicks = (typeof clicks === 'undefined') ? 1 : clicks + 1;

                        if (clicks === 1) {
                            // if there are no previous clicks,
                            // start the single click timer
                            var timer = setTimeout(function () {
                                // temporarily remove ajax class so the page loader will not handle it,
                                // submit and then add it back
                                $link.removeClass('ajax');
                                AJAX.requestHandler.call($link[0]);
                                $link.addClass('ajax');
                                $cell.data('clicks', 0);
                            }, 700);
                            $cell.data('clicks', clicks);
                            $cell.data('timer', timer);
                        } else {// When double clicking a link, switch to edit mode
                            // this is a double click, cancel the single click timer
                            // and make the click count 0
                            clearTimeout($cell.data('timer'));
                            $cell.data('clicks', 0);
                            // start grid-editing
                            startGridEditing(e, this);
                        }
                    }
                })
                .on('dblclick', function (e) {
                    if ($(e.target).is('.grid_edit a')) {
                        e.preventDefault();
                    } else {
                        startGridEditing(e, this);
                    }
                });

            $(g.cEditStd).on('keydown', 'input.edit_box, select', handleCtrlNavigation);

            $(g.cEditStd).find('.edit_box').on('focus', function () {
                g.showEditArea();
            });
            $(g.cEditStd).on('keydown', '.edit_box, select', function (e) {
                if (e.which === 13) {
                    // post on pressing "Enter"
                    e.preventDefault();
                    g.saveOrPostEditedCell();
                }
            });
            $(g.cEditStd).on('keydown', function (e) {
                if (!g.isEditCellTextEditable) {
                    // prevent text editing
                    e.preventDefault();
                }
            });

            $(g.cEditTextarea).on('keydown', 'textarea.edit_box, select', handleCtrlNavigation);

            $(g.cEditTextarea).find('.edit_box').on('focus', function () {
                g.showEditArea();
            });
            $(g.cEditTextarea).on('keydown', '.edit_box, select', function (e) {
                if (e.which === 13 && !e.shiftKey) {
                    // post on pressing "Enter"
                    e.preventDefault();
                    g.saveOrPostEditedCell();
                }
            });
            $(g.cEditTextarea).on('keydown', function (e) {
                if (!g.isEditCellTextEditable) {
                    // prevent text editing
                    e.preventDefault();
                }
            });
            $('html').on('click', function (e) {
                // hide edit cell if the click is not fromDat edit area
                if ($(e.target).parents().index($(g.cEdit)) === -1 &&
                    !$(e.target).parents('.ui-datepicker-header').length &&
                    !$('.browse_foreign_modal.ui-dialog:visible').length &&
                    !$(e.target).closest('.dismissable').length
                ) {
                    g.hideEditCell();
                }
            }).on('keydown', function (e) {
                if (e.which === 27 && g.isCellEditActive) {
                    // cancel on pressing "Esc"
                    g.hideEditCell(true);
                }
            });
            $(g.o).find('div.save_edited').on('click', function () {
                g.hideEditCell();
                g.postEditedCell();
            });
            $(window).on('beforeunload', function () {
                if (g.isCellEdited) {
                    return g.saveCellWarning;
                }
            });

            // attach to global div
            $(g.gDiv).append(g.cEditStd);
            $(g.gDiv).append(g.cEditTextarea);

            // add hint for grid editing feature when hovering "Edit" link in each table row
            if (Messages.strGridEditFeatureHint !== undefined) {
                Functions.tooltip(
                    $(g.t).find('.edit_row_anchor a'),
                    'a',
                    Messages.strGridEditFeatureHint
                );
            }
        }
    };

    /** ****************
     * Initialize grid
     ******************/

    // wrap all truncated data cells with span indicating the original length
    // todo update the original length after a grid edit
    $(t).find('td.data.truncated:not(:has(span))')
        .wrapInner(function () {
            return '<span title="' + Messages.strOriginalLength + ' ' +
                $(this).data('originallength') + '"></span>';
        });

    // wrap remaining cells, except actions cell, with span
    $(t).find('th, td:not(:has(span))')
        .wrapInner('<span></span>');

    // create grid elements
    g.gDiv = document.createElement('div');     // create global div

    // initialize the table variable
    g.t = t;

    // enclosing .sqlqueryresults div
    g.o = $(t).parents('.sqlqueryresults');

    // get data columns in the first row of the table
    var $firstRowCols = $(t).find('tr').first().find('th.draggable');

    // initialize visible headers count
    g.visibleHeadersCount = $firstRowCols.filter(':visible').length;

    // assign first column (actions) span
    if (! $(t).find('tr').first().find('th').first().hasClass('draggable')) {  // action header exist
        g.actionSpan = $(t).find('tr').first().find('th').first().prop('colspan');
    } else {
        g.actionSpan = 0;
    }

    // assign table create time
    // table_create_time will only available if we are in "Browse" tab
    g.tableCreateTime = $(g.o).find('.table_create_time').val();

    // assign the hints
    g.sortHint = Messages.strSortHint;
    g.strMultiSortHint = Messages.strMultiSortHint;
    g.markHint = Messages.strColMarkHint;
    g.copyHint = Messages.strColNameCopyHint;

    // assign common hidden inputs
    var $commonHiddenInputs = $(g.o).find('div.common_hidden_inputs');
    g.server = $commonHiddenInputs.find('input[name=server]').val();
    g.db = $commonHiddenInputs.find('input[name=db]').val();
    g.table = $commonHiddenInputs.find('input[name=table]').val();

    // add table class
    $(t).addClass('pma_table');

    // add relative position to global div so that resize handlers are correctly positioned
    $(g.gDiv).css('position', 'relative');

    // link the global div
    $(t).before(g.gDiv);
    $(g.gDiv).append(t);

    // FEATURES
    if (isResizeEnabled) {
        g.initColResize();
    }
    // disable reordering for result from EXPLAIN or SHOW syntax, which do not have a table navigation panel
    if (isReorderEnabled &&
        $(g.o).find('table.navigation').length > 0) {
        g.initColReorder();
    }
    if (isVisibEnabled) {
        g.initColVisib();
    }
    // make sure we have the ajax class
    if (isGridEditEnabled &&
        $(t).is('.ajax')) {
        g.initGridEdit();
    }

    // create tooltip for each <th> with draggable class
    Functions.tooltip(
        $(t).find('th.draggable'),
        'th',
        g.updateHint()
    );

    // register events for hint tooltip (anchors inside draggable th)
    $(t).find('th.draggable a')
        .on('mouseenter', function () {
            g.showSortHint = true;
            g.showMultiSortHint = true;
            $(t).find('th.draggable').uiTooltip('option', {
                content: g.updateHint()
            });
        })
        .on('mouseleave', function () {
            g.showSortHint = false;
            g.showMultiSortHint = false;
            $(t).find('th.draggable').uiTooltip('option', {
                content: g.updateHint()
            });
        });

    // register events for dragging-related feature
    if (isResizeEnabled || isReorderEnabled) {
        $(document).on('mousemove', function (e) {
            g.dragMove(e);
        });
        $(document).on('mouseup', function (e) {
            $(g.o).removeClass('turnOffSelect');
            g.dragEnd(e);
        });
    }

    // some adjustment
    $(t).removeClass('data');
    $(g.gDiv).addClass('data');
};

/**
 * jQuery plugin to cancel selection in HTML code.
 */
(function ($) {
    $.fn.noSelect = function (p) { // no select plugin by Paulo P.Marinas
        var prevent = (p === null) ? true : p;
        /* eslint-disable compat/compat */
        var isMsie = navigator.userAgent.indexOf('MSIE') > -1 || !!window.navigator.userAgent.match(/Trident.*rv:11\./);
        var isFirefox = navigator.userAgent.indexOf('Firefox') > -1;
        var isSafari = navigator.userAgent.indexOf('Safari') > -1;
        var isOpera = navigator.userAgent.indexOf('Presto') > -1;
        /* eslint-enable compat/compat */
        if (prevent) {
            return this.each(function () {
                if (isMsie || isSafari) {
                    $(this).on('selectstart', false);
                } else if (isFirefox) {
                    $(this).css('MozUserSelect', 'none');
                    $('body').trigger('focus');
                } else if (isOpera) {
                    $(this).on('mousedown', false);
                } else {
                    $(this).attr('unselectable', 'on');
                }
            });
        } else {
            return this.each(function () {
                if (isMsie || isSafari) {
                    $(this).off('selectstart');
                } else if (isFirefox) {
                    $(this).css('MozUserSelect', 'inherit');
                } else if (isOpera) {
                    $(this).off('mousedown');
                } else {
                    $(this).removeAttr('unselectable');
                }
            });
        }
    }; // end noSelect
}(jQuery));
