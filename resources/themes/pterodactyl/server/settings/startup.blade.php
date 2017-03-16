{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- Permission is hereby granted, free of charge, to any person obtaining a copy --}}
{{-- of this software and associated documentation files (the "Software"), to deal --}}
{{-- in the Software without restriction, including without limitation the rights --}}
{{-- to use, copy, modify, merge, publish, distribute, sublicense, and/or sell --}}
{{-- copies of the Software, and to permit persons to whom the Software is --}}
{{-- furnished to do so, subject to the following conditions: --}}

{{-- The above copyright notice and this permission notice shall be included in all --}}
{{-- copies or substantial portions of the Software. --}}

{{-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR --}}
{{-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, --}}
{{-- FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE --}}
{{-- AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER --}}
{{-- LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, --}}
{{-- OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE --}}
{{-- SOFTWARE. --}}
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
                            <h3 class="box-title">{{ $v->variable->name }}</h3>
                        </div>
                        <div class="box-body">
                            <input
                                @if($v->user_can_edit)
                                    name="env_{{ $v->variable->id }}"
                                @else
                                    readonly
                                @endif
                            class="form-control" type="text" value="{{ old('env_' . $v->id, $v->variable_value) }}" />
                            <p class="small text-muted">{{ $v->variable->description }}</p>
                            <p class="no-margin">
                                @if($v->required && $v->user_can_edit)
                                    <span class="label label-danger">@lang('strings.required')</span>
                                @elseif(! $v->required && $v->user_can_edit)
                                    <span class="label label-default">@lang('strings.optional')</span>
                                @endif
                                @if(! $v->user_can_edit)
                                    <span class="label label-warning">@lang('strings.read_only')</span>
                                @endif
                            </p>
                        </div>
                        <div class="box-footer">
                            <p class="no-margin text-muted small"><strong>@lang('server.config.startup.startup_var'):</strong> <code>{{ $v->variable->env_variable }}</code></p>
                            <p class="no-margin text-muted small"><strong>@lang('server.config.startup.startup_regex'):</strong> <code>{{ $v->variable->rules }}</code></p>
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
