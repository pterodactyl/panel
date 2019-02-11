{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/servers_view.header.server') — {{ $server->name }}: @lang('admin/servers_view.content.manage')
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>@lang('admin/servers_view.manage.header.overview')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/servers_view.header.admin')</a></li>
        <li><a href="{{ route('admin.servers') }}">@lang('admin/servers_view.header.servers')</a></li>
        <li><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
        <li class="active">@lang('admin/servers_view.content.manage')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.servers.view', $server->id) }}">About</a></li>
                @if($server->installed === 1)
                    <li><a href="{{ route('admin.servers.view.details', $server->id) }}">@lang('admin/servers_view.content.details')</a></li>
                    <li><a href="{{ route('admin.servers.view.build', $server->id) }}">@lang('admin/servers_view.header.build_config')</a></li>
                    <li><a href="{{ route('admin.servers.view.startup', $server->id) }}">@lang('admin/servers_view.content.startup')</a></li>
                    <li><a href="{{ route('admin.servers.view.database', $server->id) }}">@lang('admin/servers_view.content.database')</a></li>
                    <li class="active"><a href="{{ route('admin.servers.view.manage', $server->id) }}">@lang('admin/servers_view.content.manage')</a></li>
                @endif
                <li class="tab-danger"><a href="{{ route('admin.servers.view.delete', $server->id) }}">@lang('admin/servers_view.content.delete')</a></li>
                <li class="tab-success"><a href="{{ route('server.index', $server->uuidShort) }}"><i class="fa fa-external-link"></i></a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/servers_view.manage.content.reinstall')</h3>
            </div>
            <div class="box-body">
                <p>@lang('admin/servers_view.manage.content.reinstall_hint')</p>
            </div>
            <div class="box-footer">
                @if($server->installed === 1)
                    <form action="{{ route('admin.servers.view.manage.reinstall', $server->id) }}" method="POST">
                        {!! csrf_field() !!}
                        <button type="submit" class="btn btn-danger">@lang('admin/servers_view.manage.content.reinstall')</button>
                    </form>
                @else
                    <button class="btn btn-danger disabled">@lang('admin/servers_view.manage.content.install_properly')</button>
                @endif
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/servers_view.manage.content.status')</h3>
            </div>
            <div class="box-body">
                <p>@lang('admin/servers_view.manage.content.status_hint')</p>
            </div>
            <div class="box-footer">
                <form action="{{ route('admin.servers.view.manage.toggle', $server->id) }}" method="POST">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-primary">@lang('admin/servers_view.manage.content.toggle_status')</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/servers_view.manage.content.rebuild')</h3>
            </div>
            <div class="box-body">
                <p>@lang('admin/servers_view.manage.content.rebuild_hint')</p>
            </div>
            <div class="box-footer">
                <form action="{{ route('admin.servers.view.manage.rebuild', $server->id) }}" method="POST">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-default">@lang('admin/servers_view.manage.content.rebuild_server')</button>
                </form>
            </div>
        </div>
    </div>
    @if(! $server->suspended)
        <div class="col-sm-4">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers_view.manage.content.suspend')</h3>
                </div>
                <div class="box-body">
                    <p>@lang('admin/servers_view.manage.content.suspend_hint')</p>
                </div>
                <div class="box-footer">
                    <form action="{{ route('admin.servers.view.manage.suspension', $server->id) }}" method="POST">
                        {!! csrf_field() !!}
                        <input type="hidden" name="action" value="suspend" />
                        <button type="submit" class="btn btn-warning">@lang('admin/servers_view.manage.content.suspend')</button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="col-sm-4">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers_view.manage.content.unsuspend')</h3>
                </div>
                <div class="box-body">
                    <p>@lang('admin/servers_view.manage.content.unsuspend_hint')</p>
                </div>
                <div class="box-footer">
                    <form action="{{ route('admin.servers.view.manage.suspension', $server->id) }}" method="POST">
                        {!! csrf_field() !!}
                        <input type="hidden" name="action" value="unsuspend" />
                        <button type="submit" class="btn btn-success">@lang('admin/servers_view.manage.content.unsuspend')</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
