/**
 * @fileoverview    functions used in server privilege pages
 * @name            Server Privileges
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/**
 * Validates the "add a user" form
 *
 * @param theForm
 *
 * @return {bool} whether the form is validated or not
 */
function checkAddUser (theForm) {
    if (theForm.elements.hostname.value === '') {
        alert(Messages.strHostEmpty);
        theForm.elements.hostname.focus();
        return false;
    }

    if ((theForm.elements.pred_username && theForm.elements.pred_username.value === 'userdefined') && theForm.elements.username.value === '') {
        alert(Messages.strUserEmpty);
        theForm.elements.username.focus();
        return false;
    }

    return Functions.checkPassword($(theForm));
}

/**
 * Export privileges modal handler
 *
 * @param {object} data
 *
 * @param {JQuery} msgbox
 *
 */
function exportPrivilegesModalHandler (data, msgbox) {
    if (typeof data !== 'undefined' && data.success === true) {
        var modal = $('#exportPrivilegesModal');
        // Remove any previous privilege modal data, if any
        modal.find('.modal-body').first().html('');
        $('#exportPrivilegesModalLabel').first().html('Loading');
        modal.modal('show');
        modal.on('shown.bs.modal', function () {
            modal.find('.modal-body').first().html(data.message);
            $('#exportPrivilegesModalLabel').first().html(data.title);
            Functions.ajaxRemoveMessage(msgbox);
            // Attach syntax highlighted editor to export dialog
            Functions.getSqlEditor(modal.find('textarea'));
        });
        return;
    }
    Functions.ajaxShowMessage(data.error, false);
}

/**
 * @implements EventListener
 */
const EditUserGroup = {
    /**
     * @param {MouseEvent} event
     */
    handleEvent: function (event) {
        const editUserGroupModal = document.getElementById('editUserGroupModal');
        const button = event.relatedTarget;
        const username = button.getAttribute('data-username');

        $.get(
            'index.php?route=/server/user-groups/edit-form',
            {
                'username': username,
                'server': CommonParams.get('server')
            },
            data => {
                if (typeof data === 'undefined' || data.success !== true) {
                    Functions.ajaxShowMessage(data.error, false, 'error');

                    return;
                }

                const modal = bootstrap.Modal.getInstance(editUserGroupModal);
                const modalBody = editUserGroupModal.querySelector('.modal-body');
                const saveButton = editUserGroupModal.querySelector('#editUserGroupModalSaveButton');

                modalBody.innerHTML = data.message;

                saveButton.addEventListener('click', () => {
                    const form = $(editUserGroupModal.querySelector('#changeUserGroupForm'));

                    $.post(
                        'index.php?route=/server/privileges',
                        form.serialize() + CommonParams.get('arg_separator') + 'ajax_request=1',
                        data => {
                            if (typeof data === 'undefined' || data.success !== true) {
                                Functions.ajaxShowMessage(data.error, false, 'error');

                                return;
                            }

                            const userGroup = form.serializeArray().find(el => el.name === 'userGroup').value;
                            // button -> td -> tr -> td.usrGroup
                            const userGroupTableCell = button.parentElement.parentElement.querySelector('.usrGroup');
                            userGroupTableCell.textContent = userGroup;
                        }
                    );

                    modal.hide();
                });
            }
        );
    }
};

/**
 * @implements EventListener
 */
const AccountLocking = {
    handleEvent: function () {
        const button = this;
        const isLocked = button.dataset.isLocked === 'true';
        const url = isLocked
            ? 'index.php?route=/server/privileges/account-unlock'
            : 'index.php?route=/server/privileges/account-lock';
        const params = {
            'username': button.dataset.userName,
            'hostname': button.dataset.hostName,
            'ajax_request': true,
            'server': CommonParams.get('server'),
        };

        $.post(url, params, data => {
            if (data.success === false) {
                Functions.ajaxShowMessage(data.error);
                return;
            }

            if (isLocked) {
                const lockIcon = Functions.getImage('s_lock', Messages.strLock, {}).toString();
                button.innerHTML = '<span class="text-nowrap">' + lockIcon + ' ' + Messages.strLock + '</span>';
                button.title = Messages.strLockAccount;
                button.dataset.isLocked = 'false';
            } else {
                const unlockIcon = Functions.getImage('s_unlock', Messages.strUnlock, {}).toString();
                button.innerHTML = '<span class="text-nowrap">' + unlockIcon + ' ' + Messages.strUnlock + '</span>';
                button.title = Messages.strUnlockAccount;
                button.dataset.isLocked = 'true';
            }

            Functions.ajaxShowMessage(data.message);
        });
    }
};

/**
 * AJAX scripts for /server/privileges page.
 *
 * Actions ajaxified here:
 * Add user
 * Revoke a user
 * Edit privileges
 * Export privileges
 * Paginate table of users
 * Flush privileges
 *
 * @memberOf    jQuery
 * @name        document.ready
 */


/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server/privileges.js', function () {
    $('#fieldset_add_user_login').off('change', 'input[name=\'username\']');
    $(document).off('click', '#deleteUserCard .btn.ajax');

    const editUserGroupModal = document.getElementById('editUserGroupModal');
    if (editUserGroupModal) {
        editUserGroupModal.removeEventListener('show.bs.modal', EditUserGroup);
    }

    $(document).off('click', 'button.mult_submit[value=export]');
    $(document).off('click', 'a.export_user_anchor.ajax');
    $('button.jsAccountLocking').off('click');
    $('#dropUsersDbCheckbox').off('click');
    $(document).off('click', '.checkall_box');
    $(document).off('change', '#checkbox_SSL_priv');
    $(document).off('change', 'input[name="ssl_type"]');
    $(document).off('change', '#select_authentication_plugin');
});

AJAX.registerOnload('server/privileges.js', function () {
    /**
     * Display a warning if there is already a user by the name entered as the username.
     */
    $('#fieldset_add_user_login').on('change', 'input[name=\'username\']', function () {
        var username = $(this).val();
        var $warning = $('#user_exists_warning');
        if ($('#select_pred_username').val() === 'userdefined' && username !== '') {
            var href = $('form[name=\'usersForm\']').attr('action');
            var params = {
                'ajax_request' : true,
                'server' : CommonParams.get('server'),
                'validate_username' : true,
                'username' : username
            };
            $.get(href, params, function (data) {
                if (data.user_exists) {
                    $warning.show();
                } else {
                    $warning.hide();
                }
            });
        } else {
            $warning.hide();
        }
    });

    /**
     * Indicating password strength
     */
    $('#text_pma_pw').on('keyup', function () {
        var meterObj = $('#password_strength_meter');
        var meterObjLabel = $('#password_strength');
        var username = $('input[name="username"]');
        username = username.val();
        Functions.checkPasswordStrength($(this).val(), meterObj, meterObjLabel, username);
    });

    /**
     * Automatically switching to 'Use Text field' from 'No password' once start writing in text area
     */
    $('#text_pma_pw').on('input', function () {
        if ($('#text_pma_pw').val() !== '') {
            $('#select_pred_password').val('userdefined');
        }
    });

    $('#text_pma_change_pw').on('keyup', function () {
        var meterObj = $('#change_password_strength_meter');
        var meterObjLabel = $('#change_password_strength');
        Functions.checkPasswordStrength($(this).val(), meterObj, meterObjLabel, CommonParams.get('user'));
    });

    /**
     * Display a notice if sha256_password is selected
     */
    $(document).on('change', '#select_authentication_plugin', function () {
        var selectedPlugin = $(this).val();
        if (selectedPlugin === 'sha256_password') {
            $('#ssl_reqd_warning').show();
        } else {
            $('#ssl_reqd_warning').hide();
        }
    });

    /**
     * AJAX handler for 'Revoke User'
     *
     * @see         Functions.ajaxShowMessage()
     * @memberOf    jQuery
     * @name        revoke_user_click
     */
    $(document).on('click', '#deleteUserCard .btn.ajax', function (event) {
        event.preventDefault();

        var $thisButton = $(this);
        var $form = $('#usersForm');

        $thisButton.confirm(Messages.strDropUserWarning, $form.attr('action'), function (url) {
            var $dropUsersDbCheckbox = $('#dropUsersDbCheckbox');
            if ($dropUsersDbCheckbox.is(':checked')) {
                var isConfirmed = confirm(Messages.strDropDatabaseStrongWarning + '\n' + Functions.sprintf(Messages.strDoYouReally, 'DROP DATABASE'));
                if (! isConfirmed) {
                    // Uncheck the drop users database checkbox
                    $dropUsersDbCheckbox.prop('checked', false);
                }
            }

            Functions.ajaxShowMessage(Messages.strRemovingSelectedUsers);

            var argsep = CommonParams.get('arg_separator');
            $.post(url, $form.serialize() + argsep + 'delete=' + $thisButton.val() + argsep + 'ajax_request=true', function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    Functions.ajaxShowMessage(data.message);
                    // Refresh navigation, if we dropped some databases with the name
                    // that is the same as the username of the deleted user
                    if ($('#dropUsersDbCheckbox:checked').length) {
                        Navigation.reload();
                    }
                    // Remove the revoked user from the users list
                    $form.find('input:checkbox:checked').parents('tr').slideUp('medium', function () {
                        var thisUserInitial = $(this).find('input:checkbox').val().charAt(0).toUpperCase();
                        $(this).remove();

                        // If this is the last user with thisUserInitial, remove the link from #userAccountsPagination
                        if ($('#userRightsTable').find('input:checkbox[value^="' + thisUserInitial + '"], input:checkbox[value^="' + thisUserInitial.toLowerCase() + '"]').length === 0) {
                            $('#userAccountsPagination')
                                .find('.page-item > .page-link:contains(' + thisUserInitial + ')')
                                .parent('.page-item')
                                .addClass('disabled')
                                .html('<a class="page-link" href="#" tabindex="-1" aria-disabled="true">' + thisUserInitial + '</a>');
                        }

                        // Re-check the classes of each row
                        $form
                            .find('tbody').find('tr').each(function (index) {
                                if (index >= 0 && index % 2 === 0) {
                                    $(this).removeClass('odd').addClass('even');
                                } else if (index >= 0 && index % 2 !== 0) {
                                    $(this).removeClass('even').addClass('odd');
                                }
                            });
                        // update the checkall checkbox
                        $(Functions.checkboxesSel).trigger('change');
                    });
                } else {
                    Functions.ajaxShowMessage(data.error, false);
                }
            }); // end $.post()
        });
    }); // end Revoke User

    const editUserGroupModal = document.getElementById('editUserGroupModal');
    if (editUserGroupModal) {
        editUserGroupModal.addEventListener('show.bs.modal', EditUserGroup);
    }

    /**
     * AJAX handler for 'Export Privileges'
     *
     * @see         Functions.ajaxShowMessage()
     * @memberOf    jQuery
     * @name        export_user_click
     */
    $(document).on('click', 'button.mult_submit[value=export]', function (event) {
        event.preventDefault();
        // can't export if no users checked
        if ($(this.form).find('input:checked').length === 0) {
            Functions.ajaxShowMessage(Messages.strNoAccountSelected, 2000, 'success');
            return;
        }
        var msgbox = Functions.ajaxShowMessage();
        var argsep = CommonParams.get('arg_separator');
        var serverId = CommonParams.get('server');
        var selectedUsers = $('#usersForm input[name*=\'selected_usr\']:checkbox').serialize();
        var postStr = selectedUsers + '&submit_mult=export' + argsep + 'ajax_request=true&server=' + serverId;

        $.post($(this.form).prop('action'), postStr, function (data) {
            exportPrivilegesModalHandler(data, msgbox);
        }); // end $.post
    });
    // if exporting non-ajax, highlight anyways
    Functions.getSqlEditor($('textarea.export'));

    $(document).on('click', 'a.export_user_anchor.ajax', function (event) {
        event.preventDefault();
        var msgbox = Functions.ajaxShowMessage();
        $.get($(this).attr('href'), { 'ajax_request': true }, function (data) {
            exportPrivilegesModalHandler(data, msgbox);
        }); // end $.get
    }); // end export privileges

    $('button.jsAccountLocking').on('click', AccountLocking.handleEvent);

    $(document).on('change', 'input[name="ssl_type"]', function () {
        var $div = $('#specified_div');
        if ($('#ssl_type_SPECIFIED').is(':checked')) {
            $div.find('input').prop('disabled', false);
        } else {
            $div.find('input').prop('disabled', true);
        }
    });

    $(document).on('change', '#checkbox_SSL_priv', function () {
        var $div = $('#require_ssl_div');
        if ($(this).is(':checked')) {
            $div.find('input').prop('disabled', false);
            $('#ssl_type_SPECIFIED').trigger('change');
        } else {
            $div.find('input').prop('disabled', true);
        }
    });

    $('#checkbox_SSL_priv').trigger('change');

    /*
     * Create submenu for simpler interface
     */
    var addOrUpdateSubmenu = function () {
        var $subNav = $('.nav-pills');
        var $editUserDialog = $('#edit_user_dialog');
        var submenuLabel;
        var submenuLink;
        var linkNumber;

        // if submenu exists yet, remove it first
        if ($subNav.length > 0) {
            $subNav.remove();
        }

        // construct a submenu from the existing fieldsets
        $subNav = $('<ul></ul>').prop('class', 'nav nav-pills m-2');

        $('#edit_user_dialog .submenu-item').each(function () {
            submenuLabel = $(this).find('legend[data-submenu-label]').data('submenu-label');

            submenuLink = $('<a></a>')
                .prop('class', 'nav-link')
                .prop('href', '#')
                .html(submenuLabel);

            $('<li></li>')
                .prop('class', 'nav-item')
                .append(submenuLink)
                .appendTo($subNav);
        });

        // click handlers for submenu
        $subNav.find('a').on('click', function (e) {
            e.preventDefault();
            // if already active, ignore click
            if ($(this).hasClass('active')) {
                return;
            }
            $subNav.find('a').removeClass('active');
            $(this).addClass('active');

            // which section to show now?
            linkNumber = $subNav.find('a').index($(this));
            // hide all sections but the one to show
            $('#edit_user_dialog .submenu-item').hide().eq(linkNumber).show();
        });

        // make first menu item active
        // TODO: support URL hash history
        $subNav.find('> :first-child a').addClass('active');
        $editUserDialog.prepend($subNav);

        // hide all sections but the first
        $('#edit_user_dialog .submenu-item').hide().eq(0).show();

        // scroll to the top
        $('html, body').animate({ scrollTop: 0 }, 'fast');
    };

    $('input.autofocus').trigger('focus');
    $(Functions.checkboxesSel).trigger('change');
    Functions.displayPasswordGenerateButton();
    if ($('#edit_user_dialog').length > 0) {
        addOrUpdateSubmenu();
    }

    /**
     * Select all privileges
     *
     * @param {HTMLElement} e
     * @return {void}
     */
    var tableSelectAll = function (e) {
        const method = e.target.getAttribute('data-select-target');
        var options = $(method).first().children();
        options.each(function (_, obj) {
            obj.selected = true;
        });
    };

    $('#select_priv_all').on('click', tableSelectAll);
    $('#insert_priv_all').on('click', tableSelectAll);
    $('#update_priv_all').on('click', tableSelectAll);
    $('#references_priv_all').on('click', tableSelectAll);

    var windowWidth = $(window).width();
    $('.jsresponsive').css('max-width', (windowWidth - 35) + 'px');

    $('#addUsersForm').on('submit', function () {
        return checkAddUser(this);
    });

    $('#copyUserForm').on('submit', function () {
        return checkAddUser(this);
    });
});
