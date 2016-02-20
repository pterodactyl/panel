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
    New Service Option for {{ $service->name }}
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/services">Services</a></li>
        <li><a href="{{ route('admin.services.service', $service->id) }}">{{ $service->name }}</a></li>
        <li class="active">New Service Option</li>
    </ul>
    <h3>Service Option Settings</h3><hr />
    <form action="{{ route('admin.services.option.new', $service->id) }}" method="POST">
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="control-label">Name:</label>
                <div>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" />
                </div>
            </div>
            <div class="col-md-6 form-group">
                <label class="control-label">Description:</label>
                <div>
                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 form-group">
                <label class="control-label">Tag:</label>
                <div>
                    <input type="text" name="tag" value="{{ old('tag') }}" class="form-control" />
                </div>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">Executable:</label>
                <div>
                    <input type="text" name="executable" value="{{ old('executable') }}" class="form-control" />
                    <p class="text-muted"><small>Leave blank to use parent executable.</small></p>
                </div>
            </div>
            <div class="col-md-6 form-group">
                <label class="control-label">Docker Image:</label>
                <div>
                    <input type="text" name="docker_image" value="{{ old('docker_image') }}" class="form-control" />
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 form-group">
                <label class="control-label">Default Startup Command:</label>
                <div>
                    <input type="text" name="startup" value="{{ old('startup') }}" class="form-control" />
                    <p class="text-muted"><small>To use the default startup of the parent service simply leave this field blank.</small></p>
                </div>
            </div>
        </div>
        <div class="well well-sm">
            <div class="row">
                <div class="col-md-12">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-sm btn-primary" value="Create Service Option" />
                </div>
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
