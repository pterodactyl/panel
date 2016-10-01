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
class FileActions {
    constructor() {
        //
    }

    run() {
        this.directoryClick();
        this.rightClick();
    }

    rightClick() {
        $('#file_listing > tbody').on('contextmenu', event => {
            event.preventDefault();
            const parent = $(event.target).parent();

            $('#fileOptionMenu').appendTo('body');
            $('#fileOptionMenu').data('invokedOn', $(event.target)).show().css({
                position: 'absolute',
                left: event.pageX,
                top: event.pageY,
            });

            // Handle Events
            var Context = new ContextMenuActions(parent);
            $('#fileOptionMenu li[data-action="move"]').unbind().on('click', e => {
                Context.move();
            });

            $('#fileOptionMenu li[data-action="rename"]').unbind().on('click', e => {
                Context.rename();
            });

            $(window).click(() => {
                $('#fileOptionMenu').hide();
            });
        });
    }

    directoryClick() {
        $('a[data-action="directory-view"]').on('click', function (event) {
            event.preventDefault();
            window.location.hash = encodeURIComponent($(this).parent().data('hash') || '');
            Files.list();
        });
    }
}

window.Actions = new FileActions;
