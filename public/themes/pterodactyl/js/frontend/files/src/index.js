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
class FileManager {
    constructor() {
        this.list(this.decodeHash());
    }

    list(path, next) {
        if (_.isUndefined(path)) {
            path = this.decodeHash();
        }

        this.loader(true);
        $.ajax({
            type: 'POST',
            url: Pterodactyl.meta.directoryList,
            headers: {
                'X-CSRF-Token': Pterodactyl.meta.csrftoken,
            },
            data: {
                directory: path,
            },
        }).done(data => {
            this.loader(false);
            $('#load_files').slideUp(10).html(data).slideDown(10, () => {
                ContextMenu.run();
                this.reloadFilesButton();
                this.addFolderButton();
                this.selectItem();
                this.selectAll();
                this.selectiveDeletion();
                this.selectRow();
                if (_.isFunction(next)) {
                    return next();
                }
            });
            $('#internal_alert').slideUp();

            if (typeof Siofu === 'object') {
                Siofu.listenOnInput(document.getElementById("files_touch_target"));
            }
        }).fail(jqXHR => {
            this.loader(false);
            if (_.isFunction(next)) {
                return next(new Error('Failed to load file listing.'));
            }

            if ((path !== '' && path !== '/') && jqXHR.status === 404) {
                return this.list('', next);
            }

            swal({
                type: 'error',
                title: 'File Error',
                text: jqXHR.responseJSON.errors[0].detail || 'An error occurred while attempting to process this request. Please try again.',
            });
            console.error(jqXHR);
        });
    }

    loader(show) {
        if (show){
            $('.file-overlay').fadeIn(100);
        } else {
            $('.file-overlay').fadeOut(100);
        }
    }

    reloadFilesButton() {
        $('i[data-action="reload-files"]').unbind().on('click', () => {
            $('i[data-action="reload-files"]').addClass('fa-spin');
            this.list();
        });
    }

    selectItem() {
        $('[data-action="addSelection"]').on('click', event => {
            event.preventDefault();
        });
    }

    selectAll() {
        $('[data-action="selectAll"]').on('click', event => {
            event.preventDefault();
        });
    }

    selectiveDeletion() {
        $('[data-action="selective-deletion"]').on('mousedown', event => {
            new ActionsClass().deleteSelected();
        });
    }

    addFolderButton() {
        $('[data-action="add-folder"]').unbind().on('click', () => {
            new ActionsClass().folder($('#file_listing').data('current-dir') || '/');
        });
    }

    selectRow() {
      $('#file_listing tr').on('mousedown', event => {
          if (event.which === 1) {
              if ($(event.target).is('th') || $(event.target).is('input[data-action="selectAll"]')) {
                  new ActionsClass().highlightAll(event);
              } else if ($(event.target).is('td') || $(event.target).is('input[data-action="addSelection"]')) {
                  new ActionsClass().toggleHighlight(event);
              }

              new ActionsClass().toggleMassActions();
          }
      });
    }

    decodeHash() {
        return decodeURIComponent(window.location.hash.substring(1));
    }

}

window.Files = new FileManager;
