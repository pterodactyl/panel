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
(function initUploader() {
    var notifyUploadSocketError = false;
    uploadSocket = io(Pterodactyl.node.scheme + '://' + Pterodactyl.node.fqdn + ':' + Pterodactyl.node.daemonListen + '/upload/' + Pterodactyl.server.uuid, {
        'query': 'token=' + Pterodactyl.server.daemonSecret,
    });

    uploadSocket.io.on('connect_error', function (err) {
        if(typeof notifyUploadSocketError !== 'object') {
            notifyUploadSocketError = $.notify({
                message: 'There was an error attempting to establish a connection to the uploader endpoint.<br /><br />' + err,
            }, {
                type: 'danger',
                delay: 0
            });
        }
    });

    uploadSocket.on('error', err => {
        Siofu.destroy();
        console.error(err);
    });

    uploadSocket.on('connect', function () {
        if (notifyUploadSocketError !== false) {
            notifyUploadSocketError.close();
            notifyUploadSocketError = false;
        }
    });

    window.Siofu = new SocketIOFileUpload(uploadSocket);
    Siofu.listenOnDrop(document.getElementById("load_files"));

    if (document.getElementById("files_touch_target")) {
        Siofu.listenOnInput(document.getElementById("files_touch_target"));
    }

    window.addEventListener('dragover', function (event) {
        event.preventDefault();
    }, false);

    window.addEventListener('drop', function (event) {
        event.preventDefault();
    }, false);

    var dropCounter = 0;
    $('#load_files').bind({
        dragenter: function (event) {
            event.preventDefault();
            dropCounter++;
            $(this).addClass('hasFileHover');
        },
        dragleave: function (event) {
            dropCounter--;
            if (dropCounter === 0) {
                $(this).removeClass('hasFileHover');
            }
        },
        drop: function (event) {
            dropCounter = 0;
            $(this).removeClass('hasFileHover');
        }
    });

    Siofu.addEventListener('start', function (event) {
        window.onbeforeunload = function () {
            return 'A file upload in in progress, are you sure you want to continue?';
        };
        event.file.meta.path = $('#file_listing').data('current-dir');
        event.file.meta.identifier = Math.random().toString(36).slice(2);

        $('#append_files_to').append('<tr id="file-upload-' + event.file.meta.identifier +'"> \
            <td><i class="fa fa-file-text-o" style="margin-left: 2px;"></i></td> \
            <td>' + event.file.name + '</td> \
            <td colspan=2">&nbsp;</td> \
        </tr><tr> \
            <td colspan="5" class="has-progress"> \
                <div class="progress progress-table-bottom active"> \
                    <div class="progress-bar progress-bar-info prog-bar-' + event.file.meta.identifier +'" style="width: 0%"></div> \
                </div> \
            </td> \
        </tr>\
        ');
    });

    Siofu.addEventListener('progress', function(event) {
        window.onbeforeunload = function () {
            return 'A file upload in in progress, are you sure you want to continue?';
        };
        var percent = event.bytesLoaded / event.file.size * 100;
        if (percent >= 100) {
            $('.prog-bar-' + event.file.meta.identifier).css('width', '100%').removeClass('progress-bar-info').addClass('progress-bar-success').parent().removeClass('active');
        } else {
            $('.prog-bar-' + event.file.meta.identifier).css('width', percent + '%');
        }
    });

    // Do something when a file is uploaded:
    Siofu.addEventListener('complete', function(event) {
        window.onbeforeunload = function () {};
        if (!event.success) {
            $('.prog-bar-' + event.file.meta.identifier).css('width', '100%').removeClass('progress-bar-info').addClass('progress-bar-danger');
            $.notify({
                message: 'An error was encountered while attempting to upload this file.'
            }, {
                type: 'danger',
                delay: 5000
            });
        }
    });

    Siofu.addEventListener('error', function(event) {
        window.onbeforeunload = function () {};
        console.error(event);
        $('.prog-bar-' + event.file.meta.identifier).css('width', '100%').removeClass('progress-bar-info').addClass('progress-bar-danger');
        $.notify({
            message: 'An error was encountered while attempting to upload this file: <strong>' + event.message + '.</strong>',
        }, {
            type: 'danger',
            delay: 8000
        });
    });
})();
