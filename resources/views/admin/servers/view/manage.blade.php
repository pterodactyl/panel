{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    服务器实例 — {{ $server->name }}: 管理
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>控制此服务器的其他操作.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li><a href="{{ route('admin.servers') }}">服务器实例</a></li>
        <li><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
        <li class="active">管理</li>
    </ol>
@endsection

@section('content')
    @include('admin.servers.partials.navigation')
    <div class="row">
        <div class="col-sm-4">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">重新安装服务器实例</h3>
                </div>
                <div class="box-body">
                    <p>此操作将使用预设的安装程序对服务器初始化。 <strong>微信!</strong> 这会覆盖一部分数据.</p>
                </div>
                <div class="box-footer">
                    @if($server->isInstalled())
                        <form action="{{ route('admin.servers.view.manage.reinstall', $server->id) }}" method="POST">
                            {!! csrf_field() !!}
                            <button type="submit" class="btn btn-danger">重新安装服务器实例</button>
                        </form>
                    @else
                        <button class="btn btn-danger disabled">服务器实例必须正常安装之后才可重新安装</button>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">安装状态</h3>
                </div>
                <div class="box-body">
                    <p>如果您需要将安装状态从已卸载更改为已安装，反之亦然，您可以使用下面的按钮进行操作。</p>
                </div>
                <div class="box-footer">
                    <form action="{{ route('admin.servers.view.manage.toggle', $server->id) }}" method="POST">
                        {!! csrf_field() !!}
                        <button type="submit" class="btn btn-primary">更改安装状态</button>
                    </form>
                </div>
            </div>
        </div>

        @if(! $server->isSuspended())
            <div class="col-sm-4">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">冻结服务器实例</h3>
                    </div>
                    <div class="box-body">
                        <p>这将暂停服务器实例，停止任何正在运行的进程，并立即阻止用户访问他们的文件或通过面板或 API 管理服务器实例.</p>
                    </div>
                    <div class="box-footer">
                        <form action="{{ route('admin.servers.view.manage.suspension', $server->id) }}" method="POST">
                            {!! csrf_field() !!}
                            <input type="hidden" name="action" value="suspend" />
                            <button type="submit" class="btn btn-warning @if(! is_null($server->transfer)) disabled @endif">冻结服务器实例</button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <div class="col-sm-4">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">撤销冻结服务器实例</h3>
                    </div>
                    <div class="box-body">
                        <p>这将撤销服务器实例的冻结，且用户将有权限管理此服务器实例.</p>
                    </div>
                    <div class="box-footer">
                        <form action="{{ route('admin.servers.view.manage.suspension', $server->id) }}" method="POST">
                            {!! csrf_field() !!}
                            <input type="hidden" name="action" value="unsuspend" />
                            <button type="submit" class="btn btn-success">撤销冻结服务器实例</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if(is_null($server->transfer))
            <div class="col-sm-4">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">转移服务器实例</h3>
                    </div>
                    <div class="box-body">
                        <p>
                            将此服务器实例转移到连接到此面板的另一个节点服务器.
                            <strong>警告!</strong> 此功能未完全测试可能有BUG.
                        </p>
                    </div>

                    <div class="box-footer">
                        @if($canTransfer)
                            <button class="btn btn-success" data-toggle="modal" data-target="#transferServerModal">Transfer Server</button>
                        @else
                            <button class="btn btn-success disabled">转移服务器实例</button>
                            <p style="padding-top: 1rem;">转移一台服务器实例需要在您的面板上配置多个节点服务器。</p>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="col-sm-4">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">转移服务器实例</h3>
                    </div>
                    <div class="box-body">
                        <p>
                            此服务器实例正在转移至另一个节点服务器.
                            转移开始于 <strong>{{ $server->transfer->created_at }}</strong>
                        </p>
                    </div>

                    <div class="box-footer">
                        <button class="btn btn-success disabled">转移服务器实例</button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="modal fade" id="transferServerModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.servers.view.manage.transfer', $server->id) }}" method="POST">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">转移服务器实例</h4>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="pNodeId">节点服务器</label>
                                <select name="node_id" id="pNodeId" class="form-control">
                                    @foreach($locations as $location)
                                        <optgroup label="{{ $location->long }} ({{ $location->short }})">
                                            @foreach($location->nodes as $node)

                                                @if($node->id != $server->node_id)
                                                    <option value="{{ $node->id }}"
                                                            @if($location->id === old('location_id')) selected @endif
                                                    >{{ $node->name }}</option>
                                                @endif

                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <p class="small text-muted no-margin">该服务器实例将被转移到的节点服务器.</p>
                            </div>

                            <div class="form-group col-md-12">
                                <label for="pAllocation">默认分配</label>
                                <select name="allocation_id" id="pAllocation" class="form-control"></select>
                                <p class="small text-muted no-margin">将分配给此服务器实例的主要分配。</p>
                            </div>

                            <div class="form-group col-md-12">
                                <label for="pAllocationAdditional">额外分配</label>
                                <select name="allocation_additional[]" id="pAllocationAdditional" class="form-control" multiple></select>
                                <p class="small text-muted no-margin">将分配给此服务器实例的额外分配。</p>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        {!! csrf_field() !!}
                        <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-success btn-sm">确定</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/lodash/lodash.js') !!}

    @if($canTransfer)
        {!! Theme::js('js/admin/server/transfer.js') !!}
    @endif
@endsection
