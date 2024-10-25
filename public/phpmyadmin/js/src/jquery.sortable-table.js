/**
 * This file is internal to phpMyAdmin.
 * @license see the main phpMyAdmin license.
 *
 * @fileoverview    A jquery plugin that allows drag&drop sorting in tables.
 *                  Coded because JQuery UI sortable doesn't support tables. Also it has no animation
 *
 * @name            Sortable Table JQuery plugin
 *
 * @requires        jQuery
 */

/**
 * Options:
 *
 * $('table').sortableTable({
 *   ignoreRect: { top, left, width, height } - Relative coordinates on each element. If the user clicks
 *                                              in this area, it is not seen as a drag&drop request. Useful for toolbars etc.
 *   events: {
 *     start: callback function when the user starts dragging
 *     drop: callback function after an element has been dropped
 *   }
 * })
 */

/**
 * Commands:
 *
 * $('table').sortableTable('init')    - equivalent to $('table').sortableTable()
 * $('table').sortableTable('refresh') - if the table has been changed, refresh correctly assigns all events again
 * $('table').sortableTable('destroy') - removes all events from the table
 */

/**
 * Setup:
 *
 * Can be applied on any table, there is just one convention.
 * Each cell (<td>) has to contain one and only one element (preferably div or span)
 * which is the actually draggable element.
 */
(function ($) {
    jQuery.fn.sortableTable = function (method) {
        var methods = {
            init: function (options) {
                var tb = new SortableTableInstance(this, options);
                tb.init();
                $(this).data('sortableTable', tb);
            },
            refresh: function () {
                $(this).data('sortableTable').refresh();
            },
            destroy: function () {
                $(this).data('sortableTable').destroy();
            }
        };

        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.sortableTable');
        }

        function SortableTableInstance (table, options = {}) {
            var down = false;
            var $draggedEl;
            var oldCell;
            var previewMove;
            var id;

            /* Mouse handlers on the child elements */
            var onMouseUp = function (e) {
                dropAt(e.pageX, e.pageY);
            };

            var onMouseDown = function (e) {
                $draggedEl = $(this).children();
                if ($draggedEl.length === 0) {
                    return;
                }
                if (options.ignoreRect && insideRect({ x: e.pageX - $draggedEl.offset().left, y: e.pageY - $draggedEl.offset().top }, options.ignoreRect)) {
                    return;
                }

                down = true;
                oldCell = this;

                if (options.events && options.events.start) {
                    options.events.start(this);
                }

                return false;
            };

            var globalMouseMove = function (e) {
                if (down) {
                    move(e.pageX, e.pageY);

                    if (inside($(oldCell), e.pageX, e.pageY)) {
                        if (previewMove !== null) {
                            moveTo(previewMove);
                            previewMove = null;
                        }
                    } else {
                        $(table).find('td').each(function () {
                            if (inside($(this), e.pageX, e.pageY)) {
                                if ($(previewMove).attr('class') !== $(this).children().first().attr('class')) {
                                    if (previewMove !== null) {
                                        moveTo(previewMove);
                                    }
                                    previewMove = $(this).children().first();
                                    if (previewMove.length > 0) {
                                        moveTo($(previewMove), {
                                            pos: {
                                                top: $(oldCell).offset().top - $(previewMove).parent().offset().top,
                                                left: $(oldCell).offset().left - $(previewMove).parent().offset().left
                                            }
                                        });
                                    }
                                }

                                return false;
                            }
                        });
                    }
                }

                return false;
            };

            var globalMouseOut = function () {
                if (down) {
                    down = false;
                    if (previewMove) {
                        moveTo(previewMove);
                    }
                    moveTo($draggedEl);
                    previewMove = null;
                }
            };

            // Initialize sortable table
            this.init = function () {
                id = 1;
                // Add some required css to each child element in the <td>s
                $(table).find('td').children().each(function () {
                    // Remove any old occurrences of our added draggable-num class
                    $(this).attr('class', $(this).attr('class').replace(/\s*draggable-\d+/g, ''));
                    $(this).addClass('draggable-' + (id++));
                });

                // Mouse events
                $(table).find('td').on('mouseup', onMouseUp);
                $(table).find('td').on('mousedown', onMouseDown);

                $(document).on('mousemove', globalMouseMove);
                $(document).on('mouseleave', globalMouseOut);
            };

            // Call this when the table has been updated
            this.refresh = function () {
                this.destroy();
                this.init();
            };

            this.destroy = function () {
                // Add some required css to each child element in the <td>s
                $(table).find('td').children().each(function () {
                    // Remove any old occurrences of our added draggable-num class
                    $(this).attr('class', $(this).attr('class').replace(/\s*draggable-\d+/g, ''));
                });

                // Mouse events
                $(table).find('td').off('mouseup', onMouseUp);
                $(table).find('td').off('mousedown', onMouseDown);

                $(document).off('mousemove', globalMouseMove);
                $(document).off('mouseleave', globalMouseOut);
            };

            function switchElement (drag, dropTo) {
                var dragPosDiff = {
                    left: $(drag).children().first().offset().left - $(dropTo).offset().left,
                    top: $(drag).children().first().offset().top - $(dropTo).offset().top
                };

                var dropPosDiff = null;
                if ($(dropTo).children().length > 0) {
                    dropPosDiff = {
                        left: $(dropTo).children().first().offset().left - $(drag).offset().left,
                        top: $(dropTo).children().first().offset().top - $(drag).offset().top
                    };
                }

                /* I love you append(). It moves the DOM Elements so gracefully <3 */
                // Put the element in the way to old place
                $(drag).append($(dropTo).children().first()).children()
                    .stop(true, true)
                    .on('mouseup', onMouseUp);

                if (dropPosDiff) {
                    $(drag).append($(dropTo).children().first()).children()
                        .css('left', dropPosDiff.left + 'px')
                        .css('top', dropPosDiff.top + 'px');
                }

                // Put our dragged element into the space we just freed up
                $(dropTo).append($(drag).children().first()).children()
                    .on('mouseup', onMouseUp)
                    .css('left', dragPosDiff.left + 'px')
                    .css('top', dragPosDiff.top + 'px');

                moveTo($(dropTo).children().first(), { duration: 100 });
                moveTo($(drag).children().first(), { duration: 100 });

                if (options.events && options.events.drop) {
                    // Drop event. The drag child element is moved into the drop element
                    // and vice versa. So the parameters are switched.

                    // Calculate row and column index
                    const colIdx = $(dropTo).prevAll().length;
                    const rowIdx = $(dropTo).parent().prevAll().length;

                    options.events.drop(drag, dropTo, { col: colIdx, row: rowIdx });
                }
            }

            function move (x, y) {
                $draggedEl.offset({
                    top: Math.min($(document).height(), Math.max(0, y - $draggedEl.height() / 2)),
                    left: Math.min($(document).width(), Math.max(0, x - $draggedEl.width() / 2))
                });
            }

            function inside ($el, x, y) {
                var off = $el.offset();
                return y >= off.top && x >= off.left && x < off.left + $el.width() && y < off.top + $el.height();
            }

            function insideRect (pos, r) {
                return pos.y > r.top && pos.x > r.left && pos.y < r.top + r.height && pos.x < r.left + r.width;
            }

            function dropAt (x, y) {
                if (!down) {
                    return;
                }
                down = false;

                var switched = false;

                $(table).find('td').each(function () {
                    if ($(this).children().first().attr('class') !== $(oldCell).children().first().attr('class') && inside($(this), x, y)) {
                        switchElement(oldCell, this);
                        switched = true;
                    }
                });

                if (!switched) {
                    if (previewMove) {
                        moveTo(previewMove);
                    }
                    moveTo($draggedEl);
                }

                previewMove = null;
            }

            function moveTo (elem, opts = {}) {
                if (!opts.pos) {
                    opts.pos = { left: 0, top: 0 };
                }
                if (!opts.duration) {
                    opts.duration = 200;
                }

                $(elem).css('position', 'relative');
                $(elem).animate({ top: opts.pos.top, left: opts.pos.left }, {
                    duration: opts.duration,
                    complete: function () {
                        if (opts.pos.left === 0 && opts.pos.top === 0) {
                            $(elem)
                                .css('position', '')
                                .css('left', '')
                                .css('top', '');
                        }
                    }
                });
            }
        }
    };
}(jQuery));
