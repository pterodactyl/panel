{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}

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
    Manage Service
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/services">Services</a></li>
        <li class="active">{{ $service->name }}</li>
    </ul>
    <h3 class="nopad">Service Options</h3><hr />
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Option Name</th>
                <th>Description</th>
                <th>Tag</th>
                <th class="text-center">Servers</th>
            </tr>
        </thead>
        <tbody>
            @foreach($options as $option)
                <tr>
                    <td><a href="{{ route('admin.services.option', [ $service->id, $option->id]) }}">{{ $option->name }}</a></td>
                    <td>{!! $option->description !!}</td>
                    <td><code>{{ $option->tag }}</code></td>
                    <td class="text-center">{{ $option->c_servers }}</td>
                </tr>
            @endforeach
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-center"><a href="{{ route('admin.services.option.new', $service->id) }}"><i class="fa fa-plus"></i></a></td>
            </tr>
        </tbody>
    </table>
    <div class="well">
        <form action="{{ route('admin.services.service', $service->id) }}" method="POST">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="control-label">Service Name:</label>
                    <div>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $service->name) }}" />
                        <p class="text-muted"><small>This should be a descriptive category name that emcompasses all of the options within the service.</small></p>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="control-label">Service Description:</label>
                    <div>
                        <textarea name="description" class="form-control" rows="4">{{ old('description', $service->description) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="control-label">Service Configuration File:</label>
                    <div class="input-group">
                        <span class="input-group-addon">/src/services/</span>
                        <input type="text" name="file" class="form-control" value="{{ old('file', $service->file) }}" />
                        <span class="input-group-addon">/index.js</span>
                    </div>
                    <p class="text-muted"><small>This should be the name of the folder on the daemon that contains all of the service logic. Changing this can have unintended effects on servers or causes errors to occur.</small></p>
                </div>
                <div class="col-md-6 form-group">
                    <label class="control-label">Display Executable:</label>
                    <div>
                        <input type="text" name="executable" class="form-control" value="{{ old('executable', $service->executable) }}" />
                    </div>
                    <p class="text-muted"><small>Changing this has no effect on operation of the daemon, it is simply used for display purposes on the panel. This can be changed per-option.</small></p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 form-group">
                    <label class="control-label">Default Startup:</label>
                    <div class="input-group">
                        <span class="input-group-addon" id="disp_exec">{{ $service->executable }}</span>
                        <input type="text" name="startup" class="form-control" value="{{ old('startup', $service->startup) }}" />
                    </div>
                    <p class="text-muted"><small>This is the default startup that will be used for all servers created using this service. This can be changed per-option.</small></p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-sm btn-primary" value="Save Changes" />
                    <a href="{{ route('admin.services.service.config', $service->id) }}"><button type="button" class="pull-right btn btn-sm btn-default">Manage Configuration</button></a>
                </div>
            </div>
        </form>
    </div>
    <form action="{{ route('admin.services.service', $service->id) }}" method="POST">
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-danger">
                    Deleting a service is an irreversible action. A service can <em>only</em> be deleted if no servers are associated with it.
                </div>
                {!! csrf_field() !!}
                {!! method_field('DELETE') !!}
                <input type="submit" class="btn btn-sm btn-danger pull-right" value="Delete Service" />
            </div>
        </div>
    </form>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/services']").addClass('active');
    $('input[name="executable"]').on('keyup', function() {
        $("#disp_exec").html(escape($(this).val()));
    });
});
</script>
@endsection
