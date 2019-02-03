{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/eggs.header.title')
@endsection

@section('content-header')
    <h1>@lang('admin/eggs.header.overview')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/eggs.header.admin')</a></li>
        <li><a href="{{ route('admin.nests') }}">@lang('admin/eggs.header.nests')</a></li>
        <li class="active">@lang('admin/eggs.header.new_egg')</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.nests.egg.new') }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/eggs.content.configuration')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pNestId" class="form-label">@lang('admin/eggs.content.associated_nest')</label>
                                <div>
                                    <select name="nest_id" id="pNestId">
                                        @foreach($nests as $nest)
                                            <option value="{{ $nest->id }}" {{ old('nest_id') != $nest->id ?: 'selected' }}>{{ $nest->name }} &lt;{{ $nest->author }}&gt;</option>
                                        @endforeach
                                    </select>
                                    <p class="text-muted small">@lang('admin/eggs.content.associated_nest_description')</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="pName" class="form-label">@lang('admin/eggs.content.name')</label>
                                <input type="text" id="pName" name="name" value="{{ old('name') }}" class="form-control" />
                                <p class="text-muted small">@lang('admin/eggs.content.name_description')</p>
                            </div>
                            <div class="form-group">
                                <label for="pDescription" class="form-label">@lang('admin/eggs.content.description')</label>
                                <textarea id="pDescription" name="description" class="form-control" rows="8">{{ old('description') }}</textarea>
                                <p class="text-muted small">@lang('admin/eggs.content.name_description_description')</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pDockerImage" class="control-label">@lang('admin/eggs.content.docker')</label>
                                <input type="text" id="pDockerImage" name="docker_image" value="{{ old('docker_image') }}" placeholder="quay.io/pterodactyl/service" class="form-control" />
                                <p class="text-muted small">@lang('admin/eggs.content.docker_description')</p>
                            </div>
                            <div class="form-group">
                                <label for="pStartup" class="control-label">@lang('admin/eggs.content.startup_command')</label>
                                <textarea id="pStartup" name="startup" class="form-control" rows="14">{{ old('startup') }}</textarea>
                                <p class="text-muted small">@lang('admin/eggs.content.description')</p>
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
                                <p>@lang('admin/eggs.content.process_management_description')</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pConfigFrom" class="form-label">@lang('admin/eggs.content.copy_settings_from')</label>
                                <select name="config_from" id="pConfigFrom" class="form-control">
                                    <option value="">@lang('admin/eggs.content.none')</option>
                                </select>
                                <p class="text-muted small">@lang('admin/eggs.content.description')</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigStop" class="form-label">@lang('admin/eggs.content.stop_command')</label>
                                <input type="text" id="pConfigStop" name="config_stop" class="form-control" value="{{ old('config_stop') }}" />
                                <p class="text-muted small">@lang('admin/eggs.content.stop_command_description')</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigLogs" class="form-label">@lang('admin/eggs.content.log_config')</label>
                                <textarea data-action="handle-tabs" id="pConfigLogs" name="config_logs" class="form-control" rows="6">{{ old('config_logs') }}</textarea>
                                <p class="text-muted small">@lang('admin/eggs.content.log_config_description')</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pConfigFiles" class="form-label">@lang('admin/eggs.content.config_file')</label>
                                <textarea data-action="handle-tabs" id="pConfigFiles" name="config_files" class="form-control" rows="6">{{ old('config_files') }}</textarea>
                                <p class="text-muted small">@lang('admin/eggs.content.config_file_description')</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigStartup" class="form-label">@lang('admin/eggs.content.start_config')</label>
                                <textarea data-action="handle-tabs" id="pConfigStartup" name="config_startup" class="form-control" rows="6">{{ old('config_startup') }}</textarea>
                                <p class="text-muted small">@lang('admin/eggs.content.start_config_description')</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-success btn-sm pull-right">@lang('admin/eggs.content.create')</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/lodash/lodash.js') !!}
    <script>
    $(document).ready(function() {
        $('#pNestId').select2().change();
        $('#pConfigFrom').select2();
    });
    $('#pNestId').on('change', function (event) {
        $('#pConfigFrom').html('<option value="">None</option>').select2({
            data: $.map(_.get(Pterodactyl.nests, $(this).val() + '.eggs', []), function (item) {
                return {
                    id: item.id,
                    text: item.name + ' <' + item.author + '>',
                };
            }),
        });
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
