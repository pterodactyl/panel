
/* global isStorageSupported */ // js/config.js
/* global ChartType, ColumnType, DataTable, JQPlotChartFactory */ // js/chart.js
/* global DatabaseStructure */ // js/database/structure.js
/* global mysqlDocBuiltin, mysqlDocKeyword */ // js/doclinks.js
/* global Indexes */ // js/indexes.js
/* global firstDayOfCalendar, maxInputVars, mysqlDocTemplate, themeImagePath */ // templates/javascript/variables.twig
/* global sprintf */ // js/vendor/sprintf.js
/* global zxcvbnts */ // js/vendor/zxcvbn-ts.js

/**
 * general function, usually for data manipulation pages
 * @test-module Functions
 */
var Functions = {};

/**
 * @var sqlBoxLocked lock for the sqlbox textarea in the querybox
 */
// eslint-disable-next-line no-unused-vars
var sqlBoxLocked = false;

/**
 * @var {array}, holds elements which content should only selected once
 */
var onlyOnceElements = [];

/**
 * @var {number} ajaxMessageCount Number of AJAX messages shown since page load
 */
var ajaxMessageCount = 0;

/**
 * @var codeMirrorEditor object containing CodeMirror editor of the query editor in SQL tab
 */
var codeMirrorEditor = false;

/**
 * @var codeMirrorInlineEditor object containing CodeMirror editor of the inline query editor
 */
var codeMirrorInlineEditor = false;

/**
 * @var {boolean} sqlAutoCompleteInProgress shows if Table/Column name autocomplete AJAX is in progress
 */
var sqlAutoCompleteInProgress = false;

/**
 * @var sqlAutoComplete object containing list of columns in each table
 */
var sqlAutoComplete = false;

/**
 * @var {string} sqlAutoCompleteDefaultTable string containing default table to autocomplete columns
 */
var sqlAutoCompleteDefaultTable = '';

/**
 * @var {array} centralColumnList array to hold the columns in central list per db.
 */
var centralColumnList = [];

/**
 * @var {array} primaryIndexes array to hold 'Primary' index columns.
 */
// eslint-disable-next-line no-unused-vars
var primaryIndexes = [];

/**
 * @var {array} uniqueIndexes array to hold 'Unique' index columns.
 */
// eslint-disable-next-line no-unused-vars
var uniqueIndexes = [];

/**
 * @var {array} indexes array to hold 'Index' columns.
 */
// eslint-disable-next-line no-unused-vars
var indexes = [];

/**
 * @var {array} fulltextIndexes array to hold 'Fulltext' columns.
 */
// eslint-disable-next-line no-unused-vars
var fulltextIndexes = [];

/**
 * @var {array} spatialIndexes array to hold 'Spatial' columns.
 */
// eslint-disable-next-line no-unused-vars
var spatialIndexes = [];

/**
 * Make sure that ajax requests will not be cached
 * by appending a random variable to their parameters
 */
$.ajaxPrefilter(function (options, originalOptions) {
    var nocache = new Date().getTime() + '' + Math.floor(Math.random() * 1000000);
    if (typeof options.data === 'string') {
        options.data += '&_nocache=' + nocache + '&token=' + encodeURIComponent(CommonParams.get('token'));
    } else if (typeof options.data === 'object') {
        options.data = $.extend(originalOptions.data, { '_nocache' : nocache, 'token': CommonParams.get('token') });
    }
});

/**
 * Adds a date/time picker to an element
 *
 * @param {object} $thisElement a jQuery object pointing to the element
 * @param {string} type
 * @param {object} options
 */
Functions.addDatepicker = function ($thisElement, type, options) {
    if (type !== 'date' && type !== 'time' && type !== 'datetime' && type !== 'timestamp') {
        return;
    }

    var showTimepicker = true;
    if (type === 'date') {
        showTimepicker = false;
    }

    // Getting the current Date and time
    var currentDateTime = new Date();

    var defaultOptions = {
        timeInput : true,
        hour: currentDateTime.getHours(),
        minute: currentDateTime.getMinutes(),
        second: currentDateTime.getSeconds(),
        showOn: 'button',
        buttonImage: themeImagePath + 'b_calendar.png',
        buttonImageOnly: true,
        stepMinutes: 1,
        stepHours: 1,
        showSecond: true,
        showMillisec: true,
        showMicrosec: true,
        showTimepicker: showTimepicker,
        showButtonPanel: false,
        changeYear: true,
        dateFormat: 'yy-mm-dd', // yy means year with four digits
        timeFormat: 'HH:mm:ss.lc',
        constrainInput: false,
        altFieldTimeOnly: false,
        showAnim: '',
        beforeShow: function (input, inst) {
            // Remember that we came from the datepicker; this is used
            // in table/change.js by verificationsAfterFieldChange()
            $thisElement.data('comes_from', 'datepicker');
            if ($(input).closest('.cEdit').length > 0) {
                setTimeout(function () {
                    inst.dpDiv.css({
                        top: 0,
                        left: 0,
                        position: 'relative'
                    });
                }, 0);
            }
            setTimeout(function () {
                // Fix wrong timepicker z-index, doesn't work without timeout
                $('#ui-timepicker-div').css('z-index', $('#ui-datepicker-div').css('z-index'));
                // Integrate tooltip text into dialog
                var tooltip = $thisElement.uiTooltip('instance');
                if (typeof tooltip !== 'undefined') {
                    tooltip.disable();
                    var $note = $('<p class="note"></div>');
                    $note.text(tooltip.option('content'));
                    $('div.ui-datepicker').append($note);
                }
            }, 0);
        },
        onSelect: function () {
            $thisElement.data('datepicker').inline = true;
        },
        onClose: function () {
            // The value is no more from the date picker
            $thisElement.data('comes_from', '');
            if (typeof $thisElement.data('datepicker') !== 'undefined') {
                $thisElement.data('datepicker').inline = false;
            }
            var tooltip = $thisElement.uiTooltip('instance');
            if (typeof tooltip !== 'undefined') {
                tooltip.enable();
            }
        }
    };
    if (type === 'time') {
        $thisElement.timepicker($.extend(defaultOptions, options));
        // Add a tip regarding entering MySQL allowed-values for TIME data-type
        Functions.tooltip($thisElement, 'input', Messages.strMysqlAllowedValuesTipTime);
    } else {
        $thisElement.datetimepicker($.extend(defaultOptions, options));
    }
};

/**
 * Add a date/time picker to each element that needs it
 * (only when jquery-ui-timepicker-addon.js is loaded)
 */
Functions.addDateTimePicker = function () {
    if ($.timepicker !== undefined) {
        $('input.timefield, input.datefield, input.datetimefield').each(function () {
            var decimals = $(this).parent().attr('data-decimals');
            var type = $(this).parent().attr('data-type');

            var showMillisec = false;
            var showMicrosec = false;
            var timeFormat = 'HH:mm:ss';
            var hourMax = 23;
            // check for decimal places of seconds
            if (decimals > 0 && type.indexOf('time') !== -1) {
                if (decimals > 3) {
                    showMillisec = true;
                    showMicrosec = true;
                    timeFormat = 'HH:mm:ss.lc';
                } else {
                    showMillisec = true;
                    timeFormat = 'HH:mm:ss.l';
                }
            }
            if (type === 'time') {
                hourMax = 99;
            }
            Functions.addDatepicker($(this), type, {
                showMillisec: showMillisec,
                showMicrosec: showMicrosec,
                timeFormat: timeFormat,
                hourMax: hourMax,
                firstDay: firstDayOfCalendar
            });
            // Add a tip regarding entering MySQL allowed-values
            // for TIME and DATE data-type
            if ($(this).hasClass('timefield')) {
                Functions.tooltip($(this), 'input', Messages.strMysqlAllowedValuesTipTime);
            } else if ($(this).hasClass('datefield')) {
                Functions.tooltip($(this), 'input', Messages.strMysqlAllowedValuesTipDate);
            }
        });
    }
};

/**
 * Handle redirect and reload flags sent as part of AJAX requests
 *
 * @param data ajax response data
 */
Functions.handleRedirectAndReload = function (data) {
    if (parseInt(data.redirect_flag) === 1) {
        // add one more GET param to display session expiry msg
        if (window.location.href.indexOf('?') === -1) {
            window.location.href += '?session_expired=1';
        } else {
            window.location.href += CommonParams.get('arg_separator') + 'session_expired=1';
        }
        window.location.reload();
    } else if (parseInt(data.reload_flag) === 1) {
        window.location.reload();
    }
};

/**
 * Creates an SQL editor which supports auto completing etc.
 *
 * @param $textarea   jQuery object wrapping the textarea to be made the editor
 * @param options     optional options for CodeMirror
 * @param {'vertical'|'horizontal'|'both'} resize optional resizing ('vertical', 'horizontal', 'both')
 * @param lintOptions additional options for lint
 *
 * @return {object|null}
 */
Functions.getSqlEditor = function ($textarea, options, resize, lintOptions) {
    var resizeType = resize;
    if ($textarea.length > 0 && typeof CodeMirror !== 'undefined') {
        // merge options for CodeMirror
        var defaults = {
            lineNumbers: true,
            matchBrackets: true,
            extraKeys: { 'Ctrl-Space': 'autocomplete' },
            hintOptions: { 'completeSingle': false, 'completeOnSingleClick': true },
            indentUnit: 4,
            mode: 'text/x-mysql',
            lineWrapping: true
        };

        if (CodeMirror.sqlLint) {
            $.extend(defaults, {
                gutters: ['CodeMirror-lint-markers'],
                lint: {
                    'getAnnotations': CodeMirror.sqlLint,
                    'async': true,
                    'lintOptions': lintOptions
                }
            });
        }

        $.extend(true, defaults, options);

        // create CodeMirror editor
        var codemirrorEditor = CodeMirror.fromTextArea($textarea[0], defaults);
        // allow resizing
        if (! resizeType) {
            resizeType = 'vertical';
        }
        var handles = '';
        if (resizeType === 'vertical') {
            handles = 's';
        }
        if (resizeType === 'both') {
            handles = 'all';
        }
        if (resizeType === 'horizontal') {
            handles = 'e, w';
        }
        $(codemirrorEditor.getWrapperElement())
            .css('resize', resizeType)
            .resizable({
                handles: handles,
                resize: function () {
                    codemirrorEditor.setSize($(this).width(), $(this).height());
                }
            });
        // enable autocomplete
        codemirrorEditor.on('inputRead', Functions.codeMirrorAutoCompleteOnInputRead);

        // page locking
        codemirrorEditor.on('change', function (e) {
            e.data = {
                value: 3,
                content: codemirrorEditor.isClean(),
            };
            AJAX.lockPageHandler(e);
        });

        return codemirrorEditor;
    }
    return null;
};

/**
 * Clear text selection
 */
Functions.clearSelection = function () {
    if (document.selection && document.selection.empty) {
        document.selection.empty();
    } else if (window.getSelection) {
        var sel = window.getSelection();
        if (sel.empty) {
            sel.empty();
        }
        if (sel.removeAllRanges) {
            sel.removeAllRanges();
        }
    }
};

/**
 * Create a jQuery UI tooltip
 *
 * @param $elements     jQuery object representing the elements
 * @param item          the item
 *                      (see https://api.jqueryui.com/tooltip/#option-items)
 * @param myContent     content of the tooltip
 * @param additionalOptions to override the default options
 *
 */
Functions.tooltip = function ($elements, item, myContent, additionalOptions) {
    if ($('#no_hint').length > 0) {
        return;
    }

    var defaultOptions = {
        content: myContent,
        items:  item,
        tooltipClass: 'tooltip',
        track: true,
        show: false,
        hide: false
    };

    $elements.uiTooltip($.extend(true, defaultOptions, additionalOptions));
};

/**
 * HTML escaping
 *
 * @param {any} unsafe
 * @return {string | false}
 */
Functions.escapeHtml = function (unsafe) {
    if (typeof(unsafe) !== 'undefined') {
        return unsafe
            .toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    } else {
        return false;
    }
};

/**
 * JavaScript escaping
 *
 * @param {any} unsafe
 * @return {string | false}
 */
Functions.escapeJsString = function (unsafe) {
    if (typeof(unsafe) !== 'undefined') {
        return unsafe
            .toString()
            .replace('\x00', '')
            .replace('\\', '\\\\')
            .replace('\'', '\\\'')
            .replace('&#039;', '\\&#039;')
            .replace('"', '\\"')
            .replace('&quot;', '\\&quot;')
            .replace('\n', '\n')
            .replace('\r', '\r')
            .replace(/<\/script/gi, '</\' + \'script');
    } else {
        return false;
    }
};

/**
 * @param {string} s
 * @return {string}
 */
Functions.escapeBacktick = function (s) {
    return s.replaceAll('`', '``');
};

/**
 * @param {string} s
 * @return {string}
 */
Functions.escapeSingleQuote = function (s) {
    return s.replaceAll('\\', '\\\\').replaceAll('\'', '\\\'');
};

Functions.sprintf = function () {
    return sprintf.apply(this, arguments);
};

/**
 * Hides/shows the default value input field, depending on the default type
 * Ticks the NULL checkbox if NULL is chosen as default value.
 *
 * @param {JQuery<HTMLElement>} $defaultType
 */
Functions.hideShowDefaultValue = function ($defaultType) {
    if ($defaultType.val() === 'USER_DEFINED') {
        $defaultType.siblings('.default_value').show().trigger('focus');
    } else {
        $defaultType.siblings('.default_value').hide();
        if ($defaultType.val() === 'NULL') {
            var $nullCheckbox = $defaultType.closest('tr').find('.allow_null');
            $nullCheckbox.prop('checked', true);
        }
    }
};

/**
 * Hides/shows the input field for column expression based on whether
 * VIRTUAL/PERSISTENT is selected
 *
 * @param $virtuality virtuality dropdown
 */
Functions.hideShowExpression = function ($virtuality) {
    if ($virtuality.val() === '') {
        $virtuality.siblings('.expression').hide();
    } else {
        $virtuality.siblings('.expression').show();
    }
};

/**
 * Show notices for ENUM columns; add/hide the default value
 *
 */
Functions.verifyColumnsProperties = function () {
    $('select.column_type').each(function () {
        Functions.showNoticeForEnum($(this));
        Functions.showWarningForIntTypes();
    });
    $('select.default_type').each(function () {
        Functions.hideShowDefaultValue($(this));
    });
    $('select.virtuality').each(function () {
        Functions.hideShowExpression($(this));
    });
};

/**
 * Add a hidden field to the form to indicate that this will be an
 * Ajax request (only if this hidden field does not exist)
 *
 * @param {object} $form the form
 */
Functions.prepareForAjaxRequest = function ($form) {
    if (! $form.find('input:hidden').is('#ajax_request_hidden')) {
        $form.append('<input type="hidden" id="ajax_request_hidden" name="ajax_request" value="true">');
    }
};

Functions.checkPasswordStrength = function (value, meterObject, meterObjectLabel, username) {
    // List of words we don't want to appear in the password
    var customDict = [
        'phpmyadmin',
        'mariadb',
        'mysql',
        'php',
        'my',
        'admin',
    ];
    if (username) {
        customDict.push(username);
    }

    zxcvbnts.core.zxcvbnOptions.setOptions({ dictionary: { userInputs: customDict } });
    var zxcvbnObject = zxcvbnts.core.zxcvbn(value);
    var strength = zxcvbnObject.score;
    strength = parseInt(strength);
    meterObject.val(strength);
    switch (strength) {
    case 0: meterObjectLabel.html(Messages.strExtrWeak);
        break;
    case 1: meterObjectLabel.html(Messages.strVeryWeak);
        break;
    case 2: meterObjectLabel.html(Messages.strWeak);
        break;
    case 3: meterObjectLabel.html(Messages.strGood);
        break;
    case 4: meterObjectLabel.html(Messages.strStrong);
    }
};

/**
 * Generate a new password and copy it to the password input areas
 *
 * @param {object} passwordForm the form that holds the password fields
 *
 * @return {boolean} always true
 */
Functions.suggestPassword = function (passwordForm) {
    // restrict the password to just letters and numbers to avoid problems:
    // "editors and viewers regard the password as multiple words and
    // things like double click no longer work"
    var pwchars = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZ@!_.*/()[]-';
    var passwordlength = 16;    // do we want that to be dynamic?  no, keep it simple :)
    var passwd = passwordForm.generated_pw;
    // eslint-disable-next-line compat/compat
    var randomWords = new Int32Array(passwordlength);

    passwd.value = '';

    var i;

    // First we're going to try to use a built-in CSPRNG
    // eslint-disable-next-line compat/compat
    if (window.crypto && window.crypto.getRandomValues) {
        // eslint-disable-next-line compat/compat
        window.crypto.getRandomValues(randomWords);
    } else if (window.msCrypto && window.msCrypto.getRandomValues) {
        // Because of course IE calls it msCrypto instead of being standard
        window.msCrypto.getRandomValues(randomWords);
    } else {
        // Fallback to Math.random
        for (i = 0; i < passwordlength; i++) {
            randomWords[i] = Math.floor(Math.random() * pwchars.length);
        }
    }

    for (i = 0; i < passwordlength; i++) {
        passwd.value += pwchars.charAt(Math.abs(randomWords[i]) % pwchars.length);
    }

    var $jQueryPasswordForm = $(passwordForm);

    passwordForm.elements.pma_pw.value = passwd.value;
    passwordForm.elements.pma_pw2.value = passwd.value;
    var meterObj = $jQueryPasswordForm.find('meter[name="pw_meter"]').first();
    var meterObjLabel = $jQueryPasswordForm.find('span[name="pw_strength"]').first();
    var username = '';
    if (passwordForm.elements.username) {
        username = passwordForm.elements.username.value;
    }
    Functions.checkPasswordStrength(passwd.value, meterObj, meterObjLabel, username);
    return true;
};

/**
 * for PhpMyAdmin\Display\ChangePassword and /user-password
 */
Functions.displayPasswordGenerateButton = function () {
    var generatePwdRow = $('<tr></tr>').addClass('align-middle');
    $('<td></td>').html(Messages.strGeneratePassword).appendTo(generatePwdRow);
    var pwdCell = $('<td></td>').appendTo(generatePwdRow);
    var pwdButton = $('<input>')
        .attr({ type: 'button', id: 'button_generate_password', value: Messages.strGenerate })
        .addClass('btn btn-secondary button')
        .on('click', function () {
            Functions.suggestPassword(this.form);
        });
    var pwdTextbox = $('<input>')
        .attr({ type: 'text', name: 'generated_pw', id: 'generated_pw' });
    pwdCell.append(pwdButton).append(pwdTextbox);

    if (document.getElementById('button_generate_password') === null) {
        $('#tr_element_before_generate_password').parent().append(generatePwdRow);
    }

    var generatePwdDiv = $('<div></div>').addClass('item');
    $('<label></label>').attr({ for: 'button_generate_password' })
        .html(Messages.strGeneratePassword + ':')
        .appendTo(generatePwdDiv);
    var optionsSpan = $('<span></span>').addClass('options')
        .appendTo(generatePwdDiv);
    pwdButton.clone(true).appendTo(optionsSpan);
    pwdTextbox.clone(true).appendTo(generatePwdDiv);

    if (document.getElementById('button_generate_password') === null) {
        $('#div_element_before_generate_password').parent().append(generatePwdDiv);
    }
};

/**
 * selects the content of a given object, f.e. a textarea
 *
 * @param {object} element   element of which the content will be selected
 * @param {any | true} lock  variable which holds the lock for this element or true, if no lock exists
 * @param {boolean} onlyOnce boolean if true this is only done once f.e. only on first focus
 */
Functions.selectContent = function (element, lock, onlyOnce) {
    if (onlyOnce && onlyOnceElements[element.name]) {
        return;
    }

    onlyOnceElements[element.name] = true;

    if (lock) {
        return;
    }

    element.select();
};

/**
 * Displays a confirmation box before submitting a "DROP/DELETE/ALTER" query.
 * This function is called while clicking links
 *
 * @param {object} theLink     the link
 * @param {object} theSqlQuery the sql query to submit
 *
 * @return {boolean} whether to run the query or not
 */
Functions.confirmLink = function (theLink, theSqlQuery) {
    // Confirmation is not required in the configuration file
    // or browser is Opera (crappy js implementation)
    if (Messages.strDoYouReally === '' || typeof(window.opera) !== 'undefined') {
        return true;
    }

    var isConfirmed = confirm(Functions.sprintf(Messages.strDoYouReally, theSqlQuery));
    if (isConfirmed) {
        if (typeof(theLink.href) !== 'undefined') {
            theLink.href += CommonParams.get('arg_separator') + 'is_js_confirmed=1';
        } else if (typeof(theLink.form) !== 'undefined') {
            theLink.form.action += '?is_js_confirmed=1';
        }
    }

    return isConfirmed;
};

/**
 * Confirms a "DROP/DELETE/ALTER" query before
 * submitting it if required.
 * This function is called by the 'Functions.checkSqlQuery()' js function.
 *
 * @param {object} theForm1  the form
 * @param {string} sqlQuery1 the sql query string
 *
 * @return {boolean} whether to run the query or not
 *
 * @see Functions.checkSqlQuery()
 */
Functions.confirmQuery = function (theForm1, sqlQuery1) {
    // Confirmation is not required in the configuration file
    if (Messages.strDoYouReally === '') {
        return true;
    }

    // Confirms a "DROP/DELETE/ALTER/TRUNCATE" statement
    //
    // TODO: find a way (if possible) to use the parser-analyser
    // for this kind of verification
    // For now, I just added a ^ to check for the statement at
    // beginning of expression

    var doConfirmRegExp0 = new RegExp('^\\s*DROP\\s+(IF EXISTS\\s+)?(TABLE|PROCEDURE)\\s', 'i');
    var doConfirmRegExp1 = new RegExp('^\\s*ALTER\\s+TABLE\\s+((`[^`]+`)|([A-Za-z0-9_$]+))\\s+DROP\\s', 'i');
    var doConfirmRegExp2 = new RegExp('^\\s*DELETE\\s+FROM\\s', 'i');
    var doConfirmRegExp3 = new RegExp('^\\s*TRUNCATE\\s', 'i');
    var doConfirmRegExp4 = new RegExp('^(?=.*UPDATE\\b)^((?!WHERE).)*$', 'i');

    if (doConfirmRegExp0.test(sqlQuery1) ||
        doConfirmRegExp1.test(sqlQuery1) ||
        doConfirmRegExp2.test(sqlQuery1) ||
        doConfirmRegExp3.test(sqlQuery1) ||
        doConfirmRegExp4.test(sqlQuery1)) {
        var message;
        if (sqlQuery1.length > 100) {
            message = sqlQuery1.substr(0, 100) + '\n    ...';
        } else {
            message = sqlQuery1;
        }
        var isConfirmed = confirm(Functions.sprintf(Messages.strDoYouReally, message));
        // statement is confirmed -> update the
        // "is_js_confirmed" form field so the confirm test won't be
        // run on the server side and allows to submit the form
        if (isConfirmed) {
            theForm1.elements.is_js_confirmed.value = 1;
            return true;
        } else {
            // statement is rejected -> do not submit the form
            window.focus();
            return false;
        } // end if (handle confirm box result)
    } // end if (display confirm box)

    return true;
};

/**
 * Displays an error message if the user submitted the sql query form with no
 * sql query, else checks for "DROP/DELETE/ALTER" statements
 *
 * @param {object} theForm the form
 *
 * @return {boolean} always false
 *
 * @see Functions.confirmQuery()
 */
Functions.checkSqlQuery = function (theForm) {
    // get the textarea element containing the query
    var sqlQuery;
    if (codeMirrorEditor) {
        codeMirrorEditor.save();
        sqlQuery = codeMirrorEditor.getValue();
    } else {
        sqlQuery = theForm.elements.sql_query.value;
    }
    var spaceRegExp = new RegExp('\\s+');
    if (typeof(theForm.elements.sql_file) !== 'undefined' &&
            theForm.elements.sql_file.value.replace(spaceRegExp, '') !== '') {
        return true;
    }
    if (typeof(theForm.elements.id_bookmark) !== 'undefined' &&
            (theForm.elements.id_bookmark.value !== null || theForm.elements.id_bookmark.value !== '') &&
            theForm.elements.id_bookmark.selectedIndex !== 0) {
        return true;
    }
    var result = false;
    // Checks for "DROP/DELETE/ALTER" statements
    if (sqlQuery.replace(spaceRegExp, '') !== '') {
        result = Functions.confirmQuery(theForm, sqlQuery);
    } else {
        alert(Messages.strFormEmpty);
    }

    if (codeMirrorEditor) {
        codeMirrorEditor.focus();
    } else if (codeMirrorInlineEditor) {
        codeMirrorInlineEditor.focus();
    }
    return result;
};

/**
 * Check if a form's element is empty.
 * An element containing only spaces is also considered empty
 *
 * @param {object} theForm      the form
 * @param {string} theFieldName the name of the form field to put the focus on
 *
 * @return {boolean} whether the form field is empty or not
 */
Functions.emptyCheckTheField = function (theForm, theFieldName) {
    var theField = theForm.elements[theFieldName];
    var spaceRegExp = new RegExp('\\s+');
    return theField.value.replace(spaceRegExp, '') === '';
};

/**
 * Ensures a value submitted in a form is numeric and is in a range
 *
 * @param {object} theForm the form
 * @param {string} theFieldName the name of the form field to check
 * @param {any} message
 * @param {number} minimum the minimum authorized value
 * @param {number} maximum the maximum authorized value
 *
 * @return {boolean}  whether a valid number has been submitted or not
 */
Functions.checkFormElementInRange = function (theForm, theFieldName, message, minimum, maximum) {
    var theField         = theForm.elements[theFieldName];
    var val              = parseInt(theField.value, 10);
    var min = 0;
    var max = Number.MAX_VALUE;

    if (typeof(minimum) !== 'undefined') {
        min = minimum;
    }
    if (typeof(maximum) !== 'undefined' && maximum !== null) {
        max = maximum;
    }

    if (isNaN(val)) {
        theField.select();
        alert(Messages.strEnterValidNumber);
        theField.focus();
        return false;
    } else if (val < min || val > max) {
        theField.select();
        alert(Functions.sprintf(message, val));
        theField.focus();
        return false;
    } else {
        theField.value = val;
    }
    return true;
};

Functions.checkTableEditForm = function (theForm, fieldsCnt) {
    // TODO: avoid sending a message if user just wants to add a line
    // on the form but has not completed at least one field name

    var atLeastOneField = 0;
    var i;
    var elm;
    var elm2;
    var elm3;
    var val;
    var id;

    for (i = 0; i < fieldsCnt; i++) {
        id = '#field_' + i + '_2';
        elm = $(id);
        val = elm.val();
        if (val === 'VARCHAR' || val === 'CHAR' || val === 'BIT' || val === 'VARBINARY' || val === 'BINARY') {
            elm2 = $('#field_' + i + '_3');
            val = parseInt(elm2.val(), 10);
            elm3 = $('#field_' + i + '_1');
            if (isNaN(val) && elm3.val() !== '') {
                elm2.select();
                alert(Messages.strEnterValidLength);
                elm2.focus();
                return false;
            }
        }

        if (atLeastOneField === 0) {
            id = 'field_' + i + '_1';
            if (!Functions.emptyCheckTheField(theForm, id)) {
                atLeastOneField = 1;
            }
        }
    }
    if (atLeastOneField === 0) {
        var theField = theForm.elements.field_0_1;
        alert(Messages.strFormEmpty);
        theField.focus();
        return false;
    }

    // at least this section is under jQuery
    var $input = $('input.textfield[name=\'table\']');
    if ($input.val() === '') {
        alert(Messages.strFormEmpty);
        $input.trigger('focus');
        return false;
    }

    return true;
};

/**
 * True if last click is to check a row.
 */
var lastClickChecked = false;

/**
 * Zero-based index of last clicked row.
 * Used to handle the shift + click event in the code above.
 */
var lastClickedRow = -1;

/**
 * Zero-based index of last shift clicked row.
 */
var lastShiftClickedRow = -1;

var idleSecondsCounter = 0;
var incInterval;
var updateTimeout;
AJAX.registerTeardown('functions.js', function () {
    clearTimeout(updateTimeout);
    clearInterval(incInterval);
    $(document).off('mousemove');
});

AJAX.registerOnload('functions.js', function () {
    document.onclick = function () {
        idleSecondsCounter = 0;
    };
    $(document).on('mousemove',function () {
        idleSecondsCounter = 0;
    });
    document.onkeypress = function () {
        idleSecondsCounter = 0;
    };
    function guid () {
        function s4 () {
            return Math.floor((1 + Math.random()) * 0x10000)
                .toString(16)
                .substring(1);
        }
        return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
            s4() + '-' + s4() + s4() + s4();
    }

    function SetIdleTime () {
        idleSecondsCounter++;
    }
    function UpdateIdleTime () {
        var href = 'index.php?route=/';
        var guid = 'default';
        if (isStorageSupported('sessionStorage')) {
            guid = window.sessionStorage.guid;
        }
        var params = {
            'ajax_request' : true,
            'server' : CommonParams.get('server'),
            'db' : CommonParams.get('db'),
            'guid': guid,
            'access_time': idleSecondsCounter,
            'check_timeout': 1
        };
        $.ajax({
            type: 'POST',
            url: href,
            data: params,
            success: function (data) {
                if (data.success) {
                    if (CommonParams.get('LoginCookieValidity') - idleSecondsCounter < 0) {
                        /* There is other active window, let's reset counter */
                        idleSecondsCounter = 0;
                    }
                    var remaining = Math.min(
                        /* Remaining login validity */
                        CommonParams.get('LoginCookieValidity') - idleSecondsCounter,
                        /* Remaining time till session GC */
                        CommonParams.get('session_gc_maxlifetime')
                    );
                    var interval = 1000;
                    if (remaining > 5) {
                        // max value for setInterval() function
                        interval = Math.min((remaining - 1) * 1000, Math.pow(2, 31) - 1);
                    }
                    updateTimeout = window.setTimeout(UpdateIdleTime, interval);
                } else { // timeout occurred
                    clearInterval(incInterval);
                    if (isStorageSupported('sessionStorage')) {
                        window.sessionStorage.clear();
                    }
                    // append the login form on the page, disable all the forms which were not disabled already, close all the open jqueryui modal boxes
                    if (!$('#modalOverlay').length) {
                        $('fieldset').not(':disabled').attr('disabled', 'disabled').addClass('disabled_for_expiration');
                        $('body').append(data.error);
                        $('.ui-dialog').each(function () {
                            $('#' + $(this).attr('aria-describedby')).dialog('close');
                        });
                        $('#input_username').trigger('focus');
                    } else {
                        CommonParams.set('token', data.new_token);
                        $('input[name=token]').val(data.new_token);
                    }
                    idleSecondsCounter = 0;
                    Functions.handleRedirectAndReload(data);
                }
            }
        });
    }
    if (CommonParams.get('logged_in')) {
        incInterval = window.setInterval(SetIdleTime, 1000);
        var sessionTimeout = Math.min(
            CommonParams.get('LoginCookieValidity'),
            CommonParams.get('session_gc_maxlifetime')
        );
        if (isStorageSupported('sessionStorage')) {
            window.sessionStorage.setItem('guid', guid());
        }
        var interval = (sessionTimeout - 5) * 1000;
        if (interval > Math.pow(2, 31) - 1) { // max value for setInterval() function
            interval = Math.pow(2, 31) - 1;
        }
        updateTimeout = window.setTimeout(UpdateIdleTime, interval);
    }
});
/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('click', 'input:checkbox.checkall');
});

AJAX.registerOnload('functions.js', function () {
    /**
     * Row marking in horizontal mode (use "on" so that it works also for
     * next pages reached via AJAX); a tr may have the class noclick to remove
     * this behavior.
     */

    $(document).on('click', 'input:checkbox.checkall', function (e) {
        var $this = $(this);
        var $tr = $this.closest('tr');
        var $table = $this.closest('table');

        if (!e.shiftKey || lastClickedRow === -1) {
            // usual click

            var $checkbox = $tr.find(':checkbox.checkall');
            var checked = $this.prop('checked');
            $checkbox.prop('checked', checked).trigger('change');
            if (checked) {
                $tr.addClass('marked table-active');
            } else {
                $tr.removeClass('marked table-active');
            }
            lastClickChecked = checked;

            // remember the last clicked row
            lastClickedRow = lastClickChecked ? $table.find('tbody tr:not(.noclick)').index($tr) : -1;
            lastShiftClickedRow = -1;
        } else {
            // handle the shift click
            Functions.clearSelection();
            var start;
            var end;

            // clear last shift click result
            if (lastShiftClickedRow >= 0) {
                if (lastShiftClickedRow >= lastClickedRow) {
                    start = lastClickedRow;
                    end = lastShiftClickedRow;
                } else {
                    start = lastShiftClickedRow;
                    end = lastClickedRow;
                }
                $tr.parent().find('tr:not(.noclick)')
                    .slice(start, end + 1)
                    .removeClass('marked table-active')
                    .find(':checkbox')
                    .prop('checked', false)
                    .trigger('change');
            }

            // handle new shift click
            var currRow = $table.find('tbody tr:not(.noclick)').index($tr);
            if (currRow >= lastClickedRow) {
                start = lastClickedRow;
                end = currRow;
            } else {
                start = currRow;
                end = lastClickedRow;
            }
            $tr.parent().find('tr:not(.noclick)')
                .slice(start, end + 1)
                .addClass('marked table-active')
                .find(':checkbox')
                .prop('checked', true)
                .trigger('change');

            // remember the last shift clicked row
            lastShiftClickedRow = currRow;
        }
    });

    Functions.addDateTimePicker();

    /**
     * Add attribute to text boxes for iOS devices (based on bugID: 3508912)
     */
    if (navigator.userAgent.match(/(iphone|ipod|ipad)/i)) {
        $('input[type=text]').attr('autocapitalize', 'off').attr('autocorrect', 'off');
    }
});

/**
  * Checks/unchecks all options of a <select> element
  *
  * @param {string} theForm   the form name
  * @param {string} theSelect the element name
  * @param {boolean} doCheck  whether to check or to uncheck options
  *
  * @return {boolean} always true
  */
Functions.setSelectOptions = function (theForm, theSelect, doCheck) {
    $('form[name=\'' + theForm + '\'] select[name=\'' + theSelect + '\']').find('option').prop('selected', doCheck);
    return true;
};

/**
 * Sets current value for query box.
 * @param {string} query
 * @return {void}
 */
Functions.setQuery = function (query) {
    if (codeMirrorEditor) {
        codeMirrorEditor.setValue(query);
        codeMirrorEditor.focus();
    } else if (document.sqlform) {
        document.sqlform.sql_query.value = query;
        document.sqlform.sql_query.focus();
    }
};

/**
 * Handles 'Simulate query' button on SQL query box.
 *
 * @return {void}
 */
Functions.handleSimulateQueryButton = function () {
    var updateRegExp = /^\s*UPDATE\b\s*(((`([^`]|``)+`)|([a-z0-9_$]+))\s*\.\s*)?((`([^`]|``)+`)|([a-z0-9_$]+))\s*\bSET\b/i;
    var deleteRegExp = /^\s*DELETE\b\s*((((`([^`]|``)+`)|([a-z0-9_$]+))\s*\.\s*)?((`([^`]|``)+`)|([a-z0-9_$]+))\s*)?\bFROM\b/i;

    var query = codeMirrorEditor ? codeMirrorEditor.getValue() : $('#sqlquery').val();

    var $simulateDml = $('#simulate_dml');
    if (updateRegExp.test(query) || deleteRegExp.test(query)) {
        if (! $simulateDml.length) {
            $('#button_submit_query')
                .before('<input type="button" id="simulate_dml"' +
                'tabindex="199" class="btn btn-primary" value="' +
                Messages.strSimulateDML +
                '">');
        }
    } else {
        if ($simulateDml.length) {
            $simulateDml.remove();
        }
    }
};

/**
  * Create quick sql statements.
  *
  * @param {'clear'|'format'|'saved'|'selectall'|'select'|'insert'|'update'|'delete'} queryType
  *
  */
Functions.insertQuery = function (queryType) {
    var table;
    if (queryType === 'clear') {
        Functions.setQuery('');
        return;
    } else if (queryType === 'format') {
        if (codeMirrorEditor) {
            $('#querymessage').html(Messages.strFormatting +
                '&nbsp;<img class="ajaxIcon" src="' +
                themeImagePath + 'ajax_clock_small.gif" alt="">');
            var params = {
                'ajax_request': true,
                'sql': codeMirrorEditor.getValue(),
                'server': CommonParams.get('server')
            };
            $.ajax({
                type: 'POST',
                url: 'index.php?route=/database/sql/format',
                data: params,
                success: function (data) {
                    if (data.success) {
                        codeMirrorEditor.setValue(data.sql);
                    }
                    $('#querymessage').html('');
                },
                error: function () {
                    $('#querymessage').html('');
                }
            });
        }
        return;
    } else if (queryType === 'saved') {
        var db = $('input[name="db"]').val();
        table = $('input[name="table"]').val();
        var key = db;
        if (table !== undefined) {
            key += '.' + table;
        }
        key = 'autoSavedSql_' + key;
        if (isStorageSupported('localStorage') &&
            typeof window.localStorage.getItem(key) === 'string') {
            Functions.setQuery(window.localStorage.getItem(key));
        } else if (Cookies.get(key)) {
            Functions.setQuery(Cookies.get(key));
        } else {
            Functions.ajaxShowMessage(Messages.strNoAutoSavedQuery);
        }
        return;
    }

    var query = '';
    var myListBox = document.sqlform.dummy;
    table = Functions.escapeBacktick(document.sqlform.table.value);

    if (myListBox.options.length > 0) {
        sqlBoxLocked = true;
        var columnsList = '';
        var valDis = '';
        var editDis = '';
        var NbSelect = 0;
        for (var i = 0; i < myListBox.options.length; i++) {
            NbSelect++;
            if (NbSelect > 1) {
                columnsList += ', ';
                valDis += ',';
                editDis += ',';
            }
            columnsList += myListBox.options[i].value;
            valDis += '\'[value-' + NbSelect + ']\'';
            editDis += myListBox.options[i].value + '=\'[value-' + NbSelect + ']\'';
        }
        if (queryType === 'selectall') {
            query = 'SELECT * FROM `' + table + '` WHERE 1';
        } else if (queryType === 'select') {
            query = 'SELECT ' + columnsList + ' FROM `' + table + '` WHERE 1';
        } else if (queryType === 'insert') {
            query = 'INSERT INTO `' + table + '`(' + columnsList + ') VALUES (' + valDis + ')';
        } else if (queryType === 'update') {
            query = 'UPDATE `' + table + '` SET ' + editDis + ' WHERE 1';
        } else if (queryType === 'delete') {
            query = 'DELETE FROM `' + table + '` WHERE 0';
        }
        Functions.setQuery(query);
        sqlBoxLocked = false;
    }
};

/**
  * Inserts multiple fields.
  *
  */
Functions.insertValueQuery = function () {
    var myQuery = document.sqlform.sql_query;
    var myListBox = document.sqlform.dummy;

    if (myListBox.options.length > 0) {
        sqlBoxLocked = true;
        var columnsList = '';
        var NbSelect = 0;
        for (var i = 0; i < myListBox.options.length; i++) {
            if (myListBox.options[i].selected) {
                NbSelect++;
                if (NbSelect > 1) {
                    columnsList += ', ';
                }
                columnsList += myListBox.options[i].value;
            }
        }

        /* CodeMirror support */
        if (codeMirrorEditor) {
            codeMirrorEditor.replaceSelection(columnsList);
            codeMirrorEditor.focus();
        // IE support
        } else if (document.selection) {
            myQuery.focus();
            var sel = document.selection.createRange();
            sel.text = columnsList;
        // MOZILLA/NETSCAPE support
        } else if (document.sqlform.sql_query.selectionStart || document.sqlform.sql_query.selectionStart === '0') {
            var startPos = document.sqlform.sql_query.selectionStart;
            var endPos = document.sqlform.sql_query.selectionEnd;
            var SqlString = document.sqlform.sql_query.value;

            myQuery.value = SqlString.substring(0, startPos) + columnsList + SqlString.substring(endPos, SqlString.length);
            myQuery.focus();
        } else {
            myQuery.value += columnsList;
        }

        // eslint-disable-next-line no-unused-vars
        sqlBoxLocked = false;
    }
};

/**
 * Updates the input fields for the parameters based on the query
 */
Functions.updateQueryParameters = function () {
    if ($('#parameterized').is(':checked')) {
        var query = codeMirrorEditor ? codeMirrorEditor.getValue() : $('#sqlquery').val();

        var allParameters = query.match(/:[a-zA-Z0-9_]+/g);
        var parameters = [];
        // get unique parameters
        if (allParameters) {
            $.each(allParameters, function (i, parameter) {
                if ($.inArray(parameter, parameters) === -1) {
                    parameters.push(parameter);
                }
            });
        } else {
            $('#parametersDiv').text(Messages.strNoParam);
            return;
        }

        var $temp = $('<div></div>');
        $temp.append($('#parametersDiv').children());
        $('#parametersDiv').empty();

        $.each(parameters, function (i, parameter) {
            var paramName = parameter.substring(1);
            var $param = $temp.find('#paramSpan_' + paramName);
            if (! $param.length) {
                $param = $('<span class="parameter" id="paramSpan_' + paramName + '"></span>');
                $('<label for="param_' + paramName + '"></label>').text(parameter).appendTo($param);
                $('<input type="text" name="parameters[' + parameter + ']" id="param_' + paramName + '">').appendTo($param);
            }
            $('#parametersDiv').append($param);
        });
    } else {
        $('#parametersDiv').empty();
    }
};

/**
 * Get checkbox for foreign key checks
 *
 * @return {string}
 */
Functions.getForeignKeyCheckboxLoader = function () {
    var html = '';
    html    += '<div class="mt-1 mb-2">';
    html    += '<div class="load-default-fk-check-value">';
    html    += Functions.getImage('ajax_clock_small');
    html    += '</div>';
    html    += '</div>';
    return html;
};

Functions.loadForeignKeyCheckbox = function () {
    // Load default foreign key check value
    var params = {
        'ajax_request': true,
        'server': CommonParams.get('server'),
    };
    $.get('index.php?route=/sql/get-default-fk-check-value', params, function (data) {
        var html = '<input type="hidden" name="fk_checks" value="0">' +
            '<input type="checkbox" name="fk_checks" id="fk_checks"' +
            (data.default_fk_check_value ? ' checked="checked"' : '') + '>' +
            '<label for="fk_checks">' + Messages.strForeignKeyCheck + '</label>';
        $('.load-default-fk-check-value').replaceWith(html);
    });
};

Functions.getJsConfirmCommonParam = function (elem, parameters) {
    var $elem = $(elem);
    var params = parameters;
    var sep = CommonParams.get('arg_separator');
    if (params) {
        // Strip possible leading ?
        if (params.substring(0,1) === '?') {
            params = params.substr(1);
        }
        params += sep;
    } else {
        params = '';
    }
    params += 'is_js_confirmed=1' + sep + 'ajax_request=true' + sep + 'fk_checks=' + ($elem.find('#fk_checks').is(':checked') ? 1 : 0);
    return params;
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('click', 'a.inline_edit_sql');
    $(document).off('click', 'input#sql_query_edit_save');
    $(document).off('click', 'input#sql_query_edit_discard');
    $('input.sqlbutton').off('click');
    if (codeMirrorEditor) {
        codeMirrorEditor.off('blur');
    } else {
        $(document).off('blur', '#sqlquery');
    }
    $(document).off('change', '#parameterized');
    $(document).off('click', 'input.sqlbutton');
    $('#sqlquery').off('keydown');
    $('#sql_query_edit').off('keydown');

    if (codeMirrorInlineEditor) {
        // Copy the sql query to the text area to preserve it.
        $('#sql_query_edit').text(codeMirrorInlineEditor.getValue());
        $(codeMirrorInlineEditor.getWrapperElement()).off('keydown');
        codeMirrorInlineEditor.toTextArea();
        codeMirrorInlineEditor = false;
    }
    if (codeMirrorEditor) {
        $(codeMirrorEditor.getWrapperElement()).off('keydown');
    }
});

/**
 * Jquery Coding for inline editing SQL_QUERY
 */
AJAX.registerOnload('functions.js', function () {
    // If we are coming back to the page by clicking forward button
    // of the browser, bind the code mirror to inline query editor.
    Functions.bindCodeMirrorToInlineEditor();
    $(document).on('click', 'a.inline_edit_sql', function () {
        if ($('#sql_query_edit').length) {
            // An inline query editor is already open,
            // we don't want another copy of it
            return false;
        }

        var $form = $(this).prev('form');
        var sqlQuery  = $form.find('input[name=\'sql_query\']').val().trim();
        var $innerSql = $(this).parent().prev().find('code.sql');

        var newContent = '<textarea name="sql_query_edit" id="sql_query_edit">' + Functions.escapeHtml(sqlQuery) + '</textarea>\n';
        newContent    += Functions.getForeignKeyCheckboxLoader();
        newContent    += '<input type="submit" id="sql_query_edit_save" class="btn btn-secondary button btnSave" value="' + Messages.strGo + '">\n';
        newContent    += '<input type="button" id="sql_query_edit_discard" class="btn btn-secondary button btnDiscard" value="' + Messages.strCancel + '">\n';
        var $editorArea = $('div#inline_editor');
        if ($editorArea.length === 0) {
            $editorArea = $('<div id="inline_editor_outer"></div>');
            $editorArea.insertBefore($innerSql);
        }
        $editorArea.html(newContent);
        Functions.loadForeignKeyCheckbox();
        $innerSql.hide();

        Functions.bindCodeMirrorToInlineEditor();
        return false;
    });

    $(document).on('click', 'input#sql_query_edit_save', function () {
        // hide already existing success message
        var sqlQuery;
        if (codeMirrorInlineEditor) {
            codeMirrorInlineEditor.save();
            sqlQuery = codeMirrorInlineEditor.getValue();
        } else {
            sqlQuery = $(this).parent().find('#sql_query_edit').val();
        }
        var fkCheck = $(this).parent().find('#fk_checks').is(':checked');

        var $form = $('a.inline_edit_sql').prev('form');
        var $fakeForm = $('<form>', { action: 'index.php?route=/import', method: 'post' })
            .append($form.find('input[name=server], input[name=db], input[name=table], input[name=token]').clone())
            .append($('<input>', { type: 'hidden', name: 'show_query', value: 1 }))
            .append($('<input>', { type: 'hidden', name: 'is_js_confirmed', value: 0 }))
            .append($('<input>', { type: 'hidden', name: 'sql_query', value: sqlQuery }))
            .append($('<input>', { type: 'hidden', name: 'fk_checks', value: fkCheck ? 1 : 0 }));
        if (! Functions.checkSqlQuery($fakeForm[0])) {
            return false;
        }
        $('.alert-success').hide();
        $fakeForm.appendTo($('body')).trigger('submit');
    });

    $(document).on('click', 'input#sql_query_edit_discard', function () {
        var $divEditor = $('div#inline_editor_outer');
        $divEditor.siblings('code.sql').show();
        $divEditor.remove();
    });

    $(document).on('click', 'input.sqlbutton', function (evt) {
        Functions.insertQuery(evt.target.id);
        Functions.handleSimulateQueryButton();
        return false;
    });

    $(document).on('change', '#parameterized', Functions.updateQueryParameters);

    var $inputUsername = $('#input_username');
    if ($inputUsername) {
        if ($inputUsername.val() === '') {
            $inputUsername.trigger('focus');
        } else {
            $('#input_password').trigger('focus');
        }
    }
});

/**
 * "inputRead" event handler for CodeMirror SQL query editors for autocompletion
 * @param instance
 */
Functions.codeMirrorAutoCompleteOnInputRead = function (instance) {
    if (!sqlAutoCompleteInProgress
        && (!instance.options.hintOptions.tables || !sqlAutoComplete)) {
        if (!sqlAutoComplete) {
            // Reset after teardown
            instance.options.hintOptions.tables = false;
            instance.options.hintOptions.defaultTable = '';

            sqlAutoCompleteInProgress = true;

            var params = {
                'ajax_request': true,
                'server': CommonParams.get('server'),
                'db': CommonParams.get('db'),
                'no_debug': true
            };

            var columnHintRender = function (elem, self, data) {
                $('<div class="autocomplete-column-name">')
                    .text(data.columnName)
                    .appendTo(elem);
                $('<div class="autocomplete-column-hint">')
                    .text(data.columnHint)
                    .appendTo(elem);
            };

            $.ajax({
                type: 'POST',
                url: 'index.php?route=/database/sql/autocomplete',
                data: params,
                success: function (data) {
                    if (data.success) {
                        var tables = JSON.parse(data.tables);
                        sqlAutoCompleteDefaultTable = CommonParams.get('table');
                        sqlAutoComplete = [];
                        for (var table in tables) {
                            if (tables.hasOwnProperty(table)) {
                                var columns = tables[table];
                                table = {
                                    text: table,
                                    columns: []
                                };
                                for (var column in columns) {
                                    if (columns.hasOwnProperty(column)) {
                                        var displayText = columns[column].Type;
                                        if (columns[column].Key === 'PRI') {
                                            displayText += ' | Primary';
                                        } else if (columns[column].Key === 'UNI') {
                                            displayText += ' | Unique';
                                        }
                                        table.columns.push({
                                            text: column,
                                            displayText: column + ' | ' +  displayText,
                                            columnName: column,
                                            columnHint: displayText,
                                            render: columnHintRender
                                        });
                                    }
                                }
                            }
                            sqlAutoComplete.push(table);
                        }
                        instance.options.hintOptions.tables = sqlAutoComplete;
                        instance.options.hintOptions.defaultTable = sqlAutoCompleteDefaultTable;
                    }
                },
                complete: function () {
                    sqlAutoCompleteInProgress = false;
                }
            });
        } else {
            instance.options.hintOptions.tables = sqlAutoComplete;
            instance.options.hintOptions.defaultTable = sqlAutoCompleteDefaultTable;
        }
    }
    if (instance.state.completionActive) {
        return;
    }
    var cur = instance.getCursor();
    var token = instance.getTokenAt(cur);
    var string = '';
    if (token.string.match(/^[.`\w@]\w*$/)) {
        string = token.string;
    }
    if (string.length > 0) {
        CodeMirror.commands.autocomplete(instance);
    }
};

/**
 * Remove autocomplete information before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    sqlAutoComplete = false;
    sqlAutoCompleteDefaultTable = '';
});

/**
 * Binds the CodeMirror to the text area used to inline edit a query.
 */
Functions.bindCodeMirrorToInlineEditor = function () {
    var $inlineEditor = $('#sql_query_edit');
    if ($inlineEditor.length > 0) {
        if (typeof CodeMirror !== 'undefined') {
            var height = $inlineEditor.css('height');
            codeMirrorInlineEditor = Functions.getSqlEditor($inlineEditor);
            codeMirrorInlineEditor.getWrapperElement().style.height = height;
            codeMirrorInlineEditor.refresh();
            codeMirrorInlineEditor.focus();
            $(codeMirrorInlineEditor.getWrapperElement())
                .on('keydown', Functions.catchKeypressesFromSqlInlineEdit);
        } else {
            $inlineEditor
                .trigger('focus')
                .on('keydown', Functions.catchKeypressesFromSqlInlineEdit);
        }
    }
};

Functions.catchKeypressesFromSqlInlineEdit = function (event) {
    // ctrl-enter is 10 in chrome and ie, but 13 in ff
    if ((event.ctrlKey || event.metaKey) && (event.keyCode === 13 || event.keyCode === 10)) {
        $('#sql_query_edit_save').trigger('click');
    }
};

/**
 * Adds doc link to single highlighted SQL element
 *
 * @param $elm
 * @param params
 */
Functions.documentationAdd = function ($elm, params) {
    if (typeof mysqlDocTemplate === 'undefined') {
        return;
    }

    var url = Functions.sprintf(
        decodeURIComponent(mysqlDocTemplate),
        params[0]
    );
    if (params.length > 1) {
        // The # needs to be escaped to be part of the destination URL
        url += encodeURIComponent('#') + params[1];
    }
    var content = $elm.text();
    $elm.text('');
    $elm.append('<a target="mysql_doc" class="cm-sql-doc" href="' + url + '">' + content + '</a>');
};

/**
 * Generates doc links for keywords inside highlighted SQL
 *
 * @param idx
 * @param elm
 */
Functions.documentationKeyword = function (idx, elm) {
    var $elm = $(elm);
    /* Skip already processed ones */
    if ($elm.find('a').length > 0) {
        return;
    }
    var keyword = $elm.text().toUpperCase();
    var $next = $elm.next('.cm-keyword');
    if ($next) {
        var nextKeyword = $next.text().toUpperCase();
        var full = keyword + ' ' + nextKeyword;

        var $next2 = $next.next('.cm-keyword');
        if ($next2) {
            var next2Keyword = $next2.text().toUpperCase();
            var full2 = full + ' ' + next2Keyword;
            if (full2 in mysqlDocKeyword) {
                Functions.documentationAdd($elm, mysqlDocKeyword[full2]);
                Functions.documentationAdd($next, mysqlDocKeyword[full2]);
                Functions.documentationAdd($next2, mysqlDocKeyword[full2]);
                return;
            }
        }
        if (full in mysqlDocKeyword) {
            Functions.documentationAdd($elm, mysqlDocKeyword[full]);
            Functions.documentationAdd($next, mysqlDocKeyword[full]);
            return;
        }
    }
    if (keyword in mysqlDocKeyword) {
        Functions.documentationAdd($elm, mysqlDocKeyword[keyword]);
    }
};

/**
 * Generates doc links for builtins inside highlighted SQL
 *
 * @param idx
 * @param elm
 */
Functions.documentationBuiltin = function (idx, elm) {
    var $elm = $(elm);
    var builtin = $elm.text().toUpperCase();
    if (builtin in mysqlDocBuiltin) {
        Functions.documentationAdd($elm, mysqlDocBuiltin[builtin]);
    }
};

/**
 * Higlights SQL using CodeMirror.
 *
 * @param $base
 */
Functions.highlightSql = function ($base) {
    var $elm = $base.find('code.sql');
    $elm.each(function () {
        var $sql = $(this);
        var $pre = $sql.find('pre');
        /* We only care about visible elements to avoid double processing */
        if ($pre.is(':visible')) {
            var $highlight = $('<div class="sql-highlight cm-s-default"></div>');
            $sql.append($highlight);
            if (typeof CodeMirror !== 'undefined') {
                CodeMirror.runMode($sql.text(), 'text/x-mysql', $highlight[0]);
                $pre.hide();
                $highlight.find('.cm-keyword').each(Functions.documentationKeyword);
                $highlight.find('.cm-builtin').each(Functions.documentationBuiltin);
            }
        }
    });
};

/**
 * Updates an element containing code.
 *
 * @param {JQuery} $base     base element which contains the raw and the
 *                           highlighted code.
 *
 * @param {string} htmlValue code in HTML format, displayed if code cannot be
 *                           highlighted
 *
 * @param {string} rawValue  raw code, used as a parameter for highlighter
 *
 * @return {boolean}        whether content was updated or not
 */
Functions.updateCode = function ($base, htmlValue, rawValue) {
    var $code = $base.find('code');
    if ($code.length === 0) {
        return false;
    }

    // Determines the type of the content and appropriate CodeMirror mode.
    var type = '';
    var mode = '';
    if  ($code.hasClass('json')) {
        type = 'json';
        mode = 'application/json';
    } else if ($code.hasClass('sql')) {
        type = 'sql';
        mode = 'text/x-mysql';
    } else if ($code.hasClass('xml')) {
        type = 'xml';
        mode = 'application/xml';
    } else {
        return false;
    }

    // Element used to display unhighlighted code.
    var $notHighlighted = $('<pre>' + htmlValue + '</pre>');

    // Tries to highlight code using CodeMirror.
    if (typeof CodeMirror !== 'undefined') {
        var $highlighted = $('<div class="' + type + '-highlight cm-s-default"></div>');
        CodeMirror.runMode(rawValue, mode, $highlighted[0]);
        $notHighlighted.hide();
        $code.html('').append($notHighlighted, $highlighted[0]);
    } else {
        $code.html('').append($notHighlighted);
    }

    return true;
};

/**
 * Show a message on the top of the page for an Ajax request
 *
 * Sample usage:
 *
 * 1) var $msg = Functions.ajaxShowMessage();
 * This will show a message that reads "Loading...". Such a message will not
 * disappear automatically and cannot be dismissed by the user. To remove this
 * message either the Functions.ajaxRemoveMessage($msg) function must be called or
 * another message must be show with Functions.ajaxShowMessage() function.
 *
 * 2) var $msg = Functions.ajaxShowMessage(Messages.strProcessingRequest);
 * This is a special case. The behaviour is same as above,
 * just with a different message
 *
 * 3) var $msg = Functions.ajaxShowMessage('The operation was successful');
 * This will show a message that will disappear automatically and it can also
 * be dismissed by the user.
 *
 * 4) var $msg = Functions.ajaxShowMessage('Some error', false);
 * This will show a message that will not disappear automatically, but it
 * can be dismissed by the user after they have finished reading it.
 *
 * @param {string} message      string containing the message to be shown.
 *                              optional, defaults to 'Loading...'
 * @param {any} timeout         number of milliseconds for the message to be visible
 *                              optional, defaults to 5000. If set to 'false', the
 *                              notification will never disappear
 * @param {string} type         string to dictate the type of message shown.
 *                              optional, defaults to normal notification.
 *                              If set to 'error', the notification will show message
 *                              with red background.
 *                              If set to 'success', the notification will show with
 *                              a green background.
 * @return {JQuery<Element>}   jQuery Element that holds the message div
 *                              this object can be passed to Functions.ajaxRemoveMessage()
 *                              to remove the notification
 */
Functions.ajaxShowMessage = function (message, timeout, type) {
    var msg = message;
    var newTimeOut = timeout;
    /**
     * @var self_closing Whether the notification will automatically disappear
     */
    var selfClosing = true;
    /**
     * @var dismissable Whether the user will be able to remove
     *                  the notification by clicking on it
     */
    var dismissable = true;
    // Handle the case when a empty data.message is passed.
    // We don't want the empty message
    if (msg === '') {
        return true;
    } else if (! msg) {
        // If the message is undefined, show the default
        msg = Messages.strLoading;
        dismissable = false;
        selfClosing = false;
    } else if (msg === Messages.strProcessingRequest) {
        // This is another case where the message should not disappear
        dismissable = false;
        selfClosing = false;
    }
    // Figure out whether (or after how long) to remove the notification
    if (newTimeOut === undefined || newTimeOut === null) {
        newTimeOut = 5000;
    } else if (newTimeOut === false) {
        selfClosing = false;
    }
    // Determine type of message, add styling as required
    if (type === 'error') {
        msg = '<div class="alert alert-danger" role="alert">' + msg + '</div>';
    } else if (type === 'success') {
        msg = '<div class="alert alert-success" role="alert">' + msg + '</div>';
    }
    // Create a parent element for the AJAX messages, if necessary
    if ($('#loading_parent').length === 0) {
        $('<div id="loading_parent"></div>')
            .prependTo('#page_content');
    }
    // Update message count to create distinct message elements every time
    ajaxMessageCount++;
    // Remove all old messages, if any
    $('span.ajax_notification[id^=ajax_message_num]').remove();
    /**
     * @var $retval    a jQuery object containing the reference
     *                 to the created AJAX message
     */
    var $retval = $(
        '<span class="ajax_notification" id="ajax_message_num_' +
            ajaxMessageCount +
            '"></span>'
    )
        .hide()
        .appendTo('#loading_parent')
        .html(msg)
        .show();
    // If the notification is self-closing we should create a callback to remove it
    if (selfClosing) {
        $retval
            .delay(newTimeOut)
            .fadeOut('medium', function () {
                if ($(this).is(':data(tooltip)')) {
                    $(this).uiTooltip('destroy');
                }
                // Remove the notification
                $(this).remove();
            });
    }
    // If the notification is dismissable we need to add the relevant class to it
    // and add a tooltip so that the users know that it can be removed
    if (dismissable) {
        $retval.addClass('dismissable').css('cursor', 'pointer');
        /**
         * Add a tooltip to the notification to let the user know that they
         * can dismiss the ajax notification by clicking on it.
         */
        Functions.tooltip(
            $retval,
            'span',
            Messages.strDismiss
        );
    }
    // Hide spinner if this is not a loading message
    if (msg !== Messages.strLoading) {
        $retval.css('background-image', 'none');
    }
    Functions.highlightSql($retval);

    return $retval;
};

/**
 * Removes the message shown for an Ajax operation when it's completed
 *
 * @param {JQuery} $thisMessageBox Element that holds the notification
 *
 * @return {void}
 */
Functions.ajaxRemoveMessage = function ($thisMessageBox) {
    if ($thisMessageBox !== undefined && $thisMessageBox instanceof jQuery) {
        $thisMessageBox
            .stop(true, true)
            .fadeOut('medium');
        if ($thisMessageBox.is(':data(tooltip)')) {
            $thisMessageBox.uiTooltip('destroy');
        } else {
            $thisMessageBox.remove();
        }
    }
};

/**
 * Requests SQL for previewing before executing.
 *
 * @param {JQuery<HTMLElement>} $form Form containing query data
 *
 * @return {void}
 */
Functions.previewSql = function ($form) {
    var formUrl = $form.attr('action');
    var sep = CommonParams.get('arg_separator');
    var formData = $form.serialize() +
        sep + 'do_save_data=1' +
        sep + 'preview_sql=1' +
        sep + 'ajax_request=1';
    var $messageBox = Functions.ajaxShowMessage();
    $.ajax({
        type: 'POST',
        url: formUrl,
        data: formData,
        success: function (response) {
            Functions.ajaxRemoveMessage($messageBox);
            if (response.success) {
                $('#previewSqlModal').modal('show');
                $('#previewSqlModal').find('.modal-body').first().html(response.sql_data);
                $('#previewSqlModalLabel').first().html(Messages.strPreviewSQL);
                $('#previewSqlModal').on('shown.bs.modal', function () {
                    Functions.highlightSql($('#previewSqlModal'));
                });
            } else {
                Functions.ajaxShowMessage(response.message);
            }
        },
        error: function () {
            Functions.ajaxShowMessage(Messages.strErrorProcessingRequest);
        }
    });
};

/**
 * Callback called when submit/"OK" is clicked on sql preview/confirm modal
 *
 * @callback onSubmitCallback
 * @param {string} url The url
 */

/**
 *
 * @param {string}           sqlData  Sql query to preview
 * @param {string}           url      Url to be sent to callback
 * @param {onSubmitCallback} callback On submit callback function
 *
 * @return {void}
 */
Functions.confirmPreviewSql = function (sqlData, url, callback) {
    $('#previewSqlConfirmModal').modal('show');
    $('#previewSqlConfirmModalLabel').first().html(Messages.strPreviewSQL);
    $('#previewSqlConfirmCode').first().text(sqlData);
    $('#previewSqlConfirmModal').on('shown.bs.modal', function () {
        Functions.highlightSql($('#previewSqlConfirmModal'));
    });
    $('#previewSQLConfirmOkButton').on('click', function () {
        callback(url);
        $('#previewSqlConfirmModal').modal('hide');
    });
};

/**
 * check for reserved keyword column name
 *
 * @param {JQuery} $form Form
 *
 * @return {boolean}
 */
Functions.checkReservedWordColumns = function ($form) {
    var isConfirmed = true;
    $.ajax({
        type: 'POST',
        url: 'index.php?route=/table/structure/reserved-word-check',
        data: $form.serialize(),
        success: function (data) {
            if (typeof data.success !== 'undefined' && data.success === true) {
                isConfirmed = confirm(data.message);
            }
        },
        async:false
    });
    return isConfirmed;
};

// This event only need to be fired once after the initial page load
$(function () {
    /**
     * Allows the user to dismiss a notification
     * created with Functions.ajaxShowMessage()
     */
    var holdStarter = null;
    $(document).on('mousedown', 'span.ajax_notification.dismissable', function () {
        holdStarter = setTimeout(function () {
            holdStarter = null;
        }, 250);
    });

    $(document).on('mouseup', 'span.ajax_notification.dismissable', function (event) {
        if (holdStarter && event.which === 1) {
            clearTimeout(holdStarter);
            Functions.ajaxRemoveMessage($(this));
        }
    });
    /**
     * The below two functions hide the "Dismiss notification" tooltip when a user
     * is hovering a link or button that is inside an ajax message
     */
    $(document).on('mouseover', 'span.ajax_notification a, span.ajax_notification button, span.ajax_notification input', function () {
        if ($(this).parents('span.ajax_notification').is(':data(tooltip)')) {
            $(this).parents('span.ajax_notification').uiTooltip('disable');
        }
    });
    $(document).on('mouseout', 'span.ajax_notification a, span.ajax_notification button, span.ajax_notification input', function () {
        if ($(this).parents('span.ajax_notification').is(':data(tooltip)')) {
            $(this).parents('span.ajax_notification').uiTooltip('enable');
        }
    });

    /**
     * Copy text to clipboard
     *
     * @param {string | number | string[]} text to copy to clipboard
     *
     * @return {boolean}
     */
    Functions.copyToClipboard = function (text) {
        var $temp = $('<input>');
        $temp.css({ 'position': 'fixed', 'width': '2em', 'border': 0, 'top': 0, 'left': 0, 'padding': 0, 'background': 'transparent' });
        $('body').append($temp);
        $temp.val(text).trigger('select');
        try {
            var res = document.execCommand('copy');
            $temp.remove();
            return res;
        } catch (e) {
            $temp.remove();
            return false;
        }
    };

    $(document).on('click', 'a.copyQueryBtn', function (event) {
        event.preventDefault();

        var res = Functions.copyToClipboard($(this).attr('data-text'));
        if (res) {
            $(this).after('<span id=\'copyStatus\'> (' + Messages.strCopyQueryButtonSuccess + ')</span>');
        } else {
            $(this).after('<span id=\'copyStatus\'> (' + Messages.strCopyQueryButtonFailure + ')</span>');
        }
        setTimeout(function () {
            $('#copyStatus').remove();
        }, 2000);
    });

    $(document).on('mouseover mouseleave', '.ajax_notification a', function (event) {
        let message = Messages.strDismiss;

        if (event.type === 'mouseover') {
            message = $(this).hasClass('copyQueryBtn') ? Messages.strCopyToClipboard : Messages.strEditQuery;
        }

        Functions.tooltip(
            $('.ajax_notification'),
            'span',
            message
        );
    });

    $(document).on('mouseup', '.ajax_notification a', function (event) {
        event.stopPropagation();
    });
});

/**
 * Hides/shows the "Open in ENUM/SET editor" message, depending on the data type of the column currently selected
 *
 * @param selectElement
 */
Functions.showNoticeForEnum = function (selectElement) {
    var enumNoticeId = selectElement.attr('id').split('_')[1];
    enumNoticeId += '_' + (parseInt(selectElement.attr('id').split('_')[2], 10) + 1);
    var selectedType = selectElement.val();
    if (selectedType === 'ENUM' || selectedType === 'SET') {
        $('p#enum_notice_' + enumNoticeId).show();
    } else {
        $('p#enum_notice_' + enumNoticeId).hide();
    }
};

/**
 * Hides/shows a warning message when LENGTH is used with inappropriate integer type
 */
Functions.showWarningForIntTypes = function () {
    if ($('div#length_not_allowed').length) {
        var lengthRestrictions = $('select.column_type option').map(function () {
            return $(this).filter(':selected').attr('data-length-restricted');
        }).get();

        var restricationFound = lengthRestrictions.some(restriction => Number(restriction) === 1);

        if (restricationFound) {
            $('div#length_not_allowed').show();
        } else {
            $('div#length_not_allowed').hide();
        }
    }
};

/**
 * Creates a Profiling Chart. Used in sql.js
 * and in server/status/monitor.js
 *
 * @param target
 * @param data
 *
 * @return {object}
 */
Functions.createProfilingChart = function (target, data) {
    // create the chart
    var factory = new JQPlotChartFactory();
    var chart = factory.createChart(ChartType.PIE, target);

    // create the data table and add columns
    var dataTable = new DataTable();
    dataTable.addColumn(ColumnType.STRING, '');
    dataTable.addColumn(ColumnType.NUMBER, '');
    dataTable.setData(data);

    var windowWidth = $(window).width();
    var location = 's';
    if (windowWidth > 768) {
        location = 'se';
    }

    // draw the chart and return the chart object
    chart.draw(dataTable, {
        seriesDefaults: {
            rendererOptions: {
                showDataLabels:  true
            }
        },
        highlighter: {
            tooltipLocation: 'se',
            sizeAdjust: 0,
            tooltipAxes: 'pieref',
            formatString: '%s, %.9Ps'
        },
        legend: {
            show: true,
            location: location,
            rendererOptions: {
                numberColumns: 2
            }
        },
        // from https://web.archive.org/web/20190321233412/http://tango.freedesktop.org/Tango_Icon_Theme_Guidelines
        seriesColors: [
            '#fce94f',
            '#fcaf3e',
            '#e9b96e',
            '#8ae234',
            '#729fcf',
            '#ad7fa8',
            '#ef2929',
            '#888a85',
            '#c4a000',
            '#ce5c00',
            '#8f5902',
            '#4e9a06',
            '#204a87',
            '#5c3566',
            '#a40000',
            '#babdb6',
            '#2e3436'
        ]
    });
    return chart;
};

/**
 * Formats a profiling duration nicely (in us and ms time).
 * Used in server/status/monitor.js
 *
 * @param {number} number   Number to be formatted, should be in the range of microsecond to second
 * @param {number} accuracy Accuracy, how many numbers right to the comma should be
 * @return {string}        The formatted number
 */
Functions.prettyProfilingNum = function (number, accuracy) {
    var num = number;
    var acc = accuracy;
    if (!acc) {
        acc = 2;
    }
    acc = Math.pow(10, acc);
    if (num * 1000 < 0.1) {
        num = Math.round(acc * (num * 1000 * 1000)) / acc + 'µ';
    } else if (num < 0.1) {
        num = Math.round(acc * (num * 1000)) / acc + 'm';
    } else {
        num = Math.round(acc * num) / acc;
    }

    return num + 's';
};

/**
 * Formats a SQL Query nicely with newlines and indentation. Depends on Codemirror and MySQL Mode!
 *
 * @param {string} string Query to be formatted
 * @return {string}      The formatted query
 */
Functions.sqlPrettyPrint = function (string) {
    if (typeof CodeMirror === 'undefined') {
        return string;
    }

    var mode = CodeMirror.getMode({}, 'text/x-mysql');
    var stream = new CodeMirror.StringStream(string);
    var state = mode.startState();
    var token;
    var tokens = [];
    var output = '';
    var tabs = function (cnt) {
        var ret = '';
        for (var i = 0; i < 4 * cnt; i++) {
            ret += ' ';
        }
        return ret;
    };

    // "root-level" statements
    var statements = {
        'select': ['select', 'from', 'on', 'where', 'having', 'limit', 'order by', 'group by'],
        'update': ['update', 'set', 'where'],
        'insert into': ['insert into', 'values']
    };
    // don't put spaces before these tokens
    var spaceExceptionsBefore = { ';': true, ',': true, '.': true, '(': true };
    // don't put spaces after these tokens
    var spaceExceptionsAfter = { '.': true };

    // Populate tokens array
    while (! stream.eol()) {
        stream.start = stream.pos;
        token = mode.token(stream, state);
        if (token !== null) {
            tokens.push([token, stream.current().toLowerCase()]);
        }
    }

    var currentStatement = tokens[0][1];

    if (! statements[currentStatement]) {
        return string;
    }
    // Holds all currently opened code blocks (statement, function or generic)
    var blockStack = [];
    // If a new code block is found, newBlock contains its type for one iteration and vice versa for endBlock
    var newBlock;
    var endBlock;
    // How much to indent in the current line
    var indentLevel = 0;
    // Holds the "root-level" statements
    var statementPart;
    var lastStatementPart = statements[currentStatement][0];

    blockStack.unshift('statement');

    // Iterate through every token and format accordingly
    for (var i = 0; i < tokens.length; i++) {
        // New block => push to stack
        if (tokens[i][1] === '(') {
            if (i < tokens.length - 1 && tokens[i + 1][0] === 'statement-verb') {
                blockStack.unshift(newBlock = 'statement');
            } else if (i > 0 && tokens[i - 1][0] === 'builtin') {
                blockStack.unshift(newBlock = 'function');
            } else {
                blockStack.unshift(newBlock = 'generic');
            }
        } else {
            newBlock = null;
        }

        // Block end => pop from stack
        if (tokens[i][1] === ')') {
            endBlock = blockStack[0];
            blockStack.shift();
        } else {
            endBlock = null;
        }

        // A subquery is starting
        if (i > 0 && newBlock === 'statement') {
            indentLevel++;
            output += '\n' + tabs(indentLevel) + tokens[i][1] + ' ' + tokens[i + 1][1].toUpperCase() + '\n' + tabs(indentLevel + 1);
            currentStatement = tokens[i + 1][1];
            i++;
            continue;
        }

        // A subquery is ending
        if (endBlock === 'statement' && indentLevel > 0) {
            output += '\n' + tabs(indentLevel);
            indentLevel--;
        }

        // One less indentation for statement parts (from, where, order by, etc.) and a newline
        statementPart = statements[currentStatement].indexOf(tokens[i][1]);
        if (statementPart !== -1) {
            if (i > 0) {
                output += '\n';
            }
            output += tabs(indentLevel) + tokens[i][1].toUpperCase();
            output += '\n' + tabs(indentLevel + 1);
            lastStatementPart = tokens[i][1];
        // Normal indentation and spaces for everything else
        } else {
            if (! spaceExceptionsBefore[tokens[i][1]] &&
               ! (i > 0 && spaceExceptionsAfter[tokens[i - 1][1]]) &&
               output.charAt(output.length - 1) !== ' ') {
                output += ' ';
            }
            if (tokens[i][0] === 'keyword') {
                output += tokens[i][1].toUpperCase();
            } else {
                output += tokens[i][1];
            }
        }

        // split columns in select and 'update set' clauses, but only inside statements blocks
        if ((lastStatementPart === 'select' || lastStatementPart === 'where'  || lastStatementPart === 'set') &&
            tokens[i][1] === ',' && blockStack[0] === 'statement') {
            output += '\n' + tabs(indentLevel + 1);
        }

        // split conditions in where clauses, but only inside statements blocks
        if (lastStatementPart === 'where' &&
            (tokens[i][1] === 'and' || tokens[i][1] === 'or' || tokens[i][1] === 'xor')) {
            if (blockStack[0] === 'statement') {
                output += '\n' + tabs(indentLevel + 1);
            }
            // Todo: Also split and or blocks in newlines & indentation++
            // if (blockStack[0] === 'generic')
            //   output += ...
        }
    }
    return output;
};

/**
 * jQuery function that uses jQueryUI's dialogs to confirm with user. Does not
 * return a jQuery object yet and hence cannot be chained
 *
 * @param {string}   question
 * @param {string}   url          URL to be passed to the callbackFn to make
 *                                an Ajax call to
 * @param {Function} callbackFn   callback to execute after user clicks on OK
 * @param {Function} openCallback optional callback to run when dialog is shown
 *
 * @return {bool}
 */
Functions.confirm = function (question, url, callbackFn, openCallback) {
    var confirmState = CommonParams.get('confirm');
    if (! confirmState) {
        // user does not want to confirm
        if (typeof callbackFn === 'function') {
            callbackFn.call(this, url);
            return true;
        }
    }
    if (Messages.strDoYouReally === '') {
        return true;
    }

    /**
     * @var button_options Object that stores the options passed to jQueryUI
     *                     dialog
     */
    var buttonOptions = [
        {
            text: Messages.strOK,
            'class': 'btn btn-primary submitOK',
            click: function () {
                $(this).dialog('close');
                if (typeof callbackFn === 'function') {
                    callbackFn.call(this, url);
                }
            }
        },
        {
            text: Messages.strCancel,
            'class': 'btn btn-secondary submitCancel',
            click: function () {
                $(this).dialog('close');
            }
        }
    ];

    $('<div></div>', { 'id': 'confirm_dialog', 'title': Messages.strConfirm })
        .prepend(question)
        .dialog({
            classes: {
                'ui-dialog-titlebar-close': 'btn-close'
            },
            buttons: buttonOptions,
            close: function () {
                $(this).remove();
            },
            open: openCallback,
            modal: true
        });
};
jQuery.fn.confirm = Functions.confirm;

/**
 * jQuery function to sort a table's body after a new row has been appended to it.
 *
 * @param {string} textSelector string to select the sortKey's text
 *
 * @return {JQuery<HTMLElement>} for chaining purposes
 */
Functions.sortTable = function (textSelector) {
    return this.each(function () {
        /**
         * @var table_body  Object referring to the table's <tbody> element
         */
        var tableBody = $(this);
        /**
         * @var rows    Object referring to the collection of rows in {@link tableBody}
         */
        var rows = $(this).find('tr').get();

        // get the text of the field that we will sort by
        $.each(rows, function (index, row) {
            row.sortKey = $(row).find(textSelector).text().toLowerCase().trim();
        });

        // get the sorted order
        rows.sort(function (a, b) {
            if (a.sortKey < b.sortKey) {
                return -1;
            }
            if (a.sortKey > b.sortKey) {
                return 1;
            }
            return 0;
        });

        // pull out each row from the table and then append it according to it's order
        $.each(rows, function (index, row) {
            $(tableBody).append(row);
            row.sortKey = null;
        });
    });
};
jQuery.fn.sortTable = Functions.sortTable;

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('submit', 'form.create_table_form.ajax');
    $(document).off('click', 'form.create_table_form.ajax input[name=submit_num_fields]');
    $(document).off('keyup', 'form.create_table_form.ajax input');
    $(document).off('change', 'input[name=partition_count],input[name=subpartition_count],select[name=partition_by]');
});

/**
 * jQuery coding for 'Create Table'. Used on /database/operations,
 * /database/structure and /database/tracking (i.e., wherever
 * PhpMyAdmin\Display\CreateTable is used)
 *
 * Attach Ajax Event handlers for Create Table
 */
AJAX.registerOnload('functions.js', function () {
    /**
     * Attach event handler for submission of create table form (save)
     */
    $(document).on('submit', 'form.create_table_form.ajax', function (event) {
        event.preventDefault();

        /**
         * @var    the_form    object referring to the create table form
         */
        var $form = $(this);

        /*
         * First validate the form; if there is a problem, avoid submitting it
         *
         * Functions.checkTableEditForm() needs a pure element and not a jQuery object,
         * this is why we pass $form[0] as a parameter (the jQuery object
         * is actually an array of DOM elements)
         */

        if (Functions.checkTableEditForm($form[0], $form.find('input[name=orig_num_fields]').val())) {
            Functions.prepareForAjaxRequest($form);
            if (Functions.checkReservedWordColumns($form)) {
                Functions.ajaxShowMessage(Messages.strProcessingRequest);
                // User wants to submit the form
                $.post($form.attr('action'), $form.serialize() + CommonParams.get('arg_separator') + 'do_save_data=1', function (data) {
                    if (typeof data !== 'undefined' && data.success === true) {
                        $('#properties_message')
                            .removeClass('alert-danger')
                            .html('');
                        Functions.ajaxShowMessage(data.message);
                        // Only if the create table dialog (distinct panel) exists
                        var $createTableDialog = $('#create_table_dialog');
                        if ($createTableDialog.length > 0) {
                            $createTableDialog.dialog('close').remove();
                        }
                        $('#tableslistcontainer').before(data.formatted_sql);

                        /**
                         * @var tables_table    Object referring to the <tbody> element that holds the list of tables
                         */
                        var tablesTable = $('#tablesForm').find('tbody').not('#tbl_summary_row');
                        // this is the first table created in this db
                        if (tablesTable.length === 0) {
                            CommonActions.refreshMain(
                                CommonParams.get('opendb_url')
                            );
                        } else {
                            /**
                             * @var curr_last_row   Object referring to the last <tr> element in {@link tablesTable}
                             */
                            var currLastRow = $(tablesTable).find('tr').last();
                            /**
                             * @var curr_last_row_index_string   String containing the index of {@link currLastRow}
                             */
                            var currLastRowIndexString = $(currLastRow).find('input:checkbox').attr('id').match(/\d+/)[0];
                            /**
                             * @var curr_last_row_index Index of {@link currLastRow}
                             */
                            var currLastRowIndex = parseFloat(currLastRowIndexString);
                            /**
                             * @var new_last_row_index   Index of the new row to be appended to {@link tablesTable}
                             */
                            var newLastRowIndex = currLastRowIndex + 1;
                            /**
                             * @var new_last_row_id String containing the id of the row to be appended to {@link tablesTable}
                             */
                            var newLastRowId = 'checkbox_tbl_' + newLastRowIndex;

                            data.newTableString = data.newTableString.replace(/checkbox_tbl_/, newLastRowId);
                            // append to table
                            $(data.newTableString)
                                .appendTo(tablesTable);

                            // Sort the table
                            $(tablesTable).sortTable('th');

                            // Adjust summary row
                            DatabaseStructure.adjustTotals();
                        }

                        // Refresh navigation as a new table has been added
                        Navigation.reload();
                        // Redirect to table structure page on creation of new table
                        var argsep = CommonParams.get('arg_separator');
                        var params12 = 'ajax_request=true' + argsep + 'ajax_page_request=true';
                        var tableStructureUrl = 'index.php?route=/table/structure' + argsep + 'server=' + data.params.server +
                            argsep + 'db=' + data.params.db + argsep + 'token=' + data.params.token +
                            argsep + 'goto=' + encodeURIComponent('index.php?route=/database/structure') + argsep + 'table=' + data.params.table + '';
                        $.get(tableStructureUrl, params12, AJAX.responseHandler);
                    } else {
                        Functions.ajaxShowMessage(
                            '<div class="alert alert-danger" role="alert">' + data.error + '</div>',
                            false
                        );
                    }
                }); // end $.post()
            }
        }
    }); // end create table form (save)

    /**
     * Submits the intermediate changes in the table creation form
     * to refresh the UI accordingly
     *
     * @param actionParam
     */
    function submitChangesInCreateTableForm (actionParam) {
        /**
         * @var    the_form    object referring to the create table form
         */
        var $form = $('form.create_table_form.ajax');

        var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
        Functions.prepareForAjaxRequest($form);

        // User wants to add more fields to the table
        $.post($form.attr('action'), $form.serialize() + '&' + actionParam, function (data) {
            if (typeof data !== 'undefined' && data.success) {
                var $pageContent = $('#page_content');
                $pageContent.html(data.message);
                Functions.highlightSql($pageContent);
                Functions.verifyColumnsProperties();
                Functions.hideShowConnection($('.create_table_form select[name=tbl_storage_engine]'));
                Functions.ajaxRemoveMessage($msgbox);
            } else {
                Functions.ajaxShowMessage(data.error);
            }
        }); // end $.post()
    }

    /**
     * Attach event handler for create table form (add fields)
     */
    $(document).on('click', 'form.create_table_form.ajax input[name=submit_num_fields]', function (event) {
        event.preventDefault();
        submitChangesInCreateTableForm('submit_num_fields=1');
    }); // end create table form (add fields)

    $(document).on('keydown', 'form.create_table_form.ajax input[name=added_fields]', function (event) {
        if (event.keyCode === 13) {
            event.preventDefault();
            event.stopImmediatePropagation();
            $(this)
                .closest('form')
                .find('input[name=submit_num_fields]')
                .trigger('click');
        }
    });

    /**
     * Attach event handler to manage changes in number of partitions and subpartitions
     */
    $(document).on('change', 'input[name=partition_count],input[name=subpartition_count],select[name=partition_by]', function () {
        var $this = $(this);
        var $form = $this.parents('form');
        if ($form.is('.create_table_form.ajax')) {
            submitChangesInCreateTableForm('submit_partition_change=1');
        } else {
            $form.trigger('submit');
        }
    });

    $(document).on('change', 'input[value=AUTO_INCREMENT]', function () {
        if (this.checked) {
            var col = /\d/.exec($(this).attr('name'));
            col = col[0];
            var $selectFieldKey = $('select[name="field_key[' + col + ']"]');
            if ($selectFieldKey.val() === 'none_' + col) {
                $selectFieldKey.val('primary_' + col).trigger('change', [false]);
            }
        }
    });
    $('body')
        .off('click', 'input.preview_sql')
        .on('click', 'input.preview_sql', function () {
            var $form = $(this).closest('form');
            Functions.previewSql($form);
        });
});


/**
 * Validates the password field in a form
 *
 * @see    Messages.strPasswordEmpty
 * @see    Messages.strPasswordNotSame
 * @param {object} $theForm The form to be validated
 * @return {boolean}
 */
Functions.checkPassword = function ($theForm) {
    // Did the user select 'no password'?
    if ($theForm.find('#nopass_1').is(':checked')) {
        return true;
    } else {
        var $pred = $theForm.find('#select_pred_password');
        if ($pred.length && ($pred.val() === 'none' || $pred.val() === 'keep')) {
            return true;
        }
    }

    var $password = $theForm.find('input[name=pma_pw]');
    var $passwordRepeat = $theForm.find('input[name=pma_pw2]');
    var alertMessage = false;

    if ($password.val() === '') {
        alertMessage = Messages.strPasswordEmpty;
    } else if ($password.val() !== $passwordRepeat.val()) {
        alertMessage = Messages.strPasswordNotSame;
    }

    if (alertMessage) {
        alert(alertMessage);
        $password.val('');
        $passwordRepeat.val('');
        $password.trigger('focus');
        return false;
    }
    return true;
};

/**
 * Attach Ajax event handlers for 'Change Password' on index.php
 */
AJAX.registerOnload('functions.js', function () {
    /* Handler for hostname type */
    $(document).on('change', '#select_pred_hostname', function () {
        var hostname = $('#pma_hostname');
        if (this.value === 'any') {
            hostname.val('%');
        } else if (this.value === 'localhost') {
            hostname.val('localhost');
        } else if (this.value === 'thishost' && $(this).data('thishost')) {
            hostname.val($(this).data('thishost'));
        } else if (this.value === 'hosttable') {
            hostname.val('').prop('required', false);
        } else if (this.value === 'userdefined') {
            hostname.trigger('focus').select().prop('required', true);
        }
    });

    /* Handler for editing hostname */
    $(document).on('change', '#pma_hostname', function () {
        $('#select_pred_hostname').val('userdefined');
        $('#pma_hostname').prop('required', true);
    });

    /* Handler for username type */
    $(document).on('change', '#select_pred_username', function () {
        if (this.value === 'any') {
            $('#pma_username').val('').prop('required', false);
            $('#user_exists_warning').css('display', 'none');
        } else if (this.value === 'userdefined') {
            $('#pma_username').trigger('focus').trigger('select').prop('required', true);
        }
    });

    /* Handler for editing username */
    $(document).on('change', '#pma_username', function () {
        $('#select_pred_username').val('userdefined');
        $('#pma_username').prop('required', true);
    });

    /* Handler for password type */
    $(document).on('change', '#select_pred_password', function () {
        if (this.value === 'none') {
            $('#text_pma_pw2').prop('required', false).val('');
            $('#text_pma_pw').prop('required', false).val('');
        } else if (this.value === 'userdefined') {
            $('#text_pma_pw2').prop('required', true);
            $('#text_pma_pw').prop('required', true).trigger('focus').trigger('select');
        } else {
            $('#text_pma_pw2').prop('required', false);
            $('#text_pma_pw').prop('required', false);
        }
    });

    /* Handler for editing password */
    $(document).on('change', '#text_pma_pw,#text_pma_pw2', function () {
        $('#select_pred_password').val('userdefined');
        $('#text_pma_pw2').prop('required', true);
        $('#text_pma_pw').prop('required', true);
    });

    /**
     * Unbind all event handlers before tearing down a page
     */
    $(document).off('click', '#change_password_anchor.ajax');

    /**
     * Attach Ajax event handler on the change password anchor
     */

    $(document).on('click', '#change_password_anchor.ajax', function (event) {
        event.preventDefault();

        var $msgbox = Functions.ajaxShowMessage();

        /**
         * @var buttonOptions Object containing options to be passed to jQueryUI's dialog
         */
        var buttonOptions = {
            [Messages.strGo]: {
                text: Messages.strGo,
                'class': 'btn btn-primary',
            },
            [Messages.strCancel]: {
                text: Messages.strCancel,
                'class': 'btn btn-secondary',
            },
        };

        buttonOptions[Messages.strGo].click = function () {
            event.preventDefault();

            /**
             * @var $the_form    Object referring to the change password form
             */
            var $theForm = $('#change_password_form');

            if (! Functions.checkPassword($theForm)) {
                return false;
            }

            /**
             * @var {string} thisValue String containing the value of the submit button.
             * Need to append this for the change password form on Server Privileges
             * page to work
             */
            var thisValue = $(this).val();

            var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
            $theForm.append('<input type="hidden" name="ajax_request" value="true">');

            $.post($theForm.attr('action'), $theForm.serialize() + CommonParams.get('arg_separator') + 'change_pw=' + thisValue, function (data) {
                if (typeof data === 'undefined' || data.success !== true) {
                    Functions.ajaxShowMessage(data.error, false);
                    return;
                }

                var $pageContent = $('#page_content');
                $pageContent.prepend(data.message);
                Functions.highlightSql($pageContent);
                $('#change_password_dialog').hide().remove();
                $('#edit_user_dialog').dialog('close').remove();
                Functions.ajaxRemoveMessage($msgbox);
            }); // end $.post()
        };

        buttonOptions[Messages.strCancel].click = function () {
            $(this).dialog('close');
        };
        $.get($(this).attr('href'), { 'ajax_request': true }, function (data) {
            if (typeof data === 'undefined' || !data.success) {
                Functions.ajaxShowMessage(data.error, false);
                return;
            }

            if (data.scripts) {
                AJAX.scriptHandler.load(data.scripts);
            }

            $('<div id="change_password_dialog"></div>')
                .dialog({
                    classes: {
                        'ui-dialog-titlebar-close': 'btn-close'
                    },
                    title: Messages.strChangePassword,
                    width: 600,
                    close: function () {
                        $(this).remove();
                    },
                    buttons: buttonOptions,
                    modal: true
                })
                .append(data.message);
            // for this dialog, we remove the fieldset wrapping due to double headings
            $('fieldset#fieldset_change_password')
                .find('legend').remove().end()
                .find('table.table').unwrap().addClass('m-3')
                .find('input#text_pma_pw').trigger('focus');
            $('#fieldset_change_password_footer').hide();
            Functions.ajaxRemoveMessage($msgbox);
            Functions.displayPasswordGenerateButton();
            $('#change_password_form').on('submit', function (e) {
                e.preventDefault();
                $(this)
                    .closest('.ui-dialog')
                    .find('.ui-dialog-buttonpane .ui-button')
                    .first()
                    .trigger('click');
            });
        }); // end $.get()
    }); // end handler for change password anchor
}); // end $() for Change Password

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('change', 'select.column_type');
    $(document).off('change', 'select.default_type');
    $(document).off('change', 'select.virtuality');
    $(document).off('change', 'input.allow_null');
    $(document).off('change', '.create_table_form select[name=tbl_storage_engine]');
});
/**
 * Toggle the hiding/showing of the "Open in ENUM/SET editor" message when
 * the page loads and when the selected data type changes
 */
AJAX.registerOnload('functions.js', function () {
    // is called here for normal page loads and also when opening
    // the Create table dialog
    Functions.verifyColumnsProperties();
    //
    // needs on() to work also in the Create Table dialog
    $(document).on('change', 'select.column_type', function () {
        Functions.showNoticeForEnum($(this));
        Functions.showWarningForIntTypes();
    });
    $(document).on('change', 'select.default_type', function () {
        Functions.hideShowDefaultValue($(this));
    });
    $(document).on('change', 'select.virtuality', function () {
        Functions.hideShowExpression($(this));
    });
    $(document).on('change', 'input.allow_null', function () {
        Functions.validateDefaultValue($(this));
    });
    $(document).on('change', '.create_table_form select[name=tbl_storage_engine]', function () {
        Functions.hideShowConnection($(this));
    });
});

/**
 * If the chosen storage engine is FEDERATED show connection field. Hide otherwise
 *
 * @param $engineSelector storage engine selector
 */
Functions.hideShowConnection = function ($engineSelector) {
    var $connection = $('.create_table_form input[name=connection]');
    var $labelTh = $('.create_table_form #storage-engine-connection');
    if ($engineSelector.val() !== 'FEDERATED') {
        $connection
            .prop('disabled', true)
            .parent('td').hide();
        $labelTh.hide();
    } else {
        $connection
            .prop('disabled', false)
            .parent('td').show();
        $labelTh.show();
    }
};

/**
 * If the column does not allow NULL values, makes sure that default is not NULL
 *
 * @param $nullCheckbox
 */
Functions.validateDefaultValue = function ($nullCheckbox) {
    if (! $nullCheckbox.prop('checked')) {
        var $default = $nullCheckbox.closest('tr').find('.default_type');
        if ($default.val() === 'NULL') {
            $default.val('NONE');
        }
    }
};

/**
 * function to populate the input fields on picking a column from central list
 *
 * @param {string} inputId input id of the name field for the column to be populated
 * @param {number} offset of the selected column in central list of columns
 */
Functions.autoPopulate = function (inputId, offset) {
    var db = CommonParams.get('db');
    var table = CommonParams.get('table');
    var newInputId = inputId.substring(0, inputId.length - 1);
    $('#' + newInputId + '1').val(centralColumnList[db + '_' + table][offset].col_name);
    var colType = centralColumnList[db + '_' + table][offset].col_type.toUpperCase();
    $('#' + newInputId + '2').val(colType);
    var $input3 = $('#' + newInputId + '3');
    $input3.val(centralColumnList[db + '_' + table][offset].col_length);
    if (colType === 'ENUM' || colType === 'SET') {
        $input3.next().show();
    } else {
        $input3.next().hide();
    }
    var colDefault = centralColumnList[db + '_' + table][offset].col_default.toUpperCase();
    var $input4 = $('#' + newInputId + '4');
    if (colDefault === 'NULL' || colDefault === 'CURRENT_TIMESTAMP' || colDefault === 'CURRENT_TIMESTAMP()') {
        if (colDefault === 'CURRENT_TIMESTAMP()') {
            colDefault = 'CURRENT_TIMESTAMP';
        }
        $input4.val(colDefault);
        $input4.siblings('.default_value').hide();
    } if (colDefault === '') {
        $input4.val('NONE');
        $input4.siblings('.default_value').hide();
    } else {
        $input4.val('USER_DEFINED');
        $input4.siblings('.default_value').show();
        $input4.siblings('.default_value').val(centralColumnList[db + '_' + table][offset].col_default);
    }
    $('#' + newInputId + '5').val(centralColumnList[db + '_' + table][offset].col_collation);
    var $input6 = $('#' + newInputId + '6');
    $input6.val(centralColumnList[db + '_' + table][offset].col_attribute);
    if (centralColumnList[db + '_' + table][offset].col_extra === 'on update CURRENT_TIMESTAMP') {
        $input6.val(centralColumnList[db + '_' + table][offset].col_extra);
    }
    if (centralColumnList[db + '_' + table][offset].col_extra.toUpperCase() === 'AUTO_INCREMENT') {
        $('#' + newInputId + '9').prop('checked',true).trigger('change');
    } else {
        $('#' + newInputId + '9').prop('checked',false);
    }
    if (centralColumnList[db + '_' + table][offset].col_isNull !== '0') {
        $('#' + newInputId + '7').prop('checked',true);
    } else {
        $('#' + newInputId + '7').prop('checked',false);
    }
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('click', 'a.open_enum_editor');
    $(document).off('click', 'input.add_value');
    $(document).off('click', '#enum_editor td.drop');
    $(document).off('click', 'a.central_columns_dialog');
});

/**
 * Opens the ENUM/SET editor and controls its functions
 */
AJAX.registerOnload('functions.js', function () {
    $(document).on('click', 'a.open_enum_editor', function () {
        // Get the name of the column that is being edited
        var colname = $(this).closest('tr').find('input').first().val();
        var title;
        var i;
        // And use it to make up a title for the page
        if (colname.length < 1) {
            title = Messages.enum_newColumnVals;
        } else {
            title = Messages.enum_columnVals.replace(
                /%s/,
                '"' + Functions.escapeHtml(decodeURIComponent(colname)) + '"'
            );
        }
        // Get the values as a string
        var inputstring = $(this)
            .closest('td')
            .find('input')
            .val();
        // Escape html entities
        inputstring = $('<div></div>')
            .text(inputstring)
            .html();
        // Parse the values, escaping quotes and
        // slashes on the fly, into an array
        var values = [];
        var inString = false;
        var curr;
        var next;
        var buffer = '';
        for (i = 0; i < inputstring.length; i++) {
            curr = inputstring.charAt(i);
            next = i === inputstring.length ? '' : inputstring.charAt(i + 1);
            if (! inString && curr === '\'') {
                inString = true;
            } else if (inString && curr === '\\' && next === '\\') {
                buffer += '&#92;';
                i++;
            } else if (inString && next === '\'' && (curr === '\'' || curr === '\\')) {
                buffer += '&#39;';
                i++;
            } else if (inString && curr === '\'') {
                inString = false;
                values.push(buffer);
                buffer = '';
            } else if (inString) {
                buffer += curr;
            }
        }
        if (buffer.length > 0) {
            // The leftovers in the buffer are the last value (if any)
            values.push(buffer);
        }
        var fields = '';
        // If there are no values, maybe the user is about to make a
        // new list so we add a few for them to get started with.
        if (values.length === 0) {
            values.push('', '', '', '');
        }
        // Add the parsed values to the editor
        var dropIcon = Functions.getImage('b_drop');
        for (i = 0; i < values.length; i++) {
            fields += '<tr><td>' +
                   '<input type=\'text\' value=\'' + values[i] + '\'>' +
                   '</td><td class=\'drop\'>' +
                   dropIcon +
                   '</td></tr>';
        }
        /**
         * @var dialog HTML code for the ENUM/SET dialog
         */
        var dialog = '<div id=\'enum_editor\'>' +
                   '<fieldset class="pma-fieldset">' +
                    '<legend>' + title + '</legend>' +
                    '<p>' + Functions.getImage('s_notice') +
                    Messages.enum_hint + '</p>' +
                    '<table class="table table-borderless values">' + fields + '</table>' +
                    '</fieldset><fieldset class="pma-fieldset tblFooters">' +
                    '<table class="table table-borderless add"><tr><td>' +
                    '<div class=\'slider\'></div>' +
                    '</td><td>' +
                    '<form><div><input type=\'submit\' class=\'add_value btn btn-primary\' value=\'' +
                    Functions.sprintf(Messages.enum_addValue, 1) +
                    '\'></div></form>' +
                    '</td></tr></table>' +
                    '<input type=\'hidden\' value=\'' + // So we know which column's data is being edited
                    $(this).closest('td').find('input').attr('id') +
                    '\'>' +
                    '</fieldset>' +
                    '</div>';
        $('#enumEditorGoButton').on('click', function () {
            // When the submit button is clicked,
            // put the data back into the original form
            var valueArray = [];
            $('#enumEditorModal').find('.values input').each(function (index, elm) {
                var val = elm.value.replace(/\\/g, '\\\\').replace(/'/g, '\'\'');
                valueArray.push('\'' + val + '\'');
            });
            // get the Length/Values text field where this value belongs
            var valuesId = $('#enumEditorModal').find('input[type=\'hidden\']').val();
            $('input#' + valuesId).val(valueArray.join(','));
        });
        // Show the dialog
        var width = parseInt(
            (parseInt($('html').css('font-size'), 10) / 13) * 340,
            10
        );
        if (! width) {
            width = 340;
        }
        $('#enumEditorModal').modal('show');
        $('#enumEditorModal').find('.modal-body').first().html(dialog);
        // slider for choosing how many fields to add
        $('#enumEditorModal').find('.slider').slider({
            animate: true,
            range: 'min',
            value: 1,
            min: 1,
            max: 9,
            slide: function (event, ui) {
                $(this).closest('table').find('input[type=submit]').val(
                    Functions.sprintf(Messages.enum_addValue, ui.value)
                );
            }
        });
        // Focus the slider, otherwise it looks nearly transparent
        $('a.ui-slider-handle').addClass('ui-state-focus');
        return false;
    });

    $(document).on('click', 'a.central_columns_dialog', function () {
        var href = 'index.php?route=/database/central-columns';
        var db = CommonParams.get('db');
        var table = CommonParams.get('table');
        var maxRows = $(this).data('maxrows');
        var pick = $(this).data('pick');
        if (pick !== false) {
            pick = true;
        }
        var params = {
            'ajax_request' : true,
            'server' : CommonParams.get('server'),
            'db' : CommonParams.get('db'),
            'cur_table' : CommonParams.get('table'),
            'getColumnList':true
        };
        var colid = $(this).closest('td').find('input').attr('id');
        var fields = '';
        if (! (db + '_' + table in centralColumnList)) {
            centralColumnList.push(db + '_' + table);
            $.ajax({
                type: 'POST',
                url: href,
                data: params,
                success: function (data) {
                    centralColumnList[db + '_' + table] = data.message;
                },
                async:false
            });
        }
        var i = 0;
        var listSize = centralColumnList[db + '_' + table].length;
        var min = (listSize <= maxRows) ? listSize : maxRows;
        for (i = 0; i < min; i++) {
            fields += '<tr><td><div><span class="fw-bold">' +
                Functions.escapeHtml(centralColumnList[db + '_' + table][i].col_name) +
                '</span><br><span class="color_gray">' + centralColumnList[db + '_' + table][i].col_type;

            if (centralColumnList[db + '_' + table][i].col_attribute !== '') {
                fields += '(' + Functions.escapeHtml(centralColumnList[db + '_' + table][i].col_attribute) + ') ';
            }
            if (centralColumnList[db + '_' + table][i].col_length !== '') {
                fields += '(' + Functions.escapeHtml(centralColumnList[db + '_' + table][i].col_length) + ') ';
            }
            fields += Functions.escapeHtml(centralColumnList[db + '_' + table][i].col_extra) + '</span>' +
                '</div></td>';
            if (pick) {
                fields += '<td><input class="btn btn-secondary pick w-100" type="submit" value="' +
                    Messages.pickColumn + '" onclick="Functions.autoPopulate(\'' + colid + '\',' + i + ')"></td>';
            }
            fields += '</tr>';
        }
        var resultPointer = i;
        var searchIn = '<input type="text" class="filter_rows" placeholder="' + Messages.searchList + '">';
        if (fields === '') {
            fields = Functions.sprintf(Messages.strEmptyCentralList, '\'' + Functions.escapeHtml(db) + '\'');
            searchIn = '';
        }
        var seeMore = '';
        if (listSize > maxRows) {
            seeMore = '<fieldset class="pma-fieldset tblFooters text-center fw-bold">' +
                '<a href=\'#\' id=\'seeMore\'>' + Messages.seeMore + '</a></fieldset>';
        }
        var centralColumnsDialog = '<div class=\'max_height_400\'>' +
            '<fieldset class="pma-fieldset">' +
            searchIn +
            '<table id="col_list" class="table table-borderless values">' + fields + '</table>' +
            '</fieldset>' +
            seeMore +
            '</div>';

        var width = parseInt(
            (parseInt($('html').css('font-size'), 10) / 13) * 500,
            10
        );
        if (! width) {
            width = 500;
        }
        var buttonOptions = {};
        var $centralColumnsDialog = $(centralColumnsDialog).dialog({
            classes: {
                'ui-dialog-titlebar-close': 'btn-close'
            },
            minWidth: width,
            maxHeight: 450,
            modal: true,
            title: Messages.pickColumnTitle,
            buttons: buttonOptions,
            open: function () {
                $('#col_list').on('click', '.pick', function () {
                    $centralColumnsDialog.remove();
                });
                $('.filter_rows').on('keyup', function () {
                    $.uiTableFilter($('#col_list'), $(this).val());
                });
                $('#seeMore').on('click', function () {
                    fields = '';
                    min = (listSize <= maxRows + resultPointer) ? listSize : maxRows + resultPointer;
                    for (i = resultPointer; i < min; i++) {
                        fields += '<tr><td><div><span class="fw-bold">' +
                            centralColumnList[db + '_' + table][i].col_name +
                            '</span><br><span class="color_gray">' +
                            centralColumnList[db + '_' + table][i].col_type;

                        if (centralColumnList[db + '_' + table][i].col_attribute !== '') {
                            fields += '(' + centralColumnList[db + '_' + table][i].col_attribute + ') ';
                        }
                        if (centralColumnList[db + '_' + table][i].col_length !== '') {
                            fields += '(' + centralColumnList[db + '_' + table][i].col_length + ') ';
                        }
                        fields += centralColumnList[db + '_' + table][i].col_extra + '</span>' +
                            '</div></td>';
                        if (pick) {
                            fields += '<td><input class="btn btn-secondary pick w-100" type="submit" value="' +
                                Messages.pickColumn + '" onclick="Functions.autoPopulate(\'' + colid + '\',' + i + ')"></td>';
                        }
                        fields += '</tr>';
                    }
                    $('#col_list').append(fields);
                    resultPointer = i;
                    if (resultPointer === listSize) {
                        $('#seeMore').hide();
                    }
                    return false;
                });
                $(this).closest('.ui-dialog').find('.ui-dialog-buttonpane button').first().trigger('focus');
            },
            close: function () {
                $('#col_list').off('click', '.pick');
                $('.filter_rows').off('keyup');
                $(this).remove();
            }
        });
        return false;
    });

    // $(document).on('click', 'a.show_central_list',function(e) {

    // });
    // When "add a new value" is clicked, append an empty text field
    $(document).on('click', 'input.add_value', function (e) {
        e.preventDefault();
        var numNewRows = $('#enumEditorModal').find('div.slider').slider('value');
        while (numNewRows--) {
            $('#enumEditorModal').find('.values')
                .append(
                    '<tr class=\'hide\'><td>' +
                    '<input type=\'text\'>' +
                    '</td><td class=\'drop\'>' +
                    Functions.getImage('b_drop') +
                    '</td></tr>'
                )
                .find('tr').last()
                .show('fast');
        }
    });

    // Removes the specified row from the enum editor
    $(document).on('click', '#enum_editor td.drop', function () {
        $(this).closest('tr').hide('fast', function () {
            $(this).remove();
        });
    });
});

/**
 * Ensures indexes names are valid according to their type and, for a primary
 * key, lock index name to 'PRIMARY'
 * @param {string} formId Variable which parses the form name as
 *                        the input
 * @return {boolean} false if there is no index form, true else
 */
Functions.checkIndexName = function (formId) {
    if ($('#' + formId).length === 0) {
        return false;
    }

    // Gets the elements pointers
    var $theIdxName = $('#input_index_name');
    var $theIdxChoice = $('#select_index_choice');

    // Index is a primary key
    if ($theIdxChoice.find('option:selected').val() === 'PRIMARY') {
        $theIdxName.val('PRIMARY');
        $theIdxName.prop('disabled', true);
    } else {
        if ($theIdxName.val() === 'PRIMARY') {
            $theIdxName.val('');
        }
        $theIdxName.prop('disabled', false);
    }

    return true;
};

AJAX.registerTeardown('functions.js', function () {
    $(document).off('click', '#index_frm input[type=submit]');
});
AJAX.registerOnload('functions.js', function () {
    /**
     * Handler for adding more columns to an index in the editor
     */
    $(document).on('click', '#index_frm input[type=submit]', function (event) {
        event.preventDefault();
        var hadAddButtonHidden = $(this).closest('fieldset').find('.add_fields').hasClass('hide');
        if (hadAddButtonHidden === false) {
            var rowsToAdd = $(this)
                .closest('fieldset')
                .find('.slider')
                .slider('value');

            var tempEmptyVal = function () {
                $(this).val('');
            };

            var tempSetFocus = function () {
                if ($(this).find('option:selected').val() === '') {
                    return true;
                }
                $(this).closest('tr').find('input').trigger('focus');
            };

            while (rowsToAdd--) {
                var $indexColumns = $('#index_columns');
                var $newrow = $indexColumns
                    .find('tbody > tr').first()
                    .clone()
                    .appendTo(
                        $indexColumns.find('tbody')
                    );
                $newrow.find(':input').each(tempEmptyVal);
                // focus index size input on column picked
                $newrow.find('select').on('change', tempSetFocus);
            }
        }
    });
});
Functions.indexDialogModal = function (routeUrl, url, title, callbackSuccess, callbackFailure) {
    /* Remove the hidden dialogs if there are*/
    var modal = $('#indexDialogModal');

    const indexDialogPreviewModal = document.getElementById('indexDialogPreviewModal');
    indexDialogPreviewModal.addEventListener('shown.bs.modal', () => {
        const modalBody = indexDialogPreviewModal.querySelector('.modal-body');
        const $form = $('#index_frm');
        const formUrl = $form.attr('action');
        const sep = CommonParams.get('arg_separator');
        const formData = $form.serialize() +
            sep + 'do_save_data=1' +
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
                Functions.highlightSql($('#indexDialogPreviewModal'));
            },
            error: () => {
                modalBody.innerHTML = '<div class="alert alert-danger" role="alert">' + Messages.strErrorProcessingRequest + '</div>';
            }
        });
    });
    indexDialogPreviewModal.addEventListener('hidden.bs.modal', () => {
        indexDialogPreviewModal.querySelector('.modal-body').innerHTML = '<div class="spinner-border" role="status">' +
            '<span class="visually-hidden">' + Messages.strLoading + '</span></div>';
    });

    // Remove previous click listeners from other modal openings (issue: #17892)
    $('#indexDialogModalGoButton').off('click');
    $('#indexDialogModalGoButton').on('click', function () {
        /**
         * @var the_form object referring to the export form
         */
        var $form = $('#index_frm');
        Functions.ajaxShowMessage(Messages.strProcessingRequest);
        Functions.prepareForAjaxRequest($form);
        // User wants to submit the form
        $.post($form.attr('action'), $form.serialize() + CommonParams.get('arg_separator') + 'do_save_data=1', function (data) {
            var $sqlqueryresults = $('.sqlqueryresults');
            if ($sqlqueryresults.length !== 0) {
                $sqlqueryresults.remove();
            }
            if (typeof data !== 'undefined' && data.success === true) {
                Functions.ajaxShowMessage(data.message);
                Functions.highlightSql($('.result_query'));
                $('.result_query .alert').remove();
                /* Reload the field form*/
                $('#table_index').remove();
                $('<div id=\'temp_div\'><div>')
                    .append(data.index_table)
                    .find('#table_index')
                    .insertAfter('#index_header');
                var $editIndexDialog = $('#indexDialogModal');
                if ($editIndexDialog.length > 0) {
                    $editIndexDialog.modal('hide');
                }
                $('div.no_indexes_defined').hide();
                if (callbackSuccess) {
                    callbackSuccess(data);
                }
                Navigation.reload();
            } else {
                var $tempDiv = $('<div id=\'temp_div\'><div>').append(data.error);
                var $error;
                if ($tempDiv.find('.error code').length !== 0) {
                    $error = $tempDiv.find('.error code').addClass('error');
                } else {
                    $error = $tempDiv;
                }
                if (callbackFailure) {
                    callbackFailure();
                }
                Functions.ajaxShowMessage($error, false);
            }
        }); // end $.post()
    });

    var $msgbox = Functions.ajaxShowMessage();
    $.post(routeUrl, url, function (data) {
        if (typeof data !== 'undefined' && data.success === false) {
            // in the case of an error, show the error message returned.
            Functions.ajaxShowMessage(data.error, false);
        } else {
            Functions.ajaxRemoveMessage($msgbox);
            // Show dialog if the request was successful
            modal.modal('show');
            modal.find('.modal-body').first().html(data.message);
            $('#indexDialogModalLabel').first().text(title);
            Functions.verifyColumnsProperties();
            modal.find('.tblFooters').remove();
            Functions.showIndexEditDialog(modal);
        }
    }); // end $.get()
};

Functions.indexEditorDialog = function (url, title, callbackSuccess, callbackFailure) {
    Functions.indexDialogModal('index.php?route=/table/indexes', url, title, callbackSuccess, callbackFailure);
};

Functions.indexRenameDialog = function (url, title, callbackSuccess, callbackFailure) {
    Functions.indexDialogModal('index.php?route=/table/indexes/rename', url, title, callbackSuccess, callbackFailure);
};

Functions.showIndexEditDialog = function ($outer) {
    Indexes.checkIndexType();
    Functions.checkIndexName('index_frm');
    var $indexColumns = $('#index_columns');
    $indexColumns.find('tbody').sortable({
        axis: 'y',
        containment: $indexColumns.find('tbody'),
        tolerance: 'pointer',
        forcePlaceholderSize: true,
        // Add custom dragged row
        helper: function (event, tr) {
            var $originalCells = tr.children();
            var $helper = tr.clone();
            $helper.children().each(function (index) {
                // Set cell width in dragged row
                $(this).width($originalCells.eq(index).outerWidth());
                var $childrenSelect = $originalCells.eq(index).children('select');
                if ($childrenSelect.length) {
                    var selectedIndex = $childrenSelect.prop('selectedIndex');
                    // Set correct select value in dragged row
                    $(this).children('select').prop('selectedIndex', selectedIndex);
                }
            });
            return $helper;
        }
    });
    Functions.showHints($outer);
    // Add a slider for selecting how many columns to add to the index
    $outer.find('.slider').slider({
        animate: true,
        value: 1,
        min: 1,
        max: 16,
        slide: function (event, ui) {
            $(this).closest('fieldset').find('input[type=submit]').val(
                Functions.sprintf(Messages.strAddToIndex, ui.value)
            );
        }
    });
    $('div.add_fields').removeClass('hide');
    // focus index size input on column picked
    $outer.find('table#index_columns select').on('change', function () {
        if ($(this).find('option:selected').val() === '') {
            return true;
        }
        $(this).closest('tr').find('input').trigger('focus');
    });
    // Focus the slider, otherwise it looks nearly transparent
    $('a.ui-slider-handle').addClass('ui-state-focus');
    // set focus on index name input, if empty
    var input = $outer.find('input#input_index_name');
    if (! input.val()) {
        input.trigger('focus');
    }
};

/**
 * Function to display tooltips that were
 * generated on the PHP side by PhpMyAdmin\Util::showHint()
 *
 * @param {object} $div a div jquery object which specifies the
 *                    domain for searching for tooltips. If we
 *                    omit this parameter the function searches
 *                    in the whole body
 **/
Functions.showHints = function ($div) {
    var $newDiv = $div;
    if ($newDiv === undefined || !($newDiv instanceof jQuery) || $newDiv.length === 0) {
        $newDiv = $('body');
    }
    $newDiv.find('.pma_hint').each(function () {
        Functions.tooltip(
            $(this).children('img'),
            'img',
            $(this).children('span').html()
        );
    });
};

AJAX.registerOnload('functions.js', function () {
    Functions.showHints();
});

Functions.mainMenuResizerCallback = function () {
    // 5 px margin for jumping menu in Chrome
    // eslint-disable-next-line compat/compat
    return $(document.body).width() - 5;
};

// This must be fired only once after the initial page load
$(function () {
    // Initialise the menu resize plugin
    $('#topmenu').menuResizer(Functions.mainMenuResizerCallback);
    // register resize event
    $(window).on('resize', function () {
        $('#topmenu').menuResizer('resize');
    });
});

/**
 * var  toggleButton  This is a function that creates a toggle
 *                    sliding button given a jQuery reference
 *                    to the correct DOM element
 *
 * @param $obj
 */
Functions.toggleButton = function ($obj) {
    // In rtl mode the toggle switch is flipped horizontally
    // so we need to take that into account
    var right;
    if ($('span.text_direction', $obj).text() === 'ltr') {
        right = 'right';
    } else {
        right = 'left';
    }
    /**
     * @var  h  Height of the button, used to scale the
     *          background image and position the layers
     */
    var h = $obj.height();
    $('img', $obj).height(h);
    $('table', $obj).css('bottom', h - 1);
    /**
     * @var  on   Width of the "ON" part of the toggle switch
     * @var  off  Width of the "OFF" part of the toggle switch
     */
    var on  = $('td.toggleOn', $obj).width();
    var off = $('td.toggleOff', $obj).width();
    // Make the "ON" and "OFF" parts of the switch the same size
    // + 2 pixels to avoid overflowed
    $('td.toggleOn > div', $obj).width(Math.max(on, off) + 2);
    $('td.toggleOff > div', $obj).width(Math.max(on, off) + 2);
    /**
     *  @var  w  Width of the central part of the switch
     */
    var w = parseInt(($('img', $obj).height() / 16) * 22, 10);
    // Resize the central part of the switch on the top
    // layer to match the background
    $($obj).find('table td').eq(1).children('div').width(w);
    /**
     * @var  imgw    Width of the background image
     * @var  tblw    Width of the foreground layer
     * @var  offset  By how many pixels to move the background
     *               image, so that it matches the top layer
     */
    var imgw = $('img', $obj).width();
    var tblw = $('table', $obj).width();
    var offset = parseInt(((imgw - tblw) / 2), 10);
    // Move the background to match the layout of the top layer
    $obj.find('img').css(right, offset);
    /**
     * @var  offw    Outer width of the "ON" part of the toggle switch
     * @var  btnw    Outer width of the central part of the switch
     */
    var offw = $('td.toggleOff', $obj).outerWidth();
    var btnw = $($obj).find('table td').eq(1).outerWidth();
    // Resize the main div so that exactly one side of
    // the switch plus the central part fit into it.
    $obj.width(offw + btnw + 2);
    /**
     * @var  move  How many pixels to move the
     *             switch by when toggling
     */
    var move = $('td.toggleOff', $obj).outerWidth();
    // If the switch is initialized to the
    // OFF state we need to move it now.
    if ($('div.toggle-container', $obj).hasClass('off')) {
        if (right === 'right') {
            $('div.toggle-container', $obj).animate({ 'left': '-=' + move + 'px' }, 0);
        } else {
            $('div.toggle-container', $obj).animate({ 'left': '+=' + move + 'px' }, 0);
        }
    }
    // Attach an 'onclick' event to the switch
    $('div.toggle-container', $obj).on('click', function () {
        if ($(this).hasClass('isActive')) {
            return false;
        } else {
            $(this).addClass('isActive');
        }
        var $msg = Functions.ajaxShowMessage();
        var $container = $(this);
        var callback = $('span.callback', this).text();
        var operator;
        var url;
        var removeClass;
        var addClass;
        // Perform the actual toggle
        if ($(this).hasClass('on')) {
            if (right === 'right') {
                operator = '-=';
            } else {
                operator = '+=';
            }
            url = $(this).find('td.toggleOff > span').text();
            removeClass = 'on';
            addClass = 'off';
        } else {
            if (right === 'right') {
                operator = '+=';
            } else {
                operator = '-=';
            }
            url = $(this).find('td.toggleOn > span').text();
            removeClass = 'off';
            addClass = 'on';
        }

        var parts = url.split('?');
        $.post(parts[0], parts[1] + '&ajax_request=true', function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                Functions.ajaxRemoveMessage($msg);
                $container
                    .removeClass(removeClass)
                    .addClass(addClass)
                    .animate({ 'left': operator + move + 'px' }, function () {
                        $container.removeClass('isActive');
                    });
                // eslint-disable-next-line no-eval
                eval(callback);
            } else {
                Functions.ajaxShowMessage(data.error, false);
                $container.removeClass('isActive');
            }
        });
    });
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $('div.toggle-container').off('click');
});
/**
 * Initialise all toggle buttons
 */
AJAX.registerOnload('functions.js', function () {
    $('div.toggleAjax').each(function () {
        var $button = $(this).show();
        $button.find('img').each(function () {
            if (this.complete) {
                Functions.toggleButton($button);
            } else {
                $(this).on('load', function () {
                    Functions.toggleButton($button);
                });
            }
        });
    });
});

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('change', 'select.pageselector');
    $('#update_recent_tables').off('ready');
    $('#sync_favorite_tables').off('ready');
});

AJAX.registerOnload('functions.js', function () {
    /**
     * Autosubmit page selector
     */
    $(document).on('change', 'select.pageselector', function (event) {
        event.stopPropagation();
        // Check where to load the new content
        if ($(this).closest('#pma_navigation').length === 0) {
            // For the main page we don't need to do anything,
            $(this).closest('form').trigger('submit');
        } else {
            // but for the navigation we need to manually replace the content
            Navigation.treePagination($(this));
        }
    });

    var $updateRecentTables = $('#update_recent_tables');
    if ($updateRecentTables.length) {
        $.get(
            $updateRecentTables.attr('href'),
            { 'no_debug': true },
            function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    $('#pma_recent_list').html(data.list);
                }
            }
        );
    }

    // Sync favorite tables from localStorage to pmadb.
    if ($('#sync_favorite_tables').length) {
        var favoriteTables = '';
        if (isStorageSupported('localStorage')
            && typeof window.localStorage.favoriteTables !== 'undefined'
            && window.localStorage.favoriteTables !== 'undefined') {
            favoriteTables = window.localStorage.favoriteTables;
            if (favoriteTables === 'undefined') {
                // Do not send an invalid value
                return;
            }
        }
        $.ajax({
            url: $('#sync_favorite_tables').attr('href'),
            cache: false,
            type: 'POST',
            data: {
                'favoriteTables': favoriteTables,
                'server': CommonParams.get('server'),
                'no_debug': true
            },
            success: function (data) {
                // Update localStorage.
                if (isStorageSupported('localStorage')) {
                    window.localStorage.favoriteTables = data.favoriteTables;
                }
                $('#pma_favorite_list').html(data.list);
            }
        });
    }
}); // end of $()

/**
 * Creates a message inside an object with a sliding effect
 *
 * @param {string} msg    A string containing the text to display
 * @param {JQuery} $object   a jQuery object containing the reference
 *                 to the element where to put the message
 *                 This is optional, if no element is
 *                 provided, one will be created below the
 *                 navigation links at the top of the page
 *
 * @return {boolean} True on success, false on failure
 */
Functions.slidingMessage = function (msg, $object) {
    var $obj = $object;
    if (msg === undefined || msg.length === 0) {
        // Don't show an empty message
        return false;
    }
    if ($obj === undefined || !($obj instanceof jQuery) || $obj.length === 0) {
        // If the second argument was not supplied,
        // we might have to create a new DOM node.
        if ($('#PMA_slidingMessage').length === 0) {
            $('#page_content').prepend(
                '<span id="PMA_slidingMessage" ' +
                'class="d-inline-block"></span>'
            );
        }
        $obj = $('#PMA_slidingMessage');
    }
    if ($obj.has('div').length > 0) {
        // If there already is a message inside the
        // target object, we must get rid of it
        $obj
            .find('div')
            .first()
            .fadeOut(function () {
                $obj
                    .children()
                    .remove();
                $obj
                    .append('<div>' + msg + '</div>');
                // highlight any sql before taking height;
                Functions.highlightSql($obj);
                $obj.find('div')
                    .first()
                    .hide();
                $obj
                    .animate({
                        height: $obj.find('div').first().height()
                    })
                    .find('div')
                    .first()
                    .fadeIn();
            });
    } else {
        // Object does not already have a message
        // inside it, so we simply slide it down
        $obj.width('100%')
            .html('<div>' + msg + '</div>');
        // highlight any sql before taking height;
        Functions.highlightSql($obj);
        var h = $obj
            .find('div')
            .first()
            .hide()
            .height();
        $obj
            .find('div')
            .first()
            .css('height', 0)
            .show()
            .animate({
                height: h
            }, function () {
            // Set the height of the parent
            // to the height of the child
                $obj
                    .height(
                        $obj
                            .find('div')
                            .first()
                            .height()
                    );
            });
    }
    return true;
};

/**
 * Attach CodeMirror2 editor to SQL edit area.
 */
AJAX.registerOnload('functions.js', function () {
    var $elm = $('#sqlquery');
    if ($elm.siblings().filter('.CodeMirror').length > 0) {
        return;
    }
    if ($elm.length > 0) {
        if (typeof CodeMirror !== 'undefined') {
            codeMirrorEditor = Functions.getSqlEditor($elm);
            codeMirrorEditor.focus();
            codeMirrorEditor.on('blur', Functions.updateQueryParameters);
        } else {
            // without codemirror
            $elm.trigger('focus').on('blur', Functions.updateQueryParameters);
        }
    }
    Functions.highlightSql($('body'));
});
AJAX.registerTeardown('functions.js', function () {
    if (codeMirrorEditor) {
        $('#sqlquery').text(codeMirrorEditor.getValue());
        codeMirrorEditor.toTextArea();
        codeMirrorEditor = false;
    }
});
AJAX.registerOnload('functions.js', function () {
    // initializes all lock-page elements lock-id and
    // val-hash data property
    $('#page_content form.lock-page textarea, ' +
            '#page_content form.lock-page input[type="text"], ' +
            '#page_content form.lock-page input[type="number"], ' +
            '#page_content form.lock-page select').each(function (i) {
        $(this).data('lock-id', i);
        // val-hash is the hash of default value of the field
        // so that it can be compared with new value hash
        // to check whether field was modified or not.
        $(this).data('val-hash', AJAX.hash($(this).val()));
    });

    // initializes lock-page elements (input types checkbox and radio buttons)
    // lock-id and val-hash data property
    $('#page_content form.lock-page input[type="checkbox"], ' +
            '#page_content form.lock-page input[type="radio"]').each(function (i) {
        $(this).data('lock-id', i);
        $(this).data('val-hash', AJAX.hash($(this).is(':checked')));
    });
});

/**
 * jQuery plugin to correctly filter input fields by value, needed
 * because some nasty values may break selector syntax
 */
(function ($) {
    $.fn.filterByValue = function (value) {
        return this.filter(function () {
            return $(this).val() === value;
        });
    };
}(jQuery));

/**
 * Return value of a cell in a table.
 *
 * @param {string} td
 * @return {string}
 */
Functions.getCellValue = function (td) {
    var $td = $(td);
    if ($td.is('.null')) {
        return '';
    } else if ((! $td.is('.to_be_saved')
        || $td.is('.set'))
        && $td.data('original_data')
    ) {
        return $td.data('original_data');
    } else {
        return $td.text();
    }
};

/**
 * Validate and return stringified JSON inputs, or plain if invalid.
 *
 * @param json the json input to be validated and stringified
 * @param replacer An array of strings and numbers that acts as an approved list for selecting the object properties that will be stringified.
 * @param space Adds indentation, white space, and line break characters to the return-value JSON text to make it easier to read.
 * @return {string}
 */
Functions.stringifyJSON = function (json, replacer = null, space = 0) {
    try {
        return JSON.stringify(JSON.parse(json), replacer, space);
    } catch (e) {
        return json;
    }
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('change', '.autosubmit');
});

AJAX.registerOnload('functions.js', function () {
    /**
     * Automatic form submission on change.
     */
    $(document).on('change', '.autosubmit', function () {
        $(this).closest('form').trigger('submit');
    });
});

/**
 * @implements EventListener
 */
const PrintPage = {
    handleEvent: () => {
        window.print();
    }
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    document.querySelectorAll('.jsPrintButton').forEach(item => {
        item.removeEventListener('click', PrintPage);
    });

    $(document).off('click', 'a.create_view.ajax');
    $(document).off('keydown', '#createViewModal input, #createViewModal select');
    $(document).off('change', '#fkc_checkbox');
});

AJAX.registerOnload('functions.js', function () {
    document.querySelectorAll('.jsPrintButton').forEach(item => {
        item.addEventListener('click', PrintPage);
    });

    $('.logout').on('click', function () {
        var form = $(
            '<form method="POST" action="' + $(this).attr('href') + '" class="disableAjax">' +
            '<input type="hidden" name="token" value="' + Functions.escapeHtml(CommonParams.get('token')) + '">' +
            '</form>'
        );
        $('body').append(form);
        form.submit();
        sessionStorage.clear();
        return false;
    });
    /**
     * Ajaxification for the "Create View" action
     */
    $(document).on('click', 'a.create_view.ajax', function (e) {
        e.preventDefault();
        Functions.createViewModal($(this));
    });
    /**
     * Attach Ajax event handlers for input fields in the editor
     * and used to submit the Ajax request when the ENTER key is pressed.
     */
    if ($('#createViewModal').length !== 0) {
        $(document).on('keydown', '#createViewModal input, #createViewModal select', function (e) {
            if (e.which === 13) { // 13 is the ENTER key
                e.preventDefault();

                // with preventing default, selection by <select> tag
                // was also prevented in IE
                $(this).trigger('blur');

                $(this).closest('.ui-dialog').find('.ui-button').first().trigger('click');
            }
        }); // end $(document).on()
    }

    if ($('textarea[name="view[as]"]').length !== 0) {
        codeMirrorEditor = Functions.getSqlEditor($('textarea[name="view[as]"]'));
    }
});

Functions.createViewModal = function ($this) {
    var $msg = Functions.ajaxShowMessage();
    var sep = CommonParams.get('arg_separator');
    var params = Functions.getJsConfirmCommonParam(this, $this.getPostData());
    params += sep + 'ajax_dialog=1';
    $.post($this.attr('href'), params, function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
            Functions.ajaxRemoveMessage($msg);
            $('#createViewModalGoButton').on('click', function () {
                if (typeof CodeMirror !== 'undefined') {
                    codeMirrorEditor.save();
                }
                $msg = Functions.ajaxShowMessage();
                $.post('index.php?route=/view/create', $('#createViewModal').find('form').serialize(), function (data) {
                    Functions.ajaxRemoveMessage($msg);
                    if (typeof data !== 'undefined' && data.success === true) {
                        $('#createViewModal').modal('hide');
                        $('.result_query').html(data.message);
                        Navigation.reload();
                    } else {
                        Functions.ajaxShowMessage(data.error);
                    }
                });
            });
            $('#createViewModal').find('.modal-body').first().html(data.message);
            // Attach syntax highlighted editor
            $('#createViewModal').on('shown.bs.modal', function () {
                codeMirrorEditor = Functions.getSqlEditor($('#createViewModal').find('textarea'));
                $('input:visible[type=text]', $('#createViewModal')).first().trigger('focus');
                $('#createViewModal').off('shown.bs.modal');
            });
            $('#createViewModal').modal('show');
        } else {
            Functions.ajaxShowMessage(data.error);
        }
    });
};

/**
 * Makes the breadcrumbs and the menu bar float at the top of the viewport
 */
$(function () {
    if ($('#floating_menubar').length && $('#PMA_disable_floating_menubar').length === 0) {
        var left = $('html').attr('dir') === 'ltr' ? 'left' : 'right';
        $('#floating_menubar')
            .css('margin-' + left, $('#pma_navigation').width() + $('#pma_navigation_resizer').width())
            .css(left, 0)
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
            $('#topmenu').menuResizer('resize');
        }, 4);
    }
});

/**
 * Scrolls the page to the top if clicking the server-breadcrumb bar
 * If the user holds the Ctrl (or Meta on macOS) key, it prevents the scroll
 * so they can open the link in a new tab.
 */
$(function () {
    $(document).on('click', '#server-breadcrumb, #goto_pagetop', function (event) {
        if (event.ctrlKey || event.metaKey) {
            return;
        }
        event.preventDefault();
        $('html, body').animate({ scrollTop: 0 }, 'fast');
    });
});

var checkboxesSel = 'input.checkall:checkbox:enabled';
Functions.checkboxesSel = checkboxesSel;

/**
 * Watches checkboxes in a form to set the checkall box accordingly
 */
Functions.checkboxesChanged = function () {
    var $form = $(this.form);
    // total number of checkboxes in current form
    var totalBoxes = $form.find(checkboxesSel).length;
    // number of checkboxes checked in current form
    var checkedBoxes = $form.find(checkboxesSel + ':checked').length;
    var $checkall = $form.find('input.checkall_box');
    if (totalBoxes === checkedBoxes) {
        $checkall.prop({ checked: true, indeterminate: false });
    } else if (checkedBoxes > 0) {
        $checkall.prop({ checked: true, indeterminate: true });
    } else {
        $checkall.prop({ checked: false, indeterminate: false });
    }
};

$(document).on('change', checkboxesSel, Functions.checkboxesChanged);

$(document).on('change', 'input.checkall_box', function () {
    var isChecked = $(this).is(':checked');
    $(this.form).find(checkboxesSel).not('.row-hidden').prop('checked', isChecked)
        .parents('tr').toggleClass('marked table-active', isChecked);
});

$(document).on('click', '.checkall-filter', function () {
    var $this = $(this);
    var selector = $this.data('checkall-selector');
    $('input.checkall_box').prop('checked', false);
    $this.parents('form').find(checkboxesSel).filter(selector).prop('checked', true).trigger('change')
        .parents('tr').toggleClass('marked', true);
    return false;
});

/**
 * Watches checkboxes in a sub form to set the sub checkall box accordingly
 */
Functions.subCheckboxesChanged = function () {
    var $form = $(this).parent().parent();
    // total number of checkboxes in current sub form
    var totalBoxes = $form.find(checkboxesSel).length;
    // number of checkboxes checked in current sub form
    var checkedBoxes = $form.find(checkboxesSel + ':checked').length;
    var $checkall = $form.find('input.sub_checkall_box');
    if (totalBoxes === checkedBoxes) {
        $checkall.prop({ checked: true, indeterminate: false });
    } else if (checkedBoxes > 0) {
        $checkall.prop({ checked: true, indeterminate: true });
    } else {
        $checkall.prop({ checked: false, indeterminate: false });
    }
};

$(document).on('change', checkboxesSel + ', input.checkall_box:checkbox:enabled', Functions.subCheckboxesChanged);

$(document).on('change', 'input.sub_checkall_box', function () {
    var isChecked = $(this).is(':checked');
    var $form = $(this).parent().parent();
    $form.find(checkboxesSel).prop('checked', isChecked)
        .parents('tr').toggleClass('marked', isChecked);
});

/**
 * Rows filtering
 *
 * - rows to filter are identified by data-filter-row attribute
 *   which contains uppercase string to filter
 * - it is simple substring case insensitive search
 * - optionally number of matching rows is written to element with
 *   id filter-rows-count
 */
$(document).on('keyup', '#filterText', function () {
    var filterInput = $(this).val().toUpperCase().replace(/ /g, '_');
    var count = 0;
    $('[data-filter-row]').each(function () {
        var $row = $(this);
        /* Can not use data() here as it does magic conversion to int for numeric values */
        if ($row.attr('data-filter-row').indexOf(filterInput) > -1) {
            count += 1;
            $row.show();
            $row.find('input.checkall').removeClass('row-hidden');
        } else {
            $row.hide();
            $row.find('input.checkall').addClass('row-hidden').prop('checked', false);
            $row.removeClass('marked');
        }
    });
    setTimeout(function () {
        $(checkboxesSel).trigger('change');
    }, 300);
    $('#filter-rows-count').html(count);
});
AJAX.registerOnload('functions.js', function () {
    /* Trigger filtering of the list based on incoming database name */
    var $filter = $('#filterText');
    if ($filter.val()) {
        $filter.trigger('keyup').trigger('select');
    }
});

/**
 * Formats a byte number to human-readable form
 *
 * @param bytesToFormat the bytes to format
 * @param subDecimals optional subdecimals the number of digits after the point
 * @param pointChar optional pointchar the char to use as decimal point
 *
 * @return {string}
 */
Functions.formatBytes = function (bytesToFormat, subDecimals, pointChar) {
    var bytes = bytesToFormat;
    var decimals = subDecimals;
    var point = pointChar;
    if (!decimals) {
        decimals = 0;
    }
    if (!point) {
        point = '.';
    }
    var units = ['B', 'KiB', 'MiB', 'GiB'];
    for (var i = 0; bytes > 1024 && i < units.length; i++) {
        bytes /= 1024;
    }
    var factor = Math.pow(10, decimals);
    bytes = Math.round(bytes * factor) / factor;
    bytes = bytes.toString().split('.').join(point);
    return bytes + ' ' + units[i];
};

AJAX.registerOnload('functions.js', function () {
    /**
     * Reveal the login form to users with JS enabled
     * and focus the appropriate input field
     */
    var $loginform = $('#loginform');
    if ($loginform.length) {
        $loginform.find('.js-show').show();
        if ($('#input_username').val()) {
            $('#input_password').trigger('focus');
        } else {
            $('#input_username').trigger('focus');
        }
    }
    var $httpsWarning = $('#js-https-mismatch');
    if ($httpsWarning.length) {
        if ((window.location.protocol === 'https:') !== CommonParams.get('is_https')) {
            $httpsWarning.show();
        }
    }
});

/**
 * Formats timestamp for display
 *
 * @param {string} date
 * @param {bool} seconds
 * @return {string}
 */
Functions.formatDateTime = function (date, seconds) {
    var result = $.datepicker.formatDate('yy-mm-dd', date);
    var timefmt = 'HH:mm';
    if (seconds) {
        timefmt = 'HH:mm:ss';
    }
    return result + ' ' + $.datepicker.formatTime(
        timefmt, {
            hour: date.getHours(),
            minute: date.getMinutes(),
            second: date.getSeconds()
        }
    );
};

/**
 * Check than forms have less fields than max allowed by PHP.
 * @return {boolean}
 */
Functions.checkNumberOfFields = function () {
    if (typeof maxInputVars === 'undefined') {
        return false;
    }
    if (false === maxInputVars) {
        return false;
    }
    $('form').each(function () {
        var nbInputs = $(this).find(':input').length;
        if (nbInputs > maxInputVars) {
            var warning = Functions.sprintf(Messages.strTooManyInputs, maxInputVars);
            Functions.ajaxShowMessage(warning);
            return false;
        }
        return true;
    });

    return true;
};

/**
 * Ignore the displayed php errors.
 * Simply removes the displayed errors.
 *
 * @param clearPrevErrors whether to clear errors stored
 *             in $_SESSION['prev_errors'] at server
 *
 */
Functions.ignorePhpErrors = function (clearPrevErrors) {
    var clearPrevious = clearPrevErrors;
    if (typeof(clearPrevious) === 'undefined' ||
        clearPrevious === null
    ) {
        clearPrevious = false;
    }
    // send AJAX request to /error-report with send_error_report=0, exception_type=php & token.
    // It clears the prev_errors stored in session.
    if (clearPrevious) {
        var $pmaReportErrorsForm = $('#pma_report_errors_form');
        $pmaReportErrorsForm.find('input[name="send_error_report"]').val(0); // change send_error_report to '0'
        $pmaReportErrorsForm.trigger('submit');
    }

    // remove displayed errors
    var $pmaErrors = $('#pma_errors');
    $pmaErrors.fadeOut('slow');
    $pmaErrors.remove();
};

/**
 * Toggle the Datetimepicker UI if the date value entered
 * by the user in the 'text box' is not going to be accepted
 * by the Datetimepicker plugin (but is accepted by MySQL)
 *
 * @param $td
 * @param $inputField
 */
Functions.toggleDatepickerIfInvalid = function ($td, $inputField) {
    // If the Datetimepicker UI is not present, return
    if ($inputField.hasClass('hasDatepicker')) {
        // Regex allowed by the Datetimepicker UI
        var dtexpDate = new RegExp(['^([0-9]{4})',
            '-(((01|03|05|07|08|10|12)-((0[1-9])|([1-2][0-9])|(3[0-1])))|((02|04|06|09|11)',
            '-((0[1-9])|([1-2][0-9])|30)))$'].join(''));
        var dtexpTime = new RegExp(['^(([0-1][0-9])|(2[0-3]))',
            ':((0[0-9])|([1-5][0-9]))',
            ':((0[0-9])|([1-5][0-9]))(.[0-9]{1,6}){0,1}$'].join(''));

        // If key-ed in Time or Date values are unsupported by the UI, close it
        if ($td.attr('data-type') === 'date' && ! dtexpDate.test($inputField.val())) {
            $inputField.datepicker('hide');
        } else if ($td.attr('data-type') === 'time' && ! dtexpTime.test($inputField.val())) {
            $inputField.datepicker('hide');
        } else {
            $inputField.datepicker('show');
        }
    }
};

/**
 * Function to submit the login form after validation is done.
 * NOTE: do NOT use a module or it will break the callback, issue #15435
 */
// eslint-disable-next-line no-unused-vars, camelcase
var Functions_recaptchaCallback = function () {
    $('#login_form').trigger('submit');
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('keydown', 'form input, form textarea, form select');
});

AJAX.registerOnload('functions.js', function () {
    /**
     * Handle 'Ctrl/Alt + Enter' form submits
     */
    $('form input, form textarea, form select').on('keydown', function (e) {
        if ((e.ctrlKey && e.which === 13) || (e.altKey && e.which === 13)) {
            var $form = $(this).closest('form');

            // There could be multiple submit buttons on the same form,
            // we assume all of them behave identical and just click one.
            if (! $form.find('input[type="submit"]').first() ||
                ! $form.find('input[type="submit"]').first().trigger('click')
            ) {
                $form.trigger('submit');
            }
        }
    });
});

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('change', 'input[type=radio][name="pw_hash"]');
    $(document).off('mouseover', '.sortlink');
    $(document).off('mouseout', '.sortlink');
});

AJAX.registerOnload('functions.js', function () {
    /*
     * Display warning regarding SSL when sha256_password
     * method is selected
     * Used in /user-password (Change Password link on index.php)
     */
    $(document).on('change', 'select#select_authentication_plugin_cp', function () {
        if (this.value === 'sha256_password') {
            $('#ssl_reqd_warning_cp').show();
        } else {
            $('#ssl_reqd_warning_cp').hide();
        }
    });

    Cookies.defaults.path = CommonParams.get('rootPath');

    // Bind event handlers for toggling sort icons
    $(document).on('mouseover', '.sortlink', function () {
        $(this).find('.soimg').toggle();
    });
    $(document).on('mouseout', '.sortlink', function () {
        $(this).find('.soimg').toggle();
    });
});

/**
 * Returns an HTML IMG tag for a particular image from a theme,
 * which may be an actual file or an icon from a sprite
 *
 * @param {string} image      The name of the file to get
 * @param {string} alternate  Used to set 'alt' and 'title' attributes of the image
 * @param {object} attributes An associative array of other attributes
 *
 * @return {object} The requested image, this object has two methods:
 *                  .toString()        - Returns the IMG tag for the requested image
 *                  .attr(name)        - Returns a particular attribute of the IMG
 *                                       tag given it's name
 *                  .attr(name, value) - Sets a particular attribute of the IMG
 *                                       tag to the given value
 */
Functions.getImage = function (image, alternate, attributes) {
    var alt = alternate;
    var attr = attributes;
    // custom image object, it will eventually be returned by this functions
    var retval = {
        data: {
            // this is private
            alt: '',
            title: '',
            src: 'themes/dot.gif',
        },
        attr: function (name, value) {
            if (value === undefined) {
                if (this.data[name] === undefined) {
                    return '';
                } else {
                    return this.data[name];
                }
            } else {
                this.data[name] = value;
            }
        },
        toString: function () {
            var retval = '<' + 'img';
            for (var i in this.data) {
                retval += ' ' + i + '="' + this.data[i] + '"';
            }
            retval += ' /' + '>';
            return retval;
        }
    };
    // initialise missing parameters
    if (attr === undefined) {
        attr = {};
    }
    if (alt === undefined) {
        alt = '';
    }
    // set alt
    if (attr.alt !== undefined) {
        retval.attr('alt', Functions.escapeHtml(attr.alt));
    } else {
        retval.attr('alt', Functions.escapeHtml(alt));
    }
    // set title
    if (attr.title !== undefined) {
        retval.attr('title', Functions.escapeHtml(attr.title));
    } else {
        retval.attr('title', Functions.escapeHtml(alt));
    }
    // set css classes
    retval.attr('class', 'icon ic_' + image);
    // set all other attributes
    for (var i in attr) {
        if (i === 'src') {
            // do not allow to override the 'src' attribute
            continue;
        }

        retval.attr(i, attr[i]);
    }

    return retval;
};

/**
 * Sets a configuration value.
 *
 * A configuration value may be set in both browser's local storage and
 * remotely in server's configuration table.
 *
 * NOTE: Depending on server's configuration, the configuration table may be or
 * not persistent.
 *
 * @param {string}     key         Configuration key.
 * @param {object}     value       Configuration value.
 */
Functions.configSet = function (key, value) {
    // Updating value in local storage.
    var serialized = JSON.stringify(value);
    localStorage.setItem(key, serialized);

    $.ajax({
        url: 'index.php?route=/config/set',
        type: 'POST',
        dataType: 'json',
        data: {
            'ajax_request': true,
            key: key,
            server: CommonParams.get('server'),
            value: serialized,
        },
        success: function (data) {
            if (data.success !== true) {
                // Try to find a message to display
                if (data.error || data.message || false) {
                    Functions.ajaxShowMessage(data.error || data.message);
                }
            }
        }
    });
};

/**
 * Gets a configuration value. A configuration value will be searched in
 * browser's local storage first and if not found, a call to the server will be
 * made.
 *
 * If value should not be cached and the up-to-date configuration value from
 * right from the server is required, the third parameter should be `false`.
 *
 * @param {string}     key             Configuration key.
 * @param {boolean}    cached          Configuration type.
 * @param {Function}   successCallback The callback to call after the value is successfully received
 * @param {Function}   failureCallback The callback to call when the value can not be received
 *
 * @return {void}
 */
Functions.configGet = function (key, cached, successCallback, failureCallback) {
    var isCached = (typeof cached !== 'undefined') ? cached : true;
    var value = localStorage.getItem(key);
    if (isCached && value !== undefined && value !== null) {
        return JSON.parse(value);
    }

    // Result not found in local storage or ignored.
    // Hitting the server.
    $.ajax({
        url: 'index.php?route=/config/get',
        type: 'POST',
        dataType: 'json',
        data: {
            'ajax_request': true,
            server: CommonParams.get('server'),
            key: key
        },
        success: function (data) {
            if (data.success !== true) {
                // Try to find a message to display
                if (data.error || data.message || false) {
                    Functions.ajaxShowMessage(data.error || data.message);
                }

                // Call the callback if it is defined
                if (typeof failureCallback === 'function') {
                    failureCallback();
                }

                // return here, exit non success mode
                return;
            }

            // Updating value in local storage.
            localStorage.setItem(key, JSON.stringify(data.value));
            // Call the callback if it is defined
            if (typeof successCallback === 'function') {
                // Feed it the value previously saved like on async mode
                successCallback(JSON.parse(localStorage.getItem(key)));
            }
        }
    });
};

/**
 * Return POST data as stored by Generator::linkOrButton
 *
 * @return {string}
 */
Functions.getPostData = function () {
    var dataPost = this.attr('data-post');
    // Strip possible leading ?
    if (dataPost !== undefined && dataPost.substring(0,1) === '?') {
        dataPost = dataPost.substr(1);
    }
    return dataPost;
};
jQuery.fn.getPostData = Functions.getPostData;
