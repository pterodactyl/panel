{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    @lang('server.config.startup.header')
@endsection

@section('content-header')
    <h1>@lang('server.config.startup.header')<small>@lang('server.config.startup.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li>@lang('navigation.server.configuration')</li>
        <li class="active">@lang('navigation.server.startup_parameters')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <form action="{{ route('server.settings.startup', $server->uuidShort) }}" method="POST">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('server.config.startup.command')</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <input type="text" class="form-control" readonly value="{{ $processedStartup }}" />
                    </div>
                </div>
                @can('edit-startup', $server)
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <input type="submit" class="btn btn-primary btn-sm pull-right" value="@lang('server.config.startup.update')" />
                    </div>
                @endcan
            </div>
        </div>
        @can('edit-startup', $server)
            @foreach($variables as $v)
                <div class="col-xs-12 col-md-4 col-sm-6">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">{{ $v->name }}</h3>
                        </div>
                        <div class="box-body">
                            <input
                                @if($v->user_editable)
                                    name="env_{{ $v->id }}"
                                @else
                                    readonly
                                @endif
                            class="form-control" type="text" value="{{ old('env_' . $v->id, $v->server_set_value) }}" />
                            <p class="small text-muted">{{ $v->description }}</p>
                            <p class="no-margin">
                                @if($v->required && $v->user_editable )
                                    <span class="label label-danger">@lang('strings.required')</span>
                                @elseif(! $v->required && $v->user_editable)
                                    <span class="label label-default">@lang('strings.optional')</span>
                                @endif
                                @if(! $v->user_editable)
                                    <span class="label label-warning">@lang('strings.read_only')</span>
                                @endif
                            </p>
                        </div>
                        <div class="box-footer">
                            <p class="no-margin text-muted small"><strong>@lang('server.config.startup.startup_var'):</strong> <code>{{ $v->env_variable }}</code></p>
                            <p class="no-margin text-muted small"><strong>@lang('server.config.startup.startup_regex'):</strong> <code>{{ $v->rules }}</code></p>
                        </div>
                    </div>
                </div>
            @endforeach
        @endcan
    </form>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
@endsection
