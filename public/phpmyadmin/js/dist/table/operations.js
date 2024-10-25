/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('table/operations.js', function () {
  $(document).off('submit', '#copyTable.ajax');
  $(document).off('submit', '#moveTableForm');
  $(document).off('submit', '#tableOptionsForm');
  $(document).off('submit', '#partitionsForm');
  $(document).off('click', '#tbl_maintenance li a.maintain_action.ajax');
  $(document).off('click', '#drop_tbl_anchor.ajax');
  $(document).off('click', '#drop_view_anchor.ajax');
  $(document).off('click', '#truncate_tbl_anchor.ajax');
  $(document).off('click', '#delete_tbl_anchor.ajax');
});

/**
 * Confirm and send POST request
 *
 * @param {JQuery} linkObject
 * @param {'TRUNCATE'|'DELETE'} action
 *
 * @return {void}
 */
var confirmAndPost = function (linkObject, action) {
  /**
   * @var {String} question String containing the question to be asked for confirmation
   */
  var question = '';
  if (action === 'TRUNCATE') {
    question += Messages.strTruncateTableStrongWarning + ' ';
  } else if (action === 'DELETE') {
    question += Messages.strDeleteTableStrongWarning + ' ';
  }
  question += Functions.sprintf(Messages.strDoYouReally, linkObject.data('query'));
  question += Functions.getForeignKeyCheckboxLoader();
  linkObject.confirm(question, linkObject.attr('href'), function (url) {
    Functions.ajaxShowMessage(Messages.strProcessingRequest);
    var params = Functions.getJsConfirmCommonParam(this, linkObject.getPostData());
    $.post(url, params, function (data) {
      if ($('.sqlqueryresults').length !== 0) {
        $('.sqlqueryresults').remove();
      }
      if ($('.result_query').length !== 0) {
        $('.result_query').remove();
      }
      if (typeof data !== 'undefined' && data.success === true) {
        Functions.ajaxShowMessage(data.message);
        $('<div class="sqlqueryresults ajax"></div>').prependTo('#page_content');
        $('.sqlqueryresults').html(data.sql_query);
        Functions.highlightSql($('#page_content'));
      } else {
        Functions.ajaxShowMessage(data.error, false);
      }
    });
  }, Functions.loadForeignKeyCheckbox);
};

/**
 * jQuery coding for 'Table operations'. Used on /table/operations
 * Attach Ajax Event handlers for Table operations
 */
AJAX.registerOnload('table/operations.js', function () {
  /**
   * Ajax action for submitting the "Copy table"
   */
  $(document).on('submit', '#copyTable.ajax', function (event) {
    event.preventDefault();
    var $form = $(this);
    Functions.prepareForAjaxRequest($form);
    var argsep = CommonParams.get('arg_separator');
    $.post($form.attr('action'), $form.serialize() + argsep + 'submit_copy=Go', function (data) {
      if (typeof data !== 'undefined' && data.success === true) {
        if ($form.find('input[name=\'switch_to_new\']').prop('checked')) {
          CommonParams.set('db', $form.find('select[name=\'target_db\'],input[name=\'target_db\']').val());
          CommonParams.set('table', $form.find('input[name=\'new_name\']').val());
          CommonActions.refreshMain(false, function () {
            Functions.ajaxShowMessage(data.message);
          });
        } else {
          Functions.ajaxShowMessage(data.message);
        }
        // Refresh navigation when the table is copied
        Navigation.reload();
      } else {
        Functions.ajaxShowMessage(data.error, false);
      }
    }); // end $.post()
  }); // end of copyTable ajax submit

  /**
   * Ajax action for submitting the "Move table"
   */
  $(document).on('submit', '#moveTableForm', function (event) {
    event.preventDefault();
    var $form = $(this);
    Functions.prepareForAjaxRequest($form);
    var argsep = CommonParams.get('arg_separator');
    $.post($form.attr('action'), $form.serialize() + argsep + 'submit_move=1', function (data) {
      if (typeof data !== 'undefined' && data.success === true) {
        CommonParams.set('db', data.params.db);
        CommonParams.set('table', data.params.table);
        CommonActions.refreshMain('index.php?route=/table/sql', function () {
          Functions.ajaxShowMessage(data.message);
        });
        // Refresh navigation when the table is copied
        Navigation.reload();
      } else {
        Functions.ajaxShowMessage(data.error, false);
      }
    });
  });

  /**
   * Ajax action for submitting the "Table options"
   */
  $(document).on('submit', '#tableOptionsForm', function (event) {
    event.preventDefault();
    event.stopPropagation();
    var $form = $(this);
    var $tblNameField = $form.find('input[name=new_name]');
    var $tblCollationField = $form.find('select[name=tbl_collation]');
    var collationOrigValue = $('select[name="tbl_collation"] option[selected]').val();
    var $changeAllColumnCollationsCheckBox = $('#checkbox_change_all_collations');
    var question = Messages.strChangeAllColumnCollationsWarning;
    if ($tblNameField.val() !== $tblNameField[0].defaultValue) {
      // reload page and navigation if the table has been renamed
      Functions.prepareForAjaxRequest($form);
      if ($tblCollationField.val() !== collationOrigValue && $changeAllColumnCollationsCheckBox.is(':checked')) {
        $form.confirm(question, $form.attr('action'), function () {
          submitOptionsForm();
        });
      } else {
        submitOptionsForm();
      }
    } else {
      if ($tblCollationField.val() !== collationOrigValue && $changeAllColumnCollationsCheckBox.is(':checked')) {
        $form.confirm(question, $form.attr('action'), function () {
          $form.removeClass('ajax').trigger('submit').addClass('ajax');
        });
      } else {
        $form.removeClass('ajax').trigger('submit').addClass('ajax');
      }
    }
    function submitOptionsForm() {
      $.post($form.attr('action'), $form.serialize(), function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
          CommonParams.set('table', data.params.table);
          CommonActions.refreshMain(false, function () {
            $('#page_content').html(data.message);
            Functions.highlightSql($('#page_content'));
          });
          // Refresh navigation when the table is renamed
          Navigation.reload();
        } else {
          Functions.ajaxShowMessage(data.error, false);
        }
      });
    }
  });

  /**
   * Ajax events for actions in the "Table maintenance"
   */
  $(document).on('click', '#tbl_maintenance li a.maintain_action.ajax', function (event) {
    event.preventDefault();
    var $link = $(this);
    if ($('.sqlqueryresults').length !== 0) {
      $('.sqlqueryresults').remove();
    }
    if ($('.result_query').length !== 0) {
      $('.result_query').remove();
    }
    // variables which stores the common attributes
    var params = $.param({
      'ajax_request': 1,
      'server': CommonParams.get('server')
    });
    var postData = $link.getPostData();
    if (postData) {
      params += CommonParams.get('arg_separator') + postData;
    }
    $.post($link.attr('href'), params, function (data) {
      function scrollToTop() {
        $('html, body').animate({
          scrollTop: 0
        });
      }
      var $tempDiv;
      if (typeof data !== 'undefined' && data.success === true && data.sql_query !== undefined) {
        Functions.ajaxShowMessage(data.message);
        $('<div class=\'sqlqueryresults ajax\'></div>').prependTo('#page_content');
        $('.sqlqueryresults').html(data.sql_query);
        Functions.highlightSql($('#page_content'));
        scrollToTop();
      } else if (typeof data !== 'undefined' && data.success === true) {
        $tempDiv = $('<div id=\'temp_div\'></div>');
        $tempDiv.html(data.message);
        var $success = $tempDiv.find('.result_query .alert-success');
        Functions.ajaxShowMessage($success);
        $('<div class=\'sqlqueryresults ajax\'></div>').prependTo('#page_content');
        $('.sqlqueryresults').html(data.message);
        Functions.highlightSql($('#page_content'));
        $('.sqlqueryresults').children('fieldset,br').remove();
        scrollToTop();
      } else {
        $tempDiv = $('<div id=\'temp_div\'></div>');
        $tempDiv.html(data.error);
        var $error;
        if ($tempDiv.find('.error code').length !== 0) {
          $error = $tempDiv.find('.error code').addClass('error');
        } else {
          $error = $tempDiv;
        }
        Functions.ajaxShowMessage($error, false);
      }
    }); // end $.post()
  }); // end of table maintenance ajax click

  /**
   * Ajax action for submitting the "Partition Maintenance"
   * Also, asks for confirmation when DROP partition is submitted
   */
  $(document).on('submit', '#partitionsForm', function (event) {
    event.preventDefault();
    var $form = $(this);
    function submitPartitionMaintenance() {
      var argsep = CommonParams.get('arg_separator');
      var submitData = $form.serialize() + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
      Functions.ajaxShowMessage(Messages.strProcessingRequest);
      AJAX.source = $form;
      $.post($form.attr('action'), submitData, AJAX.responseHandler);
    }
    if ($('#partitionOperationRadioDrop').is(':checked')) {
      $form.confirm(Messages.strDropPartitionWarning, $form.attr('action'), function () {
        submitPartitionMaintenance();
      });
    } else if ($('#partitionOperationRadioTruncate').is(':checked')) {
      $form.confirm(Messages.strTruncatePartitionWarning, $form.attr('action'), function () {
        submitPartitionMaintenance();
      });
    } else {
      submitPartitionMaintenance();
    }
  });
  $(document).on('click', '#drop_tbl_anchor.ajax', function (event) {
    event.preventDefault();
    var $link = $(this);
    /**
     * @var {String} question String containing the question to be asked for confirmation
     */
    var question = Messages.strDropTableStrongWarning + ' ';
    question += Functions.sprintf(Messages.strDoYouReally, $link[0].getAttribute('data-query'));
    question += Functions.getForeignKeyCheckboxLoader();
    $(this).confirm(question, $(this).attr('href'), function (url) {
      var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
      var params = Functions.getJsConfirmCommonParam(this, $link.getPostData());
      $.post(url, params, function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
          Functions.ajaxRemoveMessage($msgbox);
          // Table deleted successfully, refresh both the frames
          Navigation.reload();
          CommonParams.set('table', '');
          CommonActions.refreshMain(CommonParams.get('opendb_url'), function () {
            Functions.ajaxShowMessage(data.message);
          });
        } else {
          Functions.ajaxShowMessage(data.error, false);
        }
      });
    }, Functions.loadForeignKeyCheckbox);
  }); // end of Drop Table Ajax action

  $(document).on('click', '#drop_view_anchor.ajax', function (event) {
    event.preventDefault();
    var $link = $(this);
    /**
     * @var {String} question String containing the question to be asked for confirmation
     */
    var question = Messages.strDropTableStrongWarning + ' ';
    question += Functions.sprintf(Messages.strDoYouReally, 'DROP VIEW `' + Functions.escapeHtml(CommonParams.get('table') + '`'));
    $(this).confirm(question, $(this).attr('href'), function (url) {
      var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
      var params = Functions.getJsConfirmCommonParam(this, $link.getPostData());
      $.post(url, params, function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
          Functions.ajaxRemoveMessage($msgbox);
          // Table deleted successfully, refresh both the frames
          Navigation.reload();
          CommonParams.set('table', '');
          CommonActions.refreshMain(CommonParams.get('opendb_url'), function () {
            Functions.ajaxShowMessage(data.message);
          });
        } else {
          Functions.ajaxShowMessage(data.error, false);
        }
      });
    });
  }); // end of Drop View Ajax action

  $(document).on('click', '#truncate_tbl_anchor.ajax', function (event) {
    event.preventDefault();
    confirmAndPost($(this), 'TRUNCATE');
  });
  $(document).on('click', '#delete_tbl_anchor.ajax', function (event) {
    event.preventDefault();
    confirmAndPost($(this), 'DELETE');
  });
}); // end $(document).ready for 'Table operations'