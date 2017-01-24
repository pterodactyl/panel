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
    Manage Service Option {{ $option->name }}
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/services">Services</a></li>
        <li><a href="{{ route('admin.services.service', $service->id) }}">{{ $service->name }}</a></li>
        <li class="active">{{ $option->name }}</li>
    </ul>
    <div class="alert alert-warning"><strong>Warning!</strong> This page contains advanced settings that the panel and daemon use to control servers. Modifying information on this page is not recommended unless you are absolutely sure of what you are doing.</div>
    <h3>Settings</h3><hr />
    <form action="{{ route('admin.services.option', [$service->id, $option->id]) }}" method="POST">
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="control-label">Name:</label>
                <div>
                    <input type="text" name="name" value="{{ old('name', $option->name) }}" class="form-control" />
                </div>
            </div>
            <div class="col-md-6 form-group">
                <label class="control-label">Description:</label>
                <div>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $option->description) }}</textarea>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 form-group">
                <label class="control-label">Tag:</label>
                <div>
                    <input type="text" name="tag" value="{{ old('tag', $option->tag) }}" class="form-control" />
                </div>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">Executable:</label>
                <div>
                    <input type="text" name="executable" value="{{ old('executable', $option->executable) }}" class="form-control" />
                    <p class="text-muted"><small>Leave blank to use parent executable.</small></p>
                </div>
            </div>
            <div class="col-md-6 form-group">
                <label class="control-label">Docker Image:</label>
                <div>
                    <input type="text" name="docker_image" value="{{ old('docker_image', $option->docker_image) }}" class="form-control" />
                    <p class="text-muted"><small>Changing the docker image will only effect servers created or modified after this point.</small></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 form-group">
                <label class="control-label">Default Startup Command:</label>
                <div>
                    <input type="text" name="startup" value="{{ old('startup', $option->startup) }}" class="form-control" />
                    <p class="text-muted"><small>To use the default startup of the parent service simply leave this field blank.</small></p>
                </div>
            </div>
        </div>
        <div class="well well-sm">
            <div class="row">
                <div class="col-md-12">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-sm btn-primary" value="Update Service Option" />
                </div>
            </div>
        </div>
    </form>
    <h3>Variables <small><a href="{{ route('admin.services.option.variable.new', [$service->id, $option->id]) }}"><i class="fa fa-plus"></i></a></small></h3><hr />
    @foreach($variables as $variable)
    <form action="{{ route('admin.services.option.variable', [$service->id, $option->id, $variable->id]) }}" method="POST">
        <div class="well">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="control-label">Variable Name:</label>
                    <div>
                        <input type="text" name="{{ $variable->id }}_name" class="form-control" value="{{ old($variable->id.'_name', $variable->name) }}" />
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="control-label">Variable Description:</label>
                    <div>
                        <textarea name="{{ $variable->id }}_description" class="form-control" rows="2">{{ old($variable->id.'_description', $variable->description) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label class="control-label">Environment Variable:</label>
                    <div>
                        <input type="text" name="{{ $variable->id }}_env_variable" id="env_var" class="form-control" value="{{ old($variable->id.'_env_variable', $variable->env_variable) }}" />
                        <p class="text-muted"><small>Accessed in startup by using <code>&#123;&#123;{{ $variable->env_variable }}&#125;&#125;</code> prameter.</small></p>
                    </div>
                </div>
                <div class="col-md-4 form-group">
                    <label class="control-label">Default Value:</label>
                    <div>
                        <input type="text" name="{{ $variable->id }}_default_value" class="form-control" value="{{ old($variable->id.'_default_value', $variable->default_value) }}" />
                        <p class="text-muted"><small>The default value to use for this field.</small></p>
                    </div>
                </div>
                <div class="col-md-4 form-group">
                    <label class="control-label">Regex:</label>
                    <div>
                        <input type="text" name="{{ $variable->id }}_regex" class="form-control" value="{{ old($variable->id.'_regex', $variable->regex) }}" />
                        <p class="text-muted"><small>Regex code to use when verifying the contents of the field.</small></p>
                    </div>
                </div>
            </div>
            <div class="row fuelux">
                <div class="col-md-4">
                    <div class="checkbox highlight">
                        <label class="checkbox-custom highlight" data-initialize="checkbox">
                            <input class="sr-only" name="{{ $variable->id }}_user_viewable" type="checkbox" value="1" @if((int) old($variable->id.'_user_viewable', $variable->user_viewable) === 1)checked="checked"@endif> <strong>User Viewable</strong>
                            <p class="text-muted"><small>Can users view this variable?</small><p>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="checkbox highlight">
                        <label class="checkbox-custom highlight" data-initialize="checkbox">
                            <input class="sr-only" name="{{ $variable->id }}_user_editable" type="checkbox" value="1" @if((int) old($variable->id.'_user_editable', $variable->user_editable) === 1)checked="checked"@endif> <strong>User Editable</strong>
                            <p class="text-muted"><small>Can users edit this variable?</small><p>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="checkbox highlight">
                        <label class="checkbox-custom highlight" data-initialize="checkbox">
                            <input class="sr-only" name="{{ $variable->id }}_required" type="checkbox" value="1" @if((int) old($variable->id.'_required', $variable->required) === 1)checked="checked"@endif> <strong>Required</strong>
                            <p class="text-muted"><small>This this variable required?</small><p>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    {!! csrf_field() !!}
                    <a href="{{ route('admin.services.option.variable.delete', [$service->id, $option->id, $variable->id]) }}"><button type="button" class="btn btn-sm btn-danger pull-right"><i class="fa fa-times"></i></button></a>
                    <input type="submit" class="btn btn-sm btn-success" value="Update Variable" />
                </div>
            </div>
        </div>
    </form>
    @endforeach
    <h3>Servers</h3><hr />
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Name</th>
                <th>Owner</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($servers as $server)
                <tr>
                    <td><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></td>
                    <td><a href="{{ route('admin.users.view', $server->owner) }}">{{ $server->a_ownerEmail }}</a></td>
                    <td>{{ $server->updated_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <form action="{{ route('admin.services.option', [$service->id, $option->id]) }}" method="POST">
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-danger">
                    Deleting an option is an irreversible action. An option can <em>only</em> be deleted if no servers are associated with it.
                </div>
                {!! csrf_field() !!}
                {!! method_field('DELETE') !!}
                <input type="submit" class="btn btn-sm btn-danger pull-right" value="Delete Option" />
            </div>
        </div>
    </form>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/services']").addClass('active');
    $('#env_var').on('keyup', function () {
        $(this).parent().find('code').html('&#123;&#123;' + escape($(this).val()) + '&#125;&#125;');
    });
});
</script>
@endsection
