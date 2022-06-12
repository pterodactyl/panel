{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    预设配置: {{ $egg->name }} &rarr; 安装程序
@endsection

@section('content-header')
    <h1>{{ $egg->name }}<small>管理预设使用的安装程序.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li><a href="{{ route('admin.nests') }}">预设组</a></li>
        <li><a href="{{ route('admin.nests.view', $egg->nest->id) }}">{{ $egg->nest->name }}</a></li>
        <li><a href="{{ route('admin.nests.egg.view', $egg->id) }}">{{ $egg->name }}</a></li>
        <li class="active">{{ $egg->name }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.nests.egg.view', $egg->id) }}">设置</a></li>
                <li><a href="{{ route('admin.nests.egg.variables', $egg->id) }}">变量</a></li>
                <li class="active"><a href="{{ route('admin.nests.egg.scripts', $egg->id) }}">安装程序</a></li>
            </ul>
        </div>
    </div>
</div>
<form action="{{ route('admin.nests.egg.scripts', $egg->id) }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">安装程序</h3>
                </div>
                @if(! is_null($egg->copyFrom))
                    <div class="box-body">
                        <div class="callout callout-warning no-margin">
                            这个选项是复制预设配置于另一个配置 <a href="{{ route('admin.nests.egg.view', $egg->copyFrom->id) }}">{{ $egg->copyFrom->name }}</a>. 除非您从下面的下拉框中选择“无”，否则您对此脚本所做的任何更改都不会应用.
                        </div>
                    </div>
                @endif
                <div class="box-body no-padding">
                    <div id="editor_install"style="height:300px">{{ $egg->script_install }}</div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-sm-4">
                            <label class="control-label">复制安装程序于</label>
                            <select id="pCopyScriptFrom" name="copy_script_from">
                                <option value="">无</option>
                                @foreach($copyFromOptions as $opt)
                                    <option value="{{ $opt->id }}" {{ $egg->copy_script_from !== $opt->id ?: 'selected' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-muted small">如果选中，上方的安装程序将被忽略，所选选项中的安装程序将被使用.</p>
                        </div>
                        <div class="form-group col-sm-4">
                            <label class="control-label">安装程序使用的镜像</label>
                            <input type="text" name="script_container" class="form-control" value="{{ $egg->script_container }}" />
                            <p class="text-muted small">运行安装程序时使用的 Docker 镜像，一般集成了程序所需的运行环境.</p>
                        </div>
                        <div class="form-group col-sm-4">
                            <label class="control-label">安装程序入口命令</label>
                            <input type="text" name="script_entry" class="form-control" value="{{ $egg->script_entry }}" />
                            <p class="text-muted small">用于此安装程序的入口命令.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 text-muted">
                            以下服务选项依赖于此脚本:
                            @if(count($relyOnScript) > 0)
                                @foreach($relyOnScript as $rely)
                                    <a href="{{ route('admin.nests.egg.view', $rely->id) }}">
                                        <code>{{ $rely->name }}</code>@if(!$loop->last),&nbsp;@endif
                                    </a>
                                @endforeach
                            @else
                                <em>无</em>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <textarea name="script_install" class="hidden"></textarea>
                    <button type="submit" name="_method" value="PATCH" class="btn btn-primary btn-sm pull-right">保存</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/ace/ace.js') !!}
    {!! Theme::js('vendor/ace/ext-modelist.js') !!}
    <script>
    $(document).ready(function () {
        $('#pCopyScriptFrom').select2();

        const InstallEditor = ace.edit('editor_install');
        const Modelist = ace.require('ace/ext/modelist')

        InstallEditor.setTheme('ace/theme/chrome');
        InstallEditor.getSession().setMode('ace/mode/sh');
        InstallEditor.getSession().setUseWrapMode(true);
        InstallEditor.setShowPrintMargin(false);

        $('form').on('submit', function (e) {
            $('textarea[name="script_install"]').val(InstallEditor.getValue());
        });
    });
    </script>

@endsection
