/**
 * @fileoverview    function used in QBE for DB
 * @name            Database Operations
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/**
 * Ajax event handlers here for /database/qbe
 *
 * Actions Ajaxified here:
 * Select saved search
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('database/qbe.js', function () {
  $(document).off('change', 'select[name^=criteriaColumn]');
  $(document).off('change', '#searchId');
  $(document).off('click', '#saveSearch');
  $(document).off('click', '#updateSearch');
  $(document).off('click', '#deleteSearch');
});
AJAX.registerOnload('database/qbe.js', function () {
  Functions.getSqlEditor($('#textSqlquery'), {}, 'none');
  $('#tblQbe').width($('#tblQbe').parent().width());
  $('#tblQbeFooters').width($('#tblQbeFooters').parent().width());
  $('#tblQbe').on('resize', function () {
    var newWidthTblQbe = $('#textSqlquery').next().width();
    $('#tblQbe').width(newWidthTblQbe);
    $('#tblQbeFooters').width(newWidthTblQbe);
  });

  /**
   * Ajax handler to check the corresponding 'show' checkbox when column is selected
   */
  $(document).on('change', 'select[name^=criteriaColumn]', function () {
    if ($(this).val()) {
      var index = /\d+/.exec($(this).attr('name'));
      $('input[name=criteriaShow\\[' + index + '\\]]').prop('checked', true);
    }
  });

  /**
   * Ajax event handlers for 'Select saved search'
   */
  $(document).on('change', '#searchId', function () {
    $('#action').val('load');
    $('#formQBE').trigger('submit');
  });

  /**
   * Ajax event handlers for 'Create bookmark'
   */
  $(document).on('click', '#saveSearch', function () {
    $('#action').val('create');
  });

  /**
   * Ajax event handlers for 'Update bookmark'
   */
  $(document).on('click', '#updateSearch', function () {
    $('#action').val('update');
  });

  /**
   * Ajax event handlers for 'Delete bookmark'
   */
  $(document).on('click', '#deleteSearch', function () {
    var question = Functions.sprintf(Messages.strConfirmDeleteQBESearch, $('#searchId').find('option:selected').text());
    if (!confirm(question)) {
      return false;
    }
    $('#action').val('delete');
  });
  var windowwidth = $(window).width();
  $('.jsresponsive').css('max-width', windowwidth - 35 + 'px');
});