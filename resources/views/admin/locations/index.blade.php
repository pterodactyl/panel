{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    节点服务器组
@endsection

@section('content-header')
    <h1>节点服务器组<small>将节点服务器分组管理</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li class="active">节点服务器组</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">节点服务器组列表</h3>
                <div class="box-tools">
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newLocationModal">新建</button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>ID</th>
                            <th>标识码</th>
                            <th>描述</th>
                            <th class="text-center">节点服务器</th>
                            <th class="text-center">服务器实例</th>
                        </tr>
                        @foreach ($locations as $location)
                            <tr>
                                <td><code>{{ $location->id }}</code></td>
                                <td><a href="{{ route('admin.locations.view', $location->id) }}">{{ $location->short }}</a></td>
                                <td>{{ $location->long }}</td>
                                <td class="text-center">{{ $location->nodes_count }}</td>
                                <td class="text-center">{{ $location->servers_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="newLocationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.locations') }}" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">创建节点服务器组</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="pShortModal" class="form-label">标识码</label>
                            <input type="text" name="short" id="pShortModal" class="form-control" />
                            <p class="text-muted small">用于将此节点服务器组与其他节点服务器区分开来的简短标识符。 必须介于 1 到 60 个字符之间, 例如, <code>us.nyc.lvl3</code>.</p>
                        </div>
                        <div class="col-md-12">
                            <label for="pLongModal" class="form-label">描述</label>
                            <textarea name="long" id="pLongModal" class="form-control" rows="4"></textarea>
                            <p class="text-muted small">此节点服务器组的详细说明，最多不应超过 191 个字符.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    {!! csrf_field() !!}
                    <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-success btn-sm">创建</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
