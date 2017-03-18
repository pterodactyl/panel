{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- Permission is hereby granted, free of charge, to any person obtaining a copy --}}
{{-- of this software and associated documentation files (the "Software"), to deal --}}
{{-- in the Software without restriction, including without limitation the rights --}}
{{-- to use, copy, modify, merge, publish, distribute, sublicense, and/or sell --}}
{{-- copies of the Software, and to permit persons to whom the Software is --}}
{{-- furnished to do so, subject to the following conditions: --}}

{{-- The above copyright notice and this permission notice shall be included in all --}}
{{-- copies or substantial portions of the Software. --}}

{{-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR --}}
{{-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, --}}
{{-- FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE --}}
{{-- AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER --}}
{{-- LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, --}}
{{-- OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE --}}
{{-- SOFTWARE. --}}
@extends('layouts.admin')

@section('title')
    Service Options &rarr; {{ $option->name }} &rarr; Variables
@endsection

@section('content-header')
    <h1>{{ $option->name }}<small>Managing variables for this service option.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.services') }}">Services</a></li>
        <li><a href="{{ route('admin.services.view', $option->service->id) }}">{{ $option->service->name }}</a></li>
        <li><a href="{{ route('admin.services.option.view', $option->id) }}">{{ $option->name }}</a></li>
        <li class="active">Variables</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.services.option.view', $option->id) }}">Configuration</a></li>
                <li class="active"><a href="{{ route('admin.services.option.variables', $option->id) }}">Variables</a></li>
                <li class="tab-success"><a href="#modal" data-toggle="modal" data-target="#newVariableModal">New Variable</a></li>
                <li><a href="{{ route('admin.services.option.scripts', $option->id) }}">Scripts</a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    @foreach($option->variables as $variable)
        <div class="col-sm-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $variable->name }}</h3>
                </div>
                <form action="{{ route('admin.services.option.variables.edit', ['id' => $option->id, 'variable' => $variable->id]) }}" method="POST">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ $variable->name }}" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ $variable->description }}</textarea>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label">Environment Variable</label>
                                <input type="text" name="env_variable" value="{{ $variable->env_variable }}" class="form-control" />
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label">Default Value</label>
                                <input type="text" name="default_value" value="{{ $variable->default_value }}" class="form-control" />
                            </div>
                            <div class="col-xs-12">
                                <p class="text-muted small">This variable can be accessed in the statup command by using <code>{{ $variable->env_variable }}</code>.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Permissions</label>
                            <select name="options[]" class="pOptions form-control" multiple>
                                <option value="user_viewable" {{ (! $variable->user_viewable) ?: 'selected' }}>Users Can View</option>
                                <option value="user_editable" {{ (! $variable->user_editable) ?: 'selected' }}>Users Can Edit</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Input Rules</label>
                            <input type="text" name="rules" class="form-control" value="{{ $variable->rules }}" />
                            <p class="text-muted small">These rules are defined using standard Laravel Framework validation rules.</p>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-sm btn-danger pull-left muted muted-hover" data-action="delete" name="action" value="delete" type="submit"><i class="fa fa-trash-o"></i></button>
                        <button class="btn btn-sm btn-primary pull-right" name="action" value="save" type="submit">Save</button>
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
                <h4 class="modal-title">Create New Option Variable</h4>
            </div>
            <form action="{{ route('admin.services.option.variables', $option->id) }}" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label">Environment Variable</label>
                            <input type="text" name="env_variable" class="form-control" />
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Default Value</label>
                            <input type="text" name="default_value" class="form-control" />
                        </div>
                        <div class="col-xs-12">
                            <p class="text-muted small">This variable can be accessed in the statup command by entering <code>@{{environment variable value}}</code>.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Permissions</label>
                        <select name="options[]" class="pOptions form-control" multiple>
                            <option value="user_viewable">Users Can View</option>
                            <option value="user_editable">Users Can Edit</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Input Rules</label>
                        <input type="text" name="rules" class="form-control" placeholder="required|string|max:20" />
                        <p class="text-muted small">These rules are defined using standard Laravel Framework validation rules.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    {!! csrf_field() !!}
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Variable</button>
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
