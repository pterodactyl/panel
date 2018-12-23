"use strict";

// Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all
// copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE.
class ActionsClass {
    constructor(element, menu) {
        this.element = element;
        this.menu = menu;
    }

    destroy() {
        this.element = undefined;
    }

    folder(path) {
        let inputValue
        if (path) {
            inputValue = path
        } else {
            const nameBlock = $(this.element).find('td[data-identifier="name"]');
            const currentName = decodeURIComponent(nameBlock.data('name'));
            const currentPath = decodeURIComponent(nameBlock.data('path'));

            if ($(this.element).data('type') === 'file') {
                inputValue = currentPath;
            } else {
                inputValue = `${currentPath}${currentName}/`;
            }
        }

        swal({
            type: 'input',
            title: 'Create Folder',
            text: 'Please enter the path and folder name below.',
            showCancelButton: true,
            showConfirmButton: true,
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
            inputValue: inputValue
        }, (val) => {
            if (val === false) {
                return false;
            }

            $.ajax({
                type: 'POST',
                headers: {
                    'X-Access-Token': Pterodactyl.server.daemonSecret,
                    'X-Access-Server': Pterodactyl.server.uuid,
                },
                contentType: 'application/json; charset=utf-8',
                url: `${Pterodactyl.node.scheme}://${Pterodactyl.node.fqdn}:${Pterodactyl.node.daemonListen}/v1/server/file/folder`,
                timeout: 10000,
                data: JSON.stringify({
                    path: val,
                }),
            }).done(data => {
                swal.close();
                Files.list();
            }).fail(jqXHR => {
                console.error(jqXHR);
                var error = 'An error occurred while trying to process this request.';
                if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                    error = jqXHR.responseJSON.error;
                }
                swal({
                    type: 'error',
                    title: '',
                    text: error,
                });
            });
        });
    }

    move() {
        const nameBlock = $(this.element).find('td[data-identifier="name"]');
        const currentName = decodeURIComponent(nameBlock.attr('data-name'));
        const currentPath = decodeURIComponent(nameBlock.data('path'));

        swal({
            type: 'input',
            title: 'Move File',
            text: 'Please enter the new path for the file below.',
            showCancelButton: true,
            showConfirmButton: true,
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
            inputValue: `${currentPath}${currentName}`,
        }, (val) => {
            if (val === false) {
                return false;
            }

            $.ajax({
                type: 'POST',
                headers: {
                    'X-Access-Token': Pterodactyl.server.daemonSecret,
                    'X-Access-Server': Pterodactyl.server.uuid,
                },
                contentType: 'application/json; charset=utf-8',
                url: `${Pterodactyl.node.scheme}://${Pterodactyl.node.fqdn}:${Pterodactyl.node.daemonListen}/v1/server/file/move`,
                timeout: 10000,
                data: JSON.stringify({
                    from: `${currentPath}${currentName}`,
                    to: `${val}`,
                }),
            }).done(data => {
                nameBlock.parent().addClass('warning').delay(200).fadeOut();
                swal.close();
            }).fail(jqXHR => {
                console.error(jqXHR);
                var error = 'An error occurred while trying to process this request.';
                if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                    error = jqXHR.responseJSON.error;
                }
                swal({
                    type: 'error',
                    title: '',
                    text: error,
                });
            });
        });

    }

    rename() {
        const nameBlock = $(this.element).find('td[data-identifier="name"]');
        const currentLink = nameBlock.find('a');
        const currentName = decodeURIComponent(nameBlock.attr('data-name'));
        const attachEditor = `
            <input class="form-control input-sm" type="text" value="${currentName}" />
            <span class="input-loader"><i class="fa fa-refresh fa-spin fa-fw"></i></span>
        `;

        nameBlock.html(attachEditor);
        const inputField = nameBlock.find('input');
        const inputLoader = nameBlock.find('.input-loader');

        inputField.focus();
        inputField.on('blur keydown', e => {
            // Save Field
            if (
                (e.type === 'keydown' && e.which === 27)
                || e.type === 'blur'
                || (e.type === 'keydown' && e.which === 13 && currentName === inputField.val())
            ) {
                if (!_.isEmpty(currentLink)) {
                    nameBlock.html(currentLink);
                } else {
                    nameBlock.html(currentName);
                }
                inputField.remove();
                ContextMenu.unbind().run();
                return;
            }

            if (e.type === 'keydown' && e.which !== 13) return;

            inputLoader.show();
            const currentPath = decodeURIComponent(nameBlock.data('path'));

            $.ajax({
                type: 'POST',
                headers: {
                    'X-Access-Token': Pterodactyl.server.daemonSecret,
                    'X-Access-Server': Pterodactyl.server.uuid,
                },
                contentType: 'application/json; charset=utf-8',
                url: `${Pterodactyl.node.scheme}://${Pterodactyl.node.fqdn}:${Pterodactyl.node.daemonListen}/v1/server/file/rename`,
                timeout: 10000,
                data: JSON.stringify({
                    from: `${currentPath}${currentName}`,
                    to: `${currentPath}${inputField.val()}`,
                }),
            }).done(data => {
                nameBlock.attr('data-name', inputField.val());
                if (!_.isEmpty(currentLink)) {
                    let newLink = currentLink.attr('href');
                    if (nameBlock.parent().data('type') !== 'folder') {
                        newLink = newLink.substr(0, newLink.lastIndexOf('/')) + '/' + inputField.val();
                    }
                    currentLink.attr('href', newLink);
                    nameBlock.html(
                        currentLink.html(inputField.val())
                    );
                } else {
                    nameBlock.html(inputField.val());
                }
                inputField.remove();
            }).fail(jqXHR => {
                console.error(jqXHR);
                var error = 'An error occurred while trying to process this request.';
                if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                    error = jqXHR.responseJSON.error;
                }
                nameBlock.addClass('has-error').delay(2000).queue(() => {
                    nameBlock.removeClass('has-error').dequeue();
                });
                inputField.popover({
                    animation: true,
                    placement: 'top',
                    content: error,
                    title: 'Save Error'
                }).popover('show');
            }).always(() => {
                inputLoader.remove();
                ContextMenu.unbind().run();
            });
        });
    }

    copy() {
        const nameBlock = $(this.element).find('td[data-identifier="name"]');
        const currentName = decodeURIComponent(nameBlock.attr('data-name'));
        const currentPath = decodeURIComponent(nameBlock.data('path'));

        swal({
            type: 'input',
            title: 'Copy File',
            text: 'Please enter the new path for the copied file below.',
            showCancelButton: true,
            showConfirmButton: true,
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
            inputValue: `${currentPath}${currentName}`,
        }, (val) => {
            if (val === false) {
                return false;
            }

            $.ajax({
                type: 'POST',
                headers: {
                    'X-Access-Token': Pterodactyl.server.daemonSecret,
                    'X-Access-Server': Pterodactyl.server.uuid,
                },
                contentType: 'application/json; charset=utf-8',
                url: `${Pterodactyl.node.scheme}://${Pterodactyl.node.fqdn}:${Pterodactyl.node.daemonListen}/v1/server/file/copy`,
                timeout: 10000,
                data: JSON.stringify({
                    from: `${currentPath}${currentName}`,
                    to: `${val}`,
                }),
            }).done(data => {
                swal({
                    type: 'success',
                    title: '',
                    text: 'File successfully copied.'
                });
                Files.list();
            }).fail(jqXHR => {
                console.error(jqXHR);
                var error = 'An error occurred while trying to process this request.';
                if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                    error = jqXHR.responseJSON.error;
                }
                swal({
                    type: 'error',
                    title: '',
                    text: error,
                });
            });
        });
    }

    download() {
        const nameBlock = $(this.element).find('td[data-identifier="name"]');
        const fileName = decodeURIComponent(nameBlock.attr('data-name'));
        const filePath = decodeURIComponent(nameBlock.data('path'));

        window.location = `/server/${Pterodactyl.server.uuidShort}/files/download/${filePath}${fileName}`;
    }

    delete() {
        const nameBlock = $(this.element).find('td[data-identifier="name"]');
        const delPath = decodeURIComponent(nameBlock.data('path'));
        const delName = decodeURIComponent(nameBlock.data('name'));

        swal({
            type: 'warning',
            title: '',
            text: 'Are you sure you want to delete <code>' + delName + '</code>?',
            html: true,
            showCancelButton: true,
            showConfirmButton: true,
            closeOnConfirm: false,
            showLoaderOnConfirm: true
        }, () => {
            $.ajax({
                type: 'POST',
                headers: {
                    'X-Access-Token': Pterodactyl.server.daemonSecret,
                    'X-Access-Server': Pterodactyl.server.uuid,
                },
                contentType: 'application/json; charset=utf-8',
                url: `${Pterodactyl.node.scheme}://${Pterodactyl.node.fqdn}:${Pterodactyl.node.daemonListen}/v1/server/file/delete`,
                timeout: 10000,
                data: JSON.stringify({
                    items: [`${delPath}${delName}`]
                }),
            }).done(data => {
                nameBlock.parent().addClass('warning').delay(200).fadeOut();
                swal({
                    type: 'success',
                    title: 'File Deleted'
                });
            }).fail(jqXHR => {
                console.error(jqXHR);
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    html: true,
                    text: 'An error occurred while attempting to delete this file. Please try again.',
                });
            });
        });
    }

    toggleMassActions() {
        if ($('#file_listing input[type="checkbox"]:checked').length) {
            $('#mass_actions').removeClass('disabled');
        } else {
            $('#mass_actions').addClass('disabled');
        }
    }

    toggleHighlight(event) {
        const parent = $(event.currentTarget);
        const item = $(event.currentTarget).find('input');

        if($(item).is(':checked')) {
            $(item).prop('checked', false);
            parent.removeClass('warning').delay(200);
        } else {
            $(item).prop('checked', true);
            parent.addClass('warning').delay(200);
        }
    }

    highlightAll(event) {
        let parent;
        const item = $(event.currentTarget).find('input');

        if($(item).is(':checked')) {
          $('#file_listing input[type=checkbox]').prop('checked', false);
          $('#file_listing input[data-action="addSelection"]').each(function() {
              parent = $(this).closest('tr');
              parent.removeClass('warning').delay(200);
          });
        } else {
          $('#file_listing input[type=checkbox]').prop('checked', true);
          $('#file_listing input[data-action="addSelection"]').each(function() {
              parent = $(this).closest('tr');
              parent.addClass('warning').delay(200);
          });
        }
    }

    deleteSelected() {
        let selectedItems = [];
        let selectedItemsElements = [];
        let parent;
        let nameBlock;
        let delLocation;

        $('#file_listing input[data-action="addSelection"]:checked').each(function() {
            parent = $(this).closest('tr');
            nameBlock = $(parent).find('td[data-identifier="name"]');
            delLocation = decodeURIComponent(nameBlock.data('path')) + decodeURIComponent(nameBlock.data('name'));

            selectedItems.push(delLocation);
            selectedItemsElements.push(parent);
        });

        if (selectedItems.length != 0)
        {
            let formattedItems = "";
            let i = 0;
            $.each(selectedItems, function(key, value) {
                formattedItems += ("<code>" + value + "</code>, ");
                i++;
                return i < 5;
            });

            formattedItems = formattedItems.slice(0, -2);
            if (selectedItems.length > 5) {
                formattedItems += ', and ' + (selectedItems.length - 5) + ' other(s)';
            }

            swal({
                type: 'warning',
                title: '',
                text: 'Are you sure you want to delete the following files: ' + formattedItems + '?',
                html: true,
                showCancelButton: true,
                showConfirmButton: true,
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, () => {
                $.ajax({
                    type: 'POST',
                    headers: {
                        'X-Access-Token': Pterodactyl.server.daemonSecret,
                        'X-Access-Server': Pterodactyl.server.uuid,
                    },
                    contentType: 'application/json; charset=utf-8',
                    url: `${Pterodactyl.node.scheme}://${Pterodactyl.node.fqdn}:${Pterodactyl.node.daemonListen}/v1/server/file/delete`,
                    timeout: 10000,
                    data: JSON.stringify({
                        items: selectedItems
                    }),
                }).done(data => {
                    $('#file_listing input:checked').each(function() {
                        $(this).prop('checked', false);
                    });

                    $.each(selectedItemsElements, function() {
                        $(this).addClass('warning').delay(200).fadeOut();
                    })

                    swal({
                        type: 'success',
                        title: 'Files Deleted'
                    });
                }).fail(jqXHR => {
                    console.error(jqXHR);
                    swal({
                        type: 'error',
                        title: 'Whoops!',
                        html: true,
                        text: 'An error occurred while attempting to delete these files. Please try again.',
                    });
                });
            });
        } else {
            swal({
              type: 'warning',
              title: '',
              text: 'Please select files/folders to delete.',
            });
        }
    }

    decompress() {
        const nameBlock = $(this.element).find('td[data-identifier="name"]');
        const compPath = decodeURIComponent(nameBlock.data('path'));
        const compName = decodeURIComponent(nameBlock.data('name'));

        swal({
            title: '<i class="fa fa-refresh fa-spin"></i> Decompressing...',
            text: 'This might take a few seconds to complete.',
            html: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
        });

        $.ajax({
            type: 'POST',
            url: `${Pterodactyl.node.scheme}://${Pterodactyl.node.fqdn}:${Pterodactyl.node.daemonListen}/v1/server/file/decompress`,
            headers: {
                'X-Access-Token': Pterodactyl.server.daemonSecret,
                'X-Access-Server': Pterodactyl.server.uuid,
            },
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({
                files: `${compPath}${compName}`
            })
        }).done(data => {
            swal.close();
            Files.list(compPath);
        }).fail(jqXHR => {
            console.error(jqXHR);
            var error = 'An error occurred while trying to process this request.';
            if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                error = jqXHR.responseJSON.error;
            }
            swal({
                type: 'error',
                title: 'Whoops!',
                html: true,
                text: error
            });
        });
    }

    compress() {
        const nameBlock = $(this.element).find('td[data-identifier="name"]');
        const compPath = decodeURIComponent(nameBlock.data('path'));
        const compName = decodeURIComponent(nameBlock.data('name'));

        $.ajax({
            type: 'POST',
            url: `${Pterodactyl.node.scheme}://${Pterodactyl.node.fqdn}:${Pterodactyl.node.daemonListen}/v1/server/file/compress`,
            headers: {
                'X-Access-Token': Pterodactyl.server.daemonSecret,
                'X-Access-Server': Pterodactyl.server.uuid,
            },
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({
                files: `${compPath}${compName}`,
                to: compPath.toString()
            })
        }).done(data => {
            Files.list(compPath, err => {
                if (err) return;
                const fileListing = $('#file_listing').find(`[data-name="${data.saved_as}"]`).parent();
                fileListing.addClass('success pulsate').delay(3000).queue(() => {
                    fileListing.removeClass('success pulsate').dequeue();
                });
            });
        }).fail(jqXHR => {
            console.error(jqXHR);
            var error = 'An error occurred while trying to process this request.';
            if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                error = jqXHR.responseJSON.error;
            }
            swal({
                type: 'error',
                title: 'Whoops!',
                html: true,
                text: error
            });
        });
    }
}
