/**
 * @fileoverview    Javascript functions used in server variables page
 * @name            Server Replication
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 */
/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server/variables.js', function () {
    $(document).off('click', 'a.editLink');
    $('#serverVariables').find('.var-name').find('a img').remove();
});

AJAX.registerOnload('server/variables.js', function () {
    var $saveLink = $('a.saveLink');
    var $cancelLink = $('a.cancelLink');

    $('#serverVariables').find('.var-name').find('a').append(
        $('#docImage').clone().css('display', 'inline-block')
    );

    /* Launches the variable editor */
    $(document).on('click', 'a.editLink', function (event) {
        event.preventDefault();
        editVariable(this);
    });

    /* Allows the user to edit a server variable */
    function editVariable (link) {
        var $link = $(link);
        var $cell = $link.parent();
        var $valueCell = $link.parents('.var-row').find('.var-value');
        var varName = $link.data('variable');

        var $mySaveLink = $saveLink.clone().css('display', 'inline-block');
        var $myCancelLink = $cancelLink.clone().css('display', 'inline-block');
        var $msgbox = Functions.ajaxShowMessage();
        var $myEditLink = $cell.find('a.editLink');
        $cell.addClass('edit'); // variable is being edited
        $myEditLink.remove(); // remove edit link

        $mySaveLink.on('click', function () {
            var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
            $.post('index.php?route=/server/variables/set/' + encodeURIComponent(varName), {
                'ajax_request': true,
                'server': CommonParams.get('server'),
                'varValue': $valueCell.find('input').val()
            }, function (data) {
                if (data.success) {
                    $valueCell
                        .html(data.variable)
                        .data('content', data.variable);
                    Functions.ajaxRemoveMessage($msgbox);
                } else {
                    if (data.error === '') {
                        Functions.ajaxShowMessage(Messages.strRequestFailed, false);
                    } else {
                        Functions.ajaxShowMessage(data.error, false);
                    }
                    $valueCell.html($valueCell.data('content'));
                }
                $cell.removeClass('edit').html($myEditLink);
            });
            return false;
        });

        $myCancelLink.on('click', function () {
            $valueCell.html($valueCell.data('content'));
            $cell.removeClass('edit').html($myEditLink);
            return false;
        });

        $.get('index.php?route=/server/variables/get/' + encodeURIComponent(varName), {
            'ajax_request': true,
            'server': CommonParams.get('server')
        }, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                var $links = $('<div></div>')
                    .append($myCancelLink)
                    .append('&nbsp;&nbsp;&nbsp;')
                    .append($mySaveLink);
                var $editor = $('<div></div>', { 'class': 'serverVariableEditor' })
                    .append(
                        $('<div></div>').append(
                            $('<input>', { type: 'text', 'class': 'form-control form-control-sm' }).val(data.message)
                        )
                    );
                    // Save and replace content
                $cell
                    .html($links)
                    .children()
                    .css('display', 'flex');
                $valueCell
                    .data('content', $valueCell.html())
                    .html($editor)
                    .find('input')
                    .trigger('focus')
                    .on('keydown', function (event) { // Keyboard shortcuts
                        if (event.keyCode === 13) { // Enter key
                            $mySaveLink.trigger('click');
                        } else if (event.keyCode === 27) { // Escape key
                            $myCancelLink.trigger('click');
                        }
                    });
                Functions.ajaxRemoveMessage($msgbox);
            } else {
                $cell.removeClass('edit').html($myEditLink);
                Functions.ajaxShowMessage(data.error);
            }
        });
    }
});
