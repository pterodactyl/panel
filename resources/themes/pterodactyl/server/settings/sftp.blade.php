{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    @lang('server.config.sftp.header')
@endsection

@section('content-header')
    <h1>@lang('server.config.sftp.header')<small>@lang('server.config.sftp.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li>@lang('navigation.server.configuration')</li>
        <li class="active">@lang('navigation.server.sftp_settings')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('server.config.sftp.details')</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label class="control-label">@lang('server.config.sftp.conn_addr')</label>
                    <div>
                        <input type="text" class="form-control" readonly value="sftp://{{ $node->fqdn }}:{{ $node->daemonSFTP }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="password" class="control-label">@lang('strings.username')</label>
                    <div>
                        <input type="text" class="form-control" readonly value="{{ auth()->user()->username }}.{{ $server->uuidShort }}" />
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <p class="small text-muted no-margin-bottom">@lang('server.config.sftp.warning')</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
@endsection
