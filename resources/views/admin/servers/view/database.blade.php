{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    服务器实例 — {{ $server->name }}: 数据库
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>管理服务器实例数据库.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li><a href="{{ route('admin.servers') }}">服务器实例</a></li>
        <li><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
        <li class="active">数据库</li>
    </ol>
@endsection

@section('content')
@include('admin.servers.partials.navigation')
<div class="row">
    <div class="col-sm-7">
        <div class="alert alert-info">
            数据库密码可以在 <a href="/server/{{ $server->uuidShort }}/databases">访问此服务器</a> 用户界面时查看.
        </div>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">活跃的数据库</h3>
            </div>
            <div class="box-body table-responsible no-padding">
                <table class="table table-hover">
                    <tr>
                        <th>数据库</th>
                        <th>用户名</th>
                        <th>连接白名单</th>
                        <th>域名</th>
                        <th>最大连接数</th>
                        <th></th>
                    </tr>
                    @foreach($server->databases as $database)
                        <tr>
                            <td>{{ $database->database }}</td>
                            <td>{{ $database->username }}</td>
                            <td>{{ $database->remote }}</td>
                            <td><code>{{ $database->host->host }}:{{ $database->host->port }}</code></td>
                            @if($database->max_connections != null)
                                <td>{{ $database->max_connections }}</td>
                            @else
                                <td>Unlimited</td>
                            @endif
                            <td class="text-center">
                                <button data-action="reset-password" data-id="{{ $database->id }}" class="btn btn-xs btn-primary"><i class="fa fa-refresh"></i></button>
                                <button data-action="remove" data-id="{{ $database->id }}" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">创建新数据库</h3>
            </div>
            <form action="{{ route('admin.servers.view.database', $server->id) }}" method="POST">
                <div class="box-body">
                    <div class="form-group">
                        <label for="pDatabaseHostId" class="control-label">数据库主机</label>
                        <select id="pDatabaseHostId" name="database_host_id" class="form-control">
                            @foreach($hosts as $host)
                                <option value="{{ $host->id }}">{{ $host->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-muted small">选择应在其上创建此数据库的主机数据库服务器.</p>
                    </div>
                    <div class="form-group">
                        <label for="pDatabaseName" class="control-label">数据库</label>
                        <div class="input-group">
                            <span class="input-group-addon">s{{ $server->id }}_</span>
                            <input id="pDatabaseName" type="text" name="database" class="form-control" placeholder="database" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="pRemote" class="control-label">连接白名单</label>
                        <input id="pRemote" type="text" name="remote" class="form-control" value="%" />
                        <p class="text-muted small">这应该反映允许连接的 IP 地址。 使用标准 MySQL 表示法。 如果不确定请填写 <code>%</code>。</p>
                    </div>
                    <div class="form-group">
                        <label for="pmax_connections" class="control-label">最大连接数</label>
                        <input id="pmax_connections" type="text" name="max_connections" class="form-control"/>
                        <p class="text-muted small">这应该反映从该用户到数据库的最大并发连接数。 留空表示无限制。</p>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <p class="text-muted small no-margin">该数据库的用户名和密码将在此表单提交后随机生成。</p>
                    <input type="submit" class="btn btn-sm btn-success pull-right" value="Create Database" />
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('#pDatabaseHost').select2();
    $('[data-action="remove"]').click(function (event) {
        event.preventDefault();
        var self = $(this);
        swal({
            title: '',
            type: 'warning',
            text: '您确定要删除此数据库吗？ 没有回头路，所有数据将立即被删除。',
            showCancelButton: true,
            confirmButtonText: '删除',
            confirmButtonColor: '#d9534f',
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
        }, function () {
            $.ajax({
                method: 'DELETE',
                url: '/admin/servers/view/{{ $server->id }}/database/' + self.data('id') + '/delete',
                headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            }).done(function () {
                self.parent().parent().slideUp();
                swal.close();
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    text: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : '处理此请求时发生错误，此请求无法继续处理！'
                });
            });
        });
    });
    $('[data-action="reset-password"]').click(function (e) {
        e.preventDefault();
        var block = $(this);
        $(this).addClass('disabled').find('i').addClass('fa-spin');
        $.ajax({
            type: 'PATCH',
            url: '/admin/servers/view/{{ $server->id }}/database',
            headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            data: { database: $(this).data('id') },
        }).done(function (data) {
            swal({
                type: 'success',
                title: '',
                text: '数据库密码已重置.',
            });
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error(jqXHR);
            var error = '处理此请求时发生错误，此请求无法继续处理！';
            if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                error = jqXHR.responseJSON.error;
            }
            swal({
                type: 'error',
                title: 'Whoops!',
                text: error
            });
        }).always(function () {
            block.removeClass('disabled').find('i').removeClass('fa-spin');
        });
    });
    </script>
@endsection
