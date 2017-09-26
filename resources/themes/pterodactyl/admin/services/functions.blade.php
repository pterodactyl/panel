{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    Service &rarr; {{ $service->name }} &rarr; Functions
@endsection

@section('content-header')
    <h1>{{ $service->name }}<small>Extend the default daemon functions using this service file.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.services') }}">Service</a></li>
        <li><a href="{{ route('admin.services.view', $service->id) }}">{{ $service->name }}</a></li>
        <li class="active">Functions</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.services.view', $service->id) }}">Overview</a></li>
                <li class="active"><a href="{{ route('admin.services.view.functions', $service->id) }}">Functions</a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Functions Control</h3>
            </div>
            <form action="{{ route('admin.services.view.functions', $service->id) }}" method="POST">
                <div class="box-body no-padding">
                    <div id="editor_index"style="height:500px">{{ $service->index_file }}</div>
                    <textarea name="index_file" class="hidden"></textarea>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-success pull-right">Save File</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/ace/ace.js') !!}
    {!! Theme::js('vendor/ace/ext-modelist.js') !!}
    <script>
    $(document).ready(function () {
        const Editor = ace.edit('editor_index');
        const Modelist = ace.require('ace/ext/modelist')

        Editor.setTheme('ace/theme/chrome');
        Editor.getSession().setMode('ace/mode/javascript');
        Editor.getSession().setUseWrapMode(true);
        Editor.setShowPrintMargin(false);

        $('form').on('submit', function (e) {
            $('textarea[name="index_file"]').val(Editor.getValue());
        });
    });
    </script>
@endsection
