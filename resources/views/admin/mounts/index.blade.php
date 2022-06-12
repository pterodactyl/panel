{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}

@extends('layouts.admin')

@section('title')
    存储挂载
@endsection

@section('content-header')
    <h1>存储挂载<small>配置和管理服务器的附加挂载点。</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li class="active">存储挂载</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">挂载点列表</h3>

                    <div class="box-tools">
                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newMountModal">新建</button>
                    </div>
                </div>

                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>ID</th>
                                <th>名称</th>
                                <th>原始路径</th>
                                <th>挂载路径</th>
                                <th class="text-center">预设</th>
                                <th class="text-center">节点</th>
                                <th class="text-center">服务器</th>
                            </tr>

                            @foreach ($mounts as $mount)
                                <tr>
                                    <td><code>{{ $mount->id }}</code></td>
                                    <td><a href="{{ route('admin.mounts.view', $mount->id) }}">{{ $mount->name }}</a></td>
                                    <td><code>{{ $mount->source }}</code></td>
                                    <td><code>{{ $mount->target }}</code></td>
                                    <td class="text-center">{{ $mount->eggs_count }}</td>
                                    <td class="text-center">{{ $mount->nodes_count }}</td>
                                    <td class="text-center">{{ $mount->servers_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newMountModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.mounts') }}" method="POST">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true" style="color: #FFFFFF">&times;</span>
                        </button>

                        <h4 class="modal-title">创建挂载</h4>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <label for="pName" class="form-label">名称</label>
                                <input type="text" id="pName" name="name" class="form-control" />
                                <p class="text-muted small">用于将此挂载与另一个挂载分开的唯一名称.</p>
                            </div>

                            <div class="col-md-12">
                                <label for="pDescription" class="form-label">描述</label>
                                <textarea id="pDescription" name="description" class="form-control" rows="4"></textarea>
                                <p class="text-muted small">此挂载的详细描述，不应多于 191 个字符.</p>
                            </div>

                            <div class="col-md-6">
                                <label for="pSource" class="form-label">原始路径</label>
                                <input type="text" id="pSource" name="source" class="form-control" />
                                <p class="text-muted small">主机系统上要挂载到容器的文件路径.</p>
                            </div>

                            <div class="col-md-6">
                                <label for="pTarget" class="form-label">挂载路径</label>
                                <input type="text" id="pTarget" name="target" class="form-control" />
                                <p class="text-muted small">将于容器内挂载的可读写文件路径.</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">只读</label>

                                <div>
                                    <div class="radio radio-success radio-inline">
                                        <input type="radio" id="pReadOnlyFalse" name="read_only" value="0" checked>
                                        <label for="pReadOnlyFalse">否</label>
                                    </div>

                                    <div class="radio radio-warning radio-inline">
                                        <input type="radio" id="pReadOnly" name="read_only" value="1">
                                        <label for="pReadOnly">是</label>
                                    </div>
                                </div>

                                <p class="text-muted small">在容器文件系统内此挂载只读?</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">用户可挂载</label>

                                <div>
                                    <div class="radio radio-success radio-inline">
                                        <input type="radio" id="pUserMountableFalse" name="user_mountable" value="0" checked>
                                        <label for="pUserMountableFalse">否</label>
                                    </div>

                                    <div class="radio radio-warning radio-inline">
                                        <input type="radio" id="pUserMountable" name="user_mountable" value="1">
                                        <label for="pUserMountable">是</label>
                                    </div>
                                </div>

                                <p class="text-muted small">用户是否可以自行进行挂载操作?</p>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        {!! csrf_field() !!}
                        <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">权限</button>
                        <button type="submit" class="btn btn-success btn-sm">创建</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
