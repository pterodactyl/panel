{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    @lang('base.index.header')
@endsection

@section('content-header')
    <h1>@lang('base.index.header')<small>@lang('base.index.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li class="active">@lang('strings.servers')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">@lang('base.index.list')</h3>
                <div class="box-tools search01">
                    <form action="{{ route('index') }}" method="GET">
                        <div class="input-group input-group-sm">
                            <input type="text" name="query" class="form-control pull-right" value="{{ request()->input('query') }}" placeholder="@lang('strings.search')">
                            <div class="input-group-btn">
                                <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>@lang('strings.id')</th>
                            <th>@lang('strings.name')</th>
                            <th>@lang('strings.node')</th>
                            <th>@lang('strings.connection')</th>
                            <th class="text-center hidden-sm hidden-xs">@lang('strings.memory')</th>
                            <th class="text-center hidden-sm hidden-xs">@lang('strings.cpu')</th>
                            <th class="text-center hidden-sm hidden-xs">@lang('strings.disk')</th>
                            <th class="text-center">@lang('strings.relation')</th>
                            <th class="text-center">@lang('strings.status')</th>
                        </tr>
                        @foreach($servers as $server)
                            <tr class="dynamic-update" data-server="{{ $server->uuidShort }}">
                                <td @if(! empty($server->description)) rowspan="2" @endif><code>{{ $server->uuidShort }}</code></td>
                                <td><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></td>
                                <td>{{ $server->getRelation('node')->name }}</td>
                                <td><code>{{ $server->getRelation('allocation')->alias }}:{{ $server->getRelation('allocation')->port }}</code></td>
                                <td class="text-center hidden-sm hidden-xs"><span data-action="memory">--</span> / {{ $server->memory === 0 ? '∞' : $server->memory }} MB</td>
                                <td class="text-center hidden-sm hidden-xs"><span data-action="cpu" data-cpumax="{{ $server->cpu }}">--</span> %</td>
                                <td class="text-center hidden-sm hidden-xs"><span data-action="disk">--</span> / {{ $server->disk === 0 ? '∞' : $server->disk }} MB </td>
                                <td class="text-center">
                                    @if($server->user->id === Auth::user()->id)
                                        <span class="label bg-purple">@lang('strings.owner')</span>
                                    @elseif(Auth::user()->root_admin)
                                        <span class="label bg-maroon">@lang('strings.admin')</span>
                                    @else
                                        <span class="label bg-blue">@lang('strings.subuser')</span>
                                    @endif
                                </td>
                                @if($server->node->maintenance_mode)
                                    <td class="text-center">
                                        <span class="label label-warning">@lang('strings.under_maintenance')</span>
                                    </td>
                                @else
                                    <td class="text-center" data-action="status">
                                        <span class="label label-default"><i class="fa fa-refresh fa-fw fa-spin"></i></span>
                                    </td>
                                @endif
                            </tr>
                            @if (! empty($server->description))
                                <tr class="server-description">
                                    <td colspan="7"><p class="text-muted small no-margin">{{ str_limit($server->description, 400) }}</p></td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($servers->hasPages())
                <div class="box-footer">
                    <div class="col-md-12 text-center">{!! $servers->appends(['query' => Request::input('query')])->render() !!}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('tr.server-description').on('mouseenter mouseleave', function (event) {
            $(this).prev('tr').css({
                'background-color': (event.type === 'mouseenter') ? '#f5f5f5' : '',
            });
        });
    </script>
    {!! Theme::js('js/frontend/serverlist.js') !!}
@endsection
