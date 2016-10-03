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
    <div class="row">
        <div class="col-md-12">
            <div id="editor" style="height:500px;">{{ $contents }}</div>
        </div>
    </div>
    @can('save-files', $server)
        <div class="row">
            <div class="col-md-12">
                <hr />
                <input type="hidden" name="file" value="{{ $file }}" />
                <button class="btn btn-primary btn-sm" id="save_file" type="submit">{{ trans('strings.save') }}</button>
                <a href="/server/{{ $server->uuidShort }}/files#{{ rawurlencode($directory) }}" class="text-muted pull-right"><small>{{ trans('server.files.back') }}</small></a>
            </div>
        </div>
    @endcan
</div>
{!! Theme::js('js/vendor/ace/ace.js') !!}
{!! Theme::js('js/vendor/ace/ext-modelist.js') !!}
<script>
$(document).ready(function () {
    $('.server-files').addClass('active');
    const Editor = ace.edit('editor');
    const Modelist = ace.require('ace/ext/modelist')

    Editor.setTheme('ace/theme/github');
    Editor.getSession().setMode(Modelist.getModeForPath('{{ $stat->name }}').mode);
    Editor.getSession().setUseWrapMode(true);
    Editor.setShowPrintMargin(false);

    @can('save-files', $server)
        Editor.commands.addCommand({
            name: 'save',
            bindKey: {win: 'Ctrl-S',  mac: 'Command-S'},
            exec: function(editor) {
                save();
            },
            readOnly: false
        });

        $('#save_file').on('click', function (e) {
            e.preventDefault();
            save();
        });

        function save() {
            var fileName = $('input[name="file"]').val();
            $('#save_file').append(' <i class="fa fa-spinner fa fa-spin"></i>').addClass('disabled');
            $.ajax({
                type: 'POST',
                url: '{{ route('server.files.save', $server->uuidShort) }}',
                headers: { 'X-CSRF-Token': '{{ csrf_token() }}' },
                data: {
                    file: fileName,
                    contents: Editor.getValue()
                }
            }).done(function (data) {
                $.notify({
                    message: '{{ trans('server.files.saved') }}'
                }, {
                    type: 'success'
                });
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
    @endcan
});
</script>
@endsection
