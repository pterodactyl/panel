/**
 * @fileoverview    functions used on the table structure page
 * @name            Table Structure
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

// eslint-disable-next-line no-unused-vars
/* global primaryIndexes:writable, indexes:writable, fulltextIndexes:writable, spatialIndexes:writable */ // js/functions.js
/* global sprintf */ // js/vendor/sprintf.js

/**
 * AJAX scripts for /table/structure
 *
 * Actions ajaxified here:
 * Drop Column
 * Add Primary Key
 * Drop Primary Key/Index
 *
 */

/**
 * Reload fields table
 */
function reloadFieldForm () {
    $.post($('#fieldsForm').attr('action'), $('#fieldsForm').serialize() + CommonParams.get('arg_separator') + 'ajax_request=true', function (formData) {
        var $tempDiv = $('<div id=\'temp_div\'><div>').append(formData.message);
        $('#fieldsForm').replaceWith($tempDiv.find('#fieldsForm'));
        $('#addColumns').replaceWith($tempDiv.find('#addColumns'));
        $('#move_columns_dialog').find('ul').replaceWith($tempDiv.find('#move_columns_dialog ul'));
    });
    $('#page_content').show();
}

function checkFirst () {
    if ($('select[name=after_field] option:selected').data('pos') === 'first') {
        $('input[name=field_where]').val('first');
    } else {
        $('input[name=field_where]').val('after');
    }
}
/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('table/structure.js', function () {
    $(document).off('click', 'a.drop_column_anchor.ajax');
    $(document).off('click', 'a.add_key.ajax');
    $(document).off('click', '#move_columns_anchor');
    $(document).off('submit', '.append_fields_form.ajax');
    $('body').off('click', '#fieldsForm button.mult_submit');
    $(document).off('click', 'a[id^=partition_action].ajax');
    $(document).off('click', '#remove_partitioning.ajax');
});

AJAX.registerOnload('table/structure.js', function () {
    // Re-initialize variables.
    primaryIndexes = [];
    indexes = [];
    fulltextIndexes = [];
    spatialIndexes = [];

    /**
     *Ajax action for submitting the "Column Change" and "Add Column" form
     */
    $('.append_fields_form.ajax').off();
    $(document).on('submit', '.append_fields_form.ajax', function (event) {
        event.preventDefault();
        /**
         * @var form object referring to the export form
         */
        var $form = $(this);
        var fieldCnt = $form.find('input[name=orig_num_fields]').val();


        function submitForm () {
            var $msg = Functions.ajaxShowMessage(Messages.strProcessingRequest);
            $.post($form.attr('action'), $form.serialize() + CommonParams.get('arg_separator') + 'do_save_data=1', function (data) {
                if ($('.sqlqueryresults').length !== 0) {
                    $('.sqlqueryresults').remove();
                } else if ($('.error:not(.tab)').length !== 0) {
                    $('.error:not(.tab)').remove();
                }
                if (typeof data.success !== 'undefined' && data.success === true) {
                    $('#page_content')
                        .empty()
                        .append(data.message)
                        .show();
                    Functions.highlightSql($('#page_content'));
                    $('.result_query .alert-primary').remove();
                    if (typeof data.structure_refresh_route !== 'string') {
                        // Do not reload the form when the code below freshly filled it
                        reloadFieldForm();
                    }
                    $form.remove();
                    Functions.ajaxRemoveMessage($msg);
                    Navigation.reload();
                    if (typeof data.structure_refresh_route === 'string') {
                        // Fetch the table structure right after adding a new column
                        $.get(data.structure_refresh_route, function (data) {
                            if (typeof data.success !== 'undefined' && data.success === true) {
                                $('#page_content').append(data.message).show();
                            }
                        });
                    } else {
                        CommonActions.refreshMain('index.php?route=/table/structure');
                    }
                } else {
                    Functions.ajaxShowMessage(data.error, false);
                }
            }); // end $.post()
        }

        function checkIfConfirmRequired ($form) {
            var i = 0;
            var id;
            var elm;
            var val;
            var nameOrig;
            var elmOrig;
            var valOrig;
            var checkRequired = false;
            for (i = 0; i < fieldCnt; i++) {
                id = '#field_' + i + '_5';
                elm = $(id);
                val = elm.val();

                nameOrig = 'input[name=field_collation_orig\\[' + i + '\\]]';
                elmOrig = $form.find(nameOrig);
                valOrig = elmOrig.val();

                if (val && valOrig && val !== valOrig) {
                    checkRequired = true;
                    break;
                }
            }
            return checkRequired;
        }

        /*
         * First validate the form; if there is a problem, avoid submitting it
         *
         * Functions.checkTableEditForm() needs a pure element and not a jQuery object,
         * this is why we pass $form[0] as a parameter (the jQuery object
         * is actually an array of DOM elements)
         */
        if (Functions.checkTableEditForm($form[0], fieldCnt)) {
            // OK, form passed validation step

            Functions.prepareForAjaxRequest($form);
            if (Functions.checkReservedWordColumns($form)) {
                // User wants to submit the form

                // If Collation is changed, Warn and Confirm
                if (checkIfConfirmRequired($form)) {
                    var question = sprintf(
                        Messages.strChangeColumnCollation, 'https://wiki.phpmyadmin.net/pma/Garbled_data'
                    );
                    $form.confirm(question, $form.attr('action'), function () {
                        submitForm();
                    });
                } else {
                    submitForm();
                }
            }
        }
    }); // end change table button "do_save_data"

    /**
     * Attach Event Handler for 'Drop Column'
     */
    $(document).on('click', 'a.drop_column_anchor.ajax', function (event) {
        event.preventDefault();
        /**
         * @var currTableName String containing the name of the current table
         */
        var currTableName = $(this).closest('form').find('input[name=table]').val();
        /**
         * @var currRow    Object reference to the currently selected row (i.e. field in the table)
         */
        var $currRow = $(this).parents('tr');
        /**
         * @var currColumnName    String containing name of the field referred to by {@link curr_row}
         */
        var currColumnName = $currRow.children('th').children('label').text().trim();
        currColumnName = Functions.escapeHtml(currColumnName);
        /**
         * @var $afterFieldItem    Corresponding entry in the 'After' field.
         */
        var $afterFieldItem = $('select[name=\'after_field\'] option[value=\'' + currColumnName + '\']');
        /**
         * @var question String containing the question to be asked for confirmation
         */
        var question = Functions.sprintf(Messages.strDoYouReally, 'ALTER TABLE `' + currTableName + '` DROP `' + currColumnName + '`;');
        var $thisAnchor = $(this);
        $thisAnchor.confirm(question, $thisAnchor.attr('href'), function (url) {
            var $msg = Functions.ajaxShowMessage(Messages.strDroppingColumn, false);
            var params = Functions.getJsConfirmCommonParam(this, $thisAnchor.getPostData());
            params += CommonParams.get('arg_separator') + 'ajax_page_request=1';
            $.post(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    Functions.ajaxRemoveMessage($msg);
                    if ($('.result_query').length) {
                        $('.result_query').remove();
                    }
                    if (data.sql_query) {
                        $('<div class="result_query"></div>')
                            .html(data.sql_query)
                            .prependTo('#structure_content');
                        Functions.highlightSql($('#page_content'));
                    }
                    // Adjust the row numbers
                    for (var $row = $currRow.next(); $row.length > 0; $row = $row.next()) {
                        var newVal = parseInt($row.find('td').eq(1).text(), 10) - 1;
                        $row.find('td').eq(1).text(newVal);
                    }
                    $afterFieldItem.remove();
                    $currRow.hide('medium').remove();

                    // Remove the dropped column from select menu for 'after field'
                    $('select[name=after_field]').find(
                        '[value="' + currColumnName + '"]'
                    ).remove();

                    // by default select the (new) last option to add new column
                    // (in case last column is dropped)
                    $('select[name=after_field] option').last().attr('selected','selected');

                    // refresh table stats
                    if (data.tableStat) {
                        $('#tablestatistics').html(data.tableStat);
                    }
                    // refresh the list of indexes (comes from /sql)
                    $('.index_info').replaceWith(data.indexes_list);
                    Navigation.reload();
                } else {
                    Functions.ajaxShowMessage(Messages.strErrorProcessingRequest + ' : ' + data.error, false);
                }
            }); // end $.post()
        });
    }); // end of Drop Column Anchor action

    /**
     * Ajax Event handler for adding keys
     */
    $(document).on('click', 'a.add_key.ajax', function (event) {
        event.preventDefault();

        var $this = $(this);
        var currTableName = $this.closest('form').find('input[name=table]').val();
        var currColumnName = $this.parents('tr').children('th').children('label').text().trim();

        var addClause = '';
        if ($this.is('.add_primary_key_anchor')) {
            addClause = 'ADD PRIMARY KEY';
        } else if ($this.is('.add_index_anchor')) {
            addClause = 'ADD INDEX';
        } else if ($this.is('.add_unique_anchor')) {
            addClause = 'ADD UNIQUE';
        } else if ($this.is('.add_spatial_anchor')) {
            addClause = 'ADD SPATIAL';
        } else if ($this.is('.add_fulltext_anchor')) {
            addClause = 'ADD FULLTEXT';
        }
        var question = Functions.sprintf(Messages.strDoYouReally, 'ALTER TABLE `' +
                Functions.escapeHtml(currTableName) + '` ' + addClause + '(`' + Functions.escapeHtml(currColumnName) + '`);');

        var $thisAnchor = $(this);

        $thisAnchor.confirm(question, $thisAnchor.attr('href'), function (url) {
            Functions.ajaxShowMessage();
            AJAX.source = $this;

            var params = Functions.getJsConfirmCommonParam(this, $thisAnchor.getPostData());
            params += CommonParams.get('arg_separator') + 'ajax_page_request=1';
            $.post(url, params, AJAX.responseHandler);
        });
    }); // end Add key

    /**
     * Inline move columns
    **/
    $(document).on('click', '#move_columns_anchor', function (e) {
        e.preventDefault();

        var buttonOptionsError = {};
        buttonOptionsError[Messages.strOK] = function () {
            $(this).dialog('close').remove();
        };

        var columns = [];

        $('#tablestructure').find('tbody tr').each(function () {
            var colName = $(this).find('input:checkbox').eq(0).val();
            var hiddenInput = $('<input>')
                .prop({
                    name: 'move_columns[]',
                    type: 'hidden'
                })
                .val(colName);
            columns[columns.length] = $('<li></li>')
                .addClass('placeholderDrag')
                .text(colName)
                .append(hiddenInput);
        });

        var colList = $('#move_columns_dialog').find('ul')
            .find('li').remove().end();
        for (var i in columns) {
            colList.append(columns[i]);
        }
        colList.sortable({
            axis: 'y',
            containment: $('#move_columns_dialog').find('div'),
            tolerance: 'pointer'
        }).disableSelection();
        var $form = $('#move_columns_dialog').find('form');
        $form.data('serialized-unmoved', $form.serialize());

        const designerModalPreviewModal = document.getElementById('designerModalPreviewModal');
        designerModalPreviewModal.addEventListener('shown.bs.modal', () => {
            const modalBody = designerModalPreviewModal.querySelector('.modal-body');
            const $form = $('#move_column_form');
            const formUrl = $form.attr('action');
            const sep = CommonParams.get('arg_separator');
            const formData = $form.serialize() +
                sep + 'preview_sql=1' +
                sep + 'ajax_request=1';
            $.post({
                url: formUrl,
                data: formData,
                success: response => {
                    if (! response.success) {
                        modalBody.innerHTML = '<div class="alert alert-danger" role="alert">' + Messages.strErrorProcessingRequest + '</div>';
                        return;
                    }

                    modalBody.innerHTML = response.sql_data;
                    Functions.highlightSql($('#designerModalPreviewModal'));
                },
                error: () => {
                    modalBody.innerHTML = '<div class="alert alert-danger" role="alert">' + Messages.strErrorProcessingRequest + '</div>';
                }
            });
        });
        designerModalPreviewModal.addEventListener('hidden.bs.modal', () => {
            designerModalPreviewModal.querySelector('.modal-body').innerHTML = '<div class="spinner-border" role="status">' +
                '<span class="visually-hidden">' + Messages.strLoading + '</span></div>';
        });

        $('#moveColumnsModal').modal('show');
        $('#designerModalGoButton').off('click');// Unregister previous modals
        $('#designerModalGoButton').on('click', function () {
            event.preventDefault();
            var $msgbox = Functions.ajaxShowMessage();
            var $this = $('#moveColumnsModal');
            var $form = $this.find('form');
            var serialized = $form.serialize();
            // check if any columns were moved at all
            $('#moveColumnsModal').modal('hide');
            if (serialized === $form.data('serialized-unmoved')) {
                Functions.ajaxRemoveMessage($msgbox);
                return;
            }
            $.post($form.prop('action'), serialized + CommonParams.get('arg_separator') + 'ajax_request=true', function (data) {
                if (data.success === false) {
                    Functions.ajaxRemoveMessage($msgbox);
                    var errorModal = $('#moveColumnsErrorModal');
                    errorModal.modal('show');
                    errorModal.find('.modal-body').first().html(data.error);
                } else {
                    // sort the fields table
                    var $fieldsTable = $('table#tablestructure tbody');
                    // remove all existing rows and remember them
                    var $rows = $fieldsTable.find('tr').remove();
                    // loop through the correct order
                    for (var i in data.columns) {
                        var theColumn = data.columns[i];
                        var $theRow = $rows
                            .find('input:checkbox[value=\'' + theColumn + '\']')
                            .closest('tr');
                        // append the row for this column to the table
                        $fieldsTable.append($theRow);
                    }
                    var $firstrow = $fieldsTable.find('tr').eq(0);
                    // Adjust the row numbers and colors
                    for (var $row = $firstrow; $row.length > 0; $row = $row.next()) {
                        $row
                            .find('td').eq(1)
                            .text($row.index() + 1)
                            .end()
                            .removeClass('odd even')
                            .addClass($row.index() % 2 === 0 ? 'odd' : 'even');
                    }
                    Functions.ajaxShowMessage(data.message);
                }
            });
        });
    });

    /**
     * Handles multi submits in table structure page such as change, browse, drop, primary etc.
     */
    $('body').on('click', '#fieldsForm button.mult_submit', function (e) {
        e.preventDefault();
        var $form = $(this).parents('form');
        var argsep = CommonParams.get('arg_separator');
        var submitData = $form.serialize() + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';

        Functions.ajaxShowMessage();
        AJAX.source = $form;

        $.post(this.formAction, submitData, AJAX.responseHandler);
    });

    /**
     * Handles clicks on Action links in partition table
     */
    $(document).on('click', 'a[id^=partition_action].ajax', function (e) {
        e.preventDefault();
        var $link = $(this);

        function submitPartitionAction (url) {
            var params = 'ajax_request=true&ajax_page_request=true&' + $link.getPostData();
            Functions.ajaxShowMessage();
            AJAX.source = $link;
            $.post(url, params, AJAX.responseHandler);
        }

        if ($link.is('#partition_action_DROP')) {
            $link.confirm(Messages.strDropPartitionWarning, $link.attr('href'), function (url) {
                submitPartitionAction(url);
            });
        } else if ($link.is('#partition_action_TRUNCATE')) {
            $link.confirm(Messages.strTruncatePartitionWarning, $link.attr('href'), function (url) {
                submitPartitionAction(url);
            });
        } else {
            submitPartitionAction($link.attr('href'));
        }
    });

    /**
     * Handles remove partitioning
     */
    $(document).on('click', '#remove_partitioning.ajax', function (e) {
        e.preventDefault();
        var $link = $(this);
        var question = Messages.strRemovePartitioningWarning;
        $link.confirm(question, $link.attr('href'), function (url) {
            var params = Functions.getJsConfirmCommonParam({
                'ajax_request' : true,
                'ajax_page_request' : true
            }, $link.getPostData());
            Functions.ajaxShowMessage();
            AJAX.source = $link;
            $.post(url, params, AJAX.responseHandler);
        });
    });

    $(document).on('change', 'select[name=after_field]', function () {
        checkFirst();
    });
});
