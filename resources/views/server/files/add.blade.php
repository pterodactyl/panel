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
    Add File to: {{ $server->name }}
@endsection

@section('scripts')
    @parent
    {!! Theme::js('js/binaryjs.js') !!}
    {!! Theme::js('js/vendor/lodash/lodash.js') !!}
@endsection

@section('content')
<div class="col-md-12">
    <ul class="nav nav-tabs" id="config_tabs">
        <li class="active"><a href="#create" data-toggle="tab">Create File</a></li>
        @can('upload-files', $server)<li><a href="#upload" data-toggle="tab">Upload Files</a></li>@endcan
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="create">
            <div class="row" style="margin: 15px 0 0;">
                <div class="col-md-8" style="padding-left:0;">
                    <div class="input-group" style="margin-bottom:5px;">
                        <span class="input-group-addon">Save As:</span>
                        <input type="text" class="form-control" id="file_name" placeholder="filename.json" value="{{ $directory}}">
                    </div>
                    <small><p class="text-muted">All files are saved relative to <code>/home/container</code>. You can enter more of the path into the Save As field to save the file into a specific folder.</p></small>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <div id="fileContents" style="height:500px;"></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <button class="btn btn-primary btn-sm" id="create_file">{{ trans('strings.save') }}</button>
                    <button class="btn btn-default btn-sm" onclick="window.location='/server/{{ $server->uuidShort }}/files?dir=/{{ $directory }}';return false;">{{ trans('server.files.back') }}</button>
                </div>
                <div class="col-md-4 pull-right">
                    <select name="aceMode" id="aceMode" class="form-control">
                        <option value="assembly_x86">Assembly x86</option>
                        <option value="c_cpp">C/C++</option>
                        <option value="coffee">CoffeeScript</option>
                        <option value="csharp">C#</option>
                        <option value="css">CSS</option>
                        <option value="golang">Go</option>
                        <option value="haml">HAML</option>
                        <option value="html">HTML</option>
                        <option value="ini">INI</option>
                        <option value="java">Java</option>
                        <option value="javascript">JavaScript</option>
                        <option value="json">JSON</option>
                        <option value="lua">Lua</option>
                        <option value="markdown">Markdown</option>
                        <option value="mysql">MySQL</option>
                        <option value="objectivec">Objective-C</option>
                        <option value="perl">Perl</option>
                        <option value="php">PHP</option>
                        <option value="properties">Properties</option>
                        <option value="python">Python</option>
                        <option value="ruby">Ruby</option>
                        <option value="rust">Rust</option>
                        <option value="smarty">Smarty</option>
                        <option value="textile" selected="selected">Plain Text</option>
                        <option value="xml">XML</option>
                        <option value="yaml">YAML</option>
                    </select>
                </div>
            </div>
        </div>
        @can('upload-files', $server)
            <div class="tab-pane" id="upload">
                <div class="row" style="margin: 15px 0 0;">
                    <div class="col-md-8" style="padding-left:0;">
                        <div class="input-group" style="margin-bottom:5px;">
                            <span class="input-group-addon">Upload Directory:</span>
                            <input type="text" class="form-control" id="u_file_name" placeholder="logs/" value="{{ $directory}}">
                        </div>
                        <small><p class="text-muted">All files are saved relative to <code>/home/container</code>. You can enter more of the path into the Save As field to save the file into a specific folder.</p></small>
                    </div>
                </div>

                <div class="alert alert-warning">Edit the path location above <strong>before you upload files</strong>. They will automatically be placed in the directory you specify above. You can change this each time you upload a new file without having to press anything else.</div>
                <div class="alert alert-danger" id="upload_error" style="display: none;"></div>
                <input type="file" id="fileinput" name="fileUpload[]" multiple="" style="display:none;"/>
                <div id="uploader_box" class="well well-sm" style="cursor:pointer;">
                    <center><h2 style="margin-bottom: 25px;">Connecting...</h2></center>
                </div>
                <span id="file_progress"></span>
            </div>
        @endcan
    </div>
</div>
{!! Theme::js('js/vendor/ace/ace.js') !!}
{!! Theme::js('js/vendor/ace/ext-modelist.js') !!}
<script>
$(window).load(function () {

    $('.server-files').addClass('active');

    var newFilePath;
    var newFileContents;

    @can('upload-files', $server)
        var client = new BinaryClient('{{ $node->scheme === 'https' ? 'wss' : 'ws' }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/upload/', {
            chunkSize: 40960
        });
        // Wait for connection to BinaryJS server
        client.on('open', function() {

            var box = $('#uploader_box');
            box.on('dragenter', doNothing);
            box.on('dragover', doNothing);
            box.html('<center><h2 style="margin-bottom:25px;">Drag or Click to Upload</h2></center>');
            box.on('click', function () {
                $('#fileinput').click();
            });
            box.on('drop', function (e, files) {

                if (typeof files !== 'undefined') {
                    e.originalEvent = {
                        dataTransfer: {
                            files: files.currentTarget.files
                        }
                    };
                }

                // e.preventDefault();
                $.each(e.originalEvent.dataTransfer.files, function(index, value) {

                    var file = e.originalEvent.dataTransfer.files[index];
                    var identifier = Math.random().toString(36).slice(2);

                    $('#file_progress').append('<div class="well well-sm" id="file-upload-' + identifier +'"> \
                        <div class="row"> \
                            <div class="col-md-12"> \
                                <h6>Uploading ' + file.name + '</h6> \
                                <span class="prog-bar-text-' + identifier +'" style="font-size: 10px;position: absolute;margin: 3px 0 0 15px;">Waiting...</span> \
                                <div class="progress progress-striped active"> \
                                    <div class="progress-bar progress-bar-info prog-bar-' + identifier +'" style="width: 0%"></div> \
                                </div> \
                            </div> \
                        </div> \
                    </div>');

                    // Add to list of uploaded files
                    var stream = client.send(file, {
                        token: "{{ $server->daemonSecret }}",
                        server: "{{ $server->uuid }}",
                        path: $("#u_file_name").val(),
                        name: file.name,
                        size: file.size
                    });

                    var tx = 0;
                    stream.on('data', function(data) {
                        if(data.error) {
                            $("#upload_error").html(data.error).show();
                            $("#file-upload-" + identifier).hide();
                        } else {
                            tx += data.rx;

                            if(tx >= 0.999) {
                                $('.prog-bar-text-' + identifier).text('Upload Complete');
                                $('.prog-bar-' + identifier).css('width', '100%').parent().removeClass('active').removeClass('progress-striped');
                            } else {
                                $('.prog-bar-text-' + identifier).text(Math.round(tx * 100) + '%');
                                $('.prog-bar-' + identifier).css('width', tx * 100 + '%');
                            }
                        }
                    });

                    stream.on('close', function(data) {
                        $("#upload_error").html("The BinaryJS data stream was closed by the server. Please refresh the page and try again.").show();
                        $("#file-upload-" + identifier).hide();
                    });

                    stream.on('error', function(data) {
                        console.error("An error was encountered with the BinaryJS upload stream.");
                    });

                });
            });

        });

        // listen for a file being chosen
        $('#fileinput').change(function (event) {
            $('#uploader_box').trigger('drop', [event, event.currentTarget]);
            $('#fileinput').val('');
        });

        // Deal with DOM quirks
        function doNothing (e){
            e.preventDefault();
            e.stopPropagation();
        }
    @endcan

    const Editor = ace.edit('fileContents');

    Editor.setTheme('ace/theme/chrome');
    Editor.getSession().setUseWrapMode(true);
    Editor.setShowPrintMargin(false);

    $('#aceMode').on('change', event => {
        Editor.getSession().setMode(`ace/mode/${$('#aceMode').val()}`);
    });

    Editor.commands.addCommand({
        name: 'save',
        bindKey: {win: 'Ctrl-S',  mac: 'Command-S'},
        exec: function(editor) {
            save();
        },
        readOnly: false
    });

    $('#create_file').on('click', function (e) {
        e.preventDefault();
        save();
    });

    function save() {
        if (_.isEmpty($('#file_name').val())) {
            $.notify({
                message: 'No filename was passed.'
            }, {
                type: 'danger'
            });
            return;
        }
        $('#create_file').append(' <i class="fa fa-spinner fa fa-spin"></i>').addClass('disabled');
        $.ajax({
            type: 'POST',
            url: '{{ route('server.files.save', $server->uuidShort) }}',
            headers: { 'X-CSRF-Token': '{{ csrf_token() }}' },
            data: {
                file: '{{ $directory }}' + $('#file_name').val(),
                contents: Editor.getValue()
            }
        }).done(function (data) {
            window.location.replace('/server/{{ $server->uuidShort }}/files/edit/{{ $directory }}' + $('#file_name').val());
        }).fail(function (jqXHR) {
            $.notify({
                message: jqXHR.responseText
            }, {
                type: 'danger'
            });
        }).always(function () {
            $('#save_file').html('{{ trans('strings.save') }}').removeClass('disabled');
        });
    }


});
</script>
@endsection
