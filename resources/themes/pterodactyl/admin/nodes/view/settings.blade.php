{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    {{ $node->name }}: @lang('admin/nodes_view.content.settings')
@endsection

@section('content-header')
    <h1>{{ $node->name }}@lang('admin/nodes_view.servers.header.overview')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/nodes_view.header.admin')</a></li>
        <li><a href="{{ route('admin.nodes') }}">@lang('admin/nodes_view.header.nodes')</a></li>
        <li><a href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></li>
        <li class="active">@lang('admin/nodes_view.content.settings')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.nodes.view', $node->id) }}">@lang('admin/nodes_view.content.about')</a></li>
                <li class="active"><a href="{{ route('admin.nodes.view.settings', $node->id) }}">@lang('admin/nodes_view.content.settings')</a></li>
                <li><a href="{{ route('admin.nodes.view.configuration', $node->id) }}">@lang('admin/nodes_view.content.configuration')</a></li>
                <li><a href="{{ route('admin.nodes.view.allocation', $node->id) }}">@lang('admin/nodes_view.content.allocation')</a></li>
                <li><a href="{{ route('admin.nodes.view.servers', $node->id) }}">@lang('admin/nodes_view.content.servers')</a></li>
            </ul>
        </div>
    </div>
</div>
<form action="{{ route('admin.nodes.view.settings', $node->id) }}" method="POST">
    <div class="row">
        <div class="col-sm-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/nodes_view.content.settings')</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-xs-12">
                        <label for="name" class="control-label">@lang('admin/nodes_view.settings.content.name')</label>
                        <div>
                            <input type="text" autocomplete="off" name="name" class="form-control" value="{{ old('name', $node->name) }}" />
                            <p class="text-muted">@lang('admin/nodes_view.settings.content.limit')</p>
                        </div>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="description" class="control-label">@lang('admin/nodes_view.index.header.description')</label>
                        <div>
                            <textarea name="description" id="description" rows="4" class="form-control">{{ $node->description }}</textarea>
                        </div>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="name" class="control-label">@lang('admin/nodes_view.settings.content.location')</label>
                        <div>
                            <select name="location_id" class="form-control">
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ (old('location_id', $node->location_id) === $location->id) ? 'selected' : '' }}>{{ $location->long }} ({{ $location->short }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="public" class="control-label">@lang('admin/nodes_view.settings.content.auto')<sup><a data-toggle="tooltip" data-placement="top" title="@lang('admin/nodes_view.settings.content.auto_title')">?</a></sup></label>
                        <div>
                            <input type="radio" name="public" value="1" {{ (old('public', $node->public) === '1') ? 'checked' : '' }} id="public_1" checked> <label for="public_1" style="padding-left:5px;">@lang('admin/nodes_view.settings.content.yes')</label><br />
                            <input type="radio" name="public" value="0" {{ (old('public', $node->public) === '0') ? 'checked' : '' }} id="public_0"> <label for="public_0" style="padding-left:5px;">@lang('admin/nodes_view.settings.content.no')</label>
                        </div>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="fqdn" class="control-label">@lang('admin/nodes_view.settings.content.fqdn')</label>
                        <div>
                            <input type="text" autocomplete="off" name="fqdn" class="form-control" value="{{ old('fqdn', $node->fqdn) }}" />
                        </div>
                        <p class="text-muted">@lang('admin/nodes_view.settings.content.fqdn_hint')
                                <a tabindex="0" data-toggle="popover" data-trigger="focus" title="@lang('admin/nodes_view.settings.content.fqdn_title')" data-content="@lang('admin/nodes_view.settings.content.fqdn_answer')">@lang('admin/nodes_view.settings.content.why')</a>
                            </small></p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label class="form-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> @lang('admin/nodes_view.settings.content.ssl')</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pSSLTrue" value="https" name="scheme" {{ (old('scheme', $node->scheme) === 'https') ? 'checked' : '' }}>
                                <label for="pSSLTrue"> @lang('admin/nodes_view.settings.content.use_ssl')</label>
                            </div>
                            <div class="radio radio-danger radio-inline">
                                <input type="radio" id="pSSLFalse" value="http" name="scheme" {{ (old('scheme', $node->scheme) !== 'https') ? 'checked' : '' }}>
                                <label for="pSSLFalse"> @lang('admin/nodes_view.settings.content.use_http')</label>
                            </div>
                        </div>
                        <p class="text-muted small">@lang('admin/nodes_view.settings.content.http_hint')</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label class="form-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> @lang('admin/nodes_view.settings.content.proxy_true')</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pProxyFalse" value="0" name="behind_proxy" {{ (old('behind_proxy', $node->behind_proxy) == false) ? 'checked' : '' }}>
                                <label for="pProxyFalse"> @lang('admin/nodes_view.settings.content.proxy_false') </label>
                            </div>
                            <div class="radio radio-info radio-inline">
                                <input type="radio" id="pProxyTrue" value="1" name="behind_proxy" {{ (old('behind_proxy', $node->behind_proxy) == true) ? 'checked' : '' }}>
                                <label for="pProxyTrue"> @lang('admin/nodes_view.settings.content.proxy_true')</label>
                            </div>
                        </div>
                        <p class="text-muted small">@lang('admin/nodes_view.settings.content.proxy_hint')</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label class="form-label"><span class="label label-warning"><i class="fa fa-wrench"></i></span> @lang('admin/nodes_view.settings.content.maintenance')</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pMaintenanceFalse" value="0" name="maintenance_mode" {{ (old('behind_proxy', $node->maintenance_mode) == false) ? 'checked' : '' }}>
                                <label for="pMaintenanceFalse"> @lang('admin/nodes_view.settings.content.disabled')</label>
                            </div>
                            <div class="radio radio-warning radio-inline">
                                <input type="radio" id="pMaintenanceTrue" value="1" name="maintenance_mode" {{ (old('behind_proxy', $node->maintenance_mode) == true) ? 'checked' : '' }}>
                                <label for="pMaintenanceTrue"> @lang('admin/nodes_view.settings.content.enabled')</label>
                            </div>
                        </div>
                        <p class="text-muted small">@lang('admin/nodes_view.settings.content.maintenance_hint')</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/nodes_view.settings.content.allocation_limit')</h3>
                </div>
                <div class="box-body row">
                    <div class="col-xs-12">
                        <div class="row">
                            <div class="form-group col-xs-6">
                                <label for="memory" class="control-label">@lang('admin/nodes_view.settings.content.memory')</label>
                                <div class="input-group">
                                    <input type="text" name="memory" class="form-control" data-multiplicator="true" value="{{ old('memory', $node->memory) }}"/>
                                    <span class="input-group-addon">MB</span>
                                </div>
                            </div>
                            <div class="form-group col-xs-6">
                                <label for="memory_overallocate" class="control-label">@lang('admin/nodes_view.settings.content.overallocate')</label>
                                <div class="input-group">
                                    <input type="text" name="memory_overallocate" class="form-control" value="{{ old('memory_overallocate', $node->memory_overallocate) }}"/>
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>
                        <p class="text-muted small">@lang('admin/nodes_view.settings.content.overallocate_hint')</p>
                    </div>
                    <div class="col-xs-12">
                        <div class="row">
                            <div class="form-group col-xs-6">
                                <label for="disk" class="control-label">@lang('admin/nodes_view.settings.content.overallocate_hint')</label>
                                <div class="input-group">
                                    <input type="text" name="disk" class="form-control" data-multiplicator="true" value="{{ old('disk', $node->disk) }}"/>
                                    <span class="input-group-addon">MB</span>
                                </div>
                            </div>
                            <div class="form-group col-xs-6">
                                <label for="disk_overallocate" class="control-label">@lang('admin/nodes_view.settings.content.overallocate')</label>
                                <div class="input-group">
                                    <input type="text" name="disk_overallocate" class="form-control" value="{{ old('disk_overallocate', $node->disk_overallocate) }}"/>
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>
                        <p class="text-muted small">@lang('admin/nodes_view.settings.content.disk_space_hint')</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/nodes_view.settings.content.configuration')</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-xs-12">
                        <label for="disk_overallocate" class="control-label">@lang('admin/nodes_view.settings.content.max_upload')</label>
                        <div class="input-group">
                            <input type="text" name="upload_size" class="form-control" value="{{ old('upload_size', $node->upload_size) }}"/>
                            <span class="input-group-addon">MB</span>
                        </div>
                        <p class="text-muted"><small>@lang('admin/nodes_view.settings.content.max_upload_hint')</small></p>
                    </div>
                    <div class="col-xs-12">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="daemonListen" class="control-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> @lang('admin/nodes_view.settings.content.daemon_port')</label>
                                <div>
                                    <input type="text" name="daemonListen" class="form-control" value="{{ old('daemonListen', $node->daemonListen) }}"/>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="daemonSFTP" class="control-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> @lang('admin/nodes_view.settings.content.daemon_port')</label>
                                <div>
                                    <input type="text" name="daemonSFTP" class="form-control" value="{{ old('daemonSFTP', $node->daemonSFTP) }}"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <p class="text-muted"><small>@lang('admin/nodes_view.settings.content.sftp_hint')</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/nodes_view.settings.content.save_settings')</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-sm-6">
                        <div>
                            <input type="checkbox" name="reset_secret" id="reset_secret" /> <label for="reset_secret" class="control-label">@lang('admin/nodes_view.settings.content.reset_key')</label>
                        </div>
                        <p class="text-muted">@lang('admin/nodes_view.settings.content.reset_key_hint')</p>
                    </div>
                </div>
                <div class="box-footer">
                    {!! method_field('PATCH') !!}
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-primary pull-right">@lang('admin/nodes_view.settings.content.save_changes')</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('[data-toggle="popover"]').popover({
        placement: 'auto'
    });
    $('select[name="location_id"]').select2();
    </script>
@endsection