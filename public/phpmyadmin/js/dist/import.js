/**
 * Functions used in the import tab
 *
 */

/**
 * Toggles the hiding and showing of each plugin's options
 * according to the currently selected plugin from the dropdown list
 */
function changePluginOpts() {
  $('#format_specific_opts').find('div.format_specific_options').each(function () {
    $(this).hide();
  });
  var selectedPluginName = $('#plugins').find('option:selected').val();
  $('#' + selectedPluginName + '_options').fadeIn('slow');
  const importNotification = document.getElementById('import_notification');
  importNotification.innerText = '';
  if (selectedPluginName === 'csv') {
    importNotification.innerHTML = '<div class="alert alert-info mb-0 mt-3" role="alert">' + Messages.strImportCSV + '</div>';
  }
}

/**
 * Toggles the hiding and showing of each plugin's options and sets the selected value
 * in the plugin dropdown list according to the format of the selected file
 *
 * @param {string} fname
 */
function matchFile(fname) {
  var fnameArray = fname.toLowerCase().split('.');
  var len = fnameArray.length;
  if (len !== 0) {
    var extension = fnameArray[len - 1];
    if (extension === 'gz' || extension === 'bz2' || extension === 'zip') {
      len--;
    }
    // Only toggle if the format of the file can be imported
    if ($('select[name=\'format\'] option').filterByValue(fnameArray[len - 1]).length === 1) {
      $('select[name=\'format\'] option').filterByValue(fnameArray[len - 1]).prop('selected', true);
      changePluginOpts();
    }
  }
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('import.js', function () {
  $('#plugins').off('change');
  $('#input_import_file').off('change');
  $('#select_local_import_file').off('change');
  $('#input_import_file').off('change').off('focus');
  $('#select_local_import_file').off('focus');
  $('#text_csv_enclosed').add('#text_csv_escaped').off('keyup');
});
AJAX.registerOnload('import.js', function () {
  // import_file_form validation.
  $(document).on('submit', '#import_file_form', function () {
    var radioLocalImport = $('#localFileTab');
    var radioImport = $('#uploadFileTab');
    var fileMsg = '<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> ' + Messages.strImportDialogMessage + '</div>';
    var wrongTblNameMsg = '<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error">' + Messages.strTableNameDialogMessage + '</div>';
    var wrongDBNameMsg = '<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error">' + Messages.strDBNameDialogMessage + '</div>';
    if (radioLocalImport.length !== 0) {
      // remote upload.

      if (radioImport.hasClass('active') && $('#input_import_file').val() === '') {
        $('#input_import_file').trigger('focus');
        Functions.ajaxShowMessage(fileMsg, false);
        return false;
      }
      if (radioLocalImport.hasClass('active')) {
        if ($('#select_local_import_file').length === 0) {
          Functions.ajaxShowMessage('<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> ' + Messages.strNoImportFile + ' </div>', false);
          return false;
        }
        if ($('#select_local_import_file').val() === '') {
          $('#select_local_import_file').trigger('focus');
          Functions.ajaxShowMessage(fileMsg, false);
          return false;
        }
      }
    } else {
      // local upload.
      if ($('#input_import_file').val() === '') {
        $('#input_import_file').trigger('focus');
        Functions.ajaxShowMessage(fileMsg, false);
        return false;
      }
      if ($('#text_csv_new_tbl_name').length > 0) {
        var newTblName = $('#text_csv_new_tbl_name').val();
        if (newTblName.length > 0 && newTblName.trim().length === 0) {
          Functions.ajaxShowMessage(wrongTblNameMsg, false);
          return false;
        }
      }
      if ($('#text_csv_new_db_name').length > 0) {
        var newDBName = $('#text_csv_new_db_name').val();
        if (newDBName.length > 0 && newDBName.trim().length === 0) {
          Functions.ajaxShowMessage(wrongDBNameMsg, false);
          return false;
        }
      }
    }

    // show progress bar.
    $('#upload_form_status').css('display', 'inline');
    $('#upload_form_status_info').css('display', 'inline');
  });

  // Initially display the options for the selected plugin
  changePluginOpts();

  // Whenever the selected plugin changes, change the options displayed
  $('#plugins').on('change', function () {
    changePluginOpts();
  });
  $('#input_import_file').on('change', function () {
    matchFile($(this).val());
  });
  $('#select_local_import_file').on('change', function () {
    matchFile($(this).val());
  });

  /**
   * Set up the interface for Javascript-enabled browsers since the default is for
   *  Javascript-disabled browsers
   */
  $('#format_specific_opts').find('div.format_specific_options').find('h3').remove();
  // $("form[name=import] *").unwrap();

  /**
   * for input element text_csv_enclosed and text_csv_escaped allow just one character to enter.
   * as mysql allows just one character for these fields,
   * if first character is escape then allow two including escape character.
   */
  $('#text_csv_enclosed').add('#text_csv_escaped').on('keyup', function () {
    if ($(this).val().length === 2 && $(this).val().charAt(0) !== '\\') {
      $(this).val($(this).val().substring(0, 1));
      return false;
    }
    return true;
  });
});