/**
 * Functions used in the export tab
 *
 */

var Export = {};

/**
 * Disables the "Dump some row(s)" sub-options
 */
Export.disableDumpSomeRowsSubOptions = function () {
    $('label[for=\'limit_to\']').fadeTo('fast', 0.4);
    $('label[for=\'limit_from\']').fadeTo('fast', 0.4);
    $('input[type=\'text\'][name=\'limit_to\']').prop('disabled', 'disabled');
    $('input[type=\'text\'][name=\'limit_from\']').prop('disabled', 'disabled');
};

/**
 * Enables the "Dump some row(s)" sub-options
 */
Export.enableDumpSomeRowsSubOptions = function () {
    $('label[for=\'limit_to\']').fadeTo('fast', 1);
    $('label[for=\'limit_from\']').fadeTo('fast', 1);
    $('input[type=\'text\'][name=\'limit_to\']').prop('disabled', '');
    $('input[type=\'text\'][name=\'limit_from\']').prop('disabled', '');
};

/**
 * Return template data as a json object
 *
 * @return {object} template data
 */
Export.getTemplateData = function () {
    var $form = $('form[name="dump"]');
    var excludeList = ['token', 'server', 'db', 'table', 'single_table',
        'export_type', 'export_method', 'sql_query', 'template_id'];
    var obj = {};
    var arr = $form.serializeArray();
    $.each(arr, function () {
        if ($.inArray(this.name, excludeList) < 0) {
            if (obj[this.name] !== undefined) {
                if (! obj[this.name].push) {
                    obj[this.name] = [obj[this.name]];
                }
                obj[this.name].push(this.value || '');
            } else {
                obj[this.name] = this.value || '';
            }
        }
    });
    // include unchecked checkboxes (which are ignored by serializeArray()) with null
    // to uncheck them when loading the template
    $form.find('input[type="checkbox"]:not(:checked)').each(function () {
        if (obj[this.name] === undefined) {
            obj[this.name] = null;
        }
    });
    // include empty multiselects
    $form.find('select').each(function () {
        if ($(this).find('option:selected').length === 0) {
            obj[this.name] = [];
        }
    });
    return obj;
};

/**
 * Create a template with selected options
 *
 * @param name name of the template
 */
Export.createTemplate = function (name) {
    var templateData = Export.getTemplateData();

    var params = {
        'ajax_request': true,
        'server': CommonParams.get('server'),
        'db': CommonParams.get('db'),
        'table': CommonParams.get('table'),
        'exportType': $('input[name="export_type"]').val(),
        'templateName': name,
        'templateData': JSON.stringify(templateData)
    };

    Functions.ajaxShowMessage();
    $.post('index.php?route=/export/template/create', params, function (response) {
        if (response.success === true) {
            $('#templateName').val('');
            $('#template').html(response.data);
            $('#template').find('option').each(function () {
                if ($(this).text() === name) {
                    $(this).prop('selected', true);
                }
            });
            Functions.ajaxShowMessage(Messages.strTemplateCreated);
        } else {
            Functions.ajaxShowMessage(response.error, false);
        }
    });
};

/**
 * Loads a template
 *
 * @param id ID of the template to load
 */
Export.loadTemplate = function (id) {
    var params = {
        'ajax_request': true,
        'server': CommonParams.get('server'),
        'db': CommonParams.get('db'),
        'table': CommonParams.get('table'),
        'exportType': $('input[name="export_type"]').val(),
        'templateId': id,
    };

    Functions.ajaxShowMessage();
    $.post('index.php?route=/export/template/load', params, function (response) {
        if (response.success === true) {
            var $form = $('form[name="dump"]');
            var options = JSON.parse(response.data);
            $.each(options, function (key, value) {
                var localValue = value;
                var $element = $form.find('[name="' + key + '"]');
                if ($element.length) {
                    if (($element.is('input') && $element.attr('type') === 'checkbox') && localValue === null) {
                        $element.prop('checked', false);
                    } else {
                        if (($element.is('input') && $element.attr('type') === 'checkbox') ||
                            ($element.is('input') && $element.attr('type') === 'radio') ||
                            ($element.is('select') && $element.attr('multiple') === 'multiple')) {
                            if (! localValue.push) {
                                localValue = [localValue];
                            }
                        }
                        $element.val(localValue);
                    }
                    $element.trigger('change');
                }
            });
            $('input[name="template_id"]').val(id);
            Functions.ajaxShowMessage(Messages.strTemplateLoaded);
        } else {
            Functions.ajaxShowMessage(response.error, false);
        }
    });
};

/**
 * Updates an existing template with current options
 *
 * @param id ID of the template to update
 */
Export.updateTemplate = function (id) {
    var templateData = Export.getTemplateData();

    var params = {
        'ajax_request': true,
        'server': CommonParams.get('server'),
        'db': CommonParams.get('db'),
        'table': CommonParams.get('table'),
        'exportType': $('input[name="export_type"]').val(),
        'templateId': id,
        'templateData': JSON.stringify(templateData)
    };

    Functions.ajaxShowMessage();
    $.post('index.php?route=/export/template/update', params, function (response) {
        if (response.success === true) {
            Functions.ajaxShowMessage(Messages.strTemplateUpdated);
        } else {
            Functions.ajaxShowMessage(response.error, false);
        }
    });
};

/**
 * Delete a template
 *
 * @param id ID of the template to delete
 */
Export.deleteTemplate = function (id) {
    var params = {
        'ajax_request': true,
        'server': CommonParams.get('server'),
        'db': CommonParams.get('db'),
        'table': CommonParams.get('table'),
        'exportType': $('input[name="export_type"]').val(),
        'templateId': id,
    };

    Functions.ajaxShowMessage();
    $.post('index.php?route=/export/template/delete', params, function (response) {
        if (response.success === true) {
            $('#template').find('option[value="' + id + '"]').remove();
            Functions.ajaxShowMessage(Messages.strTemplateDeleted);
        } else {
            Functions.ajaxShowMessage(response.error, false);
        }
    });
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('export.js', function () {
    $('#plugins').off('change');
    $('input[type=\'radio\'][name=\'sql_structure_or_data\']').off('change');
    $('input[type=\'radio\'][name$=\'_structure_or_data\']').off('change');
    $('input[type=\'radio\'][name=\'output_format\']').off('change');
    $('#checkbox_sql_include_comments').off('change');
    $('input[type=\'radio\'][name=\'quick_or_custom\']').off('change');
    $('input[type=\'radio\'][name=\'allrows\']').off('change');
    $('#btn_alias_config').off('click');
    $('.alias_remove').off('click');
    $('#db_alias_button').off('click');
    $('#table_alias_button').off('click');
    $('#column_alias_button').off('click');
    $('input[name="table_select[]"]').off('change');
    $('input[name="table_structure[]"]').off('change');
    $('input[name="table_data[]"]').off('change');
    $('#table_structure_all').off('change');
    $('#table_data_all').off('change');
    $('input[name="createTemplate"]').off('click');
    $('select[name="template"]').off('change');
    $('input[name="updateTemplate"]').off('click');
    $('input[name="deleteTemplate"]').off('click');
});

AJAX.registerOnload('export.js', function () {
    $('#showsqlquery').on('click', function () {
        // Creating a dialog box similar to preview sql container to show sql query
        var modal = $('#showSqlQueryModal');
        modal.modal('show');
        modal.on('shown.bs.modal', function () {
            $('#showSqlQueryModalLabel').first().html(Messages.strQuery);
            Functions.highlightSql(modal);
        });
    });

    /**
     * Export template handling code
     */
    // create a new template
    $('input[name="createTemplate"]').on('click', function (e) {
        e.preventDefault();
        var name = $('input[name="templateName"]').val();
        if (name.length) {
            Export.createTemplate(name);
        }
    });

    // load an existing template
    $('select[name="template"]').on('change', function (e) {
        e.preventDefault();
        var id = $(this).val();
        if (id.length) {
            Export.loadTemplate(id);
        }
    });

    // update an existing template with new criteria
    $('input[name="updateTemplate"]').on('click', function (e) {
        e.preventDefault();
        var id = $('select[name="template"]').val();
        if (id.length) {
            Export.updateTemplate(id);
        }
    });

    // delete an existing template
    $('input[name="deleteTemplate"]').on('click', function (e) {
        e.preventDefault();
        var id = $('select[name="template"]').val();
        if (id.length) {
            Export.deleteTemplate(id);
        }
    });

    /**
     * Toggles the hiding and showing of each plugin's options
     * according to the currently selected plugin from the dropdown list
     */
    $('#plugins').on('change', function () {
        $('#format_specific_opts').find('div.format_specific_options').addClass('d-none');
        var selectedPluginName = $('#plugins').find('option:selected').val();
        $('#' + selectedPluginName + '_options').removeClass('d-none');
    });

    /**
     * Toggles the enabling and disabling of the SQL plugin's comment options that apply only when exporting structure
     */
    $('input[type=\'radio\'][name=\'sql_structure_or_data\']').on('change', function () {
        var commentsArePresent = $('#checkbox_sql_include_comments').prop('checked');
        var show = $('input[type=\'radio\'][name=\'sql_structure_or_data\']:checked').val();
        if (show === 'data') {
            // disable the SQL comment options
            if (commentsArePresent) {
                $('#checkbox_sql_dates').prop('disabled', true).parent().fadeTo('fast', 0.4);
            }
            $('#checkbox_sql_relation').prop('disabled', true).parent().fadeTo('fast', 0.4);
            $('#checkbox_sql_mime').prop('disabled', true).parent().fadeTo('fast', 0.4);
        } else {
            // enable the SQL comment options
            if (commentsArePresent) {
                $('#checkbox_sql_dates').prop('disabled', false).parent().fadeTo('fast', 1);
            }
            $('#checkbox_sql_relation').prop('disabled', false).parent().fadeTo('fast', 1);
            $('#checkbox_sql_mime').prop('disabled', false).parent().fadeTo('fast', 1);
        }

        if (show === 'structure') {
            $('#checkbox_sql_auto_increment').prop('disabled', true).parent().fadeTo('fast', 0.4);
        } else {
            $('#checkbox_sql_auto_increment').prop('disabled', false).parent().fadeTo('fast', 1);
        }
    });

    // When MS Excel is selected as the Format automatically Switch to Character Set as windows-1252
    $('#plugins').on('change', function () {
        var selectedPluginName = $('#plugins').find('option:selected').val();
        if (selectedPluginName === 'excel') {
            $('#select_charset').val('windows-1252');
        } else {
            $('#select_charset').val('utf-8');
        }
    });

    // For separate-file exports only ZIP compression is allowed
    $('input[type="checkbox"][name="as_separate_files"]').on('change', function () {
        if ($(this).is(':checked')) {
            $('#compression').val('zip');
        }
    });

    $('#compression').on('change', function () {
        if ($('option:selected').val() !== 'zip') {
            $('input[type="checkbox"][name="as_separate_files"]').prop('checked', false);
        }
    });
});

Export.setupTableStructureOrData = function () {
    if ($('input[name=\'export_type\']').val() !== 'database') {
        return;
    }
    var pluginName = $('#plugins').find('option:selected').val();
    var formElemName = pluginName + '_structure_or_data';
    var forceStructureOrData = !($('input[name=\'' + formElemName + '_default\']').length);

    if (forceStructureOrData === true) {
        $('input[name="structure_or_data_forced"]').val(1);
        $('.export_structure input[type="checkbox"], .export_data input[type="checkbox"]')
            .prop('disabled', true);
        $('.export_structure, .export_data').fadeTo('fast', 0.4);
    } else {
        $('input[name="structure_or_data_forced"]').val(0);
        $('.export_structure input[type="checkbox"], .export_data input[type="checkbox"]')
            .prop('disabled', false);
        $('.export_structure, .export_data').fadeTo('fast', 1);

        var structureOrData = $('input[name="' + formElemName + '_default"]').val();

        if (structureOrData === 'structure') {
            $('.export_data input[type="checkbox"]')
                .prop('checked', false);
        } else if (structureOrData === 'data') {
            $('.export_structure input[type="checkbox"]')
                .prop('checked', false);
        }
        if (structureOrData === 'structure' || structureOrData === 'structure_and_data') {
            if (!$('.export_structure input[type="checkbox"]:checked').length) {
                $('input[name="table_select[]"]:checked')
                    .closest('tr')
                    .find('.export_structure input[type="checkbox"]')
                    .prop('checked', true);
            }
        }
        if (structureOrData === 'data' || structureOrData === 'structure_and_data') {
            if (!$('.export_data input[type="checkbox"]:checked').length) {
                $('input[name="table_select[]"]:checked')
                    .closest('tr')
                    .find('.export_data input[type="checkbox"]')
                    .prop('checked', true);
            }
        }

        Export.checkSelectedTables();
        Export.checkTableSelectAll();
        Export.checkTableSelectStructureOrData();
    }
};

/**
 * Toggles the hiding and showing of plugin structure-specific and data-specific
 * options
 */
Export.toggleStructureDataOpts = function () {
    var pluginName = $('select#plugins').val();
    var radioFormName = pluginName + '_structure_or_data';
    var dataDiv = '#' + pluginName + '_data';
    var structureDiv = '#' + pluginName + '_structure';
    var show = $('input[type=\'radio\'][name=\'' + radioFormName + '\']:checked').val();
    // Show the #rows if 'show' is not structure
    $('#rows').toggle(show !== 'structure');
    if (show === 'data') {
        $(dataDiv).slideDown('slow');
        $(structureDiv).slideUp('slow');
    } else {
        $(structureDiv).slideDown('slow');
        if (show === 'structure') {
            $(dataDiv).slideUp('slow');
        } else {
            $(dataDiv).slideDown('slow');
        }
    }
};

/**
 * Toggles the disabling of the "save to file" options
 */
Export.toggleSaveToFile = function () {
    var $ulSaveAsfile = $('#ul_save_asfile');
    if (!$('#radio_dump_asfile').prop('checked')) {
        $ulSaveAsfile.find('> li').fadeTo('fast', 0.4);
        $ulSaveAsfile.find('> li > input').prop('disabled', true);
        $ulSaveAsfile.find('> li > select').prop('disabled', true);
    } else {
        $ulSaveAsfile.find('> li').fadeTo('fast', 1);
        $ulSaveAsfile.find('> li > input').prop('disabled', false);
        $ulSaveAsfile.find('> li > select').prop('disabled', false);
    }
};

AJAX.registerOnload('export.js', function () {
    Export.toggleSaveToFile();
    $('input[type=\'radio\'][name=\'output_format\']').on('change', Export.toggleSaveToFile);
});

/**
 * For SQL plugin, toggles the disabling of the "display comments" options
 */
Export.toggleSqlIncludeComments = function () {
    $('#checkbox_sql_include_comments').on('change', function () {
        var $ulIncludeComments = $('#ul_include_comments');
        if (!$('#checkbox_sql_include_comments').prop('checked')) {
            $ulIncludeComments.find('> li').fadeTo('fast', 0.4);
            $ulIncludeComments.find('> li > input').prop('disabled', true);
        } else {
            // If structure is not being exported, the comment options for structure should not be enabled
            if ($('#radio_sql_structure_or_data_data').prop('checked')) {
                $('#text_sql_header_comment').prop('disabled', false).parent('li').fadeTo('fast', 1);
            } else {
                $ulIncludeComments.find('> li').fadeTo('fast', 1);
                $ulIncludeComments.find('> li > input').prop('disabled', false);
            }
        }
    });
};

Export.checkTableSelectAll = function () {
    var total = $('input[name="table_select[]"]').length;
    var strChecked = $('input[name="table_structure[]"]:checked').length;
    var dataChecked = $('input[name="table_data[]"]:checked').length;
    var strAll = $('#table_structure_all');
    var dataAll = $('#table_data_all');

    if (strChecked === total) {
        strAll
            .prop('indeterminate', false)
            .prop('checked', true);
    } else if (strChecked === 0) {
        strAll
            .prop('indeterminate', false)
            .prop('checked', false);
    } else {
        strAll
            .prop('indeterminate', true)
            .prop('checked', false);
    }

    if (dataChecked === total) {
        dataAll
            .prop('indeterminate', false)
            .prop('checked', true);
    } else if (dataChecked === 0) {
        dataAll
            .prop('indeterminate', false)
            .prop('checked', false);
    } else {
        dataAll
            .prop('indeterminate', true)
            .prop('checked', false);
    }
};

Export.checkTableSelectStructureOrData = function () {
    var dataChecked = $('input[name="table_data[]"]:checked').length;
    var autoIncrement = $('#checkbox_sql_auto_increment');

    var pluginName = $('select#plugins').val();
    var dataDiv = '#' + pluginName + '_data';

    if (dataChecked === 0) {
        $(dataDiv).slideUp('slow');
        autoIncrement.prop('disabled', true).parent().fadeTo('fast', 0.4);
    } else {
        $(dataDiv).slideDown('slow');
        autoIncrement.prop('disabled', false).parent().fadeTo('fast', 1);
    }
};

Export.toggleTableSelectAllStr = function () {
    var strAll = $('#table_structure_all').is(':checked');
    if (strAll) {
        $('input[name="table_structure[]"]').prop('checked', true);
    } else {
        $('input[name="table_structure[]"]').prop('checked', false);
    }
};

Export.toggleTableSelectAllData = function () {
    var dataAll = $('#table_data_all').is(':checked');
    if (dataAll) {
        $('input[name="table_data[]"]').prop('checked', true);
    } else {
        $('input[name="table_data[]"]').prop('checked', false);
    }
};

Export.checkSelectedTables = function () {
    $('.export_table_select tbody tr').each(function () {
        Export.checkTableSelected(this);
    });
};

Export.checkTableSelected = function (row) {
    var $row = $(row);
    var tableSelect = $row.find('input[name="table_select[]"]');
    var strCheck = $row.find('input[name="table_structure[]"]');
    var dataCheck = $row.find('input[name="table_data[]"]');

    var data = dataCheck.is(':checked:not(:disabled)');
    var structure = strCheck.is(':checked:not(:disabled)');

    if (data && structure) {
        tableSelect.prop({ checked: true, indeterminate: false });
        $row.addClass('marked');
    } else if (data || structure) {
        tableSelect.prop({ checked: true, indeterminate: true });
        $row.removeClass('marked');
    } else {
        tableSelect.prop({ checked: false, indeterminate: false });
        $row.removeClass('marked');
    }
};

Export.toggleTableSelect = function (row) {
    var $row = $(row);
    var tableSelected = $row.find('input[name="table_select[]"]').is(':checked');

    if (tableSelected) {
        $row.find('input[type="checkbox"]:not(:disabled)').prop('checked', true);
        $row.addClass('marked');
    } else {
        $row.find('input[type="checkbox"]:not(:disabled)').prop('checked', false);
        $row.removeClass('marked');
    }
};

Export.handleAddProcCheckbox = function () {
    if ($('#table_structure_all').is(':checked') === true
        && $('#table_data_all').is(':checked') === true
    ) {
        $('#checkbox_sql_procedure_function').prop('checked', true);
    } else {
        $('#checkbox_sql_procedure_function').prop('checked', false);
    }
};

AJAX.registerOnload('export.js', function () {
    /**
     * For SQL plugin, if "CREATE TABLE options" is checked/unchecked, check/uncheck each of its sub-options
     */
    var $create = $('#checkbox_sql_create_table_statements');
    var $createOptions = $('#ul_create_table_statements').find('input');
    $create.on('change', function () {
        $createOptions.prop('checked', $(this).prop('checked'));
    });
    $createOptions.on('change', function () {
        if ($createOptions.is(':checked')) {
            $create.prop('checked', true);
        }
    });

    /**
     * Disables the view output as text option if the output must be saved as a file
     */
    $('#plugins').on('change', function () {
        var activePlugin = $('#plugins').find('option:selected').val();
        var forceFile = $('#force_file_' + activePlugin).val();
        if (forceFile === 'true') {
            if ($('#radio_dump_asfile').prop('checked') !== true) {
                $('#radio_dump_asfile').prop('checked', true);
                Export.toggleSaveToFile();
            }
            $('#radio_view_as_text').prop('disabled', true).parent().fadeTo('fast', 0.4);
        } else {
            $('#radio_view_as_text').prop('disabled', false).parent().fadeTo('fast', 1);
        }
    });

    $('input[type=\'radio\'][name$=\'_structure_or_data\']').on('change', function () {
        Export.toggleStructureDataOpts();
    });

    $('input[name="table_select[]"]').on('change', function () {
        Export.toggleTableSelect($(this).closest('tr'));
        Export.checkTableSelectAll();
        Export.handleAddProcCheckbox();
        Export.checkTableSelectStructureOrData();
    });

    $('input[name="table_structure[]"]').on('change', function () {
        Export.checkTableSelected($(this).closest('tr'));
        Export.checkTableSelectAll();
        Export.handleAddProcCheckbox();
        Export.checkTableSelectStructureOrData();
    });

    $('input[name="table_data[]"]').on('change', function () {
        Export.checkTableSelected($(this).closest('tr'));
        Export.checkTableSelectAll();
        Export.handleAddProcCheckbox();
        Export.checkTableSelectStructureOrData();
    });

    $('#table_structure_all').on('change', function () {
        Export.toggleTableSelectAllStr();
        Export.checkSelectedTables();
        Export.handleAddProcCheckbox();
        Export.checkTableSelectStructureOrData();
    });

    $('#table_data_all').on('change', function () {
        Export.toggleTableSelectAllData();
        Export.checkSelectedTables();
        Export.handleAddProcCheckbox();
        Export.checkTableSelectStructureOrData();
    });

    if ($('input[name=\'export_type\']').val() === 'database') {
        // Hide structure or data radio buttons
        $('input[type=\'radio\'][name$=\'_structure_or_data\']').each(function () {
            var $this = $(this);
            var name = $this.prop('name');
            var val = $('input[name="' + name + '"]:checked').val();
            var nameDefault = name + '_default';
            if (!$('input[name="' + nameDefault + '"]').length) {
                $this
                    .after(
                        $('<input type="hidden" name="' + nameDefault + '" value="' + val + '" disabled>')
                    )
                    .after(
                        $('<input type="hidden" name="' + name + '" value="structure_and_data">')
                    );
                $this.parent().find('label').remove();
            } else {
                $this.parent().remove();
            }
        });
        $('input[type=\'radio\'][name$=\'_structure_or_data\']').remove();

        // Disable CREATE table checkbox for sql
        var createTableCheckbox = $('#checkbox_sql_create_table');
        createTableCheckbox.prop('checked', true);
        var dummyCreateTable = $('#checkbox_sql_create_table')
            .clone()
            .removeAttr('id')
            .attr('type', 'hidden');
        createTableCheckbox
            .prop('disabled', true)
            .after(dummyCreateTable)
            .parent()
            .fadeTo('fast', 0.4);

        Export.setupTableStructureOrData();
    }

    /**
     * Handle force structure_or_data
     */
    $('#plugins').on('change', Export.setupTableStructureOrData);
});

/**
 * Toggles display of options when quick and custom export are selected
 */
Export.toggleQuickOrCustom = function () {
    if ($('input[name=\'quick_or_custom\']').length === 0 // custom_no_form option
        || $('#radio_custom_export').prop('checked') // custom
    ) {
        $('#databases_and_tables').show();
        $('#rows').show();
        $('#output').show();
        $('#format_specific_opts').show();
        $('#output_quick_export').addClass('d-none');
        var selectedPluginName = $('#plugins').find('option:selected').val();
        $('#' + selectedPluginName + '_options').removeClass('d-none');
    } else { // quick
        $('#databases_and_tables').hide();
        $('#rows').hide();
        $('#output').hide();
        $('#format_specific_opts').hide();
        $('#output_quick_export').removeClass('d-none');
    }
};

var timeOut;

Export.checkTimeOut = function (timeLimit) {
    var limit = timeLimit;
    if (typeof limit === 'undefined' || limit === 0) {
        return true;
    }
    // margin of one second to avoid race condition to set/access session variable
    limit = limit + 1;
    clearTimeout(timeOut);
    timeOut = setTimeout(function () {
        $.get('index.php?route=/export/check-time-out',
            {
                'ajax_request': true,
                'server': CommonParams.get('server')
            },
            function (data) {
                if (data.message === 'timeout') {
                    Functions.ajaxShowMessage(
                        '<div class="alert alert-danger" role="alert">' +
                        Messages.strTimeOutError +
                        '</div>',
                        false
                    );
                }
            }
        );
    }, limit * 1000);
};

/**
 * Handler for Alias dialog box
 *
 * @param event object the event object
 *
 * @return {void}
 */
Export.createAliasModal = function (event) {
    event.preventDefault();
    var modal = $('#renameExportModal');
    modal.modal('show');
    modal.on('shown.bs.modal', function () {
        modal.closest('.ui-dialog').find('.ui-button').addClass('btn btn-secondary');
        var db = CommonParams.get('db');
        if (db) {
            var option = $('<option></option>');
            option.text(db);
            option.attr('value', db);
            $('#db_alias_select').append(option).val(db).trigger('change');
        } else {
            var params = {
                'ajax_request': true,
                'server': CommonParams.get('server')
            };
            $.post('index.php?route=/databases', params, function (response) {
                if (response.success === true) {
                    $.each(response.databases, function (idx, value) {
                        var option = $('<option></option>');
                        option.text(value);
                        option.attr('value', value);
                        $('#db_alias_select').append(option);
                    });
                } else {
                    Functions.ajaxShowMessage(response.error, false);
                }
            });
        }
    });
    modal.on('hidden.bs.modal', function () {
        var isEmpty = true;
        $(this).find('input[type="text"]').each(function () {
            // trim empty input fields on close
            if ($(this).val()) {
                isEmpty = false;
            } else {
                $(this).parents('tr').remove();
            }
        });
        // Toggle checkbox based on aliases
        $('input#btn_alias_config').prop('checked', !isEmpty);
    });
    $('#saveAndCloseBtn').on('click', function () {
        $('#alias_modal').parent().appendTo($('form[name="dump"]'));
    });
};

Export.aliasToggleRow = function (elm) {
    var inputs = elm.parents('tr').find('input,button');
    if (elm.val()) {
        inputs.attr('disabled', false);
    } else {
        inputs.attr('disabled', true);
    }
};

Export.aliasRow = null;

Export.addAlias = function (type, name, field, value) {
    if (value === '') {
        return;
    }

    if (Export.aliasRow === null) {
        Export.aliasRow = $('#alias_data tfoot tr');
    }
    var row = Export.aliasRow.clone();
    row.find('th').text(type);
    row.find('td').first().text(name);
    row.find('input').attr('name', field);
    row.find('input').val(value);
    row.find('.alias_remove').on('click', function () {
        $(this).parents('tr').remove();
    });

    var matching = $('#alias_data [name="' + $.escapeSelector(field) + '"]');
    if (matching.length > 0) {
        matching.parents('tr').remove();
    }

    $('#alias_data tbody').append(row);
};

AJAX.registerOnload('export.js', function () {
    $('input[type=\'radio\'][name=\'quick_or_custom\']').on('change', Export.toggleQuickOrCustom);

    $('#format_specific_opts').find('div.format_specific_options')
        .addClass('d-none')
        .find('h3')
        .remove();
    Export.toggleQuickOrCustom();
    Export.toggleStructureDataOpts();
    Export.toggleSqlIncludeComments();
    Export.checkTableSelectAll();
    Export.handleAddProcCheckbox();

    /**
     * Initially disables the "Dump some row(s)" sub-options
     */
    Export.disableDumpSomeRowsSubOptions();

    /**
     * Disables the "Dump some row(s)" sub-options when it is not selected
     */
    $('input[type=\'radio\'][name=\'allrows\']').on('change', function () {
        if ($('#radio_allrows_0').prop('checked')) {
            Export.enableDumpSomeRowsSubOptions();
        } else {
            Export.disableDumpSomeRowsSubOptions();
        }
    });

    // Open Alias Modal Dialog on click
    $('#btn_alias_config').on('click', Export.createAliasModal);
    $('.alias_remove').on('click', function () {
        $(this).parents('tr').remove();
    });
    $('#db_alias_select').on('change', function () {
        Export.aliasToggleRow($(this));
        var table = CommonParams.get('table');
        if (table) {
            var option = $('<option></option>');
            option.text(table);
            option.attr('value', table);
            $('#table_alias_select').append(option).val(table).trigger('change');
        } else {
            var database = $(this).val();
            var params = {
                'ajax_request': true,
                'server': CommonParams.get('server'),
                'db': database,
            };
            var url = 'index.php?route=/tables';
            $.post(url, params, function (response) {
                if (response.success === true) {
                    $.each(response.tables, function (idx, value) {
                        var option = $('<option></option>');
                        option.text(value);
                        option.attr('value', value);
                        $('#table_alias_select').append(option);
                    });
                } else {
                    Functions.ajaxShowMessage(response.error, false);
                }
            });
        }
    });
    $('#table_alias_select').on('change', function () {
        Export.aliasToggleRow($(this));
        var database = $('#db_alias_select').val();
        var table = $(this).val();
        var params = {
            'ajax_request': true,
            'server': CommonParams.get('server'),
            'db': database,
            'table': table,
        };
        var url = 'index.php?route=/columns';
        $.post(url, params, function (response) {
            if (response.success === true) {
                $.each(response.columns, function (idx, value) {
                    var option = $('<option></option>');
                    option.text(value);
                    option.attr('value', value);
                    $('#column_alias_select').append(option);
                });
            } else {
                Functions.ajaxShowMessage(response.error, false);
            }
        });
    });
    $('#column_alias_select').on('change', function () {
        Export.aliasToggleRow($(this));
    });
    $('#db_alias_button').on('click', function (e) {
        e.preventDefault();
        var db = $('#db_alias_select').val();
        Export.addAlias(
            Messages.strAliasDatabase,
            db,
            'aliases[' + db + '][alias]',
            $('#db_alias_name').val()
        );
        $('#db_alias_name').val('');
    });
    $('#table_alias_button').on('click', function (e) {
        e.preventDefault();
        var db = $('#db_alias_select').val();
        var table = $('#table_alias_select').val();
        Export.addAlias(
            Messages.strAliasTable,
            db + '.' + table,
            'aliases[' + db + '][tables][' + table + '][alias]',
            $('#table_alias_name').val()
        );
        $('#table_alias_name').val('');
    });
    $('#column_alias_button').on('click', function (e) {
        e.preventDefault();
        var db = $('#db_alias_select').val();
        var table = $('#table_alias_select').val();
        var column = $('#column_alias_select').val();
        Export.addAlias(
            Messages.strAliasColumn,
            db + '.' + table + '.' + column,
            'aliases[' + db + '][tables][' + table + '][colums][' + column + ']',
            $('#column_alias_name').val()
        );
        $('#column_alias_name').val('');
    });

    var setSelectOptions = function (doCheck) {
        Functions.setSelectOptions('dump', 'db_select[]', doCheck);
    };

    $('#db_select_all').on('click', function (e) {
        e.preventDefault();
        setSelectOptions(true);
    });

    $('#db_unselect_all').on('click', function (e) {
        e.preventDefault();
        setSelectOptions(false);
    });

    $('#buttonGo').on('click', function () {
        var timeLimit = parseInt($(this).attr('data-exec-time-limit'));

        // If the time limit set is zero,
        // then time out won't occur so no need to check for time out.
        if (timeLimit > 0) {
            Export.checkTimeOut(timeLimit);
        }
    });
});
