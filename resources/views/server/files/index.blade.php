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
@extends('layouts.master')

@section('title')
    Managing Files for: {{ $server->name }}
@endsection

@section('scripts')
    @parent
    {!! Theme::js('js/vendor/async/async.min.js') !!}
    {!! Theme::js('js/vendor/lodash/lodash.js') !!}
@endsection

@section('content')
<div class="col-md-12">
    <div class="row">
        <div class="col-md-12" id="internal_alert">
            <div class="alert alert-info">
                <i class="fa fa-spinner fa-spin"></i> {{ trans('server.files.loading') }}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="ajax_loading_box"><i class="fa fa-refresh fa-spin" id="position_me"></i></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12" id="load_files"></div>
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">File Path Information</h3>
                </div>
                <div class="panel-body">
                    When configuring any file paths in your server plugins or settings you should use <code>/home/container</code> as your base path. While your SFTP client sees the files as <code>/public</code> this is not true for the server process.
                </div>
            </div>
        </div>
    </div>
    <ul id="fileOptionMenu" class="dropdown-menu" role="menu" style="display:none" >
        <li data-action="move"><a tabindex="-1" href="#"><i class="fa fa-arrow-right"></i> Move</a></li>
        <li data-action="rename"><a tabindex="-1" href="#"><i class="fa fa-pencil-square-o"></i> Rename</a></li>
        <li><a tabindex="-1" href="#"><i class="fa fa-file-archive-o"></i> Compress</a></li>
        <li class="divider"></li>
        <li><a tabindex="-1" href="#"><i class="fa fa-download"></i> Download</a></li>
        <li><a tabindex="-1" href="#"><i class="fa fa-trash-o"></i> Delete</a></li>
    </ul>
</div>
<script src="{{ route('server.js', [$server->uuidShort, 'filemanager', 'index.js']) }}"></script>
<script src="{{ route('server.js', [$server->uuidShort, 'filemanager', 'actions.js']) }}"></script>
<script src="{{ route('server.js', [$server->uuidShort, 'filemanager', 'contextmenu.js']) }}"></script>
<script>
$(window).load(function () {
    $('.server-files').addClass('active');
});
</script>
@endsection
