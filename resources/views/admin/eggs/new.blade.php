{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    预设配置
@endsection

@section('content-header')
    <h1>新预设<small>为服务器实例创建新预设.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li><a href="{{ route('admin.nests') }}">预设组</a></li>
        <li class="active">新预设</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.nests.egg.new') }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">配置</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pNestId" class="form-label">所属预设组</label>
                                <div>
                                    <select name="nest_id" id="pNestId">
                                        @foreach($nests as $nest)
                                            <option value="{{ $nest->id }}" {{ old('nest_id') != $nest->id ?: 'selected' }}>{{ $nest->name }} &lt;{{ $nest->author }}&gt;</option>
                                        @endforeach
                                    </select>
                                    <p class="text-muted small">官方将此功能定义为 Nest 和 Egg，为了中文语境下更方便理解，将其翻译为预设组和预设。</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="pName" class="form-label">预设名</label>
                                <input type="text" id="pName" name="name" value="{{ old('name') }}" class="form-control" />
                                <p class="text-muted small">一个简单的、易读的，用作此预设的标识符。 这是用户将看到的游戏服务器类型.</p>
                            </div>
                            <div class="form-group">
                                <label for="pDescription" class="form-label">描述</label>
                                <textarea id="pDescription" name="description" class="form-control" rows="8">{{ old('description') }}</textarea>
                                <p class="text-muted small">预设的描述.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pDockerImage" class="control-label">Docker 镜像</label>
                                <textarea id="pDockerImages" name="docker_images" rows="4" placeholder="quay.io/pterodactyl/service" class="form-control">{{ old('docker_images') }}</textarea>
                                <p class="text-muted small">使用这个预设的服务器可用的 Docker 镜像。 每行输入一个。 如果提供了多个值，用户将能够从此图像列表中自行选择。</p>
                            </div>
                            <div class="form-group">
                                <label for="pStartup" class="control-label">启动命令</label>
                                <textarea id="pStartup" name="startup" class="form-control" rows="10">{{ old('startup') }}</textarea>
                                <p class="text-muted small">用于此预设创建的新服务器的默认启动命令。 您可以根据需要更改每个服务器。</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">进程管理识别</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="alert alert-warning">
                                <p>除非您从“复制设置自”下拉菜单中选择单独的选项，否则下方所有框框都是必填的，在使用复制设置情况下，框框可以留空以使用原预设中的值。</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pConfigFrom" class="form-label">复制设置自</label>
                                <select name="config_from" id="pConfigFrom" class="form-control">
                                    <option value="">无</option>
                                </select>
                                <p class="text-muted small">如果您想默认使用另一个预设的设置，请从上面的下拉列表中选择它。</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigStop" class="form-label">关机命令</label>
                                <input type="text" id="pConfigStop" name="config_stop" class="form-control" value="{{ old('config_stop') }}" />
                                <p class="text-muted small">应该发送到服务器进程以正常停止它们的命令。 如果你需要输出 <code>SIGINT</code> 你应该填入 <code>^C</code> 于此。</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigLogs" class="form-label">日志选项</label>
                                <textarea data-action="handle-tabs" id="pConfigLogs" name="config_logs" class="form-control" rows="6">{{ old('config_logs') }}</textarea>
                                <p class="text-muted small">这里应该用 JSON 格式语法来让系统判断是否进行日志记录并指定日志存储的位置。</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pConfigFiles" class="form-label">配置文件</label>
                                <textarea data-action="handle-tabs" id="pConfigFiles" name="config_files" class="form-control" rows="6">{{ old('config_files') }}</textarea>
                                <p class="text-muted small">这里应该使用 JSON 格式语法来让系统判断更改那些配置文件以及更改何处。</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigStartup" class="form-label">启动识别</label>
                                <textarea data-action="handle-tabs" id="pConfigStartup" name="config_startup" class="form-control" rows="6">{{ old('config_startup') }}</textarea>
                                <p class="text-muted small">这里应该使用 JSON 格式语法来让系统判断服务端程序是否已正常启动完成。</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-success btn-sm pull-right">创建</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/lodash/lodash.js') !!}
    <script>
    $(document).ready(function() {
        $('#pNestId').select2().change();
        $('#pConfigFrom').select2();
    });
    $('#pNestId').on('change', function (event) {
        $('#pConfigFrom').html('<option value="">None</option>').select2({
            data: $.map(_.get(Pterodactyl.nests, $(this).val() + '.eggs', []), function (item) {
                return {
                    id: item.id,
                    text: item.name + ' <' + item.author + '>',
                };
            }),
        });
    });
    $('textarea[data-action="handle-tabs"]').on('keydown', function(event) {
        if (event.keyCode === 9) {
            event.preventDefault();

            var curPos = $(this)[0].selectionStart;
            var prepend = $(this).val().substr(0, curPos);
            var append = $(this).val().substr(curPos);

            $(this).val(prepend + '    ' + append);
        }
    });
    </script>
@endsection
