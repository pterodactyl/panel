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
    New Service
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="{{ route('admin.services') }}">Services</a></li>
        <li class="active">New Service</li>
    </ul>
    <h3 class="nopad">Add New Service</h3><hr />
    <form action="{{ route('admin.services.new') }}" method="POST">
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="control-label">Service Name:</label>
                <div>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" />
                    <p class="text-muted"><small>This should be a descriptive category name that emcompasses all of the options within the service.</small></p>
                </div>
            </div>
            <div class="col-md-6 form-group">
                <label class="control-label">Service Description:</label>
                <div>
                    <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="control-label">Service Configuration File:</label>
                <div class="input-group">
                    <span class="input-group-addon">/src/services/</span>
                    <input type="text" name="file" class="form-control" value="{{ old('file') }}" />
                    <span class="input-group-addon">/index.js</span>
                </div>
                <p class="text-muted"><small>This should be a unique alpha-numeric <code>(a-z)</code> name used to identify the service.</small></p>
            </div>
            <div class="col-md-6 form-group">
                <label class="control-label">Display Executable:</label>
                <div>
                    <input type="text" name="executable" class="form-control" value="{{ old('executable') }}" />
                </div>
                <p class="text-muted"><small>Changing this has no effect on operation of the daemon, it is simply used for display purposes on the panel. This can be changed per-option.</small></p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 form-group">
                <label class="control-label">Default Startup:</label>
                <div class="input-group">
                    <span class="input-group-addon" id="disp_exec"></span>
                    <input type="text" name="startup" class="form-control" value="{{ old('startup') }}" />
                </div>
                <p class="text-muted"><small>This is the default startup that will be used for all servers created using this service. This can be changed per-option.</small></p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info">You will be able to add service options and variables once the service is created.</div>
                {!! csrf_field() !!}
                <input type="submit" class="btn btn-sm btn-primary" value="Add New Service" />
            </div>
        </div>
    </form>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/services/new']").addClass('active');
    $('input[name="executable"]').on('keyup', function() {
        $("#disp_exec").html(escape($(this).val()));
    });
});
</script>
@endsection
