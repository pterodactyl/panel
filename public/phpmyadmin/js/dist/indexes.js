/**
 * @fileoverview    function used for index manipulation pages
 * @name            Table Structure
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

/* global fulltextIndexes:writable, indexes:writable, primaryIndexes:writable, spatialIndexes:writable, uniqueIndexes:writable */ // js/functions.js

var Indexes = {};

/**
 * Returns the array of indexes based on the index choice
 *
 * @param {string} indexChoice index choice
 *
 * @return {null|object}
 */
Indexes.getIndexArray = function (indexChoice) {
  var sourceArray = null;
  switch (indexChoice.toLowerCase()) {
    case 'primary':
      sourceArray = primaryIndexes;
      break;
    case 'unique':
      sourceArray = uniqueIndexes;
      break;
    case 'index':
      sourceArray = indexes;
      break;
    case 'fulltext':
      sourceArray = fulltextIndexes;
      break;
    case 'spatial':
      sourceArray = spatialIndexes;
      break;
    default:
      return null;
  }
  return sourceArray;
};

/**
 * Hides/shows the inputs and submits appropriately depending
 * on whether the index type chosen is 'SPATIAL' or not.
 */
Indexes.checkIndexType = function () {
  /**
   * @var {JQuery<HTMLElement}, Dropdown to select the index choice.
   */
  var $selectIndexChoice = $('#select_index_choice');
  /**
   * @var {JQuery<HTMLElement}, Dropdown to select the index type.
   */
  var $selectIndexType = $('#select_index_type');
  /**
   * @var {JQuery<HTMLElement}, Table header for the size column.
   */
  var $sizeHeader = $('#index_columns').find('thead tr').children('th').eq(1);
  /**
   * @var {JQuery<HTMLElement}, Inputs to specify the columns for the index.
   */
  var $columnInputs = $('select[name="index[columns][names][]"]');
  /**
   * @var {JQuery<HTMLElement}, Inputs to specify sizes for columns of the index.
   */
  var $sizeInputs = $('input[name="index[columns][sub_parts][]"]');
  /**
   * @var {JQuery<HTMLElement}, Footer containing the controllers to add more columns
   */
  var $addMore = $('#index_frm').find('.add_more');
  if ($selectIndexChoice.val() === 'SPATIAL') {
    // Disable and hide the size column
    $sizeHeader.hide();
    $sizeInputs.each(function () {
      $(this).prop('disabled', true).parent('td').hide();
    });

    // Disable and hide the columns of the index other than the first one
    var initial = true;
    $columnInputs.each(function () {
      var $columnInput = $(this);
      if (!initial) {
        $columnInput.prop('disabled', true).parent('td').hide();
      } else {
        initial = false;
      }
    });

    // Hide controllers to add more columns
    $addMore.hide();
  } else {
    // Enable and show the size column
    $sizeHeader.show();
    $sizeInputs.each(function () {
      $(this).prop('disabled', false).parent('td').show();
    });

    // Enable and show the columns of the index
    $columnInputs.each(function () {
      $(this).prop('disabled', false).parent('td').show();
    });

    // Show controllers to add more columns
    $addMore.show();
  }
  if ($selectIndexChoice.val() === 'SPATIAL' || $selectIndexChoice.val() === 'FULLTEXT') {
    $selectIndexType.val('').prop('disabled', true);
  } else {
    $selectIndexType.prop('disabled', false);
  }
};

/**
 * Sets current index information into form parameters.
 *
 * @param {any[]}  sourceArray Array containing index columns
 * @param {string} indexChoice Choice of index
 *
 * @return {void}
 */
Indexes.setIndexFormParameters = function (sourceArray, indexChoice) {
  if (indexChoice === 'index') {
    $('input[name="indexes"]').val(JSON.stringify(sourceArray));
  } else {
    $('input[name="' + indexChoice + '_indexes"]').val(JSON.stringify(sourceArray));
  }
};

/**
 * Removes a column from an Index.
 *
 * @param {string} colIndex Index of column in form
 *
 * @return {void}
 */
Indexes.removeColumnFromIndex = function (colIndex) {
  // Get previous index details.
  var previousIndex = $('select[name="field_key[' + colIndex + ']"]').attr('data-index');
  if (previousIndex.length) {
    previousIndex = previousIndex.split(',');
    var sourceArray = Indexes.getIndexArray(previousIndex[0]);
    if (sourceArray === null) {
      return;
    }
    if (previousIndex[1] in sourceArray) {
      // Remove column from index array.
      var sourceLength = sourceArray[previousIndex[1]].columns.length;
      for (var i = 0; i < sourceLength; i++) {
        if (i in sourceArray[previousIndex[1]].columns) {
          if (sourceArray[previousIndex[1]].columns[i].col_index === colIndex) {
            sourceArray[previousIndex[1]].columns.splice(i, 1);
          }
        }
      }

      // Remove index completely if no columns left.
      if (sourceArray[previousIndex[1]].columns.length === 0) {
        sourceArray.splice(previousIndex[1], 1);
      }
    }

    // Update current index details.
    $('select[name="field_key[' + colIndex + ']"]').attr('data-index', '');
    // Update form index parameters.
    Indexes.setIndexFormParameters(sourceArray, previousIndex[0].toLowerCase());
  }
};

/**
 * Adds a column to an Index.
 *
 * @param {any[]}  sourceArray Array holding corresponding indexes
 * @param {string} arrayIndex  Index of an INDEX in array
 * @param {string} indexChoice Choice of Index
 * @param {string} colIndex    Index of column on form
 *
 * @return {void}
 */
Indexes.addColumnToIndex = function (sourceArray, arrayIndex, indexChoice, colIndex) {
  if (colIndex >= 0) {
    // Remove column from other indexes (if any).
    Indexes.removeColumnFromIndex(colIndex);
  }
  var indexName = $('input[name="index[Key_name]"]').val();
  var indexComment = $('input[name="index[Index_comment]"]').val();
  var keyBlockSize = $('input[name="index[Key_block_size]"]').val();
  var parser = $('input[name="index[Parser]"]').val();
  var indexType = $('select[name="index[Index_type]"]').val();
  var columns = [];
  $('#index_columns').find('tbody').find('tr').each(function () {
    // Get columns in particular order.
    var colIndex = $(this).find('select[name="index[columns][names][]"]').val();
    var size = $(this).find('input[name="index[columns][sub_parts][]"]').val();
    columns.push({
      'col_index': colIndex,
      'size': size
    });
  });

  // Update or create an index.
  sourceArray[arrayIndex] = {
    'Key_name': indexName,
    'Index_comment': indexComment,
    'Index_choice': indexChoice.toUpperCase(),
    'Key_block_size': keyBlockSize,
    'Parser': parser,
    'Index_type': indexType,
    'columns': columns
  };

  // Display index name (or column list)
  var displayName = indexName;
  if (displayName === '') {
    var columnNames = [];
    $.each(columns, function () {
      columnNames.push($('input[name="field_name[' + this.col_index + ']"]').val());
    });
    displayName = '[' + columnNames.join(', ').trimRight() + ']';
  }
  $.each(columns, function () {
    var id = 'index_name_' + this.col_index + '_8';
    var $name = $('#' + id);
    if ($name.length === 0) {
      $name = $('<a id="' + id + '" href="#" class="ajax show_index_dialog"></a>');
      $name.insertAfter($('select[name="field_key[' + this.col_index + ']"]'));
    }
    var $text = $('<small>').text(displayName);
    $name.html($text);
  });
  if (colIndex >= 0) {
    // Update index details on form.
    $('select[name="field_key[' + colIndex + ']"]').attr('data-index', indexChoice + ',' + arrayIndex);
  }
  Indexes.setIndexFormParameters(sourceArray, indexChoice.toLowerCase());
};

/**
 * Get choices list for a column to create a composite index with.
 *
 * @param {any[]} sourceArray Array hodling columns for particular index
 * @param {string} colIndex Choice of index
 *
 * @return {JQuery} jQuery Object
 */
Indexes.getCompositeIndexList = function (sourceArray, colIndex) {
  // Remove any previous list.
  if ($('#composite_index_list').length) {
    $('#composite_index_list').remove();
  }

  // Html list.
  var $compositeIndexList = $('<ul id="composite_index_list">' + '<div>' + Messages.strCompositeWith + '</div>' + '</ul>');

  // Add each column to list available for composite index.
  var sourceLength = sourceArray.length;
  var alreadyPresent = false;
  for (var i = 0; i < sourceLength; i++) {
    var subArrayLen = sourceArray[i].columns.length;
    var columnNames = [];
    for (var j = 0; j < subArrayLen; j++) {
      columnNames.push($('input[name="field_name[' + sourceArray[i].columns[j].col_index + ']"]').val());
      if (colIndex === sourceArray[i].columns[j].col_index) {
        alreadyPresent = true;
      }
    }
    $compositeIndexList.append('<li>' + '<input type="radio" name="composite_with" ' + (alreadyPresent ? 'checked="checked"' : '') + ' id="composite_index_' + i + '" value="' + i + '">' + '<label for="composite_index_' + i + '">' + columnNames.join(', ') + '</label>' + '</li>');
  }
  return $compositeIndexList;
};

/**
 * Shows 'Add Index' dialog.
 *
 * @param {any[]}  sourceArray   Array holding particular index
 * @param {string} arrayIndex    Index of an INDEX in array
 * @param {any[]}  targetColumns Columns for an INDEX
 * @param {string} colIndex      Index of column on form
 * @param {object} index         Index detail object
 * @param {boolean} showDialog   Whether to show index creation dialog or not
 *
 * @return {void}
 */
Indexes.showAddIndexDialog = function (sourceArray, arrayIndex, targetColumns, colIndex, index, showDialog) {
  var showDialogLocal = typeof showDialog !== 'undefined' ? showDialog : true;
  // Prepare post-data.
  var $table = $('input[name="table"]');
  var table = $table.length > 0 ? $table.val() : '';
  var postData = {
    'server': CommonParams.get('server'),
    'db': $('input[name="db"]').val(),
    'table': table,
    'ajax_request': 1,
    'create_edit_table': 1,
    'index': index
  };
  var columns = {};
  for (var i = 0; i < targetColumns.length; i++) {
    var columnName = $('input[name="field_name[' + targetColumns[i] + ']"]').val();
    var columnType = $('select[name="field_type[' + targetColumns[i] + ']"]').val().toLowerCase();
    columns[columnName] = [columnType, targetColumns[i]];
  }
  postData.columns = JSON.stringify(columns);
  var buttonOptions = {
    [Messages.strGo]: {
      text: Messages.strGo,
      class: 'btn btn-primary'
    },
    [Messages.strCancel]: {
      text: Messages.strCancel,
      class: 'btn btn-secondary'
    }
  };
  buttonOptions[Messages.strGo].click = function () {
    var isMissingValue = false;
    $('select[name="index[columns][names][]"]').each(function () {
      if ($(this).val() === '') {
        isMissingValue = true;
      }
    });
    if (!isMissingValue) {
      Indexes.addColumnToIndex(sourceArray, arrayIndex, index.Index_choice, colIndex);
    } else {
      Functions.ajaxShowMessage('<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title="" alt=""' + ' class="icon ic_s_error"> ' + Messages.strMissingColumn + ' </div>', false);
      return false;
    }
    $(this).remove();
  };
  buttonOptions[Messages.strCancel].click = function () {
    if (colIndex >= 0) {
      // Handle state on 'Cancel'.
      var $selectList = $('select[name="field_key[' + colIndex + ']"]');
      if (!$selectList.attr('data-index').length) {
        $selectList.find('option[value*="none"]').attr('selected', 'selected');
      } else {
        var previousIndex = $selectList.attr('data-index').split(',');
        $selectList.find('option[value*="' + previousIndex[0].toLowerCase() + '"]').attr('selected', 'selected');
      }
    }
    $(this).dialog('close');
  };
  var $msgbox = Functions.ajaxShowMessage();
  $.post('index.php?route=/table/indexes', postData, function (data) {
    if (data.success === false) {
      // in the case of an error, show the error message returned.
      Functions.ajaxShowMessage(data.error, false);
    } else {
      Functions.ajaxRemoveMessage($msgbox);
      var $div = $('<div></div>');
      if (showDialogLocal) {
        // Show dialog if the request was successful
        if ($('#addIndex').length > 0) {
          $('#addIndex').remove();
        }
        $div.append(data.message).dialog({
          classes: {
            'ui-dialog-titlebar-close': 'btn-close'
          },
          title: Messages.strAddIndex,
          width: 450,
          minHeight: 250,
          create: function () {
            $(this).on('keypress', function (e) {
              if (e.which === 13 || e.keyCode === 13 || window.event.keyCode === 13) {
                e.preventDefault();
                buttonOptions[Messages.strGo]();
                $(this).remove();
              }
            });
          },
          open: function () {
            Functions.checkIndexName('index_frm');
            Functions.showHints($div);
            $('#index_columns').find('td').each(function () {
              $(this).css('width', $(this).width() + 'px');
            });
            $('#index_columns').find('tbody').sortable({
              axis: 'y',
              containment: $('#index_columns').find('tbody'),
              tolerance: 'pointer'
            });
          },
          modal: true,
          buttons: buttonOptions,
          close: function () {
            $(this).remove();
          }
        });
      } else {
        $div.append(data.message);
        $div.css({
          'display': 'none'
        });
        $div.appendTo($('body'));
        $div.attr({
          'id': 'addIndex'
        });
        var isMissingValue = false;
        $('select[name="index[columns][names][]"]').each(function () {
          if ($(this).val() === '') {
            isMissingValue = true;
          }
        });
        if (!isMissingValue) {
          Indexes.addColumnToIndex(sourceArray, arrayIndex, index.Index_choice, colIndex);
        } else {
          Functions.ajaxShowMessage('<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title="" alt=""' + ' class="icon ic_s_error"> ' + Messages.strMissingColumn + ' </div>', false);
          return false;
        }
      }
    }
  });
};

/**
 * Creates a advanced index type selection dialog.
 *
 * @param {any[]}  sourceArray Array holding a particular type of indexes
 * @param {string} indexChoice Choice of index
 * @param {string} colIndex    Index of new column on form
 *
 * @return {void}
 */
Indexes.indexTypeSelectionDialog = function (sourceArray, indexChoice, colIndex) {
  var $singleColumnRadio = $('<input type="radio" id="single_column" name="index_choice"' + ' checked="checked">' + '<label for="single_column">' + Messages.strCreateSingleColumnIndex + '</label>');
  var $compositeIndexRadio = $('<input type="radio" id="composite_index"' + ' name="index_choice">' + '<label for="composite_index">' + Messages.strCreateCompositeIndex + '</label>');
  var $dialogContent = $('<fieldset class="pma-fieldset" id="advance_index_creator"></fieldset>');
  $dialogContent.append('<legend>' + indexChoice.toUpperCase() + '</legend>');

  // For UNIQUE/INDEX type, show choice for single-column and composite index.
  $dialogContent.append($singleColumnRadio);
  $dialogContent.append($compositeIndexRadio);
  var buttonOptions = {
    [Messages.strGo]: {
      text: Messages.strGo,
      class: 'btn btn-primary'
    },
    [Messages.strCancel]: {
      text: Messages.strCancel,
      class: 'btn btn-secondary'
    }
  };

  // 'OK' operation.
  buttonOptions[Messages.strGo].click = function () {
    if ($('#single_column').is(':checked')) {
      var index = {
        'Key_name': indexChoice === 'primary' ? 'PRIMARY' : '',
        'Index_choice': indexChoice.toUpperCase()
      };
      Indexes.showAddIndexDialog(sourceArray, sourceArray.length, [colIndex], colIndex, index);
    }
    if ($('#composite_index').is(':checked')) {
      if ($('input[name="composite_with"]').length !== 0 && $('input[name="composite_with"]:checked').length === 0) {
        Functions.ajaxShowMessage('<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title=""' + ' alt="" class="icon ic_s_error"> ' + Messages.strFormEmpty + ' </div>', false);
        return false;
      }
      var arrayIndex = $('input[name="composite_with"]:checked').val();
      var sourceLength = sourceArray[arrayIndex].columns.length;
      var targetColumns = [];
      for (var i = 0; i < sourceLength; i++) {
        targetColumns.push(sourceArray[arrayIndex].columns[i].col_index);
      }
      targetColumns.push(colIndex);
      Indexes.showAddIndexDialog(sourceArray, arrayIndex, targetColumns, colIndex, sourceArray[arrayIndex]);
    }
    $(this).remove();
  };
  buttonOptions[Messages.strCancel].click = function () {
    // Handle state on 'Cancel'.
    var $selectList = $('select[name="field_key[' + colIndex + ']"]');
    if (!$selectList.attr('data-index').length) {
      $selectList.find('option[value*="none"]').attr('selected', 'selected');
    } else {
      var previousIndex = $selectList.attr('data-index').split(',');
      $selectList.find('option[value*="' + previousIndex[0].toLowerCase() + '"]').attr('selected', 'selected');
    }
    $(this).remove();
  };
  $('<div></div>').append($dialogContent).dialog({
    classes: {
      'ui-dialog-titlebar-close': 'btn-close'
    },
    minWidth: 525,
    minHeight: 200,
    modal: true,
    title: Messages.strAddIndex,
    resizable: false,
    buttons: buttonOptions,
    open: function () {
      $('#composite_index').on('change', function () {
        if ($(this).is(':checked')) {
          $dialogContent.append(Indexes.getCompositeIndexList(sourceArray, colIndex));
        }
      });
      $('#single_column').on('change', function () {
        if ($(this).is(':checked')) {
          if ($('#composite_index_list').length) {
            $('#composite_index_list').remove();
          }
        }
      });
    },
    close: function () {
      $('#composite_index').off('change');
      $('#single_column').off('change');
      $(this).remove();
    }
  });
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('indexes.js', function () {
  $(document).off('click', '#save_index_frm');
  $(document).off('click', '#preview_index_frm');
  $(document).off('change', '#select_index_choice');
  $(document).off('click', 'a.drop_primary_key_index_anchor.ajax');
  $(document).off('click', '#table_index tbody tr td.edit_index.ajax, #index_div .add_index.ajax');
  $(document).off('click', '#table_index tbody tr td.rename_index.ajax');
  $(document).off('click', '#index_frm input[type=submit]');
  $('body').off('change', 'select[name*="field_key"]');
  $(document).off('click', '.show_index_dialog');
});

/**
 * @description <p>Ajax scripts for table index page</p>
 *
 * Actions ajaxified here:
 * <ul>
 * <li>Showing/hiding inputs depending on the index type chosen</li>
 * <li>create/edit/drop indexes</li>
 * </ul>
 */
AJAX.registerOnload('indexes.js', function () {
  // Re-initialize variables.
  primaryIndexes = [];
  uniqueIndexes = [];
  indexes = [];
  fulltextIndexes = [];
  spatialIndexes = [];

  // for table creation form
  var $engineSelector = $('.create_table_form select[name=tbl_storage_engine]');
  if ($engineSelector.length) {
    Functions.hideShowConnection($engineSelector);
  }
  var $form = $('#index_frm');
  if ($form.length > 0) {
    Functions.showIndexEditDialog($form);
  }
  $(document).on('click', '#save_index_frm', function (event) {
    event.preventDefault();
    var $form = $('#index_frm');
    var argsep = CommonParams.get('arg_separator');
    var submitData = $form.serialize() + argsep + 'do_save_data=1' + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
    Functions.ajaxShowMessage(Messages.strProcessingRequest);
    AJAX.source = $form;
    $.post($form.attr('action'), submitData, AJAX.responseHandler);
  });
  $(document).on('click', '#preview_index_frm', function (event) {
    event.preventDefault();
    Functions.previewSql($('#index_frm'));
  });
  $(document).on('change', '#select_index_choice', function (event) {
    event.preventDefault();
    Indexes.checkIndexType();
    Functions.checkIndexName('index_frm');
  });

  /**
   * Ajax Event handler for 'Drop Index'
   */
  $(document).on('click', 'a.drop_primary_key_index_anchor.ajax', function (event) {
    event.preventDefault();
    var $anchor = $(this);
    /**
     * @var $currRow Object containing reference to the current field's row
     */
    var $currRow = $anchor.parents('tr');
    /** @var {number} rows Number of columns in the key */
    var rows = $anchor.parents('td').attr('rowspan') || 1;
    /** @var {number} $rowsToHide Rows that should be hidden */
    var $rowsToHide = $currRow;
    for (var i = 1, $lastRow = $currRow.next(); i < rows; i++, $lastRow = $lastRow.next()) {
      $rowsToHide = $rowsToHide.add($lastRow);
    }
    var question = $currRow.children('td').children('.drop_primary_key_index_msg').val();
    Functions.confirmPreviewSql(question, $anchor.attr('href'), function (url) {
      var $msg = Functions.ajaxShowMessage(Messages.strDroppingPrimaryKeyIndex, false);
      var params = Functions.getJsConfirmCommonParam(this, $anchor.getPostData());
      $.post(url, params, function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
          Functions.ajaxRemoveMessage($msg);
          var $tableRef = $rowsToHide.closest('table');
          if ($rowsToHide.length === $tableRef.find('tbody > tr').length) {
            // We are about to remove all rows from the table
            $tableRef.hide('medium', function () {
              $('div.no_indexes_defined').show('medium');
              $rowsToHide.remove();
            });
            $tableRef.siblings('.alert-primary').hide('medium');
          } else {
            // We are removing some of the rows only
            $rowsToHide.hide('medium', function () {
              $(this).remove();
            });
          }
          if ($('.result_query').length) {
            $('.result_query').remove();
          }
          if (data.sql_query) {
            $('<div class="result_query"></div>').html(data.sql_query).prependTo('#structure_content');
            Functions.highlightSql($('#page_content'));
          }
          Navigation.reload();
          CommonActions.refreshMain('index.php?route=/table/structure');
        } else {
          Functions.ajaxShowMessage(Messages.strErrorProcessingRequest + ' : ' + data.error, false);
        }
      }); // end $.post()
    });
  }); // end Drop Primary Key/Index

  /**
   * Ajax event handler for index edit
   **/
  $(document).on('click', '#table_index tbody tr td.edit_index.ajax, #index_div .add_index.ajax', function (event) {
    event.preventDefault();
    var url;
    var title;
    if ($(this).find('a').length === 0) {
      // Add index
      var valid = Functions.checkFormElementInRange($(this).closest('form')[0], 'added_fields', 'Column count has to be larger than zero.');
      if (!valid) {
        return;
      }
      url = $(this).closest('form').serialize();
      title = Messages.strAddIndex;
    } else {
      // Edit index
      url = $(this).find('a').getPostData();
      title = Messages.strEditIndex;
    }
    url += CommonParams.get('arg_separator') + 'ajax_request=true';
    Functions.indexEditorDialog(url, title, function (data) {
      CommonParams.set('db', data.params.db);
      CommonParams.set('table', data.params.table);
      CommonActions.refreshMain('index.php?route=/table/structure');
    });
  });

  /**
   * Ajax event handler for index rename
   **/
  $(document).on('click', '#table_index tbody tr td.rename_index.ajax', function (event) {
    event.preventDefault();
    var url = $(this).find('a').getPostData();
    var title = Messages.strRenameIndex;
    url += CommonParams.get('arg_separator') + 'ajax_request=true';
    Functions.indexRenameDialog(url, title, function (data) {
      CommonParams.set('db', data.params.db);
      CommonParams.set('table', data.params.table);
      CommonActions.refreshMain('index.php?route=/table/structure');
    });
  });

  /**
   * Ajax event handler for advanced index creation during table creation
   * and column addition.
   */
  $('body').on('change', 'select[name*="field_key"]', function (e, showDialog) {
    var showDialogLocal = typeof showDialog !== 'undefined' ? showDialog : true;
    // Index of column on Table edit and create page.
    var colIndex = /\d+/.exec($(this).attr('name'));
    colIndex = colIndex[0];
    // Choice of selected index.
    var indexChoice = /[a-z]+/.exec($(this).val());
    indexChoice = indexChoice[0];
    // Array containing corresponding indexes.
    var sourceArray = null;
    if (indexChoice === 'none') {
      Indexes.removeColumnFromIndex(colIndex);
      var id = 'index_name_' + colIndex + '_8';
      var $name = $('#' + id);
      if ($name.length === 0) {
        $name = $('<a id="' + id + '" href="#" class="ajax show_index_dialog"></a>');
        $name.insertAfter($('select[name="field_key[' + '0' + ']"]'));
      }
      $name.html('');
      return false;
    }

    // Select a source array.
    sourceArray = Indexes.getIndexArray(indexChoice);
    if (sourceArray === null) {
      return;
    }
    if (sourceArray.length === 0) {
      var index = {
        'Key_name': indexChoice === 'primary' ? 'PRIMARY' : '',
        'Index_choice': indexChoice.toUpperCase()
      };
      Indexes.showAddIndexDialog(sourceArray, 0, [colIndex], colIndex, index, showDialogLocal);
    } else {
      if (indexChoice === 'primary') {
        var arrayIndex = 0;
        var sourceLength = sourceArray[arrayIndex].columns.length;
        var targetColumns = [];
        for (var i = 0; i < sourceLength; i++) {
          targetColumns.push(sourceArray[arrayIndex].columns[i].col_index);
        }
        targetColumns.push(colIndex);
        Indexes.showAddIndexDialog(sourceArray, arrayIndex, targetColumns, colIndex, sourceArray[arrayIndex], showDialogLocal);
      } else {
        // If there are multiple columns selected for an index, show advanced dialog.
        Indexes.indexTypeSelectionDialog(sourceArray, indexChoice, colIndex);
      }
    }
  });
  $(document).on('click', '.show_index_dialog', function (e) {
    e.preventDefault();

    // Get index details.
    var previousIndex = $(this).prev('select').attr('data-index').split(',');
    var indexChoice = previousIndex[0];
    var arrayIndex = previousIndex[1];
    var sourceArray = Indexes.getIndexArray(indexChoice);
    if (sourceArray === null) {
      return;
    }
    if (arrayIndex in sourceArray) {
      var sourceLength = sourceArray[arrayIndex].columns.length;
      var targetColumns = [];
      for (var i = 0; i < sourceLength; i++) {
        targetColumns.push(sourceArray[arrayIndex].columns[i].col_index);
      }
      Indexes.showAddIndexDialog(sourceArray, arrayIndex, targetColumns, -1, sourceArray[arrayIndex]);
    }
  });
  $('#index_frm').on('submit', function () {
    if (typeof this.elements['index[Key_name]'].disabled !== 'undefined') {
      this.elements['index[Key_name]'].disabled = false;
    }
  });
});