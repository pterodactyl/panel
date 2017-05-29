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
    Services &rarr; Option: {{ $option->name }}
@endsection

@section('content-header')
    <h1>{{ $option->name }}<small>{{ str_limit($option->description, 50) }}</small></h1>
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
                <li class="active"><a href="{{ route('admin.services.option.view', $option->id) }}">Configuration</a></li>
                <li><a href="{{ route('admin.services.option.variables', $option->id) }}">Variables</a></li>
                <li><a href="{{ route('admin.services.option.scripts', $option->id) }}">Scripts</a></li>
            </ul>
        </div>
    </div>
</div>
<form action="{{ route('admin.services.option.view', $option->id) }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="callout callout-info">
                <strong>Notice:</strong> Editing the Option Tag or any of the Process Management fields <em>requires</em> that each daemon be rebooted to apply the changes.
            </div>
        </div>
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Configuration</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pName" class="form-label">Option Name</label>
                                <input type="text" id="pName" name="name" value="{{ $option->name }}" class="form-control" />
                                <p class="text-muted small">A simple, human-readable name to use as an identifier for this service.</p>
                            </div>
                            <div class="form-group">
                                <label for="pDescription" class="form-label">Description</label>
                                <textarea id="pDescription" name="description" class="form-control" rows="10">{{ $option->description }}</textarea>
                                <p class="text-muted small">A description of this service that will be displayed throughout the panel as needed.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pTag" class="form-label">Option Tag</label>
                                <input type="text" id="pTag" name="tag" value="{{ $option->tag }}" class="form-control" />
                                <p class="text-muted small">This should be a unique identifer for this service option that is not used for any other service options.</p>
                            </div>
                            <div class="form-group">
                                <label for="pDockerImage" class="form-label">Docker Image</label>
                                <input type="text" id="pDockerImage" name="docker_image" value="{{ $option->docker_image }}" class="form-control" />
                                <p class="text-muted small">The default docker image that should be used for new servers under this service option. This can be left blank to use the parent service's defined image, and can also be changed per-server.</p>
                            </div>
                            <div class="form-group">
                                <label for="pStartup" class="form-label">Startup Command</label>
                                <textarea id="pStartup" name="startup" class="form-control" rows="4" placeholder="{{ $option->service->startup }}">{{ $option->startup }}</textarea>
                                <p class="text-muted small">The default statup command that should be used for new servers under this service option. This can be left blank to use the parent service's startup, and can also be changed per-server.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Process Management</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="alert alert-warning">
                                <p>The following configuration options should not be edited unless you understand how this system works. If wrongly modified it is possible for the daemon to break.</p>
                                <p>All fields are required unless you select a seperate option from the 'Copy Settings From' dropdown, in which case fields may be left blank to use the values from that option.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pConfigFrom" class="form-label">Copy Settings From</label>
                                <select name="config_from" id="pConfigFrom" class="form-control">
                                    <option value="0">None</option>
                                    @foreach($option->service->options as $o)
                                        <option value="{{ $o->id }}" {{ ($option->config_from !== $o->id) ?: 'selected' }}>{{ $o->name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-muted small">If you would like to default to settings from another option select the option from the menu above.</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigStop" class="form-label">Stop Command</label>
                                <input type="text" id="pConfigStop" name="config_stop" class="form-control" value="{{ $option->config_stop }}" />
                                <p class="text-muted small">The command that should be sent to server processes to stop them gracefully. If you need to send a <code>SIGINT</code> you should enter <code>^C</code> here.</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigLogs" class="form-label">Log Configuration</label>
                                <textarea data-action="handle-tabs" id="pConfigLogs" name="config_logs" class="form-control" rows="6">{{ ! is_null($option->config_logs) ? json_encode(json_decode($option->config_logs), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                                <p class="text-muted small">This should be a JSON representation of where log files are stored, and wether or not the daemon should be creating custom logs.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pConfigFiles" class="form-label">Configuration Files</label>
                                <textarea data-action="handle-tabs" id="pConfigFiles" name="config_files" class="form-control" rows="6">{{ ! is_null($option->config_files) ? json_encode(json_decode($option->config_files), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                                <p class="text-muted small">This should be a JSON representation of configuration files to modify and what parts should be changed.</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigStartup" class="form-label">Start Configuration</label>
                                <textarea data-action="handle-tabs" id="pConfigStartup" name="config_startup" class="form-control" rows="6">{{ ! is_null($option->config_startup) ? json_encode(json_decode($option->config_startup), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                                <p class="text-muted small">This should be a JSON representation of what values the daemon should be looking for when booting a server to determine completion.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button id="deleteButton" type="submit" name="action" value="delete" class="btn btn-danger btn-sm muted muted-hover">
                        <i class="fa fa-trash-o"></i>
                    </button>
                    <button type="submit" name="action" value="edit" class="btn btn-primary btn-sm pull-right">Edit Service</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('#pConfigFrom').select2();
    $('#deleteButton').on('mouseenter', function (event) {
        $(this).find('i').html(' Delete Option');
    }).on('mouseleave', function (event) {
        $(this).find('i').html('');
    });
    $('textarea[data-action="handle-tabs"]').on('keydown', function(event) {
        if (event.keyCode === 9) {
            event.preventDefault();

            var curPos = $(this)[0].selectionStart;
            var prepend = $(this).val().substr(0, curPos);
            var append = $(this).val().substr(curPos);

            $(this).val(prepend + '    ' + append);
        }
    });
    </script>
@endsection
