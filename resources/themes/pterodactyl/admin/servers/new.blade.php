{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/servers.new.header.title')
@endsection

@section('content-header')
    <h1>@lang('admin/servers.new.header.overview')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/servers_view.header.admin')</a></li>
        <li><a href="{{ route('admin.servers') }}">@lang('admin/servers_view.header.servers')</a></li>
        <li class="active">@lang('admin/servers_view.header.create_server')</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.servers.new') }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers.new.content.core_details')</h3>
                </div>
                <div class="box-body row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pName">@lang('admin/servers.content.server_name')</label>
                            <input type="text" class="form-control" id="pName" name="name" value="{{ old('name') }}" placeholder="Server Name">
                            <p class="small text-muted no-margin">@lang('admin/servers.new.content.character_limit')</p>
                        </div>
                        <div class="form-group">
                            <label for="pUserId">@lang('admin/servers_view.details.content.server_owner')</label>
                            <select class="form-control" style="padding-left:0;" name="owner_id" id="pUserId"></select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="description" class="control-label">@lang('admin/servers_view.details.content.server_desc')</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                            <p class="text-muted small">@lang('admin/servers_view.details.content.server_desc_hint')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="pStartOnCreation" name="start_on_completion" type="checkbox" value="1" checked />
                                <label for="pStartOnCreation" class="strong">@lang('admin/servers.new.content.start_when_installed')</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="overlay" id="allocationLoader" style="display:none;"><i class="fa fa-refresh fa-spin"></i></div>
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers_view.content.alloc_manage')</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-sm-4">
                        <label for="pNodeId">@lang('admin/servers.new.content.node')</label>
                        <select name="node_id" id="pNodeId" class="form-control">
                            @foreach($locations as $location)
                                <optgroup label="{{ $location->long }} ({{ $location->short }})">
                                @foreach($location->nodes as $node)

                                <option value="{{ $node->id }}"
                                    @if($location->id === old('location_id')) selected @endif
                                >{{ $node->name }}</option>

                                @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="small text-muted no-margin">@lang('admin/servers.new.content.node_description')</p>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="pAllocation">@lang('admin/servers.new.content.default_alloc')</label>
                        <select name="allocation_id" id="pAllocation" class="form-control"></select>
                        <p class="small text-muted no-margin">@lang('admin/servers.new.content.default_alloc_hint')</p>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="pAllocationAdditional">@lang('admin/servers.new.content.additional_alloc')</label>
                        <select name="allocation_additional[]" id="pAllocationAdditional" class="form-control" multiple></select>
                        <p class="small text-muted no-margin">@lang('admin/servers.new.content.additional_alooc_hint')</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="overlay" id="allocationLoader" style="display:none;"><i class="fa fa-refresh fa-spin"></i></div>
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers_view.content.feature_limit')</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-xs-6">
                        <label for="cpu" class="control-label">@lang('admin/servers_view.content.db_limit')</label>
                        <div>
                            <input type="text" name="database_limit" class="form-control" value="{{ old('database_limit', 0) }}"/>
                        </div>
                        <p class="text-muted small">@lang('admin/servers_view.content.db_limit_hint')</p>
                    </div>
                    <div class="form-group col-xs-6">
                        <label for="cpu" class="control-label">@lang('admin/servers_view.content.alloc_limit')</label>
                        <div>
                            <input type="text" name="allocation_limit" class="form-control" value="{{ old('allocation_limit', 0) }}"/>
                        </div>
                        <p class="text-muted small">@lang('admin/servers_view.content.alloc_limit_hint')</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers.new.content.resouce_manage')</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-sm-4">
                        <label for="pMemory">@lang('admin/servers_view.index.memory')</label>
                        <div class="input-group">
                            <input type="text" value="{{ old('memory') }}" class="form-control" name="memory" id="pMemory" />
                            <span class="input-group-addon">MB</span>
                        </div>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="pSwap">@lang('admin/servers.new.content.swap')</label>
                        <div class="input-group">
                            <input type="text" value="{{ old('swap', 0) }}" class="form-control" name="swap" id="pSwap" />
                            <span class="input-group-addon">MB</span>
                        </div>
                    </div>
                </div>
                <div class="box-footer no-border no-pad-top no-pad-bottom">
                    <p class="text-muted small">@lang('admin/servers.new.content.swap_hint')<p>
                </div>
                <div class="box-body row">
                    <div class="form-group col-sm-4">
                        <label for="pDisk">@lang('admin/servers_view.index.disk_space')</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{{ old('disk') }}" name="disk" id="pDisk" />
                            <span class="input-group-addon">MB</span>
                        </div>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="pCPU">@lang('admin/servers_view.index.cpu_limit')</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{{ old('cpu', 0) }}" name="cpu" id="pCPU" />
                            <span class="input-group-addon">%</span>
                        </div>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="pIO">@lang('admin/servers_view.index.block_io')</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{{ old('io', 500) }}" name="io" id="pIO" />
                            <span class="input-group-addon">@lang('admin/servers.new.content.io')</span>
                        </div>
                    </div>
                </div>
                <div class="box-footer no-border no-pad-top no-pad-bottom">
                    <p class="text-muted small">@lang('admin/servers.new.content.io_hint')<p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers.new.content.nest_conf')</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-xs-12">
                        <label for="pNestId">@lang('admin/servers.new.content.nest')</label>
                        <select name="nest_id" id="pNestId" class="form-control">
                            @foreach($nests as $nest)
                                <option value="{{ $nest->id }}"
                                    @if($nest->id === old('nest_id'))
                                        selected="selected"
                                    @endif
                                >{{ $nest->name }}</option>
                            @endforeach
                        </select>
                        <p class="small text-muted no-margin">@lang('admin/servers.new.content.nest_hint')</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="pEggId">@lang('admin/servers_view.startup.content.egg')</label>
                        <select name="egg_id" id="pEggId" class="form-control"></select>
                        <p class="small text-muted no-margin">@lang('admin/servers.new.content.egg_hint')</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="pPackId">@lang('admin/servers_view.startup.content.data_pack')</label>
                        <select name="pack_id" id="pPackId" class="form-control"></select>
                        <p class="small text-muted no-margin">@lang('admin/servers_view.startup.content.data_pack_hint')</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="pSkipScripting" name="skip_scripts" type="checkbox" value="1" />
                            <label for="pSkipScripting" class="strong">@lang('admin/servers_view.startup.content.skip_script')</label>
                        </div>
                        <p class="small text-muted no-margin">@lang('admin/servers_view.startup.content.skip_script_hint')</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers.new.content.docker_conf')</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-xs-12">
                        <label for="pDefaultContainer">@lang('admin/servers.new.content.docker_image')</label>
                        <input id="pDefaultContainer" name="image" value="{{ old('image') }}" class="form-control" />
                        <p class="small text-muted no-margin">@lang('admin/servers.new.content.docker_image_hint')</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers.new.content.startup_conf')</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-xs-12">
                        <label for="pStartup">@lang('admin/servers_view.startup.content.startup_command')</label>
                        <input type="text" id="pStartup" value="{{ old('startup') }}" class="form-control" name="startup" />
                        <p class="small text-muted no-margin">@lang('admin/servers.new.content.startup_command_hint')</p>
                    </div>
                </div>
                <div class="box-header with-border" style="margin-top:-10px;">
                    <h3 class="box-title">@lang('admin/servers.new.content.service_var')</h3>
                </div>
                <div class="box-body row" id="appendVariablesTo"></div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-success pull-right" value="@lang('admin/servers.new.header.create_server')" />
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/lodash/lodash.js') !!}
    {!! Theme::js('js/admin/new-server.js') !!}
@endsection
