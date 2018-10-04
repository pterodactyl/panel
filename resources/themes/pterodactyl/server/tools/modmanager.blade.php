{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    @lang('server.tools.modmanager.header')
@endsection

@section('content-header')
    <h1>@lang('server.tools.modmanager.header')<small>@lang('server.tools.modmanager.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li>@lang('navigation.server.tools')</li>
        <li class="active">@lang('navigation.tools.modmanager')</li>
    </ol>
@endsection

@section('content')

    <h1>@lang('development.construction')</h1>

@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
@endsection
