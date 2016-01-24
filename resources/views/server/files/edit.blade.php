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

@section('content')
<div class="col-md-12">
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
                    <button class="btn btn-primary btn-sm" id="save_file" type="submit">{{ trans('strings.save') }}</button>
                    <a href="/server/{{ $server->uuidShort }}/files?dir={{ rawurlencode($directory) }}" class="text-muted pull-right"><small>{{ trans('server.files.back') }}</small></a>
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
                url: '{{ route('server.files.save', $server->uuidShort) }}',
                headers: { 'X-CSRF-Token': '{{ csrf_token() }}' },
                data: {
                    file: fileName,
                    contents: fileContents
                }
            }).done(function (data) {
                $('#tpl_messages').html('<div class="alert alert-success">{{ trans('server.files.saved') }}</div>').show().delay(3000).slideUp();
            }).fail(function (jqXHR) {
                $('#tpl_messages').html('<div class="alert alert-danger">' + jqXHR.responseText + '</div>');
            }).always(function () {
                $('#save_file').html('{{ trans('strings.save') }}').removeClass('disabled');
            });

        });
    @endcan
});
</script>
@endsection
