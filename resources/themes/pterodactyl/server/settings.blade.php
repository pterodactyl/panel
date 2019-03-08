{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    @lang('server.config.name.header')
@endsection

@section('content-header')
    <h1>@lang('server.config.settings.header')<small>@lang('server.config.settings.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li>@lang('navigation.server.configuration')</li>
        <li class="active">@lang('navigation.server.server_settings')</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <form action="{{ route('server.settings', $server->uuidShort) }}" method="POST">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('server.config.settings.name.header')</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group no-margin-bottom">
                            <div>
                                <input type="text" name="name" id="pServerName" class="form-control" value="{{ $server->name }}" />
                                <p class="small text-muted no-margin-bottom">@lang('server.config.settings.name.details')</p>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {{ method_field('PATCH') }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-sm btn-primary pull-right" value="@lang('strings.submit')" />
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-4">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('server.config.settings.reinstall.header')</h3>
                </div>
                <div class="box-body">
                    <p>@lang('server.config.settings.reinstall.details')</p>
                </div>
                <div class="box-footer">
                    @if($server->installed === 1)
                    <form action="{{ route('server.settings.reinstall', $server->uuidShort) }}" method="POST">
                        {{ method_field('PATCH') }}
                        {{ csrf_field() }}
                        <button type="submit" class="btn btn-danger">@lang('server.config.settings.reinstall.header')</button>
                    </form>
                    @else
                    <button class="btn btn-danger disabled">@lang('server.config.settings.reinstall.properly')</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
@endsection
