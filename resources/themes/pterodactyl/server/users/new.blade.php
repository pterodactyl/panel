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
@extends('layouts.master')

@section('title')
    @lang('server.users.new.header')
@endsection

@section('content-header')
    <h1>@lang('server.users.new.header')<small>@lang('server.users.new.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li><a href="{{ route('server.subusers', $server->uuidShort) }}">@lang('navigation.server.subusers')</a></li>
        <li class="active">@lang('server.users.add')</li>
    </ol>
@endsection

@section('content')
<?php $oldInput = array_flip(is_array(old('permissions')) ? old('permissions') : []) ?>
<form action="{{ route('server.subusers.new', $server->uuidShort) }}" method="POST">
    <div class="row">
        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <div class="form-group">
                        <label class="control-label">@lang('server.users.new.email')</label>
                        <div>
                            {!! csrf_field() !!}
                            <input type="email" class="form-control" name="email" />
                            <p class="text-muted small">@lang('server.users.new.email_help')</p>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div class="btn-group pull-left">
                        <a id="selectAllCheckboxes" class="btn btn-sm btn-default">@lang('strings.select_all')</a>
                        <a id="unselectAllCheckboxes" class="btn btn-sm btn-default">@lang('strings.select_none')</a>
                    </div>
                    <input type="submit" name="submit" value="@lang('server.users.add')" class="pull-right btn btn-sm btn-primary" />
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        @foreach($permissions as $block => $perms)
            <div class="col-sm-6">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('server.users.new.' . $block . '_header')</h3>
                    </div>
                    <div class="box-body">
                        @foreach($perms as $permission => $daemon)
                            <div class="form-group">
                                <div class="checkbox checkbox-primary no-margin-bottom">
                                    <input id="{{ $permission }}" name="permissions[]" type="checkbox" value="{{ $permission }}"/>
                                    <label for="{{ $permission }}" class="strong">
                                        @lang('server.users.new.' . str_replace('-', '_', $permission) . '.title')
                                    </label>
                                </div>
                                <p class="text-muted small">@lang('server.users.new.' . str_replace('-', '_', $permission) . '.description')</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @if ($loop->iteration % 2 === 0)
                <div class="clearfix visible-lg-block visible-md-block visible-sm-block"></div>
            @endif
        @endforeach
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
    <script type="text/javascript">
        $(document).ready(function () {
            $('#selectAllCheckboxes').on('click', function () {
                $('input[type=checkbox]').prop('checked', true);
            });
            $('#unselectAllCheckboxes').on('click', function () {
                $('input[type=checkbox]').prop('checked', false);
            });
        })
    </script>
@endsection
