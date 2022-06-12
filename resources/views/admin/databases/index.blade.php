{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    数据库主机
@endsection

@section('content-header')
    <h1>数据库主机<small>服务器可以在其上创建数据库的数据库主机。</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li class="active">数据库主机</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">主机列表</h3>
                <div class="box-tools">
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newHostModal">新建</button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>ID</th>
                            <th>名称</th>
                            <th>地址</th>
                            <th>端口</th>
                            <th>用户名</th>
                            <th class="text-center">数据库</th>
                            <th class="text-center">节点</th>
                        </tr>
                        @foreach ($hosts as $host)
                            <tr>
                                <td><code>{{ $host->id }}</code></td>
                                <td><a href="{{ route('admin.databases.view', $host->id) }}">{{ $host->name }}</a></td>
                                <td><code>{{ $host->host }}</code></td>
                                <td><code>{{ $host->port }}</code></td>
                                <td>{{ $host->username }}</td>
                                <td class="text-center">{{ $host->databases_count }}</td>
                                <td class="text-center">
                                    @if(! is_null($host->node))
                                        <a href="{{ route('admin.nodes.view', $host->node->id) }}">{{ $host->node->name }}</a>
                                    @else
                                        <span class="label label-default">无</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="newHostModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.databases') }}" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">新建数据库主机</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="pName" class="form-label">名称</label>
                        <input type="text" name="name" id="pName" class="form-control" />
                        <p class="text-muted small">用于将此主机与其他主机区分开来的简短标识符。 必须介于 1 到 60 个字符之间，例如, <code>us.nyc.lvl3</code>.</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="pHost" class="form-label">地址</label>
                            <input type="text" name="host" id="pHost" class="form-control" />
                            <p class="text-muted small">尝试连接到此 MySQL 主机时应使用的 IP 地址或域名.</p>
                        </div>
                        <div class="col-md-6">
                            <label for="pPort" class="form-label">端口</label>
                            <input type="text" name="port" id="pPort" class="form-control" value="3306"/>
                            <p class="text-muted small">MYSQL 主机运行开放的端口.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="pUsername" class="form-label">用户名</label>
                            <input type="text" name="username" id="pUsername" class="form-control" />
                            <p class="text-muted small">具有足够权限在系统上创建新用户和数据库的帐户的用户名.</p>
                        </div>
                        <div class="col-md-6">
                            <label for="pPassword" class="form-label">密码</label>
                            <input type="password" name="password" id="pPassword" class="form-control" />
                            <p class="text-muted small">上方阁下填写的账户的密码.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="pNodeId" class="form-label">关联的节点服务器</label>
                        <select name="node_id" id="pNodeId" class="form-control">
                            <option value="">无</option>
                            @foreach($locations as $location)
                                <optgroup label="{{ $location->short }}">
                                    @foreach($location->nodes as $node)
                                        <option value="{{ $node->id }}">{{ $node->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="text-muted small">此设置除了将数据库默认添加到所选节点上的服务器以外没有任何作用.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <p class="text-danger small text-left">连接此数据库主机所使用的账户 <strong>必须</strong> 具有 <code>WITH GRANT OPTION</code> 权限. 如果账户没有此权限将 <em>无法</em> 成功建立数据库. <strong>不要为 MySQL 使用您为此面板使用的相同帐户详细信息.</strong></p>
                    {!! csrf_field() !!}
                    <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-success btn-sm">创建</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#pNodeId').select2();
    </script>
@endsection
