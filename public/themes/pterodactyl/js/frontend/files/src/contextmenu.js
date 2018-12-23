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
class ContextMenuClass {
    constructor() {
        this.activeLine = null;
    }

    run() {
        this.directoryClick();
        this.rightClick();
    }

    makeMenu(parent) {
        $(document).find('#fileOptionMenu').remove();
        if (!_.isNull(this.activeLine)) this.activeLine.removeClass('active');

        let newFilePath = $('#file_listing').data('current-dir');
        if (parent.data('type') === 'folder') {
            const nameBlock = parent.find('td[data-identifier="name"]');
            const currentName = decodeURIComponent(nameBlock.attr('data-name'));
            const currentPath = decodeURIComponent(nameBlock.data('path'));
            newFilePath = `${currentPath}${currentName}`;
        }

        let buildMenu = '<ul id="fileOptionMenu" class="dropdown-menu" role="menu" style="display:none" >';

        if (Pterodactyl.permissions.moveFiles) {
            buildMenu += '<li data-action="rename"><a tabindex="-1" href="#"><i class="fa fa-fw fa-pencil-square-o"></i> Rename</a></li> \
                          <li data-action="move"><a tabindex="-1" href="#"><i class="fa fa-fw fa-arrow-right"></i> Move</a></li>';
        }

        if (Pterodactyl.permissions.copyFiles) {
            buildMenu += '<li data-action="copy"><a tabindex="-1" href="#"><i class="fa fa-fw fa-clone"></i> Copy</a></li>';
        }

        if (Pterodactyl.permissions.compressFiles) {
            buildMenu += '<li data-action="compress" class="hidden"><a tabindex="-1" href="#"><i class="fa fa-fw fa-file-archive-o"></i> Compress</a></li>';
        }

        if (Pterodactyl.permissions.decompressFiles) {
            buildMenu += '<li data-action="decompress" class="hidden"><a tabindex="-1" href="#"><i class="fa fa-fw fa-expand"></i> Decompress</a></li>';
        }

        if (Pterodactyl.permissions.createFiles) {
            buildMenu += '<li class="divider"></li> \
                          <li data-action="file"><a href="/server/'+ Pterodactyl.server.uuidShort +'/files/add/?dir=' + newFilePath + '" class="text-muted"><i class="fa fa-fw fa-plus"></i> New File</a></li> \
                          <li data-action="folder"><a tabindex="-1" href="#"><i class="fa fa-fw fa-folder"></i> New Folder</a></li>';
        }

        if (Pterodactyl.permissions.downloadFiles || Pterodactyl.permissions.deleteFiles) {
            buildMenu += '<li class="divider"></li>';
        }

        if (Pterodactyl.permissions.downloadFiles) {
            buildMenu += '<li data-action="download" class="hidden"><a tabindex="-1" href="#"><i class="fa fa-fw fa-download"></i> Download</a></li>';
        }

        if (Pterodactyl.permissions.deleteFiles) {
            buildMenu += '<li data-action="delete" class="bg-danger"><a tabindex="-1" href="#"><i class="fa fa-fw fa-trash-o"></i> Delete</a></li>';
        }

        buildMenu += '</ul>';
        return buildMenu;
    }

    rightClick() {
        $('[data-action="toggleMenu"]').on('mousedown', event => {
            event.preventDefault();
            if ($(document).find('#fileOptionMenu').is(':visible')) {
                $('body').trigger('click');
                return;
            }
            this.showMenu(event);
        });
        $('#file_listing > tbody td').on('contextmenu', event => {
            this.showMenu(event);
        });
    }

    showMenu(event) {
        const parent = $(event.target).closest('tr');
        const menu = $(this.makeMenu(parent));

        if (parent.data('type') === 'disabled') return;
        event.preventDefault();

        $(menu).appendTo('body');
        $(menu).data('invokedOn', $(event.target)).show().css({
            position: 'absolute',
            left: event.pageX - 150,
            top: event.pageY,
        });

        this.activeLine = parent;
        this.activeLine.addClass('active');

        // Handle Events
        const Actions = new ActionsClass(parent, menu);
        if (Pterodactyl.permissions.moveFiles) {
            $(menu).find('li[data-action="move"]').unbind().on('click', e => {
                e.preventDefault();
                Actions.move();
            });
            $(menu).find('li[data-action="rename"]').unbind().on('click', e => {
                e.preventDefault();
                Actions.rename();
            });
        }

        if (Pterodactyl.permissions.copyFiles) {
            $(menu).find('li[data-action="copy"]').unbind().on('click', e => {
                e.preventDefault();
                Actions.copy();
            });
        }

        if (Pterodactyl.permissions.compressFiles) {
            if (parent.data('type') === 'folder') {
                $(menu).find('li[data-action="compress"]').removeClass('hidden');
            }
            $(menu).find('li[data-action="compress"]').unbind().on('click', e => {
                e.preventDefault();
                Actions.compress();
            });
        }

        if (Pterodactyl.permissions.decompressFiles) {
            if (_.without(['application/zip', 'application/gzip', 'application/x-gzip'], parent.data('mime')).length < 3) {
                $(menu).find('li[data-action="decompress"]').removeClass('hidden');
            }
            $(menu).find('li[data-action="decompress"]').unbind().on('click', e => {
                e.preventDefault();
                Actions.decompress();
            });
        }

        if (Pterodactyl.permissions.createFiles) {
            $(menu).find('li[data-action="folder"]').unbind().on('click', e => {
                e.preventDefault();
                Actions.folder();
            });
        }

        if (Pterodactyl.permissions.downloadFiles) {
            if (parent.data('type') === 'file') {
                $(menu).find('li[data-action="download"]').removeClass('hidden');
            }
            $(menu).find('li[data-action="download"]').unbind().on('click', e => {
                e.preventDefault();
                Actions.download();
            });
        }

        if (Pterodactyl.permissions.deleteFiles) {
            $(menu).find('li[data-action="delete"]').unbind().on('click', e => {
                e.preventDefault();
                Actions.delete();
            });
        }

        $(window).unbind().on('click', event => {
            if($(event.target).is('.disable-menu-hide')) {
                event.preventDefault();
                return;
            }
            $(menu).unbind().remove();
            if(!_.isNull(this.activeLine)) this.activeLine.removeClass('active');
        });
    }

    directoryClick() {
        $('a[data-action="directory-view"]').on('click', function (event) {
            event.preventDefault();

            const path = $(this).parent().data('path') || '';
            const name = $(this).parent().data('name') || '';

            window.location.hash = encodeURIComponent(path + name);
            Files.list();
        });
    }
}

window.ContextMenu = new ContextMenuClass;
