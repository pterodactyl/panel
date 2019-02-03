{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/eggs.variables.header.titleStart') {{ $egg->name }} @lang('admin/eggs.variables.header.titleEnd')
@endsection

@section('content-header')
    <h1>{{ $egg->name }}<small>@lang('admin/eggs.variables.header.overview')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/eggs.variables.header.admin')</a></li>
        <li><a href="{{ route('admin.nests') }}">@lang('admin/eggs.variables.header.nests')</a></li>
        <li><a href="{{ route('admin.nests.view', $egg->nest->id) }}">{{ $egg->nest->name }}</a></li>
        <li><a href="{{ route('admin.nests.egg.view', $egg->id) }}">{{ $egg->name }}</a></li>
        <li class="active">Variables</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.nests.egg.view', $egg->id) }}">@lang('admin/eggs.variables.content.configuration')</a></li>
                <li class="active"><a href="{{ route('admin.nests.egg.variables', $egg->id) }}">@lang('admin/eggs.variables.content.variables')</a></li>
                <li><a href="{{ route('admin.nests.egg.scripts', $egg->id) }}">@lang('admin/eggs.variables.content.scripts')</a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <div class="box no-border">
            <div class="box-body">
                <a href="#" class="btn btn-sm btn-success pull-right" data-toggle="modal" data-target="#newVariableModal">@lang('admin/eggs.variables.content.create_new_variable')</a>
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
                <form action="{{ route('admin.nests.egg.variables.edit', ['id' => $egg->id, 'variable' => $variable->id]) }}" method="POST">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="form-label">@lang('admin/eggs.variables.content.name')</label>
                            <input type="text" name="name" value="{{ $variable->name }}" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">@lang('admin/eggs.variables.content.description')</label>
                            <textarea name="description" class="form-control" rows="3">{{ $variable->description }}</textarea>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label">@lang('admin/eggs.variables.content.env')</label>
                                <input type="text" name="env_variable" value="{{ $variable->env_variable }}" class="form-control" />
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label">@lang('admin/eggs.variables.content.default')</label>
                                <input type="text" name="default_value" value="{{ $variable->default_value }}" class="form-control" />
                            </div>
                            <div class="col-xs-12">
                                <p class="text-muted small">@lang('admin/eggs.variables.content.hintStart') <code>{{ $variable->env_variable }}</code>@lang('admin/eggs.variables.content.hintEnd')</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">@lang('admin/eggs.variables.content.permissions')</label>
                            <select name="options[]" class="pOptions form-control" multiple>
                                <option value="user_viewable" {{ (! $variable->user_viewable) ?: 'selected' }}>@lang('admin/eggs.variables.content.user_view')</option>
                                <option value="user_editable" {{ (! $variable->user_editable) ?: 'selected' }}>@lang('admin/eggs.variables.content.user_edit')</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">@lang('admin/eggs.variables.content.input_rules')</label>
                            <input type="text" name="rules" class="form-control" value="{{ $variable->rules }}" />
                            <p class="text-muted small">@lang('admin/eggs.variables.content.input_rules_description')</p>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-sm btn-primary pull-right" name="_method" value="PATCH" type="submit">@lang('admin/eggs.variables.content.save')</button>
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
                <h4 class="modal-title">@lang('admin/eggs.variables.content.create_new_egg_variable')</h4>
            </div>
            <form action="{{ route('admin.nests.egg.variables', $egg->id) }}" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label">@lang('admin/eggs.variables.content.name') <span class="field-required"></span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}"/>
                    </div>
                    <div class="form-group">
                        <label class="control-label">@lang('admin/eggs.variables.content.description')</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="control-label">@lang('admin/eggs.variables.content.env') <span class="field-required"></span></label>
                            <input type="text" name="env_variable" class="form-control" value="{{ old('env_variable') }}" />
                        </div>
                        <div class="form-group col-md-6">
                            <label class="control-label">@lang('admin/eggs.variables.content.default')</label>
                            <input type="text" name="default_value" class="form-control" value="{{ old('default_value') }}" />
                        </div>
                        <div class="col-xs-12">
                            <p class="text-muted small">@lang('admin/eggs.variables.content.hint')</code>.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">@lang('admin/eggs.variables.content.permissions')</label>
                        <select name="options[]" class="pOptions form-control" multiple>
                            <option value="user_viewable">@lang('admin/eggs.variables.content.user_view')</option>
                            <option value="user_editable">@lang('admin/eggs.variables.content.user_edit')</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label">@lang('admin/eggs.variables.content.input_rules') <span class="field-required"></span></label>
                        <input type="text" name="rules" class="form-control" value="{{ old('rules', 'required|string|max:20') }}" placeholder="required|string|max:20" />
                        <p class="text-muted small">@lang('admin/eggs.variables.content.input_rules_description')</p>
                    </div>
                </div>
                <div class="modal-footer">
                    {!! csrf_field() !!}
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">@lang('admin/eggs.variables.content.close')</button>
                    <button type="submit" class="btn btn-primary">@lang('admin/eggs.variables.content.create_variable')</button>
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
