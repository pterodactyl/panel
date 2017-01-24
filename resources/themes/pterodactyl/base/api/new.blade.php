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
<div class="row">
    <form action="{{ route('account.api.new') }}" method="POST" id="permsForm">
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
                        <div class="col-xs-6">
                            <div class="btn-group">
                                <a id="selectAllCheckboxes" class="btn btn-default">@lang('strings.select_all')</a>
                                <a id="unselectAllCheckboxes" class="btn btn-default">@lang('strings.select_none')</a>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <button class="btn btn-success pull-right">@lang('strings.create') &rarr;</button>
                        </div>
                    </div>
                    <div class="text-right">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-lg-6">
            <div class="box">
                <div class="box-header with-border">
                    <div class="box-title">@lang('base.api.new.base.title')</div>
                </div>
                <div class="box-body">
                    <div class="checkbox">
                        <label>
                            <input name="permissions[]" type="checkbox" value="user:*">
                            <span class="label label-default">GET</span>
                            <strong>@lang('base.api.new.base.information.title')</strong>
                            <p class="text-muted small">
                                @lang('base.api.new.base.information.description')
                            </p>
                        </label>
                    </div>
                </div>
            </div>
            @if(Auth::user()->isRootAdmin())
                <div class="box">
                    <div class="box-header with-border">
                        <div class="box-title">@lang('base.api.new.user_management.title')</div>
                    </div>
                    <div class="box-body">
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:users.list">
                                <span class="label label-default">GET</span>
                                <strong>@lang('base.api.new.user_management.list.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.user_management.list.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:users.create">
                                <span class="label label-default">POST</span>
                                <strong>@lang('base.api.new.user_management.create.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.user_management.create.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:users.view">
                                <span class="label label-default">GET</span>
                                <strong>@lang('base.api.new.user_management.view.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.user_management.view.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:users.update">
                                <span class="label label-default">GET</span>
                                <strong>@lang('base.api.new.user_management.update.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.user_management.update.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:users.delete">
                                <span class="label label-danger">DELETE</span>
                                <strong>@lang('base.api.new.user_management.delete.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.user_management.delete.description')
                                </p>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="box-header with-border">
                        <div class="box-title">@lang('base.api.new.node_management.title')</div>
                    </div>
                    <div class="box-body">
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:nodes.list">
                                <span class="label label-default">GET</span>
                                <strong>@lang('base.api.new.node_management.list.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.node_management.list.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:nodes.create">
                                <span class="label label-default">POST</span>
                                <strong>@lang('base.api.new.node_management.create.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.node_management.create.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:nodes.view">
                                <span class="label label-default">GET</span>
                                <strong>@lang('base.api.new.node_management.view.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.node_management.view.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:nodes.allocations">
                                <span class="label label-default">GET</span>
                                <strong>@lang('base.api.new.node_management.allocations.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.node_management.allocations.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:nodes.delete">
                                <span class="label label-danger">DELETE</span>
                                <strong>@lang('base.api.new.node_management.delete.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.node_management.delete.description')
                                </p>
                            </label>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-xs-12 col-lg-6">
            <div class="box">
                <div class="box-header with-border">
                    <div class="box-title">@lang('base.api.new.server_management.title')</div>
                </div>
                <div class="box-body">
                    <div class="checkbox">
                        <label>
                            <input name="permissions[]" type="checkbox" value="user:server">
                            <span class="label label-default">GET</span>
                            <strong>@lang('base.api.new.server_management.server.title')</strong>
                            <p class="text-muted small">
                                @lang('base.api.new.server_management.server.description')
                            </p>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input name="permissions[]" type="checkbox" value="user:server.power">
                            <span class="label label-default">GET</span>
                            <strong>@lang('base.api.new.server_management.power.title')</strong>
                            <p class="text-muted small">
                                @lang('base.api.new.server_management.power.description')
                            </p>
                        </label>
                    </div>
                    @if(Auth::user()->isRootAdmin())
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:servers.view">
                                <span class="label label-default">GET</span>
                                <strong>@lang('base.api.new.server_management.view.title')</strong>
                                <p class="text-muted small">
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    @lang('base.api.new.server_management.view.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:servers.list">
                                <span class="label label-default">GET</span>
                                <strong>@lang('base.api.new.server_management.list.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.server_management.list.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:servers.create">
                                <span class="label label-default">POST</span>
                                <strong>@lang('base.api.new.server_management.create.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.server_management.create.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:servers.config">
                                <span class="label label-default">PATCH</span>
                                <strong>@lang('base.api.new.server_management.config.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.server_management.config.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:servers.build">
                                <span class="label label-default">PATCH</span>
                                <strong>@lang('base.api.new.server_management.build.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.server_management.build.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:servers.suspend">
                                <span class="label label-default">POST</span>
                                <strong>@lang('base.api.new.server_management.suspend.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.server_management.suspend.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:servers.unsuspend">
                                <span class="label label-default">POST</span>
                                <strong>@lang('base.api.new.server_management.unsuspend.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.server_management.unsuspend.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:servers.delete">
                                <span class="label label-danger">DELETE</span>
                                <strong>@lang('base.api.new.server_management.delete.title')</strong>
                                <p class="text-muted small">
                                    @lang('base.api.new.server_management.delete.description')
                                </p>
                            </label>
                        </div>
                    @endif
                </div>
            </div>
            @if(Auth::user()->isRootAdmin())
                <div class="box">
                    <div class="box-header with-border">
                        <div class="box-title">@lang('base.api.new.service_management.title')</div>
                    </div>
                    <div class="box-body">
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:services.list">
                                <span class="label label-default">GET</span>
                                <strong>@lang('base.api.new.service_management.list.title')</strong>
                                <p class="text-muted small">
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    @lang('base.api.new.service_management.list.description')
                                </p>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:services.view">
                                <span class="label label-default">GET</span>
                                <strong>@lang('base.api.new.service_management.view.title')</strong>
                                <p class="text-muted small">
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    @lang('base.api.new.service_management.view.description')
                                </p>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="box-header with-border">
                        <div class="box-title">@lang('base.api.new.location_management.title')</div>
                    </div>
                    <div class="box-body">
                        <div class="checkbox">
                            <label>
                                <input name="adminPermissions[]" type="checkbox" value="admin:locations.list">
                                <span class="label label-default">GET</span>
                                <strong>@lang('base.api.new.location_management.list.title')</strong>
                                <p class="text-muted small">
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    @lang('base.api.new.location_management.list.description')
                                </p>
                            </label>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        {!! csrf_field() !!}
    </form>
</div>
@endsection
