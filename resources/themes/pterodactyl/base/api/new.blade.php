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
    @lang('base.api.new.header')
@endsection

@section('content-header')
    <h1>@lang('base.api.new.header')<small>@lang('base.api.new.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('account.api') }}">@lang('navigation.account.api_access')</a></li>
        <li class="active">@lang('strings.new')</li>
    </ol>
@endsection

@section('footer-scripts')
    @parent
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

@section('content')
<form action="{{ route('account.api.new') }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <div class="box-title">@lang('base.api.new.form_title')</div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-xs-12 col-lg-6">
                            <label>@lang('base.api.new.descriptive_memo.title')</label>
                            <input type="text" name="memo" class="form-control" name />
                            <p class="help-block">@lang('base.api.new.descriptive_memo.description')</p>
                        </div>
                        <div class="form-group col-xs-12 col-lg-6">
                            <label>@lang('base.api.new.allowed_ips.title')</label>
                            <textarea name="allowed_ips" class="form-control" name></textarea>
                            <p class="help-block">@lang('base.api.new.allowed_ips.description')</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-xs-8">
                            <div class="btn-group">
                                <a id="selectAllCheckboxes" class="btn btn-default">@lang('strings.select_all')</a>
                                <a id="unselectAllCheckboxes" class="btn btn-default">@lang('strings.select_none')</a>
                            </div>
                        </div>
                        <div class="col-md-6 col-xs-4">
                            {!! csrf_field() !!}
                            <button class="btn btn-success pull-right">@lang('strings.create') &rarr;</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        @foreach($permissions['user'] as $block => $perms)
            <div class="col-sm-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('base.api.permissions.user.' . $block . '_header')</h3>
                    </div>
                    <div class="box-body">
                        @foreach($perms as $permission)
                            <div class="form-group">
                                <div class="checkbox checkbox-primary no-margin-bottom">
                                    <input id="{{ 'user.' . $block . '-' . $permission }}" name="permissions[]" type="checkbox" value="{{ $block . '-' . $permission }}"/>
                                    <label for="{{ 'user.' . $block . '-' . $permission }}" class="strong">
                                        @lang('base.api.permissions.user.' . $block . '.' . $permission . '.title')
                                    </label>
                                </div>
                                <p class="text-muted small">@lang('base.api.permissions.user.' . $block . '.' . $permission . '.desc')</p>
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
    <div class="row">
        @foreach($permissions['admin'] as $block => $perms)
            <div class="col-lg-4 col-sm-6">
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('base.api.permissions.admin.' . $block . '_header')</h3>
                    </div>
                    <div class="box-body">
                        @foreach($perms as $permission)
                            <div class="form-group">
                                <div class="checkbox {{ $permission === 'delete' ? 'checkbox-danger' : 'checkbox-primary' }} no-margin-bottom">
                                    <input id="{{ $block . '-' . $permission }}" name="admin_permissions[]" type="checkbox" value="{{ $block . '-' . $permission }}"/>
                                    <label for="{{ $block . '-' . $permission }}" class="strong">
                                        @lang('base.api.permissions.admin.' . $block . '.' . $permission . '.title')
                                    </label>
                                </div>
                                <p class="text-muted small">@lang('base.api.permissions.admin.' . $block . '.' . $permission . '.desc')</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @if ($loop->iteration % 3 === 0)
                <div class="clearfix visible-lg-block"></div>
            @endif
            @if ($loop->iteration % 2 === 0)
                <div class="clearfix visible-md-block visible-sm-block"></div>
            @endif
        @endforeach
    </div>
</form>
@endsection
