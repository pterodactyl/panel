/**
 * @fileoverview    function used in QBE for DB
 * @name            Database Operations
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/* global sprintf */ // js/vendor/sprintf.js

$(document).on('change', '.criteria_op', function () {
    const op = $(this).val();
    const criteria = $(this).closest('.table').find('.rhs_text_val');

    isOpWithoutArg(op) ? criteria.hide().val('') : criteria.show();
});

function getFormatsText () {
    return {
        '=': ' = \'%s\'',
        '>': ' > \'%s\'',
        '>=': ' >= \'%s\'',
        '<': ' < \'%s\'',
        '<=': ' <= \'%s\'',
        '!=': ' != \'%s\'',
        'LIKE': ' LIKE \'%s\'',
        'LIKE %...%': ' LIKE \'%%%s%%\'',
        'NOT LIKE': ' NOT LIKE \'%s\'',
        'NOT LIKE %...%': ' NOT LIKE \'%%%s%%\'',
        'IN (...)': ' IN (%s)',
        'NOT IN (...)': ' NOT IN (%s)',
        'BETWEEN': ' BETWEEN \'%s\'',
        'NOT BETWEEN': ' NOT BETWEEN \'%s\'',
        'REGEXP': ' REGEXP \'%s\'',
        'REGEXP ^...$': ' REGEXP \'^%s$\'',
        'NOT REGEXP': ' NOT REGEXP \'%s\''
    };
}

function opsWithoutArg () {
    return ['IS NULL', 'IS NOT NULL'];
}

function isOpWithoutArg (op) {
    return opsWithoutArg().includes(op);
}

function generateCondition (criteriaDiv, table) {
    const tableName = table.val();
    const tableAlias = table.siblings('.table_alias').val();
    const criteriaOp = criteriaDiv.find('.criteria_op').first().val();
    let criteriaText = criteriaDiv.find('.rhs_text_val').first().val();

    let query = '`' + Functions.escapeBacktick(tableAlias === '' ? tableName : tableAlias) + '`.';
    query += '`' + Functions.escapeBacktick(table.parent().find('.opColumn').first().val()) + '`';
    if (criteriaDiv.find('.criteria_rhs').first().val() === 'text') {
        if (isOpWithoutArg(criteriaOp)) {
            query += ' ' + criteriaOp;
        } else {
            const formatsText = getFormatsText();

            if (!['IN (...)', 'NOT IN (...)'].includes(criteriaOp)) {
                criteriaText = Functions.escapeSingleQuote(criteriaText);
            }

            query += sprintf(formatsText[criteriaOp], criteriaText);
        }
    } else {
        query += ' ' + criteriaOp;
        query += ' `' + Functions.escapeBacktick(criteriaDiv.find('.tableNameSelect').first().val()) + '`.';
        query += '`' + Functions.escapeBacktick(criteriaDiv.find('.opColumn').first().val()) + '`';
    }
    return query;
}

// eslint-disable-next-line no-unused-vars
function generateWhereBlock () {
    var count = 0;
    var query = '';
    $('.tableNameSelect').each(function () {
        var criteriaDiv = $(this).siblings('.jsCriteriaOptions').first();
        var useCriteria = $(this).siblings('.criteria_col').first();
        if ($(this).val() !== '' && useCriteria.prop('checked')) {
            if (count > 0) {
                criteriaDiv.find('input.logical_op').each(function () {
                    if ($(this).prop('checked')) {
                        query += ' ' + $(this).val() + ' ';
                    }
                });
            }
            query += generateCondition(criteriaDiv, $(this));
            count++;
        }
    });
    return query;
}

function generateJoin (newTable, tableAliases, fk) {
    var query = '';
    query += ' \n\tLEFT JOIN ' + '`' + Functions.escapeBacktick(newTable) + '`';
    if (tableAliases[fk.TABLE_NAME][0] !== '') {
        query += ' AS `' + Functions.escapeBacktick(tableAliases[newTable][0]) + '`';
        query += ' ON `' + Functions.escapeBacktick(tableAliases[fk.TABLE_NAME][0]) + '`';
    } else {
        query += ' ON `' + Functions.escapeBacktick(fk.TABLE_NAME) + '`';
    }
    query += '.`' + fk.COLUMN_NAME + '`';
    if (tableAliases[fk.REFERENCED_TABLE_NAME][0] !== '') {
        query += ' = `' + Functions.escapeBacktick(tableAliases[fk.REFERENCED_TABLE_NAME][0]) + '`';
    } else {
        query += ' = `' + Functions.escapeBacktick(fk.REFERENCED_TABLE_NAME) + '`';
    }
    query += '.`' + fk.REFERENCED_COLUMN_NAME + '`';
    return query;
}

function existReference (table, fk, usedTables) {
    var isReferredBy = fk.TABLE_NAME === table && usedTables.includes(fk.REFERENCED_TABLE_NAME);
    var isReferencedBy = fk.REFERENCED_TABLE_NAME === table && usedTables.includes(fk.TABLE_NAME);
    return isReferredBy || isReferencedBy;
}

function tryJoinTable (table, tableAliases, usedTables, foreignKeys) {
    for (var i = 0; i < foreignKeys.length; i++) {
        var fk = foreignKeys[i];
        if (existReference(table, fk, usedTables)) {
            return generateJoin(table, tableAliases, fk);
        }
    }
    return '';
}

function appendTable (table, tableAliases, usedTables, foreignKeys) {
    var query = tryJoinTable (table, tableAliases, usedTables, foreignKeys);
    if (query === '') {
        if (usedTables.length > 0) {
            query += '\n\t, ';
        }
        query += '`' + Functions.escapeBacktick(table) + '`';
        if (tableAliases[table][0] !== '') {
            query += ' AS `' + Functions.escapeBacktick(tableAliases[table][0]) + '`';
        }
    }
    usedTables.push(table);
    return query;
}

// eslint-disable-next-line no-unused-vars
function generateFromBlock (tableAliases, foreignKeys) {
    var usedTables = [];
    var query = '';
    for (var table in tableAliases) {
        if (tableAliases.hasOwnProperty(table)) {
            query += appendTable(table, tableAliases, usedTables, foreignKeys);
        }
    }
    return query;
}
