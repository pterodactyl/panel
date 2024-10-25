/**
 * @fileoverview   events handling from normalization page
 * @name            normalization
 *
 * @requires    jQuery
 */

// eslint-disable-next-line no-unused-vars
/* global centralColumnList:writable */ // js/functions.js

/**
 * AJAX scripts for normalization
 *
 */

var normalizeto = '1nf';
var primaryKey;
var dataParsed = null;

function appendHtmlColumnsList () {
    $.post(
        'index.php?route=/normalization',
        {
            'ajax_request': true,
            'db': CommonParams.get('db'),
            'table': CommonParams.get('table'),
            'server': CommonParams.get('server'),
            'getColumns': true
        },
        function (data) {
            if (data.success === true) {
                $('select[name=makeAtomic]').html(data.message);
            }
        }
    );
}

function goTo3NFStep1 (newTables) {
    var tables = newTables;
    if (Object.keys(tables).length === 1) {
        tables = [CommonParams.get('table')];
    }
    $.post(
        'index.php?route=/normalization',
        {
            'ajax_request': true,
            'db': CommonParams.get('db'),
            'server': CommonParams.get('server'),
            'tables': tables,
            'step': '3.1'
        }, function (data) {
            $('#page_content').find('h3').html(Messages.str3NFNormalization);
            $('#mainContent').find('legend').html(data.legendText);
            $('#mainContent').find('h4').html(data.headText);
            $('#mainContent').find('p').html(data.subText);
            $('#mainContent').find('#extra').html(data.extra);
            $('#extra').find('form').each(function () {
                var formId = $(this).attr('id');
                var colName = $(this).data('colname');
                $('#' + formId + ' input[value=\'' + colName + '\']').next().remove();
                $('#' + formId + ' input[value=\'' + colName + '\']').remove();
            });
            $('#mainContent').find('#newCols').html('');
            $('.tblFooters').html('');

            if (data.subText !== '') {
                $('<input>')
                    .attr({
                        type: 'button',
                        value: Messages.strDone,
                        class: 'btn btn-primary'
                    })
                    .on('click', function () {
                        processDependencies('', true);
                    })
                    .appendTo('.tblFooters');
            }
        }
    );
}

function goTo2NFStep1 () {
    $.post(
        'index.php?route=/normalization',
        {
            'ajax_request': true,
            'db': CommonParams.get('db'),
            'table': CommonParams.get('table'),
            'server': CommonParams.get('server'),
            'step': '2.1'
        }, function (data) {
            $('#page_content h3').html(Messages.str2NFNormalization);
            $('#mainContent legend').html(data.legendText);
            $('#mainContent h4').html(data.headText);
            $('#mainContent p').html(data.subText);
            $('#mainContent #extra').html(data.extra);
            $('#mainContent #newCols').html('');
            if (data.subText !== '') {
                $('<input>')
                    .attr({
                        type: 'submit',
                        value: Messages.strDone,
                        class: 'btn btn-primary'
                    })
                    .on('click', function () {
                        processDependencies(data.primary_key);
                    })
                    .appendTo('.tblFooters');
            } else {
                if (normalizeto === '3nf') {
                    $('#mainContent #newCols').html(Messages.strToNextStep);
                    setTimeout(function () {
                        goTo3NFStep1([CommonParams.get('table')]);
                    }, 3000);
                }
            }
        });
}

function goToFinish1NF () {
    if (normalizeto !== '1nf') {
        goTo2NFStep1();
        return true;
    }
    $('#mainContent legend').html(Messages.strEndStep);
    $('#mainContent h4').html(
        '<h3>' + Functions.sprintf(Messages.strFinishMsg, Functions.escapeHtml(CommonParams.get('table'))) + '</h3>'
    );
    $('#mainContent p').html('');
    $('#mainContent #extra').html('');
    $('#mainContent #newCols').html('');
    $('.tblFooters').html('');
}

// eslint-disable-next-line no-unused-vars
function goToStep4 () {
    $.post(
        'index.php?route=/normalization',
        {
            'ajax_request': true,
            'db': CommonParams.get('db'),
            'table': CommonParams.get('table'),
            'server': CommonParams.get('server'),
            'step4': true
        }, function (data) {
            $('#mainContent legend').html(data.legendText);
            $('#mainContent h4').html(data.headText);
            $('#mainContent p').html(data.subText);
            $('#mainContent #extra').html(data.extra);
            $('#mainContent #newCols').html('');
            $('.tblFooters').html('');
            for (var pk in primaryKey) {
                $('#extra input[value=\'' + Functions.escapeJsString(primaryKey[pk]) + '\']').attr('disabled','disabled');
            }
        }
    );
}

function goToStep3 () {
    $.post(
        'index.php?route=/normalization',
        {
            'ajax_request': true,
            'db': CommonParams.get('db'),
            'table': CommonParams.get('table'),
            'server': CommonParams.get('server'),
            'step3': true
        }, function (data) {
            $('#mainContent legend').html(data.legendText);
            $('#mainContent h4').html(data.headText);
            $('#mainContent p').html(data.subText);
            $('#mainContent #extra').html(data.extra);
            $('#mainContent #newCols').html('');
            $('.tblFooters').html('');
            primaryKey = JSON.parse(data.primary_key);
            for (var pk in primaryKey) {
                $('#extra input[value=\'' + Functions.escapeJsString(primaryKey[pk]) + '\']').attr('disabled','disabled');
            }
        }
    );
}

function goToStep2 (extra) {
    $.post(
        'index.php?route=/normalization',
        {
            'ajax_request': true,
            'db': CommonParams.get('db'),
            'table': CommonParams.get('table'),
            'server': CommonParams.get('server'),
            'step2': true
        }, function (data) {
            $('#mainContent legend').html(data.legendText);
            $('#mainContent h4').html(data.headText);
            $('#mainContent p').html(data.subText);
            $('#mainContent #extra,#mainContent #newCols').html('');
            $('.tblFooters').html('');
            if (data.hasPrimaryKey === '1') {
                if (extra === 'goToStep3') {
                    $('#mainContent h4').html(Messages.strPrimaryKeyAdded);
                    $('#mainContent p').html(Messages.strToNextStep);
                }
                if (extra === 'goToFinish1NF') {
                    goToFinish1NF();
                } else {
                    setTimeout(function () {
                        goToStep3();
                    }, 3000);
                }
            } else {
                // form to select columns to make primary
                $('#mainContent #extra').html(data.extra);
            }
        }
    );
}

function goTo2NFFinish (pd) {
    var tables = {};
    for (var dependson in pd) {
        tables[dependson] = $('#extra input[name="' + dependson + '"]').val();
    }
    var datastring = {
        'ajax_request': true,
        'db': CommonParams.get('db'),
        'table': CommonParams.get('table'),
        'server': CommonParams.get('server'),
        'pd': JSON.stringify(pd),
        'newTablesName':JSON.stringify(tables),
        'createNewTables2NF':1 };
    $.ajax({
        type: 'POST',
        url: 'index.php?route=/normalization',
        data: datastring,
        async:false,
        success: function (data) {
            if (data.success === true) {
                if (data.queryError === false) {
                    if (normalizeto === '3nf') {
                        $('#pma_navigation_reload').trigger('click');
                        goTo3NFStep1(tables);
                        return true;
                    }
                    $('#mainContent legend').html(data.legendText);
                    $('#mainContent h4').html(data.headText);
                    $('#mainContent p').html('');
                    $('#mainContent #extra').html('');
                    $('.tblFooters').html('');
                } else {
                    Functions.ajaxShowMessage(data.extra, false);
                }
                $('#pma_navigation_reload').trigger('click');
            } else {
                Functions.ajaxShowMessage(data.error, false);
            }
        }
    });
}

function goTo3NFFinish (newTables) {
    for (var table in newTables) {
        for (var newtbl in newTables[table]) {
            var updatedname = $('#extra input[name="' + newtbl + '"]').val();
            newTables[table][updatedname] = newTables[table][newtbl];
            if (updatedname !== newtbl) {
                delete newTables[table][newtbl];
            }
        }
    }
    var datastring = {
        'ajax_request': true,
        'db': CommonParams.get('db'),
        'server': CommonParams.get('server'),
        'newTables':JSON.stringify(newTables),
        'createNewTables3NF':1 };
    $.ajax({
        type: 'POST',
        url: 'index.php?route=/normalization',
        data: datastring,
        async:false,
        success: function (data) {
            if (data.success === true) {
                if (data.queryError === false) {
                    $('#mainContent legend').html(data.legendText);
                    $('#mainContent h4').html(data.headText);
                    $('#mainContent p').html('');
                    $('#mainContent #extra').html('');
                    $('.tblFooters').html('');
                } else {
                    Functions.ajaxShowMessage(data.extra, false);
                }
                $('#pma_navigation_reload').trigger('click');
            } else {
                Functions.ajaxShowMessage(data.error, false);
            }
        }
    });
}

var backup = '';

function goTo2NFStep2 (pd, primaryKey) {
    $('#newCols').html('');
    $('#mainContent legend').html(Messages.strStep + ' 2.2 ' + Messages.strConfirmPd);
    $('#mainContent h4').html(Messages.strSelectedPd);
    $('#mainContent p').html(Messages.strPdHintNote);
    var extra = '<div class="dependencies_box">';
    var pdFound = false;
    for (var dependson in pd) {
        if (dependson !== primaryKey) {
            pdFound = true;
            extra += '<p class="d-block m-1">' + Functions.escapeHtml(dependson) + ' -> ' + Functions.escapeHtml(pd[dependson].toString()) + '</p>';
        }
    }
    if (!pdFound) {
        extra += '<p class="d-block m-1">' + Messages.strNoPdSelected + '</p>';
        extra += '</div>';
    } else {
        extra += '</div>';
        var datastring = {
            'ajax_request': true,
            'db': CommonParams.get('db'),
            'table': CommonParams.get('table'),
            'server': CommonParams.get('server'),
            'pd': JSON.stringify(pd),
            'getNewTables2NF':1 };
        $.ajax({
            type: 'POST',
            url: 'index.php?route=/normalization',
            data: datastring,
            async:false,
            success: function (data) {
                if (data.success === true) {
                    extra += data.message;
                } else {
                    Functions.ajaxShowMessage(data.error, false);
                }
            }
        });
    }
    $('#mainContent #extra').html(extra);
    $('.tblFooters').html('<input type="button" class="btn btn-primary" value="' + Messages.strBack + '" id="backEditPd"><input type="button" class="btn btn-primary" id="goTo2NFFinish" value="' + Messages.strGo + '">');
    $('#goTo2NFFinish').on('click', function () {
        goTo2NFFinish(pd);
    });
}

function goTo3NFStep2 (pd, tablesTds) {
    $('#newCols').html('');
    $('#mainContent legend').html(Messages.strStep + ' 3.2 ' + Messages.strConfirmTd);
    $('#mainContent h4').html(Messages.strSelectedTd);
    $('#mainContent p').html(Messages.strPdHintNote);
    var extra = '<div class="dependencies_box">';
    var pdFound = false;
    for (var table in tablesTds) {
        for (var i in tablesTds[table]) {
            var dependson = tablesTds[table][i];
            if (dependson !== '' && dependson !== table) {
                pdFound = true;
                extra += '<p class="d-block m-1">' + Functions.escapeHtml(dependson) + ' -> ' + Functions.escapeHtml(pd[dependson].toString()) + '</p>';
            }
        }
    }
    if (!pdFound) {
        extra += '<p class="d-block m-1">' + Messages.strNoTdSelected + '</p>';
        extra += '</div>';
    } else {
        extra += '</div>';
        var datastring = {
            'ajax_request': true,
            'db': CommonParams.get('db'),
            'tables': JSON.stringify(tablesTds),
            'server': CommonParams.get('server'),
            'pd': JSON.stringify(pd),
            'getNewTables3NF':1 };
        $.ajax({
            type: 'POST',
            url: 'index.php?route=/normalization',
            data: datastring,
            async:false,
            success: function (data) {
                dataParsed = data;
                if (data.success === true) {
                    extra += dataParsed.html;
                } else {
                    Functions.ajaxShowMessage(data.error, false);
                }
            }
        });
    }
    $('#mainContent #extra').html(extra);
    $('.tblFooters').html('<input type="button" class="btn btn-primary" value="' + Messages.strBack + '" id="backEditPd"><input type="button" class="btn btn-primary" id="goTo3NFFinish" value="' + Messages.strGo + '">');
    $('#goTo3NFFinish').on('click', function () {
        if (!pdFound) {
            goTo3NFFinish([]);
        } else {
            goTo3NFFinish(dataParsed.newTables);
        }
    });
}
function processDependencies (primaryKey, isTransitive) {
    var pk = primaryKey;
    var pd = {};
    var tablesTds = {};
    var dependsOn;
    pd[pk] = [];
    $('#extra form').each(function () {
        var tblname;
        if (isTransitive === true) {
            tblname = $(this).data('tablename');
            pk = tblname;
            if (!(tblname in tablesTds)) {
                tablesTds[tblname] = [];
            }
            tablesTds[tblname].push(pk);
        }
        var formId = $(this).attr('id');
        $('#' + formId + ' input[type=checkbox]:not(:checked)').prop('checked', false);
        dependsOn = '';
        $('#' + formId + ' input[type=checkbox]:checked').each(function () {
            dependsOn += $(this).val() + ', ';
            $(this).attr('checked','checked');
        });
        if (dependsOn === '') {
            dependsOn = pk;
        } else {
            dependsOn = dependsOn.slice(0, -2);
        }
        if (! (dependsOn in pd)) {
            pd[dependsOn] = [];
        }
        pd[dependsOn].push($(this).data('colname'));
        if (isTransitive === true) {
            if (!(tblname in tablesTds)) {
                tablesTds[tblname] = [];
            }
            if ($.inArray(dependsOn, tablesTds[tblname]) === -1) {
                tablesTds[tblname].push(dependsOn);
            }
        }
    });
    backup = $('#mainContent').html();
    if (isTransitive === true) {
        goTo3NFStep2(pd, tablesTds);
    } else {
        goTo2NFStep2(pd, pk);
    }
    return false;
}

function moveRepeatingGroup (repeatingCols) {
    var newTable = $('input[name=repeatGroupTable]').val();
    var newColumn = $('input[name=repeatGroupColumn]').val();
    if (!newTable) {
        $('input[name=repeatGroupTable]').trigger('focus');
        return false;
    }
    if (!newColumn) {
        $('input[name=repeatGroupColumn]').trigger('focus');
        return false;
    }
    var datastring = {
        'ajax_request': true,
        'db': CommonParams.get('db'),
        'table': CommonParams.get('table'),
        'server': CommonParams.get('server'),
        'repeatingColumns': repeatingCols,
        'newTable':newTable,
        'newColumn':newColumn,
        'primary_columns':primaryKey.toString()
    };
    $.ajax({
        type: 'POST',
        url: 'index.php?route=/normalization',
        data: datastring,
        async:false,
        success: function (data) {
            if (data.success === true) {
                if (data.queryError === false) {
                    goToStep3();
                }
                Functions.ajaxShowMessage(data.message, false);
                $('#pma_navigation_reload').trigger('click');
            } else {
                Functions.ajaxShowMessage(data.error, false);
            }
        }
    });
}
AJAX.registerTeardown('normalization.js', function () {
    $('#extra').off('click', '#selectNonAtomicCol');
    $('#splitGo').off('click');
    $('.tblFooters').off('click', '#saveSplit');
    $('#extra').off('click', '#addNewPrimary');
    $('.tblFooters').off('click', '#saveNewPrimary');
    $('#extra').off('click', '#removeRedundant');
    $('#mainContent p').off('click', '#createPrimaryKey');
    $('#mainContent').off('click', '#backEditPd');
    $('#mainContent').off('click', '#showPossiblePd');
    $('#mainContent').off('click', '.pickPd');
});

AJAX.registerOnload('normalization.js', function () {
    var selectedCol;
    normalizeto = $('#mainContent').data('normalizeto');
    $('#extra').on('click', '#selectNonAtomicCol', function () {
        if ($(this).val() === 'no_such_col') {
            goToStep2();
        } else {
            selectedCol = $(this).val();
        }
    });

    $('#splitGo').on('click', function () {
        if (!selectedCol || selectedCol === '') {
            return false;
        }
        var numField = $('#numField').val();
        $.post(
            'index.php?route=/normalization',
            {
                'ajax_request': true,
                'db': CommonParams.get('db'),
                'table': CommonParams.get('table'),
                'server': CommonParams.get('server'),
                'splitColumn': true,
                'numFields': numField
            },
            function (data) {
                if (data.success === true) {
                    $('#newCols').html(data.message);
                    $('.default_value').hide();
                    $('.enum_notice').hide();

                    $('<input>')
                        .attr({
                            type: 'submit',
                            id: 'saveSplit',
                            value: Messages.strSave,
                            class: 'btn btn-primary'
                        })
                        .appendTo('.tblFooters');

                    $('<input>')
                        .attr({
                            type: 'submit',
                            id: 'cancelSplit',
                            value: Messages.strCancel,
                            class: 'btn btn-secondary'
                        })
                        .on('click', function () {
                            $('#newCols').html('');
                            $(this).parent().html('');
                        })
                        .appendTo('.tblFooters');
                }
            }
        );
        return false;
    });
    $('.tblFooters').on('click','#saveSplit', function () {
        centralColumnList = [];
        if ($('#newCols #field_0_1').val() === '') {
            $('#newCols #field_0_1').trigger('focus');
            return false;
        }
        var argsep = CommonParams.get('arg_separator');
        var datastring = $('#newCols :input').serialize();
        datastring += argsep + 'ajax_request=1' + argsep + 'do_save_data=1' + argsep + 'field_where=last';
        $.post('index.php?route=/table/add-field', datastring, function (data) {
            if (data.success) {
                $.post(
                    'index.php?route=/sql',
                    {
                        'ajax_request': true,
                        'db': CommonParams.get('db'),
                        'table': CommonParams.get('table'),
                        'server': CommonParams.get('server'),
                        'dropped_column': selectedCol,
                        'purge' : 1,
                        'sql_query': 'ALTER TABLE `' + CommonParams.get('table') + '` DROP `' + selectedCol + '`;',
                        'is_js_confirmed': 1
                    },
                    function (data) {
                        if (data.success === true) {
                            appendHtmlColumnsList();
                            $('#newCols').html('');
                            $('.tblFooters').html('');
                        } else {
                            Functions.ajaxShowMessage(data.error, false);
                        }
                        selectedCol = '';
                    }
                );
            } else {
                Functions.ajaxShowMessage(data.error, false);
            }
        });
    });

    $('#extra').on('click', '#addNewPrimary', function () {
        $.post(
            'index.php?route=/normalization',
            {
                'ajax_request': true,
                'db': CommonParams.get('db'),
                'table': CommonParams.get('table'),
                'server': CommonParams.get('server'),
                'addNewPrimary': true
            },
            function (data) {
                if (data.success === true) {
                    $('#newCols').html(data.message);
                    $('.default_value').hide();
                    $('.enum_notice').hide();

                    $('<input>')
                        .attr({
                            type: 'submit',
                            id: 'saveNewPrimary',
                            value: Messages.strSave,
                            class: 'btn btn-primary'
                        })
                        .appendTo('.tblFooters');
                    $('<input>')
                        .attr({
                            type: 'submit',
                            id: 'cancelSplit',
                            value: Messages.strCancel,
                            class: 'btn btn-secondary'
                        })
                        .on('click', function () {
                            $('#newCols').html('');
                            $(this).parent().html('');
                        })
                        .appendTo('.tblFooters');
                } else {
                    Functions.ajaxShowMessage(data.error, false);
                }
            }
        );
        return false;
    });
    $('.tblFooters').on('click', '#saveNewPrimary', function () {
        var datastring = $('#newCols :input').serialize();
        var argsep = CommonParams.get('arg_separator');
        datastring += argsep + 'field_key[0]=primary_0' + argsep + 'ajax_request=1' + argsep + 'do_save_data=1' + argsep + 'field_where=last';
        $.post('index.php?route=/table/add-field', datastring, function (data) {
            if (data.success === true) {
                $('#mainContent h4').html(Messages.strPrimaryKeyAdded);
                $('#mainContent p').html(Messages.strToNextStep);
                $('#mainContent #extra').html('');
                $('#mainContent #newCols').html('');
                $('.tblFooters').html('');
                setTimeout(function () {
                    goToStep3();
                }, 2000);
            } else {
                Functions.ajaxShowMessage(data.error, false);
            }
        });
    });
    $('#extra').on('click', '#removeRedundant', function () {
        var dropQuery = 'ALTER TABLE `' + CommonParams.get('table') + '` ';
        $('#extra input[type=checkbox]:checked').each(function () {
            dropQuery += 'DROP `' + $(this).val() + '`, ';
        });
        dropQuery = dropQuery.slice(0, -2);
        $.post(
            'index.php?route=/sql',
            {
                'ajax_request': true,
                'db': CommonParams.get('db'),
                'table': CommonParams.get('table'),
                'server': CommonParams.get('server'),
                'sql_query': dropQuery,
                'is_js_confirmed': 1
            },
            function (data) {
                if (data.success === true) {
                    goToStep2('goToFinish1NF');
                } else {
                    Functions.ajaxShowMessage(data.error, false);
                }
            }
        );
    });
    $('#extra').on('click', '#moveRepeatingGroup', function () {
        var repeatingCols = '';
        $('#extra input[type=checkbox]:checked').each(function () {
            repeatingCols += $(this).val() + ', ';
        });

        if (repeatingCols !== '') {
            var newColName = $('#extra input[type=checkbox]:checked').first().val();
            repeatingCols = repeatingCols.slice(0, -2);
            var confirmStr = Functions.sprintf(Messages.strMoveRepeatingGroup, Functions.escapeHtml(repeatingCols), Functions.escapeHtml(CommonParams.get('table')));
            confirmStr += '<input type="text" name="repeatGroupTable" placeholder="' + Messages.strNewTablePlaceholder + '">' +
                '( ' + Functions.escapeHtml(primaryKey.toString()) + ', <input type="text" name="repeatGroupColumn" placeholder="' + Messages.strNewColumnPlaceholder + '" value="' + Functions.escapeHtml(newColName) + '">)' +
                '</ol>';
            $('#newCols').html(confirmStr);

            $('<input>')
                .attr({
                    type: 'submit',
                    value: Messages.strCancel,
                    class: 'btn btn-secondary'
                })
                .on('click', function () {
                    $('#newCols').html('');
                    $('#extra input[type=checkbox]').prop('checked', false);
                })
                .appendTo('.tblFooters');
            $('<input>')
                .attr({
                    type: 'submit',
                    value: Messages.strGo,
                    class: 'btn btn-primary'
                })
                .on('click', function () {
                    moveRepeatingGroup(repeatingCols);
                })
                .appendTo('.tblFooters');
        }
    });
    $('#mainContent p').on('click', '#createPrimaryKey', function (event) {
        event.preventDefault();
        var url = {
            'create_index': 1,
            'server':  CommonParams.get('server'),
            'db': CommonParams.get('db'),
            'table': CommonParams.get('table'),
            'added_fields': 1,
            'add_fields':1,
            'index': { 'Key_name':'PRIMARY' },
            'ajax_request': true
        };
        var title = Messages.strAddPrimaryKey;
        Functions.indexEditorDialog(url, title, function () {
            // on success
            $('.sqlqueryresults').remove();
            $('.result_query').remove();
            $('.tblFooters').html('');
            goToStep2('goToStep3');
        });
        return false;
    });
    $('#mainContent').on('click', '#backEditPd', function () {
        $('#mainContent').html(backup);
    });
    $('#mainContent').on('click', '#showPossiblePd', function () {
        if ($(this).hasClass('hideList')) {
            $(this).html('+ ' + Messages.strShowPossiblePd);
            $(this).removeClass('hideList');
            $('#newCols').slideToggle('slow');
            return false;
        }
        if ($('#newCols').html() !== '') {
            $('#showPossiblePd').html('- ' + Messages.strHidePd);
            $('#showPossiblePd').addClass('hideList');
            $('#newCols').slideToggle('slow');
            return false;
        }
        $('#newCols').insertAfter('#mainContent h4');
        $('#newCols').html('<div class="text-center">' + Messages.strLoading + '<br>' + Messages.strWaitForPd + '</div>');
        $.post(
            'index.php?route=/normalization',
            {
                'ajax_request': true,
                'db': CommonParams.get('db'),
                'table': CommonParams.get('table'),
                'server': CommonParams.get('server'),
                'findPdl': true
            }, function (data) {
                $('#showPossiblePd').html('- ' + Messages.strHidePd);
                $('#showPossiblePd').addClass('hideList');
                $('#newCols').html(data.message);
            });
    });
    $('#mainContent').on('click', '.pickPd', function () {
        var strColsLeft = $(this).next('.determinants').html();
        var colsLeft = strColsLeft.split(',');
        var strColsRight = $(this).next().next().html();
        var colsRight = strColsRight.split(',');
        for (var i in colsRight) {
            $('form[data-colname="' + colsRight[i].trim() + '"] input[type="checkbox"]').prop('checked', false);
            for (var j in colsLeft) {
                $('form[data-colname="' + colsRight[i].trim() + '"] input[value="' + colsLeft[j].trim() + '"]').prop('checked', true);
            }
        }
    });
});
