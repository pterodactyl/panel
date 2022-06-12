{{-- Pterodactyl - Panel which Sinicizated by iLwork.CN STUDIO --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Simplified Chinese Translation Copyright (c) 2021 - 2022 Ice Ling <iceling@ilwork.cn> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    预设 &rarr; {{ $egg->name }} &rarr; 变量
@endsection

@section('content-header')
    <h1>{{ $egg->name }}<small>管理预设所使用的变量.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li><a href="{{ route('admin.nests') }}">预设组</a></li>
        <li><a href="{{ route('admin.nests.view', $egg->nest->id) }}">{{ $egg->nest->name }}</a></li>
        <li><a href="{{ route('admin.nests.egg.view', $egg->id) }}">{{ $egg->name }}</a></li>
        <li class="active">变量</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.nests.egg.view', $egg->id) }}">设置</a></li>
                <li class="active"><a href="{{ route('admin.nests.egg.variables', $egg->id) }}">变量</a></li>
                <li><a href="{{ route('admin.nests.egg.scripts', $egg->id) }}">安装程序</a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <div class="box no-border">
            <div class="box-body">
                <a href="#" class="btn btn-sm btn-success pull-right" data-toggle="modal" data-target="#newVariableModal">新建变量</a>
            </div>
        </div>
    </div>
</div>
<div class="row">
    @foreach($egg->variables as $variable)
        <div class="col-sm-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $variable->name }}</h3>
                </div>
                <form action="{{ route('admin.nests.egg.variables.edit', ['egg' => $egg->id, 'variable' => $variable->id]) }}" method="POST">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="form-label">名称</label>
                            <input type="text" name="name" value="{{ $variable->name }}" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">描述</label>
                            <textarea name="description" class="form-control" rows="3">{{ $variable->description }}</textarea>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label">环境变量</label>
                                <input type="text" name="env_variable" value="{{ $variable->env_variable }}" class="form-control" />
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label">默认值</label>
                                <input type="text" name="default_value" value="{{ $variable->default_value }}" class="form-control" />
                            </div>
                            <div class="col-xs-12">
                                <p class="text-muted small">这个变量可以在启动命令中以 <code>{{ $variable->env_variable }}</code> 来使用.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">权限</label>
                            <select name="options[]" class="pOptions form-control" multiple>
                                <option value="user_viewable" {{ (! $variable->user_viewable) ?: 'selected' }}>用户只读</option>
                                <option value="user_editable" {{ (! $variable->user_editable) ?: 'selected' }}>用户可读写</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">输入规则</label>
                            <input type="text" name="rules" class="form-control" value="{{ $variable->rules }}" />
                            <p class="text-muted small">这些规则是使用 <a href="https://laravel.com/docs/5.7/validation#available-validation-rules" target="_blank">Laravel 框架验证规则</a> 标准定义的.</p>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-sm btn-primary pull-right" name="_method" value="PATCH" type="submit">保存</button>
                        <button class="btn btn-sm btn-danger pull-left muted muted-hover" data-action="delete" name="_method" value="DELETE" type="submit"><i class="fa fa-trash-o"></i></button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</div>
<div class="modal fade" id="newVariableModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">创建新预设变量</h4>
            </div>
            <form action="{{ route('admin.nests.egg.variables', $egg->id) }}" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label">名称 <span class="field-required"></span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}"/>
                    </div>
                    <div class="form-group">
                        <label class="control-label">描述</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="control-label">环境变量 <span class="field-required"></span></label>
                            <input type="text" name="env_variable" class="form-control" value="{{ old('env_variable') }}" />
                        </div>
                        <div class="form-group col-md-6">
                            <label class="control-label">默认值</label>
                            <input type="text" name="default_value" class="form-control" value="{{ old('default_value') }}" />
                        </div>
                        <div class="col-xs-12">
                            <p class="text-muted small">这个变量可以在启动命令中以 <code>@{{environment variable value}}</code> 来使用.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">P权限</label>
                        <select name="options[]" class="pOptions form-control" multiple>
                            <option value="user_viewable">用户只读</option>
                            <option value="user_editable">用户可读写</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label">输入规则 <span class="field-required"></span></label>
                        <input type="text" name="rules" class="form-control" value="{{ old('rules', 'required|string|max:20') }}" placeholder="required|string|max:20" />
                        <p class="text-muted small">这些规则是使用 <a href="https://laravel.com/docs/5.7/validation#available-validation-rules" target="_blank">Laravel 框架验证规则</a> 标准定义的.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    {!! csrf_field() !!}
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">关闭</button>
                    <button type="submit" class="btn btn-primary">创建变量</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('.pOptions').select2();
        $('[data-action="delete"]').on('mouseenter', function (event) {
            $(this).find('i').html(' Delete Variable');
        }).on('mouseleave', function (event) {
            $(this).find('i').html('');
        });
    </script>
@endsection
