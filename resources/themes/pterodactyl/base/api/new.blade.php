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
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="user:*" name="permissions[]" type="checkbox" value="user:*">
                            <label for="user:*" class="strong">
                                <span class="label label-default">GET</span> @lang('base.api.new.base.information.title')
                            </label>
                        </div>
                        <p class="text-muted small">@lang('base.api.new.base.information.description')</p>
                    </div>
                </div>
            </div>
            @if(Auth::user()->isRootAdmin())
                <div class="box">
                    <div class="box-header with-border">
                        <div class="box-title">@lang('base.api.new.user_management.title')</div>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:users.list" name="adminPermissions[]" type="checkbox" value="admin:users.list">
                                <label for="admin:users.list" class="strong">
                                    <span class="label label-default">GET</span> @lang('base.api.new.user_management.list.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.user_management.list.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:users.create" name="adminPermissions[]" type="checkbox" value="admin:users.create">
                                <label for="admin:users.create" class="strong">
                                    <span class="label label-default">POST</span> @lang('base.api.new.user_management.create.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.user_management.create.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:users.view" name="adminPermissions[]" type="checkbox" value="admin:users.view">
                                <label for="admin:users.view" class="strong">
                                    <span class="label label-default">GET</span> @lang('base.api.new.user_management.view.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.user_management.view.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:users.update" name="adminPermissions[]" type="checkbox" value="admin:users.update">
                                <label for="admin:users.update" class="strong">
                                    <span class="label label-default">GET</span> @lang('base.api.new.user_management.update.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.user_management.update.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-danger no-margin-bottom">
                                <input id="admin:users.delete" name="adminPermissions[]" type="checkbox" value="admin:users.delete">
                                <label for="admin:users.delete" class="strong">
                                    <span class="label label-danger">DELETE</span> @lang('base.api.new.user_management.delete.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.user_management.delete.description')</p>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="box-header with-border">
                        <div class="box-title">@lang('base.api.new.node_management.title')</div>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:nodes.list" name="adminPermissions[]" type="checkbox" value="admin:nodes.list">
                                <label for="admin:nodes.list" class="strong">
                                    <span class="label label-default">GET</span> @lang('base.api.new.node_management.list.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.node_management.list.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:nodes.create" name="adminPermissions[]" type="checkbox" value="admin:nodes.create">
                                <label for="admin:nodes.create" class="strong">
                                    <span class="label label-default">POST</span> @lang('base.api.new.node_management.create.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.node_management.create.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:nodes.view" name="adminPermissions[]" type="checkbox" value="admin:nodes.view">
                                <label for="admin:nodes.view" class="strong">
                                    <span class="label label-default">GET</span> @lang('base.api.new.node_management.view.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.node_management.view.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:nodes.allocations" name="adminPermissions[]" type="checkbox" value="admin:nodes.allocations">
                                <label for="admin:nodes.allocations" class="strong">
                                    <span class="label label-default">GET</span> @lang('base.api.new.node_management.allocations.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.node_management.allocations.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-danger no-margin-bottom">
                                <input id="admin:nodes.delete" name="adminPermissions[]" type="checkbox" value="admin:nodes.delete">
                                <label for="admin:nodes.delete" class="strong">
                                    <span class="label label-danger">DELETE</span> @lang('base.api.new.node_management.delete.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.node_management.delete.description')</p>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="box-header with-border">
                        <div class="box-title">@lang('base.api.new.location_management.title')</div>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:locations.list" name="adminPermissions[]" type="checkbox" value="admin:locations.list">
                                <label for="admin:locations.list" class="strong">
                                    <span class="label label-default">GET</span> @lang('base.api.new.location_management.list.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.location_management.list.description')</p>
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
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="user:server" name="permissions[]" type="checkbox" value="user:server">
                            <label for="user:server" class="strong">
                                <span class="label label-default">GET</span> @lang('base.api.new.server_management.server.title')
                            </label>
                        </div>
                        <p class="text-muted small">@lang('base.api.new.server_management.server.description')</p>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="user:server.power" name="permissions[]" type="checkbox" value="user:server.power">
                            <label for="user:server.power" class="strong">
                                <span class="label label-default">POST</span> @lang('base.api.new.server_management.power.title')
                            </label>
                        </div>
                        <p class="text-muted small">@lang('base.api.new.server_management.power.description')</p>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="user:server.command" name="permissions[]" type="checkbox" value="user:server.command">
                            <label for="user:server.command" class="strong">
                                <span class="label label-default">POST</span> @lang('base.api.new.server_management.command.title')
                            </label>
                        </div>
                        <p class="text-muted small">@lang('base.api.new.server_management.command.description')</p>
                    </div>
                    @if(Auth::user()->isRootAdmin())
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:servers.view" name="adminPermissions[]" type="checkbox" value="admin:servers.view">
                                <label for="admin:servers.view" class="strong">
                                    <span class="label label-default">GET</span> @lang('base.api.new.server_management.view.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.server_management.view.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:servers.list" name="adminPermissions[]" type="checkbox" value="admin:servers.list">
                                <label for="admin:servers.list" class="strong">
                                    <span class="label label-default">GET</span> @lang('base.api.new.server_management.list.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.server_management.list.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:servers.create" name="adminPermissions[]" type="checkbox" value="admin:servers.create">
                                <label for="admin:servers.create" class="strong">
                                    <span class="label label-default">POST</span> @lang('base.api.new.server_management.create.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.server_management.create.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:servers.config" name="adminPermissions[]" type="checkbox" value="admin:servers.config">
                                <label for="admin:servers.config" class="strong">
                                    <span class="label label-default">PATCH</span> @lang('base.api.new.server_management.config.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.server_management.config.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:servers.build" name="adminPermissions[]" type="checkbox" value="admin:servers.build">
                                <label for="admin:servers.build" class="strong">
                                    <span class="label label-default">PATCH</span> @lang('base.api.new.server_management.build.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.server_management.build.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-warning no-margin-bottom">
                                <input id="admin:servers.suspend" name="adminPermissions[]" type="checkbox" value="admin:servers.suspend">
                                <label for="admin:servers.suspend" class="strong">
                                    <span class="label label-default">POST</span> @lang('base.api.new.server_management.suspend.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.server_management.suspend.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-warning no-margin-bottom">
                                <input id="admin:servers.unsuspend" name="adminPermissions[]" type="checkbox" value="admin:servers.unsuspend">
                                <label for="admin:servers.unsuspend" class="strong">
                                    <span class="label label-default">POST</span> @lang('base.api.new.server_management.unsuspend.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.server_management.unsuspend.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-danger no-margin-bottom">
                                <input id="admin:servers.delete" name="adminPermissions[]" type="checkbox" value="admin:servers.delete">
                                <label for="admin:servers.delete" class="strong">
                                    <span class="label label-danger">DELETE</span> @lang('base.api.new.server_management.delete.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.server_management.delete.description')</p>
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
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:services.list" name="adminPermissions[]" type="checkbox" value="admin:services.list">
                                <label for="admin:services.list" class="strong">
                                    <span class="label label-default">GET</span> @lang('base.api.new.service_management.list.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.service_management.list.description')</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="admin:services.view" name="adminPermissions[]" type="checkbox" value="admin:services.view">
                                <label for="admin:services.view" class="strong">
                                    <span class="label label-default">GET</span> @lang('base.api.new.service_management.view.title')
                                </label>
                            </div>
                            <p class="text-muted small">@lang('base.api.new.service_management.view.description')</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        {!! csrf_field() !!}
    </form>
</div>
@endsection
