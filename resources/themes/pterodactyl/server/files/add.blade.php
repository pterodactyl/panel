{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    @lang('server.files.add.header')
@endsection

@section('scripts')
    {{-- This has to be loaded before the AdminLTE theme to avoid dropdown issues. --}}
    {!! Theme::css('vendor/select2/select2.min.css') !!}
    @parent
@endsection

@section('content-header')
    <h1>@lang('server.files.add.header')<small>@lang('server.files.add.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li><a href="{{ route('server.files.index', $server->uuidShort) }}">@lang('navigation.server.file_browser')</a></li>
        <li class="active">@lang('navigation.server.create_file')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <div class="input-group">
                    <span class="input-group-addon"><code>/home/container/</code></span>
                    <input type="text" class="form-control" placeholder="@lang('server.files.add.name')" id="file_name" value="{{ $directory }}">
                </div>
            </div>
            <div class="box-body" style="height:500px;" id="editor"></div>
            <div class="box-footer with-border">
                <div class="row">
                    <div class="col-sm-8">
                        <button class="btn btn-sm btn-primary" id="create_file">@lang('server.files.add.create')</button>
                        <a href="{{ route('server.files.index', [ 'server' => $server->uuidShort, 'dir' => $directory ]) }}"><button class="btn btn-default btn-sm">@lang('strings.cancel')</button></a>
                    </div>
                    <div class="col-sm-4">
                        <select name="aceMode" id="aceMode" class="form-control">
                            <option value="assembly_x86">Assembly x86</option>
                            <option value="c_cpp">C/C++</option>
                            <option value="coffee">CoffeeScript</option>
                            <option value="csharp">C#</option>
                            <option value="css">CSS</option>
                            <option value="golang">Go</option>
                            <option value="haml">HAML</option>
                            <option value="html">HTML</option>
                            <option value="ini">INI</option>
                            <option value="java">Java</option>
                            <option value="javascript">JavaScript</option>
                            <option value="json">JSON</option>
                            <option value="kotlin">Kotlin</option>
                            <option value="lua">Lua</option>
                            <option value="markdown">Markdown</option>
                            <option value="mysql">MySQL</option>
                            <option value="objectivec">Objective-C</option>
                            <option value="perl">Perl</option>
                            <option value="php">PHP</option>
                            <option value="plain_text" selected="selected">Plain Text</option>
                            <option value="properties">Properties</option>
                            <option value="python">Python</option>
                            <option value="ruby">Ruby</option>
                            <option value="rust">Rust</option>
                            <option value="smarty">Smarty</option>
                            <option value="sql">SQL</option>
                            <option value="xml">XML</option>
                            <option value="yaml">YAML</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/select2/select2.full.min.js') !!}
    {!! Theme::js('js/frontend/server.socket.js') !!}
    {!! Theme::js('vendor/ace/ace.js') !!}
    {!! Theme::js('vendor/ace/ext-modelist.js') !!}
    {!! Theme::js('vendor/ace/ext-whitespace.js') !!}
    {!! Theme::js('vendor/lodash/lodash.js') !!}
    {!! Theme::js('js/frontend/files/editor.js') !!}
    <script>
        $(document).ready(function() {
            $('#aceMode').select2();
        });
    </script>
@endsection
