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
    Services &rarr; Option: {{ $option->name }} &rarr; Scripts
@endsection

@section('content-header')
    <h1>{{ $option->name }}<small>Manage install and upgrade scripts for this service option.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.services') }}">Services</a></li>
        <li><a href="{{ route('admin.services.view', $option->service->id) }}">{{ $option->service->name }}</a></li>
        <li class="active">{{ $option->name }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.services.option.view', $option->id) }}">Configuration</a></li>
                <li><a href="{{ route('admin.services.option.variables', $option->id) }}">Variables</a></li>
                <li class="active"><a href="{{ route('admin.services.option.scripts', $option->id) }}">Scripts</a></li>
            </ul>
        </div>
    </div>
</div>
<form action="{{ route('admin.services.option.scripts', $option->id) }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Install Script</h3>
                </div>
                @if(! is_null($option->copyFrom))
                    <div class="box-body">
                        <div class="callout callout-warning no-margin">
                            This service option is copying installation scripts and containe options from <a href="{{ route('admin.services.option.view', $option->copyFrom->id) }}">{{ $option->copyFrom->name }}</a>. Any changes you make to this script will not apply unless you select "None" from the dropdown box below.
                        </div>
                    </div>
                @endif
                <div class="box-body no-padding">
                    <div id="editor_install"style="height:300px">{{ $option->script_install }}</div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-sm-4">
                            <label class="control-label">Copy Script From</label>
                            <select id="pCopyScriptFrom" name="copy_script_from">
                                <option value="0">None</option>
                                @foreach($copyFromOptions as $opt)
                                    <option value="{{ $opt->id }}" {{ $option->copy_script_from !== $opt->id ?: 'selected' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-muted small">If selected, script above will be ignored and script from selected option will be used in place.</p>
                        </div>
                        <div class="form-group col-sm-4">
                            <label class="control-label">Script Container</label>
                            <input type="text" name="script_container" class="form-control" value="{{ $option->script_container }}" />
                            <p class="text-muted small">Docker container to use when running this script for the server.</p>
                        </div>
                        <div class="form-group col-sm-4">
                            <label class="control-label">Script Entrypoint Command</label>
                            <input type="text" name="script_entry" class="form-control" value="{{ $option->script_entry }}" />
                            <p class="text-muted small">The entrypoint command to use for this script.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 text-muted">
                            The following service options rely on this script:
                            @if(count($relyOnScript) > 0)
                                @foreach($relyOnScript as $rely)
                                    <a href="{{ route('admin.services.option.view', $rely->id) }}">
                                        <code>{{ $rely->name }}</code>&nbsp;
                                    </a>
                                @endforeach
                            @else
                                <em>none</em>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <textarea name="script_install" class="hidden"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm pull-right">Save Script</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/ace/ace.js') !!}
    {!! Theme::js('vendor/ace/ext-modelist.js') !!}
    <script>
    $(document).ready(function () {
        $('#pCopyScriptFrom').select2();

        const InstallEditor = ace.edit('editor_install');
        const Modelist = ace.require('ace/ext/modelist')

        InstallEditor.setTheme('ace/theme/chrome');
        InstallEditor.getSession().setMode('ace/mode/sh');
        InstallEditor.getSession().setUseWrapMode(true);
        InstallEditor.setShowPrintMargin(false);

        $('form').on('submit', function (e) {
            $('textarea[name="script_install"]').val(InstallEditor.getValue());
        });
    });
    </script>

@endsection
