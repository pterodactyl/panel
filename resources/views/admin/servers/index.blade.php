{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    服务器实例列表
@endsection

@section('content-header')
    <h1>服务器实例列表<small>此系统上所有的可用服务器实例.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li class="active">服务器实例</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">服务器实例列表</h3>
                <div class="box-tools search01">
                    <form action="{{ route('admin.servers') }}" method="GET">
                        <div class="input-group input-group-sm">
                            <input type="text" name="filter[*]" class="form-control pull-right" value="{{ request()->input()['filter']['*'] ?? '' }}" placeholder="Search Servers">
                            <div class="input-group-btn">
                                <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                <a href="{{ route('admin.servers.new') }}"><button type="button" class="btn btn-sm btn-primary" style="border-radius: 0 3px 3px 0;margin-left:-1px;">新建</button></a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>名称</th>
                            <th>UUID</th>
                            <th>所有者</th>
                            <th>所属节点服务器</th>
                            <th>连接</th>
                            <th></th>
                            <th></th>
                        </tr>
                        @foreach ($servers as $server)
                            <tr data-server="{{ $server->uuidShort }}">
                                <td><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></td>
                                <td><code title="{{ $server->uuid }}">{{ $server->uuid }}</code></td>
                                <td><a href="{{ route('admin.users.view', $server->user->id) }}">{{ $server->user->username }}</a></td>
                                <td><a href="{{ route('admin.nodes.view', $server->node->id) }}">{{ $server->node->name }}</a></td>
                                <td>
                                    <code>{{ $server->allocation->alias }}:{{ $server->allocation->port }}</code>
                                </td>
                                <td class="text-center">
                                    @if($server->isSuspended())
                                        <span class="label bg-maroon">冻结中</span>
                                    @elseif(! $server->isInstalled())
                                        <span class="label label-warning">安装中</span>
                                    @else
                                        <span class="label label-success">正常</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-xs btn-default" href="/server/{{ $server->uuidShort }}"><i class="fa fa-wrench"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($servers->hasPages())
                <div class="box-footer with-border">
                    <div class="col-md-12 text-center">{!! $servers->appends(['filter' => Request::input('filter')])->render() !!}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('.console-popout').on('click', function (event) {
            event.preventDefault();
            window.open($(this).attr('href'), 'Pterodactyl Console', 'width=800,height=400');
        });
    </script>
@endsection
