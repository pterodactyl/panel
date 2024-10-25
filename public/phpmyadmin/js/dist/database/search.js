/**
 * JavaScript functions used on Database Search page
 *
 * @requires    jQuery
 * @requires    js/functions.js
 *
 * @package PhpMyAdmin
 */

/* global makeGrid */ // js/makegrid.js

/**
 * AJAX script for the Database Search page.
 *
 * Actions ajaxified here:
 * Retrieve result of SQL query
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('database/search.js', function () {
  $('a.browse_results').off('click');
  $('a.delete_results').off('click');
  $('#buttonGo').off('click');
  $('#togglesearchresultlink').off('click');
  $('#togglequerybox').off('click');
  $('#togglesearchformlink').off('click');
  $('#select_all').off('click');
  $('#unselect_all').off('click');
  $(document).off('submit', '#db_search_form.ajax');
});
AJAX.registerOnload('database/search.js', function () {
  /** Hide the table link in the initial search result */
  var icon = Functions.getImage('s_tbl', '', {
    'id': 'table-image'
  }).toString();
  $('#table-info').prepend(icon).hide();

  /** Hide the browse and deleted results in the new search criteria */
  $('#buttonGo').on('click', function () {
    $('#table-info').hide();
    $('#browse-results').hide();
    $('#sqlqueryform').hide();
    $('#togglequerybox').hide();
  });
  /**
   * Prepare a div containing a link for toggle the search results
   */
  $('#togglesearchresultsdiv')
  /** don't show it until we have results on-screen */.hide();

  /**
   * Changing the displayed text according to
   * the hide/show criteria in search result forms
   */
  $('#togglesearchresultlink').html(Messages.strHideSearchResults).on('click', function () {
    var $link = $(this);
    $('#searchresults').slideToggle();
    if ($link.text() === Messages.strHideSearchResults) {
      $link.text(Messages.strShowSearchResults);
    } else {
      $link.text(Messages.strHideSearchResults);
    }
    /** avoid default click action */
    return false;
  });

  /**
   * Prepare a div containing a link for toggle the search form,
   * otherwise it's incorrectly displayed after a couple of clicks
   */
  $('#togglesearchformdiv').hide(); // don't show it until we have results on-screen

  /**
   * Changing the displayed text according to
   * the hide/show criteria in search form
   */
  $('#togglequerybox').hide().on('click', function () {
    var $link = $(this);
    $('#sqlqueryform').slideToggle('medium');
    if ($link.text() === Messages.strHideQueryBox) {
      $link.text(Messages.strShowQueryBox);
    } else {
      $link.text(Messages.strHideQueryBox);
    }
    /** avoid default click action */
    return false;
  });

  /** don't show it until we have results on-screen */

  /**
   * Changing the displayed text according to
   * the hide/show criteria in search criteria form
   */
  $('#togglesearchformlink').html(Messages.strShowSearchCriteria).on('click', function () {
    var $link = $(this);
    $('#db_search_form').slideToggle();
    if ($link.text() === Messages.strHideSearchCriteria) {
      $link.text(Messages.strShowSearchCriteria);
    } else {
      $link.text(Messages.strHideSearchCriteria);
    }
    /** avoid default click action */
    return false;
  });

  /*
   * Ajax Event handler for retrieving the results from a table
   */
  $(document).on('click', 'a.browse_results', function (e) {
    e.preventDefault();
    /**   Hides the results shown by the delete criteria */
    var $msg = Functions.ajaxShowMessage(Messages.strBrowsing, false);
    $('#sqlqueryform').hide();
    $('#togglequerybox').hide();
    /**  Load the browse results to the page */
    $('#table-info').show();
    var tableName = $(this).data('table-name');
    $('#table-link').attr({
      'href': $(this).attr('href')
    }).text(tableName);
    var url = $(this).attr('href') + '#searchresults';
    var browseSql = $(this).data('browse-sql');
    var params = {
      'ajax_request': true,
      'is_js_confirmed': true,
      'sql_query': browseSql
    };
    $.post(url, params, function (data) {
      if (typeof data !== 'undefined' && data.success) {
        $('#browse-results').html(data.message);
        Functions.ajaxRemoveMessage($msg);
        $('.table_results').each(function () {
          makeGrid(this, true, true, true, true);
        });
        $('#browse-results').show();
        Functions.highlightSql($('#browse-results'));
        $('html, body').animate({
          scrollTop: $('#browse-results').offset().top
        }, 1000);
      } else {
        Functions.ajaxShowMessage(data.error, false);
      }
    });
  });

  /*
   * Ajax Event handler for deleting the results from a table
   */
  $(document).on('click', 'a.delete_results', function (e) {
    e.preventDefault();
    /**  Hides the results shown by the browse criteria */
    $('#table-info').hide();
    $('#sqlqueryform').hide();
    $('#togglequerybox').hide();
    /** Conformation message for deletion */
    var msg = Functions.sprintf(Messages.strConfirmDeleteResults, $(this).data('table-name'));
    if (confirm(msg)) {
      var $msg = Functions.ajaxShowMessage(Messages.strDeleting, false);
      /** Load the deleted option to the page*/
      $('#sqlqueryform').html('');
      var params = {
        'ajax_request': true,
        'is_js_confirmed': true,
        'sql_query': $(this).data('delete-sql')
      };
      var url = $(this).attr('href');
      $.post(url, params, function (data) {
        if (typeof data === 'undefined' || !data.success) {
          Functions.ajaxShowMessage(data.error, false);
          return;
        }
        $('#sqlqueryform').html(data.sql_query);
        /** Refresh the search results after the deletion */
        $('#buttonGo').trigger('click');
        $('#togglequerybox').html(Messages.strHideQueryBox);
        /** Show the results of the deletion option */
        $('#browse-results').hide();
        $('#sqlqueryform').show();
        $('#togglequerybox').show();
        $('html, body').animate({
          scrollTop: $('#browse-results').offset().top
        }, 1000);
        Functions.ajaxRemoveMessage($msg);
      });
    }
  });

  /**
   * Ajax Event handler for retrieving the result of an SQL Query
   */
  $(document).on('submit', '#db_search_form.ajax', function (event) {
    event.preventDefault();
    if ($('#criteriaTables :selected').length === 0) {
      Functions.ajaxShowMessage(Messages.strNoTableSelected);
      return;
    }
    var $msgbox = Functions.ajaxShowMessage(Messages.strSearching, false);
    // jQuery object to reuse
    var $form = $(this);
    Functions.prepareForAjaxRequest($form);
    var url = $form.serialize() + CommonParams.get('arg_separator') + 'submit_search=' + $('#buttonGo').val();
    $.post($form.attr('action'), url, function (data) {
      if (typeof data !== 'undefined' && data.success === true) {
        // found results
        $('#searchresults').html(data.message);
        $('#togglesearchresultlink')
        // always start with the Show message
        .text(Messages.strHideSearchResults);
        $('#togglesearchresultsdiv')
        // now it's time to show the div containing the link
        .show();
        $('#searchresults').show();
        $('#db_search_form')
        // workaround for Chrome problem (bug #3168569)
        .slideToggle().hide();
        $('#togglesearchformlink')
        // always start with the Show message
        .text(Messages.strShowSearchCriteria);
        $('#togglesearchformdiv')
        // now it's time to show the div containing the link
        .show();
      } else {
        // error message (zero rows)
        $('#searchresults').html(data.error).show();
      }
      Functions.ajaxRemoveMessage($msgbox);
    });
  });
  $('#select_all').on('click', function () {
    Functions.setSelectOptions('db_search', 'criteriaTables[]', true);
    return false;
  });
  $('#unselect_all').on('click', function () {
    Functions.setSelectOptions('db_search', 'criteriaTables[]', false);
    return false;
  });
}); // end $()