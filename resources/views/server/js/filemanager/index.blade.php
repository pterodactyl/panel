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
class FileManager {
    constructor() {
        this.list(this.decodeHash());
    }

    reload() {
        $('[data-toggle="tooltip"]').tooltip();
    }

    list(path, isError) {
        if (_.isUndefined(path)) {
            path = this.decodeHash();
        }

        this.loader(true);
        $.ajax({
            type: 'POST',
            url: '{{ route('server.files.directory-list', $server->uuidShort) }}',
            headers: {
                'X-CSRF-Token': '{{ csrf_token() }}',
            },
            data: {
                directory: path,
            },
        }).done(data => {
            $('#load_files').slideUp().html(data).slideDown(() => {
                Actions.run();
            });
            $('#internal_alert').slideUp();
        }).fail(jqXHR => {
            swal({
                type: 'error',
                title: 'File Error',
                text: 'An error occured while attempting to process this request. Please try again.',
            });
            if (!isError) this.list('/', true);
            console.log(jqXHR);
        }).always(() => {
            this.loader(false);
        });
    }

    loader(show) {
        if ($('#load_files').height() < 5) return;

        if (show === true){
            var height = $('#load_files').height();
            var width = $('.ajax_loading_box').width();
            var center_height = (height / 2) - 30;
            var center_width = (width / 2) - 30;

            $('#position_me').css({
                'top': center_height,
                'left': center_width,
                'font-size': '60px'
            });

            $('.ajax_loading_box').css('height', (height + 5)).fadeIn();
        } else {
            $('.ajax_loading_box').fadeOut(100);
        }
    }

    decodeHash() {
        return decodeURIComponent(window.location.hash.substring(1));
    }

}

window.Files = new FileManager;
