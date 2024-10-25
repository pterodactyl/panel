/**
 * @fileoverview    functions used on the server databases list page
 * @name            Server Databases
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server/databases.js', function () {
    $(document).off('submit', '#dbStatsForm');
    $(document).off('submit', '#create_database_form.ajax');
});

/**
 * AJAX scripts for /server/databases
 *
 * Actions ajaxified here:
 * Drop Databases
 *
 */
AJAX.registerOnload('server/databases.js', function () {
    /**
     * Attach Event Handler for 'Drop Databases'
     */
    $(document).on('submit', '#dbStatsForm', function (event) {
        event.preventDefault();

        var $form = $(this);

        /**
         * @var selected_dbs Array containing the names of the checked databases
         */
        var selectedDbs = [];
        // loop over all checked checkboxes, except the .checkall_box checkbox
        $form.find('input:checkbox:checked:not(.checkall_box)').each(function () {
            $(this).closest('tr').addClass('removeMe');
            selectedDbs[selectedDbs.length] = 'DROP DATABASE `' + Functions.escapeHtml($(this).val()) + '`;';
        });
        if (! selectedDbs.length) {
            Functions.ajaxShowMessage(
                $('<div class="alert alert-warning" role="alert"></div>').text(
                    Messages.strNoDatabasesSelected
                ),
                2000
            );
            return;
        }
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = Messages.strDropDatabaseStrongWarning + ' ' +
            Functions.sprintf(Messages.strDoYouReally, selectedDbs.join('<br>'));

        const modal = $('#dropDatabaseModal');
        modal.find('.modal-body').html(question);
        modal.modal('show');

        const url = 'index.php?route=/server/databases/destroy&' + $(this).serialize();

        $('#dropDatabaseModalDropButton').on('click', function () {
            Functions.ajaxShowMessage(Messages.strProcessingRequest, false);

            var parts = url.split('?');
            var params = Functions.getJsConfirmCommonParam(this, parts[1]);

            $.post(parts[0], params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    Functions.ajaxShowMessage(data.message);

                    var $rowsToRemove = $form.find('tr.removeMe');
                    var $databasesCount = $('#filter-rows-count');
                    var newCount = parseInt($databasesCount.text(), 10) - $rowsToRemove.length;
                    $databasesCount.text(newCount);

                    $rowsToRemove.remove();
                    $form.find('tbody').sortTable('.name');
                    if ($form.find('tbody').find('tr').length === 0) {
                        // user just dropped the last db on this page
                        CommonActions.refreshMain();
                    }
                    Navigation.reload();
                } else {
                    $form.find('tr.removeMe').removeClass('removeMe');
                    Functions.ajaxShowMessage(data.error, false);
                }
            });

            modal.modal('hide');
            $('#dropDatabaseModalDropButton').off('click');
        });
    });

    /**
     * Attach Ajax event handlers for 'Create Database'.
     */
    $(document).on('submit', '#create_database_form.ajax', function (event) {
        event.preventDefault();

        var $form = $(this);

        // TODO Remove this section when all browsers support HTML5 "required" property
        var newDbNameInput = $form.find('input[name=new_db]');
        if (newDbNameInput.val() === '') {
            newDbNameInput.trigger('focus');
            alert(Messages.strFormEmpty);
            return;
        }
        // end remove

        Functions.ajaxShowMessage(Messages.strProcessingRequest);
        Functions.prepareForAjaxRequest($form);

        $.post($form.attr('action'), $form.serialize(), function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                Functions.ajaxShowMessage(data.message);

                var $databasesCountObject = $('#filter-rows-count');
                var databasesCount = parseInt($databasesCountObject.text(), 10) + 1;
                $databasesCountObject.text(databasesCount);
                Navigation.reload();

                // make ajax request to load db structure page - taken from ajax.js
                var dbStructUrl = data.url;
                dbStructUrl = dbStructUrl.replace(/amp;/ig, '');
                var params = 'ajax_request=true' + CommonParams.get('arg_separator') + 'ajax_page_request=true';
                $.get(dbStructUrl, params, AJAX.responseHandler);
            } else {
                Functions.ajaxShowMessage(data.error, false);
            }
        }); // end $.post()
    }); // end $(document).on()

    var tableRows = $('.server_databases');
    $.each(tableRows, function () {
        $(this).on('click', function () {
            CommonActions.setDb($(this).attr('data'));
        });
    });
}); // end $()
