/* global TraceKit */ // js/vendor/tracekit.js

/**
 * general function, usually for data manipulation pages
 *
 */
var ErrorReport = {
  /**
   * @var {object}, stores the last exception info
   */
  lastException: null,
  /**
   * @var object stores the Error Report Data to prevent unnecessary data fetching
   */
  errorReportData: null,
  /**
   * @var object maintains unique keys already used
   */
  keyDict: {},
  /**
   * handles thrown error exceptions based on user preferences
   *
   * @param {object} data
   * @param {any} exception
   * @return {void}
   */
  errorDataHandler: function (data, exception) {
    if (data.success !== true) {
      Functions.ajaxShowMessage(data.error, false);
      return;
    }
    if (data.report_setting === 'ask') {
      ErrorReport.showErrorNotification();
    } else if (data.report_setting === 'always') {
      var reportData = ErrorReport.getReportData(exception);
      var postData = $.extend(reportData, {
        'send_error_report': true,
        'automatic': true
      });
      $.post('index.php?route=/error-report', postData, function (data) {
        if (data.success === false) {
          // in the case of an error, show the error message returned.
          Functions.ajaxShowMessage(data.error, false);
        } else {
          Functions.ajaxShowMessage(data.message, false);
        }
      });
    }
  },
  errorHandler: function (exception) {
    // issue: 14359
    if (JSON.stringify(ErrorReport.lastException) === JSON.stringify(exception)) {
      return;
    }
    if (exception.name === null || typeof exception.name === 'undefined') {
      exception.name = ErrorReport.extractExceptionName(exception);
    }
    ErrorReport.lastException = exception;
    if (ErrorReport.errorReportData === null) {
      $.post('index.php?route=/error-report', {
        'ajax_request': true,
        'server': CommonParams.get('server'),
        'get_settings': true,
        'exception_type': 'js'
      }, function (data) {
        ErrorReport.errorReportData = data;
        ErrorReport.errorDataHandler(data, exception);
      });
    } else {
      ErrorReport.errorDataHandler(ErrorReport.errorReportData, exception);
    }
  },
  /**
   * Shows the modal dialog previewing the report
   *
   * @param exception object error report info
   *
   * @return {void}
   */
  showReportDialog: function (exception) {
    const reportData = ErrorReport.getReportData(exception);
    const sendErrorReport = function () {
      const postData = $.extend(reportData, {
        'send_error_report': true,
        'description': $('#errorReportDescription').val(),
        'always_send': $('#errorReportAlwaysSendCheckbox')[0].checked
      });
      $.post('index.php?route=/error-report', postData, function (data) {
        if (data.success === false) {
          Functions.ajaxShowMessage(data.error, false);
        } else {
          Functions.ajaxShowMessage(data.message, 3000);
        }
      });
      $('#errorReportModal').modal('hide');
    };
    $.post('index.php?route=/error-report', reportData).done(function (data) {
      // Delete the modal to refresh it in case the user changed SendErrorReports value
      if (document.getElementById('errorReportModal') !== null) {
        $('#errorReportModal').remove();
      }
      $('body').append($(data.report_modal));
      const $errorReportModal = $('#errorReportModal');
      $errorReportModal.on('show.bs.modal', function () {
        // Prevents multiple onClick events
        $('#errorReportModalConfirm').off('click', sendErrorReport);
        $('#errorReportModalConfirm').on('click', sendErrorReport);
        $('#errorReportModal .modal-body').html(data.message);
      });
      $errorReportModal.modal('show');
    });
  },
  /**
   * Shows the small notification that asks for user permission
   *
   * @return {void}
   */
  showErrorNotification: function () {
    var key = Math.random().toString(36).substring(2, 12);
    while (key in ErrorReport.keyDict) {
      key = Math.random().toString(36).substring(2, 12);
    }
    ErrorReport.keyDict[key] = 1;
    var $div = $('<div class="alert alert-danger" role="alert" id="error_notification_' + key + '"></div>').append(Functions.getImage('s_error') + Messages.strErrorOccurred);
    var $buttons = $('<div class="float-end"></div>');
    var buttonHtml = '<button class="btn btn-primary" id="show_error_report_' + key + '">';
    buttonHtml += Messages.strShowReportDetails;
    buttonHtml += '</button>';
    var settingsUrl = 'index.php?route=/preferences/features&server=' + CommonParams.get('server');
    buttonHtml += '<a class="ajax" href="' + settingsUrl + '">';
    buttonHtml += Functions.getImage('s_cog', Messages.strChangeReportSettings);
    buttonHtml += '</a>';
    buttonHtml += '<a href="#" id="ignore_error_' + key + '" data-notification-id="' + key + '">';
    buttonHtml += Functions.getImage('b_close', Messages.strIgnore);
    buttonHtml += '</a>';
    $buttons.html(buttonHtml);
    $div.append($buttons);
    // eslint-disable-next-line compat/compat
    $div.appendTo(document.body);
    $(document).on('click', '#show_error_report_' + key, ErrorReport.createReportDialog);
    $(document).on('click', '#ignore_error_' + key, ErrorReport.removeErrorNotification);
  },
  /**
   * Removes the notification if it was displayed before
   *
   * @param {Event} e
   * @return {void}
   */
  removeErrorNotification: function (e) {
    if (e) {
      // don't remove the hash fragment by navigating to #
      e.preventDefault();
    }
    $('#error_notification_' + $(this).data('notification-id')).fadeOut(function () {
      $(this).remove();
    });
  },
  /**
   * Extracts Exception name from message if it exists
   *
   * @param exception
   * @return {string}
   */
  extractExceptionName: function (exception) {
    if (exception.message === null || typeof exception.message === 'undefined') {
      return '';
    }
    var reg = /([a-zA-Z]+):/;
    var regexResult = reg.exec(exception.message);
    if (regexResult && regexResult.length === 2) {
      return regexResult[1];
    }
    return '';
  },
  /**
   * Shows the modal dialog previewing the report
   *
   * @return {void}
   */
  createReportDialog: function () {
    ErrorReport.removeErrorNotification();
    ErrorReport.showReportDialog(ErrorReport.lastException);
  },
  /**
   * Returns the report data to send to the server
   *
   * @param exception object exception info
   *
   * @return {object}
   */
  getReportData: function (exception) {
    if (exception && exception.stack && exception.stack.length) {
      for (var i = 0; i < exception.stack.length; i++) {
        var stack = exception.stack[i];
        if (stack.context && stack.context.length) {
          for (var j = 0; j < stack.context.length; j++) {
            if (stack.context[j].length > 80) {
              stack.context[j] = stack.context[j].substring(-1, 75) + '//...';
            }
          }
        }
      }
    }
    var reportData = {
      'server': CommonParams.get('server'),
      'ajax_request': true,
      'exception': exception,
      'url': window.location.href,
      'exception_type': 'js'
    };
    if (AJAX.scriptHandler.scripts.length > 0) {
      reportData.scripts = AJAX.scriptHandler.scripts.map(function (script) {
        return script;
      });
    }
    return reportData;
  },
  /**
   * Wraps given function in error reporting code and returns wrapped function
   *
   * @param {Function} func function to be wrapped
   *
   * @return {Function}
   */
  wrapFunction: function (func) {
    if (!func.wrapped) {
      var newFunc = function () {
        try {
          return func.apply(this, arguments);
        } catch (x) {
          TraceKit.report(x);
        }
      };
      newFunc.wrapped = true;
      // Set guid of wrapped function same as original function, so it can be removed
      // See bug#4146 (problem with jquery draggable and sortable)
      newFunc.guid = func.guid = func.guid || newFunc.guid || jQuery.guid++;
      return newFunc;
    } else {
      return func;
    }
  },
  /**
   * Automatically wraps the callback in AJAX.registerOnload
   *
   * @return {void}
   */
  wrapAjaxOnloadCallback: function () {
    var oldOnload = AJAX.registerOnload;
    AJAX.registerOnload = function (file, func) {
      var wrappedFunction = ErrorReport.wrapFunction(func);
      oldOnload.call(this, file, wrappedFunction);
    };
  },
  /**
   * Automatically wraps the callback in $.fn.on
   *
   * @return {void}
   */
  wrapJqueryOnCallback: function () {
    var oldOn = $.fn.on;
    $.fn.on = function () {
      for (var i = 1; i <= 3; i++) {
        if (typeof arguments[i] === 'function') {
          arguments[i] = ErrorReport.wrapFunction(arguments[i]);
          break;
        }
      }
      return oldOn.apply(this, arguments);
    };
  },
  /**
   * Wraps the callback in AJAX.registerOnload automatically
   *
   * @return {void}
   */
  setUpErrorReporting: function () {
    ErrorReport.wrapAjaxOnloadCallback();
    ErrorReport.wrapJqueryOnCallback();
  }
};
AJAX.registerOnload('error_report.js', function () {
  TraceKit.report.subscribe(ErrorReport.errorHandler);
  ErrorReport.setUpErrorReporting();
});