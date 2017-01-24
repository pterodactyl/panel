{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

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
@extends('layouts.admin')

@section('title')
    Manage Service Configuration
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/services">Services</a></li>
        <li><a href="{{ route('admin.services.service', $service->id) }}">{{ $service->name }}</a></li>
        <li class="active">Configuration</li>
    </ul>
    <h3 class="nopad">Service Configuration</h3><hr />
    <ul class="nav nav-tabs tabs_with_panel" id="config_tabs">
        <li class="active"><a href="#tab_main" data-toggle="tab">main.json</a></li>
        <li><a href="#tab_index" data-toggle="tab">index.js</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="tab_main">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body" style="padding-top:0;">
                    <div class="row" style="border-bottom:1px solid #ccc;">
                        <div class="col-md-12" style="margin:0; padding:0;">
                            <div id="editor_json" style="height:500px;">{{ $contents['json'] }}</div>
                        </div>
                    </div>
                    <div class="row" style="margin-top:15px;">
                        <div class="col-md-12">
                            <button type="submit" id="save_json" class="btn btn-sm btn-success">Save Configuration</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="tab_index">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body" style="padding-top:0;">
                    <div class="row" style="border-bottom:1px solid #ccc;">
                        <div class="col-md-12" style="margin:0; padding:0;">
                            <div id="editor_index" style="height:500px;">{{ $contents['index'] }}</div>
                        </div>
                    </div>
                    <div class="row" style="margin-top:15px;">
                        <div class="col-md-12">
                            <button type="submit" id="save_index" class="btn btn-sm btn-success">Save Scripting</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{!! Theme::js('js/vendor/ace/ace.js') !!}
{!! Theme::js('js/vendor/ace/ext-modelist.js') !!}
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/services']").addClass('active');

    const JsonEditor = ace.edit('editor_json');
    const IndexEditor = ace.edit('editor_index');
    const Modelist = ace.require('ace/ext/modelist')

    JsonEditor.setTheme('ace/theme/chrome');
    JsonEditor.getSession().setMode('ace/mode/json');
    JsonEditor.getSession().setUseWrapMode(true);
    JsonEditor.setShowPrintMargin(false);

    IndexEditor.setTheme('ace/theme/chrome');
    IndexEditor.getSession().setMode('ace/mode/javascript');
    IndexEditor.getSession().setUseWrapMode(true);
    IndexEditor.setShowPrintMargin(false);

    JsonEditor.commands.addCommand({
        name: 'save',
        bindKey: {win: 'Ctrl-S',  mac: 'Command-S'},
        exec: function(editor) {
            saveConfig();
        },
        readOnly: false
    });

    IndexEditor.commands.addCommand({
        name: 'save',
        bindKey: {win: 'Ctrl-S',  mac: 'Command-S'},
        exec: function(editor) {
            saveIndex();
        },
        readOnly: false
    });

    $('#save_json').on('click', function (e) {
        e.preventDefault();
        saveConfig();
    });

    $('#save_index').on('click', function (e) {
        e.preventDefault();
        saveIndex();
    });

    function saveConfig() {
        $('#save_json').append(' <i class="fa fa-spinner fa fa-spin"></i>').addClass('disabled');
        $.ajax({
            type: 'POST',
            url: '{{ route('admin.services.service.config', $service->id) }}',
            headers: { 'X-CSRF-Token': '{{ csrf_token() }}' },
            data: {
                file: 'main',
                contents: JsonEditor.getValue()
            }
        }).done(function (data) {
            $.notify({
                message: 'Service configuration file has been saved successfully.'
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
            $('#save_json').html('Save Configuration').removeClass('disabled');
        });
    }

    function saveIndex() {
        $('#save_json').append(' <i class="fa fa-spinner fa fa-spin"></i>').addClass('disabled');
        $.ajax({
            type: 'POST',
            url: '{{ route('admin.services.service.config', $service->id) }}',
            headers: { 'X-CSRF-Token': '{{ csrf_token() }}' },
            data: {
                file: 'index',
                contents: IndexEditor.getValue()
            }
        }).done(function (data) {
            $.notify({
                message: 'Service scripting file has been saved successfully.'
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
            $('#save_json').html('Save Scripting').removeClass('disabled');
        });
    }

});
</script>
@endsection
