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
    Services &rarr; {{ $service->name }}
@endsection

@section('content-header')
    <h1>{{ $service->name }}<small>{{ str_limit($service->description, 50) }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.services') }}">Services</a></li>
        <li class="active">{{ $service->name }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li class="active"><a href="{{ route('admin.services.view', $service->id) }}">Overview</a></li>
                <li><a href="{{ route('admin.services.view.functions', $service->id) }}">Functions</a></li>
            </ul>
        </div>
    </div>
</div>
<form action="{{ route('admin.services.view', $service->id) }}" method="POST">
    <div class="row">
        <div class="col-md-6">
            <div class="box">
                <div class="box-body">
                    <div class="form-group">
                        <label class="control-label">Name</label>
                        <div>
                            <input type="text" name="name" class="form-control" value="{{ $service->name }}" />
                            <p class="text-muted"><small>This should be a descriptive category name that emcompasses all of the options within the service.</small></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Description</label>
                        <div>
                            <textarea name="description" class="form-control" rows="6">{{ $service->description }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box">
                <div class="box-body">
                    <div class="form-group">
                        <label class="control-label">Folder Name</label>
                        <div>
                            <input type="text" name="folder" class="form-control" value="{{ $service->folder }}" />
                            <p class="text-muted"><small>Services are downloaded by the daemon and stored in a folder using this name. The storage location is <code>/srv/daemon/services/{NAME}</code> by default.</small></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Default Start Command</label>
                        <div>
                            <textarea name="startup" class="form-control" rows="2">{{ $service->startup }}</textarea>
                            <p class="text-muted"><small>The default start command to use when running options under this service. This command can be modified per-option and should include the executable to be called in the container.</small></p>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button id="deleteButton" type="input" name="action" value="delete" class="btn btn-sm btn-danger muted muted-hover"><i class="fa fa-trash-o"></i></button>
                    <button type="input" class="btn btn-primary btn-sm pull-right">Edit Service</button>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Configured Options</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tr>
                        <th class="col-sm-4 col-md-3">Name</th>
                        <th>Description</th>
                        <th>Tag</th>
                        <th class="text-center">Servers</th>
                    </tr>
                    @foreach($service->options as $option)
                        <tr>
                            <td><a href="{{ route('admin.services.option.view', $option->id) }}">{{ $option->name }}</a></td>
                            <td>{!! $option->description !!}</td>
                            <td><code>{{ $option->tag }}</code></td>
                            <td class="text-center">{{ $option->servers->count() }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <div class="box-footer">
                <a href="{{ route('admin.services.option.new') }}"><button class="btn btn-success btn-sm pull-right">New Service Option</button></a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#deleteButton').on('mouseenter', function (event) {
            $(this).find('i').html(' Delete Service');
        }).on('mouseleave', function (event) {
            $(this).find('i').html('');
        });
    </script>
@endsection
