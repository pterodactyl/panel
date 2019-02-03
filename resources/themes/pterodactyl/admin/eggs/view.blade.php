{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/eggs.view.header.title') {{ $egg->name }}
@endsection

@section('content-header')
    <h1>{{ $egg->name }}<small>{{ str_limit($egg->description, 50) }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/eggs.view.header.admin')</a></li>
        <li><a href="{{ route('admin.nests') }}">@lang('admin/eggs.view.header.nests')</a></li>
        <li><a href="{{ route('admin.nests.view', $egg->nest->id) }}">{{ $egg->nest->name }}</a></li>
        <li class="active">{{ $egg->name }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li class="active"><a href="{{ route('admin.nests.egg.view', $egg->id) }}">@lang('admin/eggs.variables.content.configuration')</a></li>
                <li><a href="{{ route('admin.nests.egg.variables', $egg->id) }}">@lang('admin/eggs.variables.content.variables')</a></li>
                <li><a href="{{ route('admin.nests.egg.scripts', $egg->id) }}">@lang('admin/eggs.variables.content.scripts')</a></li>
            </ul>
        </div>
    </div>
    <div class="col-xs-12">
        <div class="alert alert-info">
            @lang('admin/eggs.view.content.alert')
    </div>
</div>
<form action="{{ route('admin.nests.egg.view', $egg->id) }}" enctype="multipart/form-data" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-danger">
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-8">
                            <div class="form-group no-margin-bottom">
                                <label for="pName" class="control-label">@lang('admin/eggs.view.content.egg_file')</label>
                                <div>
                                    <input type="file" name="import_file" class="form-control" style="border: 0;margin-left:-10px;" />
                                    <p class="text-muted small no-margin-bottom">@lang('admin/eggs.view.content.description')</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-4">
                            {!! csrf_field() !!}
                            <button type="submit" name="_method" value="PUT" class="btn btn-sm btn-danger pull-right">@lang('admin/eggs.view.content.update_egg')</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<form action="{{ route('admin.nests.egg.view', $egg->id) }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/eggs.variables.content.configuration')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pName" class="control-label">@lang('admin/eggs.variables.content.name') <span class="field-required"></span></label>
                                <input type="text" id="pName" name="name" value="{{ $egg->name }}" class="form-control" />
                                <p class="text-muted small">@lang('admin/eggs.variables.content.egg_name_description')</p>
                            </div>
                            <div class="form-group">
                                <label for="pUuid" class="control-label">@lang('admin/eggs.variables.content.uuid')</label>
                                <input type="text" id="pUuid" readonly value="{{ $egg->uuid }}" class="form-control" />
                                <p class="text-muted small">@lang('admin/eggs.variables.content.uuid_description')</p>
                            </div>
                            <div class="form-group">
                                <label for="pAuthor" class="control-label">@lang('admin/eggs.variables.content.author')</label>
                                <input type="text" id="pAuthor" readonly value="{{ $egg->author }}" class="form-control" />
                                <p class="text-muted small">@lang('admin/eggs.variables.content.author_description')</p>
                            </div>
                            <div class="form-group">
                                <label for="pDockerImage" class="control-label">@lang('admin/eggs.content.docker') <span class="field-required"></span></label>
                                <input type="text" id="pDockerImage" name="docker_image" value="{{ $egg->docker_image }}" class="form-control" />
                                <p class="text-muted small">@lang('admin/eggs.variables.content.docker_description')</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pDescription" class="control-label">@lang('admin/eggs.content.description') <span class="field-required"></span></label>
                                <textarea id="pDescription" name="description" class="form-control" rows="6">{{ $egg->description }}</textarea>
                                <p class="text-muted small">@lang('admin/eggs.variables.content.egg_description')</p>
                            </div>
                            <div class="form-group">
                                <label for="pStartup" class="control-label">@lang('admin/eggs.content.startup_command') <span class="field-required"></span></label>
                                <textarea id="pStartup" name="startup" class="form-control" rows="6">{{ $egg->startup }}</textarea>
                                <p class="text-muted small">@lang('admin/eggs.view.content.startup_command_description')</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/eggs.content.process_management')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="alert alert-warning">
                                @lang('admin/eggs.view.content.process_management_description')
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pConfigFrom" class="form-label">@lang('admin/eggs.content.copy_settings_from')</label>
                                <select name="config_from" id="pConfigFrom" class="form-control">
                                    <option value="">@lang('admin/eggs.content.none')</option>
                                    @foreach($egg->nest->eggs as $o)
                                        <option value="{{ $o->id }}" {{ ($egg->config_from !== $o->id) ?: 'selected' }}>{{ $o->name }} &lt;{{ $o->author }}&gt;</option>
                                    @endforeach
                                </select>
                                <p class="text-muted small">@lang('admin/eggs.view.content.copy_description')</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigStop" class="form-label">@lang('admin/eggs.content.stop_command')</label>
                                <input type="text" id="pConfigStop" name="config_stop" class="form-control" value="{{ $egg->config_stop }}" />
                                <p class="text-muted small">@lang('admin/eggs.content.stop_command_description')</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigLogs" class="form-label">@lang('admin/eggs.content.log_config')</label>
                                <textarea data-action="handle-tabs" id="pConfigLogs" name="config_logs" class="form-control" rows="6">{{ ! is_null($egg->config_logs) ? json_encode(json_decode($egg->config_logs), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                                <p class="text-muted small">@lang('admin/eggs.content.log_config_description')</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pConfigFiles" class="form-label">@lang('admin/eggs.content.config_files')</label>
                                <textarea data-action="handle-tabs" id="pConfigFiles" name="config_files" class="form-control" rows="6">{{ ! is_null($egg->config_files) ? json_encode(json_decode($egg->config_files), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                                <p class="text-muted small">@lang('admin/eggs.content.config_files_description')</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigStartup" class="form-label">@lang('admin/eggs.content.start_config')</label>
                                <textarea data-action="handle-tabs" id="pConfigStartup" name="config_startup" class="form-control" rows="6">{{ ! is_null($egg->config_startup) ? json_encode(json_decode($egg->config_startup), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                                <p class="text-muted small">@lang('admin/eggs.content.start_config_description')</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" name="_method" value="PATCH" class="btn btn-primary btn-sm pull-right">@lang('admin/eggs.scripts.content.save')</button>
                    <a href="{{ route('admin.nests.egg.export', ['option' => $egg->id]) }}" class="btn btn-sm btn-info pull-right" style="margin-right:10px;">@lang('admin/eggs.view.content.export')</a>
                    <button id="deleteButton" type="submit" name="_method" value="DELETE" class="btn btn-danger btn-sm muted muted-hover">
                        <i class="fa fa-trash-o"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="col-xs-12">
            <div class="alert alert-info">
                @lang('admin/eggs.view.content.alert')
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
        $(this).find('i').html(' Delete Egg');
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
