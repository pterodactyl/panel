{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    @lang('server.files.edit.header')
@endsection

@section('content-header')
    <h1>@lang('server.files.edit.header')<small>@lang('server.files.edit.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li><a href="{{ route('server.files.index', $server->uuidShort) }}">@lang('navigation.server.file_browser')</a></li>
        <li class="active">@lang('navigation.server.edit_file')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $file }}</h3>
                <div class="pull-right box-tools">
                    <a href="/server/{{ $server->uuidShort }}/files#{{ rawurlencode($directory) }}" class="pull-right"><button class="btn btn-default btn-sm">@lang('server.files.edit.return')</button></a>
                </div>
            </div>
            <input type="hidden" name="file" value="{{ $file }}" />
            <textarea id="editorSetContent" class="hidden">{{ $contents }}</textarea>
            <div class="overlay" id="editorLoadingOverlay"><i class="fa fa-refresh fa-spin"></i></div>
            <div class="box-body" style="height:500px;" id="editor"></div>
            <div class="box-footer with-border">
                <button class="btn btn-sm btn-primary" id="save_file"><i class="fa fa-fw fa-save"></i> &nbsp;@lang('server.files.edit.save')</button>
                <a href="/server/{{ $server->uuidShort }}/files#{{ rawurlencode($directory) }}" class="pull-right"><button class="btn btn-default btn-sm">@lang('server.files.edit.return')</button></a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
    {!! Theme::js('vendor/ace/ace.js') !!}
    {!! Theme::js('vendor/ace/ext-modelist.js') !!}
    {!! Theme::js('vendor/ace/ext-whitespace.js') !!}
    {!! Theme::js('js/frontend/files/editor.js') !!}
    <script>
        $(document).ready(function () {
            Editor.setValue($('#editorSetContent').val(), -1);
            $('#editorLoadingOverlay').hide();
        });
    </script>
@endsection
