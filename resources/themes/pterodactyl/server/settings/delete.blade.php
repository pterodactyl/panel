{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    @lang('server.config.delete.header')
@endsection

@section('content-header')
    <h1>@lang('server.config.delete.header')<small>@lang('server.config.delete.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li>@lang('navigation.server.configuration')</li>
        <li class="active">@lang('navigation.server.delete')</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <form action="{{ route('server.settings.delete.submit', $server->uuidShort) }}" method="POST">
                <div class="box">
                    <div class="box-body">
                        <p>@lang('server.config.delete.details')</p>
                    </div>
                    <div class="box-footer">
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-sm btn-danger pull-right" value="@lang('strings.submit')" />
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
@endsection
