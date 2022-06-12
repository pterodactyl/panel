{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    服务器实例 — {{ $server->name }}: 启动设置
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>管理启动命令与其变量.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li><a href="{{ route('admin.servers') }}">服务器实例</a></li>
        <li><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
        <li class="active">启动设置</li>
    </ol>
@endsection

@section('content')
@include('admin.servers.partials.navigation')
<form action="{{ route('admin.servers.view.startup', $server->id) }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">启动命令编辑</h3>
                </div>
                <div class="box-body">
                    <label for="pStartup" class="form-label">启动命令</label>
                    <input id="pStartup" name="startup" class="form-control" type="text" value="{{ old('startup', $server->startup) }}" />
                    <p class="small text-muted">于此编辑服务器实例的启动命令. 默认可用的变量有: <code>@{{SERVER_MEMORY}}</code>, <code>@{{SERVER_IP}}</code>, 和 <code>@{{SERVER_PORT}}</code>.</p>
                </div>
                <div class="box-body">
                    <label for="pDefaultStartupCommand" class="form-label">默认启动命令</label>
                    <input id="pDefaultStartupCommand" class="form-control" type="text" readonly />
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-primary btn-sm pull-right">保存编辑</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">预设设置</h3>
                </div>
                <div class="box-body row">
                    <div class="col-xs-12">
                        <p class="small text-danger">
                            更改以下任何值将导致服务器处理重新安装命令。 服务器将停止运行，然后重启。
                            如果您不希望服务程序运行，请确保选中底部的框。
                        </p>
                        <p class="small text-danger">
                            <strong>在许多情况下，这是一种破坏性操作。 此服务器将立即停止，以便此操作继续进行.</strong>
                        </p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="pNestId">预设组</label>
                        <select name="nest_id" id="pNestId" class="form-control">
                            @foreach($nests as $nest)
                                <option value="{{ $nest->id }}"
                                    @if($nest->id === $server->nest_id)
                                        selected
                                    @endif
                                >{{ $nest->name }}</option>
                            @endforeach
                        </select>
                        <p class="small text-muted no-margin">选择服务器实例使用的预设组.</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="pEggId">预设</label>
                        <select name="egg_id" id="pEggId" class="form-control"></select>
                        <p class="small text-muted no-margin">选择将为该服务器提供处理数据的预设.</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="pSkipScripting" name="skip_scripts" type="checkbox" value="1" @if($server->skip_scripts) checked @endif />
                            <label for="pSkipScripting" class="strong">跳过预设安装程序</label>
                        </div>
                        <p class="small text-muted no-margin">如果选定的预设附加了安装程序，则该程序将在安装期间运行。 如果您想跳过此步骤，请选中此框.</p>
                    </div>
                </div>
            </div>
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Docker 镜像设置</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pDockerImage">镜像</label>
                        <select id="pDockerImage" name="docker_image" class="form-control"></select>
                        <input id="pDockerImageCustom" name="custom_docker_image" value="{{ old('custom_docker_image') }}" class="form-control" placeholder="Or enter a custom image..." style="margin-top:1rem"/>
                        <p class="small text-muted no-margin">这是将用于运行此服务器的 Docker 映像。 从下拉列表中选择镜像或在上面的文本字段中输入自定义镜像.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="row" id="appendVariablesTo"></div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/lodash/lodash.js') !!}
    <script>
    $(document).ready(function () {
        $('#pEggId').select2({placeholder: '选择预设'}).on('change', function () {
            var selectedEgg = _.isNull($(this).val()) ? $(this).find('option').first().val() : $(this).val();
            var parentChain = _.get(Pterodactyl.nests, $("#pNestId").val());
            var objectChain = _.get(parentChain, 'eggs.' + selectedEgg);

            const images = _.get(objectChain, 'docker_images', [])
            $('#pDockerImage').html('');
            const keys = Object.keys(images);
            for (let i = 0; i < keys.length; i++) {
                let opt = document.createElement('option');
                opt.value = images[keys[i]];
                opt.innerHTML = keys[i] + " (" + images[keys[i]] + ")";
                if (objectChain.id === parseInt(Pterodactyl.server.egg_id) && Pterodactyl.server.image == opt.value) {
                    opt.selected = true
                }
                $('#pDockerImage').append(opt);
            }
            $('#pDockerImage').on('change', function () {
                $('#pDockerImageCustom').val('');
            })

            if (objectChain.id === parseInt(Pterodactyl.server.egg_id)) {
                if ($('#pDockerImage').val() != Pterodactyl.server.image) {
                    $('#pDockerImageCustom').val(Pterodactyl.server.image);
                }
            }

            if (!_.get(objectChain, 'startup', false)) {
                $('#pDefaultStartupCommand').val(_.get(parentChain, 'startup', 'ERROR: Startup Not Defined!'));
            } else {
                $('#pDefaultStartupCommand').val(_.get(objectChain, 'startup'));
            }

            $('#appendVariablesTo').html('');
            $.each(_.get(objectChain, 'variables', []), function (i, item) {
                var setValue = _.get(Pterodactyl.server_variables, item.env_variable, item.default_value);
                var isRequired = (item.required === 1) ? '<span class="label label-danger">必填</span> ' : '';
                var dataAppend = ' \
                    <div class="col-xs-12"> \
                        <div class="box"> \
                            <div class="box-header with-border"> \
                                <h3 class="box-title">' + isRequired + item.name + '</h3> \
                            </div> \
                            <div class="box-body"> \
                                <input name="environment[' + item.env_variable + ']" class="form-control" type="text" id="egg_variable_' + item.env_variable + '" /> \
                                <p class="no-margin small text-muted">' + item.description + '</p> \
                            </div> \
                            <div class="box-footer"> \
                                <p class="no-margin text-muted small"><strong>启动命令变量:</strong> <code>' + item.env_variable + '</code></p> \
                                <p class="no-margin text-muted small"><strong>字符限制:</strong> <code>' + item.rules + '</code></p> \
                            </div> \
                        </div> \
                    </div>';
                $('#appendVariablesTo').append(dataAppend).find('#egg_variable_' + item.env_variable).val(setValue);
            });
        });

        $('#pNestId').select2({placeholder: '选择预设组'}).on('change', function () {
            $('#pEggId').html('').select2({
                data: $.map(_.get(Pterodactyl.nests, $(this).val() + '.eggs', []), function (item) {
                    return {
                        id: item.id,
                        text: item.name,
                    };
                }),
            });

            if (_.isObject(_.get(Pterodactyl.nests, $(this).val() + '.eggs.' + Pterodactyl.server.egg_id))) {
                $('#pEggId').val(Pterodactyl.server.egg_id);
            }

            $('#pEggId').change();
        }).change();
    });
    </script>
@endsection
