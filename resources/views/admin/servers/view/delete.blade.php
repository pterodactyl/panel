{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    服务器实例 — {{ $server->name }}: 删除
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>将此服务器从面板上删除.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li><a href="{{ route('admin.servers') }}">服务器实例</a></li>
        <li><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
        <li class="active">删除</li>
    </ol>
@endsection

@section('content')
@include('admin.servers.partials.navigation')
<div class="row">
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">安全删除服务器实例</h3>
            </div>
            <div class="box-body">
                <p>此操作将尝试从面板和守护程序中删除服务器。 如果其中任何流程一个报告错误，则该操作将被取消.</p>
                <p class="text-danger small">删除服务器是不可逆的操作. <strong>所有服务器数据</strong> (包括文件和用户) 都会被删除.</p>
            </div>
            <div class="box-footer">
                <form id="deleteform" action="{{ route('admin.servers.view.delete', $server->id) }}" method="POST">
                    {!! csrf_field() !!}
                    <button id="deletebtn" class="btn btn-danger">安全删除此服务器实例</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">强制删除服务器实例</h3>
            </div>
            <div class="box-body">
                <p>此操作将尝试从面板和守护程序中删除服务器。 如果守护进程没有响应，或报告错误，删除操作将继续.</p>
                <p class="text-danger small">删除服务器是不可逆的操作. <strong>所有服务器数据</strong> (包括文件和用户) 都会被删除. 如果出现错误报告，此方法可能会在您的守护程序服务器上留下垃圾文件.</p>
            </div>
            <div class="box-footer">
                <form id="forcedeleteform" action="{{ route('admin.servers.view.delete', $server->id) }}" method="POST">
                    {!! csrf_field() !!}
                    <input type="hidden" name="force_delete" value="1" />
                    <button id="forcedeletebtn"" class="btn btn-danger">强制删除此服务器实例</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('#deletebtn').click(function (event) {
        event.preventDefault();
        swal({
            title: '',
            type: 'warning',
            text: '您确定要删除此服务器吗？ 没有回头路，所有数据将立即被删除。',
            showCancelButton: true,
            confirmButtonText: '删除',
            confirmButtonColor: '#d9534f',
            closeOnConfirm: false
        }, function () {
            $('#deleteform').submit()
        });
    });
	
    $('#forcedeletebtn').click(function (event) {
        event.preventDefault();
        swal({
            title: '',
            type: 'warning',
            text: '您确定要删除此服务器吗？ 没有回头路，所有数据将立即被删除。',
            showCancelButton: true,
            confirmButtonText: '删除',
            confirmButtonColor: '#d9534f',
            closeOnConfirm: false
        }, function () {
            $('#forcedeleteform').submit()
        });
    });
    </script>
@endsection
