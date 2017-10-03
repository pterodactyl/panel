{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    New Service
@endsection

@section('content-header')
    <h1>New Service<small>Configure a new service to deploy to all nodes.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.services') }}">Service</a></li>
        <li class="active">New</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.services.new') }}" method="POST">
    <div class="row">
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">New Service</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label class="control-label">Name</label>
                        <div>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" />
                            <p class="text-muted"><small>This should be a descriptive category name that emcompasses all of the options within the service.</small></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Description</label>
                        <div>
                            <textarea name="description" class="form-control" rows="6">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box">
                <div class="box-body">
                    <div class="form-group">
                        <label class="control-label">Default Start Command</label>
                        <div>
                            <textarea name="startup" class="form-control" rows="2">{{ old('startup') }}</textarea>
                            <p class="text-muted"><small>The default start command to use when running options under this service. This command can be modified per-option and should include the executable to be called in the container.</small></p>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="input" class="btn btn-primary pull-right">Save Service</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
