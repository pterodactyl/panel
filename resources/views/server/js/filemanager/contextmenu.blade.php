"use strict";

// Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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

    makeMenu() {
        $(document).find('#fileOptionMenu').remove();
        if (!_.isNull(this.activeLine)) this.activeLine.removeClass('active');
        return '<ul id="fileOptionMenu" class="dropdown-menu" role="menu" style="display:none" > \
                    <li data-action="move"><a tabindex="-1" href="#"><i class="fa fa-arrow-right"></i> Move</a></li> \
                    <li data-action="rename"><a tabindex="-1" href="#"><i class="fa fa-pencil-square-o"></i> Rename</a></li> \
                    <li data-action="compress" class="hidden"><a tabindex="-1" href="#"><i class="fa fa-file-archive-o"></i> Compress</a></li> \
                    <li data-action="decompress" class="hidden"><a tabindex="-1" href="#"><i class="fa fa-expand"></i> Decompress</a></li> \
                    <li class="divider"></li> \
                    <li data-action="download" class="hidden"><a tabindex="-1" href="/server/{{ $server->uuidShort }}/files/download/"><i class="fa fa-download"></i> Download</a></li> \
                    <li data-action="delete" class="bg-danger"><a tabindex="-1" href="#"><i class="fa fa-trash-o"></i> Delete</a></li> \
                </ul>';
    }

    rightClick() {
        $('#file_listing > tbody td').on('contextmenu', event => {

            const parent = $(event.target).parent();
            const menu = $(this.makeMenu());

            if (parent.data('type') === 'disabled') return;
            event.preventDefault();

            $(menu).appendTo('body');
            $(menu).data('invokedOn', $(event.target)).show().css({
                position: 'absolute',
                left: event.pageX,
                top: event.pageY,
            });

            this.activeLine = parent;
            this.activeLine.addClass('active');

            if (parent.data('type') === 'file') {
                $(menu).find('li[data-action="download"]').removeClass('hidden');
            }

            if (parent.data('type') === 'folder') {
                $(menu).find('li[data-action="compress"]').removeClass('hidden');
            }

            if (_.without(['application/zip', 'application/gzip', 'application/x-gzip'], parent.data('mime')).length < 3) {
                $(menu).find('li[data-action="decompress"]').removeClass('hidden');
            }

            // Handle Events
            const Actions = new ActionsClass(parent, menu);
            $(menu).find('li[data-action="move"]').unbind().on('click', e => {
                e.preventDefault();
                Actions.move();
            });

            $(menu).find('li[data-action="rename"]').unbind().on('click', e => {
                e.preventDefault();
                Actions.rename();
            });

            $(menu).find('li[data-action="download"]').unbind().on('click', e => {
                e.preventDefault();
                Actions.download();
            });

            $(menu).find('li[data-action="delete"]').unbind().on('click', e => {
                e.preventDefault();
                Actions.delete();
            });

            $(window).on('click', () => {
                $(menu).remove();
                if(!_.isNull(this.activeLine)) this.activeLine.removeClass('active');
            });
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
