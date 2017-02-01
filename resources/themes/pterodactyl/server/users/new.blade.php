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
        <div class="col-sm-6">
            {{-- Left Side --}}
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.power_header')</h3>
                        </div>
                        <div class="box-body">
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['power-start']))checked="checked"@endif value="power-start" />
                                    <strong>@lang('server.users.new.start.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.start.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['power-stop']))checked="checked"@endif value="power-stop" />
                                    <strong>@lang('server.users.new.stop.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.stop.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['power-restart']))checked="checked"@endif value="power-restart" />
                                    <strong>@lang('server.users.new.restart.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.restart.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['power-kill']))checked="checked"@endif value="power-kill" />
                                    <strong>@lang('server.users.new.kill.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.kill.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['send-command']))checked="checked"@endif value="send-command" />
                                    <strong>@lang('server.users.new.command.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.command.description')</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.subuser_header')</h3>
                        </div>
                        <div class="box-body">
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['list-subusers']))checked="checked"@endif value="list-subusers" />
                                    <strong>@lang('server.users.new.list_subusers.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.list_subusers.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['view-subuser']))checked="checked"@endif value="view-subuser" />
                                    <strong>@lang('server.users.new.view_subuser.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.view_subuser.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['edit-subuser']))checked="checked"@endif value="edit-subuser" />
                                    <strong>@lang('server.users.new.edit_subuser.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.edit_subuser.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['create-subuser']))checked="checked"@endif value="create-subuser" />
                                    <strong>@lang('server.users.new.create_subuser.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.create_subuser.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['delete-subuser']))checked="checked"@endif value="delete-subuser" />
                                    <strong>@lang('server.users.new.delete_subuser.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.delete_subuser.description')</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.server_header')</h3>
                        </div>
                        <div class="box-body">
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['set-connection']))checked="checked"@endif value="set-connection" />
                                    <strong>@lang('server.users.new.set_connection.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.set_connection.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['view-startup']))checked="checked"@endif value="view-startup" />
                                    <strong>@lang('server.users.new.view_startup.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.view_startup.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['edit-startup']))checked="checked"@endif value="edit-startup" />
                                    <strong>@lang('server.users.new.edit_startup.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.edit_startup.description')</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.sftp_header')</h3>
                        </div>
                        <div class="box-body">
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['view-sftp']))checked="checked"@endif value="view-sftp" />
                                    <strong>@lang('server.users.new.view_sftp.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.view_sftp.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['view-sftp-password']))checked="checked"@endif value="view-sftp-password" />
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    <strong>@lang('server.users.new.view_sftp_password.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.view_sftp_password.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['reset-sftp']))checked="checked"@endif value="reset-sftp" />
                                    <strong>@lang('server.users.new.reset_sftp.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.reset_sftp.description')</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            {{-- Right Side --}}
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.file_header')</h3>
                        </div>
                        <div class="box-body">
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['list-files']))checked="checked"@endif value="list-files" />
                                    <strong>@lang('server.users.new.list_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.list_files.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['edit-files']))checked="checked"@endif value="edit-files" />
                                    <strong>@lang('server.users.new.edit_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.edit_files.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['save-files']))checked="checked"@endif value="save-files" />
                                    <strong>@lang('server.users.new.save_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.save_files.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['move-files']))checked="checked"@endif value="move-files" />
                                    <strong>@lang('server.users.new.move_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.move_files.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['copy-files']))checked="checked"@endif value="copy-files" />
                                    <strong>@lang('server.users.new.copy_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.copy_files.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['compress-files']))checked="checked"@endif value="compress-files" />
                                    <strong>@lang('server.users.new.compress_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.compress_files.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['decompress-files']))checked="checked"@endif value="decompress-files" />
                                    <strong>@lang('server.users.new.decompress_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.decompress_files.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['create-files']))checked="checked"@endif value="create-files" />
                                    <strong>@lang('server.users.new.create_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.create_files.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['upload-files']))checked="checked"@endif value="upload-files" />
                                    <strong>@lang('server.users.new.upload_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.upload_files.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['delete-files']))checked="checked"@endif value="delete-files" />
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    <strong>@lang('server.users.new.delete_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.delete_files.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['download-files']))checked="checked"@endif value="download-files" />
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    <strong>@lang('server.users.new.download_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.download_files.description')</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.task_header')</h3>
                        </div>
                        <div class="box-body">
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['list-tasks']))checked="checked"@endif value="list-tasks" />
                                    <strong>@lang('server.users.new.list_tasks.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.list_tasks.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['view-task']))checked="checked"@endif value="view-task" />
                                    <strong>@lang('server.users.new.view_task.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.view_task.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['toggle-task']))checked="checked"@endif value="toggle-task" />
                                    <strong>@lang('server.users.new.toggle_task.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.toggle_task.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['queue-task']))checked="checked"@endif value="queue-task" />
                                    <strong>@lang('server.users.new.queue_task.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.queue_task.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['create-task']))checked="checked"@endif value="create-task" />
                                    <strong>@lang('server.users.new.create_task.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.create_task.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['delete-task']))checked="checked"@endif value="delete-task" />
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    <strong>@lang('server.users.new.delete_task.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.delete_task.description')</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.db_header')</h3>
                        </div>
                        <div class="box-body">
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['view-databases']))checked="checked"@endif value="view-databases" />
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    <strong>@lang('server.users.new.view_databases.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.view_databases.description')</p>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input name="permissions[]" type="checkbox" @if(isset($oldInput['reset-db-password']))checked="checked"@endif value="reset-db-password" />
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    <strong>@lang('server.users.new.reset_db_password.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.reset_db_password.description')</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
