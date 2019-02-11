{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    {{ $node->name }}: @lang('admin/nodes_view.content.servers')
@endsection

@section('content-header')
    <h1>{{ $node->name }}@lang('admin/nodes_view.servers.header.overview')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/nodes_view.header.admin')</a></li>
        <li><a href="{{ route('admin.nodes') }}">@lang('admin/nodes_view.header.nodes')</a></li>
        <li><a href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></li>
        <li class="active">@lang('admin/nodes_view.content.servers')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.nodes.view', $node->id) }}">@lang('admin/nodes_view.content.about')</a></li>
                <li><a href="{{ route('admin.nodes.view.settings', $node->id) }}">@lang('admin/nodes_view.content.settings')</a></li>
                <li><a href="{{ route('admin.nodes.view.configuration', $node->id) }}">@lang('admin/nodes_view.content.configuration')</a></li>
                <li><a href="{{ route('admin.nodes.view.allocation', $node->id) }}">@lang('admin/nodes_view.content.allocation')</a></li>
                <li class="active"><a href="{{ route('admin.nodes.view.servers', $node->id) }}">@lang('admin/nodes_view.content.servers')</a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/nodes_view.servers.content.servers')</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tr>
                        <th>@lang('admin/nodes_view.servers.content.id')</th>
                        <th>@lang('admin/nodes_view.servers.content.name')</th>
                        <th>@lang('admin/nodes_view.servers.content.owner')</th>
                        <th>@lang('admin/nodes_view.servers.content.service')</th>
                        <th class="text-center">@lang('admin/nodes_view.servers.content.memory')</th>
                        <th class="text-center">@lang('admin/nodes_view.servers.content.disk')</th>
                        <th class="text-center">@lang('admin/nodes_view.servers.content.cpu')</th>
                        <th class="text-center">@lang('admin/nodes_view.servers.content.status')</th>
                    </tr>
                    @foreach($node->servers as $server)
                        <tr data-server="{{ $server->uuid }}">
                            <td><code>{{ $server->uuidShort }}</code></td>
                            <td><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></td>
                            <td><a href="{{ route('admin.users.view', $server->owner_id) }}">{{ $server->user->username }}</a></td>
                            <td>{{ $server->nest->name }} ({{ $server->egg->name }})</td>
                            <td class="text-center"><span data-action="memory">NaN</span> / {{ $server->memory === 0 ? '∞' : $server->memory }} MB</td>
                            <td class="text-center">{{ $server->disk }} MB</td>
                            <td class="text-center"><span data-action="cpu" data-cpumax="{{ $server->cpu }}">NaN</span> %</td>
                            <td class="text-center" data-action="status">NaN</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/admin/node/view-servers.js') !!}
@endsection
