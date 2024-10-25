/**
 * Functions used in configuration forms and on user preferences pages
 */

/* exported PASSIVE_EVENT_LISTENERS */

var configInlineParams;
var configScriptLoaded;

/**
 * checks whether browser supports web storage
 *
 * @param {'localStorage' | 'sessionStorage'} type the type of storage i.e. localStorage or sessionStorage
 * @param {boolean} warn Wether to show a warning on error
 *
 * @return {boolean}
 */
function isStorageSupported (type, warn = false) {
    try {
        window[type].setItem('PMATest', 'test');
        // Check whether key-value pair was set successfully
        if (window[type].getItem('PMATest') === 'test') {
            // Supported, remove test variable from storage
            window[type].removeItem('PMATest');
            return true;
        }
    } catch (error) {
        // Not supported
        if (warn) {
            Functions.ajaxShowMessage(Messages.strNoLocalStorage, false);
        }
    }
    return false;
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('config.js', function () {
    $('.optbox input[id], .optbox select[id], .optbox textarea[id]').off('change').off('keyup');
    $('.optbox input[type=button][name=submit_reset]').off('click');
    $('div.tab-content').off();
    $('#import_local_storage, #export_local_storage').off('click');
    $('form.prefs-form').off('change').off('submit');
    $(document).off('click', 'div.click-hide-message');
    $('#prefs_autoload').find('a').off('click');
});

AJAX.registerOnload('config.js', function () {
    var $topmenuUpt = $('#user_prefs_tabs');
    $topmenuUpt.find('a.active').attr('rel', 'samepage');
    $topmenuUpt.find('a:not(.active)').attr('rel', 'newpage');
});

// default values for fields
var defaultValues = {};

/**
 * Returns field type
 *
 * @param {Element} field
 *
 * @return {string}
 */
function getFieldType (field) {
    var $field = $(field);
    var tagName = $field.prop('tagName');
    if (tagName === 'INPUT') {
        return $field.attr('type');
    } else if (tagName === 'SELECT') {
        return 'select';
    } else if (tagName === 'TEXTAREA') {
        return 'text';
    }
    return '';
}

/**
 * Enables or disables the "restore default value" button
 *
 * @param {Element} field
 * @param {boolean} display
 *
 * @return {void}
 */
function setRestoreDefaultBtn (field, display) {
    var $el = $(field).closest('td').find('.restore-default img');
    $el[display ? 'show' : 'hide']();
}

/**
 * Marks field depending on its value (system default or custom)
 *
 * @param {Element | JQuery<Element>} field
 *
 * @return {void}
 */
function markField (field) {
    var $field = $(field);
    var type = getFieldType($field);
    var isDefault = checkFieldDefault($field, type);

    // checkboxes uses parent <span> for marking
    var $fieldMarker = (type === 'checkbox') ? $field.parent() : $field;
    setRestoreDefaultBtn($field, !isDefault);
    $fieldMarker[isDefault ? 'removeClass' : 'addClass']('custom');
}

/**
 * Sets field value
 *
 * value must be of type:
 * o undefined (omitted) - restore default value (form default, not PMA default)
 * o String - if field_type is 'text'
 * o boolean - if field_type is 'checkbox'
 * o Array of values - if field_type is 'select'
 *
 * @param {Element} field
 * @param {string}  fieldType see {@link #getFieldType}
 * @param {string | boolean}  value
 */
function setFieldValue (field, fieldType, value) {
    var $field = $(field);
    switch (fieldType) {
    case 'text':
    case 'number':
        $field.val(value);
        break;
    case 'checkbox':
        $field.prop('checked', value);
        break;
    case 'select':
        var options = $field.prop('options');
        var i;
        var imax = options.length;
        for (i = 0; i < imax; i++) {
            options[i].selected = (value.indexOf(options[i].value) !== -1);
        }
        break;
    }
    markField($field);
}

/**
 * Gets field value
 *
 * Will return one of:
 * o String - if type is 'text'
 * o boolean - if type is 'checkbox'
 * o Array of values - if type is 'select'
 *
 * @param {Element} field
 * @param {string}  fieldType returned by {@link #getFieldType}
 *
 * @return {boolean | string | string[] | null}
 */
function getFieldValue (field, fieldType) {
    var $field = $(field);
    switch (fieldType) {
    case 'text':
    case 'number':
        return $field.prop('value');
    case 'checkbox':
        return $field.prop('checked');
    case 'select':
        var options = $field.prop('options');
        var i;
        var imax = options.length;
        var items = [];
        for (i = 0; i < imax; i++) {
            if (options[i].selected) {
                items.push(options[i].value);
            }
        }
        return items;
    }
    return null;
}

/**
 * Returns values for all fields in fieldsets
 *
 * @return {object}
 */
// eslint-disable-next-line no-unused-vars
function getAllValues () {
    var $elements = $('fieldset input, fieldset select, fieldset textarea');
    var values = {};
    var type;
    var value;
    for (var i = 0; i < $elements.length; i++) {
        type = getFieldType($elements[i]);
        value = getFieldValue($elements[i], type);
        if (typeof value !== 'undefined') {
            // we only have single selects, fatten array
            if (type === 'select') {
                value = value[0];
            }
            values[$elements[i].name] = value;
        }
    }
    return values;
}

/**
 * Checks whether field has its default value
 *
 * @param {Element} field
 * @param {string}  type
 *
 * @return {boolean}
 */
function checkFieldDefault (field, type) {
    var $field = $(field);
    var fieldId = $field.attr('id');
    if (typeof defaultValues[fieldId] === 'undefined') {
        return true;
    }
    var isDefault = true;
    var currentValue = getFieldValue($field, type);
    if (type !== 'select') {
        isDefault = currentValue === defaultValues[fieldId];
    } else {
        // compare arrays, will work for our representation of select values
        if (currentValue.length !== defaultValues[fieldId].length) {
            isDefault = false;
        } else {
            for (var i = 0; i < currentValue.length; i++) {
                if (currentValue[i] !== defaultValues[fieldId][i]) {
                    isDefault = false;
                    break;
                }
            }
        }
    }
    return isDefault;
}

/**
 * Returns element's id prefix
 * @param {Element} element
 *
 * @return {string}
 */
// eslint-disable-next-line no-unused-vars
function getIdPrefix (element) {
    return $(element).attr('id').replace(/[^-]+$/, '');
}

// ------------------------------------------------------------------
// Form validation and field operations
//

// form validator assignments
var validate = {};

// form validator list
var validators = {
    // regexp: numeric value
    regExpNumeric: /^[0-9]+$/,
    // regexp: extract parts from PCRE expression
    regExpPcreExtract: /(.)(.*)\1(.*)?/,
    /**
     * Validates positive number
     *
     * @param {boolean} isKeyUp
     *
     * @return {boolean}
     */
    validatePositiveNumber: function (isKeyUp) {
        if (isKeyUp && this.value === '') {
            return true;
        }
        var result = this.value !== '0' && validators.regExpNumeric.test(this.value);
        return result ? true : Messages.error_nan_p;
    },
    /**
     * Validates non-negative number
     *
     * @param {boolean} isKeyUp
     *
     * @return {boolean}
     */
    validateNonNegativeNumber: function (isKeyUp) {
        if (isKeyUp && this.value === '') {
            return true;
        }
        var result = validators.regExpNumeric.test(this.value);
        return result ? true : Messages.error_nan_nneg;
    },
    /**
     * Validates port number
     *
     * @return {true|string}
     */
    validatePortNumber: function () {
        if (this.value === '') {
            return true;
        }
        var result = validators.regExpNumeric.test(this.value) && this.value !== '0';
        return result && this.value <= 65535 ? true : Messages.error_incorrect_port;
    },
    /**
     * Validates value according to given regular expression
     *
     * @param {boolean} isKeyUp
     * @param {string}  regexp
     *
     * @return {true|string}
     */
    validateByRegex: function (isKeyUp, regexp) {
        if (isKeyUp && this.value === '') {
            return true;
        }
        // convert PCRE regexp
        var parts = regexp.match(validators.regExpPcreExtract);
        var valid = this.value.match(new RegExp(parts[2], parts[3])) !== null;
        return valid ? true : Messages.error_invalid_value;
    },
    /**
     * Validates upper bound for numeric inputs
     *
     * @param {boolean} isKeyUp
     * @param {number} maxValue
     *
     * @return {true|string}
     */
    validateUpperBound: function (isKeyUp, maxValue) {
        var val = parseInt(this.value, 10);
        if (isNaN(val)) {
            return true;
        }
        return val <= maxValue ? true : Functions.sprintf(Messages.error_value_lte, maxValue);
    },
    // field validators
    field: {
    },
    // fieldset validators
    fieldset: {
    }
};

/**
 * Registers validator for given field
 *
 * @param {string}  id       field id
 * @param {string}  type     validator (key in validators object)
 * @param {boolean} onKeyUp  whether fire on key up
 * @param {Array}   params   validation function parameters
 */
// eslint-disable-next-line no-unused-vars
function registerFieldValidator (id, type, onKeyUp, params) {
    if (typeof validators[type] === 'undefined') {
        return;
    }
    if (typeof validate[id] === 'undefined') {
        validate[id] = [];
    }
    if (validate[id].length === 0) {
        validate[id].push([type, params, onKeyUp]);
    }
}

/**
 * Returns validation functions associated with form field
 *
 * @param {String}  fieldId     form field id
 * @param {boolean} onKeyUpOnly see registerFieldValidator
 *
 * @return {any[]} of [function, parameters to be passed to function]
 */
function getFieldValidators (fieldId, onKeyUpOnly) {
    // look for field bound validator
    var name = fieldId && fieldId.match(/[^-]+$/)[0];
    if (typeof validators.field[name] !== 'undefined') {
        return [[validators.field[name], null]];
    }

    // look for registered validators
    var functions = [];
    if (typeof validate[fieldId] !== 'undefined') {
        // validate[field_id]: array of [type, params, onKeyUp]
        for (var i = 0, imax = validate[fieldId].length; i < imax; i++) {
            if (onKeyUpOnly && !validate[fieldId][i][2]) {
                continue;
            }
            functions.push([validators[validate[fieldId][i][0]], validate[fieldId][i][1]]);
        }
    }

    return functions;
}

/**
 * Displays errors for given form fields
 *
 * WARNING: created DOM elements must be identical with the ones made by
 * PhpMyAdmin\Config\FormDisplayTemplate::displayInput()!
 *
 * @param {object} errorList list of errors in the form {field id: error array}
 */
function displayErrors (errorList) {
    var tempIsEmpty = function (item) {
        return item !== '';
    };

    for (var fieldId in errorList) {
        var errors = errorList[fieldId];
        var $field = $('#' + fieldId);
        var isFieldset = $field.attr('tagName') === 'FIELDSET';
        var $errorCnt;
        if (isFieldset) {
            $errorCnt = $field.find('dl.errors');
        } else {
            $errorCnt = $field.siblings('.inline_errors');
        }

        // remove empty errors (used to clear error list)
        errors = $.grep(errors, tempIsEmpty);

        // CSS error class
        if (!isFieldset) {
            // checkboxes uses parent <span> for marking
            var $fieldMarker = ($field.attr('type') === 'checkbox') ? $field.parent() : $field;
            $fieldMarker[errors.length ? 'addClass' : 'removeClass']('field-error');
        }

        if (errors.length) {
            // if error container doesn't exist, create it
            if ($errorCnt.length === 0) {
                if (isFieldset) {
                    $errorCnt = $('<dl class="errors"></dl>');
                    $field.find('table').before($errorCnt);
                } else {
                    $errorCnt = $('<dl class="inline_errors"></dl>');
                    $field.closest('td').append($errorCnt);
                }
            }

            var html = '';
            for (var i = 0, imax = errors.length; i < imax; i++) {
                html += '<dd>' + errors[i] + '</dd>';
            }
            $errorCnt.html(html);
        } else if ($errorCnt !== null) {
            // remove useless error container
            $errorCnt.remove();
        }
    }
}

/**
 * Validates fields and fieldsets and call displayError function as required
 */
function setDisplayError () {
    var elements = $('.optbox input[id], .optbox select[id], .optbox textarea[id]');
    // run all field validators
    var errors = {};
    for (var i = 0; i < elements.length; i++) {
        validateField(elements[i], false, errors);
    }
    // run all fieldset validators
    $('fieldset.optbox').each(function () {
        validateFieldset(this, false, errors);
    });
    displayErrors(errors);
}

/**
 * Validates fieldset and puts errors in 'errors' object
 *
 * @param {Element} fieldset
 * @param {boolean} isKeyUp
 * @param {object}  errors
 */
function validateFieldset (fieldset, isKeyUp, errors) {
    var $fieldset = $(fieldset);
    if ($fieldset.length && typeof validators.fieldset[$fieldset.attr('id')] !== 'undefined') {
        var fieldsetErrors = validators.fieldset[$fieldset.attr('id')].apply($fieldset[0], [isKeyUp]);
        for (var fieldId in fieldsetErrors) {
            if (typeof errors[fieldId] === 'undefined') {
                errors[fieldId] = [];
            }
            if (typeof fieldsetErrors[fieldId] === 'string') {
                fieldsetErrors[fieldId] = [fieldsetErrors[fieldId]];
            }
            $.merge(errors[fieldId], fieldsetErrors[fieldId]);
        }
    }
}

/**
 * Validates form field and puts errors in 'errors' object
 *
 * @param {Element} field
 * @param {boolean} isKeyUp
 * @param {object}  errors
 */
function validateField (field, isKeyUp, errors) {
    var args;
    var result;
    var $field = $(field);
    var fieldId = $field.attr('id');
    errors[fieldId] = [];
    var functions = getFieldValidators(fieldId, isKeyUp);
    for (var i = 0; i < functions.length; i++) {
        if (typeof functions[i][1] !== 'undefined' && functions[i][1] !== null) {
            args = functions[i][1].slice(0);
        } else {
            args = [];
        }
        args.unshift(isKeyUp);
        result = functions[i][0].apply($field[0], args);
        if (result !== true) {
            if (typeof result === 'string') {
                result = [result];
            }
            $.merge(errors[fieldId], result);
        }
    }
}

/**
 * Validates form field and parent fieldset
 *
 * @param {Element} field
 * @param {boolean} isKeyUp
 */
function validateFieldAndFieldset (field, isKeyUp) {
    var $field = $(field);
    var errors = {};
    validateField($field, isKeyUp, errors);
    validateFieldset($field.closest('fieldset.optbox'), isKeyUp, errors);
    displayErrors(errors);
}

function loadInlineConfig () {
    if (!Array.isArray(configInlineParams)) {
        return;
    }
    for (var i = 0; i < configInlineParams.length; ++i) {
        if (typeof configInlineParams[i] === 'function') {
            configInlineParams[i]();
        }
    }
}

function setupValidation () {
    validate = {};
    configScriptLoaded = true;
    if (configScriptLoaded && typeof configInlineParams !== 'undefined') {
        loadInlineConfig();
    }
    // register validators and mark custom values
    var $elements = $('.optbox input[id], .optbox select[id], .optbox textarea[id]');
    $elements.each(function () {
        markField(this);
        var $el = $(this);
        $el.on('change', function () {
            validateFieldAndFieldset(this, false);
            markField(this);
        });
        var tagName = $el.attr('tagName');
        // text fields can be validated after each change
        if (tagName === 'INPUT' && $el.attr('type') === 'text') {
            $el.on('keyup', function () {
                validateFieldAndFieldset($el, true);
                markField($el);
            });
        }
        // disable textarea spellcheck
        if (tagName === 'TEXTAREA') {
            $el.attr('spellcheck', false);
        }
    });

    // check whether we've refreshed a page and browser remembered modified
    // form values
    var $checkPageRefresh = $('#check_page_refresh');
    if ($checkPageRefresh.length === 0 || $checkPageRefresh.val() === '1') {
        // run all field validators
        var errors = {};
        for (var i = 0; i < $elements.length; i++) {
            validateField($elements[i], false, errors);
        }
        // run all fieldset validators
        $('fieldset.optbox').each(function () {
            validateFieldset(this, false, errors);
        });

        displayErrors(errors);
    } else if ($checkPageRefresh) {
        $checkPageRefresh.val('1');
    }
}

AJAX.registerOnload('config.js', function () {
    setupValidation();
});

//
// END: Form validation and field operations
// ------------------------------------------------------------------

function adjustPrefsNotification () {
    var $prefsAutoLoad = $('#prefs_autoload');
    var $tableNameControl = $('#table_name_col_no');
    var $prefsAutoShowing = ($prefsAutoLoad.css('display') !== 'none');

    if ($prefsAutoShowing && $tableNameControl.length) {
        $tableNameControl.css('top', '55px');
    }
}

AJAX.registerOnload('config.js', function () {
    adjustPrefsNotification();
});

// ------------------------------------------------------------------
// Form reset buttons
//

AJAX.registerOnload('config.js', function () {
    $('.optbox input[type=button][name=submit_reset]').on('click', function () {
        var fields = $(this).closest('fieldset').find('input, select, textarea');
        for (var i = 0, imax = fields.length; i < imax; i++) {
            setFieldValue(fields[i], getFieldType(fields[i]), defaultValues[fields[i].id]);
        }
        setDisplayError();
    });
});

//
// END: Form reset buttons
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// "Restore default" and "set value" buttons
//

/**
 * Restores field's default value
 *
 * @param {string} fieldId
 *
 * @return {void}
 */
function restoreField (fieldId) {
    var $field = $('#' + fieldId);
    if ($field.length === 0 || defaultValues[fieldId] === undefined) {
        return;
    }
    setFieldValue($field, getFieldType($field), defaultValues[fieldId]);
}

function setupRestoreField () {
    $('div.tab-content')
        .on('mouseenter', '.restore-default, .set-value', function () {
            $(this).css('opacity', 1);
        })
        .on('mouseleave', '.restore-default, .set-value', function () {
            $(this).css('opacity', 0.25);
        })
        .on('click', '.restore-default, .set-value', function (e) {
            e.preventDefault();
            var href = $(this).attr('href');
            var fieldSel;
            if ($(this).hasClass('restore-default')) {
                fieldSel = href;
                restoreField(fieldSel.substr(1));
            } else {
                fieldSel = href.match(/^[^=]+/)[0];
                var value = href.match(/=(.+)$/)[1];
                setFieldValue($(fieldSel), 'text', value);
            }
            $(fieldSel).trigger('change');
        })
        .find('.restore-default, .set-value')
        // inline-block for IE so opacity inheritance works
        .css({ display: 'inline-block', opacity: 0.25 });
}

AJAX.registerOnload('config.js', function () {
    setupRestoreField();
});

//
// END: "Restore default" and "set value" buttons
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// User preferences import/export
//

AJAX.registerOnload('config.js', function () {
    offerPrefsAutoimport();
    var $radios = $('#import_local_storage, #export_local_storage');
    if (!$radios.length) {
        return;
    }

    // enable JavaScript dependent fields
    $radios
        .prop('disabled', false)
        .add('#export_text_file, #import_text_file')
        .on('click', function () {
            var enableId = $(this).attr('id');
            var disableId;
            if (enableId.match(/local_storage$/)) {
                disableId = enableId.replace(/local_storage$/, 'text_file');
            } else {
                disableId = enableId.replace(/text_file$/, 'local_storage');
            }
            $('#opts_' + disableId).addClass('disabled').find('input').prop('disabled', true);
            $('#opts_' + enableId).removeClass('disabled').find('input').prop('disabled', false);
        });

    // detect localStorage state
    var lsSupported = isStorageSupported('localStorage', true);
    var lsExists = lsSupported ? (window.localStorage.config || false) : false;
    $('div.localStorage-' + (lsSupported ? 'un' : '') + 'supported').hide();
    $('div.localStorage-' + (lsExists ? 'empty' : 'exists')).hide();
    if (lsExists) {
        updatePrefsDate();
    }
    $('form.prefs-form').on('change', function () {
        var $form = $(this);
        var disabled = false;
        if (!lsSupported) {
            disabled = $form.find('input[type=radio][value$=local_storage]').prop('checked');
        } else if (!lsExists && $form.attr('name') === 'prefs_import' &&
            $('#import_local_storage')[0].checked
        ) {
            disabled = true;
        }
        $form.find('input[type=submit]').prop('disabled', disabled);
    }).on('submit', function (e) {
        var $form = $(this);
        if ($form.attr('name') === 'prefs_export' && $('#export_local_storage')[0].checked) {
            e.preventDefault();
            // use AJAX to read JSON settings and save them
            savePrefsToLocalStorage($form);
        } else if ($form.attr('name') === 'prefs_import' && $('#import_local_storage')[0].checked) {
            // set 'json' input and submit form
            $form.find('input[name=json]').val(window.localStorage.config);
        }
    });

    $(document).on('click', 'div.click-hide-message', function () {
        $(this).hide();
        $(this).parent('.card-body').css('height', '');
        $(this).parent('.card-body').find('.prefs-form').show();
    });
});

/**
 * Saves user preferences to localStorage
 *
 * @param {Element} form
 */
function savePrefsToLocalStorage (form) {
    var $form = $(form);
    var submit = $form.find('input[type=submit]');
    submit.prop('disabled', true);
    $.ajax({
        url: 'index.php?route=/preferences/manage',
        cache: false,
        type: 'POST',
        data: {
            'ajax_request': true,
            'server': CommonParams.get('server'),
            'submit_get_json': true
        },
        success: function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                window.localStorage.config = data.prefs;
                window.localStorage.configMtime = data.mtime;
                window.localStorage.configMtimeLocal = (new Date()).toUTCString();
                updatePrefsDate();
                $('div.localStorage-empty').hide();
                $('div.localStorage-exists').show();
                var group = $form.parent('.card-body');
                group.css('height', group.height() + 'px');
                $form.hide('fast');
                $form.prev('.click-hide-message').show('fast');
            } else {
                Functions.ajaxShowMessage(data.error);
            }
        },
        complete: function () {
            submit.prop('disabled', false);
        }
    });
}

/**
 * Updates preferences timestamp in Import form
 */
function updatePrefsDate () {
    var d = new Date(window.localStorage.configMtimeLocal);
    var msg = Messages.strSavedOn.replace(
        '@DATE@',
        Functions.formatDateTime(d)
    );
    $('#opts_import_local_storage').find('div.localStorage-exists').html(msg);
}

/**
 * Prepares message which informs that localStorage preferences are available and can be imported or deleted
 */
function offerPrefsAutoimport () {
    var hasConfig = (isStorageSupported('localStorage')) && (window.localStorage.config || false);
    var $cnt = $('#prefs_autoload');
    if (!$cnt.length || !hasConfig) {
        return;
    }
    $cnt.find('a').on('click', function (e) {
        e.preventDefault();
        var $a = $(this);
        if ($a.attr('href') === '#no') {
            $cnt.remove();
            $.post('index.php', {
                'server': CommonParams.get('server'),
                'prefs_autoload': 'hide'
            }, null, 'html');
            return;
        } else if ($a.attr('href') === '#delete') {
            $cnt.remove();
            localStorage.clear();
            $.post('index.php', {
                'server': CommonParams.get('server'),
                'prefs_autoload': 'hide'
            }, null, 'html');
            return;
        }
        $cnt.find('input[name=json]').val(window.localStorage.config);
        $cnt.find('form').trigger('submit');
    });
    $cnt.show();
}

/**
 * @type {boolean} Support for passive event listener option
 */
var PASSIVE_EVENT_LISTENERS = (function () {
    var passive = false;
    try {
        var options = Object.defineProperty({}, 'passive', {
            get: function () {
                return (passive = true);
            },
        });

        window.addEventListener('_', null, options);
        window.removeEventListener('_', null, options);
    } catch (error) {
        // passive not supported
    }
    return passive;
}());
