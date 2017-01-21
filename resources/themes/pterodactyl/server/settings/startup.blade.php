{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}

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
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('server.config.startup.command')</h3>
            </div>
            <div class="box-body">
                <div class="input-group">
                    <span class="input-group-addon">{{ $service->executable }}</span>
                    <input type="text" class="form-control" readonly="readonly" value="{{ $processedStartup }}" />
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('server.config.startup.edit_params')</h3>
            </div>
            @can('edit-startup', $server)
                <form action="{{ route('server.settings.startup', $server->uuidShort) }}" method="POST">
                    <div class="box-body">
                        @foreach($variables as $item)
                            <div class="form-group">
                                <label class="control-label">
                                    @if($item->required === 1)<span class="label label-danger">@lang('strings.required')</span> @endif
                                    {{ $item->name }}
                                </label>
                                <div>
                                    <input type="text"
                                        @if($item->user_editable === 1)
                                            name="{{ $item->env_variable }}"
                                        @else
                                            readonly="readonly"
                                        @endif
                                    class="form-control" value="{{ old($item->env_variable, $item->a_serverValue) }}" data-action="matchRegex" data-regex="{{ $item->regex }}" />
                                </div>
                                <p class="text-muted"><small>{!! $item->description !!}</small></p>
                            </div>
                        @endforeach
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <input type="submit" class="btn btn-primary btn-sm" value="@lang('server.config.startup.update')" />
                    </div>
                </form>
            @else
                <div class="box-body">
                    <div class="callout callout-warning callout-nomargin">
                        <p>@lang('auth.not_authorized')</p>
                    </div>
                </div>
            @endcan
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
@endsection
