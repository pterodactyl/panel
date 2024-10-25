/**
 *
 *
 * @package PhpMyAdmin
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server/status/variables.js', function () {
  $('#filterAlert').off('change');
  $('#filterText').off('keyup');
  $('#filterCategory').off('change');
  $('#dontFormat').off('change');
});
AJAX.registerOnload('server/status/variables.js', function () {
  // Filters for status variables
  var textFilter = null;
  var alertFilter = $('#filterAlert').prop('checked');
  var categoryFilter = $('#filterCategory').find(':selected').val();
  var text = ''; // Holds filter text

  /* 3 Filtering functions */
  $('#filterAlert').on('change', function () {
    alertFilter = this.checked;
    filterVariables();
  });
  $('#filterCategory').on('change', function () {
    categoryFilter = $(this).val();
    filterVariables();
  });
  $('#dontFormat').on('change', function () {
    // Hiding the table while changing values speeds up the process a lot
    const serverStatusVariables = $('#serverStatusVariables');
    serverStatusVariables.hide();
    serverStatusVariables.find('td.value span.original').toggle(this.checked);
    serverStatusVariables.find('td.value span.formatted').toggle(!this.checked);
    serverStatusVariables.show();
  }).trigger('change');
  $('#filterText').on('keyup', function () {
    var word = $(this).val().replace(/_/g, ' ');
    if (word.length === 0 || word.length >= 32768) {
      textFilter = null;
    } else {
      try {
        textFilter = new RegExp('(^| )' + word, 'i');
        $(this).removeClass('error');
      } catch (e) {
        if (e instanceof SyntaxError) {
          $(this).addClass('error');
          textFilter = null;
        }
      }
    }
    text = word;
    filterVariables();
  }).trigger('keyup');

  /* Filters the status variables by name/category/alert in the variables tab */
  function filterVariables() {
    var usefulLinks = 0;
    var section = text;
    if (categoryFilter.length > 0) {
      section = categoryFilter;
    }
    if (section.length > 1) {
      $('#linkSuggestions').find('span').each(function () {
        if ($(this).attr('class').indexOf('status_' + section) !== -1) {
          usefulLinks++;
          $(this).css('display', '');
        } else {
          $(this).css('display', 'none');
        }
      });
    }
    if (usefulLinks > 0) {
      $('#linkSuggestions').css('display', '');
    } else {
      $('#linkSuggestions').css('display', 'none');
    }
    $('#serverStatusVariables').find('th.name').each(function () {
      if ((textFilter === null || textFilter.exec($(this).text())) && (!alertFilter || $(this).next().find('span.text-danger').length > 0) && (categoryFilter.length === 0 || $(this).parent().hasClass('s_' + categoryFilter))) {
        $(this).parent().css('display', '');
      } else {
        $(this).parent().css('display', 'none');
      }
    });
  }
});