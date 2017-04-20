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
    Services &rarr; {{ $service->name }} &rarr; Functions
@endsection

@section('content-header')
    <h1>{{ $service->name }}<small>Extend the default daemon functions using this service file.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.services') }}">Services</a></li>
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
            <form action="{{ route('admin.services.view', $service->id) }}" method="POST">
                <div class="box-body no-padding">
                    <div id="editor_index"style="height:500px">{{ $service->index_file }}</div>
                    <textarea name="index_file" class="hidden"></textarea>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <input type="hidden" name="redirect_to" value="functions" />
                    <button type="submit" name="action" value="edit" class="btn btn-sm btn-success pull-right">Save File</button>
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
