@extends('layouts.master')

@section('title')
    Add File to: {{ $server->name }}
@endsection

@section('scripts')
    @parent
    <script src="{{ asset('js/binaryjs.js') }}"></script>
@endsection

@section('content')
<div class="col-md-9">
    @foreach (Alert::getMessages() as $type => $messages)
        @foreach ($messages as $message)
            <div class="alert alert-{{ $type }} alert-dismissable" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                {{ $message }}
            </div>
        @endforeach
    @endforeach
    <ul class="nav nav-tabs" id="config_tabs">
        <li class="active"><a href="#create" data-toggle="tab">Create File</a></li>
        @can('upload-files', $server)<li><a href="#upload" data-toggle="tab">Upload Files</a></li>@endcan
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="create">
            <div id="write_status" style="display:none;width: 100%; margin: 10px 0 5px;"></div>
            <form method="post" id="new_file">
                <h4>/home/container/{{ $directory }} <input type="text" id="file_name" class="filename" value="newfile.txt" /></h4>
                <div class="form-group">
                    <div>
                        <textarea name="file_contents" id="fileContents" style="border: 1px solid #dddddd;" class="form-control console"></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <div>
                        <button class="btn btn-primary btn-sm" id="create_file">{{ trans('strings.save') }}</button>
                        <button class="btn btn-default btn-sm" onclick="window.location='/server/{{ $server->uuidShort }}/files?dir=/{{ $directory }}';return false;">{{ trans('server.files.back') }}</button>
                    </div>
                </div>
            </form>
        </div>
        @can('upload-files', $server)
            <div class="tab-pane" id="upload">
                <h4>/home/container/&nbsp;&nbsp;<input type="text" id="u_file_name" value="{{ $directory }}" style='outline: none;width:450px;background: transparent;margin-left:-5px;padding:0;border: 0px;font-family: "Open Sans","Helvetica Neue",Helvetica,Arial,sans-serif;font-weight: 250;line-height: 1.1;color: inherit;font-size: 19px;'/></h4>
                <div class="alert alert-warning">Please edit the path location above <strong>before you upload files</strong>. They will automatically be placed in the directory you specify above. Simply click next to <code>/home/container/</code> and begin typing. You can change this each time you upload a new file without having to press anything else.</div>
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
<script>
$(window).load(function () {

    $('.server-files').addClass('active');

    var newFilePath;
    var newFileContents;

    @can('upload-files', $server)
        var client = new BinaryClient('wss://{{ $node->fqdn }}:{{ $node->daemonListen }}/upload/', {
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

                e.preventDefault();
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

    $('textarea').keydown(function (e) {
        if (e.keyCode === 9) {
            var start = this.selectionStart;
            var end = this.selectionEnd;
            var value = $(this).val();
            $(this).val(value.substring(0, start) + '\t' + value.substring(end));
            this.selectionStart = this.selectionEnd = start + 1;
            e.preventDefault();
        }
    });

    $("#create_file").click(function(e) {
        e.preventDefault();
        $("#create_file").append(' <i class="fa fa-spinner fa fa-spin"></i>').addClass("disabled");
        $.ajax({
            type: 'POST',
            url: '/server/{{ $server->uuidShort }}/ajax/files/save',
            headers: { 'X-CSRF-Token': '{{ csrf_token() }}' },
            data: {
                file: '{{ $directory }}' + $('#file_name').val(),
                contents: $('#fileContents').val()
            }
        }).done(function (data) {
            window.location.replace('/server/{{ $server->uuidShort }}/files/edit/{{ $directory }}' + $('#file_name').val());
        }).fail(function (jqXHR) {
            $('#write_status').html('<div class="alert alert-danger">' + jqXHR.responseText + '</div>').show();
            $('#create_file').html('{{ trans('strings.save') }}').removeClass('disabled');
        });
    });

});
</script>
@endsection
