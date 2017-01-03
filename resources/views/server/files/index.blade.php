{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}

{{-- Permission is hereby granted, free of charge, to any person obtaining a copy --}}
{{-- of this software and associated documentation files (the "Software"), to deal --}}
{{-- in the Software without restriction, including without limitation the rights --}}
{{-- to use, copy, modify, merge, publish, distribute, sublicense, and/or sell --}}
{{-- copies of the Software, and to permit persons to whom the Software is --}}
{{-- furnished to do so, subject to the following conditions: --}}

{{-- The above copyright notice and this permission notice shall be included in all --}}
{{-- copies or substantial portions of the Software. --}}

{{-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR --}}
{{-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, --}}
{{-- FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE --}}
{{-- AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER --}}
{{-- LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, --}}
{{-- OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE --}}
{{-- SOFTWARE. --}}
@extends('layouts.master')

@section('title')
    Managing Files for: {{ $server->name }}
@endsection

@section('scripts')
    @parent
    {!! Theme::js('js/vendor/async/async.min.js') !!}
    {!! Theme::js('js/vendor/lodash/lodash.js') !!}
    {!! Theme::js('js/vendor/upload/client.min.js') !!}
@endsection

@section('content')
<div class="col-md-12">
    <div class="row">
        <div class="col-md-12" id="internal_alert">
            <div class="alert alert-info">
                <i class="fa fa-spinner fa-spin"></i> {{ trans('server.files.loading') }}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="ajax_loading_box"><i class="fa fa-refresh fa-spin" id="position_me"></i></div>
        </div>
    </div>
    <div class="row" id="upload_box">
        <div class="col-md-12" id="load_files"></div>
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">File Path Information</h3>
                </div>
                <div class="panel-body">
                    When configuring any file paths in your server plugins or settings you should use <code>/home/container</code> as your base path. The maximum size for web-based file uploads is currently <code>{{ $node->upload_size }} MB</code>.
                </div>
            </div>
        </div>
    </div>
</div>
{!! Theme::js('js/files/index.js') !!}
{!! Theme::js('js/files/contextmenu.js') !!}
{!! Theme::js('js/files/actions.js') !!}
<script>
$(window).load(function () {
    $('.server-files').addClass('active');
    @can('upload-files', $server)
        var notifyUploadSocketError = false;
        var uploadSocket = io('{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/upload/{{ $server->uuid }}', {
            'query': 'token={{ $server->daemonSecret }}'
        });

        socket.io.on('connect_error', function (err) {
            siofu.destroy();
            $('#applyUpdate').removeClass('fa-circle-o-notch fa-spinner fa-spin').addClass('fa-question-circle').css({ color: '#FF9900' });
            if(typeof notifyUploadSocketError !== 'object') {
                notifyUploadSocketError = $.notify({
                    message: 'There was an error connecting to the Upload Socket for this server.'
                }, {
                    type: 'danger',
                    delay: 0
                });
            }
        });

        uploadSocket.on('error', err => {
            siofu.destroy();
            console.error(err);
        });

        uploadSocket.on('connect', function () {
            if (notifyUploadSocketError !== false) {
                notifyUploadSocketError.close();
                notifyUploadSocketError = false;
            }
        });

        socket.on('error', function (err) {
            console.error('There was an error while attemping to connect to the websocket: ' + err + '\n\nPlease try loading this page again.');
        });


        var siofu = new SocketIOFileUpload(uploadSocket);
        siofu.listenOnDrop(document.getElementById("upload_box"));

        window.addEventListener('dragover', function (event) {
            event.preventDefault();
        }, false);

        window.addEventListener('drop', function (event) {
            event.preventDefault();
        }, false);

        var dropCounter = 0;
        $('#upload_box').bind({
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

        siofu.addEventListener('start', function (event) {
            event.file.meta.path = $('#headerTableRow').attr('data-currentdir');
            event.file.meta.identifier = Math.random().toString(36).slice(2);

            $('#append_files_to').append('<tr id="file-upload-' + event.file.meta.identifier +'"> \
                <td><i class="fa fa-file-text-o" style="margin-left: 2px;"></i></td> \
                <td>' + event.file.name + '</td> \
                <td colspan=2">&nbsp;</td> \
            </tr><tr> \
                <td colspan="4" class="has-progress"> \
                    <div class="progress progress-table-bottom active"> \
                        <div class="progress-bar progress-bar-info prog-bar-' + event.file.meta.identifier +'" style="width: 0%"></div> \
                    </div> \
                </td> \
            </tr>\
            ');
        });

        siofu.addEventListener('progress', function(event) {
            var percent = event.bytesLoaded / event.file.size * 100;
            if (percent >= 100) {
                $('.prog-bar-' + event.file.meta.identifier).css('width', '100%').removeClass('progress-bar-info').addClass('progress-bar-success').parent().removeClass('active');
            } else {
                $('.prog-bar-' + event.file.meta.identifier).css('width', percent + '%');
            }
        });

        // Do something when a file is uploaded:
        siofu.addEventListener('complete', function(event){
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

        siofu.addEventListener('error', function(event){
            console.error(event);
            $('.prog-bar-' + event.file.meta.identifier).css('width', '100%').removeClass('progress-bar-info').addClass('progress-bar-danger');
            $.notify({
                message: 'An error was encountered while attempting to upload this file: <strong>' + event.message + '.</strong>',
            }, {
                type: 'danger',
                delay: 8000
            });
        });
    @endcan
});
</script>
@endsection
