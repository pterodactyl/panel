{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/servers_view.header.server') — {{ $server->name }}: @lang('admin/servers_view.content.delete')
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>@lang('admin/servers_view.delete.header.overview')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/servers_view.header.admin')</a></li>
        <li><a href="{{ route('admin.servers') }}">@lang('admin/servers_view.header.servers')</a></li>
        <li><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
        <li class="active">@lang('admin/servers_view.content.delete')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.servers.view', $server->id) }}">@lang('admin/servers_view.content.about')</a></li>
                @if($server->installed === 1)
                    <li><a href="{{ route('admin.servers.view.details', $server->id) }}">@lang('admin/servers_view.content.details')</a></li>
                    <li><a href="{{ route('admin.servers.view.build', $server->id) }}">@lang('admin/servers_view.header.build_config')</a></li>
                    <li><a href="{{ route('admin.servers.view.startup', $server->id) }}">@lang('admin/servers_view.content.startup')</a></li>
                    <li><a href="{{ route('admin.servers.view.database', $server->id) }}">@lang('admin/servers_view.content.database')</a></li>
                    <li><a href="{{ route('admin.servers.view.manage', $server->id) }}">@lang('admin/servers_view.content.manage')</a></li>
                @endif
                <li class="tab-danger active"><a href="{{ route('admin.servers.view.delete', $server->id) }}">@lang('admin/servers_view.content.delete')</a></li>
                <li class="tab-success"><a href="{{ route('server.index', $server->uuidShort) }}"><i class="fa fa-external-link"></i></a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/servers_view.delete.content.safe_del')</h3>
            </div>
            <div class="box-body">
                <p>@lang('admin/servers_view.delete.content.safe_del_hint')</p>
            </div>
            <div class="box-footer">
                <form action="{{ route('admin.servers.view.delete', $server->id) }}" method="POST">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-danger">@lang('admin/servers_view.delete.content.safe_del_confirm')</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/servers_view.delete.content.force_del')</h3>
            </div>
            <div class="box-body">
                <p>@lang('admin/servers_view.delete.content.force_del_hint')</p>
            </div>
            <div class="box-footer">
                <form action="{{ route('admin.servers.view.delete', $server->id) }}" method="POST">
                    {!! csrf_field() !!}
                    <input type="hidden" name="force_delete" value="1" />
                    <button type="submit" class="btn btn-danger">@lang('admin/servers_view.delete.content.force_del_confirm')</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('form[data-action="delete"]').submit(function (event) {
        event.preventDefault();
        swal({
            title: '',
            type: 'warning',
            text: '@lang('admin/servers_view.content.delete')',
            showCancelButton: true,
            confirmButtonText: '@lang('admin/servers_view.delete.content.warning')',
            confirmButtonColor: '#d9534f',
            closeOnConfirm: false
        }, function () {
            event.target.submit();
        });
    });
    </script>
@endsection
