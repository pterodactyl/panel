/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('table/find_replace.js', function () {
  $('#find_replace_form').off('submit');
  $('#toggle_find').off('click');
});

/**
 * Bind events
 */
AJAX.registerOnload('table/find_replace.js', function () {
  $('<div id="toggle_find_div"><a id="toggle_find"></a></div>').insertAfter('#find_replace_form').hide();
  $('#toggle_find').html(Messages.strHideFindNReplaceCriteria).on('click', function () {
    var $link = $(this);
    $('#find_replace_form').slideToggle();
    if ($link.text() === Messages.strHideFindNReplaceCriteria) {
      $link.text(Messages.strShowFindNReplaceCriteria);
    } else {
      $link.text(Messages.strHideFindNReplaceCriteria);
    }
    return false;
  });
  $('#find_replace_form').on('submit', function (e) {
    e.preventDefault();
    var findReplaceForm = $('#find_replace_form');
    Functions.prepareForAjaxRequest(findReplaceForm);
    var $msgbox = Functions.ajaxShowMessage();
    $.post(findReplaceForm.attr('action'), findReplaceForm.serialize(), function (data) {
      Functions.ajaxRemoveMessage($msgbox);
      if (data.success === true) {
        $('#toggle_find_div').show();
        $('#toggle_find').trigger('click');
        $('#sqlqueryresultsouter').html(data.preview);
      } else {
        $('#sqlqueryresultsouter').html(data.error);
      }
    });
  });
});