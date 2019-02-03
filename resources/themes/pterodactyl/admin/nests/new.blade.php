{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/nests.new.header.title')
@endsection

@section('content-header')
    <h1>@lang('admin/nests.new.header.title')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/nests.new.header.admin')</a></li>
        <li><a href="{{ route('admin.nests') }}">@lang('admin/nests.new.header.nests')</a></li>
        <li class="active">@lang('admin/nests.new.header.new')</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.nests.new') }}" method="POST">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/nests.new.content.new_nest')</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label class="control-label">@lang('admin/nests.content.name')</label>
                        <div>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" />
                            <p class="text-muted">@lang('admin/nests.new.content.name_description')</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">@lang('admin/nests.new.content.description')</label>
                        <div>
                            <textarea name="description" class="form-control" rows="6">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-primary pull-right">@lang('admin/nests.new.content.save')</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
