{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    数据库主机 &rarr; 详细信息 &rarr; {{ $host->name }}
@endsection

@section('content-header')
    <h1>{{ $host->name }}<small>查看此数据库主机的关联数据库和详细信息.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li><a href="{{ route('admin.databases') }}">数据库主机</a></li>
        <li class="active">{{ $host->name }}</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.databases.view', $host->id) }}" method="POST">
    <div class="row">
        <div class="col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">主机详情</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pName" class="form-label">名称</label>
                        <input type="text" id="pName" name="name" class="form-control" value="{{ old('name', $host->name) }}" />
                    </div>
                    <div class="form-group">
                        <label for="pHost" class="form-label">地址</label>
                        <input type="text" id="pHost" name="host" class="form-control" value="{{ old('host', $host->host) }}" />
                        <p class="text-muted small">尝试连接到此 MySQL 主机时应使用的 IP 地址或域名.</p>
                    </div>
                    <div class="form-group">
                        <label for="pPort" class="form-label">端口</label>
                        <input type="text" id="pPort" name="port" class="form-control" value="{{ old('port', $host->port) }}" />
                        <p class="text-muted small">MYSQL 主机运行开放的端口.</p>
                    </div>
                    <div class="form-group">
                        <label for="pNodeId" class="form-label">关联的节点服务器</label>
                        <select name="node_id" id="pNodeId" class="form-control">
                            <option value="">无</option>
                            @foreach($locations as $location)
                                <optgroup label="{{ $location->short }}">
                                    @foreach($location->nodes as $node)
                                        <option value="{{ $node->id }}" {{ $host->node_id !== $node->id ?: 'selected' }}>{{ $node->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="text-muted small">此设置除了将数据库默认添加到所选节点上的服务器以外没有任何作用.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">用户详情</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pUsername" class="form-label">用户名</label>
                        <input type="text" name="username" id="pUsername" class="form-control" value="{{ old('username', $host->username) }}" />
                        <p class="text-muted small">具有足够权限在系统上创建新用户和数据库的帐户的用户名.</p>
                    </div>
                    <div class="form-group">
                        <label for="pPassword" class="form-label">密码</label>
                        <input type="password" name="password" id="pPassword" class="form-control" />
                        <p class="text-muted small">已定义帐户的密码。 留空以继续使用分配的密码.</p>
                    </div>
                    <hr />
                    <p class="text-danger small text-left">连接此数据库主机所使用的账户 <strong>必须</strong> 具有 <code>WITH GRANT OPTION</code> 权限. 如果账户没有此权限将 <em>无法</em> 成功建立数据库. <strong>不要为 MySQL 使用您为此面板使用的相同帐户详细信息.</strong></p>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">保存</button>
                    <button name="_method" value="DELETE" class="btn btn-sm btn-danger pull-left muted muted-hover"><i class="fa fa-trash-o"></i></button>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">数据库</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tr>
                        <th>服务器</th>
                        <th>数据库名</th>
                        <th>用户名</th>
                        <th>连接白名单</th>
                        <th>最大连接数</th>
                        <th></th>
                    </tr>
                    @foreach($databases as $database)
                        <tr>
                            <td class="middle"><a href="{{ route('admin.servers.view', $database->getRelation('server')->id) }}">{{ $database->getRelation('server')->name }}</a></td>
                            <td class="middle">{{ $database->database }}</td>
                            <td class="middle">{{ $database->username }}</td>
                            <td class="middle">{{ $database->remote }}</td>
                            @if($database->max_connections != null)
                                <td class="middle">{{ $database->max_connections }}</td>
                            @else
                                <td class="middle">无限制</td>
                            @endif
                            <td class="text-center">
                                <a href="{{ route('admin.servers.view.database', $database->getRelation('server')->id) }}">
                                    <button class="btn btn-xs btn-primary">管理</button>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            @if($databases->hasPages())
                <div class="box-footer with-border">
                    <div class="col-md-12 text-center">{!! $databases->render() !!}</div>
                </div>
            @endif
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
