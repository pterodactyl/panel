/**
 * @fileoverview    function used in table data manipulation pages
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/* global extendingValidatorMessages */ // templates/javascript/variables.twig
/* global openGISEditor, gisEditorLoaded, loadJSAndGISEditor, loadGISEditor */ // js/gis_data_editor.js

/**
 * Modify form controls when the "NULL" checkbox is checked
 *
 * @param {string} theType   the MySQL field type
 * @param {string} urlField  the urlencoded field name - OBSOLETE
 * @param {string} md5Field  the md5 hashed field name
 * @param {string} multiEdit the multi_edit row sequence number
 *
 * @return {boolean} always true
 */
function nullify (theType, urlField, md5Field, multiEdit) {
    var rowForm = document.forms.insertForm;

    if (typeof(rowForm.elements['funcs' + multiEdit + '[' + md5Field + ']']) !== 'undefined') {
        rowForm.elements['funcs' + multiEdit + '[' + md5Field + ']'].selectedIndex = -1;
    }

    // "ENUM" field with more than 20 characters
    if (Number(theType) === 1) {
        rowForm.elements['fields' + multiEdit + '[' + md5Field +  ']'][1].selectedIndex = -1;
    // Other "ENUM" field
    } else if (Number(theType) === 2) {
        var elts     = rowForm.elements['fields' + multiEdit + '[' + md5Field + ']'];
        // when there is just one option in ENUM:
        if (elts.checked) {
            elts.checked = false;
        } else {
            var eltsCnt = elts.length;
            for (var i = 0; i < eltsCnt; i++) {
                elts[i].checked = false;
            } // end for
        } // end if
    // "SET" field
    } else if (Number(theType) === 3) {
        rowForm.elements['fields' + multiEdit + '[' + md5Field +  '][]'].selectedIndex = -1;
    // Foreign key field (drop-down)
    } else if (Number(theType) === 4) {
        rowForm.elements['fields' + multiEdit + '[' + md5Field +  ']'].selectedIndex = -1;
    // foreign key field (with browsing icon for foreign values)
    } else if (Number(theType) === 6) {
        rowForm.elements['fields' + multiEdit + '[' + md5Field + ']'].value = '';
    // Other field types
    } else /* if (theType === 5)*/ {
        rowForm.elements['fields' + multiEdit + '[' + md5Field + ']'].value = '';
    } // end if... else if... else

    return true;
} // end of the 'nullify()' function


/**
 * javascript DateTime format validation.
 * its used to prevent adding default (0000-00-00 00:00:00) to database when user enter wrong values
 * Start of validation part
 */
// function checks the number of days in febuary
function daysInFebruary (year) {
    return (((year % 4 === 0) && (((year % 100 !== 0)) || (year % 400 === 0))) ? 29 : 28);
}
// function to convert single digit to double digit
function fractionReplace (number) {
    var num = parseInt(number, 10);
    return num >= 1 && num <= 9 ? '0' + num : '00';
}

/* function to check the validity of date
* The following patterns are accepted in this validation (accepted in mysql as well)
* 1) 2001-12-23
* 2) 2001-1-2
* 3) 02-12-23
* 4) And instead of using '-' the following punctuations can be used (+,.,*,^,@,/) All these are accepted by mysql as well. Therefore no issues
*/
function isDate (val, tmstmp) {
    var value = val.replace(/[.|*|^|+|//|@]/g, '-');
    var arrayVal = value.split('-');
    for (var a = 0; a < arrayVal.length; a++) {
        if (arrayVal[a].length === 1) {
            arrayVal[a] = fractionReplace(arrayVal[a]);
        }
    }
    value = arrayVal.join('-');
    var pos = 2;
    var dtexp = new RegExp(/^([0-9]{4})-(((01|03|05|07|08|10|12)-((0[0-9])|([1-2][0-9])|(3[0-1])))|((02|04|06|09|11)-((0[0-9])|([1-2][0-9])|30))|((00)-(00)))$/);
    if (value.length === 8) {
        pos = 0;
    }
    if (dtexp.test(value)) {
        var month = parseInt(value.substring(pos + 3, pos + 5), 10);
        var day = parseInt(value.substring(pos + 6, pos + 8), 10);
        var year = parseInt(value.substring(0, pos + 2), 10);
        if (month === 2 && day > daysInFebruary(year)) {
            return false;
        }
        if (value.substring(0, pos + 2).length === 2) {
            year = parseInt('20' + value.substring(0, pos + 2), 10);
        }
        if (tmstmp === true) {
            if (year < 1978) {
                return false;
            }
            if (year > 2038 || (year > 2037 && day > 19 && month >= 1) || (year > 2037 && month > 1)) {
                return false;
            }
        }
    } else {
        return false;
    }
    return true;
}

/* function to check the validity of time
* The following patterns are accepted in this validation (accepted in mysql as well)
* 1) 2:3:4
* 2) 2:23:43
* 3) 2:23:43.123456
*/
function isTime (val) {
    var arrayVal = val.split(':');
    for (var a = 0, l = arrayVal.length; a < l; a++) {
        if (arrayVal[a].length === 1) {
            arrayVal[a] = fractionReplace(arrayVal[a]);
        }
    }
    var newVal = arrayVal.join(':');
    var tmexp = new RegExp(/^(-)?(([0-7]?[0-9][0-9])|(8[0-2][0-9])|(83[0-8])):((0[0-9])|([1-5][0-9])):((0[0-9])|([1-5][0-9]))(\.[0-9]{1,6}){0,1}$/);
    return tmexp.test(newVal);
}

/**
 * To check whether insert section is ignored or not
 * @param {string} multiEdit
 * @return {boolean}
 */
function checkForCheckbox (multiEdit) {
    if ($('#insert_ignore_' + multiEdit).length) {
        return $('#insert_ignore_' + multiEdit).is(':unchecked');
    }
    return true;
}

// used in Search page mostly for INT fields
// eslint-disable-next-line no-unused-vars
function verifyAfterSearchFieldChange (index, searchFormId) {
    var $thisInput = $('input[name=\'criteriaValues[' + index + ']\']');
    // Add  data-skip-validators attribute to skip validation in changeValueFieldType function
    if ($('#fieldID_' + index).data('data-skip-validators')) {
        $(searchFormId).validate().destroy();
        return;
    }
    // validation for integer type
    if ($thisInput.data('type') === 'INT' ||
        $thisInput.data('type') === 'TINYINT') {
        // Trim spaces if it's an integer
        $thisInput.val($thisInput.val().trim());

        var hasMultiple = $thisInput.prop('multiple');

        if (hasMultiple) {
            $(searchFormId).validate({
                // update errors as we write
                onkeyup: function (element) {
                    $(element).valid();
                }
            });
            // validator method for IN(...), NOT IN(...)
            // BETWEEN and NOT BETWEEN
            // See all possible syntaxes in tests of https://regexr.com/7h1eq
            jQuery.validator.addMethod('validationFunctionForMultipleInt', function (value) {
                if (value === '') {
                    return true;
                }
                return value.replace(/ /g,'').match(/^(((0x[0-9a-f]+)|([+-]?([0-9]*\.?[0-9]+|[0-9]+\.?[0-9]*)(e[+-]?[0-9]+)?))(,|$))+$/i) !== null;
            },
            Messages.strEnterValidNumber
            );
            validateMultipleIntField($thisInput, true);
        } else {
            // validator method for INTs
            // See all possible syntaxes in tests of https://regexr.com/7h1ci
            jQuery.validator.addMethod('validationFunctionForInt', function (value) {
                if (value === '') {
                    return true;
                }

                return value.match(/^(0x[0-9a-f]+$)|([+-]?([0-9]*\.?[0-9]+|[0-9]+\.?[0-9]*)(e[+-]?[0-9]+)?)$/i) !== null;
            },
            Messages.strEnterValidNumber
            );
            $(searchFormId).validate({
                // update errors as we write
                onkeyup: function (element) {
                    $(element).valid();
                }
            });
            validateIntField($thisInput, true);
        }
        // Update error on dropdown change
        $thisInput.valid();
    }
}

/**
 * Validate the an input contains multiple int values
 * @param {jQuery} jqueryInput the Jquery object
 * @param {boolean} returnValueIfFine the value to return if the validator passes
 * @return {void}
 */
function validateMultipleIntField (jqueryInput, returnValueIfFine) {
    // removing previous rules
    jqueryInput.rules('remove');

    jqueryInput.rules('add', {
        validationFunctionForMultipleInt: {
            param: jqueryInput.value,
            depends: function () {
                return returnValueIfFine;
            }
        }
    });
}

/**
 * Validate the an input contains an int value
 * @param {jQuery} jqueryInput the Jquery object
 * @param {boolean} returnValueIfIsNumber the value to return if the validator passes
 * @return {void}
 */
function validateIntField (jqueryInput, returnValueIfIsNumber) {
    var mini = parseInt(jqueryInput.data('min'));
    var maxi = parseInt(jqueryInput.data('max'));
    // removing previous rules
    jqueryInput.rules('remove');

    jqueryInput.rules('add', {
        validationFunctionForInt: {
            param: jqueryInput.value,
            depends: function () {
                return returnValueIfIsNumber;
            }
        },
        min: {
            param: mini,
            depends: function () {
                if (isNaN(jqueryInput.val())) {
                    return false;
                } else {
                    return returnValueIfIsNumber;
                }
            }
        },
        max: {
            param: maxi,
            depends: function () {
                if (isNaN(jqueryInput.val())) {
                    return false;
                } else {
                    return returnValueIfIsNumber;
                }
            }
        }
    });
}

function verificationsAfterFieldChange (urlField, multiEdit, theType) {
    var evt = window.event || arguments.callee.caller.arguments[0];
    var target = evt.target || evt.srcElement;
    var $thisInput = $(':input[name^=\'fields[multi_edit][' + multiEdit + '][' +
        urlField + ']\']');
    // the function drop-down that corresponds to this input field
    var $thisFunction = $('select[name=\'funcs[multi_edit][' + multiEdit + '][' +
        urlField + ']\']');
    var functionSelected = false;
    if (typeof $thisFunction.val() !== 'undefined' &&
        $thisFunction.val() !== null &&
        $thisFunction.val().length > 0
    ) {
        functionSelected = true;
    }

    // To generate the textbox that can take the salt
    var newSaltBox = '<br><input type=text name=salt[multi_edit][' + multiEdit + '][' + urlField + ']' +
        ' id=salt_' + target.id + ' placeholder=\'' + Messages.strEncryptionKey + '\'>';

    // If encrypting or decrypting functions that take salt as input is selected append the new textbox for salt
    if (target.value === 'AES_ENCRYPT' ||
            target.value === 'AES_DECRYPT' ||
            target.value === 'DES_ENCRYPT' ||
            target.value === 'DES_DECRYPT' ||
            target.value === 'ENCRYPT') {
        if (!($('#salt_' + target.id).length)) {
            $thisInput.after(newSaltBox);
        }
    } else {
        // Remove the textbox for salt
        $('#salt_' + target.id).prev('br').remove();
        $('#salt_' + target.id).remove();
    }

    var couldFetchRules = false;
    try {
        // See: issue #18792 - In some weird cases the input goes away before it validates
        // And it breaks jquery, this is a well known jquery bug with different trigger schemes
        $('#' + target.id).rules();
        couldFetchRules = true;
    } catch (error) {
        console.log(error);
    }

    if (couldFetchRules) {
        // Remove possible blocking rules if the user changed functions
        $('#' + target.id).rules('remove', 'validationFunctionForMd5');
        $('#' + target.id).rules('remove', 'validationFunctionForAesDesEncrypt');

        if (target.value === 'MD5') {
            $('#' + target.id).rules('add', {
                validationFunctionForMd5: {
                    param: $thisInput,
                    depends: function () {
                        return checkForCheckbox(multiEdit);
                    }
                }
            });
        }

        if (target.value === 'DES_ENCRYPT' || target.value === 'AES_ENCRYPT') {
            $('#' + target.id).rules('add', {
                validationFunctionForAesDesEncrypt: {
                    param: $thisInput,
                    depends: function () {
                        return checkForCheckbox(multiEdit);
                    }
                }
            });
        }
    }

    if (target.value === 'HEX' && theType.substring(0,3) === 'int') {
        // Add note when HEX function is selected on a int
        var newHexInfo = '<br><p id="note' +  target.id + '">' + Messages.HexConversionInfo + '</p>';
        if (!$('#note' + target.id).length) {
            $thisInput.after(newHexInfo);
        }
    } else {
        $('#note' + target.id)
            .prev('br')
            .remove();
        $('#note' + target.id).remove();
    }
    // Unchecks the corresponding "NULL" control
    $('input[name=\'fields_null[multi_edit][' + multiEdit + '][' + urlField + ']\']').prop('checked', false);

    // Unchecks the Ignore checkbox for the current row
    $('input[name=\'insert_ignore_' + multiEdit + '\']').prop('checked', false);

    var charExceptionHandling;
    if (theType.substring(0,4) === 'char') {
        charExceptionHandling = theType.substring(5,6);
    } else if (theType.substring(0,7) === 'varchar') {
        charExceptionHandling = theType.substring(8,9);
    }
    if (functionSelected) {
        $thisInput.removeAttr('min');
        $thisInput.removeAttr('max');
        // @todo: put back attributes if corresponding function is deselected

        if ($thisFunction.val() === 'NOW') {
            $thisInput.attr('min', 0);
            $thisInput.attr('max', $thisInput.parent().attr('data-decimals'));
        }
    }

    if ($thisInput.data('rulesadded') === null && ! functionSelected) {
        // call validate before adding rules
        $($thisInput[0].form).validate();
        // validate for date time
        if (theType === 'datetime' || theType === 'time' || theType === 'date' || theType === 'timestamp') {
            $thisInput.rules('add', {
                validationFunctionForDateTime: {
                    param: theType,
                    depends: function () {
                        return checkForCheckbox(multiEdit);
                    }
                }
            });
        }
        // validation for integer type
        if ($thisInput.data('type') === 'INT') {
            validateIntField($thisInput, checkForCheckbox(multiEdit));
            // validation for CHAR types
        } else if ($thisInput.data('type') === 'CHAR') {
            var maxlen = $thisInput.data('maxlength');
            if (typeof maxlen !== 'undefined') {
                if (maxlen <= 4) {
                    maxlen = charExceptionHandling;
                }
                $thisInput.rules('add', {
                    maxlength: {
                        param: maxlen,
                        depends: function () {
                            return checkForCheckbox(multiEdit);
                        }
                    }
                });
            }
            // validate binary & blob types
        } else if ($thisInput.data('type') === 'HEX') {
            $thisInput.rules('add', {
                validationFunctionForHex: {
                    param: true,
                    depends: function () {
                        return checkForCheckbox(multiEdit);
                    }
                }
            });
        }
        $thisInput.data('rulesadded', true);
    } else if ($thisInput.data('rulesadded') === true && functionSelected) {
        // remove any rules added
        $thisInput.rules('remove');
        // remove any error messages
        $thisInput
            .removeClass('error')
            .removeAttr('aria-invalid')
            .siblings('.error')
            .remove();
        $thisInput.data('rulesadded', null);
    }
}
/* End of fields validation*/

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('table/change.js', function () {
    $(document).off('click', 'span.open_gis_editor');
    $(document).off('click', 'input[name^=\'insert_ignore_\']');
    $(document).off('click', 'input[name=\'gis_data[save]\']');
    $(document).off('click', 'input.checkbox_null');
    $('select[name="submit_type"]').off('change');
    $(document).off('change', '#insert_rows');
});

/**
 * Ajax handlers for Change Table page
 *
 * Actions Ajaxified here:
 * Submit Data to be inserted into the table.
 * Restart insertion with 'N' rows.
 */
AJAX.registerOnload('table/change.js', function () {
    if ($('#insertForm').length) {
        // validate the comment form when it is submitted
        $('#insertForm').validate();
        jQuery.validator.addMethod('validationFunctionForHex', function (value) {
            return value.match(/^[a-f0-9]*$/i) !== null;
        });

        jQuery.validator.addMethod('validationFunctionForMd5', function (value, element, options) {
            return !(value.substring(0, 3) === 'MD5' &&
                typeof options.data('maxlength') !== 'undefined' &&
                options.data('maxlength') < 32);
        });

        jQuery.validator.addMethod('validationFunctionForAesDesEncrypt', function (value, element, options) {
            var funType = value.substring(0, 3);
            if (funType !== 'AES' && funType !== 'DES') {
                return false;
            }

            var dataType = options.data('type');

            if (dataType === 'HEX' || dataType === 'CHAR') {
                return true;
            }

            return false;
        });

        jQuery.validator.addMethod('validationFunctionForDateTime', function (value, element, options) {
            var dtValue = value;
            var theType = options;
            if (theType === 'date') {
                return isDate(dtValue);
            } else if (theType === 'time') {
                return isTime(dtValue);
            } else if (theType === 'datetime' || theType === 'timestamp') {
                var tmstmp = false;
                dtValue = dtValue.trim();
                if (dtValue === 'CURRENT_TIMESTAMP' || dtValue === 'current_timestamp()') {
                    return true;
                }
                if (theType === 'timestamp') {
                    tmstmp = true;
                }
                if (dtValue === '0000-00-00 00:00:00') {
                    return true;
                }
                var dv = dtValue.indexOf(' ');
                if (dv === -1) { // Only the date component, which is valid
                    return isDate(dtValue, tmstmp);
                }

                return isDate(dtValue.substring(0, dv), tmstmp) &&
                    isTime(dtValue.substring(dv + 1));
            }
        });
    }

    /*
     * message extending script must be run
     * after initiation of functions
     */
    extendingValidatorMessages();

    $.datepicker.initialized = false;

    $(document).on('click', 'span.open_gis_editor', function (event) {
        event.preventDefault();

        var $span = $(this);
        // Current value
        var value = $span.parent('td').children('input[type=\'text\']').val();
        // Field name
        var field = $span.parents('tr').children('td').first().find('input[type=\'hidden\']').val();
        // Column type
        var type = $span.parents('tr').find('span.column_type').text();
        // Names of input field and null checkbox
        var inputName = $span.parent('td').children('input[type=\'text\']').attr('name');

        openGISEditor();
        if (!gisEditorLoaded) {
            loadJSAndGISEditor(value, field, type, inputName);
        } else {
            loadGISEditor(value, field, type, inputName);
        }
    });

    /**
     * Forced validation check of fields
     */
    $(document).on('click','input[name^=\'insert_ignore_\']', function () {
        $('#insertForm').valid();
    });

    /**
     * Uncheck the null checkbox as geometry data is placed on the input field
     */
    $(document).on('click', 'input[name=\'gis_data[save]\']', function () {
        var inputName = $('form#gis_data_editor_form').find('input[name=\'input_name\']').val();
        var currentRow = $('input[name=\'' + inputName + '\']').parents('tr');
        var $nullCheckbox = currentRow.find('.checkbox_null');
        $nullCheckbox.prop('checked', false);
        var rowId = currentRow.find('.open_gis_editor').data('row-id');

        // Unchecks the Ignore checkbox for the current row
        $('input[name=\'insert_ignore_' + rowId + '\']').prop('checked', false);
    });

    /**
     * Handles all current checkboxes for Null; this only takes care of the
     * checkboxes on currently displayed rows as the rows generated by
     * "Continue insertion" are handled in the "Continue insertion" code
     *
     */
    $(document).on('click', 'input.checkbox_null', function () {
        nullify(
            // use hidden fields populated by /table/change
            $(this).siblings('.nullify_code').val(),
            $(this).closest('tr').find('input:hidden').first().val(),
            $(this).siblings('.hashed_field').val(),
            $(this).siblings('.multi_edit').val()
        );
    });

    /**
     * Reset the auto_increment column to 0 when selecting any of the
     * insert options in submit_type-dropdown. Only perform the reset
     * when we are in edit-mode, and not in insert-mode(no previous value
     * available).
     */
    $('select[name="submit_type"]').on('change', function () {
        var thisElemSubmitTypeVal = $(this).val();
        var $table = $('table.insertRowTable');
        var autoIncrementColumn = $table.find('input[name^="auto_increment"]');
        autoIncrementColumn.each(function () {
            var $thisElemAIField = $(this);
            var thisElemName = $thisElemAIField.attr('name');

            var prevValueField = $table.find('input[name="' + thisElemName.replace('auto_increment', 'fields_prev') + '"]');
            var valueField = $table.find('input[name="' + thisElemName.replace('auto_increment', 'fields') + '"]');
            var previousValue = $(prevValueField).val();
            if (previousValue !== undefined) {
                if (thisElemSubmitTypeVal === 'insert'
                    || thisElemSubmitTypeVal === 'insertignore'
                    || thisElemSubmitTypeVal === 'showinsert'
                ) {
                    $(valueField).val(null);
                } else {
                    $(valueField).val(previousValue);
                }
            }
        });
    });

    /**
     * Handle ENTER key when press on Continue insert with field
     */
    $('#insert_rows').on('keypress', function (e) {
        var key = e.which;
        if (key === 13) {
            addNewContinueInsertionFields(e);
        }
    });

    /**
     * Continue Insertion form
     */
    $(document).on('change', '#insert_rows', addNewContinueInsertionFields);
});

function addNewContinueInsertionFields (event) {
    event.preventDefault();
    /**
     * @var columnCount   Number of number of columns table has.
     */
    var columnCount = $('table.insertRowTable').first().find('tr').has('input[name*=\'fields_name\']').length;
    /**
     * @var curr_rows   Number of current insert rows already on page
     */
    var currRows = $('table.insertRowTable').length;
    /**
     * @var target_rows Number of rows the user wants
     */
    var targetRows = $('#insert_rows').val();

    // remove all datepickers
    $('input.datefield, input.datetimefield').each(function () {
        $(this).datepicker('destroy');
    });

    if (currRows < targetRows) {
        var tempIncrementIndex = function () {
            var $thisElement = $(this);
            /**
             * Extract the index from the name attribute for all input/select fields and increment it
             * name is of format funcs[multi_edit][10][<long random string of alphanum chars>]
             */

            /**
             * @var this_name   String containing name of the input/select elements
             */
            var thisName = $thisElement.attr('name');
            /** split {@link thisName} at [10], so we have the parts that can be concatenated later */
            var nameParts = thisName.split(/\[\d+\]/);
            /** extract the [10] from  {@link nameParts} */
            var oldRowIndexString = thisName.match(/\[\d+\]/)[0];
            /** extract 10 - had to split into two steps to accomodate double digits */
            var oldRowIndex = parseInt(oldRowIndexString.match(/\d+/)[0], 10);

            /** calculate next index i.e. 11 */
            newRowIndex = oldRowIndex + 1;
            /** generate the new name i.e. funcs[multi_edit][11][foobarbaz] */
            var newName = nameParts[0] + '[' + newRowIndex + ']' + nameParts[1];

            var hashedField = nameParts[1].match(/\[(.+)\]/)[1];
            $thisElement.attr('name', newName);

            /** If element is select[name*='funcs'], update id */
            if ($thisElement.is('select[name*=\'funcs\']')) {
                var thisId = $thisElement.attr('id');
                var idParts = thisId.split(/_/);
                var oldIdIndex = idParts[1];
                var prevSelectedValue = $('#field_' + oldIdIndex + '_1').val();
                var newIdIndex = parseInt(oldIdIndex) + columnCount;
                var newId = 'field_' + newIdIndex + '_1';
                $thisElement.attr('id', newId);
                $thisElement.find('option').filter(function () {
                    return $(this).text() === prevSelectedValue;
                }).attr('selected','selected');

                // If salt field is there then update its id.
                var nextSaltInput = $thisElement.parent().next('td').next('td').find('input[name*=\'salt\']');
                if (nextSaltInput.length !== 0) {
                    nextSaltInput.attr('id', 'salt_' + newId);
                }
            }

            // handle input text fields and textareas
            if ($thisElement.is('.textfield') || $thisElement.is('.char') || $thisElement.is('textarea')) {
                // do not remove the 'value' attribute for ENUM columns
                // special handling for radio fields after updating ids to unique - see below
                if ($thisElement.closest('tr').find('span.column_type').html() !== 'enum') {
                    $thisElement.val($thisElement.closest('tr').find('span.default_value').html());
                }
                $thisElement
                    .off('change')
                    // Remove onchange attribute that was placed
                    // by /table/change; it refers to the wrong row index
                    .attr('onchange', null)
                    // Keep these values to be used when the element
                    // will change
                    .data('hashed_field', hashedField)
                    .data('new_row_index', newRowIndex)
                    .on('change', function () {
                        var $changedElement = $(this);
                        verificationsAfterFieldChange(
                            $changedElement.data('hashed_field'),
                            $changedElement.data('new_row_index'),
                            $changedElement.closest('tr').find('span.column_type').html()
                        );
                    });
            }

            if ($thisElement.is('.checkbox_null')) {
                $thisElement
                // this event was bound earlier by jQuery but
                // to the original row, not the cloned one, so unbind()
                    .off('click')
                    // Keep these values to be used when the element
                    // will be clicked
                    .data('hashed_field', hashedField)
                    .data('new_row_index', newRowIndex)
                    .on('click', function () {
                        var $changedElement = $(this);
                        nullify(
                            $changedElement.siblings('.nullify_code').val(),
                            $thisElement.closest('tr').find('input:hidden').first().val(),
                            $changedElement.data('hashed_field'),
                            '[multi_edit][' + $changedElement.data('new_row_index') + ']'
                        );
                    });
            }
        };

        var tempReplaceAnchor = function () {
            var $anchor = $(this);
            var newValue = 'rownumber=' + newRowIndex;
            // needs improvement in case something else inside
            // the href contains this pattern
            var newHref = $anchor.attr('href').replace(/rownumber=\d+/, newValue);
            $anchor.attr('href', newHref);
        };

        var restoreValue = function () {
            if ($(this).closest('tr').find('span.column_type').html() === 'enum') {
                if ($(this).val() === $checkedValue) {
                    $(this).prop('checked', true);
                } else {
                    $(this).prop('checked', false);
                }
            }
        };

        while (currRows < targetRows) {
            /**
             * @var $last_row    Object referring to the last row
             */
            var $lastRow = $('#insertForm').find('.insertRowTable').last();

            // need to access this at more than one level
            // (also needs improvement because it should be calculated
            //  just once per cloned row, not once per column)
            var newRowIndex = 0;
            var $checkedValue = $lastRow.find('input:checked').val();

            // Clone the insert tables
            $lastRow
                .clone(true, true)
                .insertBefore('#actions_panel')
                .find('input[name*=multi_edit],select[name*=multi_edit],textarea[name*=multi_edit]')
                .each(tempIncrementIndex)
                .end()
                .find('.foreign_values_anchor')
                .each(tempReplaceAnchor);

            var $oldRow = $lastRow.find('.textfield');
            $oldRow.each(restoreValue);

            // set the value of enum field of new row to default
            var $newRow = $('#insertForm').find('.insertRowTable').last();
            $newRow.find('.textfield').each(function () {
                if ($(this).closest('tr').find('span.column_type').html() === 'enum') {
                    if ($(this).val() === $(this).closest('tr').find('span.default_value').html()) {
                        $(this).prop('checked', true);
                    } else {
                        $(this).prop('checked', false);
                    }
                }
            });


            // Insert/Clone the ignore checkboxes
            if (currRows === 1) {
                $('<input id="insert_ignore_1" type="checkbox" name="insert_ignore_1" checked="checked">')
                    .insertBefore($('table.insertRowTable').last())
                    .after('<label for="insert_ignore_1">' + Messages.strIgnore + '</label>');
            } else {
                /**
                 * @var $last_checkbox   Object reference to the last checkbox in #insertForm
                 */
                var $lastCheckbox = $('#insertForm').children('input:checkbox').last();

                /** name of {@link $lastCheckbox} */
                var lastCheckboxName = $lastCheckbox.attr('name');
                /** index of {@link $lastCheckbox} */
                var lastCheckboxIndex = parseInt(lastCheckboxName.match(/\d+/), 10);
                /** name of new {@link $lastCheckbox} */
                var newName = lastCheckboxName.replace(/\d+/, lastCheckboxIndex + 1);

                $('<br><div class="clearfloat"></div>')
                    .insertBefore($('table.insertRowTable').last());

                $lastCheckbox
                    .clone()
                    .attr({ 'id': newName, 'name': newName })
                    .prop('checked', true)
                    .insertBefore($('table.insertRowTable').last());

                $('label[for^=insert_ignore]').last()
                    .clone()
                    .attr('for', newName)
                    .insertBefore($('table.insertRowTable').last());

                $('<br>')
                    .insertBefore($('table.insertRowTable').last());
            }
            currRows++;
        }
        // recompute tabindex for text fields and other controls at footer;
        // IMO it's not really important to handle the tabindex for
        // function and Null
        var tabIndex = 0;
        $('.textfield, .char, textarea')
            .each(function () {
                tabIndex++;
                $(this).attr('tabindex', tabIndex);
                // update the IDs of textfields to ensure that they are unique
                $(this).attr('id', 'field_' + tabIndex + '_3');
            });
        $('.control_at_footer')
            .each(function () {
                tabIndex++;
                $(this).attr('tabindex', tabIndex);
            });
    } else if (currRows > targetRows) {
        /**
         * Displays alert if data loss possible on decrease
         * of rows.
         */
        var checkLock = jQuery.isEmptyObject(AJAX.lockedTargets);
        if (checkLock || confirm(Messages.strConfirmRowChange) === true) {
            while (currRows > targetRows) {
                $('input[id^=insert_ignore]').last()
                    .nextUntil('fieldset')
                    .addBack()
                    .remove();
                currRows--;
            }
        } else {
            document.getElementById('insert_rows').value = currRows;
        }
    }
    // Add all the required datepickers back
    Functions.addDateTimePicker();
}

// eslint-disable-next-line no-unused-vars
function changeValueFieldType (elem, searchIndex) {
    var fieldsValue = $('input#fieldID_' + searchIndex);
    if (0 === fieldsValue.size()) {
        return;
    }
    var type = $(elem).val();

    if ('LIKE' === type ||
        'LIKE %...%' === type ||
        'NOT LIKE' === type ||
        'NOT LIKE %...%' === type
    ) {
        $('#fieldID_' + searchIndex).data('data-skip-validators', true);
        return;
    } else {
        $('#fieldID_' + searchIndex).data('data-skip-validators', false);
    }

    if ('IN (...)' === type ||
        'NOT IN (...)' === type ||
        'BETWEEN' === type ||
        'NOT BETWEEN' === type
    ) {
        $('#fieldID_' + searchIndex).prop('multiple', true);
    } else {
        $('#fieldID_' + searchIndex).prop('multiple', false);
    }
}
