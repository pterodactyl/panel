{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/servers_view.header.server') — {{ $server->name }}
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>{{ str_limit($server->description) }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/servers_view.header.admin')</a></li>
        <li><a href="{{ route('admin.servers') }}">@lang('admin/servers_view.header.servers')</a></li>
        <li class="active">{{ $server->name }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li class="active"><a href="{{ route('admin.servers.view', $server->id) }}">@lang('admin/servers_view.content.about')</a></li>
                @if($server->installed === 1)
                    <li><a href="{{ route('admin.servers.view.details', $server->id) }}">@lang('admin/servers_view.content.details')</a></li>
                    <li><a href="{{ route('admin.servers.view.build', $server->id) }}">@lang('admin/servers_view.header.build_config')</a></li>
                    <li><a href="{{ route('admin.servers.view.startup', $server->id) }}">@lang('admin/servers_view.content.startup')</a></li>
                    <li><a href="{{ route('admin.servers.view.database', $server->id) }}">@lang('admin/servers_view.content.database')</a></li>
                    <li><a href="{{ route('admin.servers.view.manage', $server->id) }}">@lang('admin/servers_view.content.manage')</a></li>
                @endif
                <li class="tab-danger"><a href="{{ route('admin.servers.view.delete', $server->id) }}">@lang('admin/servers_view.content.delete')</a></li>
                <li class="tab-success"><a href="{{ route('server.index', $server->uuidShort) }}"><i class="fa fa-external-link"></i></a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-8">
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/servers_view.index.information')</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-hover">
                            <tr>
                                <td>@lang('admin/servers_view.index.int_id')</td>
                                <td><code>{{ $server->id }}</code></td>
                            </tr>
                            <tr>
                                <td>@lang('admin/servers_view.details.content.ext_id')</td>
                                @if(is_null($server->external_id))
                                    <td><span class="label label-default">@lang('admin/servers_view.index.not_set')</span></td>
                                @else
                                    <td><code>{{ $server->external_id }}</code></td>
                                @endif
                            </tr>
                            <tr>
                                <td>@lang('admin/servers_view.index.uuid')</td>
                                <td><code>{{ $server->uuid }}</code></td>
                            </tr>
                            <tr>
                                <td>@lang('admin/servers_view.index.service')</td>
                                <td>
                                    <a href="{{ route('admin.nests.view', $server->nest_id) }}">{{ $server->nest->name }}</a> ::
                                    <a href="{{ route('admin.nests.egg.view', $server->egg_id) }}">{{ $server->egg->name }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td>@lang('admin/servers_view.index.name')</td>
                                <td>{{ $server->name }}</td>
                            </tr>
                            <tr>
                                <td>@lang('admin/servers_view.index.memory')</td>
                                <td><code>{{ $server->memory }}MB</code> / <code data-toggle="tooltip" data-placement="top" title="Swap Space">{{ $server->swap }}MB</code></td>
                            </tr>
                            <tr>
                                <td>@lang('admin/servers_view.index.disk_space')</td>
                                <td><code>{{ $server->disk }}MB</code></td>
                            </tr>
                            <tr>
                                <td>@lang('admin/servers_view.index.block_io')</td>
                                <td><code>{{ $server->io }}</code></td>
                            </tr>
                            <tr>
                                <td>@lang('admin/servers_view.index.cpu_limit')</td>
                                <td><code>{{ $server->cpu }}%</code></td>
                            </tr>
                            <tr>
                                <td>@lang('admin/servers_view.index.default_conn')</td>
                                <td><code>{{ $server->allocation->ip }}:{{ $server->allocation->port }}</code></td>
                            </tr>
                            <tr>
                                <td>@lang('admin/servers_view.index.conn_alias')</td>
                                <td>
                                    @if($server->allocation->alias !== $server->allocation->ip)
                                        <code>{{ $server->allocation->alias }}:{{ $server->allocation->port }}</code>
                                    @else
                                        <span class="label label-default">@lang('admin/servers_view.index.no_alias')</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="box box-primary">
            <div class="box-body" style="padding-bottom: 0px;">
                <div class="row">
                    @if($server->suspended)
                        <div class="col-sm-12">
                            <div class="small-box bg-yellow">
                                <div class="inner">
                                    <h3 class="no-margin">@lang('admin/servers_view.index.suspended')</h3>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if($server->installed !== 1)
                        <div class="col-sm-12">
                            <div class="small-box {{ (! $server->installed) ? 'bg-blue' : 'bg-maroon' }}">
                                <div class="inner">
                                    <h3 class="no-margin">{{ (! $server->installed) ? '@lang('admin/servers_view.index.installing')' : '@lang('admin/servers_view.index.install_failed')' }}</h3>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="col-sm-12">
                        <div class="small-box bg-gray">
                            <div class="inner">
                                <h3>{{ str_limit($server->user->username, 16) }}</h3>
                                <p>@lang('admin/servers_view.index.server_owner')</p>
                            </div>
                            <div class="icon"><i class="fa fa-user"></i></div>
                            <a href="{{ route('admin.users.view', $server->user->id) }}" class="small-box-footer">
                                @lang('admin/servers_view.index.more_info') <i class="fa fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="small-box bg-gray">
                            <div class="inner">
                                <h3>{{ str_limit($server->node->name, 16) }}</h3>
                                <p>@lang('admin/servers_view.index.server_node')</p>
                            </div>
                            <div class="icon"><i class="fa fa-codepen"></i></div>
                            <a href="{{ route('admin.nodes.view', $server->node->id) }}" class="small-box-footer">
                                @lang('admin/servers_view.index.more_info') <i class="fa fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
