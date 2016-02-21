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
    New Variable for {{ $option->name }}
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/services">Services</a></li>
        <li><a href="{{ route('admin.services.service', $service->id) }}">{{ $service->name }}</a></li>
        <li><a href="{{ route('admin.services.option', [$service->id, $option->id]) }}">{{ $option->name }}</a></li>
        <li class="active">New Variable</li>
    </ul>
    <h3>New Option Variable</h3><hr />
    <form action="{{ route('admin.services.option.variable.new', [$service->id, $option->id]) }}" method="POST">
        <div class="well">
            <div class="row">
                <div class="col-md-12 form-group">
                    <label class="control-label">Variable Name:</label>
                    <div>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 form-group">
                    <label class="control-label">Variable Description:</label>
                    <div>
                        <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 form-group">
                    <label class="control-label">Regex:</label>
                    <div>
                        <input type="text" name="regex" class="form-control" value="{{ old('regex') }}" />
                        <p class="text-muted"><small>Regex code to use when verifying the contents of the field.</small></p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="control-label">Environment Variable:</label>
                    <div>
                        <input type="text" name="env_variable" id="env_var" class="form-control" value="{{ old('env_variable') }}" />
                        <p class="text-muted"><small>Accessed in startup by using <code>&#123;&#123;&#125;&#125;</code> parameter.</small></p>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="control-label">Default Value:</label>
                    <div>
                        <input type="text" name="default_value" class="form-control" value="{{ old('default_value') }}" />
                        <p class="text-muted"><small>The default value to use for this field.</small></p>
                    </div>
                </div>
            </div>
            <div class="row fuelux">
                <div class="col-md-4">
                    <div class="checkbox highlight">
                        <label class="checkbox-custom highlight" data-initialize="checkbox">
                            <input class="sr-only" name="user_viewable" type="checkbox" value="1" @if((int) old('user_viewable') === 1)checked="checked"@endif> <strong>User Viewable</strong>
                            <p class="text-muted"><small>Can users view this variable?</small><p>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="checkbox highlight">
                        <label class="checkbox-custom highlight" data-initialize="checkbox">
                            <input class="sr-only" name="user_editable" type="checkbox" value="1" @if((int) old('user_editable') === 1)checked="checked"@endif> <strong>User Editable</strong>
                            <p class="text-muted"><small>Can users edit this variable?</small><p>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="checkbox highlight">
                        <label class="checkbox-custom highlight" data-initialize="checkbox">
                            <input class="sr-only" name="required" type="checkbox" value="1" @if((int) old('required') === 1)checked="checked"@endif> <strong>Required</strong>
                            <p class="text-muted"><small>This this variable required?</small><p>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-sm btn-primary" value="Add Variable" />
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
