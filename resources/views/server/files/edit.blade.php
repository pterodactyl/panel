@extends('layouts.master')

@section('title')
    Managing Files for: {{ $server->name }}
@endsection

@section('content')
<div class="col-md-9">
    <span id="save_status" style="display:none;width: 100%;"></span>
    @foreach (Alert::getMessages() as $type => $messages)
        @foreach ($messages as $message)
            <div class="alert alert-{{ $type }} alert-dismissable" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                {{ $message }}
            </div>
        @endforeach
    @endforeach
    <h3 class="nopad"><small>Editing File: /home/container/{{ $file }}</small></h3>
    <form method="post" id="editing_file">
        <div class="form-group">
            <div>
                @if (in_array($extension, ['yaml', 'yml']))
                    <div class="alert alert-info">
                        {!! trans('server.files.yaml_notice', [
                            'dropdown' => '<select id="space_yaml">
                                    <option value="2">2</option>
                                    <option value="4" selected="selected">4</option>
                                    <option value="8">8</option>
                                </select>'
                        ]) !!}
                    </div>
                @endif
                <textarea name="file_contents" id="fileContent" style="border: 1px solid #dddddd;height:500px;" class="form-control console">{{ $contents }}</textarea>
            </div>
        </div>
        @can('save-files', $server)
            <div class="form-group">
                <div>
                    <input type="hidden" name="file" value="{{ $file }}" />
                    {!! csrf_field() !!}
                    <button class="btn btn-primary btn-sm" id="save_file">{{ trans('strings.save') }}</button>
                    <button class="btn btn-default btn-sm" onclick="window.location='/server/{{ $server->uuidShort }}/files?dir={{ urlencode($directory) }}';return false;">{{ trans('server.files.back') }}</button>
                </div>
            </div>
        @endcan
    </form>
</div>
<script>
$(document).ready(function () {
    $('.server-files').addClass('active');
    $('textarea').keydown(function (e) {
        if (e.keyCode === 9) {

            var start = this.selectionStart;
            var end = this.selectionEnd;
            var value = $(this).val();
            var joinYML = '\t';
            var yamlSpaces = 1;

            @if (in_array($extension, ['yaml', 'yml']))
                yamlSpaces = parseInt($("#space_yaml").val());
                joinYML = Array(yamlSpaces + 1).join(" ");
            @endif

            $(this).val(value.substring(0, start) + joinYML + value.substring(end));
            this.selectionStart = this.selectionEnd = start + yamlSpaces;
            e.preventDefault();

        }
    });
    @can('save-files', $server)
        $('#save_file').click(function (e) {
            e.preventDefault();

            var fileName = $('input[name="file"]').val();
            var fileContents = $('#fileContent').val();

            $('#save_file').append(' <i class="fa fa-spinner fa fa-spin"></i>').addClass('disabled');
            $.ajax({
                type: 'POST',
                url: '/server/{{ $server->uuidShort }}/ajax/files/save',
                headers: { 'X-CSRF-Token': '{{ csrf_token() }}' },
                data: {
                    file: fileName,
                    contents: fileContents
                }
            }).done(function (data) {
                $('#save_status').html('<div class="alert alert-success">{{ trans('server.files.saved') }}</div>').slideDown();
            }).fail(function (jqXHR) {
                $('#save_status').html('<div class="alert alert-danger">' + jqXHR.responseText + '</div>').slideDown();
            }).always(function () {
                $('#save_file').html('{{ trans('strings.save') }}').removeClass('disabled');
            });

        });
    @endcan
});
</script>
@endsection
